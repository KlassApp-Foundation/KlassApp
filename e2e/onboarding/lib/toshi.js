// Toshi-assisted onboarding driver: scripted answers, outcome-based waits,
// full conversation recording, turn counting. Never asserts on exact wording
// beyond small structural anchors.
const path = require('node:path');
const fs = require('node:fs');

// Panel composer when docked; modal composer when maximized (#917 mounts only one).
const COMPOSER = '#toshi-input-panel:visible, #toshi-input-modal:visible';
const SEND = '[data-testid="toshi-send"]:visible, [data-testid="toshi-send-modal"]:visible, button[title="Send"]:visible';

const T = 20_000;

async function ensurePanel(page) {
    console.log('[toshi] ensurePanel: waiting for root (attached)');
    await page.waitForSelector('[data-toshi-root]', { state: 'attached', timeout: 60_000 });

    // Expand the split dock (do not maximize — modal swaps the composer id).
    if (!(await page.locator(COMPOSER).first().isVisible().catch(() => false))) {
        console.log('[toshi] ensurePanel: expanding dock');
        await page.evaluate(() => {
            if (typeof window.toshiSetCollapsed === 'function') {
                window.toshiSetCollapsed(false);
            } else {
                document.body.classList.remove('toshi-collapsed');
                document.documentElement.classList.remove('toshi-collapsed');
                try { localStorage.setItem('toshi_split_collapsed', '0'); } catch (e) {}
                window.dispatchEvent(new CustomEvent('toshi-collapsed-changed', { detail: { collapsed: false } }));
            }
        }).catch(() => {});
        await page.waitForTimeout(800);
        const pill = page.locator('[data-testid="toshi-pill"]');
        if (await pill.isVisible().catch(() => false)) {
            await pill.click({ timeout: 8000 }).catch(() => {});
            await page.waitForTimeout(800);
        }
    }

    if (!(await page.locator(COMPOSER).first().isVisible().catch(() => false))) {
        console.log('[toshi] ensurePanel: trying setup banner');
        const banner = page.locator('[data-testid="setup-banner-toshi"]');
        if (await banner.isVisible().catch(() => false)) {
            await banner.click({ timeout: 8000 }).catch(() => {});
            await page.waitForTimeout(1000);
        }
    }

    if (!(await page.locator(COMPOSER).first().isVisible().catch(() => false))) {
        console.log('[toshi] ensurePanel: trying toggle / launcher');
        const toggle = page.locator('#toshi-toggle, [data-testid="toshi-toggle"]').first();
        if (await toggle.isVisible().catch(() => false)) {
            await toggle.click({ timeout: 8000 }).catch(() => {});
            await page.waitForTimeout(800);
        }
        const launcher = page.locator('[data-testid="toshi-pill"], [title*="Toshi" i]').first();
        if (await launcher.isVisible().catch(() => false)) {
            await launcher.click({ timeout: 8000 }).catch(() => {});
            await page.waitForTimeout(1000);
        }
    }

    console.log('[toshi] ensurePanel: waiting for composer');
    await page.waitForSelector(COMPOSER, { timeout: 30_000 });
    console.log('[toshi] ensurePanel: ready');
}

async function snapshot(page) {
    return page.locator('[data-toshi-root]').first().innerText({ timeout: 15_000 }).catch(() => '');
}

/** Bot chat lines only — excludes checklist labels that false-match decide() rules. */
async function botTranscript(page, { lastOnly = false } = {}) {
    const bots = page.locator('[data-toshi-root] .toshi-msg-bot');
    const n = await bots.count().catch(() => 0);
    if (n === 0) return '';
    if (lastOnly) {
        return ((await bots.nth(n - 1).innerText({ timeout: 3000 }).catch(() => '')) || '').trim();
    }
    const parts = [];
    // Keep a short window for code extraction; decide() uses lastOnly.
    const start = Math.max(0, n - 6);
    for (let i = start; i < n; i++) {
        parts.push(((await bots.nth(i).innerText({ timeout: 3000 }).catch(() => '')) || '').trim());
    }
    return parts.filter(Boolean).join('\n');
}

async function clickContinueIfPresent(page) {
    // Prefer the term-current Continue (always advances).
    const termContinue = page.locator('[data-testid="toshi-term-current-continue"]:visible');
    if (await termContinue.count().catch(() => 0)) {
        const ok = await termContinue.first().click({ timeout: 8000 }).then(() => true).catch(() => false);
        if (ok) {
            console.log('[toshi] clicked term-current Continue');
            return 'term-current Continue';
        }
    }

    const btns = page.locator('[data-toshi-root] button:visible').filter({ hasText: /^\s*Continue\b/i });
    const n = await btns.count().catch(() => 0);
    for (let i = n - 1; i >= 0; i--) {
        const b = btns.nth(i);
        const label = ((await b.innerText().catch(() => '')) || '').trim();
        // Continue (0) is a no-op for subjects (requires ≥1) and spins the driver.
        const counted = label.match(/Continue\s*\((\d+)\)/i);
        if (counted && Number(counted[1]) === 0) continue;
        const ok = await b.click({ timeout: 8000 }).then(() => true).catch(() => false);
        if (ok) {
            console.log(`[toshi] clicked continue "${label.slice(0, 40)}"`);
            return label || true;
        }
    }
    return null;
}

async function hasConfirmChips(page) {
    const yes = page.locator('[data-toshi-root] button:visible').filter({ hasText: /^\s*Yes/i });
    return (await yes.count().catch(() => 0)) > 0;
}

/**
 * Click the next incomplete checklist row. Prefer required (warning) over
 * optional (info). Skip keys already opened this run when alternatives exist.
 */
async function clickNextSetupRow(page, state = {}) {
    const opened = state.openedSetup || {};
    const rows = page.locator('[data-testid="toshi-setup-list"] [data-testid^="toshi-setup-row-"]:visible');
    const n = await rows.count().catch(() => 0);
    const candidates = [];
    for (let i = 0; i < n; i++) {
        const row = rows.nth(i);
        const tone = (await row.getAttribute('data-tone').catch(() => '')) || '';
        if (tone === 'positive') continue;
        const testid = (await row.getAttribute('data-testid').catch(() => '')) || '';
        const key = testid.replace(/^toshi-setup-row-/, '');
        const label = ((await row.locator('.toshi-setup-row-label').innerText().catch(() => '')) || '').trim();
        candidates.push({ row, tone, key, label, opened: !!opened[key] });
    }
    if (candidates.length === 0) return null;

    const rank = (c) => {
        const req = c.tone === 'warning' ? 0 : 1;
        const fresh = c.opened ? 1 : 0;
        const prefer = ['plan_selection', 'whatsapp_verify', 'fees', 'terms', 'students', 'teachers'].indexOf(c.key);
        const prefScore = prefer === -1 ? 50 : prefer;
        return [req, fresh, prefScore];
    };
    candidates.sort((a, b) => {
        const ra = rank(a);
        const rb = rank(b);
        for (let i = 0; i < ra.length; i++) {
            if (ra[i] !== rb[i]) return ra[i] - rb[i];
        }
        return 0;
    });

    const pick = candidates[0];
    const ok = await pick.row.click({ timeout: 8000 }).then(() => true).catch(() => false);
    if (ok) {
        state.openedSetup = opened;
        state.openedSetup[pick.key] = true;
        console.log(`[toshi] clicked setup row "${pick.label}" (${pick.key}, tone=${pick.tone})`);
        return pick.label || pick.key;
    }
    return null;
}

async function tryQuickReply(page, answer) {
    const a = String(answer).trim().toLowerCase();
    const map = {
        // "Yes ✓" / "Yes ✔" chips — allow trailing checkmarks / whitespace
        'yes': /^\s*yes(\s*[✓✔✔️])?\s*$/i,
        'no': /^\s*no\s*$/i,
        'skip': /^\s*skip/i,
        'done': /^\s*(done|continue|ok)\s*$/i,
    };
    const matcher = map[a] || null;
    const buttons = page.locator('[data-toshi-root] button:visible');
    const n = await buttons.count().catch(() => 0);
    for (let i = 0; i < n; i++) {
        const b = buttons.nth(i);
        const txt = ((await b.innerText({ timeout: 3000 }).catch(() => '')) || '').trim();
        const norm = txt.toLowerCase().replace(/\s+/g, ' ');
        // Never treat checklist "Set up →" rows as quick-replies for free-text answers.
        if (/toshi-setup-row/i.test((await b.getAttribute('data-testid').catch(() => '')) || '')) continue;
        const matches = (matcher && matcher.test(txt))
            || norm === a
            || (a.length > 3 && !matcher && norm.includes(a));
        if (!matches) continue;
        const ok = await b.click({ timeout: 8000 }).then(() => true).catch(() => false);
        if (ok) {
            console.log(`[toshi] clicked quick-reply "${txt.slice(0, 40)}" for "${answer}"`);
            return true;
        }
    }
    return false;
}

async function send(page, text) {
    const answer = String(text);

    // Button-driven prompts: the composer goes readonly ("+ Use Yes / No above…").
    // Prefer the matching quick-reply button when the composer is not typable.
    const composer = page.locator(COMPOSER).last();
    const visible = await composer.isVisible().catch(() => false);
    let readonly = 'missing';
    let placeholder = '';
    if (visible) {
        readonly = await composer.getAttribute('readonly', { timeout: 3000 }).catch(() => 'missing');
        placeholder = (await composer.getAttribute('placeholder', { timeout: 3000 }).catch(() => '')) || '';
    }
    const typable = visible && readonly === null && !placeholder.includes('Use');
    if (!typable) {
        if (await tryQuickReply(page, answer)) {
            await page.waitForTimeout(900);
            return;
        }
        if (!visible) {
            console.log('[toshi] composer missing before send; re-opening panel');
            await ensurePanel(page).catch(() => {});
        }
    }

    for (let attempt = 0; attempt < 3; attempt++) {
        const c = page.locator(COMPOSER).last();
        try {
            await c.scrollIntoViewIfNeeded({ timeout: 5000 }).catch(() => {});
            await c.fill(answer, { timeout: T });
        } catch (e) {
            console.log(`[toshi] fill attempt ${attempt + 1} failed: ${String(e.message || e).slice(0, 120)}`);
            if (!(await page.locator(COMPOSER).first().isVisible().catch(() => false))) {
                console.log('[toshi] composer missing during send; re-opening panel');
                await ensurePanel(page).catch(() => {});
                await page.waitForTimeout(800);
            }
            if (await tryQuickReply(page, answer)) { await page.waitForTimeout(900); return; }
            await page.waitForTimeout(900);
            continue;
        }
        await page.waitForTimeout(200);
        await c.evaluate((el) => {
            el.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
        }).catch(() => {});
        await page.waitForTimeout(1100);
        let leftover = ((await c.inputValue({ timeout: 5000 }).catch(() => '')) || '').trim();
        if (leftover) {
            await c.evaluate((el) => {
                el.closest('form')?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }).catch(() => {});
            await page.waitForTimeout(1000);
            leftover = ((await c.inputValue({ timeout: 5000 }).catch(() => '')) || '').trim();
        }
        if (!leftover) return;
        console.log('[toshi] composer still has text after submit; retrying');
    }
    throw new Error('toshi: send failed after retries');
}

async function waitForNewContent(page, pattern, timeout = 90_000) {
    const before = await snapshot(page);
    const started = Date.now();
    while (Date.now() - started < timeout) {
        const now = await snapshot(page);
        const added = now.length > before.length ? now.slice(before.length) : now.slice(-1500);
        if (pattern.test(added) || pattern.test(now.slice(-2000))) return true;
        await page.waitForTimeout(800);
    }
    return false;
}

function decide(text, data, state) {
    // `text` must be bot-message transcript only (see botTranscript). Checklist
    // labels like "Academic terms" / "Approximate school size" false-match otherwise.
    const tail = text.slice(-900);
    const rules = [
        { key: 'name', re: /real name of your school|what's the (real )?name of your school|type the correct school name/i, answer: data.schoolName },
        { key: 'name-ok', re: /is the name correct\b/i, answer: 'yes', needsConfirm: true },
        // Must match OnboardingStepsService::STUDENT_SIZE_OPTIONS exactly (or a known alias).
        { key: 'size', re: /how many students does your school|roughly how many students|reply with one of:\s*\*\*up to 500/i, answer: 'Up to 500' },
        { key: 'country', re: /which country is your school/i, answer: 'Uganda' },
        { key: 'curriculum', re: /which curriculum does your school|i recommend \*\*uneb\*\*/i, answer: 'UNEB' },
        { key: 'category', re: /what type of school is this|pick a category below|nursery only|o-level \+ a-level/i, answer: data.type.categoryAnswer },
        { key: 'emis', re: /emis \/ ministry code|ministry code\?/i, answer: data.emisCode },
        { key: 'uneb', re: /uneb centre number|uneb center number/i, answer: 'skip' },
        { key: 'ay', re: /academic year next|is \*\*\d{4}\*\* correct|is \d{4} correct\?|academic year:\s*\d{4}.*is that correct/i, answer: 'yes', needsConfirm: true },
        { key: 'term-current', re: /which term is \*\*current\*\*|mark current|tap \*\*mark current\*\*/i, answer: null, clickContinue: true },
        { key: 'classes', re: /classes are ready|add streams|type \*\*done\*\* to continue/i, answer: 'done' },
        { key: 'subjects', re: /set up subjects per class|subjects per class/i, answer: 'done' },
        // Generic confirm after defaults were seeded (subjects/terms/etc.).
        { key: 'confirm-generic', re: /is this correct\b|does this look (right|correct)|look good\?/i, answer: 'yes', needsConfirm: true },
        { key: 'teachers', re: /let'?s add teachers|paste their names/i, answer: data.teachers.join('\n') },
        { key: 'teachers-done', re: /more teachers|another teacher|type 'done'/i, answer: 'done' },
        { key: 'students', re: /let'?s add students|students' names|paste.*students/i, answer: data.students.join('\n') },
        { key: 'students-done', re: /more students|another student/i, answer: 'done' },
        // Any reply on substep 0 seeds default terms then asks for confirm.
        { key: 'terms', re: /let'?s set up academic terms/i, answer: 'ok' },
        { key: 'terms-ok', re: /default ugandan terms set/i, answer: 'yes', needsConfirm: true },
        { key: 'fees', re: /add fee categories|type a fee name/i, answer: data.fee.name },
        { key: 'fee-amount', re: /how much|amount for|fee amount/i, answer: data.fee.amount },
        { key: 'wa', re: /verify your whatsapp number|whatsapp number for school/i, answer: data.admin.phoneE164 },
        { key: 'wa-code', re: /6-digit code|verification code/i, answer: state.code || null, dynamic: true },
        { key: 'done-next', re: /your school is set up|here'?s what to do next/i, answer: null, done: true },
    ];

    const candidates = [];
    for (const r of rules) {
        const re = new RegExp(r.re.source, 'gi');
        let mm;
        let last = -1;
        while ((mm = re.exec(tail)) !== null) last = mm.index;
        if (last >= 0) candidates.push({ rule: r, idx: last });
    }
    candidates.sort((a, b) => b.idx - a.idx);

    if (candidates.length === 0) return null;

    // Prefer collecting a school name over confirming one until we've sent a name.
    let top = candidates[0];
    if (top.rule.key === 'name-ok' && !state.answered.name) {
        const nameCand = candidates.find((c) => c.rule.key === 'name');
        if (nameCand) {
            top = nameCand;
        } else {
            // Confirm chips for a name we never typed — force the journey school name.
            return { key: 'name', answer: data.schoolName };
        }
    }

    const key = top.rule.key;
    if (top.rule.done) return { key, done: true };

    // Yes/no answers must only fire when confirm chips (or an explicit confirm prompt) are up.
    if (top.rule.needsConfirm && !state.hasYesChip) {
        return { wait: true, key, reason: 'needs-confirm-chips', answer: top.rule.answer };
    }

    // Suppress only while the recent prompt fingerprint is unchanged (send in flight).
    const fingerprint = tail.slice(-160);
    if (state.lastKey === key && state.lastFingerprint === fingerprint) {
        return { wait: true, key, answer: top.rule.answer };
    }
    if (top.rule.dynamic && !top.rule.answer) return { wait: true, key };
    if (top.rule.clickContinue) {
        state.lastKey = key;
        state.lastFingerprint = fingerprint;
        return { key, answer: null, clickContinue: true };
    }
    state.lastKey = key;
    state.lastFingerprint = fingerprint;
    return { key, answer: top.rule.answer };
}

async function runToshiJourney(page, data, findings = [], opts = {}) {
    const shotDir = opts.shotDir || null;
    const state = { answered: {}, code: null, hasYesChip: false, openedSetup: {} };
    const conversation = [];
    let turns = 0;
    let done = false;

    await ensurePanel(page);

    for (let i = 0; i < 80; i++) {
        // The app can refresh the page when Toshi advances a milestone (panel resets to
        // collapsed on reload). Re-open the panel before reading/answering.
        if (!(await page.locator(COMPOSER).first().isVisible().catch(() => false))) {
            console.log('[toshi] panel closed; re-opening');
            await ensurePanel(page).catch((e) => console.log('[toshi] reopen failed: ' + String(e).slice(0, 120)));
            await page.waitForTimeout(600);
        }
        const panelText = await snapshot(page);
        const textWindow = await botTranscript(page);
        const text = await botTranscript(page, { lastOnly: true });
        state.hasYesChip = await hasConfirmChips(page);
        const cm = (textWindow || panelText).match(/verification code is:?\s*(\d{6})/i)
            || (textWindow || panelText).match(/code[: ]+(\d{6})/i);
        if (cm) state.code = cm[1];

        // Inline Continue (subjects/teachers/fees) beats free-text.
        const continued = await clickContinueIfPresent(page);
        if (continued) {
            conversation.push({ turn: ++turns, sent: `[continue] ${continued}` });
            await page.waitForTimeout(2000);
            continue;
        }

        // Final review confirm commits draft terms/teachers/fees to the DB.
        const reviewConfirm = page.locator('[data-toshi-root] button:visible').filter({ hasText: /confirm|finish setup|looks good/i });
        const reviewWire = page.locator('button[wire\\:click="confirmOnboarding"]:visible');
        if ((await reviewWire.count().catch(() => 0)) > 0 || /review\s*&\s*confirm/i.test(panelText)) {
            const btn = (await reviewWire.count()) ? reviewWire.first() : reviewConfirm.first();
            if (await btn.isVisible().catch(() => false)) {
                await btn.click({ timeout: 10_000 }).catch(() => {});
                conversation.push({ turn: ++turns, sent: '[review] confirmOnboarding' });
                await page.waitForTimeout(3000);
                const after = await botTranscript(page, { lastOnly: true });
                if (/all done|everything looks set up|your school is set up|here'?s what to do next/i.test(after || panelText)) {
                    done = true;
                    break;
                }
                continue;
            }
        }

        // Plan buttons?
        const planButtons = page.locator('button[wire\\:click*="selectPlan"]:visible');
        if ((await planButtons.count()) > 0) {
            let clicked = null;
            for (let c = 0; c < (await planButtons.count()); c++) {
                const label = (await planButtons.nth(c).innerText({ timeout: 5000 }).catch(() => '')) || '';
                if (/freemium/i.test(label)) { await planButtons.nth(c).click({ timeout: 10_000 }); clicked = label.trim(); break; }
            }
            if (!clicked && (await planButtons.count())) { await planButtons.first().click({ timeout: 10_000 }); clicked = 'first plan'; }
            conversation.push({ turn: ++turns, sent: `[plan] ${clicked}` });
            await page.waitForTimeout(2500);
            const after = await botTranscript(page, { lastOnly: true });
            if (/plan selected|saved|all done|everything looks set up|your school is set up/i.test(after)) { done = true; break; }
            continue;
        }

        if (/all done|everything looks set up|setup complete|your school is set up|here'?s what to do next/i.test(text || textWindow.slice(-400))) {
            done = true;
            break;
        }

        // Confirm chips up: if the latest bubble is a confirm prompt (or nothing
        // actionable), click Yes. Probe decide with a throwaway state so we don't
        // poison lastKey/fingerprint.
        if (state.hasYesChip) {
            const confirmish = /is (the name|this) correct|look (right|good|correct)|yes\s*\/\s*no/i.test(text);
            const probe = decide(text, data, {
                answered: { ...state.answered },
                hasYesChip: true,
                code: state.code,
            });
            const probeIsConfirm = probe && (probe.key === 'name-ok' || probe.key === 'ay'
                || probe.key === 'terms-ok' || probe.key === 'confirm-generic'
                || probe.reason === 'needs-confirm-chips');
            if (confirmish || !probe || probeIsConfirm) {
                if (await tryQuickReply(page, 'yes')) {
                    conversation.push({ turn: ++turns, sent: '[chip] yes' });
                    console.log(`[toshi] turn ${turns}: [chip] yes (latest: ${text.slice(0, 80).replace(/\n/g, ' ')})`);
                    await page.waitForTimeout(2000);
                    continue;
                }
            }
        }

        const decision = decide(text, data, state);
        if (!decision) {
            // No bot prompt matched — open the next incomplete checklist step so a prompt appears.
            state.nullStreak = (state.nullStreak || 0) + 1;
            if (state.nullStreak <= 10) {
                const opened = await clickNextSetupRow(page, state);
                if (opened) {
                    conversation.push({ turn: ++turns, sent: `[setup] ${opened}` });
                    await page.waitForTimeout(2000);
                    continue;
                }
            }
            if (state.nullStreak % 4 === 1) {
                console.log('[toshi] waiting; bot-tail: ' + text.slice(-260).replace(/\n/g, ' | '));
            }
            await page.waitForTimeout(1500);
            continue;
        }
        state.nullStreak = 0;
        if (decision.done) { done = true; break; }
        if (decision.clickContinue) {
            const cont = await clickContinueIfPresent(page);
            if (cont) {
                conversation.push({ turn: ++turns, sent: `[continue] ${cont}` });
                await page.waitForTimeout(2000);
                continue;
            }
        }
        if (decision.wait) {
            state.confirmWaitStreak = (state.confirmWaitStreak || 0) + 1;
            if (decision.reason === 'needs-confirm-chips' && state.confirmWaitStreak >= 4) {
                console.log(`[toshi] confirm chips missing for ${decision.key}; typing answer`);
            } else {
                await page.waitForTimeout(1500);
                continue;
            }
        } else {
            state.confirmWaitStreak = 0;
        }

        // Never send bare yes/no/skip as a school name.
        if (decision.key === 'name' && /^(yes|no|skip|done|ok)$/i.test(String(decision.answer || '').trim())) {
            decision.answer = data.schoolName;
        }

        // Same key thrice → stuck on a stale prompt; jump checklist instead.
        state.keyCounts = state.keyCounts || {};
        state.keyCounts[decision.key] = (state.keyCounts[decision.key] || 0) + 1;
        const isConfirmKey = ['name-ok', 'ay', 'terms-ok', 'confirm-generic'].includes(decision.key);
        if (state.keyCounts[decision.key] >= 3 && !isConfirmKey) {
            const opened = await clickNextSetupRow(page, state);
            if (opened) {
                conversation.push({ turn: ++turns, sent: `[setup-unstick] ${opened}` });
                state.keyCounts[decision.key] = 0;
                await page.waitForTimeout(2000);
                continue;
            }
        }

        // Composer is yes/no gated but rule wants "done" — confirm instead.
        if (state.hasYesChip && /^(done|ok|skip)$/i.test(String(decision.answer || '').trim())) {
            if (await tryQuickReply(page, 'yes')) {
                conversation.push({ turn: ++turns, sent: `[chip] yes (instead of ${decision.answer})` });
                await page.waitForTimeout(2000);
                continue;
            }
        }

        if (decision.answer == null) {
            await page.waitForTimeout(1200);
            continue;
        }

        conversation.push({ turn: ++turns, sent: decision.answer });
        console.log(`[toshi] turn ${turns}: [${decision.key}] sent ${JSON.stringify(decision.answer).slice(0, 80)}`);
        try {
            await send(page, decision.answer);
            state.answered[decision.key] = true;
            state.confirmWaitStreak = 0;
        } catch (e) {
            state.sendErrors = (state.sendErrors || 0) + 1;
            console.log(`[toshi] send failed (${state.sendErrors}): ${String(e.message || e).slice(0, 120)}`);
            if (state.sendErrors >= 6) {
                findings.push('Toshi send failed repeatedly; aborting dialogue');
                break;
            }
            await ensurePanel(page).catch(() => {});
            await page.waitForTimeout(1500);
            continue;
        }
        if (shotDir) {
            try { fs.mkdirSync(shotDir, { recursive: true }); await page.screenshot({ path: path.join(shotDir, `turn-${String(turns).padStart(2, '0')}.png`) }); } catch {}
        }
        await page.waitForTimeout(600);
    }

    if (!done) findings.push('Toshi flow did not reach a completion signal within turn budget');

    return { turns, conversation, done, codeShown: !!state.code };
}

module.exports = { ensurePanel, snapshot, botTranscript, send, waitForNewContent, runToshiJourney, decide };
