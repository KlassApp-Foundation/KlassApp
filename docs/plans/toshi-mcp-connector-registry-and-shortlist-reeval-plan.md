# Implementation Plan: (C) School-Registrable MCP Connector Registry + (D) Connector Shortlist Re-evaluation

> Status: **GO-AHEAD CONFIRMED (2026-09-18) for the 4-PR execution order, with three resolutions applied** — (1) Google Drive Developer Preview boundary spec → **D.3**; (2) trust model: self-registration is final, no approval step, status = `active`/`disabled` only → **C.1/C.2/C.4**; (3) Notion PR4 gated on concrete use-case research → **D.1 rank-2**. PR1 (registry core) in progress.
> Builds directly on `docs/plans/toshi-hitl-convergence-and-slack-connector-plan.md` (Part A Slack wave-1, Part B HITL/MCP gate). Where this plan and that one overlap on credential storage, **this plan supersedes** — the Slack-specific `school_slack_mcp_credentials` table generalizes into the registry designed here.
> Research pass: 2026-09-18, direct source verification (background research agents remain unavailable — worker billing exhausted; all vendor/file claims re-verified directly against the working tree at `main` post-fetch; all external claims sourced from official docs/changelog searches noted inline).
> **Second verification pass 2026-09-18 (later session):** all codebase claims re-verified green against the working tree (architecture test, auditing manager, vendor `ClientManager`/`HttpTransport`/`OAuthClient`/`OAuthRouteRegistrar`, spike files, landing copy, `ToshiMcpClient::named`); external claims re-verified via direct official-docs search (background agents again failed on billing). Two corrections applied: Figma **Education-plan rate limits** (R.4/D.1 — Education seats get 200 calls/day, not the 6/month View-seat limit; deferral verdict unchanged, re-argued on the per-user authorization model) and a **Canvas official-interest watch item** (R.5 — Instructure community roadmap discussion, July 2026).
>
> **Notion use-case research (2026-09-18, PR1 branch):** Scanned `knowledge.md`, product/UX docs, and all app models/views for Notion-relevant workflows. Result: **no credible grounded use-case found**. Bulletins are file-upload-based (`magazines` table, PDF). Lesson plans have an in-app approval workflow (`LessonPlanApproval`). Staff handbooks, meeting notes, policy docs, and SOPs do not exist in the product. Parent comms route through WhatsApp. Speculative "Toshi surfaces Notion docs" requires schools to already use Notion for school-ops docs — no product signal supports this. **Verdict: defer Notion from the 4-PR scope.** Config catalog entry retained for future activation; PR4 not shipped without grounded use-cases.

---

## PHASE 1 — RESEARCH FINDINGS (verified)

### R.1 What `McpClientConstructionTest` actually bans (the invariant)

`tests/Architecture/McpClientConstructionTest.php`:
- **Banned patterns** (regex over non-comment source, `app/` + `routes/` trees): `Client::web(`, `Client::local(` (both bare and FQ `\Laravel\Mcp\Client::web/local`), `new \?Laravel\Mcp\WebClient(`, `new WebClient(`.
- **Allowlist**: exactly one entry — `routes/ai.php`.
- Enforcement: comment-stripped source scan; violations fail CI.
- Semantics: the invariant is *construction-site confinement* — "the only place a raw MCP client may be constructed is the named-client registration factory in `routes/ai.php`", because every other path (named `Mcp::client()` via `AuditingMcpClientManager`, `ToshiMcpClient::named()`) is **structurally audited** by wrapping. The test's own docblock states the rationale: "Direct `Client::web()`/`Client::local()` skips the wrapper."

**Implication for (C):** a dynamic registry must either (i) need **no new construction sites** (credentials resolved at token level inside existing named-client factories), or (ii) deliberately add exactly one audited construction site with an **equally structural** replacement guarantee (see C.6).

### R.2 How named clients resolve today (vendor mechanics)

`vendor/laravel/mcp/src/Client/ClientManager.php`:
- `$factories: array<string, Closure>` — **registered at boot** (`routes/ai.php`); `build($name)` throws `ClientException("MCP client [{$name}] has not been registered.")` for unknown names; `client($name)` memoizes builds per process.
- `AuditingMcpClientManager::build()` (app) overrides parent and wraps every built client via `wrap()` → `AuditingMcpClient` / `AuditingWebClient`. **This wrap is name-agnostic — it is the audit guarantee for ANY client, static or future-dynamic.**
- **The per-tenant seam already exists in the vendor**: `WebClient::withToken(string|Closure $token)` → `HttpTransport` evaluates the closure **per request** (`HttpTransport.php:182`: `$this->token instanceof Closure ? (string)($this->token)() : $this->token` on every call, line 89 same for discovery). A memoized named client can therefore resolve a **different credential per school on every call** with zero new construction.
- Token refresh: `OAuthClient::refreshCredentials(string $refreshToken, ?string $clientId, ?string $clientSecret): TokenSet` (vendor `OAuth/OAuthClient.php:130`) — usable out-of-band by an app-level refresh service; `TokenSet` is a plain serializable value object (access/refresh/expiresAt/scope/clientId/clientSecret).
- OAuth connect routes: `Mcp::oAuthRoutesFor($client, $handler)` registers named routes `mcp/oauth/{client}/connect` + `/callback` (middleware 'web' default); handler receives `(string $client, TokenSet $token)` (vendor `OAuthRouteRegistrar.php`).

### R.3 Credential storage pattern today (single-global, not per-school)

- `config/services.php` `slack_mcp` block (lines 77-84): **one global credential set from env** — `mode/url/client_id/client_secret/token/timeout`. The `SLACK_MCP_TOKEN` bearer is dev-only; live mode reads a single `client_id/secret`. Nothing school-scoped.
- WhatsApp (the one live connector) is also **single global platform credentials**: `WhatsAppBusinessService` reads `config('services.whatsapp.business_api_token')` / `business_phone_number_id` at construction (app/Services/WhatsAppBusinessService.php:28-30) — the whole platform shares one Meta number. **There is no per-school credential storage precedent anywhere in the codebase.**
- The Google anti-pattern: `users.google_token` — a raw per-user OAuth token column on the users table (app/Models/User.php:53-54; populated in `SchoolSignupBootstrapService.php:197`). The spike explicitly warns "do not reuse users.google_token pattern" (routes/ai.php:65). Lesson: tokens must live in a dedicated encrypted store keyed by tenant, not appended to user rows.
- The spike's own direction (routes/ai.php:21-23): *"production must NOT use a single global Slack token. Schools have their own workspaces — store TokenSet per school (or per connecting staff user)… Real design: dedicated school_slack_mcp_credentials table"* (placeholder at line 66).

### R.4 Miro / Figma official MCP status (web-verified 2026-09-18)

**Miro (official, `mcp.miro.com`)**
- Released Dec 2025; hosted HTTPS-only, closed-source, no self-hosting; **OAuth 2.1 with admin approval** (community comparison table, pkg.go.dev/olgasafonova/miro-mcp-server — the standard reference contrasting the official server).
- **13 tools** (developers.miro.com/docs/miro-mcp-tools): reads — `board_list_items`, `board_search_boards`, `doc_get`, image metadata/URL; writes — `board_create`, `doc_create`, `doc_update` (find-and-replace), `table_create`, `diagram_create_mermaid`, `diagram_update_mermaid`, code widget, image upload URL. Some tools consume **AI credits**.
- Admin-gating: OAuth 2.1 requires workspace admin approval; org-level enablement is associated with Enterprise-plan administration (the forum thread signal referenced in the task brief, consistent with the admin-approval model).

**Figma/FigJam (official, `mcp.figma.com`)**
- Rate limits & access (developers.figma.com/docs/figma-mcp-server/rate-limits-access — verified second pass): **per-seat AND per-plan** — View/Collab seats: 20 calls/**month** on Starter, **6/month** on paid plans; Dev/Full seats: 200/day 10/min (Professional), 200/day 15/min (Organization), 600/day 20/min (Enterprise). **Education plans: 200/day, 10/min — Dev/Full-on-Professional parity.** Write tools (`add_code_connect_map`, `generate_figma_design`, `whoami`) are exempt from limits, but the read path is the one a school would actually use.
- **Permission scoping is per-user**: "You can only access Figma content that you already have permission to view or edit."
- Enterprise-managed authorization exists **only via Okta Cross App Access for Claude**; everyone else does interactive per-user OAuth.
- **Conclusion: the shared service-account pattern KlassApp needs (one platform connection serving a school) is structurally incompatible with Figma's model.** *(Corrected second pass:)* the binding constraint is **not** rate limits — a school's Figma Education plan grants 200 calls/day — but the **per-user authorization model**: interactive per-user OAuth only (enterprise-managed auth exists solely via Okta Cross App Access for Claude), and permission scoping means a service account sees only the files shared with that one account.

**Notion (official, `mcp.notion.com/mcp`) — re-verified; corrects an imprecision in the prior plan**
- Notion ships an **official hosted remote MCP server** (`https://mcp.notion.com/mcp`, OAuth) — developers.notion.com/guides/mcp/get-started-with-mcp. The prior plan's phrasing ("Notion's MCP story is third-party servers") was about *this repo having nothing wired* — the official server does exist. Read + write tools (`notion-create-pages`, `notion-update-page`, `notion-create-comment`, file upload ≤20 MiB, search).
- **Key caveat**: official docs state *"Notion MCP currently requires you to complete the OAuth authorization flow. We're working on support for non-interactive authorization for automated workflows."* → server-side automation must run on stored refresh tokens via our own refresh path (R.2's `refreshCredentials`), which the registry design (C) provides.

**Google Workspace (new finding — official, Developer Preview)**
- Google shipped **official remote MCP servers** for Gmail/Drive/Docs/Sheets/Slides/Calendar/Chat/People: e.g. `https://drivemcp.googleapis.com/mcp/v1` (developers.google.com/workspace/guides/configure-mcp-servers). **Developer Preview** (Workspace Developer Preview Program), requires Google Cloud project enablement + per-app OAuth client + scope configuration.
- **Google Classroom is NOT covered** — no official Classroom MCP server exists.

### R.5 Education-specific official MCP status (re-verified)

- **Google Classroom**: still **community-only** — no official MCP; the Workspace preview (R.4) covers consumer Workspace products but not Classroom.
- **Canvas LMS**: still **community-only** token-based servers (github.com/enkhbold470/mcp-server-canvas, github.com/tylergibbs1/canvas-mcp, github.com/bruchris/canvas-lms-mcp — third-party, API-token auth, stdio/self-hosted HTTP). **Watch item (second pass):** Instructure hosts an official community 'Canvas MCP' roadmap discussion (community.instructure.com, July 2026) — vendor interest signal; no shipped hosted server yet.
- No official education-domain remote MCP server has shipped since the earlier research. KlassApp's own domain surfaces (admissions, report cards, UNEB) remain only reachable through KlassApp's own tools, which is fine — Toshi already owns them natively.

### R.6 Slack wave-1 design: Slack-specific vs. reusable (audit of the prior plan)

| Piece (prior plan) | Slack-specific? | Reusable for any official-remote-MCP connector? |
|---|---|---|
| Part B `ApprovableMcpTool` (extends `McpTool`, implements `Approvable`) | **No** — parameterized by client name + write-classification config | Fully generic — wraps ANY named client's primitives |
| Part B `McpWriteGate` + `mcp_write_gates` config | **No** — keyed by client name | Fully generic |
| `AuditingMcpClientManager` / auditing clients / architecture test | **No** | Untouched by connector count |
| Named-client factory block in `routes/ai.php` | Per-connector-**type** block (~15 lines: endpoint, OAuth config, token closure) | Pattern reusable; each type needs its own OAuth client credentials |
| `Mcp::oAuthRoutesFor('<type>', ...)` callback | Per-type (own connect/callback routes) | Pattern reusable |
| `school_slack_mcp_credentials` table (prior plan) | Named for Slack | **Generalizes — becomes the (C) registry; this is where (A) and (C) merge** |
| `SlackSkill` + `RouteToSlackSkillTool` | Per-connector Skill content (instructions, tool allowlist) | RouteTo* pattern generic; a thin per-type Skill or a parameterized generic Skill both work (C.5) |
| `TOSHI_SLACK_*` gates | Per-type env keys | Convention generic |
| UI tiles/badges | Per-connector | Pattern generic |

**Verdict for (D):** adding connector #2 is *not* a rebuild — it is a catalog entry + a factory block + a thin Skill + gate keys + a UI badge. The expensive machinery (audit wrap, HITL gate, registry, token closure, refresh) is connector-agnostic by construction. Therefore (D) should be designed as **several connectors in parallel behind one abstraction**, not "swap Slack for Miro."

### R.8 laravel/mcp transport compliance — Streamable HTTP (addendum #4, verified 2026-09-18)

**Finding: laravel/mcp v0.8.2 implements HTTP+SSE (old protocol), NOT Streamable HTTP (2026-07-28 spec revision).**

Direct source verification against `vendor/laravel/mcp/src/Client/Transport/HttpTransport.php`:
- **Stateful session tracking**: `protected ?string $sessionId = null` + `protected bool $initialized = false`; `captureSessionId()` reads `MCP-Session-Id` response header and stores it for subsequent requests. The `terminateSession()` method sends a `DELETE` to the endpoint to end the session.
- **Session header sent on every request**: `headers()` includes `MCP-Session-Id` when set and `MCP-Protocol-Version` when `initialized`.
- **SSE fallback**: `send()` checks `Content-Type: text/event-stream` and falls into `readSseStream()` — the OLD stateful HTTP+SSE transport pattern.
- **Protocol version**: `ProtocolVersion::LATEST = self::V2025_11_25` (enum at `vendor/laravel/mcp/src/Enums/ProtocolVersion.php`) — no 2025-05-22 or 2026-07-28 revision exists.

Streamable HTTP (2026-07-28 MCP revision) eliminated session state (no `MCP-Session-Id`, no `DELETE` termination), eliminated SSE in favor of simple POST-with-JSON-response or `GET` for server-to-client streaming, and made the protocol stateless so any replica could serve any request. The upstream `HttpTransport` is still stateful.

**Implication for our connectors:** Slack/Notion/etc. hosted endpoints will accept the old transport as long as they maintain backward compatibility. The risk is NOT in our code — it's laravel/mcp's transport that may fail against a server that drops the old protocol. This is a vendor-side concern. We should monitor laravel/mcp releases for transport updates, but it is NOT a blocker for our PRs.

### R.9 laravel/mcp dependency chain — Foundation SDK? (addendum #5, verified 2026-09-18)

**Finding: laravel/mcp has ZERO dependency on `modelcontextprotocol/php-sdk`. It is a pure first-party Laravel package by Taylor Otwell.**

Source: `vendor/laravel/mcp/composer.json` requires only Laravel framework components + `symfony/process`. No external MCP SDK. Review of `vendor/laravel/mcp/src/` confirms all code is first-party: `WebClient`, `HttpTransport`, `ClientManager`, OAuth handling, server primitives — all implemented directly within the package.

This **removes** the pre-1.0 Foundation SDK supply-chain risk. The transport state (R.8 above) lives entirely within the first-party Laravel codebase and can be tracked via the laravel/mcp repo's changelog and releases. The only external dependency relevant to MCP is `laravel/ai`'s `suggests: laravel/mcp` — which is why we pin both versions.

### R.7 What "community" must mean here (trust model)

Product evidence points unambiguously at **school self-registration** — option (a):
- Marketing: "KlassApp… operates in the tools educationists already use" (landing meta, landing-v2.blade.php:7) — the *school's own* tools, not third-party-published integrations.
- Spike direction: "Schools have their own workspaces — store TokenSet per school (or per connecting staff user)" (routes/ai.php:21-23).
- Multi-tenant standing rule #13: everything school-scoped end-to-end.
- No marketplace signal exists anywhere in `knowledge.md` or `docs/`.
- A marketplace (option b — third parties publish MCP server definitions other schools opt into) requires a publisher review process, malicious-server threat model (prompt injection via tool results, credential exfiltration endpoints, SSRF), versioning/trust attestation — infrastructure KlassApp has none of. **Designing storage for (b) now would bake in a trust model the product hasn't asked for.**

**Decision recorded in this plan: (C) = school admins register their own workspace credentials for a first-party catalog of connector types.** Marketplace = explicitly out of scope (C.7). This is flagged as the one item needing explicit product-owner sign-off before PR 1 ships.

---

## PART D — Connector shortlist re-evaluation

### D.1 Fresh ranking (not defaulting to Slack)

| Rank | Connector | Verdict | The fresh case |
|---|---|---|---|
| **1** | **Slack** | **Build first** (unchanged — but re-argued) | Only connector with **merged plumbing** (PR #140: named client, mock server, OAuth scaffold, green tests). Official hosted server, OAuth 2.0, workspace-level install, **no per-seat rate-limit model, no Enterprise-gated admin approval**, no AI-credit metering. Product promised it on 6+ surfaces (hero role card "Admin · Slack", orchestration panel, chips, protocol copy); Notion appears once; Miro/Figma zero. Education fit: the product's stated role mapping (staff/admin comms channel). Marginal cost to finish is the lowest of any candidate — the registry (C) it now shares makes it thinner than the prior plan assumed. |
| **2** | **Notion** | **Build second** | Official hosted server (corrected per R.4), OAuth, read+write tools with real school-ops use cases (staff handbooks, SOPs, meeting notes, policy docs — documents schools genuinely keep). Caveats: interactive-OAuth-only (our registry refresh path covers it — R.2), education adoption moderate, write tools must clear the Part B HITL gate. Slotting it #2 **proves the (D) abstraction** with a second real connector at ~catalog-entry cost. **PR4 gated (resolution #3, confirmed 2026-09-18):** before PR4, ground Notion in 2–3 concrete KlassApp workflows from real product-surface signal (the way Slack's "Admin · Slack" positioning was grounded in landing-page copy). Research scope: scan `knowledge.md`, existing product/UX docs, and comparable school-ops patterns (meeting notes, policy docs, parent-comms logs, staff handbooks) that map to real tools in this codebase. Research runs parallel to PR1–3 and blocks nothing; if nothing credible turns up, flag honestly instead of forcing a justification. |
| **3** | **Google Drive (Workspace MCP)** | **Enroll + spike now; build when GA** | The **education jackslot**: schools live in Google Workspace for Education; KlassApp already ships Google OAuth (sign-in "Live" badge, config/services.php `google` block, existing `users.google_id`); Drive is in the marketing trio. But the official MCP servers are **Developer Preview** (per-product endpoints, Google Cloud project enablement, scope config) and **Classroom is absent** — building a product surface on a preview API risks breakage. Action this pass: enroll in the Developer Preview Program, stand up a flagged spike client (`google-drive` catalog entry, `TOSHI_GOOGLE_DRIVE_MCP_*` keys), no product UI, no user promises. Concrete boundary spec: **D.3** (resolution #1). |
| **4** | **Miro** | **Defer — catalog placeholder** | Official server is real and mature (13 tools, read+write, hosted), but: education relevance for KlassApp's K-12 school-admin buyer is weak (business whiteboarding); **OAuth 2.1 admin-approval + Enterprise-plan org-level enablement** means each school needs a Miro plan tier + admin flow it almost certainly doesn't have; AI-credit consumption adds cost opacity; zero product surface ever promised Miro. Revisit trigger: a paying school requests it with a Miro Enterprise/Expert workspace. |
| **5** | **Figma/FigJam** | **Defer — structurally blocked** | *(Corrected second pass:)* rate limits are **not** the binding blocker for the education buyer — Figma **Education plans get 200 calls/day, 10/min** (Dev/Full-on-Professional parity). The blocker is the **per-user authorization model**: interactive per-user OAuth only (enterprise-managed auth solely via Okta XAA-for-Claude) + per-user permission scoping — a service account sees only its own files, so one-platform-connection-per-school cannot work. Revisit trigger: a school explicitly requests it AND accepts a dedicated Dev/Full seat as the service account (with its limited file visibility). |
| — | Google Classroom / Canvas | **Still community-only** (R.5) | No official server; do not build on community token-based stdio servers (trust + hosting mismatch with the remote-MCP architecture). Re-check quarterly; Google is the likely first mover (Workspace preview already shipped — Classroom is the obvious next product). |

### D.2 The shared abstraction (so connector #2 is not a rebuild)

One **`mcp_connectors` catalog** in config (C.4) + five thin per-connector pieces:

1. **Catalog entry** (`config/toshi.php` → `mcp_connectors['slack'|'notion'|...]`): endpoint, auth mode, OAuth client credential env refs, default tool classification (read allowlist / write tools), Skill class, gate flags. Data, not code.
2. **Factory block** in `routes/ai.php` (unchanged construction site): `Client::web($url)->withToken(fn () => SchoolMcpConnector::resolveTokenForRequest('slack'))` — the per-school token closure from R.2. ~15 lines per type, still the only `Client::web()` site on earth that the architecture test allows.
3. **OAuth callback block**: `Mcp::oAuthRoutesFor('<type>', ...)` → upsert registry row (school_id, type, external_team_id, encrypted TokenSet).
4. **Skill**: prefer a **generic `McpConnectorSkill`** (instructions template filled from the catalog entry: "You manage the school's {name} workspace; read tools …; writes require approval") + per-type subclass ONLY where instructions genuinely diverge (Slack's posting semantics vs Notion's page semantics justify thin subclasses: `SlackSkill`, `NotionSkill`). `RouteToConnectorSkillTool` can stay one generic tool parameterized by catalog key (mirrors existing RouteTo* parity; each still gated by `AuthorizesToshiAction`).
5. **Gates**: `TOSHI_<TYPE>_CHANNEL_ENABLED` env per type (default false) + registry `status` per school + `write_mode` per school (default deny). All three must align for a tool call to proceed.

Part B's `ApprovableMcpTool`/`McpWriteGate` apply **identically to every connector** — write classification is read from the catalog/registry, never hardcoded per connector.

### D.3 Google Drive Developer Preview boundary (resolution #1 — the concrete "spike now" spec)

D.1 rank-3's "enroll + spike now" line was too vague to build against as written. This is the spec it now resolves to:

1. **Interface seam.** `App\Contracts\Toshi\GoogleDriveConnectorContract` — the wave-1 read surface only (list files, search, read doc). **All** Skill / RouteTo* / UI code type-hints the contract ONLY; nothing outside the preview implementation may reference the concrete Google client, its endpoint, or its tool names. The contract is defined in this PR wave but only implemented behind the flag in a later spike.
2. **Implementation gating.** The Developer Preview implementation is bound behind `TOSHI_GOOGLE_DRIVE_ENABLED` (default false) and **excluded from the critical path**: no other feature, agent, or connector may depend on its behavior; catalog entry `enabled => false`; no product UI, no user promises.
3. **Dated re-verification marker.** `tests/Architecture/GoogleDrivePreviewReverificationTest.php` fails when `now() > 2027-03-18` unless `TOSHI_GOOGLE_DRIVE_VERIFIED_AT` is set to a date within the previous 90 days — a deliberate CI tripwire forcing manual re-verification against Google's current Workspace MCP docs before anything progresses past spike status. The contract carries a matching dated TODO (`// TODO: re-verify against Google Workspace MCP docs before 2027-03-18`).
4. **Test isolation.** All Drive-preview tests live under `tests/Feature/Toshi/Connectors/GoogleDrive/` tagged `@group google-drive-preview`; CI runs that group as a separate suite step so a Google-side breaking change fails **in isolation**, never across unrelated suites.
5. **No production registry rows until GA.** The preview never writes `school_mcp_connectors` rows against production schools (spike credentials are dev/staging only). When Google announces GA, a PR removes the tripwire and flips `enabled` through normal product review.

---

## PART C — School-registrable MCP connector registry

### C.1 Scope & trust model (from R.7)

A **school admin self-registers their school's own workspace credentials** for a connector type from the first-party catalog. The registry is per-school (`school_id`-scoped end-to-end, standing rule #13). There is **no** third-party publishing surface in this pass.

**Self-registration is final** (resolution #2, confirmed 2026-09-18): no KlassApp admin-approval workflow, no `pending_review` state — registry `status` is `active`/`disabled` only. This does **not** relax Part B's write-gate: every write from every connector, self-registered or first-party, still routes through `ApprovableMcpTool` / human-in-the-loop approval regardless of who registered the connector. **Schema simplification applied:** the `school_mcp_connectors` table has no approval-state columns (no `pending_review`, `approved_by`, `approved_at`, or similar). The only status enum is `active`/`disabled`.

### C.2 Proposed schema

**Catalog (code, not DB)** — `config/toshi.php`:

```php
'mcp_connectors' => [
    'slack' => [
        'endpoint'        => env('SLACK_MCP_URL', 'https://mcp.slack.com/mcp'),
        'auth_mode'       => 'oauth_remote',
        'oauth_client_id' => env('SLACK_MCP_CLIENT_ID'),        // first-party OAuth app — Doppler
        'oauth_secret'    => env('SLACK_MCP_CLIENT_SECRET'),    // Doppler
        'timeout'         => 30,
        'skill'           => \App\AiAgents\Skills\SlackSkill::class,
        'default_write_mode' => 'deny',                          // Part B gate default
        'read_tools'      => ['slack_list_channels', 'slack_search', ...],
        'write_tools'     => ['slack_post_message'],
        'enabled'         => env('TOSHI_SLACK_CHANNEL_ENABLED', false),
        'allows_custom_endpoint' => false,   // Tier 2 (C.6) gate — false for all v1 types
    ],
    'notion' => [ ... ],
    // 'miro', 'figma' — placeholders, 'enabled' => false
],
```

**Why config-not-DB for the catalog:** connector types are product surface, changed by PRs with review; a DB catalog invites the marketplace trust problem (an insertable row becomes an unreviewed integration). The registry table references the catalog and rejects unknown types.

**Registry table** — `school_mcp_connectors`:

| Column | Type / notes |
|---|---|
| `id` | PK |
| `school_id` | FK, indexed — tenant scoping (rule #13) |
| `connector_type` | string, validated against catalog |
| `external_team_id` | provider workspace/team id (unique per provider) |
| `external_team_name` | display name for admin UI |
| `credentials` | **encrypted cast**, JSON TokenSet: `access_token`, `refresh_token`, `expires_at`, `scope`, `token_type` |
| `token_expires_at` | mirrored column for sweep queries (no secret) |
| `auth_mode` | enum: `oauth_remote` (v1: only this) |
| `status` | enum: `active` / `disabled` — **no approval states** (self-registration is final — resolution #2); **never delete** (standing rule #3) |
| `trust_level` | `first_party_catalog` (all v1 rows; column exists for Tier-2 future) |
| `write_mode` | enum: `deny` / `allowlist` / `all` — default **deny** |
| `tool_allowlist` / `tool_denylist` | JSON, nullable — school-level refinement on top of catalog classification |
| `connected_by` | user id (audit) |
| `last_used_at`, `last_refreshed_at` | operational |
| `timestamps` | |
| **unique** | (`school_id`, `connector_type`, `external_team_id`) |

Optional (small, worth it): stamp `connector_id` into `ToshiMcpCallAuditor` audit properties so "which workspace did this write hit" is answerable from the activity log alone.

### C.3 Resolution flow (how a school's call reaches its workspace)

```
Toshi turn (school context)
  → Skill exposes ApprovableMcpTool-wrapped primitives from Mcp::client('slack')
  → named client factory (routes/ai.php, built once per process, memoized)
      Client::web($catalogEndpoint)
        ->withToken(fn () => SchoolMcpConnector::resolveTokenForRequest('slack'))
  → per-call (HttpTransport.php:182 evaluates the closure EVERY request):
      resolveTokenForRequest('slack'):
        1. school context = auth user's school_id
           (or ToshiMcpClient actingAs override for queued jobs)
        2. row = active school_mcp_connectors(school_id, 'slack')
        3. none → throw ConnectorNotConnected(school, 'slack')
           → Skill catches → friendly "Connect Slack in Settings → Integrations"
        4. token near expiry → refresh via OAuthClient::refreshCredentials()
           (mutex-guarded, persist encrypted, update token_expires_at)
        5. return access_token
  → HTTP call → AuditingWebClient::callTool audits AFTER execution
  → Part B gate sits ABOVE this: write-classified tools paused via
    ApprovableMcpTool before callTool ever runs
```

Cross-school isolation is structural: the token closure can only see the current school's rows; queued contexts must pass an acting user explicitly (`ToshiMcpClient::named` + `actingAs`, verified pattern). An adversarial test mirrors `SchoolAdminWhatsAppAdversarialIsolationTest`: school A's Toshi can never resolve school B's credentials.

Token refresh is **app-owned** (not vendor-baked): `McpConnectorTokenRefreshService` — scheduled sweep on `token_expires_at` + refresh-once-on-401 retry. This covers Notion's interactive-OAuth-only caveat (R.4).

### C.4 OAuth connect flow (per school)

1. Admin clicks Connect {Connector} (school-scoped settings page, superadmin-visible).
2. `GET mcp/oauth/slack/connect` (vendor-registered route, 'web' middleware) — initiated with the admin's session; the callback handler receives `TokenSet`.
3. Handler upserts the registry row: resolves `school_id` from the authenticated admin, provider `team_id` from the token set / provider metadata, stores credentials encrypted, `status=active`, `write_mode=deny`.
4. Revocation: admin action → `status='disabled'` (never delete; resolution #2 — `disabled` is the only non-active state) — the token closure then fails closed with `ConnectorNotConnected`.

### C.5 Trust posture for school-registered connectors (defaults)

- **Reads**: only catalog-classified read tools, exposed via the Skill in the school's Toshi (admin) context only.
- **Writes**: `write_mode=deny` by default. Opting in (`allowlist` + tool list) still routes **every write call** through Part B's `ApprovableMcpTool` → `Approval::required()` → human decision → audit row with `approver_id` ≠ null. No connector, first-party or school-registered, gets an ungated write — the gate keys off the **classification**, not the connector brand.
- **Tool filtering**: effective toolset = catalog reads ∩ school allowlist (minus denylist); write tools additionally require write_mode. The Skill exposes only the effective toolset to the model.
- **Credential hygiene**: encrypted casts; tokens never logged (auditor logs tool args/results, never the credential); Doppler only for first-party OAuth client id/secret (our app's identity), school workspace tokens in the encrypted registry column — never in env.
- **Audit identity**: existing `acting_user_id` (conversation/request participant) + `approver_id` (HITL resolver) semantics unchanged; `connector_id` + school_id stamping added per C.2.

### C.6 The architecture invariant (how the test evolves — or doesn't)

**Tier 1 (this plan): no new construction sites.** Every connector type in the v1 catalog is a **static named client** registered in `routes/ai.php`, using the vendor's per-call token closure for tenancy. `McpClientConstructionTest` stays byte-for-byte green — "school-registrable" means *registering credentials and enablement*, not *registering servers*.

**Tier 2 (explicitly deferred — arbitrary custom endpoints):** if/when the product wants "bring your own MCP server URL", add exactly ONE audited construction site and an equally structural replacement invariant:

- New allowlist entry: `app/Services/Toshi/SchoolMcpClientFactory.php` (the only class besides `routes/ai.php` permitted to call `Client::web()`).
- Its `build()` MUST route through `AuditingMcpClientManager::wrap()` (or return only `AuditingWebClient` instances) — enforced by a new architecture test asserting the factory's source: wrap-before-return, no bare `WebClient` returns, plus https-only + private-range-blocklist + registry-row-existence validation before construction.
- Dynamic client names must embed school id (`school.{id}.{slug}`) so audit rows are tenant-traceable, and `AuditingMcpClientManager::build()` gains a registry fallback for that name pattern — all wraps still flow through the existing `wrap()`.

**Do not build Tier 2 speculatively** — a custom-URL feature is an SSRF/exfiltration surface with no current user demand. The schema's `trust_level` column exists so Tier 2 doesn't need a migration later.

### C.7 Explicitly out of scope (first pass)

- **Marketplace**: third parties publishing connector definitions for other schools (trust model, review process, malicious-server threat model — none of this infrastructure exists; per R.7 this needs explicit product demand first).
- Custom/arbitrary endpoint URLs (Tier 2, C.6).
- Per-**user** connectors (only per-school workspaces this pass; spike comment allows "or per connecting staff user" later — `connected_by` already records who).
- Inbound event webhooks from connectors (Slack events → Toshi), interactive approval buttons inside Slack/Notion UIs (the WhatsApp-bridge token pattern is the blueprint when this arrives).
- Billing/quota metering per school per connector.
- Implementations of Miro/Figma beyond catalog placeholders with documented deferral (D.1).

---

## Execution order (proposed)

1. **PR 1 — (C) registry core:** migrations (`school_mcp_connectors`), catalog config, `resolveTokenForRequest` + refresh service + `ConnectorNotConnected` typed flow, refactor the spike's `routes/ai.php` slack block onto the token-closure pattern (still the only construction site), encrypted-cast + isolation + refresh tests, `McpClientConstructionTest` untouched and green. *(Supersedes the prior plan's `school_slack_mcp_credentials` — same need, generalized.)*
2. **PR 2 — (B-1) HITL gate:** from the prior plan, unchanged; `mcp_write_gates` config now reads from the (C) catalog/registry (`write_mode`, classifications) so the gate covers every connector automatically.
3. **PR 3 — Slack wave-1:** OAuth connect UI + `SlackSkill`/`RouteToConnectorSkillTool` + gates + empty-state badge flip + tests + Playwright (375/414/768/1280). Slimmer than the prior plan's PR 3 — registry is shared.
4. **PR 4 — Notion (#2):** proves the abstraction: catalog entry + factory block + thin `NotionSkill` + gates + UI. No architecture changes expected — if any are needed, the abstraction failed and gets fixed before connector #3. **Gated on the resolution-#3 use-case research** (D.1 rank-2) — explicitly not a blocker for PR1–3.
5. **Background track:** Google Workspace Developer Preview enrollment + flagged `google-drive` spike client (no product UI). Quarterly re-check: Classroom/Canvas official MCP (R.5), Figma service-account limits, Miro education demand.

**Product sign-off (received 2026-09-18):** school self-registration is **final** — no KlassApp approval step (resolution #2); marketplace explicitly out of scope. Storage is designed to not preclude a later reviewed-catalog extension.

**Go-ahead received 2026-09-18** — resolutions #1–#3 applied above; PR1 (registry core) proceeding on a feature branch.
