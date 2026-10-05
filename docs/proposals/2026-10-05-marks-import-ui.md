# Proposal: Marks Import UI

**Date:** 2026-10-05
**Status:** proposal (not yet implemented)
**Scope:** in-app spreadsheet import for exam marks, teacher-first

## 1. Problem

Marks entry today is one number at a time: a teacher opens an exam's entry page,
types a mark per student, and posts the whole form. For a 60-student class this is
60 keystrokes per subject, with no way to work offline in a spreadsheet — which is
how teachers actually collect marks (subject meetings, shared sheets, pasted
column extracts).

The only spreadsheet-based marks ingestion in the codebase is a one-off Artisan
command written for one school (`app/Console/Commands/ImportKabaleMarks.php`,
school 104, plus its `ImportKabelMarks` variant). It is not reachable from any UI,
not tenant-generic, and not available to any other school. Every other roster-scale
import (teachers, users, promotion, holidays, questions) already has a proper
`app/Imports/*` class behind a UI — marks does not.

## 2. Current state (as built)

### Entry and save path (teacher)
- **Routes:** `routes/teacher.php:414-430` — list, `GET …/marks/enter`, `POST …/marks/save`, view.
- **Entry UI:** `resources/views/teacher/marks/enter.blade.php` — a plain HTML form
  (`method=POST` to `teacher.exam.marks.save`) rendering one number input per
  student (`marks[{student_id}]`). Nursery classes get domain-rating selects
  (`assessments[student][domain]`) instead of numbers. An `out_of[{student_id}]`
  input is rendered (line 115) but **no save-path code consumes it** — dead input.
- **Save:** `MarksController::saveExamMarks` (`app/Http/Controllers/Teacher/MarksController.php:185`)
  1. `ExamAuthorization::authorizeOrAbort` — same school AND (`exam.teacher_id`
     = actor OR actor is class teacher of the exam's section)
     (`app/Services/ExamAuthorization.php`).
  2. `assertSubmittedStudentsBelongToSchool` + `checkSubmissionLocked`.
  3. Over a submitted exam, a `correction_reason` (10–500 chars) is mandatory.
  4. Nursery branch → `NurseryAssessment::updateOrCreate` with a rating whitelist.
  5. Standard branch → `Marks::updateOrCreate` keyed on
     `(student_id, exam_id, school_id, subject_id)`, grade computed by
     `GradingSystemService::grade()`. Empty values are skipped.
  6. `Exam::changeExamStatus()` (`app/Models/Academics/Exam.php:150`):
     `undone → done → submitted`.
  7. Activity logged via `logMarksActivity`.
- **Single correction:** `editMark` / `updateMark` (`:406`, `:435`) — the *only*
  path with range validation today: `numeric|min:0|max:100` (line 456, with an
  "adjust rules to your system" comment). **Bulk save validates no range at all.**
- **Exam list:** `teacher-exam-list.blade.php`; per-exam view/edit:
  `view.blade.php`, `view2.blade.php`, `edit-single.blade.php`.

### Admin submission workflow
- `app/Http/Controllers/Admin/ExamMarksSubmissionController.php` — per
  exam+class+subject submission rows: `lock`, `reopen`, `approve`, `reject`;
  `status` ∈ {locked, reopened}, `approval_status` ∈ {pending, approved, rejected}.
  Exams sit at `status = submitted` when listed.

### Spreadsheet machinery (reusable)
- `maatwebsite/excel: 3.1.x-dev` is installed (`composer.json:45`).
- Exports exist: `app/Exports/MarksheetExport.php` + `app/Services/ExamMarksheetService.php`
  (headings = `STUDENT NAME` + subject names; **no admission-number column**).
- Imports exist for teachers/users/promotion/holidays/questions under
  `app/Imports/` — the pattern to copy. **No marks import class exists.**
- The Kabale command shows the parsing design that generalises: dynamic subject
  columns detected from headers (or a `--map` JSON), `--dry-run`, subject
  existence enforced per school.

### Invariants already tested
`tests/Feature/Import/MarksImportSubjectIntegrityTest.php` protects the rules a
UI import must keep:
- each spreadsheet subject column resolves to exactly one subject — no
  cross-column contamination (6 columns → 6 distinct `subject_id`s);
- unknown columns are *reported missing* (e.g. `physics`), never guessed;
- re-import is idempotent (no duplicate `Marks` rows);
- subject auto-creation is scoped to the school (no cross-tenant duplicates).

### Roster scoping
- `app/Services/RosterScopeService.php` provides role-scoped section/stream
  visibility; the entry page itself scopes students by
  `usergroup_id = 6` + `studentAcademic.standardLink` matching the exam's
  `standard_id` **and** `section_id` (`MarksController:128-145`).
- Canonical student identifier: `users.registration_number` (KLS#######,
  validated on save — `app/Models/User.php:80-92`, display helper at `:387`).

## 3. Gaps the UI must close

| # | Gap | Today |
|---|---|---|
| G1 | No in-app bulk entry | manual per-student form only |
| G2 | No template download | teacher must guess the format |
| G3 | No preview / dry-run | Kabale command has `--dry-run`; UI has nothing |
| G4 | No row-level errors | whole-form post, no per-row feedback |
| G5 | No range validation on bulk save | `min:0|max:100` only on single edit |
| G6 | Dead `out_of` input | rendered, never read |
| G7 | No admission-number column in exports | roster matching by name only |

## 4. Proposed design

### UX flow (teacher, per exam)
1. **Entry point** — an "Import spreadsheet" button next to the existing entry
   form on `teacher.exam.marks.enter` (same exam context, same authorization).
2. **Download template** — GET endpoint returns an XLSX/CSV for *this exam*:
   columns `registration_number`, `student_name`, then the exam's subject/one
   mark column (single-subject exam = one mark column; the template mirrors what
   the save path accepts). One row per roster student, roster from the same
   query as `enterExamMarks`.
3. **Upload** — POST the filled sheet. Server parses (maatwebsite/excel) and
   **validates only — writes nothing** (dry run):
   - each row's `registration_number` must match a student *in this exam's
     roster* (never a name match — standing rule #18);
   - mark must be numeric and within the system's valid range (align with the
     single-edit rule, currently 0–100);
   - unknown/unparsable rows are errors, not guesses;
   - subject column must map to the exam's subject — no free-text subject
     creation from a spreadsheet (keeps the subject-integrity invariant).
4. **Preview** — table of parsed rows: valid rows green, invalid rows inline
   with the exact reason ("KLS0010427: mark '120' out of range",
   "row 14: registration number not in this class"). Counts: N valid / M errors.
   Buttons: *Back*, *Import N valid rows*.
5. **Commit** — reuse the exact write pipeline of `saveExamMarks`:
   `ExamAuthorization` → school/lock checks → correction-reason gate when the
   exam is already submitted → `Marks::updateOrCreate` → grade computation →
   `changeExamStatus` → activity log. Rows are written in one DB transaction;
   any mid-flight failure rolls back entirely (all-or-nothing, reported).

### Backend shape
- `app/Imports/MarksSpreadsheetImport.php` — parse + collect row errors
  (pattern: `TeachersImport`), no writes in this class.
- `app/Services/MarksImportService.php` — roster matching, range validation,
  transactional commit through the shared save helpers.
- Routes under the existing teacher marks group:
  `GET …/marks/template`, `POST …/marks/import` (preview), confirm via the same
  POST carrying an explicit `confirm=1` or a signed one-shot token — decide in
  PR3; no second session-ful state store needed.
- Nursery exams: **out of scope for v1** (domain-rating matrices don't fit a
  flat mark column); the import button simply isn't rendered for nursery exams.

### Security / tenancy invariants (every PR)
- One authorization gate: `ExamAuthorization` on every new route.
- `school_id` scoping end-to-end (standing rule #13): roster query, marks
  upsert keys, subject lookups.
- Positive-equality status filters only (standing rule #6).
- `section_id` for class identity (standing rule #5).
- Primary-key matching for students — `registration_number` equality within the
  scoped roster, never fuzzy name matching (standing rules #4, #18).
- No production data in tests — seeded fixtures only (standing rule #9).

## 5. Phased delivery (small PRs, one at a time)

| PR | Content | Tests (failing-first where behaviour) |
|---|---|---|
| **M1** | Template download endpoint + blade button (link only) | route authorization (other-teacher 403, cross-school 403), template row set == enter-page roster, registration_number column present |
| **M2** | `MarksSpreadsheetImport` + `MarksImportService::validate()` — pure parse/validate, no route yet | subject-integrity invariants reused; range violations; unknown registration numbers; idempotent dry-run (no writes) |
| **M3** | Upload route + preview UI on the enter page | feature test: preview writes nothing; row errors rendered; cross-teacher rejected |
| **M4** | Commit path (transactional write through save helpers) + activity log | failing-first: committed rows create/update `Marks` with correct subject; correction-reason required over submitted exam; locked submission blocked; rollback on mid-batch failure; bulk range now enforced (G5) |
| **M5** (optional) | Admin-side entry point on `ExamMarksSubmissionController@show` | admin authorization matrix |

G5 (range validation on the *existing* bulk form) lands in M4 as a shared
validator so the manual form and the import enforce one rule — that part changes
existing behaviour, so it gets its own failing-first test in M4.
G6 (dead `out_of`) is removed in M1 (display-only cleanup, no behaviour).

## 6. Test plan mapping

- Extend `Tests\Feature\Import\MarksImportSubjectIntegrityTest` patterns — the
  four invariants apply unchanged; new tests live beside it.
- New `tests/Feature/Import/MarksSpreadsheetImportTest.php` (unit-ish, service).
- New `tests/Feature/Teacher/MarksImportRoutesTest.php` (route + preview +
  commit feature tests, factory roster).
- Full suite via `bash scripts/test-guard.sh` before any merge; zero new
  failures vs `tests/known-failures.txt`.

## 7. Open questions (resolve during M1/M2, not blockers)

1. **Range source:** hard 0–100 vs per-exam `out_of`? Proposal: start with the
   same rule as single edit (0–100) for consistency; revisit when exam-level
   out-of exists as data (it does not today — G6 shows `out_of` is unconsumed).
2. **Multi-subject sheets:** v1 is one exam (class+subject) per upload — matches
   the exam model. A whole-class multi-subject sheet is a report-card shaped
   problem, deliberately deferred.
3. **Column format for XLSX vs CSV:** support both from day one (maatwebsite
   handles both); confirm template produces XLSX with a CSV fallback link if
   needed.
