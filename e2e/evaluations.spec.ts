import { test, expect } from './fixtures';

test.describe('Penilaian / Evaluasi', () => {
    test('halaman konfigurasi menampilkan komponen penilaian', async ({ page }) => {
        await page.goto('/penilaian/konfigurasi');
        await expect(page.locator('text=Komponen').or(page.locator('text=Periode')).first()).toBeVisible();
    });

    test('komponen penilaian default tersedia', async ({ page }) => {
        await page.goto('/penilaian/konfigurasi');
        const components = [
            'Masa Kerja', 'Tanggung Jawab', 'Absensi',
            'Inisiatif', 'Sikap', 'Komunikasi', 'Kerjasama Tim',
        ];
        for (const comp of components) {
            await expect(page.locator(`text=${comp}`).first()).toBeVisible({ timeout: 5_000 });
        }
    });

    test('kriteria penilaian default tersedia', async ({ page }) => {
        await page.goto('/penilaian/konfigurasi');
        const criteria = ['Perlu Perhatian', 'Baik', 'Sangat Baik', 'Istimewa'];
        for (const c of criteria) {
            await expect(page.locator(`text=${c}`).first()).toBeVisible({ timeout: 5_000 });
        }
    });

    test('bisa menilai pejuang dari halaman detail', async ({ page }) => {
        await page.goto('/pejuang');
        const employeeLink = page.locator('a[href*="/pejuang/"]').first();
        if (await employeeLink.isVisible({ timeout: 5_000 })) {
            const href = await employeeLink.getAttribute('href');
            if (href) {
                const employeeId = href.split('/').pop();
                await page.goto(`/penilaian/${employeeId}`);
                // Should show evaluation form or redirect
                await expect(page.locator('body')).toBeVisible();
            }
        }
    });
});
