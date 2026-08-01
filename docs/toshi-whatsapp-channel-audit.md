# Toshi WhatsApp Channel — Design Audit (Part A)

> **Branch:** `audit/toshi-whatsapp-channel` off `origin/main` @ `212418d`  
> **Date:** 2026-08-01  
> **Scope:** docs-only investigation + proposals — **no product code**.  
> **Repo worktree:** `/Users/mac/projects/KlassApp-toshi-whatsapp-audit`

---

## Executive recommendation

**Extend, do not replace.** Keep the existing keyword / interactive-list / OTP / name-link pipeline as the deterministic first pass. Route only unmatched free-form text to Toshi (role-scoped OperationsAgent via phone→`WhatsAppUser`→usergroup).

**WhatsApp v1 should be read-only for Toshi tool writes.** Native Approvable HITL and Livewire Tier-2 `ConfirmsBeforeWrite` have no WhatsApp resume path today. Until a `sendButtons`/`sendList` confirmation bridge maps back to the same Decision/audit identity (`acting_user_id`, `approver_id`), do not expose write tools over WhatsApp.

**Parents (ug7) are the primary WhatsApp persona.** Restore advisory scope to `children` with a `ParentOperationsAgent` whose tools resolve “my children” only from the identified parent’s linked students — never LLM-supplied `student_id`. Staff/student roles map onto existing OperationsAgents; ug4 has no Toshi surface today.

---

## 1. n8n status (evidence)

### Verdict: **design-doc / infra stub only — not live in the Laravel request path**

| Claim | Evidence | Live? |
|---|---|---|
| Outbound HTTP from Laravel → n8n | `WhatsAppController`, `OutboundWhatsAppService`: **no** `Http::`, Guzzle, or URL to n8n. Only Meta Graph calls live in `WhatsAppBusinessService` (`Http::withToken` → Graph API). | **No** |
| Inbound conversation AI via n8n→Typebot/Flowise | Controller docblock still says “Data-only endpoints for the WhatsApp layer (n8n → Typebot/Flowise → Laravel)” (`WhatsAppController.php:31–33`) and “n8n calls this…” on `identify()` (`:43–44`). Actual inbound is Meta → `handleInbound` → `routeInbound` (keyword router). | **Docs stale; code is Laravel-native** |
| Docker n8n service | `docker-compose.prod.yml:134–169` — `n8nio/n8n:1.94.1`, port `5678`, `WEBHOOK_URL: http://localhost:5678`. Compose-only; no PHP client consumes it. | **Infra stub** |
| `WhatsAppHmac` for n8n callers | Class exists (`app/Http/Middleware/WhatsAppHmac.php:15–20`) with comment “n8n, Typebot, and Flowise sign…”. **Not registered** on any route (grep of `*.php` finds only the class file). Docs (`docs/dev/setup.md:214`, `api-reference.md:11`) claim HMAC on data endpoints — **doc/code drift**. | **Unused middleware** |
| knowledge.md n8n narrative | `knowledge.md:1433–1438` describes `WhatsApp → Laravel → HTTP to n8n → Typebot/Flowise → Laravel data API`. Contradicts live `handleInbound` keyword path (`:1366–1379`). | **Design-doc-only** |

**Bottom line:** n8n was the *intended* conversation orchestrator when Laravel was planned as a data API. Production reality is Meta Cloud API → Laravel keyword router. Treat n8n/Typebot/Flowise mentions as historical design debt; Toshi WhatsApp replaces that intended orchestration layer inside Laravel.

---

## 2. Inbound WhatsApp today (precise flow)

Entry: `POST|GET /api/whatsapp/inbound` → `WhatsAppController::handleInbound` (`:692`).

```
GET  → Meta hub.verify_token challenge (:696–726)
POST → handleMetaInbound (:738)
         ├─ parse messages (text | interactive button_reply/list_reply id|title) (:767–774)
         ├─ MessageDeliveryLog inbound (:783–789)
         ├─ last_inbound_at (:792)
         └─ processMetaMessage (:795 / :813)
              ├─ no WhatsAppUser → handleUnrecognizedUserMeta (:819–820)
              ├─ !opted_in → opt-out text (:824–830)
              ├─ optin/optout keywords (:834–849)
              ├─ routeInbound (:853)
              └─ OutboundWhatsAppService::flushPending (:856–860)
```

### Unrecognized users (`handleUnrecognizedUserMeta`, `:870+`)

| Path | Behaviour |
|---|---|
| `exit` / `link_help` | Help / exit copy |
| `link_school_{id}` / `linktype_klassapp_*` / `linktype_name_*` | Interactive school → ID or name link UX |
| `link_{studentId}` | Confirm button → `linkParentToStudent` |
| `demo` | Auto-link demo parent (user 104) |
| 10-digit code | School Pay OTP/code verification (`processCodeVerificationForMeta`) |
| `KLS######` | KlassApp student ID link |
| `id_card_number` | School-local ID link |
| Broad name search | School-scoped name match + confirm buttons |
| Default | “Try Demo” / “Link My Number” buttons |

### Recognized users (`routeInbound`, `:1417–1615`)

1. **Emoji strip** so list titles like “💰 Fee Balance” match keywords (`:1420–1427`).
2. **10-digit code** → link another student (`handleCodeForExistingUser`).
3. **Universal:** `menu|help|start|…`, `optin`/`optout`, `events`.
4. **Role keyword tables:**

| ug | Keywords (examples) | Handlers |
|---:|---|---|
| 3 SchoolAdmin | students, staff, exams, fees, reports, notices | sendStudentList / sendStaffList / … |
| 5 Teacher | marks, attendance, timetable, assignments | sendTeacherMarks / … |
| 6 Student | grades, attendance, fees, timetable, homework | sendStudent* |
| 7 Parent | grades, fees, attendance, code/link | sendGrades / sendFees / sendAttendance |
| 10 Receptionist | notices, calls | sendNotices / sendCallLog |
| 11 Accountant | fees, reports | sendAccountant* |
| Dual (staff + children) | my children / kids | sendGrades |
| Greetings | hello/hi/hey… | sendMenu |
| **Unknown** | anything else | “didn’t understand” + `sendMenuButtons` |

Interactive list/button replies feed the **same** keyword table via `interactive.*.id` / `.title` (`:769–773`).

### Parent compose helpers (already built, partially wired)

`OutboundWhatsAppService` has parent-facing composers (`:593+`):

- `composeFeeBalance`
- `composeAttendance`
- `composeGradesOverview`
- `composeHealthRecord` (+ `notifyHealthIncident`)

Inbound `sendFees` / `sendAttendance` / `sendGrades` currently build messages **inline** rather than calling these composers — composers are used more for outbound/notification paths. Health is **not** in the parent keyword table today (no `health` match in `routeInbound`).

### identify-user

`POST /api/whatsapp/identify-user` → `identify()` (`:49–130`):

- Normalise phone → `WhatsAppUser` → user + `resolveUserType` (ug → admin/teacher/student/parent/receptionist/accountant; **ug4 → `unknown`**).
- Parents get `children[]`; teachers get `linked_classes`.
- Intended as n8n data API; still useful for any external caller / future Toshi channel bootstrap.

---

## 3. Build vs extend: Toshi takeover scope

### Options

| Option | Description |
|---|---|
| **A — Full takeover** | Every inbound text/interactive goes to Toshi; rebuild menu/OTP/link as tools or agent prompts. |
| **B — Extend (recommended)** | Keep deterministic first pass (link/OTP/opt-in/keywords/lists). Only **unmatched free-form** after `routeInbound` would have fallen through to “didn’t understand” goes to Toshi. |

### Recommendation: **Option B — extend**

**Why (build-vs-extend):**

1. Linking, School Pay codes, KlassApp IDs, and name search are **security- and correctness-critical** — they must stay deterministic, not LLM-mediated.
2. Keyword menus are **zero LLM cost**, reliable UX, already taught to users, and open the 24h Meta window cheaply.
3. The unknown-keyword path (`:1607–1614`) is exactly the gap Toshi fills: natural language without throwing away working infra.
4. Staff/student OperationsAgents already exist; WhatsApp becomes a transport, not a second product.

**Interaction sketch (Part B):**

```
processMetaMessage / routeInbound
  → (existing) optin, codes, keywords, lists, greetings
  → else if toshi_whatsapp_enabled && identified
       → resolve OperationsAgent by usergroup
       → ask(free-form) → sendText / sendButtons
  → else
       → current “didn’t understand” + menu
```

Optional later: promote high-confidence NL (“how much do I owe?”) while keeping FEES keyword as shortcut.

---

## 4. Approval-over-WhatsApp proposal

### Gap (confirmed)

| Mechanism | Channel today | WhatsApp? |
|---|---|---|
| Native `Laravel\Ai\Contracts\Approvable` + HITL resume | Platform ops web (`PlatformApprovalGate`, `continue` as Decisions) | **No** |
| Tier-2 `ConfirmsBeforeWrite` → `__tier2_confirm` JSON → Livewire card → `confirmYes()` / `executeConfirmedTool()` | `AgentToshi` only | **No** |

`ConfirmsBeforeWrite` (`app/AiAgents/Concerns/ConfirmsBeforeWrite.php`) stores payload in `ToshiActionService::$pendingConfirmPayload` and expects Livewire to show a card. `executeConfirmedTool` (`AgentToshi.php:1052–1100`) sets `$bypassConfirm`, runs the tool, and audits with **both** `actingUser` and `approver` populated (self-approve OK; fields must stay distinguishable — `ToshiAuditService.php:17–22`).

### Proposed WhatsApp Tier-2 equivalent

1. **Pending store** (Redis or `whatsapp_pending_confirmations` table):  
   `{ token, user_id, phone, tool, args, preview, acting_user_id, expires_at }`  
   keyed by phone + token; TTL ~15–30 min (within Meta session window).

2. **Prompt:** when SDK path returns `__tier2_confirm`, instead of Livewire card:  
   `sendButtons(preview, [Confirm → confirm_yes_{token}, Cancel → confirm_no_{token}])`  
   (or `sendList` if >3 choices / child picker).

3. **Resume in inbound:** before keyword routing (or as early special IDs), if body matches `confirm_yes_*` / `confirm_no_*`:  
   - load pending row; verify phone ↔ user  
   - **Yes:** set `bypassConfirm`, run same `TOOL_CLASS_MAP` path (or shared service extracted from `executeConfirmedTool`), then  
     `ToshiAuditService::logExecution(..., actingUser: pending.acting_user, approver: same WhatsApp-linked user)`  
   - **No:** `logCancellation`, clear pending.

4. **Identity:** `acting_user_id` = conversation participant (`WhatsAppUser.user_id`); `approver_id` = same user on button confirm (self-approve). Do **not** invent a separate “bot approver.” Under school admin WhatsApp writes later, same rule: the linked staff user is both actor and approver unless a second-party HITL product is designed.

5. **Native Approvable (platform):** out of scope for school WhatsApp v1; would need a WhatsApp-facing approval queue for ug1 — defer.

### Conclusion: writes on WhatsApp v1?

**No — v1 read-only for Toshi tools.** Ship NL reads (fees, grades, attendance, events, lists) first. Unblock writes only after the confirmation bridge + audit parity tests exist. Existing keyword path remains read-oriented today (no write tools in `routeInbound`).

---

## 5. Staff/Student routing map

`ToshiSdkV2Service` scope router (`:74–88`, mirrored in `askStreamed` `:150–160`):

| Condition | Agent |
|---|---|
| `ToshiScope::Platform` | `PlatformOperationsAgent` |
| ug5 | `TeacherOperationsAgent` |
| ug11 | `AccountantOperationsAgent` |
| ug8 | `LibrarianOperationsAgent` |
| ug10 | `ReceptionistOperationsAgent` |
| ug6 | `StudentOperationsAgent` |
| **default** (incl. ug3, ug4, ug7, …) | `ToshiOrchestrator` (school-admin toolset) |

### Proposed WhatsApp mapping (mirror router + Parent)

| identify `user_type` / ug | WhatsApp → Agent | Surprises? |
|---|---|---|
| parent / 7 | **`ParentOperationsAgent` (new)** | Today falls through to `ToshiOrchestrator` — **wrong** if ug7 ever hit SDK (school-admin tools). Must add explicit `7 => Parent…` before default. |
| student / 6 | `StudentOperationsAgent` | OK; tools use `auth()->user()` only — WhatsApp path must **impersonate/bind** that user for the request (Auth::login / `forUser` / request user). |
| teacher / 5 | `TeacherOperationsAgent` | Same auth-binding requirement. |
| accountant / 11 | `AccountantOperationsAgent` | Same. |
| librarian / 8 | `LibrarianOperationsAgent` | Same. |
| receptionist / 10 | `ReceptionistOperationsAgent` | Same. |
| admin / 3 | `ToshiOrchestrator` | OK for school admin. |
| ug4 (Deputy) | see §7 | identify returns `unknown`; no agent arm. |
| Dual-role staff + children | Primary ug agent for staff NL; keep keyword `my children` for parent data | Do not auto-merge Parent tools into staff agent in v1. |

**Auth surprise:** Student/Teacher/… tools resolve `auth()->user() ?? request()->user()`. Web Livewire has a session; WhatsApp webhook does not. Part B must establish an authenticated user context from `WhatsAppUser` before `ask()` — without that, tools fail or worse, run unscoped.

---

## 6. ParentOperationsAgent proposal

### History of Parent capabilities

| When | Commit | State |
|---|---|---|
| 2026-07-01 | `34e264c` — introduced `getRoleCapabilities` | ug7: **`scope => 'children'`**, `actions => []` |
| 2026-07-12 | `6ad1339` — “align with route enforcement” | ug7: **`scope => 'none'`**, `actions => []` — because **no parent web routes**; middleware unused. Comment: “API-only (no web dashboard).” |

There was **never** a non-empty Parent action list in `getRoleCapabilities`. Original intent was scope=`children` (children-scoped reads), emptied to `none` when capabilities were aligned to **web** enforcement — not because WhatsApp parent features disappeared.

Product fact (`knowledge.md`): Parent is **WhatsApp-only by design** (no web dashboard). Restoring `scope => 'children'` for a WhatsApp/Toshi channel is consistent with original intent once the channel is Toshi, not web Blade.

### Proposed capability set

**From existing compose / keyword surface (v1 read):**

| Capability id | Source today | Tool sketch |
|---|---|---|
| `view_fee_balance` | keyword FEES / `composeFeeBalance` | ParentFeeBalanceTool |
| `view_grades` | keyword GRADES / `composeGradesOverview` | ParentGradesTool |
| `view_attendance` | keyword ATTENDANCE / `composeAttendance` | ParentAttendanceTool |
| `view_events` | universal EVENTS | ParentEventsTool (or shared) |
| `view_health` | `composeHealthRecord` (not keyword-routed yet) | ParentHealthTool |
| `list_children` | identify children / link flows | ParentListChildrenTool |
| `link_student` | code/name/KLS flows | **Keep deterministic** — do not LLM this in v1 |

**Aspirational (post-v1 / after confirmation):**

| Capability id | Notes | Write? |
|---|---|---|
| `view_report_card` | Part C report endpoint already exists for n8n-era pull | Read |
| `initiate_payment` / `pay` | `composeFeeBalance` footer says “Reply PAY…”; SchoolPay initiate API out of scope historically | **Write** — needs confirmation + SchoolPay |
| `opt_in` / `opt_out` | Already keyword | Keep deterministic |
| `message_school` | No tool today | Write / defer |

Suggested advisory map entry (docs-only proposal):

```php
7 => [ // Parent — WhatsApp / API channel
    'scope'   => 'children',
    'label'   => 'parent',
    'actions' => [
        'view_fee_balance', 'view_grades', 'view_attendance',
        'view_events', 'view_health', 'list_children',
        // aspirational (not v1): 'view_report_card', 'initiate_payment',
    ],
],
```

### Ownership model (hard rule)

Mirror **Student** discipline (`StudentOperationsAgent.php:28–29`: “Every tool resolves identity from `auth()->user()` — never LLM `student_id`”):

- Resolve parent from WhatsApp-linked / authenticated user only.
- Children = `$parent->children()` / `student_parent_links` (same as `sendFees` / `identify`).
- Optional `child_name` / ordinal for disambiguation **must** be validated against that set.
- **Reject** any LLM-supplied `student_id` not in the linked set (prefer schema that omits `student_id` entirely).

### Writes vs read-only

Given §4: **v1 ParentOperationsAgent = read-only tools.** Flag `initiate_payment` (and any leave/excuse writes) as blocked until WhatsApp confirmation + audit parity.

---

## 7. ug4 Toshi access (one line)

**ug4 (SchoolSubadmin / Deputy Admin) has no Toshi access today:** Blade allowlist is `[1, 3, 5, 11, 8, 10, 6]` (`layouts/app.blade.php:65`) — **4 omitted**; Gate `toshi-school-action` allows **ug3 only** (`AuthServiceProvider.php:238–251`); `getRoleCapabilities(4)` is `scope=school`, **`actions=[]`**; scope router has **no ug4 arm** (would incorrectly hit `ToshiOrchestrator` if ever mounted). Report only — no fix in Part A.

---

## Open questions for Part B approval

1. Confirm **Option B** (Toshi only on unmatched free-form) vs any desire to NL-replace menus in v1.
2. Confirm **read-only v1** until WhatsApp Tier-2 confirmation is built.
3. Approve **ParentOperationsAgent** + restore advisory `scope=children` (WhatsApp-channel justification vs Jul 12 web alignment).
4. Auth binding strategy for webhook → `auth()->user()` (loginOnceUsingId / `Request::setUserResolver` / agent `forUser`).
5. Multi-child UX: auto-aggregate all children vs force `sendList` child picker before tools run.
6. Dual-role staff with children: keep keyword-only parent features in v1?
7. Deprecate or resurrect unused `WhatsAppHmac` + n8n docker service (cleanup vs leave).
8. Should `compose*` be unified into inbound send* handlers as part of Parent agent work, or leave formatting debt for later?
9. Health: expose as keyword + Parent tool in v1, or outbound-only?
10. ug3 school-admin writes over WhatsApp: ever in scope, or web-only forever?

---

## Recommended v1 scope

| In | Out |
|---|---|
| Extend `routeInbound` fall-through → Toshi for identified users | Full replacement of keyword/OTP/link |
| Parent **read** tools (fees, grades, attendance, events, list children; optional health) | Parent writes (`initiate_payment`, etc.) |
| Staff/student free-form → existing OperationsAgents (read tools only until confirm bridge) | Staff write tools over WhatsApp |
| Phone → WhatsAppUser → usergroup → agent map (incl. new ug7 arm) | ug4 Toshi enablement |
| Shared pending-confirm design spike (doc/spike only if needed) | Ship write confirmation in v1 |
| Docs/knowledge update: n8n is not live | Product dependency on n8n |

**Success criteria for Part B (when approved):** parent can ask in natural language “what’s the fee balance?” after linking and get the same data as FEES; unmatched staff questions hit the correct role agent; no write executes without an audited Confirm button path (or writes remain disabled).

---

## Evidence index (key paths)

| Topic | Path |
|---|---|
| Inbound | `app/Http/Controllers/Api/WhatsAppController.php` |
| Outbound / compose* | `app/Services/OutboundWhatsAppService.php` |
| Meta transport | `app/Services/WhatsAppBusinessService.php` |
| Routes | `routes/api.php:291–314` |
| Scope router | `app/AiAgents/ToshiSdkV2Service.php` |
| Capabilities | `app/Services/ToshiActionService.php:191–288` |
| Tier-2 | `app/AiAgents/Concerns/ConfirmsBeforeWrite.php`, `AgentToshi::executeConfirmedTool` |
| Audit identity | `app/Services/ToshiAuditService.php` |
| Blade allowlist | `resources/views/layouts/app.blade.php:65–77` |
| Gates | `app/Providers/AuthServiceProvider.php:238+` |
| Role parity (web) | `docs/toshi-role-parity-audit.md` (Parent not covered as operator; Parent = WhatsApp-only) |
| n8n compose stub | `docker-compose.prod.yml:134+` |
| Parent scope history | commits `34e264c`, `6ad1339` |
