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

    // Navigate to the form with synthetic StandardLink
    await p.goto('https://klassapp.xyz/admin/attendance/add?standardLink_id=307', {
        waitUntil: 'load', timeout: 90000
    });
    await p.waitForTimeout(3000);
    console.log('Form page URL:', p.url());

    // Deep inspection of the form
    const formInfo = await p.evaluate(() => {
        const allForms = Array.from(document.querySelectorAll('form'));
        const results = allForms.map(f => {
            const inputs = Array.from(f.querySelectorAll('input, select, textarea'));
            const studentChecks = Array.from(f.querySelectorAll('input[type="checkbox"]')).map(c => ({
                name: c.name,
                value: c.value,
                checked: c.checked
            }));
            return {
                action: f.action,
                method: f.method,
                id: f.id,
                className: f.className,
                inputCount: inputs.length,
                inputs: inputs.slice(0, 20).map(i => ({
                    tag: i.tagName,
                    type: i.type,
                    name: i.name,
                    id: i.id,
                    value: (i.value || '').slice(0, 30)
                })),
                studentCheckboxCount: studentChecks.length,
                studentChecks: studentChecks.slice(0, 10)
            };
        });
        return {
            bodySnippet: document.body.textContent.replace(/\s+/g, ' ').slice(0, 400),
            forms: results
        };
    });

    console.log('Body snippet:', formInfo.bodySnippet);
    console.log('Forms:', JSON.stringify(formInfo.forms, null, 2));

    await p.screenshot({ path: 'e2e/screenshots/prod-release-verify/attendance-form-diagnostic.png' });
    await b.close();
})();
