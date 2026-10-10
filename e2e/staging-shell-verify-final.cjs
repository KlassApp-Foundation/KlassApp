// Final shell verification on staging: icon-left rows + groups open + popover fit + drawer.
const { chromium, webkit } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const BASE = 'https://test.klassapp.xyz';
const ENGINES = { chromium, webkit };
const OUT = '/Users/mac/projects/KlassApp-wt-shell-evidence/e2e/screenshots/staging-shell-verify-final';
const PAIRS = '/Users/mac/projects/KlassApp-wt-shell-evidence/evidence/shell-polish-staging/pairs';
const CONCEPT = 'file:///Users/mac/projects/KlassApp-wt-shell-evidence/design/system/concepts/admin-mvp/app.html';
fs.mkdirSync(OUT, { recursive: true, mode: 0o755 });
fs.mkdirSync(PAIRS, { recursive: true, mode: 0o755 });

function compose(left, right, out, label) {
  const py = `
from PIL import Image, ImageDraw
import sys
l = Image.open(sys.argv[1]).convert("RGB"); r = Image.open(sys.argv[2]).convert("RGB")
h = max(l.height, r.height); gap = 16; bar = 28
canvas = Image.new("RGB", (l.width + r.width + gap, h + bar), (17,24,39))
d = ImageDraw.Draw(canvas)
d.text((8, 8), sys.argv[3], fill=(226,232,240))
canvas.paste(l, (0, bar)); canvas.paste(r, (l.width + gap, bar))
canvas.save(sys.argv[4])
`;
  execFileSync('python3', ['-c', py, left, right, label, out], { stdio: 'inherit' });
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await page.fill('#email', 'admin@junior.demo.klassapp.test');
  await page.fill('#password', process.env.DEMO_PW);
  await Promise.all([page.waitForLoadState('domcontentloaded').catch(() => {}), page.click('button[type="submit"]')]);
  await page.waitForTimeout(2400);
}

async function metrics(page) {
  return page.evaluate(() => {
    const s = document.querySelector('#admin-sidebar');
    const uls = [...s.querySelectorAll('[data-sidebar-group] ul')];
    const openUls = uls.filter((u) => getComputedStyle(u).display !== 'none').length;
    const rows = [...s.querySelectorAll('li a')].filter((a) => a.offsetWidth > 0);
    const sample = rows.find((a) => (a.innerText || '').trim() === 'Students') || rows[1];
    let iconLeft = null;
    if (sample) {
      const svg = sample.querySelector('svg');
      const lab = sample.querySelector('span');
      if (svg && lab) {
        const sr = svg.getBoundingClientRect(), lr = lab.getBoundingClientRect();
        iconLeft = { svgX: Math.round(sr.x), labelX: Math.round(lr.x), dx: Math.round(lr.x - sr.x), sameLine: Math.abs(sr.y + sr.height / 2 - (lr.y + lr.height / 2)) < 8 };
      }
    }
    return { groupUls: uls.length, openUls, rowCount: rows.length, heights: rows.slice(0, 6).map((a) => Math.round(a.getBoundingClientRect().height)), iconLeft };
  });
}

(async () => {
  const results = [];
  for (const eng of ['chromium', 'webkit']) {
    for (const width of [1280, 1440]) {
      const b = await ENGINES[eng].launch();
      const p = await (await b.newContext({ viewport: { width, height: 900 } })).newPage();
      await login(p);
      await p.goto(`${BASE}/admin/dashboard`, { waitUntil: 'domcontentloaded' });
      await p.waitForTimeout(2800);
      const m = await metrics(p);
      const dir = path.join(OUT, eng, String(width)); fs.mkdirSync(dir, { recursive: true });
      const shot = path.join(dir, 'dashboard.png');
      await p.screenshot({ path: shot });
      const tmp = path.join(dir, '.concept.png');
      const b2 = await ENGINES[eng].launch();
      const p2 = await b2.newPage({ viewport: { width, height: 900 } });
      await p2.goto(CONCEPT + '?s=dashboard', { waitUntil: 'load' });
      await p2.waitForTimeout(1000);
      await p2.screenshot({ path: tmp });
      await b2.close();
      await compose(tmp, shot, path.join(PAIRS, `sidebar-${width}-${eng}.png`), `SIDEBAR ${width} ${eng}   LEFT=concept   RIGHT=staging`);
      fs.unlinkSync(tmp);
      // popover for 1280 only once per engine
      if (width === 1280) {
        await p.locator('#admin-sidebar [data-account-trigger]').click({ timeout: 8000 }).catch(() => {});
        await p.waitForTimeout(700);
        const pop = await p.evaluate(() => {
          const menu = document.querySelector('#admin-sidebar .account-card__menu');
          const m = menu ? menu.getBoundingClientRect() : null;
          return m ? { l: Math.round(m.left), r: Math.round(m.right), t: Math.round(m.top), b: Math.round(m.bottom), inViewport: m.left >= 0 && m.right <= innerWidth && m.top >= 0 && m.bottom <= innerHeight } : null;
        });
        const popshot = path.join(dir, 'popover-open.png');
        await p.screenshot({ path: popshot });
        const tmp2 = path.join(dir, '.concept2.png');
        const b3 = await ENGINES[eng].launch();
        const p3 = await b3.newPage({ viewport: { width, height: 900 } });
        await p3.goto(CONCEPT + '?s=dashboard&acct=1', { waitUntil: 'load' });
        await p3.waitForTimeout(900);
        await p3.screenshot({ path: tmp2 });
        await b3.close();
        await compose(tmp2, popshot, path.join(PAIRS, `popover-1280-${eng}.png`), `ACCOUNT POPOVER 1280 ${eng}   LEFT=concept   RIGHT=staging`);
        fs.unlinkSync(tmp2);
        m.popover = pop;
      }
      results.push({ eng, width, ...m });
      await b.close();
      console.log('done', eng, width, JSON.stringify(m));
    }
  }
  // drawer 375 webkit
  {
    const b = await webkit.launch();
    const p = await (await b.newContext({ viewport: { width: 375, height: 812 } })).newPage();
    await login(p);
    await p.goto(`${BASE}/admin/dashboard`, { waitUntil: 'domcontentloaded' });
    await p.waitForTimeout(2600);
    await p.locator('#mobile-menu-trigger, [aria-label*="menu" i]').first().click({ timeout: 6000 }).catch(() => {});
    await p.waitForTimeout(1100);
    const dm = await p.evaluate(() => {
      const d = document.querySelector('#res_sidebar');
      const rows = d ? [...d.querySelectorAll('li a')].filter((a) => a.offsetWidth > 0) : [];
      return { visible: d ? getComputedStyle(d).display !== 'none' : false, rowCount: rows.length, heights: rows.slice(0, 5).map((a) => Math.round(a.getBoundingClientRect().height)) };
    });
    const dir = path.join(OUT, 'webkit', '375'); fs.mkdirSync(dir, { recursive: true });
    const shot = path.join(dir, 'drawer-open.png');
    await p.screenshot({ path: shot });
    const tmp = path.join(dir, '.concept.png');
    const b2 = await webkit.launch();
    const p2 = await b2.newPage({ viewport: { width: 375, height: 812 } });
    await p2.goto(CONCEPT + '?s=dashboard', { waitUntil: 'load' });
    await p2.waitForTimeout(900);
    await p2.screenshot({ path: tmp });
    await b2.close();
    await compose(tmp, shot, path.join(PAIRS, 'drawer-375-webkit.png'), 'PHONE DRAWER 375 webkit   LEFT=concept   RIGHT=staging');
    fs.unlinkSync(tmp);
    results.push({ eng: 'webkit', width: 375, drawer: dm });
    await b.close();
    console.log('drawer', JSON.stringify(dm));
  }
  fs.writeFileSync(path.join(OUT, 'results.json'), JSON.stringify(results, null, 2));
  console.log('ALL DONE');
})().catch((e) => { console.error('FATAL', String(e).slice(0, 300)); process.exit(2); });
