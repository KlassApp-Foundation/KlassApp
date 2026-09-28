/**
 * Landing "How it works" Toshi tower v2 - acceptance checks (exits 1 on any failure).
 *
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud node e2e/landing-agent-core-verify.cjs
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium)
 *
 * 1. Placement: the tower lives inside #how-it-works (id agentCore); the old hub pill is gone;
 *    #toshi still renders the orbital tower and contains no ac-* elements.
 * 2. K coin: its 10s rotateY turn is paused at 0 / 2.5s / 5s = 0 / 90 / 180 degrees. A correctly
 *    oriented K is visible at 0 (front face) and 180 (back face, never mirrored); 90 is edge-on.
 * 3. Bounce, signals, screens: the cube rises ~24px per 3s cycle; landings fire a shockwave,
 *    packets reach nodes (.hit glow), and both screens swap together through the 3 model pairs.
 * 4. Reduced motion (emulated): no running animations inside the tower; K faces forward; the
 *    screens show Anthropic + OpenAI; "Runs on" lists all six; hover does not move a node.
 * 5. Below 760px: no horizontal scroll at 320/375/414; nine nodes in a 3-column grid, >=44px tall.
 */
const { chromium } = require('playwright');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const fails = [];
const check = (ok, msg) => { console.log((ok ? 'PASS ' : 'FAIL ') + msg); if (!ok) fails.push(msg); };
const PAIRS = [['anthropic-mark', 'openai-mark'], ['google-gemini-mark', 'xai-grok-mark'], ['moonshot-kimi-mark', 'zhipu-zai-mark']];

/* Parse x-scale (m11/`a`) and det from a computed transform. */
function mat(m) {
  if (!m || m === 'none') return { a: 1, det: 1, y: 0 };
  const m3 = m.match(/matrix3d\(([^)]+)\)/);
  if (m3) { const v = m3[1].split(',').map(parseFloat); return { a: v[0], det: +(v[0] * v[5] - v[1] * v[4]).toFixed(3), y: v[13] }; }
  const m2 = m.match(/matrix\(([^)]+)\)/);
  const v = m2[1].split(',').map(parseFloat);
  return { a: v[0], det: +(v[0] * v[3] - v[1] * v[2]).toFixed(3), y: v[5] };
}

(async () => {
  try { await fetch(BASE + '/'); } catch (e) { /* warm-up */ }
  const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});

  /* ---- 1: placement ---- */
  {
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    await page.goto(BASE + '/', { waitUntil: 'networkidle', timeout: 120000 });
    const r = await page.evaluate(() => {
      const a = document.getElementById('agentCore');
      const t = document.getElementById('toshi');
      return {
        inHow: !!(a && a.closest('#how-it-works')),
        hub: document.querySelectorAll('.how-hub-node').length,
        toshiOrbital: !!(t && t.querySelector('#toshiTower .tt-svg')),
        acInToshi: t ? t.querySelectorAll('[class^="ac-"], [class*=" ac-"]').length : -1,
      };
    });
    check(r.inHow, 'tower (id agentCore) lives inside #how-it-works');
    check(r.hub === 0, 'the old hub pill (.how-hub-node) is gone from the markup');
    check(r.toshiOrbital && r.acInToshi === 0, `#toshi still renders the orbital tower with no ac-* elements (${r.acInToshi})`);
    await page.close();
  }

  /* ---- 2 + 3: motion, desktop ---- */
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, reducedMotion: 'no-preference' });
    const errs = [];
    page.on('pageerror', (e) => errs.push(String(e).slice(0, 90)));
    await page.goto(BASE + '/', { waitUntil: 'networkidle', timeout: 120000 });
    await page.locator('#agentCore').evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(1500);

    /* K coin at 0 / 90 / 180 degrees */
    const spinAnim = await page.evaluate(() => {
      const spin = document.querySelector('.ac-spin');
      const anim = spin.getAnimations().find((a) => (a.animationName || '').includes('ac-coin'));
      if (!anim) return false;
      document.querySelectorAll('.ac-kb, .ac-stage svg .ac-bounce').forEach((el) => el.getAnimations().forEach((a) => a.pause()));
      anim.pause();
      return true;
    });
    check(spinAnim, 'the coin spin (ac-coin) animation exists');
    const kStates = [];
    if (spinAnim) {
      for (const tMs of [0, 2500, 5000]) {
        const st = await page.evaluate(async (tMs) => {
          const spin = document.querySelector('.ac-spin');
          const anim = spin.getAnimations().find((a) => (a.animationName || '').includes('ac-coin'));
          anim.currentTime = tMs;
          await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
          const r = document.querySelector('.ac-coin').getBoundingClientRect();
          return {
            spin: getComputedStyle(spin).transform,
            f: getComputedStyle(document.querySelector('.ac-spin img.f')).transform,
            b: getComputedStyle(document.querySelector('.ac-spin img.b')).transform,
            rect: [Math.round(r.x), Math.round(r.y), Math.round(r.width), Math.round(r.height)],
          };
        }, tMs);
        const buf = await page.screenshot({ clip: { x: st.rect[0], y: st.rect[1], width: st.rect[2], height: st.rect[3] } });
        const pix = await page.evaluate(async (b64) => {
          const img = new Image();
          img.src = 'data:image/png;base64,' + b64;
          await img.decode();
          const c = document.createElement('canvas');
          c.width = 64; c.height = 64;
          const x = c.getContext('2d');
          x.drawImage(img, 0, 0, 64, 64);
          const d = x.getImageData(0, 0, 64, 64).data;
          const gray = [];
          let ink = 0;
          for (let i = 0; i < d.length; i += 4) {
            const g = (d[i] + d[i + 1] + d[i + 2]) / 3;
            gray.push(Math.round(g));
            if (g < 243) ink++;
          }
          return { ink: +(ink / (64 * 64)).toFixed(3), gray };
        }, buf.toString('base64'));
        kStates.push({ tMs, ...st, ink: pix.ink, gray: pix.gray });
      }
      await page.evaluate(() => {
        document.querySelectorAll('.ac-kb, .ac-stage svg .ac-bounce').forEach((el) => el.getAnimations().forEach((a) => a.play()));
        const anim = document.querySelector('.ac-spin').getAnimations().find((a) => (a.animationName || '').includes('ac-coin'));
        if (anim) anim.play();
      });
    }
    check(kStates.length === 3, 'captured K states at 0 / 90 / 180 degrees');
    if (kStates.length === 3) {
      const [s0, s9, s18] = kStates.map((x) => ({ ...mat(x.spin), ink: x.ink, f: mat(x.f), b: mat(x.b), gray: x.gray }));
      check(Math.abs(s0.a - 1) < 0.01 && s0.ink > 0.04, `rotateY 0deg: K visible, facing forward (a=${s0.a.toFixed(2)}, ink=${s0.ink})`);
      check(Math.abs(s9.a) < 0.1 && s9.ink < s0.ink * 0.5, `rotateY 90deg: edge-on, far less paint (a=${s9.a.toFixed(2)}, ink=${s9.ink})`);
      check(Math.abs(s18.a + 1) < 0.01 && s18.ink > 0.04, `rotateY 180deg: K visible through the back face (a=${s18.a.toFixed(2)}, ink=${s18.ink})`);
      const net0 = s0.a * s0.f.a, net18 = s18.a * s18.b.a;
      check(net0 > 0.9 && net18 > 0.9, `never mirrored: net x-scale ${net0.toFixed(2)} at 0deg, ${net18.toFixed(2)} at 180deg`);
      const diff = (a, b) => { let s = 0; for (let i = 0; i < a.length; i++) s += Math.abs(a[i] - b[i]); return s / a.length; };
      const flipped = (a) => { const o = new Array(4096); for (let y = 0; y < 64; y++) for (let x = 0; x < 64; x++) o[y * 64 + x] = a[y * 64 + (63 - x)]; return o; };
      const dSame = diff(s0.gray, s18.gray);
      const dMirror = diff(s0.gray, flipped(s18.gray));
      console.log(`INFO K face pixel diff: same-orientation ${dSame.toFixed(1)} vs mirrored ${dMirror.toFixed(1)}`);
      check(dSame < dMirror, `back K reads like the front, not mirrored (${dSame.toFixed(1)} < ${dMirror.toFixed(1)})`);
    }

    /* Bounce: cube + K rise per 3s cycle */
    const bounce = await page.evaluate(async () => {
      const read = (el) => { const m = getComputedStyle(el).transform; const mm = m.match(/matrix3d\(([^)]+)\)/); if (mm) return parseFloat(mm[1].split(',')[13]); const m2 = m.match(/matrix\(([^)]+)\)/); return m2 ? parseFloat(m2[1].split(',')[5]) : 0; };
      const cube = document.querySelector('.ac-stage svg .ac-bounce');
      const kb = document.querySelector('.ac-kb');
      let minCube = 0, minKb = 0;
      for (let i = 0; i < 32; i++) {
        minCube = Math.min(minCube, read(cube));
        minKb = Math.min(minKb, read(kb));
        await new Promise((r) => setTimeout(r, 100));
      }
      return { minCube, minKb };
    });
    check(bounce.minCube <= -18, `cube bounces (min translateY ${bounce.minCube.toFixed(1)}px, expect <= -18)`);
    check(bounce.minKb <= -18, `K bounces with the cube (min translateY ${bounce.minKb.toFixed(1)}px)`);

    /* Signals + screens over ~7.5s */
    const sig = await page.evaluate(async () => {
      const G = document.getElementById('ac-sig');
      const nodes = [...document.querySelectorAll('.ac-node')];
      const seen = new WeakSet();
      let hits = 0, maxPackets = 0, swaps = 0, badPair = 0;
      let lastL = null;
      const valid = (l, r) => [[l, r]].some(([a, b]) => ['anthropic-mark', 'google-gemini-mark', 'moonshot-kimi-mark'].includes(a) && ['openai-mark', 'xai-grok-mark', 'zhipu-zai-mark'].includes(b) && (['anthropic-mark', 'google-gemini-mark', 'moonshot-kimi-mark'].indexOf(a) === ['openai-mark', 'xai-grok-mark', 'zhipu-zai-mark'].indexOf(b)));
      for (let i = 0; i < 50; i++) {
        maxPackets = Math.max(maxPackets, G.children.length);
        nodes.forEach((n) => { if (n.classList.contains('hit') && !seen.has(n)) { seen.add(n); hits++; } else if (!n.classList.contains('hit')) { seen.delete(n); } });
        const l = (document.getElementById('ac-scrL').getAttribute('href') || '').split('/').pop().replace('.svg', '');
        const r = (document.getElementById('ac-scrR').getAttribute('href') || '').split('/').pop().replace('.svg', '');
        if (!valid(l, r)) badPair++;
        if (lastL !== null && l !== lastL) swaps++;
        lastL = l;
        await new Promise((res) => setTimeout(res, 150));
      }
      return { hits, maxPackets, swaps, badPair };
    });
    check(sig.maxPackets >= 1, `signal packets appeared (max in flight ${sig.maxPackets})`);
    check(sig.hits >= 3, `nodes lit up on packet arrival (${sig.hits} node hits over ~7.5s)`);
    check(sig.swaps >= 1 && sig.badPair === 0, `screens swapped through valid pairs together (${sig.swaps} swaps, ${sig.badPair} invalid samples)`);
    check(errs.length === 0, 'no page errors during motion (' + errs.slice(0, 2).join(' | ') + ')');
    await page.close();
  }

  /* ---- 4: reduced motion ---- */
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
    await page.goto(BASE + '/', { waitUntil: 'networkidle', timeout: 120000 });
    await page.locator('#agentCore').evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(1500);
    const rm = await page.evaluate(() => ({
      anims: document.getElementById('agentCore').getAnimations({ subtree: true }).filter((a) => a.playState === 'running').map((a) => a.animationName || a.transitionProperty),
      spin: getComputedStyle(document.querySelector('.ac-spin')).transform,
      l: (document.getElementById('ac-scrL').getAttribute('href') || '').split('/').pop(),
      r: (document.getElementById('ac-scrR').getAttribute('href') || '').split('/').pop(),
      models: document.querySelectorAll('.ac-models img').length,
    }));
    check(rm.anims.length === 0, 'reduced motion: no running animations inside the tower (' + JSON.stringify(rm.anims) + ')');
    check(mat(rm.spin).a > 0.99, 'reduced motion: the K faces forward');
    check(rm.l === 'anthropic-mark.svg' && rm.r === 'openai-mark.svg', `reduced motion: screens show Anthropic + OpenAI (${rm.l}, ${rm.r})`);
    check(rm.models === 6, `reduced motion: Runs on lists all six (${rm.models})`);
    const bounceStatic = await page.evaluate(async () => {
      const read = () => getComputedStyle(document.querySelector('.ac-stage svg .ac-bounce')).transform;
      const a = read();
      await new Promise((r) => setTimeout(r, 900));
      return a === read();
    });
    check(bounceStatic, 'reduced motion: the cube does not bounce');
    const ch = page.locator('.ac-ch').first();
    await ch.scrollIntoViewIfNeeded();
    const before = await ch.boundingBox();
    await ch.hover();
    await page.waitForTimeout(400);
    const after = await ch.boundingBox();
    check(Math.abs(before.y - after.y) < 0.5, `reduced motion: hover does not move a node (dy ${(after.y - before.y).toFixed(2)})`);
    await page.close();
  }

  /* ---- 5: stacked layout ---- */
  for (const width of [320, 375, 414]) {
    const page = await browser.newPage({ viewport: { width, height: 800 } });
    await page.goto(BASE + '/', { waitUntil: 'networkidle', timeout: 120000 });
    const r = await page.evaluate(() => {
      const nodes = [...document.querySelectorAll('.ac-ch, .ac-role')];
      const cols = getComputedStyle(document.querySelector('.ac-nodes')).gridTemplateColumns.split(' ').length;
      return {
        sw: document.documentElement.scrollWidth,
        iw: innerWidth,
        count: nodes.length,
        heights: nodes.map((n) => Math.round(n.getBoundingClientRect().height)),
        position: getComputedStyle(nodes[0]).position,
        cols,
      };
    });
    check(r.sw <= r.iw, `${width}px: no horizontal scroll (${r.sw} <= ${r.iw})`);
    check(r.count === 9 && r.cols === 3 && r.position === 'static', `${width}px: nine nodes in a 3-column static grid (${r.count} nodes, ${r.cols} cols)`);
    check(r.heights.every((h) => h >= 44), `${width}px: node heights ${r.heights.join(',')} (all >= 44)`);
    await page.close();
  }

  await browser.close();
  console.log(fails.length ? `\n${fails.length} FAILED` : '\nALL PASS');
  process.exit(fails.length ? 1 : 0);
})();
