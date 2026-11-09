# Agentic school onboarding — audit and build plan

**Status:** audit only. No product code changed. Two docs-only PRs: this audit, and the AGENTS.md verification rule.
**Scope:** everything in this repository that touches onboarding a school, and what is missing before an agent can onboard a school from a filled checklist workbook.
**Audited at:** `main` @ `01d87832` (9 Nov 2026), plus the heads of named PR branches.

---

## 0. Method, and what this document does not claim

**What I did:** read the code, the docs, the PR diffs and the git history in my own checkout. Compared branches by diff, ancestor tests and file lists. Queried GitHub for PR state.

**What I did not do:** I did not start the app, click through any screen, query a database, or run the test suite. This machine has PHP at `/opt/homebrew/bin/php` but this checkout has no `vendor/`, so nothing ran. The preview at `http://127.0.0.1:8126` and its database belong to another agent and were not touched.

**Therefore:**

- Every finding below is **"exists in code"** — I read it. None is "tests pass". None is "works".
- The `State` column uses the requested vocabulary with this meaning: **working\*** = the code path is complete and reachable on main through its normal route; **partial** = it does a job incompletely, or the read path and write path disagree; **duplicated** = a second implementation of a job another piece already does; **abandoned** = superseded, unreachable, or a closed draft. The asterisk means: code-complete as read, not exercised.

**Author names** are git authors. `Mucunguzi Moses` is the project account (GitHub `Mucunguzi256`; commits appear under both `moemucu@gmail.com` and the `85576870+Mucunguzi256@users.noreply.github.com` noreply). `Bariyo123` is a **different** GitHub account (`131570512`), with 10 commits on main and 19 open PRs. `Devteam` is the inherited upstream (GegoK12) code.

---

## 1. What exists, wherever it lives

### 1.1 The step model — one contract, three lists

| Piece | Location | What it does | State | Author |
| --- | --- | --- | --- | --- |
| `OnboardingStepsService` | `app/Services/OnboardingStepsService.php:47` (`ALL_STEPS`, 16 steps), `:121` (`OPTIONAL_STEPS` = teachers, students), `:131` (`FINISH_WITHOUT` = whatsapp_verify), `:301` (`isStepComplete`), `:210` (`applicableSteps`), `:398` (`progress`) | The canonical step list, per-step completion checks and the single progress number used by the dashboard banner, sidebar chip and setup checklist. | working\* | Mucunguzi Moses; last touched by Bariyo123 2026-10-27 (#1245) |
| `StepRegistry` + 16 `Step` classes | `app/Onboarding/Steps/StepRegistry.php:28`; `app/Onboarding/Steps/Steps/*.php` (16 files) | Per-step contract: `key`, `applies`, `isComplete`, `prompt`, `handle`, `save`. Used by the Toshi v2 driver, and by the wizard for step **order**. | working\* | Mucunguzi Moses, 2026-10-03 |
| `StepSchema`, `StepSchemaCatalog`, `StepIssueCatalog` | `app/Onboarding/Steps/StepSchema.php` (326 lines), `StepSchemaCatalog.php` (586), `StepIssueCatalog.php` (707) | Declares input types/options per step and the issue codes with causes and fixes. This is what would let an agent ask a question with buttons instead of prose. | working\*, but only the v2 driver reads it | Mucunguzi Moses |
| `docs/onboarding/playbook.md` | 553 lines | The written spec: every step, choices, starting values, confirmation text, and every error code. **States at line 5 and line 553: "Exams and SchoolPay are not part of onboarding."** | working (doc) | Mucunguzi Moses, 2026-11-04 |
| `docs/onboarding-engine-plan.md` | 440 lines | Design for the shared write engine; records nine "flagged disharmonies" and marks the doc design-only. | working (doc) | Mucunguzi Moses, 2026-10-26 |
| `docs/proposals/2026-10-03-onboarding-steps.md` | 74 lines | Proposal for the shared registry; says Toshi-only steps need a disposition. | working (doc) | Mucunguzi Moses, 2026-10-03 |

**The three lists that must agree, and do not:**

| List | Where | Count | Contents that differ |
| --- | --- | --- | --- |
| `OnboardingStepsService::ALL_STEPS` | `OnboardingStepsService.php:47` | 16 | Has `curriculum`, `school_category`; no `admin_account` |
| `StepRegistry::STEP_CLASSES` | `StepRegistry.php:28` | 16 | Same keys as above |
| `AgentToshi::$steps` | `app/Livewire/AgentToshi.php:218` | 19 | Has `admin_account`, `co_admin_invite`, `exams`, `school_pay`, `review`; no `curriculum`/`school_category` |

The wizard guards itself against drift (`ManualOnboardingWizard.php:1659` throws if a registry key is missing from the service), but **nothing guards the Toshi list**, which is the list that still asks for exams and SchoolPay.

### 1.2 The setup wizard and "Finish setup"

| Piece | Location | What it does | State | Author |
| --- | --- | --- | --- | --- |
| `ManualOnboardingWizard` (Livewire) | `app/Livewire/ManualOnboardingWizard.php` (4,293 lines); `mount()` `:375`; `refreshSteps()` `:1659`; `confirmReview()` `:1361` | The live setup path: 16 steps + a synthetic Review. Each step validates, saves through `OnboardingEngine`, and moves on. Bulk uploads for teachers, class teachers and students are queued. | working\* | Mucunguzi Moses (66 commits, 2026-08-09 → 2026-11-04) |
| Route and shell | `routes/admin.php:12` → `resources/views/admin/onboarding/wizard.blade.php:11` | `/admin/onboarding/wizard`; Toshi is hidden on this page so its checklist cannot compete. | working\* | Mucunguzi Moses |
| Path choice | `app/Http/Controllers/OnboardingChoiceController.php`; `resources/views/auth/onboarding-choice.blade.php` | First-login choice: wizard or Toshi. Sets `schools.toshi_mode` (`preview` for wizard, `onboarding` for Toshi). | working\* | Mucunguzi Moses |
| Real schools forced to the wizard | `app/Services/Toshi/ToshiOnboardingPath.php:22` (`openFor` requires `is_test = 1`); `app/Http/Middleware/SendRealSchoolToshiOnboardingToWizard.php` | A non-test school in `toshi_mode = onboarding` is redirected to the wizard on the next full page load, with the flash "Guided chat setup with Toshi is coming soon." | working\* | Mucunguzi Moses; last Bariyo123 2026-10-27 |
| Finish | `app/Services/FinishSchoolOnboarding.php`; `OnboardingStepsService::markOnboardingFinished` `:173` | Stamps `schools.onboarding_finished_at`, runs one-time follow-ups, notifies Toshi. Idempotent. | working\* | Mucunguzi Moses |
| "Finish setup" surfaces | `resources/views/partials/setup-banner.blade.php`; `resources/views/livewire/partials/toshi-setup-checklist.blade.php` | Dashboard banner, sidebar chip and checklist, all reading `OnboardingStepsService::progress()`. | working\* | Mucunguzi Moses |

Note on the word "Finish": there is no separate "Finish setup" step type. Finishing is `confirmReview()` → `FinishSchoolOnboarding::finish()`, and it is blocked only by `blockingIncompleteSteps()` — teachers, students and WhatsApp are explicitly not blocking (`OPTIONAL_STEPS`, `FINISH_WITHOUT`).

### 1.3 Toshi's onboarding

| Piece | Location | What it does | State | Author |
| --- | --- | --- | --- | --- |
| **v1 scripted flow** | `AgentToshi.php:218` (19 steps), `:244` (9 mandatory), handlers `:4536`–`:6367`, `commitAll()` `:6873` | Chat that collects the school, admin, year, classes, subjects, teachers, students, terms, fees, exams and SchoolPay, then writes on Confirm. | working\*, **gated**: only `is_test` schools can use it (`ToshiOnboardingPath.php:22`); real schools are redirected to the wizard | Mucunguzi Moses (156 commits, 2026-06-20 → 2026-10-26) |
| **`commitAll()` create vs complete** | `AgentToshi.php:6873` | Two hand-written branches. Both now delegate content writes to `OnboardingEngine`, but school creation, admin creation, academic year, co-admin invite, exams and WhatsApp still differ branch by branch. | **duplicated** (two branches, one job) | Mucunguzi Moses |
| **v2 registry flow** | `app/Onboarding/Steps/ToshiOnboardingV2Driver.php` (373 lines); `config/toshi.php:46` `onboarding_v2` (default **false**); `AgentToshi::enterOnboardingV2` `:1120`, `actionOnboardingV2` `:1159`, `selectOnboardingV2Option` `:1220` | A chat loop over `StepRegistry` with option buttons (`onboardingV2Options`) and typed input. This is the "rebuild with in-chat buttons and forms". | working\*, **gated off by default**; the config comment says it is for test schools until Rasta enables it | Mucunguzi Moses |
| **Checklist-driven onboarding** | `AgentToshi::getOnboardingChecklistProperty` `:741`, `detectMissingSteps` `:755`, `jumpToChecklistStep` `:809`; `resources/views/livewire/partials/toshi-setup-checklist.blade.php` | Reads `OnboardingStepsService::steps()`, lists what is missing as clickable rows, jumps the chat to that step. | working\*, same gating as the flow it drives | Mucunguzi Moses |
| **Exams and SchoolPay steps** | `handleExams` `:6112`, `handleSchoolPay` `:6322` | Still asked in the v1 flow. `handleSchoolPay` asks for an API password and prints a `klassapp.xyz/api/schoolpay/webhook` URL. | working\*, but contradicts the playbook (`playbook.md:5`, `:553`) and is Uganda-only | Mucunguzi Moses |
| **Tools Toshi calls** | `TOOL_CLASS_MAP` `AgentToshi.php:48` (60+ tools); the 9 onboarding-relevant ones are `AddStudentTool`, `AddTeacherTool`, `AddParentTool`, `CreateSubjectTool`, `CreateTermTool`, `CreateFeeTool`, `CreateStreamTool`, `AssignTeacherTool`, `SetCurriculumTool` | Each write tool returns a `__tier2_confirm` preview, executes only after Confirm, then re-queries to verify. | working\* | Mucunguzi Moses |

The single most useful thing in this section: **the scripted Toshi onboarding is not the path a real school takes today.** It survives for demo/test schools, and its v2 replacement is behind a flag that is off. The wizard is the product.

### 1.4 Importers, and how they are triggered

| Entity | Path and trigger | What it writes | Reporting | State | Author |
| --- | --- | --- | --- | --- | --- |
| **Students (admin page)** | `GET /admin/import` → `ImportMemberController@index` (`ImportMemberController.php:32`); `POST /importUsers` → `@importUsers` `:41` → `app/Imports/UsersImport.php` (Maatwebsite) | `users`, `userprofiles`, `student_academics`, parent user via `CreateParent` | **Count only.** Rows with no `firstname` or `class` are skipped with a `Log::warning` (`UsersImport.php:45-48`); a mid-file exception calls **`dd($e->getMessage())`** (`:210`), killing the request after a partial write. | partial | Devteam 2025; last Mucunguzi Moses 2026-10-07 |
| **Students (wizard)** | `studentUpload` → `queueUpload()` `:746` → `onboarding_uploads` row → `ProcessOnboardingUpload` job → `OnboardingUploadService::import()` `:112` → `OnboardingEngine::saveStudents` | Same tables, in chunks, with a missing-stream retry and per-row `skips`/`notes` arrays | Per-row skip reasons, missing-stream list with "create streams and retry" | working\* | Mucunguzi Moses |
| **Teachers** | `GET /import/teacher` → `TeacherImportExportController@importCreate` `:87`; `POST /importTeachers` → `@import` `:96` → `app/Imports/TeachersImport.php` | `users`, `userprofiles`, qualifications, `teacherlinks` | Count only; blank rows skipped silently (`TeachersImport.php:36`) | partial | Devteam 2025; last Mucunguzi Moses 2026-10-21 |
| **Teacher links** (subject × class) | `POST /teacher-links/import` → `TeacherLinkImportController@import` `:42` | `teacherlinks` | Per-row errors, but only the first 3 are shown (`:172`) | partial | Mucunguzi Moses |
| **Class teachers** | `POST /admin/class-teachers/import`-style wizard upload → `importClassTeacherUpload()` `:2974` → `writeClassTeacherAssignment()` `:3239`; or `ClassTeacherInviteController@store` `:59` → `ClassTeacherInviteService::invite` `:41` | `standard_links.class_teacher_id`, `teacher_invites` | Per-row skip reasons; invite path reports success/failure per person | working\* | Mucunguzi Moses |
| **Exams** | **No importer exists.** Only Toshi's form (`showExamFormFn` `:2570` → `OnboardingEngine::saveExams` `:2331`) and the separate marks import (`MarksImportController`) | `exams` | — | missing (as an import) | — |
| **Fees as payments** | **No fee-structure importer exists.** The wizard and Toshi fee forms write fee categories via `OnboardingEngine::saveFees` `:1503`; the runbook (step 9) says the app's fee upload template is a **payments** file, not a fee structure | `fees_categories`, fee rows | — | missing (as an import) | — |

Two consequences worth stating plainly:

1. `checklist:convert` (PR #1291) writes `students.csv`, `teachers.csv`, `teacher-links.csv`, `class-teachers.csv` with headers that **do** match what `OnboardingNameListExtractor` reads (`class`, `stream`, `parent name`, `parent phone`, `gender`, `date of birth`, `school student id` — aliases at `app/Services/OnboardingNameListExtractor.php:141-200`, teacher-link columns at `:406-470`). So those files feed the **wizard's** upload path on main. They do not feed `/importUsers`, which wants `firstname`/`lastname`.
2. The converter's report text refers to "the exam importer" (`ChecklistWorkbookConverter::report()`), but no exam importer exists in the codebase. That sentence is wrong today.

### 1.5 The five T-0xx artefacts

| PR | Branch / base | Files | What it does | State | Author |
| --- | --- | --- | --- | --- | --- |
| **#1286** `phones:to-e164` | `t077-phones-to-e164` → `main` | 4 (all new): command, `app/Services/Phones/PhoneE164Converter.php`, 2 tests | Converts stored phones to E.164 using each school's country; 6 tables; **dry run by default**; anything unparseable is counted, sampled, logged and left alone | **draft PR**, additive | Mucunguzi256 |
| **#1287** `names:normalize` | `t105-names-normalize` → `main` | 4 (all new): command, `app/Services/People/PersonNameNormalizer.php`, 2 tests | Splits "AGNES KALIISA" into first/last and proper-cases; staff and parents only; students untouched; `userprofiles` only, never `users.name`; dry run by default | **draft PR**, additive | Mucunguzi256 |
| **#1291** `checklist:convert` | `t079-checklist-convert` → **`t077-phones-to-e164`** (stacked on #1286) | 3 (all new): `ChecklistConvertCommand.php`, `app/Services/Onboarding/ChecklistWorkbookConverter.php`, test | Reads the filled workbook; **refuses to write anything unless the DPA row on Confirmations is "Yes"** (`checkConfirmations` `:200`); validates every tab and flags `{tab, row, field, message}`; writes the four importer CSVs, `no-importer/`, `pending/` and `report.md`; **never touches the database** | **draft PR**, stacked | Mucunguzi256 |
| **#1293** parent/student first sign-in | `t094-parent-student-access` → `main` | 1 doc | Diagnoses why imported parents/students cannot sign in (random password, `is_reset = 1`, generated `.local` email, reset-by-email only) and proposes `AccountInviteLinkService` for **existing** users, with email / WhatsApp / hand-over channels | **draft PR**, design only | Mucunguzi256 |
| **#1296** onboarding runbook | `t089-onboarding-runbook` → `main` | 1 doc (120 lines) | The human procedure around the workbook: gates (DPA, privacy approval, AI off, staging rehearsal), export, convert, fix flagged rows, backup, import order, invites, post-import checks, record | **draft PR**, docs only | Mucunguzi256 |

### 1.6 Invite links and the invite service

| Invite | Issuer / claimer | Token | Lifetime | What claim sets | State | Author |
| --- | --- | --- | --- | --- | --- | --- |
| **Early-access school invite** | `SchoolInviteService::issue` `:31`, `::claim` `:130`; `SchoolInviteClaimController`; route `/invite/school/{token}` | 64 random chars, stored as SHA-256, email-bound, single-use | 168 h (`config/invites.school_expiry_hours`) | Creates the School + admin user, marks claimed with a `whereNull('claimed_at')` guard | working\* | Mucunguzi Moses |
| **Teacher invite** | `TeacherInviteLinkService::issue` `:35`, `::claim` `:185`; `TeacherInviteController`; `/invite/teacher/{token}` | 64 chars, SHA-256, single-use, reissue invalidates the old one | 72 h (`invites.expiry_hours`) | Creates a **new** User with the teacher's chosen password, `is_reset = 0`, assigns `class_teacher_id` when given | working\* | Mucunguzi Moses |
| **Class-teacher invite** | `ClassTeacherInviteService::invite` `:41`; `ClassTeacherInviteController` `:20`, `:59`, `:82` | Delegates to teacher invite | 72 h | Either reassigns an existing teacher (`existing_teacher_id`) or issues a teacher invite and assigns on claim | working\* | Mucunguzi Moses |
| **Co-admin invite** | `CoAdminInviteLinkService`; `CoAdminInviteController`; `/invite/co-admin/{token}` | Same pattern | same | Creates/activates a co-admin | working\* | Mucunguzi Moses |
| **Set-password link** | `app/Support/SetPasswordLink.php`; used by Toshi create mode when no password is chosen (`AgentToshi.php:6934`) | Signed link | short | Sets a password on an existing account | working\* | Mucunguzi Moses |
| **Imported parent / student** | **none** | — | — | — | **missing** (design: #1293) | — |

The pattern is sound and repeated four times; the gap is that it only ever activates a **new** user. Imported parents and students already exist with a random password nobody knows, so none of these links can help them.

### 1.7 Toshi's authorization gate and confirm-before-write

| Layer | Location | What it enforces |
| --- | --- | --- |
| **School action gate** | `app/AiAgents/Concerns/AuthorizesToshiAction.php` — `authorizeSchoolAction()` uses `Gate::inspect('toshi-school-action')`, plus `assertEffectiveSchoolScope()` (target must belong to the actor's school) | Actor may act for this school, on this record |
| **Deputy allowance** | `authorizeSchoolOrDeputyAction()` — allows `toshi-deputy-action` for ug4, owner-only tools must use the admin path | ug4 cannot use owner-only tools (`AddCoAdminTool`, `SetCurriculumTool`) |
| **Capability list** | `ToshiActionService::can()` `:341` → `getRoleCapabilities()`; ug3/ug4 action arrays at `:240-266` | Coarse per-role action list (`add_student`, `create_subject`, …) |
| **Plan limits** | `ToshiActionService::enforcePlanLimit()` | Never blocks a write; returns a notice after the fact |
| **Confirm before write** | `app/AiAgents/Concerns/ConfirmsBeforeWrite.php:26` → `ToshiActionService::confirmationPayload()` `:55` → `__tier2_confirm` JSON → `AgentToshi` shows a card → `confirmYes()` `:1426` → `executeConfirmedTool()` `:1790` with `bypassConfirm` | No tool write without an explicit Confirm |
| **Post-write verification** | `VerifiableTool::verify()`; all 9 onboarding-relevant tools implement it | Re-queries after write; a mismatch is reported as "appeared to succeed but verification could not confirm" (`AgentToshi.php:1814`) |
| **Superadmin MCP writes** | `app/Livewire/Superadmin/Toshi/PlatformApprovalGate.php`, `app/Ai/Tools/Toshi/ApprovableMcpTool.php` + `app/Services/Toshi/McpWriteGate.php` | MCP write tools pause for approval; write classification is config-driven, and **defaults to write** when a connector is not classified |

Two limits worth recording: the confirm gate is **per tool call**, not per plan (a 30-student import is 30 confirmations or one bulk preview); and the wizard, the imports and the CLI commands have **no** equivalent gate.

### 1.8 MCP server and tools

| Piece | Location | What it is |
| --- | --- | --- |
| MCP **client** layer | `app/Services/Toshi/ToshiMcpClient.php`, `AuditingMcpClient.php`, `AuditingMcpClientManager.php`, `ToshiMcpCallAuditor.php`, `McpWriteGate.php`; `app/Models/SchoolMcpConnector.php` | Outbound MCP to external connectors (Slack, Google), audited, write-gated |
| MCP **server** | `app/Mcp/Servers/SpikeSlackMockServer.php` + `app/Mcp/Tools/SpikeSlack*.php` | A **fixture mock of Slack**, registered when `SLACK_MCP_MODE=mock`. Not a product server |
| Onboarding over MCP | — | **Nothing.** No MCP server exposes onboarding, and no onboarding tool is available over MCP |

So "any MCP server or tools" has a precise answer: one throwaway Slack mock server, one outbound client stack, and zero onboarding surface.

### 1.9 Onboarding specs in `docs/`

| Doc | What it is | Still true? |
| --- | --- | --- |
| `docs/onboarding/playbook.md` (553) | Step-by-step spec with error codes and starting values | Mostly. Contradicted by Toshi v1's exams/SchoolPay steps |
| `docs/onboarding-engine-plan.md` (440) | Engine design; lists nine flagged disharmonies | Superseded in part: the engine shipped |
| `docs/onboarding-defaults-skill.md` (91) | "Configurable, but never blank" defaults per step | Principle documented; terms/classes prefill not fully implemented |
| `docs/proposals/2026-10-03-onboarding-steps.md` (74) | Step registry proposal | Shipped as `StepRegistry` |
| `docs/product/student-import.md` (97) | Student import behaviour and scale | Current, Bariyo123 |
| `docs/onboarding/school-onboarding-runbook.md` | Workbook runbook (#1296) | Draft PR |
| `docs/product/parent-student-access.md` | First sign-in design (#1293) | Draft PR |
| `docs/dev/emis-lin-onboarding.md` | EMIS/LIN onboarding notes | Country-specific |
| `docs/architecture/toshi-shape-2026-09.md` | Toshi architecture | Background |

### 1.10 TODOs, flagged decisions and issues

- **Code TODOs:** 2 in `app/`, neither about onboarding. This codebase does not use TODO comments.
- **`FLAGGED(product-decision)`:** **56** markers in `app/`, most in `AgentToshi.php` and `ManualOnboardingWizard.php`, each pointing at `docs/onboarding-engine-plan.md`. They are the real open-decisions list: uneb as default curriculum, Uganda as default country, `P1` as default class, UGX formatting, `.sch.ug` fallback emails, `0700000000` fallback phone, random passwords instead of invites. Six examples: `AgentToshi.php:146`, `:642`, `:2459`; `ManualOnboardingWizard.php:89`, `:94`, `:107`.
- **Issue #793** (open, Elicom256, 2026-09-21): "Structure & Class Teachers: on `admin/onboarding/wizard` — we're trying to invite teachers before they're uploaded, I think this can come after uploading teachers and students." Still open, and still true of the wizard's structure step.
- **No task register in the repo.** The `T-0xx` IDs in PR titles live in an external tracker; nothing in `docs/` maps them.

---

## 2. Overlaps and conflicts

### 2.1 Same job, more than one owner

| Job | Implementations | Which one is real |
| --- | --- | --- |
| Onboarding a school | wizard (`ManualOnboardingWizard`), Toshi v1 (`AgentToshi`), Toshi v2 (`ToshiOnboardingV2Driver`) | **Wizard.** The other two are gated to `is_test` / a flag that is off |
| Deciding "is this step done?" | `OnboardingStepsService::isStepComplete` (16 keys) vs `AgentToshi::$steps` (19) vs `StepRegistry` | `OnboardingStepsService` — the other two should read it |
| Writing classes | `ManualOnboardingWizard::saveClass` `:2298` → `OnboardingEngine::saveStandards`/`addClassStream`; `AgentToshi` structure checkpoint `:5427`; `OnboardingEngine::createMissingStreams` `:1036` | `OnboardingEngine` — both UIs already delegate, so this one is converged |
| Student import | `/importUsers` → `UsersImport`; wizard upload → `OnboardingUploadService` → `OnboardingEngine::saveStudents`; #1285's `ImportStudents` page → the same service | **Two** implementations: the legacy `UsersImport` (no per-row report, `dd()` on error) and the onboarding upload service (chunked, per-row skips) |
| Teacher import | `/importTeachers` → `TeachersImport`; wizard upload → `saveTeachers` → engine | Same split as students |
| Fee structures | wizard fee drafts; Toshi fee form; both via `saveFees` | Converged on the engine |
| Invites | `TeacherInviteLinkService`, `ClassTeacherInviteService`, `CoAdminInviteLinkService`, `SchoolInviteService`, `SetPasswordLink` | Five entry points, three token tables (`school_invites`, `teacher_invites`, `co_admin_invites`), one pattern. Consolidation is possible but not urgent |
| Confirmation before write | `ConfirmsBeforeWrite` (tools), `PlatformApprovalGate` (superadmin MCP), `McpWriteGate` (connector writes), wizard per-step save (none) | No single policy; the wizard and imports have no gate at all |
| Progress display | `OnboardingStepsService::progress()`; dashboard banner; sidebar chip; the Toshi checklist | Converged — the old private 19-step counter was replaced by the service (`AgentToshi.php:736` records the drift) |

### 2.2 PR and branch conflicts

**#1285 is the gravity well.** `integration/monday-2026-11-09` is 215 commits and **663 files** ahead of main, and it already contains the branch tips of **39 of the 69 open PRs**. That includes eight of Bariyo123's nineteen:

`#1258` (seed health), `#1256` (unfinished admins → setup), `#1252` (T-060 wide IDs + failed rows), `#1251` (T-065 phones), `#1250` (T-061 import entry points), `#1249` (T-064 country codes), `#1248` (T-070 branding), `#1246` (default country Uganda).

If #1285 merges, those eight are already in it and their PRs become no-ops or conflicts. If it does not merge, that work is stranded on a branch nobody can merge safely.

Onboarding files #1285 changes: `AgentToshi.php`, `ManualOnboardingWizard.php`, `OnboardingEngine.php`, `OnboardingStepsService.php`, `TeacherInviteLinkService.php`, `ClassTeacherInviteService.php`, `ImportMemberController.php`, `TeacherImportExportController.php`, `TeacherLinkImportController.php`, `SchoolInviteClaimController.php`, `TeacherInviteController.php`, plus new `ParentImportController`, `ParentsImport`, `StudentParentAccountInviteService`, `TeacherAccountInviteService`, `routes/school-imports.php`, `config/invites.php`, `InviteNoReferrer`, `ScrubInviteTokens`.

**#1295 is a copy of #1285 plus ten files.** `t107-name-route-guards` is 215 commits ahead of main, 667 files, +49,193/−6,226 — and differs from #1285 by only 10 files (the name-route guards). It is labelled "merge after #1285", which is correct and understates it: as based on main it would drag the whole integration rehearsal in. It must be rebased onto main after #1285 merges, not merged on its own.

**#1291 is stacked on #1286** (`t079-checklist-convert` → `t077-phones-to-e164`). That is declared in the PR and is fine, provided #1286 merges first. It adds only new files, so it will not conflict textually with #1285 — but it will sit next to #1285's import entry points, and the runbook tells a human to upload the CSVs at the wizard steps, not at #1285's new pages.

**#1286, #1287, #1291 are additive:** 4, 4 and 3 new files; no edits to existing files, so no merge conflicts with main or #1285. The cost is a second implementation of phone normalisation (`PhoneE164Converter`) alongside the inline normalisers already in `OnboardingEngine::normaliseOnboardingPhone` `:2579` and `ManualOnboardingWizard::normaliseOptionalPhone` `:1630`.

**Product conflicts, not textual ones:**

- `#1246` (and therefore #1285) defaults the onboarding country to **Uganda**. The brief for this audit is explicit that KlassApp is global. The default is defensible as a soft-launch choice but must not harden into schema or validation.
- Toshi v1 asks for **SchoolPay** (`AgentToshi.php:6322`), a Uganda-only provider, inside onboarding. The playbook says it must not be there at all.
- Toshi v1 asks for **exams** during onboarding (step `exams`, `:6112`), which the playbook also excludes.

**Pairs that touch the same files and must be sequenced:** #1252 and #1250 both change import entry points; #1251 and #1246 both change country/phone handling; #1291 writes files for importers that #1250 and #1252 change. Merge order: #1285 (which already contains all four) → rebase the rest.

### 2.3 Safe to retire

| Retire | Why | Evidence |
| --- | --- | --- |
| Toshi v1 scripted onboarding for real schools | Already unreachable for non-test schools | `ToshiOnboardingPath.php:22`; middleware redirect |
| Toshi v1 `exams` and `school_pay` steps | Contradict the playbook; Uganda-only; not in `ALL_STEPS` | `playbook.md:5`, `:553`; `AgentToshi.php:218` |
| `AgentToshi::$steps` as an independent list | Third taxonomy that nothing guards | `AgentToshi.php:218` vs `OnboardingStepsService.php:47` |
| Legacy student/teacher import controllers once the upload service covers them | Duplicate path, worse reporting, `dd()` on error | `UsersImport.php:210`; `ImportMemberController.php:41` |
| Stale branches whose content is already in main | `feature/onboarding-step-buttons`, `feat/wizard-structure-class-teacher` (0-file diff vs main), `co-admin-invite-links`, `fix/toshi-onboarding-default` (merged) | `git diff --name-only origin/main...origin/<branch>` |
| Closed-unmerged PRs #1238, #1109, #1108 | Superseded: #1238 by #1239/#1262; #1109/#1108 were WIP handovers | `gh pr view` |
| `dd()` in `UsersImport` | Debug statement on a production import path | `UsersImport.php:210` |

Not safe to retire without a decision: the wizard itself (it is the only real path), and `SetPasswordLink` (it is how a Toshi-created admin sets a password).

---

## 3. Gaps against the target

Target: a school fills the onboarding checklist workbook; an agent reads it, validates it, asks for what is missing through buttons and forms, creates the structure and the people, sends invites, runs post-import checks, never writes without the admin confirming, keeps exams and SchoolPay out, and stops if the data processing agreement is not confirmed.

| # | Capability | Mark | What exists | What is missing |
| --- | --- | --- | --- | --- |
| 1 | **Reads the workbook** | **partial** | `ChecklistWorkbookConverter::convert()` reads all tabs, skips the grey example row, handles `P.3`→`Primary Three`, and refuses when the "All Learners" formulas did not export (`:81-88`). In PR #1291, unmerged | Not on main; CLI only (`php artisan checklist:convert`); no upload surface; not callable as a tool by an agent |
| 2 | **Validates and reports problems with tab and row** | **partial** | `flags[] = {tab, row, field, message, severity}`; `report()` renders counts, learners per class/stream, flagged rows, pending items. Refuses on DPA ≠ Yes | Unmerged; report is a file a human reads, not structured data an agent can act on; no way to re-check just one corrected row |
| 3 | **Asks for missing answers through buttons and forms** | **partial** | `StepSchema`/`StepSchemaCatalog` declare input types and options; `ToshiOnboardingV2Driver` renders option buttons (`promptPayload`, `selectOnboardingV2Option`); the wizard has real forms | No loop that takes *the workbook's* gaps and asks only those; v2 driver is behind `toshi.onboarding_v2 = false`; no mapping from a converter flag to a question |
| 4 | **Creates structure: terms, classes, streams, subjects, fee structure** | **exists in code** | `OnboardingEngine::saveAcademicYear` `:256`, `saveStandards` `:1280`, `addClassStream` `:762`, `createMissingStreams` `:1036`, `saveSubjects` `:1302`, `saveTerms` `:1374`, `saveFees` `:1503` | Nothing drives these from the workbook. `no-importer/classes-and-streams.csv`, `subjects.csv`, `fees.csv`, `terms.csv` are explicitly "enter by hand" (runbook step 5) |
| 5 | **Creates staff, learners and parents** | **exists in code** | `saveTeachers` `:1839`, `saveStudents` `:1999` (creates parents and links by phone, `linkImportedParent` `:2252`) | Same: driven by forms/uploads, not by a plan. Legacy importers bypass the engine and report less |
| 6 | **Class teachers and teaching assignments** | **exists in code** | `TeacherLinkImportController`, `ManualOnboardingWizard::importClassTeacherUpload` `:2974`, `writeClassTeacherAssignment` `:3239`; `ClassTeacherInviteService` | Split across three entry points; the wizard invites class teachers before teachers exist (issue #793); no single "apply assignments from plan" call |
| 7 | **Sends invites** | **partial** | Teachers, class teachers, co-admins, early-adopter schools: single-use hashed tokens, expiry, resend, revoke | **Parents and students imported have no path in** — random password, `is_reset = 1`, generated `.local` email, reset-by-email only (`docs/product/parent-student-access.md`, #1293, not built) |
| 8 | **Runs post-import checks** | **missing** | The runbook's step 8 is a careful human checklist (counts, learners per class and stream, KLS numbers, a class head's register, one parent link end to end, phone format) | No code performs any of it. `OnboardingStepsService::isStepComplete` answers "does any row exist", not "do the counts match the workbook" |
| 9 | **Never writes without the admin confirming** | **partial** | Per-tool confirm + post-write verify (`ConfirmsBeforeWrite`, `VerifiableTool`); superadmin MCP approval gate | No plan-level confirmation; the wizard writes each step on Next with no preview/diff; imports and CLI write with no gate at all |
| 10 | **No exams or SchoolPay during onboarding** | **conflict** | Playbook says they are out (`:5`, `:553`) | Toshi v1 still asks both (`:6112`, `:6322`); nothing enforces the rule; `school_pay_api_password` is written in `commitAll()` create mode (`:6890`) |
| 11 | **Stops if the DPA is not confirmed** | **partial** | Only `ChecklistWorkbookConverter::checkConfirmations()` `:200` (PR #1291) reads a DPA answer and refuses | No DPA record anywhere else; signup has only a T&Cs checkbox (`resources/views/auth/register.blade.php:263`); no gate on any write path |

**The shape of the gap.** Every individual write an agent needs already exists as a method on `OnboardingEngine`, and every question it needs to ask already exists as a `Step` + `StepSchema`. What does not exist is the **middle**: a validated *plan* (workbook → ordered rows + issues), a *review* of that plan against the school's current state, and a *confirmation* that covers the plan rather than one tool call. That middle is the build, not the writes.

---

## 4. A proposed build plan

**Guiding decision:** one write path (`OnboardingEngine`), one step contract (`StepRegistry` + `OnboardingStepsService`), one review surface. The agent is a client of that, not a fourth implementation.

### Phase 0 — Fix the truth (0.5 day, no code)

Decide and write down: the wizard is the setup surface; Toshi v1 onboarding is demo-only; exams and SchoolPay are out of onboarding; Uganda defaults are soft-launch, not schema. Update `playbook.md` and `knowledge.md`.

**Exit:** one short decision note, agreed by Rasta.

### Phase 1 — Land the groundwork (1–2 days)

Merge, in this order: **#1285** (or consciously defer it and rebase everything on main), then **#1286**, **#1287**, **#1291** (after #1286), **#1296**, **#1293** as docs. Close the eight Bariyo123 PRs that #1285 already contains. Rebase **#1295** after #1285.

**Reuse:** everything already written. **Retire:** the duplicate PRs, stale branches. **Size:** review and merge only. **Risk:** #1285 is 663 files; if it lands mid-build, every onboarding file moves. Decide before Phase 2.

### Phase 2 — Workbook intake in the product (3–4 days, ~600–900 lines)

Put the converter behind an application surface: `POST /admin/onboarding/checklist` accepts `.xlsx`, runs `ChecklistWorkbookConverter`, stores the result as a structured `OnboardingPlan` (JSON: steps, rows, flags, counts), and renders `report.md`'s content as a table with tab and row. No database writes. Download the importer CSVs from the same page.

**Reuse:** converter, report, runbook steps 1–3. **New:** one controller, one Livewire page, one migration for the plan record (or a JSON column on `onboarding_sessions`). **Exit test:** a deliberately broken workbook produces the same flags as the CLI, with the same tab/row.

### Phase 3 — Plan, preview, apply with confirmation (6–8 days, ~1,200 lines)

A `OnboardingPlanApplier` that, per step, in runbook order: dry-runs against current DB state (what exists, what would be created, what conflicts), shows a diff card ("14 classes, 3 streams, 42 subjects, 18 teachers, 240 learners, 61 parents"), and writes only on Confirm. One transaction per step, and a work log of what was written. Reuse `OnboardingEngine` for every write and `StepRegistry` for step order and validation.

**Reuse:** `OnboardingEngine`, `StepRegistry`, `StepIssueCatalog` codes, `ConfirmsBeforeWrite` payload shape. **Exit test:** applying the same plan twice creates nothing the second time (idempotency), and a rejected confirm writes nothing.

### Phase 4 — People: invites and first sign-in (4–6 days)

Implement #1293's `AccountInviteLinkService` for **existing** users: single-use hashed token, `/invite/account/{token}`, claim sets the password and `is_reset = 0`; email, WhatsApp or admin hand-over. Then send invites after the people import: teachers and class teachers (exists), parents and students (new).

**Reuse:** `TeacherInviteLinkService` pattern, `SchoolInviteService` token handling. **Risk:** WhatsApp template approval; a hand-over link is the fallback. **Exit test:** an imported parent with no real email can set a password from a link an admin generated, and the link dies after one use.

### Phase 5 — Post-import checks as code (2–3 days)

`OnboardingChecker`: expected counts from the plan vs actual in DB; learners per class and stream; every learner has a KLS number; stored phones parse; class head sees every stream on the register. Returns a pass/fail report with expected vs actual. **Reuse:** runbook step 8 as the spec; `OnboardingStepsService::countActiveStudents`. **Exit test:** a deliberately short import fails the counts check with the exact class and stream.

### Phase 6 — The agent surface (5–7 days)

Expose the above as a small, fixed tool set — `onboarding.readWorkbook`, `onboarding.validate`, `onboarding.previewPlan`, `onboarding.applyStep`, `onboarding.check`, `onboarding.invite` — each with the existing confirm gate and post-write verify. The agent asks for missing answers using `StepSchema` options/input types as buttons and forms. The DPA check runs before `previewPlan`, not just before `readWorkbook`.

**Reuse:** `ConfirmsBeforeWrite`, `VerifiableTool`, `TOOL_CLASS_MAP`, `StepSchemaCatalog`. **Retire:** Toshi v1 handlers for exams and school_pay; `AgentToshi::$steps` as a list. **Exit test:** the whole flow on a synthetic workbook on staging, with the agent blocked at the DPA and at every unconfirmed write.

### Phase 7 — Cleanup (1–2 days)

Delete the dead branches, close superseded PRs, remove `dd()`, fold the legacy importers behind the upload service or delete them, update `playbook.md`/`runbook.md` to match what shipped.

**Total:** roughly **22–32 dev-days**, with Phase 3 the only large one. The order is forced: you cannot preview a plan before you can build one (2→3), cannot invite people who do not exist (3→4), cannot check counts you have not defined (3→5), and cannot give an agent tools that do not exist (5→6).

### Risks

1. **#1285.** 663 files, 215 commits, contains 39 open PRs. Merging it is the biggest single schedule risk and it touches 60+ onboarding paths (code, views, tests). Decide first.
2. **Uganda hard-coding.** 56 `FLAGGED(product-decision)` markers, plus #1246's Uganda default, plus SchoolPay. A global product cannot ship an agent whose defaults are one country's.
3. **Two truths about steps.** Three step lists; only one is guarded. An agent built on the wrong list will ask for exams.
4. **Import reporting.** `dd()`, silent skips and count-only reports mean "validated" can still mean "half imported". Phase 2 must not trust the legacy importers.
5. **Data protection.** The workbook is real children's data. Storage, retention and who may read it are unresolved; the DPA gate exists in one command only.
6. **First sign-in.** Without Phase 4, "we sent invites" is a promise the product cannot keep for parents and students.
7. **Verification debt.** No browser or DB verification was possible in this audit; every "working\*" needs a real click-through before it is trusted in a plan.
8. **Plan-level confirmation is new.** Per-tool confirm does not scale to 240 learners; the bulk-confirm design needs a product decision, not just code.

### Open questions for Rasta

1. Is **#1285** merging on 9 Nov, or is it a rehearsal to be abandoned? Everything else sequences off this answer.
2. Is the agent the **primary** surface, or a back-office operator whose work the admin reviews in the wizard? (This decides whether Phase 6 is a chat or a job runner.)
3. Do we **retire Toshi v1 onboarding** for real schools now, or keep it for demos only?
4. Which **curricula and countries** must the workbook support at launch — UNEB only, or the global list?
5. Where does the workbook **live**: upload per run, or read directly from the school's Google Sheet?
6. What is the **confirmation unit** — per phase, per step, per 50 rows? And who may confirm: school admin only, or also a KlassApp operator?
7. Where is the **DPA confirmation recorded** (workbook tab only, or a signed record in the product), and who audits it?
8. Are **exams and SchoolPay** permanently out of onboarding, or out only until a later phase? If out, we delete the steps.
9. What is the **target size** per school (learners, teachers) and the acceptable time for one onboarding, so Phase 2's chunking and Phase 3's transactions are sized correctly?
10. For imported parents with **no email and no WhatsApp**, is admin hand-over of a link acceptable as the default?

---

## Appendix A — Onboarding-relevant PRs

| PR | Author | Base | State | Onboarding relevance |
| --- | --- | --- | --- | --- |
| #1285 | Mucunguzi256 | main | draft, DO NOT MERGE | Integration rehearsal; contains 39 open PR tips, 60+ onboarding paths |
| #1296 | Mucunguzi256 | main | draft | Workbook runbook |
| #1295 | Mucunguzi256 | main | draft | Name-route guards; **is #1285 + 10 files** |
| #1294 | Mucunguzi256 | main | draft | Student ID format design |
| #1293 | Mucunguzi256 | main | draft | Parent/student first sign-in design |
| #1292 | Mucunguzi256 | main | draft | Name-addressed route audit |
| #1291 | Mucunguzi256 | **t077** | draft | `checklist:convert` |
| #1290 | Mucunguzi256 | main | draft | Parent/child linking design |
| #1289 | Mucunguzi256 | main | draft | Assign classes design |
| #1288 | Mucunguzi256 | main | draft | Subjects and teachers design |
| #1287 | Mucunguzi256 | main | draft | `names:normalize` |
| #1286 | Mucunguzi256 | main | draft | `phones:to-e164` |
| #1256 | Bariyo123 | main | draft, NEEDS REVIEW | Unfinished admins → setup after signup/login; **inside #1285** |
| #1252 | Bariyo123 | main | draft | T-060 wide IDs + failed import rows; **inside #1285** |
| #1251 | Bariyo123 | t064 | draft | T-065 international phones; **inside #1285** |
| #1250 | Bariyo123 | main | draft | T-061 import entry points; **inside #1285** |
| #1249 | Bariyo123 | main | draft | T-064 country codes; **inside #1285** |
| #1248 | Bariyo123 | main | draft | T-070 remove global branding from school admins; **inside #1285** |
| #1246 | Bariyo123 | t064 | draft | Default onboarding country to Uganda; **inside #1285** |
| #1238 | Mucunguzi256 | — | closed unmerged | Marks Phase 1; superseded by #1239/#1262 |
| #1109, #1108 | Mucunguzi256 | — | closed unmerged | WIP handovers, abandoned |

## Appendix B — Branch inventory (onboarding)

| Branch | Ahead of main | Content in main? | Read |
| --- | --- | --- | --- |
| `feature/onboarding-step-buttons` | 7 | **yes** (0-file diff) | retire |
| `feat/wizard-structure-class-teacher` | — | **yes** (0-file diff) | retire |
| `co-admin-invite-links` | 0 | merged | retire |
| `fix/toshi-onboarding-default` | 0 | merged | retire |
| `feat/wizard-from-step-registry` | 1 | no (20 files) | likely superseded by `StepRegistry` on main; verify before deleting |
| `fix/toshi-complete-mode-four-findings` | 2 | no (7 files) | stale; the engine delegation superseded it |
| `fix/toshi-skip-continue-and-plan-parity` | 1 | no (4 files) | stale |
| `fix/toshi-checklist-shared-source` | 2 | no (6 files) | stale; checklist now reads `OnboardingStepsService` |
| `cursor/hide-toshi-path-real-schools-472f` | 5 | no (36 files) | superseded by `ToshiOnboardingPath` |
| `spike/toshi-mcp-slack` | 1 | no | spike; only useful for the MCP client |

## Appendix C — Commands used (all read-only)

```bash
git log --diff-filter=A --format='%an|%ad' --date=short -1 -- <path>
git log -1 --format='%an|%ad' --date=short -- <path>
git merge-base --is-ancestor origin/<branch> origin/integration/monday-2026-11-09
git diff --name-only origin/main...origin/<branch>
git diff --stat origin/main...origin/<branch>
gh pr list --state open --json number,title,author,headRefName,baseRefName,mergeable
gh pr diff <n> --name-only
gh issue view 793
grep -rn "FLAGGED(product-decision)" app | wc -l
```

## Appendix D — Files read for this audit

`app/Services/OnboardingStepsService.php`, `app/Services/OnboardingEngine.php`, `app/Services/FinishSchoolOnboarding.php`, `app/Services/Onboarding/OnboardingUploadService.php`, `app/Services/OnboardingNameListExtractor.php`, `app/Services/TeacherInviteLinkService.php`, `app/Services/ClassTeacherInviteService.php`, `app/Services/SchoolInviteService.php`, `app/Services/Toshi/ToshiOnboardingPath.php`, `app/Services/Toshi/ToshiUiSwitch.php`, `app/Services/Toshi/McpWriteGate.php`, `app/Services/ToshiActionService.php`, `app/Livewire/AgentToshi.php`, `app/Livewire/ManualOnboardingWizard.php`, `app/Onboarding/Steps/*` (registry, driver, schemas, 16 steps), `app/Imports/UsersImport.php`, `app/Imports/TeachersImport.php`, `app/Http/Controllers/Admin/ImportMemberController.php`, `app/Http/Controllers/Admin/TeacherImportExportController.php`, `app/Http/Controllers/Admin/TeacherLinkImportController.php`, `app/Http/Controllers/Admin/ClassTeacherInviteController.php`, `app/Http/Controllers/OnboardingChoiceController.php`, `app/Http/Middleware/SendRealSchoolToshiOnboardingToWizard.php`, `app/AiAgents/Concerns/{AuthorizesToshiAction,ConfirmsBeforeWrite}.php`, `app/Ai/Tools/Toshi/ApprovableMcpTool.php`, `app/Mcp/**`, `routes/web.php`, `routes/admin.php`, `config/toshi.php`, `config/invites.php`, `AGENTS.md`, `docs/onboarding/playbook.md`, `docs/onboarding-engine-plan.md`, `docs/onboarding-defaults-skill.md`, `docs/proposals/2026-10-03-onboarding-steps.md`, `docs/product/student-import.md`, and the heads of PRs #1285, #1286, #1287, #1291, #1293, #1295, #1296.
