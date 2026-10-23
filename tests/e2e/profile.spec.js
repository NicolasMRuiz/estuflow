import { test, expect, chromium } from '@playwright/test';
import { writeFile, rm, mkdtemp } from 'node:fs/promises';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import { account, register, capturedLink, openSecurityLink, logout, login, passwordInput, expectPath, assertNoHorizontalScroll, cleanScreenshot, expectPasswordChangeMail } from './support/auth.js';
import { configureAcademic, choosePlan } from './support/academic.js';

async function ready(page, request, name) {
    const person = await register(page, account(name));
    await openSecurityLink(page, await capturedLink(request, person.email, '/verificar-email/'), '/configuracion-academica');
    await page.goto('/mi-perfil');
    await expect(page.getByRole('link', { name: 'Completar mi carrera', exact: true })).toBeVisible();
    await page.goto('/configuracion-academica');
    await configureAcademic(page);
    return person;
}
async function capture(page, name) {
    for (const width of [320, 390, 1440, 2560, 3840]) {
        await page.setViewportSize({ width, height: width >= 2560 ? 1440 : width >= 1440 ? 900 : 844 });
        await page.evaluate(() => window.scrollTo(0, 0));
        await assertNoHorizontalScroll(page);
        await cleanScreenshot(page, `docs/evidence/m05/${name}-${width}.png`, { fullPage: true });
    }
    await page.setViewportSize({ width: 1440, height: 900 });
}

test('M05 perfil: nombre, doble confirmación SMTP, contraseña y sesiones reales', async ({ page, request, browser }) => {
    test.setTimeout(180000);
    const person = await ready(page, request, 'Persona Perfil');
    await page.goto('/mi-perfil');
    await page.getByLabel('Tu nombre', { exact: true }).fill('No guardar');
    await page.getByRole('link', { name: 'Cancelar', exact: true }).click();
    await expect(page.getByLabel('Tu nombre', { exact: true })).toHaveValue('Persona Perfil');
    await page.getByLabel('Tu nombre', { exact: true }).fill('Ángela María Prueba');
    await page.getByRole('button', { name: 'Guardar nombre', exact: true }).focus();
    await page.keyboard.press('Enter');
    await expect(page.getByRole('status')).toContainText('Guardamos tu nombre.');
    await capture(page, 'perfil');
    await page.goto('/dashboard');
    await expect(page.getByRole('heading', { name: 'Hola, Ángela María Prueba.' })).toBeVisible();
    await expect(page.locator('.account-avatar')).toHaveText('Á');
    await page.goto('/notas/nueva');
    await page.getByLabel('Tu nota', { exact: true }).fill('Apunte que se conserva al cambiar el email.');
    await page.getByRole('button', { name: 'Guardar nota', exact: true }).click();
    await page.goto('/mi-perfil/email');
    const reauth = page.locator('form[action$="/reautenticar"] input[name="password"]');
    if (await reauth.count()) {
        await passwordInput(page, 'password', person.password);
        await page.getByRole('button', { name: 'Confirmar identidad', exact: true }).click();
    }
    const next = account();
    await page.getByLabel('Nuevo email', { exact: true }).fill(next.email.toUpperCase());
    await page.getByRole('button', { name: 'Solicitar cambio de email', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Cambio pendiente', exact: true })).toBeVisible();
    await expect(page.getByText(person.email, { exact: true })).toBeVisible();
    // Both links come from the isolated SMTP worker. Keep their secrets in memory only.
    const current = await capturedLink(request, person.email, '/mi-perfil/email/confirmar/');
    const proposed = await capturedLink(request, next.email, '/mi-perfil/email/confirmar/');
    await page.reload();
    await capture(page, 'email-pendiente');
    const emailContext = await browser.newContext();
    const emailSession = await emailContext.newPage();
    await login(emailSession, person); await expectPath(emailSession, '/dashboard');
    await openSecurityLink(page, proposed, new URL(proposed).pathname);
    await page.getByRole('button', { name: 'Confirmar este correo', exact: true }).click();
    await expectPath(page, '/mi-perfil/email');
    await expect(page.getByText(person.email, { exact: true })).toBeVisible();
    await openSecurityLink(page, current, new URL(current).pathname);
    await page.getByRole('button', { name: 'Confirmar este correo', exact: true }).click();
    await expectPath(page, '/mi-perfil/email');
    await expect(page.getByRole('status')).toContainText('Tu email cambió y quedó verificado.');
    await expect(page.getByText(next.email, { exact: true })).toBeVisible();
    await emailSession.goto('/mi-perfil'); await expectPath(emailSession, '/login');
    await emailContext.close();
    person.email = next.email;
    await page.goto('/notas');
    await expect(page.getByText('Apunte que se conserva al cambiar el email.', { exact: true })).toBeVisible();
    const otherContext = await browser.newContext();
    try {
        const other = await otherContext.newPage();
        await login(other, person); await expectPath(other, '/dashboard');
        await page.goto('/mi-perfil/contrasena'); await capture(page, 'contrasena');
        await passwordInput(page, 'current_password', person.password);
        await passwordInput(page, 'password', next.password);
        await passwordInput(page, 'password_confirmation', next.password);
        await page.getByRole('button', { name: 'Guardar contraseña', exact: true }).click();
        await expectPath(page, '/mi-perfil');
        await other.goto('/mi-perfil'); await expectPath(other, '/login');
        await expectPasswordChangeMail(request, person.email, [person.password, next.password]);
        person.password = next.password;
        await logout(page); await login(page, person); await expectPath(page, '/dashboard');
        await expect(page.getByRole('heading', { name: 'Hola, Ángela María Prueba.' })).toBeVisible();
        await page.goto('/mi-perfil'); await expect(page.getByLabel('Tu nombre', { exact: true })).toHaveValue('Ángela María Prueba');
    } finally { await otherContext.close(); }
});

test('M05 sin JavaScript: guardar, errores, cambio académico y retorno a perfil', async ({ browser, request }) => {
    test.setTimeout(120000);
    const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
    try {
        const page = await context.newPage(); await ready(page, request, 'Persona Perfil HTTP');
        await page.goto('/mi-perfil');
        await page.getByLabel('Tu nombre', { exact: true }).fill('');
        await page.getByRole('button', { name: 'Guardar nombre', exact: true }).click();
        await expect(page.locator('#name-error')).not.toBeEmpty();
        await expect(page.locator('.error-summary')).toHaveCount(0);
        await cleanScreenshot(page, 'docs/evidence/m05/error-nombre-http-390.png', { fullPage: true });
        await page.getByLabel('Tu nombre', { exact: true }).fill('Lucía Perfil HTTP');
        await page.getByRole('button', { name: 'Guardar nombre', exact: true }).click();
        await page.getByRole('link', { name: 'Cambiar carrera', exact: true }).click();
        await page.getByRole('radio', { name: /Higiene/ }).check();
        await page.getByRole('button', { name: 'Continuar', exact: true }).click();
        await page.getByRole('button', { name: 'Cancelar cambio', exact: true }).click();
        await expectPath(page, '/mi-perfil');
        await expect(page.getByRole('heading', { name: 'Análisis de Sistemas', exact: true })).toBeVisible();
        await page.getByRole('link', { name: 'Cambiar carrera', exact: true }).click();
        await choosePlan(page, 'Higiene');
        await page.getByRole('button', { name: 'Continuar', exact: true }).click();
        await page.getByRole('button', { name: 'Confirmar y empezar', exact: true }).click();
        await expectPath(page, '/mi-perfil');
        await expect(page.getByRole('heading', { name: /Higiene/ })).toBeVisible();
        await capture(page, 'carrera-confirmada');
        await page.getByRole('link', { name: 'Ver mis materias', exact: true }).click();
        await expect(page.getByRole('link', { name: 'Cambiar carrera', exact: true })).toHaveCount(0);
        await page.goto('/metodos-de-acceso'); await expectPath(page, '/mi-perfil');
    } finally { await context.close(); }
});

test('M05 zoom real 200 %: perfil, email y contraseña', async ({ request }) => {
    test.setTimeout(120000);
    const directory = await mkdtemp(join(tmpdir(), 'estuflow-m05-zoom-')); let context;
    try {
        await writeFile(join(directory, 'manifest.json'), JSON.stringify({ manifest_version: 3, name: 'EstuFlow zoom check', version: '1.0', permissions: ['tabs'], background: { service_worker: 'worker.js' } }));
        await writeFile(join(directory, 'worker.js'), 'chrome.runtime.onInstalled.addListener(() => {});');
        context = await chromium.launchPersistentContext('', { headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH, args: [`--disable-extensions-except=${directory}`, `--load-extension=${directory}`, '--no-proxy-server'], baseURL: 'http://test-app:8000', viewport: { width: 1280, height: 720 } });
        const worker = context.serviceWorkers()[0] || await context.waitForEvent('serviceworker');
        const page = await context.newPage(); await ready(page, request, 'Persona Perfil Zoom');
        const zoom = await worker.evaluate(async () => { const tabs = await chrome.tabs.query({}); const tab = tabs.find(tab => tab.url.startsWith('http://test-app:8000/')); await chrome.tabs.setZoom(tab.id, 2); return chrome.tabs.getZoom(tab.id); });
        expect(zoom).toBe(2); await expect.poll(() => page.evaluate(() => innerWidth)).toBe(640);
        for (const [name, path] of Object.entries({ perfil: '/mi-perfil', email: '/mi-perfil/email', contrasena: '/mi-perfil/contrasena' })) {
            await page.goto(path); await page.evaluate(() => window.scrollTo(0, 0)); await assertNoHorizontalScroll(page);
            const cdp = await context.newCDPSession(page);
            for (const position of ['superior', 'final']) {
                if (position === 'final') await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
                const shot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
                await writeFile(`docs/evidence/m05/${name}-zoom200-${position}.png`, Buffer.from(shot.data, 'base64'));
            }
            await cdp.detach();
        }
        await writeFile('docs/evidence/m05/zoom.json', JSON.stringify({ zoom, viewport: { width: 1280, height: 720 }, cssViewport: { width: 640, height: 360 }, method: 'Chromium tabs.setZoom/getZoom + CDP viewport screenshot; sin escalado de SO.' }, null, 2) + '\n');
    } finally { await context?.close(); await rm(directory, { recursive: true, force: true }); }
});
