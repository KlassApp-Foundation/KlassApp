const { chromium } = require('playwright');
const fs = require('fs');

const BASE = 'http://127.0.0.1:8000';
const TS = Date.now();
const EMAIL = `e2e${TS}@test.com`;
const PASS = 'Test1234!';
const SCHOOL = `E2E School ${TS}`;

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  page.on('pageerror', e => console.log('PAGE ERROR:', e.message));

  // 1. Go to register
  console.log('1. Register page...');
  await page.goto(BASE + '/register', { waitUntil: 'networkidle' });
  await page.waitForTimeout(2000);
  console.log('   URL:', page.url());

  // 2. Override form action
  await page.evaluate(() => {
    document.querySelector('form').action = 'http://127.0.0.1:8000/register';
  });

  // 3. Fill form
  console.log('2. Filling form...');
  await page.fill('#school_name', SCHOOL);
  await page.fill('#name', 'Test Admin');
  await page.selectOption('#country', 'Uganda');
  await page.waitForTimeout(300);
  await page.evaluate(() => document.getElementById('mobile_no').disabled = false);
  await page.fill('#mobile_no', '700000000');

  // Find a valid student_size option
  const sizeOptions = await page.evaluate(() => {
    const sel = document.getElementById('student_size');
    return Array.from(sel.options).map(o => ({ v: o.value, t: o.text })).slice(0, 10);
  });
  console.log('   student_size options:', sizeOptions);

  // Pick first non-empty option
  const firstReal = sizeOptions.find(o => o.v && o.v !== '');
  if (firstReal) await page.selectOption('#student_size', firstReal.v);

  await page.fill('#email', EMAIL);
  await page.fill('#password', PASS);
  await page.fill('#password-confirm', PASS);
  await page.check('#termsandcondn');

  // 4. Submit
  console.log('3. Submitting...');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle', timeout: 15000 })
      .catch(e => console.log('   Nav timeout:', e.message)),
    page.click('button[type="submit"]')
  ]);
  console.log('   URL:', page.url());
  await page.screenshot({ path: 'toshi-e2e-after-submit.png' });

  // 5. Walk through Toshi steps
  for (let step = 1; step <= 20; step++) {
    const url = page.url();
    console.log(`\nStep ${step}: ${url}`);
    await page.screenshot({ path: `toshi-e2e-step${step}.png` });
    
    // Check for dashboard
    if (url.includes('/admin/') || url.includes('dashboard')) {
      console.log('✅ REACHED DASHBOARD!');
      break;
    }
    if (url.includes('login')) {
      console.log('ℹ️ At login — logging in...');
      await page.fill('input[type="email"]', EMAIL);
      await page.fill('input[type="password"]', PASS);
      await page.click('button[type="submit"]');
      await page.waitForTimeout(3000);
      continue;
    }

    // Find clickable navigation button
    const clicked = await page.evaluate(() => {
      const btns = document.querySelectorAll('button, a[href], [wire\\:click]');
      for (const btn of btns) {
        const t = (btn.textContent || '').trim().toLowerCase();
        const wc = btn.getAttribute('wire:click') || '';
        const href = btn.getAttribute('href') || '';
        if (t.includes('next') || t.includes('continue') || t.includes('complete') || 
            t.includes('finish') || t.includes('submit') || t.includes('done') ||
            wc.includes('next') || wc.includes('save') || wc.includes('commit') ||
            href.includes('next') || href.includes('step')) {
          if (btn.offsetParent !== null) {
            btn.click();
            return t || wc || href;
          }
        }
      }
      return null;
    });

    if (clicked) {
      console.log('   Clicked:', clicked);
      await page.waitForTimeout(2000);
    } else {
      // Fill any empty visible fields
      const filled = await page.evaluate(() => {
        const inputs = document.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):visible, select:visible, textarea:visible');
        let count = 0;
        inputs.forEach(inp => {
          if (!inp.value && !inp.disabled && inp.offsetParent !== null) {
            if (inp.tagName === 'SELECT') {
              if (inp.options.length > 1) { inp.selectedIndex = 1; count++; }
            } else if (inp.type === 'checkbox' || inp.type === 'radio') {
              // skip
            } else {
              inp.value = 'test';
              count++;
            }
          }
        });
        return count;
      });
      console.log('   Filled', filled, 'fields');
      if (filled === 0) {
        console.log('   No more fields to fill, stopping walkthrough');
        break;
      }
      await page.waitForTimeout(500);
    }
  }

  // 6. Final verification
  console.log('\n=== FINAL ===');
  console.log('URL:', page.url());
  const html = await page.content();
  console.log('Appears logged in as admin:', html.includes('logout') || page.url().includes('/admin/') ? '✅ YES' : '❌ NO');
  await page.screenshot({ path: 'toshi-e2e-final.png' });
  
  await browser.close();
  console.log('\nDONE');
})();
