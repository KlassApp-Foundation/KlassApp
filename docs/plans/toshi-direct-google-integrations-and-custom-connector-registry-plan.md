# Implementation Plan: Direct Google REST Integrations (Thread A) + Custom Connector Registry (Thread B)

> Status: **PLAN ONLY — awaiting go-ahead. Nothing implemented, nothing enabled.** 2026-09-21.
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

## Go-ahead requested

1. **Green-light Classroom read-only wave-1** (Thread A, option b) as the next connector PR after Slack wave-1 E2E verification completes — gated behind `TOSHI_GOOGLE_CLASSROOM_ENABLED`, default false, no product UI until verification obligations are understood (see A.1).
2. Acknowledge Calendar deferral and Drive no-build (recorded in D.1/D.3 of the companion plan).
3. Acknowledge Thread B deferral with the B.7 posture recorded (no build now).
4. **Prerequisite dependency note**: staging Toshi LLM gap must be fixed first — any connector's E2E verification needs a working agent loop (same blocker as Slack's deferred verification).
