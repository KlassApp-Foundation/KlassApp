/* Probe the manual onboarding wizard on staging: what step does it land on? */
const { chromium } = require('playwright');
const { execSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const BASE = (process.env.PREVIEW_BASE || 'https://test.klassapp.xyz').replace(/\/$/, '');
const EMAIL = process.env.WIZARD_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.WIZARD_PASSWORD
  || execSync('doppler secrets get STAGING_DEMO_PASSWORD --project klassapp --config dev --plain', { encoding: 'utf8' }).trim();
const OUT = path.join(__dirname, 'screenshots', 'pr905-paste-class');
fs.mkdirSync(OUT, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  const errors = [];
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });

  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.fill('#email', EMAIL);
  await page.fill('#password', PASSWORD);
  await Promise.all([
    page.waitForURL((u) => !u.pathname.startsWith('/login'), { timeout: 90000 }),
    page.click('button[data-testid="ap-primary-submit"]'),
  ]);
  console.log('LOGIN_OK url=', page.url());

  const res = await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  console.log('WIZARD status=', res.status(), 'url=', page.url());
  await page.waitForTimeout(2500);

  const probe = await page.evaluate(() => {
    const roots = Array.from(document.querySelectorAll('[wire\\:id]'));
    return {
      title: document.title,
      wireRoots: roots.length,
      firstWireId: roots[0] ? roots[0].getAttribute('wire:id') : null,
      hasLivewire: typeof window.Livewire !== 'undefined',
      bodyText: (document.body.innerText || '').slice(0, 700),
      stepNav: Array.from(document.querySelectorAll('[data-testid*="step"], .wizard-step, [class*="step-"]'))
        .slice(0, 12).map((e) => (e.getAttribute('data-testid') || e.className || '').slice(0, 60)),
      jumpOptions: Array.from(document.querySelectorAll('select[data-testid="wizard-jump"] option'))
        .map((o) => ({ v: o.value, t: o.textContent.trim() })),
    };
  });
  console.log('PROBE', JSON.stringify(probe, null, 2));
  await page.screenshot({ path: path.join(OUT, 'wizard-1280.png') });
  console.log('CONSOLE_ERRORS', errors.length, JSON.stringify(errors.slice(0, 5)));
  await browser.close();
})().catch((e) => { console.error('FAIL', e.message); process.exit(1); });
