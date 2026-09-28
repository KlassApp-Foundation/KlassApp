// Toshi mobile deep audit — 2026-09-27 (#831 follow-up)
// Real interaction at 375/414: collapsed pill geometry, open-drawer usability
// (text sizes, tap targets, overflow), resize-handle leak check, composer input.
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const PHASE = process.argv[2] || 'audit';
const BASE = process.env.BASE_URL || 'http://localhost:8080';
const OUT = path.join(__dirname, 'screenshots', 'mobile-audit', 'toshi-' + PHASE);
fs.mkdirSync(OUT, { recursive: true });

const EMAIL = process.env.E2E_EMAIL || 'admin@testschoolone.sch.ug';
const PASSWORD = process.env.E2E_PASSWORD || 'Mobile-Audit-2026!';

(async () => {
    const browser = await chromium.launch();
    const report = {};
    for (const vp of [{ n: '375', w: 375, h: 812 }, { n: '414', w: 414, h: 896 }]) {
        const ctx = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/login`);
        await page.fill('input[name=email]', EMAIL);
        await page.fill('input[name=password]', PASSWORD);
        await page.click('button[type=submit]');
        await page.waitForURL(/dashboard|admin/, { timeout: 30000 });
        await page.goto(`${BASE}/admin/dashboard`);
        await page.waitForTimeout(2000);

        // 1. Collapsed pill state at mobile
        const collapsed = await page.evaluate(() => {
            const pill = document.getElementById('toshi-pill');
            const root = document.querySelector('[data-toshi-root]');
            const handle = document.querySelector('[data-toshi-resize-handle]');
            const r = (el) => el ? el.getBoundingClientRect() : null;
            return {
                pillVisible: pill ? r(pill).width > 0 : false,
                pillRect: pill ? { x: Math.round(r(pill).x), y: Math.round(r(pill).y), w: Math.round(r(pill).width), h: Math.round(r(pill).height) } : null,
                pillFont: pill ? getComputedStyle(pill).fontSize : null,
                rootW: root ? Math.round(r(root).width) : null,
                // resize handle must NOT be visible/interactive on mobile (display rules are ≥1280)
                handleVisible: handle ? (r(handle).width > 0 && getComputedStyle(handle).display !== 'none') : false,
                handleCursor: handle ? getComputedStyle(handle).cursor : null,
                bodyCollapsed: document.body.classList.contains('toshi-collapsed'),
                overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
            };
        });
        await page.screenshot({ path: `${OUT}/mobile-${vp.n}-collapsed.png` });

        // 2. OPEN the drawer via the pill and measure the chat interface
        await page.evaluate(() => { document.body.classList.remove('toshi-collapsed'); });
        await page.evaluate(() => { const p = document.getElementById('toshi-pill'); if (p) p.click(); });
        await page.waitForTimeout(800);
        const open = await page.evaluate(() => {
            const panel = document.querySelector('[data-toshi-root] .toshi-panel');
            const composer = document.querySelector('[data-toshi-root] .toshi-composer, [data-toshi-root] textarea, [data-toshi-root] input[type=text]');
            const msgs = document.querySelectorAll('[data-toshi-root] .toshi-message, [data-toshi-root] .toshi-bubble, [data-toshi-root] .toshi-msg');
            const r = (el) => el ? el.getBoundingClientRect() : null;
            const pr = r(panel);
            let smallestFont = null, smallTextSample = [];
            document.querySelectorAll('[data-toshi-root] *').forEach((el) => {
                if (el.children.length > 0) return;
                const t = (el.textContent || '').trim();
                if (!t) return;
                const fs = parseFloat(getComputedStyle(el).fontSize);
                if (fs < 12) smallTextSample.push({ t: t.slice(0, 30), fs });
                if (smallestFont === null || fs < smallestFont) smallestFont = fs;
            });
            // tap targets inside panel
            const tapIssues = [];
            document.querySelectorAll('[data-toshi-root] button, [data-toshi-root] a').forEach((el) => {
                const rr = el.getBoundingClientRect();
                if (rr.width === 0 || rr.height === 0) return;
                if (rr.height < 32 || rr.width < 32) tapIssues.push({ t: (el.textContent || el.getAttribute('title') || '').trim().slice(0, 24), h: Math.round(rr.height), w: Math.round(rr.width) });
            });
            return {
                panelRect: pr ? { x: Math.round(pr.x), y: Math.round(pr.y), w: Math.round(pr.width), h: Math.round(pr.height) } : null,
                panelW: pr ? Math.round(pr.width) : null,
                composerFound: !!composer,
                composerRect: composer ? { h: Math.round(composer.getBoundingClientRect().height), w: Math.round(composer.getBoundingClientRect().width), y: Math.round(composer.getBoundingClientRect().y) } : null,
                messageCount: msgs.length,
                smallestFont, smallTextSample: smallTextSample.slice(0, 6),
                tapIssues: tapIssues.slice(0, 8),
                overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
            };
        });
        await page.screenshot({ path: `${OUT}/mobile-${vp.n}-drawer-open.png` });

        // 3. REAL interaction: type a message, send, observe a response bubble
        let interaction = { typed: false, sent: false, responseAppeared: null };
        try {
            const input = await page.$('[data-toshi-root] textarea, [data-toshi-root] input[type=text]');
            if (input) {
                await input.fill('hello');
                await page.keyboard.press('Enter');
                await page.waitForTimeout(2500);
                interaction.typed = true;
                interaction.sent = true;
                const reply = await page.evaluate(() => {
                    const area = document.querySelector('[data-toshi-root] .toshi-messages-area, [data-toshi-root] .toshi-messages, [data-toshi-root]');
                    return area ? area.textContent.length : 0;
                });
                interaction.responseAppeared = reply > 50;
                await page.screenshot({ path: `${OUT}/mobile-${vp.n}-after-send.png` });
            }
        } catch (e) { interaction.error = String(e).slice(0, 100); }

        report[vp.n] = { collapsed, open, interaction };
        await ctx.close();
    }
    await browser.close();
    fs.writeFileSync(`${OUT}/report.json`, JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
})();
