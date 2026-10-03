// Signup driver + validation battery for the public /register form.
const { expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { runStagingJson } = require('./stg-bridge');

const PY = process.env.E2E_PYTHON || 'python3';
const STG_LOGS = path.join(__dirname, 'stg_logs.py');

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
    return results;
}

/**
 * Prefer the real code from Cloud runtime logs (MAIL_MAILER=log sandbox).
 * Never pre-verifies the account (no email_verified=1 writes).
 * Fallback: re-issue a fresh code via Commands (still a real issued code).
 */
function readCodeFromStagingLogs(email) {
    try {
        const out = execFileSync(PY, [STG_LOGS, email], {
            encoding: 'utf8',
            timeout: 90_000,
            maxBuffer: 8 * 1024 * 1024,
            env: process.env,
        });
        return JSON.parse(out.trim().split('\n').pop());
    } catch (e) {
        return {
            ok: false,
            error: String(e.message || e).slice(0, 300),
            source: 'logs',
        };
    }
}

function reissueCodeViaBridge(email) {
    return runStagingJson(`
        $email = ${JSON.stringify(email)};
        $u = \\App\\Models\\User::where('email', $email)->first();
        if (! $u) { echo "<<<E2E-JSON>>>" . json_encode(['ok' => false, 'error' => 'user-not-found']); return; }
        if ((int) $u->email_verified === 1) {
            echo "<<<E2E-JSON>>>" . json_encode(['ok' => false, 'error' => 'already-verified']); return;
        }
        $code = app(\\App\\Services\\EmailVerificationCodeService::class)->issue($u);
        echo "<<<E2E-JSON>>>" . json_encode(['ok' => true, 'code' => $code, 'source' => 'reissue']);
    `);
}

async function completeEmailVerification(page, data) {
    const email = data.admin.email;
    let resolved = readCodeFromStagingLogs(email);
    if (! resolved.ok || ! resolved.code) {
        console.log(`[signup] log sandbox miss (${resolved.error || 'unknown'}); falling back to Commands re-issue`);
        resolved = reissueCodeViaBridge(email);
    } else {
        console.log(`[signup] verification code read from staging log sandbox (source=${resolved.source})`);
    }
    if (! resolved.ok || ! resolved.code) {
        throw new Error('e2e signup: could not obtain verification code (no pre-verify): ' + JSON.stringify(resolved));
    }

    const codeInput = page.locator('input[name="code"], #code, input[placeholder="000000"]').first();
    await expect(codeInput).toBeVisible({ timeout: 30_000 });
    await codeInput.fill(String(resolved.code));
    await page.getByRole('button', { name: /confirm email/i }).click();
    await page.waitForLoadState('load', { timeout: 90_000 }).catch(() => {});
}

async function signupValid(page, data) {
    await open(page);
    await fill(page, data);
    await submit(page);

    const url = page.url();
    const body = await page.locator('body').innerText().catch(() => '');
    if (/register\/verify/i.test(url) || /check your email|6-digit code/i.test(body)) {
        await completeEmailVerification(page, data);
    }

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
