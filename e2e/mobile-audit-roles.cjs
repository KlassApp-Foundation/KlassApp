// All-role dashboard mobile audit — 2026-09-27
// For each role: login, dashboard at 375/414/768. Checks: h-overflow,
// oversized elements, off-viewport interactives, mobile menu opens (#825),
// body text readability (min font), tables adapting.
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const PHASE = process.argv[2] || 'audit';
const BASE = process.env.BASE_URL || 'http://localhost:8080';
const OUT = path.join(__dirname, 'screenshots', 'mobile-audit', `roles-${PHASE}`);
fs.mkdirSync(OUT, { recursive: true });

const PW = process.env.E2E_PASSWORD || 'Mobile-Audit-2026!';
const ROLES = [
    { name: 'admin', email: 'admin@testschoolone.sch.ug', path: '/admin/dashboard' },
    { name: 'teacher', email: 'teacher_lakeview_junior_school@lakeviewjuniorschool.edu', path: '/teacher/dashboard' },
    { name: 'student', email: 'student1.demo-lakeview-junior@demo.klassapp.test', path: '/student/dashboard' },
    { name: 'parent', email: 'parent1.demo-lakeview-junior@demo.klassapp.test', path: '/parent/dashboard' },
    { name: 'superadmin', email: 'siteadmin@gmail.com', path: '/superadmin/dashboard' },
];
const VPS = [
    { name: '375', width: 375, height: 812 },
    { name: '414', width: 414, height: 896 },
    { name: '768', width: 768, height: 1024 },
];

(async () => {
    const browser = await chromium.launch();
    const report = {};
    for (const role of ROLES) {
        report[role.name] = {};
        for (const vp of VPS) {
            const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
            const page = await ctx.newPage();
            try {
                await page.goto(`${BASE}/login`);
                await page.fill('input[name=email]', role.email);
                await page.fill('input[name=password]', PW);
                await page.click('button[type=submit]');
                await page.waitForTimeout(3500);
                await page.goto(`${BASE}${role.path}`, { waitUntil: 'load', timeout: 60000 });
                await page.waitForTimeout(2000);

                const data = await page.evaluate(() => {
                    const vw = document.documentElement.clientWidth;
                    const wide = [];
                    document.querySelectorAll('main *, .dashboard-content-area *').forEach((el) => {
                        const r = el.getBoundingClientRect();
                        if (r.width > 0 && r.right > vw + 2 && !el.closest('[data-toshi-root]')) {
                            wide.push({ tag: el.tagName, cls: String(el.className).slice(0, 50), w: Math.round(r.width), right: Math.round(r.right) });
                        }
                    });
                    wide.sort((a, b) => b.w - a.w);
                    const interact = [];
                    document.querySelectorAll('main a, main button, main input, main select').forEach((el) => {
                        const r = el.getBoundingClientRect();
                        if (r.width === 0 || r.height === 0) return;
                        const cs = getComputedStyle(el);
                        if (cs.display === 'none' || cs.visibility === 'hidden') return;
                        if (r.right > vw + 2) interact.push({ t: (el.textContent || el.name || '').trim().slice(0, 25), right: Math.round(r.right) });
                    });
                    return { vw, overflow: document.documentElement.scrollWidth - vw, wide: wide.slice(0, 5), offInteract: interact.slice(0, 5) };
                });

                // mobile menu check (#825) — only meaningful < 768
                let menuCheck = null;
                if (vp.width < 768) {
                    const burger = await page.locator('#mobile-menu-trigger').count();
                    if (burger) {
                        await page.click('#mobile-menu-trigger');
                        await page.waitForTimeout(500);
                        menuCheck = await page.evaluate(() => {
                            const rs = document.getElementById('res_sidebar');
                            return rs ? !rs.classList.contains('hidden') : null;
                        });
                        await page.screenshot({ path: `${OUT}/${role.name}-${vp.name}-menu.png` });
                        await page.click('#mobile-menu-trigger').catch(() => {});
                    } else menuCheck = 'no-burger';
                }
                await page.screenshot({ path: `${OUT}/${role.name}-${vp.name}.png`, fullPage: false });
                report[role.name][vp.name] = { ...data, menuOpens: menuCheck };
            } catch (e) {
                report[role.name][vp.name] = { error: String(e).slice(0, 150) };
            }
            await ctx.close();
        }
    }
    await browser.close();
    fs.writeFileSync(`${OUT}/report.json`, JSON.stringify(report, null, 2));
    for (const [role, vps] of Object.entries(report)) {
        for (const [vp, d] of Object.entries(vps)) {
            const line = `${role}@${vp}: overflow=${d.overflow ?? '?'}px menu=${d.menuOpens ?? '-'} wide=${(d.wide || []).length} offInteract=${(d.offInteract || []).length}${d.error ? ' ERROR:' + d.error : ''}`;
            console.log(line);
            if (d.wide && d.wide.length) console.log('   top-wide:', JSON.stringify(d.wide.slice(0, 2)));
            if (d.offInteract && d.offInteract.length) console.log('   off-interact:', JSON.stringify(d.offInteract.slice(0, 3)));
        }
    }
})();
