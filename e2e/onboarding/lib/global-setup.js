// Hard guard: this suite must only ever run against the staging hosts.
const ALLOWED = [
    /^https:\/\/test\.klassapp\.xyz$/i,
    /^https:\/\/klassapp-staging-[a-z0-9]+\.laravel\.cloud$/i,
];
const { wakeStaging } = require('./stg-bridge');

module.exports = async () => {
    const base = process.env.E2E_BASE_URL || 'https://test.klassapp.xyz';
    if (!ALLOWED.some((re) => re.test(base))) {
        throw new Error(
            `[e2e:onboarding] refuses to run against a non-staging host: ${base} ` +
            '(staging-only suite; set E2E_BASE_URL to test.klassapp.xyz).'
        );
    }
    // eslint-disable-next-line no-console
    console.log(`[e2e:onboarding] STAGING target: ${base}`);
    // Wake hibernating staging before the first signup/verify bridge call.
    wakeStaging();
};
