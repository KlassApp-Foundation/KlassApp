// Regression specs for merged shell fixes (sidebar, account menu, drawer).
const { test, expect } = require('@playwright/test');
const { requireCredentials, login } = require('./helpers');

test.describe('shell regression', () => {
    test.beforeEach(async () => requireCredentials());

    test('regression_single_sidebar_highlight', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await login(page);
        for (const path of ['/admin/dashboard', '/admin/students']) {
            await page.goto(path);
            const highlighted = await page.evaluate(() => {
                const side = document.querySelector('#admin-sidebar');
                if (!side) return -1;
                const links = [...side.querySelectorAll('a')].filter((a) => a.getBoundingClientRect().height > 0);
                const base = getComputedStyle(side).backgroundColor;
                // A row counts as highlighted when it is aria-current or paints a background other than the rail's.
                return links.filter((a) => {
                    const bg = getComputedStyle(a).backgroundColor;
                    const painted = bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent' && bg !== base;
                    return a.getAttribute('aria-current') === 'page' || (painted && !a.matches(':hover'));
                }).length;
            });
            expect(highlighted, `highlighted rows on ${path}`).toBe(1);
        }
    });

    test('regression_account_menu_inside_sidebar', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await login(page);
        await page.goto('/admin/dashboard');
        const box = await page.evaluate(() => {
            const side = document.querySelector('#admin-sidebar');
            const card = side && side.querySelector('.account-card__trigger');
            if (!side || !card) return null;
            const s = side.getBoundingClientRect();
            const c = card.getBoundingClientRect();
            return { s: { l: s.left, r: s.right, t: s.top, b: s.bottom }, c: { l: c.left, r: c.right, t: c.top, b: c.bottom } };
        });
        expect(box, 'account card inside #admin-sidebar').not.toBeNull();
        expect(box.c.l).toBeGreaterThanOrEqual(box.s.l - 0.5);
        expect(box.c.r).toBeLessThanOrEqual(box.s.r + 0.5);
        expect(box.c.b).toBeLessThanOrEqual(box.s.b + 0.5);
        expect(box.c.t).toBeGreaterThanOrEqual(box.s.t - 0.5);
    });

    test('regression_drawer_close_and_focus_trap', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await login(page);
        await page.goto('/admin/dashboard');
        const trigger = page.locator('#mobile-menu-trigger');
        await trigger.click();
        const drawer = page.locator('#res_sidebar');
        await expect(drawer).toBeVisible();
        await expect(drawer).toHaveAttribute('role', 'dialog');
        await expect(drawer).toHaveAttribute('aria-modal', 'true');

        // Focus stays inside the drawer for more Tab presses than it has items.
        const count = await drawer.locator('a, button, select, input').count();
        for (let i = 0; i < count + 3; i++) {
            await page.keyboard.press('Tab');
            const inside = await page.evaluate(() => document.querySelector('#res_sidebar').contains(document.activeElement));
            expect(inside, `Tab ${i + 1} kept focus in the drawer`).toBe(true);
        }
        await page.locator('#drawer-close').focus();
        await page.keyboard.press('Shift+Tab');
        expect(await page.evaluate(() => document.querySelector('#res_sidebar').contains(document.activeElement))).toBe(true);

        // Close button: real pointer click at its centre hits the button itself.
        const r = await page.locator('#drawer-close').boundingBox();
        expect(r.width).toBeGreaterThanOrEqual(24);
        const hit = await page.evaluate(([x, y]) => document.elementFromPoint(x, y)?.closest('#drawer-close') !== null, [r.x + r.width / 2, r.y + r.height / 2]);
        expect(hit, 'nothing covers the Close button').toBe(true);
        await page.mouse.click(r.x + r.width / 2, r.y + r.height / 2);
        await expect(drawer).toBeHidden();

        // Escape closes too and returns focus to the trigger.
        await trigger.click();
        await expect(drawer).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(drawer).toBeHidden();
        expect(await page.evaluate(() => document.activeElement && document.activeElement.id)).toBe('mobile-menu-trigger');
    });
});
