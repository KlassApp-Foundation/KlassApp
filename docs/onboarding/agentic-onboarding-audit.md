# Agentic onboarding audit: what exists, what conflicts, and what to build

Scope: everything in the KlassApp repository related to onboarding a school, so the work of turning
a filled onboarding checklist workbook into a school can be planned as an agent.

This audit is against `main` at `c9e7b369` (24 November 2026) and against the open pull requests and
branches that exist at that date. It is not a snapshot of 9 November: `#1285` merged on 9 November
(`2aaf327b`), so a large part of what the brief lists as "in review" is now `main`.

## How to read the evidence

Every claim below carries one of these marks:

| Mark | Meaning |
| --- | --- |
| **[code]** | I read the file at the stated ref (`origin/main`, or the stated branch). Line numbers are from that ref. |
| **[git]** | From `git log`, `git diff`, or GitHub metadata (`gh pr view`). |
| **[claim]** | A pull request body or commit message says so. I did not re-run it. |

**No claim in this document is "works", and none is "tests pass".** I did not click through the
product, did not query a database, and did not run the test suite. Where a PR body reports tests or
a click-through, it is marked **[claim]** and attributed. The three words the brief reserves —
"exists in code", "tests pass", "works" — are used with exactly that meaning.

Two consequences worth stating up front:

1. A file existing and being wired are different claims. I checked the wiring (route → component →
   service → model) where I make a wiring claim.
2. Where two pieces of code disagree about the same job, I report the disagreement rather than
   picking a winner by reading order.

---

## 1. What exists, wherever it lives

### 1.1 The setup wizard and "Finish setup"

| Piece | Location | What it does | State | Who |
| --- | --- | --- | --- | --- |
| Wizard route | `routes/admin.php:12-14` | `/admin/onboarding/wizard` renders the Blade shell | built | Mucunguzi Moses |
| Wizard shell | `resources/views/admin/onboarding/wizard.blade.php:8` | Hides Toshi entirely (`data-toshi-manual-wizard`) and mounts the Livewire component | built | Mucunguzi Moses |
| Wizard component | `app/Livewire/ManualOnboardingWizard.php` (2.3k lines) | 16 steps, inline add/paste/upload for teachers, students, class teachers; review step; edit-section; structure preview/confirm; class-teacher invites | built, unverified | Mucunguzi Moses (65 commits), Claude, Cursor Agent **[git]** |
| Canonical step list | `app/Services/OnboardingStepsService.php:51-118` | `ALL_STEPS`: school_name, student_size, country, curriculum, school_category, emis, uneb_center, academic_year, standards, subjects, teachers, students, terms, fees, whatsapp_verify, plan_selection | built | Mucunguzi Moses |
| Step applicability | `OnboardingStepsService.php:229-243` | Drops `emis` when the country has no ministry-code requirement; drops `uneb_center` and `school_category` when the curriculum is not UNEB | built | Mucunguzi Moses |
| Write engine | `app/Services/OnboardingEngine.php` (2,766 lines) | One school-scoped method per step (`saveTerms`, `saveStandards`, `saveSubjects`, `saveFees` at `:1504`, `saveTeachers`, `saveStudents`, …) | built | Mucunguzi Moses (40 commits) **[git]** |
| Upload pipeline | `app/Services/Onboarding/OnboardingUploadService.php` | Three kinds (`:32-36`): teachers, class_teachers, students. Chunked (100 students / 200 others), hash-deduped, per-row skips with row numbers, "missing streams" retry, resumable | built | Mucunguzi Moses |
| Completion stamp | `app/Services/FinishSchoolOnboarding.php:30-45` | Sets `schools.onboarding_finished_at` once, dispatches `SchoolOnboardingFinished`; called by the wizard's `confirmReview()` (`ManualOnboardingWizard.php:1406`) and by Toshi's finish | built | Mucunguzi Moses |
| "Finish setup" chip | `resources/views/layouts/partials/dashboard-v2-chip.blade.php:44-58` | Sidebar chip: done/total, next step, progress bar, links to `OnboardingEngine::setupDestination()` | built | Mucunguzi Moses |
| Resume logic | `OnboardingStepsService::nextIncompleteStep()` (`:392`), `setupDestination()` | Returns the earliest incomplete step; `main` commit `6bb0a3f3` sends unfinished admins to it after login | built | Mucunguzi Moses / #1245 |

The wizard is the only onboarding path that a real school can use today without a flag: the route is
plain `web` + auth, and every write goes through `OnboardingEngine`.

Known open issue: **#793** (open, 21 Sep) — "On the onboarding flow, we're trying to invite teachers
before they're uploaded, I think this can come after uploading teachers and students". It matches
the order in `ManualOnboardingWizard` (structure step offers class-teacher invites before the teacher
and student uploads). **[git]**

### 1.2 Toshi's legacy scripted onboarding (v1)

| Piece | Location | What it does | State | Who |
| --- | --- | --- | --- | --- |
| Panel component | `app/Livewire/AgentToshi.php` (7,379 lines) | The whole scripted setup conversation plus assistant mode | built, unverified | Mucunguzi Moses (153 commits) **[git]** |
| Scripted step list | `AgentToshi.php:218-238` | **19** steps: school_info, student_size, country, emis, uneb_center, admin_account, co_admin_invite, academic_year, standards, subjects, teachers, students, terms, fees, **exams**, whatsapp_verify, **school_pay**, plan_selection, review | built | Mucunguzi Moses |
| Mandatory subset | `AgentToshi.php:244-254` | 9 steps; exams, fees, school_pay, whatsapp_verify are optional | built | Mucunguzi Moses |
| In-chat forms | `AgentToshi.php:171-204` (`$showTeacherForm`, `$showStudentForm`, `$showSubjectForm`, `$showFeeForm`, `$showExamForm`) rendered at `resources/views/livewire/agent-toshi.blade.php:170, 210, 288, 405, 482` | Real in-panel forms for subjects, teachers, students, fees, exams | built | Mucunguzi Moses |
| Writes | 22 call sites of `ToshiActionService::` in `AgentToshi.php` | Each write is a static service call, not a tool | built | Mucunguzi Moses |
| Mode gate | `app/Services/Toshi/ToshiUiSwitch.php` | Three modes per school: **preview** (default), **onboarding** (scripted), **assistant** (model). New schools default to preview (`config/toshi.php:60`) | built | Mucunguzi Moses |
| Real-school gate | `app/Services/Toshi/ToshiOnboardingPath.php:22-58` + `app/Http/Middleware/SendRealSchoolToshiOnboardingToWizard.php:29-41`, applied in `app/Providers/RouteServiceProvider.php:122` and `MustBePrivilege.php:23` | A real school (`is_test` not 1) in onboarding mode that still has unfinished steps is redirected to the wizard, and its `toshi_mode` is rewritten to **preview**. Only `is_test = 1` keeps the chat path | built, **deliberate block** | Mucunguzi Moses |

Two facts matter for the target design:

- The legacy flow still contains **exams** and **school_pay**, which `docs/onboarding/playbook.md`
  says are *not* part of onboarding (`playbook.md:7`). A school in `onboarding` mode can therefore
  be walked into both, while the wizard cannot. **[code]**
- For a real new school the panel is in **preview** mode, so the scripted flow does not run at all
  until `schools.toshi_mode` is changed. **[code]**
- Even if someone sets `toshi_mode = onboarding`, `ToshiOnboardingPath` sends a real school to the
  wizard and flips the mode back to preview. The class docblock states the reason: "Real schools use
  the Wizard until Toshi onboarding matches it" (`ToshiOnboardingPath.php:9-18`). **Toshi chat
  onboarding is switched off for real schools by design, not by accident.** **[code]**

### 1.3 Toshi v2: the StepRegistry chat loop

| Piece | Location | What it does | State | Who |
| --- | --- | --- | --- | --- |
| Driver | `app/Onboarding/Steps/ToshiOnboardingV2Driver.php` (373 lines) | Chat loop over `StepRegistry`: `promptNext()` / `handleReply()`; returns question, options, input type | built, **off by default** | Mucunguzi Moses |
| Flag | `config/toshi.php:46` | `toshi.onboarding_v2` = `TOSHI_ONBOARDING_V2`, default **false** | — | — |
| Step schemas | `app/Onboarding/Steps/StepSchemaCatalog.php`, `StepSchema.php`, `StepField.php` | Declarative fields, options, validation rules and a `confirm` flag per step | built, **not used by any UI** (only by the step classes and tests) | Mucunguzi Moses |
| Playbook lock | `tests/Feature/Onboarding/Steps/PlaybookSyncTest.php` | Keeps `docs/onboarding/playbook.md` codes in sync with the step classes | built, not run by me | Mucunguzi Moses |
| Parity harness | `tests/Support/Onboarding/OnboardingParityHarness.php` | Compares wizard and v2 driver outcomes | built, not run by me | Mucunguzi Moses |

Limits of v2 as written:

- Buttons are **option lists**, not forms. `input_type` is text; there is no multi-field form.
- The only confirm card is the classes "apply suggested structure → Confirm" path
  (`ToshiOnboardingV2Driver.php:33-150`). `docs/onboarding/playbook.md:11` says so explicitly:
  "The confirmation text is the wording for a confirm card. That card is not in the setup driver
  yet." **[code]**
- `StepSchemaCatalog` already carries a `confirm` boolean per step, but no renderer consumes it.

### 1.4 The checklist-driven prototype: PR #1307

This is the piece closest to the target, and it is not on `main`.

| Piece | Location | What it does | State | Who |
| --- | --- | --- | --- | --- |
| PR | **#1307** `poc/toshi-checklist-onboarding` → `main`, draft | "T-131: checklist onboarding in the Toshi panel (demo only)". **+12,207 / −99 in 123 files** **[git]** | prototype, demo-gated, unmerged | Mucunguzi Moses (commits 9–11 Nov) **[git]** |
| Panel flow | `app/Livewire/ChecklistOnboarding.php` (1,432 lines) | Upload card, summary/flags card, per-step preview and result cards, completion card | prototype | Mucunguzi Moses |
| Orchestration | `app/Services/Onboarding/ChecklistOnboardingService.php` (2,573 lines) | `ingest`, `previewCard`, `apply`, `skipStep`, `skipFlags`, `abandon`, `teacherInvitesCard`, `sendTeacherInvites`, `whatsappCard`, `completionCard` | prototype | Mucunguzi Moses |
| Conversation | `ChecklistConversation.php` (479 lines), `ChecklistNarrator.php` (115), `ChecklistAssistantPrompts.php` (208) | Turns service cards into chat messages/buttons | prototype | Mucunguzi Moses |
| Gate | `ChecklistOnboardingGate.php` (25 lines) | Enabled only when `toshi.checklist_onboarding_poc` is on **and** the school is `is_demo` or `is_test` | prototype | Mucunguzi Moses |
| Invite hold | `ChecklistInviteHold.php` (27 lines) | Issues invite rows but suppresses email/WhatsApp during apply | prototype | Mucunguzi Moses |
| Plan store | `app/Models/OnboardingPlan.php` (42 lines) | Draft school-scoped plan (encrypted payload) | prototype | Mucunguzi Moses |
| Converter | `ChecklistWorkbookConverter.php` (**1,019 lines**) | Workbook → importer CSVs + `no-importer/` + `pending/` + `report.md`, flags with tab and row | prototype | Mucunguzi Moses |
| Tests | `tests/Feature/Onboarding/ChecklistOnboardingPocTest.php` (1,629 lines), `ChecklistConvertTest.php` (263) | PR body: 16 tests, 192 assertions, passed **[claim]** | unverified by me | Mucunguzi Moses |

What the flow does, read from the code:

1. **Upload** lives in the Toshi panel ("Onboard from checklist" button).
2. **Ingest** converts the workbook, stores an encrypted, school-scoped `OnboardingPlan`, and
   returns a summary card. **[code]**
3. **DPA gate**: the converter returns `blocked` when the Confirmations tab's data-processing row is
   not `Yes`, with the tab and row in the message (`ChecklistWorkbookConverter.php:200-222` on the
   #1291 branch; the same method is in #1307). The card is a refusal, and nothing is stored.
4. **Flags**: rows that do not fit are listed with tab and row; two buttons, "Skip these rows" and
   "Stop, I'll fix the workbook". **[claim, PR body]** — the button handlers exist
   (`ChecklistOnboarding.php`, `skipFlags`/`abandon` in the service).
5. **Five walk steps** (`ChecklistOnboardingService.php:42`):
   `terms → standards → subjects → teachers → students`.
   Each preview card shows create / already exists / conflict, then **Confirm step** or **Skip step**.
   Confirm writes through `OnboardingEngine::saveTerms/saveStandards/saveSubjects/…` in one
   transaction (`writeStep()`, `:962`).
6. **Teacher invites** can be sent from a card (`sendTeacherInvites()`, `:1201`): it issues or
   reissues a `TeacherInvite` and sends `TeacherInviteLinkMail` with a real `Mail::to`.
7. **Completion card** (`completionCard()`, `:1458`) checks learners per class/stream against the
   workbook, KLS numbers present, and phones in E.164; it reports "Invites ready: N".

Stated cuts, from the PR body and the code:

- **Class teachers are listed, not written.** `OnboardingEngine` has no class-teacher write, so the
  homeroom step is the first scope cut. **[claim]** — confirmed: no such method in
  `OnboardingEngine.php` **[code]**.
- **No fees step.** `WALK` has five keys and `fees` is not one of them. **[code]**
- **No WhatsApp sends** in the completion path; teacher invites are email only. **[claim]**
- **Demo/test schools only** (`ChecklistOnboardingGate`), flag off by default elsewhere.

### 1.5 The importers

| Import | Location | Trigger | What it takes / does | State | Who |
| --- | --- | --- | --- | --- | --- |
| Students (+ parents) | `app/Imports/UsersImport.php` | `/admin/import` → `ImportMemberController@import` (`routes/admin.php:513-514`) | `WithHeadingRow`; requires `firstname` + `class`; creates student and parent, links them; per-row failures collected; plan limit; phone normalised by country | legacy, built | Elijah-ug (8 commits in `app/Imports`) **[git]** |
| Teachers | `app/Imports/TeachersImport.php` | `/import/teacher`, `/importTeachers` (`routes/admin.php:405-406`) | Requires `firstname`; country, qualification, plan limit | legacy, built | Elijah-ug / Mucunguzi Moses |
| Parents (standalone) | `app/Imports/ParentsImport.php` | `/import/parents`, `/importParents` (`routes/school-imports.php:8-9`) | Requires `firstname` + `mobile_no`; creates parent only, **no child link**; duplicate check on phone/email | built, added by **#1250** (merged 9 Nov) | Mucunguzi Moses **[git]** |
| Teacher links (subject/class assignment) | `app/Http/Controllers/Admin/TeacherLinkImportController.php:41-210` | `/teacher-links/import` (`routes/admin.php:43`) | CSV/XLSX parse (`:226`), matches teacher by name/phone, `Teacherlink::firstOrCreate` (`:171`) | legacy, built | Elijah-ug |
| Class teachers | **No admin importer.** Only `ManualOnboardingWizard::importClassTeacherUpload()` (`:3033`) → `resolveClassTeacherLink()` | Wizard upload only | Per-row skips (missing name, bad email, invalid phone, unknown class); writes the class-teacher link | built, wizard-only | Mucunguzi Moses |
| Exams | **No exam importer.** Marks import only: `MarksImportController` (`routes/admin.php:913-918`), `MarksSheetReader` | Per exam | Imports marks for an existing exam — not exam creation | built | Mucunguzi Moses |
| Fees as payments | **No importer on `main`.** Fee *structure* is entered in the wizard (`FeesStep`, `OnboardingEngine::saveFees()` at `:1504`) | Wizard | Categories/amounts per class; no payment rows | built | Mucunguzi Moses |
| Term workbook (marks) | `app/Services/Marks/TermWorkbookImporter.php` (916 lines) on unmerged `feat/term-workbook-importer` (12 Nov) | Not wired on `main` | Imports a term workbook as drafts, does not submit marks | unmerged | Mucunguzi Moses |

Two duplications inside this table:

- `UsersImport` creates a parent per student row, while `ParentsImport` creates parents separately.
  Two different notions of "import a parent", with different required fields and different duplicate
  rules. **[code]**
- Student import exists twice: the legacy `UsersImport` (admin screen) and the wizard's
  `OnboardingUploadService` + `runRosterImport()` (`ManualOnboardingWizard.php:732`), which is
  chunked, idempotent by file hash, and reports per-row skips. They do not share code. **[code]**

### 1.6 The four commands and the runbook

| PR | Branch | Files | What it does | State | Author |
| --- | --- | --- | --- | --- | --- |
| **#1286** | `t077-phones-to-e164` | `PhonesToE164Command.php` (206), `PhoneE164Converter.php` (89), 236 lines of tests | `phones:to-e164`, **dry run by default**, reports what it would change | draft, 1 commit, **345 behind main** | Claude **[git]** |
| **#1287** | `t105-names-normalize` | `NamesNormalizeCommand.php` (116), `PersonNameNormalizer.php` (125), 210 lines of tests | `names:normalize`, dry run by default | draft, 1 commit, 345 behind | Claude |
| **#1291** | `t079-checklist-convert` (base `t077`) | `ChecklistConvertCommand.php` (91), `ChecklistWorkbookConverter.php` (817), 216 lines of tests | `checklist:convert`: workbook → students/teachers/teacher-links/class-teachers CSVs, `no-importer/`, `pending/`, `report.md`; exits 1 and writes nothing when the DPA row is not Yes or "All Learners" is empty | draft, stacked on #1286, 3 commits, 345 behind | Claude |
| **#1296** | `t089-onboarding-runbook` | `docs/onboarding/school-onboarding-runbook.md` (120) | The human procedure for the same job: gates, export, convert, fix flagged rows, backup, import order, invites, checks | draft, 1 commit, 345 behind | Claude |
| **#1293** | `t094-parent-student-access` | `docs/product/parent-student-access.md` (121) | Design for safe first sign-in of imported parents/students | draft, 1 commit, 345 behind, **superseded in part by code already on main** | Claude |

The converter's contract is worth quoting because it is the de facto input spec:

- Every data tab: row 1 header, row 2 a grey example row (skipped), data from row 3; a header ending
  ` *` is required; rows with an empty first column are skipped
  (`ChecklistWorkbookConverter.php:16-21`).
- Output files and headers are fixed at `:33-38` (`students.csv`, `teachers.csv`,
  `teacher-links.csv`, `class-teachers.csv`).
- Canonical class names are hardcoded at `:23-27`: Baby/Middle/Top Class, Primary One–Seven,
  Senior One–Six.

**#1291 is contained in #1307** (`git merge-base --is-ancestor origin/t079-checklist-convert
origin/poc/toshi-checklist-onboarding` is true), and #1307's converter is the larger superset
(1,019 vs 817 lines). #1286 is also contained in #1307. #1287 is not. **[git]**

### 1.7 Invite links and the invite services

| Service | Token / lifetime | Channel | Used by | State |
| --- | --- | --- | --- | --- |
| `TeacherInviteLinkService` | 64 random chars, stored as SHA-256 (`:22`, `:58-64`), single use, expiry `config/invites.php:19` (72 h) | Email; WhatsApp only when `staff_whatsapp_enabled` (`config/invites.php:24-29`, default **false** because Meta has not approved the template) | Toshi forms, teacher admin screens, #1307 | built |
| `ClassTeacherInviteService` | same pattern | Email | Class setup page (`routes/admin.php:27-31`) | built |
| `CoAdminInviteLinkService` | same pattern | Email | Co-admin step | built |
| `SchoolInviteService` | 7 days (`school_expiry_hours`, 168) | Email | Early-adopter school invites | built |
| `StudentParentAccountInviteService` | Reuses `TeacherInvite` rows; `/invite/account/{token}` (`routes/web.php:261-264`) | Email (only when the address is not a generated `.local` one: `emailIsUsable()`, `:44`) | Profile screens "resend setup" | built; **already in `main`** (commit `ccd56ead`, 5 Nov, via #1285) |

First sign-in today, read from code:

- Imported people get `Str::random(16)` and `is_reset = 1` (`app/Support/UserProvisioning.php`), and
  a generated `@<school-slug>.local` email when the row has none
  (`OnboardingEngine.php:2638`). **[code]**
- The only self-service recovery is an email code (`ForgotPasswordController`,
  `PasswordResetCodeController`). No phone/WhatsApp reset. **[code]**
- `StudentParentAccountInviteService` is the safe path that shipped, but nothing in the onboarding
  flows calls it automatically; an admin must press "resend setup" on a profile. **[code]**

### 1.8 Toshi's authorization gate and confirm-before-write

**Authorization**

- `ToshiUiSwitch` decides *whether the panel exists at all* for a user: preview / onboarding /
  assistant, per school, with public-demo users forced to preview
  (`app/Services/Toshi/ToshiUiSwitch.php:20-59`). **[code]**
- `ToshiActionService::getRoleCapabilities($usergroup_id)` returns `actions` and `scope`; the panel
  returns early when there are no actions or `scope === 'none'`
  (`AgentToshi.php:402-406`). **[code]**
- Per-tool tenant scoping is inside each tool/service call, not in the gate; the comment at
  `AgentToshi.php:3398` says the capability scope is advisory UI context, not the real check.
  **[code]**
- The real-school redirect is enforced in middleware on the authenticated admin group
  (`RouteServiceProvider.php:122`) and again inside `MustBePrivilege` (`:23`), so a deep link to a
  Toshi screen does not bypass it. **[code]**
- Tool-level authorization: `AuthorizesToshiAction` (`app/AiAgents/Concerns/AuthorizesToshiAction.php`)
  checks `Gate::inspect('toshi-school-action')` plus an explicit effective-school scope
  (`assertEffectiveSchoolScope`), with a deputy-admin variant. **[code]**

**Confirm-before-write**

- `ConfirmsBeforeWrite` trait → `ToshiActionService::confirmationPayload()`
  (`app/AiAgents/Concerns/ConfirmsBeforeWrite.php`, `ToshiActionService.php:52-70`).
- The gate is a **process-wide static**: `ToshiActionService::$bypassConfirm` (`:40`). `confirmYes()`
  sets it `true`, calls the tool, and resets it in a `finally` (`AgentToshi.php:1803`, `:1837`).
  **[code]**
- The pending confirmation is Livewire state plus a session key (`AgentToshi.php:1623`
  `toshi_pending_confirm_<messageId>`); WhatsApp has a table instead
  (`whatsapp_pending_confirmations`, migration `2026_08_01_194203`). **[code]**
- There is **no immutable audit row** for a panel confirmation: who confirmed what, and when, lives
  in the session and disappears with it. The MCP path does have an auditor
  (`ToshiMcpCallAuditor`), the panel path does not. **[code]**

### 1.9 MCP servers and tools

- **Remote connectors are real**: `config/toshi.php:274-330` defines Slack, Notion and Google Drive
  MCP endpoints with OAuth client ids/secrets. Write gates at `:354-364` are per-connector
  (`deny` default, `classify`, `allowlist`) with `master_switch` default **false**. **[code]**
- Implementation: `app/Services/Toshi/ToshiMcpClient.php`, `McpWriteGate.php`,
  `ToshiMcpCallAuditor.php`, `AuditingMcpClient.php`, `ApprovableMcpTool` (pending approval →
  `PendingMcpApprovalException` → panel confirm card). **[code]**
- `app/Mcp/` contains **only a spike**: `SpikeSlackMockServer` plus three Slack spike tools. It is
  not a product MCP server and nothing in onboarding uses it. **[code]**
- `upgrade-prep/ai-mcp` (16 Nov) is a framework migration (Laravel AI 1.4 / MCP 1.2), not an
  onboarding feature. **[git]**

None of this is wired into onboarding. For an onboarding agent, MCP is infrastructure that exists
but has no onboarding tools behind it.

### 1.10 Onboarding specs in `docs/`

| Doc | Size / last touch | What it says | Is it true of the code? |
| --- | --- | --- | --- |
| `docs/onboarding/playbook.md` | 553 lines, 4 Nov | The setup script for admin and supporter: every step, question, defaults, confirmation text, summary line, every error code with cause and fix. Exams and SchoolPay are out. | **Yes** — it is the best spec in the repo, and `PlaybookSyncTest` locks codes to step classes. One exception: it says the confirm card "is not in the setup driver yet" (`:11`), which is still true. |
| `docs/onboarding-engine-plan.md` | 440 lines, 26 Oct | Design for `OnboardingEngine` as the single write layer. Header says "design-only… No implementation code has been written." | **Stale header** — the engine exists (2,766 lines) and both the wizard and #1307 write through it. |
| `docs/onboarding-defaults-skill.md` | 91 lines, 25 Aug | Prefill real defaults instead of blank forms. Header says "not yet implemented in any UI." | **Partly stale** — `SchoolTypePreset` and `CountryProfiles` prefill structure/subjects/terms, in both wizard and Toshi. |
| `docs/wizard-bulk-upload/`, `docs/wizard-review-preview/` | screenshots + notes | Wizard upload and review evidence | Historical. |
| `docs/onboarding/school-checklist-guide.md` (**only on `docs/onboarding-school-checklist`**, 10 Nov) | 1 file, ~210 lines | How a school fills the workbook: tab order, yellow cells, required `*` headers, per-tab columns, formats, common mistakes | **This is the input contract**, and it is not on `main`. It also says School Details and Confirmations start on **row 2**, while the converter's docblock assumes a grey example row 2 for every data tab (`ChecklistWorkbookConverter.php:16-21`). The converter's form/`checkConfirmations()` readers tolerate it, but the two documents disagree and should be reconciled before either ships. |
| `docs/onboarding/marks-workbook-guide.md` (**only on `docs/onboarding-marks-workbook`**, 10 Nov) | 1 file | The marks workbook layout (KLS, name, three blocks) | Adjacent to onboarding; not the setup input. |
| `docs-site/help/setup-and-people/*` (**only on `docs/onboarding-attendance-guides`**, 16 Nov) | 9 files | User-facing help: onboarding steps, invite a class teacher, parent and student setup links, plan limits | Useful wording to reuse; unmerged. |
| `docs/onboarding/school-onboarding-runbook.md` (only on #1296) | 120 lines, 8 Nov | The human runbook for a filled workbook | Accurate about `main` at 8 Nov; **wrong now** on one point: it says T-094 first sign-in is "not built", but `StudentParentAccountInviteService` is in `main` via #1285. |

### 1.11 TODOs and issues that mention onboarding

- **No `TODO`/`FIXME`/`HACK`** in `app/Onboarding/**`, `OnboardingEngine.php`,
  `ManualOnboardingWizard.php` or `AgentToshi.php` (grep, 0 hits). The debt is structural, not
  annotated. **[code]**
- Open issues: **#793** (wizard invites teachers before upload — see 1.1), #1090 (builder status log,
  not onboarding), #629 (nav, unrelated). **[git]**
- PR **#1183** "Record the wizard and Toshi v2 onboarding pass" is a `knowledge.md`-only draft, 413
  commits behind, open since 5 Nov. It records a pass that has since been overtaken. **[git]**

### 1.12 What the merge state actually is

- **#1285 merged on 2026-11-09** (`2aaf327b`), combining **39 PRs**. It carried, among others,
  #1248, #1250, #1251, #1252, #1256, #1246, #1260 and #1275. **[git]**
- Of **125** open PRs, **47** have a head commit that is already an ancestor of `main`. They are
  mergeable only as no-ops and should be closed, not rebased. **[git]**
- The six onboarding PRs (#1286, #1287, #1291, #1293, #1296, #1183) are **345 commits behind**
  `main` (413 for #1183). **[git]**
- Bariyo123's 19 open PRs: #1250, #1252, #1248, #1256, #1251, #1246 are already in `main` via
  #1285; #1258, #1259, #1249, #1141, #1147, #1155, #1161, #1133, #1137–#1140, #1149 are not, and
  none of them touch the onboarding write path (they touch seeds, signup throttling, country codes,
  test speed, WhatsApp verify token, design brief, Google connectors, docs-site governance).
  **[git]**

---

## 2. Overlaps and conflicts

### 2.1 Two pieces doing the same job

| Job | Piece A | Piece B | What to do |
| --- | --- | --- | --- |
| Read the checklist workbook | #1291 `ChecklistWorkbookConverter` (817 lines) | #1307 `ChecklistWorkbookConverter` (1,019 lines) | **#1307 is a superset and contains #1291's commits.** Close #1291 as superseded; keep one converter. |
| Define the onboarding steps | `OnboardingStepsService::ALL_STEPS` (16) | `AgentToshi::$steps` (19) | Merge into one list. The 19-step list is the only place exams and SchoolPay appear; the playbook says they must not. |
| Same, again | `StepRegistry::STEP_CLASSES` (16) | #1307 `WALK` (5) | The 5 are the subset the workbook can fill; that is legitimate, but it must be declared as a projection of the 16, not a fourth list. |
| Write class teachers | Wizard `importClassTeacherUpload()` (the only writer) | #1307 lists them and does not write; `OnboardingEngine` has no method | Add `saveClassTeachers()` to the engine and delete the wizard's inline path. |
| Create parents | `UsersImport` (one per student row) | `ParentsImport` (standalone, no child link) | Decide: parents exist only through a child (then delete `ParentsImport`) or as people (then link them separately). |
| Student import | `UsersImport` (legacy screen) | `OnboardingUploadService` + `runRosterImport()` (wizard) | Keep the chunked wizard pipeline; re-point the admin screen at it or mark it legacy. |
| First sign-in for imported people | #1293 design doc | `StudentParentAccountInviteService` (already in `main`) | Close #1293 as implemented-in-part, or rewrite it as the design of record for the remaining WhatsApp/hand-over channels. |
| Register the "finish" event | `FinishSchoolOnboarding` | Wizard's own `finished` flag + Livewire event | Already rationalised in `FinishSchoolOnboarding`'s docblock; no action. |

### 2.2 Conflicts with each other and with `#1285`

- There is no live conflict *with* #1285: it is merged. The conflict is with **`main`**, and it is
  large. All six onboarding PRs touch files that #1285 rewrote (`ManualOnboardingWizard.php`,
  `AgentToshi.php`, `routes/admin.php`, the dashboard Blade, `knowledge.md`) and are 345 commits
  behind. A plain `git merge` will conflict; they need rebasing onto `main` one at a time.
- #1291 is stacked on #1286 (`baseRefName = t077-phones-to-e164`), so it cannot merge before #1286.
  #1307 has already merged both, which is why it is the cheaper base.
- #1296 (runbook) and #1293 (design) are docs-only and do not conflict textually, but #1296 now
  contradicts `main` on first sign-in, and #1293 is out of date relative to #1275.

### 2.3 Safe to retire

| Item | Why |
| --- | --- |
| #1291 | Contained in #1307; its converter is the smaller copy of the same class. |
| #1293 | Its "nothing exists" premise is false on `main`; the service it proposes exists. Rewrite or close. |
| #1183 | A `knowledge.md` session log for a pass that has been overtaken by 413 commits of work. |
| 47 open PRs whose heads are in `main` | No-op merges. Close with a note; do not rebase. |
| `app/Mcp/Tools/SpikeSlack*`, `SpikeSlackMockServer` | Spike, superseded by the real `app/Services/Toshi` MCP client. |
| `ParentsImport` (or `UsersImport`'s parent creation) | One of the two must go; keeping both keeps two definitions of a parent. |

Not safe to retire: #1286 (phones) and #1287 (names). Both are prerequisites for clean imports, and
#1287 is not contained in #1307.

---

## 3. Gaps against the target

The target is: a school fills the onboarding checklist workbook; an agent reads it, validates it,
asks for what is missing through buttons and forms, creates the school, sends invites, runs
post-import checks, never writes without confirmation, does no exams or SchoolPay, and stops if the
DPA is not confirmed.

| # | Target capability | Status | Evidence, and what is missing |
| --- | --- | --- | --- |
| 0 | The agent can run for a real school at all | **Missing** | Two independent gates: `ToshiOnboardingPath` redirects real schools to the wizard and sets the mode to preview (`:22-58`); `ChecklistOnboardingGate` allows the checklist flow only for `is_demo`/`is_test` schools. Today an onboarding agent exists only for test and demo schools. |
| 1 | Reads the workbook | **Partial** | Converter exists twice (#1291, #1307) and is not on `main`. Reads `.xlsx` via PhpSpreadsheet (`ChecklistWorkbookConverter.php:55-70`), expects a fixed tab layout and a grey example row 2. Nothing in `main` reads a workbook. |
| 2 | Validates and reports problems with tab and row | **Partial** | Flags carry `tab`, `row`, `field`, `message`, `severity` (`:813`); `report.md` lists counts, learners per class, flagged rows, missing required fields (`:144-196`). Missing: no in-app fix-and-retry loop — the school must edit the source sheet and re-export (`#1296` step 3 says exactly this). |
| 3 | Asks for missing answers through buttons and forms, not typed commands | **Partial** | #1307 has cards and buttons for skip/confirm/abandon; `saveTeacherEmail()` lets a teacher's email be filled in a form; the wizard has real forms. Missing: no form for parent email, relationship, second guardian, teacher gender/role — those go to `pending/*.csv` for manual entry. The v2 driver is buttons + free text only. |
| 4a | Creates terms | **Partial** | #1307 step `terms` → `OnboardingEngine::saveTerms()`; wizard terms step. Not on `main` for real schools. |
| 4b | Creates classes and streams | **Partial** | #1307 step `standards` → `saveStandards()`; wizard has preview/confirm and "create missing streams and retry". Works from canonical class names only (`:23-27`), so a school using other names is flagged, not mapped. |
| 4c | Creates subjects | **Partial** | #1307 step `subjects` → `saveSubjects()`; wizard subjects step. |
| 4d | Creates fee structure | **Missing** | No workbook step (`WALK` excludes `fees`), no importer; `saveFees()` exists (`OnboardingEngine.php:1504`) but only the wizard calls it. #1296 sends fees to `no-importer/fees.csv` for manual entry. |
| 4e | Creates staff | **Partial** | #1307 `teachers` step writes teachers + teaching assignments through the engine; legacy `TeachersImport` exists. Missing: teacher gender/role have no column (`pending/teacher-details.csv`). |
| 4f | Creates learners and parents | **Partial** | #1307 `students` step; wizard chunked import. Missing: parent email/relationship/second guardian (no column in `UsersImport` either). |
| 4g | Creates class teachers | **Missing (in the agent path)** | Listed in the #1307 completion card, not written; `OnboardingEngine` has no method; only the wizard's inline `importClassTeacherUpload()` writes them. |
| 4h | Creates teaching assignments | **Partial** | Teacher-links importer (`TeacherLinkImportController`) and #1307's teachers step. The importer ignores the stream column (documented in #1291's `pendingNotes`). |
| 5 | Sends invites | **Partial** | Teacher invites: `TeacherInviteLinkService` + #1307 `sendTeacherInvites()` (real `Mail::to`). Parent/student: `StudentParentAccountInviteService` exists on `main`, not called by any onboarding flow. WhatsApp staff invites are disabled by config (`staff_whatsapp_enabled=false`). |
| 6 | Runs post-import checks | **Partial** | #1307 completion card: learners per class/stream vs workbook, KLS present, phones E.164, invites ready. #1296 lists 7 manual checks, including a class-head register check and one parent link end to end — neither is automated. |
| 7 | Never writes without the admin confirming | **Partial** | Per-step "Confirm step" in #1307; per-structure confirm in v2; tier-2 tool confirm in the panel. Missing: confirmation is per **step**, not per record; it lives in session state, not an audit table; `$bypassConfirm` is a static flag. |
| 8 | No exams or SchoolPay during onboarding | **Partial / conflicting** | Playbook excludes them (`:7`), wizard has no such steps, #1307 has no such steps — but the legacy Toshi flow still lists `exams` and `school_pay` (`AgentToshi.php:218-238`) and is the flow a school in `onboarding` mode sees. |
| 9 | Stops if the DPA is not confirmed | **Partial** | Enforced in the converter (`checkConfirmations()`, `:200-222`) and in #1291's exit code; #1296 makes it gate 1. Not enforced in `OnboardingEngine` or the wizard — any other path can write without it. |
| 10 | Global product, not Uganda-only | **Partial** | Country profiles (`app/Onboarding/Country/*`), conditional EMIS/UNEB (`OnboardingStepsService.php:229-243`), per-country phone normalisation (`PhoneCountry`). But: default country Uganda (#1246, in `main`), canonical class names are Uganda's (`:23-27`), UNEB/EMIS vocabulary is baked into step names, and `SchoolTypePreset` assumes a Uganda-shaped ladder. A nursery or A-level school outside Uganda will be flagged or mis-mapped. |

---

## 4. Proposed build plan

Order matters: consolidate first, then write, then ask, then invite, then verify.

### Phase 0 — Decide the shape (2–3 days, 1 person)

- Pick **one** step list: `OnboardingStepsService` (16). Make `AgentToshi::$steps` and
  `StepRegistry` derive from it; delete `exams` and `school_pay` from any onboarding path.
- Pick **one** converter: #1307's. Close #1291 as superseded.
- Decide the workbook tab layout and freeze it in one fixture (the POC already ships
  `docs/demo/poc-checklist-workbook.xlsx` and a correct twin).
- Rewrite the three stale headers (`onboarding-engine-plan.md`, `onboarding-defaults-skill.md`,
  `#1296`'s first-sign-in paragraph) so the docs stop contradicting `main`.

**Reuse:** `OnboardingStepsService`, `OnboardingEngine`, the playbook.
**Retire:** the 19-step list, #1291, #1293 as written.

### Phase 1 — Land the read-and-validate half without UI (1 week, 1–2 people)

- Land #1286 (`phones:to-e164`) and the converter from #1307, with tests, behind a flag. No panel
  changes, no writes. This is the lowest-risk slice and it makes `checklist:convert` real on `main`.
- Keep #1287 separate; it is not in #1307 and is needed before any import of real names.
- Output: `checklist:convert` on `main`, dry by default, writing only to an output folder.

**Size:** ~1,200 lines of production code + tests, mostly already written.
**Risk:** low; no database writes.

### Phase 2 — Promote the POC to a real-school flow (1–2 weeks, 2 people)

- Remove the `is_demo || is_test` restriction in `ChecklistOnboardingGate`; replace it with a
  per-school feature flag plus an explicit "this school may onboard from a workbook" switch.
- Change `ToshiOnboardingPath` in the same PR: it currently sends every real school to the wizard and
  rewrites `toshi_mode` to preview. Until that class changes, no amount of flag-flipping makes the
  agent reachable for a real school. Decide the parity bar with Rasta before touching it, because
  the redirect is the current safety net.
- Land the five write steps (`terms`, `standards`, `subjects`, `teachers`, `students`) with the
  engine, in one transaction per step, idempotent on re-run.
- Add the two missing engine methods: `saveClassTeachers()` and `saveFeeStructure()` (the latter can
  wrap `saveFees()`), and make the wizard call them instead of its inline code.
- Add an immutable confirmation record: `onboarding_plan_steps` (plan, step, actor, preview hash,
  confirmed_at, applied counts). Session state must not be the only record.

**Reuse:** #1307's service, cards, `OnboardingPlan`, `ChecklistInviteHold`.
**Size:** ~1,500–2,000 lines changed; the largest phase.
**Risk:** medium — first real writes; class-teacher semantics (issue #793) and fee-structure shape
are unresolved.

### Phase 3 — Ask, don't flag (1–2 weeks, 1–2 people)

- For every field the importers cannot take (parent email, relationship, second guardian, teacher
  gender/role, stream on a teaching assignment), add a **form** in the flow with the workbook value
  prefilled and the tab/row shown. Only fall back to `pending/*.csv` when the admin skips.
- Add the "fix in place" path: accept a corrected value in the form, record the override, and keep
  the original flag in the report.

**Reuse:** `StepField`/`StepSchema` (they already describe fields, types, rules) — this is the phase
that finally gives `StepSchemaCatalog` a consumer.
**Size:** ~1,000 lines.
**Risk:** medium — scope creep into "edit the workbook in the app".

### Phase 4 — Invites and first sign-in (1 week, 1–2 people)

- Teacher invites: keep #1307's `sendTeacherInvites()`, add a per-invite preview and a send summary.
- Parent/student: call `StudentParentAccountInviteService` from the flow for every imported person
  with a usable channel; queue the rest as "hand-over link" for the admin.
- WhatsApp: keep `staff_whatsapp_enabled=false` until Meta approves the template; do not build a
  parallel sender.

**Reuse:** four invite services already on `main`.
**Size:** ~600–900 lines.
**Risk:** low–medium; delivery is externally gated.

### Phase 5 — Post-import checks as code (1 week, 1 person)

- Turn #1296's seven checks into a `OnboardingVerificationService`: counts per class/stream, KLS
  presence, E.164 phones, class-head register covers every stream, one parent link end to end,
  no stray demo data.
- Show the result as a card with pass/fail per check and a link to the failing screen.

**Reuse:** #1307's `completionCard()` (three of the checks already exist).
**Size:** ~500 lines + tests.
**Risk:** low.

### Phase 6 — Retire the old paths (2–3 days, 1 person)

- Delete the 19-step Toshi list and its `exams`/`school_pay` handlers; keep assistant mode.
- Point the admin student import at the chunked pipeline or mark `UsersImport` legacy.
- Delete `ParentsImport` or `UsersImport`'s parent creation — one definition of a parent.
- Close the 47 already-merged PRs; close #1291, #1293, #1183.

### Sequencing risk, stated plainly

The whole plan assumes #1307 is salvageable. It is a 12k-line branches-from-9-November prototype
with a demo gate and no full test-guard run. If a rebase onto `main` takes more than a few days,
the fallback is Phase 1 (converter only) plus the wizard, and #1307 is closed as a reference
implementation. That decision should be made at the end of Phase 0, not at the end of Phase 2.

### Risks

| Risk | Why it matters | Mitigation |
| --- | --- | --- |
| 345-commit drift | Every onboarding PR predates #1285 and touches files it rewrote | Rebase one PR at a time onto `main`; land the converter first |
| Static `$bypassConfirm` | Process-wide flag; a tool that throws before the `finally` would leave it set for the request | Replace with an explicit argument/context object; add a test |
| Two converters, four step lists | Divergence is already visible (exams/SchoolPay) | Phase 0 consolidation, with a test that asserts one list |
| DPA gate only in the converter | Any other path (wizard, direct engine call) can write without it | Move the gate into `OnboardingEngine` (or a policy) as a precondition for real schools |
| No confirmation audit | "Who confirmed what" is session state | Add the confirmation table in Phase 2 |
| Uganda-shaped defaults | Class names, EMIS/UNEB, default country | Make the canonical class list a country profile, not a constant |
| Invite deliverability | WhatsApp staff template not approved; many parents have `.local` emails | Treat invites as "ready" vs "sent"; hand-over links for the rest |
| The agent is off by design | `ToshiOnboardingPath` and the POC gate both block real schools; turning either on is a product decision, not a flag flip | Make the parity bar explicit in Phase 0; keep the redirect until the checklist flow passes the same checks the wizard does |

### Open questions for Rasta

1. **Is the workbook the canonical input, or a convenience?** If a school uses the wizard instead,
   must the agent support both, or is the workbook the only supported start for the agent?
2. **Fee structure**: what exactly should the agent create — fee categories with amounts per class,
   or a termly bill? `saveFees()` allows both shapes and #1296 sends fees to manual entry.
3. **Class teachers**: write them from the workbook (and add the engine method), or keep them a
   human step as #1307 does? Issue #793 suggests the order, not the ownership.
4. **DPA**: is the workbook's Confirmations tab the legal record, or does the product need its own
   acceptance (user, timestamp, version)? The converter currently treats a spreadsheet cell as the
   gate.
5. **Who may run this**: school admin only, or also KlassApp ops on the school's behalf? The answer
   decides the authorization gate and the audit fields.
6. **Parents' first sign-in**: is `StudentParentAccountInviteService` (email link) the intended
   path, with WhatsApp hand-over later, or should parents only ever link by messaging the school
   number?
7. **Non-Uganda schools**: which class ladder should the converter accept for a nursery or an
   A-level school — free text mapped to profiles, or a per-country canonical list?

---

## 5. What I did not do

- I did not run the test suite, the test guard, or any single test.
- I did not start the app, click through the wizard, Toshi, or the POC, at any viewport.
- I did not query a database, including the preview database on `127.0.0.1:8126`, which belongs to
  another agent.
- I did not merge, deploy, or push to any integration or preview branch.

Everything above is read from the repository at the refs named, with GitHub metadata for PR state.
Where a PR body reports a result, it is marked **[claim]**.

---

## Appendix A — Onboarding-relevant PRs, as at 24 November 2026

| PR | Author | Head → base | State | What it is |
| --- | --- | --- | --- | --- |
| #1307 | Mucunguzi256 | `poc/toshi-checklist-onboarding` → `main` | draft, +12,207/−99, 123 files | Checklist onboarding in the Toshi panel, demo-gated. The only end-to-end prototype. |
| #1296 | Mucunguzi256 | `t089-onboarding-runbook` → `main` | draft, 345 behind | Human runbook for the workbook. |
| #1293 | Mucunguzi256 | `t094-parent-student-access` → `main` | draft, 345 behind | First sign-in design; partly implemented on `main` by #1275. |
| #1291 | Mucunguzi256 | `t079-checklist-convert` → `t077` | draft, 345 behind, stacked | `checklist:convert`; contained in #1307. |
| #1287 | Mucunguzi256 | `t105-names-normalize` → `main` | draft, 345 behind | `names:normalize`, dry run. Not in #1307. |
| #1286 | Mucunguzi256 | `t077-phones-to-e164` → `main` | draft, 345 behind | `phones:to-e164`, dry run; contained in #1307. |
| #1275 | Mucunguzi256 | `cursor/t094-parent-student-setup-links` → `…t091` | open, **already in `main`** via #1285 | Setup links for imported parents/students (`ccd56ead`). |
| #1260 | Mucunguzi256 | `cursor/onboarding-steps-and-size-plus` → `main` | **merged** (9 Nov) | Step counters, school name, year. |
| #1256 | Bariyo123 | `cursor/school-admin-setup-login-30c4` → `main` | **merged** (9 Nov) | Unfinished admins → setup after signup/login. |
| #1252 | Bariyo123 | `cursor/t060-widen-student-id-c495` → `main` | **merged** (9 Nov) | Student IDs past 9,999; failed import rows. |
| #1250 | Bariyo123 | `cursor/t061-import-entry-points-c495` → `main` | **merged** (9 Nov) | Each import button → its own import; added `ParentsImport`. |
| #1248, #1246, #1251, #1249 | Bariyo123 | various | **merged** (9 Nov) | Branding/SEO lock, Uganda default country, international phones, country codes. |
| #1258, #1259, #1249, #1141, #1147, #1155, #1161, #1133, #1137–#1140, #1149 | Bariyo123 | various | open, not in `main` | Seeds, signup throttle, country codes, test speed, WhatsApp verify token, design brief, Google connectors, docs-site governance. **None touches the onboarding write path.** |
| #1183 | Mucunguzi256 | `cursor/onboarding-session-log-a453` → `main` | draft, 413 behind | `knowledge.md` session log for a pass since overtaken. |

## Appendix B — Onboarding-adjacent branches verified on 24 November 2026

| Branch | Ahead of `main` | Files | Read |
| --- | --- | --- | --- |
| `poc/toshi-checklist-onboarding` | 7 | 123 | The prototype (PR #1307). |
| `t077-phones-to-e164`, `t079-checklist-convert`, `t089-onboarding-runbook`, `t094-parent-student-access`, `t105-names-normalize` | 1–3 | 2–5 each | The five T-0xx artefacts. |
| `feat/term-workbook-importer` | 1 | 2 | Term marks workbook → drafts; not onboarding. |
| `feat/wizard-from-step-registry` | 1 | 20 | 5 Oct: drive the wizard from `StepRegistry`; superseded in practice by the engine work, but it is the only attempt to unify the two lists. |
| `fix/toshi-complete-mode-four-findings` | 2 | 7 | 7 Sep: complete-mode fixes; overtaken. |
| `fix/toshi-skip-continue-and-plan-parity` | 1 | 4 | 11 Sep: skip on Continue forms, plan/sidebar parity; overtaken by v2. |
| `fix/toshi-checklist-shared-source` | 2 | 6 | 10 Oct: one checklist source; the service now owns this. |
| `docs/onboarding-school-checklist` | 5 | 1 | The workbook-filling guide (see 1.10). |
| `docs/onboarding-marks-workbook` | 3 | 1 | Marks workbook guide. |
| `docs/onboarding-attendance-guides` | 3 | 9 | Docs-site help pages incl. setup and people. |
| `upgrade-prep/ai-mcp` | 2 | 12 | Laravel AI 1.4 / MCP 1.2 migration; infrastructure only. |

Branches the previous audit listed (`feature/onboarding-step-buttons`,
`feat/wizard-structure-class-teacher`, `spike/toshi-mcp-slack`) no longer exist on the remote. **[git]**
