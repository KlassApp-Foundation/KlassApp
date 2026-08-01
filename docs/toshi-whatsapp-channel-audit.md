# Toshi WhatsApp Channel — Design Audit (Part A)

> **Branch:** `audit/toshi-whatsapp-channel` off `origin/main` @ `212418d`  
> **Date:** 2026-08-01  
> **Scope:** docs-only investigation + proposals — **no product code**.  
> **Repo worktree:** `/Users/mac/projects/KlassApp-toshi-whatsapp-audit`

---

## Executive recommendation

**Extend, do not replace.** Keep the existing keyword / interactive-list / OTP / name-link pipeline as the deterministic first pass. Route only unmatched free-form text to Toshi (role-scoped OperationsAgent via phone→`WhatsAppUser`→usergroup).

**WhatsApp v1 should be read-only for Toshi tool writes.** Native Approvable HITL and Livewire Tier-2 `ConfirmsBeforeWrite` have no WhatsApp resume path today. Until a confirmation bridge maps **button taps or typed replies** to `Decision::approve()`/`reject()` (platform) or Tier-2 `bypassConfirm` resume (school roles) with the same audit identity (`acting_user_id`, `approver_id`), do not expose write tools over WhatsApp. See **§ WhatsApp confirmation bridge (Part A)** below for the full design (buttons + text as first-class paths).

**Ship Part B read-only in parallel** with the confirmation bridge design/build — do **not** hold NL reads waiting for writes. Writes stay gated until the bridge is proven.

**Parents (ug7) are the primary WhatsApp persona.** Restore advisory scope to `children` with a `ParentOperationsAgent` whose tools resolve “my children” only from the identified parent’s linked students — never LLM-supplied `student_id`. Staff/student roles map onto existing OperationsAgents; ug4 has no Toshi surface today. Payroll + impersonation remain **web-only** even after confirmation works.

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

**Interaction sketch (Part B + confirmation bridge):**

```
processMetaMessage / routeInbound
  → (existing) optin, codes, role keywords, lists, greetings
  → else if pending-approval match for this phone
       (button ty_*/tn_*  OR  typed YES|NO <token>)
       → resolve / reject / expired UX  (does not reach Toshi)
  → else if toshi_whatsapp_enabled && identified
       → resolve OperationsAgent by usergroup
       → ask(free-form) → sendText / sendButtons(+text fallback copy)
  → else
       → current “didn’t understand” + menu
```

Optional later: promote high-confidence NL (“how much do I owe?”) while keeping FEES keyword as shortcut.

---

## 4. Approval-over-WhatsApp proposal

> **Superseded in detail by § WhatsApp confirmation bridge (Part A)** above. Short summary retained for continuity.

### Gap (confirmed)

| Mechanism | Channel today | WhatsApp? |
|---|---|---|
| Native `Laravel\Ai\Contracts\Approvable` + HITL resume | Platform ops web (`PlatformApprovalGate`, `continue` as Decisions) | **No** |
| Tier-2 `ConfirmsBeforeWrite` → `__tier2_confirm` JSON → Livewire card → `confirmYes()` / `executeConfirmedTool()` | `AgentToshi` only | **No** |

**Design choice (Part A):** opaque token + pending confirmation row; resume via **button** (`ty_*` / `tn_*`) **or typed** (`YES|NO <token>`) → `Decision::approve()`/`reject()` (Approvable) or Tier-2 `bypassConfirm` (school). Self-approve on same WhatsApp phone. Same token/TTL for both paths. See full section.

### Conclusion: writes on WhatsApp v1?

**No — Part B / v1 channel = read-only for Toshi tools.** Unblock writes only after the confirmation bridge is proven. Existing keyword path remains read-oriented today.
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

## WhatsApp confirmation bridge (Part A) — buttons + text

> **Status:** docs-only design. Ground truth: `sendButtons()` / `sendList()` already work in production for keyword/menu; inbound already parses button/list taps **and** free text. **Missing:** map Approve/Reject (tap **or** typed reply) → specific pending Approvable / Tier-2 decision → resume paused agent → reply on WhatsApp.  
> **Self-approve:** same `WhatsAppUser` phone that triggered the pending action resolves it — no new identity model.  
> **First-class dual path:** interactive buttons and human-typed confirmation share one token, one pending row, and one TTL. Text is not a “nice to have” — Meta clients, accessibility, and broken button UX all need it.  
> **Deliberate exclusions:** payroll + impersonation stay **web-only** even after this works.

### Inbound payload shape (evidence)

**Local DB:** `message_delivery_log` had **no** recent inbound interactive rows in the Boost-connected database at audit time — no live tap sample to quote. Shape below is reconstructed from (1) Meta Cloud API docs example webhook and (2) our parser in `WhatsAppController::handleMetaInbound` (`:767–774`).

**What we extract today**

```php
// type === 'interactive'
$ir = $msg['interactive'] ?? [];
$body = $ir['button_reply']['id'] ?? $ir['list_reply']['id']
    ?? $ir['button_reply']['title'] ?? $ir['list_reply']['title']
    ?? '';
```

- **Which button was tapped?** Prefer `interactive.button_reply.id` / `list_reply.id`; title is fallback only if id is missing.
- **That `$body` is then keyword-routed** (after emoji strip) — e.g. `FEES`, `demo`, `link_help`, `link_{studentId}`, `link_school_{id}`.
- **Inbound log:** `MessageDeliveryLog` stores `whatsapp_message_id` (inbound wamid), `phone`, `content_preview` = limited `$body` (so for buttons, the **id**, not the title). No `user_id` / `flow_type` on inbound create today (`:783–789`).
- **`context` is ignored.** Meta includes `messages[].context.id` = outbound wamid of the interactive message being answered. Our parser never reads it. Outbound `MessageDeliveryLog.whatsapp_message_id` already stores that wamid — unused for correlation.

**Canonical Meta button-reply webhook (docs example, abbreviated)**

```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "changes": [{
      "value": {
        "contacts": [{ "profile": { "name": "Pablo Morales" }, "wa_id": "16505551234" }],
        "messages": [{
          "context": {
            "from": "15550783881",
            "id": "wamid.HBgLMTY0NjcwNDM1OTUVAgARGBJBM0Y4RUU0RUNFQkFDMjYzQUMA"
          },
          "from": "16505551234",
          "id": "wamid.…inbound…",
          "timestamp": "1714510003",
          "type": "interactive",
          "interactive": {
            "type": "button_reply",
            "button_reply": {
              "id": "change-button",
              "title": "Change"
            }
          }
        }]
      },
      "field": "messages"
    }]
  }]
}
```

**List reply** is the same envelope with `interactive.type = "list_reply"` and `list_reply: { id, title, description? }`.

**Production examples of outbound button IDs we already emit** (round-trip proven for keyword path):

| Outbound `reply.id` | Title (≤20) | Inbound `$body` |
|---|---|---|
| `FEES` / `GRADES` / `ATTENDANCE` | menu labels | keyword |
| `demo` / `link_help` | Try Demo / Link My Number | unrecognized UX |
| `link_school_{id}` / `linktype_klassapp_{id}` / `linktype_name_{id}` | link UX | school-scoped link |
| `link_{studentId}` | confirm student | `linkParentToStudent` |

**Pitfall — list rows without `id`:** `SchoolPayWebhookController` receipt list rows (incl. “Link Another Student”) omit `id`; `sendList` then assigns `Str::uuid()`. Inbound prefers that UUID over title, so the tap body is a UUID — **not** the string `"Link Another Student"`. Parent keyword match for `link another student` relies on typed text / title only when id is absent. Any confirmation design must **always set explicit `id`s**.

### sendButtons ID constraints (Meta + our code)

| Constraint | Meta Cloud API | Our code (`WhatsAppBusinessService`) |
|---|---|---|
| Max buttons | **3** | `array_slice($buttons, 0, 3)` |
| Button title | **≤20** chars | `mb_substr(..., 0, 20)` |
| Button **id** | **≤256** chars; unique within message; no leading/trailing spaces | Pass-through `$btn['id']` or `Str::uuid()` if missing — **no length truncate** |
| Body | ≤1024 | `mb_substr(..., 0, 1024)` |
| List row id | ≤200 (list API; same interactive family) | Pass-through / UUID; title ≤24 |

Sources: [Meta interactive reply buttons](https://developers.facebook.com/docs/whatsapp/cloud-api/messages/interactive-reply-buttons-messages/) (button id max **256**); our `sendInteractiveButtons` / `sendButtons` / `sendList` (`WhatsAppBusinessService.php:200–412`).

**Implication:** Meta **does** allow arbitrary opaque IDs up to 256 chars. Encoding a conversation UUID (36) + tool-call id + short prefix fits easily (~80 chars). Titles stay human (“Approve” / “Reject”) and must be ≤20.

### Recommended correlation mechanism

**Recommend: short opaque token in button `reply.id` + pending confirmation row (phone-bound) — same token used for typed replies.**

| Option | How | Pros | Cons |
|---|---|---|---|
| **A — Encode IDs in button id** | e.g. `t1a:{conversationId}:{toolCallId}` | No new table; Meta 256 OK; matches `link_{id}` pattern | Leaks UUIDs; hard TTL/revoke; Tier-2 has no `approval_state` to resume from encode alone |
| **B — Phone + “latest pending” lookup** | Tap / bare yes → find newest pending for phone | Simple | Concurrent pending / stale taps race; ambiguous |
| **C — Opaque token + table (recommended)** | Buttons `ty_{token}` / `tn_{token}` (yes/no); typed `YES {token}` / `NO {token}`; row holds conversation_id, approval_id, mechanism, phone, user_id, outbound_wamid, expires_at | Unifies **Approvable + Tier-2**; TTL; revoke; phone bind; multi-pending safe; ≤25-char ids | One small table/Redis |

**Why C:** School-role writes use Livewire `ConfirmsBeforeWrite` (`__tier2_confirm` + `ToshiActionService::$bypassConfirm`), **not** native `Approvable` pause state. Platform uses native `agent_conversation_messages.approval_state` + `Decision::*`. One WhatsApp bridge should cover both without two correlation schemes. Token stays well under Meta’s 256 limit; titles stay “Approve” / “Reject”. Typed path reuses the **same** token so button and text stay one decision surface.

**Optional secondary check:** persist outbound wamid on the pending row; on inbound, if `context.id` present, require match. Parser must start reading `context` (today it does not). Soft log-only at first is fine.

**Do not use title as the correlation key** — titles are truncated, localized, and non-unique across messages.

### Text confirmation as a first-class path

Buttons alone are insufficient: some WhatsApp clients mishandle interactive replies, screen readers / low-literacy flows favour typing, and users often reply in the thread as plain text. Text confirmation is therefore **required in v1 of the bridge**, not a later fallback.

#### Design question: reply format

| Option | Format | Multi-pending for one phone? |
|---|---|---|
| **A — Coded reply (recommended)** | Body copy: `Reply YES a7f3k2 or NO a7f3k2 — or tap Approve / Reject`. Parser accepts case-insensitive `YES|APPROVE|Y` / `NO|REJECT|N` + whitespace + **same opaque token** as `ty_`/`tn_`. | **Safe** — each pending has its own token; concurrent confirms disambiguate |
| **B — Bare yes/no** | `yes` / `no` → newest pending for phone | **Unsafe** — two open confirms → wrong tool executes; race with stale “yes” after a later request |

**Recommendation: Option A (coded reply sharing the opaque token).**

Reasons:

1. Part A already rejected phone+“latest pending” for **buttons** (correlation option B) for the same race — bare text would reintroduce it.
2. Multi-pending is realistic (teacher confirms a task, then a second tool pauses before the first reply).
3. One token for button id suffix and typed code keeps TTL/invalidate logic identical: first valid response (either channel) resolves and clears the row.
4. Token length stays short (8–12 chars alphanumeric) so it is human-typeable on mobile; show it in the confirmation **body** (≤1024), not only in button ids.

**Accepted text patterns (normalize before match):**

```
(?i)^\s*(yes|approve|y)\s+([a-z0-9]{6,16})\s*$
(?i)^\s*(no|reject|n)\s+([a-z0-9]{6,16})\s*$
```

Synonyms beyond this set go to Toshi / “didn’t understand” — do **not** treat bare `yes`/`ok`/`sure` as approval.

**Outbound body template (illustrative):**

```
Confirm: Create task "Collect homework for P.5"?
Reply YES a7f3k2 or NO a7f3k2
Or tap a button below.
[Approve] [Reject]     ← ids ty_a7f3k2 / tn_a7f3k2
```

#### Multi-pending handling

- **Allow N open pending rows per phone** (one per outstanding tool pause), each with a unique token.
- Button tap or coded text resolves **exactly one** row; other open rows remain valid until used or expired.
- **Do not** collapse to “one pending per phone” as a substitute for codes — that still loses the second confirm when two arrive close together, and forces silent discard of the older request.
- Optional UX: if phone has >1 open pending and user sends bare `yes`/`no` (no token), reply with a short list: `You have 2 open confirms. Reply YES <code> for the one you mean:` + one-line previews — still **no** silent latest-pending approve.

### Resume path (step-by-step)

Two mechanisms exist today; WhatsApp must branch on `pending.mechanism`.

#### Inbound precedence (updated)

Slots into the existing recognized-user pipeline **after** deterministic keyword/menu handling and **before** free-form Toshi:

```
POST /api/whatsapp/inbound
  → handleMetaInbound
  → extract $body (button/list id | text), phone, optional context.id
  → processMetaMessage (opt-in / unrecognized / link / OTP as today)
  → routeInbound (recognized):
       1. emoji strip
       2. 10-digit / link-another-student codes
       3. universal keywords (menu|help|optin|optout|events|…)
       4. role keyword tables (FEES, GRADES, …) + greetings
       5. ★ PENDING-APPROVAL CHECK (this phone) ★
            a. button/list id matches ty_{token} | tn_{token}
               OR text matches YES|NO <token> regex
            b. load pending by token
            c. verify phone === pending.phone AND WhatsAppUser.user_id === pending.user_id
            d. if missing / already resolved / expired
                 → sendText “This request has expired. Please ask again.”
                 → clear/ignore stale row; STOP (do not fall through to Toshi)
            e. else dispatch by mechanism (Approvable | Tier-2 below)
            f. mark pending resolved; invalidate token (first valid response wins)
            g. sendText result; STOP
       6. else if toshi_whatsapp_enabled → free-form Toshi NL (Part B)
       7. else “didn’t understand” + sendMenuButtons
```

**Why keywords before pending?** Keeps FEES/menu/opt-out reachable while a confirm is open (escape hatch). Button `ty_*`/`tn_*` and coded `YES|NO <token>` never collide with role keywords, so step 5 is the only consumer. Bare `yes` is **not** an approval — it falls through to Toshi / menu, avoiding accidental writes.

**Early intercept note:** Implementation may short-circuit `ty_*`/`tn_*` as soon as `$body` is parsed (before role tables) for clarity; behaviour is equivalent because those ids are not keywords. Typed confirms must stay **after** keyword tables so `fees` / `menu` still work.

#### Shared resolve / TTL rules (button = text)

| Rule | Behaviour |
|---|---|
| Same token | `ty_{t}` / `tn_{t}` / `YES t` / `NO t` address one pending row |
| First valid wins | First successful approve **or** reject (either channel) executes resume, then **invalidates** the token |
| Second response | Treat as expired/missing → explicit expiry copy (not silent ignore) |
| Wrong phone / wrong user | Fail closed; do not reveal whether token exists |
| TTL | `expires_at` on pending row (recommend 10–15 min, align with web HITL patience); Redis TTL or MySQL sweep both OK |
| Expiry UX | Always `sendText`: **“This request has expired. Please ask again.”** — never silent fail |
| Clock skew | Compare server time only; do not trust client timestamps |

#### Path 1 — Native Approvable (platform / any Conversational agent with HITL)

Evidence: laravel/ai Human Tool Approval docs; app `PlatformOpsConversationService` + `PlatformApprovalGate`; tests `PlatformPlanToolsTest` resume via `continue` + `Decisions::from([$approvalId => Decision::approve()])`.

Pause state lives on **`agent_conversation_messages`**:

| Table | Role |
|---|---|
| `agent_conversations` | id (uuid), participant_type/id |
| `agent_conversation_messages` | `tool_calls`, **`approval_state`** JSON (`pending` map keyed by tool-call id → reason) |

Resume (mirror web gate):

1. When agent `prompt()` returns `hasPendingApprovals()`, create pending row(s); `sendButtons` with Approve/Reject tokens **and** body copy that includes `Reply YES {token} or NO {token}`; body = tool name + reason + safe arg preview + reply hint (≤1024).
2. On Approve (button `ty_*` **or** typed `YES {token}`):  
   `(new Agent)->continue($conversationId, as: $user)->prompt(Decisions::from([$approvalId => Decision::approve()]))`
3. On Reject (button `tn_*` **or** typed `NO {token}`):  
   `Decision::reject('Rejected via WhatsApp')` (optional short reason; WhatsApp won’t collect free-text easily in v1).
4. Send `$response->text` (or a fixed “Approved / Rejected” summary) via `sendText`.
5. Audit: keep existing listeners (`LogToolApprovalRequested` / resolved) — `acting_user_id` = conversation participant; `approver_id` = same WhatsApp-linked user (self-approve).

`Decision::edit()` is **web-only** for v1 (no JSON editor on WhatsApp).

#### Path 2 — Tier-2 `ConfirmsBeforeWrite` (school OperationsAgents / AgentToshi)

Evidence: `ConfirmsBeforeWrite` → `__tier2_confirm` JSON; `AgentToshi::confirmYes` / `executeConfirmedTool` sets `$bypassConfirm`, re-invokes tool, audits with acting + approver.

There is **no** `approval_state` row — payload is Livewire/session side-channel today (`ToshiActionService::$pendingConfirmPayload`). For WhatsApp:

1. On `__tier2_confirm`, persist `{ tool, args, preview, user_id, phone }` in pending table (mechanism=`tier2`).
2. `sendButtons` Approve/Reject **plus** typed `YES|NO {token}` copy in body.
3. Approve (tap or text) → set `bypassConfirm`, run same `TOOL_CLASS_MAP` / `executeConfirmedTool` path with auth bound to WhatsApp user; audit `acting_user_id` + `approver_id` = that user.
4. Reject (tap or text) → `logCancellation`, clear pending, `sendText` cancelled.

**Auth binding (both paths):** webhook must establish `auth()->user()` / `forUser($user)` / `continue(..., as: $user)` from `WhatsAppUser.user_id` before resume — same requirement as Part B reads.

### Self-approve / phone identity

- **Requester:** `WhatsAppUser` for inbound phone → `user_id` (already required for recognized routing).
- **Approver:** same phone must tap **or** type the coded reply; reject if pending.phone ≠ inbound phone or pending.user_id ≠ linked user.
- **No second-party HITL on WhatsApp v1** (no “ask another admin”). Platform second-party review stays on web `PlatformApprovalGate`.
- **Dual-SIM / shared phone:** out of scope; phone↔user link is the trust boundary (same as today’s keyword bot).

### v1 write candidates per role (one low-risk each)

Enable **only after** confirmation bridge (button **and** text) is proven end-to-end (happy + reject + expired + wrong-phone + multi-pending disambiguation). One write counterpart per role — not a flood.

| Role | ug | Existing read / keyword surface | **v1 write candidate** | Why low-risk |
|---|---:|---|---|---|
| Parent | 7 | fees/grades/attendance (read) | **none in first write slice** — or soft `initiate_payment` **only if** SchoolPay + confirm proven; prefer **defer** | Parent value is reads; payment is money |
| Teacher | 5 | marks/attendance/timetable views | `CreateTaskTool` | Lowest blast radius vs attendance/marks/leave |
| Student | 6 | grades/attendance/fees views | `ManageTasksTool` (self tasks) | Self-scope; avoid submit assignment/homework first |
| Receptionist | 10 | notices/calls views | `CreateTaskTool` or visitor-log **create** | Routine office log / task |
| Librarian | 8 | cards/dashboard views | `CreateTaskTool` | Avoid lending / book mutate first |
| Accountant | 11 | fees/reports views | `CreateTaskTool` | **Not** `RecordPaymentTool` |
| School admin | 3 | students/staff/fees keywords | Defer WhatsApp writes; web AgentToshi remains primary | Broad school blast radius |
| Platform | 1 | n/a on WhatsApp | Defer; if ever, geo create — **never** impersonation | Platform WhatsApp not a v1 goal |

### Deliberate web-only exclusions

| Surface | Why web-only |
|---|---|
| **Payroll** (`ManagePayrollTool`) | Money + employment; accountant already stricter Tier-2 on web |
| **Impersonation** (`ImpersonateSchoolAdminTool`) | Session/security boundary; Approvable on web only |
| **Subscription approve/cancel** (platform billing) | Financial access; keep ops gate UI |
| **Co-admin delete / password reset** | Account takeover risk |
| **Feature toggles / access system settings** | Cross-school / availability impact |
| **Teacher attendance / marks / leave** (first write slice) | High parent-visible impact — later slices only |
| **Librarian lending issue/return** | Inventory integrity — later |
| **Parent payment initiate** | Until SchoolPay + confirm hardened |

### Sequencing: Part B read-only now vs wait for writes

**Recommend: ship Part B read-only as planned; design/build confirmation bridge (buttons + coded text) in parallel; do not hold Part B.**

Reasoning:

1. Reads need auth-binding + agent routing only — **not** Decision resume.
2. Parents’ primary WhatsApp value is NL fees/grades/attendance; delaying that for write UX costs adoption with no safety gain (writes stay off).
3. Confirmation bridge is a discrete spike (pending table + inbound pending check after keywords + resume adapters for **button and text**) that can land behind a feature flag after Part B.
4. Coupling reads+writes into one release risks blocking on Approvable/Tier-2 dual-path complexity.

**Hold Part B only if** product insists the first WhatsApp Toshi demo must include a confirmed write — otherwise parallel is safer.

### Open questions

1. Prefer Redis TTL vs MySQL `whatsapp_pending_confirmations` for pending tokens?
2. Should inbound start requiring `context.id` match as hard fail, or soft log-only at first? (**Lean soft log-only.**)
3. Platform WhatsApp approvals ever in scope, or school Tier-2 only?
4. Reject-with-reason on WhatsApp (follow-up free-text) vs fixed reject string?
5. ~~Multi-pending: allow only one open confirmation per phone vs N tokens?~~ **Resolved: N tokens + coded text (Option A); bare yes/no never auto-approves.**
6. Confirm Parent first-write is deferred (no payment) for the first post-bridge slice?
7. Token alphabet / length for typeability (recommend 8 lowercase alphanumeric, avoid `0/O`/`1/l`)?

---

## Open questions for Part B approval

1. Confirm **Option B** (Toshi only on unmatched free-form) vs any desire to NL-replace menus in v1.
2. Confirm **Part B = read-only now** (parallel confirmation bridge) — **recommended**; do not hold NL reads for writes.
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
| Button→Approvable design (this §) — **buttons + coded text** | Ship write confirmation / enable write tools |
| Docs/knowledge update: n8n is not live | Product dependency on n8n |

**Success criteria for Part B (when approved):** parent can ask in natural language “what’s the fee balance?” after linking and get the same data as FEES; unmatched staff questions hit the correct role agent; no write executes without an audited Confirm path (button **or** coded text) — or writes remain disabled.

**Success criteria for confirmation bridge (post–Part B):** Approve/Reject on WhatsApp (tap **or** `YES|NO {token}`) resumes the same pending tool call as web (`Decision::approve`/`reject` or Tier-2 `bypassConfirm`); first valid response invalidates the token; expired/stale replies get an explicit “This request has expired” message (never silent); self-approve phone check passes; wrong-phone fails closed; multi-pending disambiguates by token; bare `yes`/`no` never approves; payroll/impersonation never offered.

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
| Native Approvable resume | `PlatformOpsConversationService`, `PlatformApprovalGate`, `Decision` / `Decisions` (laravel/ai) |
| Pause storage | `agent_conversations`, `agent_conversation_messages.approval_state` |
| Delivery log | `message_delivery_log` (`MessageDeliveryLog`) — wamid, phone, preview; **no** approval correlation |
| Audit identity | `app/Services/ToshiAuditService.php` |
| Blade allowlist | `resources/views/layouts/app.blade.php:65–77` |
| Gates | `app/Providers/AuthServiceProvider.php:238+` |
| Role parity (web) | `docs/toshi-role-parity-audit.md` (Parent not covered as operator; Parent = WhatsApp-only) |
| n8n compose stub | `docker-compose.prod.yml:134+` |
| Parent scope history | commits `34e264c`, `6ad1339` |
| Meta button id limit | https://developers.facebook.com/docs/whatsapp/cloud-api/messages/interactive-reply-buttons-messages/ (id ≤256) |
