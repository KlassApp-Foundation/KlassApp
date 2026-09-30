const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'https://test.klassapp.xyz').replace(/\/$/, '');
const EMAIL = process.env.VERIFY_EMAIL;
const PASSWORD = process.env.VERIFY_PASSWORD;
const EXPECT = 'uploads/male.png';

(async () => {
  const outDir = path.join(process.cwd(), 'e2e/screenshots/pr903-default-avatar');
  fs.mkdirSync(outDir, { recursive: true });

  const viewports = [
    { name: '375', width: 375, height: 812 },
    { name: '1280', width: 1280, height: 800 },
  ];

  const report = { pass: true, base: BASE, viewports: {}, consoleErrors: [], pageErrors: [] };
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();
  page.on('console', (m) => { if (m.type() === 'error') report.consoleErrors.push(m.text()); });
  page.on('pageerror', (e) => report.pageErrors.push(String(e)));

  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#email', { timeout: 30000 });
  await page.fill('#email', EMAIL);
  await page.fill('#password', PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForTimeout(4000);

  report.afterLoginUrl = page.url();
  if (/login/i.test(report.afterLoginUrl)) {
    report.pass = false;
    report.loginFailed = true;
    report.formState = await page.evaluate(() => ({
      email: document.querySelector('#email')?.value || '',
      pwLen: (document.querySelector('#password')?.value || '').length,
      bodyText: document.body.innerText.slice(0, 400),
    }));
    console.log(JSON.stringify(report, null, 2));
    await browser.close();
    process.exit(1);
  }

  const target = report.afterLoginUrl;

  for (const vp of viewports) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(3000);

    if (vp.name === '375') {
      const burger = page.locator('button.md\\:hidden').first();
      if (await burger.count()) {
        await burger.click({ timeout: 5000 }).catch(() => {});
        await page.waitForTimeout(1500);
      }
    }

    const result = await page.evaluate((needle) => {
      const imgs = Array.from(document.querySelectorAll('img'));
      const srcs = imgs.map((i) => i.getAttribute('src') || '');
      const matches = imgs.filter((i) => {
        const s = i.getAttribute('src') || '';
        return s.includes(needle) || /avatar|profile-photo|male\.png|female\.png|default-user/.test(s);
      });
      const avatar = matches.find((i) => i.offsetParent !== null && i.getBoundingClientRect().width > 0)
        || matches[0]
        || null;

      if (!avatar) {
        return { found: false, url: location.href, imgCount: imgs.length, srcs: srcs.slice(0, 25) };
      }
      const src = avatar.getAttribute('src') || '';
      return {
        found: true,
        url: location.href,
        src,
        naturalWidth: avatar.naturalWidth,
        naturalHeight: avatar.naturalHeight,
        complete: avatar.complete,
        hasXAmz: src.includes('X-Amz'),
        hasBucketHost: /r2\.cloudflarestorage\.com|laravel\.cloud/.test(src),
        isAppHost: src.startsWith('https://test.klassapp.xyz/uploads/'),
        imgCount: imgs.length,
      };
    }, EXPECT);

    const shot = path.join(outDir, `avatar-${vp.name}.png`);
    await page.screenshot({ path: shot, fullPage: false });

    let closeShot = null;
    if (result.found) {
      const handle = await page.evaluateHandle((needle) => {
        const imgs = Array.from(document.querySelectorAll('img'));
        const matches = imgs.filter((i) => (i.getAttribute('src') || '').includes(needle));
        return matches.find((i) => i.offsetParent !== null && i.getBoundingClientRect().width > 0)
          || matches[0]
          || null;
      }, EXPECT);
      const el = handle.asElement();
      if (el) {
        try {
          await el.scrollIntoViewIfNeeded({ timeout: 5000 });
          closeShot = path.join(outDir, `avatar-closeup-${vp.name}.png`);
          await el.screenshot({ path: closeShot, timeout: 10000 });
        } catch (e) {
          report.pageErrors.push(`[${vp.name}] closeup: ${e.message}`);
        }
      }
    }
    if (closeShot) result.closeShot = closeShot;

    const pass = result.found
      && result.isAppHost
      && !result.hasXAmz
      && !result.hasBucketHost
      && result.naturalWidth > 0;

    if (!pass) report.pass = false;
    report.viewports[vp.name] = { ...result, shot, pass };
  }

  console.log(JSON.stringify(report, null, 2));
  await browser.close();
  process.exit(report.pass ? 0 : 1);
})();
