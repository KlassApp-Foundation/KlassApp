const { chromium } = require('playwright');
(async () => {
  const base = 'http://127.0.0.1:8018';
  const browser = await chromium.launch();
  for (const [w, h, tag] of [[1280, 800, '1280'], [375, 720, '375']]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
    await page.goto(base + '/login', { waitUntil: 'domcontentloaded' });
    await page.fill('#email', 'classless.admin@test.sch.ug');
    await page.fill('#password', 'LocalOnly-2026!');
    await page.click('button[type=submit], button[data-testid="ap-primary-submit"]');
    await page.waitForURL(/dashboard|admin/, { timeout: 20000 }).catch(() => {});
    await page.goto(base + '/admin/students', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1200);
    const body = await page.content();
    const hasNoClass = body.includes('No class');
    const hasFilter = body.includes('Needs a class');
    await page.screenshot({ path: `e2e/screenshots/pr907-classless/list-${tag}.png`, fullPage: false });
    await page.selectOption('#students-class', 'none');
    await page.click('button[type=submit]');
    await page.waitForTimeout(1200);
    const filtered = await page.content();
    const seeClassless = filtered.includes('Nalwoga Classless');
    const enrolledGone = !filtered.includes('Okello Enrolled');
    await page.screenshot({ path: `e2e/screenshots/pr907-classless/filtered-${tag}.png`, fullPage: false });
    console.log(JSON.stringify({ viewport: tag, hasNoClass, hasFilter, seeClassless, enrolledGone, consoleErrors: errors }));
    await ctx.close();
  }
  await browser.close();
})().catch(e => { console.error('FATAL', e.message); process.exit(1); });
