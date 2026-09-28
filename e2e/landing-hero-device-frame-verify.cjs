/**
 * Landing hero device frame — exits 1 on any failure.
 *
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud node e2e/landing-hero-device-frame-verify.cjs
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium)
 *
 * >=901px: dark window (radius 22, padding 16, chrome visible, 14px base) that fills
 *   its column, with an address bar tracking the active card's tool through a flip cycle.
 * <=900px: 280x560 phone (radius 40, padding 12), chrome/base hidden; a card taller than
 *   the screen scrolls inside it (the phone never grows). No horizontal scroll.
 */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/';
const HOST = { parent: 'web.whatsapp.com', teacher: 'drive.google.com', admin: 'app.slack.com' };
let fails = 0;
const ck = (ok, m) => { console.log((ok ? 'PASS ' : 'FAIL ') + m); if (!ok) fails++; };
(async () => {
  const b = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
  for (const w of [320, 375, 414, 768, 900, 901, 1280, 1920]) {
    const p = await b.newPage({ viewport: { width: w, height: 900 } });
    await p.goto(BASE, { waitUntil: 'networkidle' });
    const r = await p.evaluate(() => {
      const fr = document.querySelector('.hero-device-frame'), cs = getComputedStyle(fr), f = fr.getBoundingClientRect();
      const col = document.querySelector('.hero-preview').getBoundingClientRect();
      const base = document.querySelector('.hero-device-base').getBoundingClientRect();
      return { sw: document.documentElement.scrollWidth, iw: innerWidth, w: Math.round(f.width), h: Math.round(f.height), col: Math.round(col.width),
        radius: cs.borderRadius, pad: cs.paddingTop, chrome: getComputedStyle(document.querySelector('.hero-device-chrome')).display, baseH: Math.round(base.height),
        cards: [...document.querySelectorAll('.hero-role-card')].map(c => ({ role: c.dataset.role, sh: c.scrollHeight, ch: c.clientHeight, oy: getComputedStyle(c).overflowY })) };
    });
    ck(r.sw <= r.iw, `${w}px no horizontal scroll (${r.sw}/${r.iw})`);
    if (w <= 900) {
      ck(r.w === 280 && r.h === 560 && r.radius === '40px' && r.pad === '12px' && r.chrome === 'none', `${w}px phone ${r.w}x${r.h} r${r.radius} p${r.pad} chrome:${r.chrome}`);
      for (const c of r.cards) ck(c.sh <= c.ch || c.oy === 'auto', `${w}px ${c.role} card ${c.sh}px content in ${c.ch}px screen → ${c.sh > c.ch ? 'scrolls inside (overflow-y ' + c.oy + ')' : 'fits'}`);
    } else {
      ck(r.radius === '22px' && r.pad === '16px' && r.chrome === 'flex' && r.baseH === 14 && r.w === r.col, `${w}px window ${r.w}px = column ${r.col}px, r${r.radius} p${r.pad} chrome:${r.chrome} base ${r.baseH}px`);
      const seen = [];
      for (let k = 0; k < 4; k++) {
        seen.push(await p.evaluate(() => [document.getElementById('heroDeviceUrl').textContent, document.querySelector('.hero-role-card.is-active').dataset.role]));
        await p.waitForTimeout(3300);
      }
      ck(seen.every(([u, role]) => u === HOST[role]) && new Set(seen.map(s => s[1])).size === 3, `${w}px address bar tracks flip: ${seen.map(s => s.join('@')).join(' → ')}`);
    }
    await p.close();
  }
  await b.close();
  console.log(fails ? `${fails} FAILED` : 'ALL PASS'); process.exit(fails ? 1 : 0);
})();
