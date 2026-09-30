/* Quick probe: login once, then hit /teacher/dashboard several times, report HTTP status + testid count. */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'https://klassapp-staging-7mpoqg.laravel.cloud').replace(/\/$/, '');
const EMAIL = process.env.TEACHER_EMAIL;
const PASSWORD = process.env.TEACHER_PASSWORD;

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
  console.log('landed:', page.url());

  for (const w of [1280, 375, 1280, 375]) {
    await page.setViewportSize({ width: w, height: 800 });
    const resp = await page.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(1200);
    const shell = await page.locator('[data-testid="teacher-dashboard-shell"]').count();
    const is500 = await page.locator('text=Server Error').count();
    console.log(`width=${w} status=${resp && resp.status()} shell=${shell} 500page=${is500} url=${page.url()}`);
  }
  await browser.close();
})();
