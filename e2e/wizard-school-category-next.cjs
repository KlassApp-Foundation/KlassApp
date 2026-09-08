/**
 * Headed Playwright: registration → school details → category → Next must reach EMIS (Step 5).
 * Env: PLAYWRIGHT_BASE_URL (default https://klassapp.xyz), HEADED=0 for CI-ish.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const HEADED = process.env.HEADED !== '0';
const stamp = Date.now();
const OUT = path.join(__dirname, 'screenshots', `wizard-category-next-${stamp}`);
const email = `wizard.cat.${stamp}@testmail.ug`;
const password = 'KlassAppTest@2026';
const schoolName = `Wizard Cat School ${stamp}`;

fs.mkdirSync(OUT, { recursive: true });

function stepKeyFromBody(text) {
  const t = text.toLowerCase();
  if (/emis|ministry code/.test(t) && /school category/.test(t) === false) {
    // EMIS step often still mentions category in checklist — prefer explicit field
  }
  if (/emis\s*\/\s*ministry|ministry code|emis \/ ministry/.test(t)) return 'emis';
  if (/school category/.test(t) && /choose|primary|nursery|o-level/.test(t)) return 'school_category';
  if (/board\s*\/\s*curriculum|curriculum/.test(t)) return 'curriculum';
  if (/country/.test(t)) return 'country';
  if (/school name/.test(t)) return 'school_name';
  return 'unknown';
}

(async () => {
  const report = {
    pass: false,
    base: BASE,
    email,
    schoolName,
    steps: {},
    errors: [],
  };
  const browser = await chromium.launch({
    headless: !HEADED,
    args: HEADED ? [] : undefined,
  });
  const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });
  page.on('pageerror', (e) => report.errors.push('pageerror: ' + e.message));

  try {
    // ——— Register ———
    await page.goto(`${BASE}/register`, { waitUntil: 'domcontentloaded', timeout: 90000 }).catch(async () => {
      await page.goto(`${BASE}/signup`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    });
    await page.screenshot({ path: path.join(OUT, '00-register.png'), fullPage: true });

    // Flexible registration field fill
    const fillIf = async (sels, value) => {
      for (const sel of sels) {
        const loc = page.locator(sel).first();
        if (await loc.count()) {
          await loc.fill(value);
          return true;
        }
      }
      return false;
    };

    await fillIf(['input[name="name"]', '#name', 'input[name="firstname"]'], 'Wizard Cat Admin');
    await fillIf(['input[name="email"]', '#email'], email);
    await fillIf(['input[name="password"]', '#password'], password);
    await fillIf(
      ['input[name="password_confirmation"]', '#password_confirmation', 'input[name="password_confirmation"]'],
      password
    );
    await fillIf(['input[name="school_name"]', '#school_name', 'input[name="school"]'], schoolName);
    await fillIf(['input[name="phone"]', '#phone', 'input[name="mobile_no"]'], `070${String(stamp).slice(-7)}`);

    const submit = page.locator('button[type="submit"], input[type="submit"]').first();
    await submit.click();
    await page.waitForTimeout(5000);
    await page.screenshot({ path: path.join(OUT, '01-after-register.png'), fullPage: true });
    report.steps.register = { url: page.url() };

    // Force password change gate
    if (page.url().includes('password')) {
      await fillIf(['input[name="password"]', '#password'], password + 'X');
      await fillIf(['input[name="password_confirmation"]', '#password_confirmation'], password + 'X');
      await page.locator('button[type="submit"]').first().click();
      await page.waitForTimeout(3000);
    }

    // ——— Open wizard ———
    await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForTimeout(3000);
    await page.screenshot({ path: path.join(OUT, '02-wizard.png'), fullPage: true });

    const next = async () => {
      const btn = page.locator('[data-testid="wizard-next"], button:has-text("Next")').first();
      await btn.click();
      await page.waitForTimeout(2500);
    };

    // School name (may already be set)
    let body = await page.locator('body').innerText();
    report.steps.landing = { snip: body.slice(0, 400), inferred: stepKeyFromBody(body) };

    if (/school name/i.test(body) && page.locator('#wizard-school-name').count) {
      const nameInput = page.locator('#wizard-school-name, input[wire\\:model="schoolName"]').first();
      if (await nameInput.count()) {
        await nameInput.fill(schoolName);
      }
      await next();
      body = await page.locator('body').innerText();
    }

    // Country
    body = await page.locator('body').innerText();
    if (/country/i.test(body) && (await page.locator('#wizard-country, select[wire\\:model="countryName"]').count())) {
      const sel = page.locator('#wizard-country, select[wire\\:model="countryName"]').first();
      await sel.selectOption({ label: 'Uganda' }).catch(async () => sel.selectOption('Uganda'));
      await next();
    }

    // Curriculum
    body = await page.locator('body').innerText();
    if (/curriculum|board/i.test(body)) {
      const sel = page.locator('#wizard-curriculum, select[wire\\:model="curriculum"]').first();
      if (await sel.count()) {
        await sel.selectOption('uneb');
      }
      await next();
    }

    await page.waitForTimeout(1500);
    body = await page.locator('body').innerText();
    await page.screenshot({ path: path.join(OUT, '03-category-step.png'), fullPage: true });
    report.steps.before_category = { snip: body.slice(0, 600), inferred: stepKeyFromBody(body) };

    // Must be on category
    const primaryBtn = page.locator('[data-testid="wizard-category-primary"]').first();
    if (!(await primaryBtn.count())) {
      report.errors.push('Primary category button missing — not on school_category step?');
      throw new Error(report.errors[0]);
    }

    // Prove broken path would leave empty: we only use selectSchoolCategory via the button
    await primaryBtn.click();
    await page.waitForTimeout(1500);
    await page.screenshot({ path: path.join(OUT, '04-after-category-click.png'), fullPage: true });

    const selected = await primaryBtn.getAttribute('aria-checked');
    report.steps.category_click = { aria_checked: selected, ok: selected === 'true' };

    await next();
    await page.waitForTimeout(3000);
    body = await page.locator('body').innerText();
    await page.screenshot({ path: path.join(OUT, '05-after-next.png'), fullPage: true });

    const onEmis =
      /emis|ministry code/i.test(body) &&
      (await page.locator('#wizard-emis, input[wire\\:model="ministryCode"]').count()) > 0;

    const stillOnCategory =
      (await page.locator('[data-testid="wizard-category-primary"]').count()) > 0 &&
      /school category/i.test(body) &&
      !(await page.locator('#wizard-emis').count());

    report.steps.after_next = {
      snip: body.slice(0, 700),
      on_emis: onEmis,
      still_on_category: stillOnCategory,
      url: page.url(),
    };

    report.pass = onEmis && !stillOnCategory && report.steps.category_click.ok;
    if (!report.pass) {
      report.errors.push('Did not advance from school_category to EMIS after Next');
    }
  } catch (e) {
    report.errors.push(String(e && e.stack ? e.stack : e));
  } finally {
    await browser.close();
    fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
    process.exit(report.pass ? 0 : 1);
  }
})();
