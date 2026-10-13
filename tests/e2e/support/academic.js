import { expect } from '@playwright/test';
import { expectPath } from './auth.js';

export async function choosePlan(page, career = 'Análisis de Sistemas') {
    await page.getByRole('radio', { name: new RegExp(career) }).check();
    const confirmation = page.locator('[name="confirm_change"]');
    if (await confirmation.count()) await confirmation.check();
    await page.getByRole('button', { name: 'Continuar con mis materias' }).click();
    await expectPath(page, '/configuracion-academica/materias');
}

export async function confirmDraft(page) {
    await page.getByRole('button', { name: 'Revisar resumen' }).click();
    await expectPath(page, '/configuracion-academica/resumen');
    await page.locator('[name="confirm"]').check();
    await page.getByRole('button', { name: 'Confirmar y ver mi inicio' }).click();
    await expectPath(page, '/dashboard');
}

export async function configureAcademic(page) {
    await expectPath(page, '/configuracion-academica');
    await choosePlan(page);
    await confirmDraft(page);
    await expect(page.getByText('0 de 23 materias aprobadas')).toBeVisible();
}
