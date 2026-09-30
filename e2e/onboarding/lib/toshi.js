// Toshi-assisted onboarding driver: scripted answers, outcome-based waits,
// full conversation recording, turn counting. Never asserts on exact wording
// beyond small structural anchors.
const path = require('node:path');
const fs = require('node:fs');

const COMPOSER = '#toshi-input-panel:visible';
const SEND = 'button[title="Send"]:visible';

const T = 20_000;

async function ensurePanel(page) {
    console.log('[toshi] ensurePanel: waiting for root (attached)');
    await page.waitForSelector('[data-toshi-root]', { state: 'attached', timeout: 60_000 });

    // Prefer the in-page event path (same as the setup banner dispatch).
    if (!(await page.locator(COMPOSER).first().isVisible().catch(() => false))) {
        console.log('[toshi] ensurePanel: dispatching maximize events');
        await page.evaluate(() => {
            document.body.classList.remove('toshi-collapsed');
            window.dispatchEvent(new CustomEvent('toshi-maximize'));
        }).catch(() => {});
        await page.waitForTimeout(1200);
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
        console.log('[toshi] ensurePanel: trying launcher fallback');
        const launcher = page.locator('[title*="Toshi" i], [data-toshi-toggle], button:has-text("Toshi")').first();
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

async function tryQuickReply(page, answer) {
    const a = String(answer).trim().toLowerCase();
    const map = {
        'yes': /^(yes|correct|ok)$/i,
        'no': /^no$/i,
        'skip': /^skip/i,
        'done': /^(done|continue|ok)$/i,
    };
    const matcher = map[a] || null;
    const buttons = page.locator('[data-toshi-root] button:visible');
    const n = await buttons.count().catch(() => 0);
    for (let i = 0; i < n; i++) {
        const b = buttons.nth(i);
        const txt = ((await b.innerText({ timeout: 3000 }).catch(() => '')) || '').trim();
        const norm = txt.toLowerCase();
        const matches = (matcher && matcher.test(txt))
            || norm === a
            || (a.length > 3 && norm.includes(a));
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
    const tail = text.slice(-4000);
    const rules = [
        { key: 'name', re: /real name of your school|what's the (real )?name|school name\?/i, answer: data.schoolName },
        { key: 'name-ok', re: /is the name correct|name correct\?/i, answer: 'yes' },
        { key: 'size', re: /how many students|school size|under 100 students/i, answer: '100-300 students' },
        { key: 'country', re: /which country/i, answer: 'Uganda' },
        { key: 'curriculum', re: /which curriculum|board \/ curriculum/i, answer: 'UNEB' },
        { key: 'category', re: /what type of school|category below|nursery only|o-level \+ a-level/i, answer: data.type.categoryAnswer },
        { key: 'emis', re: /emis \/ ministry|ministry code/i, answer: data.emisCode },
        { key: 'uneb', re: /uneb centre|uneb center/i, answer: 'skip' },
        { key: 'ay', re: /academic year next|is \d{4} correct/i, answer: 'yes' },
        { key: 'classes', re: /classes are ready/i, answer: 'done' },
        { key: 'subjects', re: /subjects per class/i, answer: 'done' },
        { key: 'teachers', re: /add teachers|paste their names/i, answer: data.teachers.join('\n') },
        { key: 'teachers-done', re: /more teachers|another teacher|type 'done'/i, answer: 'done' },
        { key: 'students', re: /add students|students' names|paste.*students/i, answer: data.students.join('\n') },
        { key: 'students-done', re: /more students|another student/i, answer: 'done' },
        { key: 'terms', re: /academic terms|set up.*terms/i, answer: 'yes' },
        { key: 'fees', re: /fee categor|fee name|add fee/i, answer: data.fee.name },
        { key: 'fee-amount', re: /amount|how much/i, answer: data.fee.amount },
        { key: 'wa', re: /verify your whatsapp|whatsapp number/i, answer: data.admin.phoneE164 },
        { key: 'wa-code', re: /6-digit code|verification code/i, answer: state.code || null, dynamic: true },
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

    const recent = state.recent || (state.recent = []);
    for (const c of candidates) {
        const key = c.rule.key;
        if (recent.includes(key)) continue;
        if (c.rule.dynamic && !c.rule.answer) return { wait: true, key };
        recent.push(key);
        state.recent = recent.slice(-5);
        return { key, answer: c.rule.answer };
    }
    return null;
}

async function runToshiJourney(page, data, findings = [], opts = {}) {
    const shotDir = opts.shotDir || null;
    const state = { answered: {}, code: null };
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
        const text = await snapshot(page);
        const cm = text.match(/verification code is:?\s*(\d{6})/i) || text.match(/code[: ]+(\d{6})/i);
        if (cm) state.code = cm[1];

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
            const after = await snapshot(page);
            if (/plan selected|saved|all done|everything looks set up/i.test(after.slice(-1200))) { done = true; break; }
            continue;
        }

        if (/all done|everything looks set up|setup complete/i.test(text.slice(-1200))) { done = true; break; }

        const decision = decide(text, data, state);
        if (!decision) {
            state.nullStreak = (state.nullStreak || 0) + 1;
            if (state.nullStreak % 4 === 1) {
                console.log('[toshi] waiting; tail: ' + text.slice(-260).replace(/\n/g, ' | '));
            }
            await page.waitForTimeout(1500);
            continue;
        }
        state.nullStreak = 0;
        if (decision.wait) { await page.waitForTimeout(1500); continue; }

        conversation.push({ turn: ++turns, sent: decision.answer });
        console.log(`[toshi] turn ${turns}: sent ${JSON.stringify(decision.answer).slice(0, 80)}`);
        try {
            await send(page, decision.answer);
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

module.exports = { ensurePanel, snapshot, send, waitForNewContent, runToshiJourney };
