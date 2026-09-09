/**
 * Local UI verification for admin legacy-demographics strip + DOB optional.
 *
 *   PLAYWRIGHT_BASE_URL=http://127.0.0.1:8015 node e2e/legacy-demographics-ui-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8015';
const EMAIL = process.env.ADMIN_EMAIL || 'examfix.admin.1788867098@t.sch.ug';
const PASSWORD = process.env.ADMIN_PASSWORD || 'KlassAppTest@2026';
const TEACHER_NAME = process.env.TEACHER_NAME || 'Exam Fix Teacher';
const OUT = path.join(__dirname, 'screenshots', 'legacy-demographics');
const FORBIDDEN = [
  'Blood Group',
  'Aadhaar',
  'Aadhar',
  'Mother Tongue',
  'Birth Place',
  'Native Place',
  'Caste',
  'Sub Caste',
  'Marital Status',
  'Religion',
  'Nationality',
];

fs.mkdirSync(OUT, { recursive: true });

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null),
    page.click('button[type="submit"], input[type="submit"]'),
  ]);
}

function findForbidden(text) {
  return FORBIDDEN.filter((label) => text.includes(label));
}

async function checkPage(page, report, name, url, opts = {}) {
  await page.goto(`${BASE}${url}`, { waitUntil: 'networkidle', timeout: 90000 });
  await page.waitForTimeout(opts.wait || 2500);
  const shot = path.join(OUT, `${name}.png`);
  await page.screenshot({ path: shot, fullPage: true });
  const bodyText = await page.locator('body').innerText();
  const hits = findForbidden(bodyText);
  const hasDob = /Date Of Birth|Date of Birth/i.test(bodyText);
  const upgradeBlocked = /Upgrade Plan to Add More/i.test(bodyText);
  const result = {
    name,
    url,
    shot,
    forbiddenHits: hits,
    hasDobField: hasDob,
    upgradeBlocked,
    pass: hits.length === 0 && !upgradeBlocked && (!opts.requireDob || hasDob),
  };
  report.pages.push(result);
  console.log(
    `${result.pass ? 'PASS' : 'FAIL'} ${name}`,
    hits.length ? hits : 'clean',
    hasDob ? 'DOB present' : 'no DOB text',
    upgradeBlocked ? 'BLOCKED' : ''
  );
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });
  const report = {
    base: BASE,
    started_at: new Date().toISOString(),
    pages: [],
    pass: false,
  };

  try {
    await login(page);
    report.loginUrl = page.url();

    await checkPage(page, report, '01-student-add', '/admin/student/add', { requireDob: true });
    await checkPage(page, report, '02-teacher-add', '/admin/teacher/add', { requireDob: true });
    await checkPage(page, report, '03-staff-add', '/admin/staff/add', { requireDob: true });
    await checkPage(page, report, '04-teacher-edit', `/admin/teacher/edit/${encodeURIComponent(TEACHER_NAME)}`, {
      requireDob: true,
    });
    await checkPage(page, report, '05-export-student', '/admin/student/export');
    await checkPage(page, report, '06-export-teacher', '/admin/teacher/export');
    await checkPage(page, report, '07-export-staff', '/admin/staff/export');

    // DOB optional + marital gone: leave DOB empty and submit teacher create
    await page.goto(`${BASE}/admin/teacher/add`, { waitUntil: 'networkidle', timeout: 90000 });
    await page.waitForTimeout(2500);
    const firstname = page.locator('input[name="firstname"], #firstname').first();
    await firstname.waitFor({ state: 'visible', timeout: 30000 });
    await firstname.fill('NoDob');
    await page.locator('input[name="lastname"], #lastname').first().fill('Probe');
    await page.locator('input[name="mobile_no"], #mobile_no').first().fill('0700000099');
    await page.locator('input[name="employee_id"], #employee_id').first().fill('EMPNODB1');
    const gender = page.locator('select[name="gender"], #gender').first();
    if (await gender.count()) {
      await gender.selectOption({ index: 1 }).catch(() => null);
    }
    // Clear DOB if browser autofilled
    const dob = page.locator('input[name="date_of_birth"], #date_of_birth').first();
    if (await dob.count()) {
      await dob.fill('');
    }
    await page.locator('button[type="submit"], button:has-text("Submit"), button:has-text("Save")').first().click().catch(() => null);
    await page.waitForTimeout(2500);
    const afterSubmit = await page.locator('body').innerText();
    const dobRequiredError = /Date Of Birth Is Required|Date Of Birth is required/i.test(afterSubmit);
    const maritalRequired = /Marital Status/i.test(afterSubmit);
    const bloodShown = /Blood Group/i.test(afterSubmit);
    report.pages.push({
      name: '08-teacher-create-without-dob',
      pass: !dobRequiredError && !maritalRequired && !bloodShown,
      dobRequiredError,
      maritalRequired,
      bloodShown,
      shot: path.join(OUT, '08-teacher-create-without-dob.png'),
    });
    await page.screenshot({ path: path.join(OUT, '08-teacher-create-without-dob.png'), fullPage: true });
    console.log(`${!dobRequiredError && !maritalRequired && !bloodShown ? 'PASS' : 'FAIL'} 08-teacher-create-without-dob`, {
      dobRequiredError,
      maritalRequired,
      bloodShown,
    });

    report.pass = report.pages.every((p) => p.pass);
  } catch (e) {
    report.error = String(e);
    report.pass = false;
    console.error(e);
  } finally {
    fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
    await browser.close();
    console.log('REPORT', path.join(OUT, 'report.json'), 'pass=', report.pass);
    process.exit(report.pass ? 0 : 1);
  }
})();
