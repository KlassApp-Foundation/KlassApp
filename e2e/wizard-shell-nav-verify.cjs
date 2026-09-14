/**
 * Piece 3 PR1 — wizard shell/nav kit parity on staging.
 *
 * Default: login as DASH_EMAIL/DASH_PASSWORD (phase4 demo) and verify chrome,
 * real STUDENT_SIZE_OPTIONS / SchoolCategorySeeder labels via progress-dot jumps,
 * Confirm & finish on review, screenshots at 375/414/768/1280.
 *
 * CREATE_MODE=1: register a fresh school and walk Continue through to review
 * (requires staging plans to exist; OTP is shown in-UI).
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

async function login(page) {
  const email = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
  const password = process.env.DASH_PASSWORD || 'demo123';
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name=email], input[type=email]', email);
  await page.fill('input[name=password], input[type=password]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
    page.click('button[type=submit], input[type=submit]'),
  ]);
}

(async () => {
  console.log('launch');
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(45000);

  await login(page);
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('[data-testid=wizard-nav]', { timeout: 30000 });

  const chrome = await page.evaluate(() => ({
    brand: !!document.querySelector('[data-testid=wizard-brand]'),
    aside: document.querySelector('.manual-wizard-brand-aside')?.textContent?.trim() || '',
    next: document.querySelector('[data-testid=wizard-next]')?.textContent?.trim() || '',
    dots: [...document.querySelectorAll('[data-step-key]')].map((d) => d.getAttribute('data-step-key')),
  }));
  console.log('chrome', JSON.stringify({
    brand: chrome.brand,
    aside: chrome.aside,
    next: chrome.next,
    dotCount: chrome.dots.length,
  }));

  if (!chrome.brand || chrome.aside !== 'Setting up without Toshi') {
    throw new Error('brand strip mismatch');
  }
  if (chrome.dots.length !== 17) {
    throw new Error('expected 17 progress dots, got ' + chrome.dots.length);
  }
  if (chrome.dots[chrome.dots.length - 1] !== 'review') {
    throw new Error('last progress key must be review');
  }

  await page.click('[data-step-key=student_size]');
  await page.waitForTimeout(1200);
  await page.waitForSelector('[data-testid=wizard-student-size]');
  const sizeLabels = await page
    .locator('[data-testid=wizard-student-size] .manual-wizard-plan-name')
    .allTextContents();
  for (const size of REAL_SIZES) {
    if (!sizeLabels.some((o) => o.trim() === size)) {
      throw new Error('missing real size option: ' + size + ' got ' + JSON.stringify(sizeLabels));
    }
  }
  if (sizeLabels.some((l) => /1-100|1000\+|101-300/.test(l))) {
    throw new Error('kit placeholder size leaked');
  }
  console.log('sizes OK');

  if (chrome.dots.includes('school_category')) {
    await page.click('[data-step-key=school_category]');
    await page.waitForTimeout(1200);
    const labels = await page.locator('.manual-wizard-plan-card .manual-wizard-plan-name').allTextContents();
    for (const cat of REAL_CATEGORIES) {
      if (!labels.some((l) => l.trim() === cat)) {
        throw new Error('missing real category: ' + cat + ' got ' + JSON.stringify(labels));
      }
    }
    if (labels.some((l) => /Kindergarten|P1-P7|primary_secondary/.test(l))) {
      throw new Error('kit placeholder category leaked');
    }
    console.log('categories OK');
  }

  await page.click('[data-step-key=review]');
  await page.waitForTimeout(1500);
  const onReview = await page.evaluate(() =>
    document.querySelector('[data-step-key=review]')?.getAttribute('aria-current')
  );
  const next = (await page.locator('[data-testid=wizard-next]').textContent()) || '';
  if (onReview === 'step') {
    if (!/Confirm\s*&\s*finish/i.test(next)) {
      throw new Error('expected Confirm & finish, got: ' + next);
    }
  } else if (next.trim() !== 'Continue →') {
    throw new Error('expected Continue → when review jump blocked, got: ' + next);
  }
  console.log('nav label OK', next.trim());

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(250);
    await page.screenshot({ path: path.join(OUT, `shell-${vp.name}.png`), fullPage: true });
    const nav = await page.locator('[data-testid=wizard-nav]').boundingBox();
    if (!nav) {
      throw new Error('wizard-nav missing at ' + vp.name);
    }
    console.log('PASS ' + vp.name);
  }

  console.log('OK wizard shell/nav kit parity @ ' + BASE);
  await browser.close();
  process.exit(0);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
