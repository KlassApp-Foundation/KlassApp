/**
 * Piece 3 PR3 — Structure & Class Teacher checkpoint cards on staging.
 *
 * Verifies kit chrome (empty stream/CT states), then exercises real
 * addStructureStream + inviteStructureClassTeacher via the UI, then
 * screenshots at 375/414/768/1280.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PREVIEW_BASE || process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp-staging-7mpoqg.laravel.cloud';
const VIEWPORTS = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 900 },
];
const OUT = path.join(__dirname, 'screenshots', 'wizard-structure-ct');
fs.mkdirSync(OUT, { recursive: true });

async function login(page) {
  const email = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
  const password = process.env.DASH_PASSWORD || 'demo123';
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name=email], input[type=email]', email);
  await page.fill('input[name=password], input[type=password]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
    page.click('button[type=submit], input[type=submit]'),
  ]);
}

(async () => {
  console.log('launch');
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.setDefaultTimeout(45000);

  await login(page);
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('[data-testid=wizard-nav]', { timeout: 30000 });

  await page.click('[data-step-key=standards]');
  await page.waitForTimeout(1500);
  await page.waitForSelector('[data-testid=wizard-structure-step]');

  // Capture empty-state chrome before mutations when present.
  await page.screenshot({
    path: path.join(OUT, 'structure-empty-1280.png'),
    fullPage: true,
  });

  const cards = page.locator('[data-testid^=wizard-structure-class-]');
  const cardCount = await cards.count();
  if (cardCount < 1) {
    throw new Error('expected at least one structure class card');
  }
  console.log('cards', cardCount);

  const bodyText = await page.locator('[data-testid=wizard-structure-step]').innerText();
  if (!/No streams yet — undivided base class|Streams:/.test(bodyText)) {
    throw new Error('missing stream empty/populated chrome');
  }
  if (!/No class teacher yet|CT:/.test(bodyText)) {
    throw new Error('missing CT empty/populated chrome');
  }
  console.log('empty-or-populated chrome OK');

  // Prefer a card with invite form (and empty streams when possible).
  let targetSid = null;
  let inviteSid = null;
  for (let i = 0; i < cardCount; i++) {
    const card = cards.nth(i);
    const testid = await card.getAttribute('data-testid');
    const sid = testid.replace('wizard-structure-class-', '');
    const hasInvite = (await page.locator(`[data-testid=wizard-structure-invite-ct-${sid}]`).count()) > 0;
    const hasEmptyStreams = (await page.locator(`[data-testid=wizard-structure-streams-empty-${sid}]`).count()) > 0;
    if (hasInvite) {
      inviteSid = sid;
    }
    if (hasInvite && hasEmptyStreams) {
      targetSid = sid;
      break;
    }
    if (targetSid === null && hasInvite) {
      targetSid = sid;
    }
  }
  if (targetSid === null) {
    const firstAdd = page.locator('[data-testid^=wizard-structure-add-stream-]').first();
    const addId = await firstAdd.getAttribute('data-testid');
    targetSid = addId.replace('wizard-structure-add-stream-', '');
  }
  if (inviteSid === null) {
    inviteSid = targetSid;
  }
  console.log('targetSid', targetSid, 'inviteSid', inviteSid);

  const streamLabel = 'Kit' + Date.now().toString().slice(-5);
  await page.fill(`[data-testid=wizard-structure-stream-input-${targetSid}]`, streamLabel);
  await page.click(`[data-testid=wizard-structure-add-stream-${targetSid}]`);
  await page.waitForTimeout(2000);

  const flash = ((await page.locator('[data-testid=wizard-structure-flash]').textContent().catch(() => '')) || '').trim();
  if (!flash.includes('Added stream') || !flash.includes(streamLabel)) {
    throw new Error('addStructureStream flash missing/unexpected: ' + JSON.stringify(flash));
  }
  const chips = await page.locator(`[data-testid=wizard-structure-class-${targetSid}] .manual-wizard-stream-chip`).allTextContents();
  if (!chips.some((c) => c.trim() === streamLabel)) {
    throw new Error('stream chip not rendered after add: ' + JSON.stringify(chips));
  }
  console.log('stream add OK', flash);

  // Invite CT — required live proof for inviteStructureClassTeacher.
  const inviteBtn = page.locator(`[data-testid=wizard-structure-invite-ct-${inviteSid}]`);
  if ((await inviteBtn.count()) === 0) {
    throw new Error('no CT invite form available — clear a class_teacher_id on staging first');
  }
  {
    const email = `kit.ct.${Date.now()}@klassapp.xyz`;
    await page.fill(`[data-testid=wizard-structure-ct-email-${inviteSid}]`, email);
    await page.selectOption(`[data-testid=wizard-structure-ct-existing-${inviteSid}]`, '');
    await page.waitForTimeout(500);
    await page.fill(`[data-testid=wizard-structure-ct-name-${inviteSid}]`, 'Kit Class Teacher');
    await page.fill(`[data-testid=wizard-structure-ct-phone-${inviteSid}]`, '0700123456');
    await inviteBtn.click();
    await page.waitForTimeout(2500);

    const flash2 = ((await page.locator('[data-testid=wizard-structure-flash]').textContent().catch(() => '')) || '').trim();
    if (!flash2) {
      throw new Error('inviteStructureClassTeacher produced no flash');
    }
    const status = ((await page.locator(`[data-testid=wizard-structure-ct-status-${inviteSid}]`).textContent()) || '').trim();
    if (!/CT:/i.test(status) || /No class teacher yet/i.test(status)) {
      throw new Error('CT status did not populate after invite: ' + status + ' flash=' + flash2);
    }
    if ((await page.locator(`[data-testid=wizard-structure-invite-ct-${inviteSid}]`).count()) > 0) {
      throw new Error('CT invite form still visible after successful invite');
    }
    console.log('CT invite OK', flash2, status);
  }

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(400);
    await page.screenshot({
      path: path.join(OUT, `structure-${vp.name}.png`),
      fullPage: true,
    });
    console.log('PASS', vp.name);
  }

  await browser.close();
  console.log('OK wizard structure/CT kit parity @', BASE);
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
