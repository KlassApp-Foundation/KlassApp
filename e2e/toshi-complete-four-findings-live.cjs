/**
 * Live verify: four complete-mode findings on production Toshi chat.
 * 1) Reload keeps fees step  2) Fee form shows O'Level/A'Level
 * 3) Teacher name not student-lookup  4) Skip WA lands on School Pay
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const CREDS_PATH = process.env.TOSHI_CREDS || '/tmp/toshi-four-findings-creds.json';
const OUT_DIR = path.join(__dirname, 'screenshots', 'toshi-complete-four-findings');

function modal(page) {
  return page.locator('#toshi-modal:visible').first();
}

async function sendToshi(page, text) {
  const root = modal(page);
  const composer = root.locator('textarea[placeholder*="Message Toshi"]:visible').last();
  await composer.click();
  await composer.fill(String(text));
  await root.locator('button[title="Send"]:visible').last().click();
  await page.waitForTimeout(1000);
}

async function botText(page) {
  if (await modal(page).count()) {
    return modal(page).innerText().catch(() => '');
  }
  return page.locator('[data-toshi-root]').innerText().catch(() => '');
}

async function openMaximizedToshi(page) {
  // Prefer maximize if pill visible
  const pill = page.locator('[wire\\:click="toggle"], button:has-text("Toshi"), #toshi-pill').first();
  if (await page.locator('#toshi-modal:visible').count() === 0) {
    if (await pill.count()) {
      await pill.click({ force: true }).catch(() => {});
      await page.waitForTimeout(800);
    }
  }
  const maxBtn = page.locator('button[wire\\:click="maximize"], button[title*="Maximize"], [wire\\:click*="maxim"]').first();
  if (await maxBtn.count()) {
    await maxBtn.click({ force: true }).catch(() => {});
    await page.waitForTimeout(800);
  }
  // Force maximize via Livewire if still closed
  await page.evaluate(() => {
    const el = document.querySelector('[wire\\:id]');
    // no-op; rely on UI
  });
}

(async () => {
  fs.mkdirSync(OUT_DIR, { recursive: true });
  const creds = JSON.parse(fs.readFileSync(CREDS_PATH, 'utf8'));
  const report = { pass: false, steps: [], errors: [] };

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

  try {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.fill('input[name="email"], #email', creds.email);
    await page.fill('input[name="password"], #password', creds.password);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2500);
    await page.screenshot({ path: path.join(OUT_DIR, '01-after-login.png'), fullPage: true });

    // Navigate with toshi_onboarding open hint
    await page.goto(`${BASE}/admin/dashboard?toshi_onboarding=1`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2500);
    await openMaximizedToshi(page);
    await page.screenshot({ path: path.join(OUT_DIR, '02-toshi-open.png'), fullPage: true });

    // Jump toward teachers via typed flow if needed — use Livewire skip path:
    // Put component on whatsapp via sending through steps is heavy; instead drive via wire calls.
    const wireId = await page.evaluate(() => {
      const el = document.querySelector('#toshi-modal [wire\\:id], [wire\\:id]');
      return el ? el.getAttribute('wire:id') : null;
    });
    if (!wireId) throw new Error('No Livewire wire:id for Toshi');

    async function lw(method, params = []) {
      await page.evaluate(async ({ wireId, method, params }) => {
        await window.Livewire.find(wireId).call(method, ...params);
      }, { wireId, method, params });
      await page.waitForTimeout(700);
    }

    async function lwSet(prop, value) {
      await page.evaluate(async ({ wireId, prop, value }) => {
        await window.Livewire.find(wireId).set(prop, value);
      }, { wireId, prop, value });
      await page.waitForTimeout(400);
    }

    // Ensure complete mode + school
    await lwSet('mode', 'complete');
    await lwSet('schoolId', creds.school_id);
    await lwSet('schoolPhone', '+256700119902');

    // ── Finding 3: teacher name not lookup ──
    const teachersIdx = await page.evaluate(async ({ wireId }) => {
      const c = window.Livewire.find(wireId);
      return c.get('steps').indexOf('teachers');
    }, { wireId });
    await lwSet('step', teachersIdx);
    await lwSet('substep', 0);
    await lwSet('input', 'Jane Auma');
    await lw('send');
    let text = await botText(page);
    const lookupHijack = /couldn't find a student matching|Found \*\*\d+\*\* students/i.test(text);
    const mode = await page.evaluate(async ({ wireId }) => window.Livewire.find(wireId).get('mode'), { wireId });
    const stepAfterName = await page.evaluate(async ({ wireId }) => window.Livewire.find(wireId).get('step'), { wireId });
    report.steps.push({
      finding: 3,
      lookup_hijack: lookupHijack,
      mode,
      step: stepAfterName,
      teachers_idx: teachersIdx,
      ok: !lookupHijack && mode === 'complete' && stepAfterName === teachersIdx,
    });
    await page.screenshot({ path: path.join(OUT_DIR, '03-teacher-name.png'), fullPage: true });

    // ── Finding 2: fee form levels ──
    const feesIdx = await page.evaluate(async ({ wireId }) => {
      return window.Livewire.find(wireId).get('steps').indexOf('fees');
    }, { wireId });
    await lwSet('step', feesIdx);
    await lw('showFeeFormFn');
    await page.waitForTimeout(800);
    text = await botText(page);
    const html = await modal(page).innerHTML().catch(() => '');
    const hasO = /O'Level|O’Level/.test(html) || /O'Level|O’Level/.test(text);
    const hasA = /A'Level|A’Level/.test(html) || /A'Level|A’Level/.test(text);
    report.steps.push({ finding: 2, has_olevel: hasO, has_alevel: hasA, ok: hasO && hasA });
    await page.screenshot({ path: path.join(OUT_DIR, '04-fee-levels.png'), fullPage: true });

    // ── Finding 1: reload keeps fees step ──
    await lwSet('step', feesIdx);
    await lwSet('substep', 6);
    await lwSet('teacherList', ['Jane Auma']);
    // persistState is private — trigger via a public path that persists
    await page.evaluate(async ({ wireId }) => {
      const c = window.Livewire.find(wireId);
      // toggle visibility forces dehydrate; call a no-op public if needed
      await c.call('$refresh');
    }, { wireId }).catch(() => {});
    // Force persist by calling skip-noop: set visible
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    await page.goto(`${BASE}/admin/dashboard?toshi_onboarding=1`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    await openMaximizedToshi(page);
    const wireId2 = await page.evaluate(() => {
      const el = document.querySelector('#toshi-modal [wire\\:id], [wire\\:id]');
      return el ? el.getAttribute('wire:id') : null;
    });
    const afterReload = await page.evaluate(async ({ wireId }) => {
      const c = window.Livewire.find(wireId);
      return {
        mode: c.get('mode'),
        step: c.get('step'),
        stepName: c.get('steps')[c.get('step')],
      };
    }, { wireId: wireId2 });
    report.steps.push({
      finding: 1,
      ...afterReload,
      ok: afterReload.mode === 'complete' && afterReload.stepName === 'fees',
    });
    await page.screenshot({ path: path.join(OUT_DIR, '05-after-reload.png'), fullPage: true });

    // ── Finding 4: skip WA → School Pay (not plan) ──
    const waIdx = await page.evaluate(async ({ wireId }) => {
      return window.Livewire.find(wireId).get('steps').indexOf('whatsapp_verify');
    }, { wireId: wireId2 });
    await page.evaluate(async ({ wireId, waIdx }) => {
      const c = window.Livewire.find(wireId);
      await c.set('mode', 'complete');
      await c.set('step', waIdx);
      await c.set('substep', 0);
      await c.set('schoolPhone', '+256700119902');
      await c.call('skipStep');
    }, { wireId: wireId2, waIdx });
    await page.waitForTimeout(1200);
    const afterSkip = await page.evaluate(async ({ wireId }) => {
      const c = window.Livewire.find(wireId);
      return {
        step: c.get('step'),
        stepName: c.get('steps')[c.get('step')],
        text: document.querySelector('#toshi-modal')?.innerText || '',
      };
    }, { wireId: wireId2 });
    const schoolPayOk = afterSkip.stepName === 'school_pay'
      && /School Pay/i.test(afterSkip.text)
      && afterSkip.stepName !== 'plan_selection';
    report.steps.push({
      finding: 4,
      stepName: afterSkip.stepName,
      mentions_school_pay: /School Pay/i.test(afterSkip.text),
      ok: schoolPayOk,
    });
    await page.screenshot({ path: path.join(OUT_DIR, '06-after-wa-skip.png'), fullPage: true });

    report.pass = report.steps.every((s) => s.ok);
  } catch (e) {
    report.errors.push(String(e && e.stack ? e.stack : e));
    await page.screenshot({ path: path.join(OUT_DIR, 'error.png'), fullPage: true }).catch(() => {});
  } finally {
    fs.writeFileSync(path.join(OUT_DIR, 'REPORT.json'), JSON.stringify(report, null, 2));
    await browser.close();
    console.log(JSON.stringify(report, null, 2));
    process.exit(report.pass ? 0 : 1);
  }
})();
