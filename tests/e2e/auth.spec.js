import { test, expect } from '@playwright/test';
import { randomBytes, randomUUID } from 'node:crypto';
import {
    account, assertNoHorizontalScroll, capturedLink, cleanScreenshot,
    expectPasswordChangeMail, login, logout, openSecurityLink, passwordInput,
    register, requestRecovery,
} from './support/auth.js';

test('registro → verificación SMTP → dashboard → recuperación SMTP → nueva contraseña', async ({ page, request, browser }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const person = await register(page);
    await page.goto('/dashboard');
    await expect.poll(() => new URL(page.url()).pathname).toBe('/verificar-email');
    await expect(page.getByRole('button', { name: 'Reenviar enlace' })).toBeDisabled();
    await expect(page.locator('[data-cooldown-message]')).toBeVisible();
    const verification = await capturedLink(request, person.email, '/verificar-email/');
    await logout(page);
    await login(page, person);
    await expect.poll(() => new URL(page.url()).pathname).toBe('/verificar-email');
    await logout(page);
    await openSecurityLink(page, verification, '/login');
    await login(page, person);
    await expect.poll(() => new URL(page.url()).pathname).toBe('/dashboard');
    await expect(page.getByRole('heading', { name: `Hola, ${person.name}.` })).toBeVisible();
    await expect(page.getByText('Sin datos', { exact: true })).toBeVisible();
    await expect(page.locator('html')).toHaveAttribute('lang', 'es');
    // The same still-valid verification link is safe to open again.
    await openSecurityLink(page, verification, '/dashboard');

    const csrfStatus = await page.evaluate(async () => {
        const response = await fetch('/logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=invalido',
        });
        return response.status;
    });
    expect(csrfStatus).toBe(419);
    await page.reload();
    await expect(page.getByRole('heading', { name: `Hola, ${person.name}.` })).toBeVisible();
    await logout(page);
    await page.goto('/dashboard');
    await expect.poll(() => new URL(page.url()).pathname).toBe('/login');
    await login(page, person);
    await expect.poll(() => new URL(page.url()).pathname).toBe('/dashboard');
    const priorSession = await browser.newContext({ baseURL: 'http://test-app:8000' });
    const priorPage = await priorSession.newPage();
    await login(priorPage, person);
    await expect.poll(() => new URL(priorPage.url()).pathname).toBe('/dashboard');
    await logout(page);

    const unknownStatus = await requestRecovery(page, `ausente-${randomUUID()}@example.test`);
    const knownStatus = await requestRecovery(page, person.email.toUpperCase());
    expect(knownStatus).toBe(unknownStatus);
    const reset = await capturedLink(request, person.email, '/restablecer-contrasena/');
    await openSecurityLink(page, reset, new URL(reset).pathname);
    await cleanScreenshot(page, 'docs/evidence/m02/restablecer-1440x900.png');
    const newPassword = randomBytes(20).toString('hex');
    await passwordInput(page, 'password', newPassword);
    await passwordInput(page, 'password_confirmation', newPassword);
    await page.locator('form[action$="/restablecer-contrasena"] button[type="submit"]').click();
    await expect.poll(() => new URL(page.url()).pathname).toBe('/login');
    await expect(page.getByRole('status')).toBeVisible();
    await expectPasswordChangeMail(request, person.email, [person.password, newPassword]);
    await priorPage.goto('/dashboard');
    await expect.poll(() => new URL(priorPage.url()).pathname).toBe('/login');
    await priorSession.close();
    await login(page, person);
    await expect(page.getByRole('alert')).toHaveText('El email o la contraseña no son correctos.');
    await expect(page.getByText('El email o la contraseña no son correctos.', { exact: true })).toHaveCount(1);
    await expect(page.getByLabel('Email', { exact: true })).toHaveAttribute('aria-invalid', 'false');
    await expect(page.getByLabel('Contraseña', { exact: true })).toHaveAttribute('aria-invalid', 'false');
    expect(await page.getByLabel('Contraseña', { exact: true }).evaluate(input => input.value === '')).toBe(true);
    await login(page, { ...person, password: newPassword });
    await expect.poll(() => new URL(page.url()).pathname).toBe('/dashboard');
    await logout(page);
    await openSecurityLink(page, reset, new URL(reset).pathname);
    await passwordInput(page, 'password', randomBytes(20).toString('hex'));
    await passwordInput(page, 'password_confirmation', await page.locator('[name="password"]').inputValue());
    await page.locator('form[action$="/restablecer-contrasena"] button[type="submit"]').click();
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(page.getByRole('link', { name: /(?:otro|nuevo) enlace/i })).toBeVisible();
    await assertNoHorizontalScroll(page);
    expect(errors).toEqual([]);
});

test('errores inline, corrección, foco y credenciales sin duplicación a 320 px', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 740 });
    await page.goto('/login');
    await expect(page.locator('form[action$="/login"]')).toHaveAttribute('novalidate', '');
    await expect(page.locator('[aria-invalid="true"]')).toHaveCount(0);
    await page.getByRole('button', { name: 'Ingresar', exact: true }).click();
    const email = page.getByLabel('Email', { exact: true });
    await expect(email).toHaveAttribute('aria-invalid', 'true');
    await expect(email).toHaveAttribute('aria-describedby', /email-error/);
    await expect(page.locator('#email-error')).toBeVisible();
    await expect(email).toBeFocused();
    await email.fill('correo-sin-arroba');
    await page.keyboard.press('Tab');
    await expect(page.locator('#email-error')).toBeVisible();
    await email.fill(`ausente-${randomUUID()}@example.test`);
    await page.keyboard.press('Tab');
    await expect(email).toHaveAttribute('aria-invalid', 'false');
    await passwordInput(page, 'password', randomBytes(20).toString('hex'));
    await page.getByRole('button', { name: 'Ingresar', exact: true }).click();
    await expect(page.getByRole('alert')).toHaveText('El email o la contraseña no son correctos.');
    await expect(page.getByRole('alert')).toBeFocused();
    await expect(page.getByText('El email o la contraseña no son correctos.', { exact: true })).toHaveCount(1);
    await expect(email).toHaveAttribute('aria-invalid', 'false');
    await expect(page.getByLabel('Contraseña', { exact: true })).toHaveAttribute('aria-invalid', 'false');
    await cleanScreenshot(page, 'docs/evidence/m02/login-error-320x740.png', { fullPage: true });
    await email.fill('otra-direccion@example.test');
    await expect(page.getByRole('alert')).toBeHidden();
    await page.getByRole('link', { name: '¿Olvidaste la contraseña?' }).focus();
    await page.keyboard.press('Enter');
    await expect.poll(() => new URL(page.url()).pathname).toBe('/olvide-mi-contrasena');
    await cleanScreenshot(page, 'docs/evidence/m02/recuperacion-320x740.png', { fullPage: true });
    await page.locator('button[type="submit"]').click();
    await expect(page.getByLabel('Email', { exact: true })).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#email-error')).toBeVisible();
    await page.goto('/registro');
    await page.getByLabel('Tu nombre', { exact: true }).fill('Persona Validación');
    await page.getByLabel('Email', { exact: true }).fill(`validacion-${randomUUID()}@example.test`);
    const password = randomBytes(20).toString('hex');
    await passwordInput(page, 'password', password);
    await passwordInput(page, 'password_confirmation', randomBytes(20).toString('hex'));
    await page.getByRole('button', { name: 'Crear mi cuenta' }).click();
    await expect(page.locator('#password_confirmation-error')).toHaveText('Las contraseñas no coinciden.');
    await expect(page.getByLabel('Confirmar contraseña', { exact: true })).toBeFocused();
    await expect(page.getByLabel('Confirmar contraseña', { exact: true })).toHaveAttribute('aria-describedby', /password_confirmation-error/);
    await passwordInput(page, 'password_confirmation', password);
    await page.keyboard.press('Tab');
    await expect(page.getByLabel('Confirmar contraseña', { exact: true })).toHaveAttribute('aria-invalid', 'false');
    await expect(page.getByRole('button', { name: 'Crear mi cuenta' })).toBeEnabled();
    await assertNoHorizontalScroll(page);
});

test('POST de registro, verificación, login y recuperación funcionan sin JavaScript', async ({ browser, request }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, baseURL: 'http://test-app:8000' });
    const page = await context.newPage();
    try {
        await page.goto('/login');
        await page.getByRole('button', { name: 'Ingresar', exact: true }).click();
        await expect(page.getByLabel('Email', { exact: true })).toHaveAttribute('aria-invalid', 'true');
        await expect(page.locator('#email-error')).toBeVisible();
        await expect(page.locator('#password-error')).toBeVisible();
        const person = await register(page, account('Persona Sin JavaScript'));
        const verification = await capturedLink(request, person.email, '/verificar-email/');
        await openSecurityLink(page, verification, '/dashboard');
        await expect(page.getByRole('heading', { name: `Hola, ${person.name}.` })).toBeVisible();
        await logout(page);
        await login(page, person);
        await expect.poll(() => new URL(page.url()).pathname).toBe('/dashboard');
        await logout(page);
        await requestRecovery(page, person.email);
        const reset = await capturedLink(request, person.email, '/restablecer-contrasena/');
        await openSecurityLink(page, reset, new URL(reset).pathname);
        const newPassword = randomBytes(20).toString('hex');
        await passwordInput(page, 'password', newPassword);
        await passwordInput(page, 'password_confirmation', newPassword);
        await page.locator('form[action$="/restablecer-contrasena"] button[type="submit"]').click();
        await expect.poll(() => new URL(page.url()).pathname).toBe('/login');
        await login(page, { ...person, password: newPassword });
        await expect.poll(() => new URL(page.url()).pathname).toBe('/dashboard');
    } finally {
        await context.close();
    }
});
