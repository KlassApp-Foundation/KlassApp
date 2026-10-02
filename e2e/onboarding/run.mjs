#!/usr/bin/env node
// Onboarding suite launcher.
//
//   npm run e2e:onboarding -- --journey=primary-manual --headed   (visible, slow)
//   npm run e2e:onboarding -- --ui                                (Playwright UI mode)
//   npm run e2e:onboarding                                        (all journeys, both widths)
//
// Extra args pass through to `playwright test` (e.g. --project=desktop-1280).
import { spawnSync } from 'node:child_process';

const argv = process.argv.slice(2);
let journey = null;
let headed = false;
let ui = false;
let slowmo = null;
const passthrough = [];

for (const a of argv) {
    if (a.startsWith('--journey=')) journey = a.split('=')[1];
    else if (a === '--headed') headed = true;
    else if (a === '--ui') ui = true;
    else if (a.startsWith('--slowmo=')) slowmo = a.split('=')[1];
    else passthrough.push(a);
}

const env = { ...process.env };
env.E2E_HEADED = headed ? '1' : '';
env.E2E_SLOWMO = String(slowmo ?? (headed ? 400 : 0));

const args = ['playwright', 'test', '-c', 'e2e/onboarding/playwright.config.js'];
if (journey) args.push('--grep', `@${journey}`);
if (ui) args.push('--ui');
args.push(...passthrough);

if (!ui) {
    // eslint-disable-next-line no-console
    console.log(`[e2e:onboarding] ${journey ? `journey=${journey}` : 'ALL journeys'} ${headed ? '(headed, slowMo ' + env.E2E_SLOWMO + 'ms)' : '(headless)'}`);
    // eslint-disable-next-line no-console
    console.log('[e2e:onboarding] report: npx playwright show-report e2e/onboarding/report');
}

const res = spawnSync('npx', args, { stdio: 'inherit', env });
process.exit(res.status ?? 1);
