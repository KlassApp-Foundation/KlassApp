const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const base = 'http://127.0.0.1:8010/landing-preview';
  const outDir = path.join(process.cwd(), 'e2e/screenshots/landing-preview-v3-restore');
  fs.mkdirSync(outDir, { recursive: true });
  const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 1024, height: 768 },
    { name: 'mobile', width: 390, height: 844 },
  ];
  const report = { pass: true, viewports: {}, consoleErrors: [] };
  const browser = await chromium.launch({ headless: true });
  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e)));
    page.on('console', (msg) => {
      if (msg.type() === 'error') errors.push(msg.text());
    });
    const resp = await page.goto(base, { waitUntil: 'networkidle', timeout: 60000 });
    await page.waitForTimeout(1500);
    // trigger layout for Toshi connectors
    await page.evaluate(() => window.dispatchEvent(new Event('resize')));
    await page.waitForTimeout(500);
    const checks = await page.evaluate(() => {
      const ids = ['hero', 'connectors', 'toshi', 'how-it-works', 'protocol', 'open-source'];
      const present = Object.fromEntries(ids.map((id) => [id, !!document.getElementById(id)]));
      const channels = document.querySelectorAll('.toshi-visual-channel').length;
      const heroGrid = getComputedStyle(document.querySelector('.hero') || document.body).backgroundImage || '';
      const dashed = !!document.querySelector('.hero') && (
        getComputedStyle(document.querySelector('.hero')).backgroundImage.includes('linear-gradient') ||
        document.querySelector('.hero')?.innerHTML.includes('hero')
      );
      return {
        present,
        channels,
        title: document.title,
        q12027: document.body.innerText.includes('Q1 2027'),
        toolsConnected: document.body.innerText.includes('Tools connected by'),
      };
    });
    const shot = path.join(outDir, `${vp.name}-full.png`);
    await page.screenshot({ path: shot, fullPage: true });
    const vpPass = resp.ok() && errors.length === 0 && Object.values(checks.present).every(Boolean) && checks.q12027 && checks.toolsConnected;
    report.viewports[vp.name] = { status: resp.status(), errors, checks, shot, pass: vpPass };
    if (!vpPass) report.pass = false;
    if (errors.length) report.consoleErrors.push(...errors.map((e) => ({ vp: vp.name, e })));
    await page.close();
  }
  await browser.close();
  fs.writeFileSync(path.join(outDir, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  process.exit(report.pass ? 0 : 1);
})().catch((e) => { console.error(e); process.exit(1); });
