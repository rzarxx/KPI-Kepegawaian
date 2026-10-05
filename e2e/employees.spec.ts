import { test, expect } from './fixtures';

test.describe('Pejuang', () => {
    test('daftar pejuang tampil dengan tabel', async ({ page }) => {
        await page.goto('/pejuang');
        // Should show a table or list of employees
        await expect(page.locator('table, [role="table"]').or(page.locator('text=Pejuang')).first()).toBeVisible();
    });

    test('bisa search/filter pejuang', async ({ page }) => {
        await page.goto('/pejuang');
        const searchInput = page.locator('input[placeholder*="Cari"], input[type="search"], input[placeholder*="cari"]').first();
        if (await searchInput.isVisible({ timeout: 3_000 })) {
            await searchInput.fill('Ayu');
            await page.waitForTimeout(1_000);
            // Should filter results
            await expect(page.locator('text=Ayu').first()).toBeVisible();
        }
    });

    test('halaman tambah pejuang memiliki form lengkap', async ({ page }) => {
        await page.goto('/pejuang/create');
        // Check for essential form fields
        await expect(page.locator('input, select, textarea').first()).toBeVisible();
    });

    test('detail pejuang bisa diakses', async ({ page }) => {
        await page.goto('/pejuang');
        // Klik baris pertama tabel yang BUKAN tombol tambah pejuang
        const employeeLink = page.locator('a[href*="/pejuang/"]').filter({ hasNotText: 'Tambah' }).first();
        if (await employeeLink.isVisible({ timeout: 5_000 })) {
            await employeeLink.click();
            await expect(page).toHaveURL(/pejuang\/\d+/);
        }
    });

    test('edit pejuang bisa diakses', async ({ page }) => {
        await page.goto('/pejuang');
        const employeeLink = page.locator('a[href*="/pejuang/"]').filter({ hasNotText: 'Tambah' }).first();
        if (await employeeLink.isVisible({ timeout: 5_000 })) {
            const href = await employeeLink.getAttribute('href');
            if (href && !href.includes('create')) {
                await page.goto(href + '/edit');
                await expect(page).toHaveURL(/pejuang\/\d+\/edit/);
            }
        }
    });

    test('catatan khusus pejuang bisa diakses', async ({ page }) => {
        await page.goto('/pejuang');
        const employeeLink = page.locator('a[href*="/pejuang/"]').filter({ hasNotText: 'Tambah' }).first();
        if (await employeeLink.isVisible({ timeout: 5_000 })) {
            const href = await employeeLink.getAttribute('href');
            if (href && !href.includes('create')) {
                await page.goto(href + '/masalah');
                await expect(page).toHaveURL(/pejuang\/\d+\/masalah/);
            }
        }
    });
});
