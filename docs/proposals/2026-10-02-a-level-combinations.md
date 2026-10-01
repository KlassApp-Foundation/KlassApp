# Proposal: A-level subject combinations (UACE)

**Date:** 2026-10-02
**Author:** Buffy (Codebuff agent), at Rasta's request
**Status:** Draft for approval — no code has been written. Nothing here is final until Rasta signs off and the curriculum data (§7) is supplied.
**Related:** knowledge.md "Deferred until after soft launch" item "A-level subject combinations (S.5–S.6 combos) — not yet designed"; `design/system/` 2026-10-01 brand rule (demo schools named "Demo Senior School", global framing).

---

## 1. Problem

Uganda's Advanced Certificate of Education (UACE, Senior 5–6) is organised around
**subject combinations**, not class-wide subject lists. A student in "Senior Five
East" takes **their** three principal subjects plus the compulsory subsidiaries —
not every subject the school offers, and not the same set as their classmates.
KlassApp today cannot represent that: a subject row on a section is offered to
every student in the section, marks entry lists all subjects on the class, and
report cards treat the class as one flat subject list.

This document proposes a data model that fixes that **without breaking the
existing subject/marks machinery**, and frames the whole curriculum as one
portable pack so another country's curriculum can be added later as data, not
code.

---

## 2. How subjects and secondary classes work today

All references are to `origin/main` @ `2482fc1d`.

### 2.1 The class hierarchy

| Concept | Table / model | What it is |
|---|---|---|
| Level band | `standards` / `App\Models\Standard` ([app/Models/Standard.php](../../app/Models/Standard.php)) | A grading-tier band per school: `nursery`, `primary`, `o-level`, `a-level` (`StandardController.php:117`). Not a class. |
| Class | `sections` / `App\Models\Section` ([app/Models/Section.php](../../app/Models/Section.php)) | The actual class: "Senior Five", "Senior Six East", etc. |
| Class-in-year | `standards_link` / `App\Models\StandardLink` ([app/Models/StandardLink.php](../../app/Models/StandardLink.php)) | One stream of one class in one academic year (the standard↔section join, `standard_id` + `section_id` + `academic_year_id`; AGENTS.md standing rule #5: "class-scoped" always means `section_id`). |
| Student-in-class | `student_academics` / `App\Models\StudentAcademic` ([app/Models/StudentAcademic.php](../../app/Models/StudentAcademic.php)) | Links a user to a `standardLink_id` for a year; also carries `lin`, `board_registration_number` etc. |
| Teacher-in-class-subject | `teacherlinks` / `App\Models\Teacherlink` ([app/Models/Teacherlink.php](../../app/Models/Teacherlink.php)) | `standardLink_id` + `subject_id` + `teacher_id`; drives timetable and marks-entry authorisation (`ExamAuthorization.php`, `Toshi/TeacherActionService.php`). |

### 2.2 Subjects are per-section rows

`subjects` schema — [database/migrations/2020_02_18_053001_create_subjects_table.php](../../database/migrations/2020_02_18_053001_create_subjects_table.php):
`school_id`, `academic_year_id`, `standard_id`, `section_id` (**NOT NULL**),
`name`, `code`, `type` enum(`core`,`elective`), unique on
(`school_id`, `section_id`, `name`).

- `App\Models\Subject` ([app/Models/Subject.php](../../app/Models/Subject.php)) — `$fillable` includes
  `standard_id`, `section_id`, `type`; accessor `name` uppercased; static
  `sortByReportOrder()` hard-codes the PLE report order (ENGLISH, MTC, …).
- The A-level scaffolding already exists: `SchoolCategorySeeder`
  ([app/Services/SchoolCategorySeeder.php](../../app/Services/SchoolCategorySeeder.php)) seeds `a-level` with
  sections Senior Five / Senior Six and a flat subject list (General Paper 800,
  Biology 530, Chemistry 540, Physics 550, …), documenting that UACE codes 220
  (Geography/Economics) and 840 (ICT/General Mathematics/Entrepreneurship)
  collide and are left null. `AcademicSetupService`
  ([app/Services/AcademicSetupService.php](../../app/Services/AcademicSetupService.php)) seeds a similar
  list plus UACE-style grading rows (A=6 … E=2, O=1 "Subsidiary Pass", F=0).
  `GradingHelper::levelTypeForStandard()` ([app/Helpers/GradingHelper.php](../../app/Helpers/GradingHelper.php))
  maps Senior Five/Six names → `a-level`.
- **No concept of a combination, a principal/subsidiary role, or a per-student
  subject selection exists anywhere in the schema.**

### 2.3 How subjects flow into marks and report cards

- Marks: `App\Models\Academics\Marks` ([app/Models/Academics/Marks.php](../../app/Models/Academics/Marks.php)) —
  `student_id`, `subject_id`, `section_id`, `exam_id`, `teacher_id`, `school_id`,
  `marks`, `grade`.
- Exams: `App\Models\Academics\Exam` — per `section_id` (+ optional
  `subject_id`); teachers enter marks per class/subject via `Teacherlink`
  authorisation.
- Report cards: `StudentReportCardService::generatePdf()`
  ([app/Services/StudentReportCardService.php:188](../../app/Services/StudentReportCardService.php)) is the single
  implementation (AGENTS.md bug pattern #1; declared at [app/Services/StudentReportCardService.php:177](../../app/Services/StudentReportCardService.php)). The subject list comes from
  `StudentReportHelperService::subjects()`
  ([app/Services/StudentReportHelperService.php:45](../../app/Services/StudentReportHelperService.php)) — **every subject on
  the section**,  ordered by `Subject::sortByReportOrder()` (a PLE order;
  [StudentReportCardService.php:191](../../app/Services/StudentReportCardService.php)).
  Aggregate points are summed across all subjects
  (`$aggregatePoints`, [line 241](../../app/Services/StudentReportCardService.php)), the division scale is the O-level-style
  12/24/28/32 bands ([lines 224–235](../../app/Services/StudentReportCardService.php)), and `examinedSubjectCount` counts every
  subject the student has marks in ([line 341](../../app/Services/StudentReportCardService.php)). None of this distinguishes
  principal from subsidiary subjects.

### 2.4 Consequences today

- A marks template for "Senior Five East" lists all ~11 a-level subjects for
  every student.
- Report cards show every subject with a flat aggregate and an O-level division
  scale — wrong for UACE, where the aggregate is over **three principal
  subjects** and divisions don't apply.
- There is no way to say "Aisha takes Hist/Geo/Eco + Subsidiary ICT; John takes
  Phy/Chem/Bio + Subsidiary Maths" inside one section.

---

## 3. Design goals

1. One UACE curriculum = one **pack**: subjects, combinations, roles, grading —
   data, not code. Another country's curriculum (Kenya KCSE, Tanzania ACSEE,
   …) is another pack, with zero schema changes.
2. Minimal disturbance to the existing marks/teacherlink/exam machinery —
   the per-section `subjects` rows stay; the pack tells each section *which*
   subjects it gets and what each is *for*.
3. Per-student combination assignment that reads correctly on class lists,
   marks entry, and report cards.
4. Multi-tenant from the start: every table scoped by `school_id`
   (AGENTS.md standing rule #13).
5. Nothing about UACE semantics is hard-coded. The engine only knows
   "roles", "groups" and "aggregation rule" as opaque pack data.

---

## 4. Curriculum-pack data model

### 4.1 New tables

All scoped by `school_id` and `academic_year_id` where the school's own usage
is year-bound. Tables are curriculum-agnostic; "UACE" is seed data.

```
curriculum_packs
  id, school_id, code ('UACE-2026'), name ('Uganda A-Level (UACE)'),
  country ('UG'), level ('a-level'),          -- maps to standards.name
  status, timestamps, soft-deletes
  unique(school_id, code)

curriculum_subjects
  id, school_id, curriculum_pack_id, code ('530'), name ('Biology'),
  role ('principal' | 'subsidiary' | 'compulsory' | NULL),
  is_active, timestamps
  unique(curriculum_pack_id, code)
  -- role is the pack's DEFAULT role for the subject; a combination may
  -- override it for its members (e.g. General Paper is subsidiary-compulsory
  -- for everyone; a subject may be principal in one combo, subsidiary in
  -- another) — see curriculum_combination_subjects.

curriculum_combinations
  id, school_id, curriculum_pack_id, code ('PCM', 'HEG', …),
  name ('Physics, Chemistry, Biology'), rules (json: min/max principals,
  required subsidiary groups), is_active, timestamps
  unique(curriculum_pack_id, code)

curriculum_combination_subjects
  id, curriculum_combination_id, curriculum_subject_id,
  role_override ('principal' | 'subsidiary' | 'compulsory'), sort_order
  -- THE valid-combination table: which subjects, in which role, make up
  -- which combination. Only rows in this table may be offered to a student
  -- assigned that combination.

school_grading_systems (existing table, no change)
  -- grading stays where it is: per standard_id rows with grade/points/
  -- min_score/max_score/remark. The pack carries a `grading_pack_key`
  -- reference (a new nullable column on curriculum_packs) so a pack can
  -- ship its canonical scale for seeding; the school's own editable copy
  -- remains the source of truth for report cards.
```

`type` on the existing `subjects` table (`core`/`elective`, migration
`2020_02_18_053001`) is left alone; role information lives in the pack, not on
`subjects`, so the same subject row keeps working for schools that never use
combinations.

### 4.2 How a pack populates a section

The seeder/`OnboardingEngine` subject-creation path
(`OnboardingEngine::ensureSubjectForClass`, [app/Services/OnboardingEngine.php:640](../../app/Services/OnboardingEngine.php))
keeps working unchanged. A new pack-driven step enumerates the pack's subjects
and creates the per-section `subjects` rows it always did — but now each
section also learns **which combinations it hosts**:

```
section_combinations
  id, school_id, academic_year_id, standardLink_id (one stream of one class),
  curriculum_combination_id, timestamps
  unique(standardLink_id, curriculum_combination_id)
```

A section can host one combination (a pure "PCM class") or many (a "hybrid"
Senior Five where students of several combos share a classroom — the normal
Ugandan arrangement, handled by per-student assignment below).

### 4.3 Portability to another country

Nothing in the schema names Uganda. A Kenya pack carries `country='KE'`,
`level='secondary'`, its own subjects/combinations/roles and its own grading
pack key. Adding a country = seeding one pack. The engine code — assignment,
scoping, report grouping — is curriculum-blind.

---

## 5. Student combination assignment

### 5.1 Where the assignment lives

**Recommended:** a dedicated table, not a column on `student_academics`.

```
student_combinations
  id, school_id, academic_year_id, student_academic_id (FK, unique per year),
  curriculum_combination_id, section_combination_id (nullable — set when the
  student's stream explicitly hosts that combo), assigned_by, assigned_at,
  timestamps, soft-deletes
  unique(student_academic_id)  -- one combination per student per year
```

Why not a nullable `curriculum_combination_id` on `student_academics`:
`student_academics` is already wide, written by many traits (import, admission,
onboarding, `klassapp_student_id` guard), and a combination is year-scoped the
same way `standardLink_id` is but is conceptually a *curriculum* fact, not an
*enrolment* fact. A separate table keeps `combination_id` unique-per-year,
records who/when it was assigned, and can be dropped/re-created during
promotion without touching the enrolment row. (Trade-off noted: one extra join
on every read; acceptable — assignment is created once and read per request.)

Assignment rules (validated in a `CombinationAssignmentService`, not invented):
- The combination must belong to the same `curriculum_pack_id` as the school's
  a-level pack and be active.
- The combination must be hosted on the student's `standardLink_id`
  (`section_combinations`), unless the school runs hybrid sections.
- **Rule #4 discipline:** if the school's data doesn't satisfy the pack rules
  (e.g. an imported student with a combination code the pack doesn't define),
  the record is flagged for review (`status='needs_review'`), never silently
  mismatched.

### 5.2 Class lists

`ClassStructureService` / roster listings join `student_combinations` and show
a **Combination** column ("PCM", "HEG") next to the stream. Students without an
assignment in an a-level section show "— assign combination" instead of a blank,
mirroring the "Needs a class" pattern from #907. The design-system's ledger
pattern (`data-label` for mobile restack) applies unchanged.

### 5.3 Marks entry

Today `Teacher/MarksController` loads the class's subjects for the grid.
Change: filter the subject list per student by their combination's subjects
(`curriculum_combination_subjects`), while keeping the grid per subject-column.
Teachers marking "the class" mark each student only in their combination's
subjects. `Exam` rows stay per section(+subject); no change there. The
`contributes_to_report_total` discipline (AGENTS.md bug pattern #5) applies to
any new aggregation: only combination subjects count.

### 5.4 Report cards

In `StudentReportCardService::generatePdf()` (single implementation, bug
pattern #1):

1. Load the student's combination (if any) for the exam's year.
2. Subject list = intersection of the section's subjects and the combination's
   subjects, grouped **Principal** / **Subsidiary / Compulsory** using the
   effective role (combination override → pack default).
3. Aggregation: a UACE pack sets an `aggregate_rule` (e.g. "sum of principal
   points, best 3" — **placeholder**, see §7.4). The existing O-level division
   scale ([lines 224–235](../../app/Services/StudentReportCardService.php)) is only used when no combination context applies.
4. Ordering: pack-defined subject order replaces the PLE hard-coded
   `sortByReportOrder()` list when a combination context exists.
5. A student without a combination keeps today's behaviour — no regression for
   O-level and primary, and a-level schools that haven't adopted the pack.

### 5.5 Toshi

Natural-language flows map cleanly: "Assign John to PCM in Senior Five East" →
`CombinationAssignmentService::assign()`; "Which students take HEG?" → class
list filtered by combination. Capability name: `assign_combination` (admin /
co-admin), consistent with `create_subject` in `ToshiActionService`.

---

## 6. Demo Senior School

Demo Senior School (O + A level) is the reference deployment:

- S.1–S.4 continue exactly as today (O-level pack, class-wide subjects).
- S.5 East hosts PCM / PCB; S.5 West hosts HEG / HEL; S.6 likewise
  (`section_combinations`).
- Students are split across combinations at import or by Toshi command; each
  student's `student_combinations` row drives their marks grid and report card.
- The A-level pack ships with the school category `o_a_level` seeding, so a
  demo reset produces a populated, correct S.5 with combination-aware report
  cards out of the box.
- The 2026-10-01 brand rule applies: the demo shows a **global product**; the
  UACE pack is presented as one curriculum pack among others, with the school's
  own currency and class names — not as a Uganda-only headline.

---

## 7. Data Rasta must supply (placeholders — do not fill from memory)

> **These are marked placeholders. Nothing below may be invented**; the
> seeders' current subject lists (`SchoolCategorySeeder`, `AcademicSetupService`)
> are plausible defaults, not authority. Codes 220/840 collisions are already
> documented there and must be resolved from the official source.

### 7.1 Authoritative subject list
`[PLACEHOLDER: official UACE subject list with UACE codes — from the UNEB
UACE regulations/NCDC curricula Rasta will supply]`

### 7.2 Valid combinations
`[PLACEHOLDER: the official/allowed principal-subject combinations the school
offers (e.g. is HEG official or school-local?), with the subsidiary/compulsory
rules — General Paper and Subsidiary ICT/Maths status for each]`

### 7.3 Role rules
`[PLACEHOLDER: which subjects may be principal in one combination and
subsidiary in another; compulsory subjects applicable to every combination]`

### 7.4 Grading and aggregation
`[PLACEHOLDER: official UACE grading scale and the aggregate/division rule for
UACE report cards (best-3-principals? points thresholds?) — replaces the
O-level 12/24/28/32 division bands currently hard-coded in
StudentReportCardService]`

### 7.5 Demo school specifics
`[PLACEHOLDER: Demo Senior School's actual combination offerings and S.5/S.6
stream names, so the seed data matches what Rasta demos]`

---

## 8. Build sequence (smallest first)

| # | PR | Scope | Depends on |
|---|---|---|---|
| 1 | **Pack schema + seeder** | Migrations for the five tables; `CurriculumPack`/`CurriculumSubject`/`CurriculumCombination` models; artisan seeder; unit tests. No UI. | §7.1–7.3 data |
| 2 | **Section hosting + assignment** | `section_combinations`, `student_combinations`; assignment service + validation; admin UI (class list column + assign/unassign); Toshi `assign_combination`. | PR 1 |
| 3 | **Marks-entry scoping** | Per-student subject filtering on the marks grid; teacher-facing docs. | PR 2 |
| 4 | **Report cards** | Combination-aware subject grouping + aggregate rule in `StudentReportCardService`; placeholder-free only after §7.4 arrives; falls back to today's behaviour without a combination. | PR 2 + §7.4 |
| 5 | **Pack-driven section population + demo data** | `SchoolCategorySeeder`/`AcademicSetupService` learn packs; Demo Senior School seed data. | PR 1–4 |
| 6 | **Toshi flows round-out** | Query flows ("who takes HEG"), plan-card confirmations. | PR 2 |

PRs 2–4 can be reviewed/merged incrementally; nothing ships user-visible until
PR 2. Each PR is small and atomic (standing rule #10), multi-tenant by
construction (rule #13), and no raw production writes (rule #1 — seeding new
tables is additive).

---

## 9. Open questions for Rasta

1. §7 data — all five placeholders need the official source before PR 1 lands
   real seed data.
2. Pure vs hybrid sections: should S.5 classes be one-combination-per-class
   (simpler timetable) or multi-combination (matches current Ugandan
   practice)? The model supports both; the default affects Demo Senior School's
   seed data.
3. Should combination assignment be mandatory before marks entry is allowed on
   an a-level section, or advisory ("7 students have no combination yet")?
   Recommendation: advisory, flagging for review rather than blocking (rule #4).
4. Does the A-level pack apply to S.5–S.6 only, or do schools want an
   O-level-equivalent pack for S.1–S.4 later? (The schema doesn't care; this is
   a seeding question.)
