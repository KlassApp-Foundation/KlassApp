#!/usr/bin/env node
'use strict';

/**
 * CLI for the local WhatsApp simulator. Talks to server.cjs (which signs and
 * forwards the webhook) and to the local app only.
 *
 *   node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --text "Hi"
 *   node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --button demo --title "Try Demo"
 *   node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --list REPORT --title "Report Card"
 *   node e2e/whatsapp-sim/cli.cjs outbox [--to +15550100001]
 *   node e2e/whatsapp-sim/cli.cjs reset
 *   node e2e/whatsapp-sim/cli.cjs handshake       (GET verify-token check, token read from .env, never printed)
 *
 * Options: --sim http://127.0.0.1:9123  --app http://127.0.0.1:8010  --app-dir <repo root>
 */

const path = require('node:path');
const lib = require('./lib.cjs');

function parseArgs(argv) {
    const args = { _: [] };
    for (let i = 0; i < argv.length; i++) {
        const a = argv[i];
        if (a.startsWith('--')) {
            const key = a.slice(2);
            const next = argv[i + 1];
            if (next === undefined || next.startsWith('--')) {
                args[key] = true;
            } else {
                args[key] = next;
                i++;
            }
        } else {
            args._.push(a);
        }
    }
    return args;
}

async function main() {
    const args = parseArgs(process.argv.slice(2));
    const cmd = args._[0];
    const sim = args.sim || `http://127.0.0.1:${lib.DEFAULTS.simPort}`;
    lib.assertLocalUrl(sim, 'Simulator URL');

    if (cmd === 'send') {
        const input = { from: args.from || lib.DEFAULTS.phones.ui, name: args.name || 'Sim User' };
        if (args.button) Object.assign(input, { kind: 'button', id: args.button, title: args.title || args.button });
        else if (args.list) Object.assign(input, { kind: 'list', id: args.list, title: args.title || args.list });
        else Object.assign(input, { kind: 'text', text: String(args.text || '') });
        if (args.app) input.appUrl = args.app;
        if (args['bad-signature']) input.badSignature = true;

        const res = await fetch(new URL('/__sim/send', sim), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(input) });
        const out = await res.json();
        if (out.error) throw new Error(out.error.message);
        console.log(lib.renderInbound(input));
        console.log(`  (webhook HTTP ${out.status}, ${out.ms} ms)`);
        if (!out.outbound.length) console.log('  (no outbound message)');
        for (const rec of out.outbound) console.log(lib.renderOutbound(rec));
        return out.status >= 200 && out.status < 300 ? 0 : 1;
    }

    if (cmd === 'outbox') {
        const u = new URL('/__sim/outbox', sim);
        if (args.to) u.searchParams.set('to', args.to);
        if (args.since) u.searchParams.set('since', args.since);
        const out = await (await fetch(u)).json();
        if (args.json) console.log(JSON.stringify(out, null, 2));
        else for (const rec of out.messages) console.log(`#${rec.seq} ${rec.at}\n${lib.renderOutbound(rec)}`);
        return 0;
    }

    if (cmd === 'reset') {
        await fetch(new URL('/__sim/reset', sim), { method: 'POST' });
        console.log('outbox cleared');
        return 0;
    }

    if (cmd === 'handshake') {
        const app = args.app || lib.DEFAULTS.appUrl;
        lib.assertLocalUrl(app, 'App URL');
        const env = lib.readDotEnv(path.join(args['app-dir'] || process.cwd(), '.env'));
        const token = env.WHATSAPP_BUSINESS_VERIFY_TOKEN || '';
        const challenge = String(Date.now());
        const u = new URL('/api/whatsapp/inbound', app);
        u.search = `hub.mode=subscribe&hub.verify_token=${encodeURIComponent(token)}&hub.challenge=${challenge}`;
        const res = await fetch(u);
        const body = await res.text();
        const ok = res.status === 200 && body === challenge;
        console.log(`verify-token handshake: HTTP ${res.status}, challenge ${ok ? 'echoed (OK)' : 'NOT echoed'}`);
        return ok ? 0 : 1;
    }

    console.log('Usage: cli.cjs send|outbox|reset|handshake [options] (see the header of this file)');
    return 2;
}

main().then((code) => process.exit(code), (e) => { console.error(`error: ${e.message}`); process.exit(1); });
