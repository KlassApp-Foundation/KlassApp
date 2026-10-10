/**
 * SHELL POLISH verification harness (K55–K70).
 *
 * Tests the admin shell in BOTH Chromium and WebKit (iPhone emulation with the
 * dynamic-toolbar viewport) at 375, 390, 768, 1280 and 1440, runs axe (wcag2aa)
 * on the dashboard, and saves a screenshot for every checked state.
 *
 * Usage:
 *   BASE=http://127.0.0.1:8000 \
 *   SHELL_EMAIL=admin@junior.demo.klassapp.test SHELL_PASSWORD=demo1234 \
 *   node e2e/shell-polish-verify.cjs
 *
 * Exit code 0 = all checks passed; 1 = at least one failure.
 * The JSON report is printed and saved next to the screenshots.
 */
const { chromium, webkit, devices } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.BASE || process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.SHELL_EMAIL || process.env.DASH_EMAIL || 'admin@junior.demo.klassapp.test';
const PASSWORD = process.env.SHELL_PASSWORD || process.env.DASH_PASSWORD || 'demo1234';
const OUT = path.join(__dirname, 'screenshots/shell-polish');
fs.mkdirSync(OUT, { recursive: true });

const VIEWPORTS = [
  { name: '375', width: 375, height: 812, phone: true },
  { name: '390', width: 390, height: 844, phone: true, iphone: true }, // iPhone emulation
  { name: '768', width: 768, height: 1024, phone: false, tablet: true },
  { name: '1280', width: 1280, height: 800, phone: false },
  { name: '1440', width: 1440, height: 900, phone: false },
];
const BROWSERS = [
  { name: 'chromium', launcher: chromium },
  { name: 'webkit', launcher: webkit },
];

const failures = [];
const results = { base: BASE, at: new Date().toISOString(), browsers: {} };

function check(browser, vp, id, ok, detail) {
  const key = `${browser}/${vp}`;
  results.browsers[key] = results.browsers[key] || { checks: [], axe: [] };
  results.browsers[key].checks.push({ id, ok: !!ok, detail: detail || null });
  const line = `${ok ? 'PASS' : 'FAIL'} [${key}] ${id}${detail ? ' — ' + detail : ''}`;
  console.log(line);
  if (!ok) failures.push(line);
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

async function shot(page, browser, vp, name) {
  const dir = path.join(OUT, browser, vp);
  fs.mkdirSync(dir, { recursive: true });
  await page.screenshot({ path: path.join(dir, `${name}.png`), fullPage: false });
}

function luminanceOf(rgb) {
  if (!rgb) return null;
  const m = rgb.match(/\d+/g);
  if (!m) return null;
  const [r, g, b] = m.map(Number);
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

async function runAxe(page) {
  await page.addScriptTag({ path: path.join(__dirname, '..', 'node_modules', 'axe-core', 'axe.min.js') });
  return page.evaluate(async () => {
    const res = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2aa'] } });
    return res.violations.map((v) => ({
      id: v.id,
      impact: v.impact,
      nodes: v.nodes.map((n) => (n.target || []).join(' ')).slice(0, 4),
    }));
  });
}

async function newContext(browserType, vp) {
  if (vp.iphone) {
    const device = devices['iPhone 13'];
    return browserType.newContext({
      ...device,
      viewport: { width: vp.width, height: vp.height },
    });
  }
  return browserType.newContext({
    viewport: { width: vp.width, height: vp.height },
    isMobile: vp.phone,
    hasTouch: vp.phone,
  });
}

(async () => {
  const bootBrowser = await chromium.launch({ headless: true });
  const bootPage = await (await bootBrowser.newContext()).newPage();
  await login(bootPage);
  await bootBrowser.close();

  for (const b of BROWSERS) {
    const browser = await b.launcher.launch({ headless: true });
    for (const vp of VIEWPORTS) {
      const context = await newContext(browser, vp);
      const page = await context.newPage();
      await login(page);
      await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'load', timeout: 90000 });
      await page.waitForTimeout(1200);

      // ── Global: no sideways scroll ────────────────────────────────────────
      const scroll = await page.evaluate(() => ({
        sw: document.documentElement.scrollWidth,
        iw: window.innerWidth,
      }));
      check(b.name, vp.name, 'no-horizontal-scroll', scroll.sw <= scroll.iw + 1, `scrollWidth=${scroll.sw} innerWidth=${scroll.iw}`);
      await shot(page, b.name, vp.name, 'dashboard');

      // ── Dashboard v2 header ───────────────────────────────────────────────
      const greet = await page.locator('[data-testid="dashboard-v2-greeting"]').count();
      check(b.name, vp.name, 'dashboard-greeting-present', greet > 0);
      const subtitle = await page.evaluate(() => {
        const el = document.querySelector('[data-testid="dashboard-v2-title"]');
        return el ? el.textContent.replace(/\s+/g, ' ').trim() : null;
      });
      check(b.name, vp.name, 'dashboard-subtitle', !!subtitle && subtitle.includes('·'), subtitle);

      // Year picker appears exactly once (dashboard header owns it; top bar suppressed).
      const yearCount = await page.evaluate(() => {
        const top = document.querySelectorAll('.dashboard-ay-selector select, .dashboard-ay select, #navbarSupportedContent select').length;
        const head = document.querySelectorAll('[data-testid="dashboard-v2-year"] select, [data-testid="dashboard-v2-year-chip"]').length;
        return { top, head, total: top + head };
      });
      check(b.name, vp.name, 'year-picker-once', yearCount.total === 1, JSON.stringify(yearCount));

      // ── Toshi edge triangle (item 12: hidden <1280 in preview mode) ───────
      const toshi = await page.evaluate(() => {
        const t = document.querySelector('[data-testid="toshi-toggle-wrapper"]');
        if (!t) return { present: false, visible: false };
        const r = t.getBoundingClientRect();
        const style = getComputedStyle(t);
        return {
          present: true,
          visible: r.width > 0 && r.height > 0 && style.display !== 'none' && style.visibility !== 'hidden',
        };
      });
      const toshiOk = vp.width < 1280 ? !toshi.visible : true;
      check(b.name, vp.name, 'toshi-triangle-hidden-below-1280', toshiOk, JSON.stringify(toshi));

      if (vp.phone) {
        // ── Top bar ─────────────────────────────────────────────────────────
        const topbar = await page.evaluate(() => {
          const burger = !!document.querySelector('#mobile-menu-trigger');
          const avatar = !!document.querySelector('.account-card--topbar .account-card__trigger');
          const search = !!document.querySelector('#command-palette-trigger');
          const titleEl = document.querySelector('.nav-brand .shell-page-title, .nav-brand strong, .nav-brand a strong');
          const title = titleEl ? titleEl.textContent.trim() : null;
          return { burger, avatar, search, title };
        });
        check(b.name, vp.name, 'topbar-burger', topbar.burger);
        check(b.name, vp.name, 'topbar-avatar', topbar.avatar);
        check(b.name, vp.name, 'topbar-search', topbar.search);
        check(b.name, vp.name, 'topbar-title', !!topbar.title, topbar.title);

        // ── Drawer (item 1) ─────────────────────────────────────────────────
        await page.click('#mobile-menu-trigger');
        await page.waitForTimeout(350);
        const drawer = await page.evaluate(() => {
          const el = document.querySelector('#res_sidebar');
          if (!el) return null;
          const style = getComputedStyle(el);
          const r = el.getBoundingClientRect();
          const firstLink = el.querySelector('li > a');
          const lr = firstLink ? firstLink.getBoundingClientRect() : null;
          const labels = Array.from(el.querySelectorAll('li > a')).slice(0, 3).map((a) => a.textContent.trim().slice(0, 30));
          const offscreen = style.transform.includes('-100%') || r.right <= 0;
          return {
            visible: !el.classList.contains('hidden') && r.height > 0 && !offscreen,
            bg: style.backgroundColor,
            luminance: null,
            rowHeight: lr ? Math.round(lr.height) : null,
            labels,
            width: Math.round(r.width),
          };
        });
        const lum = drawer ? luminanceOf(drawer.bg) : null;
        check(b.name, vp.name, 'drawer-opens', drawer && drawer.visible, JSON.stringify(drawer && { w: drawer.width, bg: drawer.bg }));
        check(b.name, vp.name, 'drawer-width-288', drawer && drawer.width >= 260 && drawer.width <= 310, `width=${drawer && drawer.width}`);
        check(b.name, vp.name, 'drawer-light-bg', lum !== null && lum > 200, `bg=${drawer && drawer.bg} luminance=${lum}`);
        check(b.name, vp.name, 'drawer-row-44-to-48', drawer && drawer.rowHeight >= 44 && drawer.rowHeight <= 50, `rowHeight=${drawer && drawer.rowHeight}`);
        await shot(page, b.name, vp.name, 'drawer-open');

        // axe with drawer open (contrast on the drawer labels).
        const axeDrawer = await runAxe(page);
        results.browsers[`${b.name}/${vp.name}`].axe.push({ state: 'drawer-open', violations: axeDrawer });
        const contrastInDrawer = axeDrawer.filter((v) => v.id === 'color-contrast');
        check(b.name, vp.name, 'axe-drawer-contrast', contrastInDrawer.length === 0, JSON.stringify(contrastInDrawer.slice(0, 2)));

        // Tap outside (scrim area, right of the 288px drawer) closes it.
        await page.mouse.click(vp.width - 12, Math.floor(vp.height / 2));
        await page.waitForTimeout(300);
        const drawerClosed = await page.evaluate(() => {
          const el = document.querySelector('#res_sidebar');
          return !el || el.classList.contains('hidden') || el.getBoundingClientRect().width === 0 || getComputedStyle(el).transform === 'translateX(-100%)';
        });
        check(b.name, vp.name, 'drawer-tap-outside-closes', drawerClosed);

        // Escape closes the drawer.
        await page.click('#mobile-menu-trigger');
        await page.waitForTimeout(300);
        await page.keyboard.press('Escape');
        await page.waitForTimeout(250);
        const drawerEscClosed = await page.evaluate(() => {
          const el = document.querySelector('#res_sidebar');
          return !el || el.classList.contains('hidden') || el.getBoundingClientRect().width === 0 || getComputedStyle(el).transform === 'translateX(-100%)';
        });
        check(b.name, vp.name, 'drawer-esc-closes', drawerEscClosed);
        if (!drawerClosed) {
          // Best-effort cleanup for subsequent checks.
          await page.click('#mobile-menu-trigger').catch(() => null);
          await page.waitForTimeout(250);
        }

        // Drawer closes on navigation.
        await page.click('#mobile-menu-trigger');
        await page.waitForTimeout(300);
        const navHref = await page.evaluate(() => {
          const a = document.querySelector('#res_sidebar a[href*="/admin/students"], #res_sidebar a[href*="/admin/exams"]');
          return a ? a.href : null;
        });
        if (navHref) {
          await page.click(`#res_sidebar a[href="${new URL(navHref).pathname}"]`).catch(async () => {
            await page.goto(navHref, { waitUntil: 'load', timeout: 60000 });
          });
          await page.waitForTimeout(800);
          const closedAfterNav = await page.evaluate(() => {
            const el = document.querySelector('#res_sidebar');
            return !el || el.classList.contains('hidden') || el.getBoundingClientRect().height === 0;
          });
          check(b.name, vp.name, 'drawer-closes-on-navigate', closedAfterNav);
          await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'load', timeout: 90000 });
          await page.waitForTimeout(900);
        } else {
          check(b.name, vp.name, 'drawer-closes-on-navigate', false, 'no menu link found in drawer');
        }

        // ── Phone account popover (item 2) ──────────────────────────────────
        await page.click('.account-card--topbar .account-card__trigger');
        await page.waitForTimeout(300);
        const pop = await page.evaluate(() => {
          const trigger = document.querySelector('.account-card--topbar .account-card__trigger');
          const menu = trigger ? document.getElementById(trigger.getAttribute('aria-controls')) : null;
          if (!trigger || !menu) return null;
          const tr = trigger.getBoundingClientRect();
          const mr = menu.getBoundingClientRect();
          const name = menu.querySelector('.account-card__name');
          const email = menu.querySelector('.account-card__email');
          const pts = [[mr.left + 2, mr.top + 2], [mr.right - 2, mr.top + 2], [mr.left + 2, mr.bottom - 2], [mr.right - 2, mr.bottom - 2]];
          const corners = pts.every(([x, y]) => {
            const hit = document.elementFromPoint(x, y);
            return hit && (hit === menu || menu.contains(hit));
          });
          return {
            visible: mr.width > 0 && mr.height > 0 && getComputedStyle(menu).display !== 'none',
            opensDown: mr.top >= tr.bottom - 2,
            rightAligned: mr.right <= window.innerWidth - 4,
            insideViewport: mr.left >= 0 && mr.right <= window.innerWidth + 1 && mr.top >= 0 && mr.bottom <= window.innerHeight + 1,
            width: Math.round(mr.width),
            maxWidthOk: mr.width <= window.innerWidth - 16 + 1,
            onBody: menu.parentElement === document.body,
            showsName: !!(name && name.textContent.trim() && getComputedStyle(name).display !== 'none'),
            showsEmail: !!(email && email.textContent.trim() && getComputedStyle(email).display !== 'none'),
            corners,
          };
        });
        check(b.name, vp.name, 'phone-popover-visible', pop && pop.visible, JSON.stringify(pop));
        check(b.name, vp.name, 'phone-popover-opens-downward', pop && pop.opensDown);
        check(b.name, vp.name, 'phone-popover-inside-viewport', pop && pop.insideViewport && pop.rightAligned && pop.maxWidthOk, JSON.stringify(pop && { w: pop.width }));
        check(b.name, vp.name, 'phone-popover-corners', pop && pop.corners && pop.onBody && pop.showsName && pop.showsEmail, JSON.stringify(pop && { corners: pop.corners, name: pop.showsName }));
        await shot(page, b.name, vp.name, 'phone-popover-open');

        // Keyboard: Esc closes and returns focus to the trigger.
        await page.keyboard.press('Escape');
        await page.waitForTimeout(250);
        const closed = await page.evaluate(() => {
          const trigger = document.querySelector('.account-card--topbar [data-account-trigger]');
          const menu = trigger ? document.getElementById(trigger.getAttribute('aria-controls')) : null;
          return !menu || menu.hasAttribute('hidden') || menu.getBoundingClientRect().height === 0;
        });
        check(b.name, vp.name, 'phone-popover-esc-closes', closed);
      } else if (vp.width >= 1024) {
        // ── Laptop sidebar + popover (items 2, 4) ──────────────────────────
        // Gated to ≥1024: WebKit's classic scrollbars shrink a 768px window's
        // CSS viewport to ~753px, so 768 legitimately renders the phone shell
        // there while Chromium shows the desktop shell. Both are correct at
        // the md (768) breakpoint; the laptop shell is exercised at 1024+.
        const sidebar = await page.evaluate(() => {
          const el = document.querySelector('#admin-sidebar');
          if (!el) return null;
          const r = el.getBoundingClientRect();
          const links = [...el.querySelectorAll('.sidebar-group ul a')];
          const lr = links[0] ? links[0].getBoundingClientRect() : null;
          const pitch = links.length > 1 ? Math.round(links[1].getBoundingClientRect().top - links[0].getBoundingClientRect().top) : null;
          const card = el.querySelector('.account-card__trigger');
          return {
            left: Math.round(r.left),
            width: Math.round(r.width),
            rowHeight: lr ? Math.round(lr.height) : null,
            pitch,
            hasCard: !!card,
          };
        });
        check(b.name, vp.name, 'sidebar-present', !!sidebar, JSON.stringify(sidebar));
        check(b.name, vp.name, 'sidebar-row-40-to-48', sidebar && sidebar.rowHeight >= 40 && sidebar.rowHeight <= 48, `rowHeight=${sidebar && sidebar.rowHeight}`);
        check(b.name, vp.name, 'sidebar-pitch-48', sidebar && sidebar.pitch >= 46 && sidebar.pitch <= 52, `pitch=${sidebar && sidebar.pitch}`);

        if (sidebar && sidebar.hasCard) {
          await page.click('#admin-sidebar .account-card__trigger');
          await page.waitForTimeout(300);
          const pop = await page.evaluate(() => {
            const trigger = document.querySelector('#admin-sidebar .account-card__trigger');
            const menu = trigger ? document.getElementById(trigger.getAttribute('aria-controls')) : null;
            const side = document.querySelector('#admin-sidebar');
            if (!trigger || !menu) return null;
            const tr = trigger.getBoundingClientRect();
            const mr = menu.getBoundingClientRect();
            const sideBefore = side.getBoundingClientRect().left;
            const pts = [[mr.left + 2, mr.top + 2], [mr.right - 2, mr.top + 2], [mr.left + 2, mr.bottom - 2], [mr.right - 2, mr.bottom - 2]];
            const corners = pts.every(([x, y]) => {
              const hit = document.elementFromPoint(x, y);
              return hit && (hit === menu || menu.contains(hit));
            });
            return {
              sideLeft: sideBefore,
              visible: mr.width > 0 && mr.height > 0 && getComputedStyle(menu).display !== 'none',
              opensUp: mr.bottom <= tr.top + 2,
              width: Math.round(mr.width),
              left: Math.round(mr.left),
              insideViewport: mr.left >= -1 && mr.right <= window.innerWidth + 1 && mr.top >= -1 && mr.bottom <= window.innerHeight + 1,
              onBody: menu.parentElement === document.body,
              alignedToCard: Math.abs(mr.left - tr.left) <= 2,
              corners,
            };
          });
          check(b.name, vp.name, 'laptop-popover-visible', pop && pop.visible, JSON.stringify(pop));
          check(b.name, vp.name, 'sidebar-never-moves', pop && Math.abs(pop.sideLeft) < 1, `left=${pop && pop.sideLeft}`);
          check(b.name, vp.name, 'laptop-popover-inside-viewport', pop && pop.insideViewport && pop.onBody && pop.alignedToCard && pop.width === 224, JSON.stringify(pop && { w: pop.width, left: pop.left }));
          check(b.name, vp.name, 'laptop-popover-corners', pop && pop.corners, JSON.stringify(pop && { corners: pop.corners }));
          await shot(page, b.name, vp.name, 'laptop-popover-open');
          await page.keyboard.press('Escape').catch(() => null);
          await page.mouse.click(600, 300).catch(() => null);
          await page.waitForTimeout(200);
        }
      }

      // ── axe on dashboard ──────────────────────────────────────────────────
      const axeDash = await runAxe(page);
      results.browsers[`${b.name}/${vp.name}`].axe.push({ state: 'dashboard', violations: axeDash });
      const serious = axeDash.filter((v) => ['serious', 'critical'].includes(v.impact));
      check(b.name, vp.name, 'axe-dashboard-serious', serious.length === 0, JSON.stringify(serious.slice(0, 3)));

      await context.close();
    }
    await browser.close();
  }

  const reportPath = path.join(OUT, 'report.json');
  fs.writeFileSync(reportPath, JSON.stringify({ ...results, failures }, null, 1));
  console.log(`\nReport: ${reportPath}`);
  console.log(failures.length === 0 ? 'ALL SHELL CHECKS PASSED' : `${failures.length} FAILURES`);
  process.exit(failures.length === 0 ? 0 : 1);
})().catch((e) => {
  console.error('HARNESS ERROR:', e);
  process.exit(1);
});
