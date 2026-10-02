// Per-journey artifact writer: summary JSON, conversation markdown, health, outcomes.
const fs = require('node:fs');
const path = require('node:path');

const ROOT = path.join(__dirname, '..', 'artifacts');

function dirFor(journeyId, stamp) {
    const d = path.join(ROOT, `${journeyId}-${stamp}`);
    fs.mkdirSync(d, { recursive: true });
    return d;
}

function conversationMarkdown(entries) {
    return ['# Toshi conversation (scripted answers, recorded)', '']
        .concat(entries.map((e) => `- turn ${e.turn}: sent: ${JSON.stringify(e.sent)}`))
        .join('\n');
}

function save({ journeyId, stamp, summary, conversation, health, outcome }) {
    const d = dirFor(journeyId, stamp);
    fs.writeFileSync(path.join(d, 'summary.json'), JSON.stringify(summary, null, 2));
    if (conversation) fs.writeFileSync(path.join(d, 'conversation.md'), conversationMarkdown(conversation));
    if (health) fs.writeFileSync(path.join(d, 'health.json'), JSON.stringify(health, null, 2));
    if (outcome) fs.writeFileSync(path.join(d, 'outcomes.json'), JSON.stringify(outcome, null, 2));
    return d;
}

module.exports = { save, dirFor };
