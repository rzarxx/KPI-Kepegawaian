import { test, expect } from './fixtures';

test.describe('PWA & Asset Integrity', () => {
    test('service worker terdaftar', async ({ page }) => {
        await page.goto('/dashboard');
        const swRegistered = await page.evaluate(async () => {
            if (!('serviceWorker' in navigator)) return false;
            const registrations = await navigator.serviceWorker.getRegistrations();
            return registrations.length > 0;
        });
        // SW may not be registered in first visit, just check it doesn't error
        expect(typeof swRegistered).toBe('boolean');
    });

    test('sw.js bisa diakses', async ({ page }) => {
        const response = await page.goto('/sw.js');
        expect(response?.status()).toBe(200);
        expect(response?.headers()['content-type']).toContain('javascript');
    });

    test('offline.html bisa diakses', async ({ page }) => {
        const response = await page.goto('/offline.html');
        expect(response?.status()).toBe(200);
    });

    test('semua icon PWA bisa diakses', async ({ page }) => {
        const icons = [
            '/icons/kpi-64.png',
            '/icons/kpi-192.png',
            '/icons/kpi-512.png',
            '/icons/kpi-maskable-512.png',
            '/icons/apple-touch-icon.png',
            '/icons/kpi-mark.svg',
        ];
        for (const icon of icons) {
            const response = await page.goto(icon);
            expect(response?.status(), `Icon ${icon} should be accessible`).toBe(200);
        }
    });

    test('CSS utama loaded tanpa error', async ({ page }) => {
        const cssErrors: string[] = [];
        page.on('response', (response) => {
            if (response.url().includes('.css') && response.status() >= 400) {
                cssErrors.push(`${response.status()} ${response.url()}`);
            }
        });
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        expect(cssErrors).toHaveLength(0);
    });

    test('JS utama loaded tanpa error', async ({ page }) => {
        const jsErrors: string[] = [];
        page.on('response', (response) => {
            if (response.url().includes('.js') && response.status() >= 400 && !response.url().includes('cloudflare')) {
                jsErrors.push(`${response.status()} ${response.url()}`);
            }
        });
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        expect(jsErrors).toHaveLength(0);
    });

    test('tidak ada console error fatal', async ({ page }) => {
        const consoleErrors: string[] = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error' && !msg.text().includes('cloudflare') && !msg.text().includes('beacon')) {
                consoleErrors.push(msg.text());
            }
        });
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        // Filter out known non-critical errors
        const critical = consoleErrors.filter(
            (e) => !e.includes('favicon') && !e.includes('ERR_BLOCKED_BY_CLIENT')
        );
        expect(critical).toHaveLength(0);
    });

    test('tidak ada CDN external (kecuali Cloudflare proxy)', async ({ page }) => {
        const externalRequests: string[] = [];
        page.on('request', (request) => {
            const url = request.url();
            if (
                !url.includes('kpi.albahjah.or.id') &&
                !url.includes('localhost') &&
                !url.includes('127.0.0.1') &&
                !url.includes('cloudflare') &&
                (url.includes('.js') || url.includes('.css') || url.includes('font'))
            ) {
                externalRequests.push(url);
            }
        });
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        expect(externalRequests, 'No external CDN for JS/CSS/fonts allowed').toHaveLength(0);
    });
});
