import { test as setup, expect } from '@playwright/test';
import { fileURLToPath } from 'url';
import path from 'path';
import { E2E_PASSWORD } from './environment';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const AUTH_FILE = path.join(__dirname, '.auth', 'super-admin.json');

setup('authenticate as Super Admin', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('super.admin@kpi.local.test');
    const password = page.locator('input[name="password"]');
    await password.fill(E2E_PASSWORD);
    await expect(password).toHaveValue(E2E_PASSWORD);
    const loginResponse = page.waitForResponse((response) => (
        response.request().method() === 'POST' && new URL(response.url()).pathname === '/login'
    ));
    await page.getByRole('button', { name: 'Masuk' }).click();
    const response = await loginResponse;
    expect(response.status()).toBeLessThan(400);
    await page.waitForURL('**/dashboard', { timeout: 30_000, waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
    await page.context().storageState({ path: AUTH_FILE });
});
