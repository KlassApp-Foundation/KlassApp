/**
 * Live: Greenfield admin Approvals inbox — approve Grace Auma (Approval #6).
 *
 *   PLAYWRIGHT_BASE_URL=https://klassapp.xyz node e2e/greenfield-approve-grace-auma.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const OUT = path.join(__dirname, 'screenshots', 'greenfield-grace-approve');
const EMAIL = process.env.GF_ADMIN_EMAIL || 'sarah.nakamya+1788863734@gmail.com';
const PASSWORD = process.env.GF_ADMIN_PASSWORD || 'Password123!';

fs.mkdirSync(OUT, { recursive: true });

async function main() {
  const browser = await chromium.launch({ headless: process.env.HEADED !== '1' });
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await context.newPage();
  const report = { steps: [], pass: false };

  try {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.fill('input[name="email"], input[type="email"]', EMAIL);
    await page.fill('input[name="password"], input[type="password"]', PASSWORD);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null),
      page.click('button[type="submit"], input[type="submit"]'),
    ]);
    await page.screenshot({ path: path.join(OUT, '01-after-login.png'), fullPage: true });
    report.steps.push({ step: 'login', url: page.url() });

    await page.goto(`${BASE}/admin/approvals`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: path.join(OUT, '02-approvals-inbox.png'), fullPage: true });

    const bodyText = await page.locator('body').innerText();
    const pendingMatch = bodyText.match(/Pending[^\d]*(\d+)/i);
    const pendingCount = pendingMatch ? Number(pendingMatch[1]) : null;
    report.steps.push({
      step: 'inbox_loaded',
      pendingCount,
      hasGrace: bodyText.includes('Grace Auma'),
      hasSunday: bodyText.includes('Sunday Johnson'),
      bodySnippet: bodyText.slice(0, 800),
    });

    if (pendingCount !== 1) {
      throw new Error(`Expected Pending count 1, got ${pendingCount}`);
    }
    if (!bodyText.includes('Grace Auma')) {
      throw new Error('Grace Auma not visible in Approvals inbox');
    }

    // Parent-link approve form: select suggested student if present, then Approve
    const approveForm = page.locator('form[action*="approvals"]').filter({ hasText: 'Approve' }).first();
    const select = approveForm.locator('select[name="matched_student_id"]');
    if (await select.count()) {
      const options = await select.locator('option').allTextContents();
      report.steps.push({ step: 'candidate_options', options });
      const graceOpt = await select.locator('option', { hasText: 'Grace Auma' }).first();
      if (await graceOpt.count()) {
        await select.selectOption({ label: await graceOpt.innerText() });
      }
    }

    page.once('dialog', async (dialog) => {
      report.steps.push({ step: 'confirm_dialog', message: dialog.message() });
      await dialog.accept();
    });

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null),
      approveForm.locator('button[type="submit"]').filter({ hasText: 'Approve' }).click(),
    ]);
    await page.waitForTimeout(1500);
    await page.screenshot({ path: path.join(OUT, '03-after-approve.png'), fullPage: true });

    const after = await page.locator('body').innerText();
    const approvedMatch = after.match(/Approved[^\d]*(\d+)/i);
    const pendingAfter = after.match(/Pending[^\d]*(\d+)/i);
    const approvedCount = approvedMatch ? Number(approvedMatch[1]) : null;
    const pendingCountAfter = pendingAfter ? Number(pendingAfter[1]) : null;
    const successFlash = after.toLowerCase().includes('approved successfully')
      || after.toLowerCase().includes('request approved');

    report.steps.push({
      step: 'after_approve',
      pendingCountAfter,
      approvedCount,
      successFlash,
      url: page.url(),
    });

    if (pendingCountAfter !== 0) {
      throw new Error(`Expected Pending 0 after approve, got ${pendingCountAfter}`);
    }
    if (approvedCount !== 1) {
      throw new Error(`Expected Approved 1 after approve, got ${approvedCount}`);
    }

    report.pass = true;
  } catch (e) {
    report.error = String(e && e.message ? e.message : e);
    await page.screenshot({ path: path.join(OUT, '99-error.png'), fullPage: true }).catch(() => {});
  } finally {
    fs.writeFileSync(path.join(OUT, 'REPORT.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
    await browser.close();
  }

  process.exit(report.pass ? 0 : 1);
}

main();
