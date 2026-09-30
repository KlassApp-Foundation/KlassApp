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
    await p.goto('https://klassapp.xyz/admin/attendance/add?standardLink_id=307', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(2500);
    // The real interactive path: select the class from the dropdown, then the
    // student list loads; pick date=today, session=forenoon, mark present, save.
    const classSelect = await p.$('select[name*="class" i], select[name*="standard" i], select[name*="section" i]');
    if (!classSelect) { console.log('no class select found'); 
        const sels = await p.evaluate(() => Array.from(document.querySelectorAll('select')).map(s => ({ n: s.name, opts: Array.from(s.options).slice(0,5).map(o => o.value) })));
        console.log(JSON.stringify(sels, null, 1));
        await b.close(); return; }
    const opts = await classSelect.$$eval('option', os => os.map(o => ({ v: o.value, t: (o.textContent||'').trim() })));
    console.log('class options:', JSON.stringify(opts.slice(0, 6)));
    await b.close();
})();
