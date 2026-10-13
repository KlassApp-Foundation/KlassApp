#!/usr/bin/env node
/**
 * Final-gate runner for the staging onboarding suite.
 *
 * Fourteen runs: primary / olevel / oalevel, each as manual and Toshi, plus
 * the MVP journey, at desktop-1280 and mobile-375. One command. Staging only
 * (the Playwright config refuses any other host).
 *
 * After the runs it collects throwaway school ids from this run's summaries
 * and purges them: dry run first, then --force, and only when the dry run
 * says the school would be purged.
 *
 *   node e2e/onboarding/final-gate.mjs --pin <sha>            # run + purge
 *   node e2e/onboarding/final-gate.mjs --pin <sha> --dry-run  # print the plan
 *
 * The worktree must be the pinned commit (HEAD starts with --pin).
 */
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(HERE, '..', '..');
const JOURNEYS = [
    'primary-manual',
    'olevel-manual',
    'oalevel-manual',
    'primary-toshi',
    'olevel-toshi',
    'oalevel-toshi',
    'mvp-endtoend',
];
const PROJECTS = ['desktop-1280', 'mobile-375'];
const WIDTH = { 'desktop-1280': '1280', 'mobile-375': '375' };

function arg(name) {
    const hit = process.argv.find((a) => a.startsWith(`--${name}=`));
    if (hit) return hit.slice(name.length + 3);
    const index = process.argv.indexOf(`--${name}`);
    const next = index >= 0 ? process.argv[index + 1] : '';
    return next && !next.startsWith('--') ? next : null;
}

function git(args) {
    const res = spawnSync('git', args, { cwd: ROOT, encoding: 'utf8' });
    if (res.status !== 0) {
        console.error(res.stderr || res.stdout);
        process.exit(1);
    }
    return (res.stdout || '').trim();
}

const pin = arg('pin');
const dry = process.argv.includes('--dry-run');
const head = git(['rev-parse', 'HEAD']);
if (!pin) {
    console.error('final-gate: pass --pin=<sha> for the commit this worktree is pinned to');
    process.exit(1);
}
if (!head.startsWith(pin)) {
    console.error(`final-gate: HEAD ${head} is not pinned to ${pin}`);
    process.exit(1);
}

const runs = [];
for (const journey of JOURNEYS) {
    for (const project of PROJECTS) {
        runs.push({ journey, project, width: WIDTH[project] });
    }
}

console.log(`final-gate: pin ${head}`);
console.log(`final-gate: ${runs.length} runs on https://test.klassapp.xyz`);
for (const run of runs) {
    console.log(`  - ${run.journey} @ ${run.width}`);
}
if (dry) {
    console.log('final-gate: dry run — no browser, no purge');
    process.exit(0);
}

const started = Date.now();
const logPath = path.join(HERE, 'artifacts', `final-gate-${started}.log`);
fs.mkdirSync(path.dirname(logPath), { recursive: true });

const playwright = spawnSync('npx', [
    'playwright', 'test',
    '-c', 'e2e/onboarding/playwright.config.js',
    '--reporter=list',
], {
    cwd: ROOT,
    encoding: 'utf8',
    env: { ...process.env, E2E_BASE_URL: 'https://test.klassapp.xyz' },
    maxBuffer: 64 * 1024 * 1024,
});
const output = `${playwright.stdout || ''}\n${playwright.stderr || ''}`;
fs.writeFileSync(logPath, output);
process.stdout.write(playwright.stdout || '');
process.stderr.write(playwright.stderr || '');

const results = [];
for (const run of runs) {
    const re = new RegExp(
        `([✓✘x])\\s+\\d+\\s+\\[${run.project}\\].*@${run.journey}\\b`,
    );
    const match = output.match(re);
    let status = 'missing';
    if (match) status = match[1] === '✓' ? 'pass' : 'fail';
    results.push({ ...run, status });
}

function walk(dir, found) {
    if (!fs.existsSync(dir)) return;
    for (const name of fs.readdirSync(dir)) {
        const full = path.join(dir, name);
        const stat = fs.statSync(full);
        if (stat.isDirectory()) walk(full, found);
        else if (name.startsWith('summary') && name.endsWith('.json') && stat.mtimeMs >= started - 5000) {
            found.push(full);
        }
    }
}

const summaries = [];
walk(path.join(HERE, 'artifacts'), summaries);
walk(path.join(ROOT, 'e2e', 'artifacts', 'mvp'), summaries);
const schoolIds = new Set();
for (const file of summaries) {
    try {
        const data = JSON.parse(fs.readFileSync(file, 'utf8'));
        const id = Number(data.schoolId || data.school_id || 0);
        if (id > 0) schoolIds.add(id);
    } catch {
        // A partial summary is not a purge target.
    }
}

function bridge(php) {
    const res = spawnSync('python3', [path.join(HERE, 'lib', 'stg_bridge.py')], {
        cwd: ROOT,
        input: php,
        encoding: 'utf8',
        maxBuffer: 8 * 1024 * 1024,
    });
    const text = `${res.stdout || ''}\n${res.stderr || ''}`;
    const marker = text.split('<<<E2E-JSON>>>').pop() || '';
    let json = null;
    try {
        json = JSON.parse(marker.trim().split('\n')[0]);
    } catch {
        json = null;
    }
    return { status: res.status, text, json };
}

const purged = [];
for (const id of [...schoolIds].sort((a, b) => a - b)) {
    const dryPhp = `
\\Illuminate\\Support\\Facades\\Artisan::call('test:purge-schools', ['--school' => [${id}]]);
$out = \\Illuminate\\Support\\Facades\\Artisan::output();
echo '<<<E2E-JSON>>>'.json_encode(['id'=>${id},'out'=>substr($out,0,500),'would'=>str_contains($out,'would purge'),'refused'=>str_contains(strtolower($out),'refus')]);
`;
    const dryRes = bridge(dryPhp);
    const info = dryRes.json || {};
    if (!info.would || info.refused) {
        purged.push({ id, action: 'kept', reason: 'dry run did not say would purge' });
        console.log(`final-gate: school ${id} kept (dry run did not authorise a delete)`);
        continue;
    }
    const forcePhp = `
\\Illuminate\\Support\\Facades\\Artisan::call('test:purge-schools', ['--school' => [${id}], '--force' => true]);
$out = \\Illuminate\\Support\\Facades\\Artisan::output();
$left = \\App\\Models\\School::query()->where('id', ${id})->exists();
echo '<<<E2E-JSON>>>'.json_encode(['id'=>${id},'gone'=>!$left,'tail'=>substr($out,-180)]);
`;
    const forceRes = bridge(forcePhp);
    purged.push({ id, action: forceRes.json && forceRes.json.gone ? 'purged' : 'purge-failed' });
    console.log(`final-gate: school ${id} ${forceRes.json && forceRes.json.gone ? 'purged' : 'purge failed'}`);
}

const report = {
    pin: head,
    started,
    playwrightExit: playwright.status,
    results,
    passed: results.filter((r) => r.status === 'pass').length,
    failed: results.filter((r) => r.status !== 'pass').length,
    schools: purged,
    log: logPath,
};
const reportPath = path.join(HERE, 'artifacts', `final-gate-${started}.json`);
fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
console.log(`final-gate: ${report.passed} passed, ${report.failed} not passed`);
console.log(`final-gate: report ${reportPath}`);
for (const row of results) {
    console.log(`  ${row.status.toUpperCase()}  ${row.journey} @ ${row.width}`);
}
process.exit(report.failed === 0 && playwright.status === 0 ? 0 : 1);
