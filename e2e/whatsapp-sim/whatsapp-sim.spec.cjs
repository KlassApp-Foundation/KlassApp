// @ts-check
/**
 * WhatsApp demo scenarios through the local simulator (local e2e mode).
 *
 * No browser is used. The spec plays signed inbound webhooks into a LOCAL app
 * and asserts on the outbound calls the mock Cloud API recorded.
 *
 * Opt-in, local only:
 *   1. Serve the app locally (APP_ENV=local) with the simulator keys in .env
 *      (node e2e/whatsapp-sim/setup-local-env.cjs), e.g. php artisan serve --port=8010
 *   2. WHATSAPP_SIM_E2E=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:8010 \
 *        npx playwright test e2e/whatsapp-sim --project=chromium
 *
 * The spec starts the simulator itself on 127.0.0.1:9123 when it is not
 * already running. See docs/ops/LOCAL-WHATSAPP-TESTING.md.
 */
const path = require('node:path');
const { test, expect } = require('@playwright/test');
const { SCENARIOS, runScenarios } = require('./scenarios.cjs');

const enabled = process.env.WHATSAPP_SIM_E2E === '1';
const appUrl = process.env.WHATSAPP_SIM_APP_URL || process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8010';
const PENDING_NOTE = 'Pending until the Task 9 Tier 1 PR: BIND <code> and MARKS are not on main yet. The scenario runner reports it as "not implemented yet".';

test.describe('WhatsApp simulator scenarios (local)', () => {
    test.describe.configure({ mode: 'serial' });
    test.skip(!enabled, 'Local only: set WHATSAPP_SIM_E2E=1 with the app served locally (docs/ops/LOCAL-WHATSAPP-TESTING.md).');

    /** @type {Record<string, any>} */
    const byName = {};

    test.beforeAll(async () => {
        test.setTimeout(240_000);
        const live = SCENARIOS.filter((s) => !s.pendingUntil).map((s) => s.name);
        const results = await runScenarios({
            appUrl,
            appDir: path.resolve(__dirname, '..', '..'),
            only: live.join(','),
        });
        for (const r of results) byName[r.name] = r;
    });

    for (const sc of SCENARIOS) {
        if (sc.pendingUntil) {
            test(`${sc.name}: ${sc.title}`, { annotation: { type: 'pending', description: PENDING_NOTE } }, async () => {
                test.skip(true, PENDING_NOTE);
            });
            continue;
        }

        test(`${sc.name}: ${sc.title}`, async () => {
            const r = byName[sc.name];
            expect(r, `scenario ${sc.name} ran`).toBeTruthy();
            const failed = r.checks.filter((c) => !c.ok).map((c) => c.label);
            expect(failed, `failed checks\n\n${r.transcript}`).toEqual([]);
            expect(r.status).toBe('pass');
        });
    }
});
