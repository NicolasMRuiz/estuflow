import { test, expect, chromium } from '@playwright/test';
import { mkdtemp, writeFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { account, register, capturedLink, openSecurityLink, expectPath, logout, login, cleanScreenshot, assertNoHorizontalScroll, viewports } from './support/auth.js';
import { choosePlan, confirmDraft, configureAcademic } from './support/academic.js';

const captures = viewports.filter(viewport => [390, 1440, 2560, 3840].includes(viewport.width));
const hygiene = 'Higiene, Seguridad y Ambiente Laboral';
const practice = 'Práctica Profesionalizante 3: Diseño de Proyectos en Higiene, Seguridad y Ambiente Laboral';
const row = (page, name) => page.locator('.course-row').filter({ has: page.locator('h3').filter({ hasText: new RegExp(`^${name}$`) }) });
const year = (page, number) => page.locator(`[data-year="${number}"]`);
const edit = (page, name) => page.getByRole('link', { name: `Editar estado de ${name}`, exact: true });

async function inspect(page, screen) {
    for (const viewport of [...captures, { width: 320, height: 740 }, { width: 640, height: 360 }]) {
        await page.setViewportSize(viewport);
        await page.evaluate(() => window.scrollTo(0, 0));
        await assertNoHorizontalScroll(page);
        if ([390, 1440, 2560, 3840].includes(viewport.width)) await cleanScreenshot(page, `docs/evidence/m03-correction/${screen}-${viewport.width}x${viewport.height}.png`);
    }
    await page.setViewportSize({ width: 1440, height: 900 });
}

test('configuración, carga por año y edición contextual; cambio cancelado, interrumpido y confirmado', async ({ page, request }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.name));
    const person = await register(page, account('Valentina Prueba'));
    await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
    await expect(page.locator('input[type="radio"]:checked')).toHaveCount(0);
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await expect(page.locator('#plan-error')).toHaveText('Elegí una carrera para continuar.');
    await expect(page.getByRole('radio').first()).toBeFocused();
    await expect(page.getByText(/validation\./)).toHaveCount(0);
    await inspect(page, 'elegir-plan');
    await choosePlan(page, hygiene);
    await expect(year(page, 1)).toHaveAttribute('open', '');
    await expect(year(page, 2)).not.toHaveAttribute('open');
    const first = row(page, 'Administración de las Organizaciones en Seguridad');
    await expect(first.locator('[data-method-select]')).toBeHidden();
    await year(page, 1).locator('[data-bulk-state]').selectOption('approved');
    await page.getByRole('button', { name: 'Aplicar al año 1', exact: true }).click();
    await expect(first.locator('[data-status-select]')).toHaveValue('approved');
    await expect(first.locator('[data-method-select]')).toHaveValue('unspecified');
    await page.getByRole('button', { name: 'Deshacer', exact: true }).click();
    await expect(first.locator('[data-status-select]')).toHaveValue('pending');
    await expect(first.locator('[data-method-select]')).toBeHidden();
    await first.locator('[data-status-select]').selectOption('approved');
    await first.locator('[data-method-select]').selectOption('promotion');
    await row(page, 'Seguridad 1').locator('[data-status-select]').selectOption('in_progress');
    await row(page, 'Práctica Profesionalizante 1: Aproximación al Campo Laboral').locator('[data-status-select]').selectOption('regularized');
    await year(page, 2).locator('summary').click();
    await row(page, 'Ergonomía').locator('[data-status-select]').selectOption('regularized');
    await year(page, 2).locator('summary').click();
    await page.getByRole('button', { name: 'Guardar borrador', exact: true }).click();
    await expect(page.getByText('Borrador guardado. Podés retomarlo cuando quieras.')).toBeVisible();
    await expect(row(page, 'Ergonomía').locator('[data-status-select]')).toHaveValue('regularized');
    await logout(page); await login(page, person);
    await expectPath(page, '/configuracion-academica/materias');
    await expect(first.locator('[data-method-select]')).toHaveValue('promotion');
    await inspect(page, 'situacion-inicial');
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await page.getByRole('link', { name: 'Volver a editar', exact: true }).click();
    await expect(first.locator('[data-method-select]')).toHaveValue('promotion');
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await inspect(page, 'resumen');
    await expect(page.locator('input[type="checkbox"]')).toHaveCount(0);
    await page.getByRole('button', { name: 'Confirmar y empezar' }).click();
    await expectPath(page, '/dashboard');
    await expect(page.locator('.account-avatar')).toHaveText('V');
    await expect(page.locator('.dashboard-grid .panel-heading h2')).toHaveText(['Avance de carrera', 'Próximas actividades', 'Materias en curso', 'Correlativas']);
    await expect(page.getByRole('img', { name: /4 por ciento, 1 de 24/ })).toBeVisible();
    await expect(page.getByText('TSHSA-INGRESO-2026')).toHaveCount(0);
    await inspect(page, 'dashboard');
    await page.getByRole('link', { name: 'Ver todas (1)', exact: true }).click();
    await expect(page.locator('.course-list>li')).toHaveCount(1);
    await edit(page, 'Seguridad 1').focus();
    await page.keyboard.press('Enter');
    await expect(page.locator('.inline-editor')).toBeVisible();
    await page.keyboard.press('Tab');
    await expect(page.locator('.inline-editor [name="status"]')).toBeFocused();
    await page.locator('.inline-editor [name="status"]').selectOption('approved');
    await page.locator('.inline-editor').getByRole('button', { name: 'Cancelar', exact: true }).click();
    await expect(page.locator('.inline-editor')).toHaveCount(0);
    await expect(edit(page, 'Seguridad 1')).toBeFocused();
    await expect(page.locator('.course-list .state-tag')).toHaveText('Cursando');
    await edit(page, 'Seguridad 1').click();
    await page.locator('.inline-editor [name="status"]').selectOption('regularized');
    await expect(page.locator('.inline-editor [name="status"]')).toHaveValue('regularized');
    await page.getByRole('button', { name: 'Guardar cambios', exact: true }).click();
    await expect(page.getByText('Guardamos los cambios. La materia ya no coincide con el filtro elegido.')).toBeVisible();
    await expect(page.locator('.course-list>li')).toHaveCount(0);
    expect(new URL(page.url()).searchParams.get('status')).toBe('in_progress');
    await expect(page.locator('#resultados')).toBeFocused();
    await page.getByRole('link', { name: 'Limpiar filtros', exact: true }).click();
    await inspect(page, 'mi-carrera');
    await page.getByRole('link', { name: `Ver detalle de ${practice}`, exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Las demás materias del plan' })).toBeVisible();
    await expect(page.getByText('Celda vacía en esta columna del PDF.')).toHaveCount(0);
    await inspect(page, 'detalle-practica');
    await page.getByRole('link', { name: 'Volver a Mi carrera' }).click();
    await edit(page, 'Administración de las Organizaciones en Seguridad').click();
    await page.locator('.inline-editor [name="status"]').selectOption('regularized');
    await page.getByRole('button', { name: 'Guardar cambios', exact: true }).click();
    await page.reload();
    await edit(page, 'Administración de las Organizaciones en Seguridad').click();
    await expect(page.locator('.inline-editor [name="approval_method"]')).toHaveValue('unspecified');
    await expect(page.locator('.inline-editor [name="approval_method"]')).toBeHidden();
    await page.goto('/dashboard');
    await expect(page.getByText('0 de 24 materias aprobadas')).toBeVisible();
    await page.goto('/configuracion-academica'); await choosePlan(page, 'Enfermería');
    await row(page, 'Psicología').locator('[data-status-select]').selectOption('approved');
    await row(page, 'Psicología').locator('[data-method-select]').selectOption('promotion');
    await page.getByRole('button', { name: 'Guardar borrador', exact: true }).click();
    await page.goto('/dashboard');
    await expect(page.locator('.intro-strip strong')).toHaveText(hygiene);
    await logout(page); await login(page, person);
    await expectPath(page, '/dashboard');
    await page.goto('/configuracion-academica/materias');
    await expect(row(page, 'Psicología').locator('[data-method-select]')).toHaveValue('promotion');
    await page.getByRole('button', { name: 'Cancelar cambio', exact: true }).click();
    await expectPath(page, '/mi-carrera');
    await expect(page.getByRole('heading', { name: hygiene, exact: true })).toBeVisible();
    await page.goto('/configuracion-academica'); await choosePlan(page, 'Enfermería');
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await page.getByRole('button', { name: 'Cancelar cambio', exact: true }).click();
    await page.goto('/configuracion-academica');
    await page.getByRole('button', { name: 'Cancelar cambio', exact: true }).click();
    await expectPath(page, '/mi-carrera');
    await page.goto('/configuracion-academica'); await choosePlan(page, 'Enfermería');
    await confirmDraft(page);
    await expect(page.locator('.intro-strip strong')).toHaveText('Enfermería');
    await expect(page.getByText('1 de 24 materias aprobadas')).toBeVisible();
    await page.goto('/configuracion-academica'); await choosePlan(page, hygiene);
    await expect(first.locator('[data-status-select]')).toHaveValue('regularized');
    await confirmDraft(page);
    await page.goto('/configuracion-academica');
    await page.getByRole('radio', { name: new RegExp(hygiene) }).check();
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await expectPath(page, '/mi-carrera');
    await page.goto('/dashboard');
    await expect(page.getByText('0 de 24 materias aprobadas')).toBeVisible();
    expect(errors).toEqual([]);
});

test('cuenta existente sin JavaScript: configuración y edición con filtros conservados', async ({ browser, request }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, baseURL: 'http://test-app:8000', viewport: { width: 390, height: 844 } });
    const page = await context.newPage();
    try {
        const person = await register(page, account('Persona Sin JavaScript'));
        await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
        await logout(page); await login(page, person); await configureAcademic(page);
        await page.goto('/mi-carrera?year=1&status=pending');
        await edit(page, 'Inglés I').click();
        await page.locator('[name="status"]').selectOption('approved');
        await page.locator('[name="approval_method"]').selectOption('promotion');
        await page.getByRole('button', { name: 'Guardar cambios' }).click();
        await expectPath(page, '/mi-carrera');
        expect(new URL(page.url()).searchParams.get('year')).toBe('1');
        expect(new URL(page.url()).searchParams.get('status')).toBe('pending');
        await expect(page.getByText('Guardamos los cambios. La materia ya no coincide con el filtro elegido.')).toBeVisible();
        await expect(page.locator('.course-list>li')).toHaveCount(7);
        await logout(page); await login(page, person);
        await expectPath(page, '/dashboard');
        await expect(page.getByText('1 de 23 materias aprobadas')).toBeVisible();
        await assertNoHorizontalScroll(page);
    } finally { await context.close(); }
});

test('anillo real y conteos en cero, avance intermedio y carrera aprobada', async ({ page, request }) => {
    const person = await register(page, account('Persona Avance'));
    await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
    await configureAcademic(page);
    await expect(page.locator('.ring-value')).toHaveAttribute('stroke-dasharray', '0 100');
    await cleanScreenshot(page, 'docs/evidence/m03-correction/avance-0-1440x900.png');
    await page.goto('/mi-carrera');
    const names = await page.locator('.course-name').allTextContents();
    await edit(page, names[0]).click();
    await page.locator('.inline-editor [name="status"]').selectOption('approved');
    await page.locator('.inline-editor [name="approval_method"]').selectOption('promotion');
    await page.getByRole('button', { name: 'Guardar cambios', exact: true }).click();
    await expect(page.locator('.inline-editor')).toHaveCount(0);
    await page.goto('/dashboard');
    await expect(page.locator('.ring-value')).toHaveAttribute('stroke-dasharray', '4 100');
    await cleanScreenshot(page, 'docs/evidence/m03-correction/avance-4-1440x900.png');
    await page.goto('/mi-carrera');
    for (const name of names.slice(1)) {
        await edit(page, name).click();
        await page.locator('.inline-editor [name="status"]').selectOption('approved');
        await page.getByRole('button', { name: 'Guardar cambios', exact: true }).click();
        await expect(page.locator('.inline-editor')).toHaveCount(0);
    }
    await page.goto('/dashboard');
    await expect(page.getByText('23 de 23 materias aprobadas')).toBeVisible();
    await expect(page.locator('.ring-value')).toHaveAttribute('stroke-dasharray', '100 100');
    await expect(page.getByRole('img', { name: /100 por ciento, 23 de 23/ })).toBeVisible();
    await cleanScreenshot(page, 'docs/evidence/m03-correction/avance-100-1440x900.png');
});

test('error de método abre el año correcto, conserva valores y enfoca el campo', async ({ page, request }) => {
    const person = await register(page, account('Persona Validación'));
    await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
    await choosePlan(page, hygiene);
    await year(page, 2).locator('summary').click();
    const course = row(page, 'Ergonomía');
    await course.locator('[data-status-select]').selectOption('approved');
    await course.locator('[data-method-select]').evaluate(select => select.add(new Option('Método de prueba inválido', 'promotion')));
    await course.locator('[data-method-select]').selectOption('promotion');
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await expectPath(page, '/configuracion-academica/materias');
    await expect(year(page, 2)).toHaveAttribute('open', '');
    await expect(course.locator('[data-status-select]')).toHaveValue('approved');
    await expect(course.locator('[data-method-select]')).toHaveAttribute('aria-invalid', 'true');
    await expect(course.locator('[data-method-select]')).toBeFocused();
    await expect(course.getByRole('alert')).toHaveText('Elegí un valor válido para método de aprobación.');
    await expect(page.getByText(/validation\./)).toHaveCount(0);
    await course.locator('[data-method-select]').selectOption('final');
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await expectPath(page, '/configuracion-academica/resumen');
});

// Real tab zoom via Chromium's extension API, not CSS scaling or viewport emulation.
// The extension exists only inside this disposable, isolated browser process.
test('zoom real del navegador al 200 % en las seis pantallas académicas', async ({ request }) => {
    const directory = await mkdtemp(join(tmpdir(), 'estuflow-zoom-'));
    let context;
    try {
        await writeFile(join(directory, 'manifest.json'), JSON.stringify({ manifest_version: 3, name: 'EstuFlow zoom check', version: '1.0', permissions: ['tabs'], background: { service_worker: 'worker.js' } }));
        await writeFile(join(directory, 'worker.js'), 'chrome.runtime.onInstalled.addListener(() => {});');
        context = await chromium.launchPersistentContext('', {
            headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH,
            args: [`--disable-extensions-except=${directory}`, `--load-extension=${directory}`, '--no-proxy-server'],
            baseURL: 'http://test-app:8000', viewport: { width: 1280, height: 720 },
        });
        const worker = context.serviceWorkers()[0] || await context.waitForEvent('serviceworker');
        const page = await context.newPage();
        const person = await register(page, account('Persona M03 zoom'));
        await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
        const before = await page.evaluate(() => ({ width: innerWidth, height: innerHeight }));
        const zoom = await worker.evaluate(async () => {
            const tabs = await chrome.tabs.query({});
            const tab = tabs.find(tab => tab.url.startsWith('http://test-app:8000/'));
            await chrome.tabs.setZoom(tab.id, 2);
            return chrome.tabs.getZoom(tab.id);
        });
        expect(zoom).toBe(2);
        await expect.poll(() => page.evaluate(() => innerWidth)).toBe(640);
        const measurements = [];
        async function inspectZoom(screen) {
            await page.evaluate(() => window.scrollTo(0, 0));
            await assertNoHorizontalScroll(page);
            const dimensions = await page.evaluate(() => ({ width: innerWidth, height: innerHeight, documentWidth: document.documentElement.scrollWidth }));
            expect(dimensions.width).toBe(640);
            measurements.push({ screen, ...dimensions });
            await cleanScreenshot(page, `docs/evidence/m03-correction/${screen}-zoom-200.png`);
        }
        await inspectZoom('elegir-plan');
        await choosePlan(page, 'Higiene, Seguridad y Ambiente Laboral');
        await inspectZoom('situacion-inicial');
        await page.getByRole('button', { name: 'Continuar', exact: true }).click();
        await inspectZoom('resumen');
        await page.getByRole('button', { name: 'Confirmar y empezar' }).click();
        await expectPath(page, '/dashboard');
        await inspectZoom('dashboard');
        await page.goto('/mi-carrera');
        await inspectZoom('mi-carrera');
        await page.getByRole('link', { name: 'Ver detalle de Práctica Profesionalizante 3: Diseño de Proyectos en Higiene, Seguridad y Ambiente Laboral', exact: true }).click();
        await inspectZoom('detalle-practica');
        await page.locator('[name="status"]').selectOption('regularized');
        await page.getByRole('button', { name: 'Guardar cambios' }).click();
        await expect(page.getByRole('status')).toHaveText('Guardamos los cambios.');
        await writeFile('docs/evidence/m03-correction/zoom-200.json', JSON.stringify({ scope: 'Chromium headless aislado; zoom real de pestaña via chrome.tabs.setZoom/getZoom. No prueba el escalado físico de macOS ni otros navegadores.', zoom, before, measurements }, null, 2) + '\n');
    } finally {
        if (context) await context.close();
        await rm(directory, { recursive: true, force: true });
    }
});
