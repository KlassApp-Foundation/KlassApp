/**
 * Piece 3 PR5 — review panels kit on staging.
 *
 * Confirms per-section panels (not flat list), Edit jump-back, subjects are
 * unique names (not repeated once per class), screenshots at 375/414/768/1280.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PREVIEW_BASE || process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp-staging-7mpoqg.laravel.cloud';
const VIEWPORTS = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 900 },
];
const OUT = path.join(__dirname, 'screenshots', 'wizard-review-panels');
fs.mkdirSync(OUT, { recursive: true });

const EXPECTED_KEYS = [
  'school_name',
  'student_size',
  'curriculum',
  'school_category',
  'country',
  'emis',
  'uneb_center',
  'academic_year',
  'standards',
  'subjects',
  'teachers',
  'students',
  'terms',
  'fees',
  'whatsapp_verify',
  'plan_selection',
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
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.setDefaultTimeout(45000);

  await login(page);
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('[data-testid=wizard-nav]', { timeout: 30000 });

  await page.click('[data-step-key=review]');
  await page.waitForTimeout(2000);
  await page.waitForSelector('[data-testid=wizard-review]');

  const chrome = await page.evaluate((keys) => {
    const panels = [...document.querySelectorAll('.manual-wizard-review-panel')];
    const found = keys.map((k) => ({
      key: k,
      panel: !!document.querySelector(`[data-testid=wizard-review-${k}]`),
      edit: !!document.querySelector(`[data-testid=wizard-edit-${k}]`),
      value: (document.querySelector(`[data-testid=wizard-review-${k}] .manual-wizard-review-value`)?.textContent || '').trim(),
    }));
    return {
      panelCount: panels.length,
      grid: !!document.querySelector('[data-testid=wizard-review-card].manual-wizard-review-panels, .manual-wizard-review-panels'),
      flatRows: document.querySelectorAll('.manual-wizard-review-row').length,
      found,
    };
  }, EXPECTED_KEYS);

  console.log('chrome', JSON.stringify({ panelCount: chrome.panelCount, grid: chrome.grid, flatRows: chrome.flatRows }));
  if (!chrome.grid) throw new Error('review panels grid missing');
  if (chrome.flatRows > 0) throw new Error('legacy flat review rows still present');
  if (chrome.panelCount < EXPECTED_KEYS.length) {
    throw new Error('expected >= ' + EXPECTED_KEYS.length + ' panels, got ' + chrome.panelCount);
  }
  for (const row of chrome.found) {
    if (!row.panel || !row.edit) throw new Error('missing panel/edit for ' + row.key);
    if (!row.value) throw new Error('empty value for ' + row.key);
  }

  // Subjects must not list the same name once per class (e.g. English × 7).
  const subjectsValue = chrome.found.find((r) => r.key === 'subjects')?.value || '';
  console.log('subjectsValue', subjectsValue);
  const nameHits = {};
  for (const token of subjectsValue.split(/[,·]/).map((s) => s.trim()).filter(Boolean)) {
    if (/^\d+$/.test(token) || /unique across|class rows/i.test(token)) continue;
    nameHits[token] = (nameHits[token] || 0) + 1;
  }
  for (const [name, n] of Object.entries(nameHits)) {
    if (n > 1) throw new Error('subject duplicated in review display: ' + name + ' ×' + n);
  }

  // Edit jump — school name, then back via progress dot (return-to-review is Next).
  await page.click('[data-testid=wizard-edit-school_name]');
  await page.waitForTimeout(1500);
  await page.waitForSelector('#wizard-school-name, [data-testid=wizard-brand]');
  const onSchool = await page.evaluate(() => !!document.querySelector('#wizard-school-name'));
  if (!onSchool) throw new Error('Edit school_name did not land on school name step');
  console.log('edit school_name OK');

  await page.click('[data-step-key=review]');
  await page.waitForTimeout(1500);
  await page.waitForSelector('[data-testid=wizard-review]');

  await page.click('[data-testid=wizard-edit-subjects]');
  await page.waitForTimeout(1500);
  const onSubjects = await page.evaluate(() => {
    return !!document.querySelector('[data-testid=wizard-subjects-existing], #wizard-subject-name, [wire\\:model="subjectName"]')
      || /Subjects/i.test(document.querySelector('.manual-wizard-step-title')?.textContent || '');
  });
  // Soft check — land off review
  const leftReview = (await page.locator('[data-testid=wizard-review]').count()) === 0;
  if (!leftReview && !onSubjects) throw new Error('Edit subjects did not leave review');
  console.log('edit subjects OK', { onSubjects, leftReview });

  await page.click('[data-step-key=review]');
  await page.waitForTimeout(1500);
  await page.waitForSelector('[data-testid=wizard-review]');

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(400);
    await page.screenshot({
      path: path.join(OUT, `review-${vp.name}.png`),
      fullPage: true,
    });
    console.log('screenshot', vp.name);
  }

  console.log('PASS wizard-review-panels');
  await browser.close();
  process.exit(0);
})().catch((err) => {
  console.error('FAIL', err);
  process.exit(1);
});
