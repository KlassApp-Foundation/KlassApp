# Toshi Teacher — Own-leave + Assignment-review Gate proposals (Part A continuation)

> Branch: `audit/toshi-teacher-batch2` (extends PR #155)  
> Date: 2026-08-03  
> Scope: **docs-only Gate proposals** for (1) own-leave panel IDOR and (2) assignment submission-review IDOR.  
> **Do not implement Gates or Batch 2b tools on this turn.** Awaiting approval → implement on dedicated fix branches before Batch 2b tools.

Parent inventory: `docs/toshi-teacher-batch2-audit.md`. Ground truth: Batch 2 approved domains = homework review + assignment review + own leave list/show/optional-cancel. Hold = classwall, peer leave approve, destroy, marks expansion.

---

## Sequencing (non-negotiable)

| Step | What | Status |
|---|---|---|
| 1 | Propose **both** Gate fixes (this doc) | **This PR update** |
| 2 | Wait for approval of Gate designs | **Awaiting** |
| 3 | Implement on fix branches (mirror homework Gate #152 / #154) | Not started |
| 4 | Then Batch 2b tools (assignment review + own leave) | Blocked on step 3 |

**Rejected shortcut:** tool-only `user_id = actor` (or ActionService filter) as the **sole** fix for own-leave. Panel/API remain IDOR-class for any non-Toshi client; Gate-level ownership is required — same discipline as `post-destroy` / `event-destroy` / `homework` ownership.

---

## 1. Own-leave — current auth evidence

### No leave Gate today

`AuthServiceProvider` defines **no** `leave`, `teacher-leave`, `leave-own`, or `leave-approve` Gate. Leave authorization is route middleware + ad-hoc queries only.

### Teacher panel (`Teacher\LeaveController`)

| Action | Load / auth | Verdict |
|---|---|---|
| `list` (applier) | `user_id = Auth::id()` + school + academic year | List OK for applier |
| `list` (checker) | `reporting_to = Auth::id()` + pending (peer path — **held**) | Separate approve problem |
| `myList` | `user_id = Auth::id()` + school + year | List OK |
| `store` | Sets `user_id = Auth::id()`; creates `Approval` morph | Apply OK (advisory already) |
| **`show` / `view` / `edit` / `update` / `destroy`** | `TeacherLeaveApplication::where('id', $id)->first()` — **no** `user_id`, **no** `school_id`, **no** Gate | **IDOR-class** |
| `approveStore` / `rejectStore` | Same id-only load; routes under `designation:leave_checker` | Designation ≠ row auth — **held** (peer approve) |

Evidence (id-only show / update / destroy):

```276:295:app/Http/Controllers/Teacher/LeaveController.php
    public function show($id)
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $array = [];

        $leave = TeacherLeaveApplication::where('id',$id)->first();
        // ... returns leave fields with no ownership check ...
        return $array;
    }
```

```332:347:app/Http/Controllers/Teacher/LeaveController.php
    public function update(LeaveEditRequest $request, $id)
    {
        try
        {
            $leave = TeacherLeaveApplication::where('id',$id)->first();
            // ... mutates any id ...
            $leave->save();
```

```389:399:app/Http/Controllers/Teacher/LeaveController.php
    public function destroy($id)
    {
        try
        {
            $leave = TeacherLeaveApplication::where('id',$id)->first();
            $leave->status     =   'cancelled';
            $leave->save();
            $leave->delete();
```

### Teacher API (`Api\Teacher\LeaveController`)

| Action | Auth | Verdict |
|---|---|---|
| `index` / `availableList` | `user_id = Auth::id()` | List OK |
| `store` | Sets actor as `user_id` | Apply OK |
| **`show` / `update` / `destroy`** | `where('id',$id)->first()` only | **IDOR-class** (same as panel) |
| `leaveCheckList*` / approve·reject | `designation:leave_checker` + list scoped to reportees; mutate still id-only | **Held** |

### FormRequests — authorize always true

```19:22:app/Http/Requests/LeaveEditRequest.php
    public function authorize()
    {
        return true;
    }
```

```15:18:app/Http/Requests/LeaveApproveRequest.php
    public function authorize()
    {
        return true;
    }
```

`LeaveEditRequest` uniqueness validators filter by `Auth::id()`, but that does **not** authorize the target row — a teacher can still edit another teacher’s leave id.

### Admin leave surface — **does ug3/ug1 moderate teacher leave?**

**Answer: yes — school-scoped admin moderation is legitimate (not peer `leave_checker`).**

| Surface | Evidence | Role |
|---|---|---|
| **Approvals inbox** | `routes/admin.php`: `/approvals`, approve/reject. `ApprovalController@inbox` scopes by `approvable.school_id`. Teacher `store` creates `Approval` for `TeacherLeaveApplication` | ug3 (MustBeSchoolAdmin); ug1 when on admin routes |
| **Teacher profile leave history** | `Admin\TeacherShowController@showLeaveHistory` — lists `TeacherLeaveApplication` for a named teacher with `school_id = Auth::user()->school_id` | **Read** moderation / HR view |
| **Leave types** | `Admin\LeaveTypesController` — config CRUD for leave *types*, not applications | Config only |
| **No Admin LeaveApplication CRUD controller** | No `Admin\LeaveController` for teacher applications | Moderation is Approvals + profile history |

```19:36:app/Http/Controllers/Admin/ApprovalController.php
    public function inbox()
    {
        $schoolId = Auth::user()->school_id;
        $approvals = Approval::with(['approvable', 'requester', 'reviewer'])
            ->whereHas('approvable', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            // ...
```

```96:109:app/Http/Controllers/Admin/TeacherShowController.php
    public function showLeaveHistory($name)
    {
      $user = User::where('name', $name)->first();
      $school_id = Auth::user()->school_id;
      // ...
      $leave = TeacherLeaveApplication::where([
                ['user_id',$user->id],
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id]
              ])->paginate(5);
```

`TeacherLeaveApplication` implements `Approvable` + `HasApprovals` — intended for the unified admin inbox.

**Not admin moderation:** Teacher `designation:leave_checker` peer approve (reporting_to). That stays **held** and needs a **separate** Gate later (`leave-approve` / reporting-line), not folded into own-leave.

---

## 1b. Proposed Gate design — own / manage leave

Mirror homework Option A (`homework` owner vs `homework-manage` admin) and `post-destroy` (author + ug1 + ug3).

### Option A (recommended) — dual Gates

#### `teacher-leave` — owner self-actions (Batch 2 tools + Teacher panel/API id routes)

Accepts `TeacherLeaveApplication` (null → deny).

| Role | Rule |
|---|---|
| **ug5** | `(int) $user->school_id === (int) $leave->school_id` **and** `(int) $user->id === (int) $leave->user_id` |
| **ug1 / ug3 / others** | **deny** on this Gate |

Wire: Teacher (+ API Teacher) `show` / `view` / `edit` / `update` / pending `destroy` (cancel). Toshi `ShowMyLeaveTool` / `CancelMyLeaveTool` must call `Gate::forUser($user)->allows('teacher-leave', $leave)` — **not** only `where('user_id', $actor)`.

#### `teacher-leave-manage` — admin school moderation

| Role | Rule |
|---|---|
| **ug1** | allow (unscoped) |
| **ug3** | `(int) $user->school_id === (int) $leave->school_id` |
| **ug5 / others** | deny |

Wire: `Admin\ApprovalController` when `$approval->approvable` is `TeacherLeaveApplication` (load leave, Gate before transition); optionally `TeacherShowController@showLeaveHistory` rows (or school-scoped query already — Gate on each row for consistency).

### Option B — single Gate (post-destroy style)

One `teacher-leave` with branches: ug1 allow; ug3 school_id; ug5 owner. Simpler registration; weaker separation between “own cancel” and “admin inbox approve.” Prefer Option A unless reviewers want one Gate.

### Explicitly out of this Gate PR

| Concern | Plan |
|---|---|
| Peer `leave_checker` approve/reject | Separate `leave-approve` Gate (school + reporting_to / designation) — **held** |
| Student leave approve | Separate `studentLeave-approve` — **held** |
| Hard destroy beyond soft-cancel | Out of Batch 2 |

### Why not tool-only `user_id = actor`

Panel/API id routes remain callable by any authenticated teacher (browser, mobile API, future clients). Homework/post/event discipline was: **fix first, then tools**. Own-leave follows the same rule.

---

## 2. Assignment submission review — current auth evidence

### Existing Gates (exact)

```223:234:app/Providers/AuthServiceProvider.php
      Gate::define('assignment', function ($user, $assignment) {
        return $user->school_id == $assignment->school_id;
      });

      Gate::define('studentassignment', function ($user, $studentassignment) {
        if ($studentassignment === null || $studentassignment->assignment === null) {
          return false;
        }

        return (int) $user->id === (int) $studentassignment->user_id
          && (int) $user->school_id === (int) $studentassignment->assignment->school_id;
      });
```

| Gate | Purpose today | Safe to widen? |
|---|---|---|
| **`assignment`** | School_id only — used on Teacher assignment **destroy** | **Do not** use for staff *review*; optionally tighten later for destroy (held) |
| **`studentassignment`** | Student owner (#130) — Student panel show/destroy | **Do not widen** for staff review (same rule as `studentHomework`) |

Student owner wiring (must remain):

```180:182:app/Http/Controllers/Student/AssignmentController.php
        if ($studentAssignment === null || Gate::denies('studentassignment', $studentAssignment)) {
            abort(403);
        }
```

### Pattern to mirror — `studentHomework-review`

```246:297:app/Providers/AuthServiceProvider.php
      // Staff review of submissions (admin / teacher / teacher API). Accepts Homework (list) or StudentHomework.
      // ug5: assigned class/subject only — teacher_id, class teacher, or Teacherlink (same as CreateHomeworkTool).
      Gate::define('studentHomework-review', function ($user, $model) {
        // ... ug1 unscoped; ug3 school_id; ug5 teacher_id | class_teacher | Teacherlink ...
      });
```

### Teacher panel mark/update — IDOR

Routes: `POST /assignment/addMarks/{id}`, `GET …/edit/list/{id}`, `POST /assignment/editMarks/{id}`.

```99:112:app/Http/Controllers/Teacher/StudentAssignmentController.php
    public function store(StudentAssignmentUpdateRequest $request,$id)
    {
        try
        {
            $studentAssignment     =   StudentAssignment::where('id',$id)->first();
            $studentAssignment->obtained_marks  =   $request->obtained_marks;
            // ... no Gate ...
```

```160:170:app/Http/Controllers/Teacher/StudentAssignmentController.php
    public function show($id)
    {
        $studentAssignment     =   StudentAssignment::where('id',$id)->first();
        // ... no Gate ...
```

List path `showAssignmentList` scopes `assignment.teacher_id = Auth::id()` — **list OK**; mutators/show by submission id are not.

### Teacher API — worse list + same mutator IDOR

```33:37:app/Http/Controllers/Api/Teacher/StudentAssignmentController.php
    public function submittedAssignmentList(Request $request,$assignment_id)
    {
        $studentAssignment = StudentAssignment::where([['assignment_id',$assignment_id],['status','submitted']])->get();
        // no teacher_id / Gate on parent Assignment
```

`store` / `show` / `update`: id-only, no Gate (imports `Gate` unused).

### Admin

No `Admin\StudentAssignmentController` — staff review for assignments is Teacher-path today. Still include **ug1/ug3** on the new review Gate for parity with homework and future admin tools.

---

## 2b. Proposed Gate design — `studentAssignment-review`

### New Gate only — do not widen `studentassignment`

Name: **`studentAssignment-review`** (camel parallel to `studentHomework-review`).

Accepts `Assignment` **or** `StudentAssignment` (resolve parent `assignment` via `loadMissing('assignment')`).

| Role | Rule |
|---|---|
| **ug1** | allow |
| **ug3** | `(int) $user->school_id === (int) $assignment->school_id` |
| **ug5** | same school **and** any of: `(int) $assignment->teacher_id === (int) $user->id` **or** class teacher on `$assignment->standardLink` **or** `Teacherlink` exists for `(teacher_id, standardLink_id, subject_id)` |
| **else** | deny |

### Wire (Part B implementation — not this PR)

| Site | Change |
|---|---|
| `Teacher\StudentAssignmentController` | `store` / `show` / `update` (+ optionally `index` / list after loading Assignment) → `Gate::allows('studentAssignment-review', …)` |
| `Api\Teacher\StudentAssignmentController` | list by `assignment_id`: authorize parent `Assignment` first; store/show/update same Gate |
| Leave **`studentassignment`** | Untouched — student owner #130 |
| Leave **`assignment`** | Untouched for now; destroy still school-only — **held** for later ownership tighten |
| Toshi Batch 2b mark/list tools | `Gate::forUser` + shared service — never copy id-only controller body |

### Tests (when implementing)

Mirror `LegacyPortalHomeworkAuthorizationTest` / IDOR suite: peer ug5 denied; owner teacher allowed; class_teacher / Teacherlink allowed; ug3 same-school allowed; cross-school denied; student Gate still peer-denied.

---

## Decision summary (for approval)

| Proposal | Design | Admin answer | Wire targets |
|---|---|---|---|
| **Own-leave** | Option A: `teacher-leave` (ug5 owner) + `teacher-leave-manage` (ug1 / ug3 school) | **Yes** — Approvals inbox + teacher leave history are legitimate ug3 moderation | Teacher/API id routes; Admin Approval when approvable is leave |
| **Assignment review** | New `studentAssignment-review` (mirror `studentHomework-review`); **keep** `studentassignment` student-only | N/A (no admin StudentAssignment controller today; ug3 still on Gate for parity) | Teacher + API StudentAssignment mark/list/show |

**Confirm before Part B implementation:**

1. Approve leave Option A (dual) vs Option B (single).  
2. Approve `studentAssignment-review` shape (teacher_id + class_teacher + Teacherlink).  
3. Confirm peer leave approve / `assignment` destroy tighten remain **out** of this Gate PR pair.

**Status:** Proposals only. **Awaiting approval** before Gate implementation branches and before Batch 2b tools.
