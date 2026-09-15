/**
 * Piece 4 / PR2 — Students roster kit parity + Pulse canaries.
 * Viewports: 375, 414, 768, 1280. Staging-only (no prod).
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=phase4.admin@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/dashboard-students-roster-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/dashboard-students-roster');
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
    await page.goto(`${BASE}/admin/students`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(800);

    const measured = await page.evaluate(() => {
      const head = document.querySelector('[data-testid="students-page-head"]');
      const title = document.querySelector('.ds-page-head-title');
      const sub = document.querySelector('[data-testid="students-page-sub"]');
      const filter = document.querySelector('[data-testid="students-filter-card"]');
      const ledger = document.querySelector('[data-testid="students-ledger"]');
      const empty = document.querySelector('[data-testid="students-empty"]');
      const pagination = document.querySelector('[data-testid="students-pagination"]');
      const table = document.querySelector('table.ds-table-ledger');
      const thead = table?.querySelector('thead');
      const nameLink = document.querySelector('.dt-name-link');
      const whatsappHeader = Array.from(document.querySelectorAll('th')).some(
        (th) => /whatsapp/i.test(th.textContent || '')
      );
      const selectAll = document.querySelector('#select-all, .dt-checkbox');
      const sortArrow = document.querySelector('.dt-sort-arrow');
      return {
        url: location.href,
        hasHead: !!head,
        titleText: title?.textContent?.trim() || null,
        subText: sub?.textContent?.trim() || null,
        hasFilter: !!filter,
        hasLedger: !!ledger,
        hasEmpty: !!empty,
        hasPagination: !!pagination,
        hasTable: !!table,
        hasNameLink: !!nameLink,
        hasWhatsAppCol: whatsappHeader || !!empty,
        hasSelectable: !!selectAll || !!empty,
        hasSortable: !!sortArrow || !!empty,
        theadBlur: thead ? getComputedStyle(thead).backdropFilter || getComputedStyle(thead).webkitBackdropFilter : null,
      };
    });

    await page.screenshot({ path: path.join(OUT, `students-${vp.name}.png`), fullPage: true });

    const vpOk =
      measured.hasHead &&
      measured.titleText === 'Students' &&
      measured.hasFilter &&
      (measured.hasLedger || measured.hasEmpty) &&
      measured.hasWhatsAppCol &&
      measured.hasSelectable &&
      measured.hasSortable &&
      (!measured.hasTable || (measured.theadBlur && measured.theadBlur.includes('blur')));

    if (!vpOk) {
      report.ok = false;
      fail(`${vp.name}: ${JSON.stringify(measured)}`);
    } else {
      console.log(`PASS ${vp.name}:`, measured.titleText, measured.hasEmpty ? 'empty' : 'ledger', measured.theadBlur);
    }

    report.viewports[vp.name] = measured;
    await context.close();
  }

  // Pulse canary on same ledger (or home KPI if empty school)
  const pulseCtx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const pulsePage = await pulseCtx.newPage();
  await login(pulsePage);
  await pulsePage.goto(`${BASE}/admin/students`, { waitUntil: 'load', timeout: 90000 });
  await pulsePage.waitForSelector('table.ds-table-ledger thead, [data-testid="students-empty"]', { timeout: 20000 }).catch(() => null);
  report.pulse = await pulsePage.evaluate(() => {
    const thead = document.querySelector('table.ds-table-ledger thead');
    if (!thead) {
      return { mode: 'empty', blur: null };
    }
    const blur = getComputedStyle(thead).backdropFilter || getComputedStyle(thead).webkitBackdropFilter;
    return { mode: 'ledger', blur };
  });
  if (report.pulse.mode === 'ledger' && !(report.pulse.blur || '').includes('blur')) {
    report.ok = false;
    fail(`Pulse ledger blur missing: ${JSON.stringify(report.pulse)}`);
  } else {
    console.log('Pulse canary:', report.pulse);
  }
  await pulseCtx.close();

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  await browser.close();
  if (!report.ok) process.exit(1);
  console.log('OK students roster kit parity @', BASE);
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
