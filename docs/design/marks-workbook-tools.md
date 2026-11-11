# Marks workbook: pipeline, roles and three tools

**Design note only.** No app code. Everything about today's behaviour is read from `main` at `2aaf327b` (11 November 2026) unless a line says otherwise. The workbook discussed is `KlassApp-Marks-Workbook.xlsx`: Setup, one tab per class, A KLS number, B learner name, three exam blocks C:K, L:T, U:AC, each six subjects plus Total, Average, Position; a mark is a number, `ABS` or empty.

---

## 1. The pipeline today

### 1.1 Exam types

- The catalog is global, not per school: `exam_types.school_id` is nullable and `ExamType::visibleToSchool()` returns shared rows plus the school's own (`app/Models/Academics/ExamType.php:20-25`). A row with `school_id` null is shared (`:32`).
- The catalog content lives in `app/Reference/ExamTypeCatalog.php` and is written by the seeder `database/seeders/result/ExamTypes.php:12-19`, called from `database/seeders/ReferenceSeeder.php:39`.
- A school can add its own types in Settings (`app/Http/Controllers/Admin/Setting/ExamTypeController.php:23`, `:124`).
- **Nothing seeds or copies types when a school is created.** `SchoolSignupBootstrapService` and `RegisterController` contain no `ExamType` reference. On a database that has never run the reference seeder, a new school sees an empty list. That is the code shape behind T-141.

### 1.2 Exam sets and exams

- An exam set is one term's exams: `ExamSetService::create()` takes the school, term, chosen type ids and optional class ids (`app/Services/Academics/ExamSetService.php:26`).
- It builds the list of subjects in scope (`:30-31`, `subjectsInScope()` `:127-136`) and, in one transaction, creates the set (`:38`) and one `Exam` per subject per type (`:46-72`), skipping duplicates (`:54-55`).
- The teacher on each exam comes from the teacher–subject–class link, falling back to the actor (`:47`, `teacherFor()`).
- Routes: index, create, store, show and delete-one-exam (`routes/admin.php:927-931`). Admin routes sit behind the `schooladmin` middleware, which admits usergroups 1, 3 and 4 (`app/Http/Middleware/MustBeSchoolAdmin.php:22`), so a SchoolSubadmin (4) can create exam sets today.

### 1.3 Marks entry and the absent flag

- Marks are saved through one writer: `ExamMarkWriter::save()` (`app/Services/Marks/ExamMarkWriter.php:28`).
- `ABS` is a real state, not a zero: the writer sets `marks = null, absent = true` for it (`:59-61`) and `absent = false` for a number (`:76-78`).
- Once a class/subject sitting is submitted, approved or locked, writes are refused (`:89-103`), with a message naming return/reopen (`:120`).
- Entry is authorised by `ExamAuthorization::canActOnExam()`: same school, and either the exam's `teacher_id` is the actor (`app/Services/ExamAuthorization.php:26-28`) or the actor is the class teacher for that section/stream (`:33-63`).
- Teacher routes call that check before showing, saving or submitting (`app/Http/Controllers/Teacher/MarksController.php:235-243`, `:306-314`).

### 1.4 Saved → Submitted → Approved → Released

- A submission row is per exam, class and subject (`exam_marks_submissions`), with `status` = `draft`/`submitted`/`locked`/`reopened` and `approval_status` = pending/approved/rejected (`app/Models/Academics/ExamMarksSubmission.php:97-156`).
- **Submit** requires saved work and a complete, within-maximum grid; it sets `status = submitted` and notifies admins (`app/Services/Marks/MarksSubmissionService.php:55-92`).
- **Approve** is restricted to usergroups 1, 2 and 3 (`:23`, `assertSchoolAdmin()` `:418-425`), only for submitted/locked rows (`:120-124`), and notifies the teacher (`:133-138`).
- **Return ("send back")** exists: `returnToTeacher()` requires a reason, sets `reopened` + `approval_status = rejected`, stores the note and notifies the teacher (`:181-221`). Route: `admin.marks.submissions.reject` (`routes/admin.php:954`).
- **Lock / reopen** are separate admin actions (`:223`, `:256`), exposed at `routes/admin.php:950-951`. The Approvals page is `/admin/marks/submissions` (`routes/admin.php:948`), with a per-exam detail page (`:949`) and bulk approve (`:952`).
- **Release** is a separate record: `MarksReleaseService::create()` refuses until every subject in scope is approved (`app/Services/MarksRelease/MarksReleaseService.php:195-198`, `requireApproval()` `:315-331`) and, for a past sitting, until the past confirmation is ticked (`:479-489`). The UI is create → preview → store (`routes/admin.php:937-941`; `app/Http/Controllers/MarksReleaseController.php:41-125`).
- Release is scoped to a class or the whole school (`MarksReleaseService.php:195-215`), and re-releasing an already released sitting requires a reason (`MarksReleaseController.php:84-90`).

### 1.5 Report cards from released marks

- The class report screen reads one released sitting only: "released marks only… a class or stream whose sitting is not released shows the not released state and no marks" (`app/Services/ReportCards/ClassReportScreen.php:18-22`, `:103-139`).
- Released means `marks_releases.status = released`; scope `school` covers every class of that type and term, scope `class` covers the section (`app/Services/Results/EloquentReleaseGate.php:18-60`).
- PDF generation and downloads hang off that screen (`app/Http/Controllers/Admin/ReportCardsController.php:371` `generatePdf()`, `:151-246`).
- Parents see released results only: `ParentPortalService` injects the release gate and applies it before returning anything (`app/Services/Parent/ParentPortalService.php:32`, `:457-464`).

### 1.6 The existing per-exam importer

- Template: `KLS number`, `student_name`, `Mark (out of 100)` (`app/Services/MarksImport/MarksImportService.php:119-121`), downloaded from `public/templates/exam-upload-template.xlsx`.
- Who may import: usergroups 1 and 3, or a teacher authorised on that exam (`:52-53`, `canImport()` `:90-107`).
- What it validates: KLS number is required — "students are matched by KLS number, not by name" (`:69`); blank mark, non-number, out of 0–100, no grading band, unknown student, not in class, duplicate in file, duplicate existing, and unconfirmed overwrite are all reported per row with a reason code (`:62-74`, range check `:620`).
- It previews to a plan and commits on confirmation (`:232`, `:275`), and it does **not** accept `ABS`: an `ABS` cell is "not a number". It writes marks but never submits, approves or releases.

### 1.7 What is missing for a whole-term workbook

1. No parser for Setup or for three exam blocks with six subject columns each.
2. No mapping from a block to an exam type or exam set, and no creation of missing exams from a workbook.
3. No `ABS` handling in the importer.
4. No idempotency across a term (the per-exam plan is per exam).
5. No KLS filling step for learners who have no number yet.
6. No release event for an external workflow; the only webhook is demo-only and sends learner codes (`MarksReleaseService.php:1069-1115`).

---

## 2. Who does what, at each stage

Legend: **✓ matches Rasta's rule**, **≠ differs**, **– not enforced / not allowed but should be**, **n/a**.

### 2.1 Rasta's rule (the target)

- Subject teacher: enters and saves their own subjects/classes, then submits.
- Class teacher: the same for their own class in any subject, and approves and releases their own class.
- Admin: every stage for any class, including exam sets, release of a class or the whole school, report cards.
- Parents and students: see marks and report cards only after release.

### 2.2 What the code enforces today

| Stage | Subject teacher | Class teacher | Head teacher (SchoolSubadmin, ug4) | Admin (ug3) | Parent | Student |
| --- | --- | --- | --- | --- | --- | --- |
| **Set up exams** | – teacher routes exist but scope is class-teacher sections only, so a subject teacher gets no classes (`ExamSetController.php:370-381`) | **≠ can create sets for own class** (`routes/teacher.php:441-444`; `ExamSetController.php:370-381`) | **≠ can create sets for every class** (ug4 passes `schooladmin`; `routes/admin.php:927-931`) | ✓ `routes/admin.php:927-931` | n/a | n/a |
| **Record and save** | ✓ own exams (`ExamAuthorization.php:26-28`) | ✓ any subject in own class (`:33-63`) | ≠ per-student add only, via the admin student profile (`routes/admin.php:280`); no class grid | ✓ per-student add (`routes/admin.php:280`); no class grid on admin screens | n/a | n/a |
| **Submit** | ✓ (`MarksController.php:145`; `MarksSubmissionService.php:55`) | ✓ can submit any exam in the class (`ExamAuthorization` class-teacher branch) | – | n/a (admin does not submit) | n/a | n/a |
| **Approve** | ≠ not allowed | **≠ not allowed** — admin groups only (`MarksSubmissionService.php:23`, `:418-425`) | **≠ not allowed** | ✓ (`:97-142`; `routes/admin.php:952-953`) | n/a | n/a |
| **Release** | ✓ not allowed (no class links) | ✓ **only if** `teachersMayReleaseMarks` (default true) and they are the class teacher (`MarksReleaseService.php:79-145`; `SiteHelper.php:262-273`) | **≠ blocked** — `links()` returns empty for ug4, so `canRelease()` is false (`MarksReleaseService.php:79-145`) | ✓ class or whole school (`:195-215`) | n/a | n/a |
| **Report cards** | – (teacher report page is scoped to their classes) | – | ≠ can open and generate on the admin screens (ug4 passes `schooladmin`; no separate check) | ✓ (`ReportCardsController.php:371`) | ✓ released only (`ParentPortalService.php:457`) | ✓ released only (release gate) |

**Where the head teacher sits today:** the app has a Laratrust `principal` / headteacher role (`database/seeders/RolesTableSeeder.php:29-33`), but **no marks gate reads roles**; every marks decision uses `usergroup_id`. On a `SchoolSubadmin` account (ug4) a head teacher can open admin marks screens, add a mark on a student profile, and create exam sets, but every approve, return and release call rejects them: `assertSchoolAdmin` accepts only 1/2/3 (`MarksSubmissionService.php:23`), and `links()` accepts only ug3 or ug5 (`MarksReleaseService.php:84-145`). If the head teacher is on a Teacher account (ug5) instead, every marks action falls back to the teacher rules above; the `principal` role changes nothing.

### 2.3 Smallest change for each difference

1. **Class teacher approves own class.** In `MarksSubmissionService::approve()` and `returnToTeacher()`, accept a ug5 actor when `ExamAuthorization::isClassTeacherForExam()` is true for that exam's section/stream, and limit the affected rows to that section. Keep the admin path as is.
2. **Class teacher releases own class.** Already works when the school switch is on. No code change; make the switch default explicit in the UI copy and keep the school setting.
3. **Head teacher.** Decide first (section 5). If "admin for marks, no settings": add ug4 to the approve/return groups and to `links()` as a school-wide reader, but leave `routes/setting.php` behind `fullschooladmin` so Settings stay blocked. If "approve only": add ug4 to approve/return, not release.
4. **Set up exams by class teacher and head teacher.** Today both can create sets (teacher routes scoped to class-teacher sections; ug4 through the admin route). If the answer to 2.5 is admin-only, gate the teacher routes and add a ug4 check in `ExamSetController::store()`; if class teachers keep it, say so in the role copy and add a test that pins the scope.
5. **Subject teacher approve/release.** No change: the rule says they should not.

### 2.4 "Send back" and a release confirmation

- **Send back at approval: exists.** `returnToTeacher()` with a mandatory reason, status `reopened`, `approval_status = rejected`, note stored, teacher notified (`MarksSubmissionService.php:181-221`); route `admin.marks.submissions.reject` (`routes/admin.php:954`).
- **Confirmation at release: exists, in two parts.** The flow is create → preview → store (`MarksReleaseController.php:41-125`), and a past sitting needs an explicit confirmation tick (`MarksReleaseService.php:479-489`). There is no separate "type the school name" step.

### 2.5 Research question: who sets exams (not a decision)

Design assumes **admin only** for stage 1 until the early adopter schools answer — but note that the code already lets a **class teacher** create exam sets for their own class (teacher routes `routes/teacher.php:441-444`, scoped by `ExamSetController.php:370-381`). So the question is not only what schools want; it is also whether to remove what exists. Neutral questions for Rasta to put to them:

1. Who creates the exams today — one person, or each teacher for their own subject?
2. How often does that happen: once a term, or whenever a teacher adds a paper?
3. When one person sets every exam, what goes wrong — wrong papers, missing subjects, wrong dates, delays?
4. If teachers could set their own exams, who would check them before marks entry opens?

What changes in each case: if schools say one admin sets exams, keep stage 1 admin-only and add a head-teacher "approve setup" step later. If teachers set their own, `ExamSetController` needs a teacher-scoped create path and `ExamAuthorization` needs a "may create exam for own subject/class" rule; the workbook importer would then create exams per teacher, not per school. If it is mixed, the importer needs a per-row owner and a review list before creating.

---

## 3. Three tools

### 3.1 KLS filler

**Input:** the school, the workbook, and the class tab names.
**Steps:**
1. Read each class tab; for every row from 3 with a name and no KLS in A, collect `(tab, row, name)`.
2. Load the school's active learners in that class; normalise names (trim, collapse spaces, case-fold) but **match exactly** after that.
3. Write `users.registration_number` into column A only when exactly one learner matches and that learner has a KLS.
4. Leave the cell empty when there is no match, more than one match, or the learner has no KLS.

**Validations:** tab name must be a canonical class (`ChecklistWorkbookConverter::CANONICAL_CLASSES`); name must be non-empty; a KLS already in A must match the same learner or the row is a conflict; a learner may not be matched twice.
**Failure reports:** per row — `no_match`, `ambiguous` (with the candidate names and KLS numbers), `already_has_different_kls`, `learner_has_no_kls`.
**Never guesses:** no fuzzy match, no surname-only match, no cross-class match.
**Tests:** exact match writes A; two same-name learners in one class are listed, not written; a learner with no KLS is listed; re-running changes nothing; a name with different spacing still matches.
**Who may run:** school admin, or platform staff on the school's behalf. Not teachers.

### 3.2 Term workbook importer

**Input:** workbook, school, term; the school's exam types and subjects; the class tabs.
**Steps:**
1. Read Setup: school name, year, term, three exam sets (name, out of, on the report card, weight).
2. Check the school name matches the signed-in school; check the term exists in that year.
3. For each block, resolve the exam type by name within the school's visible types (`ExamType::visibleToSchool()`), then the exam set for that term and type.
4. For each class tab and subject column, resolve the class (tab name) and the subject (row 2 header) against the school's subjects. Report unknown names rather than creating subjects.
5. Create missing exams per class and subject through `ExamSetService`-shaped logic (one per type/class/subject), skipping existing ones.
6. Build mark rows: number, `ABS`, or empty. Reject out-of-range, text, and marks above Setup "out of".
7. Save drafts through `ExamMarkWriter` (never submit/approve/release).
8. Record an import run: workbook hash, term, exam sets, counts per class, per-row skips.

**Validations:** Setup "out of" must be a positive number; weights numeric if present; subject header must be non-empty; KLS present or exact name fallback (same rule as the sheet); class must exist; duplicate learner in a tab; mark types as above.
**Idempotency:** key rows by `(exam_id, student_id)`. Re-import with the same workbook writes nothing and reports "unchanged"; a changed mark updates the draft and is reported as `updated` with old and new values. No deletes: a row removed from the workbook leaves the saved draft alone and is reported.
**Who may import:** decision for Rasta (section 5). Design default: school admin; optionally the class teacher for their own class. Imported marks are attributed to the importer (`marks.updated_by`/audit), and submit/approve/release then follow section 2.
**Reuse:** `MarksImportService` for mark validation, plan/commit shape, reason codes and duplicate/overwrite handling; `ExamMarkWriter` for the write; `ExamSetService` for exam creation; `MarksSheetReader` for XLSX reading. The new part is the workbook parser and the type/set mapping.
**Tests:** one clean workbook creates exams and drafts; `ABS` sets absent; empty leaves no row; out-of-range rejected with row; re-import changes nothing; a changed mark updates and reports; unknown subject reported; unknown class reported; KLS-less row matched by exact name or flagged; import never submits.

### 3.3 Reports trigger

**Event:** `MarksReleased` with `school_id`, `academic_term_id`, `exam_type_id`, `exam_set_id`, `scope` (class/school), `class_ids`, `released_at`, and counts (learners, subjects, marks). **No learner names, KLS numbers or marks in the payload.**
**Where:** dispatched after a release is persisted and after commit, from `MarksReleaseService::create()`/`run()`; idempotent per release id.
**Consumer:** an n8n webhook (signed, HTTPS, retry with backoff, delivery log). The existing demo webhook (`MarksReleaseService.php:1069-1115`) is demo-only and includes learner codes, so it is not this event.
**Tests:** event fires once per release; payload has no personal data; a failed webhook does not fail the release; a re-release fires a new event with the new release id.

---

## 4. Gaps to close first

1. **Exam types on a fresh school (T-141).** Types are global and seeded only by the reference seeder (`ReferenceSeeder.php:39`); school creation does not seed or copy them, so an unseeded database leaves a school with no types and exam-set creation with nothing to choose (`ExamSetController.php:53`).
2. **Class teachers not set by Toshi.** Toshi invites a class teacher (`app/Livewire/AgentToshi.php:5673`) but the commit path writes teacher links, not `sections.class_teacher_id`/`standards_link.class_teacher_id`. A school onboarded through Toshi can therefore have no class teacher, and the class-teacher branches in `ExamAuthorization.php:33-63` and `MarksReleaseService.php:118-135` never match.
3. **Parents optional (T-138).** A learner row with no parent phone still creates the learner and skips the parent: `OnboardingEngine::linkImportedParent()` returns null without a phone (`app/Services/OnboardingEngine.php:2411-2414`). Onboarding's optional steps are teachers and students only (`OnboardingStepsService.php:126`), so a school can finish with learners who have no parent contact.
4. **Learners without KLS numbers.** The engine allocates a KLS when it creates a learner (`OnboardingEngine.php:2297`, `StudentIdGeneratorService::nextForStudent()`), but rows created through other paths are not guaranteed one, and the students list shows KLS as a column (`app/Services/People/PeopleListQuery.php:99`). The workbook importer cannot match those learners without the KLS filler or the name fallback.

---

## 5. Open decisions for Rasta

1. **Which exam sets count on the report card, and with what weights.** The workbook has "on the report card" and "weight"; the code has `exam_types.contributes_to_report_total` and `schools.report_composition`/`curriculum_profile`. Decide whether the workbook's per-set flags override the school setting for that term.
2. **Where the head teacher sits.** Suggestion: admin for marks (approve, return, release) without Settings. Today ug4 can create exam sets but cannot approve or release.
3. **Who may import a term workbook.** Admin only, or class teacher for their own class. This changes attribution and the review burden.
4. **Nursery classes.** Marks or learning-area remarks? The code already separates nursery ratings (`MarksSubmissionService.php:39-43`, `NurseryAssessment`), so the workbook needs a decision on whether nursery tabs carry marks at all.
5. **The research question in 2.5** — who sets exams today.

---

## 6. Build order and rough size

| Order | Piece | Why first | Rough size |
| --- | --- | --- | --- |
| 1 | Exam types on school creation (T-141) | Nothing downstream works without types | 1–2 days |
| 2 | KLS filler | The importer's match key; also fixes the "no KLS" gap | 2–3 days |
| 3 | Workbook parser + dry-run report (no writes) | Proves mapping of Setup, blocks, subjects before any write | 4–6 days |
| 4 | Term importer writing drafts (reuse `MarksImportService`/`ExamMarkWriter`) | The core; idempotency and ABS are the risky parts | 6–8 days |
| 5 | Roles: class-teacher approve/return, head-teacher decision | Needed before schools use it at scale | 2–4 days |
| 6 | `MarksReleased` event + webhook | Enables the n8n report path without coupling | 2–3 days |
| 7 | Class teachers set during onboarding | Removes a manual step and makes role gates real | 3–5 days |
| 8 | Parents required (T-138) | Improves delivery reach; not a blocker for marks | 2–3 days |

Total: roughly **22–34 dev-days**, with the importer (3–4) the only large piece. The interface does not need new screens for the first four: a CLI/command plus the existing import pages is enough until the workbook is stable.

## Sources

All line references are to `main` at `2aaf327b`: `app/Models/Academics/ExamType.php`, `app/Reference/ExamTypeCatalog.php`, `database/seeders/result/ExamTypes.php`, `database/seeders/ReferenceSeeder.php`, `app/Services/Academics/ExamSetService.php`, `app/Http/Controllers/Admin/ExamSetController.php`, `app/Services/Marks/ExamMarkWriter.php`, `app/Services/ExamAuthorization.php`, `app/Http/Controllers/Teacher/MarksController.php`, `app/Models/Academics/ExamMarksSubmission.php`, `app/Services/Marks/MarksSubmissionService.php`, `app/Services/MarksRelease/MarksReleaseService.php`, `app/Http/Controllers/MarksReleaseController.php`, `app/Services/Results/EloquentReleaseGate.php`, `app/Services/ReportCards/ClassReportScreen.php`, `app/Http/Controllers/Admin/ReportCardsController.php`, `app/Services/Parent/ParentPortalService.php`, `app/Services/MarksImport/MarksImportService.php`, `app/Services/OnboardingEngine.php`, `app/Services/OnboardingStepsService.php`, `app/Livewire/AgentToshi.php`, `routes/admin.php`, `routes/teacher.php`, `app/Http/Middleware/MustBeSchoolAdmin.php`, `app/Helpers/SiteHelper.php`.
