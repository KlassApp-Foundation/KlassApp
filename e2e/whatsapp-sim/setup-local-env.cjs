#!/usr/bin/env node
'use strict';

/**
 * Points a LOCAL KlassApp checkout at the simulator by writing these keys to
 * its .env (the .env is git-ignored; nothing here is committed):
 *
 *   WHATSAPP_GRAPH_API_BASE_URL       http://127.0.0.1:<sim port>
 *   WHATSAPP_BUSINESS_API_TOKEN       sim-local-not-a-real-token (clearly fake)
 *   WHATSAPP_BUSINESS_PHONE_NUMBER_ID 100000000000001 (fake)
 *   WHATSAPP_APP_SECRET               random value generated now, if the key is empty
 *   WHATSAPP_BUSINESS_VERIFY_TOKEN    random value generated now, if the key is empty
 *   WHATSAPP_VERIFY_SIGNATURE         true (inbound webhooks must be signed)
 *   WHATSAPP_REPLIES_ENABLED          true
 *   WHATSAPP_DEMO_PARENT_USER_ID      first parent of the local demo school (with --demo-parent auto)
 *
 * Refuses unless APP_ENV=local. Prints key names only, never values of secrets.
 *
 *   node e2e/whatsapp-sim/setup-local-env.cjs [--app-dir .] [--sim-port 9123] [--demo-parent auto|<id>]
 */

const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const lib = require('./lib.cjs');

const args = {};
const argv = process.argv.slice(2);
for (let i = 0; i < argv.length; i++) {
    if (argv[i].startsWith('--')) { args[argv[i].slice(2)] = argv[i + 1]; i++; }
}

const appDir = path.resolve(args['app-dir'] || process.cwd());
const envFile = path.join(appDir, '.env');
if (!fs.existsSync(envFile)) {
    console.error(`No .env in ${appDir} (cp .env.example .env && php artisan key:generate first).`);
    process.exit(1);
}

const current = lib.readDotEnv(envFile);
if (current.APP_ENV !== 'local') {
    console.error(`Refusing: APP_ENV is "${current.APP_ENV || ''}" in ${envFile}; this kit is for APP_ENV=local only.`);
    process.exit(1);
}

try {
    execFileSync('git', ['-C', appDir, 'ls-files', '--error-unmatch', '.env'], { stdio: 'ignore' });
    console.error('Refusing: .env is tracked by git here.');
    process.exit(1);
} catch (e) { /* not tracked: good */ }

const simPort = Number(args['sim-port'] || lib.DEFAULTS.simPort);
const updates = {
    WHATSAPP_GRAPH_API_BASE_URL: `http://127.0.0.1:${simPort}`,
    WHATSAPP_BUSINESS_API_TOKEN: 'sim-local-not-a-real-token',
    WHATSAPP_BUSINESS_PHONE_NUMBER_ID: '100000000000001',
    WHATSAPP_VERIFY_SIGNATURE: 'true',
    WHATSAPP_REPLIES_ENABLED: 'true',
};
if (!current.WHATSAPP_APP_SECRET) {
    updates.WHATSAPP_APP_SECRET = crypto.randomBytes(32).toString('hex');
}
if (!current.WHATSAPP_BUSINESS_VERIFY_TOKEN) {
    updates.WHATSAPP_BUSINESS_VERIFY_TOKEN = crypto.randomBytes(16).toString('hex');
}

const demoParent = args['demo-parent'] || 'auto';
if (demoParent === 'auto') {
    try {
        const out = execFileSync('php', ['artisan', 'tinker', '--execute', `
            $sid = \\App\\Models\\School::where('is_demo', 1)->orderBy('id')->value('id');
            $id = $sid ? \\App\\Models\\User::where('usergroup_id', 7)->where('school_id', $sid)->whereHas('children')->orderBy('id')->value('id') : null;
            echo 'SIMJSON:'.json_encode(['id' => $id]).PHP_EOL;
        `], { cwd: appDir, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
        const m = out.match(/SIMJSON:(.*)$/m);
        const id = m && JSON.parse(m[1]).id;
        if (id) updates.WHATSAPP_DEMO_PARENT_USER_ID = String(id);
        else console.warn('No demo school parent found; seed a demo school first (see docs/ops/LOCAL-WHATSAPP-TESTING.md). WHATSAPP_DEMO_PARENT_USER_ID left unchanged.');
    } catch (e) {
        console.warn(`Could not look up a demo parent (${e.message.split('\n')[0]}); WHATSAPP_DEMO_PARENT_USER_ID left unchanged.`);
    }
} else if (/^\d+$/.test(demoParent)) {
    updates.WHATSAPP_DEMO_PARENT_USER_ID = demoParent;
}

let text = fs.readFileSync(envFile, 'utf8');
for (const [k, v] of Object.entries(updates)) {
    const re = new RegExp(`^${k}=.*$`, 'm');
    text = re.test(text) ? text.replace(re, `${k}=${v}`) : `${text.replace(/\n?$/, '\n')}${k}=${v}\n`;
}
fs.writeFileSync(envFile, text);

const secretKeys = new Set(['WHATSAPP_APP_SECRET', 'WHATSAPP_BUSINESS_VERIFY_TOKEN', 'WHATSAPP_BUSINESS_API_TOKEN']);
console.log(`Updated ${envFile}:`);
for (const [k, v] of Object.entries(updates)) {
    console.log(`  ${k}=${secretKeys.has(k) ? '(set, not shown)' : v}`);
}
if (current.APP_URL && !/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?/.test(current.APP_URL)) {
    console.warn('APP_URL is not a loopback URL; report card links will not be reachable by the simulator. Use APP_URL=http://127.0.0.1:8010.');
}
console.log('If config is cached, run: php artisan config:clear');
