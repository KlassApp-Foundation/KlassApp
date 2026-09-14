/**
 * Register a synthetic school admin and walk the wizard through plan selection.
 * Confirms end-to-end plan-selection fix on staging or production.
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   node e2e/four-surface-register-wizard-walkthrough.cjs
 *
 *   PREVIEW_BASE=https://klassapp.xyz CUTOVER_MODE=prod \
 *   node e2e/four-surface-register-wizard-walkthrough.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const MODE = process.env.CUTOVER_MODE || 'staging';
const OUT = path.join(__dirname, 'screenshots/four-surface-cutover', MODE, 'register-wizard');
fs.mkdirSync(OUT, { recursive: true });

const stamp = Date.now();
const email = `cutover.${MODE}.${stamp}@v.test`;
const phone = `+25670${String(stamp).slice(-7)}`;
const password = 'CutoverTest123!';
const adminName = 'Cutover Test Admin';

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

(async () => {
  const report = { mode: MODE, base: BASE, email, at: new Date().toISOString(), checks: {}, ok: true };
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.setDefaultTimeout(60000);

  await page.goto(`${BASE}/register`, { waitUntil: 'load', timeout: 120000 });
  await page.getByLabel('Your Full Name').fill(adminName);
  await page.getByLabel('Email Address').fill(email);
  await page.getByLabel('Phone (WhatsApp)').fill(phone);
  await page.locator('#password').fill(password);
  await page.locator('#password-confirm').fill(password);
  const tos = page.getByLabel(/I agree to/i);
  if (await tos.count()) await tos.check();

  await page.getByRole('button', { name: 'Create account with password' }).click();
  try {
    await page.waitForURL(/\/admin\/dashboard/, { timeout: 90000 });
    report.checks.registerLanded = true;
  } catch (e) {
    report.checks.registerLanded = false;
    report.checks.registerUrl = page.url();
    report.checks.registerErrors = await page.locator('.klass-error, [role=alert], .invalid-feedback').allTextContents().catch(() => []);
    await page.screenshot({ path: path.join(OUT, 'register-fail.png'), fullPage: true });
    fail(`register did not reach dashboard: ${page.url()}`);
    fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
    await browser.close();
    process.exit(1);
  }
  await page.screenshot({ path: path.join(OUT, 'dashboard-after-register.png'), fullPage: true });

  // Enter manual wizard
  const manual = page.getByText(/set up manually|continue school setup/i).first();
  if (await manual.isVisible().catch(() => false)) {
    await manual.click();
    await page.waitForTimeout(1500);
  }
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 120000 });
  await page.waitForSelector('[data-testid="manual-wizard-shell"]', { timeout: 60000 });
  report.checks.wizardShell = true;

  // Jump to plan_selection and assert cards
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
  await page.waitForTimeout(1800);
  report.checks.jumpedPlan = jumped;
  const body = await page.locator('body').innerText();
  const cards = await page.locator('[data-testid="wizard-plan-cards"], [data-testid="wizard-plan-card"]').count();
  const empty = await page.locator('[data-testid="wizard-plan-empty"]').count();
  report.checks.planCards = cards > 0 || /Freemium|Growth|Premium/.test(body);
  report.checks.planEmptyAbsent = empty === 0;
  if (!report.checks.planCards) fail('plan cards missing after register');
  if (!report.checks.planEmptyAbsent) fail('empty-plan still showing');
  await page.screenshot({ path: path.join(OUT, 'wizard-plans.png'), fullPage: true });

  // Select first plan if clickable and Continue
  const firstCard = page.locator('[data-testid="wizard-plan-card"], .manual-wizard-plan-card').first();
  if (await firstCard.isVisible().catch(() => false)) {
    await firstCard.click();
    await page.waitForTimeout(600);
    const cont = page.locator('[data-testid="wizard-next"], [data-testid="wizard-continue"], button:has-text("Continue")').first();
    if (await cont.isVisible().catch(() => false)) {
      await cont.click();
      await page.waitForTimeout(1200);
      report.checks.planContinue = true;
    }
  }

  // Spot-check a few more redesigned steps still load for this new school
  for (const step of ['student_size', 'school_category', 'standards', 'review']) {
    const ok = await page.evaluate((key) => {
      const root = document.querySelector('[data-testid="manual-wizard-shell"]');
      if (!root || !window.Livewire) return false;
      const comp = window.Livewire.find(root.getAttribute('wire:id'));
      if (!comp) return false;
      const steps = comp.get('steps') || [];
      const idx = steps.findIndex((s) => s && s.key === key);
      if (idx < 0) return false;
      comp.call('goToStep', idx);
      return true;
    }, step).catch(() => false);
    await page.waitForTimeout(900);
    report.checks[`step_${step}`] = ok;
    await page.screenshot({ path: path.join(OUT, `step-${step}.png`) });
  }

  await browser.close();
  report.ok = process.exitCode !== 1;
  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  console.log(report.ok ? 'PASS register+wizard walkthrough' : 'FAIL register+wizard walkthrough');
  process.exit(report.ok ? 0 : 1);
})();
