/**
 * Real pointer paths for the folded rail flyout and the account menu.
 * Run on its own, or from e2e/shell-polish-verify.cjs:
 *   BASE=http://127.0.0.1:8005 SHELL_PASSWORD=… node e2e/shell-rail-pointer-verify.cjs
 *
 * Uses page.mouse.move({ steps }), not locator.hover().
 */
const { chromium, webkit } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.BASE || 'http://127.0.0.1:8005').replace(/\/$/, '');
const EMAIL = process.env.SHELL_EMAIL || 'admin@junior.demo.klassapp.test';
const PASSWORD = process.env.SHELL_PASSWORD || '';
const BRAVE = process.env.BRAVE_PATH || '/Applications/Brave Browser.app/Contents/MacOS/Brave Browser';
const OUT = process.env.RAIL_POINTER_OUT || path.join(__dirname, 'screenshots/shell-rail-pointer');

function browsers() {
  const list = [
    { name: 'chrome', launch: () => chromium.launch({ channel: 'chrome', headless: true }) },
    { name: 'webkit', launch: () => webkit.launch({ headless: true }) },
  ];
  if (fs.existsSync(BRAVE)) {
    list.splice(1, 0, { name: 'brave', launch: () => chromium.launch({ executablePath: BRAVE, headless: true }) });
  }
  return list;
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

async function dashboard(page, collapsed) {
  await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.evaluate((flag) => localStorage.setItem('ka:sidebar:v3', flag), collapsed ? '1' : '0');
  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.locator('#admin-sidebar a[aria-label="Students"]').waitFor({ timeout: 20000 });
}

async function center(locator) {
  const box = await locator.boundingBox();
  if (!box) return null;
  return { x: box.x + box.width / 2, y: box.y + box.height / 2, box };
}

async function flyoutOpen(page) {
  return page.evaluate(() => !!document.querySelector('#admin-sidebar .sidebar-v3-item.is-flyout .sidebar-v3-child'));
}

async function hitLabel(page, point) {
  return page.evaluate(({ x, y }) => {
    const el = document.elementFromPoint(x, y);
    const link = el && el.closest ? el.closest('a') : null;
    return link ? link.getAttribute('aria-label') : null;
  }, point);
}

async function menuOpen(page) {
  return page.evaluate(() => {
    const trigger = [...document.querySelectorAll('[data-account-trigger][aria-expanded="true"]')].find((el) => el.getBoundingClientRect().width > 0);
    if (!trigger) return false;
    const menu = document.getElementById(trigger.getAttribute('aria-controls'));
    if (!menu || menu.hidden) return false;
    const style = getComputedStyle(menu);
    const box = menu.getBoundingClientRect();
    return style.display !== 'none' && style.visibility !== 'hidden' && box.height > 20;
  });
}

async function visibleTrigger(page) {
  const topbar = page.locator('.account-card--topbar [data-account-trigger]');
  if (await topbar.count() && await topbar.first().isVisible()) return topbar.first();
  return page.locator('#admin-sidebar [data-account-trigger]').first();
}

async function openMenu(page) {
  const trigger = await visibleTrigger(page);
  const point = await center(trigger);
  await page.mouse.move(point.x, point.y, { steps: 4 });
  await page.mouse.click(point.x, point.y);
  await page.waitForTimeout(150);
  return { trigger, point };
}

async function pathToThirdChild(page, steps) {
  const icon = await center(page.locator('#admin-sidebar a[aria-label="Students"]'));
  await page.mouse.move(icon.x, icon.y);
  await page.waitForTimeout(120);
  const child = await center(page.locator('#admin-sidebar .sidebar-v3-item.is-flyout a[aria-label="Health records"]'));
  if (!child) return { icon, child: null, hit: null };
  await page.mouse.move(icon.x, icon.y);
  await page.mouse.move(child.x, child.y, { steps });
  const hit = await hitLabel(page, child);
  return { icon, child, hit };
}

async function run() {
  if (!PASSWORD) throw new Error('SHELL_PASSWORD is required');
  fs.mkdirSync(OUT, { recursive: true });
  const failures = [];
  const results = [];
  function check(browser, id, ok, detail) {
    results.push({ browser, id, ok: !!ok, detail: detail || null });
    const line = `${ok ? 'PASS' : 'FAIL'} [${browser}] ${id}${detail ? ' — ' + detail : ''}`;
    console.log(line);
    if (!ok) failures.push(line);
  }

  for (const spec of browsers()) {
    const browser = await spec.launch();
    const context = await browser.newContext({ viewport: { width: 1280, height: 680 } });
    const page = await context.newPage();
    await login(page);
    await dashboard(page, true);

    for (const steps of [30, 3]) {
      await dashboard(page, true);
      const pathResult = await pathToThirdChild(page, steps);
      check(spec.name, `flyout-path-${steps}-stays`, pathResult.hit === 'Health records', `hit=${pathResult.hit}`);
      if (pathResult.child) {
        await Promise.all([
          page.waitForURL(/\/admin\/health/, { timeout: 8000 }).catch(() => null),
          page.mouse.click(pathResult.child.x, pathResult.child.y),
        ]);
      }
      check(spec.name, `flyout-path-${steps}-click`, page.url().includes('/admin/health'), page.url());
      await page.screenshot({ path: path.join(OUT, `${spec.name}-path-${steps}.png`) });
    }

    await dashboard(page, true);
    const icon = await center(page.locator('#admin-sidebar a[aria-label="Students"]'));
    await page.mouse.move(icon.x, icon.y);
    await page.waitForTimeout(120);
    await page.mouse.move(900, 80, { steps: 2 });
    await page.waitForTimeout(80);
    check(spec.name, 'flyout-stays-briefly-after-leave', await flyoutOpen(page), 'closed before 200ms');
    await page.waitForTimeout(220);
    check(spec.name, 'flyout-closes-after-leave', !(await flyoutOpen(page)), 'still open');

    await page.mouse.move(icon.x, icon.y);
    await page.waitForTimeout(120);
    await page.mouse.move(icon.x + 90, icon.y + 70, { steps: 8 });
    await page.waitForTimeout(40);
    await page.mouse.move(icon.x + 90, icon.y - 120, { steps: 4 });
    await page.waitForTimeout(40);
    await page.mouse.move(icon.x + 90, icon.y + 70, { steps: 4 });
    await page.waitForTimeout(240);
    check(spec.name, 'flyout-reenter-cancels-close', await flyoutOpen(page), 'closed despite re-entry');

    await page.mouse.move(700, 200);
    await page.waitForTimeout(250);
    await page.setViewportSize({ width: 1280, height: 430 });
    await page.waitForTimeout(100);
    const settings = await page.evaluate(() => {
      const links = [...document.querySelectorAll('#admin-sidebar .sidebar-v3-item .sidebar-v3-link')].filter((link) => {
        const box = link.getBoundingClientRect();
        return box.width > 20 && box.top > 80 && box.bottom < window.innerHeight - 8 && link.closest('.sidebar-v3-item').querySelector('.sidebar-v3-children');
      });
      const link = links.sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top).pop();
      const box = link.getBoundingClientRect();
      return { x: box.x + box.width / 2, y: box.y + box.height / 2, label: link.getAttribute('aria-label') };
    });
    await page.mouse.move(settings.x, settings.y);
    await page.waitForTimeout(150);
    const flip = await page.evaluate(() => {
      const item = document.querySelector('#admin-sidebar .sidebar-v3-item.is-flyout');
      const panel = item && item.querySelector('.sidebar-v3-children');
      const trigger = item && item.querySelector('.sidebar-v3-link');
      if (!panel || !trigger) return null;
      const pr = panel.getBoundingClientRect();
      const tr = trigger.getBoundingClientRect();
      return { bottom: pr.bottom, inner: window.innerHeight, triggerTop: tr.top, panelTop: pr.top };
    });
    check(spec.name, 'flyout-flips-into-viewport', !!flip && flip.bottom <= flip.inner + 1 && flip.panelTop < flip.triggerTop, JSON.stringify(flip));

    await page.setViewportSize({ width: 1280, height: 680 });
    await page.mouse.move(700, 200);
    await page.waitForTimeout(250);
    await page.keyboard.press('Escape');
    await page.locator('#admin-sidebar a[aria-label="Students"]').focus();
    await page.keyboard.press('ArrowRight');
    await page.waitForTimeout(50);
    check(spec.name, 'flyout-keyboard-open', await flyoutOpen(page), 'ArrowRight');
    await page.keyboard.press('ArrowDown');
    const focused = await page.evaluate(() => document.activeElement && document.activeElement.getAttribute('aria-label'));
    check(spec.name, 'flyout-keyboard-child', focused === 'All students' || focused === 'Admissions', focused);
    await page.keyboard.press('Escape');
    const back = await page.evaluate(() => document.activeElement && document.activeElement.getAttribute('aria-label'));
    check(spec.name, 'flyout-esc-returns-focus', back === 'Students' && !(await flyoutOpen(page)), back);

    await (await visibleTrigger(page)).focus();
    const opened = await openMenu(page);
    check(spec.name, 'account-opens-on-rail', await menuOpen(page), 'rail');
    await page.mouse.click(640, 24);
    await page.waitForTimeout(80);
    check(spec.name, 'account-click-outside', !(await menuOpen(page)), 'rail');

    await openMenu(page);
    await page.keyboard.press('Escape');
    const escFocus = await page.evaluate(() => document.activeElement && document.activeElement.hasAttribute('data-account-trigger'));
    check(spec.name, 'account-esc', !(await menuOpen(page)) && escFocus, 'rail');

    await openMenu(page);
    await page.mouse.click(opened.point.x, opened.point.y);
    await page.waitForTimeout(80);
    check(spec.name, 'account-second-click', !(await menuOpen(page)), 'rail');

    await openMenu(page);
    await page.evaluate(() => {
      document.addEventListener('click', (event) => {
        if (event.target.closest && event.target.closest('[data-account-menu]')) event.preventDefault();
      }, true);
    });
    const item = page.locator('[data-account-menu]:not([hidden]) [role="menuitem"]').first();
    const itemPoint = await center(item);
    if (itemPoint) await page.mouse.click(itemPoint.x, itemPoint.y);
    await page.waitForTimeout(80);
    check(spec.name, 'account-choose-item', !(await menuOpen(page)), 'rail');

    await openMenu(page);
    await page.evaluate(() => window.dispatchEvent(new Event('blur')));
    await page.waitForTimeout(50);
    check(spec.name, 'account-window-blur', !(await menuOpen(page)), 'rail');

    await openMenu(page);
    await page.mouse.move(40, 240);
    await page.mouse.wheel(0, 120);
    await page.waitForTimeout(50);
    check(spec.name, 'account-scroll-nav', !(await menuOpen(page)), 'rail');

    await openMenu(page);
    await page.setViewportSize({ width: 1200, height: 640 });
    await page.waitForTimeout(50);
    check(spec.name, 'account-resize', !(await menuOpen(page)), 'rail');
    await page.setViewportSize({ width: 1280, height: 680 });

    await openMenu(page);
    const fold = await center(page.locator('#sidebar-collapse-toggle'));
    if (fold) await page.mouse.click(fold.x, fold.y);
    await page.waitForTimeout(80);
    check(spec.name, 'account-fold', !(await menuOpen(page)), 'rail');

    await dashboard(page, false);
    await openMenu(page);
    check(spec.name, 'account-opens-expanded', await menuOpen(page), 'expanded');
    await page.mouse.click(640, 24);
    await page.waitForTimeout(80);
    check(spec.name, 'account-expanded-outside', !(await menuOpen(page)), 'expanded');
    await openMenu(page);
    const fold2 = await center(page.locator('#sidebar-collapse-toggle'));
    if (fold2) await page.mouse.click(fold2.x, fold2.y);
    await page.waitForTimeout(80);
    check(spec.name, 'account-unfold-or-fold', !(await menuOpen(page)), 'expanded');

    await page.setViewportSize({ width: 375, height: 812 });
    await page.evaluate(() => localStorage.setItem('ka:sidebar:v3', '0'));
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.locator('.account-card--topbar [data-account-trigger]').waitFor({ state: 'visible' });
    await openMenu(page);
    check(spec.name, 'account-opens-phone', await menuOpen(page), 'phone');
    await page.mouse.click(40, 400);
    await page.waitForTimeout(80);
    check(spec.name, 'account-phone-outside', !(await menuOpen(page)), 'phone');
    await openMenu(page);
    const burger = await center(page.locator('#mobile-menu-trigger'));
    if (burger) await page.mouse.click(burger.x, burger.y);
    await page.waitForTimeout(120);
    check(spec.name, 'account-phone-drawer-toggle', !(await menuOpen(page)), 'phone');

    await page.screenshot({ path: path.join(OUT, `${spec.name}-done.png`) });
    await context.close();
    await browser.close();
  }

  const reportPath = path.join(OUT, 'report.json');
  fs.writeFileSync(reportPath, JSON.stringify({ failures, results }, null, 2));
  return { failures, results, reportPath };
}

module.exports = { run };

if (require.main === module) {
  run().then((out) => {
    console.log(out.failures.length === 0 ? 'ALL POINTER CHECKS PASSED' : `${out.failures.length} FAILURES`);
    console.log(out.reportPath);
    process.exit(out.failures.length === 0 ? 0 : 1);
  }).catch((error) => {
    console.error(error);
    process.exit(1);
  });
}
