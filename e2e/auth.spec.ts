import { test, expect } from '@playwright/test';
import { E2E_PASSWORD } from './environment';

async function loginAsSuperAdmin(page: import('@playwright/test').Page) {
    await page.goto('/login');
    const email = page.getByLabel('Email');
    const password = page.locator('input[name="password"]');
    await email.fill('super.admin@kpi.local.test');
    await password.fill(E2E_PASSWORD);
    await expect(email).toHaveValue('super.admin@kpi.local.test');
    await expect(password).toHaveValue(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL('**/dashboard', { timeout: 30_000, waitUntil: 'domcontentloaded' });
}

test.describe('Autentikasi', () => {
    test('halaman login bisa diakses', async ({ page }) => {
        await page.goto('/login');
        await expect(page).toHaveTitle(/Masuk|Login/i);
        await expect(page.getByLabel('Email')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Masuk' })).toBeVisible();
    });

    test('login gagal dengan kredensial salah', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill('wrong@test.com');
        await page.locator('input[name="password"]').fill('wrongpassword1');
        await page.getByRole('button', { name: 'Masuk' }).click();
        await expect(page.locator('text=Email atau kata sandi salah').or(page.locator('.text-red-600'))).toBeVisible({ timeout: 10_000 });
    });

    test('login berhasil dengan Super Admin', async ({ page }) => {
        await loginAsSuperAdmin(page);
        await expect(page).toHaveURL(/dashboard/);
    });

    test('login ditolak untuk akun nonaktif', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill('employee@kpi.local.test');
        await page.locator('input[name="password"]').fill(E2E_PASSWORD);
        await page.getByRole('button', { name: 'Masuk' }).click();
        // Should show error or not redirect to dashboard
        await expect(page).not.toHaveURL(/dashboard/, { timeout: 5_000 });
    });

    test('halaman lupa password bisa diakses', async ({ page }) => {
        await page.goto('/forgot-password');
        await expect(page.getByLabel('Email')).toBeVisible();
    });

    test('toggle visibility password bekerja', async ({ page }) => {
        await page.goto('/login');
        const passwordInput = page.locator('input[name="password"]');
        await expect(passwordInput).toHaveAttribute('type', 'password');
        // Click eye toggle button
        const toggle = page.locator('button[aria-label*="password"], button[aria-label*="sandi"], [data-toggle-password]').first();
        if (await toggle.isVisible()) {
            await toggle.click();
            await expect(passwordInput).toHaveAttribute('type', 'text');
        }
    });

    test('redirect ke login jika belum autentikasi', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/login/);
    });

    test('logout berhasil', async ({ page }) => {
        await loginAsSuperAdmin(page);
        await page.getByRole('button', { name: 'Akun Lokal - Super Admin' }).click();
        const logoutResponse = page.waitForResponse((response) => (
            response.request().method() === 'POST' && new URL(response.url()).pathname === '/logout'
        ));
        await page.getByRole('button', { name: 'Keluar' }).click({ noWaitAfter: true });
        expect((await logoutResponse).status()).toBeLessThan(400);
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/login$/);
    });
});
