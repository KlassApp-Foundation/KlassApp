# Assessment model: Uganda curricula as KlassApp's first curriculum package

*Design doc. Nothing here is built. Every rule that comes from an official document is cited; every rule that does not is marked **needs confirmation**, because the owner is separately verifying the NCDC and UNEB rules and will add the findings to this pull request.*

**Context.** Every school we serve now uses Uganda's new curriculum, so the Uganda Ministry of Education curricula become KlassApp's first curriculum package. Old curricula stay configurable. The marks stack (#1236–#1244) is the exam-mark model this package has to sit alongside, not replace.

---

## 1. What the curricula are, at the level we model them

| Level | Curriculum | Who owns the rules | What that means for us |
|---|---|---|---|
| Nursery | Pre-primary, play- and domain-based | NCDC develops the pre-primary curriculum | No marks out of 100, no positions; descriptive reporting per domain |
| Primary | Primary curriculum, PLE at the end | NCDC curriculum, UNEB examines PLE | Termly class marks plus a cycle result; PLE grading is UNEB's |
| O level (S1–S4) | Lower secondary, competency-based (CBC) | NCDC curriculum and subject assessment guidelines; UNEB examines UCE | Competencies and learning outcomes, project work, and school-based assessment that counts towards UCE |
| A level (S5–S6) | Advanced secondary | NCDC curriculum; UNEB examines UACE | Principal and subsidiary subjects, points-based grading |

Sources checked for this doc:

- NCDC: competency-based lower secondary curriculum and per-subject assessment guidelines ([ncdc.go.ug](https://ncdc.go.ug/), *Resources* → subject assessment guidelines; *Key Milestones*: "Reviewed Lower Secondary (O' Level) Curriculum to make it competency-based").
- NCDC notice, 27 July 2026: "Assessment Guidelines to Support Effective Implementation of the Advanced Secondary Curriculum" ([ncdc.go.ug](https://ncdc.go.ug/)).
- NCDC: pre-primary curriculum development with the Play Matters Consortium ([ncdc.go.ug](https://ncdc.go.ug/)).
- UNEB: circulars on handling continuous assessment for S.3 and S.4 (2026), CA scores submission, the S.3 project theme for 2026, and coursework assessment 2025 ([uneb.ac.ug](https://uneb.ac.ug/)).
- UNEB: the UCE release statement figures the grading config already cites (`config/grading_uganda.php:62`, "UCE 2024 release statement"). The PDF URL recorded there was not re-fetched for this doc; **treat the exact table as needing confirmation** until the owner's research lands.

**Not yet sourced, needs confirmation:** the exact split between school-based assessment and the end-of-cycle exam (the owner states 20% and 80% for UCE); the precise definition of the Result 1/2/3 categories; whether CA is 20% of every subject or of the aggregate; the UACE point and grade rules; PLE division rules; and how nursery domains are named and rated in the current NCDC pre-primary curriculum. The owner's research will settle these and this doc will be corrected before any build.

---

## 2. What exists today

| Piece | Where | What it does | Gap against the curricula |
|---|---|---|---|
| Curriculum profiles | `config/curricula.php:15` (`default_profile`), `:19` (`new`), `:35` (`legacy`) | Picks a band table per level for a school, via `schools.curriculum_profile` | Two profiles only; no competencies, no CA, no project, no cycle exam |
| Band tables | `config/grading_uganda.php:40` (primary), `:62` (O level A–E), `:78` (legacy O level, now the school's own table), `:99` (A level), `:125` (nursery) | Grade letters and score bounds | A level cites a **third-party guide**, not UNEB (`:94`); no CA or project weighting anywhere |
| Letter on write | `app/Services/Marks/ExamMarkWriter.php:24`, `:43`, `:53`, `:121` | Writes a mark and stores the letter from `GradingSystemService::grade` (`app/Services/GradingSystemService.php:14`) | A letter is the only derived value; a mark has no component, competency or evidence attached |
| Sheets and totals | `app/Services/Results/ResultsService.php:38` (`sheet`), `:61`–`:92` (composition, weights, students) | One sheet per sitting or term; totals, averages, positions | Weighting exists only between exam types; there is no CA-versus-exam weighting |
| Which types count | `app/Services/Results/ExamTypeContribution.php:22`; `schools.report_composition` (`database/migrations/2026_10_26_130000_add_report_composition_and_curriculum_profile_to_schools_table.php`) | Show on report cards, weight, contributes-to-total | Exam types only |
| Division | `app/Services/Results/ThresholdDivisionRule.php:15` | Divisions 1–4 by aggregate points | Fixed thresholds, not a curriculum rule; no Result 1/2/3 |
| Report cards | `app/Services/StudentReportCardService.php:37`–`:38` (formal, warm), `:299` (`generatePdf`), `:347` (division) | Two templates, positions, division, remarks | No competency or outcome reporting; no CA breakdown; no project |
| Release | `app/Services/MarksRelease/MarksReleaseService.php:607` (`run`), `:622`–`:624` | Marks a sitting released for a class or school | Releases exam marks only; a term record is not released as one thing |

---

## 3. What the package has to add

1. **Continuous assessment across terms and years.** A learner's evidence is collected per subject, per term, across years, and is not thrown away when a term ends.
2. **Competencies and learning outcomes.** The O-level curriculum is competency-based; a mark is one kind of evidence, an outcome judgement is another.
3. **Project work.** UNEB publishes a project theme for S.3 and handles coursework; a project is assessed, and it is not a subject mark.
4. **School-based assessment combined with the cycle exam.** The school's CA record plus UNEB's end-of-cycle result produce the certificate grade.
5. **Result categories.** A–E subject grades and the Result 1/2/3 categories at UCE; UACE points and grades at A level.
6. **Primary and nursery reporting.** Divisions and aggregates at PLE; descriptive domain reporting in nursery, with no positions.
7. **Old curricula.** Schools still on the pre-NLSC scale keep their own bands (already true since #1243: `config/curricula.php:35`, `schools.legacy_o_level_bands`).

---

## 4. Proposed data model

Names are proposals; the point is the shape. Everything is school-scoped and keyed by academic year and term.

### 4.1 Curriculum package (the plug-in unit, per #1119)

```
curriculum_packages        id, key ('uganda'), name, country, version, status
curriculum_levels          id, package_id, level ('nursery','primary','o-level','a-level')
curriculum_subjects        id, package_id, level, code, name          -- package's subject catalogue
curriculum_outcomes        id, package_id, level, subject_id, code, statement, sort
```
A school selects `schools.curriculum_package_id` + `curriculum_profile` (existing column). Another country ships its own package row and its own subjects/outcomes; nothing in the marks tables changes.

### 4.2 Assessment components (one per kind of evidence)

```
assessment_components      id, package_id, level, key ('exam','ca','project','practical'),
                           label, weight_hint, counts_toward_cycle (bool)
school_component_settings  id, school_id, component_id, weight, enabled, source ('ncdc','uneb','school')
```
`weight_hint` carries the official split where one exists; `school_component_settings` is where a school's own split lives. The 20/80 rule, once confirmed, is seeded as a hint, not hard-coded.

### 4.3 Continuous assessment

```
ca_schemes      id, school_id, academic_year_id, level, subject_id, name, components (json)
ca_records      id, school_id, academic_year_id, academic_term_id, subject_id, student_id,
                outcome_id nullable, scheme_id, score nullable, level_rating nullable,
                evidence_note nullable, assessed_on, recorded_by, created_at
                -- one row per judgement, never overwritten: a correction is a new row
                -- that supersedes the previous one (superseded_by_id)
```
Two shapes in one table on purpose: a **score** where the curriculum uses marks, a **rating** where it uses outcomes or domains (nursery, competency statements). `outcome_id` is null for a plain subject score.

### 4.4 Projects

```
projects           id, school_id, academic_year_id, academic_term_id, level, title, theme,
                   brief, due_on, component_weight
project_marks      id, project_id, student_id, score nullable, rating nullable, remarks,
                   assessed_by, assessed_on, superseded_by_id
```
A project is not a subject. At UCE it is evidence the school reports to UNEB (needs confirmation on how it is weighted and submitted).

### 4.5 Cycle results (what UNEB awards)

```
cycle_results      id, school_id, student_id, cycle ('ple','uce','uace'), year,
                   subject_id nullable, grade, points nullable, result_category nullable,
                   source ('uneb','school'), reference, recorded_at
```
Kept separate from school marks: a UNEB grade is evidence we did not produce.

### 4.6 Reporting

```
report_definitions id, package_id, level, key ('formal','warm','term','cycle'),
                   sections (json: marks, ca, outcomes, project, division, positions, remarks)
report_records     id, school_id, student_id, academic_year_id, academic_term_id,
                   definition_id, version, payload (json), released_at, superseded_by_id
```
`report_records.payload` is the canonical snapshot described in `verifiable-records.md`.

---

## 5. How the pieces combine

```
subject term result = f(exam marks for the term, CA records for the term, project where the level uses one)
cycle result        = f(term results across the cycle, UNEB cycle result)      -- grades only where official
report card         = report_definition(level) applied to the term result set
```

Rules the model must keep:

- A mark is never required to produce an outcome judgement, and an outcome judgement never silently becomes a mark.
- A CA record is append-only; a correction is a new row with a reason, the same rule release snapshots use.
- Weights are data (`school_component_settings`), not code, so a corrected UNEB rule is a seeded change.
- Anything not official stays flagged in the seed, visible to the school, until the owner confirms it.

---

## 6. Fit with the package architecture (#1119)

#1119 defines the package lifecycle and eight extension points. This package uses four of them:

| Extension point | This package uses it for |
|---|---|
| Grading scale | `packages/grading-uneb`: band tables per level, replacing hard-coded `config/grading_uganda.php` |
| Curriculum profile | `packages/curriculum-uganda`: levels, subjects, outcomes, components |
| Report definition | `packages/curriculum-uganda/reports`: formal, warm, term, cycle |
| Assessment rules | `packages/curriculum-uganda/assessment`: the CA/exam split and what counts |

`ResultsService` stays the only place that computes totals, averages, positions and divisions; the package supplies inputs and scales. A second country ships a second package; the marks tables and `ResultsService` are untouched.

---

## 7. Build phases (after the marks stack merges)

| Phase | What lands | Depends on | Why this order |
|---|---|---|---|
| A1 | Curriculum package tables + Uganda package seeded (levels, subjects, outcomes), read-only screens | #1119 merged | Nothing else can reference a subject catalogue until it exists |
| A2 | CA records: entry per subject/outcome, append-only, corrections with reason; teacher and admin entry; term view | A1 | The evidence model, before any combining |
| A3 | Project records per level and term | A2 | A project is evidence of the same kind as CA |
| A4 | Component weights and the combined term result; report definitions read it | A2, A3 | The rule layer, once there is evidence to combine |
| A5 | Cycle results (UNEB) recorded per learner; Result 1/2/3 and UACE rendering once confirmed | A4 | Depends on the owner's source check |
| A6 | Report templates: competency/outcome sections, CA breakdown, project line | A4 | Presentation last |
| A7 | Old curricula: keep the legacy scale, map its report definition | A1 | Small, and already half-built |

Each phase is one pull request, behind a per-school toggle until a school asks for it.

---

## 8. Open questions for the owner

1. The official CA/exam split at UCE, in UNEB's own words, and whether it is per subject or on the aggregate.
2. What exactly Result 1, 2 and 3 mean, and whether they replace divisions entirely at UCE.
3. Whether CA is recorded per competency, per learning outcome, or per subject topic — NCDC's subject assessment guidelines may differ per subject.
4. How a project is scored and reported to UNEB, and whether a school may weight it.
5. UACE: the official grade and point table (today's `config/grading_uganda.php:94` cites a third-party guide).
6. PLE: whether we model divisions only, or aggregates as well.
7. Nursery: the official domain names and rating scale in the current pre-primary curriculum.
8. Whether a school may override an official weight, and if so, what the report card must say.
