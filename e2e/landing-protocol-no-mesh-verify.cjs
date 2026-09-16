/**
 * Protocol section: mesh/icon under "Not just software. A protocol." removed.
 * Viewports: 375, 414, 768, 1280 (AGENTS.md).
 *
 *   PREVIEW_BASE=http://127.0.0.1:8000 node e2e/landing-protocol-no-mesh-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = path.join(__dirname, 'screenshots/landing-protocol-no-mesh');
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
    await page.waitForTimeout(300);

    const measured = await page.evaluate(() => {
      const section = document.getElementById('protocol');
      const h2 = section && section.querySelector('h2');
      const header = section && section.querySelector('.protocol-header');
      const visual = section && section.querySelector('.protocol-visual');
      const mesh = section && section.querySelector('.mesh');
      const meshHub = section && section.querySelector('.mesh-hub');
      const cards = section ? section.querySelectorAll('.protocol-card') : [];
      const rect = section ? section.getBoundingClientRect() : null;
      const headerRect = header ? header.getBoundingClientRect() : null;
      return {
        hasSection: !!section,
        heading: h2 ? h2.textContent.trim() : null,
        hasVisual: !!visual,
        hasMesh: !!mesh,
        hasMeshHub: !!meshHub,
        cardCount: cards.length,
        sectionHeight: rect ? rect.height : 0,
        headerVisible: headerRect ? headerRect.height > 0 && headerRect.width > 0 : false,
        htmlHasMeshClass: /class="mesh"/.test(document.documentElement.outerHTML),
        htmlHasProtocolVisual: /protocol-visual/.test(document.documentElement.outerHTML),
      };
    });

    const checks = {
      hasSection: measured.hasSection,
      headingOk: measured.heading === 'Not just software. A protocol.',
      noVisual: !measured.hasVisual,
      noMesh: !measured.hasMesh,
      noMeshHub: !measured.hasMeshHub,
      noMeshInHtml: !measured.htmlHasMeshClass,
      noVisualInHtml: !measured.htmlHasProtocolVisual,
      threeCards: measured.cardCount === 3,
      sectionRenders: measured.sectionHeight > 80,
      headerVisible: measured.headerVisible,
    };

    const failed = Object.entries(checks).filter(([, v]) => !v).map(([k]) => k);
    if (failed.length) {
      fail(`${vp.name}: ${failed.join(', ')}`);
      report.ok = false;
    }

    await page.locator('#protocol').screenshot({ path: path.join(OUT, `${vp.name}-protocol.png`) }).catch(() => {});
    report.viewports[vp.name] = { checks, failed, measured };
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
