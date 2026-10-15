import { test, expect, type Page } from '@playwright/test';
import { E2E_PASSWORD } from './environment';

type RoleCase = {
    email: string;
    role: string;
    visiblePaths: string[];
    forbiddenPaths: string[];
};

const COMMON_PATHS = [
    '/dashboard',
    '/pejuang',
    '/pejuang-bermasalah',
    '/penilaian',
    '/target',
    '/notifikasi',
    '/pengaturan/organisasi',
];

const ROLES: RoleCase[] = [
    {
        email: 'super.admin@kpi.local.test',
        role: 'Super Admin',
        visiblePaths: [...COMMON_PATHS, '/laporan', '/audit-aktivitas', '/pengaturan/pengguna', '/pengaturan/tampilan'],
        forbiddenPaths: [],
    },
    {
        email: 'hr.admin@kpi.local.test',
        role: 'HR Admin',
        visiblePaths: [...COMMON_PATHS, '/laporan', '/pengaturan/pengguna'],
        forbiddenPaths: ['/audit-aktivitas', '/pengaturan/tampilan'],
    },
    {
        email: 'hr.manager@kpi.local.test',
        role: 'HR Manager',
        visiblePaths: [...COMMON_PATHS, '/laporan'],
        forbiddenPaths: ['/audit-aktivitas', '/pengaturan/pengguna', '/pengaturan/tampilan'],
    },
    {
        email: 'branch.head@kpi.local.test',
        role: 'Branch Head',
        visiblePaths: [...COMMON_PATHS, '/laporan'],
        forbiddenPaths: ['/audit-aktivitas', '/pengaturan/pengguna', '/pengaturan/tampilan'],
    },
    {
        email: 'division.head@kpi.local.test',
        role: 'Division Head',
        visiblePaths: [...COMMON_PATHS, '/laporan'],
        forbiddenPaths: ['/audit-aktivitas', '/pengaturan/pengguna', '/pengaturan/tampilan'],
    },
    {
        email: 'sub.division.head@kpi.local.test',
        role: 'Sub Division Head',
        visiblePaths: COMMON_PATHS,
        forbiddenPaths: ['/laporan', '/audit-aktivitas', '/pengaturan/pengguna', '/pengaturan/tampilan'],
    },
    {
        email: 'auditor@kpi.local.test',
        role: 'Auditor',
        visiblePaths: [...COMMON_PATHS, '/laporan'],
        forbiddenPaths: ['/audit-aktivitas', '/pengaturan/pengguna', '/pengaturan/tampilan'],
    },
];

async function login(page: Page, emailAddress: string) {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    const email = page.getByLabel('Email');
    const password = page.locator('input[name="password"]');
    await email.fill(emailAddress);
    await password.fill(E2E_PASSWORD);
    await expect(email).toHaveValue(emailAddress);
    await expect(password).toHaveValue(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL('**/dashboard', { timeout: 60_000, waitUntil: 'domcontentloaded' });
}

for (const roleCase of ROLES) {
    test(`${roleCase.role}: menu sesuai dan URL sensitif ditolak`, async ({ page }) => {
        await login(page, roleCase.email);
        const sidebar = page.locator('aside').first().locator('nav');

        for (const path of roleCase.visiblePaths) {
            await expect(sidebar.locator(`a[href$="${path}"]`), `Menu ${path} harus terlihat untuk ${roleCase.role}.`).toBeVisible();
        }

        for (const path of roleCase.forbiddenPaths) {
            const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
            expect(response?.status(), `${roleCase.role} harus menerima 403 untuk ${path}.`).toBe(403);
        }
    });
}
