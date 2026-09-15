/**
 * PR #611 staging verify — SiteAdmin lands on /superadmin/dashboard (not school shell).
 *
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=siteadmin.pr611@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/siteadmin-superadmin-redirect-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'https://klassapp-staging-7mpoqg.laravel.cloud').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'siteadmin.pr611@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots', 'pr611-siteadmin');

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const results = [];

  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  const googleHrefLoggedOut = await page.locator('[data-testid="login-google"], a[href*="/auth/google"]').first().getAttribute('href').catch(() => null);
  results.push({ check: 'google_login_link_present', pass: !!googleHrefLoggedOut, href: googleHrefLoggedOut });

  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 60000 }),
    page.click('button[type="submit"], input[type="submit"]'),
  ]);

  const afterLogin = page.url();
  results.push({ check: 'post_login_url', url: afterLogin });
  await page.screenshot({ path: path.join(OUT, '01-after-login.png'), fullPage: true });

  const onSuper = afterLogin.includes('/superadmin/dashboard');
  const onAdmin = afterLogin.includes('/admin/dashboard') && !afterLogin.includes('/superadmin');
  results.push({ check: 'lands_superadmin', pass: onSuper });
  results.push({ check: 'not_school_admin_dashboard', pass: !onAdmin });

  // Hitting school shell must bounce SiteAdmin back to superadmin.
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 60000 });
  const afterAdmin = page.url();
  results.push({ check: 'admin_dashboard_redirect', url: afterAdmin, pass: afterAdmin.includes('/superadmin/dashboard') });
  await page.screenshot({ path: path.join(OUT, '02-admin-bounce.png'), fullPage: true });

  const html = await page.content();
  const schoolNavHints = ['Students', 'Parents', 'Finance'].filter((label) => {
    // Superadmin may mention schools; fail only if classic school sidebar links appear.
    return html.includes(`>${label}<`) && html.toLowerCase().includes('/admin/student');
  });
  results.push({ check: 'no_school_sidebar_student_links', pass: schoolNavHints.length === 0, hints: schoolNavHints });

  const failed = results.filter((r) => r.pass === false);
  console.log(JSON.stringify({ base: BASE, email: EMAIL, results, failed: failed.length }, null, 2));
  await browser.close();
  if (failed.length) process.exit(1);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
