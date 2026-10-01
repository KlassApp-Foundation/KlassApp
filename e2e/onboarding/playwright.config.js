// E2E onboarding suite — STAGING ONLY (hard-guarded in lib/global-setup.js).
// Base URL defaults to the staging custom domain; override with E2E_BASE_URL.
const { defineConfig } = require('@playwright/test');

const BASE = process.env.E2E_BASE_URL || 'https://test.klassapp.xyz';
const SLOWMO = Number(process.env.E2E_SLOWMO || 0);

module.exports = defineConfig({
    testDir: __dirname + '/journeys',
    outputDir: __dirname + '/artifacts/pw',
    timeout: 25 * 60_000,
    expect: { timeout: 20_000 },
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { outputFolder: __dirname + '/report', open: 'never' }]],
    globalSetup: require.resolve('./lib/global-setup.js'),
    use: {
        baseURL: BASE,
        channel: 'chrome',
        headless: !process.env.E2E_HEADED,
        launchOptions: { slowMo: SLOWMO },
        trace: 'on',
        video: 'on',
        screenshot: 'on',
        viewport: { width: 1280, height: 900 },
    },
    projects: [
        { name: 'desktop-1280', use: { viewport: { width: 1280, height: 900 } } },
        { name: 'mobile-375', use: { viewport: { width: 375, height: 812 } } },
    ],
});
