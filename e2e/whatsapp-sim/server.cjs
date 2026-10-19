#!/usr/bin/env node
'use strict';

/**
 * Local WhatsApp simulator server (Level 1).
 *
 *  1. A mock of Meta's outbound Cloud API: POST /:version/:phoneNumberId/messages
 *     records every call and answers like the real API (contacts + messages[].id).
 *     Point the app at it with WHATSAPP_GRAPH_API_BASE_URL=http://127.0.0.1:<port>.
 *  2. A send proxy: POST /__sim/send builds a Meta-shaped inbound webhook, signs it
 *     with X-Hub-Signature-256 (HMAC-SHA256, the local test app secret) and posts it
 *     to the app's /api/whatsapp/inbound. Returns the replies recorded meanwhile.
 *  3. A one-page web UI at / (no external assets) to chat as a chosen phone number.
 *
 * Listens on 127.0.0.1 only and makes no external requests.
 *
 * Usage: node e2e/whatsapp-sim/server.cjs [--port 9123] [--app http://127.0.0.1:8010]
 *        [--app-dir .] [--record /tmp/whatsapp-sim-outbox.jsonl]
 */

const http = require('node:http');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const lib = require('./lib.cjs');

function parseArgs(argv) {
    const args = {};
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
        }
    }
    return args;
}

function createSimServer(options = {}) {
    const port = Number(options.port || lib.DEFAULTS.simPort);
    const appUrl = options.appUrl || lib.DEFAULTS.appUrl;
    lib.assertLocalUrl(appUrl, 'App URL');
    const appDir = options.appDir || process.cwd();
    const secret = options.secret || lib.resolveAppSecret(appDir);
    const recordFile = options.recordFile || null;

    const outbox = [];
    let seq = 0;

    function record(version, phoneNumberId, body, authorized) {
        seq += 1;
        const rec = {
            seq,
            at: new Date().toISOString(),
            version,
            phone_number_id: phoneNumberId,
            to: body && body.to,
            type: body && body.type,
            authorized,
            summary: lib.summarizeOutbound(body),
            body,
        };
        outbox.push(rec);
        if (recordFile) {
            fs.appendFileSync(recordFile, JSON.stringify(rec) + '\n');
        }
        return rec;
    }

    function readBody(req) {
        return new Promise((resolve, reject) => {
            const chunks = [];
            req.on('data', (c) => chunks.push(c));
            req.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
            req.on('error', reject);
        });
    }

    function json(res, status, obj) {
        res.writeHead(status, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify(obj));
    }

    async function sendInbound(input) {
        if (!secret) {
            throw new Error('No local test app secret: run setup-local-env.cjs first (writes WHATSAPP_APP_SECRET to the local .env) or set WHATSAPP_SIM_APP_SECRET.');
        }
        const target = input.appUrl || appUrl;
        lib.assertLocalUrl(target, 'App URL');
        const payload = lib.buildInboundPayload(input);
        const raw = JSON.stringify(payload);
        const before = seq;
        const startedAt = Date.now();
        const resp = await fetch(new URL('/api/whatsapp/inbound', target), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Hub-Signature-256': input.badSignature ? 'sha256=' + '0'.repeat(64) : lib.sign(raw, secret),
            },
            body: raw,
        });
        const text = await resp.text();
        return {
            status: resp.status,
            body: text.slice(0, 500),
            ms: Date.now() - startedAt,
            message_id: payload.entry[0].changes[0].value.messages[0].id,
            outbound: outbox.filter((r) => r.seq > before),
        };
    }

    const uiFile = path.join(__dirname, 'ui.html');

    const server = http.createServer(async (req, res) => {
        try {
            const url = new URL(req.url, `http://127.0.0.1:${port}`);

            // ── Mock of the Cloud API messages endpoint ──
            const m = url.pathname.match(/^\/(v\d+\.\d+)\/([^/]+)\/messages$/);
            if (m && req.method === 'POST') {
                const raw = await readBody(req);
                let body;
                try {
                    body = JSON.parse(raw);
                } catch (e) {
                    return json(res, 400, { error: { message: 'Invalid JSON', type: 'OAuthException', code: 100 } });
                }
                const authorized = /^Bearer\s+\S+/.test(String(req.headers.authorization || ''));
                if (!authorized) {
                    return json(res, 401, { error: { message: 'Missing access token (simulator)', type: 'OAuthException', code: 190 } });
                }
                if (body.messaging_product !== 'whatsapp' || !body.to || !body.type) {
                    return json(res, 400, { error: { message: 'messaging_product, to and type are required (simulator)', type: 'OAuthException', code: 100 } });
                }
                const rec = record(m[1], m[2], body, authorized);
                return json(res, 200, {
                    messaging_product: 'whatsapp',
                    contacts: [{ input: body.to, wa_id: lib.digits(body.to) }],
                    messages: [{ id: `wamid.SIM${String(rec.seq).padStart(6, '0')}` }],
                });
            }

            if (url.pathname === '/__sim/health') {
                return json(res, 200, { ok: true, appUrl, secretConfigured: Boolean(secret), recorded: outbox.length });
            }

            if (url.pathname === '/__sim/outbox' && req.method === 'GET') {
                const to = url.searchParams.get('to');
                const since = Number(url.searchParams.get('since') || 0);
                let list = outbox.filter((r) => r.seq > since);
                if (to) {
                    const d = lib.digits(to);
                    // The app may re-prefix a sender's number, so match on the trailing digits.
                    list = list.filter((r) => lib.digits(r.to).endsWith(d.slice(-10)));
                }
                return json(res, 200, { seq, messages: list });
            }

            if (url.pathname === '/__sim/reset' && req.method === 'POST') {
                outbox.length = 0;
                return json(res, 200, { ok: true, seq });
            }

            if (url.pathname === '/__sim/send' && req.method === 'POST') {
                const input = JSON.parse(await readBody(req) || '{}');
                const result = await sendInbound(input);
                return json(res, 200, result);
            }

            if (url.pathname === '/' && req.method === 'GET') {
                res.writeHead(200, {
                    'Content-Type': 'text/html; charset=utf-8',
                    'Content-Security-Policy': "default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'",
                });
                return res.end(fs.readFileSync(uiFile, 'utf8'));
            }

            // Any other Graph-looking path: answer like Meta does for unsupported calls.
            return json(res, 404, { error: { message: `Unsupported path in simulator: ${req.method} ${url.pathname}`, type: 'GraphMethodException', code: 100 } });
        } catch (e) {
            return json(res, 500, { error: { message: String(e && e.message || e) } });
        }
    });

    return {
        server,
        outbox,
        get seq() { return seq; },
        sendInbound,
        listen() {
            return new Promise((resolve) => server.listen(port, '127.0.0.1', () => resolve(this)));
        },
        close() {
            return new Promise((resolve) => server.close(() => resolve()));
        },
        port,
    };
}

module.exports = { createSimServer };

if (require.main === module) {
    const args = parseArgs(process.argv.slice(2));
    const sim = createSimServer({
        port: args.port,
        appUrl: args.app,
        appDir: args['app-dir'],
        recordFile: args.record || path.join(os.tmpdir(), 'whatsapp-sim-outbox.jsonl'),
    });
    sim.listen().then(() => {
        console.log(`whatsapp-sim listening on http://127.0.0.1:${sim.port} (UI at /, Graph mock at /<version>/<phone-number-id>/messages)`);
        console.log(`posting signed webhooks to ${args.app || lib.DEFAULTS.appUrl}/api/whatsapp/inbound`);
    });
    const stop = () => sim.close().then(() => process.exit(0));
    process.on('SIGINT', stop);
    process.on('SIGTERM', stop);
}
