const { chromium } = require('playwright');
(async () => {
    const b = await chromium.launch();
    const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
    const p = await ctx.newPage();
    await p.goto('https://klassapp.xyz/login', { waitUntil: 'load', timeout: 90000 });
    await p.fill('input[name=email]', 'prodverify.demo-lakeview-junior@demo.klassapp.test');
    await p.fill('input[name=password]', 'ProdVerify-2026!');
    await Promise.all([ p.waitForNavigation({waitUntil:'load',timeout:90000}).catch(()=>null), p.click('button[type=submit]') ]);
    await p.waitForTimeout(3000);

    // parent/children as admin — follow the redirect, record the final URL
    await p.goto('https://klassapp.xyz/parent/children', { waitUntil: 'load', timeout: 90000 });
    console.log('parent/children final URL:', p.url());
    console.log('is redirect to own dashboard:', p.url().includes('/admin/dashboard') || p.url().includes('/login'));

    // attendance write-then-read: find the class attendance page
    await p.goto('https://klassapp.xyz/admin/attendance', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(2000);
    console.log('attendance page URL:', p.url());
    const links = await p.evaluate(() => Array.from(document.querySelectorAll('a')).map(a => ({ t: (a.textContent||'').trim().slice(0,30), href: a.getAttribute('href') })).filter(l => l.href && (l.href.includes('attendance'))).slice(0, 6));
    console.log('attendance links:', JSON.stringify(links, null, 1));
    await b.close();
})();
