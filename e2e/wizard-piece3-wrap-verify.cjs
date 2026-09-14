/**
 * Piece 3 wrap smoke — shell + key redesigned steps still present on staging.
 * Viewports 375/414/768/1280.
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
const OUT = path.join(__dirname, 'screenshots', 'wizard-piece3-wrap');
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

async function gotoWizardStep(page, stepKey) {
  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('[data-testid=wizard-nav]', { timeout: 30000 });
  await page.click(`[data-step-key=${stepKey}]`);
  await page.waitForTimeout(1200);
}

(async () => {
  console.log('launch', BASE);
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.setDefaultTimeout(45000);
  await login(page);

  await page.goto(`${BASE}/admin/onboarding/wizard`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('[data-testid=manual-wizard-shell]', { timeout: 30000 });
  const shell = await page.evaluate(() => ({
    brand: document.querySelector('[data-testid=wizard-brand]')?.textContent?.includes('KlassApp') || false,
    aside: document.querySelector('[data-testid=wizard-brand]')?.textContent?.includes('Setting up without Toshi') || false,
    nav: !!document.querySelector('[data-testid=wizard-nav]'),
    dots: document.querySelectorAll('[data-testid=wizard-progress] [data-step-key]').length,
  }));
  console.log('shell', JSON.stringify(shell));
  if (!shell.brand || !shell.aside || !shell.nav || shell.dots < 10) throw new Error('shell incomplete');

  const checks = {
    student_size: '[data-testid=wizard-plan-cards], .manual-wizard-plan-grid',
    school_category: '.manual-wizard-plan-card, [data-testid^=wizard-plan-]',
    standards: '.manual-wizard-structure-card, [data-testid=wizard-structure]',
    teachers: '[data-testid=wizard-teachers-bulk]',
    students: '[data-testid=wizard-students-bulk]',
    terms: '[data-testid=wizard-terms-bulk]',
    fees: '[data-testid=wizard-fees-bulk]',
    review: '.manual-wizard-review-panels, [data-testid=wizard-review]',
  };

  for (const [step, sel] of Object.entries(checks)) {
    await gotoWizardStep(page, step);
    const ok = await page.locator(sel).first().count();
    console.log('step', step, 'ok', !!ok);
    if (!ok) throw new Error('missing chrome for ' + step + ' selector ' + sel);
  }

  // UNEB gate: board reg only for candidate class when selectable
  await gotoWizardStep(page, 'students');
  const classSelect = page.locator('[data-testid=wizard-student-class]');
  if (await classSelect.locator('option').count() > 1) {
    await classSelect.selectOption({ index: 1 });
    await page.waitForTimeout(800);
    const unebAfterNonCandidate = await page.locator('[data-testid=wizard-student-board-reg]').count();
    console.log('unebHiddenForFirstClass', unebAfterNonCandidate === 0);

    const options = await classSelect.locator('option').allTextContents();
    const candidateIdx = options.findIndex((t) => /P\.?7|Primary Seven|S\.?4|Senior Four|S\.?6|Senior Six/i.test(t));
    if (candidateIdx > 0) {
      await classSelect.selectOption({ index: candidateIdx });
      await page.waitForTimeout(800);
      const unebShown = await page.locator('[data-testid=wizard-student-board-reg]').count();
      console.log('unebShownForCandidate', unebShown === 1);
      if (unebShown !== 1) throw new Error('UNEB field should show for candidate class');
    }
  }

  await gotoWizardStep(page, 'terms');
  const termsIntro = await page.locator('[data-testid=wizard-terms-intro]').textContent();
  if (!/admin settings/i.test(termsIntro || '')) throw new Error('terms deferral copy missing');

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(350);
    await page.screenshot({ path: path.join(OUT, `terms-${vp.name}.png`), fullPage: true });
  }

  await gotoWizardStep(page, 'review');
  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(350);
    await page.screenshot({ path: path.join(OUT, `review-${vp.name}.png`), fullPage: true });
  }

  console.log('OK piece3-wrap →', OUT);
  await browser.close();
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
