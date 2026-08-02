# Toshi Google MCP Connector — Design Audit (Part A)

> **Branch:** `audit/toshi-google-connector` off `origin/main` @ `20c54ad`  
> **Date:** 2026-08-02  
> **Scope:** docs-only investigation + proposals — **no product code**.  
> **Repo worktree:** `/Users/mac/projects/KlassApp-audit-google-connector`  
> **Framing:** Connectors stay **narrow and role-scoped**, not OpenClaw-style broad personal-assistant integration. Prefer depth inside Toshi’s existing safety architecture (memory, digests, role tools) over matching what a general assistant would offer.

---

## Tip confirmation (`origin/main`)

| Claim | Status on tip `20c54ad` |
|---|---|
| Deputy Admin (ug4) | **Merged** — PR #137 (`feature/toshi-deputy-admin-role`) |
| Nine role surfaces | **Present** — Blade allowlist `[1, 3, 4, 5, 11, 8, 10, 6]`; `ToshiSdkV2Service` routes ug4/5/6/7/8/10/11 + platform ug1; ug3 via orchestrator/skills |
| Preference memory | **Not on tip** — lives on `origin/feature/toshi-preference-memory` (`0a34630`, `9e5b40b`); no preference tables in live schema at audit time |

Nine operational personas on tip: Super Admin (1), School Admin (3), Deputy Admin (4), Teacher (5), Student (6), Parent (7), Librarian (8), Receptionist (10), Accountant (11).

---

## google_token reality (evidence)

### Verdict: **login-only artifact column — not reusable for Google APIs**

| Fact | Evidence |
|---|---|
| Columns | Migration `2026_06_20_171448_add_google_fields_to_users_table.php`: `google_id` (unique string), `google_avatar` (text), `google_token` (text, nullable) |
| Live DB | Among users with Google linkage: **0 nonempty `google_token`**, 1 null token, max length null (Boost `database-query`) |
| What is written | New-user path sets `'google_token' => ''` only (`GoogleAuthController.php:105`). `updateGoogleData()` updates **`google_id` + `google_avatar` only** — never `$googleUser->token`, `refreshToken`, or `expiresIn` (`:247–257`) |
| Mass-assignment gap | `User::$fillable` does **not** include `google_id` / `google_avatar` / `google_token` (`User.php:52–54`). Create-array google fields are ignored by mass assignment; linking on existing users works via direct property set + `save()` |
| OAuth scopes | Socialite `GoogleProvider` defaults: **`openid`, `profile`, `email` only** — no Calendar / Gmail / Drive scopes. Controller calls `Socialite::driver('google')->redirect()` with **no** `scopes()` / `setScopes()` / `withScopes()` |
| Config | `config/services.php` `google` key: `client_id`, `client_secret`, `redirect` only — no API scopes, no refresh-token storage config |
| Routes | `GET /auth/google`, `GET /auth/google/callback`, onboarding `GET|POST /welcome` (`routes/web.php`) |
| Socialite token shape (unused) | Socialite `Two\User` exposes `$token` (access token), `$refreshToken`, `$expiresIn`. Google also returns `id_token` in the token response for OpenID — **none of these are persisted** in KlassApp |

**Conclusion:** Existing Google OAuth is **sign-in / account-link only**. Treat it as **unrelated** to any future Google API or MCP connector. Do **not** overload `users.google_token` for Calendar/Gmail/Drive — if API access is ever approved, use a separate credential store (refresh token, scopes, expiry, per-user consent) and a **separate** OAuth consent with explicit API scopes + `access_type=offline`.

---

## Native calendar conflict

### Verdict: **`events` is the school-wide calendar source of truth — Google Calendar would collide**

| Aspect | Native KlassApp |
|---|---|
| Table | `events` (school-scoped, soft deletes) |
| Categories | enum: `culturals`, `education`, `exam`, `holidays`, `meeting` |
| Audience | `select_type`: `school` \| `class` \| `alumni` |
| Exam sync | `Exam::syncCalendarEvents()` — delete+rebuild; creates exam date + marks-deadline rows with `category='exam'`, FK `exam_id` |
| Timetable sync | `TimetableSlot::syncCalendarEvent()` — weekly recurring `category='education'`, FK `timetable_slot_id` |
| Toshi already reads it | Role tools: `ViewEventsTool` (Teacher/Student/Receptionist), `EventsTool` (Parent) — school calendar via native data |

Marketing docs (`docs/community/ecosystem.md`) casually describe “Google Calendar → parent WhatsApp.” That is **aspirational product copy**, not implemented sync. Implementing Google Calendar as SoT or as a two-way mirror would undermine the exam/timetable auto-sync already on `events`.

---

## Recommended starting scope (narrow)

**Do not start with Google as an MCP connector.** If Google is revisited later, the only conservative slice worth considering is **optional one-way personal visibility** (see next section) — still not a first connector.

### Why skip / defer each Google surface

| Service | Conflict / risk | Fit under non-OpenClaw framing |
|---|---|---|
| **Calendar** | Direct conflict with native `events` SoT + exam/timetable sync | Poor as MCP “inbox of time.” Personal push only, if ever |
| **Gmail** | No native equivalent, but high PII, mailbox sprawl, staff/parent privacy, hard to role-scope | **Reject for v1–v2.** Personal-assistant shape |
| **Drive** | No native document store conflict; attractive for report/circular attach | Only if a **single** school-admin tool (“attach this circular PDF from a pre-approved folder”) — still not first |

### Narrow principle for any future connector

1. **One job** that Toshi cannot do with existing DB tools.  
2. **Role-scoped** via OperationsAgents + existing gates (`toshi-*-action`), not platform-wide tool dumps.  
3. **HITL / digests / memory first** — preference memory is still unmerged; digests have no dedicated connector path yet.  
4. Prefer **KlassApp → external** one-way pushes over reading personal inboxes/calendars into the agent context.

---

## Calendar sync if any

**Recommendation: none for starting scope.**

If product later insists on Google Calendar despite the collision:

| Option | Recommendation |
|---|---|
| Two-way sync | **No** — fights exam/timetable delete+rebuild; ownership ambiguity |
| Replace native events | **No** — breaks existing UI, Toshi `ViewEventsTool` / `EventsTool`, WhatsApp digests that should cite school SoT |
| One-way **KlassApp → personal Google Cal** | **Only** as opt-in per user (teacher / school admin), push of school/class events the user already can see in KlassApp — **personal visibility aid**, not school SoT |
| One-way Google → KlassApp | **No** — pollutes school calendar with personal noise |

Audience clarification: teacher personal Google Cal visibility ≠ replacing `events`. Parents should continue to get calendar facts from **native events + WhatsApp**, not from a Google MCP tool.

---

## Role scoping

| Approach | Recommendation |
|---|---|
| Platform-wide Google tools on every agent | **No** |
| Specific OperationsAgents | **Yes, if ever** — start with **Teacher (ug5)** and/or **School Admin (ug3) / Deputy (ug4)** for personal push only |
| Parent / Student Google | **No** for MCP — parents already have WhatsApp + `EventsTool`; students have `ViewEventsTool` |
| Super Admin | **No** school Google — platform agent stays platform |

Any connector tools must register only on the matching OperationsAgent `tools()` list (same pattern as role tools today), inherit school_id scoping, and stay behind confirmation / Approvable for writes.

---

## First connector: Google vs Slack/Notion/Telegram

### Package reality (laravel/ai + laravel/mcp)

- Installed: `laravel/ai`, `laravel/mcp` (^0.8) — Boost `application-info`; `composer.lock` notes MCP required for agent MCP tools.
- Docs support `Mcp::client('name')->tools()` and spreading into agent `tools()` (laravel/mcp + laravel/ai “MCP Tools”).
- **Codebase:** **zero** `Mcp::client`, `registerClient`, `Client::web`, or `Client::local` usages under `app/` / `routes/` on tip. Capability exists; no named clients yet.

### Knowledge / sequencing

`knowledge.md` already sequences **Phase 3 connectors (WhatsApp first)**. WhatsApp channel + ParentOperationsAgent are on tip — that “first connector” in the channel sense is done. This audit is about **external MCP clients**, which are still greenfield.

### Comparison (conservative)

| Candidate | Native collision | School value today | Auth / ops cost | Recommend as first MCP client? |
|---|---|---|---|---|
| **Google** | Calendar yes; Gmail/Drive no | Login exists but **unrelated**; Workspace MCP would need new OAuth | High (scopes, refresh tokens, Workspace admin) | **No** |
| **Slack** | None in app (keyword seeder / monitoring mentions only) | Staff chat — useful later, not blocking Toshi depth | Medium (workspace OAuth) | Possible #2 experiment |
| **Notion** | None | Policy wiki / staff KB (matches community roadmap copy) | Medium | **Best first *external* MCP experiment** if one must be chosen |
| **Telegram** | `sites` lang string only — no bot stack | Overlaps WhatsApp channel goals | Medium–high (another messaging surface) | **No** — consolidate on WhatsApp |

### Recommendation

1. **Do not make Google the first MCP connector.**  
2. **Prefer no new external MCP until** preference memory merges and digests/role tools need an external side-channel.  
3. If an external MCP must be proven for laravel/ai wiring: **Notion (read-only page fetch for school-admin knowledge)** or a **KlassApp-owned MCP server** exposing existing Toshi tools — not Google, not Telegram, not Calendar.  
4. Slack after Notion only if staff already live in Slack (not assumed for Ugandan pilots).

---

## Open questions for Part B

1. Confirm product intent: is “Google connector” marketing (ecosystem.md) or an engineering priority?  
2. Merge order: preference memory (`feature/toshi-preference-memory`) before any connector work?  
3. If Notion-first: which pages/databases, school-level vs user-level token, read-only only?  
4. Should Part B prototype `Mcp::registerClient` + one agent tool spread with a **local/fixture MCP** before any third-party OAuth?  
5. Credential storage design if Google Drive ever returns (dedicated table vs encrypted columns; never reuse login OAuth).  
6. Digests: do morning digests need external calendar at all, or only native `events`?  
7. Parent WhatsApp calendar messaging vs any personal Google Cal push — keep mutually exclusive?

---

## Ready for Part B? (await approval)

**Not ready to implement a Google MCP connector.** Part A recommends **deferring Google** and treating existing Google OAuth as login-only.

**Await approval** on:

- [ ] Accept “no Google Calendar / Gmail as starting scope”  
- [ ] Accept “first external MCP ≠ Google” (Notion or internal MCP smoke test)  
- [ ] Whether Part B should be (A) Notion read-only design+spike, (B) internal MCP client wiring spike only, or (C) stop after this audit  

---

## Report format (summary)

### google_token reality (evidence)
Column exists; stored as empty string or null; never holds access/refresh/id_token. Scopes = `openid profile email`. Login-only — not API-capable.

### Native calendar conflict
`events` + exam/timetable sync is SoT. Toshi already has role-scoped event tools. Google Calendar sync would collide.

### Recommended starting scope (narrow)
Skip Google for first MCP connector. Do not add Gmail. Calendar only as optional future one-way personal push — not in starting scope.

### Calendar sync if any
None now. If later: one-way KlassApp → personal Google Cal, opt-in staff only. Never two-way; never replace native.

### Role scoping
Not platform-wide. If ever: Teacher / School Admin (Deputy) only; parents/students stay on native + WhatsApp.

### First connector: Google vs Slack/Notion/Telegram
**Not Google.** Prefer deferral; if forced, Notion read-only or internal MCP smoke test. Slack later. Telegram no (WhatsApp already).

### Open questions for Part B
See list above.

### Ready for Part B? (await approval)
**Await approval** — docs-only Part A complete; no product implementation until scope choice (A/B/C) is confirmed.
