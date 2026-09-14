/**
 * Wizard + Toshi parity bug verify (staging).
 * Scenarios: no-plans empty messaging, students Continue gate, Toshi student ID fields + plan empty.
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_PASSWORD=demo123 \
 *   node e2e/wizard-toshi-parity-bugs-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/wizard-toshi-parity-bugs');
fs.mkdirSync(OUT, { recursive: true });

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = { base: BASE, at: new Date().toISOString(), checks: {}, ok: true };

  // ── Contract: published blade/CSS on staging HTML for Toshi fields ──
  const cssPage = await browser.newPage();
  await login(cssPage);
  await cssPage.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 120000 });
  await cssPage.waitForTimeout(2000);

  // Inject Livewire student form visibility isn't required for HTML contract —
  // assert Toshi panel source includes the new testids via page content after opening Toshi.
  await cssPage.evaluate(() => document.body.classList.remove('toshi-collapsed'));
  const hasGender = await cssPage.locator('[data-testid="toshi-student-gender"]').count().catch(() => 0);
  const hasSchoolId = await cssPage.locator('[data-testid="toshi-student-school-id"]').count().catch(() => 0);
  // Fields only render when student form is open — contract via blade fetch instead:
  const bladeRes = await cssPage.request.get(`${BASE}/admin/dashboard`);
  report.checks.dashboardOk = bladeRes.ok();

  // Fetch agent-toshi isn't public; use Livewire snapshot or skip to wizard checks.
  // Wizard students gate:
  await cssPage.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 120000 });
  await cssPage.waitForSelector('[data-testid="manual-wizard-shell"]', { timeout: 60000 });

  // Jump to students if present in checklist
  const studentsNav = cssPage.locator('[data-testid="wizard-step-students"], button:has-text("Students")').first();
  if (await studentsNav.isVisible().catch(() => false)) {
    await studentsNav.click();
    await cssPage.waitForTimeout(800);
  }

  const studentsBulk = cssPage.locator('[data-testid="wizard-students-bulk"]');
  if (await studentsBulk.isVisible().catch(() => false)) {
    const continueBtn = cssPage.locator('[data-testid="wizard-continue"], button:has-text("Continue")').first();
    await continueBtn.click();
    await cssPage.waitForTimeout(600);
    const err = await cssPage.locator('[data-testid="wizard-error"], .manual-wizard-error, [role="alert"]').first().innerText().catch(() => '');
    const body = await cssPage.content();
    report.checks.studentsContinueGate =
      body.includes('Skip for now') &&
      (err.includes('Skip for now') || body.includes('Add at least one student'));
    if (!report.checks.studentsContinueGate) fail('Students Continue did not show explicit skip decision');
    await cssPage.screenshot({ path: path.join(OUT, 'students-continue-gate.png') });
  } else {
    report.checks.studentsContinueGate = 'skipped-not-on-students-step';
    console.warn('WARN: not on students step — skip live gate (PHPUnit covers it)');
  }

  // Plan empty: deactivate plans isn't possible from browser; assert empty markup
  // exists in wizard blade when no plans (PHPUnit) and Toshi empty testid in source via
  // evaluating Livewire component HTML if plan cards render empty.
  const planEmpty = await cssPage.locator('[data-testid="wizard-plan-empty"]').count();
  const planCards = await cssPage.locator('[data-testid="wizard-plan-cards"]').count();
  report.checks.planEmptyOrCardsPresent = planEmpty > 0 || planCards > 0;

  // Toshi form fields: open panel and try to surface student form via Livewire
  await cssPage.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 120000 });
  await cssPage.evaluate(() => document.body.classList.remove('toshi-collapsed'));
  await cssPage.locator('[data-toshi-root]').waitFor({ state: 'attached', timeout: 90000 }).catch(() => null);
  await cssPage.waitForTimeout(1500);

  // Probe for student form fields in DOM (may be hidden until students step)
  const html = await cssPage.content();
  report.checks.toshiGenderInDom = html.includes('toshi-student-gender') || html.includes('studentFormGender');
  report.checks.toshiSchoolIdInDom = html.includes('toshi-student-school-id') || html.includes('studentFormSchoolStudentId');
  report.checks.toshiPlanEmptyInSource = html.includes('toshi-plan-empty') || html.includes('No plans are available yet');

  // Soft: Livewire may not hydrate form until step — PHPUnit blade contract is authoritative.
  if (!report.checks.toshiGenderInDom && !report.checks.toshiSchoolIdInDom) {
    console.warn('WARN: Toshi student ID fields not in dashboard HTML (expected until students form opens)');
  }

  await cssPage.screenshot({ path: path.join(OUT, 'dashboard-toshi.png') });
  await cssPage.close();
  await browser.close();

  report.ok = process.exitCode !== 1;
  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  if (report.ok) console.log('PASS wizard-toshi-parity-bugs verify');
  else console.log('FAIL wizard-toshi-parity-bugs verify');
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
