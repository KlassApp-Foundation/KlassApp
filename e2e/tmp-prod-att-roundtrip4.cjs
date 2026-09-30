const { chromium } = require('playwright');
(async () => {
    const b = await chromium.launch();
    const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
    const p = await ctx.newPage();
    await p.goto('https://klassapp.xyz/login', { waitUntil: 'load', timeout: 90000 });
    await p.fill('input[name=email]', 'prodverify.demo-lakeview-junior@demo.klassapp.test');
    await p.fill('input[name=password]', 'ProdVerify-2026!');
    await Promise.all([ p.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null), p.click('button[type=submit]') ]);
    await p.waitForTimeout(3000);
    const r = await p.goto('https://klassapp.xyz/admin/attendance/add', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(2500);
    const snap = await p.evaluate(() => {
        const body = document.body.textContent.replace(/\s+/g, ' ').slice(0, 600);
        const allForms = Array.from(document.querySelectorAll('form')).map(f => ({ a: f.getAttribute('action'), m: f.method }));
        return { body, allForms };
    });
    console.log('status:', r.status());
    console.log('body:', snap.body);
    console.log('forms:', JSON.stringify(snap.allForms));
    await p.screenshot({ path: 'e2e/screenshots/prod-release-verify/attendance-add.png' });
    await b.close();
})();
