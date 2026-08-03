# Toshi Report Cards Audit (Part A — docs only)

**Date**: 2026-08-03  
**Branch**: `audit/toshi-report-cards` (from `origin/main` @ `862ba92`)  
**Scope**: Reconcile knowledge-base conflict on academic report cards. **No Toshi tools implemented. No tool scope proposed.**

---

## 1. Knowledge conflict — resolved

| Source | Claim | Verdict |
|---|---|---|
| KB module audit (~ReportsController / `/admin/reports`) | “No dedicated academic report cards or termly report generation — reports are primarily CSV exports” | **Partially true for the Reports module only** (CSV exports of students/fees/holidays/stock). **False as a product-wide claim.** |
| Session 2026-07-06 + July 15 report-card audit | `DownloadStudentReport` + `student-report.blade.php` PDF; nursery descriptive branch; MoE grading | **True** — confirmed in code and live PDF generation (below). |
| Open item referencing `.sisyphus/plans/report-card-design-spec.md` | “Report card content brief review — draft at …” | **Stale path**. File **never existed in git** (no commit adds/deletes it). Closest related plan: `.sisyphus/plans/part-c-parent-report-cards.md`. |

**Reconciled statement**: KlassApp **does** have per-student academic report-card PDF generation. The older “CSV only” note described the admin **Reports** export hub, not the marks/report-card path. There is still **no** class/term batch report-card generator or full distribution pipeline.

---

## 2. What exists (code map)

| Piece | Location | Role |
|---|---|---|
| Admin PDF download | `app/Http/Controllers/Admin/DownloadStudentReport.php` | Auth’d admin; loads learner/subjects/marks/position; DomPDF download |
| PDF template | `resources/views/admin/marks/student-report.blade.php` | Single template; `@if($isNursery)` descriptive domains vs numeric marks table |
| Helper | `app/Services/StudentReportHelperService.php` | Learner, subjects, fees, class totals, position, grade aggregate |
| Level detection | `app/Helpers/GradingHelper::levelTypeForStandard()` | `nursery` / `primary` / `o-level` / `a-level` |
| MoE scales | `config/grading_uganda.php` + `school_grading_systems` + `GradingSystemService` | Per-school grade boundaries / nursery descriptors |
| Nursery data model | `nursery_assessments` + `NurseryAssessment` | Domain ratings (Literacy, Numeracy, Motor Skills, Social/Emotional) |
| Route | `GET /admin/report/student/{learner}/class/{class}/{exam}` → `admin.report.student.class` | Per student + section + **single exam** |
| WhatsApp PDF | `Api\WhatsAppController@report` + `whatsapp.report-card` + `sendDocument()` | Parent pull via WhatsApp (Part C largely implemented vs older plan) |
| Alumni PDF | `AlumniController@downloadReportCard` + `alumni.pdf-report-card` | Separate alumni transcript-style PDF |
| Admin Reports hub | `ReportsController` + `/admin/reports` | **CSV/operational exports** — not academic report cards |
| Toshi `generateReport` | `ToshiActionService::generateReport()` | School **summary** text (counts/attendance) — **not** student report cards |

**Storage**: No `report_cards` table. PDFs are computed live from `marks` / `nursery_assessments` each request (same finding as 2026-07-15 audit).

---

## 3. Live verification (local DB `klassapp_local`)

Evidence generated 2026-08-03 via `php artisan tinker` calling `DownloadStudentReport@download` as school admin `admin@testschoolone.sch.ug` (user id 5, school_id 1). DomPDF engine **ran successfully**.

| Level | Student | IDs | Result | Evidence |
|---|---|---|---|---|
| **Primary** | Micheal Okwir | learner=58, section=90, exam=71 | `%PDF-1.7`, 8917 bytes, `MICHEAL_OKWIR__report_card.pdf` | `/tmp/klassapp-report-card-audit/primary-58-exam71.pdf` |
| **Nursery** | Brian Okello | learner=47, section=84, exam=1 | `%PDF-1.7`, 7764 bytes, `BRIAN_OKELLO__report_card.pdf` | `/tmp/klassapp-report-card-audit/nursery-47-exam1.pdf` |
| **O-Level** | Andrew Ssentongo | learner=66, section=94, exam=1 | `%PDF-1.7`, 9138 bytes | `/tmp/klassapp-report-card-audit/o-level-66-exam1.pdf` |
| **A-Level** | Jackie Namuyomba | learner=75, section=98, exam=56 | `%PDF-1.7`, 9147 bytes | `/tmp/klassapp-report-card-audit/a-level-75-exam56.pdf` |

HTML render check (same view, before DomPDF):

- Nursery → `isNursery=yes`, Domain/Literacy table present, no SUBJECT marks header; **`nursery_assessments` count = 0** locally → ratings render as `—`.
- Primary / O-Level / A-Level → numeric SUBJECT path; nursery Domain table absent.

**Blockers / caveats**:

1. Local `nursery_assessments` has **0 rows** — nursery PDF path works, but descriptive content is empty placeholders (knowledge notes earlier click-tests elsewhere; not present in this DB).
2. Blade bug on every render: `Attempt to read property "academicYear" on string` (`student-report.blade.php` title block treats term name as object).
3. O-Level case also hit `floor(null)` deprecation when average/score missing for some columns.
4. Exam↔enrolment data quality: some marks sit on exams whose `section_id`/`standard_id` do not match the student’s current `student_academics` class (legacy/seed noise). Detection still uses **student** standardLink for nursery vs numeric branch.

Supporting tests (not a substitute for live PDF): `tests/Feature/Toshi/ToshiReportCardPipelineFixTest.php` covers examType relation + term-scoped marks loading for the helper pipeline.

---

## 4. Design spec status

| Artifact | Status |
|---|---|
| `.sisyphus/plans/report-card-design-spec.md` | **Missing / never committed.** KB open-item path is orphaned. `git log --all --full-history` finds no such file. |
| `.sisyphus/plans/part-c-parent-report-cards.md` | Exists. Planned WhatsApp `sendDocument` + `/api/whatsapp/student/{id}/report`. **Largely implemented** in current main (`WhatsAppController@report`, `WhatsAppBusinessService::sendDocument`, `resources/views/whatsapp/report-card.blade.php`). Uses a **simpler** WhatsApp template, not the full admin `student-report` layout. |
| Nursery PDF work (2026-07-06) | Implemented in admin template; assessment entry UI / populated assessments still incomplete in local data. |

**Spec vs implemented (admin PDF):**

- Implemented: school header, student row, marks or nursery domains, teacher/HT comment slots, next-term/fees block, grading-system legend, stamp note, DomPDF A4 download.
- Not implemented / unfinished relative to aspirational product + Part C extras: polished design brief (lost/uncommitted), termly multi-exam cumulative “official” card as a first-class object, approval-aware admin PDF (WhatsApp path has approval checks; admin download does not gate the same way), class batch export, email/portal distribution UI.

---

## 5. Gap analysis: per-student PDF vs termly / batch / distribution

| Capability | Status |
|---|---|
| Per-student PDF (admin, one exam) | **Working** (live PDFs above) |
| Nursery descriptive branch | **Partial** — code path works; assessment data often empty; no dedicated nursery entry UI maturity |
| MoE / per-school grading on card | **Partial** — grades come from stored mark grades + `school_grading_systems` legend; template bugs in header/year |
| True termly cumulative report (BOT+MID+EOT / contributes_to_report_total) | **Partial** — helper can pull multiple exam types into columns for one download context; no dedicated “generate term report for class” UX; WhatsApp path filters contributing types |
| Batch class / term PDF zip or print pack | **Missing** |
| Email distribution of report cards | **Missing** |
| WhatsApp distribution | **Partial** — parent **pull** API exists; no admin “send all class report cards” blast |
| Parent web / portal download | **Missing** (mobile marks routes still historically commented; alumni has its own PDF) |
| Stored/published report-card records | **Missing** — always computed live |
| Toshi student report-card tools | **Out of scope for this PR** — not proposed here |

---

## 6. O-Level / A-Level status

**Same code path as Primary**, not a separate generator:

```
DownloadStudentReport
  → GradingHelper::levelTypeForStandard(...)
  → if nursery: NurseryAssessment domains
  → else: numeric marks table (Primary, O-Level, A-Level share this branch)
```

Live PDF generation succeeded for O-Level and A-Level students in local DB. Coverage is thinner than Primary/Nursery in seed quality (few subjects per student; cross-section exam noise). Post-nursery work did **not** fork O/A templates; risk is **under-tested edge cases** (points for A-Level on the card, division/aggregate semantics, multi-paper subjects), not a missing code path.

---

## 7. Explicit non-goals (Part A audit PR)

- No Toshi tools added or wired (Part A).
- No tool scope / skill proposal for report cards (Part A).
- No other-role expansion (teacher/parent/accountant report-card UX) in Part A.
- No PDF template bugfixes in the docs-only pass (bugs noted for follow-up).

---

## 8. Recommended follow-ups (product)

1. ~~Fix `student-report.blade.php` `academicYear` / null `nextTerm` / `floor(null)`~~ — **done in v1** (`feature/toshi-report-cards-v1`).
2. Seed or enter real `nursery_assessments` and re-verify nursery PDF content (not just empty Domain rows). **Still open** — local DB `COUNT(*)=0` as of 2026-08-03; nursery path works with `—` placeholders (unverified gap for real descriptive ratings).
3. Decide whether “termly report” means multi-column single exam-type download (today) vs a new batch/term artifact.
4. Batch / term-pack / email / `report_cards` table — **deferred** (explicit v1 non-goals).

---

## 9. v1 implementation decisions (`feature/toshi-report-cards-v1`)

| Decision | Choice |
|---|---|
| Scope | Per-student generate/download only |
| Canonical PDF | `admin.marks.student-report` via `StudentReportCardService` |
| Shared callers | `DownloadStudentReport`, `WhatsAppController@report`, Toshi SA + Teacher tools |
| WhatsApp | Parent-pull keeps approval gate; PDF generation switched to shared service (same admin template, not `whatsapp.report-card`) |
| SA tool | `GenerateStudentReportCardTool` on `SchoolAcademicsOpsSkill` (Batch 2 pattern) |
| Teacher tool | `Teacher\GenerateStudentReportCardTool` on `TeacherTeachingOpsSkill` — assigned class only (class teacher **or** Teacherlink on student’s standardLink) |
| Deputy | Inherits SA tool via `authorizeOrMessage` + registered on `DeputyAdminOperationsAgent` (not Settings) |
| Deferred | Batch/term-pack, email blast, `report_cards` table |

**Live PDF re-verify (2026-08-03, post-fix):** Primary/Nursery/O/A → `%PDF-1.7`, blade title shows `2026`, PDF bytes contain `2026`. Evidence: `/tmp/klassapp-report-cards-v1/`.
