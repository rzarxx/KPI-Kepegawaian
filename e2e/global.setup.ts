import { test as setup, expect } from '@playwright/test';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const AUTH_FILE = path.join(__dirname, '.auth', 'super-admin.json');

setup('authenticate as Super Admin', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('super.admin@kpi.local.test');
    await page.locator('input[name="password"]').fill('KpiLokal2026!');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL('**/dashboard');
    await expect(page.locator('body')).toBeVisible();
    await page.context().storageState({ path: AUTH_FILE });
});
