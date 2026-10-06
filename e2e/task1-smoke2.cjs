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
  const reachedVerify = p.url().includes('/register/verify');
  console.log(reachedVerify ? 'PASS signup reaches the email verification step' : 'FAIL signup: ' + p.url());

  let code = '';
  for (let i = 0; i < 10; i++) {
    try {
      code = execSync(`python3 -c "import sys; sys.path.insert(0, 'e2e/onboarding/lib'); import stg_logs; print(stg_logs.extract_code_for_email(sys.argv[1], minutes=10) or '')" ${email}`, { cwd: '/Users/mac/projects/KlassApp-wt-dashv2', timeout: 60000 }).toString().trim();
    } catch (e) { code = ''; }
    if (code) break;
    await p.waitForTimeout(6000);
  }
  console.log(code ? 'PASS verification code read from staging logs' : 'FAIL no code found');
  if (code) {
    await p.fill('[data-testid="verify-code-input"]', code);
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p.click('[data-testid="ap-primary-submit"]')]);
    await p.waitForTimeout(1500);
    const done = !p.url().includes('/register/verify');
    console.log(done ? 'PASS email confirmed, signup continues into the app (' + p.url().replace(BASE, '') + ')' : 'FAIL still on verify: ' + p.url());
    fs.writeFileSync('/Users/mac/.openclaw-autoclaw/workspace/.openclaw/tmp/taskc/smoke-signup-result.txt', (done ? 'PASS' : 'FAIL') + '|' + p.url() + '|' + email);
  }
  await p.screenshot({ path: '/Users/mac/litellm/task1-smoke/signup2.png' }).catch(() => {});
  await ctx.close();

  const ctx2 = await b.newContext({ viewport: { width: 1280, height: 800 } });
  const p2 = await ctx2.newPage();
  await p2.goto(`${BASE}/login`, { waitUntil: 'load' });
  await p2.fill('#email', process.env.DEMO_EMAIL);
  await p2.fill('#password', pw);
  await Promise.all([p2.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p2.click('button[type="submit"]')]);
  await p2.waitForTimeout(900);
  const ok2 = p2.url().includes('/admin/dashboard');
  const body = ok2 ? await p2.textContent('body') : '';
  console.log(ok2 && /Demo Junior/i.test(body) ? 'PASS demo login (Demo Junior dashboard)' : `FAIL demo login url=${p2.url()}`);
  await p2.screenshot({ path: '/Users/mac/litellm/task1-smoke/demo-login2.png' }).catch(() => {});
  await b.close();
})();
