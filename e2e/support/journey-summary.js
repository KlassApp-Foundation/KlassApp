/**
 * Writes/reads JSON journey summaries so onboarding-parity.spec.js
 * can compare the two onboarding paths.
 */
const fs = require('node:fs');
const path = require('node:path');

const OUT_DIR = path.resolve(__dirname, '..', '..', 'tmp', 'e2e-output');

function ensureDir() {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}

function writeJourney(label, summary) {
    ensureDir();
    const file = path.join(OUT_DIR, `${label}-${summary.stamp || Date.now()}.json`);
    fs.writeFileSync(file, JSON.stringify(summary, null, 2));
    return file;
}

function readLatestJourney(label) {
    ensureDir();
    const files = fs.readdirSync(OUT_DIR)
        .filter(f => f.startsWith(`${label}-`) && f.endsWith('.json'))
        .sort()
        .reverse();
    if (files.length === 0) {
        throw new Error(`No journey summary found for label: ${label}`);
    }
    return JSON.parse(fs.readFileSync(path.join(OUT_DIR, files[0]), 'utf8'));
}

module.exports = { writeJourney, readLatestJourney };
