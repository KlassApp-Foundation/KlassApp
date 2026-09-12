const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const base = process.env.ERRORS_PREVIEW_BASE || 'http://127.0.0.1:8020';
  const outDir = path.join(process.cwd(), 'e2e/screenshots/errors-preview');
  fs.mkdirSync(outDir, { recursive: true });

  const viewports = [
    { name: '1440', width: 1440, height: 900 },
    { name: '1024', width: 1024, height: 768 },
    { name: '900', width: 900, height: 700 },
    { name: '760', width: 760, height: 700 },
  ];
  const codes = ['404', '419', '500'];

  const report = {
    pass: true,
    base,
    viewports: {},
    checks: {},
    consoleErrors: [],
    deviations: [
      'Typography: Sora/DM Sans (existing errors.illustrated-layout + auth), not mockup Bricolage/Inter.',
      'Logo: SVG asset (klassapp-logo-primary.svg) instead of mockup text wordmark.',
      'Pass-2 lives only under resources/views/errors-preview/; live resources/views/errors/ untouched (A/B pattern).',
      '401/403/429/503 not in Pass-2 mockup scope.',
    ],
  };

  const browser = await chromium.launch({ headless: true });

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e)));
    page.on('console', (msg) => {
      if (msg.type() === 'error') errors.push(msg.text());
    });

    const vpResult = { screens: {}, pass: true };
    for (const code of codes) {
      const resp = await page.goto(`${base}/preview/errors/${code}`, {
        waitUntil: 'networkidle',
        timeout: 60000,
      });
      await page.waitForTimeout(300);
      const checks = await page.evaluate(() => ({
        shell: document.body.getAttribute('data-error-shell'),
        code: document.body.getAttribute('data-error-code'),
        title: document.querySelector('.err-title')?.textContent?.trim() || null,
        hasException: !!document.querySelector('[data-testid="exception-present"]'),
        primary: document.querySelector('[data-testid="err-primary"]')?.textContent?.trim() || null,
      }));
      const shot = path.join(outDir, `${code}-${vp.name}.png`);
      await page.screenshot({ path: shot, fullPage: true });
      const ok =
        resp.ok() &&
        checks.shell === 'pass2' &&
        checks.code === code &&
        checks.hasException &&
        !!checks.title;
      vpResult.screens[code] = { status: resp.status(), checks, shot, pass: ok };
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

  // Measured Pass-2 tokens + 419 copy at desktop
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.goto(`${base}/preview/errors/419`, { waitUntil: 'networkidle' });
    const measured = await page.evaluate(() => {
      const primary = document.querySelector('[data-testid="err-primary"]');
      const card = document.querySelector('.err-card');
      const msg = document.querySelector('.err-msg')?.textContent || '';
      const cs = (el) => (el ? getComputedStyle(el) : null);
      const p = cs(primary);
      const c = cs(card);
      return {
        msg: msg.replace(/\s+/g, ' ').trim(),
        primaryText: primary?.textContent?.replace(/\s+/g, ' ').trim(),
        primaryBg: p?.backgroundColor,
        cardRadius: c ? Math.round(parseFloat(c.borderRadius)) : null,
        fontBody: cs(document.body)?.fontFamily || '',
        fontTitle: cs(document.querySelector('.err-title'))?.fontFamily || '',
      };
    });

    await page.goto(`${base}/preview/errors/500`, { waitUntil: 'networkidle' });
    const leak = await page.evaluate(() => document.body.innerText);

    const rgb = (r, g, b) => `rgb(${r}, ${g}, ${b})`;
    const checks = {
      amberPrimary: measured.primaryBg === rgb(217, 119, 6),
      refreshCopy: measured.primaryText.includes('Refresh and try again'),
      verbatim419:
        measured.msg.includes('no changes have been lost') &&
        measured.msg.includes('Your session has expired'),
      soraTitle: /Sora/i.test(measured.fontTitle),
      dmSansBody: /DM Sans/i.test(measured.fontBody),
      noLeak500: !leak.includes('TypeError') && !leak.includes('$schoolId') && !leak.includes('synthetic HttpException'),
      raw: { measured },
    };
    checks.pass = Object.entries(checks)
      .filter(([k]) => !['raw', 'pass'].includes(k))
      .every(([, v]) => v === true);
    report.checks = checks;
    if (!checks.pass) report.pass = false;
    await page.close();
  }

  // Real 404 path must still use illustrated-layout (NOT Pass-2)
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    const resp = await page.goto(`${base}/phase-c-missing-${Date.now()}`, {
      waitUntil: 'networkidle',
      timeout: 60000,
    });
    const real = await page.evaluate(() => ({
      pass2Shell: document.body.getAttribute('data-error-shell'),
      hasKlassShell: !!document.querySelector('.klass-error-shell'),
      hasErrCard: !!document.querySelector('.err-card'),
      previewBadge: !!document.querySelector('.err-preview-badge'),
      title: document.querySelector('.klass-error-title, .err-title')?.textContent?.trim() || null,
    }));
    report.real404 = {
      status: resp.status(),
      real,
      pass:
        resp.status() === 404 &&
        real.hasKlassShell === true &&
        real.hasErrCard === false &&
        real.pass2Shell !== 'pass2' &&
        !real.previewBadge,
    };
    if (!report.real404.pass) report.pass = false;
    await page.screenshot({ path: path.join(outDir, 'real-404-illustrated-1440.png'), fullPage: true });
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
