import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8765';
const target = new URL(baseURL);
const isLoopback = ['127.0.0.1', 'localhost', '::1'].includes(target.hostname);

if (!isLoopback && process.env.E2E_ALLOW_REMOTE !== 'true') {
    throw new Error('E2E diblokir: target nonlokal memerlukan E2E_ALLOW_REMOTE=true.');
}

if (!isLoopback && !process.env.E2E_PASSWORD) {
    throw new Error('E2E diblokir: target nonlokal memerlukan E2E_PASSWORD eksplisit.');
}

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [['html', { open: 'never' }], ['list']],
    timeout: 120_000,
    expect: { timeout: 30_000 },

    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        ignoreHTTPSErrors: false,
    },

    webServer: baseURL === 'http://127.0.0.1:8765'
        ? {
            command: 'php artisan serve --host=127.0.0.1 --port=8765',
            url: baseURL,
            reuseExistingServer: false,
            timeout: 120_000,
        }
        : undefined,

    projects: [
        { name: 'setup', testMatch: /global\.setup\.ts/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
    ],
});
