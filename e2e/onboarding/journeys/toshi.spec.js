// Toshi-assisted onboarding journeys (3 school types) — staging only.
const { test, expect } = require('@playwright/test');
const { buildJourneyData } = require('../lib/journey-data');
const health = require('../lib/health');
const signup = require('../lib/signup');
const toshi = require('../lib/toshi');
const outcomes = require('../lib/outcomes');
const artifacts = require('../lib/artifacts');

const JOURNEYS = [
    ['primary', 'primary-toshi'],
    ['olevel', 'olevel-toshi'],
    ['oalevel', 'oalevel-toshi'],
];

for (const [typeId, tag] of JOURNEYS) {
    test(`@${tag} Toshi onboarding: signup, scripted chat, outcomes`, async ({ page, browser }) => {
        test.setTimeout(25 * 60_000);
        const data = buildJourneyData({ typeId, mode: 'toshi' });
        const h = health.attach(page);
        const artDir = artifacts.dirFor(tag, data.stamp);
        const findings = [];
        let run = null;
        let outcome = null;

        const validation = process.env.E2E_SKIP_VALIDATION ? [] : await signup.runValidationBattery(page, data);
        for (const v of validation) {
            if (!v.ok) findings.push(`Signup validation "${v.name}": expected message missing (missing: ${v.missing})`);
        }
        const reg = await signup.signupValid(page, data);
        expect(reg.landedOnDashboard).toBeTruthy();

        // Duplicate-email check now that the email exists (fresh guest context).
        validation.push(await signup.checkDuplicate(browser, data));

        const flagged = outcomes.setTestFlag(data.admin.email);
        expect(flagged.school_id).toBeGreaterThan(0);
        console.log(`[${tag}] signup ok; school=${flagged.school_id}; starting Toshi`);

        // Fresh schools land in §33 preview mode (Coming soon, guide off). The
        // scripted journeys run on the per-school onboarding mode — flip the
        // test school (is_test-only) and reload so AgentToshi mounts the guide.
        const modeFlipped = outcomes.enableOnboardingMode(data.admin.email, data.schoolName);
        expect(modeFlipped.school_id).toBe(flagged.school_id);
        await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });

        await toshi.ensurePanel(page);
        await health.checkNoHorizontalScroll(page, 'dashboard (toshi)', findings);

        run = await toshi.runToshiJourney(page, data, findings, { shotDir: artDir });
        await health.checkNoHorizontalScroll(page, 'toshi end', findings);
        await page.screenshot({ path: `${artDir}/final-panel.png`, fullPage: true }).catch(() => {});
        // Final state after a fresh load (setup banner / dashboard reflect the saved school).
        await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(3000);
        await page.screenshot({ path: `${artDir}/final-dashboard.png`, fullPage: true }).catch(() => {});

        outcome = outcomes.fetchOutcome(data.admin.email);
        const verdict = outcomes.evaluate(outcome, data);
        for (const f of verdict.failed) findings.push(`CHECK FAILED: ${f.name} (${f.detail})`);
        findings.push(...verdict.findings);

        const summary = {
            journeyId: tag, typeId, mode: 'toshi', stamp: data.stamp,
            schoolName: data.schoolName, adminEmail: data.admin.email,
            schoolId: outcome.school_id, validation,
            toshi: { turns: run.turns, done: run.done, codeShown: run.codeShown },
            outcomes: outcome, checks: verdict.checks, findings,
            health: health.summarize(h),
        };
        const dir = artifacts.save({ journeyId: tag, stamp: data.stamp, summary, conversation: run.conversation, health: health.summarize(h), outcome: verdict });
        console.log(`\n[${tag}] summary: ${dir}/summary.json — turns=${run.turns}, failed checks=${verdict.failed.length}, findings=${findings.length}`);

        expect(page.url()).toContain('/admin/');
        expect(run.done, `Toshi journey must reach a completion signal (turns=${run.turns})`).toBeTruthy();
        expect(
            verdict.failed,
            `Hard outcome checks failed: ${verdict.failed.map((f) => f.name + ' — ' + f.detail).join('; ')}`,
        ).toEqual([]);
    });
}
