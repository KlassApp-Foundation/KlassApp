# Toshi School Admin Batch 2 — Part A audit (docs only)

> Branch: `audit/toshi-schooladmin-batch2` off `origin/main` @ `e80fc87` (includes merged School Admin Batch 1 / #150)  
> Date: 2026-08-03  
> Scope: **docs-only** — inventory remaining School Admin domains after Batch 1; propose Batch 2 with graduated-risk discipline. **No tools built.**  
> Ground truth: Batch 1 (#150) = notices / events / holidays create·list·update via `SchoolCommsSkill` + `RouteToSchoolCommsSkillTool`. Ranking: `docs/toshi-panel-parity-ranking.md` (PR #141).  
> Method: active `routes/admin.php` + `routes/setting.php` route counts (comments stripped); `AuthServiceProvider` Gates; Admin controller load patterns; existing Teacher / Librarian / Receptionist / Student Toshi tools + ActionServices.

---

## Framing (non-negotiable)

| Rule | Implication |
|---|---|
| **Batch 1 closed** | Notices / events / holidays are **done** for create·list·update. Do not re-scope them here. Destroy still panel-only. |
| **Pattern** | New domains → Skill + `RouteTo*SkillTool` (custom RouteTo*, same as Academic / Fee / SchoolComms). Reuse ActionService + shared tables where other roles already write. **Do not reinvent** parallel CRUD stacks. |
| **Deputy (ug4)** | Inherits School Admin surface **minus** Settings-adjacent / owner governance (`AddCoAdminTool`, `SetCurriculumTool`). Batch 2 domains must be dual-Gate (`toshi-school-action` **or** `toshi-deputy-action`) unless explicitly Settings-adjacent (none proposed). |
| **Destructive defaults** | Prefer list / create / update (+ approve/reject where that is the admin job). **No** destroy, promotion→alumni, admissions enroll, settings/academic-year, emergency broadcast, fee-category destroy in Batch 2. |
| **Sized like Batch 1** | ~**3 related domains**, not the full remaining gap. |

---

## Baseline after Batch 1

| Surface | Evidence |
|---|---|
| School Admin panel | `admin.php` ≈ **543** active routes / **184** mutators across **~108** first-segments; `setting.php` ≈ **29** routes / **9** mutators (ug3-only via `MustBeFullSchoolAdmin`) |
| Toshi School Admin (web) | Orchestrator: **7** `RouteTo*SkillTool`s (Student, Teacher, Academic, Fee, Grading, Reporting, **SchoolComms**). Leaf tools live on skills; Deputy flattens **31** tools including 9 SchoolComms leaves (no AddCoAdmin / SetCurriculum). |
| School Admin tools for remaining gap domains | **Zero** named leaf tools under `app/AiAgents/Tools/*.php` for timetable / homework / classwall / library / transport / visitor / call / postal / promotion / admission / messaging. Overlaps exist only on **other role** agents (below). |

---

## Remaining-domain inventory (School Admin panel)

Counts = active routes in `routes/admin.php` (block + line comments stripped). Prefix groups counted separately where first-segment inventory undercounts (`transport`, `library`).

| Domain | Routes | Mutators | Notes |
|---|---:|---:|---|
| **classwall** | 43 | 20 | Largest remaining surface (pages + posts + comments + likes) |
| **homework** (+ `studenthomework`) | 17 + 3 | 6 + 1 | Approval `Approval\HomeWorkController` path (legacy non-approval block commented out) |
| **timetable** | 10 | 3 | Mix of legacy standardLink views + `TimetableSlotController` slot CRUD |
| **visitorlog** | 11 | 2 | Front-desk register |
| **calllog** | 9 | 2 | Front-desk register |
| **postalrecord** | 9 | 2 | Front-desk register |
| **library** (prefix) | 11 | 5 | books CRUD + lends + cards index |
| **transport** (prefix) | 6 | 3 | `Transportation` route CRUD incl. destroy |
| **promotion** (path contains) | 11 | 5 | Rules + export/import + marks promote entry |
| **admission(s)** | 6 | 1 | Update can create student + fee |
| **messaging** (messages / sentmessages / emergency / sendMessage*) | ~6 | ~4 | Broadcast / emergency — high blast radius |
| **report(s)** | 16 | 1 | Mostly CSV/download GETs; `GenerateReportTool` already partial |
| **student*** (broad) | ~57 | ~19 | Full CRUD beyond add/list already partial via StudentSkill |
| **teacher*** (broad) | ~45 | ~18 | Same — AddTeacher / List / Assign exist; edit/block/import/destroy gap |
| **settings** (`setting.php`) | 29 | 9 | Owner-level; Deputy blocked |

**Assignment:** **0** admin first-segment routes. Assignment CRUD lives on **Teacher** panel (`assignment` Gate exists). Admin “assignment oversight” in the ranking sketch maps to **homework approval + student-homework review**, not a third admin route tree.

---

## Current Toshi coverage (verify zero for School Admin; note overlaps)

| Domain | School Admin / Deputy | Teacher | Student | Librarian | Receptionist |
|---|---|---|---|---|---|
| Timetable | **none** | `ViewTimetableTool` (read) | — | — | — |
| Homework | **none** | `CreateHomeworkTool` | `ViewHomeworkTool`, `SubmitHomeworkTool` | — | — |
| Assignment | **none** (no admin routes) | `CreateAssignmentTool` | `ViewAssignmentsTool`, `SubmitAssignmentTool` | — | — |
| Classwall | **none** | `CreateClassWallPostTool` | `ViewClassWallTool` (read; mutations deferred) | — | — (panel residual ~21 routes; no Toshi classwall tools) |
| Library | **none** | — | `ViewLibraryActivityTool` | `ManageBooksTool`, `ManageLendingTool`, `ManageBookCategoriesTool`, `ViewLibraryCardsTool` (view-only cards) | — |
| Transport | **none** | — | — | — | — |
| Visitor / call / postal | **none** | — (panel has desk logs; low Toshi priority) | — | — | `ManageVisitorLogTool`, `ManageCallLogTool`, `ManagePostalRecordTool` |
| Promotion / admissions / settings / messaging CSV | **none** | — | — | — | — |
| Notices / events / holidays | Batch 1 **done** | view notices/events | view notices/events | — | view notices/events |

**Verdict:** Remaining gap domains have **zero School Admin Toshi tools**. Overlaps are role-scoped agents writing the **same tables** — Batch 2 should **share ActionService patterns / tables**, not fork models.

---

## Graduated risk — safe now vs hold

### Safe enough for Batch 2 (with Tier-2 + school_id isolation tests)

| Domain | Why safe-enough | Still exclude in Batch 2 |
|---|---|---|
| **Timetable slots** | Contained CRUD; controller already `abort_if($slot->school_id !== $schoolId)`; Teacher already reads slots | **destroy** |
| **Homework admin oversight** | Day-to-day headteacher job (list / create / update / approve / reject); `homework` Gate is school_id; Teacher already creates same `Homework` rows | **destroy**; fix approve/reject IDOR before trusting raw controller copy |
| **StudentHomework review** | Natural third sibling to homework (submission list/show/update); Student already submits | Cross-school id loads today — tool layer must Gate + school scope |

### Hold for later (one-line reasons)

| Domain | Hold reason |
|---|---|
| **Classwall** | Largest surface (43/20); ranking Batch 4; needs own pass even though `post` Gate was hardened |
| **Library** | Librarian agent already covers books/lending; admin is parallel UI — finish Librarian card-issue first or schedule dedicated batch |
| **Transport** | Low criticality vs academics; includes destroy; no other-role Toshi reuse yet |
| **Visitor / call / postal** | Receptionist tools already cover desk ops; admin controllers have **id-only** update/destroy/show paths (IDOR) — fix before exposing |
| **Promotion** | Irreversible class moves / alumni path (`PromotionController` export uses `alumni` next standard; marks promote) |
| **Admissions** | `AdmissionController@update` → `CreateStudent` + fee group side effects |
| **Messaging / emergency** | Broadcast blast radius (`sendMessageToAll`, `emergency/send`) |
| **Settings / academic year** | Owner-level (`setting.php`); Deputy must not inherit |
| **Student/teacher destroy·block·import** | Destructive / bulk; ranking high-risk |
| **Full CSV report suite** | Prefer curated report tools over dumping every export |
| **Any destroy** | Explicit Batch 1+2 boundary |

---

## Proposed Batch 2 scope (~3 related domains)

### Theme: **Academic schedule & homework oversight**

Aligns with ranking sketch (“Batch 2: Timetable + homework/assignment *admin* oversight”) and Batch 1 sizing (three siblings, non-destructive).

| Sub-batch | Panel domains | Proposed tool direction | Confirm rigor |
|---|---|---|---|
| **2a — Timetable** | `TimetableSlotController` list / create / update (+ optional teacher-weekly read) | `ListTimetableSlotsTool`, `CreateTimetableSlotTool`, `UpdateTimetableSlotTool` | Create/update → Tier-2 `ConfirmsBeforeWrite`; destroy stays panel |
| **2b — Homework** | Approval homework list / show / create / update / approve / reject | `ListHomeworkTool`, `CreateHomeworkTool` *(admin Gate)*, `UpdateHomeworkTool`, `ApproveHomeworkTool`, `RejectHomeworkTool` — or one `ManageHomeworkApprovalTool` with action enum if that matches Librarian/Receptionist manage style | Writes → Tier-2; **must** enforce `Gate::allows('homework', $row)` + school_id (do not copy approve/reject id-only loads) |
| **2c — Student homework review** | `studenthomework` list / show / update | `ListStudentHomeworkTool`, `UpdateStudentHomeworkTool` (mark/return style — mirror Teacher submission-review intent without opening Assignment admin tree) | Update → Tier-2; school scope via homework→school_id |

**Implementation pattern (Part B — not this PR):**

- New skill e.g. `SchoolAcademicsOpsSkill` **or** extend `AcademicSkill` only if leaf count stays coherent — prefer a **dedicated skill + `RouteToSchoolAcademicsOpsSkillTool`** so AcademicSkill (terms/subjects/exams) stays onboarding-shaped.
- `UsesToshiLlm` on any new prompt()-ing skill (Batch 1 lesson).
- Wire into `ToshiOrchestrator` + flatten onto `DeputyAdminOperationsAgent`.
- WhatsApp: lists may join School Admin read agent; writes stay fail-closed unless explicitly allowlisted later.

**Explicitly out of Batch 2:** classwall, library, transport, desk logs, promotion, admissions, messaging, settings, destroy, full student/teacher CRUD, CSV dump.

**Deputy:** receives 2a–2c automatically (not Settings-adjacent).

**Success criteria (for Part B):** ug3/ug4 can list/create/update timetable slots and manage homework approval + student-homework review via Toshi with audit identity; cross-school update denied; destroy/settings/promotion absent from tool registry; isolation tests green.

---

## Cross-references — shared tables vs parallel

| Domain | Shared tables / models | Existing writers | Batch 2 approach |
|---|---|---|---|
| Timetable | `TimetableSlot` (+ Section / AcademicTerm / Subject / User teachers) | Teacher `ViewTimetableTool` → `TeacherActionService::viewTimetable` (read) | **Shared table**; new admin write service methods; reuse school_id abort pattern from `TimetableSlotController` |
| Homework | `Homework`, `HomeworkApproval` | Teacher `CreateHomeworkTool` → `TeacherActionService::createHomework` | **Shared table**; admin tools add approval workflow Teacher does not have; prefer extract shared create into service both roles call |
| StudentHomework | `StudentHomework` | Student `SubmitHomeworkTool` / `ViewHomeworkTool` | **Shared table**; admin/teacher review is new School Admin surface (Teacher panel has submission routes; no Teacher Toshi review tool yet) |
| Assignment *(held)* | `Assignment`, `StudentAssignment` | Teacher create; Student submit/view | No admin routes — **do not invent** admin Assignment CRUD in Batch 2 |
| Classwall *(held)* | `Post`, comments, pages | Teacher `CreateClassWallPostTool`; Student view | Shared `Post` + hardened `post` / `post-destroy` Gates |
| Library *(held)* | `Book`, `BookCategory`, `BookLending` | Librarian ActionService | Admin `LibraryController` is **parallel UI** on same tables — prefer Librarian expansion over duplicate School Admin library skill |
| Visitor/call/postal *(held)* | `VisitorLog`, `CallLog`, `PostalRecord` | Receptionist ActionService (school-scoped creates) | Admin controllers are **parallel**; reuse Receptionist service patterns if ever exposed to ug3 |
| Notices *(Batch 1)* | `NoticeBoard` | SchoolComms writes; Receptionist/Teacher/Student views | Precedent for shared-table + role-scoped tools |

---

## Auth / IDOR flags (pre–Part B)

Quick check — cheaper than mid-build surprise. Pattern reference: event/post Gates before #131 / post-gate fixes.

| Domain | Gate / policy today | Controller risk | Batch 2 implication |
|---|---|---|---|
| **Timetable slots** | **No** dedicated Gate in `AuthServiceProvider` | `TimetableSlotController` update/destroy: `abort_if($slot->school_id !== $schoolId)` — **good** | Add explicit school_id checks (and tests) in ActionService; optional Gate for consistency |
| **Homework** | `homework` Gate = school_id match; destroy path uses Gate | `HomeworkApprovalController@approve` / `@reject`: `Homework::where('id',$id)->first()` — **no school_id / Gate** before mutating approval | **Must fix in tool/service layer** (and ideally panel) before shipping approve/reject tools |
| **StudentHomework** | `studentHomework` Gate = **student owner** + school via homework | Admin `StudentHomeworkController`: load by id only | Admin review tools need **school-scoped** authorization (not student-owner Gate alone) |
| **Classwall** | `post` school_id; `post-destroy` author / ug3 school / ug1 | Admin posts use Gate on show/destroy | Safer than pre-fix, but **hold** for surface size |
| **Visitor / call / postal** | **No** Gates | `show` / `update` / `destroy` often `where('id',$id)` without school_id; `edit` sometimes scoped | **High IDOR suspicion** — hold + fix panel before Toshi |
| **Library / transport** | `book` Gate school_id; transport uses `whereSchool` | Controllers generally school-scoped | Lower IDOR urgency; still held for product priority |
| **Admission** | — | Update creates student + fee | Hold — side effects |
| **Promotion** | `StudentPromotionRulesPolicy` exists | Export/import + alumni next step | Hold — irreversible |

---

## Deputy / Settings-adjacent notes

| Proposed Batch 2 domain | Settings-adjacent? | Deputy |
|---|---|---|
| Timetable slots | **No** — operational schedule, not `setting.php` / curriculum owner tools | **Inherit** |
| Homework approval | **No** | **Inherit** |
| StudentHomework review | **No** | **Inherit** |

Still excluded for Deputy (unchanged): `AddCoAdminTool`, `SetCurriculumTool`, and any future tools that mutate `setting.php` academic-year / general settings / SEO / maintenance / exam-types.

---

## Suggested later School Admin batches (unchanged sketch + this audit)

| Batch | Theme | Notes |
|---:|---|---|
| 1 | Comms & calendar | **Shipped** #150 |
| **2** | **Timetable + homework + student-homework** | **This Part A proposal** |
| 3 | Discipline + leave types | Moderate risk |
| 4 | Classwall (admin) | Large; after Gate confidence |
| 5 | Desk logs (visitor/call/postal) **or** library admin thin wrap | Only after IDOR hardening / Librarian card CRUD |
| 6 | Reports expansion | Curated tools |
| ∞ | Promotion / admissions / settings / destroy / messaging broadcast | Product + HITL design |

---

## Open questions for Part B approval

1. Confirm Batch 2 = **timetable + homework approval + student-homework review** (vs desk-logs-first reuse of Receptionist tools).  
2. Confirm **assignment** stays Teacher-only (no admin Assignment tree).  
3. Confirm destroy remains panel-only through Batch 2.  
4. New skill name: `SchoolAcademicsOpsSkill` vs folding into `AcademicSkill`?  
5. Should homework approve/reject panel IDOR be fixed in the same Part B PR as a prerequisite, or a tiny fix PR first?

---

## Ready for Part B?

**Awaiting approval of Batch 2 scope.** No implementation on this branch.
