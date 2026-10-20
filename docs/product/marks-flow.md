# Marks flow: proposal (Phase 1)

Status: proposal only, no code changes. Based on an audit of `main` at 073e232
(October 2026): code reading plus a local run on the provisioned demo school. The full
audit (screen-by-screen table, file:line evidence) is kept outside the repo; the
references below use the audit's numbering (L1-L10 for release leaks, section 3 for
calculations).

## 1. Why

Three things go wrong today, all visible in a demo:

1. **Marks reach parents before they are released.** The only release check is in the
   demo WhatsApp follow-along (`LatestExamMarks::latestReleasedExam`). The parent portal,
   the student portal, WhatsApp GRADES and REPORT, Toshi, and an automatic "Results
   Published" WhatsApp sent on every teacher save all ignore it (L1-L8).
2. **The same student gets different numbers on different screens.** Grades, totals,
   averages, positions and divisions are computed in about ten places with different
   rules (missing mark = 0 or ignored, integer vs decimal grade lookup, hard-coded vs
   school scale, ranking pools that include other terms). Two report-card routes gave
   one demo student 2nd and 7th place for the same exam.
3. **Report cards assume a data shape the app no longer writes.** An exam row is one
   subject, but the report-card pipeline treats one exam row as "the EOT exam": the
   end-of-term table shows one subject, aggregate and division come from that subject,
   and the term is "latest created" rather than the term the admin picked.

## 2. The flow

One "sitting" = one exam type in one term for one class (all of its per-subject exam
rows). Storage stays one exam row per subject; every screen groups by sitting.

| Step | Who | Screen (target) | Rule |
|---|---|---|---|
| 1. Enter or upload | subject teacher, class teacher, admin | `teacher.exam.marks.enter`, import (teacher and admin) | grade derived from the school scale; locked rows refuse edits |
| 2. Submit | the teacher | "Submit marks" on the entry page | writes `exam_marks_submissions` (status submitted); no parent message |
| 3. Review and approve | admin | Approvals queue for marks (menu link), per class and subject | approve, reject with reason (reopens), lock |
| 4. Combined mark sheet | admin, class teacher | one sheet by class and stream, per sitting or per term report | numbers from the results service (section 4) |
| 5. Student result view | admin, class teacher | per-student page from the same results | same numbers as the report card |
| 6. Release | admin (any class), class teacher (own class) | `marks/releases/new` | needs approval of every subject in scope (admin may override with a reason) |
| 7. Parents and students see results | parent, student | portal, WhatsApp MARKS/GRADES/REPORT, Toshi, report-card PDF | every output asks the release gate (section 5) |

Quick actions, one per step: Enter marks (exam list filtered to "needs marks"), Submit
(on the entry page), Review submissions (admin menu, count badge), Mark sheet, Release
marks, Report cards. The dashboard tiles link to these and to nothing else.

## 3. Keep, merge or remove

| Today (audit section) | Decision | Notes |
|---|---|---|
| Teacher exam list `/teacher/exam`, `/teacher/exam/marks` (2a) | Keep one route | the second becomes a redirect |
| Teacher entry, edit-one, view, template, import (2a) | Keep | add "Submit" that writes the submission row |
| Admin import (2a) | Keep | same service as teacher |
| `TogglekStatus` / `exams.status` as "submitted" (2a) | Remove | status derived from submission rows |
| Toshi teacher/admin enter marks (2a) | Keep, fix | go through the same save path (grade, lock, school scope) |
| Admin submissions list and detail (2a) | Keep, fix, link | list submission rows, not `exams.status`; add to the Approvals menu |
| `/admin/marks` filter form + `/admin/marks/filter` grid (2b) | Merge into the combined mark sheet | grid today hides every mark unless the exam was toggled |
| `/admin/marks/classes` (2b) | Merge | becomes the default view of the combined mark sheet |
| Release page combined sheet (2b) | Keep as the model | move its numbers onto the results service |
| PDF marksheet `/admin/marks/download` (2b) | Merge | an export of the combined sheet |
| Combined marksheet xlsx, admin and teacher (2b) | Merge | same export; today fills one subject |
| Single-exam marksheet, admin and teacher (2b) | Keep | per-subject sheet is still useful to teachers |
| Per-student page `/admin/marks/student/...` (2b) | Replace | today shows another term's marks; rebuild on the results service |
| Per-student report `/admin/report/student/...` (2c) | Remove | duplicate of `reports/cards/{stdLink}/student/{learner}` with its own ranking |
| Report cards admin, teacher, batch job (2c) | Keep, fix | one pipeline, explicit term, results service, release-aware |
| Legacy showmark / showallmark / comparemark routes (2b) | Remove | reference models that no longer exist |
| Unrouted `WhatsAppController::grades`, `::report`, `notifyGradesPublished`, `GradesPublished`, `schoolMarksOverview` | Remove | dead and ungated |
| Automatic "Results Published" on save (L6) | Remove | release is the only parent trigger |
| Settings > Exam types (2d) | Keep, wire up | today it changes nothing; the results service reads it |
| GET `/admin/promotion` (2d) | Change to POST | it mutates on GET |
| Teacher dashboard "Upcoming Exams" link (5) | Fix | points at a 404 |

## 4. One source of truth for results

Proposed: `App\Services\Results\ResultsService` (read-only, no I/O besides queries) plus
small value objects. Every screen, export, PDF, message and Toshi tool that shows a grade,
total, average, position or division calls it.

**Inputs**
- school, class (section) and optional stream (standard link), academic term;
- what to include: one sitting (exam type) or a term report (the exam types that
  contribute, from the school's exam-type setting);
- a results policy resolved per school and class level: grading scale, points source,
  missing-mark rule, rounding, ranking basis and tie rule, division rule;
- optional: one student (still computes the class so positions are real).

**Outputs** (`ResultSheet`)
- subjects in report order;
- per student: per-subject cells (mark, grade, points, status: marked, missing, absent),
  total, subjects counted, average, aggregate, division, class position, stream
  position, "out of" counts for both;
- per sitting inside a term report (for the MID rows of the report card);
- class summary (averages per subject, completeness: subjects with marks vs expected).

**Rules it fixes once**
- grade looked up on the decimal mark against the scale with no gaps (store the grade
  only as a cache, or drop it);
- missing marks never count as zero; they are shown and excluded, and the division rule
  decides whether a missing core subject means "no division";
- positions by the policy's ranking basis over the students of that class (or stream)
  in that term only; ties share a position (1, 2, 2, 4);
- one rounding rule for display, computed on unrounded values.

**Replaces**: `Admin\MarksController::classExamOverview` totals/positions,
`MarksReportService::buildMarksheet`, `StudentReportHelperService::{totalMarks, position,
grade}`, `GradingSystemService::aggregates`, `StudentReportCardService` inline division,
MID/stream ranking and `computePositionMap` (and the job's copy), `DownloadStudentReport`
and `GetStudentsMarks` ranking, `MarksReleaseService::sheetRow/overallGrade`,
`DemoBindReplies::renderMarks` totals, `WhatsAppController::sendGrades/sendStudentGrades`
averages and ranks, `OutboundWhatsAppService` composers, `ParentPortalService::grades`
grouping, `CombinedMarksheetExport`, dashboard "Performance by class" and "Report cards
ready", `ReportKpiSnapshotService` totals.

It must handle both stored shapes (one exam row per subject, and the legacy one row for
all subjects) by reading marks by `(exam type, term, section, subject)`, never by "the"
exam row.

## 5. One release gate

Proposed: `App\Services\Results\ReleaseGate`, built from today's
`LatestExamMarks::latestReleasedExam`.

- `releasedSittings(User $student): Collection` — sittings whose `marks_releases` row is
  `released` and covers the student (scope school, or class with the student's standard
  link).
- `isReleased(User $student, sitting): bool`, and `isTermReportReleased(student, term)`
  — true when the term's report-contributing sitting is released.
- Every parent- or student-facing output filters through it: parent portal and
  dashboard panel, student portal, WhatsApp MARKS/GRADES/REPORT for every role, Toshi
  parent and student tools, report-card PDF for parents (portal or WhatsApp), alumni.
- Staff screens are not gated, but show "Not released" badges.
- Release requires approval: `MarksReleaseService::authorise` refuses a release while a
  subject in scope is not approved (admin override with a reason, logged).
- Release is a school action, not a WhatsApp feature: a release already completes for
  real schools (messages are skipped), so the gate works for them from day one.
  Messaging real-school parents on release stays behind its own switch.
- Transition: before turning the gate on, backfill `released` records for every sitting
  of past terms, so existing schools do not lose history.

## 6. Build plan (one PR each)

| # | PR | Estimate | Depends on |
|---|---|---|---|
| 1 | `ReleaseGate` service + tests; gate parent portal, dashboard panel, student portal, Toshi parent/student tools (L1, L2, L7, L8); past-term backfill command | 1.5 d | – |
| 2 | Gate WhatsApp GRADES/MARKS/REPORT for parents and students (L3-L5); remove the automatic "Results Published" on save (L6) | 1 d | 1 |
| 3 | Delete dead ungated paths (unrouted WhatsApp grades/report, `GradesPublished`, legacy showmark routes, `schoolMarksOverview`) | 0.5 d | – |
| 4 | Release requires approval (with override); release page shows approval state per subject | 1 d | 7 |
| 5 | `ResultsService` + policy object, tests on both data shapes, ties, missing marks, decimals | 2.5 d | – |
| 6 | Report cards on `ResultsService`: explicit term everywhere (index, single, batch job), full EOT table, aggregate/division from all subjects, MID rows per sitting; parent copies gated (L10) | 2 d | 1, 5 |
| 7 | Submission flow: teacher Submit writes submission rows; admin queue lists rows; menu link with count; drop `exams.status` toggle | 1.5 d | – |
| 8 | Combined mark sheet: merge admin grid, class marks, PDF and xlsx exports onto `ResultsService`; per-student page rebuilt; remove `/admin/report/student/...` | 2 d | 5 |
| 9 | Release page, WhatsApp and dashboard tiles read `ResultsService` | 1 d | 5, 8 |
| 10 | Quick actions and menus: fix 404, one Enter marks target, Approvals entry, POST for promotion | 0.5 d | 7, 8 |
| 11 | Toshi mark entry through the shared save path (grade, lock, school scope) | 0.5 d | 5 |
| 12 | Grades derived at read time; backfill stored grades; wire Settings > Exam types | 1 d | 5 |

Total about 15 working days. PRs 1-3 close the release leaks first (3 days) and can ship
before any calculation change. PR 3 and PR 7 can run in parallel with 5.

## 7. Configuration notes (for the future package system)

Notes only; the package system itself is designed elsewhere.

- **Grading scales**: already per school and class level (`school_grading_systems`);
  should also carry the points source and whether bands are integer or decimal. The
  hard-coded bands in the release sheet and the defaults in `config/grading_uganda.php`
  become package defaults.
- **Division rules**: today hard-coded (points thresholds 12/24/28/32, no subject
  minimum). Should become a per-curriculum rule: thresholds, number of subjects counted,
  required subjects, what a missing or failed core subject does.
- **Ranking**: basis (aggregate or total or average), tie rule, pool (class or stream),
  whether positions are shown at all.
- **Exam types and contribution**: `exam_types` is global and `contributes_to_report_total`
  is global; the per-school preference exists but is unused. Should be per school (or per
  package), with weights if a term report mixes sittings.
- **Term structure**: number of terms, which sittings appear on a term report (MID rows,
  EOT table), labels ("OCT MID" is derived from the date today).
- **Report templates**: two Blade templates selected per school; layout, columns
  (AGG/DIVISION visibility is decided by `grading_style` or class name today), comments
  bank, signatures, logo.
- **Curricula and levels**: nursery (domain ratings), lower primary (no aggregate),
  upper primary, O-level, A-level are detected by class names today
  (`GradingHelper::levelTypeForStandard`); should be explicit configuration.
- **Release policy**: approval required or not, who may release, whether release
  messages go out and on which channels.
