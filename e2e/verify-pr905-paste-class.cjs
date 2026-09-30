/* PR #905 staging verification: pasting students with no class chosen must show
   "No class" (the designed empty label) and must NOT silently fall back to P1. */
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

const NAMES = 'Amara Nansubuga\nKiiza Ssemakula';

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

  const res = await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (res.status() !== 200) throw new Error(`wizard status ${res.status()}`);

  // Jump straight to the Students step (option value 11 = "12. Students").
  await page.selectOption('select[data-testid="wizard-jump"]', '11');
  await page.waitForSelector('#wizard-student-paste', { timeout: 30000 });
  await page.waitForTimeout(1200);

  // No class chosen: the select must still be on its placeholder.
  const before = await page.inputValue('#wizard-student-class');
  if (before !== '') throw new Error(`expected empty class select before paste, got "${before}"`);

  await page.fill('#wizard-student-paste', NAMES);
  await page.click('[data-testid="wizard-student-paste-btn"]');
  await page.waitForTimeout(1800);

  const rows = await page.$$eval('.manual-wizard-bulk-item', (els) => els.map((e) => e.innerText.replace(/\s+/g, ' ').trim()));
  console.log('DRAFT_ROWS', JSON.stringify(rows, null, 2));
  if (rows.length < 2) throw new Error(`expected 2 drafts, got ${rows.length}`);

  const shown = rows.join(' | ');
  const hasNoClass = rows.every((r) => r.includes('No class'));
  const hasP1 = /\bP1\b/.test(shown);

  await page.screenshot({ path: path.join(OUT, 'paste-no-class-1280.png') });
  await page.setViewportSize({ width: 375, height: 812 });
  await page.waitForTimeout(600);
  await page.screenshot({ path: path.join(OUT, 'paste-no-class-375.png') });

  console.log('ALL_SHOW_NO_CLASS', hasNoClass);
  console.log('P1_PRESENT', hasP1);
  console.log('CONSOLE_ERRORS', errors.length, JSON.stringify(errors.slice(0, 5)));
  await browser.close();

  if (!hasNoClass || hasP1) {
    console.error('VERIFY_FAIL');
    process.exit(1);
  }
  console.log('VERIFY_PASS');
})().catch((e) => { console.error('FAIL', e.message); process.exit(1); });
