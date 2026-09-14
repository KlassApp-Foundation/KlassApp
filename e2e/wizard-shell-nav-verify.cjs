/**
 * Piece 3 PR1 — wizard shell/nav kit parity + create-mode walk to review.
 * Env: PREVIEW_BASE, optional DASH_EMAIL/DASH_PASSWORD (falls back to fresh register).
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PREVIEW_BASE || process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp-staging-7mpoqg.laravel.cloud';
const VIEWPORTS = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 800 },
];
const OUT = path.join(__dirname, 'screenshots', 'wizard-shell-nav');
fs.mkdirSync(OUT, { recursive: true });

const REAL_SIZES = [
  'Under 100 students',
  '100-300 students',
  '300-500 students',
  '500+ students',
];
const REAL_CATEGORIES = [
  'Nursery only',
  'Primary',
  'Primary + Nursery',
  'O-Level',
  'O-Level + A-Level',
];

async function loginOrRegister(page) {
  const email = process.env.DASH_EMAIL;
  const password = process.env.DASH_PASSWORD;
  if (email && password) {
    await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
    await page.fill('input[name=email], input[type=email]', email);
    await page.fill('input[name=password], input[type=password]', password);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
      page.click('button[type=submit], input[type=submit]'),
    ]);
    return { mode: 'login', email };
  }

  const stamp = Date.now();
  const regEmail = `wizard.shell.${stamp}@testmail.ug`;
  const regPassword = 'KlassAppTest@2026';
  await page.goto(`${BASE}/register`, { waitUntil: 'load', timeout: 90000 });
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
  await fillIf(['input[name="name"]', '#name'], `Wizard Shell ${stamp}`);
  await fillIf(['input[name="email"]', '#email'], regEmail);
  await fillIf(['input[name="password"]', '#password'], regPassword);
  await fillIf(['input[name="password_confirmation"]', '#password_confirmation'], regPassword);
  await fillIf(['input[name="phone"]', '#phone'], `070${String(stamp).slice(-7)}`);
  await page.locator('button[type=submit], input[type=submit]').first().click();
  await page.waitForTimeout(4000);
  return { mode: 'register', email: regEmail };
}

async function currentStepKey(page) {
  return page.evaluate(() => {
    const cur = document.querySelector('[data-testid=wizard-progress] [aria-current="step"]');
    return cur ? cur.getAttribute('data-step-key') : null;
  });
}

async function clickContinue(page) {
  await page.locator('[data-testid=wizard-next]').click();
  await page.waitForTimeout(900);
}

async function walkToReview(page) {
  const seen = [];
  for (let i = 0; i < 40; i++) {
    const key = await currentStepKey(page);
    seen.push(key);
    if (key === 'review') {
      return seen;
    }

    if (key === 'school_name') {
      const input = page.locator('#wizard-school-name');
      if (await input.count()) {
        const v = await input.inputValue();
        if (!v || /'s School$/i.test(v)) {
          await input.fill(`Shell Walk Academy ${Date.now()}`);
        }
      }
    } else if (key === 'student_size') {
      await page.selectOption('#wizard-student-size', REAL_SIZES[0]);
      const opts = await page.locator('#wizard-student-size option').allTextContents();
      for (const size of REAL_SIZES) {
        if (!opts.some((o) => o.trim() === size)) {
          throw new Error(`Missing real size option: ${size}; got ${JSON.stringify(opts)}`);
        }
      }
    } else if (key === 'country') {
      await page.selectOption('#wizard-country', { label: 'Uganda' }).catch(() => {});
    } else if (key === 'curriculum') {
      await page.selectOption('#wizard-curriculum', 'uneb').catch(() => {});
    } else if (key === 'school_category') {
      const labels = await page.locator('.manual-wizard-plan-card .manual-wizard-plan-name').allTextContents();
      for (const cat of REAL_CATEGORIES) {
        if (!labels.some((l) => l.trim() === cat)) {
          throw new Error(`Missing real category: ${cat}; got ${JSON.stringify(labels)}`);
        }
      }
      await page.locator('.manual-wizard-plan-card', { hasText: 'Primary' }).first().click();
    } else if (key === 'emis') {
      await page.fill('#wizard-emis', `EMIS-SHELL-${Date.now()}`);
    } else if (key === 'uneb_center') {
      // optional — continue
    } else if (key === 'academic_year') {
      // defaults usually present
    } else if (key === 'standards' || key === 'subjects' || key === 'teachers' || key === 'students') {
      // optional / checkpoint — continue
    } else if (key === 'terms') {
      const name = page.locator('#wizard-term-name, input[wire\\:model="termName"]').first();
      if (await name.count()) {
        const v = await name.inputValue();
        if (!v) await name.fill('Term 1');
      }
    } else if (key === 'fees') {
      await page.locator('#wizard-fee-name, input[wire\\:model="feeName"]').first().fill('Tuition').catch(() => {});
      await page.locator('#wizard-fee-amount, input[wire\\:model="feeAmount"]').first().fill('100000').catch(() => {});
    } else if (key === 'whatsapp_verify') {
      await page.fill('[data-testid=wizard-wa-phone]', `+25670${String(Date.now()).slice(-7)}`);
      await page.click('[data-testid=wizard-wa-send-otp]');
      await page.waitForTimeout(1200);
      const status = await page.locator('[data-testid=wizard-wa-otp-status]').textContent();
      const m = (status || '').match(/(\d{6})/);
      if (!m) throw new Error('OTP not shown in wizard status: ' + status);
      await page.fill('[data-testid=wizard-wa-otp-input]', m[1]);
      await page.click('[data-testid=wizard-wa-verify-otp]');
      await page.waitForSelector('[data-testid=wizard-wa-verified]', { timeout: 10000 });
    } else if (key === 'plan_selection') {
      const freemium = page.locator('.manual-wizard-plan-card', { hasText: 'Freemium' }).first();
      if (await freemium.count()) await freemium.click();
    }

    const err = await page.locator('[data-testid=wizard-error]').textContent().catch(() => '');
    await clickContinue(page);
    const after = await currentStepKey(page);
    if (after === key) {
      const err2 = await page.locator('[data-testid=wizard-error]').textContent().catch(() => '');
      throw new Error(`Stuck on step ${key}. error=${err2 || err || '(none)'}`);
    }
  }
  throw new Error('Did not reach review. seen=' + seen.join('>'));
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await loginOrRegister(page);
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('[data-testid=manual-wizard-shell], [data-testid=wizard-nav]', { timeout: 30000 });

  const chrome = await page.evaluate(() => ({
    brand: !!document.querySelector('[data-testid=wizard-brand]'),
    aside: (document.querySelector('.manual-wizard-brand-aside') || {}).textContent || '',
    next: (document.querySelector('[data-testid=wizard-next]') || {}).textContent || '',
    dots: document.querySelectorAll('[data-testid=wizard-progress] [data-step-key], [data-testid=wizard-progress] .manual-wizard-dot').length,
    toshiHidden: getComputedStyle(document.querySelector('[data-toshi-root]') || document.body).display === 'none'
      || document.body.classList.contains('toshi-manual-wizard'),
  }));

  if (!chrome.brand) throw new Error('missing wizard brand strip');
  if (!/Setting up without Toshi/.test(chrome.aside)) throw new Error('missing brand aside');
  if (chrome.dots < 17) throw new Error('expected 17 progress dots, got ' + chrome.dots);

  const pathKeys = await walkToReview(page);
  const reviewNext = await page.locator('[data-testid=wizard-next]').textContent();
  if (!/Confirm\s*&\s*finish/i.test(reviewNext || '')) {
    throw new Error('Expected Confirm & finish on review, got: ' + reviewNext);
  }

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(400);
    await page.screenshot({ path: path.join(OUT, `review-${vp.name}.png`), fullPage: true });
    const nav = await page.locator('[data-testid=wizard-nav]').boundingBox();
    if (!nav) throw new Error('wizard-nav missing at ' + vp.name);
    console.log(`PASS ${vp.name}: review Confirm & finish`);
  }

  console.log('Walk path:', pathKeys.filter(Boolean).join(' → '));
  console.log('Chrome:', chrome);
  console.log(`OK wizard shell/nav kit parity @ ${BASE}`);
  await browser.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
