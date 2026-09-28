/**
 * Landing muted-text AA scan. For every rendered element with its own text in the
 * landing greys (#94A3B8 --text-muted, #64748B --text-secondary, #1E293B) or the
 * honesty-line amber (#B45309), compute contrast against its real background
 * (colour layers blended, color-mix() resolved). Exits 1 if any VISIBLE one is < 4.5:1.
 *
 *   PREVIEW_BASE=https://klassapp.xyz node e2e/landing-muted-text-aa-verify.cjs
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium)
 */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/';
let failed = 0;
const TARGETS = ['rgb(148, 163, 184)', 'rgb(100, 116, 139)', 'rgb(30, 41, 59)', 'rgb(180, 83, 9)'];
(async () => { const b = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
 for (const w of [375, 414, 768, 1280]) {
  const p = await b.newPage({ viewport: { width: w, height: 900 } });
  await p.goto(BASE, { waitUntil: 'networkidle' });
  await p.evaluate(() => document.querySelectorAll('.reveal,.how-step').forEach(e => e.classList.add('visible')));
  await p.waitForTimeout(800);
  const rows = await p.evaluate((TARGETS) => {
    const P = s => { const m = s.match(/[\d.]+/g).map(Number); if (s.startsWith('color(srgb')) return { r: m[0] * 255, g: m[1] * 255, b: m[2] * 255, a: m.length > 3 ? m[3] : 1 }; return { r: m[0], g: m[1], b: m[2], a: m.length > 3 ? m[3] : 1 }; };
    const L = c => [c.r, c.g, c.b].map(v => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); }).reduce((a, v, i) => a + v * [.2126, .7152, .0722][i], 0);
    const over = (t, u) => ({ r: t.r * t.a + u.r * (1 - t.a), g: t.g * t.a + u.g * (1 - t.a), b: t.b * t.a + u.b * (1 - t.a), a: 1 });
    const out = {};
    for (const el of document.querySelectorAll('body *')) {
      const cs = getComputedStyle(el); if (!TARGETS.includes(cs.color)) continue;
      const own = [...el.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent.trim()).join(' ').trim(); if (!own) continue;
      const r = el.getBoundingClientRect(); const vis = r.width > 0 && r.height > 0 && cs.visibility !== 'hidden';
      const layers = []; let n = el, img = false; while (n && n.nodeType === 1) { const s = getComputedStyle(n); if (s.backgroundImage !== 'none') img = true; const bg = P(s.backgroundColor); if (bg.a > 0) layers.push(bg); if (bg.a === 1) break; n = n.parentElement; }
      let base = { r: 255, g: 255, b: 255, a: 1 }; for (let i = layers.length - 1; i >= 0; i--) base = over(layers[i], base);
      const fg = over(P(cs.color), base); const [hi, lo] = [L(fg), L(base)].sort((a, b) => b - a);
      const key = el.tagName.toLowerCase() + '.' + [...el.classList].join('.') + ' <' + (el.parentElement.className || el.parentElement.tagName).toString().split(' ')[0] + '>';
      (out[key] = out[key] || { n: 0, vis: 0, sample: own.slice(0, 60), bg: `rgb(${Math.round(base.r)},${Math.round(base.g)},${Math.round(base.b)})`, ratio: +((hi + .05) / (lo + .05)).toFixed(2), gradientBg: img, size: cs.fontSize }).n++;
      if (vis) out[key].vis++;
    } return out; }, TARGETS);
  console.log('== width', w); for (const [k, v] of Object.entries(rows)) { const bad = v.vis > 0 && v.ratio < 4.5; if (bad) failed++; console.log((bad ? 'FAIL ' : 'ok   ') + `${v.ratio}:1 x${v.n} vis${v.vis} ${v.size} bg=${v.bg}${v.gradientBg ? ' (+gradient)' : ''}  ${k}  "${v.sample}"`); }
  await p.close(); } await b.close(); console.log(failed ? `${failed} FAILED` : 'ALL PASS (>= 4.5:1)'); process.exit(failed ? 1 : 0); })();
