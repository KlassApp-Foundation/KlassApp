// Node wrapper for stg_bridge.py — run PHP on staging (read-only checks + test-data writes).
const { execFileSync, spawnSync } = require('node:child_process');
const path = require('node:path');

function sleepMs(ms) {
    spawnSync('sleep', [String(Math.max(1, Math.ceil(ms / 1000)))]);
}

const PY = process.env.E2E_PYTHON || 'python3';
const BRIDGE = path.join(__dirname, 'stg_bridge.py');
const DEFAULT_TIMEOUT_MS = Number(process.env.E2E_BRIDGE_TIMEOUT_MS || 12 * 60_000);
const RETRIES = Number(process.env.E2E_BRIDGE_NODE_RETRIES || 3);

function runStagingPhp(php, { timeoutMs = DEFAULT_TIMEOUT_MS } = {}) {
    let lastErr = null;
    for (let attempt = 1; attempt <= RETRIES; attempt++) {
        try {
            return execFileSync(PY, [BRIDGE], {
                input: php,
                encoding: 'utf8',
                timeout: timeoutMs,
                maxBuffer: 16 * 1024 * 1024,
                env: process.env,
            });
        } catch (e) {
            lastErr = e;
            const detail = [
                e.message,
                e.stdout ? String(e.stdout).slice(-400) : '',
                e.stderr ? String(e.stderr).slice(-400) : '',
            ].filter(Boolean).join('\n');
            console.error(`[stg-bridge] attempt ${attempt}/${RETRIES} failed:\n${detail.slice(0, 600)}`);
            if (attempt < RETRIES) {
                // Give hibernating staging time to wake.
                sleepMs(Math.min(10_000 * attempt, 30_000));
            }
        }
    }
    const err = new Error(
        'stg-bridge: all retries failed: '
        + (lastErr && lastErr.message ? lastErr.message : String(lastErr))
        + (lastErr && lastErr.stderr ? '\n' + String(lastErr.stderr).slice(-400) : ''),
    );
    throw err;
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

/** Cheap wake ping so the first real bridge call is less likely to time out. */
function wakeStaging() {
    try {
        runStagingJson('echo "<<<E2E-JSON>>>" . json_encode(["awake" => true, "t" => time()]);');
        return true;
    } catch (e) {
        console.error('[stg-bridge] wake failed: ' + String(e.message || e).slice(0, 200));
        return false;
    }
}

module.exports = { runStagingPhp, runStagingJson, wakeStaging };
