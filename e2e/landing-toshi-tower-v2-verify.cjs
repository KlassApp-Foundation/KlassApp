/**
 * Landing #toshi — Toshi tower v2 acceptance checks (exits 1 on any failure).
 *
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud node e2e/landing-toshi-tower-v2-verify.cjs
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium)
 *
 * 1. K tile: pause the plate's 48s spin at 0 / 12 / 24 / 36 / 43.2s. The plate reads
 *    0 / 90 / 180 / -90 / -36 deg; the K mark reads 0 deg (never sideways) with
 *    det = +1 (never mirrored) at every sample, and has no animation of its own.
 * 2. Model cycling: exactly one .tv-mt.on at a time, changing every ~3s, alternating faces.
 * 3. Reduced motion (emulated prefers-reduced-motion: reduce): no running animations
 *    inside #toshi, exactly two .tv-mt.on unchanged after 10s, hover does not move a
 *    node, flow streaks display:none.
 * 4. Below 760px: no horizontal scroll at 320/375/414, channels/roles >= 44px tall.
 * 5. Label contrast: "Toshi" pill text vs its rendered pill background (pixel-sampled).
 */
const { chromium } = require('playwright');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const fails = [];
const check = (ok, msg) => { console.log((ok ? 'PASS ' : 'FAIL ') + msg); if (!ok) fails.push(msg); };

const angle = (m) => {
  if (!m || m === 'none') return { deg: 0, det: 1 };
  const v = m.match(/matrix\(([^)]+)\)/)[1].split(',').map(parseFloat);
  const deg = Math.round(Math.atan2(v[1], v[0]) * 180 / Math.PI);
  return { deg: Object.is(deg, -0) ? 0 : deg, det: +(v[0] * v[3] - v[1] * v[2]).toFixed(3) };
};

(async () => {
  const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});

  // ---- 1 + 2 + 5: full motion, desktop ----
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, reducedMotion: 'no-preference' });
    await page.goto(BASE + '/', { waitUntil: 'networkidle' });
    await page.locator('#toshi .toshi-visual').evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(1200);

    const samples = await page.evaluate(() => {
      const plate = document.querySelector('.tv-plate');
      const k = document.querySelector('.tv-ktile img');
      const anim = plate.getAnimations().find((a) => a.animationName === 'orbitSpin');
      if (!anim) return null;
      anim.pause();
      const kAnims = k.getAnimations().length;
      return { kAnims, points: [0, 12000, 24000, 36000, 43200].map((t) => {
        anim.currentTime = t;
        const r = k.getBoundingClientRect();
        return { t, plate: getComputedStyle(plate).transform, k: getComputedStyle(k).transform, kBox: [+r.width.toFixed(1), +r.height.toFixed(1)] };
      }) };
    });
    check(!!samples, 'plate has an orbitSpin animation');
    if (samples) {
      check(samples.kAnims === 0, `K mark has no animation of its own (found ${samples.kAnims})`);
      const want = { 0: 0, 12000: 90, 24000: 180, 36000: -90, 43200: -36 };
      for (const s of samples.points) {
        const p = angle(s.plate); const k = angle(s.k);
        const plateOk = ((p.deg - want[s.t]) % 360 + 360) % 360 === 0;
        check(plateOk, `t=${s.t / 1000}s plate ${p.deg}deg (expect ${want[s.t]})`);
        check(k.deg === 0 && k.det === 1, `t=${s.t / 1000}s K ${k.deg}deg det=${k.det} box=${s.kBox.join('x')} (upright, not mirrored)`);
      }
    }

    const seq = [];
    for (let n = 0; n < 6; n++) {
      seq.push(await page.evaluate(() => {
        const on = [...document.querySelectorAll('.tv-mt')].map((e, i) => (e.classList.contains('on') ? i : -1)).filter((i) => i >= 0);
        return { on, face: on.length ? (document.querySelectorAll('.tv-mt')[on[0]].classList.contains('tv-mt-l') ? 'L' : 'R') : '-' };
      }));
      await page.waitForTimeout(1500);
    }
    check(seq.every((s) => s.on.length === 1), 'exactly one model tile on at each 1.5s sample: ' + seq.map((s) => s.on.join('') + s.face).join(' → '));
    const distinct = [...new Set(seq.map((s) => s.on[0]))];
    check(distinct.length >= 3, `model tile advances (~3s): saw ${distinct.join(',')}`);

    // Label contrast, pixel-sampled: text colour from computed style, background from rendered pill pixels.
    const lbl = page.locator('.tv-lbl');
    const box = await lbl.boundingBox();
    const png = await page.screenshot({ clip: { x: box.x + 3, y: box.y + box.height / 2, width: 1, height: 1 } });
    const bgPx = await page.evaluate(async (b64) => {
      const img = new Image(); img.src = 'data:image/png;base64,' + b64; await img.decode();
      const c = document.createElement('canvas'); c.width = 1; c.height = 1; const x = c.getContext('2d'); x.drawImage(img, 0, 0);
      return [...x.getImageData(0, 0, 1, 1).data].slice(0, 3);
    }, png.toString('base64'));
    const fg = await lbl.evaluate((e) => getComputedStyle(e).color.match(/\d+/g).slice(0, 3).map(Number));
    const L = ([r, g, b]) => [r, g, b].map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }).reduce((a, v, i) => a + v * [0.2126, 0.7152, 0.0722][i], 0);
    const [hi, lo] = [L(fg), L(bgPx)].sort((a, b) => b - a);
    const ratio = +((hi + 0.05) / (lo + 0.05)).toFixed(2);
    console.log(`INFO label text rgb(${fg}) on rendered pill rgb(${bgPx}) = ${ratio}:1`);
    check(ratio >= 4.5, `Toshi label contrast ${ratio}:1 >= 4.5`);
    await page.close();
  }

  // ---- 3: reduced motion ----
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
    await page.goto(BASE + '/', { waitUntil: 'networkidle' });
    await page.locator('#toshi .toshi-visual').evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(1500);
    const state = () => page.evaluate(() => ({
      anims: document.getElementById('toshi').getAnimations({ subtree: true }).filter((a) => a.playState === 'running').map((a) => a.animationName || a.transitionProperty),
      on: [...document.querySelectorAll('.tv-mt')].map((e, i) => (e.classList.contains('on') ? i : -1)).filter((i) => i >= 0),
      flow: getComputedStyle(document.querySelector('.tv-fl')).display,
    }));
    const a = await state();
    await page.waitForTimeout(10000);
    const b = await state();
    check(a.anims.length === 0 && b.anims.length === 0, `no running animations in #toshi (${JSON.stringify(b.anims)})`);
    check(b.on.length === 2 && a.on.join() === b.on.join(), `two static model tiles, unchanged after 10s (${a.on} → ${b.on})`);
    check(b.flow === 'none', `flow streaks display:${b.flow}`);
    const ch = page.locator('.tv-ch').first();
    const before = await ch.boundingBox();
    await ch.hover(); await page.waitForTimeout(500);
    const after = await ch.boundingBox();
    check(Math.abs(before.y - after.y) < 0.5, `hover does not move a node (Δy=${(after.y - before.y).toFixed(2)})`);
    const docAnims = await page.evaluate(() => document.getAnimations().filter((x) => x.playState === 'running').length);
    console.log(`INFO document-wide running animations under reduce: ${docAnims}`);
    await page.close();
  }

  // ---- 4: stacked layout ----
  for (const width of [320, 375, 414]) {
    const page = await browser.newPage({ viewport: { width, height: 800 } });
    await page.goto(BASE + '/', { waitUntil: 'networkidle' });
    const r = await page.evaluate(() => ({
      sw: document.documentElement.scrollWidth, iw: innerWidth,
      ch: [...document.querySelectorAll('.tv-ch')].map((e) => Math.round(e.getBoundingClientRect().height)),
      roles: [...document.querySelectorAll('.tv-role')].map((e) => Math.round(e.getBoundingClientRect().height)),
      tower: (() => { const b = document.querySelector('.tv-tower').getBoundingClientRect(); return [Math.round(b.width), Math.round(b.height)]; })(),
    }));
    check(r.sw <= r.iw, `${width}px no horizontal scroll (scrollWidth ${r.sw} <= ${r.iw})`);
    check(r.ch.every((h) => h >= 44) && r.roles.every((h) => h >= 44), `${width}px tap heights channels ${r.ch} roles ${r.roles} (>=44)`);
    check(r.tower[0] === 300 && r.tower[1] === 260, `${width}px tower crop ${r.tower.join('x')}`);
    await page.close();
  }

  await browser.close();
  console.log(fails.length ? `\n${fails.length} FAILED` : '\nALL PASS');
  process.exit(fails.length ? 1 : 0);
})();
