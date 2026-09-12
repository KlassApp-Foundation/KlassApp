const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PREVIEW_BASE || 'http://127.0.0.1:8000';
const OUT = path.join(__dirname, 'screenshots/landing-preview-build');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'tablet', width: 1024, height: 768 },
  { name: 'mobile', width: 390, height: 844 },
];

const requiredIds = ['hero', 'connectors', 'toshi', 'how-it-works', 'trust', 'compare', 'faq', 'protocol'];
const absentIds = ['community', 'open-source'];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = { base: BASE, at: new Date().toISOString(), viewports: {}, isolation: {}, ok: true };

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const consoleErrors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });
    page.on('pageerror', (err) => consoleErrors.push(String(err)));

    const res = await page.goto(`${BASE}/landing-preview`, { waitUntil: 'networkidle', timeout: 60000 });
    await page.evaluate(() => {
      document.querySelectorAll('.reveal').forEach((el) => el.classList.add('visible'));
    });
    await page.waitForTimeout(500);

    const ids = await page.evaluate((all) =>
      all.map((id) => ({ id, present: !!document.getElementById(id) })),
      [...requiredIds, ...absentIds]
    );
    const productUi = await page.evaluate(() => {
      const text = document.body.textContent || '';
      return {
        pillars: document.querySelectorAll('.pillar').length,
        provable: text.includes('Provable'),
        protocolCores: text.includes('Protocol Cores'),
        howProtocol: text.includes('One protocol layer')
          && text.includes('protocol orchestration')
          && text.includes('Protocol path')
          && !text.includes('One intelligence layer orchestrating three perspectives'),
        emDash: text.includes('\u2014'),
        q1: text.includes('Q1 2027'),
        vintageHero: !!document.querySelector('.hero-bg-vintage'),
        navPrimaryLogo: !!document.querySelector('.navbar-logo-img[src*="klassapp-logo-primary"]'),
        toshiHubMark: !!document.querySelector('.hub-mark img[src*="klassapp-logo.svg"]'),
        toshiFlowArrows: !!document.querySelector('marker#arrowInGreen'),
        toshiStreaks: document.querySelectorAll('.toshi-line-streak').length >= 5,
        humanInLoop: text.includes('Human in the loop')
          && text.includes('Before consequential writes, Toshi asks for confirmation'),
        protocolMesh: !!document.querySelector('.mesh .mesh-hub'),
        openSourceCard: text.includes('MIT licensed. Source and self-hosting will open publicly after an independent security review'),
        prodFooter: !!document.querySelector('footer.site-footer')
          && text.includes('Smarter schools start here.')
          && !!document.querySelector('.site-footer-wordmark')
          && !text.includes('Stay in the loop')
          && !document.querySelector('.footer-columns'),
        noCommunitySection: !document.getElementById('community') && !document.getElementById('open-source'),
      };
    });

    const isolation = await page.evaluate(() => ({
      dsElements: document.querySelectorAll('[class*="ds-"]').length,
      dVarInInline: (document.documentElement.getAttribute('style') || '').includes('--d-'),
      htmlHasDsKpi: document.documentElement.outerHTML.includes('ds-kpi'),
    }));

    const shot = path.join(OUT, `${vp.name}-full.png`);
    await page.screenshot({ path: shot, fullPage: true });

    for (const [sel, name] of [
      ['#hero', 'hero'],
      ['#toshi', 'toshi'],
      ['#protocol', 'protocol'],
      ['footer.site-footer', 'footer'],
    ]) {
      const el = await page.$(sel);
      if (el) {
        await el.screenshot({ path: path.join(OUT, `${vp.name}-${name}.png`) });
      }
    }

    const requiredPresent = ids.filter((x) => requiredIds.includes(x.id)).every((x) => x.present);
    const absentGone = ids.filter((x) => absentIds.includes(x.id)).every((x) => !x.present);

    const entry = {
      status: res.status(),
      consoleErrors,
      ids,
      productUi,
      isolation,
      screenshot: shot,
    };
    report.viewports[vp.name] = entry;

    if (
      res.status() !== 200
      || consoleErrors.length
      || !requiredPresent
      || !absentGone
      || productUi.pillars < 5
      || productUi.emDash
      || productUi.q1
      || !productUi.provable
      || !productUi.protocolCores
      || !productUi.howProtocol
      || !productUi.vintageHero
      || !productUi.navPrimaryLogo
      || !productUi.toshiHubMark
      || !productUi.toshiFlowArrows
      || !productUi.toshiStreaks
      || !productUi.humanInLoop
      || !productUi.protocolMesh
      || !productUi.openSourceCard
      || !productUi.prodFooter
      || !productUi.noCommunitySection
      || isolation.dsElements > 0
      || isolation.htmlHasDsKpi
    ) {
      report.ok = false;
    }
    await page.close();
  }

  const page = await browser.newPage();
  for (const url of ['/preview/login', '/preview/errors/404']) {
    const r = await page.goto(`${BASE}${url}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    report[url] = { status: r.status() };
    if (r.status() !== 200) report.ok = false;
  }
  await browser.close();

  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
