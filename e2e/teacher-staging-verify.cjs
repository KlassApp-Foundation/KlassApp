/**
 * Post-#873 staging verification — synthetic class teacher.
 * Flows: dashboard, own-class attendance, foreign-class attendance (403),
 * timetable view + valid edit + unauthorized-class edit (403), homework create + list.
 *
 * Usage (staging):
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   TEACHER_EMAIL=... TEACHER_PASSWORD=... \
 *   node e2e/teacher-staging-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'https://klassapp-staging-7mpoqg.laravel.cloud').replace(/\/$/, '');
const EMAIL = process.env.TEACHER_EMAIL;
const PASSWORD = process.env.TEACHER_PASSWORD;
const SLOT_ID = process.env.SLOT_ID || '1';
const OWN_LINK = process.env.OWN_LINK || '4';
const FOREIGN_LINK = process.env.FOREIGN_LINK || '3';
const OWN_SUBJECT = process.env.OWN_SUBJECT || '9';
const HOMEWORK_TEXT = 'Synthetic homework 873 verification';
const OUT = path.join(__dirname, 'screenshots', 'teacher-staging-873');
fs.mkdirSync(OUT, { recursive: true });

if (!EMAIL || !PASSWORD) {
  console.error('TEACHER_EMAIL and TEACHER_PASSWORD are required');
  process.exit(2);
}

const report = { base: BASE, email: EMAIL, at: new Date().toISOString(), checks: {}, ok: true };
function check(name, ok, detail = '') {
  report.checks[name] = { ok: !!ok, detail: String(detail) };
  if (!ok) report.ok = false;
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? ` — ${detail}` : ''}`);
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
  await page.waitForTimeout(1500);
  const url = page.url();
  if (url.includes('/login')) {
    const err = await page.locator('.alert, .invalid-feedback, [role="alert"]').first().textContent().catch(() => '');
    throw new Error(`login failed, still on ${url}: ${err}`);
  }
}

async function xsrfHeader(context) {
  const cookies = await context.cookies();
  const xsrf = cookies.find((c) => c.name === 'XSRF-TOKEN');
  return xsrf ? decodeURIComponent(xsrf.value) : '';
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();

  try {
    // ── Login ────────────────────────────────────────────────────────────
    await login(page);
    check('login', !page.url().includes('/login'), page.url());

    // ── 1. Dashboard ─────────────────────────────────────────────────────
    await page.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(1200);
    const shell = await page.locator('[data-testid="teacher-dashboard-shell"]').count();
    const kpis = await page.locator('[data-testid="dashboard-kpi-grid"]').count();
    const greeting = await page.locator('[data-testid="dashboard-greeting"]').textContent().catch(() => '');
    check('dashboard shell renders', shell > 0, `url=${page.url()}`);
    check('dashboard kpi grid renders', kpis > 0, `greeting="${(greeting || '').trim().slice(0, 60)}"`);
    await page.screenshot({ path: path.join(OUT, 'dashboard-1280.png'), fullPage: true });

    // ── 2. Attendance — own class (must succeed) ─────────────────────────
    await page.goto(`${BASE}/teacher/attendance/add`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForSelector('select', { timeout: 30000 });
    await page.waitForTimeout(1200);
    const classSelect = page.locator('select').first();
    const ownOptionCount = await classSelect.locator(`option[value="${OWN_LINK}"]`).count();
    const foreignOptionCount = await classSelect.locator(`option[value="${FOREIGN_LINK}"]`).count();
    check('attendance class list contains own class', ownOptionCount > 0, `option value=${OWN_LINK}`);
    check('attendance class list excludes foreign class (server-scoped)', foreignOptionCount === 0, `option value=${FOREIGN_LINK} count=${foreignOptionCount}`);
    await classSelect.selectOption(OWN_LINK);
    await page.click('button:has-text("Select Students")');
    await page.waitForTimeout(1500);
    const studentVisible = await page.getByText('Synthetic Student 873').first().isVisible().catch(() => false);
    check('own-class roster shows synthetic student', studentVisible);
    await page.check('input[type="radio"][value="forenoon"]', { force: true }).catch(() => {});
    const attendRespP = page
      .waitForResponse((r) => r.url().includes('/teacher/attendance/add') && r.request().method() === 'POST', { timeout: 30000 })
      .catch(() => null);
    await page.click('button:has-text("Submit Attendance")');
    const attendResp = await attendRespP;
    await page.waitForTimeout(1000);
    const attendSuccess = await page.locator('#success-alert').textContent().catch(() => '');
    check('own-class attendance POST succeeds', !!attendResp && attendResp.status() === 200, `status=${attendResp ? attendResp.status() : 'no response'}`);
    check('own-class attendance success message', !!attendSuccess && attendSuccess.trim().length > 0, (attendSuccess || '').trim().slice(0, 80));
    await page.screenshot({ path: path.join(OUT, 'attendance-own-class.png'), fullPage: true });

    // ── 3. Attendance — foreign class (must be 403) ──────────────────────
    const token = await xsrfHeader(context);
    const today = new Date().toISOString().slice(0, 10);
    const refused = await context.request.post(`${BASE}/teacher/attendance/add`, {
      headers: { 'X-XSRF-TOKEN': token, Referer: `${BASE}/teacher/attendance/add` },
      form: {
        standardLink_id: FOREIGN_LINK,
        date: today,
        session: 'forenoon',
        absentCount: '0',
        presentCount: '0',
      },
      failOnStatusCode: false,
    });
    check('foreign-class attendance refused with 403', refused.status() === 403, `status=${refused.status()}`);

    // ── 4. Timetable — view ──────────────────────────────────────────────
    await page.goto(`${BASE}/teacher/timetable`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(1000);
    const ttBody = await page.locator('body').textContent().catch(() => '');
    check('timetable lists synthetic slot', ttBody.includes('08:00') || ttBody.includes('08:00:00'), 'slot 08:00 present');
    check('timetable shows synthetic subject', ttBody.includes('SYN873 MATH'));
    await page.screenshot({ path: path.join(OUT, 'timetable-view.png'), fullPage: true });

    // ── 5. Timetable — valid edit (own class/subject, time change) ───────
    await page.goto(`${BASE}/teacher/timetable/${SLOT_ID}/edit`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForSelector(`select[name="section_id"]`, { timeout: 30000 });
    await page.fill('input[name="start_time"]', '09:00');
    await page.fill('input[name="end_time"]', '09:40');
    // @method('PUT') is wire-POSTed with a _method field — match the real wire method.
    const editRespP = page
      .waitForResponse((r) => r.request().method() === 'POST' && r.url().endsWith(`/timetable/${SLOT_ID}`), { timeout: 30000 })
      .catch(() => null);
    await page.click('button:has-text("Update Slot")');
    const editResp = await editRespP;
    await page.waitForTimeout(1500);
    check('timetable valid edit accepted', !!editResp && [200, 302].includes(editResp.status()), `status=${editResp ? editResp.status() : 'no response'} url=${page.url()}`);
    const afterEdit = await page.locator('body').textContent().catch(() => '');
    check('timetable edit success flash', afterEdit.includes('Timetable slot updated'), '');
    const showsNewTime = afterEdit.includes('09:00');
    check('timetable shows updated time 09:00', showsNewTime);
    await page.screenshot({ path: path.join(OUT, 'timetable-edited.png'), fullPage: true });

    // ── 6. Timetable — move to a class the teacher is not assigned to (403) ──
    // The edit form only offers teacherlink-filtered sections/subjects, so the
    // unauthorized move is exercised as a forged request against the server-side
    // gate (TimetableSlotController::validateTeacherSlot → abort 403).
    const token2 = await xsrfHeader(context);
    const forbidden = await context.request.post(`${BASE}/teacher/timetable/${SLOT_ID}`, {
      headers: { 'X-XSRF-TOKEN': token2, Referer: `${BASE}/teacher/timetable/${SLOT_ID}/edit` },
      form: {
        _method: 'PUT',
        section_id: FOREIGN_LINK,
        subject_id: OWN_SUBJECT,
        day_of_week: '1',
        start_time: '10:00',
        end_time: '10:40',
        room: 'Synth Lab',
      },
      failOnStatusCode: false,
    });
    check('timetable move to foreign class refused with 403', forbidden.status() === 403, `status=${forbidden.status()}`);
    await page.screenshot({ path: path.join(OUT, 'timetable-403.png'), fullPage: true });

    // ── 7. Homework — view list, create, verify ──────────────────────────
    await page.goto(`${BASE}/teacher/homeworks`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(1000);
    const hwListBody = await page.locator('body').textContent().catch(() => '');
    check('homework list page renders', hwListBody.length > 200 && !hwListBody.includes('Whoops'), `url=${page.url()}`);
    await page.screenshot({ path: path.join(OUT, 'homework-list.png'), fullPage: true });

    await page.goto(`${BASE}/teacher/homework/add`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForSelector('#standardLink_id', { timeout: 30000 });
    await page.waitForTimeout(1200);
    await page.selectOption('#standardLink_id', OWN_LINK);
    await page.waitForSelector('#subject_id', { state: 'visible', timeout: 30000 });
    await page.selectOption('#subject_id', OWN_SUBJECT);
    await page.waitForTimeout(800);
    // teacher select (nullable in teacher mode) — pick ours when an option renders
    const teachOpt = page.locator('#subject_id ~ select option, select option', { hasText: 'Synthetic Teacher 873' }).first();
    if ((await teachOpt.count()) > 0) {
      const tval = await teachOpt.getAttribute('value');
      if (tval) {
        const tsel = teachOpt.locator('xpath=ancestor::select[1]');
        await tsel.selectOption(tval);
      }
    }
    await page.fill('.ql-editor', HOMEWORK_TEXT);
    const subDate = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10);
    await page.fill('input[name="submission_date"]', subDate);
    const hwRespP = page
      .waitForResponse((r) => r.url().includes('/teacher/homework/add') && r.request().method() === 'POST', { timeout: 30000 })
      .catch(() => null);
    await page.click('#submit-btn');
    const hwResp = await hwRespP;
    await page.waitForTimeout(1200);
    const hwSuccess = await page.locator('#success-alert').textContent().catch(() => '');
    check('homework create POST succeeds', !!hwResp && hwResp.status() === 200, `status=${hwResp ? hwResp.status() : 'no response'}`);
    check('homework create success message', !!hwSuccess && hwSuccess.trim().length > 0, (hwSuccess || '').trim().slice(0, 80));
    await page.screenshot({ path: path.join(OUT, 'homework-created.png'), fullPage: true });

    // ── 8. Mobile smoke (375) — dashboard chrome ─────────────────────────
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(1000);
    const mobileShell = await page.locator('[data-testid="teacher-dashboard-shell"]').count();
    check('dashboard renders at 375', mobileShell > 0);
    await page.screenshot({ path: path.join(OUT, 'dashboard-375.png'), fullPage: true });
  } catch (err) {
    report.ok = false;
    report.error = String(err && err.stack ? err.message : err);
    console.error('ERROR:', report.error);
    await page.screenshot({ path: path.join(OUT, 'failure.png'), fullPage: true }).catch(() => {});
  } finally {
    fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
    console.log(`\nREPORT: ${path.join(OUT, 'report.json')} — overall=${report.ok ? 'PASS' : 'FAIL'}`);
    await browser.close();
  }
  process.exit(report.ok ? 0 : 1);
})();
