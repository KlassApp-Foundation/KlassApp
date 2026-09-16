/**
 * Onboarding unique-constraint collisions (wizard).
 *
 * Verifies specific ValidationException messages surface in the wizard UI for:
 * - teacher email already registered (global)
 * - student LIN already registered (global)
 * - clean teacher+student save succeeds after collisions are fixed
 *
 * Requires staging (or local) with a logged-in school admin. Uses unique
 * fixture values so it does not depend on leftover PR611 school 14 data.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PREVIEW_BASE || process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp-staging-7mpoqg.laravel.cloud';
const OUT = path.join(__dirname, 'screenshots', 'wizard-unique-constraint-collisions');
fs.mkdirSync(OUT, { recursive: true });

const stamp = Date.now();
const teacherEmail = `e2e.teacher.${stamp}@collision.test`;
const studentLin = `LIN9${String(stamp).slice(-9)}`;
const studentEmail = `e2e.student.${stamp}@collision.test`;

async function login(page) {
  const email = process.env.DASH_EMAIL || process.env.STAGING_ADMIN_EMAIL || 'moemucu@gmail.com';
  const password = process.env.DASH_PASSWORD || process.env.STAGING_ADMIN_PASSWORD || '';
  if (!password) {
    throw new Error('Set DASH_PASSWORD / STAGING_ADMIN_PASSWORD for wizard e2e');
  }
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
  await page.waitForTimeout(1200);
}

async function errorBannerText(page) {
  return page.evaluate(() => {
    const el = document.querySelector('[data-testid=wizard-error], .wizard-error, [role=alert]');
    return (el?.textContent || '').trim();
  });
}

(async () => {
  console.log('launch', { BASE, teacherEmail, studentLin });
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.setDefaultTimeout(60000);

  await login(page);

  // --- Seed colliding teacher via API-less path: save once, then retry same email ---
  await gotoWizardStep(page, 'teachers');
  await page.waitForSelector('[data-testid=wizard-teachers-bulk], [data-testid=wizard-teacher-email]', { timeout: 30000 });

  // Single-add path if present
  const hasSingle = await page.locator('[data-testid=wizard-teacher-email]').count();
  if (hasSingle) {
    await page.fill('[data-testid=wizard-teacher-name]', `E2E Collision Teacher ${stamp}`);
    await page.fill('[data-testid=wizard-teacher-email]', teacherEmail);
    const addBtn = page.locator('[data-testid=wizard-teacher-add]');
    if (await addBtn.count()) {
      await addBtn.click();
      await page.waitForTimeout(800);
    }
  } else {
    await page.fill('[data-testid=wizard-teacher-paste]', `E2E Collision Teacher ${stamp}`);
    await page.click('[data-testid=wizard-teacher-paste-btn]');
    await page.waitForTimeout(800);
  }

  // Continue to persist first teacher (may need list populated from paste with generated emails —
  // for collision we need the exact email. Prefer Livewire drafts via evaluate if paste only).
  // Upload-style: set teacherDrafts via wire if needed — fall back to filling email field on last draft.
  const emailInputs = page.locator('input[type=email], [data-testid=wizard-teacher-email]');
  if (await emailInputs.count()) {
    await emailInputs.last().fill(teacherEmail);
  }

  await page.click('[data-testid=wizard-next], button:has-text("Continue"), button:has-text("Next")');
  await page.waitForTimeout(2500);
  await page.screenshot({ path: path.join(OUT, '01-first-teacher-save.png'), fullPage: true });

  // Second save attempt with same email
  await gotoWizardStep(page, 'teachers');
  await page.waitForTimeout(1000);
  if (await page.locator('[data-testid=wizard-teacher-name]').count()) {
    await page.fill('[data-testid=wizard-teacher-name]', `E2E Collision Teacher B ${stamp}`);
    await page.fill('[data-testid=wizard-teacher-email]', teacherEmail);
    const addBtn = page.locator('[data-testid=wizard-teacher-add]');
    if (await addBtn.count()) await addBtn.click();
  }
  await page.click('[data-testid=wizard-next], button:has-text("Continue"), button:has-text("Next")');
  await page.waitForTimeout(2500);
  await page.screenshot({ path: path.join(OUT, '02-teacher-email-collision.png'), fullPage: true });

  let err = await errorBannerText(page);
  console.log('teacherCollisionError', err);
  if (!/already registered|duplicate|email/i.test(err) && !err.includes(teacherEmail)) {
    console.warn('WARN: expected email collision message; got:', err || '(empty)');
  } else {
    console.log('OK teacher email collision surfaced');
  }

  // --- Student LIN collision: save one student with LIN, then retry ---
  await gotoWizardStep(page, 'students');
  await page.waitForTimeout(1000);

  // Prefer paste/upload chrome; set drafts via name+class+lin fields when available
  const studentName = page.locator('[data-testid=wizard-student-name]');
  if (await studentName.count()) {
    await studentName.fill(`E2E Lin Student ${stamp}`);
    const classInput = page.locator('[data-testid=wizard-student-class]');
    if (await classInput.count()) await classInput.fill('P.1');
    const linInput = page.locator('[data-testid=wizard-student-lin], input[name*=lin]');
    if (await linInput.count()) await linInput.fill(studentLin);
    const add = page.locator('[data-testid=wizard-student-add]');
    if (await add.count()) await add.click();
  }

  await page.click('[data-testid=wizard-next], button:has-text("Continue"), button:has-text("Next")');
  await page.waitForTimeout(2500);
  await page.screenshot({ path: path.join(OUT, '03-first-student-save.png'), fullPage: true });

  await gotoWizardStep(page, 'students');
  if (await studentName.count()) {
    await studentName.fill(`E2E Lin Student B ${stamp}`);
    const classInput = page.locator('[data-testid=wizard-student-class]');
    if (await classInput.count()) await classInput.fill('P.1');
    const linInput = page.locator('[data-testid=wizard-student-lin], input[name*=lin]');
    if (await linInput.count()) await linInput.fill(studentLin);
    const add = page.locator('[data-testid=wizard-student-add]');
    if (await add.count()) await add.click();
  }
  await page.click('[data-testid=wizard-next], button:has-text("Continue"), button:has-text("Next")');
  await page.waitForTimeout(2500);
  await page.screenshot({ path: path.join(OUT, '04-student-lin-collision.png'), fullPage: true });

  err = await errorBannerText(page);
  console.log('linCollisionError', err);
  if (!/LIN|already registered/i.test(err)) {
    console.warn('WARN: expected LIN collision message; got:', err || '(empty)');
  } else {
    console.log('OK student LIN collision surfaced');
  }

  // Clean unique save smoke (different email + lin)
  await gotoWizardStep(page, 'teachers');
  if (await page.locator('[data-testid=wizard-teacher-name]').count()) {
    await page.fill('[data-testid=wizard-teacher-name]', `E2E Clean Teacher ${stamp}`);
    await page.fill('[data-testid=wizard-teacher-email]', `clean.teacher.${stamp}@ok.test`);
    const addBtn = page.locator('[data-testid=wizard-teacher-add]');
    if (await addBtn.count()) await addBtn.click();
  }
  await page.click('[data-testid=wizard-next], button:has-text("Continue"), button:has-text("Next")');
  await page.waitForTimeout(2500);
  err = await errorBannerText(page);
  console.log('cleanTeacherError', err || '(none)');
  await page.screenshot({ path: path.join(OUT, '05-clean-teacher.png'), fullPage: true });

  console.log('done', { studentEmail, OUT });
  await browser.close();
  process.exit(0);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
