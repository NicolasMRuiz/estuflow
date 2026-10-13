import { test, expect, chromium } from '@playwright/test';
import { mkdtemp, writeFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { account, register, capturedLink, openSecurityLink, expectPath, logout, login, cleanScreenshot, assertNoHorizontalScroll, viewports } from './support/auth.js';
import { choosePlan, confirmDraft, configureAcademic } from './support/academic.js';

const captures = viewports.filter(viewport => [390, 1440, 2560, 3840].includes(viewport.width));

async function inspect(page, screen) {
    for (const viewport of [...captures, { width: 320, height: 740 }, { width: 640, height: 360 }]) {
        await page.setViewportSize(viewport);
        await page.evaluate(() => window.scrollTo(0, 0));
        await assertNoHorizontalScroll(page);
        if ([390, 1440, 2560, 3840].includes(viewport.width)) {
            await cleanScreenshot(page, `docs/evidence/m03/${screen}-${viewport.width}x${viewport.height}.png`);
        }
    }
    await page.setViewportSize({ width: 1440, height: 900 });
}

test('configuración recuperable → Mi carrera → edición Livewire → dashboard → cambio y retorno de plan', async ({ page, request }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.name));
    const person = await register(page, account('Persona M03'));
    await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
    await expect(page.locator('input[type="radio"]:checked')).toHaveCount(0);
    await page.getByRole('button', { name: 'Continuar con mis materias' }).click();
    await expect(page.locator('#plan-error')).toBeVisible();
    await inspect(page, 'elegir-plan');
    await choosePlan(page, 'Higiene, Seguridad y Ambiente Laboral');
    const row604 = page.locator('.course-row').filter({ has: page.getByRole('heading', { name: /^604 ·/ }) });
    const row610 = page.locator('.course-row').filter({ has: page.getByRole('heading', { name: /^610 ·/ }) });
    const row611 = page.locator('.course-row').filter({ has: page.getByRole('heading', { name: /^611 ·/ }) });
    await row604.locator('select').first().selectOption('approved');
    await row604.locator('select').nth(1).selectOption('promotion');
    await row610.locator('select').first().selectOption('in_progress');
    await row611.locator('select').first().selectOption('regularized');
    await page.getByRole('button', { name: 'Guardar borrador' }).click();
    await expect(page.getByRole('status')).toContainText('Borrador guardado');
    await logout(page);
    await login(page, person);
    await expectPath(page, '/configuracion-academica/materias');
    await expect(row604.locator('select').first()).toHaveValue('approved');
    await expect(row604.locator('select').nth(1)).toHaveValue('promotion');
    await inspect(page, 'situacion-inicial');
    await page.getByRole('button', { name: 'Revisar resumen' }).click();
    await expectPath(page, '/configuracion-academica/resumen');
    await expect(page.getByText('Aprobada · Promoción')).toBeVisible();
    await inspect(page, 'resumen');
    await page.locator('[name="confirm"]').check();
    await page.getByRole('button', { name: 'Confirmar y ver mi inicio' }).click();
    await expectPath(page, '/dashboard');
    await expect(page.getByText('1 de 24 materias aprobadas')).toBeVisible();
    await expect(page.getByText('4 %', { exact: true })).toBeVisible();
    await inspect(page, 'dashboard');
    await page.getByRole('link', { name: 'Ver materias cursando' }).click();
    await expect(page.locator('.course-list li')).toHaveCount(1);
    await expect(page.getByRole('link', { name: '610 · Seguridad 1' })).toBeVisible();
    await page.getByRole('link', { name: 'Limpiar filtros' }).click();
    await inspect(page, 'mi-carrera');
    await page.getByRole('link', { name: /^627 ·/ }).click();
    await expect(page.getByRole('heading', { name: 'Regla especial: TODAS' })).toBeVisible();
    await expect(page.getByText(/Se excluye esta práctica/)).toBeVisible();
    await inspect(page, 'detalle-practica');
    await page.getByRole('link', { name: 'Volver a Mi carrera' }).click();
    await page.getByRole('link', { name: /^604 ·/ }).click();
    await page.locator('[name="status"]').selectOption('regularized');
    await page.getByRole('button', { name: 'Guardar estado' }).click();
    await expect(page.getByRole('status')).toHaveText('Estado guardado.');
    await page.reload();
    await expect(page.locator('[name="status"]')).toHaveValue('regularized');
    await expect(page.locator('[name="approval_method"]')).toHaveValue('unspecified');
    await page.goto('/dashboard');
    await expect(page.getByText('0 de 24 materias aprobadas')).toBeVisible();
    await page.goto('/configuracion-academica');
    await choosePlan(page, 'Enfermería');
    await confirmDraft(page);
    await page.goto('/configuracion-academica');
    await choosePlan(page, 'Higiene, Seguridad y Ambiente Laboral');
    await expect(row604.locator('select').first()).toHaveValue('regularized');
    await expect(row610.locator('select').first()).toHaveValue('in_progress');
    await confirmDraft(page);
    await logout(page);
    await login(page, person);
    await expectPath(page, '/dashboard');
    await expect(page.getByText('0 de 24 materias aprobadas')).toBeVisible();
    expect(errors).toEqual([]);
});

test('configuración y edición persistente de una cuenta existente sin JavaScript', async ({ browser, request }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, baseURL: 'http://test-app:8000', viewport: { width: 390, height: 844 } });
    const page = await context.newPage();
    try {
        const person = await register(page, account('Persona M03 sin JavaScript'));
        await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
        await logout(page);
        await login(page, person);
        await configureAcademic(page);
        await page.goto('/mi-carrera');
        await page.getByRole('link', { name: /^294 ·/ }).click();
        await page.locator('[name="status"]').selectOption('approved');
        await page.locator('[name="approval_method"]').selectOption('promotion');
        await page.getByRole('button', { name: 'Guardar estado' }).click();
        await expect(page.getByRole('status')).toHaveText('Estado guardado.');
        await logout(page);
        await login(page, person);
        await expectPath(page, '/dashboard');
        await expect(page.getByText('1 de 23 materias aprobadas')).toBeVisible();
        await assertNoHorizontalScroll(page);
        await page.goto('/mi-carrera?year=1&status=approved');
        await expect(page.locator('.course-list li')).toHaveCount(1);
        await expect(page.getByRole('link', { name: /^294 ·/ })).toBeVisible();
    } finally {
        await context.close();
    }
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
            await cleanScreenshot(page, `docs/evidence/m03/${screen}-zoom-200.png`);
        }
        await inspectZoom('elegir-plan');
        await choosePlan(page, 'Higiene, Seguridad y Ambiente Laboral');
        await inspectZoom('situacion-inicial');
        await page.getByRole('button', { name: 'Revisar resumen' }).click();
        await inspectZoom('resumen');
        await page.locator('[name="confirm"]').check();
        await page.getByRole('button', { name: 'Confirmar y ver mi inicio' }).click();
        await expectPath(page, '/dashboard');
        await inspectZoom('dashboard');
        await page.goto('/mi-carrera');
        await inspectZoom('mi-carrera');
        await page.getByRole('link', { name: /^627 ·/ }).click();
        await inspectZoom('detalle-practica');
        await page.locator('[name="status"]').selectOption('regularized');
        await page.getByRole('button', { name: 'Guardar estado' }).click();
        await expect(page.getByRole('status')).toHaveText('Estado guardado.');
        await writeFile('docs/evidence/m03/zoom-200.json', JSON.stringify({ scope: 'Chromium headless aislado; zoom real de pestaña via chrome.tabs.setZoom/getZoom. No prueba el escalado físico de macOS ni otros navegadores.', zoom, before, measurements }, null, 2) + '\n');
    } finally {
        if (context) await context.close();
        await rm(directory, { recursive: true, force: true });
    }
});
