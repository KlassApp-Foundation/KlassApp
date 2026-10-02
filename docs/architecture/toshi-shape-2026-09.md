# Toshi shape (code audit, 2026-09 / verified 2026-10-02)

> **Read-only map of Toshi as it exists in the codebase today.**  
> Verified against `origin/main` tip at audit time + live staging Cloud config.  
> Not a redesign. Future work (including an eventual Drive onboarding helper) should design against this shape.

**Related:** short bridge page [`docs/architecture.md`](../architecture.md); connector plans under [`docs/plans/`](../plans/); Slack go-live [`docs/ops/slack-connector-go-live-checklist.md`](../ops/slack-connector-go-live-checklist.md).

**Naming note:** There was no `docs/architecture/` directory before this file — only the bridge `docs/architecture.md`. Dated audit/triage docs elsewhere use `YYYY-MM-DD` (e.g. `test-suite-triage-2026-09-26.md`). This file uses a month stamp because it describes a durable shape, not a single-day incident.

---

## 1. The agent core

### 1.1 `laravel/ai` version and contracts actually used

| Field | Value (from `composer.lock`) |
|---|---|
| Package | `laravel/ai` |
| Version | **`v0.10.2`** |
| Constraint in `composer.json` | `^0.10` |

**Contracts / traits Toshi code actually uses:**

| Surface | Who uses it | Where |
|---|---|---|
| `Conversational` + `RemembersConversations` | **`SlackSkill`**, **`PlatformOperationsAgent`** | `app/AiAgents/Skills/SlackSkill.php`; `app/Ai/Agents/PlatformOperationsAgent.php` |
| `Approvable` + `InteractsWithApprovals` | **`ApprovableMcpTool`** + Superadmin tools (`ApproveSubscriptionTool`, `CancelSubscriptionTool`, `CreatePlanTool`, `UpdatePlanTool`, `DeleteCoAdminTool`, `ResetCoAdminPasswordTool`, `ImpersonateSchoolAdminTool`, `ToggleSchoolFeatureTool`, `UpdateSystemSettingsTool`) | `app/Ai/Tools/Toshi/ApprovableMcpTool.php`; `app/Ai/Tools/Superadmin/*` |
| `Agent` + `HasTools` + `Promptable` | Orchestrator, all school Skills, route tools' nested skills | `app/AiAgents/*` |

**Not used by school Skills today:** ordinary school Skills (`StudentSkill`, `FeeSkill`, etc.) do **not** implement `Conversational` / `RemembersConversations`. Their writes use the Tier-2 `ConfirmsBeforeWrite` path, not native Approvable pause/resume.

Do not confuse laravel/ai `Approvable` with the domain inbox contract `App\Models\Contracts\Approvable` (lesson plans, leaves, etc.).

### 1.2 Concrete request flow (school-admin → Skill → tool)

Entry is Livewire, not a dedicated HTTP API for chat:

```
AgentToshi::send()
  → handleAssistantQuery($text)          # assistant mode
    → ToshiSdkV2Service::ask(...)        # when toshi.sdk_v2_enabled
      → new ToshiOrchestrator            # default school-admin arm
        → Orchestrator LLM picks one RouteTo*SkillTool
          → RouteTo*SkillTool::handle()
            → (new *Skill)->prompt($query)
              → Skill LLM picks a leaf Tool
                → Tool::handle()
```

**Traced example — “add a student named John in Primary Four”:**

1. `app/Livewire/AgentToshi.php` — `send()` → `handleAssistantQuery`
2. `app/AiAgents/ToshiSdkV2Service.php` — `ask()` constructs `ToshiOrchestrator` for the default school-admin scope
3. `app/AiAgents/ToshiOrchestrator.php` — `instructions()` require routing to exactly one domain; `tools()` returns nine `RouteTo*SkillTool` instances (student…slack). Orchestrator itself is **not** Conversational.
4. `app/AiAgents/Tools/RouteToStudentSkillTool.php` — auth gate (`AuthorizesToshiAction`) → `StudentSkill::prompt($query)->text`
5. `app/AiAgents/Skills/StudentSkill.php` — leaf tools include `AddStudentTool`
6. `AddStudentTool::handle` — Tier-2 `ConfirmsBeforeWrite` → `ToshiActionService::addStudent` (or pause for panel confirm)

**Slack variant of the same spine** (outbound connector tools):

```
… → RouteToSlackSkillTool
      → (new SlackSkill)->forUser($user)->prompt($query)
        → ApprovableMcpTool::wrap('slack', $primitive)
          → (read) execute + audit
          → (write) pause → mcp_resume side-channel → AgentToshi confirm card
```

Teacher path diverges earlier: usergroup 5 → `TeacherOperationsAgent` → `RouteToTeacherTeachingOpsSkillTool` → `TeacherTeachingOpsSkill` (not the Orchestrator).

### 1.3 Skills as a pattern

**Skills on `main` today** (`app/AiAgents/Skills/`):

| Skill | Conversational? | UsesToshiLlm? | Invoked by |
|---|---|---|---|
| `StudentSkill`, `TeacherSkill`, `AcademicSkill`, `FeeSkill`, `GradingSkill`, `ReportingSkill` | No | No (historical gap) | Matching `RouteTo*SkillTool` on Orchestrator |
| `SchoolCommsSkill`, `SchoolAcademicsOpsSkill` | No | Yes | Orchestrator |
| `TeacherTeachingOpsSkill` | No | Yes | `TeacherOperationsAgent` |
| **`SlackSkill`** | **Yes** (+ `RemembersConversations`) | Yes | `RouteToSlackSkillTool` |

**Common across Skills:**

- `Agent` + `HasTools` + `Promptable` + `#[MaxSteps(5)]`
- `instructions()` + `tools()` returning concrete Tool instances
- Invoked via a thin **`RouteTo*SkillTool`** (auth → nested `prompt()`), **not** by returning the Skill from Orchestrator `tools()` for SDK Sub-Agent wrapping (`CanActAsTool` is deliberately avoided — see comments on `SchoolCommsSkill` / `RouteToSchoolCommsSkillTool`)

**What varies:**

- Whether the Skill calls `prompt()` itself → prefer `UsesToshiLlm` (SchoolComms lesson; older Skills omit it)
- Whether writes need **native** HITL pause → `Conversational` + `RemembersConversations` (Slack + Platform ops only today)
- Leaf confirmation style: Tier-2 `ConfirmsBeforeWrite` (school domain tools) vs `ApprovableMcpTool` (MCP writes)

**Gotcha (explicit — Slack discovered this):**  
Vendor `GeneratesText::throwIfNotResumable` throws `ApprovalNotResumableException` if the agent is **not** `Conversational`. Any Skill that exposes `Approvable` tools (MCP writes) **must** implement `Conversational` + `RemembersConversations`. Documented in `SlackSkill` docblock and handled defensively in `RouteToSlackSkillTool`. Ordinary school Skills that use Tier-2 confirm cards do **not** need this.

**`config/toshi-skills.php`:** lists six older domains. **Runtime does not read it** — Orchestrator hardcodes tools in `tools()`. Treat the config file as stale documentation relative to the live nine route tools.

**Google Classroom Skill:** **not on `main`.** Exists only on open PR [#728](https://github.com/KlassApp-Foundation/KlassApp/pull/728) (`feat/google-classroom-wave1`) — see §3 inventory.

**Checklist — new Skill that fits the pattern:**

1. `app/AiAgents/Skills/YourSkill.php` — `Agent` + `HasTools` + `Promptable` (+ `UsesToshiLlm` if it calls `prompt()`)
2. Matching `RouteToYourSkillTool` with auth + `query` schema + nested `prompt()`
3. Register on `ToshiOrchestrator::tools()` (and/or the correct role `*OperationsAgent`) and update orchestrator instructions
4. If native Approvable pause is required: also `Conversational` + `RemembersConversations` + a resume side-channel (Slack/`mcp_resume` precedent)
5. Do **not** implement `CanActAsTool` unless intentionally changing architecture

---

## 2. The safety layer

### 2.1 `McpWriteGate` — how to add a connector safely

**Config lives in `config/toshi.php` under two sibling keys:**

| Key | Holds |
|---|---|
| `toshi.mcp_connectors.{name}` | Catalog: endpoint, OAuth, **`read_tools`**, **`write_tools`**, `enabled`, `default_write_mode`, … |
| `toshi.mcp_write_gates` | `master_switch` + per-connector **`mode` only** |

`read_tools` / `write_tools` are **not** under `mcp_write_gates`.

**Classification (`McpWriteGate::isWrite`) when master switch is ON:**

| Mode | Semantics |
|---|---|
| `deny` (default for new / Slack env default) | **Every** tool is a write — fail closed |
| `classify` | Name in `read_tools` → read; **everything else** (incl. unknown names) → write |
| `allowlist` | Name in `write_tools` → write; **everything else free** (incl. unknown) |

**Fail-closed when master switch ON:** unknown connector, missing catalog, or invalid mode → treat as write. `assertExecutable` throws unless inside `bypassFor`.

**Fail-open when master switch OFF** (code default `TOSHI_MCP_WRITE_GATES_ENABLED=false`): `isWrite` always returns `false`. Ops checklist warns: enabling a connector without the master switch means unapproved writes. Staging currently has the master switch **on** (verified Cloud env 2026-10-02).

**How-to — add a connector safely:**

1. Add `mcp_connectors.{name}` with exhaustive `read_tools` / prefer empty `write_tools` until reviewed; `enabled` behind env default `false`
2. Add `mcp_write_gates.connectors.{name}` with `mode` starting at **`deny`**, then graduate to **`classify`** once `read_tools` is trusted; use **`allowlist` only** when you intentionally want non-listed tools free
3. Register named client in `routes/ai.php` (only legal construction site — `McpClientConstructionTest`)
4. Skill exposes tools **only** via `ApprovableMcpTool::wrap('{name}', $primitive)` — never raw primitives
5. Turn **`TOSHI_MCP_WRITE_GATES_ENABLED=true` before** enabling the connector channel flag
6. Cover classify cases + defense-in-depth raw `callTool` throw + HITL pause/resume in tests

### 2.2 `ApprovableMcpTool` — wrap, pause, resume

- Implements `Tool` + `Approvable` (uses `InteractsWithApprovals`); holds an inner laravel/ai `McpTool`
- `needsApproval`: if `McpWriteGate::isWrite(client, rawToolName)` → `Approval::required(...)`; else `false`. Strips `mcp_tools_` prefix so catalog names match.
- `handle()` always runs under `McpWriteGate::bypassFor(...)` — **the only legal write path** past `assertExecutable`

**Pause cycle (concrete):**

1. LLM emits tool call → vendor loop sees Approvable + approval required → **pauses** (no MCP call yet)
2. State written: `agent_conversation_messages.approval_state` JSON `{ pending: { toolCallId: reason } }` plus `tool_calls` on that message — **no dedicated approvals table**
3. HITL listeners write `activity_log` (`log_name=toshi`) with `status=pending_approval`
4. School panel path: `RouteToSlackSkillTool` copies pause into `ToshiActionService::$pendingConfirmPayload` with `mcp_resume` (`agent_class`, `conversation_id`, `approval_id`) and returns `__tier2_confirm`-shaped JSON for the existing confirm card
5. Resume: `AgentToshi::confirmYes` / `confirmNo` → `resumeMcpApproval` → `continue($conversationId)->prompt(Decisions::approve|reject)` → loop calls `handle()` → `bypassFor` → audited `callTool`
6. Platform ops UI: `PlatformApprovalGate` Livewire uses the same Decisions API; WhatsApp: `WhatsAppConfirmationBridge` can resume Approvable via token (`ty_`/`tn_`)

**UI must surface:** pending confirm card in `agent-toshi` (school), platform approval gate (superadmin), and (for WA) interactive buttons / typed YES|NO token.

### 2.3 Auditing — guarantee (including bypass)

| Piece | Role |
|---|---|
| `AuditingMcpClientManager` | Bound as `ClientManager` in `AppServiceProvider` — every named `build()` wraps with auditing client |
| `AuditsMcpToolCalls` | `assertExecutable` then `parent::callTool` then **always** `ToshiMcpCallAuditor::audit` |
| `ToshiMcpCallAuditor` | Writes via `ToshiAuditService::logExecution` → **`activity_log`** (`log_name=toshi`) |

**Confirmed still true (defense-in-depth code + `McpWriteGateDefenseInDepthTest`):** `bypassFor` only disables `assertExecutable`; the audit call after `parent::callTool` is unconditional. Bypass context still produces an audit row.

Caveats: auditor no-ops without an acting user; direct `Client::web()` / `Client::local()` outside `routes/ai.php` bypasses the manager (banned by architecture test). Master switch off disables **gating**, not auditing.

### 2.4 Two separate “remember progress” mechanisms

These answer **different questions**. Do not merge them.

| | **(A) Approvable pause-for-permission** | **(B) Onboarding commit-per-step** |
|---|---|---|
| Question | “May this side-effecting tool run?” | “Is this school setup field durable?” |
| State | `agent_conversation_messages.approval_state` (+ optional `mcp_resume` / WA token) | Domain tables via `OnboardingEngine` / wizard `persistCurrentStep` / Toshi selective immediate persist + `commitAll` for drafts |
| Resume | `continue(id)->prompt(Decisions::…)` | Next incomplete step from DB / session — **not** Decisions |
| When write happens | **Only after** human approve | **On step continue/confirm** (manual wizard every Continue; Toshi identity confirms often immediate) |
| Use for | MCP writes, platform Superadmin Approvable tools | School onboarding / setup progress |

**Rule for new work:**

- External or irreversible **tool side effects** that need a human gate → **(A) Approvable** (and Conversational Skill if nested prompt + pause).
- **School configuration progress** that must survive refresh / resume onboarding → **(B) commit-per-step** into domain tables via `OnboardingEngine`.
- School domain tools that already use Tier-2 `ConfirmsBeforeWrite` stay on that path until an intentional HITL convergence PR moves them — do not invent a third shared layer (see `docs/plans/toshi-hitl-convergence-and-slack-connector-plan.md`).

---

## 3. The connector registry

### 3.1 `school_mcp_connectors`

**Schema** (`database/migrations/2026_09_18_172858_create_school_mcp_connectors_table.php`):

`school_id` (FK cascade), `connector_type`, `external_team_id` / `external_team_name`, encrypted `credentials` (text), `token_expires_at`, `auth_mode` (default `oauth_remote`), `status` (default `active`), `trust_level` (default `first_party_catalog`), `write_mode` (default `deny`), JSON allow/deny lists, `connected_by`, `last_used_at` / `last_refreshed_at`. Unique `(school_id, connector_type, external_team_id)`.

**`SchoolMcpConnector::resolveTokenForRequest($connectorType, ?$schoolId)`:**

1. Resolve school from arg or `auth()->user()->school_id`; none → `null`
2. Query `active` + type + school → first row; missing → `null`
3. If expired → `McpConnectorTokenRefreshService::refresh`; failure → `null`
4. Bump `last_used_at`; return `accessToken()` from encrypted credentials

**Isolation guarantee:** every resolve path filters by `school_id`. Proven by `tests/Feature/Toshi/SchoolMcpConnectorIsolationTest.php` (school A token ≠ school B; disabled/unconnected → null; type isolation).

### 3.2 Token-closure registration (`routes/ai.php`) — checklist for a new connector PR

**Today on `main`:** only **Slack** is registered.

- **Mock:** `Mcp::local('spike-slack-mock', …)` + `Client::local(artisan mcp:start …)` — no per-school token
- **Live:** `Client::web(url)->withOAuth(...)->withToken(fn () => SchoolMcpConnector::resolveTokenForRequest('slack') ?? throw ConnectorNotConnected)` + `Mcp::oAuthRoutesFor('slack', …)` upserting the registry row (`write_mode=deny`)

**New Tier-1 connector PR checklist (Slack precedent; Classroom PR #728 follows the local-MCP variant):**

| # | Requirement | Slack (`main`) | Classroom (#728, not merged) |
|---|---|---|---|
| 1 | Catalog entry `config/toshi.php` → `mcp_connectors.{key}` | Yes | Yes (`write_tools=[]`) |
| 2 | Write-gate key `mcp_write_gates.connectors.{key}` | Yes | Yes |
| 3 | Named client in `routes/ai.php` only | Remote `Client::web` + token closure | Local `Mcp::local` + in-process REST; token via `resolveTokenForRequest` inside tools |
| 4 | OAuth upsert → `school_mcp_connectors` | `Mcp::oAuthRoutesFor` closure | Dedicated OAuth controller (separate GCP client from Google sign-in) |
| 5 | Skill + `RouteTo*SkillTool` (+ Conversational if any writes / for future-proof wrap) | `SlackSkill` | `GoogleClassroomSkill` (on branch) |
| 6 | Integrations UI connect/disconnect | Live | Extend beyond Slack-only |
| 7 | Isolation + OAuth + write-gate tests | Yes | Mirrored on branch |
| 8 | Env gates default off | `TOSHI_SLACK_CHANNEL_ENABLED` | `TOSHI_GOOGLE_CLASSROOM_ENABLED` |

**Catalog entry shape** (from Slack / Notion / Drive stubs):  
`endpoint`, `token_url`, `auth_mode`, `oauth_client_id`, `oauth_secret`, `timeout`, `skill`, `default_write_mode`, `read_tools[]`, `write_tools[]`, `enabled`, `allows_custom_endpoint`.

### 3.3 Connector inventory (code + Current Status — verified 2026-10-02)

| Connector | Status verified this audit |
|---|---|
| **Slack** | **Built on `main`**, live flags on staging (`SLACK_MCP_MODE=live`, `TOSHI_SLACK_CHANNEL_ENABLED=true`, write gates master on). OAuth connect proven against real Slack (2026-09-21). **§6c agent-loop E2E still pending** (checklist not updated after LLM fix — see §5). Outbound tools only. |
| **Google Classroom** | **Not on `main`.** Open PR [#728](https://github.com/KlassApp-Foundation/KlassApp/pull/728) title: “code complete, dormant — NOT end-to-end verified.” knowledge.md Sept 21–25 still says “no Classroom code until LLM + Slack §6c” — **drift**: code exists on the PR branch; prerequisites (a) LLM is now fixed; (b) Slack §6c still unmet. |
| **Google Drive** | Catalog stub + `GoogleDriveConnectorContract` + preview tripwire (**2027-03-18**). knowledge.md / plans: **WAITING FOR GA** (MCP Program Term iv) and REST/`drive.readonly` path rejected. **No Drive Skill or `routes/ai.php` wiring on `main`.** A customer-facing **`drive.file` + Picker** direction has been discussed as a future shape distinct from the rejected MCP-server/readonly path — it is **not yet stamped as a Current Status decision** in `knowledge.md` (see §6). |
| **Notion** | Catalog stub only; **dropped** (no grounded school-ops use case). |
| **Miro / Figma** | Not in catalog; **dropped** (Enterprise/admin OAuth; per-user auth incompatible with school service account). |
| **Community / Tier-2 registry** | Schema has `trust_level`; all v1 entries `allows_custom_endpoint=false`. **Never built** — deferred curated manifests, not raw URLs. |

---

## 4. Channels (separate from connectors)

**Make this distinction sticky:**

| | **Connector** | **Channel** |
|---|---|---|
| Role | School-linked external **tool** surface (MCP / REST wrappers) | User↔Toshi **conversation transport** |
| Creds | Per-school `school_mcp_connectors` | Platform transport creds (e.g. Meta WABA) — not MCP registry rows |
| Entry | Skills via named MCP clients | Livewire / WhatsApp webhook / (future Slack Events) |
| HITL | `ApprovableMcpTool` + write gate | Channel bridge (`WhatsAppConfirmationBridge`) + web confirm cards |

### 4.1 WhatsApp (channel)

1. `POST/GET /api/whatsapp/inbound` → `WhatsAppController@handleInbound` (+ signature verify)
2. Keyword / menu router runs first
3. `WhatsAppConfirmationBridge::handleInbound` for pending confirms (`ty_`/`tn_` buttons or `YES|NO {token}`)
4. Free-form: `WhatsAppToshiChannelService::ask` when `toshi.whatsapp_channel_enabled` **and** `toshi.sdk_v2_enabled`

**Channel-specific:** button/token transport, `whatsapp_pending_confirmations`, `WhatsAppBusinessService` send, phone↔user bind, WA write exclusion allowlist.  
**Generic:** resume engines underneath (`MECHANISM_APPROVABLE` via Decisions; `MECHANISM_TIER2` via `bypassConfirm` re-run).

### 4.2 Slack (connector today — not an inbound channel)

- **Today:** Toshi (invoked from KlassApp web) can call Slack MCP tools — **outbound connector**.
- **Deferred:** inbound Slack→Toshi (Events API) and Slack interactive Approve/Reject blocks — logged in `docs/plans/toshi-hitl-convergence-and-slack-connector-plan.md` and checklist wave-2. Easy to conflate; keep them separate.

### 4.3 New channel vs new connector

| Extension | Precedent | Touches |
|---|---|---|
| **New connector** | Slack / Classroom PR | Catalog + write gate + `routes/ai.php` + OAuth upsert + Skill/RouteTo + Integrations UI + isolation tests. **No** webhook required. |
| **New channel** | WhatsApp | Inbound transport + identity map to `User` + channel ask service + outbound replies + HITL bridge for that transport + availability flags (+ write exclusion if high-risk). **No** `school_mcp_connectors` unless the channel also exposes MCP tools. |

---

## 5. Known gaps and open items (re-verified)

| Item | What Current Status / checklist said | Verified 2026-10-02 |
|---|---|---|
| **Staging LLM config** | Sept 21/28: `OPENAI_COMPATIBLE_URL`/`MODEL` NULL; Sept 28: staging `sdk_v2=TRUE` with empty key | **FIXED.** Staging has `OPENAI_COMPATIBLE_URL` / `MODEL` / `API_KEY` set. `php artisan toshi:llm-health` → **`command.success`**, Result OK (`openai-compatible` / `glm-5.3-flash` / host `opencode.ai`). **knowledge.md Current Status still claims the gap** — documentation drift. |
| **Slack §6c E2E** | Deferred on LLM gap; re-entry = fix LLM then run 5 steps | **Still pending.** LLM blocker cleared; checklist §6c and knowledge stamps were **not** updated to record a completed E2E run. Reads / write-gate pause / approve-reject audit / real-vs-mock shapes still need a live agent-loop pass. |
| **Classroom** | “No code until prerequisites”; later narrative “code complete, dormant” | **Code complete on open PR #728 only** — **not merged to `main`**. E2E not verified. Prerequisite (a) LLM ✅; (b) Slack §6c ❌. |
| **Transport-era tripwire** | Dated re-verification | **`tests/Architecture/McpTransportEraReverificationTest.php` deadline `2027-04-28`**. Marker comment in `routes/ai.php`. Marks incomplete until then. |
| **Drive preview tripwire** | Separate from transport | **`GoogleDrivePreviewReverificationTest` deadline `2027-03-18`**. |
| **Other Current Status opens (soft-launch era)** | Panel bugs, plan-step bypass, nightly E2E, backups bucket, etc. | Still listed under Sept 30 “Deferred until after soft launch” — not re-audited as Toshi-architecture items here. |

---

## 6. Explicit extension-point summary

### 6a. New MCP connector

Touch: `config/toshi.php` (catalog + write gate) → `routes/ai.php` named client (web+token closure **or** local MCP server) → OAuth upsert into `school_mcp_connectors` → Skill + `RouteTo*` (+ Conversational if Approvable writes) → Integrations UI → isolation/OAuth/gate tests → env flags default off; enable write-gate master **before** connector flag.

### 6b. New channel

Touch: inbound route + signature/auth → identity resolution → channel service wrapping role agent / SDK ask → outbound send → confirmation bridge for that transport → `*_channel_enabled` (+ usually `sdk_v2_enabled`) → feature tests. WhatsApp is the precedent; Slack inbound would be this class of work, **not** a connector PR.

### 6c. New Skill (in-app domain)

Touch: Skill class + `RouteTo*` + register on Orchestrator / role agent + instructions + prefer `UsesToshiLlm`. Add Conversational only if native Approvable pause is in scope.

### 6d. New write-gated action

| Kind | Pattern |
|---|---|
| MCP tool | List in catalog `write_tools` (or rely on `classify` unknown→write); always `ApprovableMcpTool::wrap`; never call `Client::callTool` outside approved/`bypassFor` path |
| Platform Superadmin tool | Implement laravel/ai `Approvable` + `InteractsWithApprovals` on the tool; agent must be Conversational |
| School domain tool | Existing Tier-2 `ConfirmsBeforeWrite` / panel confirm — do not mix with MCP gate |

### 6e. Drive along `drive.file` + Picker lines (shape only — no build decision)

**Classification:** a **connector** (per-school OAuth + tool calls into Drive), not a channel — unless inbound Drive notifications are separately scoped later.

**Existing pattern it would follow:** Tier-1 registry row + OAuth upsert + named client (likely local REST wrapper like Classroom’s planned shape, **or** a GA hosted MCP client if that becomes legal/usable) + Skill/`RouteTo*` + write gate + audit wrap. Slack/Classroom checklist in §3.2 applies.

**What is genuinely new (no Slack/Classroom precedent):**

- A **client-side Google Picker** (or equivalent) file-selection step before/during connect or import — Slack and Classroom use pure backend OAuth with no browser file chooser
- Scope posture **`drive.file`** (per-file access after user picks) vs the earlier-rejected broad `drive.readonly` / pre-GA Drive MCP path documented in plans

**Open questions for whoever scopes it for real (not answered here):**

1. Where does Picker UX live (Integrations settings vs Toshi chat vs onboarding wizard), and how is the selected file ID set persisted into the connector/session?
2. Does “onboard their school from Drive” mean read-parse-into-`OnboardingEngine` (**commit-per-step (B)**) with optional Approvable only if writes back to Drive (**pause (A)**) — keep those mechanisms separate per §2.4?
3. Is the transport a local REST MCP server (Classroom pattern) or a future GA remote MCP — and does the transport-era tripwire apply?
4. Update `knowledge.md` Current Status when the `drive.file`+Picker product direction is formally adopted; today plans still say WAITING FOR GA / REST rejected.

---

## Source index

| Area | Primary paths |
|---|---|
| Orchestrator / SDK | `app/AiAgents/ToshiOrchestrator.php`, `app/AiAgents/ToshiSdkV2Service.php`, `app/Livewire/AgentToshi.php` |
| Skills / routes | `app/AiAgents/Skills/*`, `app/AiAgents/Tools/RouteTo*SkillTool.php` |
| Gate / Approvable / audit | `app/Services/Toshi/McpWriteGate.php`, `app/Ai/Tools/Toshi/ApprovableMcpTool.php`, `app/Services/Toshi/Concerns/AuditsMcpToolCalls.php`, `app/Services/Toshi/ToshiMcpCallAuditor.php` |
| Registry | `app/Models/SchoolMcpConnector.php`, `routes/ai.php`, `config/toshi.php` |
| Channels | `app/Http/Controllers/Api/WhatsAppController.php`, `app/Services/WhatsApp/WhatsAppConfirmationBridge.php`, `app/Services/WhatsApp/WhatsAppToshiChannelService.php` |
| Tests | `SchoolMcpConnectorIsolationTest`, `McpWriteGateDefenseInDepthTest`, `SlackMcpApprovalFlowTest`, `McpTransportEraReverificationTest`, `McpClientConstructionTest` |
| Plans / ops | `docs/plans/toshi-*.md`, `docs/ops/slack-connector-go-live-checklist.md` §6c |

---

*Audit date: 2026-10-02. Code base: `origin/main`. Staging LLM health: verified via Cloud Commands.*
