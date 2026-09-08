/**
 * Kampala Primary Academy (school 33) — exam marksheet / marks E2E.
 * Usage: node e2e/kampala-exam-marksheet-verify.cjs [pre|post]
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const MODE = process.argv[2] || 'pre';
const BASE = process.env.BASE_URL || 'https://klassapp.xyz';
const CREDS = JSON.parse(fs.readFileSync('/tmp/kpa-e2e-creds.json', 'utf8'));
const OUT = path.join(__dirname, 'screenshots', `kampala-marksheet-${MODE}`);
fs.mkdirSync(OUT, { recursive: true });

async function login(page, email, password) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"], input[type="email"]', email);
  await page.fill('input[name="password"], input[type="password"]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null),
    page.click('button[type="submit"], input[type="submit"]'),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ acceptDownloads: true });
  const page = await context.newPage();
  const report = { mode: MODE, base: BASE, steps: [] };

  try {
    // Admin exams list
    await login(page, CREDS.admin_email, CREDS.password);
    await page.goto(`${BASE}/admin/exams`, { waitUntil: 'networkidle', timeout: 60000 });
    await page.screenshot({ path: path.join(OUT, '01-admin-exams.png'), fullPage: true });
    const examsHtml = await page.content();
    const hasMath = /MATHEMATICS|Mathematics/i.test(examsHtml);
    const hasEnglish = /ENGLISH|English Language/i.test(examsHtml);
    const dashSubjectCells = (examsHtml.match(/>\s*-\s*<\/td>/g) || []).length;
    report.steps.push({
      step: 'admin_exams_list',
      url: page.url(),
      hasMath,
      hasEnglish,
      dashSubjectCells,
    });

    // Marksheet download for exam 11 (English / Primary Seven)
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 30000 }).catch(() => null),
      page.locator('a[href*="/admin/exams/11/marksheet"]').first().click().catch(() => null),
    ]);
    if (download) {
      const dest = path.join(OUT, download.suggestedFilename() || 'marksheet.xlsx');
      await download.saveAs(dest);
      report.steps.push({
        step: 'admin_marksheet_download',
        file: dest,
        bytes: fs.statSync(dest).size,
      });
    } else {
      report.steps.push({ step: 'admin_marksheet_download', error: 'no download' });
    }

    // Teacher enter marks for exam 10 (Math, teacher 117)
    await context.clearCookies();
    await login(page, CREDS.teacher2_email, CREDS.password);
    await page.goto(`${BASE}/teacher/exam/10/marks/enter`, { waitUntil: 'networkidle', timeout: 60000 });
    await page.screenshot({ path: path.join(OUT, '02-teacher-enter.png'), fullPage: true });
    const enterHtml = await page.content();
    const studentVisible = /Grace Nakamya/i.test(enterHtml);
    const studentCountText = await page.locator('text=/\\d+ students/i').first().textContent().catch(() => null);
    report.steps.push({
      step: 'teacher_enter_marks_page',
      url: page.url(),
      studentVisible,
      studentCountText,
    });

    if (studentVisible && MODE === 'post') {
      const input = page.locator('input[name="marks[116]"]').first();
      if (await input.count()) {
        await input.fill('81');
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null),
          page.click('button[type="submit"]'),
        ]);
        await page.screenshot({ path: path.join(OUT, '03-after-save.png'), fullPage: true });
        report.steps.push({ step: 'teacher_save_marks', url: page.url(), saved: true });
      } else {
        report.steps.push({ step: 'teacher_save_marks', error: 'marks[116] input missing' });
      }
    }

    // Fresh exam create (admin) — post-deploy only
    if (MODE === 'post') {
      await context.clearCookies();
      await login(page, CREDS.admin_email, CREDS.password);
      await page.goto(`${BASE}/admin/exams/create`, { waitUntil: 'networkidle', timeout: 60000 });
      await page.screenshot({ path: path.join(OUT, '04-create-exam.png'), fullPage: true });
      // Prefer Science / P.7 if form fields exist
      const sectionSelect = page.locator('select[name="section_id"], select#section_id, select[name="section"]').first();
      if (await sectionSelect.count()) {
        const options = await sectionSelect.locator('option').allTextContents();
        const p7 = options.findIndex((t) => /Primary Seven|P\.?7/i.test(t));
        if (p7 >= 0) await sectionSelect.selectOption({ index: p7 });
        await page.waitForTimeout(1500);
      }
      report.steps.push({ step: 'create_exam_form', url: page.url() });
    }

    fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
  } catch (e) {
    report.error = String(e);
    fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
    await page.screenshot({ path: path.join(OUT, 'error.png'), fullPage: true }).catch(() => null);
    console.error(JSON.stringify(report, null, 2));
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
