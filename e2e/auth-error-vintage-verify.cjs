/**
 * Playwright: vintage-paper auth + error preview shells at 3 breakpoints.
 * OD source: klassapp-auth-error-vintage-paper-v1.html
 * Locks: Sora/DM Sans (not Bricolage/Inter), 44px toggle, green CTAs, error reds,
 * force-change zero escape hatch, no em dashes, no ds-* bleed.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PREVIEW_BASE || 'http://127.0.0.1:8000';
const OUT = path.join(__dirname, 'screenshots/auth-error-vintage');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'tablet', width: 1024, height: 768 },
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
  const report = {
    base: BASE,
    at: new Date().toISOString(),
    viewports: {},
    pass2: {},
    ok: true,
  };

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const consoleErrors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });
    page.on('pageerror', (err) => consoleErrors.push(String(err)));

    const screens = {};

    for (const screen of authScreens) {
      const res = await page.goto(`${BASE}${screen.path}`, { waitUntil: 'networkidle', timeout: 60000 });
      await page.waitForTimeout(300);
      const checks = await page.evaluate(() => {
        const paper = document.querySelector('[data-ap-paper="vintage"]');
        const html = document.documentElement.outerHTML;
        const bodyText = document.body.innerText || '';
        const titleFont = getComputedStyle(document.querySelector('.ap-title') || document.body).fontFamily;
        const bodyFont = getComputedStyle(document.body).fontFamily;
        const bg = getComputedStyle(document.body).backgroundColor;
        return {
          paper: !!paper,
          sora: /Sora/i.test(titleFont) || html.includes('family=Sora'),
          dmSans: /DM Sans/i.test(bodyFont) || html.includes('family=DM+Sans'),
          bricolage: /Bricolage/i.test(html) || /Bricolage/i.test(titleFont),
          interOnly: /Inter/i.test(titleFont) && !/Sora/i.test(titleFont),
          emDash: bodyText.includes('\u2014') || html.includes('\u2014'),
          dsElements: document.querySelectorAll('[class*="ds-"]').length,
          paperRgb: bg,
          hasPrimary: !!document.querySelector('[data-testid="ap-primary-submit"]'),
        };
      });
      const shot = path.join(OUT, `${vp.name}-${screen.key}.png`);
      await page.screenshot({ path: shot, fullPage: true });
      const pass =
        res.ok() &&
        checks.paper &&
        checks.sora &&
        checks.dmSans &&
        !checks.bricolage &&
        !checks.interOnly &&
        !checks.emDash &&
        checks.dsElements === 0 &&
        checks.hasPrimary;
      screens[screen.key] = { status: res.status(), checks, shot, pass };
      if (!pass) report.ok = false;
    }

    for (const code of errorCodes) {
      const res = await page.goto(`${BASE}/preview/errors/${code}`, {
        waitUntil: 'networkidle',
        timeout: 60000,
      });
      await page.waitForTimeout(300);
      const checks = await page.evaluate(() => {
        const html = document.documentElement.outerHTML;
        const bodyText = document.body.innerText || '';
        const titleFont = getComputedStyle(document.querySelector('.err-title') || document.body).fontFamily;
        return {
          paper: document.body.getAttribute('data-error-paper') === 'vintage',
          shell: document.body.getAttribute('data-error-shell') === 'pass2',
          sora: /Sora/i.test(titleFont) || html.includes('family=Sora'),
          bricolage: /Bricolage/i.test(html),
          emDash: bodyText.includes('\u2014'),
          dsElements: document.querySelectorAll('[class*="ds-"]').length,
          paperVar: html.includes('--paper-base'),
        };
      });
      const shot = path.join(OUT, `${vp.name}-error-${code}.png`);
      await page.screenshot({ path: shot, fullPage: true });
      const pass =
        res.ok() &&
        checks.paper &&
        checks.shell &&
        checks.sora &&
        !checks.bricolage &&
        !checks.emDash &&
        checks.dsElements === 0 &&
        checks.paperVar;
      screens[`error-${code}`] = { status: res.status(), checks, shot, pass };
      if (!pass) report.ok = false;
    }

    report.viewports[vp.name] = { screens, consoleErrors, pass: consoleErrors.length === 0 };
    if (consoleErrors.length) report.ok = false;
    await page.close();
  }

  // Desktop pass-2 lock measurements
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.goto(`${BASE}/preview/login?demo_errors=1`, { waitUntil: 'networkidle' });
    const metrics = await page.evaluate(() => {
      const toggle = document.querySelector('.ap-password-toggle');
      const submit = document.querySelector('[data-testid="ap-primary-submit"]');
      const alert = document.querySelector('[data-testid="auth-flash-error"]');
      const cs = (el) => (el ? getComputedStyle(el) : null);
      const t = cs(toggle);
      const s = cs(submit);
      const a = cs(alert);
      return {
        toggle: t ? { w: Math.round(parseFloat(t.width)), h: Math.round(parseFloat(t.height)) } : null,
        submitBg: s ? s.backgroundColor : null,
        alert: a
          ? { color: a.color, backgroundColor: a.backgroundColor, borderColor: a.borderTopColor }
          : null,
        paperBg: getComputedStyle(document.body).backgroundColor,
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
    const pass2 = {
      toggle44: metrics.toggle && metrics.toggle.w === 44 && metrics.toggle.h === 44,
      primaryGreen: metrics.submitBg === rgb(34, 197, 94),
      primaryHover: hoverBg === rgb(22, 163, 74),
      errorTokens:
        metrics.alert &&
        metrics.alert.color === rgb(220, 38, 38) &&
        metrics.alert.backgroundColor === rgb(254, 242, 242),
      paperBody: metrics.paperBg === rgb(245, 240, 230), // #F5F0E6
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
  console.log(JSON.stringify({ ok: report.ok, pass2: report.pass2, viewports: Object.keys(report.viewports) }, null, 2));
  process.exit(report.ok ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
