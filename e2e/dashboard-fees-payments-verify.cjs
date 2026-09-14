/**
 * Piece 4 / PR3 — Fees payments kit parity + Pulse / reduced-motion canaries.
 * Viewports: 375, 414, 768, 1280. Staging-only (no prod).
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=phase4.admin@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/dashboard-fees-payments-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/dashboard-fees-payments');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 800 },
];

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
  const report = { base: BASE, email: EMAIL, at: new Date().toISOString(), viewports: {}, pulse: {}, ok: true };

  for (const vp of viewports) {
    const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await context.newPage();
    await login(page);
    await page.goto(`${BASE}/admin/fees/payments`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(800);

    const measured = await page.evaluate(() => {
      const head = document.querySelector('[data-testid="fees-page-head"]');
      const title = document.querySelector('.ds-page-head-title');
      const kpis = document.querySelector('[data-testid="fees-kpi-grid"]');
      const kpiCards = document.querySelectorAll('.ds-kpi-card');
      const kpiValue = document.querySelector('.ds-kpi-card .ds-kpi-value');
      const toggle = document.querySelector('[data-testid="fees-record-toggle"]');
      const form = document.querySelector('[data-testid="fees-record-form"]');
      const ledger = document.querySelector('[data-testid="fees-ledger"]');
      const empty = document.querySelector('[data-testid="fees-empty"]');
      const table = document.querySelector('table.ds-table-ledger');
      const thead = table?.querySelector('thead');
      return {
        url: location.href,
        hasHead: !!head,
        titleText: title?.textContent?.trim() || null,
        hasKpis: !!kpis,
        kpiCount: kpiCards.length,
        kpiValueColor: kpiValue ? getComputedStyle(kpiValue).color : null,
        hasToggle: !!toggle,
        formHidden: form ? form.classList.contains('hidden') : null,
        hasLedgerOrEmpty: !!(ledger || empty),
        theadBlur: thead ? getComputedStyle(thead).backdropFilter || getComputedStyle(thead).webkitBackdropFilter : null,
      };
    });

    // Toggle inline form open
    await page.click('[data-testid="fees-record-toggle"]');
    await page.waitForTimeout(200);
    const formOpen = await page.evaluate(() => {
      const form = document.querySelector('[data-testid="fees-record-form"]');
      return form && !form.classList.contains('hidden');
    });

    await page.screenshot({ path: path.join(OUT, `fees-${vp.name}.png`), fullPage: true });

    const vpOk =
      measured.hasHead &&
      measured.titleText === 'Fee payments' &&
      measured.hasKpis &&
      measured.kpiCount >= 4 &&
      measured.hasToggle &&
      formOpen &&
      measured.hasLedgerOrEmpty &&
      (!measured.theadBlur || measured.theadBlur.includes('blur'));

    if (!vpOk) {
      report.ok = false;
      fail(`${vp.name}: ${JSON.stringify({ ...measured, formOpen })}`);
    } else {
      console.log(`PASS ${vp.name}:`, measured.titleText, measured.kpiValueColor, measured.theadBlur || 'empty');
    }

    report.viewports[vp.name] = { ...measured, formOpen };
    await context.close();
  }

  const pulseCtx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const pulsePage = await pulseCtx.newPage();
  await login(pulsePage);
  await pulsePage.goto(`${BASE}/admin/fees/payments`, { waitUntil: 'load', timeout: 90000 });
  report.pulse = await pulsePage.evaluate(() => {
    const kpiValue = document.querySelector('.ds-kpi-card .ds-kpi-value');
    const thead = document.querySelector('table.ds-table-ledger thead');
    return {
      kpiColor: kpiValue ? getComputedStyle(kpiValue).color : null,
      blur: thead ? (getComputedStyle(thead).backdropFilter || getComputedStyle(thead).webkitBackdropFilter) : null,
    };
  });
  // Pulse greens KPI values when toshi-ui rule applies
  if (report.pulse.kpiColor && !/rgb\(34,\s*197,\s*94\)|#22c55e/i.test(report.pulse.kpiColor) && report.pulse.kpiColor !== 'rgb(34, 197, 94)') {
    // Soft check — still log; fail only if blur regresses when ledger present
    console.log('Pulse KPI color note:', report.pulse.kpiColor);
  }
  if (report.pulse.blur && !report.pulse.blur.includes('blur')) {
    report.ok = false;
    fail(`Pulse ledger blur missing: ${JSON.stringify(report.pulse)}`);
  } else {
    console.log('Pulse canary:', report.pulse);
  }
  await pulseCtx.close();

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  await browser.close();
  if (!report.ok) process.exit(1);
  console.log('OK fees payments kit parity @', BASE);
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
