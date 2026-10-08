import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser',
    fullyParallel: false,
    workers: 1,
    timeout: 120000,
    reporter: [['list']],
    use: {
        baseURL: process.env.EP_TEST_URL || 'http://127.0.0.1:8010',
        channel: 'chrome',
        viewport: { width: 1440, height: 1000 },
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
});
