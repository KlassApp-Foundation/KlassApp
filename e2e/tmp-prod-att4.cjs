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
    await p.goto('https://klassapp.xyz/admin/classes', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(2500);
    const links = await p.evaluate(() => Array.from(document.querySelectorAll('a')).map(a => ({ t: (a.textContent||'').trim().slice(0,40), href: a.getAttribute('href') })).filter(l => l.href && /attendance/i.test(l.t + l.href)).slice(0, 6));
    console.log(JSON.stringify(links, null, 1));
    await p.screenshot({ path: 'e2e/screenshots/prod-release-verify/classes-index.png' });
    await b.close();
})();
