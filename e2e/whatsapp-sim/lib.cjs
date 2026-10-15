'use strict';

/**
 * Shared helpers for the local WhatsApp simulator.
 *
 * Local only: every URL this kit talks to must be a loopback address
 * (127.0.0.1, localhost or ::1). Nothing here contacts Meta or any other
 * external host.
 */

const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');

const DEFAULTS = {
    simPort: 9123,
    appUrl: 'http://127.0.0.1:8010',
    // Fictional numbers from the reserved 555-0100..0199 range. Not real phones.
    phones: {
        stranger: '+15550100001',
        demo: '+15550100002',
        stopOptin: '+15550100003',
        bind: '+15550100004',
        unknownCode: '+15550100005',
        killSwitch: '+15550100006',
        ui: '+15550100009',
    },
};

const LOOPBACK_HOSTS = new Set(['127.0.0.1', 'localhost', '::1', '[::1]']);

function assertLocalUrl(url, what = 'URL') {
    let parsed;
    try {
        parsed = new URL(url);
    } catch (e) {
        throw new Error(`${what} is not a valid URL: ${url}`);
    }
    if (!LOOPBACK_HOSTS.has(parsed.hostname)) {
        throw new Error(`${what} must be a local loopback address (127.0.0.1, localhost or ::1), got host "${parsed.hostname}". This kit never contacts external hosts.`);
    }
    return parsed;
}

/** Read KEY=VALUE pairs from a .env file (no interpolation; enough for this kit). */
function readDotEnv(file) {
    const out = {};
    if (!fs.existsSync(file)) {
        return out;
    }
    for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
        const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
        if (!m) continue;
        let v = m[2];
        if ((v.startsWith('"') && v.endsWith('"')) || (v.startsWith("'") && v.endsWith("'"))) {
            v = v.slice(1, -1);
        }
        out[m[1]] = v;
    }
    return out;
}

/**
 * The app secret used to sign inbound webhooks. Taken from
 * WHATSAPP_SIM_APP_SECRET, else from WHATSAPP_APP_SECRET in the app's .env.
 * Never printed.
 */
function resolveAppSecret(appDir) {
    if (process.env.WHATSAPP_SIM_APP_SECRET) {
        return process.env.WHATSAPP_SIM_APP_SECRET;
    }
    if (appDir) {
        const env = readDotEnv(path.join(appDir, '.env'));
        if (env.WHATSAPP_APP_SECRET) {
            return env.WHATSAPP_APP_SECRET;
        }
    }
    return '';
}

function digits(phone) {
    return String(phone || '').replace(/\D+/g, '');
}

function randomId(prefix) {
    return `${prefix}${crypto.randomBytes(12).toString('hex').toUpperCase()}`;
}

/**
 * Build a Meta Cloud API "messages" webhook payload for one inbound message.
 * kind: text | button | list
 */
function buildInboundPayload({ from, name = 'Sim User', kind = 'text', text = '', id = '', title = '', phoneNumberId = '100000000000001', displayPhone = '15550100000' }) {
    const wa = digits(from);
    const msg = {
        from: wa,
        id: randomId('wamid.SIMIN'),
        timestamp: String(Math.floor(Date.now() / 1000)),
    };
    if (kind === 'text') {
        msg.type = 'text';
        msg.text = { body: text };
    } else if (kind === 'button') {
        msg.type = 'interactive';
        msg.interactive = { type: 'button_reply', button_reply: { id: id || text, title: title || id || text } };
    } else if (kind === 'list') {
        msg.type = 'interactive';
        msg.interactive = { type: 'list_reply', list_reply: { id: id || text, title: title || id || text } };
    } else {
        throw new Error(`Unknown inbound kind "${kind}" (use text, button or list)`);
    }

    return {
        object: 'whatsapp_business_account',
        entry: [{
            id: '100000000000000',
            changes: [{
                field: 'messages',
                value: {
                    messaging_product: 'whatsapp',
                    metadata: { display_phone_number: displayPhone, phone_number_id: phoneNumberId },
                    contacts: [{ profile: { name }, wa_id: wa }],
                    messages: [msg],
                },
            }],
        }],
    };
}

/** X-Hub-Signature-256 value for a raw body, as Meta computes it. */
function sign(rawBody, secret) {
    return 'sha256=' + crypto.createHmac('sha256', secret).update(rawBody).digest('hex');
}

/** One-line human summary of a recorded outbound Graph API message body. */
function summarizeOutbound(body) {
    const type = body && body.type;
    if (type === 'text') {
        return { kind: 'text', text: body.text && body.text.body || '' };
    }
    if (type === 'document') {
        const d = body.document || {};
        return { kind: 'document', text: d.caption || '', filename: d.filename || '', link: d.link || '' };
    }
    if (type === 'template') {
        const t = body.template || {};
        return { kind: 'template', text: `template ${t.name || '?'}` };
    }
    if (type === 'interactive') {
        const i = body.interactive || {};
        const bodyText = i.body && i.body.text || '';
        if (i.type === 'button') {
            const buttons = (i.action && i.action.buttons || []).map((b) => ({ id: b.reply && b.reply.id, title: b.reply && b.reply.title }));
            return { kind: 'buttons', text: bodyText, buttons };
        }
        if (i.type === 'list') {
            const rows = [];
            for (const s of (i.action && i.action.sections) || []) {
                for (const r of s.rows || []) rows.push({ id: r.id, title: r.title, section: s.title });
            }
            return { kind: 'list', text: bodyText, header: i.header && i.header.text || '', button: i.action && i.action.button || '', rows };
        }
        if (i.type === 'flow') {
            return { kind: 'flow', text: bodyText };
        }
        return { kind: `interactive:${i.type}`, text: bodyText };
    }
    return { kind: String(type || 'unknown'), text: '' };
}

/** Render one recorded outbound message as chat lines. */
function renderOutbound(rec) {
    const s = rec.summary || summarizeOutbound(rec.body);
    const lines = [];
    const head = `  BOT -> ${rec.to} [${s.kind}]`;
    lines.push(head);
    for (const l of String(s.text || '').split('\n')) lines.push(`      ${l}`);
    if (s.kind === 'buttons') {
        lines.push(`      buttons: ${s.buttons.map((b) => `[${b.title}](id=${b.id})`).join(' ')}`);
    }
    if (s.kind === 'list') {
        if (s.header) lines.push(`      header: ${s.header}`);
        lines.push(`      list button: ${s.button}`);
        for (const r of s.rows) lines.push(`      - ${r.title} (id=${r.id})`);
    }
    if (s.kind === 'document') {
        lines.push(`      file: ${s.filename}`);
        lines.push(`      link: ${s.link}`);
    }
    return lines.join('\n');
}

function renderInbound(step) {
    const label = step.kind === 'text' ? `"${step.text}"` : `[${step.kind} reply] ${step.title || step.id} (id=${step.id})`;
    return `USER ${step.from} -> ${label}`;
}

module.exports = {
    DEFAULTS,
    assertLocalUrl,
    readDotEnv,
    resolveAppSecret,
    digits,
    randomId,
    buildInboundPayload,
    sign,
    summarizeOutbound,
    renderOutbound,
    renderInbound,
};
