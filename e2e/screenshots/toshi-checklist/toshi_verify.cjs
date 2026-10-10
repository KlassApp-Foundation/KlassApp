// Toshi checklist verification: bar = chip = Toshi checklist (dock + modal), both widths, lever schools.
const { chromium } = require('playwright');
const fs = require('node:fs');
const OUT = '/Users/mac/projects/KlassApp-wt-toshi/e2e/screenshots/toshi-checklist';
fs.mkdirSync(OUT, { recursive: true });
const BASE = 'https://test.klassapp.xyz';
const T_PW = process.env.T_PW;

async function login(page, email, pw) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await page.fill('#email', email);
  await page.fill('#password', pw);
  await Promise.all([page.waitForLoadState('domcontentloaded').catch(() => {}), page.click('button[type="submit"]')]);
  await page.waitForTimeout(2500);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const results = {};
  for (const [label, email] of [
    ['mid266', 'pr1mid266@e2e.stg'],
    ['fresh267', 'pr1new267@e2e.stg'],
  ]) {
    for (const width of [1280, 375]) {
      const ctx = await browser.newContext({ viewport: { width, height: width === 375 ? 812 : 900 } });
      const page = await ctx.newPage();
      const key = `${label}-${width}`;
      try {
        await login(page, email, T_PW);
        await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(3000);
        const bar = await page.evaluate(() => {
          const t = (sel) => (document.querySelector(sel) || {}).innerText || null;
          return { count: t('[data-testid="dashboard-v2-setup-count"]'), next: t('[data-testid="dashboard-v2-setup-next"]') };
        });
        // open the panel via the bar button (or JS) and read the dock checklist
        const btn = page.locator('[data-testid="dashboard-v2-toshi-setup"]');
        let opened = false;
        if (await btn.count().catch(() => 0)) {
          await btn.scrollIntoViewIfNeeded().catch(() => {});
          opened = await btn.click({ timeout: 8000 }).then(() => true).catch(() => false);
          await page.waitForTimeout(2500);
        }
        if (!opened) {
          await page.evaluate(() => window.toshiSetCollapsed && window.toshiSetCollapsed(false));
          await page.waitForTimeout(1800);
        }
        const dock = await page.evaluate(() => {
          const t = (sel) => (document.querySelector(sel) || {}).innerText || null;
          const modal = document.querySelector('[data-testid="toshi-modal-checklist"]');
          const modalVisible = modal ? modal.getBoundingClientRect().width > 0 : false;
          const dock = document.querySelector('[data-testid="toshi-setup-list"]');
          const dockVisible = dock ? dock.getBoundingClientRect().width > 0 : false;
          const rows = [...document.querySelectorAll('[data-testid^="toshi-setup-row-"]')].filter((el) => el.getBoundingClientRect().width > 0).length;
          const modalRows = [...document.querySelectorAll('[data-testid^="toshi-setup-row-modal-"]')].filter((el) => el.getBoundingClientRect().width > 0).length;
          return {
            modalVisible, dockVisible,
            rowsVisible: rows, modalRowsVisible: modalRows,
            text: (document.querySelector('[data-toshi-root]') || {}).innerText?.slice(0, 300) || null,
          };
        });
        results[key] = { bar, dock };
        await page.screenshot({ path: `${OUT}/${key}-checklist.png` });
      } catch (e) {
        results[key] = { error: String(e).slice(0, 220) };
      }
      await ctx.close();
    }
  }
  fs.writeFileSync(`${OUT}/results.json`, JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 2).slice(0, 3800));
  await browser.close();
})().catch((e) => { console.error('FATAL', String(e).slice(0, 300)); process.exit(2); });
