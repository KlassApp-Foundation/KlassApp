# Implementation Plan: (B) HITL Convergence + (A) Slack/Notion Connectors

> Status: **PLAN ONLY — no code written, no branch/PR opened.** Awaiting explicit go-ahead.
> Research pass: 2026-09-17, direct source verification (background research agents were unavailable this session — worker billing exhausted; every claim below was re-verified directly against the working tree at `main` post-fetch).
> Companion docs: `docs/toshi-role-parity-audit.md`, `docs/toshi-whatsapp-channel-audit.md`, `knowledge.md` (#124–#140 closeout).

---

## PART B — Converge Tier-2 ConfirmsBeforeWrite + native Approvable, extended to MCP writes

### B.1 The deferred backlog item (exact language)

`docs/toshi-role-parity-audit.md:354`:

> **Converge ConfirmsBeforeWrite (Tier-2) + native Approvable into one confirmation mechanism** — deferred/tracked; both currently populate the same audit identity fields (`acting_user_id` + `approver_id`) via different paths.

Related markers in shipped code (all say the same thing):
- `config/services.php:75` — "MCP Approvable/HITL for write tools is deferred — read audit via ToolInvoked."
- `routes/ai.php:27` — "MCP HITL deferred."
- `ToshiAuditService` docblock (app/Services/ToshiAuditService.php:25) — "MCP Approvable/HITL for write tools is deferred."
- `tests/Feature/Toshi/SpikeSlackMcpClientTest.php:26` — "MCP Approvable/HITL for write tools is deferred."

### B.2 Verified call paths

**Tier-2 ConfirmsBeforeWrite (school scope, web)**
1. Tool `handle()` calls `$this->confirmOrExecute()` (`app/AiAgents/Concerns/ConfirmsBeforeWrite.php:31`) → `ToshiActionService::confirmationPayload()`.
2. `ToshiActionService::$bypassConfirm === false` (default) → returns `__tier2_confirm` JSON **and** stashes it in static side-channel `ToshiActionService::$pendingConfirmPayload` (app/Services/ToshiActionService.php:56-63).
3. `AgentToshi` (Livewire) decodes `__tier2_confirm`, sets `pendingToolConfirm` Livewire prop **and** `session(['toshi_pending_confirm_'.md5($preview) => ...])` (app/Livewire/AgentToshi.php:1166-1172), renders the confirm card. Plan steps pause the same way (`executeNextPlanStep`, :1163-1175).
4. User clicks Yes → `confirmYes()` (:1016) → `executeConfirmedTool()` (:1281): resolves class via hardcoded `TOOL_CLASS_MAP`, sets static `ToshiActionService::$bypassConfirm = true`, re-runs `handle()` (so the **same code path** now writes), runs `VerifiableTool` re-read check, then `ToshiAuditService::logExecution(approver: $actor, actingUser: $actor)` (:1316-1324), resets flag in `finally`.
5. `confirmNo()` (:1072) → `logCancellation` audit.

**Tier-2 over WhatsApp (wave-1 live)**
- `WhatsAppToshiChannelService::dispatchTier2ConfirmationIfPending()` (app/Services/WhatsApp/WhatsAppToshiChannelService.php:180) + text-fallback parser (:157) → `WhatsAppConfirmationBridge::sendConfirmation(..., MECHANISM_TIER2, ...)` (:195-215) creates `whatsapp_pending_confirmations` row (token, phone-bound, `expires_at` = 15 min TTL, outbound wamid).
- Inbound `ty_{t}`/`tn_{t}` button or `YES|NO {t}` text → `handleInbound` (:243) → phone+user match, `claimPending` row-lock, first-valid-wins → `resumeTier2()` re-runs the tool with `bypassConfirm=true` (mirrors web). Write tools exposed over WhatsApp are only the five wave-1 allowlisted task tools (`WhatsAppWriteExclusion::WRITE_ALLOWLIST`); `HARD_DENY` = payroll + impersonation (app/AiAgents/WhatsApp/WhatsAppWriteExclusion.php:36-53).

**Native Approvable (platform scope)**
1. 10 Superadmin tools (`app/Ai/Tools/Superadmin/*`) implement `Laravel\Ai\Contracts\Approvable` + `InteractsWithApprovals`, each overriding `needsApproval(Request): Approval|bool` returning `Approval::required($reason)` (e.g. `ToggleSchoolFeatureTool.php:40-50`).
2. In `laravel/ai` v0.10.2's `TextGenerationLoop::approvalForTool()` (vendor/laravel/ai/src/Gateway/TextGenerationLoop.php:510): `return $tool instanceof Approvable ? $tool->shouldRequestApproval(new Request(...)) : null;` — any gated tool call pauses the run.
3. Pause state persists as `approval_state` JSON (`{pending: {toolCallId: reason}}`) on the assistant row in `agent_conversation_messages` (vendor `DatabaseConversationStore.php:119,155`); conversation rows in `agent_conversations`.
4. Resume = `(new AgentClass)->continue($conversationId, as: $user)->prompt(Decisions::from([$approvalId => Decision::approve()|reject(...)|edit($args)]))` — used by `PlatformApprovalGate` (Livewire, app/Livewire/Superadmin/Toshi/PlatformApprovalGate.php:37-67), `PlatformOpsConversationController` (GET|POST `/superadmin/toshi/ops/{conversation}`, routes/web.php:432-436), and `WhatsAppConfirmationBridge::resumeApprovable()` (:402-429).
5. Audit: `ToolApprovalResolved` event → `LogToolApprovalResolved` listener sets `approver_id` = auth (resolving) user and `acting_user_id` = conversation participant, deliberately distinct even under self-approve.

**MCP path (the gap being closed)**
- `AuditingMcpClientManager::build()` wraps every `Mcp::client($name)` in `AuditingMcpClient`/`AuditingWebClient` (app/Services/Toshi/AuditingMcpClientManager.php:16-34).
- Both use `AuditsMcpToolCalls::callTool()` (app/Services/Toshi/Concerns/AuditsMcpToolCalls.php:16-23) which is **execute-then-audit**: `$result = parent::callTool(...); ToshiMcpCallAuditor::audit($name, $arguments, $result);` — no pre-execution interception point exists.
- `ToshiMcpCallAuditor::audit()` writes an activity-log row with `approver: null, actingUser: $user` (app/Services/Toshi/ToshiMcpCallAuditor.php:67-75) — **an MCP write is executed with no human ever confirming it**; the audit proves it happened, not that it was allowed.
- Architecture test `Tests\Architecture\McpClientConstructionTest` bans `Client::web()` / `Client::local()` / `new WebClient` outside `routes/ai.php` — the only sanctioned client-construction site. Any HITL design must live **inside the wrapper/manager or above it**, not in new construction paths.
- `laravel/ai` v0.10.2's `McpTool` wrapper (vendor/laravel/ai/src/Tools/McpTool.php) implements `Tool` only — **not** `Approvable` — so `approvalForTool()` returns null for every MCP tool and the native loop cannot pause on them. Changelog check through v0.11.0 (2026-08-19): **no first-party MCP+Approvable integration exists**; HITL (PR #773) is Tool-level only. No vendor feature is coming to do this for us.

### B.3 Behavioral divergences (mechanism, not naming)

| Dimension | Tier-2 ConfirmsBeforeWrite | Native Approvable |
|---|---|---|
| Where the gate lives | Inside each tool's `handle()` (opt-in call to `confirmOrExecute`) | Inside the SDK loop (`approvalForTool`), outside the tool |
| Pause representation | Sentinel `__tier2_confirm` JSON returned to the model + static side-channel | `approval_state.pending` map on `agent_conversation_messages` keyed by tool-call id |
| Pending durability | Livewire public prop + session key (web); `whatsapp_pending_confirmations` row (WhatsApp) | DB conversation message — survives process restarts, channel switches, sessions |
| Who can approve | The requesting user in the same UI session (self-approve by construction) | Any authorized viewer of the conversation (`Gate::authorize('view', $conversation)`) — separable approver |
| Approval surfaces | Livewire card buttons; WhatsApp buttons/text | Superadmin ops UI (approve / reject-with-reason / **edit args**) |
| Second approver possible | No | Yes (`approver_id` ≠ `acting_user_id` is first-class) |
| Argument editing on approve | No | Yes — `Decision::edit($arguments)` |
| Expiry | None on web (session lifetime); 15-min TTL only via WhatsApp bridge row | None in package; bridge TTL applies to the channel row, not the conversation state |
| Resume semantics | Re-run `handle()` with `bypassConfirm=true` — model never sees the tool result | `continue()` + `Decisions` — the **model loop resumes** and can react to the tool result |
| Audit identity | Both `approver` and `actingUser` = confirming actor (same person, both populated) | `approver` = resolver, `actingUser` = conversation participant (distinct by design) |
| Multi-pending | One `pendingToolConfirm` at a time (new confirm overwrites; N tokens over WhatsApp) | Map of pending ids per message; mismatched decisions throw `ApprovalMismatchException` |
| Rejection audit | `logCancellation` (separate log shape) | `logApprovalResolved(denied: true)` |
| What breaks on reroute | Static `$bypassConfirm` / `$pendingConfirmPayload` must be reset in `finally` — fragile under long-lived workers | Package-managed; store prunes resolved ids on resume |

### B.4 Target mechanism: native Approvable as the single engine (recommended)

**Decision: converge on native Approvable (`approval_state` + `Decisions` resume) as the canonical pause/resume engine, keep Tier-2's card UX as a *view*, and extend Approvable to MCP tools via an app-level `ApprovableMcpTool` wrapper.** A new third "shared layer" is explicitly rejected — Approvable *is* the shared layer; adding another mechanism is re-divergence.

Justification:

1. **Identity fields.** The audit contract already models the exact distinction the platform needs: `approver_id` (who resolved) vs `acting_user_id` (who asked). Tier-2 can only ever self-approve; Approvable supports both self-approve and second-party review, and `LogToolApprovalResolved` already writes both correctly. Converging the *other* direction (onto Tier-2) would erase second-party approval from the platform.
2. **Architecture test.** The whole design lives at or above the named-client layer: the MCP gate is a Tool subclass (constructed from `Mcp::client()->tools()` primitives — no client construction), the loop check is the vendor's own `instanceof Approvable` seam at `TextGenerationLoop.php:510`. `McpClientConstructionTest` stays green untouched.
3. **Durability.** DB-backed pause state is required for MCP writes: a `slack_post_message` approval might be resolved minutes later over a different surface (web ops UI, WhatsApp bridge, future Slack surface). Tier-2's session/static state cannot survive that; `approval_state` already does, and `DatabaseConversationStore` already prunes resolved ids on resume.
4. **Channel parity is already half-built.** `WhatsAppConfirmationBridge` already implements the Approvable resume (`MECHANISM_APPROVABLE` + `resumeApprovable()` using `Decision::*`) and the audit doc's Part A explicitly designed the token bridge to unify both mechanisms. The missing half is only the *dispatch* side (see B.7) — the same gap MCP has.
5. **Model-in-the-loop.** After an approved MCP write, the agent should keep reasoning with the result (Approvable resume re-enters the generation loop). Tier-2 just returns a string to the UI.

What Tier-2 keeps (deliberately, for now): the *school-side chat card UX* and the in-tool preview authoring (`confirmOrExecute(toolName, args, preview)` is a nice authoring API). Those become a rendering concern over Approvable state during migration, not a second confirmation engine.

### B.5 The MCP gate: exact interception point

**Primary hook — Tool layer (agent-mediated calls, the only path that can pause):**

```
app/Ai/Tools/ApprovableMcpTool.php        (new)
    extends Laravel\Ai\Tools\McpTool       (wraps the same Client\Primitives\Tool)
    implements Laravel\Ai\Contracts\Approvable
    uses Laravel\Ai\Concerns\InteractsWithApprovals

    needsApproval(Request $request): Approval|bool
        → resolves write-classification for ($this->name(), $request)
        → write   ⇒ Approval::required("Slack write: {tool} {args-summary}")
        → read    ⇒ false (no gating)
```

Why this works without forking the vendor (verified in `GeneratesText::resolveTool()`, vendor/laravel/ai/src/Providers/Concerns/GeneratesText.php): the match arm `$tool instanceof Tool => $tool` fires **before** `McpTool::supports($tool) => new McpTool($tool)`, so an app-returned wrapper passes through unwrapped; the loop's `approvalForTool()` then sees an `Approvable` and pauses. Agents/Skills expose MCP tools as `ApprovableMcpTool::wrap($primitive)` instead of spreading raw primitives (`SpikeSlackMcpTestAgent::tools()` is the pattern to update).

- **Interception happens before execution**: the loop only calls `Tool::handle()` (→ `Client::callTool()` → `AuditingMcpClient` audit) *after* the decision resolves. The audit row therefore gains a real `approver_id` via `ToolApprovalResolved` instead of today's always-null.
- `name()` stays `mcp_tools_{name}` (inherited) — existing audit rows/tests (`SpikeSlackMcpClientTest::raw_named_client_call_tool_writes_read_shaped_audit_when_authenticated`) remain valid for reads.

**Secondary hook — client layer (defense-in-depth for non-agent calls):**

```
app/Services/Toshi/Concerns/AuditsMcpToolCalls.php  (modified)
    callTool($name, $arguments):
        McpWriteGate::assertExecutable($this->name(), $name);   // NEW — before parent::callTool
        $result = parent::callTool($name, $arguments);
        ToshiMcpCallAuditor::audit($name, $arguments, $result);
```

`McpWriteGate` (new, app/Services/Toshi/) holds the write-classification table and, for write-classified tools, requires an in-process "approval consumed" marker (set by the loop when a decision resolves) — otherwise it throws. Effect: **raw `Mcp::client()->callTool()` / `ToshiMcpClient::callTool()` on a write-classified MCP tool fails closed** outside an approved agent turn. Reads are untouched; the audit-only behavior for reads is preserved. This converts the execute-then-audit layer into gate-then-execute-then-audit for writes only, and it is the *only* behavioral change inside the auditing client.

**Write classification (new, config-driven):**

```
config/toshi.php
  'mcp_write_gates' => [
    'slack' => [
        'mode' => env('TOSHI_SLACK_MCP_WRITE_MODE', 'deny'),   // deny | classify | allowlist
        'read_tools' => [...],   // explicit allowlist in classify mode
        'write_tools' => ['slack_post_message', ...],          // gated in allowlist mode
    ],
  ],
```

Default `deny` = all tools from that client treated as writes → nothing executes without approval (fail closed); `classify`/`allowlist` refine. There is no existing write-classification layer for MCP tools anywhere in the repo — this is new surface, deliberately config-first so adding Notion later doesn't need code changes to the gate.

### B.6 Sequencing & migration (what moves, what could break)

**Phase B-1 (this convergence, platform + MCP only — no school tool changes):**
- Ship `ApprovableMcpTool` + `McpWriteGate` + `mcp_write_gates` config. Zero impact on Tier-2 users — school tools, WhatsApp wave-1 writes, and the Livewire card flow are untouched.
- Extend `SpikeSlackMockServer` with a fixture *write* tool (e.g. `spike-slack-post-message`) so the pause/resume path is CI-testable end-to-end in mock mode: assert (1) prompt pauses with `approval_state`, (2) no audit row exists yet, (3) `Decisions::approve()` resumes, (4) exactly one audit row with `approver_id` = resolver and `acting_user_id` = participant, (5) raw client write call throws before execution.
- Architecture test still green; add a rule that every MCP tool exposed by an Agent must pass through `ApprovableMcpTool` (grep-style test mirroring `McpClientConstructionTest`).

**Phase B-2 (school Tier-2 → Approvable migration — separate PR series, one role batch at a time, NOT bundled with connectors):**
- Per-batch: tool drops `ConfirmsBeforeWrite` in favor of `needsApproval()`; `AgentToshi` renders its existing card from `pendingApprovals` instead of `__tier2_confirm`; `confirmYes` maps to `Decisions::approve()`; plan-step pause/resume re-keys off pending approval ids.
- Risks / what could break:
  - **WhatsApp wave-1 writes** (`WhatsAppWritesWave1Test` — 5 task tools): `resumeTier2()` + `TIER2_TOOL_MAP` + `bypassConfirm` are load-bearing. Migration swaps `MECHANISM_TIER2` dispatch to `MECHANISM_APPROVABLE` and uses the already-built `resumeApprovable()`. Requires the missing dispatch half (B.7). Until then both mechanisms must keep working — the bridge is already dual-mechanism by design.
  - **`TOOL_CLASS_MAP` / `TIER2_TOOL_MAP` drift**: two hardcoded maps (AgentToshi + bridge) must stay in sync with tool classes during migration; a map-driven-by-attribute refactor should land first to avoid a third copy.
  - **Static state leaks**: until Tier-2 is fully retired, `ToshiActionService::$bypassConfirm` / `$pendingConfirmPayload` resets must remain in `finally` (known fragility, documented in the trait).
  - **VerifiableTool re-read**: currently runs inside `executeConfirmedTool`; with Approvable the tool executes inside the loop, so verification must move into `handle()` after write.
  - **Self-approve semantics preserved**: school cards keep self-approve (approver = actor, both fields set — same row shape as today, so audit consumers don't change).
  - **No-expiry on web Tier-2 today** — Approvable pause state has no TTL; decide and implement a consistent expiry sweep (bridge TTL is 15 min) during B-2 so stale pendings can't linger forever.

### B.7 The missing dispatch half (both MCP and Approvable-over-WhatsApp need it)

Today nothing ever *sends* an Approvable pending to WhatsApp: `WhatsAppToshiChannelService` handles only the Tier-2 side-channel + text fallback (verified — no `hasPendingApprovals`/`MECHANISM_APPROVABLE` call site outside the bridge itself). When an agent turn over WhatsApp (or any future channel) pauses on Approvable — including MCP write gates from B-1 — the channel service must detect `hasPendingApprovals()`, look up conversation id + agent class, and call `sendConfirmation(..., MECHANISM_APPROVABLE, [conversation_id, approval_id, agent_class, ...])`. This is the single new channel-side piece B-1 needs for platform/MCP approvals to be reachable from WhatsApp; the resume side already exists and is tested (`WhatsAppToshiChannelTest`).

---

## PART A — Slack / Notion connectors

### A.1 Verified inventory: everything today is UI-only

| Surface | File:line | Backend? |
|---|---|---|
| Brand SVG components (`slack`, `google-drive`, `whatsapp` — **no Notion component exists**) | resources/views/components/brand/ | none |
| Landing hero "Connected float" marks (WhatsApp/Drive/Slack) | landing-v2.blade.php:114-116 | none |
| Orchestration panel connectors | landing-v2.blade.php:150-162 | none |
| Connector chips | landing-v2.blade.php:189-191 | none |
| "Admin · Slack" hero role card | landing-v2.blade.php:98-99 | none |
| Marketing copy (multi-channel, protocol sections) | landing-v2.blade.php:7,66,221,234,396,442 | none |
| Empty-state product-demo hub: WhatsApp **Live** / Google **Sign-in** / Slack **Coming soon** / Notion **Coming soon** + disclaimer | partials/empty-state-product-demo.blade.php:96-115 | none |
| Admin dashboard "connected" marks (WhatsApp, Drive) | admin/dashboard/dashboard.blade.php:72,79 | none |
| `config/services.php` `slack_mcp` block (mode/url/client_id/client_secret/token/timeout, default **mock**) | config/services.php:77-84 | **spike plumbing only** (PR #140) |
| `routes/ai.php` named client `slack` (mock: `Mcp::local` fixture server; live: `Client::web` + `withToken`/`withOAuth`), OAuth callback scaffold that only logs (placeholder says "Real design: dedicated `school_slack_mcp_credentials` table") | routes/ai.php:30-75 | spike, mock by default, **not product-wired** |
| Spike test + mock server + 2 read-only tools (`spike-slack-auth-test`, `spike-slack-list-channels`) + throwaway agent | tests/Feature/Toshi/SpikeSlackMcpClientTest.php, app/Mcp/**, app/Ai/Agents/SpikeSlackMcpTestAgent.php | test-only |
| **Notion: zero anything** — no config, no route, no test, no brand component; appears only in the empty-state hub tile + disclaimer | — | none |

Everything else in `app/`, `routes/`, `resources/js/`, `config/` matching slack/notion is unrelated (Monolog slack log driver, spatie-backup slack notification channel).

### A.2 Recommendation: **Slack first**

1. **Sunk plumbing.** PR #140 already built and merged the Slack MCP client path: named client, auditing wrapper, mock server, OAuth scaffold (`Mcp::oAuthRoutesFor('slack')`), and a green test suite. Notion starts from zero at every layer.
2. **Server availability.** Slack ships an official hosted remote MCP server (`https://mcp.slack.com/mcp`, OAuth) — already encoded in the spike config; live mode needs only credentials. Notion's MCP story is third-party servers with integration tokens; nothing official is wired or tested here.
3. **Demand signal (as far as one exists).** No explicit Slack-vs-Notion decision exists anywhere in `knowledge.md`. But every product surface consistently promises **Slack** — the "Admin · Slack" hero role card, orchestration panel, chips, protocol copy ("Toshi orchestrates WhatsApp, Drive, and Slack"), README architecture diagram (Drive/Slack dashed as product-model) — while **Notion appears exactly once** (empty-state "Coming soon" tile). Slack is the promised connector; Notion is aspirational chrome.
4. **Auth fit.** Slack OAuth is workspace-scoped, and the spike already identified the correct production model (per-workspace `team_id` → `school_id`, per-school credential storage) — hard but *known*. Notion integration tokens are simpler per-app but the data model (pages/databases graph) maps far worse to school-domain actions than Slack's flat channel/message model.
5. **Channel-role fit.** Product model assigns Slack the **admin/staff** channel (hero rotate: Parent WhatsApp / Teacher Drive / Admin Slack), complementing the live WhatsApp parent channel. The `WhatsAppConfirmationBridge` token design (opaque token + pending row + phone-bound) is channel-agnostic by construction (Part A explicitly designed it to unify Approvable + Tier-2 over *an* external channel) — Slack inherits the same pattern later.

### A.3 Minimal real scope (wave 1 — deliberately small)

**Read-only + gated notification posting; school-admin-facing. No inbound Slack→Toshi. No interactive Slack approval buttons.**

- Read tools (no HITL): `slack_list_channels`, `slack_search`, channel history.
- Write tool (HITL-gated via Part B): `slack_post_message` — post an notification/alert to a pre-registered school channel.
- **Explicitly out of scope:** Notion, Google Drive connector, Slack event webhooks (inbound), Slack interactive Approve/Reject blocks, per-user DM delivery, multi-workspace roster sync, anything for parents/students. Single workspace per school, admin-initiated connect.

**Dependency ordering:** if Part B-1 is not shipped first, drop `slack_post_message` from wave 1 (ship reads only) — the spike's own docblock and `config/services.php:75` both defer MCP write HITL, and shipping an ungated MCP write would be a regression against the audit philosophy the whole #140 architecture exists to enforce.

### A.4 How it plugs into existing patterns

**Skill + RouteTo\* packaging** (template: `RouteToSchoolCommsSkillTool` app/AiAgents/Tools/RouteToSchoolCommsSkillTool.php + `SchoolCommsSkill` app/AiAgents/Skills/SchoolCommsSkill.php):
- `RouteToSlackSkillTool` — plain `Tool`, `AuthorizesToshiAction` gate, schema `{query}`, dispatches `(new SlackSkill)->prompt($query)->text`. Registered in the school-admin orchestrator's router set alongside the other RouteTo* tools.
- `SlackSkill` — `#[MaxSteps]`, `Promptable`, **`UsesToshiLlm`** (the SchoolCommsSkill docblock explicitly warns new skills must resolve the Toshi LLM provider, not `ai.default`), instructions() scoping to "post school notifications to the connected workspace; list/search read-only", `tools()` returns `ApprovableMcpTool::wrap(...)` per primitive — never raw spreads.
- Tool naming stays `mcp_tools_slack_*` via the wrapper (audit continuity with `SpikeSlackMcpClientTest`).

**Named-client audit requirement (invariant, no new work):**
- All Slack traffic goes through `Mcp::client('slack')` → `AuditingMcpClientManager` → `AuditingMcpClient`/`AuditingWebClient` → `ToshiMcpCallAuditor` — structurally guaranteed by the existing manager + architecture test. The Skill never constructs clients (test stays green).
- For acting-user pinning in queued contexts: `ToshiMcpClient::named('slack', $user)` already exists.

**Write gating (from Part B):**
- `slack_post_message` (and any future write tool) is classified in `config/toshi.php` `mcp_write_gates.slack` → `ApprovableMcpTool::needsApproval()` returns `Approval::required("Post Slack message to #channel: {excerpt}")` → conversation pauses in `approval_state` → school-admin confirms in the AgentToshi card (post B-2) or ops UI → resume via `Decisions::approve()` → executes → audit row with real `approver_id`.

**OAuth / credentials (replacing the spike placeholder):**
- `school_slack_mcp_credentials` table (new migration): `school_id` (FK, unique per workspace `team_id`), `team_id`, `team_name`, token set columns (`access_token`, `refresh_token`, `expires_at`), `status` (active/inactive per standing rule #3), timestamps. Replaces the "do not reuse users.google_token pattern" placeholder in routes/ai.php:65.
- `Mcp::oAuthRoutesFor('slack')` callback resolves the connecting school admin's `school_id`, stores the TokenSet row keyed by `team_id`, maps `team_id → school_id`. Token refresh on 401 via `AuditingWebClient`'s preserved OAuth config (wrap() keeps `oAuthConfig`).
- Client resolution becomes school-aware: the `routes/ai.php` live-mode factory (the only allowed construction site) reads the connecting school's stored TokenSet; **no global env token in production** (spike comment: "production must NOT use a single global Slack token" — routes/ai.php:21).

### A.5 New env vars / Doppler secrets / gates (TOSHI_* convention)

| Key | Default | Layer | Purpose |
|---|---|---|---|
| `TOSHI_SLACK_CHANNEL_ENABLED` | `false` | config/toshi.php | Master gate (mirrors `TOSHI_WHATSAPP_CHANNEL_ENABLED`, toshi.php:44). Off = RouteToSlackSkillTool not registered, connector UI hidden |
| `SLACK_MCP_MODE` | `mock` | config/services.php (exists) | mock/live branch in routes/ai.php — keep for local/CI |
| `SLACK_MCP_URL` | `https://mcp.slack.com/mcp` | config/services.php (exists) | Remote MCP endpoint |
| `SLACK_MCP_CLIENT_ID` / `SLACK_MCP_CLIENT_SECRET` | unset → Doppler | config/services.php (exists) | Live-mode OAuth app credentials (Doppler prod/staging configs) |
| `SLACK_MCP_TIMEOUT` | `30` | config/services.php (exists) | Keep |
| `TOSHI_SLACK_MCP_WRITE_MODE` | `deny` | config/toshi.php `mcp_write_gates.slack.mode` (new, from B-1) | `deny` fail-closed → `allowlist` once `slack_post_message` is approval-gated |
| `TOSHI_MCP_WRITE_GATES_ENABLED` | `false` | config/toshi.php (new) | Master switch for the B-1 client-layer gate (lets the gate ship dark and be env-flipped per environment) |

`SLACK_MCP_TOKEN` (dev-only bearer) stays out of production Doppler; live auth is per-school OAuth rows only.

### A.6 UI work (small, honest)

- Replace the empty-state hub Slack badge "Coming soon" → gated badge ("Live"/"Connect") **only when `TOSHI_SLACK_CHANNEL_ENABLED` + school credential row exist**; keep the Notion tile as-is (still aspirational).
- Admin dashboard "connected" marks stay honest: Slack mark appears in the school's connector row only when a credential row exists (WhatsApp/Drive marks already behave as status displays there).
- A small "Connect Slack" admin action (button → `mcp.oauth.slack.connect` flow) + connected-workspace display. No landing-page changes — marketing copy already says "operates in the tools educationists already use" and stays future-facing.

---

## Execution order (proposed)

1. **PR 1 (B-1 core):** `ApprovableMcpTool` + `McpWriteGate` + `mcp_write_gates` config + mock-server write tool + tests (pause/resume/audit/fail-closed). Architecture tests extended.
2. **PR 2 (B-1 channel half):** Approvable dispatch from WhatsApp channel service (`MECHANISM_APPROVABLE` sendConfirmation when `hasPendingApprovals()`), reusing the existing resume path.
3. **PR 3 (A wave-1):** `school_slack_mcp_credentials` migration + OAuth callback + school-aware live client factory in routes/ai.php + `SlackSkill`/`RouteToSlackSkillTool` (reads only if PR 1 not yet enabled for writes) + `TOSHI_SLACK_CHANNEL_ENABLED` gate + UI badges + tests + Playwright pass at 375/414/768/1280.
4. **PR 4 (A writes):** flip `TOSHI_SLACK_MCP_WRITE_MODE=allowlist`, expose `slack_post_message` behind the approval gate, end-to-end test incl. reject + approver/acting audit fields.
5. **B-2 (separate later series, one role batch per PR):** Tier-2 → Approvable school migration per B.6, bridge dual-mechanism until the last batch retires `MECHANISM_TIER2`.

**Stopped here per instructions — no code, no branch, no PR until explicit go-ahead.**
