// Staging shell verification: compact sidebar + account popover, 1280/1440, chromium+webkit.
// Captures staging frames, asserts geometry, composes concept|staging pairs.
const { chromium, webkit } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const BASE = 'https://test.klassapp.xyz';
const ENGINES = { chromium, webkit };
const OUT = '/Users/mac/projects/KlassApp-wt-shell-evidence/e2e/screenshots/staging-shell-verify';
const PAIRS = '/Users/mac/projects/KlassApp-wt-shell-evidence/evidence/shell-polish-staging/pairs';
const CONCEPT = 'file:///Users/mac/projects/KlassApp-wt-shell-evidence/design/system/concepts/admin-mvp/app.html';
fs.mkdirSync(OUT, { recursive: true });
fs.mkdirSync(PAIRS, { recursive: true });

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await page.fill('#email', 'admin@junior.demo.klassapp.test');
  await page.fill('#password', process.env.DEMO_PW);
  await Promise.all([page.waitForLoadState('domcontentloaded').catch(() => {}), page.click('button[type="submit"]')]);
  await page.waitForTimeout(2500);
}

async function capture(engineName, width) {
  const browser = await ENGINES[engineName].launch();
  const ctx = await browser.newContext({ viewport: { width, height: 900 } });
  const page = await ctx.newPage();
  const dir = path.join(OUT, engineName, String(width));
  fs.mkdirSync(dir, { recursive: true });
  const res = { engine: engineName, width };
  await login(page);
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);

  // sidebar metrics
  res.sidebar = await page.evaluate(() => {
    const rows = [...document.querySelectorAll('#admin-sidebar a')].filter((a) => a.offsetWidth > 0);
    const groups = [...document.querySelectorAll('[data-sidebar-group]')].map((g) => g.getAttribute('data-sidebar-group'));
    const heights = rows.slice(0, 8).map((a) => Math.round(a.getBoundingClientRect().height));
    const withIcon = rows.filter((a) => a.querySelector('svg')).length;
    return { rowCount: rows.length, heights, withIcon, groups };
  });
  await page.screenshot({ path: path.join(dir, 'dashboard.png') });

  // popover
  const trigger = page.locator('#admin-sidebar [data-account-trigger]');
  await trigger.click({ timeout: 8000 }).catch(() => {});
  await page.waitForTimeout(700);
  res.popover = await page.evaluate(() => {
    const menu = document.querySelector('#admin-sidebar .account-card__menu');
    const card = document.querySelector('#admin-sidebar .account-card__trigger');
    const m = menu ? menu.getBoundingClientRect() : null;
    const c = card ? card.getBoundingClientRect() : null;
    return {
      visible: !!(m && m.width > 0),
      rect: m ? { l: Math.round(m.left), r: Math.round(m.right), t: Math.round(m.top), b: Math.round(m.bottom), w: Math.round(m.width) } : null,
      gap: m && c ? Math.round(c.top - m.bottom) : null,
      inViewport: m ? (m.left >= 0 && m.right <= window.innerWidth && m.top >= 0 && m.bottom <= window.innerHeight) : null,
      vw: window.innerWidth, vh: window.innerHeight,
    };
  });
  await page.screenshot({ path: path.join(dir, 'popover-open.png') });
  await browser.close();
  return res;
}

async function shootConcept(query, viewport, engineName, out) {
  const browser = await ENGINES[engineName].launch();
  const page = await browser.newPage({ viewport });
  await page.goto(CONCEPT + query, { waitUntil: 'load' });
  await page.waitForTimeout(1200);
  await page.screenshot({ path: out, fullPage: false });
  await browser.close();
}

function compose(left, right, out, label) {
  const py = `
from PIL import Image, ImageDraw
import sys
l = Image.open(sys.argv[1]).convert("RGB"); r = Image.open(sys.argv[2]).convert("RGB")
h = max(l.height, r.height); gap = 16; bar = 28
canvas = Image.new("RGB", (l.width + r.width + gap, h + bar), (17,24,39))
d = ImageDraw.Draw(canvas)
d.text((8, 8), sys.argv[3] + "   LEFT=concept   RIGHT=staging", fill=(226,232,240))
canvas.paste(l, (0, bar)); canvas.paste(r, (l.width + gap, bar))
canvas.save(sys.argv[4])
`;
  execFileSync('python3', ['-c', py, left, right, label, out], { stdio: 'inherit' });
}

(async () => {
  const results = [];
  for (const engineName of ['chromium', 'webkit']) {
    for (const width of [1280, 1440]) {
      results.push(await capture(engineName, width));
    }
  }
  // pairs: sidebar + popover, both widths, both engines
  for (const engineName of ['chromium', 'webkit']) {
    for (const width of [1280, 1440]) {
      const dir = path.join(OUT, engineName, String(width));
      const tmp1 = path.join(dir, '.concept-dash.png');
      await shootConcept('?s=dashboard', { width, height: 900 }, engineName, tmp1);
      compose(tmp1, path.join(dir, 'dashboard.png'), path.join(PAIRS, `sidebar-${width}-${engineName}.png`), `SIDEBAR ${width} ${engineName}`);
      const tmp2 = path.join(dir, '.concept-acct.png');
      await shootConcept('?s=dashboard&acct=1', { width, height: 900 }, engineName, tmp2);
      compose(tmp2, path.join(dir, 'popover-open.png'), path.join(PAIRS, `popover-${width}-${engineName}.png`), `ACCOUNT POPOVER ${width} ${engineName}`);
      fs.unlinkSync(tmp1); fs.unlinkSync(tmp2);
      console.log('composed', width, engineName);
    }
  }
  fs.writeFileSync(path.join(OUT, 'results.json'), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 1).slice(0, 2800));
})().catch((e) => { console.error('FATAL', String(e).slice(0, 300)); process.exit(2); });
