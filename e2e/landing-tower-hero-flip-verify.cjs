/**
 * Landing Toshi tower + hero X-flip — Playwright locks at AGENTS.md viewports.
 *
 * Usage:
 *   PREVIEW_BASE=http://127.0.0.1:8000 node e2e/landing-tower-hero-flip-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots/landing-tower-hero-flip');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 800 },
];

const MARKS = [
  'anthropic-mark.svg',
  'openai-mark.svg',
  'xai-grok-mark.svg',
  'google-gemini-mark.svg',
  'moonshot-kimi-mark.svg',
  'zhipu-zai-mark.svg',
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

    await page.evaluate(() => {
      document.querySelectorAll('.reveal').forEach((el) => el.classList.add('visible'));
    });
    await page.waitForTimeout(500);

    const measured = await page.evaluate((marks) => {
      const tower = document.getElementById('toshiTower');
      const deck = document.getElementById('heroRoleDeck');
      const cards = deck ? Array.from(deck.querySelectorAll('.hero-role-card')) : [];
      const active = cards.find((c) => c.classList.contains('is-active'));
      const avatar = active && active.querySelector('.hero-role-avatar');
      const avatarImg = avatar && avatar.querySelector('img');
      const avStyle = avatar ? getComputedStyle(avatar) : null;
      const cardStyle = active ? getComputedStyle(active) : null;
      const html = document.documentElement.outerHTML;

      const markHrefs = marks.map((m) => {
        const sel = `image[href*="${m}"], img[src*="${m}"]`;
        const el = document.querySelector(sel);
        return { mark: m, present: !!el };
      });

      // Sample one mark URL for HTTP check via absolute path in href
      const firstImage = tower && tower.querySelector('image[href*="brand/models/"]');
      const firstHref = firstImage ? firstImage.getAttribute('href') : null;

      return {
        hasTower: !!tower,
        towerVisible: tower ? getComputedStyle(tower).display !== 'none' : false,
        towerHeight: tower ? tower.getBoundingClientRect().height : 0,
        hasDeck: !!deck,
        cardCount: cards.length,
        activeRotate: cardStyle ? cardStyle.transform : null,
        avatarBorder: avStyle ? avStyle.borderColor : null,
        avatarBg: avStyle ? avStyle.backgroundColor : null,
        avatarRadius: avStyle ? avStyle.borderRadius : null,
        avatarImgSrc: avatarImg ? avatarImg.getAttribute('src') : null,
        hasDeepseek: /deepseek/i.test(html),
        hasOldHub: /toshi-visual-hub/.test(html),
        markHrefs,
        firstHref,
        cssHasDVar: !!document.documentElement.innerHTML.match(/var\(--d-/),
      };
    }, MARKS);

    // Reduced-motion page
    const rmContext = await browser.newPage({
      viewport: { width: vp.width, height: vp.height },
      reducedMotion: 'reduce',
    });
    await rmContext.goto(`${BASE}/`, { waitUntil: 'load', timeout: 90000 });
    await rmContext.evaluate(() => {
      document.querySelectorAll('.reveal').forEach((el) => el.classList.add('visible'));
    });
    const rm = await rmContext.evaluate(() => {
      const active = document.querySelector('.hero-role-card.is-active');
      const inactive = document.querySelector('.hero-role-card:not(.is-active)');
      const a = active ? getComputedStyle(active) : null;
      const b = inactive ? getComputedStyle(inactive) : null;
      const mo = document.querySelector('.toshi-tower .mo-1');
      const moAnim = mo ? getComputedStyle(mo).animationName : null;
      return {
        activeOpacity: a ? a.opacity : null,
        activeTransition: a ? a.transitionProperty : null,
        inactiveOpacity: b ? b.opacity : null,
        moAnimation: moAnim,
      };
    });
    await rmContext.close();

    // Mark HTTP 200
    const markStatuses = {};
    for (const m of MARKS) {
      const url = `${BASE}/images/brand/models/${m}`;
      const r = await page.request.get(url);
      markStatuses[m] = r.status();
      if (r.status() !== 200) {
        fail(`${vp.name}: mark ${m} → HTTP ${r.status()}`);
        report.ok = false;
      }
    }

    const checks = {
      hasTower: measured.hasTower,
      towerVisible: measured.towerVisible,
      towerHasHeight: measured.towerHeight > 80,
      hasDeck: measured.hasDeck,
      threeCards: measured.cardCount === 3,
      avatarTransparent: measured.avatarBg === 'rgba(0, 0, 0, 0)' || measured.avatarBg === 'transparent',
      avatarRound: measured.avatarRadius === '50%' || parseFloat(measured.avatarRadius) >= 16,
      avatarK: measured.avatarImgSrc && /klassapp-icon\.svg/.test(measured.avatarImgSrc),
      noDeepseek: !measured.hasDeepseek,
      noOldHub: !measured.hasOldHub,
      allMarksInDom: measured.markHrefs.every((x) => x.present),
      marksHttpOk: Object.values(markStatuses).every((s) => s === 200),
      rmActiveVisible: rm.activeOpacity === '1',
      rmInactiveHidden: rm.inactiveOpacity === '0',
      rmNoTowerAnim: !rm.moAnimation || rm.moAnimation === 'none',
    };

    const failed = Object.entries(checks).filter(([, v]) => !v).map(([k]) => k);
    if (failed.length) {
      fail(`${vp.name}: ${failed.join(', ')}`);
      report.ok = false;
    }

    await page.locator('#toshiTower').screenshot({ path: path.join(OUT, `${vp.name}-tower.png`) }).catch(() => {});
    await page.locator('.hero-preview').screenshot({ path: path.join(OUT, `${vp.name}-hero.png`) }).catch(() => {});

    report.viewports[vp.name] = { checks, failed, measured, rm, markStatuses };
    console.log(vp.name, failed.length ? `FAIL ${failed.join(',')}` : 'OK');
    await page.close();
  }

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(report.ok ? 'ALL OK' : 'SOME FAILED', OUT);
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
