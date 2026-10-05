# Production Deploy Checklist

> **Status**: ready-to-use runbook, re-verified 2026-10-06 (night shift) against the
> pre-deploy tip of `main`; production still runs `dabfb80c`. Production runs Laravel
> Cloud, push-to-deploy **off** — merging to `main` does **not** ship; a deploy is an
> explicit release you start and watch.
>
> No secrets in this file. Every credential lives in Doppler (`CLOUD_AGENT_TOOLING`
> for the Cloud API; backup password held by the owner). Environment-variable names
> and intended values are listed; values are never printed to logs or PRs.
>
> Standing rules that bind every step: no raw production data writes (rule #1);
> backup + verify before any production data write (rule #2); never deploy from an
> arbitrary checkout (rule #15); merging to `main` is not shipping (rule #24).

---

## 1. Pre-deploy

1. **CI green on the tip you are about to ship.** All three checks
   (`test-guard`, `check`, `scan`) must be SUCCESS on the exact `main` SHA.
   `gh api repos/KlassApp-Foundation/KlassApp/commits/<sha>/check-runs` — do not
   ship a SHA that has never had a green run.
2. **Staging first, same SHA.** Staging auto-deploys every merge (push-to-deploy
   on). Confirm the latest staging deployment is `deployment.succeeded` on the
   same commit you intend to ship, and run the smoke battery below on staging
   before pointing the same release at production.
3. **Migrations reviewed.** `php artisan migrate:status` on production must show
   no *pending* migrations before the deploy (verified 2026-10-05: 0 pending).
   The deploy itself will run the migrations main has added since production's
   current SHA — see §2 for the classified list. If anything in §2 looks wrong
   for the current production data, stop and review before deploying.
4. **Database snapshot.** Take one immediately before the deploy (run a
   `backup:run` on production, or use the Cloud-provided snapshot if enabled).
   Record its identifier. The nightly spatie backup (02:00, Hetzner object
   storage, gzip) is the safety net, but a deploy-day snapshot minimises the
   rollback window.
5. **Backup integrity.** Confirm you can open the latest backup **only with its
   password** (never store the password in the repo, Doppler holds it or the
   owner does). A backup you cannot open is not a backup.
6. **Backup alerts armed.** `BACKUP_WHATSAPP_PHONE` must be set on production so
   a failed or stale backup alerts out (WhatsApp + email) instead of failing
   silently.
7. **`.env` backed up separately** (the spatie backup deliberately excludes it).
8. **Environment variables reviewed** against the table in §4 — especially any
   marked "must NOT copy from staging".

## 2. Migrations production will run (classified)

Re-verified 2026-10-06 (night shift) against the pre-deploy tip of `main` with
production on `dabfb80c`: **12 new migrations will run; none are destructive** (no
drops of tables/columns, no row deletions). The list below is final for this
deploy — re-run `migrate:status` on production at deploy time and stop if the
count differs. Two migrations change existing data — flagged below.

**The database snapshot is the rollback for the data migrations.** A code revert
cannot undo a data migration; the §1.4 snapshot is the pre-deploy state to
restore for both flagged rows if their effect on production data is ever wrong:
`map_school_student_size_to_new_buckets` (one-way wording map; `down()` is
intentionally empty) and `backfill_existing_schools_toshi_safe_defaults` (the
existing-schools Toshi reset; also reversible via its own capture — see below).

There is **no gate backfill migration**: the verified-email sign-in gate does its
backfill job in code (`EmailVerificationGate` exempts accounts created before the
cutoff), so deploying it writes nothing.

| Migration | Kind | Notes |
|---|---|---|
| `2026_09_26_013906_create_teacher_invites_table` | structure | new table + FKs |
| `2026_09_28_010000_create_demo_requests_table` | structure | new table |
| `2026_09_28_020000_create_co_admin_invites_table` | structure | new table + FKs |
| `2026_09_29_000000_add_ugandan_admission_fields_to_admissions_table` | structure | adds nullable columns |
| `2026_09_29_000002_make_legacy_admission_columns_nullable` | structure | loosens legacy columns to nullable (no values changed) |
| `2026_09_30_000001_add_attempts_and_locked_until_to_authentications` | structure | sign-up code lockout columns |
| `2026_09_30_150000_map_school_student_size_to_new_buckets` | **data** | rewrites legacy `schools.student_size` labels to the new buckets (one-way; `down()` empty) |
| `2026_10_01_210214_add_toshi_mode_to_schools_table` | **data** | adds `toshi_mode`; backfills `assistant`/`preview` per existing `toshi_enabled` |
| `2026_10_01_221239_change_schools_toshi_mode_default_to_onboarding` | structure | default change only |
| `2026_10_02_183000_backfill_existing_schools_toshi_safe_defaults` | **data — the existing-schools Toshi reset** | flips every school currently in `assistant` back to `preview` + `toshi_enabled=0` (AI is opt-in per school, AGENTS rule #33); before flipping it captures school ids plus old `toshi_enabled`/`toshi_mode` only: to `storage/logs/toshi-backfill-restore-<date>.json` (feeds `down()`) **and** to the application log via `Log::info` (Cloud log stream), so the capture survives on a deployed container |
| `2026_10_03_023019_add_onboarding_finish_and_skipped_steps_to_schools_table` | structure | adds nullable columns |
| `2026_10_06_000001_create_user_preferences_table` | structure | creates the per-user preference store behind the dashboard v2 setup-banner dismissal (INT UNSIGNED FK to users.id) |

Also on the path: `2026_09_22_010000_add_is_demo_to_schools_and_seed_demo_data`
was **modified** since production's SHA (demo seeding stripped, schema-only) —
it will **not** re-run (Laravel tracks by filename); the change only affects
fresh installs. No action needed.

**At deploy time, watch the log stream** for `[toshi backfill] capture before
reset` (per-school ids and old values) and the summary line `[toshi backfill]
flipped N school(s): assistant=A, flag-only=B`. If N is larger than expected,
the captured values (log stream or the restore file) allow an exact reversal.

## 3. Deploy

1. Deploy from the dedicated main worktree at the reviewed SHA (rule #15).
2. Trigger the release with the empty-body deploy POST to the production
   environment (token: Doppler `CLOUD_AGENT_TOOLING`; pattern in
   `knowledge.md` → "Triggering a real deployment"). The POST deploys the
   environment's latest commit — confirm the worktree matches `origin/main`
   first.
3. Poll `GET /deployments/{id}` until `deployment.succeeded` (2-minute cadence).
   A failed deploy: read the deployment logs before anything else; do not
   re-trigger blind.
4. Verify the live release serves the SHA you shipped (probe a version marker or
   the deployment's `commit_hash`).
5. Watch the first minutes of the log stream: the toshi-backfill line (§2), no
   new exceptions, scheduler/queue alive.

## 4. Environment variables (names + intended values only)

| Variable | Production value | Why |
|---|---|---|
| `TOSHI_DEFAULT_MODE` | `preview` | New schools start with the Coming-soon panel; `assistant`/`onboarding` are never defaults (AGENTS rule #33). Regression test: `NoSchoolGetsAiByDefaultTest`. |
| `TOSHI_ONBOARDING_V2` | unset / `false` | Toshi v2 onboarding chat is a staging-only feature until soft-launch sign-off. |
| `ROBOTS_NOINDEX` | unset / `false` | Only staging should noindex. `true` in production would de-index the live site. |
| `EMAIL_VERIFICATION_CUTOFF` | **unset (blank)** | Blank keeps the built-in default (2026-10-05 00:00 UTC) — behaviour identical to the merged gate. Set it **only** if Rasta explicitly decides to move the boundary; value would be an ISO-8601 UTC instant. Must NOT be copied from staging (staging leaves it unset too — blank everywhere today). |
| `BACKUP_WHATSAPP_PHONE` | set (E.164 number) | Backup failure alerts must reach a human. |
| `LEADS_EMAIL` / `DEMO_BOOKING_URL` | set as configured | Talk-to-sales lead capture destination. |
| `WHATSAPP_VERIFY_SIGNATURE` | `true` | Reject unverified WhatsApp webhooks. |
| `FILESYSTEM_DISK` | cloud object storage | Production uploads go to the object bucket, never `local`. **Do not copy the staging value.** |

General rule: **staging's non-listed env values are not production's.** Copy
individual variables deliberately, one at a time, never a bulk staging→production
env copy. Never read, print or paste secret values — if a value must change,
tell Rasta the exact variable name, environment and intended value.

## 5. Post-deploy smoke tests

Run in order; stop at the first failure and assess for rollback (§6):

1. **Sign-up with the emailed code** — register a fresh account at the live
   site; the 6-digit code email arrives; entering it completes sign-up.
2. **Login** — the new account (and an existing pre-gate account) signs in to
   the dashboard.
3. **Manual setup wizard on a phone (375) and a laptop (1280)** — wizard opens,
   steps save, checklist reflects progress.
4. **Add a student** — appears in the roster.
5. **Add a teacher** — invite flows / record appears.
6. **Report card PDF** — generate/download succeeds (file opens).
7. **Profile photo upload and display** — upload succeeds; image renders from
   object storage (no broken URL).
8. **Password reset email** — request arrives; link works.
9. **Talk to sales form** — submit; the lead lands (`demo_requests` row /
   `LEADS_EMAIL` receives it).
10. **Log stream** — Cloud log stream is receiving application entries.
11. **Next backup opens only with its password** — wait for (or trigger) the
    post-deploy backup; verify it decrypts and does not open without the
    password.
12. **Toshi panel shows Coming soon** — a school admin sees the preview
    (`TOSHI_DEFAULT_MODE=preview`) and no chat UI.
13. **Admission approval (repaired in #988)** — on a throwaway test school,
    create one admission and approve it; confirm the student user (and KLS
    number) exists, then remove the school with the purge command (dry run
    first).

Clean up any account created for the smoke tests per standing rule #3 (flag
inactive / purge command — never a raw delete).

## 6. Rollback

Two levers, in this order:

1. **Revert PR (code).** Open a revert of the offending merge on GitHub, let CI
   go green, merge, and run the deploy POST again. This is the primary route —
   fast, safe, no data loss. Prefer it whenever the failure is a code/behaviour
   regression rather than a data problem.
2. **Snapshot restore (data).** Only if data written since the §1.4 snapshot is
   itself the problem (e.g. a migration corrupted records in a way code-revert
   cannot undo). Restoring rolls **all** data back to the snapshot point — real
   schools' post-snapshot activity is lost. Requires the owner's explicit
   go-ahead before executing; re-deploy the previous release SHA alongside it if
   the code must also move back.

A code revert does **not** undo a data migration. If a released data migration
must be undone, use that migration's `down()` deliberately (e.g. the Toshi
backfill's restore log) rather than a database-side reverse.
