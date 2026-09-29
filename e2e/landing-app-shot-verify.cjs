/**
 * Landing screenshots section + Toshi K-mark drift — exits 1 on any failure.
 *
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud node e2e/landing-app-shot-verify.cjs
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium)
 *
 * - Every .shot-frame is 16:10 and every capture loads (HTTP 200, naturalWidth > 0) with real alt text.
 * - Swapping a capture for a different-aspect image changes neither the frame height nor
 *   the position of the section below it (no layout shift from future recaptures).
 * - "How it works" WhatsApp avatar and teacher sidebar use the K icon, not "KA" / "K" text.
 * - app-page.webp is gone; app-fees.webp is served.
 */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/';
let fails = 0;
const ck = (ok, m) => { console.log((ok ? 'PASS ' : 'FAIL ') + m); if (!ok) fails++; };
(async () => {
  const b = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
  for (const w of [375, 768, 1280]) {
    const p = await b.newPage({ viewport: { width: w, height: 900 } });
    await p.goto(BASE, { waitUntil: 'networkidle' });
    await p.evaluate(async () => { for (const i of document.querySelectorAll('.shot-frame img')) { i.loading = 'eager'; if (!i.complete) await new Promise(r => { i.onload = i.onerror = r; }); } });
    const shots = await p.evaluate(() => [...document.querySelectorAll('.shot-frame')].map(f => { const r = f.getBoundingClientRect(), i = f.querySelector('img');
      return { ratio: +(r.width / r.height).toFixed(3), src: i.getAttribute('src').split('/').pop(), nat: i.naturalWidth, alt: i.alt }; }));
    ck(shots.length === 3 && shots.every(s => Math.abs(s.ratio - 1.6) < 0.01), `${w}px three 16:10 frames: ${shots.map(s => s.ratio).join(', ')}`);
    ck(shots.every(s => s.nat > 0), `${w}px captures load: ${shots.map(s => s.src + ' ' + s.nat + 'px').join(', ')}`);
    ck(shots.every(s => s.alt.length > 40), `${w}px real alt text on all three`);
    const before = await p.evaluate(() => [document.querySelector('.shot-frame').getBoundingClientRect().height, document.querySelector('.shots').nextElementSibling ? document.querySelector('.shots').getBoundingClientRect().bottom + scrollY : 0, document.body.scrollHeight]);
    await p.evaluate(async () => { const i = document.querySelector('.shot-frame img'); i.removeAttribute('width'); i.removeAttribute('height');
      i.src = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1400"><rect width="1600" height="1400" fill="#ccc"/></svg>'); await i.decode(); });
    const after = await p.evaluate(() => [document.querySelector('.shot-frame').getBoundingClientRect().height, document.querySelector('.shots').nextElementSibling ? document.querySelector('.shots').getBoundingClientRect().bottom + scrollY : 0, document.body.scrollHeight]);
    ck(before.every((v, k) => Math.abs(v - after[k]) < 0.5), `${w}px 1600x1400 swap-in: frame ${before[0].toFixed(1)}→${after[0].toFixed(1)}px, page ${before[2]}→${after[2]}px (no shift)`);
    const marks = await p.evaluate(() => ({ wa: [...document.querySelectorAll('.wa-avatar')].map(e => [e.textContent.trim(), e.querySelector('img') && e.querySelector('img').getAttribute('src').split('/').pop(), e.querySelector('img') && e.querySelector('img').naturalWidth]),
      side: [...document.querySelectorAll('.teach-side-mark')].map(e => [e.tagName, e.textContent.trim(), (e.getAttribute('src') || '').split('/').pop(), e.naturalWidth]) }));
    ck(marks.wa.length > 0 && marks.wa.every(([t, s, n]) => t === '' && s === 'klassapp-icon.svg' && n > 0), `${w}px WhatsApp preview avatar is the K icon ${JSON.stringify(marks.wa)}`);
    ck(marks.side.length > 0 && marks.side.every(([tag, t, s, n]) => tag === 'IMG' && t === '' && s === 'klassapp-icon.svg' && n > 0), `${w}px teacher sidebar mark is the K icon ${JSON.stringify(marks.side)}`);
    await p.close();
  }
  const p = await b.newPage();
  for (const [f, want] of [['app-fees.webp', 200], ['app-page.webp', 404]]) { const r = await p.request.get(BASE + 'images/landing/' + f); ck(r.status() === want, `${f} → HTTP ${r.status()} (want ${want})`); }
  await b.close();
  console.log(fails ? `${fails} FAILED` : 'ALL PASS'); process.exit(fails ? 1 : 0);
})();
