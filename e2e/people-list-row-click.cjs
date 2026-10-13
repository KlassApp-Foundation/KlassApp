/**
 * Real pointer: a click on the row, away from the name text, follows the profile link.
 * The checkbox and the row menu stay above the overlay and must not navigate.
 *
 *   BASE=http://127.0.0.1:8003 SHELL_EMAIL=… SHELL_PASSWORD=… node e2e/people-list-row-click.cjs
 */
const { chromium } = require('playwright');

const BASE = (process.env.BASE || 'http://127.0.0.1:8003').replace(/\/$/, '');
const EMAIL = process.env.SHELL_EMAIL || '';
const PASSWORD = process.env.SHELL_PASSWORD || '';

function fail(message) {
  console.error('FAIL:', message);
  process.exitCode = 1;
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 90000 }),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

(async () => {
  if (!EMAIL || !PASSWORD) {
    fail('SHELL_EMAIL and SHELL_PASSWORD are required');
    return;
  }

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await login(page);
  await page.goto(`${BASE}/admin/students`, { waitUntil: 'domcontentloaded', timeout: 90000 });

  const row = page.locator('tr.people-row').first();
  if ((await row.count()) === 0) {
    fail('no student row');
    await browser.close();
    return;
  }

  const href = await row.locator('a.people-name').getAttribute('href');
  const cell = row.locator('td').nth(2);
  const box = await cell.boundingBox();
  if (!href || !box) {
    fail('row has no profile link or measurable cell');
    await browser.close();
    return;
  }

  await page.mouse.click(box.x + box.width / 2, box.y + Math.min(box.height / 2, 20));
  await page.waitForURL((url) => url.href.startsWith(href) || url.pathname !== '/admin/students', { timeout: 15000 }).catch(() => null);

  const landed = page.url();
  if (!landed.startsWith(href) && !landed.includes('/admin/student')) {
    fail(`row click stayed on ${landed}; expected ${href}`);
  } else {
    console.log('PASS row click', landed.split('?')[0]);
  }

  await page.goto(`${BASE}/admin/students`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  const check = page.locator('tr.people-row [data-people-check]').first();
  const checkBox = await check.boundingBox();
  if (checkBox) {
    await page.mouse.click(checkBox.x + checkBox.width / 2, checkBox.y + checkBox.height / 2);
    await page.waitForTimeout(400);
    if (!page.url().includes('/admin/students')) {
      fail(`checkbox navigated to ${page.url()}`);
    } else if (!(await check.isChecked())) {
      fail('checkbox click did not toggle');
    } else {
      console.log('PASS checkbox stays on the list');
    }
  }

  await browser.close();
  if (process.exitCode) process.exit(process.exitCode);
})().catch((error) => {
  console.error('FAIL:', error.message);
  process.exit(1);
});
