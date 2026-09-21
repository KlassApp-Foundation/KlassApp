# Implementation Plan: Direct Google REST Integrations (Thread A) + Custom Connector Registry (Thread B)

> Status: **GO-AHEAD CONFIRMED 2026-09-21 (all four items). Classroom wave-1 code is BUILT (branch `feat/google-classroom-wave1`) — genuinely code-complete as of 2026-09-21 session #3 (verification pass): Skill, RouteTo tool, real server + 2 real tools, mock server + 2 mock tools, OAuth controller + routes, catalog entry, 20 tests green (6 skill + 3 OAuth + 4 isolation + 6 Slack regression + 1 architecture = 54 assertions), re-run clean after killing zombie processes from an earlier hang. Two permanent architectural findings recorded below (mock-registration gating asymmetry; local-transport token resolution). **CODE COMPLETE, NOT END-TO-END VERIFIED** — no live agent-loop test has ever been made against this code; the same two prerequisites (staging LLM fix + Slack §6c E2E) still gate enablement, and finishing this code does not change that. Stays dormant: TOSHI_GOOGLE_CLASSROOM_ENABLED=false everywhere (verified: absent from .env/.env.example/Doppler/Cloud; config default false), no deploy.**
>
> **⚠️ NAMED DECISION — SEQUENCING CONSTRAINT DELIBERATELY OVERRIDDEN (2026-09-21, later):** the product owner **explicitly** chose to start Classroom wave-1 code ahead of the two prerequisites. The earlier implementation pass (session #1) built server + tools + catalog + OAuth but missed the Skill class (the config referenced a nonexistent class). This session (session #2, 2026-09-21 ~14:00) corrected that: built GoogleClassroomSkill (mirrors SlackSkill), RouteToGoogleClassroomSkillTool (mirrors RouteToSlackSkillTool), SpikeGoogleClassroomMockServer + 2 mock tools, and 9 passing tests. The code is now genuinely complete on branch `feat/google-classroom-wave1`.
> - Build everything genuinely verifiable **without** a live LLM loop ✅ (done)
> - The result is labeled **CODE COMPLETE (corrected), VERIFICATION PENDING** — never "merge-ready" in the Slack-PR3 sense
> - Direct-invocation tests (the Slack precedent's pattern) are clearly labeled as standing in for the LLM
> - **Stays dormant**: no flags flipped, no staging deploy of live mode, until explicitly instructed. The two prerequisites (LLM fix + Slack §6c E2E) still gate actual enablement, exactly as they gate Slack's.
>
> **Architectural findings from the wave-1 build (permanent, not workarounds):**
>
> 1. **Mock-server registration is NOT flag-gated, and that is deliberate — but the LIVE connector IS hard-gated.** `routes/ai.php` registers `Mcp::local('google-classroom-mock', SpikeGoogleClassroomMockServer::class)` **unconditionally**, unlike Slack's mock which sits inside `if ($mode === 'mock')`. This asymmetry is safe and intended, verified directly (not assumed from parity): (a) the mock server is a stdio *local* server — it has **no HTTP route**; the only way to reach it is an in-process `artisan mcp:start google-classroom-mock` subprocess, which only tests spawn (the test's `ensureMockGoogleClassroomClient()` registers the named client); it can never be reached over HTTP by any end user in any environment, flag or no flag. (b) Its two tools return hard-coded synthetic fixture data and make **no network calls and no registry lookups** — there is nothing meaningful for it to do or leak. (c) By contrast, everything with real consequence — the real `GoogleClassroomServer`, the `google-classroom` named client, and both OAuth routes — sits inside `if (env('TOSHI_GOOGLE_CLASSROOM_ENABLED', false))` (default false), so in a non-test environment with the flag unset, **no client is registered and no route exists at all**. Verified: `TOSHI_GOOGLE_CLASSROOM_ENABLED` appears nowhere in `.env`, `.env.example`, Doppler, or Cloud, and the config default is `false`.
>
> 2. **Local MCP transport has no per-request token-resolution point — a permanent architectural difference from remote (web) connectors.** With `Client::web()` (Slack), the per-school token is resolved in a closure inside the named-client registration (`->withToken(fn () => SchoolMcpConnector::resolveTokenForRequest(...))`), so `ConnectorNotConnected` can fire **at tool-listing time** — Slack's `not_connected` test exercises the Skill's `tools()` method and expects the status-tool-only fallback. With `Client::local()` (Classroom), the client is a stdio subprocess and the token closure does not exist; `Mcp::client('google-classroom')->tools()` succeeds regardless of connection state, and the per-school token is resolved **inside each tool's `handle()`** via `SchoolMcpConnector::resolveTokenForRequest()`. Consequently Classroom's `not_connected` test was **narrowed from testing `tools()` to testing the tool's `handle()`-level structured `not_connected` error** — which is the real user-facing failure mode for local transport. **This is not a workaround to revisit — it is a fact of the architecture.** Anyone building the next local-transport connector should know up front: (i) an unconnected school still sees the full tool list (not a status tool); (ii) the not-connected signal must be surfaced by each tool at call time; (iii) a `ConnectorNotConnected` catch in `tools()` is dead code for local transport (it exists in `GoogleClassroomSkill` only for defensive symmetry and can never fire from the local client path).
>
> **The approval, verbatim from the product owner (2026-09-21):**
> 1. **Classroom read-only wave-1 — APPROVED as scoped**: `google_classroom_list_courses` + `google_classroom_list_coursework`, scopes `classroom.courses.readonly` + `classroom.coursework.students.readonly`, no roster/email/guardian scopes, self-hosted local MCP server (option b, `SpikeSlackMockServer` pattern via `Mcp::local()`), standard Tier-1 catalog entry — no new registry machinery.
>    **HARD SEQUENCING CONSTRAINT — do not start implementation until both prerequisites are INDEPENDENTLY CONFIRMED COMPLETE, with evidence (do not assume either is done just because time has passed; if either is unmet when picked up, STOP and report that instead of proceeding):**
>    - (a) the staging LLM config gap is fixed — `OPENAI_COMPATIBLE_URL`/`OPENAI_COMPATIBLE_MODEL` set (as of 2026-09-21 they are NULL on staging, verified via Cloud Commands) **and `php artisan toshi:llm-health` passing on staging**;
>    - (b) Slack's deferred end-to-end verification is **actually run and passes** — checklist §6c: read tool E2E, write-gate pause against real Slack, approve/reject audit correctness, real-vs-mock response shape comparison.
>    When Classroom implementation starts, it is **its own task with its own go-ahead** — it must not be folded into whatever unblocks the prerequisites.
> 2. **Calendar — deferral acknowledged** (not-cleared: duplicates the in-app `Events` model; same posture as Notion). No action.
> 3. **Drive — no-build acknowledged**, the CASA/verification-cost finding on record as an additional reason beyond the GA/contract blocker. No action.
> 4. **Thread B — continued deferral acknowledged**, with the three sketch corrections (curated manifests not live discovery, reviewed templates not raw URLs, mandatory write-gate declaration) recorded for whenever picked up. No action.
> Research pass: 2026-09-21, direct source verification against Google's live official pages (fetched, not search-snippet): the OAuth 2.0 Scopes for Google APIs list, Drive API Terms of Service, Drive/Classroom/Calendar scope guides, the Google APIs Terms of Service, the Workspace User Data Developer Policy, the Restricted Scope Verification page, and the OAuth App Verification / App Audience help pages. Plus direct code re-reads: `routes/ai.php`, `config/toshi.php`, `app/Services/Toshi/McpWriteGate.php`, `app/Mcp/Servers/SpikeSlackMockServer.php`, the registry migration, and `vendor/laravel/mcp` v0.8.2 server sources.
> Companion doc: `docs/plans/toshi-mcp-connector-registry-and-shortlist-reeval-plan.md` (Part C registry, Part D shortlist — both updated with pointers to this doc).

---

## THREAD A — Direct (non-MCP-vendor-hosted) Google integrations

### A.1 GA + ToS verification — all three APIs are genuinely GA, plain OAuth 2.0, no preview terms

Verified directly against Google's official pages:

| API | GA status | OAuth model | Preview-program restriction? |
|---|---|---|---|
| **Drive API v3** | GA (scopes list + API-specific-auth guide; ToS last modified 2022-07-08, doc updated 2026-09-03) | Standard OAuth 2.0, own GCP client ID, user consent | **None.** The Developer Preview restriction applies ONLY to the `drivemcp.googleapis.com` MCP server. The plain REST API (`drive.googleapis.com`) has no equivalent clause anywhere in its ToS or policy pages. |
| **Calendar API v3** | GA (scopes list "Calendar API, v3"; overview describes plain RESTful access) | Standard OAuth 2.0 | **None found** in Google APIs ToS or the Workspace User Data Developer Policy. |
| **Classroom API v1** | GA (scopes list "Google Classroom API, v1"; product page updated 2025-04-16) | Standard OAuth 2.0 | **None.** Rostering/coursework management is the API's *intended purpose*. |

**What the REST-API terms DO impose (different obligations, not blockers):**

- **Google APIs Terms of Service** (general): standard clauses — confidentiality of developer credentials, no data-resale, prohibitions list (ITAR data, etc.). **No "can't serve customers outside your domain" clause exists anywhere** — that clause exists only in the Workspace Developer *Preview Program* terms (which govern the MCP server).
- **Drive API ToS** prohibited uses: no using Drive as a CDN, no file-cloning/sharding tools, no *backup of the developer's own app content to Drive*, no bulk video distribution. KlassApp's candidate use cases don't touch any of these.
- **Workspace User Data Developer Policy**: approved use cases explicitly include *"productivity and educational applications (…task management, note taking, workgroup communications, and classroom collaboration applications)"* — KlassApp is squarely an approved type. Restricted-scope apps must meet CASA security requirements; privacy policy required; data-deletion-on-request obligations.
- **Scope-classification costs (the real difference between the three APIs):**
  - `drive.file` — **non-sensitive**: no verification at all, but *per-file only* (files the app created or the user opened through our app). **This kills the only credible Drive use case** ("search the whole staff shared Drive" needs `drive.readonly`).
  - `drive`, `drive.readonly`, `drive.metadata*` — **restricted**: apps requesting these from/through a third-party server must undergo an **annual security assessment by a Google-approved third party** (CASA) — weeks of process and real cost, recurring.
  - `classroom.*` readonly scopes, `calendar.*` readonly scopes — **sensitive**: standard OAuth app verification (questionnaire + privacy policy + home-page requirements, brand verification first, ~2–3 business days + review), **not** the annual third-party CASA assessment.
  - **Unverified cap**: unapproved sensitive/restricted scopes = **100 new users, lifetime of the project, non-resettable**; Testing publishing mode = 100 test users AND **refresh tokens expire every 7 days** — Testing mode is therefore unusable for the registry (we store long-lived refresh tokens). Real rollout requires publishing "In production" + verification for whatever sensitive scopes we request.

**Conclusion A.1**: the REST path is contractually open. The MCP server's blocker (Program Term iv) does not exist for the REST APIs. The real costs are scope classification: Classroom (sensitive, light verification) is the cheapest; Drive's useful scopes are restricted (annual CASA); Calendar is sensitive.

### A.2 Use-case bar — honest verdicts (the same bar Notion failed, and Drive-via-MCP failed)

- **Classroom — ✅ CLEARS THE BAR (read-only wave-1).** KlassApp *is* a school system; Classroom is the adjacent system in the exact same domain (rosters, coursework, grades). Ugandan schools on Google Workspace for Education demonstrably live in Classroom. Concrete grounded workflow: *"Toshi, list my Classroom courses and what's due this week"* — a teacher (KlassApp's own teacher persona) asking about their Google Classroom coursework. This is the first connector whose use case is grounded in the product's core identity, not marketing chrome. **Wave-1 scope (deliberately narrow):** `classroom.courses.readonly` + `classroom.coursework.students.readonly` — list the connecting teacher's courses, list coursework + due dates. **No roster scope** (`classroom.rosters` reads student names/emails — higher-stakes, excluded from wave-1), **no email/photo profile scopes**, no guardian links, no submission grades. Two read tools: `google_classroom_list_courses`, `google_classroom_list_coursework` (with course id + due-window params).
- **Calendar — 🟡 NOT CLEARED for wave-1.** Honest finding: KlassApp already has an in-app calendar (`Events` model + `ListEventsTool` in Toshi). The read use case ("what's on the school calendar") **duplicates an in-app surface** — the exact failure mode that sank Notion and Drive-via-MCP. The credible use case is *write-side* ("sync school term dates / exam timetable to the school's Google Calendar") — the Calendar chip exists in the landing hero and connector grid, so there IS marketing grounding, but it's a write flow: it would go through the full HITL write gate, and "Toshi, publish the term calendar to Google Calendar" as a per-event approval flow is clunky. **Verdict: defer; revisit when a concrete sync workflow is product-identified** (e.g., a "sync this term's exam timetable" button with per-run approval — a batch design, not an agent-loop tool).
- **Drive — ❌ STILL FAILS THE BAR, and the REST path makes it *worse*, not better.** The A.1 finding is decisive: the only credible use case (search/read the shared staff Drive) requires `drive.readonly` — a **restricted scope** with an **annual third-party CASA assessment** — while the verification-free scope (`drive.file`) is per-file only and can't search. So Drive-via-REST = same unmet use case as D.3, *plus* the heaviest verification burden of the three. The D.3 "waiting for GA" record stands for the MCP path; **the REST path is contractually open but fails on use case + scope cost. Do not build.**

### A.3 Architecture decision — **(b) self-hosted local MCP servers wrapping the REST APIs**

The decisive findings:

1. **laravel/mcp 0.8.2 fully supports building servers** — `Server` base class, `#[Name]/#[Version]/#[Instructions]` attributes, tool classes, `Mcp::web()` (HTTP) and `Mcp::local()` (stdio, in-process) registration. **The app already builds one**: `SpikeSlackMockServer` is registered in production `routes/ai.php` via `Mcp::local('spike-slack-mock', …)` whenever `SLACK_MCP_MODE=mock`. This is not new scaffolding — it's a pattern already running in production code.
2. **Local (stdio) servers bypass the transport-era gap entirely.** Both ends of the conversation are laravel/mcp 0.8.2 in one PHP process — the 2026-07-28 stateless-era question (the prerequisite recorded in D.3 for remote Google servers) **does not arise**. Google's transport era is irrelevant when we call `drive.googleapis.com`/`classroom.googleapis.com` as plain HTTPS REST from inside our own server tool code.
3. **The entire safety stack is name-agnostic and free.** `AuditingMcpClientManager::wrap()` wraps any named client (that's the audit guarantee — verified in the companion doc's research note); `McpWriteGate::isWrite()` classifies by catalog `read_tools`/`write_tools` lists; `ApprovableMcpTool` gates writes; `school_mcp_connectors` stores tokens; `McpConnectorTokenRefreshService` sweeps them. Option (a) — plain Skills with their own OAuth — would build a **second, parallel OAuth+token+audit path** that bypasses every piece of infrastructure this series built and battle-tested on Slack. It would duplicate token storage/refresh, skip the write gate by construction (Skills' tools aren't MCP tools — `AuditsMcpToolCalls` wouldn't see them), and create the "duplicated business logic across render paths" bug-pattern this project has already been burned by (known bug pattern #1).
4. **Registry/OAuth fit is natural.** Google REST OAuth = our own GCP client ID + Google's fixed authorization/token endpoints (standard authorization-code flow, no RFC 7591 dynamic registration — we implement the consent redirect ourselves, exactly like the existing `GoogleAuthController` sign-in flow does; Google user tokens come back as a plain OAuth TokenSet). Store in the registry as `auth_mode=oauth_remote`, `connector_type=google_classroom` (etc.); the local server's tool code resolves the school's row via the existing per-request resolution (`SchoolMcpConnector::resolveTokenForRequest`) — being in-process, it doesn't even need the client token closure.

**Per-product server shape (when a product is green-lit):** `app/Mcp/Servers/GoogleClassroomServer` (extends `Server`, `Mcp::local('google-classroom', …)` in `routes/ai.php`), catalog entry in `config/toshi.php` (`read_tools` = the two tools, `write_tools` = `[]` — empty by design), OAuth connect route reusing the `Mcp::oAuthRoutesFor` callback pattern with Google's endpoints, one `connector_type` row per school. **Wave-1 has zero write tools, so `write_mode=deny` on every row and nothing to gate — but the write-gate config still ships enabled for the connector so any future write tool lands gated by default (fail-closed, never the ungated-slack_post_message failure mode from the go-live checklist §0).**

### A.4 laravel/mcp server-building support — confirmed in installed version

Covered above (v0.8.2: full server SDK, both transports, production precedent in-repo). **The 1.x upgrade is NOT a prerequisite for Thread A** — it remains a prerequisite only for *remote* Google MCP servers (D.3), which Thread A deliberately does not use.

### Thread A recommendation

**Build Classroom read-only wave-1 (option b architecture) — the only Google product that clears the use-case bar. Scope: 2 read tools, 2 sensitive scopes, no roster/email/guardian scopes, write-gate armed with zero write tools. Defer Calendar (in-app duplication; revisit on a concrete sync workflow). Do not build Drive (use case unmet + restricted-scope CASA cost).** Verification obligations for Classroom rollout: GCP OAuth consent screen (External, In production), brand verification, sensitive-scope verification (questionnaire + privacy policy + public home page) before >100 users can consent; testing mode only for the dev/staging spike (with its 7-day token expiry understood).

Wave-1 acceptance criteria (for the eventual go-ahead): `google_classroom_list_courses` + `google_classroom_list_coursework` return real course/due-date data for a connected test teacher; every tool call audited with connector_id + school_id; write-gate config present with `write_tools=[]`; OAuth connect route byte-exact redirect URI; Playwright at 375/414/768/1280 for the connect UI; architecture tests still green (no new `Client::web()` construction sites — `Mcp::local` registration is not a construction site).

---

## THREAD B — Custom/community connector registry (Part C Tier 2)

### B.5 What Part C actually deferred vs shipped (re-read, confirmed)

- **Shipped (Tier 1):** static named clients only — every connector type is registered in `routes/ai.php`, `connector_type` is validated against the `config/toshi.php` catalog, `allows_custom_endpoint=false` on all v1 entries, `trust_level` column exists (default `first_party_catalog`) but only ever holds the default. "School-registrable" = registering *credentials and enablement*, never servers.
- **Explicitly deferred (Tier 2, C.6):** arbitrary school-supplied MCP server URLs — with a pre-sketched shape: one new audited construction site (`SchoolMcpClientFactory`), wrap-before-return architecture test, https-only + private-range blocklist, dynamic `school.{id}.{slug}` client names with a registry fallback in `AuditingMcpClientManager::build()`. And the standing warning: *"Do not build Tier 2 speculatively — a custom-URL feature is an SSRF/exfiltration surface with no current user demand."*
- **Out of scope entirely (C.7):** third-party marketplace publishing, per-user connectors, inbound webhooks.

### B.6 What changes would be needed for school-registered NEW connector types

The C.6 sketch remains valid as a starting point but is **incomplete** on three axes the original didn't cover (found in this pass):

1. **Tool discovery vs classification.** `McpWriteGate::isWrite()` classifies by *catalog tool-name lists*. An arbitrary server's tools aren't in any catalog — and **dynamic discovery from the remote server must NOT feed classification** (a hostile server could rename tools between the discovery call and the actual call — a TOCTOU hole). KlassApp must **curate the tool manifest per registered server** (or per "community connector template") and classify against the curated manifest, never against live server metadata.
2. **Connector-type validation.** `connector_type` is validated against the config catalog — school-registered custom connectors need either a separate validated type namespace (e.g., `custom:` prefix) or a "community template" registry table (reviewed by KlassApp before any school can register it). The latter is strongly preferable (see B.7).
3. **OAuth.** Arbitrary servers may or may not do OAuth; the registry's `auth_mode` enum only has `oauth_remote`. Custom entries need `auth_mode` extension (e.g., `oauth_remote` with school-supplied client credentials vs KlassApp-managed — an unmanaged surface that needs its own review) or `none`/`bearer_token` modes.

### B.7 Trust model — proposed default posture for untrusted arbitrary servers

- **Read-only by default, writes structurally absent**: a non-catalog connector's curated manifest ships with `write_tools=[]` and **no path to add write tools without a KlassApp-side review** — the manifest is KlassApp-curated data (a template), never school-editable. This is stronger than `write_mode=deny` on the row (which is default anyway): there is simply no write tool to classify. Writes on a school-custom connector require the template itself (code-reviewed by us) to add a write tool, which then flows through the existing `ApprovableMcpTool` gate like any first-party write.
- **`McpWriteGate` fail-closed review — confirmed adequate with one caveat**: the gate returns `isWrite()===true` for unknown connector config, unknown catalog entry, or invalid mode — i.e., unknown = treated as write = gated/denied. **The caveat: the master switch (`TOSHI_MCP_WRITE_GATES_ENABLED`, default false) returning `false` for everything means "no gate"** — the documented §0 failure mode. For school-custom connectors this is worse than for first-party ones, so the posture adds: **custom-template connectors require the master switch on at the template level** (a template declares `requires_write_gates: true`, and the factory refuses to build the client if the master switch is off — a hard precondition, not a deploy-ordering hope).
- **Template registry, not raw URLs**: the shape that satisfies both trust and demand: schools register against a **reviewed community template** (name, server URL vetted by KlassApp, curated tool manifest, declared read/write classification) — `trust_level='community_template'`. Raw arbitrary URL entry (`trust_level='school_custom'`) stays **deferred** (SSRF/exfiltration per C.6) until a concrete user demand exists that a template can't serve.
- SSRF protections per the C.6 sketch (https-only, private-range blocklist, registry-row-existence check) apply to both.

### B.8 Does Thread A need Thread B? — **No. Confirmed independent.**

Thread A's Google connectors are **static named clients** (`Mcp::local('google-classroom', …)` in `routes/ai.php`) with catalog entries, exactly like Slack — Tier 1, zero new construction sites, zero arbitrary URLs, zero template machinery. The registry schema, `trust_level` column, catalog validation, write gate, and audit wrap all work unchanged. **Thread B remains a separate, independent, still-deferred decision about genuine third-party/community servers** — with the B.6/B.7 findings (curated manifests, template registry, master-switch precondition) recorded here so the eventual Tier-2 build starts from this, not from scratch.

---

## Decision summary

| Question | Decision |
|---|---|
| Google REST APIs GA + no preview restriction? | ✅ Confirmed (Drive/Calendar/Classroom all GA, plain OAuth, no Term-iv equivalent) |
| Drive via REST | ❌ Do not build — use case unmet (same as D.3) + the useful scope (`drive.readonly`) is restricted → annual CASA assessment |
| Calendar via REST | 🟡 Defer — read duplicates in-app `Events`; revisit only on a concrete write-side sync workflow (batch design + full write gate) |
| Classroom via REST | ✅ **Build read-only wave-1** — 2 tools, 2 sensitive scopes, no roster/email/guardian scopes; light verification path |
| Architecture | **(b) self-hosted local MCP servers** — plugs into registry/audit/write-gate; pattern already in production (`SpikeSlackMockServer`); no transport-era upgrade needed; no new OAuth path |
| Thread B needed for Thread A? | **No** — Thread A ships entirely within Tier 1 catalog-driven model |
| Thread B | Keep deferred; posture recorded (template registry, curated manifests, read-only default, master-switch precondition); raw URLs stay out until demand |

## Go-ahead status (updated 2026-09-21)

**CONFIRMED on all four items** — see the status header for the verbatim record. Practical restatement:

1. **Classroom read-only wave-1: approved as scoped.** Implementation start is gated on prerequisites (a) LLM gap fixed + `toshi:llm-health` passing and (b) Slack §6c E2E run and passing — **both must be confirmed with evidence at the start of the Classroom task itself**; if either is unmet, stop and report. When confirmed, the Classroom task re-scopes itself (its own go-ahead — this document's approval transfers, but the task must still present its own implementation plan).
2. Calendar deferral — acknowledged, recorded (D.1 companion plan).
3. Drive no-build — acknowledged, recorded (D.3 companion plan + this doc A.2).
4. Thread B deferral — acknowledged, posture recorded (C.6 companion plan + this doc B.6/B.7).
