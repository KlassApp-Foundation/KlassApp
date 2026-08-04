const { defineConfig, devices } = require('@playwright/test');

const baseURL =
    process.env.PLAYWRIGHT_BASE_URL
    || process.env.APP_URL
    || 'http://127.0.0.1:8000';

module.exports = defineConfig({
    testDir: './e2e',
    globalSetup: require.resolve('./e2e/global-setup.js'),
    timeout: 60_000,
    expect: {
        timeout: 15_000,
    },
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        viewport: { width: 1440, height: 960 },
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: process.env.PLAYWRIGHT_WEB_SERVER_COMMAND
        ? {
            command: process.env.PLAYWRIGHT_WEB_SERVER_COMMAND,
            url: baseURL,
            reuseExistingServer: true,
            timeout: 120_000,
        }
        : undefined,
});
