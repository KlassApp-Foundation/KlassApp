/**
 * REAL VERIFICATION — Toshi header full-width (Option B) shipped fix.
 * Local pass. Checks at 375 / 414 / 768 / 1280 / 1440 / 1920:
 *   Desktop (≥1280): navbar full viewport width; #app full width; root fixed
 *   column top=69 (header offset), bottom=viewport, right=0, width 380;
 *   toshi header INSIDE the panel at expected geometry; no horizontal overflow;
 *   maximized modal covers navbar; collapsed state hides column + shows pill.
 *   Sub-1280: everything untouched (drawer model) — root NOT the fixed column.
 * Also grabs the previously-fixed y=69/69px regressions explicitly.
 */
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = (process.env.PREVIEW_BASE || 'http://localhost:8080').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots', 'toshi-fullwidth-verify');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 800 },
  { name: '1440', width: 1440, height: 900 },
  { name: '1920', width: 1920, height: 1080 },
];

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"]', 'diag.toshi@demo.klassapp.test');
  await page.fill('input[name="password"]', 'diag-pass-2026');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = { at: new Date().toISOString(), base: BASE, viewports: {}, failures: [] };

  for (const vp of viewports) {
    const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', e => errors.push(String(e)));
    page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });

    await login(page);
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
    await page.evaluate(() => document.body.classList.remove('toshi-collapsed'));
    await page.waitForTimeout(700);

    const m = await page.evaluate(() => {
      const root = document.querySelector('[data-toshi-root]');
      const panel = document.querySelector('[data-toshi-root] .toshi-panel');
      const thead = document.querySelector('[data-testid="toshi-header"]');
      const navbar = document.querySelector('.navbar.dashboard-themed-header');
      const app = document.getElementById('app');
      const sidebar = document.querySelector('.sidebar');
      const r = el => el ? JSON.parse(JSON.stringify(el.getBoundingClientRect())) : null;
      const cs = getComputedStyle(root);
      return {
        rootPos: cs.position, rootMarginTop: cs.marginTop, rootHeight: cs.height,
        root: r(root), panel: r(panel), toshiHeader: r(thead), navbar: r(navbar),
        navbarHeight: navbar ? navbar.getBoundingClientRect().height : null,
        app: r(app), sidebar: r(sidebar),
        viewport: { w: innerWidth, h: innerHeight },
        hOverflow: document.documentElement.scrollWidth > innerWidth,
        pageScroll: { scrollW: document.documentElement.scrollWidth },
      };
    });

    const v = { ...m, errors };
    const w = m.viewport.w;

    if (w >= 1280) {
      const checks = {
        rootIsFixed: m.rootPos === 'fixed',
        rootTopIs69: Math.abs(m.root.top - 69) < 1,
        rootMarginZero: m.rootMarginTop === '0px',
        rootBottomAtViewport: Math.abs(m.root.bottom - m.viewport.h) < 1,
        rootRightAtViewport: Math.abs(m.root.right - w) < 1,
        rootWidth380: Math.abs(m.root.width - 380) < 1,
        panelFillsRoot: Math.abs(m.panel.top - m.root.top) < 1 && Math.abs(m.panel.height - m.root.height) < 1,
        navbarFullWidth: Math.abs(m.navbar.width - w) < 1,
        appFullWidth: Math.abs(m.app.width - w) < 1,
        toshiHeaderInsidePanel: Math.abs(m.toshiHeader.top - (m.panel.top + 1)) < 3 && m.toshiHeader.right > w - 380,
        navbarHeight69: Math.abs(m.navbar.height - 69) < 1.5,
        noHOverflow: !m.hOverflow,
      };
      v.checks = checks;
      const failed = Object.entries(checks).filter(([, ok]) => !ok).map(([k]) => k);
      if (failed.length) report.failures.push(`${vp.name}: ${failed.join(', ')}`);

      // modal-above-navbar check
      await page.evaluate(() => {
        const o = document.querySelector('.toshi-modal-overlay');
        o.style.display = 'flex';
        o.classList.add('toshi-modal-overlay--open');
      });
      await page.waitForTimeout(200);
      v.modalCoversNavbar = await page.evaluate(() => {
        const nb = document.querySelector('.navbar.dashboard-themed-header').getBoundingClientRect();
        const el = document.elementFromPoint(nb.x + 10, nb.y + 10);
        return el ? !!el.closest('.toshi-modal-overlay') : false;
      });
      if (!v.modalCoversNavbar) report.failures.push(`${vp.name}: modal does not cover navbar`);
      await page.screenshot({ path: path.join(OUT, `${vp.name}-modal.png`) });
      await page.evaluate(() => {
        const o = document.querySelector('.toshi-modal-overlay');
        o.style.display = '';
        o.classList.remove('toshi-modal-overlay--open');
      });

      // collapsed state
      await page.evaluate(() => document.body.classList.add('toshi-collapsed'));
      await page.waitForTimeout(400);
      v.collapsed = await page.evaluate(() => {
        const root = document.querySelector('[data-toshi-root]');
        const pill = document.querySelector('.toshi-pill');
        const r = el => el ? JSON.parse(JSON.stringify(el.getBoundingClientRect())) : null;
        return {
          rootWidth: getComputedStyle(root).width,
          rootInvisible: root.getBoundingClientRect().width < 2,
          pillVisible: getComputedStyle(pill).display !== 'none' && pill.getBoundingClientRect().width > 50,
          pillRect: r(pill),
        };
      });
      if (!v.collapsed.rootInvisible || !v.collapsed.pillVisible) report.failures.push(`${vp.name}: collapsed state broken`);
      await page.screenshot({ path: path.join(OUT, `${vp.name}-collapsed.png`) });
      await page.evaluate(() => document.body.classList.remove('toshi-collapsed'));
      await page.waitForTimeout(300);
    } else {
      // sub-1280: drawer model — root must NOT be the fixed 380 column; the panel is
      // the fixed drawer (min(420px,92vw)). Pill/panel behavior unchanged.
      const checks = {
        notDockColumn: !(m.rootPos === 'fixed' && Math.abs(m.root.width - 380) < 1 && Math.abs(m.root.top - 69) < 1),
        noHOverflow: !m.hOverflow,
      };
      v.checks = checks;
      const failed = Object.entries(checks).filter(([, ok]) => !ok).map(([k]) => k);
      if (failed.length) report.failures.push(`${vp.name} (sub-1280): ${failed.join(', ')}`);
    }

    await page.screenshot({ path: path.join(OUT, `${vp.name}.png`) });
    report.viewports[vp.name] = v;
    await context.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(OUT, 'verify-report.json'), JSON.stringify(report, null, 2));

  console.log('=== VERIFICATION SUMMARY ===');
  for (const [name, v] of Object.entries(report.viewports)) {
    console.log(`${name}: ${JSON.stringify(v.checks)}${v.modalCoversNavbar !== undefined ? ' modalCoversNavbar=' + v.modalCoversNavbar : ''}`);
  }
  console.log('FAILURES:', report.failures.length ? report.failures : 'NONE');
  process.exitCode = report.failures.length ? 1 : 0;
})().catch(e => { console.error('VERIFY FAILED:', e); process.exit(1); });
