/**
 * Staging KPI href click-through + Toshi desktop dock / mobile blur canaries.
 *
 * Verifies:
 * 1. Every linked .ds-kpi-card on role dashboards has a real http(s) href (no %22).
 * 2. Clicking each linked KPI yields HTTP 200 (not 404 from escaped quotes).
 * 3. At 1280px, [data-toshi-root] is in the viewport (docked), not below fold.
 * 4. When maximize overlay is hidden, backdrop-filter is none.
 *
 * Usage:
 *   PREVIEW_BASE=https://… DASH_EMAIL=… DASH_PASSWORD=…
 *   node e2e/dashboard-kpi-toshi-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/dashboard-kpi-toshi');
fs.mkdirSync(OUT, { recursive: true });

const DASHBOARDS = [
  { role: 'admin', path: '/admin/dashboard' },
  { role: 'teacher', path: '/teacher/dashboard' },
  { role: 'accountant', path: '/accountant/dashboard' },
  { role: 'reception', path: '/reception/dashboard' },
  { role: 'library', path: '/library/dashboard' },
];

function fail(msg, report) {
  console.error('FAIL:', msg);
  report.ok = false;
  report.failures = report.failures || [];
  report.failures.push(msg);
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
  const report = {
    base: BASE,
    email: EMAIL,
    at: new Date().toISOString(),
    dashboards: {},
    toshi: {},
    ok: true,
  };

  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();
  await login(page);

  for (const dash of DASHBOARDS) {
    const entry = { path: dash.path, kpis: [], skipped: false };
    const resp = await page.goto(`${BASE}${dash.path}`, { waitUntil: 'load', timeout: 90000 });
    if (!resp || resp.status() === 403 || resp.status() === 404 || page.url().includes('/login')) {
      entry.skipped = true;
      entry.reason = `status=${resp?.status()} url=${page.url()}`;
      report.dashboards[dash.role] = entry;
      console.log(`SKIP ${dash.role}: ${entry.reason}`);
      continue;
    }

    await page.waitForTimeout(600);
    await page.screenshot({ path: path.join(OUT, `${dash.role}-dashboard.png`), fullPage: true });

    const cards = await page.$$eval('a.ds-kpi-card', (nodes) =>
      nodes.map((a) => ({
        href: a.getAttribute('href') || '',
        label: (a.querySelector('.ds-kpi-label')?.textContent || '').trim(),
      }))
    );

    if (cards.length === 0) {
      // Some roles may only have unlinked divs — still assert no escaped anchors exist.
      const bad = await page.$$eval('a.ds-kpi-card, .ds-kpi-card', (nodes) =>
        nodes
          .map((n) => n.outerHTML.slice(0, 200))
          .filter((h) => h.includes('%22') || h.includes('&quot;https'))
      );
      if (bad.length) {
        fail(`${dash.role}: escaped KPI markup ${bad[0]}`, report);
      }
      entry.note = 'no linked KPI cards';
      report.dashboards[dash.role] = entry;
      continue;
    }

    for (const card of cards) {
      const row = { ...card, status: null };
      if (!/^https?:\/\//i.test(card.href) && !card.href.startsWith('/')) {
        fail(`${dash.role} KPI "${card.label}" bad href=${card.href}`, report);
        entry.kpis.push(row);
        continue;
      }
      if (card.href.includes('%22') || card.href.includes('"https')) {
        fail(`${dash.role} KPI "${card.label}" escaped href=${card.href}`, report);
        entry.kpis.push(row);
        continue;
      }

      const target = card.href.startsWith('http') ? card.href : `${BASE}${card.href}`;
      const clickResp = await page.goto(target, { waitUntil: 'load', timeout: 90000 });
      row.status = clickResp ? clickResp.status() : null;
      row.finalUrl = page.url();
      if (!row.status || row.status >= 400) {
        fail(`${dash.role} KPI "${card.label}" → ${row.status} ${row.finalUrl}`, report);
      }
      if ((row.finalUrl || '').includes('%22')) {
        fail(`${dash.role} KPI "${card.label}" landed on %22 URL ${row.finalUrl}`, report);
      }
      entry.kpis.push(row);

      await page.goto(`${BASE}${dash.path}`, { waitUntil: 'load', timeout: 90000 });
      await page.waitForTimeout(300);
    }

    report.dashboards[dash.role] = entry;
    console.log(
      `OK ${dash.role}: ${entry.kpis.length} linked KPI(s)`,
      entry.kpis.map((k) => `${k.label}=${k.status}`).join(', ')
    );
  }

  // Toshi dock at desktop
  await page.setViewportSize({ width: 1280, height: 800 });
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForTimeout(800);

  const toshiDesktop = await page.evaluate(() => {
    const root = document.querySelector('[data-toshi-root]');
    if (!root) return { present: false };
    const r = root.getBoundingClientRect();
    const cs = getComputedStyle(root);
    return {
      present: true,
      top: r.top,
      left: r.left,
      width: r.width,
      height: r.height,
      position: cs.position,
      inViewport: r.top >= 0 && r.top < window.innerHeight && r.height > 40,
    };
  });
  report.toshi.desktop = toshiDesktop;
  await page.screenshot({ path: path.join(OUT, 'toshi-desktop-1280.png') });

  if (!toshiDesktop.present) {
    fail('Toshi root missing on admin dashboard', report);
  } else if (!toshiDesktop.inViewport) {
    fail(`Toshi dock below fold: top=${toshiDesktop.top}`, report);
  } else {
    console.log('OK toshi desktop dock', toshiDesktop);
  }

  // Mobile blur: overlay hidden → backdrop-filter none
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForTimeout(600);

  const toshiMobile = await page.evaluate(() => {
    const overlay = document.querySelector('[data-toshi-root] .toshi-modal-overlay');
    if (!overlay) return { overlay: false };
    const cs = getComputedStyle(overlay);
    return {
      overlay: true,
      display: cs.display,
      backdropFilter: cs.backdropFilter || cs.webkitBackdropFilter || '',
      hasOpenClass: overlay.classList.contains('toshi-modal-overlay--open'),
    };
  });
  report.toshi.mobile = toshiMobile;
  await page.screenshot({ path: path.join(OUT, 'toshi-mobile-390.png') });

  if (toshiMobile.overlay && toshiMobile.display === 'none') {
    const bf = (toshiMobile.backdropFilter || '').toLowerCase();
    if (bf.includes('blur') && !bf.includes('none')) {
      fail(`Hidden overlay still blurs: ${toshiMobile.backdropFilter}`, report);
    } else {
      console.log('OK mobile overlay blur scoped', toshiMobile);
    }
    if (toshiMobile.hasOpenClass) {
      fail('Hidden overlay has --open class', report);
    }
  }

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(report.ok ? 'PASS dashboard-kpi-toshi-verify' : 'FAIL dashboard-kpi-toshi-verify');
  process.exit(report.ok ? 0 : 1);
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
