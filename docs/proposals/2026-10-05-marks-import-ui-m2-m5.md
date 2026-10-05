# Addendum: marks import, what this PR adds on top of M1

**Date:** 2026-10-05
**Builds on:** `2026-10-05-marks-import-ui.md` (#985, the plan) and #986 (M1, the template download). Nothing from those is renamed or redone.

## Already on main before this PR

| Piece | Where |
| --- | --- |
| Plan, gap table G1 to G7, phases M1 to M5 | `docs/proposals/2026-10-05-marks-import-ui.md` (#985) |
| M1: template download for a teacher's exam | `GET /teacher/exam/{exam}/marks/template`, route `teacher.exam.marks.template`, `MarksController::downloadTemplate`, headings `registration_number`, `student_name`, `mark`, `?format=csv`, file `marks-template-exam-{id}.{xlsx,csv}` (#986) |
| Shared roster query | `MarksController::examRoster` (#986); the enter page uses it too |
| "Download template" button on the Enter Marks page | `resources/views/teacher/marks/enter.blade.php` (#986) |
| Tests | `tests/Feature/Teacher/MarksTemplateDownloadTest.php` (auth matrix, roster equality, registration column, empty mark cells) |

## Added by this PR (M2 to M5 in one change)

| Plan item | What this PR does |
| --- | --- |
| M2 parse and validate, no writes | `App\Services\MarksImport\MarksImportService::preview()` plus `MarksSheetReader` (reads xlsx, xls, csv through the existing Maatwebsite Excel). Row outcomes and reason codes, range 0 to 100, duplicates, unknown or foreign registration numbers. |
| M3 upload route and preview UI | Page, preview, confirm and result routes and views for teacher and admin; the preview saves nothing. |
| M4 commit | `commit()` re-validates against the database, runs in a transaction, honours the lock, the correction reason on submitted exams, grade computation, activity log `marks.imported`, and the `MarksUpdated` event. Idempotent. |
| M5 admin entry point | "Import from spreadsheet" on the admin exams list, and an admin template route that uses the same headings and roster as #986. |
| Entry points | A second button beside #986's "Download template" on the Enter Marks page, and on the teacher exam list. |

## Decisions taken to match main

- **No name matching.** The plan says registration number only (standing rules 4 and 18). An earlier draft of this change matched exact unique names; that was removed. A row without a registration number is skipped and reported (`no_identifier`).
- **Template shape, roster and filename are #986's.** The teacher upload page links to `teacher.exam.marks.template`; there is no second teacher template route. The admin template reuses the same headings, row shape, roster rule and filename pattern, and a test asserts the two rosters are equal. The service's import roster uses the same rule (usergroup 6, same school, standard link of the exam's standard and section), with no status filter, like the entry page.
- **Import parser accepts #986's headings** (`registration_number`, `student_name`, `mark`), and a test downloads #986's CSV, fills the mark column and imports it.

## Differences from the plan, for review

- **Skip and report, not all-or-nothing, for bad rows.** The plan's M4 says any failure rolls back. Here a row that cannot be saved is skipped and listed; the database writes still run in one transaction, so a failure mid-write rolls everything back. Say if you prefer to refuse the whole file when any row is invalid.
- **Overwriting a different saved mark is opt-in** (a checkbox on the preview), which the plan does not cover.
- **G5 (range check on the existing bulk form) and G6 (dead `out_of` input) are untouched.** Behaviour of the existing entry screens is unchanged. The import enforces 0 to 100 on its own path.
- **Exam status:** an import moves `undone` to `done` only and never to `submitted`.
- **Parent WhatsApp grade messages** are not sent from an import.
- **Duplicate code to fold later:** `MarksController::downloadTemplate` and the admin template use two small code paths for the same rows. Folding the teacher route onto the service is a follow-up that would touch #986's code.
- **#986's "Download template" link is 38px high** (`py-2 text-sm`); the import button beside it is 44px. Not changed here.
