const { chromium } = require('playwright');
(async () => {
    const b = await chromium.launch();
    const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
    const p = await ctx.newPage();

    // Login
    await p.goto('https://klassapp.xyz/login', { waitUntil: 'load', timeout: 90000 });
    await p.fill('input[name=email]', 'prodverify.demo-lakeview-junior@demo.klassapp.test');
    await p.fill('input[name=password]', 'ProdVerify-2026!');
    await Promise.all([
        p.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
        p.click('button[type=submit]')
    ]);
    await p.waitForTimeout(3000);
    console.log('Logged in, URL:', p.url());

    // Navigate to attendance add with standardLink_id preselected
    await p.goto('https://klassapp.xyz/admin/attendance/add?standardLink_id=307', {
        waitUntil: 'networkidle', timeout: 90000
    });
    await p.waitForTimeout(5000); // Let Vue boot fully

    // Inspect what Vue has rendered
    const dom = await p.evaluate(() => {
        const selects = Array.from(document.querySelectorAll('select')).map(s => ({
            name: s.name || s.getAttribute('v-model') || s.id,
            optCount: s.options.length,
            opts: Array.from(s.options).slice(0, 10).map(o => ({
                v: o.value,
                t: (o.textContent || '').trim().slice(0, 40)
            }))
        }));
        const dateInputs = Array.from(document.querySelectorAll('input[type="date"]')).map(i => i.outerHTML.slice(0, 120));
        const radios = Array.from(document.querySelectorAll('input[type="radio"]')).map(r => ({
            name: r.name,
            value: r.value,
            checked: r.checked
        }));
        const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]')).slice(0, 10).map(c => ({
            name: c.name,
            value: c.value,
            checked: c.checked,
            text: (c.parentElement?.textContent || '').trim().slice(0, 40)
        }));
        const btn = Array.from(document.querySelectorAll('button')).map(b => ({
            text: (b.textContent || '').trim().slice(0, 40),
            className: b.className
        }));
        return { selects, dateInputs, radios, checkboxes, buttons: btn };
    });

    console.log('Selects:', JSON.stringify(dom.selects, null, 2));
    console.log('Date inputs:', dom.dateInputs);
    console.log('Radios:', JSON.stringify(dom.radios, null, 2));
    console.log('Checkboxes:', JSON.stringify(dom.checkboxes, null, 2));
    console.log('Buttons:', JSON.stringify(dom.buttons.slice(0, 6), null, 2));

    await p.screenshot({ path: 'e2e/screenshots/prod-release-verify/attendance-vue-rendered.png' });
    await b.close();
})();
