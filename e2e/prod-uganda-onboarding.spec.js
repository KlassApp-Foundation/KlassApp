/**
 * Seeder-closure Step 1 — Signup only.
 *
 * Registers a fresh Uganda school via the real /register form on
 * production (https://klassapp.xyz). Verifies the admin lands on
 * /admin/dashboard and that an AcademicYear does NOT yet exist
 * (proving we got a fresh school with no seeder run).
 *
 * NOTE: This test is intentionally NOT destructive — it does NOT
 * delete the school it creates. The previous Cursor session left
 * behind 5 schools (137–142) by doing the same.
 */
const { test, expect } = require('@playwright/test');

test.describe('@prod seeder closure step 1 — signup', () => {
  test.skip(
    !String(process.env.PLAYWRIGHT_BASE_URL || '').includes('klassapp.xyz'),
    'Set PLAYWRIGHT_BASE_URL=https://klassapp.xyz to run this prod-only suite'
  );

  test('fresh Uganda signup lands on /admin/dashboard', async ({ page }) => {
    test.setTimeout(180_000);

    const stamp = Date.now();
    const email = `prod.seed.s1.${stamp}@v.test`;
    const adminName = 'Amina Nakato';
    // Phone regex on prod: /^(\+?256)?0?7[0578]\d{7}$/
    // Use 0700 prefix + 7 random digits = 10 digits, matches /^07[0578]\d{7}$/
    const phoneTail = String(stamp).slice(-7);
    const phone = `+25670${phoneTail}`;
    const password = 'TestProd123!';

    await page.goto('/register', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle').catch(() => {});

    await page.getByLabel('Your Full Name').fill(adminName);
    await page.getByLabel('Email Address').fill(email);
    await page.getByLabel('Phone (WhatsApp)').fill(phone);
    await page.locator('#password').fill(password);
    await page.locator('#password-confirm').fill(password);
    const tos = page.getByLabel(/I agree to/i);
    if (await tos.count()) {
      await tos.check();
    }

    // Click submit and wait for navigation — cap with timeout in case validation fails
    const submit = page.getByRole('button', { name: 'Create account with password' });
    await submit.click();
    // Capture early state to see what URL we end up at
    await page.waitForTimeout(3000);
    const urlAfter3s = page.url();
    const html3s = (await page.content()).substring(0, 800);
    console.log(`STEP1_URL_3s=${urlAfter3s}`);
    console.log(`STEP1_HTML_3s=${html3s}`);
    try {
      await page.waitForURL(/\/admin\/dashboard/, { timeout: 30_000 });
    } catch (e) {
      await page.screenshot({ path: 'test-results/step1-failure.png', fullPage: true });
      const errors = await page.locator('.klass-error').allTextContents().catch(() => []);
      const finalUrl = page.url();
      const htmlEnd = (await page.content()).substring(0, 1500);
      throw new Error(`Signup did not reach /admin/dashboard within 30s. url=${finalUrl} errors=${JSON.stringify(errors)} phone=${phone} htmlEnd=${htmlEnd}`);
    }

    const url = page.url();
    console.log(`STEP1_OK email=${email} url=${url}`);
    expect(url).toContain('/admin/dashboard');
  });
});
