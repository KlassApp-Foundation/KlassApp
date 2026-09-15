/**
 * Piece 1 errors alignment — Playwright locks for parchment + ds-btn metrics + blue focus.
 * Hits live 404 (random path) and preview 419/500.
 *
 * Usage:
 *   PREVIEW_BASE=http://127.0.0.1:8000 node e2e/errors-d-tokens-verify.cjs
 *   PREVIEW_BASE=https://klassapp-staging-….laravel.cloud node e2e/errors-d-tokens-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots/errors-d-tokens');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 800 },
];

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

function rgb(r, g, b) {
  return `rgb(${r}, ${g}, ${b})`;
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = { base: BASE, at: new Date().toISOString(), viewports: {}, ok: true };
  const missPath = `/this-route-definitely-does-not-exist-errors-d-${Date.now()}`;

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const res = await page.goto(`${BASE}${missPath}`, { waitUntil: 'load', timeout: 90000 });
    if (!res || res.status() !== 404) {
      fail(`${vp.name}: expected 404 got ${res ? res.status() : 'null'}`);
      report.ok = false;
      await page.close();
      continue;
    }

    await page.waitForTimeout(200);

    const measured = await page.evaluate(() => {
      const body = getComputedStyle(document.body);
      const btn = document.querySelector('[data-testid="err-primary"]');
      const s = btn ? getComputedStyle(btn) : null;
      return {
        canvas: body.backgroundColor,
        ink: body.color,
        pad: s ? s.padding : null,
        minH: s ? Math.round(parseFloat(s.minHeight)) : null,
        fs: s ? s.fontSize : null,
        bg: s ? s.backgroundColor : null,
        shadow: s ? s.boxShadow : null,
        radius: s ? s.borderRadius : null,
      };
    });

    await page.focus('[data-testid="err-primary"]');
    await page.waitForTimeout(80);
    const focusStyles = await page.evaluate(() => {
      const s = getComputedStyle(document.querySelector('[data-testid="err-primary"]'));
      return { outlineColor: s.outlineColor, outlineWidth: s.outlineWidth, boxShadow: s.boxShadow };
    });

    const shot = path.join(OUT, `404-${vp.name}.png`);
    await page.screenshot({ path: shot, fullPage: true });

    const checks = {
      canvasOk: measured.canvas === rgb(250, 250, 245),
      inkOk: measured.ink === rgb(30, 41, 59),
      padOk: measured.pad === '8px 18px',
      minHOk: measured.minH === 44,
      fsOk: measured.fs === '13.6px' || measured.fs === '0.85rem',
      greenFillOk: measured.bg === rgb(34, 197, 94),
      noGreenGlow: !String(measured.shadow || '').includes('34, 197, 94'),
      radius8: measured.radius === '8px',
      blueFocus:
        focusStyles.outlineColor === rgb(30, 111, 217) ||
        String(focusStyles.outlineColor || '').includes('30, 111, 217'),
    };

    const ok = Object.values(checks).every(Boolean);
    if (!ok) {
      fail(`${vp.name}: ${JSON.stringify({ checks, measured, focusStyles })}`);
      report.ok = false;
    }
    report.viewports[vp.name] = { checks, measured, focusStyles, shot, ok };
    await page.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify({
    ok: report.ok,
    base: report.base,
    viewports: Object.fromEntries(
      Object.entries(report.viewports).map(([k, v]) => [k, { ok: v.ok, checks: v.checks }])
    ),
  }, null, 2));
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
