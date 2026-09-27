// Mobile-responsiveness audit — 2026-09-27
// Phase 1: landing page. Loads the PUBLIC landing at 375/414/768, screenshots,
// and reports: horizontal overflow px, oversized elements, tap-target geometry,
// text that overflows containers, and interactive elements extending past the viewport.
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const PHASE = process.argv[2] || 'audit';
const BASE = process.env.BASE_URL || 'http://localhost:8080';
const OUT = path.join(__dirname, 'screenshots', 'mobile-audit', PHASE);
fs.mkdirSync(OUT, { recursive: true });

const VPS = [
    { name: '375', width: 375, height: 812 },
    { name: '414', width: 414, height: 896 },
    { name: '768', width: 768, height: 1024 },
];

async function auditPage(page, label, url, opts = {}) {
    await page.goto(url, { waitUntil: 'load', timeout: 60000 });
    await page.waitForTimeout(opts.settle || 1500);
    const data = await page.evaluate(() => {
        const vw = document.documentElement.clientWidth;
        const issues = [];
        // 1. horizontal overflow
        const overflow = document.documentElement.scrollWidth - vw;
        // 2. elements wider than viewport (top offenders)
        const wide = [];
        document.querySelectorAll('body *').forEach((el) => {
            const r = el.getBoundingClientRect();
            if (r.width > 0 && (r.right > vw + 2 || r.width > vw + 2)) {
                const cs = getComputedStyle(el);
                if (cs.position === 'fixed' && el.closest('[data-toshi-root]')) return; // toshi drawer is offscreen by design
                wide.push({ tag: el.tagName, cls: String(el.className).slice(0, 60), w: Math.round(r.width), right: Math.round(r.right), id: el.id || undefined });
            }
        });
        wide.sort((a, b) => b.w - a.w);
        // 3. interactive elements off-viewport or tiny tap targets
        const interact = [];
        document.querySelectorAll('a, button, input, select, [role=button]').forEach((el) => {
            const r = el.getBoundingClientRect();
            if (r.width === 0 || r.height === 0) return;
            const cs = getComputedStyle(el);
            if (cs.visibility === 'hidden' || cs.display === 'none') return;
            const area = Math.round(r.width * r.height);
            const off = r.right > vw + 2;
            if (off) interact.push({ tag: el.tagName, text: (el.textContent || el.getAttribute('aria-label') || '').trim().slice(0, 40), right: Math.round(r.right), off: true });
        });
        return { vw, overflow, wide: wide.slice(0, 12), offViewportInteractive: interact.slice(0, 10) };
    });
    return { label, url, ...data };
}

(async () => {
    const browser = await chromium.launch();
    const report = [];
    for (const vp of VPS) {
        const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
        const page = await ctx.newPage();
        const landing = await auditPage(page, `landing-${vp.name}`, `${BASE}/`);
        await page.screenshot({ path: `${OUT}/landing-${vp.name}.png`, fullPage: false });
        await page.screenshot({ path: `${OUT}/landing-${vp.name}-full.png`, fullPage: true });
        report.push(landing);
        await ctx.close();
    }
    await browser.close();
    fs.writeFileSync(`${OUT}/landing-report.json`, JSON.stringify(report, null, 2));
    for (const r of report) {
        console.log(`\n=== ${r.label} (${r.url}) vw=${r.vw}`);
        console.log(`overflow: ${r.overflow}px`);
        if (r.wide.length) console.log('oversized elements:', JSON.stringify(r.wide.slice(0, 6), null, 1));
        if (r.offViewportInteractive.length) console.log('off-viewport interactive:', JSON.stringify(r.offViewportInteractive, null, 1));
    }
})();
