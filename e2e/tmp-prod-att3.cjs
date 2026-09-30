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
    await p.goto('https://klassapp.xyz/admin/attendance', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(2500);
    // the page is a class-picker: find links to a specific class attendance page
    const classLinks = await p.evaluate(() => {
        return Array.from(document.querySelectorAll('a')).map(a => ({ t: (a.textContent||'').trim().slice(0,50), href: a.getAttribute('href') }))
            .filter(l => l.href && (l.href.match(/\/admin\/(class|attendance|standard)/) || /p\.?\d|class/i.test(l.t)))
            .filter(l => !l.href.includes('/admin/attendance')).slice(0, 10);
    });
    console.log('class links:', JSON.stringify(classLinks, null, 1));
    await b.close();
})();
