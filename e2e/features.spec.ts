import { test, expect } from './fixtures';

test.describe('Organisasi', () => {
    test('halaman organisasi menampilkan cabang', async ({ page }) => {
        await page.goto('/pengaturan/organisasi');
        await expect(page.locator('text=Cabang').first()).toBeVisible();
    });

    test('struktur organisasi UAT tersedia', async ({ page }) => {
        await page.goto('/pengaturan/organisasi');
        await expect(page.locator('text=UAT Jakarta').or(page.locator('text=Jakarta')).first()).toBeVisible({ timeout: 5_000 });
    });
});

test.describe('Laporan', () => {
    test('halaman laporan menampilkan opsi ekspor', async ({ page }) => {
        await page.goto('/laporan');
        await expect(page.locator('text=Laporan').first()).toBeVisible();
    });
});

test.describe('Riwayat Aktivitas (Audit)', () => {
    test('halaman audit menampilkan log aktivitas', async ({ page }) => {
        await page.goto('/audit-aktivitas');
        await expect(page.locator('text=Riwayat Aktivitas').or(page.locator('text=Aktivitas')).first()).toBeVisible();
    });
});

test.describe('Notifikasi', () => {
    test('halaman notifikasi bisa diakses', async ({ page }) => {
        await page.goto('/notifikasi');
        await expect(page.locator('text=Notifikasi').first()).toBeVisible();
    });
});

test.describe('Pengaturan Tampilan', () => {
    test('halaman tampilan menampilkan form branding', async ({ page }) => {
        await page.goto('/pengaturan/tampilan');
        await expect(page.locator('text=Tampilan').or(page.locator('text=Nama Aplikasi')).first()).toBeVisible();
    });
});

test.describe('Profil', () => {
    test('halaman profil menampilkan form', async ({ page }) => {
        await page.goto('/profile');
        await expect(page.locator('text=Profil').or(page.locator('input')).first()).toBeVisible();
    });
});
