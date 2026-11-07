# Deploy runbook: Monday 9 November 2026

> **For Rasta, step by step.** Written on 5 November from the repository and the integration rehearsal (draft PR #1285, `integration/monday-2026-11-09`). I had no Laravel Cloud access, so every Cloud step below is what the existing checklist (`docs/ops/production-deploy-checklist.md`) and AGENTS.md say to do; none of it was tried from this session. No secrets, tokens or environment identifiers are in this file. Companion: `docs/release/2026-11-09-merge-guide.md` (what was merged, every conflict, every fix).
>
> Rules that bind every step: #1 no raw production writes, #2 backup and verify first, #15 deploy from the right checkout, #21 confirm `merged: true` by API, #24 merging is not shipping, #32 no admin bypass, #36 merge only on finished green and never during a staging deploy, #38 Cloud settings are yours, #45 retarget stacked PRs to `main` before merging.

## 0. Before Monday: make the PRs safe to merge

The rehearsal found problems that exist **only in combination**. If you merge the PRs without these fixes, `main` breaks. Do all of this first.

1. **Apply the fixes to their home PR branches** (exact changes in merge guide section 4):
   - **#1279** (or #1246, whichever lands second): delete the duplicate `public const DEFAULT_COUNTRY = 'Uganda';`. Without it PHP stops with "Cannot redefine class constant" and the whole test suite cannot start.
   - **#1244**: qualify `standards_link.class_teacher_id` and `standards_link.section_id` in `MarksReleaseService::links()` (a class teacher opening Marks Release gets a database error once #1269's class ordering is in).
   - **#1280 and #1281**: rebase onto #1272's tip (their copy of `requiresCorrection()` is the old `return false`).
2. **Rebase the stacks that are behind their base:** #1240, #1241, #1244, #1272, #1281, #1280 (merge guide section 2). #1243 carries a late commit (with a migration) that #1244 and everything above it lack.
3. **Test and screen changes that belong to other PRs** (merge guide section 6): #1240's dashboard test, #1277's parent attendance test, and any others listed there.
4. **Decide the teacher dashboard:** #1283 replaces the whole page and drops the Marks Release button, the "Marks needing you" tile and the "Marks & corrections" card. If teachers need them on Monday, they must be added to #1283 first.
5. **Review the eight PRs by `Bariyo123`** (#1246, #1248, #1249, #1250, #1251, #1252, #1256, #1258) if this person is not on the team (AGENTS.md rule 25). I reviewed none of them.
6. **Staging:** confirm the latest staging deployment is `deployment.succeeded` and nothing is deploying (rule 36). Staging auto-deploys every merge to `main`.

## 1. Approvals and merges, in merge order

Approve and merge in this order. Each PR: required checks (`check`, `scan`, `test-guard`) finished green on its final commit; no admin override; then confirm the merge by API and record the merge commit.

`gh api repos/KlassApp-Foundation/KlassApp/pulls/{n} --jq '{merged,merge_commit_sha,merged_at}'`

Order (same as the rehearsal): #1248, #1250, #1249, #1246, #1251, #1252, #1256, #1258, #1260, #1263, #1264, #1265, #1266, #1282, #1277, #1269, #1270, #1284, #1267, #1279, #1283, #1237, #1239, #1262, #1275, #1240, #1241, #1242, #1243, #1244, #1255, #1261, #1272, #1281, #1271, #1274, #1280, then the docs #1276 and #1278 (#1273 is already in).

How stacked PRs behave in this order:
- Retarget each stacked PR to `main` before merging it (rule 45) and confirm afterwards that its merge commit is on `main`, not on a base branch.
- Where a PR is merged **before the PR it is stacked on** (#1267, #1279 and #1275 before #1262 and #1239; #1241 and #1240 before #1239 is finished; and so on), its merge carries the base PR's commits with it. Then #1239 and #1262 are already in `main`; GitHub normally marks them merged on its own. If it does not, check with the API call above and close them with a note.
- Expect each merge to start a **staging deploy**. 38 merges mean 38 deploys: wait for each `deployment.succeeded` before the next merge (rule 36). Budget the time.
- **After the last merge**, check that `main` equals the rehearsal plus the fixes you applied:
  `git diff origin/main origin/integration/monday-2026-11-09 --stat -- . ':!docs/release' ':!tests/Feature/Integration'`
  should show only changes that came from the fixes in step 0 (and nothing in `app/`, `routes/`, `config/`, `database/` or `resources/` that you did not expect).

**Option B (your decision, not mine):** instead of 38 merges and 38 staging deploys, apply the fixes to the integration branch, take it out of draft, and merge that one PR through the normal required checks. The tree that shipped is then exactly the tree that was tested, with one staging deploy. The cost is that per-PR review history and authorship are not the merge commits. I chose Option A above because you asked for approvals in merge order.

## 2. Database backup in Laravel Cloud

1. In the Cloud dashboard, take a snapshot of the production database **immediately before** the deploy (or run `php artisan backup:run` through Cloud Commands). Record its identifier here: `__________`.
2. Check the backup opens (with its password, held by you). A backup you cannot open is not a backup.
3. Back up `.env` separately (the backup does not include it).
4. `BACKUP_WHATSAPP_PHONE` set so a failed backup alerts a person.
5. **Why this matters this time:** 14 migrations run (section 5). Two change existing rows (country codes, early-adopter plan) and one rewrites a foreign key on `fees_categories`. A code revert cannot undo a data migration; the snapshot is the rollback.

## 3. `db:health` before and after

`db:health` is read-only (`app/Console/Commands/DatabaseHealthCommand.php`): it reports country-code, school, student-id, duplicate and orphan problems and writes nothing.

1. **Before:** run `php artisan db:health` through Cloud Commands on production. Save the output (counts and section names only; do not paste student or school names into a PR).
2. **After the deploy:** run it again and compare. Expected: country-code problems fall or go to zero (migration 5 corrects codes by name; rows it could not match are logged as "Country code correction skipped a row"). Anything new in "orphan" or "duplicate" sections is a stop: do not announce the release; see section 8.
3. `php artisan db:health --fail` exits 1 on any problem; use it only if you want a hard stop.

## 4. Deploy: migrations only, never seeders

1. Deploy from the dedicated main checkout at the reviewed SHA (rule 15). Confirm CI is green on that exact SHA.
2. **Build command parity** (from the existing checklist): production's build command must match staging's before you deploy: `composer install --no-dev && npm ci --include=dev --no-audit --no-fund && npm run build`. Do not change it from an agent session. This release adds a Composer package (`giggsey/libphonenumber-for-php`, from #1251), so `composer install` must run on the build.
3. **Deploy commands: migrations only.** The deploy may run `php artisan migrate --force` and the usual cache commands. It must **not** run `db:seed`, `migrate:fresh`, `migrate:refresh`, or any `--seed` flag. Seeders in this release (country codes, plans, exam types, reference catalogs) are for fresh installs; the data changes you need on production are already inside the migrations. Check the deploy command list in the Cloud dashboard before you press deploy.
4. Trigger the release with the empty-body deploy POST (pattern in `knowledge.md`, "Triggering a real deployment"; token from Doppler/Cloud, never printed). Poll until `deployment.succeeded` (2-minute cadence). If it fails, read the logs; do not re-trigger blind.
5. Watch the log stream for the first minutes: the country-code lines ("Country code correction finished", with `corrected` and `skipped` counts), no new exceptions, queue worker and scheduler alive. The queue worker matters: class report PDFs (`GenerateClassReportsJob`) and spreadsheet imports (`ProcessOnboardingUpload`) run on it.
6. `php artisan migrate:status`: all 14 listed in section 5 show **Ran**, none Pending.

**Environment values for this release** (names only; setting them is yours, rule 38):

| Variable | Value for Monday | Why |
|---|---|---|
| `FILESYSTEM_DISK` | the cloud object storage disk (not `local`) | Student photos, imports and generated files. Verified by the photo check in section 6 |
| `MAIL_FROM_ADDRESS` | a valid sender address | Invite and password-reset-code emails (#1262, #1267). A blank or invalid value falls back to `connect@klassapp.xyz` (config/mail.php) |
| `WHATSAPP_APPROVED_TEMPLATES` | leave unset | Empty means every gated WhatsApp send is refused. Releasing marks sends nothing to WhatsApp until `results_released` is recorded as approved by Meta **and** the release's "Send these results on WhatsApp" box is ticked (off by default) |
| `STAFF_INVITE_WHATSAPP_ENABLED` | leave unset (false) | Teacher invites go by email only |
| `RECORD_SNAPSHOTS_ALLOW_DROP` | leave unset (false) | Only for a deliberate rollback of migration 6 (section 5) |
| `TOSHI_DEFAULT_MODE` | unchanged (`preview`) | AGENTS.md rule 33: AI stays off by default |

## 5. Migrations that will run, in order

Run order is by filename. Each is guarded (`hasTable`/`hasColumn`), so a second run is a no-op. "Rollback" is what `down()` does; for anything after real data has been entered, **restoring the snapshot is the rollback** (section 8).

| # | Migration | Changes | Existing data | Rollback (`down()`) |
|---|---|---|---|---|
| 1 | `2026_10_26_120000_add_revoked_at_to_teacher_invites_table` | adds nullable `teacher_invites.revoked_at` | untouched | drops the column |
| 2 | `2026_10_26_130000_add_report_composition_and_curriculum_profile_to_schools_table` | adds nullable `schools.report_composition` (json) and `curriculum_profile` | untouched (null = default profile) | drops both columns |
| 3 | `2026_10_26_150000_add_whatsapp_send_to_marks_releases_table` | adds `marks_releases.whatsapp_send` default false | old releases read as "not sent on WhatsApp" | drops the column |
| 4 | `2026_10_26_160000_add_legacy_o_level_bands_to_schools_table` | adds nullable `schools.legacy_o_level_bands` (json) | untouched | drops the column |
| 5 | `2026_10_27_120000_correct_country_codes_by_name` | **data**: corrects `countries` short/ISO/tel codes by country name, in one transaction | rows with no match or more than one match are skipped and logged, never forced | restores the known seed codes only for rows still holding the corrected code |
| 6 | `2026_10_27_120000_create_record_snapshots_table` | new table `record_snapshots`; foreign keys are RESTRICT so released records cannot be cascade-deleted | new table | **refuses** to drop unless `RECORD_SNAPSHOTS_ALLOW_DROP=true` (it would delete released records) |
| 7 | `2026_10_27_130000_add_release_reason_to_marks_releases_table` | adds nullable `marks_releases.release_reason` | untouched | drops the column |
| 8 | `2026_11_03_140000_ensure_city_state_and_reference_catalogs` | adds `cities.state_id` if missing; adds its foreign key only if no orphan values exist (else logs and leaves them); creates `reference_curricula` and `reference_grading_bands` | existing city rows unchanged | drops the two reference tables and the foreign key; keeps the column |
| 9 | `2026_11_04_090000_add_school_id_to_exam_types` | adds nullable `exam_types.school_id` and an index | shared exam types keep `school_id` null | drops the index and column |
| 10 | `2026_11_04_120000_add_fee_structure_duration_and_school_wide` | adds `fees_categories.duration` (default `termly`) and `applies_school_wide` (default false); creates `fee_category_sections`; makes `fees_categories.standard_id` nullable by dropping and re-adding its foreign key | existing fees keep working through the defaults | drops the new table and columns; **does not** set `standard_id` back to NOT NULL |
| 11 | `2026_11_04_120000_make_users_password_nullable` | `users.password` becomes nullable (teacher accounts created by invite have no password until they set one) | untouched | sets NOT NULL again: **fails on MySQL if any user has a null password** |
| 12 | `2026_11_04_180000_create_exam_sets_table` | new table `exam_sets`; adds nullable `exams.exam_set_id` with a foreign key and an index | existing exams get null | drops the foreign key, index, column and table |
| 13 | `2026_11_04_210000_add_absent_to_marks_table` | adds `marks.absent` (default false) and makes `marks.marks` nullable | existing marks unchanged | **refuses** while any row has `absent = true` (rolling back must not turn AB into 0) |
| 14 | `2026_11_05_000000_add_platform_only_plans_and_seed_early_adopter` | adds `plans.is_platform_only` (default false); inserts or updates one `early_adopter` plan if the plans table is not empty | other plans untouched | deactivates the `early_adopter` row and drops the column |

Two to look at on MySQL first: **10** (foreign key drop and re-add on a table with live data) and **5** (a data change; check the "skipped" log lines). CI ran all 14 on an empty MySQL 8.4 database; nothing has run them over a copy of production data. If you have a staging copy with real-shaped fees and countries, run them there first.

## 6. Checks on the test school (after the deploy)

Use the demo school only (AGENTS.md rule 9), logged in with the demo logins (placeholders: `<demo admin>`, `<demo class teacher>`, `<demo parent>`; never write a password down). Do these in order and stop at the first failure.

| # | Check | Where | Expect |
|---|---|---|---|
| 1 | Admin dashboard loads | `/admin/dashboard` | no error, school name shown |
| 2 | Class order | `/admin/students`, Classes | classes in level order, streams under their class |
| 3 | Student profile | `/admin/students/{id}` | ID card, Performance, Attendance, Fees cards |
| 4 | **Photo upload (storage)** | Profile → **Add photo**, choose a small JPG | "Photo saved." and the picture shows on the card after reload (a broken image means the storage disk or its URL is wrong). Then **Change photo → Remove photo** to leave the demo clean |
| 5 | Mark as paid | Profile → Fees → **Mark as paid** (only on a student who owes) | the balance goes to zero; do this on a demo student only |
| 6 | Exam set | `/admin/exams/sets/create` | creates exams for base classes; set page lists them |
| 7 | Marks entry | teacher: `/teacher/exam/marks` → one exam | `AB` accepted; 105 shows "Above 100. Check the mark."; Submit for review works |
| 8 | Bulk approve | `/admin/marks/submissions` | tick, **Approve N** |
| 9 | Mark sheet | `/admin/marks` | combined grid for the class |
| 10 | Marks Release | `/admin/marks/releases/new` | checklist, "Who will be told" count; release **without** ticking WhatsApp |
| 11 | Parent sees only released | parent: `/parent/children` → child → Grades | released sitting only; nothing from an unreleased class |
| 12 | Report cards | `/admin/reports/cards` | list for a released sitting; one **PDF** downloads; **Build all** finishes (needs the queue worker) |
| 13 | Fees | `/admin/fees-categories`, a parent's Fees page | a yearly fee counts once in "Year total" |
| 14 | One-click attendance | teacher: `/teacher/attendance/register` | saves once per day; parent sees it |
| 15 | Invite email | Teachers → add a teacher with a real test address you control | a setup link email arrives; no password in it |
| 16 | Import | `/admin/students/import` | upload a 3-row CSV; report names skipped rows |

Mark any failure with the screenshot and the time; do not retry the release.

## 7. What not to do on Monday

- No `demo:refresh` or `demo:set-password` on production; no `db:seed`, `migrate:fresh` or `migrate:refresh` anywhere near production.
- No one-off SQL on production (rule 1). Fixes go through a migration or a committed command.
- No admin merge past a red or pending check (rule 32). If a merge breaks staging, open a revert PR first and investigate after (rule 36).
- No WhatsApp send from a release until `results_released` is approved and recorded.

## 8. How to roll back each step

| Step | If it goes wrong | Roll back by |
|---|---|---|
| Approvals and merges | a merged PR breaks staging | open a revert PR for that merge first (rule 36), then investigate; do not merge the next PR |
| Backup | cannot open the backup | stop; do not deploy until you can |
| `db:health` before | reports problems already there | record them as the baseline; only new problems after the deploy are this release's |
| Deploy fails before migrations | build or boot error | read the deployment log; redeploy the previous successful deployment from the Cloud dashboard. Nothing in the database changed |
| Deploy fails during migrations | one migration errors | migrations are guarded and several are in transactions, but MySQL commits schema changes implicitly: run `migrate:status` to see which ran; **restore the snapshot** if migration 5, 10 or 11 is the one that failed; otherwise fix forward |
| Release live, a screen is wrong | a UI bug, no data problem | redeploy the previous deployment; the new tables and columns are additive and old code ignores them (migration 11 loosens a column, 13 loosens `marks.marks`: old code still works) |
| Release live, data is wrong | wrong country codes, fee totals, plan row | **restore the snapshot** and redeploy the previous deployment; do not run `down()` by hand over real data |
| `down()` by hand (last resort, before any new data) | rolling back migrations | in reverse order, one at a time with `php artisan migrate:rollback --step=1`; expect refusals: 13 refuses once any mark is `AB`, 11 fails if any user has a null password, 6 refuses without `RECORD_SNAPSHOTS_ALLOW_DROP=true`, 10 leaves `standard_id` nullable |
| WhatsApp or email went out by mistake | a release ticked "Send these results on WhatsApp" | cannot be recalled; set `WHATSAPP_REPLIES_ENABLED=false` (kill switch in the existing checklist) to stop more |
| Photo check fails | storage disk or URL | set `FILESYSTEM_DISK` correctly (yours to change), redeploy, upload again; photos saved to a local disk are lost on the next deploy (rule 39) |

After a rollback of any kind: run `php artisan db:health` again and compare with the baseline.
