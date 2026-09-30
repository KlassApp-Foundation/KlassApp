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
    await p.goto('https://klassapp.xyz/admin/attendance/add', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(2000);
    // inspect the /admin/attendance/add forms (there are 2: maybe class-picker then entry)
    const info = await p.evaluate(() => {
        const forms = Array.from(document.querySelectorAll('form[action*="attendance/add"]')).map(f => ({
            action: f.action, method: f.method,
            selects: Array.from(f.querySelectorAll('select')).map(s => ({ n: s.name, opts: Array.from(s.options).slice(0, 6).map(o => ({ v: o.value, t: (o.textContent||'').trim().slice(0,30) })) })),
            inputs: Array.from(f.querySelectorAll('input')).slice(0, 8).map(i => ({ n: i.name, t: i.type, v: (i.value||'').slice(0,12) })),
            text: f.textContent.replace(/\s+/g, ' ').slice(0, 200),
        }));
        return forms;
    });
    console.log(JSON.stringify(info, null, 1));
    await b.close();
})();
