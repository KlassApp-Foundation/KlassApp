// Toshi entry pill and phone drawer: real wheel and keyboard input only.
// Credentials come only from env (E2E_ADMIN_EMAIL / E2E_ADMIN_PASSWORD, a
// school admin of a seeded demo school). Run locally:
//   npx playwright test e2e/regression/toshi-pill-drawer.spec.js --project=chromium --project=webkit
const { test, expect } = require('@playwright/test');

const EMAIL = process.env.E2E_ADMIN_EMAIL || '';
const PASSWORD = process.env.E2E_ADMIN_PASSWORD || '';
const LIST_PAGE = process.env.E2E_LIST_PATH || '/admin/students';

test.skip(!EMAIL || !PASSWORD, 'Set E2E_ADMIN_EMAIL and E2E_ADMIN_PASSWORD (school admin of a demo school).');

async function login(page) {
    await page.goto('/login');
    await page.fill('input[name="email"], input[type="email"]', EMAIL);
    await page.fill('input[name="password"], input[type="password"]', PASSWORD);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.startsWith('/login'), { timeout: 60_000 }),
        page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
    ]);
}

async function pillBox(page) {
    return page.evaluate(() => {
        const pill = document.getElementById('toshi-entry-pill');
        if (!pill) return { present: false, width: 0, height: 0 };
        const r = pill.getBoundingClientRect();
        const s = getComputedStyle(pill);
        return {
            present: !pill.hidden && s.display !== 'none' && r.width > 0,
            width: Math.round(r.width),
            height: Math.round(r.height),
            radius: s.borderTopLeftRadius,
        };
    });
}

// The element that really scrolls: an inner overflow container or the document.
async function scrollOffset(page) {
    return page.evaluate(() => {
        let best = document.scrollingElement ? document.scrollingElement.scrollTop : 0;
        document.querySelectorAll('main, main *').forEach((el) => {
            if (el.scrollTop > best) best = el.scrollTop;
        });
        return best;
    });
}

async function wheelOverContent(page, dy, steps) {
    const vp = page.viewportSize();
    await page.mouse.move(Math.round(vp.width * 0.45), Math.round(vp.height * 0.6));
    for (let i = 0; i < steps; i++) {
        await page.mouse.wheel(0, dy);
        await page.waitForTimeout(60);
    }
}

async function focusKey(page) {
    return page.evaluate(() => {
        const el = document.activeElement;
        const drawer = document.getElementById('res_sidebar');
        window.__kaSeq = window.__kaSeq || 0;
        if (!el.__kaId) el.__kaId = 'f' + (++window.__kaSeq);
        return {
            id: el.__kaId,
            label: (el.getAttribute('aria-label') || el.textContent || el.tagName).trim().slice(0, 40),
            inside: !!drawer && drawer.contains(el),
            child: el.classList.contains('sidebar-v3-child'),
        };
    });
}

async function openDrawer(page) {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/admin/dashboard');
    await page.locator('#mobile-menu-trigger').click();
    await expect(page.locator('#res_sidebar')).not.toHaveClass(/(^|\s)hidden(\s|$)/);
    // Expand the first group so its sub-items are part of the cycle.
    const chevron = page.locator('#res_sidebar .sidebar-v3-chevron').first();
    await chevron.click();
    await expect(chevron).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#res_sidebar .sidebar-v3-children:visible .sidebar-v3-child').first()).toBeVisible();
}

test.describe('Toshi entry pill', () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await login(page);
    });

    test('toshi_pill_collapses_to_44px_circle_after_80px_wheel_scroll_and_expands_at_top', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await expect.poll(async () => (await pillBox(page)).present).toBe(true);
        const before = await pillBox(page);
        expect(before.height).toBe(44);
        expect(before.width).toBeGreaterThan(44);

        await wheelOverContent(page, 60, 4); // 240px of real wheel input
        await expect.poll(() => scrollOffset(page)).toBeGreaterThan(80);
        await expect.poll(async () => (await pillBox(page)).width).toBe(44);
        const compact = await pillBox(page);
        expect(compact.height).toBe(44);
        expect(compact.radius).toMatch(/^(999px|50%|22px)$/);

        await wheelOverContent(page, -400, 4);
        await expect.poll(() => scrollOffset(page)).toBe(0);
        await expect.poll(async () => (await pillBox(page)).width).toBeGreaterThan(44);
    });

    test('toshi_pill_is_on_the_dashboard_and_absent_on_a_list_page', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await expect.poll(async () => (await pillBox(page)).present).toBe(true);

        await page.goto(LIST_PAGE);
        await expect(page.locator('#toshi-toggle-wrapper')).toHaveCount(1);
        await page.waitForLoadState('load');
        expect((await pillBox(page)).present).toBe(false);
        const handle = await page.locator('#toshi-toggle').evaluate((el) => {
            const r = el.getBoundingClientRect();
            return { width: Math.round(r.width), height: Math.round(r.height), tab: el.tabIndex };
        });
        expect(handle.width).toBeGreaterThan(0);
        expect(handle.tab).toBe(0);
    });
});

test.describe('Phone drawer focus trap', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('phone_drawer_tab_and_shift_tab_cycle_every_control_inside_the_drawer', async ({ page }) => {
        await openDrawer(page);

        const forward = [await focusKey(page)];
        for (let i = 0; i < 60; i++) {
            await page.keyboard.press('Tab');
            forward.push(await focusKey(page));
        }
        const outside = forward.filter((f) => !f.inside);
        expect(outside, 'Tab left the drawer').toEqual([]);
        const distinct = new Set(forward.map((f) => f.id));
        expect(distinct.size, 'distinct controls on Tab: ' + forward.map((f) => f.label).join(' > ')).toBeGreaterThanOrEqual(10);
        expect(forward.some((f) => f.child), 'Tab never reached an expanded sub-item').toBe(true);

        const nextOf = new Map();
        for (let i = 0; i + 1 < forward.length; i++) nextOf.set(forward[i].id, forward[i + 1].id);

        const backward = [await focusKey(page)];
        for (let i = 0; i < 60; i++) {
            await page.keyboard.press('Shift+Tab');
            backward.push(await focusKey(page));
        }
        expect(backward.filter((f) => !f.inside), 'Shift+Tab left the drawer').toEqual([]);
        const back = new Set(backward.map((f) => f.id));
        expect(back.size, 'distinct controls on Shift+Tab: ' + backward.map((f) => f.label).join(' < ')).toBeGreaterThanOrEqual(10);
        for (let i = 0; i + 1 < backward.length; i++) {
            expect(nextOf.get(backward[i + 1].id), 'Shift+Tab is not the reverse of Tab at step ' + i).toBe(backward[i].id);
        }
    });

    test('phone_drawer_escape_closes_and_returns_focus_to_the_opener', async ({ page }) => {
        await openDrawer(page);
        for (let i = 0; i < 3; i++) await page.keyboard.press('Tab');
        await page.keyboard.press('Escape');
        await expect(page.locator('#res_sidebar')).toHaveClass(/(^|\s)hidden(\s|$)/);
        await expect.poll(() => page.evaluate(() => document.activeElement && document.activeElement.id)).toBe('mobile-menu-trigger');
    });
});
