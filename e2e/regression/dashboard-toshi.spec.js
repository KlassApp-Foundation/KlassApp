// Regression specs for merged dashboard and Toshi pill fixes.
const { test, expect } = require('@playwright/test');
const { requireCredentials, login } = require('./helpers');

test.describe('dashboard and Toshi regression', () => {
    test.beforeEach(async () => requireCredentials());

    test('regression_kpi_tiles_at_least_176px_with_toshi_open', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await login(page);
        await page.goto('/admin/dashboard');
        await page.evaluate(() => window.toshiSetCollapsed && window.toshiSetCollapsed(false));
        await page.waitForTimeout(400);
        const widths = await page.$$eval('.ds-kpi-card, [data-testid^="dashboard-v2-kpi-"]', (els) =>
            els.filter((e) => e.getBoundingClientRect().height > 0 && e.dataset.testid !== 'dashboard-v2-kpis')
                .map((e) => e.getBoundingClientRect().width));
        expect(widths.length, 'KPI tiles rendered').toBeGreaterThan(0);
        for (const w of widths) expect(w).toBeGreaterThanOrEqual(176);
    });

    test('regression_toshi_pill_one_fixed_position_and_44px_after_80px_scroll', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 680 });
        await login(page);
        await page.goto('/admin/students');
        await page.evaluate(() => window.toshiSetCollapsed && window.toshiSetCollapsed(true));
        const pill = page.locator('#toshi-entry-pill');
        await expect(pill).toBeVisible();
        expect(await pill.evaluate((el) => getComputedStyle(el).position)).toBe('fixed');

        const rect = () => pill.evaluate((el) => {
            const r = el.getBoundingClientRect();
            return { right: window.innerWidth - r.right, bottom: window.innerHeight - r.bottom, w: r.width, h: r.height };
        });
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.waitForTimeout(200);
        const top = await rect();

        // Make sure the page can scroll past 80px, then hover over rows: the pill must not move.
        await page.evaluate(() => { document.body.style.minHeight = '3000px'; window.scrollTo(0, 200); window.dispatchEvent(new Event('scroll')); });
        await page.waitForTimeout(300);
        const scrolled = await rect();
        await page.mouse.move(400, 300);
        await page.mouse.move(640, 500);
        const afterHover = await rect();

        expect(Math.abs(scrolled.right - top.right)).toBeLessThanOrEqual(1);
        expect(Math.abs(scrolled.bottom - top.bottom)).toBeLessThanOrEqual(1);
        expect(afterHover).toEqual(scrolled);
        expect(Math.round(scrolled.w)).toBe(44);
        expect(Math.round(scrolled.h)).toBe(44);
        await expect(pill).toHaveClass(/is-compact/);
        await expect(pill).toHaveAttribute('aria-label', 'Open Toshi');
        await expect(pill.locator('.toshi-entry-label')).toBeHidden();

        await page.evaluate(() => { window.scrollTo(0, 0); window.dispatchEvent(new Event('scroll')); });
        await page.waitForTimeout(300);
        await expect(pill).not.toHaveClass(/is-compact/);
        expect((await rect()).w).toBeGreaterThan(44);
    });
});
