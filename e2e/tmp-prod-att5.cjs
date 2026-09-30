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
    const r = await p.goto('https://klassapp.xyz/admin/attendance/add?standardLink_id=307', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(2500);
    console.log('status:', r.status());
    const form = await p.evaluate(() => {
        const f = document.querySelector('form[action*="attendance/add"], form[action$="/attendance/add"]');
        if (!f) return { found: false };
        return { found: true, action: f.action, method: f.method,
            fields: Array.from(f.querySelectorAll('input, select')).slice(0, 16).map(i => ({ n: i.name, t: i.type, v: (i.value||'').slice(0,14) })),
            buttons: Array.from(f.querySelectorAll('button')).map(x => (x.textContent||'').trim().slice(0,24)).slice(0,4),
            studentRows: f.querySelectorAll('[class*="student"], input[name*="student"], input[name*="attendance"]').length };
    });
    console.log(JSON.stringify(form, null, 1));
    await p.screenshot({ path: 'e2e/screenshots/prod-release-verify/attendance-entry-form.png' });
    await b.close();
})();
