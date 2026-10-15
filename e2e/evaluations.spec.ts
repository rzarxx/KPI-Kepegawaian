import { test, expect } from './fixtures';
import { E2E_PASSWORD } from './environment';

test.describe('Penilaian', () => {
    test('halaman konfigurasi menampilkan komponen dan kriteria default', async ({ page }) => {
        await page.goto('/penilaian/konfigurasi');

        for (const component of [
            'Masa Kerja',
            'Tanggung Jawab',
            'Absensi',
            'Inisiatif',
            'Sikap',
            'Komunikasi',
            'Kerjasama Tim',
        ]) {
            await expect(page.getByText(component, { exact: true }).first()).toBeVisible();
        }

        for (const criterion of ['Perlu Perhatian', 'Baik', 'Sangat Baik', 'Istimewa']) {
            await expect(page.getByText(criterion, { exact: true }).first()).toBeVisible();
        }
    });

    test('Kasubdiv menyimpan draf lalu menyelesaikan penilaian', async ({ browser }) => {
        const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
        const page = await context.newPage();

        await page.goto('/login', { waitUntil: 'domcontentloaded' });
        const email = page.getByLabel('Email');
        const password = page.locator('input[name="password"]');
        await email.fill('sub.division.head@kpi.local.test');
        await password.fill(E2E_PASSWORD);
        await expect(email).toHaveValue('sub.division.head@kpi.local.test');
        await expect(password).toHaveValue(E2E_PASSWORD);
        await page.getByRole('button', { name: 'Masuk' }).click();
        await page.waitForURL('**/dashboard', { timeout: 60_000, waitUntil: 'domcontentloaded' });

        await page.goto('/penilaian');
        await expect(page.getByRole('heading', { name: 'Daftar Tugas Kasubdiv' })).toBeVisible();
        await expect(page.getByText('Periode E2E Aktif', { exact: true })).toBeVisible();

        await page.getByRole('link', { name: 'Mulai Penilaian' }).first().click();
        await expect(page.getByRole('heading', { name: 'Nilai Kinerja Karyawan' })).toBeVisible();

        for (const input of await page.locator('input[aria-label^="Nilai "]').all()) {
            await input.fill('80');
        }

        await Promise.all([
            page.waitForURL((url) => url.pathname === '/penilaian', { waitUntil: 'domcontentloaded' }),
            page.getByRole('button', { name: 'Simpan Draf' }).click(),
        ]);
        await expect(page.getByText('Draf', { exact: true }).first()).toBeVisible();

        await page.getByRole('link', { name: 'Lanjutkan Draf' }).first().click();
        await page.getByRole('button', { name: 'Selesaikan Penilaian' }).click();
        await Promise.all([
            page.waitForURL((url) => url.pathname === '/penilaian', { waitUntil: 'domcontentloaded' }),
            page.getByRole('button', { name: 'Ya, Selesaikan' }).click(),
        ]);
        await expect(page.getByText('Selesai', { exact: true }).first()).toBeVisible();

        await context.close();
    });
});
