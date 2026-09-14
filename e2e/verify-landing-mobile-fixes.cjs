/**
 * Per-issue Playwright verification for landing mobile bugfixes.
 * Usage: node e2e/verify-landing-mobile-fixes.cjs [baseUrl]
 */
const { chromium, devices } = require('playwright');
const fs = require('fs');
const path = require('path');

const baseUrl = process.argv[2] || 'http://127.0.0.1:8000';
const outDir = path.join(__dirname, 'screenshots', 'landing-fix-verify');
fs.mkdirSync(outDir, { recursive: true });

function report(name, ok, detail) {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
  return ok;
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const iPhone = devices['iPhone 13'];
  const ctx = await browser.newContext({ ...iPhone });
  const page = await ctx.newPage();
  let failures = 0;

  await page.goto(baseUrl + '/', { waitUntil: 'networkidle', timeout: 60000 });
  await page.waitForTimeout(600);

  // --- 6. Hero ordering: kicker/h1 before phone ---
  const heroOrder = await page.evaluate(() => {
    const content = document.querySelector('.hero-content');
    const phone = document.querySelector('.phone-wrapper');
    if (!content || !phone) return { error: 'missing nodes' };
    const cr = content.getBoundingClientRect();
    const pr = phone.getBoundingClientRect();
    return { contentTop: cr.top, phoneTop: pr.top, ok: cr.top < pr.top - 8 };
  });
  await page.locator('#hero').screenshot({ path: path.join(outDir, '01-hero-order-mobile.png') });
  if (!report('hero text above phone', heroOrder.ok, JSON.stringify(heroOrder))) failures++;

  // --- 3. Hamburger: opens panel with links ---
  const toggle = page.locator('#navbarMobileToggle');
  const panel = page.locator('#navbarMobilePanel');
  const before = await panel.evaluate((el) => ({ hidden: el.hidden, display: getComputedStyle(el).display }));
  await toggle.click();
  await page.waitForTimeout(300);
  const after = await panel.evaluate((el) => ({
    hidden: el.hidden,
    display: getComputedStyle(el).display,
    text: el.innerText.trim().slice(0, 200),
    aria: document.getElementById('navbarMobileToggle')?.getAttribute('aria-expanded'),
  }));
  await page.screenshot({ path: path.join(outDir, '02-hamburger-open.png') });
  const hamOk = !after.hidden && after.display !== 'none' && /Integrations|Toshi|FAQ/.test(after.text) && after.aria === 'true';
  if (!report('hamburger opens with nav links', hamOk, JSON.stringify({ before, after }))) failures++;
  await toggle.click();
  await page.waitForTimeout(200);

  // --- 1. Compare cards stack (single column) ---
  await page.locator('#compare').scrollIntoViewIfNeeded();
  await page.waitForTimeout(400);
  const compare = await page.evaluate(() => {
    const card = document.querySelector('.compare-card');
    if (!card) return { error: 'no compare-card' };
    const style = getComputedStyle(card);
    const cols = style.gridTemplateColumns;
    const width = card.getBoundingClientRect().width;
    const overflowX = document.documentElement.scrollWidth > window.innerWidth + 2;
    return { cols, width, overflowX, viewport: window.innerWidth };
  });
  await page.locator('#compare').screenshot({ path: path.join(outDir, '03-compare-cards-mobile.png') });
  const compareOk = compare.cols && !compare.overflowX && (compare.cols.split(' ').length === 1 || compare.cols === 'none');
  // stacked cards use grid-template-columns: 1fr (one track)
  const stacked = !compare.error && !compare.overflowX && String(compare.cols).trim().split(/\s+/).length === 1;
  if (!report('compare cards stacked, no page overflow', stacked, JSON.stringify(compare))) failures++;

  // --- 2. Protocol: decorative mesh hidden; cards readable ---
  await page.locator('#protocol').scrollIntoViewIfNeeded();
  await page.waitForTimeout(400);
  const protocol = await page.evaluate(() => {
    const visual = document.querySelector('.protocol-visual');
    const card = document.querySelector('.protocol-card, .protocol-grid .card, .mesh-card, [class*="protocol"] article');
    const visualDisplay = visual ? getComputedStyle(visual).display : 'absent';
    const body = document.body.innerText;
    return {
      visualDisplay,
      hasOpenSource: /Open Source/i.test(body),
      overflowX: document.documentElement.scrollWidth > window.innerWidth + 2,
    };
  });
  await page.locator('#protocol').screenshot({ path: path.join(outDir, '04-protocol-mobile.png') });
  const protoOk = protocol.visualDisplay === 'none' && protocol.hasOpenSource && !protocol.overflowX;
  if (!report('protocol mesh hidden; content readable', protoOk, JSON.stringify(protocol))) failures++;

  // --- 5. Footer Terms/Privacy ---
  const footer = await page.evaluate(() => {
    const terms = [...document.querySelectorAll('footer a')].find((a) => /terms/i.test(a.textContent || ''));
    const privacy = [...document.querySelectorAll('footer a')].find((a) => /privacy/i.test(a.textContent || ''));
    return {
      terms: terms ? terms.getAttribute('href') : null,
      privacy: privacy ? privacy.getAttribute('href') : null,
    };
  });
  await page.locator('footer').screenshot({ path: path.join(outDir, '05-footer-links.png') });
  const footOk = footer.terms && footer.privacy && !footer.terms.endsWith('#') && !privacyDead(footer);
  function privacyDead(f) {
    return f.privacy.endsWith('#') || f.privacy === '#' || f.terms === '#';
  }
  if (!report('footer Terms/Privacy real URLs', footOk, JSON.stringify(footer))) failures++;

  await browser.close();
  console.log(failures ? `\n${failures} failure(s)` : '\nAll per-issue checks passed');
  process.exit(failures ? 1 : 0);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
