/**
 * Live verify medical-history height/weight strip (Kampala school 33).
 *
 *   PLAYWRIGHT_BASE_URL=https://klassapp.xyz node e2e/medical-history-height-weight-live.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const CREDS = JSON.parse(fs.readFileSync('/tmp/kpa-e2e-creds.json', 'utf8'));
const OUT = path.join(__dirname, 'screenshots', 'medical-history-height-weight');
const FORBIDDEN = ['Height :', 'Weight :', "Height ( in cm's )", "Weight ( in kg's )", 'Height ( in cm', 'Weight ( in kg'];

fs.mkdirSync(OUT, { recursive: true });

function hits(text, labels) {
  return labels.filter((l) => text.includes(l));
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });
  const report = { base: BASE, school_id: CREDS.school_id, checks: [], pass: false };

  const add = (name, ok, extra = {}) => {
    report.checks.push({ name, ok, ...extra });
    console.log(`${ok ? 'PASS' : 'FAIL'} ${name}`, extra.hits || extra.detail || '');
  };

  try {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.fill('input[name="email"], input[type="email"]', CREDS.admin_email);
    await page.fill('input[name="password"], input[type="password"]', CREDS.password);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }).catch(() => null),
      page.click('button[type="submit"], input[type="submit"]'),
    ]);

    // Admin student show → Medical History tab
    await page.goto(`${BASE}/admin/student/show/DAVID%20SSEMPIJJA`, {
      waitUntil: 'networkidle',
      timeout: 120000,
    });
    await page.waitForTimeout(3000);
    await page.getByRole('link', { name: /Medical History/i }).click();
    await page.waitForTimeout(2500);
    let body = await page.locator('body').innerText();
    let h = hits(body, FORBIDDEN);
    const showShot = path.join(OUT, '01-admin-medical-history-tab.png');
    await page.screenshot({ path: showShot, fullPage: true });
    add('01-admin-medical-history-no-height-weight', h.length === 0 && !/Server Error/i.test(body), {
      hits: h,
      shot: showShot,
    });

    // Admin medical history edit form
    await page.goto(`${BASE}/admin/student/add/medicalHistory/DAVID%20SSEMPIJJA`, {
      waitUntil: 'networkidle',
      timeout: 120000,
    });
    await page.waitForTimeout(3000);
    body = await page.locator('body').innerText();
    h = hits(body, FORBIDDEN);
    const editShot = path.join(OUT, '02-admin-medical-history-edit.png');
    await page.screenshot({ path: editShot, fullPage: true });
    add('02-admin-medical-history-edit-no-height-weight', h.length === 0 && !/Server Error/i.test(body), {
      hits: h,
      shot: editShot,
      hasMedicationLabel: /Medication Problems/i.test(body),
    });

    report.pass = report.checks.every((c) => c.ok);
  } catch (err) {
    report.error = String(err);
    console.error(err);
  } finally {
    fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
    await browser.close();
    console.log('pass=', report.pass);
    process.exit(report.pass ? 0 : 1);
  }
})();
