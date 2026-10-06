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
  let code = '';
  for (let i = 0; i < 10 && !code; i++) {
    try { code = JSON.parse(execSync(`python3 e2e/onboarding/lib/stg_logs.py ${email}`, { cwd: '/Users/mac/projects/KlassApp-wt-dashv2', timeout: 60000 }).toString().trim()).code || ''; } catch (e) { code = ''; }
    if (!code) await p.waitForTimeout(6000);
  }
  await p.fill('[data-testid="verify-code-input"]', code);
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p.click('[data-testid="ap-primary-submit"]')]);
  await p.waitForTimeout(2500);
  if (p.url().includes('/register/verify')) {
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p.evaluate(() => { const f = document.getElementById('verify-continue-form'); if (f) f.requestSubmit(); })]);
  }
  const signupOk = !p.url().includes('/register/verify');
  console.log(signupOk ? 'PASS signup -> ' + p.url().replace(BASE, '') : 'FAIL signup ' + p.url());
  fs.writeFileSync('/Users/mac/.openclaw-autoclaw/workspace/.openclaw/tmp/taskc/smoke4-email.txt', email);
  await ctx.close();

  const ctx2 = await b.newContext({ viewport: { width: 1280, height: 800 } });
  const p2 = await ctx2.newPage();
  await p2.goto(`${BASE}/login`, { waitUntil: 'load' });
  await p2.fill('#email', process.env.DEMO_EMAIL);
  await p2.fill('#password', pw);
  await Promise.all([p2.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p2.click('button[type="submit"]')]);
  await p2.waitForTimeout(900);
  const demoOk = p2.url().includes('/admin/dashboard') && /Demo Junior/i.test(await p2.textContent('body'));
  console.log(demoOk ? 'PASS demo login' : 'FAIL demo login ' + p2.url());
  await b.close();
  process.exit(signupOk && demoOk ? 0 : 1);
})();
