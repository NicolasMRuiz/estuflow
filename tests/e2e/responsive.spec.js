import { test, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import {
    account, assertNoHorizontalScroll, capturedLink, cleanScreenshot,
    openSecurityLink, register, viewports,
} from './support/auth.js';

const captureWidths = new Set([390, 1440, 2560, 3840]);

test('matriz responsive de nueve viewports, nuevas pantallas y navegación móvil con teclado', async ({ browser, request }) => {
    const verificationContext = await browser.newContext({ baseURL: 'http://test-app:8000' });
    const verificationPage = await verificationContext.newPage();
    const person = await register(verificationPage, account('Valentina Prueba'));
    for (const viewport of viewports) {
        await verificationPage.setViewportSize(viewport);
        await verificationPage.goto('/verificar-email');
        await expect(verificationPage.getByRole('heading', { name: 'Verificá tu email.' })).toBeVisible();
        await assertNoHorizontalScroll(verificationPage);
        if (viewport.width === 390 || viewport.width === 1440) {
            await cleanScreenshot(verificationPage, `docs/evidence/m02/verificar-email-${viewport.width}x${viewport.height}.png`);
        }
        await verificationPage.goto('/verificar-email/0/email-invalido?expires=0&signature=invalida');
        await expect(verificationPage.getByRole('link', { name: /(?:verificar|verificación|nuevo enlace)/i })).toBeVisible();
        await assertNoHorizontalScroll(verificationPage);
        if (viewport.width === 390 || viewport.width === 1440) {
            await cleanScreenshot(verificationPage, `docs/evidence/m02/verificacion-invalida-${viewport.width}x${viewport.height}.png`);
        }
    }
    const verification = await capturedLink(request, person.email, '/verificar-email/');
    await openSecurityLink(verificationPage, verification, '/dashboard');
    await verificationPage.setViewportSize({ width: 2560, height: 1440 });
    const measureLayout = () => verificationPage.evaluate(() => {
        const sidebar = document.querySelector('.sidebar').getBoundingClientRect();
        const main = document.querySelector('.dashboard-main').getBoundingClientRect();
        return {
            viewport: { width: window.innerWidth, height: window.innerHeight },
            sidebarWidth: sidebar.width, mainLeft: main.left, mainWidth: main.width,
            remainingLeft: main.left - sidebar.right, remainingRight: window.innerWidth - main.right,
        };
    });
    // Focused reproduction of the prior positioning/width rules over the M02
    // markup. This is intentionally not a screenshot of the complete old app.
    const legacyStyle = await verificationPage.addStyleTag({ content: `
        body.dashboard-page { padding-left: 0 !important; }
        .sidebar { width: 250px !important; }
        .dashboard-main { width: auto !important; margin-left: 250px !important;
            margin-right: 0 !important; max-width: 1700px !important; padding: 0 40px 24px !important; }
    ` });
    let previousRules;
    try {
        previousRules = await measureLayout();
        await cleanScreenshot(verificationPage, 'docs/evidence/m02/dashboard-qhd-reglas-previas.png');
    } finally {
        await legacyStyle.evaluate(style => style.remove());
    }
    const currentRules = await measureLayout();
    await mkdir('docs/evidence/m02', { recursive: true });
    await writeFile('docs/evidence/m02/layout-qhd.json', JSON.stringify({
        scope: 'Reglas previas de posicionamiento y ancho aplicadas temporalmente sobre markup M02; no representa la versión anterior completa ni reproduce el monitor físico del reporte.',
        previousRules, currentRules,
    }, null, 2) + '\n');
    expect(previousRules.remainingRight - previousRules.remainingLeft).toBeGreaterThan(100);
    expect(Math.abs(currentRules.remainingRight - currentRules.remainingLeft)).toBeLessThanOrEqual(2);
    const verifiedState = await verificationContext.storageState();
    await verificationContext.close();

    const anonymous = await browser.newContext({ baseURL: 'http://test-app:8000' });
    const verified = await browser.newContext({ baseURL: 'http://test-app:8000', storageState: verifiedState });
    const anonymousPage = await anonymous.newPage();
    const verifiedPage = await verified.newPage();
    try {
        for (const viewport of viewports) {
            await anonymousPage.setViewportSize(viewport);
            for (const path of ['/login', '/registro', '/olvide-mi-contrasena', '/restablecer-contrasena/enlace-invalido']) {
                await anonymousPage.goto(path);
                await expect(anonymousPage.locator('main')).toBeVisible();
                await assertNoHorizontalScroll(anonymousPage);
                const form = anonymousPage.locator('.auth-card');
                const rect = await form.boundingBox();
                expect(rect.width).toBeLessThanOrEqual(560);
                if (path === '/registro' && (viewport.width === 390 || viewport.width === 1440)) {
                    await cleanScreenshot(anonymousPage, `docs/evidence/m02/registro-${viewport.width}x${viewport.height}.png`, { fullPage: true });
                }
                if (path === '/login' && captureWidths.has(viewport.width)) {
                    await cleanScreenshot(anonymousPage, `docs/evidence/m02/login-${viewport.width}x${viewport.height}.png`);
                }
            }
            await verifiedPage.setViewportSize(viewport);
            await verifiedPage.goto('/dashboard');
            await expect(verifiedPage.getByRole('heading', { name: 'Hola, Valentina Prueba.' })).toBeVisible();
            await assertNoHorizontalScroll(verifiedPage);
            const menu = verifiedPage.getByRole('button', { name: 'Abrir o cerrar navegación' });
            const navigation = verifiedPage.getByRole('navigation', { name: 'Navegación principal' });
            if (viewport.width <= 800) {
                await expect(navigation).toBeHidden();
                await menu.focus();
                await verifiedPage.keyboard.press('Enter');
                await expect(menu).toHaveAttribute('aria-expanded', 'true');
                await expect(navigation).toBeVisible();
                await verifiedPage.keyboard.press('Escape');
                await expect(menu).toBeFocused();
                await expect(navigation).toBeHidden();
                const outline = await menu.evaluate(button => getComputedStyle(button).outlineStyle);
                expect(outline).not.toBe('none');
                await verifiedPage.keyboard.press('Enter');
                await navigation.getByRole('link', { name: 'Inicio', exact: true }).focus();
                await verifiedPage.keyboard.press('Enter');
                await expect(navigation).toBeHidden();
            } else {
                await expect(navigation).toBeVisible();
                const aligned = await verifiedPage.evaluate(() => {
                    const sidebar = document.querySelector('.sidebar').getBoundingClientRect();
                    const main = document.querySelector('.dashboard-main').getBoundingClientRect();
                    return main.left >= sidebar.right - 1;
                });
                expect(aligned).toBe(true);
                if (viewport.width >= 2560) {
                    const margins = await verifiedPage.evaluate(() => {
                        const sidebar = document.querySelector('.sidebar').getBoundingClientRect();
                        const main = document.querySelector('.dashboard-main').getBoundingClientRect();
                        return Math.abs((main.left - sidebar.right) - (window.innerWidth - main.right));
                    });
                    expect(margins).toBeLessThanOrEqual(2);
                }
            }
            if (captureWidths.has(viewport.width)) {
                await cleanScreenshot(verifiedPage, `docs/evidence/m02/dashboard-${viewport.width}x${viewport.height}.png`);
                if (viewport.width === 390) await cleanScreenshot(verifiedPage, 'docs/evidence/m02/dashboard-390-full.png', { fullPage: true });
            }
            await verifiedPage.goto('/metodos-de-acceso');
            await expect(verifiedPage.getByRole('heading', { name: 'Métodos de acceso' })).toBeVisible();
            await assertNoHorizontalScroll(verifiedPage);
            if (viewport.width === 390 || viewport.width === 1440) {
                await cleanScreenshot(verifiedPage, `docs/evidence/m02/metodos-de-acceso-${viewport.width}x${viewport.height}.png`, { fullPage: true });
            }
        }
        // A 1280×720 display at 200% yields 640×360 CSS px. This checks that
        // equivalent layout/reflow; it does not claim to automate OS/browser zoom.
        await anonymousPage.setViewportSize({ width: 640, height: 360 });
        await anonymousPage.goto('/login');
        await assertNoHorizontalScroll(anonymousPage);
        await anonymousPage.getByRole('button', { name: 'Ingresar', exact: true }).scrollIntoViewIfNeeded();
        await expect(anonymousPage.getByRole('button', { name: 'Ingresar', exact: true })).toBeInViewport();
        await verifiedPage.setViewportSize({ width: 640, height: 360 });
        await verifiedPage.goto('/dashboard');
        await assertNoHorizontalScroll(verifiedPage);
        await verifiedPage.getByRole('button', { name: 'Cerrar sesión' }).scrollIntoViewIfNeeded();
        await expect(verifiedPage.getByRole('button', { name: 'Cerrar sesión' })).toBeInViewport();
    } finally {
        await Promise.all([anonymous.close(), verified.close()]);
    }
});
