# Marks collection notes: how marks are collected today, and a proposed class-teacher layout

Status: research note, 2026-10-05. Nothing here is built beyond the per-exam spreadsheet
import (PR #984). The proposal in the last section is not scheduled or estimated.

## How KlassApp collects marks today (verified in the repository)

- **One mark row per student, per exam, per subject.** `App\Models\Academics\Marks` stores
  `marks`, `grade`, `exam_id`, `subject_id`, `section_id`, `teacher_id`, `school_id`. In the
  current model a "subject" in that row is really a per-subject exam.
- **Exams are per class + subject.** `App\Models\Academics\Exam` carries `standard_id`
  (level tier), `section_id` (the class), `subject_id`, `academic_term_id`, `exam_type_id`,
  `teacher_id`, and a status (`undone` -> `done` -> `submitted`). The teacher screens list
  a teacher's exams per class and open one entry form per exam
  (`/teacher/exam/{exam}/marks/enter`); admins have the school-wide exams area
  (`/admin/exams`) with marksheet downloads.
- **Assessment types and terms.** `ExamType` rows (BOT/MID/EOT style) with a
  `contributes_to_report_total` flag; terms are `AcademicTerm` rows within an
  `AcademicYear` (three terms per year). Collection is therefore per exam per subject,
  per term; the assessment type is a property of the exam.
- **Nursery classes** are assessed by domain ratings (Excellent/Good/Satisfactory/Needs
  Improvement) instead of 0-100 marks; the same route switches to "Enter Assessments".
  The nursery skills model is a known seam (report cards pass an empty `nurseryAssessments`
  collection today).
- **Maximum marks: 100.** The entry form caps inputs at 100 and the import service enforces
  0-100.
- **Grading** is per school: `school_grading_systems` bands (min_score/max_score -> grade,
  e.g. A) and a grade is computed on every save via `GradingSystemService`.
- **Lock and approval.** `ExamMarksSubmission` rows (draft/submitted/locked plus an approval
  status) gate edits; submitted exams require a reason for corrections; locked exams cannot
  be edited until reopened.
- **What report cards read:** `StudentReportCardService` loads the saved `Marks` rows for the
  report scope and renders subject rows with grades; the formal and warm templates are fixed
  layouts (MID columns by exam-type code, EOT appended, totals and ranks computed in the
  service).
- **Entry modes today:** web forms per exam; the spreadsheet import from PR #984 (per exam,
  matched by KLS number); WhatsApp/Toshi flows elsewhere.

## Reference platforms used

- **Primary reference: KlassApp's own code**, read directly for every statement above.
- **Fork origin: GeGoK12** (GoGo Technologies, India). The marks module (controllers, models,
  views, grade logic) is largely inherited; see `docs/project-provenance.md` for the caveats.
  The GeGoK12 upstream repository was not fetched for this note, so statements about GeGoK12
  itself are limited to what the provenance notes record.
- **Comparison research: PR #732** (branch `docs/ui-research-independent-findings`,
  `docs/ui-ecosystem-comparison.md`), which reviewed Academico's report structure
  (GradeType/EvaluationType presets: report columns and weightings as data) and its
  grade-edit-as-grid pattern (R1/R5). Those two findings informed the proposal below.

## Proposal (not built): class-teacher layout, one column per subject

Class teachers typically hold the whole class list and every subject's marks for one
assessment at once. Proposal for a second template layout, complementing the per-subject
template:

- **A "Class grid" sheet:** rows are students (KLS number + name), columns are one per
  subject taught in that class, with an Exam-info-style header block naming the school,
  class, assessment (exam type), term, and a template key (class + assessment + checksum).
- **Importing the grid** writes one mark per student per subject through the same service
  path (validate -> preview -> confirm; per-cell skips reported with reasons; locked and
  submitted rules, grading, and the audit record unchanged). The column-to-subject map and
  the class/assessment key are checked before the preview, exactly like the exam key today.
- **Open questions to settle before building:** multi-stream classes; subjects not taught in
  a term; partial columns (some teachers fill only their subject); and who may confirm which
  columns when a grid spans several subjects. Nursery classes keep the ratings flow and are
  out of scope for this layout.
