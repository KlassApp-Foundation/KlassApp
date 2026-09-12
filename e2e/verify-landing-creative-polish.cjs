/**
 * Verify hero role-rotate + Toshi hub polish across breakpoints.
 * Usage: node e2e/verify-landing-creative-polish.cjs [baseUrl]
 */
const { chromium, devices } = require('playwright');
const fs = require('fs');
const path = require('path');

const baseUrl = process.argv[2] || 'http://127.0.0.1:8000';
const outDir = path.join(__dirname, 'screenshots', 'landing-creative-polish');
fs.mkdirSync(outDir, { recursive: true });

function report(name, ok, detail) {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
  return ok;
}

async function measurePerf(page) {
  return page.evaluate(() => {
    const nav = performance.getEntriesByType('navigation')[0];
    const paints = performance.getEntriesByType('paint');
    const fcp = paints.find((p) => p.name === 'first-contentful-paint');
    const css = [...document.querySelectorAll('link[rel="stylesheet"]')]
      .map((l) => l.href)
      .filter((h) => h.includes('landing-preview'));
    const js = [...document.querySelectorAll('script[src]')]
      .map((s) => s.src)
      .filter((h) => h.includes('landing-preview'));
    return {
      domContentLoaded: nav ? Math.round(nav.domContentLoadedEventEnd) : null,
      loadEvent: nav ? Math.round(nav.loadEventEnd) : null,
      fcp: fcp ? Math.round(fcp.startTime) : null,
      transferSize: nav ? nav.transferSize : null,
      css,
      js,
    };
  });
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  let failures = 0;

  // Desktop
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
    await page.goto(baseUrl + '/', { waitUntil: 'networkidle', timeout: 60000 });
    await page.waitForTimeout(500);
    const perf = await measurePerf(page);
    fs.writeFileSync(path.join(outDir, 'perf-desktop.json'), JSON.stringify(perf, null, 2));
    console.log('PERF desktop', JSON.stringify(perf));

    const hero = await page.evaluate(() => {
      const deck = document.getElementById('heroRoleDeck');
      const cards = [...document.querySelectorAll('.hero-role-card')];
      const active = document.querySelector('.hero-role-card.is-active');
      return {
        deck: !!deck,
        cardCount: cards.length,
        activeRole: active?.dataset.role || null,
        mist: getComputedStyle(document.querySelector('.toshi-visual'), '::before').backgroundImage || null,
      };
    });
    await page.locator('#hero').screenshot({ path: path.join(outDir, 'desktop-hero.png') });
    // Rotation may already have advanced past parent by the time Playwright evaluates;
    // require deck structure + a valid active role, then assert auto-rotate separately.
    const roleOk = ['parent', 'teacher', 'admin'].includes(hero.activeRole);
    if (!report('desktop hero deck present', hero.deck && hero.cardCount === 3 && roleOk, JSON.stringify(hero))) failures++;

    // Wait for rotation to teacher
    await page.waitForTimeout(3500);
    const afterRotate = await page.evaluate(() => document.querySelector('.hero-role-card.is-active')?.dataset.role);
    await page.locator('#hero').screenshot({ path: path.join(outDir, 'desktop-hero-rotated.png') });
    if (!report('desktop hero auto-rotates', afterRotate && afterRotate !== 'parent', 'role=' + afterRotate)) failures++;

    await page.locator('#toshi').scrollIntoViewIfNeeded();
    await page.waitForTimeout(600);
    const toshi = await page.evaluate(() => {
      const v = document.querySelector('.toshi-visual');
      const s = getComputedStyle(v);
      const ico = document.querySelector('.channel-ico');
      return {
        backdrop: s.backdropFilter || s.webkitBackdropFilter,
        boxShadow: s.boxShadow,
        hasIco: !!ico,
        icoBg: ico ? getComputedStyle(ico).backgroundImage : null,
      };
    });
    await page.locator('#toshi .toshi-visual').screenshot({ path: path.join(outDir, 'desktop-toshi-hub.png') });
    if (!report('desktop toshi glass + filled tiles', /blur/i.test(toshi.backdrop || '') && toshi.hasIco && /gradient/i.test(toshi.icoBg || ''), JSON.stringify(toshi))) failures++;

    const social = await page.evaluate(() => {
      return [...document.querySelectorAll('.site-footer-social')].map((a) => a.getAttribute('href'));
    });
    const socialOk = social.every((h) => h && h !== '#' && !h.endsWith('#'));
    if (!report('footer socials not dead #', socialOk && social.some((h) => h.includes('x.com/klassapp')) && social.some((h) => h.includes('github.com')), JSON.stringify(social))) failures++;

    await page.close();
  }

  // Mobile
  {
    const ctx = await browser.newContext({ ...devices['iPhone 13'], deviceScaleFactor: 1 });
    const page = await ctx.newPage();
    await page.goto(baseUrl + '/', { waitUntil: 'networkidle', timeout: 60000 });
    await page.waitForTimeout(400);
    const order = await page.evaluate(() => {
      const content = document.querySelector('.hero-content');
      const preview = document.querySelector('.hero-preview');
      return { ok: content.getBoundingClientRect().top < preview.getBoundingClientRect().top - 8 };
    });
    await page.locator('#hero').screenshot({ path: path.join(outDir, 'mobile-hero.png') });
    if (!report('mobile text above preview', order.ok, JSON.stringify(order))) failures++;
    await page.locator('#toshi').scrollIntoViewIfNeeded();
    await page.waitForTimeout(400);
    await page.locator('#toshi .toshi-visual').screenshot({ path: path.join(outDir, 'mobile-toshi-hub.png') });
    await page.close();
  }

  // Reduced motion
  {
    const ctx = await browser.newContext({
      viewport: { width: 1280, height: 800 },
      deviceScaleFactor: 1,
      reducedMotion: 'reduce',
    });
    const page = await ctx.newPage();
    await page.goto(baseUrl + '/', { waitUntil: 'networkidle', timeout: 60000 });
    await page.waitForTimeout(4000);
    const rm = await page.evaluate(() => {
      const active = document.querySelector('.hero-role-card.is-active')?.dataset.role;
      const dotsDisplay = getComputedStyle(document.getElementById('heroRoleDots')).display;
      const roles = [...document.querySelectorAll('.hero-role-card.is-active')].map((c) => c.dataset.role);
      return { active, dotsDisplay, roles };
    });
    await page.locator('#hero').screenshot({ path: path.join(outDir, 'reduced-motion-hero.png') });
    if (!report('reduced-motion stays on parent + hides dots', rm.active === 'parent' && rm.dotsDisplay === 'none', JSON.stringify(rm))) failures++;
    await page.close();
  }

  // Legal pages substance
  {
    const page = await browser.newPage();
    for (const [pathName, minLen] of [['/terms-of-service', 3000], ['/privacy-policy', 1500]]) {
      await page.goto(baseUrl + pathName, { waitUntil: 'domcontentloaded', timeout: 30000 });
      const text = await page.evaluate(() => document.body.innerText.replace(/\s+/g, ' ').trim());
      const ok = text.length >= minLen;
      if (!report(`legal ${pathName} has real copy (>=${minLen} chars)`, ok, 'len=' + text.length)) failures++;
    }
    await page.close();
  }

  await browser.close();
  console.log(failures ? `\n${failures} failure(s)` : '\nAll creative polish checks passed');
  process.exit(failures ? 1 : 0);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
