/**
 * Piece 2 / PR3 — docking (≥1280) + fullscreen (≤640) + pill collapse.
 * Multi-role: school admin + subject teacher. Pulse canary after CSS publish.
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_PASSWORD=demo123 \
 *   node e2e/toshi-piece2-docking-pill-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const ROLES = [
  {
    key: 'admin',
    email: process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz',
    home: '/admin/dashboard',
  },
  {
    key: 'teacher',
    email: process.env.TEACHER_EMAIL || 'phase4.teacher@klassapp.xyz',
    home: '/teacher/dashboard',
  },
];
const OUT = path.join(__dirname, 'screenshots/toshi-piece2-docking-pill');
fs.mkdirSync(OUT, { recursive: true });

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

async function login(page, email) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', email);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

async function openToshi(page) {
  await page.evaluate(() => {
    document.body.classList.remove('toshi-collapsed');
  });
  // Staging hibernation / Livewire boot can lag past 30s on cold requests.
  await page.locator('[data-toshi-root]').waitFor({ state: 'attached', timeout: 90000 });
  const panel = page.locator('#toshi-panel');
  if (!(await panel.isVisible().catch(() => false))) {
    const pill = page.locator('[data-testid="toshi-pill"], #toshi-pill');
    if (await pill.isVisible().catch(() => false)) {
      await pill.click();
      await page.waitForTimeout(400);
    } else {
      await page.evaluate(async () => {
        const c = window.Livewire?.all?.().find((x) => (x.name || '').includes('agent-toshi'));
        if (c?.$wire?.set) await c.$wire.set('visible', true);
      }).catch(() => null);
      await page.waitForTimeout(800);
    }
  }
  await page.locator('[data-testid="toshi-header"]').waitFor({ state: 'visible', timeout: 60000 });
}

async function measureDock(page) {
  return page.evaluate(() => {
    const root = document.querySelector('[data-toshi-root]');
    const panel = document.querySelector('#toshi-panel');
    const pill = document.querySelector('[data-testid="toshi-pill"], #toshi-pill');
    const toggle = document.querySelector('[data-testid="toshi-toggle"], #toshi-toggle');
    const sidebar = document.querySelector('.sidebar');
    const rs = root ? getComputedStyle(root) : null;
    const ps = panel ? getComputedStyle(panel) : null;
    const before = panel ? getComputedStyle(panel, '::before') : null;
    return {
      collapsed: document.body.classList.contains('toshi-collapsed'),
      rootPosition: rs?.position || null,
      rootWidth: root ? Math.round(root.getBoundingClientRect().width) : null,
      panelWidth: panel ? Math.round(panel.getBoundingClientRect().width) : null,
      panelHeight: panel ? Math.round(panel.getBoundingClientRect().height) : null,
      panelDisplay: ps?.display || null,
      panelRadius: ps?.borderTopLeftRadius || null,
      panelBorderLeft: ps?.borderLeftColor || null,
      accent: before?.backgroundColor || null,
      pillDisplay: pill ? getComputedStyle(pill).display : null,
      pillBorder: pill ? getComputedStyle(pill).borderTopColor : null,
      toggleDisplay: toggle ? getComputedStyle(toggle).display : null,
      toggleBg: toggle ? getComputedStyle(toggle).backgroundColor : null,
      sidebarOverflow: sidebar ? getComputedStyle(sidebar).overflowY : null,
      sidebarPosition: sidebar ? getComputedStyle(sidebar).position : null,
      viewportW: window.innerWidth,
      viewportH: window.innerHeight,
    };
  });
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = {
    base: BASE,
    at: new Date().toISOString(),
    pulse: {},
    roles: {},
    ok: true,
  };

  const cssPage = await browser.newPage();
  const cssRes = await cssPage.goto(`${BASE}/vendor/toshi-ui/toshi-ui.css`, { waitUntil: 'load', timeout: 60000 });
  const cssText = await cssRes.text();
  report.pulse.hasLedgerThead = cssText.includes('.ds-table-ledger thead');
  report.pulse.hasBlur = /backdrop-filter:\s*blur\(12px\)/.test(cssText);
  report.pulse.hasFrozenBanner = cssText.includes('FROZEN — Pulse design system');
  report.pulse.hasDockingBanner = cssText.includes('PIECE 2 — Docking / pill / fullscreen chrome');
  report.pulse.dockHasClay = cssText.includes('background: #c96442');
  report.pulse.dockBlockHasPulseGreen = cssText.split('FROZEN — Pulse')[0].includes('#22C55E');
  await cssPage.close();

  if (!report.pulse.hasLedgerThead || !report.pulse.hasBlur || !report.pulse.hasFrozenBanner) {
    fail('Pulse canary missing from published toshi-ui.css');
    report.ok = false;
  }
  if (!report.pulse.hasDockingBanner || !report.pulse.dockHasClay || report.pulse.dockBlockHasPulseGreen) {
    fail('Piece 2 docking clay tokens missing or Pulse green still in dock block');
    report.ok = false;
  }

  const clay = 'rgb(201, 100, 66)';

  for (const role of ROLES) {
    const roleReport = { email: role.email, home: role.home, docked: null, collapsed: null, mobile: null };
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    await login(page, role.email);
    console.log(`[${role.key}] post-login url=${page.url()}`);
    await page.goto(`${BASE}${role.home}`, { waitUntil: 'load', timeout: 120000 });
    await page.waitForTimeout(1500);
    console.log(`[${role.key}] home url=${page.url()}`);
    try {
      await openToshi(page);
    } catch (err) {
      await page.screenshot({ path: path.join(OUT, `${role.key}-open-fail.png`), fullPage: false }).catch(() => null);
      const snap = await page.evaluate(() => ({
        url: location.href,
        hasRoot: !!document.querySelector('[data-toshi-root]'),
        bodyClass: document.body.className,
      })).catch(() => ({}));
      console.error(`[${role.key}] openToshi failed`, snap);
      throw err;
    }

    const docked = await measureDock(page);
    roleReport.docked = docked;
    if (docked.rootPosition !== 'static') {
      fail(`[${role.key}] docked root position expected static, got ${docked.rootPosition}`);
      report.ok = false;
    }
    if (docked.rootWidth < 360 || docked.rootWidth > 400) {
      fail(`[${role.key}] docked root width expected ~380, got ${docked.rootWidth}`);
      report.ok = false;
    }
    if (docked.panelWidth < 360 || docked.panelWidth > 400) {
      fail(`[${role.key}] docked panel width expected ~380, got ${docked.panelWidth}`);
      report.ok = false;
    }
    if (docked.panelHeight < 500) {
      fail(`[${role.key}] docked panel height expected full-ish, got ${docked.panelHeight}`);
      report.ok = false;
    }
    if (docked.panelRadius !== '0px') {
      fail(`[${role.key}] docked panel radius expected 0px, got ${docked.panelRadius}`);
      report.ok = false;
    }
    if (docked.accent !== clay) {
      fail(`[${role.key}] docked accent expected ${clay}, got ${docked.accent}`);
      report.ok = false;
    }
    if (docked.pillDisplay !== 'none') {
      fail(`[${role.key}] pill should be hidden while docked open, got ${docked.pillDisplay}`);
      report.ok = false;
    }
    if (docked.sidebarOverflow !== 'auto' && docked.sidebarOverflow !== 'scroll') {
      // sidebar may be absent on some teacher shells — warn only if present null
      if (docked.sidebarOverflow !== null) {
        fail(`[${role.key}] sidebar overflow expected auto/scroll, got ${docked.sidebarOverflow}`);
        report.ok = false;
      }
    }

    await page.screenshot({ path: path.join(OUT, `${role.key}-docked-1280.png`), fullPage: false });

    // Collapse → width 0, pill returns, toggle visible
    await page.evaluate(() => {
      document.body.classList.add('toshi-collapsed');
      const t = document.getElementById('toshi-toggle');
      if (t) t.textContent = '◀';
    });
    await page.waitForTimeout(350);
    // Clear inline display:none on pill from Livewire visible state for chrome check
    await page.evaluate(() => {
      const pill = document.querySelector('#toshi-pill');
      if (pill) pill.style.display = '';
    });
    const collapsed = await measureDock(page);
    roleReport.collapsed = collapsed;
    if (collapsed.rootWidth > 8) {
      fail(`[${role.key}] collapsed root width expected ~0, got ${collapsed.rootWidth}`);
      report.ok = false;
    }
    if (collapsed.pillDisplay === 'none') {
      fail(`[${role.key}] pill should return when collapsed`);
      report.ok = false;
    }
    if (collapsed.pillBorder !== clay) {
      fail(`[${role.key}] pill border expected ${clay}, got ${collapsed.pillBorder}`);
      report.ok = false;
    }
    if (collapsed.toggleDisplay === 'none') {
      fail(`[${role.key}] toggle tab should show when collapsed`);
      report.ok = false;
    }
    if (collapsed.toggleBg !== clay) {
      fail(`[${role.key}] toggle bg expected ${clay}, got ${collapsed.toggleBg}`);
      report.ok = false;
    }
    await page.screenshot({ path: path.join(OUT, `${role.key}-collapsed-1280.png`), fullPage: false });

    // Mobile fullscreen
    await page.setViewportSize({ width: 375, height: 812 });
    await page.evaluate(() => {
      document.body.classList.remove('toshi-collapsed');
    });
    await openToshi(page);
    await page.waitForTimeout(300);
    const mobile = await measureDock(page);
    roleReport.mobile = mobile;
    if (Math.abs(mobile.panelWidth - mobile.viewportW) > 4) {
      fail(`[${role.key}] mobile panel width expected ~viewport (${mobile.viewportW}), got ${mobile.panelWidth}`);
      report.ok = false;
    }
    if (Math.abs(mobile.panelHeight - mobile.viewportH) > 8) {
      fail(`[${role.key}] mobile panel height expected ~viewport (${mobile.viewportH}), got ${mobile.panelHeight}`);
      report.ok = false;
    }
    if (mobile.panelRadius !== '0px') {
      fail(`[${role.key}] mobile panel radius expected 0px, got ${mobile.panelRadius}`);
      report.ok = false;
    }
    if (mobile.accent !== clay) {
      fail(`[${role.key}] mobile accent expected ${clay}, got ${mobile.accent}`);
      report.ok = false;
    }
    await page.screenshot({ path: path.join(OUT, `${role.key}-mobile-375.png`), fullPage: false });

    report.roles[role.key] = roleReport;
    await ctx.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  if (!report.ok) process.exit(1);
  console.log('PASS Piece 2 PR3 docking/pill + multi-role + Pulse canary');
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
