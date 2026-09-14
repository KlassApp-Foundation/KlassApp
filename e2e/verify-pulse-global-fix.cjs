/**
 * Pulse global consistency verification (admin KPI + ledger table blur).
 * Usage: node e2e/verify-pulse-global-fix.cjs [before|after] [baseUrl]
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const phase = process.argv[2] || 'after';
const BASE = process.argv[3] || 'https://klassapp.xyz';
const OUTDIR = path.join(__dirname, 'screenshots/pulse-global-fix');
fs.mkdirSync(OUTDIR, { recursive: true });

const ADMIN = { email: 'admin@uireview.klassapp.demo', password: 'UiReview2026!' };

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.fill('input[name="email"], input[type="email"]', ADMIN.email);
  await page.fill('input[name="password"], input[type="password"]', ADMIN.password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null),
    page.click('button[type="submit"], input[type="submit"]'),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });
  const report = { phase, base: BASE, at: new Date().toISOString(), checks: [] };

  await login(page);
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'networkidle', timeout: 90000 });
  await page.waitForTimeout(1500);

  const dashShot = path.join(OUTDIR, `${phase}-admin-dashboard.png`);
  await page.screenshot({ path: dashShot, fullPage: false });

  const kpi = await page.evaluate(() => {
    const legacy = document.querySelector('.dashboard-kpi-card');
    const modern = document.querySelector('.ds-kpi-card');
    const value = document.querySelector('.ds-kpi-value, .dashboard-kpi-value');
    const card = modern || legacy;
    if (!card || !value) {
      return {
        ok: false,
        url: location.href,
        title: document.title,
        hasLegacy: !!legacy,
        hasModern: !!modern,
        bodySnippet: document.body.innerText.slice(0, 400),
      };
    }
    const vs = getComputedStyle(value);
    const cs = getComputedStyle(card);
    return {
      ok: true,
      url: location.href,
      hasLegacy: !!legacy,
      hasModern: !!modern,
      valueClass: value.className,
      valueColor: vs.color,
      boxShadow: cs.boxShadow,
      transform: cs.transform,
    };
  });
  report.checks.push({ name: 'admin_dashboard_kpi', ...kpi });

  await page.goto(`${BASE}/admin/students`, { waitUntil: 'networkidle', timeout: 90000 });
  await page.waitForSelector('table.ds-table-ledger', { timeout: 30000 }).catch(() => null);
  await page.waitForTimeout(1000);

  const tableShot = path.join(OUTDIR, `${phase}-ledger-table.png`);
  await page.screenshot({ path: tableShot, fullPage: false });

  const thead = await page.evaluate(() => {
    const theadEl = document.querySelector('table.ds-table-ledger thead');
    if (!theadEl) {
      return { ok: false, url: location.href };
    }
    const s = getComputedStyle(theadEl);
    const blur = s.backdropFilter || s.webkitBackdropFilter || 'none';
    return {
      ok: true,
      backdropFilter: blur,
      background: s.backgroundColor,
      position: s.position,
      zIndex: s.zIndex,
      hasBlur: blur !== 'none' && blur !== '',
    };
  });
  report.checks.push({ name: 'ledger_thead_blur', ...thead });

  const cssRes = await page.request.get(`${BASE}/vendor/toshi-ui/toshi-ui.css`);
  const css = await cssRes.text();
  const hasLedgerTheadRule = /\.ds-table-ledger\s+thead/.test(css);
  report.checks.push({ name: 'css_has_ds-table-ledger_thead', ok: hasLedgerTheadRule, status: cssRes.status() });

  const outJson = path.join(OUTDIR, `${phase}-metrics.json`);
  fs.writeFileSync(outJson, JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  await browser.close();
})();
