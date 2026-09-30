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
    // What does the attendance index show? Class list? Screenshot + structure
    await p.screenshot({ path: 'e2e/screenshots/prod-release-verify/attendance-index.png', fullPage: false });
    const info = await p.evaluate(() => {
        const links = Array.from(document.querySelectorAll('a')).map(a => ({ t: (a.textContent||'').trim().slice(0,40), href: a.getAttribute('href') })).filter(l => l.href && l.href.includes('attendance'));
        const forms = Array.from(document.querySelectorAll('form')).map(f => ({ action: f.action.slice(-60), method: f.method }));
        const heading = document.querySelector('h1, h2') ? document.querySelector('h1, h2').textContent.trim().slice(0, 60) : null;
        return { links: links.slice(0, 8), forms: forms.slice(0, 5), heading, bodySnippet: document.body.textContent.replace(/\s+/g, ' ').slice(0, 300) };
    });
    console.log(JSON.stringify(info, null, 1));
    await b.close();
})();
