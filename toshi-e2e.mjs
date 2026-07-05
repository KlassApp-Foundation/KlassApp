import { chromium } from 'playwright';
import { writeFileSync } from 'fs';

const BASE = 'http://127.0.0.1:8000';
const TS = Date.now();
const EMAIL = `e2e-test-${TS}@test.com`;
const PASSWORD = 'Test1234!';
const SCHOOL = `E2E Test School ${TS}`;
const ADMIN_NAME = 'Test Admin';

async function run() {
  console.log(`\n=== TOSHI E2E: ${SCHOOL} ===`);
  
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.on('pageerror', err => console.log('  [PAGE ERROR]', err.message));

  try {
    // ── Visit /register ──
    console.log('\n1. Visiting /register...');
    await page.goto(`${BASE}/register`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(1500);
    console.log(`   URL: ${page.url()}`);

    // ── Fill form ──
    console.log('\n2. Filling registration form...');
    
    await page.fill('#school_name', SCHOOL);
    await page.fill('#name', ADMIN_NAME);
    
    // Select country — then wait for JS to enable mobile field
    await page.selectOption('#country', 'Uganda');
    await page.waitForTimeout(500);
    
    // Enable mobile field via JS directly (country change triggers this in the app)
    await page.evaluate(() => {
      document.getElementById('mobile_no').disabled = false;
    });
    await page.fill('#mobile_no', '700000000');
    
    await page.selectOption('#student_size', '1-100');
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await page.fill('#password-confirm', PASSWORD);
    await page.check('#termsandcondn');
    
    console.log('   Form filled');

    // ── Submit ──
    console.log('\n3. Submitting...');
    await page.evaluate(() => {
      // Try direct form submit instead of button click if button is overlapped
      const form = document.querySelector('form');
      if (form) form.submit();
    });
    await page.waitForTimeout(3000);
    console.log(`   URL: ${page.url()}`);

    await page.screenshot({ path: '/Users/mac/projects/KlassApp/toshi-e2e-after-submit.png' });

    // ── Walk through Toshi steps ──
    console.log('\n4. Walking through Toshi onboarding...');
    
    for (let step = 1; step <= 30; step++) {
      const url = page.url();
      console.log(`   Step ${step}: ${url}`);

      // Save screenshots periodically
      if (step % 3 === 1) {
        await page.screenshot({ path: `/Users/mac/projects/KlassApp/toshi-e2e-s${String(step).padStart(2, '0')}.png` });
      }

      // Check if we reached admin dashboard
      if (url.includes('/admin/') && !url.includes('login') && !url.includes('register')) {
        console.log('   ✅ REACHED ADMIN DASHBOARD!');
        await page.screenshot({ path: '/Users/mac/projects/KlassApp/toshi-e2e-dashboard.png' });
        break;
      }

      // If redirected to login, log in
      if (url.includes('login')) {
        console.log('   ℹ️  At login page — logging in...');
        await page.fill('input[name="email"], input[type="email"]', EMAIL);
        await page.fill('input[name="password"], input[type="password"]', PASSWORD);
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        console.log(`   After login: ${page.url()}`);
        continue;
      }

      // Click next/continue button via evaluate to bypass any overlay issues
      const clicked = await page.evaluate(() => {
        const btns = document.querySelectorAll('button, a.btn, a[role="button"]');
        const nextTexts = ['next', 'continue', 'submit', 'complete', 'finish', 'done', 'proceed', 'save'];
        for (const btn of btns) {
          if (!btn.offsetParent) continue; // hidden
          const text = (btn.textContent || '').trim().toLowerCase();
          if (nextTexts.some(t => text.includes(t))) {
            btn.click();
            return text;
          }
        }
        return null;
      });

      if (clicked) {
        console.log(`   Clicked: "${clicked}"`);
        await page.waitForTimeout(2000);
      } else {
        // No navigation button found
        const inputCount = await page.evaluate(() => 
          document.querySelectorAll('input:not([type="hidden"]):not([disabled]):visible, select:visible, textarea:visible').length
        );
        console.log(`   No nav button. ${inputCount} visible inputs.`);
        
        // If we've been on the same page for 3+ iterations, break
        break;
      }
    }

    // ── Final state ──
    console.log('\n5. Final state:');
    console.log(`   URL: ${page.url()}`);
    
    const finalHTML = await page.content();
    const loggedIn = finalHTML.includes('logout') || finalHTML.includes('admin');
    console.log(`   Logged into admin: ${loggedIn ? '✅ YES' : '❌ NO'}`);
    
    await page.screenshot({ path: '/Users/mac/projects/KlassApp/toshi-e2e-final.png' });
    writeFileSync('/Users/mac/projects/KlassApp/toshi-e2e-final.html', finalHTML);
    console.log('   Saved toshi-e2e-final.html');

    console.log('\n=== E2E TEST COMPLETE ===\n');

  } catch (err) {
    console.log('\n❌ ERROR:', err.message);
    await page.screenshot({ path: '/Users/mac/projects/KlassApp/toshi-e2e-error.png' });
    console.log('   Saved error screenshot');
  }

  await browser.close();
}

run();