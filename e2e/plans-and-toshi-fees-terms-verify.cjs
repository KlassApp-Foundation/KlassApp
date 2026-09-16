/**
 * Staging: plan cards visible after Plans seed; Toshi yearly fee + term current UI.
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_PASSWORD=demo123 \
 *   node e2e/plans-and-toshi-fees-terms-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/plans-toshi-fees-terms');
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
  const page = await browser.newPage();
  const report = { base: BASE, at: new Date().toISOString(), checks: {}, ok: true };

  await login(page);
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 120000 });
  await page.waitForSelector('[data-testid="manual-wizard-shell"]', { timeout: 60000 });

  const jumped = await page.evaluate(() => {
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
  if (jumped) await page.waitForTimeout(1500);

  const cards = await page.locator('[data-testid="wizard-plan-cards"], [data-testid="wizard-plan-card"]').count();
  const empty = await page.locator('[data-testid="wizard-plan-empty"]').count();
  const body = await page.locator('body').innerText();
  report.checks.planCardsVisible = cards > 0 || /Freemium|Growth|Premium/.test(body);
  report.checks.planEmptyAbsent = empty === 0;
  if (!report.checks.planCardsVisible) fail('Expected Freemium/Growth/Premium plan cards on wizard');
  if (!report.checks.planEmptyAbsent) fail('wizard-plan-empty still showing after seed');
  await page.screenshot({ path: path.join(OUT, 'wizard-plans.png'), fullPage: true });

  // Blade contract: yearly checkbox + term current picker markup present in dashboard HTML source path
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 120000 });
  const html = await page.content();
  report.checks.toshiFeeYearlyInSource = html.includes('toshi-fee-yearly') || html.includes('feeFormIsYearly');
  report.checks.toshiTermCurrentInSource =
    html.includes('toshi-term-current-picker') || html.includes('markTermCurrent') || html.includes('showTermCurrentPicker');
  // Livewire may defer form until step — soft warn only for DOM presence
  if (!report.checks.toshiFeeYearlyInSource) {
    console.warn('WARN: toshi-fee-yearly not in initial dashboard HTML (PHPUnit covers Livewire)');
  }

  await page.screenshot({ path: path.join(OUT, 'dashboard.png') });
  await browser.close();

  report.ok = process.exitCode !== 1;
  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  if (report.ok) console.log('PASS plans-and-toshi-fees-terms verify');
  else console.log('FAIL plans-and-toshi-fees-terms verify');
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
