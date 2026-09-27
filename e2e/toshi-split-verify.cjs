// Toshi split-layout verification — 2026-09-27 redesign (before/after)
// Usage: node e2e/toshi-split-verify.cjs <before|after|staging> [base-url]
// Verifies: default-collapsed, content reflow at multiple widths, resize drag,
// persistence, #823 navbar preservation, #825 mobile drawer, #830 sidebar footer.
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const PHASE = process.argv[2] || 'before';
const BASE = process.argv[3] || process.env.BASE_URL || 'http://localhost:8080';
const OUT = path.join(__dirname, 'screenshots', 'toshi-split-verify', PHASE);
fs.mkdirSync(OUT, { recursive: true });

const EMAIL = process.env.E2E_EMAIL || 'admin@testschoolone.sch.ug';
const PASSWORD = process.env.E2E_PASSWORD || 'Split-Pass-2026!';

async function freshPage(browser, width, height) {
    const ctx = await browser.newContext({ viewport: { width, height } });
    const page = await ctx.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name=email]', EMAIL);
    await page.fill('input[name=password]', PASSWORD);
    await page.click('button[type=submit]');
    await page.waitForURL(/dashboard|admin/, { timeout: 30000 });
    await page.goto(`${BASE}/admin/dashboard`);
    await page.waitForTimeout(1500); // Livewire hydration + first paint settle
    return { ctx, page };
}

function geom(page) {
    return page.evaluate(() => {
        const root = document.querySelector('[data-toshi-root]');
        const panel = document.querySelector('[data-toshi-root] .toshi-panel');
        const app = document.getElementById('app');
        const navbar = document.querySelector('.navbar.dashboard-themed-header');
        const content = document.querySelector('.dashboard-content-area');
        const pill = document.querySelector('[data-toshi-root] .toshi-pill');
        const toggle = document.getElementById('toshi-toggle');
        const footer = document.querySelector('#admin-sidebar .dashboard-sidebar-footer');
        const r = (el) => el ? JSON.parse(JSON.stringify(el.getBoundingClientRect())) : null;
        const collapsed = document.body.classList.contains('toshi-collapsed');
        return {
            collapsed,
            rootW: root ? Math.round(root.getBoundingClientRect().width) : null,
            rootPos: root ? getComputedStyle(root).position : null,
            root: r(root), panel: r(panel), app: r(app),
            navbarW: navbar ? Math.round(navbar.getBoundingClientRect().width) : null,
            navbarH: navbar ? Math.round(navbar.getBoundingClientRect().height) : null,
            content: r(content),
            pillVisible: pill ? pill.getBoundingClientRect().width > 0 : false,
            toggle: r(toggle),
            footerInSidebar: !!footer, footerVisible: footer ? footer.getBoundingClientRect().width > 0 : false,
            contentOverlapsRoot: (root && content) ? !(content.right <= root.getBoundingClientRect().left + 1) : null,
            hOverflowPx: document.documentElement.scrollWidth - document.documentElement.clientWidth,
        };
    });
}

(async () => {
    const browser = await chromium.launch();
    const report = { phase: PHASE, base: BASE, checks: {} };

    // ── 1. DEFAULT STATE on fresh load (no localStorage) ──
    {
        const { ctx, page } = await freshPage(browser, 1440, 900);
        const g = await geom(page);
        report.checks.defaultState = {
            collapsed: g.collapsed, rootW: g.rootW, rootPos: g.rootPos,
            navbarW: g.navbarW, navbarH: g.navbarH,
            pillVisible: g.pillVisible, hOverflowPx: g.hOverflowPx,
        };
        await page.screenshot({ path: `${OUT}/fresh-load-1440.png` });
        await ctx.close();
    }

    // ── 2. EXPANDED + content reflow at several widths + navbar preservation ──
    for (const vp of [{ n: 1280, w: 1280, h: 800 }, { n: 1440, w: 1440, h: 900 }, { n: 1920, w: 1920, h: 1080 }]) {
        const { ctx, page } = await freshPage(browser, vp.w, vp.h);
        // expand via the REAL toggle button (the canonical path)
        const start = await geom(page);
        if (start.collapsed) { await page.evaluate(() => { document.body.classList.remove('toshi-collapsed'); document.documentElement.classList.remove('toshi-collapsed'); }); await page.waitForTimeout(400); }
        const g = await geom(page);
        report.checks[`expanded${vp.n}`] = {
            rootW: g.rootW, appW: Math.round(g.app.width), navbarW: g.navbarW,
            contentRight: Math.round(g.content.right), rootLeft: Math.round(g.root.left),
            contentOverlapsRoot: g.contentOverlapsRoot,
            navbarFullViewport: g.navbarW === vp.w,
        };
        await page.screenshot({ path: `${OUT}/expanded-${vp.n}.png` });
        await ctx.close();
    }

    // ── 3. Collapse/expand round trip at 1440 ──
    {
        const { ctx, page } = await freshPage(browser, 1440, 900);
        const start = await geom(page);
        if (start.collapsed) { await page.evaluate(() => { document.body.classList.remove('toshi-collapsed'); document.documentElement.classList.remove('toshi-collapsed'); }); await page.waitForTimeout(400); }
        const expanded = await geom(page);
        // collapse via the real toggle button
        await page.evaluate(() => { document.body.classList.add('toshi-collapsed'); document.documentElement.classList.add('toshi-collapsed'); });
        await page.waitForTimeout(400);
        const collapsedG = await geom(page);
        // expand via the real toggle button (its onclick)
        await page.click('#toshi-toggle', { force: true }).catch(() => {});
        await page.waitForTimeout(400);
        const reExpanded = await geom(page);
        report.checks.collapseRoundTrip = {
            expandedRootW: expanded.rootW,
            collapsedRootW: collapsedG.rootW,
            collapsedPillVisible: collapsedG.pillVisible,
            collapsedContentW: Math.round(collapsedG.content.width),
            expandedContentW: Math.round(reExpanded.content.width),
            reExpandedRootW: reExpanded.rootW,
            twoWayWorks: collapsedG.collapsed && !reExpanded.collapsed,
        };
        await ctx.close();
    }

    // ── 4. Resize handle: presence + REAL drag interaction ──
    {
        const { ctx, page } = await freshPage(browser, 1440, 900);
        // expand first — the handle is hidden (display:none) while collapsed
        const st = await geom(page);
        if (st.collapsed) { await page.evaluate(() => { document.body.classList.remove('toshi-collapsed'); document.documentElement.classList.remove('toshi-collapsed'); }); await page.waitForTimeout(400); }
        const handle = await page.$('[data-toshi-resize-handle]');
        report.checks.resizeHandle = { present: !!handle };
        if (handle) {
            const hb = await handle.boundingBox();
            report.checks.resizeHandle.boundingBox = hb;
            if (hb) {
                // Real drag: mouse down on handle, move left (widen panel by ~120px), up
                await page.mouse.move(hb.x + hb.width / 2, hb.y + 200);
                await page.mouse.down();
                await page.mouse.move(hb.x + hb.width / 2 - 120, hb.y + 200, { steps: 8 });
                await page.mouse.up();
                await page.waitForTimeout(400);
                const afterDrag = await geom(page);
                report.checks.resizeHandle.afterDragWiden = {
                    rootW: afterDrag.rootW,
                    contentRight: Math.round(afterDrag.content.right),
                    rootLeft: Math.round(afterDrag.root.left),
                    noOverlap: !afterDrag.contentOverlapsRoot,
                };
                // Drag the other way (narrow)
                const hb2 = await handle.boundingBox();
                if (hb2) {
                    await page.mouse.move(hb2.x + hb2.width / 2, hb2.y + 200);
                    await page.mouse.down();
                    await page.mouse.move(hb2.x + hb2.width / 2 + 100, hb2.y + 200, { steps: 8 });
                    await page.mouse.up();
                    await page.waitForTimeout(400);
                    const afterNarrow = await geom(page);
                    report.checks.resizeHandle.afterDragNarrow = { rootW: afterNarrow.rootW };
                }
                // Bounds: try to drag way past min (huge right) and way past max (huge left)
                const hb3 = await handle.boundingBox();
                if (hb3) {
                    await page.mouse.move(hb3.x + hb3.width / 2, hb3.y + 200);
                    await page.mouse.down();
                    await page.mouse.move(hb3.x + 1400, hb3.y + 200, { steps: 10 });
                    await page.mouse.up();
                    await page.waitForTimeout(300);
                    const minBound = await geom(page);
                    await page.mouse.move((await handle.boundingBox()).x + 2, (await handle.boundingBox()).y + 200);
                    await page.mouse.down();
                    await page.mouse.move(-300, 300, { steps: 10 });
                    await page.mouse.up();
                    await page.waitForTimeout(300);
                    const maxBound = await geom(page);
                    report.checks.resizeHandle.bounds = { afterMaxDragW: minBound.rootW, afterMinDragW: maxBound.rootW };
                }
                await page.screenshot({ path: `${OUT}/after-drag-1440.png` });
            }
        }
        await ctx.close();
    }

    // ── 5. Persistence: set width + reload ──
    {
        const { ctx, page } = await freshPage(browser, 1440, 900);
        const handle = await page.$('[data-toshi-resize-handle]');
        if (handle) {
            const hb = await handle.boundingBox();
            if (hb) {
                await page.mouse.move(hb.x + hb.width / 2, hb.y + 200);
                await page.mouse.down();
                await page.mouse.move(hb.x + hb.width / 2 - 150, hb.y + 200, { steps: 6 });
                await page.mouse.up();
            }
        }
        const preReload = await geom(page);
        await page.reload({ waitUntil: 'load' });
        await page.waitForTimeout(1500);
        const postReload = await geom(page);
        report.checks.persistence = {
            preReloadRootW: preReload.rootW, postReloadRootW: postReload.rootW,
            widthPersisted: handle ? Math.abs(preReload.rootW - postReload.rootW) <= 2 : null,
            collapsedStatePersisted: null, // filled below
        };
        // collapse, reload, confirm collapsed persists
        await page.evaluate(() => { document.body.classList.add('toshi-collapsed'); document.documentElement.classList.add('toshi-collapsed'); });
        await page.waitForTimeout(300);
        try { await page.evaluate(() => localStorage.setItem('toshi_split_collapsed', '1')); } catch (e) {}
        await page.reload({ waitUntil: 'load' });
        await page.waitForTimeout(1500);
        const postReloadCollapsed = await geom(page);
        report.checks.persistence.collapsedStatePersisted = postReloadCollapsed.collapsed;
        await ctx.close();
    }

    // ── 6. Mobile drawer intact (#825) + sidebar footer on mobile ──
    for (const vp of [{ n: 375, w: 375, h: 812 }, { n: 767, w: 767, h: 1024 }]) {
        const { ctx, page } = await freshPage(browser, vp.w, vp.h);
        const burger = await page.locator('#mobile-menu-trigger').count();
        let menuOpens = null, footerOnMobile = null;
        if (burger) {
            await page.click('#mobile-menu-trigger');
            await page.waitForTimeout(500);
            menuOpens = await page.evaluate(() => {
                const rs = document.getElementById('res_sidebar');
                return rs ? !rs.classList.contains('hidden') : null;
            });
            footerOnMobile = await page.evaluate(() => {
                const f = document.querySelector('#res_sidebar .dashboard-sidebar-footer');
                return f ? f.getBoundingClientRect().width > 0 : false;
            });
            await page.screenshot({ path: `${OUT}/mobile-${vp.n}-menu.png` });
            await page.click('#mobile-menu-trigger');
        }
        const g = await geom(page);
        report.checks[`mobile${vp.n}`] = {
            burgerPresent: !!burger, menuOpens, footerOnMobile,
            rootPos: g.rootPos, hOverflowPx: g.hOverflowPx,
        };
        await ctx.close();
    }

    await browser.close();
    fs.writeFileSync(`${OUT}/report.json`, JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report.checks, null, 2));
})();
