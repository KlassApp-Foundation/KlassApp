// Manual onboarding journeys (3 school types) — staging only.
const { test, expect } = require('@playwright/test');
const { buildJourneyData } = require('../lib/journey-data');
const health = require('../lib/health');
const signup = require('../lib/signup');
const wizard = require('../lib/manual-wizard');
const outcomes = require('../lib/outcomes');
const artifacts = require('../lib/artifacts');

const JOURNEYS = [
    ['primary', 'primary-manual'],
    ['olevel', 'olevel-manual'],
    ['oalevel', 'oalevel-manual'],
];

for (const [typeId, tag] of JOURNEYS) {
    test(`@${tag} manual onboarding: signup, wizard, outcomes`, async ({ page, browser }) => {
        test.setTimeout(25 * 60_000);
        const data = buildJourneyData({ typeId, mode: 'manual' });
        const h = health.attach(page);
        const artDir = artifacts.dirFor(tag, data.stamp);
        const findings = [];
        let outcome = null;
        let wizardRecord = null;
        let validation = [];

        // 1) Signup validation battery (every field/message).
        validation = process.env.E2E_SKIP_VALIDATION ? [] : await signup.runValidationBattery(page, data);
        for (const v of validation) {
            if (!v.ok) findings.push(`Signup validation "${v.name}": expected message missing (missing: ${v.missing})`);
        }

        // 2) Valid signup.
        const reg = await signup.signupValid(page, data);
        expect(reg.landedOnDashboard).toBeTruthy();

        // 2b) Duplicate-email check now that the email exists (fresh guest context).
        validation.push(await signup.checkDuplicate(browser, data));

        // 3) Mark as test school (staging write; purge-able).
        const flagged = outcomes.setTestFlag(data.admin.email);
        expect(flagged.school_id).toBeGreaterThan(0);
        console.log(`[${tag}] signup ok; school=${flagged.school_id}; starting wizard`);

        await health.checkNoHorizontalScroll(page, 'dashboard', findings);

        // 4) Manual wizard via the dashboard banner.
        const banner = page.locator('[data-testid="setup-banner-manual"]');
        if (await banner.isVisible({ timeout: 15_000 }).catch(() => false)) {
            await banner.click();
        } else {
            findings.push('setup banner "Set up manually" not visible; navigating directly to wizard');
        }
        await page.waitForURL(/onboarding\/wizard|\/admin\/dashboard/, { timeout: 30_000 }).catch(() => {});
        if (!/onboarding\/wizard/.test(page.url())) {
            await page.goto('/admin/onboarding/wizard', { waitUntil: 'domcontentloaded' });
        }
        wizardRecord = await wizard.runManualWizard(page, data, findings, { shotDir: artDir });
        await health.checkNoHorizontalScroll(page, 'wizard end', findings);

        // 5) Outcomes from the DB.
        outcome = outcomes.fetchOutcome(data.admin.email);
        const verdict = outcomes.evaluate(outcome, data);
        for (const f of verdict.failed) findings.push(`CHECK FAILED: ${f.name} (${f.detail})`);
        findings.push(...verdict.findings);

        // 6) Artifacts + report line.
        const summary = {
            journeyId: tag, typeId, mode: 'manual', stamp: data.stamp,
            schoolName: data.schoolName, adminEmail: data.admin.email,
            schoolId: outcome.school_id, validation, wizard: wizardRecord,
            outcomes: outcome, checks: verdict.checks, findings,
            health: health.summarize(h),
        };
        const dir = artifacts.save({ journeyId: tag, stamp: data.stamp, summary, health: health.summarize(h), outcome: verdict });
        console.log(`\n[${tag}] summary: ${dir}/summary.json — ${verdict.failed.length} failed check(s), ${findings.length} finding(s)`);

        expect(page.url()).toContain('/admin/');
    });
}
