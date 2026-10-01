// Drives the manual onboarding wizard end to end and records what happened.
// Instrumented: logs each step, screenshots each step, captures the in-page
// wizard error banner, and fails fast (bounded timeouts) instead of hanging.
const { expect } = require('@playwright/test');
const path = require('node:path');
const fs = require('node:fs');

const NEXT = '[data-testid="wizard-next"]';
const PREV = '[data-testid="wizard-prev"]';
const TITLE = '[data-testid="wizard-step-title"]';
const ERROR = '[data-testid="wizard-error"]';

const T = 20_000; // per-action timeout

async function title(page) {
    return (await page.locator(TITLE).innerText({ timeout: T }).catch(() => '')).trim();
}

/** Read the step title after it settles (Livewire transitions can lag). */
async function titleSettled(page) {
    let last = await title(page);
    for (let k = 0; k < 8; k++) {
        await page.waitForTimeout(500);
        const now = await title(page);
        if (now === last && now !== '') return now;
        last = now;
    }
    return last;
}

async function waitIdle(page, ms = 1000) {
    await page.waitForTimeout(ms);
}

async function shot(page, shotDir, name) {
    if (!shotDir) return;
    try {
        fs.mkdirSync(shotDir, { recursive: true });
        await page.screenshot({ path: path.join(shotDir, name) });
    } catch { /* screenshots are best-effort */ }
}

async function readWizardError(page) {
    const el = page.locator(ERROR);
    if (await el.isVisible().catch(() => false)) {
        return (await el.innerText().catch(() => '')).trim().slice(0, 200);
    }
    return '';
}

/**
 * Click Continue and wait until the step actually advances (or an error banner
 * appears). A short sleep alone races Livewire morphs and can skip Structure
 * when the stale title still says "Academic year".
 */
async function clickNext(page, shotDir, tag) {
    const before = (await title(page)).toLowerCase();
    await page.locator(NEXT).click({ timeout: T });

    const deadline = Date.now() + 30_000;
    let err = '';
    while (Date.now() < deadline) {
        await waitIdle(page, 400);
        err = await readWizardError(page);
        if (err) {
            console.log(`[wizard] ${tag}: ERROR after next: ${err}`);
            return err;
        }
        // Review / completion surfaces mean we left the step loop path.
        if (await page.locator('[data-testid="wizard-review"]').isVisible().catch(() => false)) {
            return '';
        }
        if (await page.locator('[data-testid="wizard-completion-suggestions"]').isVisible().catch(() => false)) {
            return '';
        }
        const after = (await title(page)).toLowerCase();
        if (after && after !== before) {
            return '';
        }
    }

    console.log(`[wizard] ${tag}: title still "${before}" after next (no advance within 30s)`);
    return await readWizardError(page);
}

async function selectByLabelOrValue(page, selector, label) {
    const norm = (s) => String(s || '').trim().toLowerCase();
    const wanted = norm(label);
    try {
        const options = await page.locator(selector).evaluate((el) =>
            [...el.options].map((o) => ({ value: o.value, text: o.text })));
        const byExact = options.find((o) => norm(o.text) === wanted);
        const byContains = options.find((o) => norm(o.text).includes(wanted));
        const pick = byExact || byContains;
        if (!pick) {
            console.log(`[wizard] select ${selector}: no option matches "${label}". Options: ${options.map((o) => JSON.stringify(o.text)).join(', ')}`);
            return false;
        }
        await page.locator(selector).selectOption(pick.value, { timeout: T });
        // Ensure Livewire sees the change even if native change event timing is odd.
        await page.locator(selector).evaluate((el) => {
            el.dispatchEvent(new Event('change', { bubbles: true }));
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }).catch(() => {});
        return true;
    } catch (e) {
        console.log(`[wizard] select ${selector} failed: ${String(e.message || e).slice(0, 120)}`);
        return false;
    }
}

async function runManualWizard(page, data, findings = [], opts = {}) {
    const shotDir = opts.shotDir || null;
    const record = { steps: [], prevExercised: false, streamAdded: null, waCodeShown: false, finished: false, wizardErrors: [] };

    await page.waitForSelector('[data-testid="manual-wizard-shell"]', { timeout: 60_000 });

    let lastTitle = '';
    let sameCount = 0;

    for (let i = 0; i < 45; i++) {
        const t = (await titleSettled(page)).toLowerCase();
        if (!t) { findings.push('wizard: step title disappeared'); break; }
        console.log(`[wizard] step ${i}: ${t}`);
        await shot(page, shotDir, `step-${String(i).padStart(2, '0')}-${t.replace(/[^a-z0-9]+/g, '-').slice(0, 40)}.png`);

        if (t === lastTitle) {
            sameCount++;
            if (sameCount >= 3) {
                findings.push(`wizard stuck on "${t}" for 3 consecutive iterations`);
                break;
            }
        } else { sameCount = 0; lastTitle = t; }

        record.steps.push(t);
        let err = '';

        // Synthetic review step (testid wizard-review) → Confirm & finish.
        if (await page.locator('[data-testid="wizard-review"]').isVisible().catch(() => false)) {
            await shot(page, shotDir, 'step-review.png');
            await page.locator(NEXT).click({ timeout: T });
            await page.waitForSelector('[data-testid="wizard-completion-suggestions"]', { timeout: 60_000 }).catch(() => {});
            record.finished = true;
            console.log('[wizard] review confirmed — finished');
            break;
        }

        try {
            if (t.includes('school name')) {
                await page.locator('#wizard-school-name').fill(data.schoolName, { timeout: T });
                await waitIdle(page, 400);
                err = await clickNext(page, shotDir, 'school-name');
                if (err) { record.wizardErrors.push(err); }
                if (!record.prevExercised) {
                    await page.locator(PREV).click({ timeout: T });
                    await waitIdle(page, 900);
                    const backTitle = (await title(page)).toLowerCase();
                    record.prevExercised = true;
                    if (!backTitle.includes('school name')) findings.push(`wizard prev returned to unexpected step: ${backTitle}`);
                    err = await clickNext(page, shotDir, 'school-name-again');
                    if (err) record.wizardErrors.push(err);
                }
            } else if (t.includes('school size') || t.includes('approximate size')) {
                await page.locator('[data-testid^="wizard-student-size-"]').first().click({ timeout: T });
                await waitIdle(page, 500);
                err = await clickNext(page, shotDir, 'size');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('country')) {
                const ok = await selectByLabelOrValue(page, '#wizard-country', 'Uganda');
                if (!ok) findings.push('country select: Uganda option not found');
                await waitIdle(page, 400);
                err = await clickNext(page, shotDir, 'country');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('curriculum') || t.includes('board')) {
                const ok = await selectByLabelOrValue(page, '#wizard-curriculum', 'UNEB');
                if (!ok) findings.push('curriculum select: UNEB option not found');
                await waitIdle(page, 400);
                err = await clickNext(page, shotDir, 'curriculum');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('category')) {
                await page.locator(`[data-testid="wizard-category-${data.type.category}"]`).click({ timeout: T });
                await waitIdle(page, 700);
                err = await clickNext(page, shotDir, 'category');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('emis') || t.includes('ministry')) {
                await page.locator('#wizard-emis').fill(data.emisCode, { timeout: T });
                await waitIdle(page, 400);
                err = await clickNext(page, shotDir, 'emis');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('uneb')) {
                err = await clickNext(page, shotDir, 'uneb-skip');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('academic year')) {
                err = await clickNext(page, shotDir, 'academic-year');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('structure')) {
                await page.waitForSelector('[data-testid="wizard-structure-step"]', { timeout: 30_000 });
                const card = page.locator('[data-testid^="wizard-structure-class-"]').filter({ hasText: data.type.streamClassExample }).first();
                if (await card.count()) {
                    const input = card.locator('[data-testid^="wizard-structure-stream-input-"]').first();
                    const addBtn = card.locator('[data-testid^="wizard-structure-add-stream-"]').first();
                    await input.fill(data.type.streamName, { timeout: T });
                    await waitIdle(page, 400);
                    await addBtn.click({ timeout: T });
                    await waitIdle(page, 900);
                    // Confirm the chip/label appeared before counting the stream as added.
                    const chip = card.getByText(data.type.streamName, { exact: false });
                    if (await chip.first().isVisible().catch(() => false)) {
                        record.streamAdded = data.type.streamName;
                    } else {
                        findings.push(`structure step: added "${data.type.streamName}" but label not visible on ${data.type.streamClassExample}`);
                    }
                } else {
                    findings.push(`structure step: class card for ${data.type.streamClassExample} not found`);
                }
                err = await clickNext(page, shotDir, 'structure');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('subject')) {
                const seeded = page.locator('[data-testid="wizard-subjects-seeded-list"]');
                if (await seeded.isVisible().catch(() => false)) {
                    record.subjectsSeeded = (await seeded.innerText()).split('\n').map((s) => s.trim()).filter(Boolean);
                }
                err = await clickNext(page, shotDir, 'subjects');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('teacher')) {
                await page.locator('[data-testid="wizard-teacher-paste"]').fill(data.teachers.join('\n'), { timeout: T });
                await page.locator('[data-testid="wizard-teacher-paste-btn"]').click({ timeout: T });
                await waitIdle(page, 700);
                record.teachersAdded = data.teachers;
                err = await clickNext(page, shotDir, 'teachers');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('student')) {
                // Paste uses the single-form class (defaults to 'P1' otherwise).
                const classSel = page.locator('[data-testid="wizard-student-class"]');
                if (await classSel.isVisible().catch(() => false)) {
                    await selectByLabelOrValue(page, '[data-testid="wizard-student-class"]', data.type.studentClass);
                    await waitIdle(page, 500);
                }
                let li = page.locator('[data-testid="wizard-student-list"] li');
                if ((await li.count().catch(() => 0)) === 0) {
                    await page.locator('[data-testid="wizard-student-paste"]').fill(data.students.join('\n'), { timeout: T });
                    await page.locator('[data-testid="wizard-student-paste-btn"]').click({ timeout: T });
                    await waitIdle(page, 700);
                    record.studentsAdded = data.students;
                }
                err = await clickNext(page, shotDir, 'students');
                if (err) {
                    record.wizardErrors.push(err);
                    if (/could not place student/i.test(err)) {
                        // Clear bad drafts and retry once with the class fix above.
                        const removes = page.locator('[data-testid="wizard-student-list"] button[aria-label="Remove"]');
                        for (let r = (await removes.count()) - 1; r >= 0; r--) {
                            await removes.nth(r).click({ timeout: T }).catch(() => {});
                        }
                        await waitIdle(page, 500);
                        findings.push(`students step required class fix: ${err.slice(0, 120)}`);
                    }
                }
            } else if (t.includes('term')) {
                record.termsSeen = await page.locator('[data-testid="wizard-term-list"] li').allInnerTexts().catch(() => []);
                const mark = page.locator('[data-testid^="wizard-term-mark-current-"]').first();
                if (await mark.isVisible().catch(() => false)) await mark.click({ timeout: T });
                await waitIdle(page, 500);
                err = await clickNext(page, shotDir, 'terms');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('fee')) {
                await page.locator('[data-testid="wizard-fee-name"]').fill(data.fee.name, { timeout: T });
                await page.locator('[data-testid="wizard-fee-amount"]').fill(data.fee.amount, { timeout: T });
                await page.locator('[data-testid="wizard-fee-add"]').click({ timeout: T });
                await waitIdle(page, 700);
                err = await clickNext(page, shotDir, 'fees');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('whatsapp')) {
                await page.locator('[data-testid="wizard-wa-phone"]').fill(data.admin.phoneE164, { timeout: T });
                await page.locator('[data-testid="wizard-wa-send-otp"]').click({ timeout: T });
                await page.waitForSelector('[data-testid="wizard-wa-otp-status"]', { timeout: 30_000 });
                const status = await page.locator('[data-testid="wizard-wa-otp-status"]').innerText();
                const m = status.match(/(\d{6})/);
                if (!m) findings.push(`wizard WhatsApp OTP: no 6-digit code shown (status: ${status.slice(0, 120)})`);
                else {
                    record.waCodeShown = true;
                    await page.locator('[data-testid="wizard-wa-otp-input"]').fill(m[1], { timeout: T });
                    await page.locator('[data-testid="wizard-wa-verify-otp"]').click({ timeout: T });
                    await page.waitForSelector('[data-testid="wizard-wa-verified"]', { timeout: 30_000 }).catch(() => {
                        findings.push('wizard WhatsApp verify did not reach verified state');
                    });
                }
                err = await clickNext(page, shotDir, 'whatsapp');
                if (err) record.wizardErrors.push(err);
            } else if (t.includes('plan')) {
                const cards = page.locator('button[data-testid^="wizard-plan-"]');
                const count = await cards.count();
                let clicked = false;
                for (let c = 0; c < count; c++) {
                    const name = (await cards.nth(c).getAttribute('data-plan-name')) || '';
                    if (/freemium/i.test(name)) { await cards.nth(c).click({ timeout: T }); clicked = true; break; }
                }
                if (!clicked && count) await cards.first().click({ timeout: T });
                await waitIdle(page, 800);
                const selectedCount = await cards.evaluateAll((els) => els.filter((el) => el.classList.contains('is-selected')).length).catch(() => 0);
                record.planClicked = selectedCount > 0;
                if (!selectedCount) findings.push('plan step: no plan card registered as selected after click');
                err = await clickNext(page, shotDir, 'plan');
                if (err) { record.wizardErrors.push(err); findings.push(`plan step error: ${err}`); }
            } else {
                findings.push(`wizard: unknown step "${t}" — clicking Next`);
                err = await clickNext(page, shotDir, 'unknown');
                if (err) record.wizardErrors.push(err);
            }
        } catch (e) {
            const after = (await titleSettled(page)).toLowerCase();
            if (after && after !== t) {
                // The step moved on while we were acting — not an error.
                console.log(`[wizard] step moved on (${t} -> ${after}); continuing`);
                continue;
            }
            const werr = await readWizardError(page);
            findings.push(`wizard step "${t}" error: ${String(e.message || e).slice(0, 180)}${werr ? ' | wizard-error: ' + werr : ''}`);
            await shot(page, shotDir, `step-${i}-ERROR.png`);
            break;
        }
    }

    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' }).catch(() => {});
    await page.waitForTimeout(800);
    return record;
}

module.exports = { runManualWizard };
