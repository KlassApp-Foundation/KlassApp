# Toshi Role Parity Audit (read-only)

> Branch: `audit/toshi-role-parity` off `origin/main` @ `e5db3b3`  
> Date: 2026-08-01  
> Scope: audit only — no tools built. Platform-scope work (`feature/toshi-platform-tools`) not touched.  
> Depth this pass: **School Admin (full)** → **Teacher** → **Accountant**. Librarian / Receptionist / Student deferred pending review.

---

## Shared UI / gate facts (applies to all school roles)

### Blade mount (authoritative UI gate)

Both shells that wrap role dashboards:

```php
// resources/views/layouts/app.blade.php:65-66
@if(in_array(auth()->user()->usergroup_id, [1, 3]))
    <div v-pre>@livewire('agent-toshi')</div>
```

Same allowlist in `layouts/superadmin-app.blade.php`.

Teacher / Accountant / Receptionist / Librarian / Student layouts all `@extends('layouts.app')`, so they inherit this gate. **Non-[1,3] roles never receive the Livewire component** — pill absence is not a missing `@livewire` on the role dashboard view.

### Intentional vs accidental

**Intentional.** Commit `7c4b314` (2026-07-01): *"fix: restrict Toshi to super admin (1) and school admin (3) only"* — added Blade `[1, 3]` and a matching early-return in `AgentToshi::mount()`. The PHP early-return was later removed (mount now uses `getRoleCapabilities()` emptiness), but **Blade still hard-restricts to `[1, 3]`**. Brief flirtation with ug2 (`b0ef137`) was reverted (`5779e8d`). No commit ever opened Teacher/Accountant/etc. to the UI.

### Capability map vs enforcement

| Layer | Role |
|---|---|
| `getRoleCapabilities()` | Advisory LLM / UI context only |
| Blade `[1, 3]` | Whether panel mounts |
| `ToshiSdkV2Service::isAvailable()` | School path: `sdk_v2_enabled` + `per_school_gate` → school with `toshi_enabled` + AI key |
| Gate `toshi-school-action` | Tool execution: **ug3 only** (or ug1 impersonating ug3). All other usergroups **denied** |

So even if Blade were widened to ug5/11, SDK tools would still refuse via Gate unless Gate changed.

---

## Role 1 — School Admin (usergroup 3)

### (a) Does the pill/panel render?

**Yes.** Dashboard uses `layouts.admin.layout` → `layouts.app`. ug=3 passes Blade allowlist. Panel + toggle mount.

### (b) Does `isAvailable()` return true?

**Conditional on the school flag — not a silent siteadmin-style always-false.**

| Check | Local evidence (2026-08-01) |
|---|---|
| `sdk_v2_enabled` | `true` |
| AI key set | yes |
| School 1 (`Test School One`, admin id=5) | `toshi_enabled=0` → `isAvailable=false` |
| School 19 (admin id=101) | `toshi_enabled=1` → `isAvailable=true` |

When false: NL falls through keyword router then `fallbackMessage()` (same soft-fail pattern as siteadmin, but **rooted in school flag**, not null `school_id`).

### (c) NL / tool coverage spot-check (static map + gate)

`ToshiOrchestrator` routes to 6 skills with **26 concrete tools** (plus 6 routers). Gate allows ug3.

| `getRoleCapabilities()` action | Tool present? | Notes |
|---|---|---|
| `add_student` | ✅ `AddStudentTool` | |
| `add_teacher` | ✅ `AddTeacherTool` | |
| `add_coadmin` | ✅ `AddCoAdminTool` | |
| `create_fee` | ✅ `CreateFeeTool` | |
| `create_term` | ✅ `CreateTermTool` | |
| `record_attendance` | ✅ `RecordAttendanceTool` (+ bulk) | |
| `record_payment` | ✅ `RecordPaymentTool` | |
| `create_exam` | ✅ `CreateExamTool` | |
| `add_parent` | ✅ `AddParentTool` | |
| `enter_mark` | ✅ `EnterMarkTool` | |
| `assign_teacher` | ✅ `AssignTeacherTool` | |
| `create_subject` | ✅ `CreateSubjectTool` | |
| `list_classes` | ✅ `ListClassesTool` | |
| `list_teachers` | ✅ `ListTeachersTool` | |
| `list_sections` | ✅ `ListSectionsTool` | |
| `generate_report` | ✅ `GenerateReportTool` | Narrow vs full `/admin/report/*` CSV suite |

**Tools beyond capability list:** `FindStudent`, `GetStudentCount`, `GetFeeBalance`, `CreateStream`, `AssignStudentsToStream`, grading trio + `SetCurriculum`.

### Route inventory (admin panel — Batch-style domains)

~**588** `/admin/*` routes across ~**108** path domains. Mutating methods ~**189**. Top domains by route count:

| Domain | Routes | Toshi tool coverage |
|---|---:|---|
| classwall | 42 | ❌ gap |
| student | 36 | ✅ partial (add/find/count; not full CRUD/block/promote) |
| teacher | 30 | ✅ partial (add/assign/list; not leave/payroll/docs) |
| dashboard | 24 | N/A / thin |
| standardLink | 24 | ❌ gap (streams tools cover a slice) |
| report / reports | 22 | ✅ thin (`GenerateReportTool` ≠ full CSV suite) |
| settings / setting | 29 | ❌ gap |
| academics / academic / academic-term | 32 | ✅ partial (term/subject/exam) |
| parent | 14 | ✅ partial (add only) |
| homework | 13 | ❌ gap |
| events | 12 | ❌ gap |
| library | 11 | ❌ gap |
| task | 11 | ❌ gap |
| visitorlog / calllog / postalrecord | 29 | ❌ gap |
| marks | 10 | ✅ enter mark |
| discipline / notice / holiday | 24 | ❌ gap |
| attendance | 8 | ✅ |
| fees / fees-categories / payment | ~14 | ✅ create fee + record payment + balance |
| grades | 7 | ✅ grading tools |
| timetable | 7 | ❌ gap |
| transport / promotion / admission / whatsapp / messaging | many | ❌ gap |

### Capability map verdict

- Advisory list (16 actions) ≈ **covered by tools** for the narrow operator set those actions describe.
- **"No capability gaps found for School Admin"** in Role 2 audit (`knowledge.md` ~1735) referred to **route middleware / usergroup scoping**, **not** Toshi-vs-panel parity. Treat that phrase as **unrelated to Toshi coverage**.
- Knowledge "Toshi now parity with admin panel (or better)" (Jun 29) was about **onboarding `commitAll` data shape**, not post-onboarding operator surface.

### Gap list (Toshi vs admin panel)

1. **Large panel surface with zero tools:** classwall, homework, events, noticeboard, holidays, discipline, leave types, library, transport, visitor/call/postal logs, timetable, promotion, admissions, messaging, WhatsApp admin, settings, documents, magazines, stock reports, etc.
2. **Partial domains:** students/teachers/parents/reports — create/list/query only; missing edit/delete/block/import/export and most admin CSV reports.
3. **School flag dependency:** many real schools (incl. Test School One) have `toshi_enabled=0` → SDK NL soft-fails even though pill mounts.
4. **Onboarding vs assistant:** ug3 still gets `complete` mode when onboarding steps missing; assistant tools are the post-complete path.

### School Admin summary

| Question | Answer |
|---|---|
| Pill renders? | **Yes** (Blade `[1,3]`) |
| `isAvailable`? | **Yes iff** school `toshi_enabled` (+ SDK flag + key) |
| Capability list vs tools? | **~aligned** for the 16 advisory actions |
| Panel parity? | **No** — ~subset of core CRUD; majority of admin domains ungapped in tools |
| Prior "no gaps" note? | **Does not settle Toshi parity** — was middleware/scoping audit |

---

## Role 2 — Teacher (usergroup 5)

### (a) Does the pill/panel render?

**No.** Uses `layouts.teacher.layout` → `layouts.app`, but Blade `@if(...[1, 3])` excludes ug=5. Confirmed: no other `@livewire('agent-toshi')` under `resources/views/teacher/`.

### (b) `isAvailable()`?

**N/A for UI** (component never mounts). Hypothetically: same school-flag rules; teacher id=30 on school 1 → `toshi_enabled=0` → false. Even on an enabled school, **Gate `toshi-school-action` denies ug5** — tools would return unauthorized.

### (c) NL spot-check

**Cannot run via UI.** Advisory `getRoleCapabilities(5)` claims 12 actions (`mark_attendance`, `enter_marks`, `manage_lesson_plans`, `manage_assignments`, `manage_homework`, `apply_leave`, `manage_class_wall`, `view_students`, `view_timetable`, `view_events`, `manage_tasks`, `manage_noticeboard`) — **zero corresponding teacher-scoped tools**; existing attendance/marks tools are school-admin Gate-bound.

### Route inventory (prefix `teacher/`)

~**219** routes. Top: classwall(34), lessonplan(20), assignment(16), leave(16), standardLink(14), student(12), homework(11), task(11), visitor/call/postal logs, exams/marks.

### Capability map vs gap list

| Capability claim | Reachable via Toshi? | Panel routes exist? |
|---|---|---|
| All 12 advisory actions | ❌ UI absent + Gate deny | ✅ mostly yes under `/teacher/*` |

**Gap:** 100% of claimed Toshi teacher actions — UI not rolled out; enforcement Gate is school-admin-only; no teacher tools exist.

### UI-absence flag

**Intentional** — commit `7c4b314` explicitly restricted to ug 1 and 3. Advisory capabilities look like **aspirational / ahead-of-UI** scaffolding, not a forgotten mount.

---

## Role 3 — Accountant (usergroup 11)

### (a) Does the pill/panel render?

**Yes (on `feature/toshi-accountant-role`).** Blade allowlist is `[1, 3, 5, 11]` in `layouts.app`. Accountant layout extends `layouts.app`, so ug11 mounts `@livewire('agent-toshi')`.

### (b) `isAvailable()`?

Same school-flag rules as other roles. Gate `toshi-accountant-action` allows ug11 with `school_id` (and ug1→ug11 impersonation). `toshi-school-action` and `toshi-teacher-action` still deny ug11.

### (c) NL spot-check

Caps claim: `record_payment`, `manage_payroll`, `view_unpaid_reports`, `view_fee_structure`, `view_dashboard`, `manage_tasks`. Implemented via `AccountantOperationsAgent` + `AccountantActionService` (accountant panel paths — not ug3 `ToshiActionService`).

### Route inventory (prefix `accountant/`)

~**107** routes. Top: payroll(50), dashboard(17), task(11), events, fees, unpaid, notices.

### Capability map vs gap list

| Capability claim | Toshi? | Panel? |
|---|---|---|
| `record_payment` | ✅ `Accountant\RecordPaymentTool` (Tier-2 confirm) | ✅ `/accountant/fees/...` |
| `manage_payroll` | ✅ `ManagePayrollTool` (Tier-2 confirm; batch by template) | ✅ large payroll tree |
| `view_unpaid_reports` | ✅ `ViewUnpaidReportsTool` (read-only) | ✅ unpaid / payroll reports |
| `view_fee_structure` | ✅ `ViewFeeStructureTool` (read-only) | ✅ |
| `view_dashboard` | ✅ `ViewDashboardTool` (read-only) | ✅ |
| `manage_tasks` | ✅ `CreateTaskTool` (Tier-2 confirm) | ✅ |

**Gap:** closed for advisory set on accountant branch. Payroll batch UI false-alarm noted in knowledge (July 10) — blade/content present; overlay/redirect confusion.

### UI-absence flag

**Resolved on accountant branch** — Blade `[1, 3, 5, 11]`.
---

## Role 4 — Librarian (usergroup **8** — confirmed via `MustBeLibrarian` + `getRoleCapabilities`)

### (a) Does the pill/panel render?

**Yes (on `feature/toshi-librarian-role`).** Blade allowlist is `[1, 3, 5, 8, 11]` in `layouts.app`. Librarian layout extends `layouts.app`, so ug8 mounts `@livewire('agent-toshi')`.

### (b) `isAvailable()`?

Same school-flag rules as other roles. Gate `toshi-librarian-action` allows ug8 with `school_id` (and ug1→ug8 impersonation). `toshi-school-action`, `toshi-teacher-action`, and `toshi-accountant-action` still deny ug8.

### (c) NL / tool reachability

Caps claim (after rename): `manage_books`, `manage_book_categories`, `manage_lending`, `view_library_cards`, `view_dashboard`, `manage_tasks`. Implemented via `LibrarianOperationsAgent` + `LibrarianActionService`.

**Write vs read (flagged):** books / categories / lending / tasks are **panel writes** → Tier-2 ConfirmsBeforeWrite. Dashboard + library cards are **read-only**.

### Route inventory (prefix `library/`)

~**41** routes including view-only `GET /library/cards` (`library.cards`, `MustBeLibrarian`). Shared lookup: `LibraryCardLookupService` (also used by admin `cardIndex`). Admin `/admin/library/cards` retained.

### Capability map vs gap list

| Capability claim | Toshi? | Panel (`/library/*`)? |
|---|---|---|
| `manage_books` | ✅ `ManageBooksTool` (Tier-2 write) | ✅ books CRUD |
| `manage_book_categories` | ✅ `ManageBookCategoriesTool` (Tier-2 write) | ✅ bookscategory CRUD |
| `manage_lending` | ✅ `ManageLendingTool` (Tier-2 write) | ✅ booklending CRUD |
| `view_library_cards` | ✅ `ViewLibraryCardsTool` (read-only) | ✅ `/library/cards` view-only |
| `view_dashboard` | ✅ `ViewDashboardTool` (read-only) | ✅ |
| `manage_tasks` | ✅ `CreateTaskTool` (Tier-2 write) | ✅ task CRUD |

**Gap:** closed for advisory set on librarian branch (view-only cards). Issue/return/create/update card CRUD is **follow-up**.

### UI-absence flag

**Resolved on librarian branch** — Blade `[1, 3, 5, 8, 11]`.

---

## Role 5 — Receptionist (usergroup 10)

### (a) Does the pill/panel render?

**Yes (on `feature/toshi-receptionist-role`).** Blade allowlist is `[1, 3, 5, 8, 10, 11]` in `layouts.app`. Reception layout extends `layouts.app`, so ug10 mounts `@livewire('agent-toshi')`.

### (b) `isAvailable()`?

Same school-flag rules as other roles. Gate `toshi-receptionist-action` allows ug10 with `school_id` (and ug1→ug10 impersonation). `toshi-school-action`, `toshi-teacher-action`, `toshi-accountant-action`, and `toshi-librarian-action` still deny ug10.

### (c) NL / tool reachability

Caps claim (after Part B hygiene): `manage_visitor_log`, `manage_call_log`, `manage_postal_record`, `view_dashboard`, `view_events`, `view_noticeboard`, `manage_tasks`. Implemented via `ReceptionistOperationsAgent` + `ReceptionistActionService` (receptionist panel paths — not ug3 `ToshiActionService`). **`manage_email_record` dropped** (not follow-up).

### Route inventory (prefix `receptionist/`)

~**75** routes, ~**16** mutators, **18** domains. Top: dashboard(15), task(11), visitorlog(11), calllog(9), postalrecord(9), events(4), notices, notifications, holidays, profile.

`EmailRecordController.php` remains dead code (no routes) — capability removed from advisory set.

### Capability map vs gap list

| Capability claim | Toshi? | Panel? |
|---|---|---|
| `manage_visitor_log` | ✅ Tier-2 | ✅ visitorlog CRUD |
| `manage_call_log` | ✅ Tier-2 | ✅ calllog CRUD |
| `manage_postal_record` | ✅ Tier-2 | ✅ postalrecord CRUD |
| `manage_email_record` | ❌ **DROPPED** | ❌ abandoned scaffold |
| `view_dashboard` | ✅ read | ✅ |
| `view_events` | ✅ read | ✅ events (read-only) |
| `view_noticeboard` | ✅ read (renamed) | ✅ list/index only |
| `manage_tasks` | ✅ Tier-2 | ✅ task CRUD |

**Gap:** resolved on receptionist Part B (7 tools).

### UI-absence flag

**Resolved on receptionist branch** — Blade `[1, 3, 5, 8, 10, 11]`.

---

## Role 6 — Student (usergroup 6)

### (a) Does the pill/panel render?

**No.** `layouts.student.layout` → `layouts.app`; excluded by `[1, 3]`.

### (b) `isAvailable()`?

**UI N/A.** Caps use `scope => 'self'` (not `school`) — distinct from other roles. Local student id=46 on school 1 → `toshi_enabled=0` → false under current `isAvailable()` school-flag path. Gate `toshi-school-action` denies ug6. Any future student Toshi would need a **self-scope** availability story (not just widening Blade), separate from school-admin/teacher operator tools.

### (c) NL / tool reachability

**Cannot run via UI.** Existing student-domain tools (`AddStudentTool`, attendance, marks, etc.) are **admin operator** tools (mutate other people), not student self-service. No student-facing view tools.

### Route inventory (prefix `student/`)

~**75** routes, ~**22** mutators, **20** domains. Top: classwall(25), task(11), homework(7), assignment(4), events(4), conversations(3), dashboard/notifications, libraryactivity, holidays. Controllers cover posts/pages/homework/assignments/conversations/library activity — largely **read + limited social/task** mutators.

### Capability map vs gap list

| Capability claim | Toshi? | Panel? |
|---|---|---|
| `view_dashboard` | ❌ | ✅ |
| `view_assignments` | ❌ | ✅ |
| `view_homework` | ❌ | ✅ |
| `manage_tasks` | ❌ | ✅ |
| `view_events` | ❌ | ✅ |
| `view_notices` | ❌ | ✅ (noticeboard controller present) |
| `view_marks` | ❌ | ⚠️ confirm under student portal (often embedded in dashboard/show) |
| `view_attendance` | ❌ | ⚠️ same |
| `view_library_activity` | ❌ | ✅ libraryactivity |
| `view_class_wall` | ❌ | ✅ classwall |
| `manage_conversations` | ❌ | ✅ conversations |

**Gap:** 100% of Toshi advisory set; almost all claims are **view/self** — different blast-radius profile than Teacher/Admin operator tools.

### UI-absence flag

**Intentional** — same Blade allowlist. Additionally, `scope: self` in capabilities already signals a different product shape than school-operator Toshi.

---

## Cross-role summary (Part A complete)

| Role | ug | Pill | isAvailable (if mounted) | Advisory actions | Matching tools | UI-absence |
|---|---|---|---|---|---|---|
| Siteadmin | 1 | yes | platform path (separate branch) | 3 | platform tools on feature branch | n/a |
| School Admin | 3 | yes | school `toshi_enabled` | 16 | ~26 tools cover 16 | mounts |
| Teacher | 5 | **yes** (teacher/accountant/librarian branches) | school flag; `toshi-teacher-action` | 12 | 12 | resolved on teacher branch |
| Student | 6 | **no** | school flag; Gate denies; scope=`self` | 11 | 0 (admin tools ≠ self) | **intentional** |
| Librarian | **8** | **yes** (`feature/toshi-librarian-role`) | school flag; `toshi-librarian-action` | 6 | 6 | resolved on librarian branch |
| Receptionist | **10** | **yes** (`feature/toshi-receptionist-role`) | school flag; `toshi-receptionist-action` | 7 | 7 | resolved on receptionist branch |
| Accountant | 11 | **yes** (`feature/toshi-accountant-role`) | school flag; `toshi-accountant-action` | 6 | 6 | resolved on accountant branch |

---

## Product decision update (2026-08-01)

**Confirmed:** Toshi extends to **all users**, not admin-only. Reverse `[1, 3]` in a **graduated** sequence (Teacher first), not a blanket Blade flip.

### Backlog (advisory/panel mismatches)

- **Shipped (librarian Part B):** view-only `/library/cards` + ug8 `LibrarianOperationsAgent` route; advisory key renamed `manage_library_cards` → `view_library_cards`.
- **Follow-up — library card issue/return CRUD:** create/renew/deactivate/edit card fields as a scoped build (Approvable / Tier-2 confirm judgment later). Keep admin URL.
- **Receptionist `manage_email_record`**: **DROPPED** (Part B 2026-08-01) — not a follow-up; abandoned scaffold left as dead controller. Advisory rename `manage_noticeboard` → `view_noticeboard` **shipped**.
- **Converge ConfirmsBeforeWrite (Tier-2) + native Approvable into one confirmation mechanism** — deferred/tracked; both currently populate the same audit identity fields (`acting_user_id` + `approver_id`) via different paths.

### Teacher Part B (implemented on `feature/toshi-teacher-role`)

1. `TeacherOperationsAgent` — 12 tools matching advisory actions; `toshi-teacher-action` Gate; scope router ug5 → teacher agent.
2. Write tools use AgentToshi `ConfirmsBeforeWrite` (Tier-2 cards) — not platform native Approvable HTTP ops UI.
3. Blade allowlist widened to `[1, 3, 5]` after tools+tests green.
4. Isolation: `AddCoAdminTool` absent from `tools()`; `toshi-school-action` still denies ug5.

### Accountant Part B (implemented on `feature/toshi-accountant-role`)

1. `AccountantOperationsAgent` — 6 tools matching advisory actions; `toshi-accountant-action` Gate; scope router ug11 → accountant agent (ug5 → teacher retained).
2. `AccountantActionService` mirrors accountant fee/payroll/dashboard/task panel paths (not ug3 `ToshiActionService`).
3. Blade allowlist widened to `[1, 3, 5, 11]` after tools+tests green.
4. Isolation: `AddCoAdminTool` absent from `tools()`; `toshi-school-action` **and** `toshi-teacher-action` deny ug11; `toshi-accountant-action` allows.
5. Money tools (`record_payment`, `manage_payroll`) use Tier-2 ConfirmsBeforeWrite — **stricter bar than Teacher** (payroll/money blast radius).
6. Tier-2 confirm sets `acting_user_id` + `approver_id` (AgentToshi fix verified in accountant tests).

### Librarian Part B (implemented on `feature/toshi-librarian-role`)

1. View-only `GET /library/cards` (`library.cards`, MustBeLibrarian) via `LibraryCardLookupService` shared with admin `cardIndex`.
2. `LibrarianOperationsAgent` — 6 tools; Gate `toshi-librarian-action`; scope router ug8 → librarian (ug11/ug5 retained).
3. Capability rename: `manage_library_cards` → `view_library_cards`.
4. Writes (books, categories, lending, tasks) use Tier-2 ConfirmsBeforeWrite — **flagged** (panel mutators, not pure reads).
5. Blade allowlist widened to `[1, 3, 5, 8, 11]` after tools+tests green.
6. Isolation: `AddCoAdminTool` absent; school+teacher+accountant Gates deny ug8; librarian Gate allows; read-only audit asserts `approver_id` explicitly null.

### Receptionist Part B (implemented on `feature/toshi-receptionist-role`)

1. Capability hygiene: drop `manage_email_record`; rename `manage_noticeboard` → `view_noticeboard` (7 advisory actions).
2. `ReceptionistOperationsAgent` — 7 tools; Gate `toshi-receptionist-action`; scope router ug10 → receptionist (ug8/ug11/ug5 retained).
3. Writes (visitor/call/postal logs, tasks) use Tier-2 ConfirmsBeforeWrite; reads (dashboard/events/noticeboard) leave `approver_id` explicitly null.
4. Blade allowlist widened to `[1, 3, 5, 8, 10, 11]` after tools+tests green.
5. Isolation: `AddCoAdminTool` absent + count 7; school+teacher+accountant+librarian Gates deny ug10; receptionist Gate allows.

Student builds still deferred.

---

## Part A — Library cards access investigation (2026-08-01) — superseded by Part B above

> Branch: `feature/toshi-librarian-role` off `origin/main`  
> Scope: **docs / findings only** — no LibrarianOperationsAgent, Gates, tools, or Blade mounts.  
> Product decision (this session): extend **real** librarian-scoped HTTP access to card management (not a Toshi tool bypassing the HTTP boundary).

### What is a library card (schema)

Table `library_card` (migration `2020_04_10_105100_create_library_card_table.php`; live DB matches):

| Column | Type | Notes |
|---|---|---|
| `id` | int unsigned PK | |
| `school_id` | bigint unsigned FK → schools | tenant scope |
| `user_id` | int unsigned FK → users, nullable | borrower (student) |
| `library_card_no` | int, **unique** | physical/logical card number used at checkout |
| `book_limit` | int | concurrent borrow cap (factory default `5`) |
| `status` | boolean / tinyint(1), default `0` | active flag (Blade sometimes compares to string `'active'` — schema is boolean) |
| `expiry_date` | date | required in schema |
| timestamps | | |

**Not in schema:** fees, deposits, RFID, barcode, digital-vs-physical flag, fine balance.

Model `App\Models\LibraryCard` — fillable mirrors columns; `User::librarycard()` hasOne. Used as borrower identity on `books_lending.library_card_no`. Production create path found only in seeders (`UsersStudentTableSeeder` `firstOrCreate`); **no HTTP issue/revoke/edit UI** anywhere today.

### Current code map (routes, controller methods, model)

**Admin (ug3 `schooladmin` + `privilegeconditions`)** — `RouteServiceProvider::mapAdminRoutes()` → prefix `admin`, `routes/admin.php`:

```
// Library module — school admin book management   (comment @ routes/admin.php:903)
Route::prefix('library')->name('admin.library.')->group(...)
  GET  /admin/library/cards  → LibraryController@cardIndex  name: admin.library.cards
```

- Controller: `App\Http\Controllers\Admin\LibraryController` (commit `b4807e0`)
  - `cardIndex` — **read-only**: pick student (`usergroup_id=6`), show card + lending history
  - Also owns books CRUD + lend check-out/return (separate from librarian module)
- View: `resources/views/admin/library/cards/index.blade.php` (“View library cards and lending history per student”)
- Sidebar: admin menu links to `admin.library.books` only (cards reachable by URL / in-module nav if added later)

**Librarian (ug8 `MustBeLibrarian`)** — `mapLibrarianRoutes()` → prefix `library`, `routes/librarian.php`:

- Books, book categories, book lending, holidays, activity, tasks, dashboard
- Lending **looks up** `LibraryCard` by `library_card_no` + `school_id` in `Librarian\BookLendingController` — no card CRUD routes
- **No** `/library/cards` (or members/issue) registered
- Sidebar (`layouts/library/menu.blade.php`) links `/library/members` etc. — **dead URLs** vs `librarian.php` (separate UX debt)

**Capability claim:** `ToshiActionService::getRoleCapabilities(8)` includes `manage_library_cards` since `a98590f` (2026-07-12) — advisory overclaim vs panel.

### Why admin-only (deliberate / oversight + evidence)

| Claim | Verdict | Evidence |
|---|---|---|
| Admin library module (incl. cards **view**) under `/admin/library/*` | **Deliberate** | Commit `b4807e0` (2026-07-11): *“feat: admin library module…”* — “Add Admin LibraryController with book CRUD, check-out/check-in, and **card history views**”; route comment *“Library module — school admin book management”*; `knowledge.md` session: dead `/admin/library` sidebar → **Decision: BUILD** thin admin layer because school admins hit librarian middleware redirect. |
| Cards living **only** under admin, not `/library/*` | **Oversight** (vs librarian role + advisory) | `routes/librarian.php` never gained card routes (`git log -S library_card -- routes/librarian.php` empty). Librarian module always treated cards as lending lookup key only. |
| `manage_library_cards` in ug8 capabilities | **Aspirational overclaim** | Commit `a98590f` (2026-07-12, day after admin module): populated Librarian actions including `manage_library_cards` “despite having real routes” for other domains — cards had admin view only, no ug8 HTTP. No commit/comment saying “cards must stay ug3-only.” |

**Summary:** Putting a school-admin card **viewer** on admin routes was intentional. Keeping librarians **out** of card management was not a documented product lock — it is gap vs capability list + day-to-day library work.

### Recommended auth shape + tradeoffs

**Recommend Option A:** librarian-scoped `/library/cards*` under `routes/librarian.php` + `MustBeLibrarian`, reusing shared query/service (extract from `Admin\LibraryController@cardIndex` / future issue logic). Librarian Blade layout + menu link. Keep `/admin/library/cards` for ug3.

| Option | Pros | Cons |
|---|---|---|
| **A — `/library/cards` + MustBeLibrarian (recommended)** | Matches role prefix pattern (`mapLibrarianRoutes`); keeps HTTP boundary role-clean; Toshi later mirrors panel; Laravel route-group middleware stays single-role ([docs](https://laravel.com/docs/12.x/routing#route-group-middleware)) | Small duplication of view/controller method unless extracted to service |
| **B — duplicate librarian-specific controller** | Clear ug8 namespace (`Librarian\LibraryCardController`) | More code for currently thin read (+ later issue); drifts from admin view |
| **C — widen admin middleware to include MustBeLibrarian** | One URL | Mixes `schooladmin`+`privilegeconditions` with ug8; breaks impersonation/redirect mental model; librarians on `/admin/*` UI |

**Do not** implement card management as a Toshi-only path.

### Scope split: day-to-day vs admin-only config?

Current admin cards page is **100% day-to-day lookup** (student → card no / limit / expiry / lending history). There is **no** admin-only fee/deposit/RFID config surface — those fields do not exist.

| Day-to-day (ug8 should get) | Config / policy (ug3-only if ever built) |
|---|---|
| View card by student / card no | School-wide default `book_limit` / default loan days (not in schema today) |
| Issue / renew / deactivate card (when UI exists) | Fee/deposit rules (not in schema) |
| Adjust per-card `book_limit` / `expiry_date` / `status` | Cross-school card number policy |

No need to split the existing `cardIndex` surface — give librarians the same read (and later issue) under `/library/cards`.

### Size: fold into librarian branch vs defer cards?

- **View-only `/library/cards`** mirroring `cardIndex`: **small** (~1 route, thin controller method, 1 Blade under `library/`, menu link). Safe to fold into the librarian HTTP/Toshi branch as panel parity for `manage_library_cards` (read).
- **Issue / revoke / edit** card fields: **medium** — no production create UI today (seeder-only); needs validation (unique `library_card_no`, school scope), forms, tests. Prefer **follow-up**.
- **Toshi:** ship **5 confirmed panel-backed tools first** (`manage_books`, `manage_book_categories`, `manage_lending`, `view_dashboard`, `manage_tasks`). Add `manage_library_cards` tool only **after** ug8 HTTP exists (view minimum; issue when built). Do not claim the 6th tool against admin-only routes.

**Recommendation:** same librarian branch may include thin `/library/cards` view for advisory/panel honesty; defer issue CRUD + Toshi card tool to a cards follow-up PR if schedule is tight.

Historical findings: cards were admin-only view under `/admin/library/cards`; ug8 had no `/library/cards`; capability `manage_library_cards` was aspirational. **Approved Option A shipped in Part B** as view-only librarian cards + shared lookup service. Issue/return/create/update remains follow-up.

---

## Part A — Receptionist EmailRecord + capability hygiene (2026-08-01)

Branch: `feature/toshi-receptionist-role` (docs only at Part A — **no** `ReceptionistOperationsAgent`, Gates, tools, Blade mounts, or email routes until Part B).

Ground truth reconfirmed: ug10, ~75 `/receptionist/*` routes (`routes/receptionist.php` has 84 `Route::` lines; birthday/feed inflate dashboard domain), ~16 primary mutators on visitor/call/postal/task (+ birthday message POSTs), 8 advisory actions in `getRoleCapabilities(10)` before Part B hygiene.

### EmailRecord: intended purpose (schema + controller)

**No schema exists.** Local DB has `visitor_log`, `call_log`, `postal_record` — **zero** `email_record` / `email_records` table (`information_schema` query). No migration file ever committed under any name matching email records.

Controller-inferred purpose (cite `EmailRecordController@store`): a **front-desk email correspondence registry** parallel to postal record — not an SMTP client and not WhatsApp transport:

| Field written | Source |
|---|---|
| `school_id`, `academic_year_id` | Auth + `SiteHelper::getAcademicYear` |
| `type` | `$request->type` (inbound/outbound style, same pattern as postal `type`) |
| `subject` | `$request->subject` |
| `sender_email`, `receiver_email` | `$request->sender_email` / `receiver_email` |
| `date`, `time` | `$request->date` / `time` |
| `attachment` | optional upload to `{school_slug}/emailrecord/` |

Views referenced but **missing**: `/reception/emailrecord/{index,create,edit}`. Imports that **do not resolve**: `App\Models\EmailRecord`, `EmailRecordRequest`, `EmailRecord` Resource. Activity constants `LOGNAME_ADD_EMAIL_RECORD` / `LOGNAME_DELETE_EMAIL_RECORD` are **undefined** in `config/school-plus.php`; `update()` even logs `LOGNAME_EDIT_POSTAL_RECORD` (copy-paste from postal sibling).

Sibling postal completeness: `PostalRecord` model + `postal_record` migration (2020-07-28) + Request/Resource + views + 9 routes. Email has **controller only**.

### Why unwired (deliberate / abandoned / superseded / oversight + evidence)

**Verdict: abandoned mid-build** (incomplete GegoK12 scaffold), later **overclaimed** into Toshi advisory. Not a deliberate product cut; not a missed route wire-up of a finished feature.

| Evidence | Detail |
|---|---|
| First commit | `a6784c3` (2025-07-11) added `EmailRecordController.php` alone — never model/migration/views/routes in same or later commits |
| Routes | `routes/receptionist.php` from first commit through HEAD: visitor/call/postal blocks present; **no `emailrecord` lines ever** (`git log -S 'emailrecord' -- routes/receptionist.php` empty) |
| Git history of dependents | `git log --all -- app/Models/EmailRecord.php`, views, migrations: **empty** — artifacts never existed in repo |
| Nav | Reception menu has no Email Record item; no Vue `*email*record*` components |
| Capability intro | `a98590f` (2026-07-12) populated ug10 actions including `manage_email_record` by mirroring controller filename inventory — same day as school-id leak fixes (`7eecd78`) which **scoped the dead controller** without noticing it was unroutable |
| WhatsApp / messaging | **Not a documented supersession.** No commit removed email routes (none existed). `MessageDeliveryLog` / Evolution WhatsApp is outbound delivery plumbing, not a front-desk correspondence ledger. Postal (physical mail log) remains live — email ledger was the unfinished digital twin |

### Recommendation: build routes vs drop capability

**DROP `manage_email_record` from `getRoleCapabilities(10)`** (and leave controller as dead code / optional later cleanup — out of Part B scope). **Not a follow-up.**

Why not build routes now:
1. Finishing the feature requires model + migration + Request + Resource + views + Vue list/form + routes + LOGNAME constants + nav — full greenfield, not “wire three routes.”
2. Advisory overclaim correction matches library-cards Part A discipline (claim only what panel can do).
3. Operational parent messaging already lives in WhatsApp stack; resurrecting an email ledger adds Toshi surface with no live panel users.

### Other 7 actions: clean / surprises

| Advisory key | Routes? | Mutators work? | Boundary / surprises |
|---|---|---|---|
| `manage_visitor_log` | ✅ 11 routes under `/receptionist/visitorlog` | ✅ store/update/destroy | Clean for ug10. Also on **admin** + **teacher** prefixes (shared domain, role-scoped controllers) — not admin-only leakage into receptionist. |
| `manage_call_log` | ✅ 9 routes | ✅ CRUD | Same shared admin/teacher pattern. Clean. |
| `manage_postal_record` | ✅ 9 routes | ✅ CRUD | Same. Clean. |
| `view_dashboard` | ✅ `/dashboard` + widgets | N/A (read + birthday POSTs) | Clean. Birthday/work-anniversary message POSTs exist but are **not** in advisory set (out of Part B unless expanded). |
| `view_events` | ✅ 4 GET routes | Read-only (correct for `view_*`) | Clean. |
| `manage_noticeboard` | ✅ `/notices` + `/notice/show/list` only | **No** store/update/destroy on receptionist | **Surprise (rename):** controller is list+index only. Admin owns notice CRUD. Orphan blades `reception/noticeboard/{create,edit}` link to `/admin/notices` and are unrouted. Same pattern as library `manage_library_cards` → `view_*`. |
| `manage_tasks` | ✅ full task CRUD (+ snooze/status) | ✅ | Clean. |

**Extra hygiene (not advisory keys, flag only):** `layouts/reception/menu.blade.php` links to `reception/{students,parents,visitors,appointments,messages,calls}` — wrong prefix (`reception` vs `receptionist`) and several paths have **no** matching routes (students/parents/appointments/messages). Dashboard KPIs also use `/reception/events`. Panel CRUD views correctly use `/receptionist/...`. Menu drift is separate from Toshi Part B but explains why “Messages” looks like a WhatsApp stand-in without being wired.

### Proposed Part B tool set (exact list)

**7 tools** after dropping the 8th and renaming noticeboard:

1. `ManageVisitorLogTool` ← `manage_visitor_log` (Tier-2 write)
2. `ManageCallLogTool` ← `manage_call_log` (Tier-2 write)
3. `ManagePostalRecordTool` ← `manage_postal_record` (Tier-2 write)
4. `ViewDashboardTool` ← `view_dashboard` (read)
5. `ViewEventsTool` ← `view_events` (read)
6. `ViewNoticeboardTool` ← **`view_noticeboard`** (rename from `manage_noticeboard`; read-only)
7. `CreateTaskTool` ← `manage_tasks` (Tier-2 write; match librarian naming)

**Drop / do not implement:** `manage_email_record` / any `ManageEmailRecordTool`.

### Receptionist Part B status — **shipped** on `feature/toshi-receptionist-role`

`ReceptionistOperationsAgent` + `toshi-receptionist-action` Gate + scope router ug10 + Blade `[1, 3, 5, 8, 10, 11]` + isolation/audit tests green. Email capability **dropped** (not follow-up); noticeboard renamed and shipped.
