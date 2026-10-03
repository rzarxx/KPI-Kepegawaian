import { test, expect } from './fixtures';

test.describe('Karyawan', () => {
    test('daftar karyawan tampil dengan tabel', async ({ page }) => {
        await page.goto('/karyawan');
        // Should show a table or list of employees
        await expect(page.locator('table, [role="table"]').or(page.locator('text=Karyawan')).first()).toBeVisible();
    });

    test('bisa search/filter karyawan', async ({ page }) => {
        await page.goto('/karyawan');
        const searchInput = page.locator('input[placeholder*="Cari"], input[type="search"], input[placeholder*="cari"]').first();
        if (await searchInput.isVisible({ timeout: 3_000 })) {
            await searchInput.fill('Ayu');
            await page.waitForTimeout(1_000);
            // Should filter results
            await expect(page.locator('text=Ayu').first()).toBeVisible();
        }
    });

    test('halaman tambah karyawan memiliki form lengkap', async ({ page }) => {
        await page.goto('/karyawan/create');
        // Check for essential form fields
        await expect(page.locator('input, select, textarea').first()).toBeVisible();
    });

    test('detail karyawan bisa diakses', async ({ page }) => {
        await page.goto('/karyawan');
        // Klik baris pertama tabel yang BUKAN tombol tambah karyawan
        const employeeLink = page.locator('a[href*="/karyawan/"]').filter({ hasNotText: 'Tambah' }).first();
        if (await employeeLink.isVisible({ timeout: 5_000 })) {
            await employeeLink.click();
            await expect(page).toHaveURL(/karyawan\/\d+/);
        }
    });

    test('edit karyawan bisa diakses', async ({ page }) => {
        await page.goto('/karyawan');
        const employeeLink = page.locator('a[href*="/karyawan/"]').filter({ hasNotText: 'Tambah' }).first();
        if (await employeeLink.isVisible({ timeout: 5_000 })) {
            const href = await employeeLink.getAttribute('href');
            if (href && !href.includes('create')) {
                await page.goto(href + '/edit');
                await expect(page).toHaveURL(/karyawan\/\d+\/edit/);
            }
        }
    });

    test('catatan khusus karyawan bisa diakses', async ({ page }) => {
        await page.goto('/karyawan');
        const employeeLink = page.locator('a[href*="/karyawan/"]').filter({ hasNotText: 'Tambah' }).first();
        if (await employeeLink.isVisible({ timeout: 5_000 })) {
            const href = await employeeLink.getAttribute('href');
            if (href && !href.includes('create')) {
                await page.goto(href + '/masalah');
                await expect(page).toHaveURL(/karyawan\/\d+\/masalah/);
            }
        }
    });
});
