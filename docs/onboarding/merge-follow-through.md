# Merge follow-through after #1285

**Tracker:** T-121. **Status:** report only — nothing merged, closed or pushed to #1285.

Base: `origin/integration/monday-2026-11-09` at the time of writing. Rasta merges and closes; the commands below are for him or a session he authorises.


## 1. The eight Bariyo123 PRs inside #1285

Each tip is an ancestor of the integration tip (`git merge-base --is-ancestor`). They need no rebase and should be closed after #1285 merges, with a comment that names the merge commit.


| PR | Branch | Contained | Draft close comment |
| --- | --- | --- | --- |
| #1258 | `cursor/reference-seed-health-e2dc` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |
| #1256 | `cursor/school-admin-setup-login-30c4` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |
| #1252 | `cursor/t060-widen-student-id-c495` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |
| #1251 | `cursor/t065-international-phone-c495` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |
| #1250 | `cursor/t061-import-entry-points-c495` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |
| #1249 | `cursor/t064-country-codes-c495` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |
| #1248 | `cursor/t070-school-branding-seo-c495` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |
| #1246 | `cursor/school-country-default-c495` | yes | "Shipped inside #1285 (merge commit `<sha>`). Closing as contained; the branch tip is unchanged in the integration branch." |

**Command, after #1285 reports `merged: true`:**

```bash
gh api repos/KlassApp-Foundation/KlassApp/pulls/1285 --jq '{merged,merge_commit_sha,merged_at}'
for n in 1258 1256 1252 1251 1250 1249 1248 1246; do
  gh pr close "$n" --comment "Shipped inside #1285 (merge commit <sha>). Closing as contained."
done
```


## 2. Every other open PR, and what to do after #1285

`contained` = the branch tip is already in the integration branch.


| PR | Author | Head | Contained | Action |
| --- | --- | --- | --- | --- |
| #1301 | Mucunguzi256 | `t118-imports-on-engine` | no | Merge after #1285 (this stack) |
| #1300 | Mucunguzi256 | `t117-retire-toshi-v1` | no | Merge after #1285 (this stack) |
| #1299 | Mucunguzi256 | `t116-decisions` | no | Merge after #1285 (this stack) |
| #1298 | Mucunguzi256 | `docs/agents-browser-check-rule` | no | Merge after #1285 (docs) |
| #1297 | Mucunguzi256 | `docs/onboarding-agentic-audit` | no | Merge after #1285 (docs) |
| #1296 | Mucunguzi256 | `t089-onboarding-runbook` | no | Merge after #1285 (docs) |
| #1295 | Mucunguzi256 | `t107-name-route-guards` | no | Rebase onto `main` after #1285; do not merge as-is (it carries #1285) |
| #1294 | Mucunguzi256 | `t072-student-id-format` | no | Merge after #1285 (docs), order free |
| #1293 | Mucunguzi256 | `t094-parent-student-access` | no | Merge after #1285 (docs) |
| #1292 | Mucunguzi256 | `t104-edit-routes-by-id` | no | Merge after #1285 (docs), order free |
| #1291 | Mucunguzi256 | `t079-checklist-convert` | no | Rebase after #1286; merge after #1285 |
| #1290 | Mucunguzi256 | `t095-parent-linking-design` | no | Merge after #1285 (docs), order free |
| #1289 | Mucunguzi256 | `t098-assign-classes-design` | no | Merge after #1285 (docs), order free |
| #1288 | Mucunguzi256 | `t099-subjects-teachers-design` | no | Merge after #1285 (docs), order free |
| #1287 | Mucunguzi256 | `t105-names-normalize` | no | Merge before #1291 (independent, additive) |
| #1286 | Mucunguzi256 | `t077-phones-to-e164` | no | Merge before #1291 (independent, additive) |
| #1285 | Mucunguzi256 | `integration/monday-2026-11-09` | yes | Merge after T-114 (attendance check) |
| #1284 | Mucunguzi256 | `cursor/fees-page6-6ada` | yes | Close after #1285 merges (already contained) |
| #1283 | Mucunguzi256 | `cursor/teacher-dashboard-page8-6ada` | yes | Close after #1285 merges (already contained) |
| #1282 | Mucunguzi256 | `cursor/parent-student-portals-page9-6ada` | yes | Close after #1285 merges (already contained) |
| #1281 | Mucunguzi256 | `cursor/base-class-fixes-1cb2` | yes | Close after #1285 merges (already contained) |
| #1280 | Mucunguzi256 | `cursor/report-cards-base-class-screen-b71c` | yes | Close after #1285 merges (already contained) |
| #1279 | Mucunguzi256 | `cursor/t082-plan-limits` | yes | Close after #1285 merges (already contained) |
| #1278 | Mucunguzi256 | `docs/screen-inventory` | yes | Close after #1285 merges (already contained) |
| #1277 | Mucunguzi256 | `cursor/t095-one-click-attendance` | yes | Close after #1285 merges (already contained) |
| #1276 | Mucunguzi256 | `docs/demo-run-sheet-2026-11-12` | yes | Close after #1285 merges (already contained) |
| #1275 | Mucunguzi256 | `cursor/t094-parent-student-setup-links` | yes | Close after #1285 merges (already contained) |
| #1274 | Mucunguzi256 | `feat/student-profile-report-cards` | yes | Close after #1285 merges (already contained) |
| #1272 | Mucunguzi256 | `cursor/exam-sets-page-marks-f7b0` | yes | Close after #1285 merges (already contained) |
| #1271 | Mucunguzi256 | `cursor/whole-class-report-pdf-cd2d` | yes | Close after #1285 merges (already contained) |
| #1270 | Mucunguzi256 | `cursor/demo-fees-structure-a0f0` | yes | Close after #1285 merges (already contained) |
| #1269 | Mucunguzi256 | `cursor/demo-blockers-part-2-49d6` | yes | Close after #1285 merges (already contained) |
| #1267 | Mucunguzi256 | `cursor/password-reset-code-send-a7c1` | yes | Close after #1285 merges (already contained) |
| #1266 | Mucunguzi256 | `cursor/parent-student-portal-0657` | yes | Close after #1285 merges (already contained) |
| #1265 | Mucunguzi256 | `feat/consistent-topbar-account-menu` | yes | Close after #1285 merges (already contained) |
| #1264 | Mucunguzi256 | `cursor/t092-login-while-authenticated` | yes | Close after #1285 merges (already contained) |
| #1263 | Mucunguzi256 | `cursor/demo-blockers` | yes | Close after #1285 merges (already contained) |
| #1262 | Mucunguzi256 | `cursor/t091-teacher-email-invite-6d9a` | yes | Close after #1285 merges (already contained) |
| #1261 | Mucunguzi256 | `cursor/exam-sets` | yes | Close after #1285 merges (already contained) |
| #1260 | Mucunguzi256 | `cursor/onboarding-steps-and-size-plus` | yes | Close after #1285 merges (already contained) |
| #1259 | Bariyo123 | `cursor/clear-signup-resend-limiter-11f9` | no | Rebase onto `main` after #1285, then review normally |
| #1258 | Bariyo123 | `cursor/reference-seed-health-e2dc` | yes | Close after #1285 merges (already contained) |
| #1256 | Bariyo123 | `cursor/school-admin-setup-login-30c4` | yes | Close after #1285 merges (already contained) |
| #1255 | Mucunguzi256 | `cursor/marks-level-reports` | yes | Close after #1285 merges (already contained) |
| #1254 | Mucunguzi256 | `docs/curriculum-assessment-and-records` | no | Rebase onto `main` after #1285, then review normally |
| #1253 | Mucunguzi256 | `docs/marks-design-brief` | no | Rebase onto `main` after #1285, then review normally |
| #1252 | Bariyo123 | `cursor/t060-widen-student-id-c495` | yes | Close after #1285 merges (already contained) |
| #1251 | Bariyo123 | `cursor/t065-international-phone-c495` | yes | Close after #1285 merges (already contained) |
| #1250 | Bariyo123 | `cursor/t061-import-entry-points-c495` | yes | Close after #1285 merges (already contained) |
| #1249 | Bariyo123 | `cursor/t064-country-codes-c495` | yes | Close after #1285 merges (already contained) |
| #1248 | Bariyo123 | `cursor/t070-school-branding-seo-c495` | yes | Close after #1285 merges (already contained) |
| #1246 | Bariyo123 | `cursor/school-country-default-c495` | yes | Close after #1285 merges (already contained) |
| #1244 | Mucunguzi256 | `cursor/marks-phase-6-release` | yes | Close after #1285 merges (already contained) |
| #1243 | Mucunguzi256 | `cursor/marks-phase-5-report-cards` | yes | Close after #1285 merges (already contained) |
| #1242 | Mucunguzi256 | `cursor/marks-phase-4-combined-sheet` | yes | Close after #1285 merges (already contained) |
| #1241 | Mucunguzi256 | `cursor/marks-phase-3-submission` | yes | Close after #1285 merges (already contained) |
| #1240 | Mucunguzi256 | `cursor/marks-phase-2-entry-writer` | yes | Close after #1285 merges (already contained) |
| #1239 | Mucunguzi256 | `cursor/marks-phase-1-class-teacher-v2` | yes | Close after #1285 merges (already contained) |
| #1237 | Mucunguzi256 | `cursor/class-save-atomicity-d848` | yes | Close after #1285 merges (already contained) |
| #1198 | Mucunguzi256 | `cursor/fee-reminder-message-build-c006` | no | Rebase onto `main` after #1285, then review normally |
| #1183 | Mucunguzi256 | `cursor/onboarding-session-log-a453` | no | Rebase onto `main` after #1285, then review normally |
| #1161 | Bariyo123 | `cursor/connected-tools-page-c492` | no | Rebase onto `main` after #1285, then review normally |
| #1159 | Mucunguzi256 | `cursor/mobile-header-targets-b873` | no | Rebase onto `main` after #1285, then review normally |
| #1155 | Bariyo123 | `cursor/ux-design-brief-c253` | no | Rebase onto `main` after #1285, then review normally |
| #1149 | Bariyo123 | `cursor/google-connectors-calendar-b53f` | no | Rebase onto `main` after #1285, then review normally |
| #1147 | Bariyo123 | `cursor/whatsapp-verify-token-required-c2f9` | no | Rebase onto `main` after #1285, then review normally |
| #1141 | Bariyo123 | `cursor/speed-up-test-suite-ca5a` | no | Rebase onto `main` after #1285, then review normally |
| #1140 | Bariyo123 | `cursor/google-connectors-classroom-b53f` | no | Rebase onto `main` after #1285, then review normally |
| #1139 | Bariyo123 | `cursor/google-connectors-foundation-b53f` | no | Rebase onto `main` after #1285, then review normally |
| #1138 | Bariyo123 | `cursor/google-connectors-sheets-b53f` | no | Rebase onto `main` after #1285, then review normally |
| #1137 | Bariyo123 | `cursor/google-connectors-drive-b53f` | no | Rebase onto `main` after #1285, then review normally |
| #1133 | Bariyo123 | `cursor/docs-site-governance-55f0` | no | Rebase onto `main` after #1285, then review normally |
| #1120 | Mucunguzi256 | `cursor/docs-site-early-adopters-56ae` | no | Rebase onto `main` after #1285, then review normally |
| #1119 | Mucunguzi256 | `cursor/package-architecture-proposal-56ae` | no | Rebase onto `main` after #1285, then review normally |

## 3. Exact order

```bash
# 0. Confirm #1285 merged, and record the SHA.
gh api repos/KlassApp-Foundation/KlassApp/pulls/1285 --jq '{merged,merge_commit_sha}'

# 1. Update every local branch people still care about.
git fetch origin --prune

# 2. Onboarding docs first (no code): T-116, audit, AGENTS rule, runbook, designs.
for b in t116-decisions docs/onboarding-agentic-audit docs/agents-browser-check-rule \
         t089-onboarding-runbook t094-parent-student-access; do
  gh pr list --state open --head "$b" --json number --jq '.[].number'
done
# Merge each after a rebase onto main:
#   git checkout <branch> && git rebase origin/main && git push --force-with-lease

# 3. The independent commands, then the stacked one.
#    #1286 -> #1287 -> (#1291 after a rebase onto main, it was built on #1286)
git checkout t077-phones-to-e164 && git rebase origin/main
git checkout t105-names-normalize && git rebase origin/main
git checkout t079-checklist-convert && git rebase origin/main

# 4. This session's code stack.
git checkout t117-retire-toshi-v1 && git rebase origin/main
git checkout t118-imports-on-engine && git rebase origin/main

# 5. #1295 must be rebuilt, not merged: it is #1285 plus ten files.
git checkout t107-name-route-guards
git rebase --onto origin/main origin/integration/monday-2026-11-09

# 6. Everything else: rebase, run CI, merge in the normal queue.
```


## 4. Notes

- `#1291` was opened against `t077-phones-to-e164`, not `main`; after #1286 merges it must be rebased onto `main` before review.

- `#1295` has 215 commits and 667 files because it was branched from the integration content; its own change is ten files (name-addressed routes). The `--onto` rebase above keeps only those ten.

- The 40 contained branches include PRs whose titles are not onboarding (marks, portals, fees). They are still contained; closing them is safe once #1285 lands.

