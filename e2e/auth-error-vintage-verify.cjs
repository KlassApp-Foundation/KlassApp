/**
 * Playwright: auth/error vintage v2 - desktop split + mobile stack.
 * OD: klassapp-auth-error-vintage-paper-v2-breakpoints.html
 * Confirms intentional desktop layout (not centered mobile card),
 * transparent form shell, hero-exact paper (rules + grain 0.22), locks.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PREVIEW_BASE || 'http://127.0.0.1:8000';
const OUT = path.join(__dirname, 'screenshots/auth-error-vintage');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 390, height: 844 },
];

const authScreens = [
  { path: '/preview/login', key: 'login' },
  { path: '/preview/register', key: 'register' },
  { path: '/preview/reset-request', key: 'reset-request' },
  { path: '/preview/reset-code', key: 'reset-code' },
  { path: '/preview/reset-newpw', key: 'reset-newpw' },
  { path: '/preview/force-change-password', key: 'force-change-password' },
];

const errorCodes = ['404', '419', '500'];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = { base: BASE, at: new Date().toISOString(), viewports: {}, pass2: {}, ok: true };

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const consoleErrors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });
    page.on('pageerror', (err) => consoleErrors.push(String(err)));

    const screens = {};
    const isDesktop = vp.width >= 960;

    for (const screen of authScreens) {
      const res = await page.goto(`${BASE}${screen.path}`, { waitUntil: 'networkidle', timeout: 60000 });
      await page.waitForTimeout(350);
      const checks = await page.evaluate((desktop) => {
        const pageEl = document.querySelector('.ap-page');
        const shell = document.querySelector('.ap-shell');
        const brand = document.querySelector('.ap-brand-panel');
        const formPanel = document.querySelector('.ap-form-panel');
        const formShell = document.querySelector('.ap-form-shell, .ap-card');
        const bg = document.querySelector('.ap-bg-vintage');
        const title = document.querySelector('.ap-title');
        const html = document.documentElement.outerHTML;
        const bodyText = document.body.innerText || '';
        const cs = (el) => (el ? getComputedStyle(el) : null);
        const shellCs = cs(shell);
        const formCs = cs(formShell);
        const bgBefore = bg ? getComputedStyle(bg, '::before') : null;
        const titleFont = cs(title)?.fontFamily || '';
        const bodyFont = cs(document.body)?.fontFamily || '';
        const brandBox = brand?.getBoundingClientRect();
        const formBox = formPanel?.getBoundingClientRect();
        const sideBySide =
          desktop &&
          brandBox &&
          formBox &&
          Math.abs(brandBox.top - formBox.top) < 120 &&
          formBox.left > brandBox.right - 8;
        const stacked =
          !desktop &&
          brandBox &&
          formBox &&
          formBox.top >= brandBox.bottom - 4;
        const formBg = formCs?.backgroundColor || '';
        const opaqueWhite =
          formBg === 'rgb(255, 255, 255)' ||
          formBg.startsWith('rgba(255, 255, 255, 1)');
        const paperRules =
          !!bg &&
          (html.includes('repeating-linear-gradient') ||
            (bgBefore && (bgBefore.backgroundImage || '').includes('repeating-linear-gradient')));

        return {
          paper: pageEl?.getAttribute('data-ap-paper') === 'vintage',
          layout: pageEl?.getAttribute('data-ap-layout') === 'split',
          hasBg: !!bg,
          gridCols: shellCs?.gridTemplateColumns || '',
          sideBySide: !!sideBySide,
          stacked: !!stacked,
          opaqueWhite,
          formBg,
          paperRules,
          sora: /Sora/i.test(titleFont) || html.includes('family=Sora'),
          dmSans: /DM Sans/i.test(bodyFont) || html.includes('family=DM+Sans'),
          bricolage: /Bricolage/i.test(html),
          emDash: bodyText.includes('\u2014'),
          dsElements: document.querySelectorAll('[class*="ds-"]').length,
          hasPrimary: !!document.querySelector('[data-testid="ap-primary-submit"]'),
          paperBody: cs(document.body)?.backgroundColor === 'rgb(245, 240, 230)',
        };
      }, isDesktop);

      const shot = path.join(OUT, `${vp.name}-${screen.key}.png`);
      await page.screenshot({ path: shot, fullPage: true });

      const layoutOk = isDesktop ? checks.sideBySide : checks.stacked;
      const pass =
        res.ok() &&
        checks.paper &&
        checks.layout &&
        checks.hasBg &&
        layoutOk &&
        !checks.opaqueWhite &&
        checks.paperRules &&
        checks.sora &&
        checks.dmSans &&
        !checks.bricolage &&
        !checks.emDash &&
        checks.dsElements === 0 &&
        checks.hasPrimary &&
        checks.paperBody;

      screens[screen.key] = { status: res.status(), checks, shot, pass };
      if (!pass) report.ok = false;
    }

    for (const code of errorCodes) {
      const res = await page.goto(`${BASE}/preview/errors/${code}`, {
        waitUntil: 'networkidle',
        timeout: 60000,
      });
      await page.waitForTimeout(300);
      const checks = await page.evaluate((desktop) => {
        const brand = document.querySelector('.err-brand-panel');
        const panel = document.querySelector('.err-form-panel');
        const card = document.querySelector('.err-card');
        const bg = document.querySelector('.err-bg-vintage');
        const html = document.documentElement.outerHTML;
        const bodyText = document.body.innerText || '';
        const brandBox = brand?.getBoundingClientRect();
        const formBox = panel?.getBoundingClientRect();
        const sideBySide =
          desktop &&
          brandBox &&
          formBox &&
          Math.abs(brandBox.top - formBox.top) < 120 &&
          formBox.left > brandBox.right - 8;
        const stacked = !desktop && brandBox && formBox && formBox.top >= brandBox.bottom - 4;
        const cardBg = card ? getComputedStyle(card).backgroundColor : '';
        const opaqueWhite =
          cardBg === 'rgb(255, 255, 255)' || cardBg.startsWith('rgba(255, 255, 255, 1)');
        return {
          paper: document.body.getAttribute('data-error-paper') === 'vintage',
          layout: document.body.getAttribute('data-error-layout') === 'split',
          shell: document.body.getAttribute('data-error-shell') === 'pass2',
          hasBg: !!bg,
          sideBySide: !!sideBySide,
          stacked: !!stacked,
          opaqueWhite,
          paperRules: html.includes('repeating-linear-gradient'),
          sora: /Sora/i.test(getComputedStyle(document.querySelector('.err-title') || document.body).fontFamily),
          bricolage: /Bricolage/i.test(html),
          emDash: bodyText.includes('\u2014'),
          dsElements: document.querySelectorAll('[class*="ds-"]').length,
        };
      }, isDesktop);

      const shot = path.join(OUT, `${vp.name}-error-${code}.png`);
      await page.screenshot({ path: shot, fullPage: true });
      const layoutOk = isDesktop ? checks.sideBySide : checks.stacked;
      const pass =
        res.ok() &&
        checks.paper &&
        checks.layout &&
        checks.shell &&
        checks.hasBg &&
        layoutOk &&
        !checks.opaqueWhite &&
        checks.paperRules &&
        checks.sora &&
        !checks.bricolage &&
        !checks.emDash &&
        checks.dsElements === 0;
      screens[`error-${code}`] = { status: res.status(), checks, shot, pass };
      if (!pass) report.ok = false;
    }

    report.viewports[vp.name] = { screens, consoleErrors, pass: consoleErrors.length === 0 };
    if (consoleErrors.length) report.ok = false;
    await page.close();
  }

  // Pass-2 lock measurements @ desktop
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.goto(`${BASE}/preview/login?demo_errors=1`, { waitUntil: 'networkidle' });
    const metrics = await page.evaluate(() => {
      const toggle = document.querySelector('.ap-password-toggle');
      const submit = document.querySelector('[data-testid="ap-primary-submit"]');
      const alert = document.querySelector('[data-testid="auth-flash-error"]');
      const cs = (el) => (el ? getComputedStyle(el) : null);
      return {
        toggle: toggle
          ? { w: Math.round(parseFloat(cs(toggle).width)), h: Math.round(parseFloat(cs(toggle).height)) }
          : null,
        submitBg: cs(submit)?.backgroundColor || null,
        alert: alert
          ? {
              color: cs(alert).color,
              backgroundColor: cs(alert).backgroundColor,
              borderColor: cs(alert).borderTopColor,
            }
          : null,
        grainOpacity: (() => {
          const bg = document.querySelector('.ap-bg-vintage');
          if (!bg) return null;
          return getComputedStyle(bg, '::after').opacity;
        })(),
      };
    });
    await page.hover('[data-testid="ap-primary-submit"]');
    await page.waitForTimeout(200);
    const hoverBg = await page.evaluate(
      () => getComputedStyle(document.querySelector('[data-testid="ap-primary-submit"]')).backgroundColor
    );
    await page.goto(`${BASE}/preview/force-change-password`, { waitUntil: 'networkidle' });
    const force = await page.evaluate(() => {
      const visible = document.body.innerText.toLowerCase();
      return {
        hasSkip: /\bskip\b/.test(visible),
        hasCancel: /\bcancel\b/.test(visible),
        hasSignOut: /sign[\s-]?out/.test(visible),
      };
    });
    const rgb = (r, g, b) => `rgb(${r}, ${g}, ${b})`;
    const isErrorRed = (c) =>
      c === rgb(220, 38, 38) || c.includes('220, 38, 38') || c.includes('0.862745');
    const isErrorBg = (c) =>
      c === rgb(254, 242, 242) ||
      c.startsWith('rgba(254, 242, 242') ||
      c.includes('0.996078 0.94902 0.94902') ||
      c.includes('254, 242, 242');
    const pass2 = {
      toggle44: metrics.toggle && metrics.toggle.w === 44 && metrics.toggle.h === 44,
      primaryGreen: metrics.submitBg === rgb(34, 197, 94),
      primaryHover: hoverBg === rgb(22, 163, 74),
      errorTokens:
        metrics.alert && isErrorRed(metrics.alert.color) && isErrorBg(metrics.alert.backgroundColor),
      grain022: metrics.grainOpacity === '0.22',
      forceNoEscape: !force.hasSkip && !force.hasCancel && !force.hasSignOut,
      raw: { metrics, hoverBg, force },
    };
    pass2.pass = Object.entries(pass2)
      .filter(([k]) => !['raw', 'pass'].includes(k))
      .every(([, v]) => v === true);
    report.pass2 = pass2;
    if (!pass2.pass) report.ok = false;
    await page.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(
    JSON.stringify(
      {
        ok: report.ok,
        pass2: report.pass2,
        desktopLogin: report.viewports.desktop?.screens?.login?.checks,
        mobileLogin: report.viewports.mobile?.screens?.login?.checks,
      },
      null,
      2
    )
  );
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
