/**
 * Piece 1 auth alignment — Playwright locks for canonical tokens + ds-btn metrics + blue focus.
 * Viewports: 375, 414, 768, 1280 (AGENTS.md).
 *
 * Usage:
 *   PREVIEW_BASE=http://127.0.0.1:8000 node e2e/auth-d-tokens-verify.cjs
 *   PREVIEW_BASE=https://klassapp-staging-….laravel.cloud node e2e/auth-d-tokens-verify.cjs
 *
 * Prefer waitUntil "load" (not networkidle) — staging hibernates and long-polls stall networkidle.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots/auth-d-tokens');
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

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const res = await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
    if (!res || !res.ok()) {
      fail(`${vp.name}: HTTP ${res ? res.status() : 'null'}`);
      report.ok = false;
      await page.close();
      continue;
    }

    await page.waitForTimeout(300);

    const measured = await page.evaluate(() => {
      const body = getComputedStyle(document.body);
      const submit = document.querySelector('[data-testid="ap-primary-submit"]');
      const input = document.querySelector('.ap-input');
      const s = submit ? getComputedStyle(submit) : null;
      return {
        canvas: body.backgroundColor,
        ink: body.color,
        submitPad: s ? s.padding : null,
        submitMinH: s ? Math.round(parseFloat(s.minHeight)) : null,
        submitFs: s ? s.fontSize : null,
        submitBg: s ? s.backgroundColor : null,
        submitShadow: s ? s.boxShadow : null,
        hasInput: !!input,
      };
    });

    await page.focus('[data-testid="ap-primary-submit"]');
    await page.waitForTimeout(100);
    const focusStyles = await page.evaluate(() => {
      const s = getComputedStyle(document.querySelector('[data-testid="ap-primary-submit"]'));
      return { outline: s.outline, outlineColor: s.outlineColor, outlineWidth: s.outlineWidth, boxShadow: s.boxShadow };
    });

    await page.locator('.ap-input').first().focus();
    await page.waitForTimeout(100);
    const inputFocus = await page.evaluate(() => {
      const el = document.querySelector('.ap-input');
      const s = el ? getComputedStyle(el) : null;
      return s ? { boxShadow: s.boxShadow, borderColor: s.borderTopColor } : null;
    });

    const shot = path.join(OUT, `login-${vp.name}.png`);
    await page.screenshot({ path: shot, fullPage: true });

    const checks = {
      canvasOk: measured.canvas === rgb(250, 250, 245),
      inkOk: measured.ink === rgb(30, 41, 59),
      padOk: measured.submitPad === '8px 18px',
      minHOk: measured.submitMinH === 44,
      fsOk: measured.submitFs === '13.6px' || measured.submitFs === '0.85rem',
      greenFillOk: measured.submitBg === rgb(34, 197, 94),
      noGreenGlowHoverShadow: !String(measured.submitShadow || '').includes('34, 197, 94'),
      blueFocusOutline:
        focusStyles.outlineColor === rgb(30, 111, 217) ||
        String(focusStyles.outline || '').includes('rgb(30, 111, 217)'),
      inputBlueRing:
        inputFocus &&
        (String(inputFocus.boxShadow).includes('30, 111, 217') ||
          inputFocus.borderColor === rgb(30, 111, 217)),
    };

    const ok = Object.values(checks).every(Boolean);
    if (!ok) {
      fail(`${vp.name}: ${JSON.stringify({ checks, measured, focusStyles, inputFocus })}`);
      report.ok = false;
    }

    report.viewports[vp.name] = { checks, measured, focusStyles, inputFocus, shot, ok };
    await page.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify({ ok: report.ok, base: report.base, viewports: Object.fromEntries(
    Object.entries(report.viewports).map(([k, v]) => [k, { ok: v.ok, checks: v.checks }])
  ) }, null, 2));
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
