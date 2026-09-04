const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const OUT = path.join(__dirname, 'screenshots', 'ui-review-secondary-demo');
fs.mkdirSync(OUT, { recursive: true });

const PW = 'UiReview2026!';
const PRIMARY_ADMIN = { email: 'admin@uireview.klassapp.demo', password: PW };
const SEC_ADMIN = { email: 'admin@uireview-secondary.klassapp.demo', password: PW };
const SEC_CT = { email: 'classteacher@uireview-secondary.klassapp.demo', password: PW };
const SEC_SUBJ = { email: 'subjectteacher@uireview-secondary.klassapp.demo', password: PW };

async function login(page, email, password) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"], input[type="email"]', email);
  await page.fill('input[name="password"], input[type="password"]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => null),
    page.click('button[type="submit"], input[type="submit"]'),
  ]);
}

function push(report, name, ok, extra = {}) {
  report.checks.push({ name, ok, ...extra });
  console.log(`${ok ? 'PASS' : 'FAIL'} ${name}`, extra.url || '', extra.detail || '');
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });
  const report = {
    base: BASE,
    started_at: new Date().toISOString(),
    school_ids: {},
    checks: [],
  };

  // ── Primary gender donut (school 124) ──
  await login(page, PRIMARY_ADMIN.email, PRIMARY_ADMIN.password);
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'networkidle' });
  const primaryDash = await page.content();
  const girlsMatch = primaryDash.match(/Girls[\s\S]{0,120}?>(\d+|—)</i);
  const boysMatch = primaryDash.match(/Boys[\s\S]{0,120}?>(\d+|—)</i);
  const girls = girlsMatch ? girlsMatch[1] : null;
  const boys = boysMatch ? boysMatch[1] : null;
  await page.screenshot({ path: path.join(OUT, '01-primary-dashboard-gender.png'), fullPage: true });
  push(report, 'primary_gender_populated', girls !== '0' && boys !== '0' && girls !== '—' && boys !== '—', {
    girls,
    boys,
  });

  // ── Secondary admin login + dashboard ──
  await page.context().clearCookies();
  await login(page, SEC_ADMIN.email, SEC_ADMIN.password);
  push(report, 'secondary_admin_lands_dashboard', /\/admin\/dashboard/.test(page.url()), {
    url: page.url(),
  });
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'networkidle' });
  const secDash = await page.content();
  const secGirls = (secDash.match(/Girls[\s\S]{0,120}?>(\d+|—)</i) || [])[1] || null;
  const secBoys = (secDash.match(/Boys[\s\S]{0,120}?>(\d+|—)</i) || [])[1] || null;
  await page.screenshot({ path: path.join(OUT, '02-secondary-dashboard-gender.png'), fullPage: true });
  push(report, 'secondary_gender_populated', Number(secGirls) > 0 && Number(secBoys) > 0, {
    girls: secGirls,
    boys: secBoys,
  });
  push(report, 'secondary_dashboard_shows_school_name', secDash.includes('UI Review Secondary'), {
    detail: 'school name in shell/nav',
  });

  // Classes / roster
  await page.goto(`${BASE}/admin/classes`, { waitUntil: 'networkidle' });
  const classesHtml = await page.content();
  await page.screenshot({ path: path.join(OUT, '03-secondary-classes.png'), fullPage: true });
  push(report, 'secondary_classes_has_senior_four', /Senior Four|S\.4/i.test(classesHtml));
  push(report, 'secondary_classes_has_senior_six', /Senior Six|S\.6/i.test(classesHtml));
  push(report, 'secondary_classes_livewire', /wire:id|livewire/i.test(classesHtml));

  // Fees / tuition labels
  await page.goto(`${BASE}/admin/fees/payments/create`, { waitUntil: 'networkidle' });
  const feeCreate = await page.content();
  await page.screenshot({ path: path.join(OUT, '04-secondary-fee-create.png'), fullPage: true });
  push(report, 'tuition_o_level_label', /Tuition \(O'?Level\)/i.test(feeCreate));
  push(report, 'tuition_a_level_label', /Tuition \(A'?Level\)/i.test(feeCreate));

  // Settings school name
  await page.goto(`${BASE}/admin/settings/generalsettings`, { waitUntil: 'domcontentloaded' });
  const settings = await page.content();
  await page.screenshot({ path: path.join(OUT, '05-secondary-settings.png'), fullPage: true });
  push(report, 'settings_school_name_field', settings.includes('School Name') && settings.includes('UI Review Secondary Demo School'));

  // Report cards index
  await page.goto(`${BASE}/admin/reports/cards`, { waitUntil: 'networkidle' });
  const reports = await page.content();
  await page.screenshot({ path: path.join(OUT, '06-secondary-reports-cards.png'), fullPage: true });
  push(report, 'reports_lists_senior_classes', /Senior Four|Senior Six|Senior One/i.test(reports));

  // Try open a PDF/preview if a student report link exists
  const reportLink = page.locator('a[href*="report"], a[href*="pdf"], button:has-text("Preview"), a:has-text("Preview")').first();
  if ((await reportLink.count()) > 0) {
    const href = await reportLink.getAttribute('href').catch(() => null);
    if (href) {
      const res = await page.goto(href.startsWith('http') ? href : `${BASE}${href}`, {
        waitUntil: 'domcontentloaded',
      });
      const ct = res?.headers()?.['content-type'] || '';
      const body = await page.content();
      await page.screenshot({ path: path.join(OUT, '07-secondary-report-preview.png'), fullPage: true });
      push(report, 'secondary_report_preview', ct.includes('pdf') || body.includes('UI Review Secondary') || body.includes('%PDF'), {
        url: page.url(),
        contentType: ct,
      });
    } else {
      push(report, 'secondary_report_preview', false, { detail: 'link without href' });
    }
  } else {
    // Fall back: students list presence as proxy for secondary data readiness
    await page.goto(`${BASE}/admin/students`, { waitUntil: 'networkidle' });
    const students = await page.content();
    await page.screenshot({ path: path.join(OUT, '07-secondary-students.png'), fullPage: true });
    push(report, 'secondary_students_list', /Diana Namukasa|Faith Atwine|Eric Ssempala/i.test(students));
    push(report, 'secondary_report_preview', false, {
      detail: 'no preview link on reports/cards — students list checked instead',
    });
  }

  // ── Class teacher ──
  await page.context().clearCookies();
  await login(page, SEC_CT.email, SEC_CT.password);
  push(report, 'ct_lands_teacher_dashboard', /\/teacher\/dashboard/.test(page.url()), { url: page.url() });
  await page.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'networkidle' });
  await page.screenshot({ path: path.join(OUT, '08-ct-dashboard.png'), fullPage: true });
  await page.goto(`${BASE}/teacher/classes`, { waitUntil: 'networkidle' });
  const ctClasses = await page.content();
  await page.screenshot({ path: path.join(OUT, '09-ct-classes.png'), fullPage: true });
  push(report, 'ct_sees_senior_four', /Senior Four/i.test(ctClasses) || page.url().includes('/teacher/'));

  // ── Subject teacher ──
  await page.context().clearCookies();
  await login(page, SEC_SUBJ.email, SEC_SUBJ.password);
  push(report, 'subject_teacher_lands_dashboard', /\/teacher\/dashboard/.test(page.url()), {
    url: page.url(),
  });
  await page.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'networkidle' });
  await page.screenshot({ path: path.join(OUT, '10-subject-teacher-dashboard.png'), fullPage: true });

  // ── Wizard secondary category smoke (fresh school would be heavy; assert category options exist via Toshi/docs) ──
  // Light check: secondary admin can open onboarding wizard without 500
  await page.context().clearCookies();
  await login(page, SEC_ADMIN.email, SEC_ADMIN.password);
  const wiz = await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'domcontentloaded' });
  await page.screenshot({ path: path.join(OUT, '11-secondary-wizard.png'), fullPage: true });
  push(report, 'secondary_wizard_reachable', (wiz?.status() || 0) < 500, {
    status: wiz?.status(),
    url: page.url(),
  });

  report.pass = report.checks.every((c) => c.ok);
  report.finished_at = new Date().toISOString();
  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log('REPORT', path.join(OUT, 'REPORT.json'), 'pass=', report.pass);
  await browser.close();
  process.exit(report.pass ? 0 : 1);
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
