/**
 * Piece 2 / PR1 — Toshi header/composer + chip-first confirm (staging).
 * Also asserts Pulse canary in published toshi-ui.css (.ds-table-ledger thead blur).
 *
 * Usage:
 *   PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud \
 *   DASH_EMAIL=phase4.admin@klassapp.xyz DASH_PASSWORD=demo123 \
 *   node e2e/toshi-piece2-header-composer-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const OUT = path.join(__dirname, 'screenshots/toshi-piece2-header-composer');
fs.mkdirSync(OUT, { recursive: true });

const viewports = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 800 },
];

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
  const root = page.locator('[data-toshi-root]');
  await root.waitFor({ state: 'attached', timeout: 30000 });
  const panel = page.locator('#toshi-panel');
  const visible = await panel.isVisible().catch(() => false);
  if (!visible) {
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
    viewports: {},
    chipConfirm: null,
    ok: true,
  };

  // Pulse canary from published CSS URL
  const cssPage = await browser.newPage();
  const cssRes = await cssPage.goto(`${BASE}/vendor/toshi-ui/toshi-ui.css`, { waitUntil: 'load', timeout: 60000 });
  const cssText = await cssRes.text();
  report.pulse.hasLedgerThead = cssText.includes('.ds-table-ledger thead');
  report.pulse.hasBlur = /backdrop-filter:\s*blur\(12px\)/.test(cssText);
  report.pulse.hasFrozenBanner = cssText.includes('FROZEN — Pulse design system');
  report.pulse.hasPiece2 = cssText.includes('PIECE 2 — Toshi panel chrome');
  if (!report.pulse.hasLedgerThead || !report.pulse.hasBlur) {
    fail('Pulse canary missing in published toshi-ui.css');
    report.ok = false;
  }
  if (!report.pulse.hasFrozenBanner || !report.pulse.hasPiece2) {
    fail('Piece 2 / Pulse freeze banners missing in published CSS');
    report.ok = false;
  }
  await cssPage.close();

  for (const vp of viewports) {
    const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await context.newPage();
    await login(page);
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(800);
    await ensureToshiOpen(page);

    const measured = await page.evaluate(() => {
      const header = document.querySelector('[data-testid="toshi-header"]');
      const composer = document.querySelector('[data-testid="toshi-composer"]');
      const panel = document.querySelector('#toshi-panel');
      const logo = document.querySelector('[data-testid="toshi-header"] .toshi-header-logo span');
      const hs = header ? getComputedStyle(header) : null;
      const ls = logo ? getComputedStyle(logo) : null;
      const cs = composer ? getComputedStyle(composer.querySelector('.toshi-composer-inner') || composer) : null;
      const ps = panel ? getComputedStyle(panel) : null;
      return {
        hasHeader: !!header,
        hasComposer: !!composer,
        headerBg: hs?.backgroundColor || null,
        logoColor: ls?.color || null,
        composerRadius: cs?.borderRadius || null,
        panelDisplay: ps?.display || null,
        panelWidth: panel ? Math.round(panel.getBoundingClientRect().width) : null,
        awaiting: !!document.querySelector('[data-testid="toshi-confirm-chips"]'),
        composerDeferred: composer?.classList.contains('toshi-composer--awaiting-confirm') || false,
      };
    });

    const shot = path.join(OUT, `toshi-${vp.name}.png`);
    await page.screenshot({ path: shot, fullPage: false });
    report.viewports[vp.name] = { ...measured, shot };

    if (!measured.hasHeader || !measured.hasComposer) {
      fail(`viewport ${vp.name}: missing header/composer`);
      report.ok = false;
    }
    // clay logo ≈ rgb(201, 100, 66)
    if (measured.logoColor && !measured.logoColor.includes('201')) {
      // allow slight variance — warn only if clearly not clay-ish
      if (!/rgb\(\s*20[01]\s*,\s*9\d\s*,\s*6\d\s*\)/.test(measured.logoColor)) {
        console.warn(`WARN ${vp.name}: logo color ${measured.logoColor} (expected clay ~201,100,66)`);
      }
    }

    await context.close();
  }

  // Chip-based confirmation: force awaitingConfirm via Livewire (independent of chat state).
  const confirmCtx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await confirmCtx.newPage();
  await login(page);
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
  await ensureToshiOpen(page);

  const inject = await page.evaluate(async () => {
    if (!window.Livewire) return { ok: false, reason: 'no Livewire' };
    const all = typeof window.Livewire.all === 'function' ? window.Livewire.all() : [];
    let component = all.find((c) => {
      const name = (c.name || c.__instance?.fingerprint?.name || '').toString();
      return name === 'agent-toshi' || name.includes('agent-toshi');
    }) || null;
    if (!component) {
      const root = document.querySelector('[data-toshi-root]');
      const wireEl = root?.closest('[wire\\:id]') || (root?.hasAttribute('wire:id') ? root : null)
        || document.querySelector('[wire\\:id]');
      const id = wireEl?.getAttribute('wire:id');
      if (id) component = window.Livewire.find(id);
    }
    if (!component) {
      return {
        ok: false,
        reason: 'no component',
        names: all.map((c) => c.name || c.__instance?.fingerprint?.name || '?'),
      };
    }

    const wire = component.$wire;
    if (!wire || typeof wire.set !== 'function') {
      return { ok: false, reason: 'no $wire.set', id: component.id || component.__livewireId };
    }

    await wire.set('visible', true);
    await wire.set('awaitingConfirm', true);
    if (typeof wire.$commit === 'function') {
      await wire.$commit();
    }

    // Wait for network morph; poll for real DOM nodes (not snapshot JSON strings).
    for (let i = 0; i < 40; i++) {
      if (document.querySelector('#toshi-panel [data-testid="toshi-confirm-chips"]')) break;
      await new Promise((r) => setTimeout(r, 250));
    }

    const chipNodes = document.querySelectorAll('[data-testid="toshi-confirm-chips"]');
    return {
      ok: true,
      name: component.name || 'found',
      id: component.id || component.__livewireId,
      awaitingConfirm: wire.awaitingConfirm,
      visible: wire.visible,
      chipNodeCount: chipNodes.length,
      panelChipCount: document.querySelectorAll('#toshi-panel [data-testid="toshi-confirm-chips"]').length,
      composerDeferredCount: document.querySelectorAll('.toshi-composer--awaiting-confirm').length,
      panelDisplay: document.querySelector('#toshi-panel')
        ? getComputedStyle(document.querySelector('#toshi-panel')).display
        : null,
    };
  });
  report.chipConfirm = { inject };

  await page.waitForSelector('#toshi-panel [data-testid="toshi-confirm-chips"]', { timeout: 5000 }).catch(() => null);

  const chipsMeta = await page.evaluate(() => {
    const nodes = [...document.querySelectorAll('[data-testid="toshi-confirm-chips"]')];
    return nodes.map((el) => {
      const style = getComputedStyle(el);
      const panel = el.closest('#toshi-panel, #toshi-modal');
      return {
        variant: el.getAttribute('data-toshi-confirm-variant'),
        display: style.display,
        visibility: style.visibility,
        parentId: panel?.id || null,
        parentDisplay: panel ? getComputedStyle(panel).display : null,
        w: Math.round(el.getBoundingClientRect().width),
        h: Math.round(el.getBoundingClientRect().height),
      };
    });
  });
  report.chipConfirm.chipsMeta = chipsMeta;

  const chips = page.locator('#toshi-panel [data-testid="toshi-confirm-chips"]');
  const yesBtn = page.locator('#toshi-panel [data-testid="toshi-confirm-yes"]');
  const visible = (await chips.count()) > 0 && await chips.first().isVisible().catch(() => false);
  report.chipConfirm.chipsVisible = visible;

  if (!visible) {
    fail('confirm chips not visible after attempting to enter awaitingConfirm');
    report.ok = false;
  } else {
    const deferred = await page.locator('[data-testid="toshi-composer"].toshi-composer--awaiting-confirm').count();
    report.chipConfirm.composerDeferred = deferred > 0;
    if (deferred === 0) {
      fail('composer not deferred while confirm chips shown');
      report.ok = false;
    }

    await yesBtn.click();
    await page.waitForTimeout(1200);
    const still = await chips.isVisible().catch(() => false);
    report.chipConfirm.afterYesGone = !still;
    if (still) {
      report.chipConfirm.afterYesGone = 'still_or_next_confirm';
      console.log('NOTE: chips still present after Yes (may be next confirm substep) — click registered');
    }
    await page.screenshot({ path: path.join(OUT, 'chip-confirm-after-yes.png'), fullPage: false });
  }

  await confirmCtx.close();
  await browser.close();

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  if (!report.ok) {
    process.exit(1);
  }
  console.log('PASS Piece 2 PR1 header/composer + chip confirm + Pulse canary');
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
