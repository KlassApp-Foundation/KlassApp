// Signup driver + validation battery for the public /register form.
const { expect } = require('@playwright/test');

async function open(page) {
    await page.goto('/register', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#saas-register-form')).toBeVisible({ timeout: 30_000 });
}

async function fill(page, data, overrides = {}) {
    const pw = overrides.password ?? data.password;
    const vals = {
        name: overrides.name ?? data.admin.name,
        email: overrides.email ?? data.admin.email,
        phone: overrides.phone ?? data.admin.phoneLocal,
        password: pw,
        confirm: overrides.confirm ?? pw,
        terms: overrides.terms ?? true,
    };
    await page.locator('#name').fill(vals.name);
    await page.locator('#email').fill(vals.email);
    await page.locator('#phone').fill(vals.phone);
    await page.locator('#password').fill(vals.password);
    await page.locator('#password-confirm').fill(vals.confirm);
    const terms = page.locator('#termsandcondn');
    if (vals.terms) await terms.check().catch(() => {});
    else await terms.uncheck().catch(() => {});
    return vals;
}

async function submit(page, { bypassClientValidation = false } = {}) {
    if (bypassClientValidation) {
        await page.locator('#saas-register-form').evaluate((f) => f.setAttribute('novalidate', 'novalidate'));
    }
    await page.getByRole('button', { name: 'Create account with password' }).click();
    await page.waitForLoadState('load', { timeout: 90_000 }).catch(() => {});
}

async function readErrors(page) {
    return (await page.locator('.ap-error').allInnerTexts()).map((t) => t.trim()).filter(Boolean);
}

async function submitExpectErrors(page, data, overrides, expectedFragments) {
    await open(page);
    await fill(page, data, overrides);
    await submit(page, { bypassClientValidation: true });
    const errors = await readErrors(page);
    const all = errors.join(' | ');
    const missing = expectedFragments.filter((f) => !new RegExp(f, 'i').test(all));
    return { ok: missing.length === 0, missing, errors };
}

async function runValidationBattery(page, data) {
    const results = [];
    results.push({ name: 'required fields', ...(await submitExpectErrors(page, data, {
        name: '', email: '', phone: '', password: '', confirm: '', terms: false,
    }, ['full name is required', 'Email is required', 'Phone \\(WhatsApp\\) is required', 'Password is required', 'agree to the Terms'])) });
    results.push({ name: 'invalid email', ...(await submitExpectErrors(page, data, {
        email: 'not-an-email',
    }, ['valid email'])) });
    results.push({ name: 'invalid phone', ...(await submitExpectErrors(page, data, {
        phone: '12345',
    }, ['valid Ugandan WhatsApp number'])) });
    results.push({ name: 'short password', ...(await submitExpectErrors(page, data, {
        password: 'abc', confirm: 'abc',
    }, ['at least 8 characters'])) });
    results.push({ name: 'password mismatch', ...(await submitExpectErrors(page, data, {
        password: 'Password123!', confirm: 'Password124!',
    }, ['confirmation does not match'])) });
    results.push({ name: 'terms unchecked', ...(await submitExpectErrors(page, data, {
        terms: false,
    }, ['agree to the Terms'])) });
    // Password rule hints check: the form must surface the min-8 rule.
    return results;
}

async function signupValid(page, data) {
    await open(page);
    await fill(page, data);
    await submit(page);
    await page.waitForURL(/\/admin\/dashboard/, { timeout: 120_000 });
    const errors = await readErrors(page).catch(() => []);
    return { landedOnDashboard: /\/admin\/dashboard/.test(page.url()), inlineErrors: errors };
}

async function checkDuplicate(browser, data) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    const res = await submitExpectErrors(page, data, {}, ['already registered|already been taken']);
    await ctx.close();
    return { name: 'duplicate email', ...res };
}

module.exports = { open, fill, submit, readErrors, runValidationBattery, signupValid, checkDuplicate };
