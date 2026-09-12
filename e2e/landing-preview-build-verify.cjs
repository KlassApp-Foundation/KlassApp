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
    // reveal all
    await page.evaluate(() => {
      document.querySelectorAll('.reveal').forEach((el) => el.classList.add('visible'));
    });
    await page.waitForTimeout(400);

    const ids = await page.evaluate(() =>
      ['hero', 'connectors', 'toshi', 'how-it-works', 'trust', 'compare', 'community', 'faq', 'protocol', 'open-source']
        .map((id) => ({ id, present: !!document.getElementById(id) }))
    );
    const productUi = await page.evaluate(() => ({
      uiChrome: document.querySelectorAll('.ui-chrome').length,
      waBubble: document.querySelectorAll('.wa-bubble').length,
      teachShell: document.querySelectorAll('.teach-shell').length,
      adminStack: document.querySelectorAll('.admin-stack').length,
      pillars: document.querySelectorAll('.pillar').length,
      provable: (document.body.textContent || '').includes('Provable'),
      compare: !!document.getElementById('compare'),
      faq: !!document.getElementById('faq'),
      emDash: (document.body.textContent || '').includes('\u2014'),
      q1: (document.body.textContent || '').includes('Q1 2027'),
    }));

    // isolation: no --d-* computed vars on body / no .ds-* elements
    const isolation = await page.evaluate(() => {
      const styles = getComputedStyle(document.documentElement);
      const cssText = Array.from(document.styleSheets)
        .map((s) => {
          try {
            return Array.from(s.cssRules || [])
              .map((r) => r.cssText)
              .join('\n');
          } catch (e) {
            return '';
          }
        })
        .join('\n');
      return {
        dsElements: document.querySelectorAll('[class*="ds-"]').length,
        dVarInInline: (document.documentElement.getAttribute('style') || '').includes('--d-'),
        dVarInLandingCss: /--d-[a-z]/.test(cssText) && cssText.includes('landing-preview'),
        // simpler: page HTML shouldn't reference ds-kpi
        htmlHasDsKpi: document.documentElement.outerHTML.includes('ds-kpi'),
      };
    });

    const shot = path.join(OUT, `${vp.name}-full.png`);
    await page.screenshot({ path: shot, fullPage: true });

    const entry = {
      status: res.status(),
      consoleErrors,
      ids,
      productUi,
      isolation,
      screenshot: shot,
    };
    report.viewports[vp.name] = entry;
    if (res.status() !== 200 || consoleErrors.length || ids.some((x) => !x.present) || productUi.pillars < 5 || productUi.emDash || productUi.q1 || !productUi.provable) {
      report.ok = false;
    }
    if (isolation.dsElements > 0 || isolation.htmlHasDsKpi) report.ok = false;
    await page.close();
  }

  // Auth + errors smoke (preview routes still up)
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
