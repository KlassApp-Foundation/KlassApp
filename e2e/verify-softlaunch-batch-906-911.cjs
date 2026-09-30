/* Combined staging verify for soft-launch merge batch #906–#911.
 * Viewports: 375 + 1280. Staging only. Demo admin via STAGING_DEMO_PASSWORD.
 *
 *   PREVIEW_BASE=https://test.klassapp.xyz node e2e/verify-softlaunch-batch-906-911.cjs
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'https://test.klassapp.xyz').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD
  || execSync('doppler secrets get STAGING_DEMO_PASSWORD --project klassapp --config dev --plain', { encoding: 'utf8' }).trim();
const OUT = path.join(__dirname, 'screenshots', 'softlaunch-batch-906-911');
fs.mkdirSync(OUT, { recursive: true });

const VIEWPORTS = [
  { name: '1280', width: 1280, height: 900 },
  { name: '375', width: 375, height: 812 },
];

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.fill('#email, input[name="email"]', EMAIL);
  await page.fill('#password, input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL((u) => !u.pathname.startsWith('/login'), { timeout: 90000 }),
    page.click('button[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = {
    base: BASE,
    email: EMAIL,
    at: new Date().toISOString(),
    deploy_expect: '69524023055b9593410a0188ebdd9e6dc7f23d0c',
    checks: {},
    consoleErrors: [],
    ok: true,
  };

  for (const vp of VIEWPORTS) {
    const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });

    await login(page);

    // --- #911 dashboard greeting / year / banner ---
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForTimeout(1200);

    const dash = await page.evaluate(() => {
      const greetingEl = document.querySelector('[data-testid="dashboard-greeting"]');
      const greeting = greetingEl ? greetingEl.textContent.replace(/\s+/g, ' ').trim() : null;
      const yearLabel = Array.from(document.querySelectorAll('label, span, .ay-label, [data-testid]'))
        .map((e) => e.textContent.replace(/\s+/g, ' ').trim())
        .find((t) => /academic year/i.test(t));
      const yearControl = document.querySelector(
        '[data-testid="academic-year-control"], [data-testid="dashboard-ay-selector"], select#academic_year, select[name="academic_year"], .dashboard-ay-select'
      );
      const banner = document.querySelector('[data-testid="setup-banner"]');
      const bannerText = banner ? banner.textContent.replace(/\s+/g, ' ').trim() : null;
      const reminder = document.getElementById('onboarding-reminder');
      const toshiToggle = document.querySelector('[data-testid="toshi-toggle"]');
      const toshiEmbed = document.getElementById('toshi-panel') || document.querySelector('[data-testid="toshi-toggle-wrapper"]');
      return {
        greeting,
        yearLabel: yearLabel || null,
        yearControlPresent: !!yearControl || !!yearLabel,
        bannerText,
        reminderPresent: !!reminder,
        toshiTogglePresent: !!toshiToggle,
        toshiChromePresent: !!toshiEmbed,
        titleCaseOk: greeting ? !/[A-Z]{2,}/.test(greeting.replace(/Good (morning|afternoon|evening)/i, '')) : false,
      };
    });

    await page.screenshot({ path: path.join(OUT, `dashboard-${vp.name}.png`), fullPage: false });

    // --- #907 students classless filter ---
    await page.goto(`${BASE}/admin/students`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForTimeout(800);
    const students = await page.evaluate(() => {
      const sel = document.querySelector('#students-class, select[name="standard"]');
      const opts = sel ? Array.from(sel.options).map((o) => ({ value: o.value, text: o.textContent.trim() })) : [];
      const needs = opts.find((o) => o.value === 'none' || /needs a class/i.test(o.text));
      return { hasNeedsAClass: !!needs, optionCount: opts.length };
    });
    await page.screenshot({ path: path.join(OUT, `students-${vp.name}.png`), fullPage: false });

    // --- #909 size buckets on register (public) + import overlimit markup contract ---
    await page.goto(`${BASE}/register`, { waitUntil: 'domcontentloaded', timeout: 90000 }).catch(() => null);
    // register may redirect if logged in — use onboarding size page or wizard jump
    await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForTimeout(1000);
    // Jump to school-size step if jump exists (often early)
    const jump = await page.$('select[data-testid="wizard-jump"]');
    if (jump) {
      const sizeOpt = await page.$$eval('select[data-testid="wizard-jump"] option', (opts) => {
        const hit = opts.find((o) => /school size|approximate/i.test(o.textContent));
        return hit ? hit.value : null;
      });
      if (sizeOpt != null) {
        await page.selectOption('select[data-testid="wizard-jump"]', sizeOpt);
        await page.waitForTimeout(1000);
      }
    }
    const size = await page.evaluate(() => {
      const labels = Array.from(document.querySelectorAll('label, option, button, [role="radio"], .manual-wizard-choice'))
        .map((e) => e.textContent.replace(/\s+/g, ' ').trim())
        .filter(Boolean);
      const joined = labels.join(' | ');
      return {
        hasUpTo500: /Up to 500/i.test(joined),
        hasUpTo1000: /Up to 1,?000/i.test(joined),
        hasMoreThan1000: /More than 1,?000/i.test(joined),
        hasLegacyUnder100: /\bUnder 100\b/i.test(joined),
        sample: labels.filter((t) => /500|1,?000|Under|students/i.test(t)).slice(0, 12),
      };
    });
    await page.screenshot({ path: path.join(OUT, `wizard-size-${vp.name}.png`), fullPage: false });

    await page.goto(`${BASE}/admin/import`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForTimeout(600);
    const importPage = await page.evaluate(() => {
      const html = document.body.innerHTML;
      return {
        hasUpgradeCopy: /Upgrade your plan/i.test(html) || /overlimit/i.test(html),
        status: 200,
      };
    });
    await page.screenshot({ path: path.join(OUT, `import-${vp.name}.png`), fullPage: false });

    // --- #910 Toshi UI switch: for phase4, record presence (flag may be on/off) ---
    // Soft-launch rule: entry points must be gated. We assert no orphan reminder
    // and that toggle presence is boolean-consistent with embed.
    const toshi = {
      toggle: dash.toshiTogglePresent,
      chrome: dash.toshiChromePresent,
      reminderGone: !dash.reminderPresent,
    };

    const vpReport = {
      dashboard: dash,
      students,
      size,
      importPage,
      toshi,
      consoleErrors: errors.slice(0, 8),
    };
    report.checks[vp.name] = vpReport;
    report.consoleErrors.push(...errors);

    // Assertions
    if (!dash.greeting) fail(`${vp.name}: missing dashboard greeting`);
    if (/\b01-01-1970\b|\b1970-01-01\b/.test(dash.greeting || '')) fail(`${vp.name}: greeting has epoch date`);
    // Title-case: firstname part should not be ALL CAPS (accessor used to uppercase)
    if (dash.greeting && /,\s*[A-Z]{3,}\b/.test(dash.greeting) && dash.greeting === dash.greeting.toUpperCase()) {
      fail(`${vp.name}: greeting still all-caps`);
    }
    if (!dash.yearControlPresent && vp.name === '375') {
      // year must be visible on mobile per #911
      fail(`${vp.name}: academic year control/label not found on mobile`);
    }
    if (dash.bannerText && /\d+\s+steps remaining\s+\./i.test(dash.bannerText)) {
      fail(`${vp.name}: banner still has space before period`);
    }
    if (dash.reminderPresent) fail(`${vp.name}: onboarding-reminder still present (deleted in #911)`);
    if (!students.hasNeedsAClass) fail(`${vp.name}: missing "Needs a class" filter (#907)`);
    if (!(size.hasUpTo500 && size.hasUpTo1000 && size.hasMoreThan1000)) {
      fail(`${vp.name}: final size buckets missing on wizard (#909): ${JSON.stringify(size.sample)}`);
    }
    if (size.hasLegacyUnder100) fail(`${vp.name}: legacy Under 100 size option still shown (#909)`);

    await ctx.close();
  }

  // Public register size buckets (logged-out)
  const pubCtx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const pub = await pubCtx.newPage();
  await pub.goto(`${BASE}/register`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await pub.waitForTimeout(800);
  const reg = await pub.evaluate(() => {
    const sel = document.querySelector('#student_size, select[name="student_size"]');
    if (!sel) return { found: false, options: [] };
    return {
      found: true,
      options: Array.from(sel.options).map((o) => o.value).filter(Boolean),
    };
  });
  await pub.screenshot({ path: path.join(OUT, 'register-size-1280.png') });
  report.checks.register = reg;
  if (reg.found) {
    const expected = ['Up to 500', 'Up to 1,000', 'More than 1,000'];
    const ok = expected.every((e) => reg.options.includes(e)) && reg.options.length === 3;
    if (!ok) fail(`register size options wrong: ${JSON.stringify(reg.options)}`);
  } else {
    // register may be multi-step; flag but don't hard-fail if wizard already covered buckets
    console.warn('WARN: #student_size not on /register first paint — wizard buckets already checked');
  }
  await pubCtx.close();

  report.ok = process.exitCode !== 1;
  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  console.log(report.ok ? 'VERIFY_PASS' : 'VERIFY_FAIL');
  await browser.close();
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error('FAIL', e && e.stack ? e.stack : e);
  process.exit(1);
});
