/**
 * Landing footer: X handle https://x.com/Klass_App + new tagline.
 * Viewports: 375, 414, 768, 1280 (AGENTS.md).
 *
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud node e2e/landing-footer-tagline-x-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots/landing-footer-tagline-x');
fs.mkdirSync(OUT, { recursive: true });

const TAGLINE = "Educationists' tools connected by intelligence.";
const X_HREF = 'https://x.com/Klass_App';
const OLD_TAGLINE = 'Smarter schools start here.';
const OLD_X = 'https://x.com/klassapp';

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
    const res = await page.goto(`${BASE}/`, { waitUntil: 'load', timeout: 90000 });
    if (!res || !res.ok()) {
      fail(`${vp.name}: HTTP ${res ? res.status() : 'null'}`);
      report.ok = false;
      await page.close();
      continue;
    }

    await page.locator('footer.site-footer').scrollIntoViewIfNeeded();
    await page.waitForTimeout(200);

    const checks = await page.evaluate(({ tagline, xHref, oldTagline, oldX }) => {
      const footer = document.querySelector('footer.site-footer');
      const tagEl = document.querySelector('.site-footer-tagline');
      const xLink = document.querySelector(`a.site-footer-social[href="${xHref}"]`);
      const oldXLink = document.querySelector(`a.site-footer-social[href="${oldX}"]`);
      const text = document.body.innerText || '';
      return {
        hasFooter: !!footer,
        taglineText: tagEl ? tagEl.textContent.trim() : null,
        taglineOk: !!(tagEl && tagEl.textContent.includes(tagline)),
        xHrefOk: !!xLink,
        xVisible: !!(xLink && xLink.offsetParent !== null),
        oldTaglineGone: !text.includes(oldTagline),
        oldXGone: !oldXLink,
      };
    }, { tagline: TAGLINE, xHref: X_HREF, oldTagline: OLD_TAGLINE, oldX: OLD_X });

    const shot = path.join(OUT, `${vp.name}-footer.png`);
    await page.locator('footer.site-footer').screenshot({ path: shot });

    const vpOk = checks.hasFooter && checks.taglineOk && checks.xHrefOk && checks.oldTaglineGone && checks.oldXGone;
    if (!vpOk) {
      fail(`${vp.name}: ${JSON.stringify(checks)}`);
      report.ok = false;
    } else {
      console.log(`OK ${vp.name}: tagline + X ${X_HREF}`);
    }

    report.viewports[vp.name] = { ...checks, screenshot: shot, ok: vpOk };
    await page.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  if (!report.ok) {
    console.error('FAIL: landing footer tagline/X verify');
    process.exit(1);
  }
  console.log('ALL OK — landing footer tagline + X handle');
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
