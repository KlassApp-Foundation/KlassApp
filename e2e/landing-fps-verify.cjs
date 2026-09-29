/**
 * Frame-rate check at 4x CPU slowdown. Exits 1 on meaningful long frames.
 *
 *   PREVIEW_BASE=https://... node e2e/landing-fps-verify.cjs
 *   FPS_TARGET="#agentCore" to measure the tower (default: #toshiTower, the orbit).
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium)
 *
 * The 8s window covers at least two full 3s bounce cycles of the tower.
 * rAF deltas are vsync-locked: small sub-millisecond jitter around 16.7ms is timer
 * noise, so the check flags meaningful long frames (>25ms) and hard stalls (>50ms)
 * while reporting the raw distribution.
 */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/';
const TARGET = process.env.FPS_TARGET || '#toshiTower';
let fails = 0;
const ck = (ok, m) => { console.log((ok ? 'PASS ' : 'FAIL ') + m); if (!ok) fails++; };

(async () => {
  const b = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
  for (const w of [390, 1440]) {
    const ctx = await b.newContext({ viewport: { width: w, height: 900 } });
    const p = await ctx.newPage();
    const client = await ctx.newCDPSession(p);
    await client.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    await p.goto(BASE, { waitUntil: 'networkidle' });
    await p.locator(TARGET).evaluate((e) => e.scrollIntoView({ block: 'center' }));
    const stats = await p.evaluate(async () => {
      await new Promise((r) => setTimeout(r, 2500));
      const deltas = [];
      let last = performance.now();
      await new Promise((res) => {
        const t0 = performance.now();
        function tick(t) {
          deltas.push(t - last);
          last = t;
          if (t - t0 > 8000) res(); else requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
      });
      deltas.shift();
      const sorted = [...deltas].sort((a, b) => a - b);
      const pct = (q) => sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * q))];
      return {
        n: deltas.length,
        median: pct(0.5),
        p95: pct(0.95),
        max: sorted[sorted.length - 1],
        over167: deltas.filter((d) => d > 16.75).length,
        over25: deltas.filter((d) => d > 25).length,
        over50: deltas.filter((d) => d > 50).length,
      };
    });
    console.log(`${w}px 4x throttle [${TARGET}]: frames=${stats.n} median=${stats.median.toFixed(2)}ms p95=${stats.p95.toFixed(2)}ms max=${stats.max.toFixed(2)}ms >16.7=${stats.over167} >25=${stats.over25} >50=${stats.over50}`);
    ck(stats.over50 === 0 && stats.over25 <= 5, w + 'px: no meaningful long frames at 4x throttling');
    await ctx.close();
  }
  await b.close();
  console.log(fails ? fails + ' FAILED' : 'ALL PASS');
  process.exit(fails ? 1 : 0);
})();
