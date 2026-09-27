// UI-polish before/after capture — 2026-09-27
// Usage: node e2e/ui-polish-capture.js <before|after>
// Logs in as local synthetic admin, captures:
//   - /admin/dashboard at 1280/1440/1920 (issues 1-4 all visible)
//   - collapsed sidebar at 1440 (issue 4 rail state)
//   - 375 + 768 mobile (issue 4 must not regress mobile nav)
// Writes to e2e/screenshots/ui-polish-20260927/<phase>/
const { chromium } = require('playwright');

const PHASE = process.argv[2] || 'before';
const BASE = process.env.BASE_URL || 'http://localhost:8080';
const OUT = `e2e/screenshots/ui-polish-20260927/${PHASE}`;
const EMAIL = process.env.E2E_EMAIL || 'admin@testschoolone.sch.ug';
const PASSWORD = process.env.E2E_PASSWORD || 'Polish-Pass-123!';

(async () => {
    const browser = await chromium.launch();
    const report = { phase: PHASE, base: BASE, shots: [], probes: {} };

    for (const vp of [
        { name: '1280', width: 1280, height: 800 },
        { name: '1440', width: 1440, height: 900 },
        { name: '1920', width: 1920, height: 1080 },
    ]) {
        const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/login`);
        await page.fill('input[name=email]', EMAIL);
        await page.fill('input[name=password]', PASSWORD);
        await page.click('button[type=submit]');
        await page.waitForURL(/dashboard/);
        await page.waitForTimeout(1200);

        // Issue 2 probe: LIVE badge presence
        const liveBadge = await page.locator('[data-testid="dashboard-live-badge"]').count();

        // Issue 3 probe: academic-year label computed color
        const labelInfo = await page.evaluate(() => {
            const el = document.querySelector('.dashboard-themed-header .tw-form-label');
            if (!el) return null;
            const cs = getComputedStyle(el);
            return { color: cs.color, text: el.textContent.trim(), visible: cs.display !== 'none' };
        });

        // Issue 4 probe: where are bell + profile avatar?
        const chromeInfo = await page.evaluate(() => {
            const bell = document.querySelector('#admin-sidebar .dashboard-sidebar-footer-bell, .dashboard-sidebar-footer-bell');
            const nav = document.querySelector('nav.dashboard-themed-header');
            const sidebar = document.getElementById('admin-sidebar');
            const bellVisible = bell ? (bell.getBoundingClientRect().width > 0) : false;
            const bellRect = bell ? bell.getBoundingClientRect() : null;
            const profile = document.querySelector('.profile-click');
            const profileInNav = profile ? !!profile.closest('nav') : null;
            const profileInSidebar = profile ? !!profile.closest('#admin-sidebar') : null;
            const sbRect = sidebar ? sidebar.getBoundingClientRect() : null;
            return { bellInSidebar: !!bell, bellVisible, bellY: bellRect ? Math.round(bellRect.y) : null, profileInNav, profileInSidebar, sidebarW: sbRect ? Math.round(sbRect.width) : null };
        });

        const file = `${OUT}/dashboard-${vp.name}.png`;
        await page.screenshot({ path: file, fullPage: false });
        report.shots.push(file);

        if (vp.name === '1440') {
            // Sidebar-footer crop (issue 4) + open profile dropdown from its new home
            const footer = page.locator('#admin-sidebar .dashboard-sidebar-footer');
            if (await footer.count()) {
                const fcrop = `${OUT}/sidebar-footer-1440.png`;
                await footer.screenshot({ path: fcrop });
                report.shots.push(fcrop);
                await page.click('.dashboard-sidebar-footer .profile-click');
                await page.waitForTimeout(400);
                const openCrop = `${OUT}/sidebar-footer-profile-open-1440.png`;
                await page.screenshot({ path: openCrop });
                report.shots.push(openCrop);
                report.probes.profileDropdownOpens = await page.evaluate(() => !!document.querySelector('.profile-click.open .user-dtl'));
                await page.keyboard.press('Escape');
            }
            // Toshi scene-2 card crop (issue 1)
            const demo = page.locator('[data-testid="es-demo-scene-toshi"]');
            if (await demo.count()) {
                // scene 2 must be active to be visible; force it via dots if needed
                await page.evaluate(() => {
                    const s = document.querySelector('[data-scene="1"]');
                    if (s && !s.classList.contains('is-active')) {
                        document.querySelectorAll('.es-demo-scene').forEach((x) => x.classList.remove('is-active'));
                        s.classList.add('is-active');
                        s.setAttribute('aria-hidden', 'false');
                    }
                });
                await page.waitForTimeout(300);
                const crop = `${OUT}/toshi-card-${vp.name}.png`;
                await demo.screenshot({ path: crop });
                report.shots.push(crop);
            }
            // Header crop (issues 2+3+4 visibility)
            const header = page.locator('nav.dashboard-themed-header');
            if (await header.count()) {
                const hcrop = `${OUT}/header-${vp.name}.png`;
                await header.screenshot({ path: hcrop });
                report.shots.push(hcrop);
            }
            // Collapsed sidebar state (issue 4)
            await page.click('#sidebar-collapse-toggle');
            await page.waitForTimeout(600);
            const collapsed = `${OUT}/dashboard-1440-collapsed.png`;
            await page.screenshot({ path: collapsed });
            report.shots.push(collapsed);
            const collapsedProbe = await page.evaluate(() => {
                const sb = document.getElementById('admin-sidebar');
                return { sidebarW: sb ? Math.round(sb.getBoundingClientRect().width) : null };
            });
            report.probes[`collapsed1440`] = collapsedProbe;
            await page.click('#sidebar-collapse-toggle');
            await page.waitForTimeout(600);
        }

        report.probes[vp.name] = { liveBadge, labelInfo, chromeInfo };
        await ctx.close();
    }

    // Mobile widths — issue 4 must not regress the mobile nav fix (#825).
    // 767 (not 768): the burger is md:hidden, so exactly-768 shows the desktop rail.
    for (const vp of [
        { name: '375', width: 375, height: 812 },
        { name: '767', width: 767, height: 1024 },
    ]) {
        const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/login`);
        await page.fill('input[name=email]', EMAIL);
        await page.fill('input[name=password]', PASSWORD);
        await page.click('button[type=submit]');
        await page.waitForURL(/dashboard/);
        await page.waitForTimeout(1000);

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        // Mobile menu toggle (the #825 fix)
        const burger = await page.locator('#mobile-menu-trigger').count();
        let menuOpened = null;
        if (burger) {
            await page.click('#mobile-menu-trigger');
            await page.waitForTimeout(500);
            menuOpened = await page.evaluate(() => {
                const rs = document.getElementById('res_sidebar');
                return rs ? !rs.classList.contains('hidden') : null;
            });
            const mshot = `${OUT}/mobile-${vp.name}-menu-open.png`;
            await page.screenshot({ path: mshot });
            report.shots.push(mshot);
            await page.click('#mobile-menu-trigger');
            await page.waitForTimeout(400);
        }
        const shot = `${OUT}/mobile-${vp.name}.png`;
        await page.screenshot({ path: shot });
        report.shots.push(shot);
        report.probes[`mobile-${vp.name}`] = { horizontalOverflowPx: overflow, burgerPresent: !!burger, menuOpens: menuOpened };
        await ctx.close();
    }

    await browser.close();
    require('fs').writeFileSync(`${OUT}/report.json`, JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report.probes, null, 2));
})();
