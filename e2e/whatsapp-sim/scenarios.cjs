#!/usr/bin/env node
'use strict';

/**
 * Plays the WhatsApp DEMO scenarios end to end against a LOCAL app through the
 * simulator, and writes one chat transcript per scenario.
 *
 *   node e2e/whatsapp-sim/scenarios.cjs [--only try-demo,report-pdf] [--out /path/outside/repo]
 *        [--app http://127.0.0.1:8010] [--sim http://127.0.0.1:9123] [--app-dir .]
 *        [--kill-switch-port 8019] [--bind-code CODE]
 *
 * Results: pass, fail or pending. "pending" is used for the BIND/MARKS demo
 * features that are not on main yet (Task 9 Tier 1); they report
 * "not implemented yet" instead of failing the run. Exit code is 1 only when a
 * scenario fails.
 *
 * Local only: the app URL, the simulator URL and any document link fetched
 * must be loopback addresses. The runner refuses an app whose APP_ENV is not
 * "local" because it resets local state for the simulator's fictional numbers.
 */

const fs = require('node:fs');
const path = require('node:path');
const { execFileSync, spawn } = require('node:child_process');
const net = require('node:net');
const lib = require('./lib.cjs');
const { createSimServer } = require('./server.cjs');

const PHONES = lib.DEFAULTS.phones;
const SIM_PHONE_DIGITS = Object.values(PHONES).map(lib.digits);

function eatNow() {
    return new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Africa/Kampala', year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    }).format(new Date()) + ' EAT';
}

class Transcript {
    constructor(name, title) {
        this.name = name;
        this.title = title;
        this.lines = [];
        this.checks = [];
        this.status = 'pass';
        this.note = '';
    }
    log(line = '') { this.lines.push(line); }
    check(ok, label) {
        this.checks.push({ ok, label });
        this.log(`CHECK ${ok ? 'ok  ' : 'FAIL'}: ${label}`);
        if (!ok && this.status !== 'pending') this.status = 'fail';
        return ok;
    }
    pending(note) {
        this.status = 'pending';
        this.note = note;
        this.log(`PENDING: ${note}`);
    }
    render(ctx) {
        const head = [
            `=== Scenario: ${this.name} — ${this.title}`,
            `Run at: ${this.startedAt} | app ${ctx.appUrl} | simulator ${ctx.simUrl}`,
            `Result: ${this.status.toUpperCase()}${this.note ? ` (${this.note})` : ''}`,
            'Numbers are fictional (+1 555 01xx). "BOT -> <to>" is the outbound call the app made to the mock Cloud API.',
            'Replies go back to the sender\'s own number (Meta\'s wa_id, kept as sent since #1104).',
            '',
        ];
        return head.concat(this.lines).join('\n') + '\n';
    }
}

function makeContext(opts = {}) {
    const appDir = path.resolve(opts.appDir || process.cwd());
    const ctx = {
        appDir,
        appUrl: opts.appUrl || lib.DEFAULTS.appUrl,
        simUrl: opts.simUrl || `http://127.0.0.1:${lib.DEFAULTS.simPort}`,
        php: opts.php || process.env.PHP_BINARY || 'php',
        killSwitchPort: Number(opts.killSwitchPort || 8019),
        bindCode: opts.bindCode || process.env.WHATSAPP_SIM_BIND_CODE || '',
        sim: null,
    };
    lib.assertLocalUrl(ctx.appUrl, 'App URL');
    lib.assertLocalUrl(ctx.simUrl, 'Simulator URL');
    return ctx;
}

async function ensureSim(ctx) {
    try {
        const r = await fetch(new URL('/__sim/health', ctx.simUrl));
        if (r.ok) return null;
    } catch (e) { /* not running: start one in-process */ }
    const port = Number(new URL(ctx.simUrl).port || lib.DEFAULTS.simPort);
    const sim = createSimServer({ port, appUrl: ctx.appUrl, appDir: ctx.appDir });
    await sim.listen();
    ctx.sim = sim;
    return sim;
}

async function stopSim(ctx) {
    if (ctx.sim) {
        await ctx.sim.close();
        ctx.sim = null;
    }
}

function artisan(ctx, args, env = {}) {
    return execFileSync(ctx.php, ['artisan', ...args], {
        cwd: ctx.appDir,
        env: { ...process.env, ...env },
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
        timeout: 180000,
    });
}

function tinker(ctx, code) {
    const out = artisan(ctx, ['tinker', '--execute', code]);
    const m = out.match(/SIMJSON:(.*)$/m);
    return m ? JSON.parse(m[1]) : null;
}

function assertLocalApp(ctx) {
    const env = lib.readDotEnv(path.join(ctx.appDir, '.env'));
    if ((env.APP_ENV || '') !== 'local') {
        throw new Error(`Refusing to run: APP_ENV in ${ctx.appDir}/.env is "${env.APP_ENV || ''}", expected "local".`);
    }
    if (env.WHATSAPP_GRAPH_API_BASE_URL) {
        lib.assertLocalUrl(env.WHATSAPP_GRAPH_API_BASE_URL, 'WHATSAPP_GRAPH_API_BASE_URL');
    } else {
        throw new Error('WHATSAPP_GRAPH_API_BASE_URL is not set in the local .env; run setup-local-env.cjs first.');
    }
}

/** Remove local simulator state for the kit's fictional numbers only (local app only). */
function resetLocalState(ctx) {
    const patterns = SIM_PHONE_DIGITS.map((d) => `%${d}`);
    const code = `
        $patterns = json_decode('${JSON.stringify(patterns)}', true);
        $q = \\App\\Models\\WhatsAppUser::query()->where(function ($w) use ($patterns) { foreach ($patterns as $p) { $w->orWhere('phone', 'like', $p); } });
        $phones = $q->pluck('phone')->all();
        foreach ($phones as $ph) { \\Illuminate\\Support\\Facades\\RateLimiter::clear('whatsapp-report:'.$ph); }
        $n = $q->delete();
        echo 'SIMJSON:'.json_encode(['removed' => $n]).PHP_EOL;
    `;
    return tinker(ctx, code);
}

async function send(ctx, t, input) {
    const res = await fetch(new URL('/__sim/send', ctx.simUrl), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(input),
    });
    const out = await res.json();
    if (out.error) throw new Error(out.error.message);
    t.log(lib.renderInbound(input));
    t.log(`  (webhook HTTP ${out.status}, ${out.ms} ms${input.appUrl ? `, app ${input.appUrl}` : ''})`);
    if (!out.outbound.length) t.log('  (no outbound message recorded)');
    for (const rec of out.outbound) t.log(lib.renderOutbound(rec));
    t.log('');
    return out;
}

async function outboxSeq(ctx) {
    const out = await (await fetch(new URL('/__sim/outbox?since=999999999', ctx.simUrl))).json();
    return out.seq;
}

async function outboxSince(ctx, since) {
    const out = await (await fetch(new URL(`/__sim/outbox?since=${since}`, ctx.simUrl))).json();
    return out.messages;
}

/** The app's newest log file (single or daily channel). */
function latestLog(ctx) {
    const dir = path.join(ctx.appDir, 'storage', 'logs');
    const files = fs.existsSync(dir) ? fs.readdirSync(dir).filter((f) => /^laravel.*\.log$/.test(f)) : [];
    files.sort((a, b) => fs.statSync(path.join(dir, b)).mtimeMs - fs.statSync(path.join(dir, a)).mtimeMs);
    return path.join(dir, files[0] || 'laravel.log');
}

function logSince(file, offset, re) {
    if (!fs.existsSync(file)) return 0;
    const buf = fs.readFileSync(file);
    return (buf.subarray(offset).toString('utf8').match(re) || []).length;
}

function portFree(port) {
    return new Promise((resolve) => {
        const srv = net.createServer();
        srv.once('error', () => resolve(false));
        srv.listen(port, '127.0.0.1', () => srv.close(() => resolve(true)));
    });
}

const text = (out) => out.outbound.map((r) => r.summary.text || '').join('\n');
const has = (out, kind) => out.outbound.filter((r) => r.summary.kind === kind);

async function tryDemo(ctx, t, from, name = 'Sim Parent') {
    return send(ctx, t, { from, name, kind: 'button', id: 'demo', title: '🎯 Try Demo' });
}

// ─────────────────────────────── scenarios ───────────────────────────────

const SCENARIOS = [
    {
        name: 'try-demo',
        title: 'A stranger says Hi, taps Try Demo and gets the parent menu',
        async run(ctx, t) {
            const from = PHONES.stranger;
            const hi = await send(ctx, t, { from, name: 'Sim Stranger', kind: 'text', text: 'Hi' });
            t.check(hi.status === 200, 'webhook accepted the signed message (HTTP 200)');
            const welcome = has(hi, 'buttons')[0];
            t.check(Boolean(welcome) && welcome.summary.buttons.some((b) => b.id === 'demo'), 'the stranger welcome offers a Try Demo button (id=demo)');

            const demo = await tryDemo(ctx, t, from, 'Sim Stranger');
            t.check(/Hello/.test(text(demo)), 'Try Demo replies with a greeting');
            t.check(/Your number is removed after 30 minutes\./.test(text(demo)), 'the demo greeting carries the 30-minute removal notice');
            const menu = has(demo, 'list')[0];
            t.check(Boolean(menu) && menu.summary.rows.some((r) => r.id === 'REPORT') && menu.summary.rows.some((r) => r.section === 'Parent'),
                'the parent menu (list with a Parent section and a Report Card row) is sent');
        },
    },
    {
        name: 'bind-marks',
        title: 'BIND <code>, then MARKS (Task 9 Tier 1, pending until that PR)',
        pendingUntil: 'Task 9 Tier 1 PR',
        async run(ctx, t) {
            const from = PHONES.bind;
            let code = ctx.bindCode;
            if (!code) {
                const r = tinker(ctx, `
                    $sid = \\App\\Models\\School::where('is_demo', 1)->orderBy('id')->value('id');
                    $kls = \\App\\Models\\StudentAcademic::where('school_id', $sid)->whereNotNull('klassapp_student_id')->orderBy('id')->value('klassapp_student_id');
                    echo 'SIMJSON:'.json_encode(['kls' => $kls]).PHP_EOL;
                `);
                code = (r && r.kls) || 'KLS0000000';
                t.log(`(no --bind-code given: using a demo student's KLS number "${code}"; Task 9 derives short codes from it)`);
                t.log('');
            }
            const bind = await send(ctx, t, { from, name: 'Sim Presenter Guest', kind: 'text', text: `BIND ${code}` });
            t.check(bind.status === 200, 'webhook accepted the signed message (HTTP 200)');
            const implemented = /MARKS/.test(text(bind)) && /removed after/i.test(text(bind));
            if (!implemented) {
                t.pending('not implemented yet: BIND <code> is part of Task 9 Tier 1 and is not on main; the app answered with its stranger flow');
                return;
            }
            t.check(/Your number is removed after 45 minutes\./.test(text(bind)), 'the bind reply says the number is removed after 45 minutes');
            const marks = await send(ctx, t, { from, name: 'Sim Presenter Guest', kind: 'text', text: 'MARKS' });
            t.check(/average/i.test(text(marks)) && /total/i.test(text(marks)), 'MARKS returns the latest exam as a table with total and average');
        },
    },
    {
        name: 'report-pdf',
        title: 'REPORT returns the report card PDF',
        async run(ctx, t) {
            const from = PHONES.demo;
            t.log('-- setup: link this number to the demo parent through Try Demo --');
            await tryDemo(ctx, t, from);
            t.log('-- scenario --');
            const rep = await send(ctx, t, { from, name: 'Sim Parent', kind: 'list', id: 'REPORT', title: 'Report Card' });
            t.check(rep.status === 200, 'webhook accepted the signed message (HTTP 200)');
            const docs = has(rep, 'document');
            t.check(docs.length > 0, `the mock recorded ${docs.length} document message(s)`);
            for (const d of docs) {
                t.check(/\.pdf$/i.test(d.summary.filename), `document filename ends in .pdf (${d.summary.filename})`);
                let ok = false;
                let info = '';
                try {
                    lib.assertLocalUrl(d.summary.link, 'Document link');
                    const r = await fetch(d.summary.link);
                    const buf = Buffer.from(await r.arrayBuffer());
                    ok = r.status === 200 && buf.subarray(0, 4).toString('latin1') === '%PDF';
                    info = `HTTP ${r.status}, ${buf.length} bytes, starts with ${JSON.stringify(buf.subarray(0, 5).toString('latin1'))}`;
                } catch (e) {
                    info = e.message;
                }
                t.check(ok, `the document link serves a PDF from the local app (${info})`);
            }
        },
    },
    {
        name: 'stop-optin',
        title: 'STOP, then OPTIN',
        async run(ctx, t) {
            const from = PHONES.stopOptin;
            t.log('-- setup: link this number to the demo parent through Try Demo --');
            await tryDemo(ctx, t, from);
            t.log('-- scenario --');
            const stop = await send(ctx, t, { from, kind: 'text', text: 'STOP' });
            t.check(/unsubscribed/i.test(text(stop)) && /OPTIN/.test(text(stop)), 'STOP confirms the unsubscribe and mentions OPTIN');
            const after = await send(ctx, t, { from, kind: 'text', text: 'FEES' });
            t.check(/opted out/i.test(text(after)) && has(after, 'list').length === 0, 'while opted out a keyword gets only the opted-out notice, no menu');
            const optin = await send(ctx, t, { from, kind: 'text', text: 'OPTIN' });
            t.check(/opted in/i.test(text(optin)), 'OPTIN confirms with one line');
            t.check(optin.outbound.length === 1, `OPTIN sends exactly one message (${optin.outbound.length})`);
            const menu = await send(ctx, t, { from, kind: 'text', text: 'MENU' });
            t.check(has(menu, 'list').length > 0, 'after OPTIN the menu works again');
        },
    },
    {
        name: 'unknown-code',
        title: 'An unknown BIND code gets a friendly reply with the follow-along URL (Task 9 Tier 1, pending until that PR)',
        pendingUntil: 'Task 9 Tier 1 PR',
        async run(ctx, t) {
            const from = PHONES.unknownCode;
            const out = await send(ctx, t, { from, name: 'Sim Guest', kind: 'text', text: 'BIND ZZ9Q' });
            t.check(out.status === 200, 'webhook accepted the signed message (HTTP 200)');
            if (!/\/demo\b/.test(text(out))) {
                t.pending('not implemented yet: the unknown-code reply is part of BIND (Task 9 Tier 1); the app answered with its stranger flow');
                return;
            }
            t.check(out.outbound.length >= 1, 'a friendly reply was sent');
        },
    },
    {
        name: 'unknown-kls-id',
        title: 'An unknown KlassApp ID (existing linking path on main) gets a friendly not-found reply',
        async run(ctx, t) {
            const from = PHONES.unknownCode;
            const out = await send(ctx, t, { from, name: 'Sim Guest', kind: 'text', text: 'KLS9999999' });
            t.check(out.status === 200, 'webhook accepted the signed message (HTTP 200)');
            t.check(/couldn't find a student/i.test(text(out)), 'the reply says the ID was not found');
            const b = has(out, 'buttons')[0];
            t.check(Boolean(b) && b.summary.buttons.some((x) => x.id === 'link_help'), 'the reply offers Link help');
        },
    },
    {
        name: 'fee-reminder-demo',
        title: 'A fee reminder on a demo school sends nothing',
        async run(ctx, t) {
            const info = tinker(ctx, `
                $sid = \\App\\Models\\School::where('is_demo', 1)->orderBy('id')->value('id');
                $n = \\App\\Models\\WhatsAppUser::where('school_id', $sid)->where('opted_in', true)->update(['last_inbound_at' => now()]);
                echo 'SIMJSON:'.json_encode(['school_id' => $sid, 'windows_opened' => $n]).PHP_EOL;
            `);
            t.log(`-- setup (local DB only): demo school id ${info.school_id}; opened the 24-hour window for ${info.windows_opened} opted-in demo WhatsApp numbers, so the reminder would be sent immediately if the guard let it --`);
            t.check(Boolean(info.school_id), 'a demo school exists locally');

            const logFile = latestLog(ctx);
            const logSize = fs.existsSync(logFile) ? fs.statSync(logFile).size : 0;
            const guardCount = () => logSince(logFile, 0, /\[demo-guard\] WhatsApp send blocked/g);

            t.log(`$ php artisan whatsapp:send-fee-reminders --school-id=${info.school_id} --dry-run`);
            const dry = artisan(ctx, ['whatsapp:send-fee-reminders', `--school-id=${info.school_id}`, '--dry-run']);
            const would = (dry.match(/Would notify/g) || []).length;
            t.log(dry.split('\n').filter((l) => /Would notify|Running|Done/.test(l)).slice(0, 6).map((l) => `  ${l.trim()}`).join('\n'));
            t.log(`  ... (${would} students with a reachable parent number)`);
            t.check(would > 0, `the dry run finds parents it would notify (${would}), so the check below is meaningful`);

            const before = await outboxSeq(ctx);
            const g0 = guardCount();
            t.log(`$ php artisan whatsapp:send-fee-reminders --school-id=${info.school_id}`);
            const real = artisan(ctx, ['whatsapp:send-fee-reminders', `--school-id=${info.school_id}`]);
            t.log(real.split('\n').filter((l) => /Running|Done/.test(l)).map((l) => `  ${l.trim()}`).join('\n'));
            const sent = await outboxSince(ctx, before);
            const blocked = guardCount() - g0;
            t.log(sent.length ? sent.map(lib.renderOutbound).join('\n') : '  (no outbound message recorded by the mock)');
            t.log('');
            t.check(sent.length === 0, `the mock Cloud API recorded no message (${sent.length})`);
            t.check(/Total WhatsApp messages sent: 0/.test(real), 'the command reports 0 messages sent');
            const crashes = logSince(logFile, logSize, /Fee reminder failed for student/g);
            if (crashes > 0) {
                t.log(`NOTE: the command logged ${crashes} per-student "Fee reminder failed" error(s) and ${blocked} demo-guard block(s),`);
                t.log('      so part A alone does not prove the guard stopped the sends.');
                t.log('      Part B checks the guard on the other fee-reminder path (a queued reminder flushed when the parent writes).');
            } else {
                t.check(blocked > 0, `the demo guard logged ${blocked} blocked send(s)`);
            }
            t.log('');

            t.log('-- part B: a fee reminder queued for a demo parent stays blocked while the reply to their own message goes out --');
            const from = PHONES.demo;
            await tryDemo(ctx, t, from);
            const q = tinker(ctx, `
                $wa = \\App\\Models\\WhatsAppUser::where('phone', 'like', '%${lib.digits(PHONES.demo)}')->first();
                $n = \\App\\Models\\WhatsAppPendingNotification::create(['whatsapp_user_id' => $wa->id, 'flow_type' => 'fee_reminder', 'message' => 'SIMULATOR QUEUED FEE REMINDER: Term fees are due.']);
                echo 'SIMJSON:'.json_encode(['queued' => $n->id, 'school_id' => $wa->school_id]).PHP_EOL;
            `);
            t.log(`(queued fee_reminder notification #${q.queued} for this number, school id ${q.school_id})`);
            const g1 = guardCount();
            const menu = await send(ctx, t, { from, name: 'Sim Parent', kind: 'text', text: 'MENU' });
            const leaked = menu.outbound.filter((r) => /SIMULATOR QUEUED FEE REMINDER/.test(r.summary.text || ''));
            t.check(has(menu, 'list').length > 0, 'the reply to the parent (the menu) goes out');
            t.check(leaked.length === 0, `the queued fee reminder was not sent (${leaked.length} recorded)`);
            t.check(guardCount() - g1 > 0, `the demo guard logged the blocked reminder (${guardCount() - g1})`);
        },
    },
    {
        name: 'kill-switch-off',
        title: 'With WHATSAPP_REPLIES_ENABLED=false nothing is sent, not even replies',
        async run(ctx, t) {
            const port = ctx.killSwitchPort;
            const offUrl = `http://127.0.0.1:${port}`;
            t.log(`-- setup: a second local app process on ${offUrl} with WHATSAPP_REPLIES_ENABLED=false (same code, same local DB) --`);
            if (!(await portFree(port))) {
                t.check(false, `port ${port} is free for the switch-off app process (it is in use; pass --kill-switch-port)`);
                return;
            }
            const proc = spawn(ctx.php, ['-S', `127.0.0.1:${port}`, '-t', 'public', 'server.php'], {
                cwd: ctx.appDir,
                env: { ...process.env, WHATSAPP_REPLIES_ENABLED: 'false' },
                stdio: 'ignore',
            });
            let exited = false;
            proc.on('exit', () => { exited = true; });
            try {
                let up = false;
                for (let i = 0; i < 60 && !up && !exited; i++) {
                    try { up = (await fetch(`${offUrl}/login`)).status < 500; } catch (e) { await new Promise((r) => setTimeout(r, 250)); }
                }
                t.check(up && !exited, 'the switch-off app process (started by this run) is up');
                if (!up || exited) return;
                const s1 = await send(ctx, t, { from: PHONES.killSwitch, name: 'Sim Stranger', kind: 'text', text: 'Hi', appUrl: offUrl });
                t.check(s1.status === 200 && s1.outbound.length === 0, `a stranger's Hi gets no outbound message (${s1.outbound.length})`);
                const s2 = await send(ctx, t, { from: PHONES.killSwitch, name: 'Sim Stranger', kind: 'button', id: 'demo', title: '🎯 Try Demo', appUrl: offUrl });
                t.check(s2.status === 200 && s2.outbound.length === 0, `Try Demo gets no outbound message (${s2.outbound.length})`);
            } finally {
                proc.kill('SIGTERM');
            }
            t.log('-- control: the same number against the normal app (switch on) --');
            const s3 = await send(ctx, t, { from: PHONES.killSwitch, name: 'Sim Stranger', kind: 'text', text: 'MENU' });
            t.check(s3.outbound.length > 0, `with the switch on the same number gets replies (${s3.outbound.length})`);
        },
    },
];

async function runScenarios(opts = {}) {
    const ctx = makeContext(opts);
    assertLocalApp(ctx);
    await ensureSim(ctx);
    const results = [];
    try {
        const reset = resetLocalState(ctx);
        const only = opts.only ? String(opts.only).split(',') : null;
        for (const sc of SCENARIOS) {
            if (only && !only.includes(sc.name)) continue;
            const t = new Transcript(sc.name, sc.title);
            t.startedAt = eatNow();
            try {
                await sc.run(ctx, t);
            } catch (e) {
                t.log(`ERROR: ${e.message}`);
                t.status = 'fail';
            }
            results.push({ name: sc.name, title: sc.title, status: t.status, note: t.note, checks: t.checks, transcript: t.render(ctx) });
        }
        results.reset = reset;
    } finally {
        await stopSim(ctx);
    }
    if (opts.out) {
        const out = path.resolve(opts.out);
        try {
            const top = execFileSync('git', ['-C', ctx.appDir, 'rev-parse', '--show-toplevel'], { encoding: 'utf8' }).trim();
            if (out === top || out.startsWith(top + path.sep)) {
                throw new Error(`--out ${out} is inside the repository; transcripts are evidence and stay outside the repo.`);
            }
        } catch (e) {
            if (/inside the repository/.test(e.message)) throw e;
        }
        fs.mkdirSync(out, { recursive: true });
        for (const r of results) fs.writeFileSync(path.join(out, `${r.name}.txt`), r.transcript);
    }
    return results;
}

module.exports = { SCENARIOS, runScenarios, makeContext };

if (require.main === module) {
    const args = {};
    const argv = process.argv.slice(2);
    for (let i = 0; i < argv.length; i++) {
        if (argv[i].startsWith('--')) { args[argv[i].slice(2)] = argv[i + 1]; i++; }
    }
    runScenarios({
        appUrl: args.app, simUrl: args.sim, appDir: args['app-dir'], only: args.only, out: args.out,
        killSwitchPort: args['kill-switch-port'], bindCode: args['bind-code'],
    }).then((results) => {
        for (const r of results) {
            console.log(`${r.status.toUpperCase().padEnd(8)} ${r.name}${r.note ? ` — ${r.note}` : ''}`);
        }
        if (args.out) console.log(`transcripts written to ${path.resolve(args.out)}`);
        process.exit(results.some((r) => r.status === 'fail') ? 1 : 0);
    }, (e) => { console.error(`error: ${e.message}`); process.exit(1); });
}
