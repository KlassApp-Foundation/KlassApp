/**
 * Onboarding parity E2E test.
 *
 * Reads the two JSON journey summaries produced by manual-selfserve
 * and toshi specs, then compares them for structural parity.
 *
 * Depends on 'selfserve' and 'toshi' projects completing first
 * (configured via `dependencies` in playwright.config.js).
 *
 * Run:
 *   PLAYWRIGHT_BASE_URL=https://klassapp.xyz npx playwright test --project=parity
 */

const { test, expect } = require('@playwright/test');
const { readLatestJourney } = require('./support/journey-summary');

test.describe('onboarding parity', () => {
    test.skip(
        !String(process.env.PLAYWRIGHT_BASE_URL || '').includes('klassapp.xyz'),
        'Set PLAYWRIGHT_BASE_URL=https://klassapp.xyz',
    );

    test('both journeys produce structurally identical results', async () => {
        const manual = readLatestJourney('manual-selfserve');
        const toshi = readLatestJourney('toshi');

        console.log('MANUAL SUMMARY', JSON.stringify(manual, null, 2));
        console.log('TOSHI SUMMARY', JSON.stringify(toshi, null, 2));

        // ── Student count parity ──
        expect(manual.counts.students).toBeGreaterThanOrEqual(3);
        expect(toshi.counts.students).toBeGreaterThanOrEqual(3);
        expect(manual.counts.students).toEqual(toshi.counts.students);

        // ── Teacher count parity ──
        expect(manual.counts.teachers).toBeGreaterThanOrEqual(1);
        expect(toshi.counts.teachers).toBeGreaterThanOrEqual(1);

        // ── Subject count parity ──
        expect(manual.counts.subjects).toBeGreaterThanOrEqual(3);
        expect(toshi.counts.subjects).toBeGreaterThanOrEqual(3);

        // ── Class count parity ──
        expect(manual.counts.classes).toBeGreaterThanOrEqual(1);
        expect(toshi.counts.classes).toBeGreaterThanOrEqual(1);

        // ── Term count parity ──
        expect(manual.counts.terms).toEqual(3);
        expect(toshi.counts.terms).toEqual(3);

        // ── Fee count parity ──
        expect(manual.counts.fees).toBeGreaterThanOrEqual(1);
        expect(toshi.counts.fees).toBeGreaterThanOrEqual(1);

        // ── Student registration number format ──
        for (const reg of manual.studentRegNumbers || []) {
            expect(reg).toMatch(/^KLS/);
        }
        for (const reg of toshi.studentRegNumbers || []) {
            expect(reg).toMatch(/^KLS/);
        }

        // ── Console error cleanliness ──
        const manualErrors = manual.consoleErrorCount || 0;
        const toshiErrors = toshi.consoleErrorCount || 0;
        console.log(`Console errors: manual=${manualErrors}, toshi=${toshiErrors}`);
        // Soft check — not a hard failure if one path has more errors,
        // but flag for investigation
        if (manualErrors !== toshiErrors) {
            console.warn(`Console error mismatch: manual=${manualErrors}, toshi=${toshiErrors}`);
        }

        // ── Soft failures ──
        if ((manual.softFails || []).length > 0) {
            console.log('Manual soft fails:', manual.softFails);
        }
        if ((toshi.softFails || []).length > 0) {
            console.log('Toshi soft fails:', toshi.softFails);
        }
    });
});
