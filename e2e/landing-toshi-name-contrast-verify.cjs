/**
 * Landing "Meet Toshi." heading: the green "Toshi" word must clear WCAG AA
 * (4.5:1) against its real rendered background. Was #22C55E on #FAFAF5 = 2.18:1.
 * Viewports: 375, 414, 768, 1280 (AGENTS.md). Exits 1 if any viewport < 4.5.
 *
 *   PREVIEW_BASE=https://klassapp.xyz node e2e/landing-toshi-name-contrast-verify.cjs
 *   (cloud VM: CHROMIUM_PATH=/opt/pw-browsers/chromium-1194/chrome-linux/chrome)
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots/landing-toshi-name-contrast');
fs.mkdirSync(OUT, { recursive: true });

(async () => {
  const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
  const results = [];
  for (const width of [375, 414, 768, 1280]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    await page.goto(BASE + '/', { waitUntil: 'load' });
    const el = page.locator('.toshi-header h2 .toshi-name');
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(800);
    const r = await el.evaluate((node) => {
      const parse = (c) => {
        const m = c.match(/rgba?\(([^)]+)\)/);
        if (!m) return null;
        const p = m[1].split(',').map(parseFloat);
        return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
      };
      // Effective background: nearest ancestor with an opaque-ish fill.
      let bg = null;
      for (let n = node; n && n.nodeType === 1; n = n.parentElement) {
        const c = parse(getComputedStyle(n).backgroundColor);
        if (c && c.a > 0) { bg = c; break; }
      }
      bg = bg || { r: 255, g: 255, b: 255 };
      const fg = parse(getComputedStyle(node).color);
      const lum = ({ r, g, b }) => {
        const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
        return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
      };
      const [hi, lo] = [lum(fg), lum(bg)].sort((x, y) => y - x);
      return {
        color: getComputedStyle(node).color,
        background: `rgb(${bg.r}, ${bg.g}, ${bg.b})`,
        contrast: +((hi + 0.05) / (lo + 0.05)).toFixed(2),
      };
    });
    await page.locator('.toshi-header h2').screenshot({ path: path.join(OUT, `${width}.png`) });
    results.push({ width, ...r, pass: r.contrast >= 4.5 });
    await page.close();
  }
  await browser.close();
  console.log(JSON.stringify({ base: BASE, results }, null, 2));
  process.exit(results.every((r) => r.pass) ? 0 : 1);
})();
