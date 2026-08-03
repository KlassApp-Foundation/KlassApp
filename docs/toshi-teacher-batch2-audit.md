# Toshi Teacher Batch 2 — Part A audit (docs only)

> Branch: `audit/toshi-teacher-batch2` off `origin/main` @ `102f92e` (includes merged School Admin Batch 2 / #153 + homework Gate / #152)  
> Date: 2026-08-03 (updated same day — Gate proposals for leave + assignment)  
> Scope: **docs-only** — inventory remaining Teacher (ug5) domains after advisory ~12 tools; propose Batch 2 with graduated-risk + mandatory Gate discipline. **No tools built. No Gates implemented.**  
> Continuation: `docs/toshi-teacher-leave-assignment-gates-audit.md` — own-leave + assignment-review Gate proposals (awaiting approval).  
> Ground truth: Teacher advisory shipped earlier (`TeacherOperationsAgent` — 12 tools). Ranking: `docs/toshi-panel-parity-ranking.md` (PR #141) flagged submission review / leave / classwall as core-teaching remainder. School Admin Batches 1–2 each found real Gate gaps via Part A — same rigor here.  
> Method: active `routes/teacher.php` + `routes/teacherapi.php` (comments stripped); `AuthServiceProvider` Gates; Teacher / API Teacher controllers; existing Teacher / Student / School Admin Toshi tools + ActionServices.

---

## Framing (non-negotiable)

| Rule | Implication |
|---|---|
| **Advisory closed** | The 12 Teacher tools are **done**. Do not re-scope attendance / marks-enter / create lessonplan·assignment·homework·task / apply leave / create classwall post / view students·timetable·events·notices. |
| **Pattern** | New domains → Skill + `RouteTo*SkillTool` (same as `SchoolComms` / `SchoolAcademicsOps`), **or** leave tools flattened onto `TeacherOperationsAgent` if leaf count stays small — prefer dedicated skill so the flat agent does not grow unbounded. Reuse ActionService + shared tables where Student / School Admin already write. **Do not reinvent** parallel CRUD stacks. |
| **Destructive defaults** | Prefer list / review / mark / approve·reject / own-status. **No** destroy, principal assignment-approval workflow, desk-log CRUD, or grades/records with broad downstream effects in Batch 2. |
| **Sized like SA Batch 1–2** | ~**2–3 related domains**, not the full ~219-route gap. |
| **Gate-first** | Any IDOR-class / school-only-where-assignment-required gap → **fix-before-tools** (homework Gate Option A precedent #152). |

---

## Baseline after Teacher advisory

| Surface | Evidence |
|---|---|
| Teacher panel | `routes/teacher.php` ≈ **219** active route declarations (**157** GET / **60** POST / **2** PATCH). Top first-segments: classwall **35**, lessonplan **20**, assignment **16**, leave **16**, standardLink **14**, student **12**, task **11**, homework **11**, desk logs (visitor+call+postal) **27**. |
| Teacher API | `routes/teacherapi.php` ≈ **97** declarations (**71** GET / **26** POST). Heavy: assignment **13**, task **9**, homework **7**, leave/checkleave **10**. |
| Combined inventory | Ranking cited ~243; measured **219 + 97 = 316** declarations across web+API files (API often mirrors panel — not 316 unique product actions). Panel-alone **~219** matches the ranking’s ~243 order of magnitude. |
| Toshi Teacher | `TeacherOperationsAgent::tools()` — **exactly 12** advisory tools (listed below). **Zero** Teacher tools for submission *review*, leave *approve/status list*, or classwall beyond single create-post. |
| School Admin overlap | Batch 2 (#153) shipped `ListStudentHomeworkTool` / `ShowStudentHomeworkTool` / `UpdateStudentHomeworkTool` on `SchoolAcademicsOpsSkill` — they use `AuthorizesToshiAction` (ug3/ug4), **not** `toshi-teacher-action`. Service layer already enforces `studentHomework-review` (ug5-capable Gate). |

### Existing ~12 Teacher tools (complete list)

| # | Tool | Domain |
|---|---|---|
| 1 | `MarkAttendanceTool` | attendance |
| 2 | `EnterMarksTool` | marks |
| 3 | `CreateLessonPlanTool` | lessonplan |
| 4 | `CreateAssignmentTool` | assignment create |
| 5 | `CreateHomeworkTool` | homework create |
| 6 | `ApplyLeaveTool` | leave apply |
| 7 | `CreateClassWallPostTool` | classwall create |
| 8 | `CreateTaskTool` | task |
| 9 | `ViewStudentsTool` | students (read) |
| 10 | `ViewTimetableTool` | timetable basis (read) |
| 11 | `ViewEventsTool` | events (read) |
| 12 | `ViewNoticeboardTool` | notices (read) |

**Coverage vs ~219 routes:** advisory ≈ create/apply + a few reads. Remaining panel depth is dominated by classwall (likes/comments/pages), leave (edit/approve/student-leave), lessonplan edit, assignment/homework *submission review*, and desk logs — **not** “clean remainder.”

---

## Remaining-domain inventory (Teacher panel)

Counts = active routes in `routes/teacher.php` (block + line comments stripped). Mutators = non-GET.

| Domain | Routes | Mutators | Advisory Toshi today | Notes |
|---|---:|---:|---|---|
| **classwall** | 36 | 17 | create post only | Largest remainder; pages + posts + comments + likes |
| **leave** (+ myleaves + studentLeave) | 25 | 6 | apply only | Own CRUD + `designation:leave_checker` approve + `student_leave_checker` |
| **lessonplan** | 21 | 12 | create only | Richer edit / attachment surface |
| **assignment** (+ student assignment mark paths) | 17 | 6 | create only | Submission list/mark/return is the teaching loop |
| **homework** (+ studenthomework) | 15 | 3 | create only | Teacher review wired to `studentHomework-review` on panel/API |
| **desk logs** (visitor/call/postal) | 27 | 6 | none | Front-desk overlap; ranking = low Toshi priority |
| **task** | 14 | 4 | create only | Personal tasks — lower centrality than submissions |
| **attendance / marks / events / notices** | — | — | covered (advisory) | Out of Batch 2 |

---

## Ranking check — still accurate?

| Ranking claim (PR #141) | Verdict after this audit |
|---|---|
| Central remaining = homework/assignment **submission review**, leave status depth, classwall beyond create-post | **Confirmed.** Create tools exist; review/approve/depth do not. |
| Classwall held from SA Batch 2 for size | **Still holds for Teacher Batch 2** as a *full* domain (36/17). Thin slice optional later. |
| Edge = visitor/call/postal (~27) | **Confirmed** — hold. |

---

## Graduated risk — safe now vs hold

### Safe enough for Batch 2 (with Tier-2 + Gate / ownership)

| Domain | Why safe-enough | Still exclude in Batch 2 |
|---|---|---|
| **Homework submission review** | Panel + API Teacher controllers already `Gate::allows('studentHomework-review', …)`; Gate encodes ug5 teacher_id / class_teacher / Teacherlink; SA ActionService methods are reusable if Teacher auth is wired | Destroy; do **not** call SA tools as-is (`AuthorizesToshiAction` denies ug5) |
| **Assignment submission review** | Core teaching loop; Student already submits; list path scopes by `assignment.teacher_id` | **destroy**; **must fix Gate + id-only mark/update before tools** |
| **Own leave status** (list / show own / optional cancel pending) | `ApplyLeaveTool` already writes; status depth is natural | Peer leave **approve/reject** until Gate exists; destroy; **panel id-only IDOR → Gate-first** (not tool-only `user_id`) — see `docs/toshi-teacher-leave-assignment-gates-audit.md` |

### Hold for later (one-line reasons)

| Domain | Hold reason |
|---|---|
| **Classwall (full)** | Largest surface (36/17); comment/like controllers load by id without Gate; create already shipped |
| **Peer leave approve/reject** (`leave_checker`) | No `leave` Gate; approve/reject load `TeacherLeaveApplication::where('id',$id)` only — IDOR-class |
| **Student leave approve/reject** | List is class_teacher-scoped; approve/reject still id-only — IDOR-class |
| **Assignment principal approve/reject** | Designation / principal workflow — not day-to-day teacher marking |
| **Homework/assignment destroy** | Destructive; `assignment` Gate is school_id-only today (see Gate audit) |
| **Lessonplan edit depth** | Large mutator surface; create covered; lower criticality than marking submissions |
| **Desk logs** | Receptionist overlap; ranking edge |
| **Marks / records expansion** | Downstream report-card effects — keep to existing `EnterMarksTool` |
| **Any destroy** | Explicit Batch boundary |
| **Cross-cutting auth / impersonation** | Out of role batch |

---

## Proposed Batch 2 scope (~3 related domains)

### Theme: **Teaching loop — submission review + own leave status**

Aligns with ranking alternate (“Teacher Batch 1 = submission review + leave status”) and SA Batch sizing (siblings, non-destructive). Classwall full surface stays held (create already advisory).

| Sub-batch | Panel domains | Proposed tool direction | Confirm rigor |
|---|---|---|---|
| **2a — Homework submission review** | `studenthomework` list / show / update (mark checked) | Teacher-auth tools: `ListStudentHomeworkTool` / `ShowStudentHomeworkTool` / `UpdateStudentHomeworkTool` **variants** (or shared service + `AuthorizesTeacherToshiAction`) reusing `SchoolAcademicsOpsActionService::{list,show,update}StudentHomework` which already call `studentHomework-review` | Writes → Tier-2; **Gate already ug5-correct** — wire auth trait only |
| **2b — Assignment submission review** | `assignment/show/*`, `addMarks`, `editMarks` | `ListStudentAssignmentsTool`, `ShowStudentAssignmentTool`, `MarkStudentAssignmentTool` | Writes → Tier-2; **fix-first Gate** `studentAssignment-review` (mirror homework Option A) + wire Teacher/API controllers before trusting panel copy |
| **2c — Own leave status** | `leave/list`, `leaves`, `leave/show`, optional pending cancel | `ListMyLeavesTool`, `ShowMyLeaveTool` (+ optional `CancelMyLeaveTool` if product wants) | **Fix-first Gate** (`teacher-leave` + `teacher-leave-manage`) before tools; **reject** tool-only `user_id=actor` as sole fix; **hold** peer/student leave approve |

**Implementation pattern (Part B — not this PR):**

- New skill e.g. `TeacherTeachingOpsSkill` + `RouteToTeacherTeachingOpsSkillTool`, registered on `TeacherOperationsAgent` (keeps advisory leaves + routes to skill).
- Prefer **shared service methods** with `Gate::forUser($user)->allows('studentHomework-review'|new assignment review Gate)` — do not fork StudentHomework update logic.
- `UsesToshiLlm` on any new prompt()-ing skill.
- WhatsApp: fail-closed for new writes unless explicitly allowlisted later.

**Explicitly out of Batch 2:** full classwall, peer/student leave approval, desk logs, lessonplan edit suite, destroy, principal assignment approval, marks expansion.

**Success criteria (for Part B):** ug5 can list/check homework submissions and list/mark assignment submissions for **assigned** class/subject only; list own leave status; cross-teacher / cross-school denied; destroy/desk-logs/classwall-comments absent from tool registry; isolation tests green.

---

## Cross-references — shared tables vs parallel

| Domain | Shared tables / models | Existing writers / readers | Batch 2 approach |
|---|---|---|---|
| **StudentHomework** | `StudentHomework`, `Homework` | Student `SubmitHomeworkTool` / `ViewHomeworkTool`; SA Batch 2 list/show/update via `SchoolAcademicsOpsActionService` + `studentHomework-review`; Teacher panel/API already Gate-checked | **Shared service**; Teacher-auth tool wrappers (do not reuse `AuthorizesToshiAction` as-is) |
| **StudentAssignment** | `StudentAssignment`, `Assignment` | Student `SubmitAssignmentTool` / `ViewAssignmentsTool`; Teacher `CreateAssignmentTool`; **no** staff-review Gate/tools | **Shared table**; new review Gate + Teacher tools; list query already filters `assignment.teacher_id` |
| **Leave** | `TeacherLeaveApplication`, `LeaveType`, Approvals | Teacher `ApplyLeaveTool` → `TeacherActionService::applyLeave`; Student leave applications reuse same model with `standardLink_id` | Own-status tools scoped `user_id`; approve paths need new Gate before tools |
| **Classwall** *(held)* | `Post`, comments, pages | Teacher `CreateClassWallPostTool`; Student `ViewClassWallTool`; `post` / `post-destroy` Gates | Shared; hold mutations beyond create |
| **Homework parent** | `Homework` | Teacher create; SA approve/manage | Review tools take `homework_id` / `student_homework_id` — not recreate homework CRUD |

---

## Mandatory Gate / policy audit (proposed domains)

### 2a — Homework submission review — **CLEAN (for tools)**

| Layer | Evidence | Verdict |
|---|---|---|
| **Gate** | `studentHomework-review` (`AuthServiceProvider` ~248–297): ug1 unscoped; ug3 school_id; **ug5** school_id + (`homework.teacher_id` **or** class_teacher **or** Teacherlink class+subject) | **Ownership/assignment — good** |
| **Teacher panel** | `Teacher\StudentHomeworkController` list/show/update: `Gate::allows('studentHomework-review', …)` before mutate | **Clean** |
| **Teacher API** | `API\Teacher\StudentHomeworkController` show/update: same Gate | **Clean** |
| **Student owner Gate** | `studentHomework` = student `user_id` only — correctly **not** used for staff review | Do not widen |
| **SA tools** | `List/Show/UpdateStudentHomeworkTool` use `AuthorizesToshiAction` (ug3/ug4) + service Gate check | Service reusable; **tool auth trait must be Teacher (or dual)** for ug5 |
| **Related panel gap (non-blocking for 2a)** | `Teacher\Approval\HomeWorkController` show/edit/update still `Homework::where('id',$id)->first()` without Gate; **destroy** uses `homework` Gate (teacher_id). Flag for separate panel hygiene — not required to ship review tools that only touch StudentHomework via the clean controllers/service | Fix-later |

**Batch 2 implication:** 2a can proceed in Part B **without** a new Gate PR — only Teacher wiring + tests.

---

### 2b — Assignment submission review — **NEEDS FIX-FIRST (IDOR-class)**

| Layer | Evidence | Verdict |
|---|---|---|
| **Gate `assignment`** | `return $user->school_id == $assignment->school_id` only (`AuthServiceProvider` ~223–225) | **School-only** — any same-school teacher passes; **not** teacher_id / Teacherlink |
| **Gate `studentassignment`** | Student owner `user_id` + school via assignment (`~227–234`); tests in `LegacyPortalIdorOwnershipTest` are **student peer** scope | **Wrong Gate for staff review** (same pattern as pre-`studentHomework-review`) |
| **Missing** | No `studentAssignment-review` (or assignment-manage) analogous to homework Option A | **Gap** |
| **Teacher panel mark/update** | `StudentAssignmentController@store` / `@update` / `@show`: `StudentAssignment::where('id',$id)->first()` — **no Gate, no teacher_id check** (~104, ~163, ~185) | **IDOR-class** — any authenticated teacher who knows an id can mark another teacher’s submission |
| **Teacher panel list** | `showAssignmentList` scopes via `whereHas('assignment', teacher_id = Auth::id())` | List OK; mutators not |
| **Teacher panel assignment edit/show** | `AssignmentController` / `Approval\AssignmentController` load by id; destroy uses school-only `assignment` Gate | Within-school privilege gap |
| **Teacher API** | `API\Teacher\StudentAssignmentController` list by `assignment_id` **without** teacher_id filter; store/update id-only | **IDOR-class** (worse than panel list) |

**Batch 2 implication:** Ship a **Gate Part B** (mirror #152): define `studentAssignment-review` (ug5 = assignment.teacher_id / class_teacher / Teacherlink; ug3 school_id; ug1 unscoped), wire Teacher + API StudentAssignment controllers, tighten `assignment` mutate/destroy to teacher ownership (or separate manage Gate). **Then** build Toshi tools. Do not copy id-only controller logic into ActionService.

---

### 2c — Leave — **own-status = FIX-FIRST (Gate); approve = FIX-FIRST (separate)**

| Path | Evidence | Verdict |
|---|---|---|
| **Gate** | **No** `leave` / `teacher-leave` / `leave-approve` / `studentLeave` Gate in `AuthServiceProvider` | Missing |
| **Apply (advisory)** | `ApplyLeaveTool` → `TeacherActionService::applyLeave` | Already shipped |
| **Own show/edit/update/destroy** | `LeaveController` (+ API): `TeacherLeaveApplication::where('id',$id)->first()` with **no** `user_id` / school_id / Gate (~283, ~337, ~394) | **IDOR-class** — **Gate-level fix required** (not tool-only `user_id`) |
| **FormRequests** | `LeaveEditRequest` / `LeaveApproveRequest` `authorize()` → `true` | No ownership |
| **Admin moderation?** | **Yes:** `Admin\ApprovalController` inbox (school-scoped Approvals; Teacher leave creates `Approval`); `TeacherShowController@showLeaveHistory` school-scoped read. Leave *types* only via `LeaveTypesController`. | ug1/ug3 school-scoped + owner ug5 on Gate pair |
| **Peer approve/reject** | Routes under `middleware designation:leave_checker`; `approveStore`/`rejectStore` id-only (~477, ~547) | Designation ≠ row authorization — **held** (separate Gate) |
| **Student leave list** | `StudentLeaveController@indexList`: `standardLink_id ∈ class_teacher` + school_id | List OK |
| **Student leave approve/reject** | id-only load (~110, ~181); API Teacher mirror same | **IDOR-class** — **held** |

**Batch 2 implication (updated):**

- **Own leave:** propose `teacher-leave` (ug5 owner) + `teacher-leave-manage` (ug1 / ug3 school) — full proposal in `docs/toshi-teacher-leave-assignment-gates-audit.md`. **Reject** tool-only `user_id=actor` as sole fix.
- **Approve/reject (peer or student):** still **out of Batch 2 tools**; separate Gate PR later.

---

### Classwall (held) — Gate notes for later

| Path | Evidence | Verdict |
|---|---|---|
| **Read `post`** | school_id match; Teacher `PostsController@show` uses Gate | OK for school-scoped read |
| **Destroy `post-destroy`** | author / ug3 school / ug1; Teacher destroy uses Gate | OK for author destroy |
| **Edit** | `PostEditController` checks `created_by == Auth::id()` | Ownership OK (no Gate, but ownership check) |
| **Comments / likes** | `PostCommentsController` etc. load post/comment by id — **no Gate** | IDOR-class for later classwall batch |
| **`post_reply` Gate** | Uses undefined `$post_reply` variable in closure (`AuthServiceProvider` ~167–168) | **Broken Gate definition** — fix before any reply tools |

---

## Gate-audit summary (Batch 2 decision table)

| Proposed sub-batch | Gate posture | Panel/API posture | Part B order |
|---|---|---|---|
| **2a Homework review** | **Clean** (ug5 assignment-aware) | Teacher review controllers clean | Tools OK after Teacher auth wiring |
| **2b Assignment review** | **Needs fix-first** (school-only / student-owner only / missing review Gate) | Mark/update id-only; API list unscoped | **Gate PR → then tools** |
| **2c Own leave status** | **Needs fix-first** (`teacher-leave` + manage) | Panel/API id-only; FormRequests authorize true | **Gate PR → then tools** (see leave-assignment gates doc) |
| **Leave approve / student leave** *(not in Batch 2 tools)* | Missing | id-only approve | **Fix-first** (separate) |
| **Classwall beyond create** *(held)* | post OK; comments weak; post_reply broken | Large surface | Hold |

---

## Open questions for Part B approval

1. ~~Confirm Batch 2 = homework review + assignment review + own leave status~~ — **approved** (ground truth).  
2. Confirm **Gate designs** in `docs/toshi-teacher-leave-assignment-gates-audit.md`: leave Option A (dual) + `studentAssignment-review` (mirror homework-review).  
3. Confirm peer/student **leave approve** stays out of the leave Gate PR (own/manage only).  
4. Skill name: `TeacherTeachingOpsSkill` + `RouteToTeacherTeachingOpsSkillTool` vs flattening new leaves onto `TeacherOperationsAgent`?  
5. Reuse `SchoolAcademicsOpsActionService` student-homework methods from Teacher tools (dual-auth) vs thin TeacherActionService delegates?

---

## Gate proposals (Part A continuation)

Full evidence + proposed Gate code shapes: **`docs/toshi-teacher-leave-assignment-gates-audit.md`**.

| Fix | Proposed Gates | Implement before |
|---|---|---|
| Own-leave panel IDOR | `teacher-leave` + `teacher-leave-manage` | Own-leave tools (2c) |
| Assignment review IDOR | `studentAssignment-review` (do **not** widen `studentassignment`) | Assignment review tools (2b) |

Homework review (2a) remains Gate-clean — tools-only after Teacher auth wiring.

---

## Ready for Part B?

**Batch 2 scope approved.** Gate proposals drafted — **awaiting approval of Gate designs** before implementation branches. **No Gates implemented. No Batch 2b tools on this branch.**
)
