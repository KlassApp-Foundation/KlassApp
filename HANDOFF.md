# Handover — onboarding work, 9 November 2026

**Base for every branch below:** `origin/integration/monday-2026-11-09`. Every PR targets `main`, is a draft, and is marked **merge after #1285**. Nothing was merged, deployed, or pushed to the integration or preview branches. The preview at `127.0.0.1:8126` and its database were not touched.

## Done

| Item | PR | What it is |
| --- | --- | --- |
| T-116 | #1299 | The November decisions note, plus `playbook.md` and `knowledge.md` aligned |
| T-117 part 1 | #1300 | Wizard is the only setup path; Toshi v1 unreachable; exams and SchoolPay out of onboarding; migration moves parked schools; tests |
| T-118 (students) | #1301 | `/admin/import` and `/admin/importUsers` redirect to the engine-backed page; no `dd()` |
| T-121 | #1302 | Merge follow-through: the eight Bariyo123 tips confirmed contained, all 74 open PRs with an action, exact commands |
| T-125 | #1303 | Wizard walkthrough for Rasta's post-deploy check |
| T-122 | #1304 | 48 Uganda assumptions catalogued, with the 8 resolved on #1285 named |
| T-123a | #1305 | Plan-layer design: plan shape, storage/encryption/retention, preview, confirmation options, idempotency, DPA gate |
| audit | #1297 | The audit of record |
| rule | #1298 | AGENTS.md: a browser check counts only when clicked through |

## Next, in order

1. **T-117 part 2** (#1300). Delete `AgentToshi::$steps` (218–236), `$mandatorySteps` (242–252) and the v1 tail (4387–6326); then the blade's v1 blocks (169–207, 209–285, 287–379, 381–402, 404–479, 481–576, 578–610, 614–671, 674–912, 1105–1760), keeping the v2 options include at 612 and the checklist at 728/1028. Before that, restore the four tests that still cover live behaviour: `WhatsAppVerificationGuardTest`, `FinishSchoolOnboardingTest` (minus its Toshi test), tests 3–4 of `StudentRegistrationNumberPathsTest`, and a gender assertion in an engine test. Then click the panel through once — typed URLs do not count.
2. **T-118 teachers** (#1301). Port `TeachersImport` to `OnboardingUploadService` with per-row reports, or retire `/import/teacher` to the wizard step while setup is unfinished; say which. Delete the legacy controller and importer after.
3. **T-119.** Architecture test: no onboarding write outside `OnboardingEngine` for `users`, `userprofiles`, `student_academics`, `sections`, `standard_links`, `subjects`, `terms`, `fees`, `teacherlinks`. Allow-list each exception with a reason; prove the test fails on a deliberate violation, then remove it.
4. **T-120.** One phone normaliser: merge `OnboardingEngine::normaliseOnboardingPhone`, `ManualOnboardingWizard::normaliseOptionalPhone` and #1286's `PhoneE164Converter`, driven by the school's country.
5. **T-123b.** Intake only: upload the workbook, run `ChecklistWorkbookConverter` (#1291), store the plan, show flags with tab and row. No school-data writes. Apply waits on bootcamp Q6.

## Still open with Rasta

- #1285 merges after T-114 (attendance check).
- Bootcamp Q2, Q4–Q7, Q9, Q10 (decisions note, §D5).
- Approval of the T-122 table before any of its code changes.
- The `#1295` rebuild (`--onto` in #1302), not a plain merge.

## Test baseline used

```
php artisan cache:clear
vendor/bin/phpunit tests/Feature/Onboarding      # 562 passed, 5 incomplete
vendor/bin/phpunit tests/Feature/Toshi           # 499 passed, 2 skipped
vendor/bin/phpunit tests/Feature/Auth tests/Feature/EarlyAccess tests/Feature/Invites
```
Run the last three per file: together, the confirm-link throttle accumulates across files through the file cache and yields 429s.
