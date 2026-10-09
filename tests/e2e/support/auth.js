import { expect } from '@playwright/test';
import { randomBytes, randomUUID } from 'node:crypto';

export const viewports = [
    { width: 320, height: 740 },
    { width: 390, height: 844 },
    { width: 430, height: 932 },
    { width: 768, height: 1024 },
    { width: 1280, height: 720 },
    { width: 1440, height: 900 },
    { width: 1920, height: 1080 },
    { width: 2560, height: 1440 },
    { width: 3840, height: 2160 },
];

export const recoveryStatus = 'Si ese email está asociado a una cuenta, recibirás las instrucciones para recuperar el acceso';
const mailpitURL = 'http://test-mailpit:8025';

export function account(name = 'Valentina Prueba') {
    return { name, email: `e2e-${randomUUID()}@example.test`, password: randomBytes(20).toString('hex') };
}

// Passwords are never passed to Playwright's fill() call log, reports or screenshots.
export async function passwordInput(page, name, value) {
    await page.locator(`input[name="${name}"]`).evaluate((input, secret) => {
        input.value = secret;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }, value);
}

export async function register(page, person = account()) {
    await page.goto('/registro');
    await page.getByLabel('Tu nombre', { exact: true }).fill(person.name);
    await page.getByLabel('Email', { exact: true }).fill(person.email);
    await passwordInput(page, 'password', person.password);
    await passwordInput(page, 'password_confirmation', person.password);
    await page.locator('form[action$="/registro"] button[type="submit"]').click();
    await expect.poll(() => new URL(page.url()).pathname).toBe('/verificar-email');
    await expect(page.getByRole('heading', { name: 'Verificá tu email.' })).toBeVisible();
    await expect(page.getByText(person.email, { exact: true })).toBeVisible();
    return person;
}

export async function login(page, person) {
    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill(person.email.toUpperCase());
    await passwordInput(page, 'password', person.password);
    await page.locator('form[action$="/login"] button[type="submit"]').click();
}

export async function logout(page) {
    await page.locator('form[action$="/logout"] button[type="submit"]').click();
    await expect.poll(() => new URL(page.url()).pathname).toBe('/login');
}

function messageIsFor(message, email) {
    return message.To?.some(recipient => recipient.Address?.toLowerCase() === email.toLowerCase());
}

// Read the real isolated SMTP capture, never a log transport or a fake notification.
// Email bodies and signed links stay in memory; no mailboxes or security URLs are published.
export async function capturedLink(request, email, pathname) {
    let result;
    await expect.poll(async () => {
        const response = await request.get(`${mailpitURL}/api/v1/messages?limit=100`);
        if (!response.ok()) return false;
        const mailbox = await response.json();
        for (const summary of (mailbox.messages ?? []).filter(message => messageIsFor(message, email))) {
            const messageResponse = await request.get(`${mailpitURL}/api/v1/message/${summary.ID}`);
            if (!messageResponse.ok()) continue;
            const message = await messageResponse.json();
            const content = `${message.HTML ?? ''}\n${message.Text ?? ''}`.replaceAll('&amp;', '&');
            for (const match of content.matchAll(/https?:\/\/[^\s<>"']+/g)) {
                let candidate;
                try { candidate = new URL(match[0]); } catch { continue; }
                if (candidate.origin === 'http://test-app:8000' && candidate.pathname.startsWith(pathname)) {
                    result = candidate.href;
                    return true;
                }
            }
        }
        return false;
    }, { timeout: 30_000, message: 'El worker debe entregar un enlace utilizable al capturador SMTP aislado.' }).toBe(true);
    return result;
}

export async function expectPasswordChangeMail(request, email, secrets) {
    await expect.poll(async () => {
        const response = await request.get(`${mailpitURL}/api/v1/messages?limit=100`);
        const mailbox = await response.json();
        for (const summary of (mailbox.messages ?? []).filter(message => messageIsFor(message, email))) {
            const messageResponse = await request.get(`${mailpitURL}/api/v1/message/${summary.ID}`);
            const message = await messageResponse.json();
            const content = `${message.Subject ?? summary.Subject}\n${message.Text ?? ''}\n${message.HTML ?? ''}`;
            if (/contraseña.*(?:actualiz|cambi)|(?:actualiz|cambi).*contraseña/is.test(content)
                && !content.includes('/restablecer-contrasena/')) {
                return secrets.every(secret => !content.includes(secret));
            }
        }
        return false;
    }, { timeout: 30_000, message: 'El cambio debe generar un correo informativo sin contraseñas.' }).toBe(true);
}

export async function openSecurityLink(page, link, expectedPath) {
    const url = new URL(link);
    if (url.origin !== 'http://test-app:8000') throw new Error('Enlace de seguridad fuera de la instancia aislada.');
    // Avoid printing a reset token or a verification signature when a navigation fails.
    await Promise.all([
        page.waitForEvent('framenavigated', { predicate: frame => frame === page.mainFrame() }),
        page.evaluate(destination => { window.location.assign(destination); }, link),
    ]);
    await expect.poll(() => new URL(page.url()).pathname === expectedPath, { timeout: 15_000, message: 'El enlace debe abrir la pantalla prevista.' }).toBe(true);
    await page.waitForLoadState('domcontentloaded');
}

export async function requestRecovery(page, email) {
    await page.goto('/olvide-mi-contrasena');
    await page.getByLabel('Email', { exact: true }).fill(email);
    await page.locator('form[action$="/olvide-mi-contrasena"] button[type="submit"]').click();
    await expect(page.getByRole('status')).toContainText(recoveryStatus);
    return page.getByRole('status').innerText();
}

export async function assertNoHorizontalScroll(page) {
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    const outside = await page.locator('main input:not([type="hidden"]), main button, main h1, main h2, main .panel').evaluateAll(elements =>
        elements.filter(element => {
            const rect = element.getBoundingClientRect();
            return rect.width > 0 && (rect.left < -1 || rect.right > window.innerWidth + 1);
        }).length,
    );
    expect(outside).toBe(0);
}

export async function cleanScreenshot(page, path, options = {}) {
    const secretsVisible = await page.locator('input[type="password"]').evaluateAll(inputs => inputs.some(input => input.value !== ''));
    if (secretsVisible) throw new Error('Captura rechazada: el formulario contiene una contraseña.');
    await page.screenshot({ path, ...options });
}
