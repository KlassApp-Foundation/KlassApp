// PR7 staging checks: account popover behavior + currency hint.
const { chromium } = require('playwright');
const fs = require('node:fs');
const OUT = '/Users/mac/projects/KlassApp-wt-pr7/e2e/screenshots/pr7';
fs.mkdirSync(OUT, { recursive: true });
const BASE = 'https://test.klassapp.xyz';
const PW = process.env.DEMO_PW;

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await page.fill('#email', 'admin@junior.demo.klassapp.test');
  await page.fill('#password', PW);
  await Promise.all([page.waitForLoadState('domcontentloaded').catch(() => {}), page.click('button[type="submit"]')]);
  await page.waitForTimeout(2500);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const r = {};

  // A) 1280: popover geometry + sidebar never moves + keyboard
  {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    r.sidebarWidth = await page.evaluate(() => Math.round(document.querySelector('#admin-sidebar').getBoundingClientRect().width));
    const before = await page.evaluate(() => document.querySelector('#admin-sidebar').getBoundingClientRect().left);
    // click the sidebar card trigger
    await page.locator('#admin-sidebar [data-account-trigger]').click({ timeout: 8000 }).catch((e) => { r.clickErr = String(e).slice(0, 80); });
    await page.waitForTimeout(600);
    r.open = await page.evaluate(() => {
      const menu = document.querySelector('#admin-sidebar .account-card__menu');
      const card = document.querySelector('#admin-sidebar .account-card__trigger');
      const mr = menu ? menu.getBoundingClientRect() : null;
      const cr = card ? card.getBoundingClientRect() : null;
      const focused = document.activeElement ? document.activeElement.innerText.trim().slice(0, 30) : null;
      return {
        visible: !!(menu && menu.getBoundingClientRect().width > 0),
        menu: mr ? { left: Math.round(mr.left), right: Math.round(mr.right), bottom: Math.round(mr.bottom), top: Math.round(mr.top), w: Math.round(mr.width) } : null,
        cardTop: cr ? Math.round(cr.top) : null,
        gap: mr && cr ? Math.round(cr.top - mr.bottom) : null,
        inViewport: mr ? (mr.left >= 0 && mr.right <= window.innerWidth && mr.top >= 0) : null,
        focus: focused,
      };
    });
    const after = await page.evaluate(() => document.querySelector('#admin-sidebar').getBoundingClientRect().left);
    r.sidebarLeftBefore = before;
    r.sidebarLeftAfter = after;
    await page.screenshot({ path: `${OUT}/popover-1280.png` });
    // Escape closes + focus returns
    await page.keyboard.press('Escape');
    await page.waitForTimeout(400);
    r.esc = await page.evaluate(() => ({
      hidden: document.querySelector('#admin-sidebar .account-card__menu').hasAttribute('hidden'),
      focusTag: document.activeElement ? document.activeElement.className.toString().slice(0, 40) : null,
    }));
    await ctx.close();
  }

  // B) 375: topbar avatar + right-aligned popover
  {
    const ctx = await browser.newContext({ viewport: { width: 375, height: 812 } });
    const page = await ctx.newPage();
    await login(page);
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    const av = page.locator('.account-card--topbar [data-account-trigger]');
    r.mobile = { avatarCount: await av.count().catch(() => 0), avatarVisible: await av.first().isVisible().catch(() => false) };
    if (r.mobile.avatarVisible) {
      await av.first().click({ timeout: 8000 });
      await page.waitForTimeout(600);
      r.mobile.menu = await page.evaluate(() => {
        const menu = document.querySelector('.account-card--topbar .account-card__menu');
        if (!menu) return null;
        const m = menu.getBoundingClientRect();
        return { left: Math.round(m.left), right: Math.round(m.right), w: Math.round(m.width), inViewport: m.left >= 0 && m.right <= window.innerWidth && m.left >= 0, vw: window.innerWidth };
      });
      await page.screenshot({ path: `${OUT}/popover-375.png` });
    }
    await ctx.close();
  }

  // C) v2 dashboard currency hint (no setting on demos yet)
  {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(3000);
    r.currency = await page.evaluate(() => {
      const el = document.querySelector('[data-testid="dashboard-v2-kpi-fees"]');
      const card = el ? el.closest('.dv2-kpi') : null;
      const hint = document.querySelector('[data-testid="dashboard-v2-currency-hint"]');
      return { feesText: card ? card.innerText.replace(/\n/g, ' | ') : null, hint: !!hint };
    });
    await page.screenshot({ path: `${OUT}/currency-hint-1280.png` });
    await ctx.close();
  }

  fs.writeFileSync(`${OUT}/results.json`, JSON.stringify(r, null, 2));
  console.log(JSON.stringify(r, null, 2));
  await browser.close();
})().catch((e) => { console.error('FATAL', String(e).slice(0, 300)); process.exit(2); });
