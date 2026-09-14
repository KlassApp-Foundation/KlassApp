/**
 * Piece 3 gaps 4–7 — staging verification for student gender, teacher
 * class/subject assignment, multi-term + current, and fees fields.
 *
 * Screenshots at 375/414/768/1280.
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
const OUT = path.join(__dirname, 'screenshots', 'wizard-gaps-4-7');
fs.mkdirSync(OUT, { recursive: true });

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

async function gotoWizardStep(page, stepKey) {
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('[data-testid=wizard-nav]', { timeout: 30000 });
  await page.click(`[data-step-key=${stepKey}]`);
  await page.waitForTimeout(1500);
}

(async () => {
  console.log('launch', BASE);
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.setDefaultTimeout(45000);

  await login(page);

  // --- Students: gender + IDs ---
  await gotoWizardStep(page, 'students');
  await page.waitForSelector('[data-testid=wizard-students-bulk]');
  const studentChrome = await page.evaluate(() => ({
    gender: !!document.querySelector('[data-testid=wizard-student-gender]'),
    dob: !!document.querySelector('[data-testid=wizard-student-dob]'),
    schoolId: !!document.querySelector('#wizard-student-school-id'),
    boardReg: !!document.querySelector('#wizard-student-board-reg'),
  }));
  console.log('studentChrome', JSON.stringify(studentChrome));
  if (!studentChrome.gender) throw new Error('student gender missing');
  if (!studentChrome.dob) throw new Error('student dob missing');
  if (!studentChrome.schoolId || !studentChrome.boardReg) throw new Error('student ID fields missing');

  await page.selectOption('[data-testid=wizard-student-gender]', 'female');
  await page.fill('[data-testid=wizard-student-name]', 'E2E Gender Student');
  const classSelect = page.locator('[data-testid=wizard-student-class]');
  if (await classSelect.locator('option').count() > 1) {
    await classSelect.selectOption({ index: 1 });
  }
  await page.fill('#wizard-student-school-id', 'E2E-ADM-001');
  await page.click('[data-testid=wizard-student-add]');
  await page.waitForTimeout(1200);
  const studentList = await page.locator('[data-testid=wizard-student-list] li').count();
  if (studentList < 1) throw new Error('student draft not added');
  console.log('studentDraftOk', studentList);

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(400);
    await page.screenshot({ path: path.join(OUT, `students-${vp.name}.png`), fullPage: true });
  }

  // --- Teachers: class/subject checkboxes ---
  await gotoWizardStep(page, 'teachers');
  await page.waitForSelector('[data-testid=wizard-teachers-bulk]');
  const teacherChrome = await page.evaluate(() => ({
    classes: !!document.querySelector('[data-testid=wizard-teacher-classes]'),
    subjects: !!document.querySelector('[data-testid=wizard-teacher-subjects]'),
    classChecks: document.querySelectorAll('[data-testid=wizard-teacher-classes] input[type=checkbox]').length,
    subjectChecks: document.querySelectorAll('[data-testid=wizard-teacher-subjects] input[type=checkbox]').length,
  }));
  console.log('teacherChrome', JSON.stringify(teacherChrome));
  if (!teacherChrome.classes || !teacherChrome.subjects) throw new Error('teacher assignment UI missing');

  if (teacherChrome.classChecks > 0) {
    await page.locator('[data-testid=wizard-teacher-classes] input[type=checkbox]').first().check();
  }
  if (teacherChrome.subjectChecks > 0) {
    await page.locator('[data-testid=wizard-teacher-subjects] input[type=checkbox]').first().check();
  }
  await page.fill('[data-testid=wizard-teacher-name]', 'E2E Assigned Teacher');
  await page.fill('[data-testid=wizard-teacher-email]', `e2e.assigned.${Date.now()}@gaps.test`);
  await page.click('[data-testid=wizard-teacher-add]');
  await page.waitForTimeout(1200);
  const teacherList = await page.locator('[data-testid=wizard-teacher-list] li').count();
  if (teacherList < 1) throw new Error('teacher draft not added');
  console.log('teacherDraftOk', teacherList);

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(400);
    await page.screenshot({ path: path.join(OUT, `teachers-${vp.name}.png`), fullPage: true });
  }

  // --- Terms: multi list + mark current (no auto-advance on add) ---
  await gotoWizardStep(page, 'terms');
  await page.waitForSelector('[data-testid=wizard-terms-bulk]');
  const termsChrome = await page.evaluate(() => ({
    list: document.querySelectorAll('[data-testid=wizard-term-list] li').length,
    add: !!document.querySelector('[data-testid=wizard-term-add]'),
    currentBadge: !!document.querySelector('[data-testid=wizard-term-current-badge]'),
  }));
  console.log('termsChrome', JSON.stringify(termsChrome));
  if (termsChrome.list < 3) throw new Error('expected prefilled Term 1–3, got ' + termsChrome.list);
  if (!termsChrome.add) throw new Error('term add missing');
  if (!termsChrome.currentBadge) throw new Error('current term badge missing');

  const beforeKey = await page.getAttribute('[data-step-key].is-active, [data-step-key][aria-current]', 'data-step-key').catch(() => null);
  await page.fill('[data-testid=wizard-term-name]', 'Term Extra E2E');
  await page.fill('[data-testid=wizard-term-start]', '2026-01-01');
  await page.fill('[data-testid=wizard-term-end]', '2026-01-31');
  await page.click('[data-testid=wizard-term-add]');
  await page.waitForTimeout(1200);
  const afterAdd = await page.locator('[data-testid=wizard-term-list] li').count();
  if (afterAdd < 4) throw new Error('add term did not stay on step / list, count=' + afterAdd);
  // Still on terms step (not auto-advanced)
  const stillTerms = await page.locator('[data-testid=wizard-terms-bulk]').count();
  if (!stillTerms) throw new Error('auto-advanced away from terms after add');
  console.log('termsMultiOk', afterAdd, 'beforeKey', beforeKey);

  const markBtn = page.locator('[data-testid^=wizard-term-mark-current-]').first();
  if (await markBtn.count()) {
    await markBtn.click();
    await page.waitForTimeout(800);
  }

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(400);
    await page.screenshot({ path: path.join(OUT, `terms-${vp.name}.png`), fullPage: true });
  }

  // --- Fees: name/amount/scope/term/yearly ---
  await gotoWizardStep(page, 'fees');
  await page.waitForSelector('[data-testid=wizard-fees-bulk]');
  const feesChrome = await page.evaluate(() => ({
    name: !!document.querySelector('[data-testid=wizard-fee-name]'),
    amount: !!document.querySelector('[data-testid=wizard-fee-amount]'),
    scope: !!document.querySelector('[data-testid=wizard-fee-scope]'),
    yearly: !!document.querySelector('[data-testid=wizard-fee-yearly]'),
    term: !!document.querySelector('[data-testid=wizard-fee-term]'),
    add: !!document.querySelector('[data-testid=wizard-fee-add]'),
  }));
  console.log('feesChrome', JSON.stringify(feesChrome));
  if (!feesChrome.name || !feesChrome.amount || !feesChrome.scope || !feesChrome.yearly || !feesChrome.add) {
    throw new Error('fees fields incomplete');
  }

  await page.fill('[data-testid=wizard-fee-name]', 'E2E Development Fee');
  await page.fill('[data-testid=wizard-fee-amount]', '75000');
  await page.locator('[data-testid=wizard-fee-yearly] input[type=checkbox]').check();
  await page.click('[data-testid=wizard-fee-add]');
  await page.waitForTimeout(1200);
  const feeList = await page.locator('[data-testid=wizard-fee-list] li').count();
  if (feeList < 1) throw new Error('fee draft not added');
  console.log('feeDraftOk', feeList);

  // Class scope shows class select
  await page.locator('[data-testid=wizard-fee-scope] input[value=class]').check();
  await page.waitForTimeout(800);
  const classVisible = await page.locator('[data-testid=wizard-fee-class]').count();
  if (!classVisible) throw new Error('fee class select not shown for class scope');

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(400);
    await page.screenshot({ path: path.join(OUT, `fees-${vp.name}.png`), fullPage: true });
  }

  console.log('OK gaps-4-7 screenshots →', OUT);
  await browser.close();
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
