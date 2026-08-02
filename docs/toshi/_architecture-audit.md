# Toshi Architecture Audit

> Generated: Jul 28, 2026
> Source of truth for status badges in public docs. Every claim verified against codebase.
> **Do not publish in nav** — internal reference only.

---

## 1. Channel Reach (Where Toshi runs today)

**STATUS: Dashboard-only (Livewire). No WhatsApp/Slack/Telegram access.**

Toshi is a Livewire component (`App\Livewire\AgentToshi`, 5,166 lines) rendered inside the school admin dashboard (`layouts/main`). The Blade view at `resources/views/livewire/agent-toshi.blade.php` is a chat-panel UI embedded in the admin shell.

The `whatsappPhone` / `whatsappVerified` / `whatsapp_verify` properties in AgentToshi are **not** about Toshi being accessible via WhatsApp. They are about configuring the school's WhatsApp Business number for outbound parent notifications (grades, fees, attendance reports). This is a school setup step Toshi walks admins through during onboarding.

**Implication for docs:**
- `toshi/getting-started.md` must say "in your KlassApp dashboard" — not "message Toshi on WhatsApp"
- Channel layer (per §0: channel/brain/body model) is **currently web-dashboard-only**.
- WhatsApp/Slack/Telegram as channels for Toshi itself = `planned` status.
- The existing WhatsApp pipeline (Evolution API, WhatsAppController, etc.) is a *separate outbound notification system* for parent-facing communications — Toshi does not plug into it today.

---

## 2. Architecture — Three-Layer Model

### Layer 1: Channel (Livewire Component)

| File | Lines | Role |
|---|---|---|
| `app/Livewire/AgentToshi.php` | 5,166 | Chat UI, state management, tool confirmation UI, streaming display |
| `resources/views/livewire/agent-toshi.blade.php` | ~300 | Blade template with Alpine.js for interactivity |
| `resources/assets/css/toshi-ui.css` | — | Custom styling (position:fixed panel) |

Key capabilities:
- **Streaming**: Uses `Laravel\Ai\Agent::stream()` with Livewire's `$this->stream()` for real-time token-by-token display
- **Tier 2 confirmation**: Write tools return `__tier2_confirm` JSON → AgentToshi shows a confirmation card → user clicks Yes/No → on Yes, re-runs with `bypassConfirm=true`
- **ToshiPersona**: Per-user preference memory (`summary`, `traits`, `interaction_count`) — persists between sessions
- **Plan execution**: `executeNextPlanStep()` for multi-step plans Toshi generates
- **Mode system**: Toshi has different modes (visible in the mode dropdown partial)

**Status: `live`** — fully shipped.

### Layer 2: Brain (ToshiOrchestrator)

| File | Lines | Role |
|---|---|---|
| `app/AiAgents/ToshiOrchestrator.php` | 185 | Query classifier — routes to 1 of 6 skill agents |
| `config/toshi.php` | — | Config: model, per-school gate, SDK v2 toggle |

The orchestrator:
- Implements `Laravel\Ai\Contracts\Agent` + `HasTools`
- Uses the `laravel/ai` Promptable trait for instruction generation
- Builds per-school context via `buildSchoolContext()` + geographic awareness via `getSchoolCountryLabel()`
- Classifies user query into EXACTLY ONE domain and routes using 6 `RouteTo*SkillTool` classes
- Max 3 steps (`#[MaxSteps(3)]`), 120s timeout (`#[Timeout(120)]`)
- Model: `nvidia/llama-3.3-nemotron-super-49b-v1` via `openai-compatible` provider

**Status: `live`** — fully shipped.

### Layer 3: Body (Skills + Tools)

**6 skill agents** (in `app/AiAgents/Skills/`):

| Skill | File | Owned Tools | Description |
|---|---|---|---|
| StudentSkill | `StudentSkill.php` | AddStudent, FindStudent, GetStudentCount, RecordAttendance, RecordBulkAttendance, EnterMark, AddParent (7) | Student CRUD, attendance, marks, parents |
| TeacherSkill | `TeacherSkill.php` | AddTeacher, AssignTeacher, ListTeachers (3 + ability to promote to co-admin) | Teacher management |
| AcademicSkill | `AcademicSkill.php` | ListClasses, ListSections, CreateTerm, CreateSubject, CreateExam, CreateStream, AssignStudentsToStream (7) | Classes, terms, subjects, exams |
| FeeSkill | `FeeSkill.php` | CreateFee, RecordPayment, GetFeeBalance (3) | Fee categories, payments |
| GradingSkill | `GradingSkill.php` | SetGradingScale, SeedDefaultGrading, ViewGradingScale (3) | Grading scales |
| ReportingSkill | `ReportingSkill.php` | GenerateReport (1) | Academic/fee/attendance reports |

**32 total tool files** (26 action + 6 route):

Route tools (owned by orchestrator, not skills):
- `RouteToStudentSkillTool`, `RouteToTeacherSkillTool`, `RouteToAcademicSkillTool`, `RouteToFeeSkillTool`, `RouteToGradingSkillTool`, `RouteToReportingSkillTool`

**Status: `live`** — all 32 tools shipped.

---

## 3. Security & Audit Model

### Authorization (`AuthorizesToshiAction`)

- Trait used by tool classes
- `authorizeSchoolAction()` — gates via Laravel `Gate::inspect('toshi-school-action')`
- Validates the user belongs to a school and the target record's `school_id` matches
- `authorizeOrMessage()` — convenience wrapper returning error strings (prefixed `❌`) for tool handle() methods
- `resolveToshiUser()` — resolves effective user, supporting Superadmin-impersonating-SchoolAdmin

**Status: `live`**

### Confirmation Gate (`ConfirmsBeforeWrite`)

- Write tools use `confirmOrExecute()` to check `ToshiActionService::$bypassConfirm`
- If `false` (default), returns `__tier2_confirm` JSON payload → AgentToshi shows confirmation card
- If `true` (second pass after user confirms), executes write directly
- Side-channel via `ToshiActionService::$pendingConfirmPayload` for SDK v2 path

**Status: `live`**

### Pre-Execution Guards (`HasPreExecutionGuards`)

- `runGuards()` uses reflection to find all `guard*()` methods on the tool class
- Returns first error or null (all pass)
- Called before `confirmOrExecute()` in tool handle() methods

**Status: `live`**

### Audit Logging (`ToshiAuditService`)

- Single wrapper around `activity_log` table with `log_name = 'toshi'`
- Three log paths: `logExecution()` (success/failure), `logCancellation()` (user cancelled)
- Logs tool name, arguments, status (success/failed/cancelled), result string
- Admin UI: `ToshiActivityController@index` — paginated activity view filtered by tool/status

**Status: `live`**

---

## 4. Laravel AI SDK Usage

| Feature | Used? | Details |
|---|---|---|
| Agent interface | ✅ | `ToshiOrchestrator` implements `Agent` |
| HasTools interface | ✅ | Both orchestrator and skills implement `HasTools` |
| Promptable trait | ✅ | Used by orchestrator + all 6 skill agents |
| #[MaxSteps] | ✅ | Orchestrator: 3, Skills: 5 each |
| #[Timeout] | ✅ | Orchestrator: 120s |
| #[Model] | ✅ | Via config, not attribute |
| Agent::run() | ✅ | Synchronous execution |
| Agent::stream() | ✅ | Token-by-token streaming to Livewire |
| TextDelta | ✅ | Used in streaming response handling |
| v0 package | `laravel/ai ^0.9.0` | Not yet v1 stable |

**SDK v2 path** (`ToshiSdkV2Service`):
- Programmatic interface to ToshiOrchestrator
- Gated behind `config('toshi.sdk_v2_enabled', false)` — **disabled by default**
- Per-school gate (`toshi_per_school_gate`): checks `school.toshi_enabled` flag
- Requires `ai.providers.openai-compatible.key` to be configured

**Status: `partial`** — SDK exists but disabled by default. Not yet ready for external developers.

---

## 5. What's NOT Yet Built (Planned Features)

| Feature | Status |
|---|---|
| Toshi accessible via WhatsApp | `planned` — Toshi is currently dashboard-only |
| Toshi accessible via Telegram/Slack | `planned` — no code exists |
| Toshi SDK for external developers | `planned` — SDK v2 exists but is internal-only, disabled by default |
| Multi-step plan execution in dashboard | `live` — executeNextPlanStep() works |
| Toshi persona persistence | `live` — ToshiPersona model with summary/traits |
| Tool confirmation cards | `live` — Tier 2 confirmation in dashboard |
| Audit trail UI | `live` — `/admin/toshi-activity` route |

---

## 6. Actionable Corrections to Original Spec

| Spec Claim | Actual | Correction Needed |
|---|---|---|
| 6 skills (spec §0 said "6 skills") | ✅ Confirmed — 6 exactly | None |
| 32 tools (spec said "32") | ✅ Confirmed — 32 tool files | None |
| Toshi reaches WhatsApp (spec §0: "channel layer — WhatsApp (live)") | ❌ Dashboard-only | Change to `planned`. WhatsApp is for parent outbound notifications, not Toshi. |
| SDK v2 for developers (spec mentioned) | ⚠️ Exists but disabled | Document as `partial` — internal only, not developer-facing yet |
| "SMS fallback" — exists? (spec flagged unverified) | ⚠️ `CheckSms.php` command exists | Needs verification it actually works end-to-end |
| "Email notifications" — exists? | ✅ Laravel Notification classes exist (NewMessage, Birthday, Device, Teacher) | Works via standard Laravel notification system |

## Backlog

- `toshi_personas` unused scaffolding — fate separate
