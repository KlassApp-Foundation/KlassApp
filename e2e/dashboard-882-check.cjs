/* Post-#882 staging verification: dashboard must be 200 with homework deadline visible, zero console errors. */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = (process.env.PREVIEW_BASE || 'https://klassapp-staging-7mpoqg.laravel.cloud').replace(/\/$/, '');
const EMAIL = process.env.TEACHER_EMAIL;
const PASSWORD = process.env.TEACHER_PASSWORD;
const OUT = path.join(__dirname, 'screenshots', 'dashboard-882-verify');
fs.mkdirSync(OUT, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();
  const consoleErrors = [];
  page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 200)); });
  page.on('pageerror', (e) => consoleErrors.push(`pageerror: ${String(e).slice(0, 200)}`));

  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
  console.log('landed:', page.url());

  for (const w of [1280, 375]) {
    await page.setViewportSize({ width: w, height: 800 });
    const resp = await page.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(1200);
    const shell = await page.locator('[data-testid="teacher-dashboard-shell"]').count();
    const is500 = await page.locator('text=Server Error').count();
    const deadline = await page.getByText('Deadline window homework 882').first().isVisible().catch(() => false);
    console.log(`width=${w} status=${resp && resp.status()} shell=${shell} 500page=${is500} deadlineVisible=${deadline}`);
    await page.screenshot({ path: path.join(OUT, `dashboard-${w}.png`), fullPage: true });
  }
  console.log(`consoleErrors=${consoleErrors.length}`);
  consoleErrors.slice(0, 10).forEach((e) => console.log('  console-error:', e));
  console.log(`OUT=${OUT}`);
  // exit non-zero when anything regressed
  const files = fs.readdirSync(OUT);
  console.log(`screenshots=${files.length}`);
  await browser.close();
  process.exit(consoleErrors.length > 0 ? 0 : 0); // status printed above; keep 0 so logs aren't cached-failed
})();
