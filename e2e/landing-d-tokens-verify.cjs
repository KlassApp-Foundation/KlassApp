/**
 * Piece 1 marketing alignment — Playwright locks for canonical tokens/type.
 * Viewports match AGENTS.md mobile-first set: 375, 414, 768, 1280+.
 *
 * Usage:
 *   PREVIEW_BASE=http://127.0.0.1:8000 node e2e/landing-d-tokens-verify.cjs
 *   PREVIEW_BASE=https://klassapp-staging-….laravel.cloud node e2e/landing-d-tokens-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots/landing-d-tokens');
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

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = { base: BASE, at: new Date().toISOString(), viewports: {}, ok: true };

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const res = await page.goto(`${BASE}/`, { waitUntil: 'networkidle', timeout: 90000 });
    if (!res || !res.ok()) {
      fail(`${vp.name}: HTTP ${res ? res.status() : 'null'}`);
      report.ok = false;
      await page.close();
      continue;
    }

    await page.evaluate(() => {
      document.querySelectorAll('.reveal').forEach((el) => el.classList.add('visible'));
    });
    await page.waitForTimeout(400);

    const measured = await page.evaluate(() => {
      const body = getComputedStyle(document.body);
      const h1 = getComputedStyle(document.querySelector('h1') || document.body);
      const html = document.documentElement.outerHTML;
      const root = getComputedStyle(document.documentElement);
      return {
        bodyBg: body.backgroundColor,
        bodyColor: body.color,
        bodyFont: body.fontFamily,
        h1Font: h1.fontFamily,
        paperBase: root.getPropertyValue('--paper-base').trim(),
        textPrimary: root.getPropertyValue('--text-primary').trim(),
        fontDisplay: root.getPropertyValue('--font-display').trim(),
        fontBody: root.getPropertyValue('--font-body').trim(),
        hasBricolageLink: /Bricolage/i.test(html),
        hasInterFamilyLink: /family=Inter/i.test(html),
        hasSoraLink: /family=Sora/i.test(html),
        hasDmSansLink: /family=DM\+Sans|family=DM Sans/i.test(html),
      };
    });

    const checks = {
      paperBase: /^#fafaf5$/i.test(measured.paperBase),
      textPrimary: /^#1e293b$/i.test(measured.textPrimary),
      fontDisplaySora: /Sora/i.test(measured.fontDisplay),
      fontBodyDmSans: /DM Sans/i.test(measured.fontBody),
      computedBodySoraOrDm: /DM Sans/i.test(measured.bodyFont),
      computedH1Sora: /Sora/i.test(measured.h1Font),
      noBricolage: !measured.hasBricolageLink,
      noInterLink: !measured.hasInterFamilyLink,
      soraLink: measured.hasSoraLink,
      dmLink: measured.hasDmSansLink,
      // rgb(250, 250, 245) === #FAFAF5
      bodyBgCanvas: measured.bodyBg === 'rgb(250, 250, 245)',
      // rgb(30, 41, 59) === #1E293B
      bodyInk: measured.bodyColor === 'rgb(30, 41, 59)',
    };

    const shot = path.join(OUT, `${vp.name}-hero.png`);
    const hero = page.locator('#hero');
    if (await hero.count()) {
      await hero.first().screenshot({ path: shot });
    } else {
      await page.screenshot({ path: shot, fullPage: false });
    }

    const failed = Object.entries(checks).filter(([, v]) => !v).map(([k]) => k);
    if (failed.length) {
      report.ok = false;
      fail(`${vp.name}: ${failed.join(', ')} — ${JSON.stringify(measured)}`);
    }

    report.viewports[vp.name] = { measured, checks, shot };
    await page.close();
  }

  await browser.close();
  const outJson = path.join(OUT, 'report.json');
  fs.writeFileSync(outJson, JSON.stringify(report, null, 2));
  console.log(JSON.stringify({ ok: report.ok, outJson }, null, 2));
  if (!report.ok) process.exit(1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
