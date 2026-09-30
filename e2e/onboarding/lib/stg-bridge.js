// Node wrapper for stg_bridge.py — run PHP on staging (read-only checks + test-data writes).
const { execFileSync } = require('node:child_process');
const path = require('node:path');

const PY = process.env.E2E_PYTHON || 'python3';
const BRIDGE = path.join(__dirname, 'stg_bridge.py');

function runStagingPhp(php, { timeoutMs = 6 * 60_000 } = {}) {
    return execFileSync(PY, [BRIDGE], {
        input: php,
        encoding: 'utf8',
        timeout: timeoutMs,
        maxBuffer: 16 * 1024 * 1024,
        env: process.env,
    });
}

function runStagingJson(php, opts) {
    const out = runStagingPhp(php, opts);
    const marker = '<<<E2E-JSON>>>';
    const idx = out.lastIndexOf(marker);
    if (idx === -1) {
        throw new Error('stg-bridge: no JSON sentinel in output:\n' + out.slice(-600));
    }
    return JSON.parse(out.slice(idx + marker.length).trim());
}

module.exports = { runStagingPhp, runStagingJson };
