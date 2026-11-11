# Marks and reports progress

Handover for any later session. Updated after each numbered item. Draft only: no merges, no deploys.

This file is [#1328](https://github.com/KlassApp-Foundation/KlassApp/pull/1328) on `marks/progress`.

**Decisions (final, 2026-11-11).** Class teachers keep creating exam sets for their own class, and can approve or return marks for that class. Head teachers have the same powers as a school admin inside their own school, never across schools. Admins and head teachers can enter, save, submit, approve, return and release for any class. Subject teachers enter, save and submit their own subjects. Each exam set has its own report. The end-of-term report's composition is a per-school setting (weights total 100; default is End of Term only) and never rewrites a report already released. Parents and learners see marks and report cards only after release.

**Where this work lives.** New work goes in its own worktree from `origin/main` (`2aaf327b`), with its own database and a port other than 8000 or 8126. The demo on `poc/toshi-checklist-onboarding` at tag `demo-final-2026-11-11d` (`http://localhost:8000`) stays untouched. Part B screens wait for `~/Downloads/marks-handoff/`, which is not on this machine yet. Item 4 may reuse the existing teacher marks screen before that handoff arrives.

**Design note.** [#1322](https://github.com/KlassApp-Foundation/KlassApp/pull/1322) (`docs/design/marks-workbook-tools.md`) describes the pipeline on `main`. Build on exam sets, marks entry, submissions, release and report cards. Do not add a parallel version.

## Items

| # | Item | Status | PR | What's left | Next step |
| --- | --- | --- | --- | --- | --- |
| 1 | Demo release webhook sends counts only and never posts from production | Verified. Own tests pass. Do not rewrite. | [#1323](https://github.com/KlassApp-Foundation/KlassApp/pull/1323) `fix/demo-release-webhook-guard` `3c056244` | Full-suite guard is red for a different test: `MarksReleaseQueueTest::test_release_now_with_50_bound_parents_queues_50_jobs_and_returns_at_once` expects 50 queued jobs and sees 51, because a demo release also queues `SendDemoMarksReleaseWebhook`. Left as-is on purpose. | None on this branch. |
| 2 | Class teacher approve and return, scoped like release | Pushed. Test-guard green. Usable. | [#1324](https://github.com/KlassApp-Foundation/KlassApp/pull/1324) `feat/class-teacher-approval` `b50ae93f` | Click-through of `/teacher/marks/approvals` at 375 then 1280. Bulk approve is routed but not on the page. | Do the click-through in the proof after items 2–6. |
| 3 | Head teacher equals school admin inside the school | Pushed again at `c57bcc52`. Local suites green (19 + 65). Full test-guard not re-run here. | [#1325](https://github.com/KlassApp-Foundation/KlassApp/pull/1325) `feat/head-teacher-parity` | Browser click-through. `toshi-school-action` stays school-admin only; head teachers use `toshi-deputy-action`. `Common::is_admin` stays usergroup 3 (impersonation target). | Wait for GitHub test-guard. Then item 4. |
| 4 | Admins and head teachers enter, save and submit marks (T-143) | Not started. | — | The whole item. Reuse the teacher marks screen. Attribute each entry to the person who made it. | Start after item 3 is green. Branch from `main`. |
| 5 | Importer accepts `ABS` (any case) as absent; an empty cell is no mark | Pushed. Test-guard green. Usable. | [#1326](https://github.com/KlassApp-Foundation/KlassApp/pull/1326) `feat/marks-import-absent` `e4a78521` | The PR body calls this item 4. The behaviour matches item 5. `AB` is also accepted, matching the entry screen. | None until the term workbook (item 10) reuses it. |
| 6 | Shared exam-type catalog wherever a school can be created (T-141), plus an empty-list message with a fix link | Pushed. Not usable. | [#1327](https://github.com/KlassApp-Foundation/KlassApp/pull/1327) `feat/exam-type-catalog` `4db11507` | Test-guard fails: `ExamSetPageAndRosterTest::test_store_redirects_to_the_set_page_grouped_by_class_and_type` does not see "End of Term Examination" on the response. The PR also has no empty-list message and no deploy-time seed, which this item requires. | Fix after items 3–5. Do not build items 7+ on this branch until it is green. |
| 7 | A report per exam set, and a configurable end-of-term composition that does not rewrite released reports | Not started. | — | School setting for admins and head teachers, extending `schools.report_composition`. Default: End of Term only. Weights total 100. | After item 6. |
| 8 | History and integrity: order, audit, lock, reopen, content hash on `record_snapshots` | Not started. | — | Whole item. | After item 7. |
| 9 | KLS filler: exact match only; near-misses and duplicates listed; never guess | Not started. | — | As specified in #1322. | After item 8. |
| 10 | Term workbook importer: drafts only, idempotent, never submits | Not started. | — | Read `~/Downloads/KlassApp-Marks-Workbook.xlsx`. Admins and head teachers: any class. Class teachers: their own class. | After item 9. Depends on items 5 and 6. |
| 11 | `MarksReleased` for n8n: counts only, signed, retried, logged | Not started. | — | `docs/integrations/n8n-marks-released.md` and an importable example workflow. Distinct from the demo webhook in #1323. | After item 10. |
| 12 | Marks overview that mirrors the workbook | Waiting on the design handoff. | — | `~/Downloads/marks-handoff/` is not here. | Do not build a new marks screen before it arrives. |
| 13 | Phone marks entry: one subject, save as you go, retry, resume | Waiting on the design handoff. | — | Same. | Same. |
| 14 | Approve, return and release screens for class teacher, head teacher and admin | Waiting on the design handoff. Service behaviour for class teachers is item 2. | — | Browser proof of the existing preview and confirmation. | Same. |
| 15 | Reports: per set and end-of-term, open, download, send | Waiting on the design handoff. Data rules are item 7. | — | Use the existing delivery path. | Same. |
| 16 | Dashboard and navigation: marks one tap away, clear empty states | Waiting on the design handoff. | — | Each role. | Same. |

## Proof after items 2–6

Not started. On an isolated copy, by clicking: an admin creates an exam set; a subject teacher enters marks at 375px and submits; the class teacher approves and releases; the admin opens the per-set report card. Screenshots at 375 and 1280.

## Recovered work (2026-11-11)

Nothing uncommitted was marks code worth keeping. No "WIP recovered" commit was made.

- `KlassApp-wt-marks` is a clean detached checkout of #1323 (`3c056244`). Its `vendor` is a symlink to the preview worktree, so a test run there executes preview code. Do not use it to judge this work.
- `KlassApp-wt-marks-verify` is a detached checkout of the same commit with its own vendor copy. `MarksReleaseTest` there: 30 tests, 191 assertions, pass.
- `KlassApp-wt-examsets`, the AutoClaw `cursor/marks-level-reports` checkout (31 commits behind its remote), and `docs/curriculum-assessment-and-records` are clean and older than #1321.
- `/private/tmp/pr1240`, `pr1272` and `pr-cards` are listed by git and missing on disk (prunable).
- Dirty trees left alone: this repo's `evidence/demo-report-cards` checkout (untracked screenshots and a local `knowledge.md` edit), `preview/full-stack` (`scripts/demo/preview-reset.sh`, `HANDOFF.md`, sqlite backups), and untracked `build/` directories on the `tg-*` worktrees.
- Stashes are older session leftovers (knowledge notes, landing, onboarding). None are this marks work.
- `HANDOFF.md` in the preview worktree and in `~/Downloads` is not a marks progress file.
- Open marks-phase drafts from before #1321 (#1239–#1244, #1255, #1261, #1272, #1280, #1281) stay open. The pipeline they describe is what #1322 reads on `main`. #1240 and #1272 conflict with their bases. Do not revive them as a second implementation.

## Open pull requests at the start of this handover

72 open. Authors are only `Mucunguzi256` (59) and `Bariyo123` (13, this project's own account). No outside contributor is waiting. These conflict with their base: #1139, #1141, #1147, #1159, #1183, #1240, #1272. The rest have no merge conflict. All of the marks items above are drafts.
