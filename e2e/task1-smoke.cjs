const { chromium } = require('playwright');
const fs = require('fs');
(async () => {
  const BASE = 'https://test.klassapp.xyz';
  const ts = Date.now();
  const email = `e2e.signup.smoke.${ts}@example.com`;
  const pw = process.env.SMOKE_PW;
  const b = await chromium.launch({ headless: true });

  // 1) Sign-up smoke
  const ctx = await b.newContext({ viewport: { width: 1280, height: 800 } });
  const p = await ctx.newPage();
  await p.goto(`${BASE}/register`, { waitUntil: 'load' });
  await p.fill('#name', 'Suite Signup Smoke');
  await p.fill('#email', email);
  await p.fill('#phone', '070' + String(ts).slice(-7));
  await p.fill('#password', pw);
  await p.fill('#password-confirm', pw);
  await p.check('#termsandcondn');
  await Promise.all([p.waitForURL('**/admin/dashboard', { timeout: 90000 }).catch(() => null), p.click('button[type="submit"]')]);
  const ok = p.url().includes('/admin/dashboard');
  console.log(ok ? 'PASS signup reaches the dashboard' : 'FAIL signup: ' + p.url());
  await p.screenshot({ path: '/Users/mac/litellm/task1-smoke/signup.png' }).catch(() => {});
  fs.writeFileSync('/Users/mac/.openclaw-autoclaw/workspace/.openclaw/tmp/taskc/smoke-email.txt', email);
  await ctx.close();

  // 2) Demo login smoke
  const ctx2 = await b.newContext({ viewport: { width: 1280, height: 800 } });
  const p2 = await ctx2.newPage();
  await p2.goto(`${BASE}/login`, { waitUntil: 'load' });
  await p2.fill('#email', process.env.DEMO_EMAIL);
  await p2.fill('#password', pw);
  await Promise.all([p2.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), p2.click('button[type="submit"]')]);
  await p2.waitForTimeout(900);
  const ok2 = p2.url().includes('/admin/dashboard');
  const body = ok2 ? await p2.textContent('body') : '';
  const named = /Demo Junior/i.test(body);
  console.log(ok2 && named ? 'PASS demo login (Demo Junior dashboard)' : `FAIL demo login url=${p2.url()} named=${named}`);
  await p2.screenshot({ path: '/Users/mac/litellm/task1-smoke/demo-login.png' }).catch(() => {});
  await b.close();
  process.exit(ok && ok2 && named ? 0 : 1);
})();
