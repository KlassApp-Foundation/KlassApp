/**
 * Piece 2 / PR2 — clay chips + plan/confirm cards (staging).
 * Re-asserts Pulse canary after toshi-ui.css publish.
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=phase4.admin@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/toshi-piece2-cards-chips-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/toshi-piece2-cards-chips');
fs.mkdirSync(OUT, { recursive: true });

function fail(msg) {
  console.error('FAIL:', msg);
  process.exitCode = 1;
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

async function ensureToshiOpen(page) {
  await page.evaluate(() => {
    document.body.classList.remove('toshi-collapsed');
  });
  await page.locator('[data-toshi-root]').waitFor({ state: 'attached', timeout: 30000 });
  const panel = page.locator('#toshi-panel');
  if (!(await panel.isVisible().catch(() => false))) {
    const pill = page.locator('#toshi-pill, .toshi-pill');
    if (await pill.isVisible().catch(() => false)) {
      await pill.click();
      await page.waitForTimeout(500);
    }
  }
  await page.locator('[data-testid="toshi-header"]').waitFor({ state: 'visible', timeout: 30000 });
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = {
    base: BASE,
    email: EMAIL,
    at: new Date().toISOString(),
    pulse: {},
    cards: {},
    ok: true,
  };

  const cssPage = await browser.newPage();
  const cssRes = await cssPage.goto(`${BASE}/vendor/toshi-ui/toshi-ui.css`, { waitUntil: 'load', timeout: 60000 });
  const cssText = await cssRes.text();
  report.pulse.hasLedgerThead = cssText.includes('.ds-table-ledger thead');
  report.pulse.hasBlur = /backdrop-filter:\s*blur\(12px\)/.test(cssText);
  report.pulse.hasFrozenBanner = cssText.includes('FROZEN — Pulse design system');
  report.pulse.hasPiece2Cards = cssText.includes('chips / cards');
  report.pulse.hasOptionBadge = cssText.includes('.toshi-option-card-badge');
  report.pulse.hasConfirmYes = cssText.includes('.toshi-confirm-btn-yes');
  report.pulse.hasPlanExecute = cssText.includes('.toshi-plan-btn-execute');
  await cssPage.close();

  if (!report.pulse.hasLedgerThead || !report.pulse.hasBlur || !report.pulse.hasFrozenBanner) {
    fail('Pulse canary missing from published toshi-ui.css');
    report.ok = false;
  }
  if (!report.pulse.hasPiece2Cards || !report.pulse.hasOptionBadge) {
    fail('Piece 2 card/chip tokens missing from published toshi-ui.css');
    report.ok = false;
  }

  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await ctx.newPage();
  await login(page);
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await ensureToshiOpen(page);

  // Inject tool-confirm + execution-plan markup into the live panel to assert computed clay tokens.
  const injected = await page.evaluate(() => {
    const panel = document.querySelector('#toshi-panel .toshi-messages-area')
      || document.querySelector('#toshi-panel');
    if (!panel) return { ok: false, reason: 'no panel' };

    const wrap = document.createElement('div');
    wrap.setAttribute('data-testid', 'toshi-piece2-inject');
    wrap.innerHTML = `
      <div class="toshi-confirm-card is-pending" data-testid="toshi-tool-confirm-card">
        <div class="toshi-confirm-card-header">
          <div class="toshi-confirm-card-title">
            <span class="toshi-confirm-card-icon">⚡</span><span>Demo Tool</span>
          </div>
        </div>
        <div class="toshi-confirm-card-footer">
          <button type="button" class="toshi-confirm-btn toshi-confirm-btn-yes">✓ Confirm</button>
          <button type="button" class="toshi-confirm-btn toshi-confirm-btn-no">Cancel</button>
        </div>
      </div>
      <div class="toshi-plan-card" data-testid="toshi-execution-plan-card">
        <div class="toshi-plan-card-header">
          <span class="toshi-plan-card-icon">📋</span>
          <span class="toshi-plan-card-title">Execution Plan</span>
          <span class="toshi-plan-card-count">1 step</span>
        </div>
        <div class="toshi-plan-card-footer">
          <button type="button" class="toshi-plan-btn toshi-plan-btn-execute">Execute All (1)</button>
          <button type="button" class="toshi-plan-btn toshi-plan-btn-cancel">Cancel</button>
        </div>
      </div>
      <button type="button" class="toshi-option-card" data-testid="toshi-option-card-demo">
        <div class="toshi-option-card-badge">⭐</div>
        <div>Demo plan</div>
      </button>
      <button type="button" class="toshi-chip-suggestion" data-testid="toshi-chip-suggestion-demo">
        <span class="toshi-chip-icon">📋</span><span>Demo chip</span>
      </button>
    `;
    panel.prepend(wrap);

    const cs = (sel) => {
      const el = wrap.querySelector(sel);
      return el ? getComputedStyle(el) : null;
    };
    const confirmBar = cs('.toshi-confirm-card');
    // ::before not readable via query; use yes btn + badge as clay signals
    return {
      ok: true,
      confirmYesBg: cs('.toshi-confirm-btn-yes')?.backgroundColor || null,
      planExecuteBg: cs('.toshi-plan-btn-execute')?.backgroundColor || null,
      badgeBg: cs('.toshi-option-card-badge')?.backgroundColor || null,
      planCardBg: cs('.toshi-plan-card')?.backgroundColor || null,
      chipBg: cs('.toshi-chip-suggestion')?.backgroundColor || null,
      confirmRadius: cs('.toshi-confirm-card')?.borderRadius || null,
      planRadius: cs('.toshi-plan-card')?.borderRadius || null,
    };
  });
  report.cards.inject = injected;

  const clay = 'rgb(201, 100, 66)';
  const warm = 'rgb(245, 244, 237)';
  if (injected.confirmYesBg !== clay) {
    fail(`confirm yes bg expected ${clay}, got ${injected.confirmYesBg}`);
    report.ok = false;
  }
  if (injected.planExecuteBg !== clay) {
    fail(`plan execute bg expected ${clay}, got ${injected.planExecuteBg}`);
    report.ok = false;
  }
  if (injected.badgeBg !== clay) {
    fail(`option badge bg expected ${clay}, got ${injected.badgeBg}`);
    report.ok = false;
  }
  if (injected.planCardBg !== warm) {
    fail(`plan card bg expected ${warm}, got ${injected.planCardBg}`);
    report.ok = false;
  }
  if (injected.chipBg !== warm) {
    fail(`suggestion chip bg expected ${warm}, got ${injected.chipBg}`);
    report.ok = false;
  }

  await page.screenshot({ path: path.join(OUT, 'cards-chips-1280.png'), fullPage: false });
  await ctx.close();
  await browser.close();

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  if (!report.ok) process.exit(1);
  console.log('PASS Piece 2 PR2 cards/chips + Pulse canary');
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
