const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const base = process.env.AUTH_PREVIEW_BASE || 'http://127.0.0.1:8020';
  const outDir = path.join(process.cwd(), 'e2e/screenshots/auth-preview');
  fs.mkdirSync(outDir, { recursive: true });

  const viewports = [
    { name: '1440', width: 1440, height: 900 },
    { name: '1024', width: 1024, height: 768 },
    { name: '900', width: 900, height: 700 },
    { name: '760', width: 760, height: 700 },
  ];

  const screens = [
    { path: '/preview/login', key: 'login' },
    { path: '/preview/register', key: 'register' },
    { path: '/preview/reset-request', key: 'reset-request' },
    { path: '/preview/reset-code', key: 'reset-code' },
    { path: '/preview/reset-newpw', key: 'reset-newpw' },
    { path: '/preview/force-change-password', key: 'force-change-password' },
  ];

  const report = {
    pass: true,
    base,
    viewports: {},
    screens: {},
    pass2: {},
    formValidation: {},
    consoleErrors: [],
    deviations: [
      'Reset code keeps single input (pattern=[0-9]{6} maxlength=6); mockup six-box UI not ported (open decision).',
      'Typography: Sora/DM Sans (app brand / layouts.empty) instead of mockup Bricolage/Inter.',
      'Live /login /register etc. untouched — preview routes only until cutover.',
    ],
  };

  const browser = await chromium.launch({ headless: true });

  // --- Viewport + console + field presence ---
  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e)));
    page.on('console', (msg) => {
      if (msg.type() === 'error') errors.push(msg.text());
    });

    const vpResult = { screens: {}, pass: true };
    for (const screen of screens) {
      const resp = await page.goto(base + screen.path, { waitUntil: 'networkidle', timeout: 60000 });
      await page.waitForTimeout(400);
      const checks = await page.evaluate(() => {
        const form = document.querySelector('form[data-testid^="preview-"]') || document.querySelector('form');
        const names = form
          ? Array.from(form.querySelectorAll('input[name]')).map((i) => i.getAttribute('name'))
          : [];
        return {
          screen: document.querySelector('[data-ap-screen]')?.getAttribute('data-ap-screen') || null,
          names,
          hasPrimary: !!document.querySelector('[data-testid="ap-primary-submit"]'),
        };
      });
      const shot = path.join(outDir, `${screen.key}-${vp.name}.png`);
      await page.screenshot({ path: shot, fullPage: true });
      const ok = resp.ok() && !!checks.screen && checks.hasPrimary;
      vpResult.screens[screen.key] = { status: resp.status(), checks, shot, pass: ok };
      if (!ok) vpResult.pass = false;
    }

    vpResult.errors = errors;
    if (errors.length) {
      vpResult.pass = false;
      report.consoleErrors.push(...errors.map((e) => ({ vp: vp.name, e })));
    }
    report.viewports[vp.name] = vpResult;
    if (!vpResult.pass) report.pass = false;
    await page.close();
  }

  // --- Pass-2 measured properties (desktop) ---
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.goto(base + '/preview/login?demo_errors=1', { waitUntil: 'networkidle' });

    const loginMetrics = await page.evaluate(() => {
      const toggle = document.querySelector('.ap-password-toggle');
      const svg = toggle?.querySelector('svg');
      const submit = document.querySelector('[data-testid="ap-primary-submit"]');
      const alert = document.querySelector('[data-testid="auth-flash-error"]');
      const cs = (el) => (el ? getComputedStyle(el) : null);
      const t = cs(toggle);
      const s = cs(submit);
      const a = cs(alert);
      const svgBox = svg ? svg.getBoundingClientRect() : null;
      return {
        toggle: t ? { w: Math.round(parseFloat(t.width)), h: Math.round(parseFloat(t.height)) } : null,
        icon: svgBox ? { w: Math.round(svgBox.width), h: Math.round(svgBox.height) } : null,
        submitBg: s ? s.backgroundColor : null,
        alert: a
          ? { color: a.color, backgroundColor: a.backgroundColor, borderColor: a.borderTopColor }
          : null,
        googleHref: document.querySelector('[data-testid="login-google"]')?.getAttribute('href') || null,
        googleTag: document.querySelector('[data-testid="login-google"]')?.tagName || null,
      };
    });

    // hover primary
    await page.hover('[data-testid="ap-primary-submit"]');
    await page.waitForTimeout(200);
    const hoverBg = await page.evaluate(() => getComputedStyle(document.querySelector('[data-testid="ap-primary-submit"]')).backgroundColor);

    await page.goto(base + '/preview/register', { waitUntil: 'networkidle' });
    const registerGoogle = await page.evaluate(() => {
      const btn = document.querySelector('[data-testid="register-google"]');
      return {
        tag: btn?.tagName,
        formaction: btn?.getAttribute('formaction'),
        formnovalidate: btn?.hasAttribute('formnovalidate'),
        formmethod: btn?.getAttribute('formmethod'),
      };
    });

    await page.goto(base + '/preview/reset-code', { waitUntil: 'networkidle' });
    const codeInput = await page.evaluate(() => {
      const el = document.querySelector('[data-testid="reset-code-input"]');
      return {
        name: el?.getAttribute('name'),
        pattern: el?.getAttribute('pattern'),
        maxlength: el?.getAttribute('maxlength'),
        boxCount: document.querySelectorAll('.code-box').length,
      };
    });

    await page.goto(base + '/preview/force-change-password', { waitUntil: 'networkidle' });
    const force = await page.evaluate(() => {
      const items = Array.from(document.querySelectorAll('[data-testid="password-requirements"] li')).map((li) => li.textContent.trim());
      const body = document.body.innerText.toLowerCase();
      return {
        rules: items,
        hasSkip: body.includes('skip'),
        hasCancel: body.includes('cancel'),
        hasSignOut: body.includes('sign out') || body.includes('sign-out'),
      };
    });

    const rgb = (r, g, b) => `rgb(${r}, ${g}, ${b})`;
    const pass2 = {
      toggle44: loginMetrics.toggle && loginMetrics.toggle.w === 44 && loginMetrics.toggle.h === 44,
      icon20: loginMetrics.icon && loginMetrics.icon.w === 20 && loginMetrics.icon.h === 20,
      primaryGreen: loginMetrics.submitBg === rgb(34, 197, 94),
      primaryHover: hoverBg === rgb(22, 163, 74),
      errorTokens:
        loginMetrics.alert &&
        loginMetrics.alert.color === rgb(220, 38, 38) &&
        loginMetrics.alert.backgroundColor === rgb(254, 242, 242) &&
        (loginMetrics.alert.borderColor === rgb(254, 202, 202) ||
          loginMetrics.alert.borderColor === rgb(254, 202, 202)),
      loginGoogleGet: loginMetrics.googleTag === 'A' && (loginMetrics.googleHref || '').includes('/auth/google'),
      registerGooglePost:
        registerGoogle.tag === 'BUTTON' &&
        !!registerGoogle.formaction &&
        registerGoogle.formnovalidate === true,
      singleCodeInput:
        codeInput.name === 'code' &&
        codeInput.pattern === '[0-9]{6}' &&
        codeInput.maxlength === '6' &&
        codeInput.boxCount === 0,
      forceFiveRules: force.rules.length === 5,
      forceNoEscape: !force.hasSkip && !force.hasCancel && !force.hasSignOut,
      raw: { loginMetrics, hoverBg, registerGoogle, codeInput, force },
    };
    pass2.pass = Object.entries(pass2)
      .filter(([k]) => !['raw', 'pass'].includes(k))
      .every(([, v]) => v === true);
    report.pass2 = pass2;
    if (!pass2.pass) report.pass = false;
    await page.close();
  }

  // --- Real form POST validation E2E ---
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    const results = {};

    // Login empty → validation
    await page.goto(base + '/preview/login', { waitUntil: 'networkidle' });
    await page.locator('[data-testid="preview-login-form"] [data-testid="ap-primary-submit"]').click();
    // HTML5 required may block — clear and use request API style: fill invalid then remove required via evaluate post
    // Prefer real POST via page.request for certainty, then also try browser submit
    const loginPost = await page.request.post(base + '/login', {
      form: { email: '', password: '', _token: await page.locator('input[name="_token"]').inputValue() },
      headers: { Referer: base + '/preview/login' },
      maxRedirects: 0,
    });
    results.loginEmpty = {
      status: loginPost.status(),
      location: loginPost.headers()['location'] || null,
      // 302 back expected
      pass: loginPost.status() === 302,
    };

    // Reset request invalid email
    await page.goto(base + '/preview/reset-request', { waitUntil: 'networkidle' });
    const token2 = await page.locator('input[name="_token"]').inputValue();
    const resetPost = await page.request.post(base + '/password/email', {
      form: { email: 'not-an-email', _token: token2 },
      headers: { Referer: base + '/preview/reset-request' },
      maxRedirects: 0,
    });
    results.resetRequestInvalid = {
      status: resetPost.status(),
      location: resetPost.headers()['location'] || null,
      pass: resetPost.status() === 302,
    };

    // Reset code wrong length / verify
    await page.goto(base + '/preview/reset-code', { waitUntil: 'networkidle' });
    const token3 = await page.locator('input[name="_token"]').inputValue();
    const codePost = await page.request.post(base + '/password/reset-code/verify', {
      form: { email: 'grace@school.ug', code: '000000', _token: token3 },
      headers: { Referer: base + '/preview/reset-code' },
      maxRedirects: 0,
    });
    results.resetCodeVerify = {
      status: codePost.status(),
      location: codePost.headers()['location'] || null,
      pass: [302, 422, 419].includes(codePost.status()) || codePost.status() === 302,
    };

    // Register missing fields → validation redirect
    await page.goto(base + '/preview/register', { waitUntil: 'networkidle' });
    const token4 = await page.locator('input[name="_token"]').inputValue();
    const regPost = await page.request.post(base + '/register', {
      form: {
        name: '',
        email: 'bad',
        phone: '',
        password: 'x',
        password_confirmation: 'y',
        _token: token4,
      },
      headers: { Referer: base + '/preview/register' },
      maxRedirects: 0,
    });
    results.registerInvalid = {
      status: regPost.status(),
      location: regPost.headers()['location'] || null,
      pass: regPost.status() === 302,
    };

    // Force-change without auth → redirect to login (middleware)
    await page.goto(base + '/preview/force-change-password', { waitUntil: 'networkidle' });
    const token5 = await page.locator('input[name="_token"]').inputValue();
    const forcePost = await page.request.post(base + '/password/force-change', {
      form: {
        current_password: 'OldPass1!',
        password: 'weak',
        password_confirmation: 'weak',
        _token: token5,
      },
      headers: { Referer: base + '/preview/force-change-password' },
      maxRedirects: 0,
    });
    results.forceChangeUnauthed = {
      status: forcePost.status(),
      location: forcePost.headers()['location'] || null,
      pass: forcePost.status() === 302,
    };

    report.formValidation = results;
    if (!Object.values(results).every((r) => r.pass)) report.pass = false;
    await page.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(outDir, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  process.exit(report.pass ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
