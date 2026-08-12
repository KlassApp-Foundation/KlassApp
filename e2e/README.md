# KlassApp E2E Test Suite

## Files

```
e2e/
  prod-uganda-onboarding.spec.js   — legacy: single Uganda signup+onboarding flow
  signup-onboarding.spec.js        — legacy: local signup + Toshi flow
  global-setup.js                  — Playwright global setup (seeds local DB)
  manual-selfserve-onboarding.spec.js — NEW: self-serve /register → manual admin onboarding
  toshi-onboarding.spec.js           — NEW: SSH-created school → Toshi chat onboarding
  onboarding-parity.spec.js           — NEW: compares the two journey JSON summaries
  support/
    dummy-data.js        — generates run-unique TEST-E2E- prefixed data
    otp.js               — OTP retrieval (ssh strategy queries authentications table)
    journey-summary.js   — writes/reads JSON summaries for parity comparison
    report-generation.js — downloads report card PDF
```

## Running

All against production:
```
PLAYWRIGHT_BASE_URL=https://klassapp.xyz npx playwright test --project=selfserve
PLAYWRIGHT_BASE_URL=https://klassapp.xyz npx playwright test --project=toshi
PLAYWRIGHT_BASE_URL=https://klassapp.xyz npx playwright test --project=parity
```

Or run all three (selfserve + toshi first, then parity):
```
PLAYWRIGHT_BASE_URL=https://klassapp.xyz npx playwright test --project=selfserve --project=toshi --project=parity
```

Requirements:
- SSH key at `~/.ssh/id_ed25519_do` (or set `KLASSAPP_SSH_KEY`)
- SSH host root@46.101.111.131 (or set `KLASSAPP_SSH_HOST`)
- OTP: SSH strategy queries the `authentications` table directly

## Naming convention

All entities use `TEST-E2E-` prefix for schools and `@klassapp.test` emails.
Each run cleans up after itself via SSH delete.
