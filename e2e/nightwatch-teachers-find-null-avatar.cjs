/**
 * Nightwatch trio — verify /admin/teachers/find survives null avatars.
 *
 * Usage (staging):
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=phase4.admin@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/nightwatch-teachers-find-null-avatar.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/nightwatch-teachers-find');
fs.mkdirSync(OUT, { recursive: true });

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();

  try {
    await login(page);
    if (page.url().includes('/login')) {
      fail('login failed — still on /login');
      return;
    }

    const findResp = await page.request.get(`${BASE}/admin/teachers/find`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const status = findResp.status();
    const bodyText = await findResp.text();
    fs.writeFileSync(path.join(OUT, 'teachers-find.json'), bodyText);

    if (status !== 200) {
      fail(`/admin/teachers/find status=${status} body=${bodyText.slice(0, 400)}`);
      return;
    }

    let payload;
    try {
      payload = JSON.parse(bodyText);
    } catch (e) {
      fail(`teachers/find not JSON: ${bodyText.slice(0, 400)}`);
      return;
    }

    const teachers = Array.isArray(payload)
      ? payload
      : Array.isArray(payload?.data)
        ? payload.data
        : null;

    if (!teachers) {
      fail(`unexpected teachers/find shape: ${bodyText.slice(0, 400)}`);
      return;
    }

    console.log(`OK teachers/find 200 — ${teachers.length} teacher(s)`);
    const nullAvatars = teachers.filter((t) => t.avatar === null || t.avatar === '' || t.avatar === undefined);
    console.log(`OK null/empty avatar rows: ${nullAvatars.length} (no TypeError crash)`);

    await page.goto(`${BASE}/admin/teachers`, { waitUntil: 'load', timeout: 90000 });
    await page.screenshot({ path: path.join(OUT, 'teachers-index-1280.png'), fullPage: true });
    console.log('OK /admin/teachers rendered');
  } catch (e) {
    fail(e.stack || String(e));
  } finally {
    await browser.close();
  }
})();
