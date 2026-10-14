// Shared helpers for the regression specs. Credentials come only from env.
const { test } = require('@playwright/test');

const EMAIL = process.env.E2E_ADMIN_EMAIL || '';
const PASSWORD = process.env.E2E_ADMIN_PASSWORD || '';

function requireCredentials() {
    test.skip(!EMAIL || !PASSWORD, 'Set E2E_ADMIN_EMAIL and E2E_ADMIN_PASSWORD (school admin of a demo school).');
}

async function login(page) {
    await page.goto('/login');
    await page.fill('input[name="email"], input[type="email"]', EMAIL);
    await page.fill('input[name="password"], input[type="password"]', PASSWORD);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.startsWith('/login'), { timeout: 60_000 }),
        page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
    ]);
}

module.exports = { requireCredentials, login };
