/**
 * Piece 3 PR4 — bulk teachers/students kit parity on staging.
 *
 * Covers paste mode, upload mode, template download (static teacher + dynamic
 * student), Skip for now with confirm-when-drafts-exist, screenshots at
 * 375/414/768/1280.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const os = require('os');

const BASE = process.env.PREVIEW_BASE || process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp-staging-7mpoqg.laravel.cloud';
const VIEWPORTS = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 900 },
];
const OUT = path.join(__dirname, 'screenshots', 'wizard-bulk-teachers-students');
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
  console.log('launch');
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.setDefaultTimeout(45000);

  await login(page);
  await gotoWizardStep(page, 'teachers');
  await page.waitForSelector('[data-testid=wizard-teachers-bulk]');

  // --- Teachers: chrome + paste ---
  const teacherChrome = await page.evaluate(() => ({
    template: document.querySelector('[data-testid=wizard-teacher-template]')?.textContent?.trim() || '',
    templateHref: document.querySelector('[data-testid=wizard-teacher-template]')?.getAttribute('href') || '',
    upload: !!document.querySelector('[data-testid=wizard-teacher-upload]'),
    paste: !!document.querySelector('[data-testid=wizard-teacher-paste]'),
    pasteBtn: document.querySelector('[data-testid=wizard-teacher-paste-btn]')?.textContent?.trim() || '',
    email: !!document.querySelector('[data-testid=wizard-teacher-email]'),
    phone: !!document.querySelector('[data-testid=wizard-teacher-phone]'),
    skip: document.querySelector('[data-testid=wizard-teachers-skip]')?.textContent?.trim() || '',
    skipConfirm: document.querySelector('[data-testid=wizard-teachers-skip]')?.getAttribute('wire:confirm') || '',
  }));
  console.log('teacherChrome', JSON.stringify(teacherChrome));
  if (teacherChrome.template !== 'Download template') throw new Error('teacher template link missing');
  if (!/teacher-upload-template\.xlsx/.test(teacherChrome.templateHref)) {
    throw new Error('teacher template href unexpected: ' + teacherChrome.templateHref);
  }
  if (!teacherChrome.upload || !teacherChrome.paste || teacherChrome.pasteBtn !== 'Add from paste') {
    throw new Error('teacher paste/upload chrome incomplete');
  }
  if (!teacherChrome.email || !teacherChrome.phone) throw new Error('teacher email/phone missing');
  if (teacherChrome.skip !== 'Skip for now') throw new Error('teacher skip missing');
  if (teacherChrome.skipConfirm) throw new Error('empty drafts must not have wire:confirm');

  await page.fill('[data-testid=wizard-teacher-paste]', 'E2E Paste Teacher\nE2E Second Teacher');
  await page.click('[data-testid=wizard-teacher-paste-btn]');
  await page.waitForTimeout(1500);
  const teacherList = await page.locator('[data-testid=wizard-teacher-list] li').count();
  if (teacherList < 2) throw new Error('paste did not populate teacher list, count=' + teacherList);
  console.log('teacher paste OK', teacherList);

  const skipConfirmAfter = await page.getAttribute('[data-testid=wizard-teachers-skip]', 'wire:confirm');
  if (!skipConfirmAfter || !/teachers in the list/.test(skipConfirmAfter)) {
    throw new Error('wire:confirm missing after drafts: ' + skipConfirmAfter);
  }

  // Upload a CSV teacher row — Livewire file uploads are async; wait for list growth.
  const teacherCsv = path.join(os.tmpdir(), 'wizard-teachers-e2e.csv');
  fs.writeFileSync(
    teacherCsv,
    'Name,Email,Subjects,Classes,Phone\nE2E Upload Teacher,e2e.upload@klassapp.xyz,Math,P1,+256700999888\n'
  );
  const teacherCountBeforeUpload = await page.locator('[data-testid=wizard-teacher-list] li').count();
  await page.setInputFiles('[data-testid=wizard-teacher-upload]', teacherCsv);
  await page.waitForFunction(
    (before) => {
      const n = document.querySelectorAll('[data-testid=wizard-teacher-list] li').length;
      const err = document.querySelector('.manual-wizard-error, [data-testid=wizard-error]')?.textContent || '';
      return n > before || /No names|Upload failed|Supported files/i.test(err);
    },
    teacherCountBeforeUpload,
    { timeout: 20000 }
  ).catch(() => {});
  await page.waitForTimeout(1000);
  const teacherListAfterUpload = await page.locator('[data-testid=wizard-teacher-list] li').count();
  const teacherErr = ((await page.locator('.manual-wizard-error, [role=alert]').first().textContent().catch(() => '')) || '').trim();
  if (teacherListAfterUpload <= teacherCountBeforeUpload) {
    throw new Error(
      'upload did not add teacher, before=' +
        teacherCountBeforeUpload +
        ' after=' +
        teacherListAfterUpload +
        ' err=' +
        JSON.stringify(teacherErr)
    );
  }
  console.log('teacher upload OK', teacherListAfterUpload);

  // Teacher template download
  const [teacherDownload] = await Promise.all([
    page.waitForEvent('download', { timeout: 20000 }),
    page.click('[data-testid=wizard-teacher-template]'),
  ]);
  const teacherSuggested = teacherDownload.suggestedFilename();
  console.log('teacher download', teacherSuggested);
  if (!/teacher-upload-template/i.test(teacherSuggested) && !/\.xlsx$/i.test(teacherSuggested)) {
    throw new Error('unexpected teacher download name: ' + teacherSuggested);
  }

  // Skip with confirm — accept dialog, drafts discarded. skipOptionalStep jumps to
  // the next incomplete *blocking* step (past optional students), so click the
  // students progress dot to exercise the students surface.
  page.once('dialog', async (dialog) => {
    console.log('dialog', dialog.message());
    await dialog.accept();
  });
  await page.click('[data-testid=wizard-teachers-skip]');
  await page.waitForTimeout(2000);
  await page.click('[data-step-key=students]');
  await page.waitForTimeout(1500);
  await page.waitForSelector('[data-testid=wizard-students-bulk]', { timeout: 15000 });
  console.log('teacher skip+confirm OK; on students via progress dot');

  // --- Students: dynamic template + paste + upload + skip confirm ---
  const studentChrome = await page.evaluate(() => ({
    templateHref: document.querySelector('[data-testid=wizard-student-template]')?.getAttribute('href') || '',
    templateText: document.querySelector('[data-testid=wizard-student-template]')?.textContent?.trim() || '',
    upload: !!document.querySelector('[data-testid=wizard-student-upload]'),
    paste: !!document.querySelector('[data-testid=wizard-student-paste]'),
    skip: document.querySelector('[data-testid=wizard-students-skip]')?.textContent?.trim() || '',
  }));
  console.log('studentChrome', JSON.stringify(studentChrome));
  if (studentChrome.templateText !== 'Download template') throw new Error('student template link missing');
  if (!/students\/upload-template|upload-template/i.test(studentChrome.templateHref)) {
    throw new Error('student template must be dynamic route, got: ' + studentChrome.templateHref);
  }
  if (!studentChrome.upload || !studentChrome.paste) throw new Error('student paste/upload missing');
  if (studentChrome.skip !== 'Skip for now') throw new Error('student skip missing');

  const [studentDownload] = await Promise.all([
    page.waitForEvent('download', { timeout: 30000 }),
    page.click('[data-testid=wizard-student-template]'),
  ]);
  const studentPath = await studentDownload.path();
  const studentName = studentDownload.suggestedFilename();
  console.log('student download', studentName, 'bytes', studentPath ? fs.statSync(studentPath).size : 0);
  if (!studentPath || fs.statSync(studentPath).size < 100) {
    throw new Error('student dynamic template download empty/missing');
  }
  if (!/\.(xlsx|csv)$/i.test(studentName)) {
    throw new Error('unexpected student template filename: ' + studentName);
  }

  await page.fill('[data-testid=wizard-student-paste]', 'E2E Paste Student');
  await page.click('[data-testid=wizard-student-paste-btn]');
  await page.waitForTimeout(1500);
  const studentList = await page.locator('[data-testid=wizard-student-list] li').count();
  if (studentList < 1) throw new Error('student paste failed');
  console.log('student paste OK', studentList);

  const studentCsv = path.join(os.tmpdir(), 'wizard-students-e2e.csv');
  fs.writeFileSync(
    studentCsv,
    'Name,Class,Stream,Parent Name,Parent Phone\nE2E Upload Student,P.1,,Parent E2E,+256700111000\n'
  );
  const studentCountBeforeUpload = await page.locator('[data-testid=wizard-student-list] li').count();
  await page.setInputFiles('[data-testid=wizard-student-upload]', studentCsv);
  await page.waitForFunction(
    (before) => document.querySelectorAll('[data-testid=wizard-student-list] li').length > before,
    studentCountBeforeUpload,
    { timeout: 20000 }
  ).catch(() => {});
  await page.waitForTimeout(1000);
  const studentListAfterUpload = await page.locator('[data-testid=wizard-student-list] li').count();
  if (studentListAfterUpload <= studentCountBeforeUpload) {
    throw new Error('student upload failed, before=' + studentCountBeforeUpload + ' after=' + studentListAfterUpload);
  }
  console.log('student upload OK', studentListAfterUpload);

  const studentSkipConfirm = await page.getAttribute('[data-testid=wizard-students-skip]', 'wire:confirm');
  if (!studentSkipConfirm || !/students in the list/.test(studentSkipConfirm)) {
    throw new Error('student wire:confirm missing: ' + studentSkipConfirm);
  }
  page.once('dialog', async (dialog) => {
    await dialog.accept();
  });
  await page.click('[data-testid=wizard-students-skip]');
  await page.waitForTimeout(2000);
  // After skip, should leave students step (terms or next).
  const leftStudents = (await page.locator('[data-testid=wizard-students-bulk]').count()) === 0;
  if (!leftStudents) throw new Error('still on students after skip+confirm');
  console.log('student skip+confirm OK');

  // Revisit for viewport screenshots (teachers + students chrome)
  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await gotoWizardStep(page, 'teachers');
    await page.waitForSelector('[data-testid=wizard-teachers-bulk]');
    await page.screenshot({
      path: path.join(OUT, `teachers-${vp.name}.png`),
      fullPage: true,
    });
    await page.click('[data-step-key=students]');
    await page.waitForTimeout(1200);
    await page.waitForSelector('[data-testid=wizard-students-bulk]');
    await page.screenshot({
      path: path.join(OUT, `students-${vp.name}.png`),
      fullPage: true,
    });
    console.log('screenshot', vp.name);
  }

  console.log('PASS wizard-bulk-teachers-students');
  await browser.close();
  process.exit(0);
})().catch(async (err) => {
  console.error('FAIL', err);
  process.exit(1);
});
