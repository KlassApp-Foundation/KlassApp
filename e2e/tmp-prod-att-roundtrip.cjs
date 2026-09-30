// PRODUCTION today's-attendance round trip — real write via the real form,
// then read back. Demo-school synthetic class (sl 307) + demo students only.
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

    // 1. GET the class attendance page for sl 307 (the getAttendance view that used to 500)
    const viewResp = await p.goto('https://klassapp.xyz/admin/standardLink/show/attendances/307?select_month=' + new Date().toISOString().slice(0, 7), { waitUntil: 'domcontentloaded', timeout: 90000 });
    console.log('class attendance view status:', viewResp ? viewResp.status() : 'ERR');
    await p.waitForTimeout(2000);
    await p.screenshot({ path: 'e2e/screenshots/prod-release-verify/attendance-view-307.png' });

    // 2. The WRITE path: find the add-attendance form for today on this class.
    //    Route: POST /admin/standardLink/show/attendances/{id} (showAttendance store)
    //    Inspect the page for the form structure:
    const formInfo = await p.evaluate(() => {
        const forms = Array.from(document.querySelectorAll('form')).map(f => ({
            action: f.getAttribute('action'),
            method: f.method,
            inputs: Array.from(f.querySelectorAll('input, select, button')).slice(0, 8).map(i => ({ tag: i.tagName, name: i.name, type: i.type, value: (i.value || '').slice(0, 20) })),
        }));
        return forms.filter(f => f.action && f.action.includes('attendance')).slice(0, 3);
    });
    console.log('attendance forms:', JSON.stringify(formInfo, null, 1));

    // 3. Real write attempt via fetch with the page's CSRF (today's date) — same
    //    attack-of-record as cf473818's verification, JSON headers so refusals surface.
    const writeResult = await p.evaluate(async () => {
        const token = document.querySelector('meta[name=csrf-token]')?.content;
        const today = new Date().toISOString().slice(0, 10);
        // find a student id on this class page (demo students only — school 53)
        // build the payload shape AttendanceAddRequest expects:
        // students[<id>] = present(1)/absent(0), plus select_date/session
        const body = new FormData();
        body.append('select_date', today);
        body.append('session', 'forenoon');
        const r = await fetch('/admin/attendance/add', { method: 'GET' });
        const addPage = await r.text();
        return { addPageStatus: r.status, token: !!token, today };
    });
    console.log('probe:', JSON.stringify(writeResult));

    await b.close();
})();
