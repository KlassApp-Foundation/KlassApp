const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
(async () => {
  const BASE = 'https://test.klassapp.xyz';
  const ts = Date.now();
  const email = `e2e.signup.smoke.${ts}@example.com`;
  const pw = process.env.SMOKE_PW;
  const b = await chromium.launch({ headless: true });
  const ctx = await b.newContext({ viewport: { width: 1280, height: 800 } });
  const p = await ctx.newPage();
  await p.goto(`${BASE}/register`, { waitUntil: 'load' });
  await p.fill('#name', 'Suite Signup Smoke');
  await p.fill('#email', email);
  await p.fill('#phone', '070' + String(ts).slice(-7));
  await p.fill('#password', pw);
  await p.fill('#password-confirm', pw);
  await p.check('#termsandcondn');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p.click('button[type="submit"]')]);
  console.log(p.url().includes('/register/verify') ? 'PASS signup reaches verify' : 'FAIL ' + p.url());

  let code = '';
  for (let i = 0; i < 10 && !code; i++) {
    try {
      code = execSync(`python3 e2e/onboarding/lib/stg_logs.py ${email}`, { cwd: '/Users/mac/projects/KlassApp-wt-dashv2', timeout: 60000 }).toString();
      code = (JSON.parse(code.trim()).code) || '';
    } catch (e) { code = ''; }
    if (!code) await p.waitForTimeout(6000);
  }
  console.log(code ? 'PASS code read' : 'FAIL no code');
  await p.fill('[data-testid="verify-code-input"]', code);
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p.click('[data-testid="ap-primary-submit"]')]);
  await p.waitForTimeout(2500);
  // The tab moves on via the hidden continue form once the email is confirmed.
  if (p.url().includes('/register/verify')) {
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p.evaluate(() => { const f = document.getElementById('verify-continue-form'); if (f) f.requestSubmit(); })]);
    await p.waitForTimeout(1500);
  }
  const done = !p.url().includes('/register/verify');
  const err = ((await p.locator('[data-testid="auth-flash-error"]').textContent().catch(() => '')) || '').trim();
  console.log(done ? 'PASS signup continues into the app (' + p.url().replace(BASE, '') + ')' : 'FAIL still on verify err=' + err);
  await p.screenshot({ path: '/Users/mac/litellm/task1-smoke/signup3.png' }).catch(() => {});
  fs.writeFileSync('/Users/mac/.openclaw-autoclaw/workspace/.openclaw/tmp/taskc/smoke-signup-result.txt', (done ? 'PASS' : 'FAIL') + '|' + p.url() + '|' + email);
  await b.close();
})();
