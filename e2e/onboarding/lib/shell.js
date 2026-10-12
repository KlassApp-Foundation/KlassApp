// Shell helpers for the current staging chrome: sidebar groups, folded rail,
// flyouts, account menu, school line, and the v2 dashboard. Selectors prefer
// data-testid hooks the app already ships. This file does not change the app.
const { expect } = require('@playwright/test');

async function settleShell(page) {
    const menu = page.locator('[data-account-menu]:visible');
    if (await menu.count().catch(() => 0)) {
        await page.keyboard.press('Escape').catch(() => {});
    }
    const scrim = page.locator('[data-testid="sidebar-scrim"]');
    if (await scrim.isVisible().catch(() => false)) {
        await scrim.click({ timeout: 3000 }).catch(() => {});
    }
}

async function signIn(page, email, password) {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    const form = page.locator('[data-testid="preview-login-form"]');
    await expect(form).toBeVisible({ timeout: 30_000 });
    await form.locator('#email').fill(email);
    await form.locator('#password').fill(password);
    await Promise.all([
        page.waitForLoadState('domcontentloaded').catch(() => {}),
        form.locator('[data-testid="ap-primary-submit"]').click(),
    ]);
}

/** School line in the header. Present on the admin shell after login. */
function schoolLine(page) {
    return page.locator('a.nav-school-name, a.nav-school-line').first();
}

module.exports = { settleShell, signIn, schoolLine };
