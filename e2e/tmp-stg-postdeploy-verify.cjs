// STAGING post-deploy verification of the 7283f66f release (same app code as prod).
// Demo admin phase4.admin (school 1) only. Foreign targets = school 2 fixtures.
// Attendance is submitted ALL-PRESENT so no parent SMS/WhatsApp/email fires.
const { chromium } = require('playwright');
const fs = require('fs');

const BASE = 'https://klassapp-staging-7mpoqg.laravel.cloud';
const OUT = __dirname + '/screenshots/staging-postdeploy-verify';
fs.mkdirSync(OUT, { recursive: true });
const SL_OWN = '4', SL_FOREIGN = 9, FB_FOREIGN = 1, FM_FOREIGN = 1;
const DATE = process.env.ATT_DATE || '2026-09-27';

async function login(page) {
    await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
    await page.fill('input[name=email]', 'phase4.admin@klassapp.xyz');
    await page.fill('input[name=password]', process.env.PW);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
        page.click('[data-testid="ap-primary-submit"], button[type=submit]'),
    ]);
    await page.waitForTimeout(2500);
    return !page.url().includes('/login');
}

(async () => {
    const browser = await chromium.launch({ headless: true });
    const report = { base: BASE, at: new Date().toISOString(), checks: {} };

    // ── A. Attendance write via the real UI ──
    {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const page = await ctx.newPage();
        report.checks.login = await login(page);
        if (!process.env.SKIP_ATT) {
        await page.goto(`${BASE}/admin/attendance/add`, { waitUntil: 'networkidle', timeout: 90000 });
        await page.waitForTimeout(1500);
        const classSelect = page.locator('select:not(#academic_year)').first();
        await classSelect.selectOption(SL_OWN);
        await page.fill('input[type=date]', DATE);
        await page.locator('input[type=radio]').nth(1).check(); // Afternoon
        await page.click('button:has-text("Select Students")');
        await page.waitForTimeout(800);
        const presentCount = await page.locator('input[type=checkbox].accent-green-600').count();
        const postResp = page.waitForResponse(r => r.url().endsWith('/admin/attendance/add') && r.request().method() === 'POST', { timeout: 60000 });
        await page.click('button:has-text("Submit Attendance")');
        const resp = await postResp;
        let body = null; try { body = await resp.json(); } catch (e) { body = (await resp.text()).slice(0, 200); }
        await page.waitForTimeout(1500);
        await page.screenshot({ path: `${OUT}/attendance-submitted.png`, fullPage: true });
        report.checks.attendanceWrite = { presentCount, status: resp.status(), body };

        // read back through the app: class attendance view
        const r = await page.goto(`${BASE}/admin/standardLink/show/attendances/${SL_OWN}`, { waitUntil: 'networkidle', timeout: 90000 });
        await page.waitForTimeout(1500);
        await page.screenshot({ path: `${OUT}/attendance-readback.png`, fullPage: true });
        report.checks.attendanceReadPage = { status: r.status() };
        }

        // ── B/C. Cross-school probes (direct HTTP with this admin's session) ──
        await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
        const token = await page.evaluate(() => document.querySelector('meta[name=csrf-token]')?.content);
        const post = (url, form) => ctx.request.post(`${BASE}${url}`, { form: { _token: token, ...form }, maxRedirects: 0, failOnStatusCode: false });
        const get = (url) => ctx.request.get(`${BASE}${url}`, { maxRedirects: 0, failOnStatusCode: false });
        const summarize = async (res) => ({ status: res.status(), location: res.headers()['location'] || null });

        report.checks.idor819 = {
            foreignUpdateStatus: await summarize(await post(`/admin/standardLink/updateStatus/${SL_FOREIGN}`, { status: 1 })),
            foreignEditGet: await summarize(await get(`/admin/standardLink/edit/${SL_FOREIGN}`)),
            foreignIdCard: await summarize(await get(`/admin/standardLink/id-card/${SL_FOREIGN}`)),
            foreignIdCardPrint: await summarize(await get(`/admin/standardLink/id-card-print/${SL_FOREIGN}`)),
            ownIdCardControl: await summarize(await get(`/admin/standardLink/id-card/${SL_OWN}`)),
        };
        report.checks.idor839 = {
            view: await summarize(await get(`/admin/feedback/edit/${FB_FOREIGN}`)),
            reply: await summarize(await post(`/admin/feedback/edit/${FB_FOREIGN}`, { message: 'IDOR probe reply', category: 'feedback_or_bug_for_app_or_software' })),
            statusChange: await summarize(await post(`/admin/feedback/updateStatus/${FM_FOREIGN}`, { status: 1 })),
            ownIndexControl: await summarize(await get(`/admin/feedbacks`)),
        };
        await ctx.close();
    }

    // ── D. Mobile nav tap-to-open @375 ──
    {
        const ctx = await browser.newContext({ viewport: { width: 375, height: 812 }, hasTouch: true, isMobile: true });
        const page = await ctx.newPage();
        await login(page);
        await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load', timeout: 90000 });
        await page.waitForTimeout(2000);
        const burger = await page.locator('#mobile-menu-trigger').count();
        let menuOpens = null, navWorks = null, navTo = null;
        const isOpen = () => page.evaluate(() => { const rs = document.getElementById('res_sidebar'); return rs ? !rs.classList.contains('hidden') && rs.getBoundingClientRect().width > 0 : null; });
        const closedBefore = await isOpen();
        if (burger) {
            await page.tap('#mobile-menu-trigger');
            await page.waitForTimeout(700);
            menuOpens = await isOpen();
            await page.screenshot({ path: `${OUT}/mobile-375-menu-open.png` });
            const link = page.locator('#res_sidebar a[href*="/admin/"]:visible').first();
            if (menuOpens && await link.count()) {
                navTo = await link.getAttribute('href');
                await Promise.all([page.waitForNavigation({ timeout: 45000 }).catch(() => null), link.tap()]);
                navWorks = !page.url().includes('/login') && page.url() !== `${BASE}/admin/dashboard`;
            }
        }
        report.checks.mobile375 = { burgerPresent: !!burger, openBeforeTap: closedBefore, menuOpens, navTo, navWorks, landedOn: page.url().replace(BASE, '') };
        await ctx.close();
    }

    console.log(JSON.stringify(report, null, 2));
    fs.writeFileSync(`${OUT}/report.json`, JSON.stringify(report, null, 2));
    await browser.close();
})();
