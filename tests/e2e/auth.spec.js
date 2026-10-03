import { test, expect } from '@playwright/test';
import { randomBytes, randomUUID } from 'node:crypto';

async function register(page, name = 'Valentina Prueba') {
    const email = `e2e-${randomUUID()}@example.test`;
    const password = randomBytes(20).toString('hex');
    await page.goto('/registro');
    await page.getByLabel('Tu nombre', { exact: true }).fill(name);
    await page.getByLabel('Email', { exact: true }).fill(email);
    await page.getByLabel('Contraseña', { exact: true }).fill(password);
    await page.getByLabel('Confirmar contraseña', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Crear mi cuenta' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
    return { email, password };
}

async function assertNoHorizontalScroll(page) {
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
}

test('registro → dashboard → logout → login; escritorio y reflow móvil', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const { email, password } = await register(page);
    await expect(page.getByRole('heading', { name: 'Hola, Valentina Prueba.' })).toBeVisible();
    await expect(page.getByText('Sin datos', { exact: true })).toBeVisible();
    await expect(page.locator('html')).toHaveAttribute('lang', 'es');
    await assertNoHorizontalScroll(page);
    await page.screenshot({ path: 'docs/evidence/dashboard-1440x900.png' });

    await page.setViewportSize({ width: 390, height: 844 });
    await assertNoHorizontalScroll(page);
    await expect(page.getByRole('navigation', { name: 'Navegación principal' })).toBeHidden();
    const menu = page.getByRole('button', { name: 'Abrir o cerrar navegación' });
    await menu.click();
    await expect(menu).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByRole('navigation')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(menu).toBeFocused();
    await expect(page.getByRole('navigation')).toBeHidden();
    await page.screenshot({ path: 'docs/evidence/dashboard-390x844.png' });
    await page.screenshot({ path: 'docs/evidence/dashboard-390-full.png', fullPage: true });

    await page.setViewportSize({ width: 320, height: 844 });
    await assertNoHorizontalScroll(page);
    await page.screenshot({ path: 'docs/evidence/dashboard-320-full.png', fullPage: true });
    await page.getByRole('button', { name: 'Cerrar sesión' }).click();
    await expect(page).toHaveURL(/\/login$/);
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
    await page.getByLabel('Email', { exact: true }).fill(email.toUpperCase());
    await page.getByLabel('Contraseña', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Ingresar', exact: true }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.getByRole('heading', { name: 'Hola, Valentina Prueba.' })).toBeVisible();
    expect(errors).toEqual([]);
});

test('logout exige POST con CSRF válido en el navegador real', async ({ page }) => {
    await register(page, 'Persona CSRF');
    const status = await page.evaluate(async () => {
        const response = await fetch('/logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=invalido',
        });
        return response.status;
    });
    expect(status).toBe(419);
    await page.reload();
    await expect(page.getByRole('heading', { name: 'Hola, Persona CSRF.' })).toBeVisible();
    await page.getByRole('button', { name: 'Cerrar sesión' }).click();
    await expect(page).toHaveURL(/\/login$/);
});

test('login inválido asocia errores y admite navegación por teclado a 320 px', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 844 });
    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill(`ausente-${randomUUID()}@example.test`);
    await page.getByLabel('Contraseña', { exact: true }).fill(randomBytes(10).toString('hex'));
    await page.getByRole('button', { name: 'Ingresar', exact: true }).click();
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(page.getByLabel('Email', { exact: true })).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#email-help')).toHaveText('El email o la contraseña no son correctos.');
    await page.getByLabel('Contraseña', { exact: true }).focus();
    await page.keyboard.press('Tab');
    await expect(page.getByRole('button', { name: 'Ingresar', exact: true })).toBeFocused();
    await assertNoHorizontalScroll(page);
});
