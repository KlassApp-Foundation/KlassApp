# E2E onboarding suite (staging only)

Six journeys: **3 school types × 2 modes** (manual wizard, Toshi-assisted), each starting
from the public sign-up page and ending on the new school's admin dashboard, fully set up.

**School types**
| id | type | category | classes created |
|---|---|---|---|
| `primary` | Nursery & Primary | `primary_nursery` | Baby/Middle/Top + Primary One–Seven |
| `olevel` | Secondary O-Level | `o_level` | Senior One–Four |
| `oalevel` | Secondary O & A-Level | `o_a_level` | Senior One–Six (streams used for combinations, e.g. PCM) |

**Journey ids (for `--journey=`)**:
`primary-manual`, `primary-toshi`, `olevel-manual`, `olevel-toshi`, `oalevel-manual`, `oalevel-toshi`

## Commands

```bash
# Play one journey slowly in a visible browser (headed, slowMo):
npm run e2e:onboarding -- --headed --journey=primary-manual

# Playwright UI mode — pick a journey, step through it, watch each click:
npm run e2e:onboarding -- --ui

# Full run: all 6 journeys × both widths (1280 desktop + 375 mobile):
npm run e2e:onboarding

# One journey, one width:
npm run e2e:onboarding -- --journey=oalevel-toshi --project=desktop-1280

# Open the HTML report after a run:
npx playwright show-report e2e/onboarding/report
```

Every run records **video, per-step screenshots and a trace** (Playwright defaults in this
config). Artifacts (per journey): `e2e/onboarding/artifacts/<journey>-<stamp>/` —
`summary.json`, `conversation.md` (Toshi), `health.json`, `outcomes.json`.

## Safety rules baked in

- **Staging only.** The config hard-guards the base URL (`test.klassapp.xyz` or the staging
  Laravel Cloud domain); anything else aborts before a single test runs.
- **No real people contacted.** Test accounts use `example.com` emails and non-real
  `070…` phone numbers. Staging's mail driver is `log` (no mail leaves the app). Email
  OTP is read from Cloud runtime logs (`klassapp.email_verification_code` marker), with
  Commands re-issue as fallback — **never** by pre-setting `email_verified`. WhatsApp
  verification codes are produced **in-UI** by design.
- **Nightwatch note:** staging `LOG_STACK` includes `nightwatch`; agent logs show
  `Ingest failed: No authentication details` / `403 Exceeded quota`. That is Nightwatch
  APM ingest, not Laravel Cloud API auth. OTP sandbox uses `laravel-cloud-socket` + the
  structured marker above. Rasta: fix Nightwatch quota/token or drop `nightwatch` from
  staging `LOG_STACK` if those errors should stop.
- **Test data is marked.** Every school is named `E2E <type> <mode> <date>` and flagged
  `is_test`; the suite can purge its own schools (see below).
- The only direct DB access is a small staging bridge (`lib/stg_bridge.py`) that runs
  tinker snippets against the **staging** environment via the Laravel Cloud API with a
  staging env-id guard. It needs `~/.openclaw-autoclaw/workspace/.openclaw/tmp/kc.py`
  (or set `E2E_KC_PATH`).

## Cleaning up E2E schools

```bash
# On staging (via the bridge or the Cloud console):
php artisan test:purge-schools --school=<id>            # dry run, prints inventory
php artisan test:purge-schools --school=<id> --force    # delete
```

Refuses schools that are not `is_test=1` or not named `E2E …`.

## Layout

```
e2e/onboarding/
  playwright.config.js   # staging guard, 2 viewports, video/trace/report
  run.mjs                # command launcher (--journey / --headed / --ui / passthrough)
  lib/                   # signup battery, wizard driver, toshi driver, outcomes, health, bridge
  journeys/              # manual.spec.js + toshi.spec.js (tagged @<journey-id>)
  artifacts/             # per-run output (gitignored)
  report/                # HTML report (gitignored)
```
