/**
 * Piece 4 / PR4 — Exams/marks kit parity + Pulse canaries.
 * Viewports: 375, 414, 768, 1280. Staging-only (no prod).
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=phase4.admin@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/dashboard-exams-marks-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/dashboard-exams-marks');
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
    await page.goto(`${BASE}/admin/marks/filter`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(800);

    const measured = await page.evaluate(() => {
      const head = document.querySelector('[data-testid="exams-page-head"]');
      const title = document.querySelector('.ds-page-head-title');
      const filter = document.querySelector('[data-testid="exams-filter-form"]');
      const empty = document.querySelector('[data-testid="exams-empty"]');
      const grid = document.querySelector('[data-testid="ds-grid-marks"], table.ds-grid-marks');
      const reminder = document.querySelector('[data-testid="exams-reminder-banner"], .ds-reminder-banner');
      const gmCode = document.querySelector('.gm-subject-code');
      return {
        url: location.href,
        hasHead: !!head,
        titleText: title?.textContent?.trim() || null,
        hasFilter: !!filter,
        hasEmpty: !!empty,
        hasGrid: !!grid,
        hasReminder: !!reminder,
        subjectCode: gmCode?.textContent?.trim() || null,
      };
    });

    await page.screenshot({ path: path.join(OUT, `exams-${vp.name}.png`), fullPage: true });

    const vpOk =
      measured.hasHead &&
      measured.hasFilter &&
      (measured.hasEmpty || measured.hasGrid) &&
      (!measured.hasGrid || measured.subjectCode === '/100' || measured.hasEmpty);

    if (!vpOk) {
      report.ok = false;
      fail(`${vp.name}: ${JSON.stringify(measured)}`);
    } else {
      console.log(`PASS ${vp.name}:`, measured.titleText, measured.hasEmpty ? 'empty' : 'grid');
    }

    report.viewports[vp.name] = measured;
    await context.close();
  }

  const pulseCtx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const pulsePage = await pulseCtx.newPage();
  await login(pulsePage);
  await pulsePage.goto(`${BASE}/admin/students`, { waitUntil: 'load', timeout: 90000 });
  report.pulse = await pulsePage.evaluate(() => {
    const thead = document.querySelector('table.ds-table-ledger thead');
    return {
      blur: thead ? (getComputedStyle(thead).backdropFilter || getComputedStyle(thead).webkitBackdropFilter) : null,
    };
  });
  if (!(report.pulse.blur || '').includes('blur')) {
    report.ok = false;
    fail(`Pulse ledger blur missing: ${JSON.stringify(report.pulse)}`);
  } else {
    console.log('Pulse canary:', report.pulse);
  }
  await pulseCtx.close();

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  await browser.close();
  if (!report.ok) process.exit(1);
  console.log('OK exams marks kit parity @', BASE);
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
