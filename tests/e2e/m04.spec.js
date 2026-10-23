import { test, expect, chromium } from '@playwright/test';
import { randomUUID } from 'node:crypto';
import { writeFile, readFile, mkdir, rm, mkdtemp } from 'node:fs/promises';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import { account, register, capturedLink, openSecurityLink, logout, login, assertNoHorizontalScroll, cleanScreenshot } from './support/auth.js';
import { configureAcademic } from './support/academic.js';

async function control(email, action, event) {
    const directory = 'storage/framework/testing-m04';
    await mkdir(directory, { recursive: true });
    const id = randomUUID();
    const request = join(directory, `${id}.request.json`);
    const result = join(directory, `${id}.result.json`);
    const temporary = `${request}.tmp`;
    await writeFile(temporary, JSON.stringify({ email, action, event }));
    const { rename } = await import('node:fs/promises');
    await rename(temporary, request);
    let response;
    await expect.poll(async () => { try { response = JSON.parse(await readFile(result, 'utf8')); return true; } catch { return false; } }, { timeout: 15000, message: 'El controlador CLI aislado debe completar el fixture.' }).toBe(true);
    await rm(result); expect(response.ok).toBe(true); return response.result;
}
async function ready(page, request, name) {
    const person = await register(page, account(name));
    await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
    await configureAcademic(page); return person;
}
function future(days = 3) {
    const value = new Date(Date.now() + days * 86400000);
    return value.toLocaleDateString('en-CA', { timeZone: 'America/Argentina/Buenos_Aires' });
}
async function eventForm(page, title, date = future()) {
    await page.getByLabel('Título', { exact: true }).fill(title);
    await page.getByLabel('Fecha de inicio', { exact: true }).fill(date);
    await page.getByLabel('Hora de inicio', { exact: true }).fill('18:00');
}
async function capture(page, name) {
    for (const width of [320, 390, 1440, 2560, 3840]) {
        await page.setViewportSize({ width, height: width >= 2560 ? 1440 : width >= 1440 ? 900 : 844 });
        await page.evaluate(() => window.scrollTo(0, 0));
        await assertNoHorizontalScroll(page);
        await cleanScreenshot(page, `docs/evidence/m04/${name}-${width}.png`, { fullPage: true });
    }
    await page.setViewportSize({ width: 1440, height: 900 });
}

test('M04: evento completo, reprogramación persistente, SMTP, bandeja, cartelera y notas', async ({ page, request }) => {
    test.setTimeout(180000);
    const person = await ready(page, request, 'Persona Calendario');
    await page.goto(`/calendario/nuevo?date=${future()}&view=agenda`);
    await expect(page.getByLabel('Fecha de inicio', { exact: true })).toHaveValue(future());
    await eventForm(page, 'Examen de Redes');
    await page.getByLabel('24 horas antes', { exact: true }).check();
    await page.getByLabel('1 hora antes', { exact: true }).check();
    await page.getByLabel('También por email para este evento').check();
    await page.getByLabel('Lugar (opcional)').fill('Aula 3');
    await capture(page, 'evento-formulario');
    await page.getByRole('button', { name: 'Guardar evento', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Examen de Redes', exact: true })).toBeVisible();
    await cleanScreenshot(page, 'docs/evidence/m04/crud-evento-creado.png', { fullPage: true });
    const event = Number(new URL(page.url()).pathname.split('/').pop());
    expect(await control(person.email, 'inspect', event)).toMatchObject({ version: 1, pending: 2 });
    await page.goto('/dashboard'); await expect(page.locator('.dashboard-activities').getByText('Examen de Redes')).toBeVisible();
    await page.goto('/calendario'); await capture(page, 'calendario');
    await page.goto(`/calendario/${event}/editar?view=agenda&filter_type=exam`);
    await page.getByLabel('Fecha de inicio', { exact: true }).fill(future(4));
    await page.getByLabel('Fecha final (opcional)').fill('');
    await page.getByRole('button', { name: 'Guardar evento' }).click();
    await page.evaluate(() => window.scrollTo(0, 0));
    await cleanScreenshot(page, 'docs/evidence/m04/crud-evento-editado.png', { fullPage: true });
    expect(new URL(page.url()).searchParams.get('view')).toBe('agenda');
    expect(await control(person.email, 'inspect', event)).toMatchObject({ version: 2, pending: 2, cancelled: 2 });
    await page.getByRole('link', { name: 'Editar evento', exact: true }).click();
    await page.getByLabel('Título', { exact: true }).fill('No se guarda');
    await page.getByRole('link', { name: 'Cancelar', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Examen de Redes', exact: true })).toBeVisible();
    await control(person.email, 'deliver', event);
    // This link must arrive through the real isolated database worker and SMTP transport.
    const mailLink = await capturedLink(request, person.email, `/calendario/${event}`);
    expect(new URL(mailLink).pathname).toBe(`/calendario/${event}`);
    expect(await control(person.email, 'inspect', event)).toMatchObject({ sent: 1 });
    await control(person.email, 'board');
    await page.goto('/notificaciones'); await expect(page.getByRole('link', { name: 'Examen de Redes', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Novedad de prueba M04', exact: true })).toBeVisible();
    await capture(page, 'notificaciones');
    await page.getByRole('link', { name: 'Novedad de prueba M04', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Novedad de prueba M04' })).toBeVisible(); await capture(page, 'cartelera');
    await page.goto('/notificaciones'); await page.getByRole('button', { name: 'Marcar como leída: Examen de Redes' }).click();
    await expect(page.locator('.notice-row').filter({ hasText: 'Examen de Redes' })).toHaveClass(/is-read/);
    await page.getByRole('link', { name: 'Examen de Redes', exact: true }).click();
    await page.getByRole('link', { name: 'Eliminar evento', exact: true }).click(); await page.getByRole('link', { name: 'Cancelar', exact: true }).click();
    await page.getByRole('link', { name: 'Eliminar evento', exact: true }).click(); await page.getByRole('button', { name: 'Sí, eliminar evento' }).click();
    expect(await control(person.email, 'inspect', event)).toMatchObject({ pending: 0 });
    await page.goto('/notificaciones'); await page.getByRole('link', { name: 'Examen de Redes', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Este evento ya no está disponible' })).toBeVisible();
    await cleanScreenshot(page, 'docs/evidence/m04/crud-evento-eliminado.png');
    await page.goto('/notas/nueva'); await page.getByLabel('Título (opcional)').fill('Ideas para el trabajo');
    await page.getByLabel('Tu nota', { exact: true }).fill('Consultar bibliografía.\nPreparar el esquema.');
    await page.getByRole('button', { name: 'Guardar nota' }).click();
    await expect(page.getByText('Consultar bibliografía.', { exact: false })).toBeVisible();
    await cleanScreenshot(page, 'docs/evidence/m04/crud-nota-creada.png');
    await page.getByRole('link', { name: 'Editar nota', exact: true }).click();
    await page.getByLabel('Tu nota', { exact: true }).fill('Este borrador se cancela');
    await page.getByRole('link', { name: 'Cancelar', exact: true }).click();
    await expect(page.getByText('Preparar el esquema.', { exact: false })).toBeVisible();
    await page.getByRole('link', { name: 'Editar nota', exact: true }).click(); await page.getByLabel('Tu nota', { exact: true }).fill('Consultar bibliografía.\nLlevar el esquema a clase.');
    await page.getByRole('button', { name: 'Guardar nota' }).click();
    await logout(page); await login(page, person); await expect(page.locator('#mis-notas')).toContainText('Llevar el esquema a clase.');
    await page.locator('#mis-notas').scrollIntoViewIfNeeded();
    await cleanScreenshot(page, 'docs/evidence/m04/crud-nota-editada-persistida.png');
    await capture(page, 'dashboard'); await page.goto('/notas'); await capture(page, 'notas');
    await page.getByRole('link', { name: 'Ideas para el trabajo', exact: true }).click();
    await page.getByRole('link', { name: 'Eliminar nota', exact: true }).click();
    await page.getByRole('link', { name: 'Cancelar', exact: true }).click();
    await expect(page.getByText('Llevar el esquema a clase.', { exact: false })).toBeVisible();
    await page.getByRole('link', { name: 'Eliminar nota', exact: true }).click(); await page.getByRole('button', { name: 'Sí, eliminar nota' }).click();
    await expect(page.getByRole('heading', { name: 'Un espacio para tus ideas' })).toBeVisible();
    await cleanScreenshot(page, 'docs/evidence/m04/crud-nota-eliminada.png');
});

test('M04 sin JavaScript: agenda y CRUD de evento/nota con teclado', async ({ browser, request }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, baseURL: 'http://test-app:8000', viewport: { width: 390, height: 844 } });
    const page = await context.newPage();
    try {
        await ready(page, request, 'Persona Agenda sin JS');
        await page.goto('/calendario?view=agenda'); await page.getByRole('link', { name: 'Nuevo evento', exact: true }).focus(); await page.keyboard.press('Enter');
        await eventForm(page, 'Entrega sin JavaScript'); await page.getByRole('button', { name: 'Guardar evento' }).click();
        await expect(page.getByRole('heading', { name: 'Entrega sin JavaScript' })).toBeVisible();
        await page.getByRole('link', { name: 'Editar evento', exact: true }).click(); await page.getByLabel('Título', { exact: true }).fill('Entrega corregida'); await page.getByRole('button', { name: 'Guardar evento' }).click();
        await page.getByRole('link', { name: 'Eliminar evento', exact: true }).click(); await page.getByRole('button', { name: 'Sí, eliminar evento' }).click();
        await page.goto('/notas/nueva'); await page.getByLabel('Tu nota', { exact: true }).fill('Apunte sin JavaScript'); await page.getByRole('button', { name: 'Guardar nota' }).click();
        await expect(page.getByText('Apunte sin JavaScript')).toBeVisible(); await page.getByRole('link', { name: 'Editar nota', exact: true }).click(); await page.getByLabel('Tu nota', { exact: true }).fill('Apunte editado'); await page.getByRole('button', { name: 'Guardar nota' }).click();
        await page.getByRole('link', { name: 'Eliminar nota', exact: true }).click(); await page.getByRole('button', { name: 'Sí, eliminar nota' }).click(); await assertNoHorizontalScroll(page);
    } finally { await context.close(); }
});

test('M04 zoom real 200 %: calendario, formulario, bandeja, notas e Inicio', async ({ request }) => {
    const directory = await mkdtemp(join(tmpdir(), 'estuflow-m04-zoom-')); let context;
    try {
        await writeFile(join(directory, 'manifest.json'), JSON.stringify({ manifest_version: 3, name: 'EstuFlow zoom check', version: '1.0', permissions: ['tabs'], background: { service_worker: 'worker.js' } }));
        await writeFile(join(directory, 'worker.js'), 'chrome.runtime.onInstalled.addListener(() => {});');
        context = await chromium.launchPersistentContext('', { headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH, args: [`--disable-extensions-except=${directory}`, `--load-extension=${directory}`, '--no-proxy-server'], baseURL: 'http://test-app:8000', viewport: { width: 1280, height: 720 } });
        const worker = context.serviceWorkers()[0] || await context.waitForEvent('serviceworker'); const page = await context.newPage(); await ready(page, request, 'Persona Calendario zoom');
        const zoom = await worker.evaluate(async () => { const tabs = await chrome.tabs.query({}); const tab = tabs.find(tab => tab.url.startsWith('http://test-app:8000/')); await chrome.tabs.setZoom(tab.id, 2); return chrome.tabs.getZoom(tab.id); });
        expect(zoom).toBe(2); await expect.poll(() => page.evaluate(() => innerWidth)).toBe(640);
        for (const [screen, path] of Object.entries({ calendario: '/calendario', formulario: '/calendario/nuevo', notificaciones: '/notificaciones', notas: '/notas', dashboard: '/dashboard' })) {
            await page.goto(path); await page.evaluate(() => window.scrollTo(0, 0)); await assertNoHorizontalScroll(page);
            expect(await page.evaluate(() => innerWidth)).toBe(640);
            const cdp = await context.newCDPSession(page);
            const topShot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
            await writeFile(`docs/evidence/m04/${screen}-zoom200.png`, Buffer.from(topShot.data, 'base64'));
            await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
            const bottomShot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
            await writeFile(`docs/evidence/m04/${screen}-zoom200-final.png`, Buffer.from(bottomShot.data, 'base64'));
            await cdp.detach();
        }
        await writeFile('docs/evidence/m04/zoom.json', JSON.stringify({ zoom, viewport: { width: 1280, height: 720 }, cssViewport: { width: 640, height: 360 }, scope: 'Chromium aislado, chrome.tabs.setZoom/getZoom. Capturas del viewport superior y final; no escalado físico de macOS.' }, null, 2) + '\n');
    } finally { await context?.close(); await rm(directory, { recursive: true, force: true }); }
});
