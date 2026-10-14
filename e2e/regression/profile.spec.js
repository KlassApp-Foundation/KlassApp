// Regression specs for initials avatars and the parent profile layout.
const { test, expect } = require('@playwright/test');
const { requireCredentials, login } = require('./helpers');

test.describe('profile regression', () => {
    test.beforeEach(async () => requireCredentials());

    test('regression_initials_avatar_in_account_card', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await login(page);
        await page.goto('/admin/dashboard');
        const av = await page.locator('#admin-sidebar .account-card__avatar').first().evaluate((el) => {
            const r = el.getBoundingClientRect();
            return { img: el.tagName === 'IMG', text: el.textContent.trim(), w: r.width, h: r.height, radius: getComputedStyle(el).borderRadius };
        });
        if (!av.img) {
            expect(av.text).toMatch(/^\p{Lu}{1,2}$/u);
            expect(av.radius).toBe('50%');
        }
        expect(Math.abs(av.w - av.h)).toBeLessThanOrEqual(1);
    });

    test('regression_parent_profile_layout', async ({ page }) => {
        const path = process.env.E2E_PARENT_PROFILE_PATH || '';
        test.skip(!path, 'Set E2E_PARENT_PROFILE_PATH to a demo parent profile, e.g. /admin/parent/show/<id>.');
        for (const width of [375, 1280]) {
            await page.setViewportSize({ width, height: 800 });
            if (width === 375) await login(page);
            await page.goto(path);
            await expect(page.getByTestId('parent-profile')).toBeVisible();
            const m = await page.evaluate(() => {
                const q = (s) => document.querySelector(s)?.getBoundingClientRect();
                const header = q('[data-testid="person-profile-header"]');
                const initials = q('[data-testid="person-initials"]');
                const tiles = q('[data-testid="person-profile-tiles"]');
                const tabs = q('[data-testid="person-profile-tabs"]');
                return {
                    sw: document.documentElement.scrollWidth, iw: window.innerWidth,
                    header, initials, tiles, tabs,
                    kpis: ['kpi-children', 'kpi-fees', 'kpi-whatsapp'].map((id) => !!document.querySelector(`[data-testid="${id}"]`)),
                };
            });
            expect(m.sw, `no sideways scroll at ${width}`).toBeLessThanOrEqual(m.iw + 1);
            expect(m.header && m.initials && m.tiles && m.tabs).toBeTruthy();
            expect(m.initials.left).toBeGreaterThanOrEqual(m.header.left);
            expect(m.initials.top).toBeGreaterThanOrEqual(m.header.top);
            expect(m.tiles.top).toBeGreaterThanOrEqual(m.header.bottom - 1);
            expect(m.tabs.top).toBeGreaterThanOrEqual(m.header.bottom - 1);
            expect(m.kpis).toEqual([true, true, true]);
        }
    });
});
