/**
 * Layout-shift (CLS) check for the landing page: loads, settles, then walks
 * the page slowly and reports the total unshifted-by-input CLS plus the top
 * contributors. Evidence tool: prints values, exits 0.
 *   PREVIEW_BASE=http://127.0.0.1:8000 node e2e/landing-cls-verify.cjs
 */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/';
(async () => {
  try { await fetch(BASE); } catch (e) { /* warm-up */ }
  const b = await chromium.launch({ channel: 'chrome', headless: true });
  const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const p = await ctx.newPage();
  await p.addInitScript(() => {
    window.__cls = 0; window.__top = [];
    new PerformanceObserver((l) => {
      for (const e of l.getEntries()) {
        if (e.hadRecentInput) continue;
        window.__cls += e.value;
        const src = (e.sources || []).map((s) => { const n = s.node; return n ? (n.tagName || '') + '.' + String(n.className || '').split(' ')[0] : '?'; }).slice(0, 3);
        window.__top.push({ v: +e.value.toFixed(4), at: Math.round(e.startTime), src });
      }
    }).observe({ type: 'layout-shift', buffered: true });
  });
  await p.goto(BASE, { waitUntil: 'load', timeout: 120000 });
  await p.waitForTimeout(2500);
  await p.evaluate(async () => {
    const h = document.body.scrollHeight;
    for (let y = 0; y <= h; y += 350) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 200)); }
  });
  await p.waitForTimeout(1500);
  const r = await p.evaluate(() => ({ cls: +window.__cls.toFixed(4), shifts: [...window.__top].sort((a, b) => b.v - a.v).slice(0, 5) }));
  console.log('CLS=' + r.cls + ' top=' + JSON.stringify(r.shifts));
  await b.close();
})();
