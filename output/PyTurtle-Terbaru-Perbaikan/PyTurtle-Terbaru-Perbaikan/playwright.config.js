import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: './tests/browser',
    timeout: 90000,
    workers: 1,
    retries: 0,
    reporter: 'list',
    use: {
        baseURL: 'http://127.0.0.1:8010',
        viewport: { width: 1440, height: 1000 },
        browserName: 'chromium',
        channel: 'chrome',
        headless: true,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'node scripts/test-server.mjs',
        url: 'http://127.0.0.1:8010/login',
        reuseExistingServer: false,
        stderr: 'ignore',
        timeout: 60000,
    },
});
