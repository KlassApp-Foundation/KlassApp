/**
 * Holistic four-surface cutover verify (Pieces 1–4 together).
 * Runs existing piece verifiers against one BASE, plus cross-surface smoke.
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_PASSWORD=demo123 \
 *   node e2e/four-surface-cutover-verify.cjs
 *
 *   PREVIEW_BASE=https://klassapp.xyz CUTOVER_MODE=prod \
 *   DASH_EMAIL=… DASH_PASSWORD=… node e2e/four-surface-cutover-verify.cjs
 */
const { chromium } = require('playwright');
const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const MODE = process.env.CUTOVER_MODE || 'staging';
const OUT = path.join(__dirname, 'screenshots/four-surface-cutover', MODE);
fs.mkdirSync(OUT, { recursive: true });

const ADMIN_EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const TEACHER_EMAIL = process.env.TEACHER_EMAIL || 'phase4.teacher@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';

const CHILD_SCRIPTS = [
  'landing-d-tokens-verify.cjs',
  'auth-d-tokens-verify.cjs',
  'errors-d-tokens-verify.cjs',
  'dashboard-home-shell-verify.cjs',
  'dashboard-students-roster-verify.cjs',
  'dashboard-fees-payments-verify.cjs',
  'dashboard-exams-marks-verify.cjs',
  'wizard-shell-nav-verify.cjs',
  'wizard-piece3-wrap-verify.cjs',
  'wizard-toshi-parity-bugs-verify.cjs',
  'plans-and-toshi-fees-terms-verify.cjs',
  'toshi-piece2-docking-pill-verify.cjs',
];

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

function runChild(script) {
  const env = {
    ...process.env,
    PREVIEW_BASE: BASE,
    DASH_EMAIL: ADMIN_EMAIL,
    TEACHER_EMAIL,
    DASH_PASSWORD: PASSWORD,
  };
  console.log(`\n── child: ${script} ──`);
  const r = spawnSync(process.execPath, [path.join(__dirname, script)], {
    env,
    encoding: 'utf8',
    timeout: 10 * 60 * 1000,
  });
  if (r.stdout) process.stdout.write(r.stdout);
  if (r.stderr) process.stderr.write(r.stderr);
  return {
    script,
    status: r.status,
    signal: r.signal,
    ok: r.status === 0,
  };
}

async function login(page, email) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', email);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

async function holisticSmoke(browser) {
  const checks = {};
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });

  // Landing — use `load` (not networkidle): staging hibernation + analytics keep the network busy.
  let res = await page.goto(`${BASE}/`, { waitUntil: 'load', timeout: 120000 });
  checks.landingOk = !!(res && res.ok());
  await page.waitForTimeout(800);
  const landing = await page.evaluate(() => {
    const body = getComputedStyle(document.body);
    return {
      bg: body.backgroundColor,
      font: body.fontFamily,
      hasSora: /Sora/i.test(document.documentElement.outerHTML),
    };
  });
  checks.landingPaper = landing.bg === 'rgb(250, 250, 245)' || /Sora/i.test(landing.font);
  checks.landingSora = landing.hasSora;
  await page.screenshot({ path: path.join(OUT, 'landing-1280.png'), fullPage: true });

  // Login
  res = await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  checks.loginOk = !!(res && res.ok());
  await page.screenshot({ path: path.join(OUT, 'login-1280.png') });

  // Forced 404
  res = await page.goto(`${BASE}/__cutover-forced-404__`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  checks.forced404Status = res ? res.status() : null;
  checks.forced404Page = checks.forced404Status === 404 || /not found|404/i.test(await page.title().catch(() => ''));
  await page.screenshot({ path: path.join(OUT, 'forced-404.png') });

  // Admin dashboard + Pulse canary + Toshi docked
  await login(page, ADMIN_EMAIL);
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 120000 });
  await page.waitForTimeout(1500);
  checks.adminDashboardUrl = page.url();
  checks.adminLoggedIn = /\/admin\//.test(page.url());
  await page.evaluate(() => document.body.classList.remove('toshi-collapsed'));
  await page.waitForTimeout(500);
  const adminToshi = await page.evaluate(() => {
    const panel = document.querySelector('#toshi-panel');
    const before = panel ? getComputedStyle(panel, '::before') : null;
    return {
      toshiVisible: !!(panel && getComputedStyle(panel).display !== 'none'),
      dockClay: before ? before.backgroundColor : '',
    };
  });
  checks.adminToshiDocked = adminToshi.toshiVisible;
  // Incomplete-setup schools have no KPI strip — Pulse is verified on a ledger page.
  await page.goto(`${BASE}/admin/students`, { waitUntil: 'load', timeout: 120000 });
  await page.waitForTimeout(800);
  const pulse = await page.evaluate(() => {
    const thead = document.querySelector('.ledger-table thead, table.ds-table thead, .ds-table thead, thead');
    const kpi = document.querySelector('.ds-kpi-card, [data-testid="ds-kpi-card"]');
    return {
      hasKpi: !!kpi,
      theadBlur: thead
        ? getComputedStyle(thead).backdropFilter || getComputedStyle(thead).webkitBackdropFilter || ''
        : '',
    };
  });
  checks.pulseTheadBlur = /blur/i.test(pulse.theadBlur || '');
  // KPI cards only appear on completed schools — do not fail incomplete-setup dashboards.
  checks.adminLedgerLoaded = /\/admin\/students/.test(page.url());
  await page.screenshot({ path: path.join(OUT, 'admin-students-pulse.png'), fullPage: true });

  // Wizard plan selection (plan-selection fix)
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 120000 });
  const shell = page.locator('[data-testid="manual-wizard-shell"]');
  checks.wizardShell = await shell.isVisible().catch(() => false);
  if (checks.wizardShell) {
    await page.evaluate(() => {
      const root = document.querySelector('[data-testid="manual-wizard-shell"]');
      if (!root || !window.Livewire) return false;
      const comp = window.Livewire.find(root.getAttribute('wire:id'));
      if (!comp) return false;
      const steps = comp.get('steps') || [];
      const idx = steps.findIndex((s) => s && s.key === 'plan_selection');
      if (idx < 0) return false;
      comp.call('goToStep', idx);
      return true;
    }).catch(() => false);
    await page.waitForTimeout(1500);
    const body = await page.locator('body').innerText();
    const cards = await page.locator('[data-testid="wizard-plan-cards"], [data-testid="wizard-plan-card"]').count();
    const empty = await page.locator('[data-testid="wizard-plan-empty"]').count();
    checks.planCards = cards > 0 || /Freemium|Growth|Premium/.test(body);
    checks.planEmptyAbsent = empty === 0;
    await page.screenshot({ path: path.join(OUT, 'wizard-plans.png'), fullPage: true });
  }

  // Teacher + Toshi mobile
  const teacher = await browser.newPage({ viewport: { width: 375, height: 812 } });
  await login(teacher, TEACHER_EMAIL);
  await teacher.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'load', timeout: 120000 });
  await teacher.waitForTimeout(1500);
  checks.teacherLoggedIn = /\/teacher\//.test(teacher.url());
  await teacher.evaluate(() => document.body.classList.remove('toshi-collapsed'));
  await teacher.waitForTimeout(400);
  const teacherToshi = await teacher.locator('[data-toshi-root], #toshi-panel, [data-testid="toshi-pill"]').count();
  checks.teacherToshiPresent = teacherToshi > 0;
  await teacher.screenshot({ path: path.join(OUT, 'teacher-mobile-toshi.png'), fullPage: true });
  await teacher.close();
  await page.close();

  for (const [k, v] of Object.entries(checks)) {
    if (v === false || v === null) fail(`holistic.${k}=${v}`);
  }
  return checks;
}

(async () => {
  const report = {
    mode: MODE,
    base: BASE,
    at: new Date().toISOString(),
    children: [],
    holistic: {},
    ok: true,
  };

  console.log(`Four-surface cutover verify — ${MODE} @ ${BASE}`);

  for (const script of CHILD_SCRIPTS) {
    const result = runChild(script);
    report.children.push(result);
    if (!result.ok) {
      fail(`child failed: ${script} (status=${result.status})`);
      // Continue remaining children so we get full evidence, but mark fail.
    }
  }

  const browser = await chromium.launch({ headless: true });
  try {
    report.holistic = await holisticSmoke(browser);
  } catch (e) {
    fail(`holistic smoke threw: ${e.message}`);
    report.holisticError = String(e.stack || e);
  }
  await browser.close();

  report.ok = process.exitCode !== 1;
  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log('\n=== FOUR-SURFACE CUTOVER REPORT ===');
  console.log(JSON.stringify(report, null, 2));
  process.exit(report.ok ? 0 : 1);
})();
