/**
 * Visual verify: official WhatsApp / Slack / Google Drive marks on landing connectors.
 * Usage: node e2e/verify-landing-brand-icons.cjs [baseUrl]
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const base = process.argv[2] || 'http://127.0.0.1:8000';
const outDir = path.join(__dirname, 'screenshots', 'landing-brand-icons');
fs.mkdirSync(outDir, { recursive: true });

function report(name, ok, detail) {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
  return ok;
}

(async () => {
  let failures = 0;
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.goto(base + '/', { waitUntil: 'networkidle', timeout: 60000 });

  const marks = await page.evaluate(() => {
    const pick = (sel) => {
      const el = document.querySelector(sel);
      if (!el) return null;
      const fills = [...el.querySelectorAll('path')].map((p) => p.getAttribute('fill')).filter(Boolean);
      return { className: el.className.baseVal || el.getAttribute('class'), fills };
    };
    return {
      wa: pick('.brand-mark--whatsapp'),
      slack: pick('.brand-mark--slack'),
      drive: pick('.brand-mark--drive'),
      waCount: document.querySelectorAll('.brand-mark--whatsapp').length,
      slackCount: document.querySelectorAll('.brand-mark--slack').length,
      driveCount: document.querySelectorAll('.brand-mark--drive').length,
    };
  });

  if (!report('whatsapp official green glyph', marks.wa && marks.wa.fills.includes('#25D366') && marks.wa.fills.includes('#fff'), JSON.stringify(marks.wa))) failures++;
  if (!report('slack official four colors', marks.slack && ['#E01E5A', '#36C5F0', '#2EB67D', '#ECB22E'].every((c) => marks.slack.fills.includes(c)), JSON.stringify(marks.slack))) failures++;
  if (!report('drive official triangle colors', marks.drive && marks.drive.fills.includes('#0066da') && marks.drive.fills.includes('#00ac47'), JSON.stringify(marks.drive))) failures++;
  if (!report('marks appear across surfaces', marks.waCount >= 3 && marks.slackCount >= 3 && marks.driveCount >= 3, JSON.stringify({ wa: marks.waCount, slack: marks.slackCount, drive: marks.driveCount }))) failures++;

  // No Lucide stroke approximations left for brand connectors in the orchestration panel
  const legacy = await page.evaluate(() => {
    const panel = document.querySelector('#connectors .orchestration-panel');
    if (!panel) return { ok: false, reason: 'no panel' };
    const strokes = [...panel.querySelectorAll('.panel-connector-icon.whatsapp svg, .panel-connector-icon.drive svg, .panel-connector-icon.slack svg')]
      .map((svg) => svg.getAttribute('stroke') || [...svg.querySelectorAll('[stroke]')].map((n) => n.getAttribute('stroke')).join(','));
    const brandOk = panel.querySelectorAll('.panel-connector-icon.whatsapp .brand-mark--whatsapp, .panel-connector-icon.drive .brand-mark--drive, .panel-connector-icon.slack .brand-mark--slack').length === 3;
    return { ok: brandOk && strokes.every((s) => !s || s === 'none' || s === ''), strokes, brandOk };
  });
  if (!report('panel uses brand components not stroke glyphs', legacy.ok, JSON.stringify(legacy))) failures++;

  await page.locator('#connectors').scrollIntoViewIfNeeded();
  await page.screenshot({ path: path.join(outDir, 'desktop-connectors.png'), fullPage: false });
  await page.locator('.orchestration-panel').screenshot({ path: path.join(outDir, 'orchestration-panel.png') });
  await page.locator('.connector-grid-compact').screenshot({ path: path.join(outDir, 'connector-chips.png') });

  const toshi = page.locator('#toshi, .toshi-hub, .toshi-visual').first();
  if (await toshi.count()) {
    await toshi.scrollIntoViewIfNeeded();
    await page.locator('.toshi-visual, .toshi-hub-surface').first().screenshot({ path: path.join(outDir, 'toshi-channels.png') }).catch(() => {});
  }

  // Crop hero connector float if present
  const float = page.locator('.connector-float');
  if (await float.count()) {
    await page.locator('#hero').scrollIntoViewIfNeeded();
    await float.screenshot({ path: path.join(outDir, 'hero-connector-float.png') }).catch(() => {});
  }

  // Mobile
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(base + '/', { waitUntil: 'networkidle', timeout: 60000 });
  await page.locator('#connectors').scrollIntoViewIfNeeded();
  await page.screenshot({ path: path.join(outDir, 'mobile-connectors.png') });

  // Read screenshots as a human-verification aid for the agent
  console.log('SHOTS', outDir);

  await browser.close();
  if (failures) {
    console.log(`\n${failures} failure(s)`);
    process.exit(1);
  }
  console.log('\nAll brand icon checks passed');
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
