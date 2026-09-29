/**
 * Orbit (#toshi) verification - exits 1 on any failure.
 *
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud node e2e/landing-orbit-verify.cjs
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium)
 *
 * - The SVG is aria-hidden and #toshiTower carries no aria-label.
 * - Tokens: .tt-beat computes to the brand green; no retired literals remain.
 * - One K decal visible at a time; the other face is hidden.
 * - <=700px: 6 tiles visible, including Drive, excluding SMS.
 * - No-preference: at most one .tt-beat lit at a time across an 18s sample.
 * - Reduced motion: zero animations inside #toshiTower, K faces forward, all tiles visible.
 * - No console errors.
 */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/';
let fails = 0;
const ck = (ok, m) => { console.log((ok ? 'PASS ' : 'FAIL ') + m); if (!ok) fails++; };

(async () => {
  const b = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});

  // ---- desktop, no-preference ----
  {
    const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
    const errs = [];
    p.on('pageerror', (e) => errs.push(String(e).slice(0, 90)));
    await p.goto(BASE, { waitUntil: 'networkidle' });
    await p.locator('#toshiTower').scrollIntoViewIfNeeded();
    await p.waitForTimeout(1200);

    const attrs = await p.evaluate(() => {
      const tower = document.getElementById('toshiTower');
      const svg = tower.querySelector('svg.tt-svg');
      return { ariaLabel: tower.getAttribute('aria-label'), svgAriaHidden: svg.getAttribute('aria-hidden'), svgFocusable: svg.getAttribute('focusable') };
    });
    ck(attrs.ariaLabel === null, 'toshiTower has no aria-label');
    ck(attrs.svgAriaHidden === 'true' && attrs.svgFocusable === 'false', 'svg is aria-hidden with focusable=false');

    const tokens = await p.evaluate(() => {
      const beat = document.querySelector('.tt-beat');
      const rim = document.querySelector('.tt-core > circle.tt-gs');
      return { beat: getComputedStyle(beat).stroke, rim: rim ? getComputedStyle(rim).stroke : null };
    });
    ck(tokens.beat === 'rgb(34, 197, 94)', 'tt-beat stroke = brand green (' + tokens.beat + ')');
    ck(tokens.rim === 'rgb(34, 197, 94)', 'globe rim stroke = brand green (' + tokens.rim + ')');

    const literals = await p.evaluate(() => {
      const html = document.getElementById('toshiTower').innerHTML;
      return ['#86EFAC', '#4ADE80', '#34D399', '#93C5FD', '#ECFDF5', '#e8e6dc'].filter((x) => html.includes(x));
    });
    ck(literals.length === 0, 'no retired colour literals in the tower markup: ' + JSON.stringify(literals));

    // K decals: never both visible at once; a single face shows for most of the cycle.
    const kSamples = [];
    for (let i = 0; i < 12; i++) {
      kSamples.push(await p.evaluate(() => [...document.querySelectorAll('.tt-k')].map((k) => parseFloat(getComputedStyle(k).opacity) > 0.5)));
      await p.waitForTimeout(2000);
    }
    const doubles = kSamples.filter((s) => s.filter(Boolean).length === 2).length;
    const singles = kSamples.filter((s) => s.filter(Boolean).length === 1).length;
    ck(doubles === 0, 'K decals never both visible at once (' + JSON.stringify(kSamples) + ')');
    ck(singles >= 8, 'a single K face visible for most of the cycle (' + singles + '/12 samples)');

    // at most one beat lit across an 18s sample (300ms steps)
    let maxLit = 0;
    for (let i = 0; i < 62; i++) {
      const lit = await p.evaluate(() => [...document.querySelectorAll('.tt-beat')].filter((c) => parseFloat(getComputedStyle(c).opacity) > 0.05).length);
      maxLit = Math.max(maxLit, lit);
      await p.waitForTimeout(300);
    }
    ck(maxLit <= 1, 'at most one beat lit at a time across 18s (max seen: ' + maxLit + ')');

    ck(errs.length === 0, 'no console/page errors on the landing (' + errs.slice(0, 2).join(' | ') + ')');
    await p.close();
  }

  // ---- mobile tiles ----
  {
    const p = await b.newPage({ viewport: { width: 375, height: 812 } });
    await p.goto(BASE, { waitUntil: 'networkidle' });
    const tiles = await p.evaluate(() => {
      return [...document.querySelectorAll('.tt-nodes > g.tt-node')].map((g) => ({ t: g.getAttribute('transform'), hidden: getComputedStyle(g).display === 'none' }));
    });
    const visible = tiles.filter((x) => !x.hidden);
    ck(visible.length === 6, '375px: six tiles visible (' + visible.length + ')');
    ck(visible.some((x) => x.t.includes('606 402.7')), '375px: Drive visible');
    ck(!tiles.filter((x) => x.t.includes('394 257.3')).some((x) => !x.hidden), '375px: SMS hidden');
    await p.close();
  }

  // ---- reduced motion ----
  {
    const ctx = await b.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' });
    const p = await ctx.newPage();
    await p.goto(BASE, { waitUntil: 'networkidle' });
    await p.locator('#toshiTower').scrollIntoViewIfNeeded();
    await p.waitForTimeout(1200);
    const rm = await p.evaluate(() => {
      const tower = document.getElementById('toshiTower');
      const anims = tower.getAnimations({ subtree: true }).length;
      const k = [...tower.querySelectorAll('.tt-k')].map((x) => parseFloat(getComputedStyle(x).opacity));
      const tiles = [...tower.querySelectorAll('.tt-tile')].every((t) => getComputedStyle(t).display !== 'none');
      return { anims, k, tiles };
    });
    ck(rm.anims === 0, 'reduced motion: zero animations inside the tower (' + rm.anims + ')');
    ck(rm.k[0] > 0.5 && rm.k[1] < 0.5, 'reduced motion: the K faces forward (' + JSON.stringify(rm.k) + ')');
    ck(rm.tiles, 'reduced motion: tiles visible');
    await ctx.close();
  }

  await b.close();
  console.log(fails ? fails + ' FAILED' : 'ALL PASS');
  process.exit(fails ? 1 : 0);
})();
