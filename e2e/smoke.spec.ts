import { test, expect } from './fixtures';

test.describe('Navigasi & Smoke Test Halaman', () => {
    test('dashboard bisa diakses', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/dashboard/);
        await expect(page.locator('text=Beranda').first()).toBeVisible();
    });

    test('sidebar menampilkan semua menu Super Admin', async ({ page }) => {
        await page.goto('/dashboard');
        const sidebar = page.locator('aside').first();
        await expect(sidebar.locator('text=Beranda')).toBeVisible();
        await expect(sidebar.locator('text=Karyawan')).toBeVisible();
        await expect(sidebar.locator('text=Catatan Khusus')).toBeVisible();
        await expect(sidebar.locator('text=Penilaian')).toBeVisible();
        await expect(sidebar.locator('text=Laporan')).toBeVisible();
        await expect(sidebar.locator('text=Riwayat Aktivitas')).toBeVisible();
        await expect(sidebar.locator('text=Notifikasi')).toBeVisible();
        await expect(sidebar.locator('text=Organisasi')).toBeVisible();
        await expect(sidebar.locator('text=Pengguna')).toBeVisible();
        await expect(sidebar.locator('text=Tampilan')).toBeVisible();
    });

    test('halaman karyawan bisa diakses', async ({ page }) => {
        await page.goto('/karyawan');
        await expect(page).toHaveURL(/karyawan/);
        await expect(page.locator('h1, h2').filter({ hasText: /Karyawan/i }).first()).toBeVisible();
    });

    test('halaman tambah karyawan bisa diakses', async ({ page }) => {
        await page.goto('/karyawan/create');
        await expect(page).toHaveURL(/karyawan\/create/);
    });

    test('halaman catatan khusus bisa diakses', async ({ page }) => {
        await page.goto('/karyawan-bermasalah');
        await expect(page).toHaveURL(/karyawan-bermasalah/);
    });

    test('halaman konfigurasi penilaian bisa diakses', async ({ page }) => {
        await page.goto('/penilaian/konfigurasi');
        await expect(page).toHaveURL(/penilaian\/konfigurasi/);
    });

    test('halaman laporan bisa diakses', async ({ page }) => {
        await page.goto('/laporan');
        await expect(page).toHaveURL(/laporan/);
    });

    test('halaman riwayat aktivitas bisa diakses', async ({ page }) => {
        await page.goto('/audit-aktivitas');
        await expect(page).toHaveURL(/audit-aktivitas/);
    });

    test('halaman notifikasi bisa diakses', async ({ page }) => {
        await page.goto('/notifikasi');
        await expect(page).toHaveURL(/notifikasi/);
    });

    test('halaman organisasi bisa diakses', async ({ page }) => {
        await page.goto('/pengaturan/organisasi');
        await expect(page).toHaveURL(/pengaturan\/organisasi/);
    });

    test('halaman pengguna bisa diakses', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        await expect(page).toHaveURL(/pengaturan\/pengguna/);
    });

    test('halaman tampilan bisa diakses', async ({ page }) => {
        await page.goto('/pengaturan/tampilan');
        await expect(page).toHaveURL(/pengaturan\/tampilan/);
    });

    test('halaman profil bisa diakses', async ({ page }) => {
        await page.goto('/profile');
        await expect(page).toHaveURL(/profile/);
    });

    test('logo KPI tampil di sidebar', async ({ page }) => {
        await page.goto('/dashboard');
        const logo = page.locator('aside').first().locator('svg, img').first();
        await expect(logo).toBeVisible();
    });

    test('tidak ada broken asset (503/404 pada JS/CSS)', async ({ page }) => {
        const errors: string[] = [];
        page.on('response', (response) => {
            const url = response.url();
            if ((url.includes('.js') || url.includes('.css')) && response.status() >= 400) {
                errors.push(`${response.status()} ${url}`);
            }
        });
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        expect(errors).toHaveLength(0);
    });

    test('manifest.webmanifest valid', async ({ page }) => {
        const response = await page.goto('/manifest.webmanifest');
        expect(response?.status()).toBe(200);
        const body = await response?.text();
        const manifest = JSON.parse(body!);
        expect(manifest.name).toBe('KPI Kepegawaian');
        expect(manifest.icons).toBeDefined();
        expect(manifest.icons.length).toBeGreaterThan(0);
    });
});
