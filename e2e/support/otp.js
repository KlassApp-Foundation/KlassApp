/**
 * OTP retrieval strategies for e2e tests.
 *
 * Production sends real email OTPs after /register. There is no
 * test-mode bypass. Current available strategies:
 *
 *   manual  — pauses with page.pause(); human must read the OTP and type it
 *   ssh     — queries the `authentications` table on production via SSH
 *             (requires KLASSAPP_SSH_KEY + KLASSAPP_SSH_HOST env vars)
 *   none    — throws; only valid when OTP is skipped entirely
 */

const OTP_STRATEGY = process.env.OTP_STRATEGY || 'ssh';
const SSH_KEY = process.env.KLASSAPP_SSH_KEY || `${process.env.HOME}/.ssh/id_ed25519_do`;
const SSH_HOST = process.env.KLASSAPP_SSH_HOST || 'root@46.101.111.131';

const { execFileSync } = require('node:child_process');

function shellQuote(s) {
    return `'${String(s).replace(/'/g, `'\\''`)}'`;
}

function sshTinker(php) {
    const oneLine = php.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
    return execFileSync('ssh', [
        '-i', SSH_KEY,
        '-o', 'StrictHostKeyChecking=no',
        '-o', 'ConnectTimeout=20',
        SSH_HOST,
        `docker exec sms-app php artisan tinker --execute ${shellQuote(oneLine)}`,
    ], { encoding: 'utf8', timeout: 30_000, maxBuffer: 1 * 1024 * 1024 });
}

/**
 * @param {{ page: import('@playwright/test').Page, email: string }} opts
 * @returns {Promise<string>} the OTP code
 */
async function getEmailOTP({ page, email }) {
    switch (OTP_STRATEGY) {
        case 'manual': {
            console.log(`\n[OTP] Waiting for email OTP for ${email}. Check mailbox, then resume Playwright inspector.`);
            await page.pause();
            // After resume, we expect the caller to have the OTP.
            // This path requires human intervention.
            throw new Error(
                'OTP_STRATEGY=manual: human must provide the OTP. ' +
                'Caller should catch this and prompt.'
            );
        }
        case 'ssh': {
            // Query the authentications table for the most recent OTP for this email
            const out = sshTinker(`
                $auth = \\\\Illuminate\\\\Support\\\\Facades\\\\DB::table('authentications')
                    ->where('email', ${JSON.stringify(email)})
                    ->orderByDesc('created_at')
                    ->first();
                echo $auth ? $auth->otp : 'NONE';
            `).trim();
            if (out === 'NONE' || out === '') {
                throw new Error(`No OTP found in authentications table for ${email}`);
            }
            return out;
        }
        case 'none':
            throw new Error('OTP_STRATEGY=none: no OTP available, test cannot proceed');
        default:
            throw new Error(`Unknown OTP_STRATEGY: ${OTP_STRATEGY}`);
    }
}

module.exports = { getEmailOTP, OTP_STRATEGY };
