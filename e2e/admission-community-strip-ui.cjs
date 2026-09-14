/**
 * Local UI verify: Admission student-detail no longer shows Community (caste).
 *
 *   PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000 node e2e/admission-community-strip-ui.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000';
const SLUG = process.env.ADMISSION_SLUG || 'verify-streams';
const OUT = path.join(__dirname, 'screenshots', 'admission-community-strip');

fs.mkdirSync(OUT, { recursive: true });

(async () => {
  const report = {
    base: BASE,
    slug: SLUG,
    community_visible_on_student_step: null,
    student_step_next_ok: null,
    screenshots: [],
    errors: [],
  };

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.on('pageerror', (e) => report.errors.push(String(e)));

  try {
    await page.goto(`${BASE}/${SLUG}/admission-form`, {
      waitUntil: 'networkidle',
      timeout: 90000,
    });
    await page.waitForTimeout(1500);
    await page.screenshot({
      path: path.join(OUT, '01-admission-open.png'),
      fullPage: true,
    });
    report.screenshots.push('01-admission-open.png');

    const classSelect = page.locator('#standard_id, select[name="standard_id"]').first();
    await classSelect.waitFor({ state: 'visible', timeout: 30000 });
    const options = await classSelect.locator('option').all();
    let picked = false;
    for (const opt of options) {
      const val = await opt.getAttribute('value');
      if (val) {
        await classSelect.selectOption(val);
        picked = true;
        break;
      }
    }
    if (!picked) {
      throw new Error('No class options in admission form');
    }

    await page.locator('a:has-text("Next")').first().click();
    await page.waitForTimeout(1200);

    await page.screenshot({
      path: path.join(OUT, '02-student-detail.png'),
      fullPage: true,
    });
    report.screenshots.push('02-student-detail.png');

    const bodyText = await page.locator('body').innerText();
    report.community_visible_on_student_step =
      /\bCommunity\b/.test(bodyText) ||
      /\(BC\s*\/\s*BCM/.test(bodyText) ||
      (await page.locator('input[name="community"], label[for="community"]').count()) > 0;

    if (report.community_visible_on_student_step) {
      throw new Error('Community field still visible on student detail step');
    }

    await page.fill('input[name="name"]', 'Amina');
    await page.fill('input[name="lastname"]', 'Nakato');
    await page.fill('input[name="date_of_birth"]', '2015-03-12');
    await page.locator('input[name="gender"][value="female"]').check();
    await page.fill('input[name="identification_marks"]', 'Scar on left knee');
    await page.fill('textarea[name="permanent_address"]', 'Kampala Road 1');
    await page.fill('textarea[name="address_for_communication"]', 'Kampala Road 1');
    await page.locator('input[name="siblings"][value="igottwo"]').check();

    const studentNext = page
      .locator('.bg-white.shadow:visible a:has-text("Next")')
      .last();
    await studentNext.click();
    await page.waitForTimeout(1500);

    // Academic step heading / Tamil mark field proves we advanced past student detail
    const onAcademic =
      (await page.locator('text=Half Yearly Mark Details').count()) > 0 ||
      (await page.locator('input[name="english"]').count()) > 0 ||
      (await page.locator('text=Board of Study').count()) > 0;

    report.student_step_next_ok = onAcademic;
    await page.screenshot({
      path: path.join(OUT, '03-after-student-next.png'),
      fullPage: true,
    });
    report.screenshots.push('03-after-student-next.png');

    if (!onAcademic) {
      throw new Error('Student detail Next did not advance (validation may have failed)');
    }

    report.pass = true;
  } catch (e) {
    report.pass = false;
    report.errors.push(String(e && e.stack ? e.stack : e));
    await page
      .screenshot({ path: path.join(OUT, 'FAIL.png'), fullPage: true })
      .catch(() => {});
  } finally {
    fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
    await browser.close();
    console.log(JSON.stringify(report, null, 2));
    process.exit(report.pass ? 0 : 1);
  }
})();
