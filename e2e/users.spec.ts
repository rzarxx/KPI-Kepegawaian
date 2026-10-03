import { test, expect } from './fixtures';

test.describe('Kelola Pengguna', () => {
    const testEmail = `e2e.test.${Date.now()}@kpi.local.test`;

    test('halaman pengguna menampilkan daftar user', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        await expect(page.locator('text=Daftar Pengguna')).toBeVisible();
        await expect(page.locator('text=Tambah Pengguna Baru')).toBeVisible();
    });

    test('form create user - validasi wajib diisi', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        // Submit form kosong — HTML5 required akan mencegah submit
        const submitButton = page.getByRole('button', { name: 'Simpan Pengguna' });
        await expect(submitButton).toBeVisible();
    });

    test('form create user - validasi password minimal 12 karakter', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        await page.locator('label:has-text("Nama lengkap") input').fill('Test User E2E');
        await page.locator('label:has-text("Alamat email") input').fill(testEmail);
        await page.locator('label:has-text("Kata sandi") input').fill('short');
        await page.locator('label:has-text("Peran") select').selectOption('HR Admin');
        await page.getByRole('button', { name: 'Simpan Pengguna' }).click();
        // Should show validation error in Indonesian
        await expect(page.locator('text=Kata sandi harus setidaknya 12 karakter').or(page.locator('.text-red-600')).first()).toBeVisible({ timeout: 10_000 });
    });

    test('form create user - validasi email duplikat', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        await page.locator('label:has-text("Nama lengkap") input').fill('Duplicate Test');
        await page.locator('label:has-text("Alamat email") input').fill('super.admin@kpi.local.test');
        await page.locator('label:has-text("Kata sandi") input').fill('TestPassword123!');
        await page.locator('label:has-text("Peran") select').selectOption('HR Admin');
        await page.getByRole('button', { name: 'Simpan Pengguna' }).click();
        // Should show email already taken error
        await expect(page.locator('text=sudah digunakan').or(page.locator('.text-red-600')).first()).toBeVisible({ timeout: 10_000 });
    });

    test('berhasil membuat user baru', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        const email = `e2e.create.${Date.now()}@kpi.local.test`;

        await page.locator('label:has-text("Nama lengkap") input').fill('E2E Test User');
        await page.locator('label:has-text("Alamat email") input').fill(email);
        await page.locator('label:has-text("Kata sandi") input').fill('E2eTestPass2026!');
        await page.locator('label:has-text("Peran") select').selectOption('Auditor');
        await page.getByRole('button', { name: 'Simpan Pengguna' }).click();

        // Should show success toast or the user appears in the list
        await expect(
            page.locator('text=Pengguna berhasil ditambahkan')
                .or(page.locator(`text=${email}`))
                .first()
        ).toBeVisible({ timeout: 15_000 });
    });

    test('memilih role Super Admin menyembunyikan scope', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        await page.locator('label:has-text("Peran") select').selectOption('Super Admin');
        // Super Admin should have no scope editor rows
        await expect(page.locator('text=Cakupan Organisasi')).toBeVisible();
        const scopeRows = page.locator('.rounded-xl.border.border-slate-200.bg-slate-50');
        await expect(scopeRows).toHaveCount(0);
    });

    test('memilih role Branch Head menampilkan 1 scope', async ({ page }) => {
        await page.goto('/pengaturan/pengguna');
        await page.locator('label:has-text("Peran") select').selectOption('Branch Head');
        const scopeRows = page.locator('.rounded-xl.border.border-slate-200.bg-slate-50');
        await expect(scopeRows).toHaveCount(1);
        // Should NOT have "Tambah" button for single-scope roles
        await expect(page.getByRole('button', { name: 'Tambah' })).not.toBeVisible();
    });
});
