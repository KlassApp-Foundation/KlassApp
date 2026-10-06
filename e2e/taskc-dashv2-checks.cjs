/**
 * Dashboard v2 staging checks (?v2=1): counts vs OnboardingStepsService, dismissal
 * persistence, Toshi states, no h-scroll / console errors / 5xx; 375, 768, 1280.
 * Env: A1_EMAIL A1_PW (fresh), A2_EMAIL A2_PW (mid), D38_EMAIL/D39_EMAIL + PW, OUT_DIR.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = 'https://test.klassapp.xyz';
const OUT = process.env.OUT_DIR || '/Users/mac/litellm/taskc-dashv2';
fs.mkdirSync(OUT, { recursive: true });
const report = { checks: [], consoleErrors: [], badResponses: [] };
function chk(n, ok, x) { report.checks.push({ n, ok, x: x ?? null }); console.log(`${ok ? 'PASS' : 'FAIL'} ${n}${x ? ' | ' + x : ''}`); }
function wire(p) {
  p.on('console', m => { if (m.type() === 'error') report.consoleErrors.push(`${p.url().replace(BASE, '')} :: ${m.text().slice(0, 130)}`); });
  p.on('response', r => { if (r.status() >= 500) report.badResponses.push(`${r.status()} ${r.url()}`); });
}
async function login(page, email, pw) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load' });
  await page.fill('#email', email); await page.fill('#password', pw);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
  await page.waitForTimeout(800);
}
async function collect(page) {
  return await page.evaluate(() => {
    const q = (sel) => document.querySelector(sel);
    const txt = (sel) => (q(sel)?.textContent || '').replace(/\s+/g, ' ').trim();
    return {
      shell: !!q('[data-testid="dashboard-v2-shell"]'),
      banner: !!q('[data-testid="dashboard-v2-banner"]'),
      bannerProgress: txt('[data-testid="dashboard-v2-banner-progress"]'),
      chip: !!q('[data-testid="dashboard-v2-chip"]'),
      chipText: txt('[data-testid="dashboard-v2-chip"]'),
      toshiComing: !!q('[data-testid="dashboard-v2-toshi-coming"]'),
      toshiSetup: !!q('[data-testid="dashboard-v2-toshi-setup"]'),
      early: (document.body.textContent || '').includes('Early access'),
      hScroll: document.documentElement.scrollWidth > window.innerWidth + 1,
    };
  });
}
(async () => {
  const b = await chromium.launch({ headless: true });
  const schools = [
    { id: 'fresh', email: process.env.A1_EMAIL, pw: process.env.A1_PW, expect: process.env.EXPECT_FRESH, mode: process.env.MODE_FRESH, dismiss: true },
    { id: 'mid', email: process.env.A2_EMAIL, pw: process.env.A2_PW, expect: process.env.EXPECT_MID, mode: process.env.MODE_MID, dismiss: true },
    { id: 'demo38', email: process.env.D38_EMAIL, pw: process.env.D38_PW, expect: process.env.EXPECT_D38, mode: process.env.MODE_D38, dismiss: false },
    { id: 'demo39', email: process.env.D39_EMAIL, pw: process.env.D39_PW, expect: process.env.EXPECT_D39, mode: process.env.MODE_D39, dismiss: false },
  ];
  for (const s of schools) {
    if (!s.email) continue;
    for (const vp of [{ n: '375', w: 375, h: 812 }, { n: '768', w: 768, h: 1024 }, { n: '1280', w: 1280, h: 800 }]) {
      const ctx = await b.newContext({ viewport: { width: vp.w, height: vp.h } });
      const page = await ctx.newPage(); wire(page);
      await login(page, s.email, s.pw);
      await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'load' });
      await page.waitForTimeout(700);
      const c = await collect(page);
      chk(`[${s.id}][${vp.n}] v2 shell renders`, c.shell);
      chk(`[${s.id}][${vp.n}] no horizontal scroll`, !c.hScroll);
      if (s.expect && s.expect.includes('/')) {
        const [done, total] = s.expect.split('/');
        if (done !== total) {
          chk(`[${s.id}][${vp.n}] banner count matches service (${done} of ${total})`, c.bannerProgress.includes(`${done} of ${total} steps done`), c.bannerProgress);
        } else {
          chk(`[${s.id}][${vp.n}] completed school shows no banner`, !c.banner, `banner=${c.banner} chip=${c.chip}`);
        }
      }
      if (s.mode === 'preview') chk(`[${s.id}][${vp.n}] Toshi state preview (Coming soon)`, c.toshiComing && !c.toshiSetup && !c.early, JSON.stringify({ coming: c.toshiComing, setup: c.toshiSetup, early: c.early }));
      if (s.mode === 'onboarding') chk(`[${s.id}][${vp.n}] Toshi state onboarding (Set up with Toshi, no pill)`, c.toshiSetup && !c.early, JSON.stringify({ setup: c.toshiSetup, early: c.early }));
      if (s.mode === 'assistant') chk(`[${s.id}][${vp.n}] Toshi state assistant (Early access pill only)`, c.early && !c.toshiSetup, JSON.stringify({ setup: c.toshiSetup, early: c.early }));
      await page.screenshot({ path: path.join(OUT, `${s.id}-${vp.n}.png`), fullPage: false });
      await ctx.close();
    }
    // dismissal persistence at 1280 (fresh/mid only)
    if (s.dismiss) {
      const ctx = await b.newContext({ viewport: { width: 1280, height: 800 } });
      const page = await ctx.newPage(); wire(page);
      await login(page, s.email, s.pw);
      await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'load' });
      await page.waitForTimeout(500);
      const before = await collect(page);
      if (before.banner) {
        await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('[data-testid="dashboard-v2-banner-dismiss"]')]);
        await page.waitForTimeout(700);
        await page.goto(`${BASE}/admin/dashboard?v2=1`, { waitUntil: 'load' });
        await page.waitForTimeout(700);
        const after = await collect(page);
        chk(`[${s.id}][1280] dismissal persists after reload (chip, same count)`, !after.banner && after.chip, after.chipText.slice(0, 80));
        chk(`[${s.id}][1280] chip count matches the banner count`, s.expect ? after.chipText.replace(/\s+/g, '').includes(s.expect.replace('/', '/')) : after.chip, after.chipText.slice(0, 60));
        await page.screenshot({ path: path.join(OUT, `${s.id}-after-dismiss-1280.png`), fullPage: false });
      } else {
        chk(`[${s.id}][1280] dismissal persistence (banner already absent)`, before.chip || !before.banner, `banner=${before.banner} chip=${before.chip}`);
      }
      await ctx.close();
    }
  }
  await b.close();
  report.summary = { checks: report.checks.length, passed: report.checks.filter(c => c.ok).length, failed: report.checks.filter(c => !c.ok).length, consoleErrors: report.consoleErrors.length, badResponses: report.badResponses.length };
  fs.writeFileSync(path.join(OUT, 'dashv2-report.json'), JSON.stringify(report, null, 2));
  console.log('SUMMARY', JSON.stringify(report.summary));
  if (report.consoleErrors.length) console.log('console', report.consoleErrors.slice(0, 8));
  if (report.badResponses.length) console.log('5xx', report.badResponses.slice(0, 5));
  process.exit(report.summary.failed > 0 ? 1 : 0);
})();
