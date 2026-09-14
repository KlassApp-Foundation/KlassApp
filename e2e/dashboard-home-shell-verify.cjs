/**
 * Piece 4 / PR1 — Admin dashboard home shell kit parity + Pulse canaries.
 * Viewports: 375, 414, 768, 1280. Staging-only (no prod).
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=phase4.admin@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/dashboard-home-shell-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/dashboard-home-shell');
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

  const boot = await browser.newPage();
  await login(boot);
  await boot.close();

  for (const vp of viewports) {
    const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await context.newPage();
    await login(page);
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(800);

    const measured = await page.evaluate(() => {
      const greeting = document.querySelector('[data-testid="dashboard-greeting"]');
      const live = document.querySelector('[data-testid="dashboard-live-badge"]');
      const kpi = document.querySelector('[data-testid="dashboard-kpi-grid"]');
      const tools = document.querySelector('[data-testid="dashboard-connected-tools"]');
      const topfold = document.querySelector('[data-testid="dashboard-topfold-kit"]');
      const demo = document.querySelector('[data-testid="empty-state-product-demo"]');
      const kpiCard = document.querySelector('.ds-kpi-card');
      const kpiValue = document.querySelector('.ds-kpi-card .ds-kpi-value');
      return {
        url: location.href,
        hasGreeting: !!greeting,
        greetingText: greeting?.textContent?.trim() || null,
        hasLive: !!live,
        liveText: live?.textContent?.replace(/\s+/g, ' ').trim() || null,
        hasKpiGrid: !!kpi,
        hasTools: !!tools,
        hasTopfold: !!topfold,
        hasDemo: !!demo,
        incomplete: !!demo,
        kpiValueColor: kpiValue ? getComputedStyle(kpiValue).color : null,
        hasDsKpi: !!kpiCard,
      };
    });

    const shot = path.join(OUT, `dashboard-${vp.name}.png`);
    await page.screenshot({ path: shot, fullPage: true });

    const checks = {
      greetingOk: measured.hasGreeting && /Good (morning|afternoon|evening),/i.test(measured.greetingText || ''),
      liveOk: measured.hasLive && /Live/i.test(measured.liveText || ''),
      compositionOk: measured.incomplete
        ? measured.hasDemo && !measured.hasKpiGrid && !measured.hasTools
        : measured.hasKpiGrid && measured.hasTools && measured.hasTopfold && measured.hasDsKpi,
    };

    const ok = Object.values(checks).every(Boolean);
    if (!ok) {
      fail(`${vp.name}: ${JSON.stringify({ checks, measured })}`);
      report.ok = false;
    }
    report.viewports[vp.name] = { checks, measured, shot, ok };
    await context.close();
  }

  // Pulse canary on students ledger (complete-setup school)
  const pulsePage = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await login(pulsePage);
  await pulsePage.goto(`${BASE}/admin/students`, { waitUntil: 'load', timeout: 90000 });
  await pulsePage.waitForSelector('table.ds-table-ledger thead', { timeout: 20000 }).catch(() => null);
  const pulse = await pulsePage.evaluate(() => {
    const thead = document.querySelector('table.ds-table-ledger thead');
    if (!thead) {
      return { ok: false, reason: 'no ledger thead' };
    }
    const s = getComputedStyle(thead);
    return {
      ok: (s.backdropFilter || s.webkitBackdropFilter || '').includes('blur'),
      backdropFilter: s.backdropFilter || s.webkitBackdropFilter || null,
    };
  });
  report.pulse = pulse;
  if (!pulse.ok) {
    fail(`pulse ledger: ${JSON.stringify(pulse)}`);
    report.ok = false;
  }
  await pulsePage.close();

  await browser.close();
  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify({
    ok: report.ok,
    base: report.base,
    viewports: Object.fromEntries(Object.entries(report.viewports).map(([k, v]) => [k, { ok: v.ok, checks: v.checks, incomplete: v.measured.incomplete }])),
    pulse: report.pulse,
  }, null, 2));
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
