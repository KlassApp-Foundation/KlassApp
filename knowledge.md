# KlassApp Project Knowledge

## Current Status: June 2026

### Git
- **Branch**: `main`
- **Last commit**: `53d6a49` — "fix(nav): replace Tailwind hamburger with vanilla JS, fix mobile sidebar toggle"
- **Remote**: `origin/main` (GitHub: Elijah-ug/KlassApp)

---

## Production

| Component | Server | URL |
|---|---|---|
| KlassApp App | 165.245.250.16 | https://klassapp.xyz |
| Evolution API | 46.101.130.70 | http://46.101.130.70:8081 |
| Deploy key | `~/.ssh/id_ed25519_do` | |

### Deploy Command
```bash
ssh -i ~/.ssh/id_ed25519_do root@165.245.250.16 \
  "cd /var/www/klassapp && git pull origin main && php artisan optimize:clear && systemctl restart php8.3-fpm"
```

### Production .env (key values)
```
EVOLUTION_API_URL=http://10.19.0.6:8081
EVOLUTION_API_KEY=78E5A6FF-BA89-45C6-987C-C31407BD22B4
EVOLUTION_INSTANCE_NAME=klassapp
WHATSAPP_BUSINESS_NUMBER=+256765275289
WHATSAPP_BUSINESS_NAME=KlassApp
```

### Next Session Priority
1. Implement Toshi general query handling (handleGeneralQuery, intent detection, real data queries)
2. Fix v1/v2 landing navbar alignment

### Local Dev
```bash
# Start MySQL
brew services start mysql

# Start server
php artisan serve

# Start Evolution API (Docker via Colima)
colima start
```

---

## Landing Pages

| URL | File | Status |
|---|---|---|
| `/` | `landing.blade.php` | v1 — official landing |
| `/landing2` | `landing2.blade.php` | v2 — alternate |

**v1 Features:**
- Flare-style navbar: transparent → white + compact on scroll (scrollY > 60)
- Gradient hero H1: animated blue→green→amber (`gradientShift`)
- Looping keyword typewriter: Grades → fees → attendance → health → canteen → discipline → notifications → timetables → exams → reports
- Audience tabs: Admin/Teacher/Parent with auto-rotation every 3s
- Stable hero container: `min-height: 20rem` on wrapper, `3.3em` on H1
- WhatsApp phone mockup, pricing, testimonials, CTA sections

---

## Dashboard Redesign (COMPLETED — June 2026)

### Files Changed
| File | Change |
|---|---|
| `public/css/dashboard-refresh.css` | Full rewrite — Sora/DM Sans fonts, brand colors, CSS variables, card hover |
| `resources/views/layouts/app.blade.php` | Font import: Nunito → Sora + DM Sans |
| `resources/views/admin/dashboard/dashboard.blade.php` | Cleaned HTML structure (all PHP/JS logic untouched) |
| `resources/views/layouts/admin/sidebar.blade.php` | Rounded active states, green accent |
| `resources/views/layouts/partials/navigation.blade.php` | White bg, dark text, Sora font, vanilla JS hamburger |
| `resources/views/auth/login.blade.php` | Fixed false maintenance banner (default login_status to 1) |

### Design Tokens
| Token | Value |
|---|---|
| Display font | Sora |
| Body font | DM Sans |
| Blue | `#1E6FD9` |
| Green | `#22C55E` |
| Dark | `#0F172A` |
| Amber | `#D97706` |
| Card shadow | `0 1px 3px rgba(0,0,0,0.06)` |
| Card radius | 14px |
| Sidebar bg | `#0F172A` |
| Navbar bg | `#FFFFFF` |

### Admin Dashboard Analytics
| Metric | Type | Data |
|---|---|---|
| Students | KPI card | `$dashboard['studentCount']` |
| Teachers | KPI card | `$dashboard['teacherCount']` |
| Parents | KPI card | `$dashboard['parentCount']` |
| Staff | KPI card | `$dashboard['nonteachingCount']` |
| Gender split | Doughnut chart | Chart.js 2.6, `maleCount`/`femaleCount` |
| Notices | List | Latest 5 |
| Exams | Table | Upcoming (configurable via gexam) |
| Feedbacks | List | Parent feedback |
| Events | Table | Latest 5 |
| Products | Table | Sellable products |
| Absentees | Vue component | `<absentees-student>`, `<absentees-staff>` |

---

## WhatsApp Integration

### Architecture
```
User ↔ WhatsApp ↔ Evolution API (Docker) ↔ Laravel Webhook
                                              ↕
                                        WhatsAppService (send)
                                        OutboundWhatsAppService (notify)
                                        MessageDeliveryLog (track)
```

### Evolution API Config
| Setting | Local | Production |
|---|---|---|
| URL | `http://localhost:8081` | `http://10.19.0.6:8081` |
| API Key | `68ca94ce...` | `78E5A6FF...` |
| Instance | `klassapp` | `klassapp` |

### Flow Architecture

```
                    ┌──────────────────────────────────────────┐
                    │            Inbound Flow                   │
                    │  User sends WhatsApp → +256 765 275289    │
                    │         ↓                                 │
                    │  Meta Cloud API (WABA) receives message    │
                    │         ↓                                 │
                    │  POST https://klassapp.xyz/.../inbound     │
                    │         ↓                                 │
                    │  WhatsAppController@handleInbound()        │
                    │  ├─ GET?  → webhook verification           │
                    │  └─ POST? → detect payload:                │
                    │      ├─ object: whatsapp_business_account  │
                    │      │  → handleMetaInbound()              │
                    │      └─ Evolution API format               │
                    │         → handleEvolutionInbound()         │
                    └──────────────────────────────────────────┘
                              ↓
                    processMetaMessage() / routeInbound()
                    ├─ Not identified? → "not linked" reply
                    ├─ Opted out? → optout reply
                    ├─ "optin"/"optout" → toggle, confirm
                    └─ Keyword match → data query → reply
                         ↓
                    flushPending() → drain queued notifications
                                      into free service window

                    ┌──────────────────────────────────────────┐
                    │           Outbound Flow                   │
                    │  Trigger events:                          │
                    │  ├─ Grades published → sendGradeNotification()
                    │  ├─ Fee reminder    → sendFeeReminder()
                    │  ├─ Cron: flushAllOpenWindows() (free)    │
                    │  └─ Cron: sendExpiredQueue() (cold, $)    │
                    │         ↓                                 │
                    │  OutboundWhatsAppService@queueOrSend()     │
                    │         ↓                                 │
                    │  ┌─ 24hr window open? ─┐                  │
                    │  │    yes          no   │                  │
                    │  ↓                    ↓                    │
                    │  sendTextDual()    queueNotification()     │
                    │  ↓                    ↓                    │
                    │  ┌─ Business API configured? ─┐            │
                    │  │  yes          no          │            │
                    │  ↓              ↓            │            │
                    │ WhatsAppBiz    WhatsAppSvc   │            │
                    │ (Meta Graph)   (Evolution)   │            │
                    │  ↓              ↓            │            │
                    │ POST /messages  POST /sendText            │
                    │ Logged to message_delivery_log             │
                    └──────────────────────────────────────────┘
```

### Dual-Transport Priority (OutboundWhatsAppService)
1. Try `WhatsAppBusinessService` (Meta Cloud API) first
2. If fails or not configured → fall back to `WhatsAppService` (Evolution API)
3. All callers (`MarksController`, `SendGradesToWhatsApp`, console commands) work unchanged via Laravel auto-resolution

### Cost Optimisation
- **Free window**: When a parent sends a message, a 24hr customer service window opens. Replies sent within this window are FREE (up to 1000/month).
- **Queued delivery**: If the window is closed, notifications are queued in `whatsapp_pending_notifications` and sent when the parent next messages (`flushPending()` drains queue into the free window).
- **Cold sends**: Cron `sendExpiredQueue()` sends notifications whose `send_after` deadline has passed — costs ~$0.004 each.

### Inbound Feature Matrix (by role via WhatsApp keywords)

| Role | Keywords | Response |
|---|---|---|
| **Parent** (7) | GRADES, FEES, ATTENDANCE, EVENTS | Grades by exam, fee balance, attendance %, upcoming events |
| **Student** (6) | GRADES, ATTENDANCE, FEES, TIMETABLE, HOMEWORK | Personal results, attendance, schedule, assignments |
| **Teacher** (5) | MARKS, ATTENDANCE, TIMETABLE, ASSIGNMENTS | Enter/view marks, mark attendance, schedule, homework |
| **SchoolAdmin** (3) | STUDENTS, STAFF, EXAMS, FEES, REPORTS, NOTICES | Student/staff lists, exam overview, fee reports, analytics |
| **Receptionist** (10) | NOTICES, CALLS | Announcements, call logs |
| **Accountant** (11) | FEES, REPORTS | Fee collections, financial summaries |
| **Staff w/ children** (any) | MY CHILDREN | Parent features for their own kids |
| **Unidentified** | anything | "Not linked" message → contact school |

### n8n / RAG / External Bot Integration
- Laravel handles keyword routing **inline** — no RAG, no LLM, no vector store.
- n8n was designed as the **conversation orchestrator** bridging WhatsApp → external services (Typebot/Flowise).
- Flow: `WhatsApp → Laravel webhook → HTTP call to n8n → Typebot/Flowise (conversation AI) → Laravel data API (identify, marks, attendance, fees)`
- The `/api/whatsapp/identify-user` endpoint is the data-only API n8n calls to resolve a phone number to a user, roles, and linked students.
- If integrating with external school ERPs, n8n is the right integration layer — Laravel stays as the data provider.

### Meta WhatsApp Business Cloud API (Active — June 2026 +)

**Critical ID hierarchy:**
- **Business Portfolio ID** (business.facebook.com): `856846937044672` — the Meta Business Account
- **WABA ID** (WhatsApp Business Account): `1709193870117417` — owns the phone number, receives messages
- **App ID** (developers.facebook.com): `1674033610469729` — the developer app with webhook callback URL
- **Phone Number ID**: `1192586767270209` — `+256 765 275289`, verified name "KlassApp", mode LIVE

**The WABA ID and Business Portfolio ID are DIFFERENT.** Using the wrong WABA ID was the root cause of webhook delivery failure.

**Webhook flow:**
1. App-level: Callback URL + Verify Token configured in App Dashboard ✓
2. App-level: Webhook field `messages` subscribed in WhatsApp → API Setup ✓
3. **WABA-level** (the missing step): `POST /{waba-id}/subscribed_apps` — subscribes the app to receive webhooks from the WABA. Requires the CORRECT WABA ID.
4. Once subscribed, Meta sends POST webhooks to the callback URL for each message the WABA receives.

**Outbound messaging** uses `phone_number_id` + token — works independently of WABA ID correctness.
**Inbound webhook delivery** requires both (2) the field subscription AND (3) the correct WABA subscription.

**Config:**
- `.env`: `WHATSAPP_BUSINESS_WABA_ID=1709193870117417`
- `config/services.php`: reads via `env()` — no code change needed
- Cache cleared: `php artisan optimize:clear`

### Webhook
- **Inbound**: `POST /api/whatsapp/inbound` → `WhatsAppController@handleInbound`
  - Handles both Evolution API (old) and Meta Cloud API (new) payloads — auto-detected via `object: "whatsapp_business_account"`
- **Delivery**: `POST /api/whatsapp/delivery` → `WhatsAppController@deliveryWebhook`
- **Critical fix**: Removed `EnsureFrontendRequestsAreStateful` from API middleware group (was causing 302 redirects)

### Key Files
| File | Purpose |
|---|---|
| `app/Services/WhatsAppService.php` | Evolution API client — sendText, sendList, sendTemplate |
| `app/Services/WhatsAppBusinessService.php` | Meta Cloud API client — sendText, sendTemplate, isConfigured |
| `app/Services/OutboundWhatsAppService.php` | Dual-transport router — tries Business API first, falls back to Evolution |
| `app/Http/Controllers/Api/WhatsAppController.php` | Webhook handlers, user identification |
| `app/Http/Controllers/Admin/WhatsAppDashboardController.php` | Delivery dashboard |
| `app/Models/WhatsAppUser.php` | Phone→user linking |
| `app/Models/MessageDeliveryLog.php` | Message tracking |
| `scripts/provision-evolution.sh` | Evolution API server provisioning |

---

## Test Credentials

| Role | Email | Password |
|---|---|---|
| Super Admin | `siteadmin@gmail.com` | `password` |
| Test School One | `admin@testschoolone.sch.ug` | `password123` |

---

## Toshi 2.0 Spec (Final — June 2026)

### Dual-Mode Architecture
- **Super Admin Dashboard**: Creates schools from scratch (plan selection → all 13 steps → commit)
- **School Admin Dashboard**: Completion mode (detects missing setup, prompts step-by-step)

### WhatsApp Integration
- **Verification**: Pre-approved template (`klassapp_verification`) sent via shared number
- **Opt-in strategy**: Prompt user to send first message to KlassApp number OR click WABA link to open 24hr window, OR use template (utility category, cheapest tier) for first contact
- **WhatsAppUser**: Source of truth for parent phone→school mapping. `school_id` column needed on table.
- **Parent verification**: Parent confirms student name during opt-in flow to link to correct school

### Implementation Priority (Ordered)

| # | Task | Effort | Status |
|---|---|---|---|
| 1 | Fix role assignment in commitAll() | 30 min | ✅ Done |
| 2 | Fix fee persistence in commitAll() | 30 min | ✅ Done |
| 3 | Fix step machine bug ($this->step = 1) | 30 min | ✅ Done |
| 4 | Add plan selection as Step 0 | 1 hr | ✅ Done |
| 5 | Button-driven flow (school type, confirm/edit pattern) | 3 hrs | ✅ Done |
| 6 | Input validation (duplicate, email, phone) | 2 hrs | ✅ Done — centralized validateRequired, normalizeUgandaPhone, validateEmail, isDuplicateSchool helpers |
| 7 | Review card before commit | 2 hrs | ✅ Done — styled card with plan, school, admin, counts grid, Confirm & Edit buttons |
| 8 | Error handling + user-friendly commit messages | 1 hr | ✅ Done — specific QueryException handling (duplicate vs connection), try/catch in commit, success card with credentials, reset button |
| 9 | WhatsApp verification step | 3 hrs | ✅ Done — OTP send/verify via WhatsApp template, WhatsAppUser record on commit, status in review card |
| 10 | Dual-mode detection (super admin vs admin) | 2 hrs | ✅ Done |
| 11 | Progress persistence (onboarding_sessions table) | 3 hrs | ✅ Done |
| 12 | Mandatory/optional step enforcement | 1 hr | ✅ Done |
| 13 | Admin dashboard completion mode | 3 hrs | ✅ Done (part of Item 10) |
| 14 | Persistent reminders (post-onboarding) | 3 hrs | ✅ Done |
| 15 | XLSX file parsing (CSV, XLSX, PDF, DOCX) | 2 hrs | ✅ Done |
| 16 | Add school_id to whatsapp_users table | 1 hr | ✅ Done |
| 17 | Co-admin invite step in Toshi | 2 hrs | ✅ Done |
| 18 | Premium API access (Integrations page) | Deferred | ⏸️ |

### Implementation Status

**Done (June 19):**
- ✅ Item 1 — Role assignment via `usergroup_id = 3` already correct, no fix needed
- ✅ Item 2 — Fee persistence: `commitAll()` now creates `FeesCategories` records
- ✅ Item 3 — Step machine bug: All handlers use `$substep` pattern instead of hardcoded step jumps
- ✅ Item 4 — Plan selection: Step 0 with plan buttons, `selectPlan()`, `CurrentPlan` + `Subscription` on commit
- ✅ Item 5 — Button-driven confirm/edit flow across all 12 steps with [✅ Looks good] / [✏️ No, edit] buttons
- ✅ Plan enforcement: `no_of_students`/`no_of_users` columns added, StudentController limit check, dashboard usage banner
- ✅ Toshi avatar: Fixed from non-existent `favicon/klassapp-favicon.svg` to `images/klassapp-logo.svg`
- ✅ Duplicate Alpine on superadmin layout: Commented out manual Alpine CDN
- ✅ Landing pricing restored to pre-merge simple inline design
- ✅ Real school names in marquee, real reviewers in testimonials

**Done (June 20):**
- ✅ Item 14 — Persistent reminders: Amber dismissible banner on admin dashboard showing missing onboarding steps + "Open Toshi" button + session dismiss route
- ✅ Item 12 — Mandatory/optional step enforcement: 7 mandatory steps (plan_selection, school_info, admin_account, academic_year, standards, subjects, terms), skip blocked with message, optional steps (teachers, students, fees, exams, whatsapp_verify, teacher_links)
- ✅ Item 15 — XLSX/DOCX/PDF parsing: `extractNamesFromFile()` handles CSV/TXT (fgetcsv), XLSX/XLS (PhpSpreadsheet), PDF (smalot/pdfparser), DOCX (PhpOffice/PhpWord). Libraries installed via composer.
- ✅ Item 16 — `school_id` on `whatsapp_users`: Migration adds nullable FK column. Model updated with `school()` relationship. All creation points (OnboardingAgent, UserProfileController) now set `school_id`.
- ✅ Item 17 — Co-admin invite step: Dual-mode step added to Toshi. Create mode: enter email+name → new User created. Complete mode: shows existing teachers as selectable buttons → promote (usergroup_id 5→3) on commit. Success card shows credentials or "Promoted from teacher".

### School Type Flow
- Category: [Primary] [Secondary] [Primary & Secondary (Mixed)]
- Levels (if Secondary/Mixed): [O-Level] [A-Level] [Both O&A]
- Gender (always): [Boys] [Girls] [Mixed (Co-ed)]
- Curriculum defaults load from NCDC Uganda per combination

### Known Issues

1. **Chart.js 2.6** — Do NOT upgrade to v4. API has breaking changes (legend→plugins.legend, tooltips→plugins.tooltip, scale→scales)
2. **Landing v2** — Has different navbar JS (direction-aware). Not aligned with v1 style.
3. **Toshi assistant mode** — Placeholder only. No handleGeneralQuery() yet. Cannot answer questions or run reports.

---

## Session Log

### 2026-06-20: Dashboard refresh, Toshi evolution — rename, assistant mode, maximize redesign
- **Work done**: Refreshed all 7 role dashboards to use dashboard-refresh.css (Accountant, Receptionist, Librarian, Student — Superadmin/Admin/Teacher already done). Fixed accountantDashboard() trait (was returning book data instead of fee data). Fixed plans table data (growth $30, premium contact sales). Renamed OnboardingAgent → AgentToshi across all files. Implemented post-onboarding assistant mode transition. Redesigned maximize modal with Claude-inspired two-column layout. Added session persistence (messages survive page refresh). Added co-admin email notification. Added chart title + timezone fixes. Maximized modal buttons were missing — fixed.
- **Files modified**: `app/Livewire/AgentToshi.php` (new, was OnboardingAgent.php), `resources/views/livewire/agent-toshi.blade.php` (new), `app/Traits/Dashboard.php`, `app/Helpers/OnboardingHelper.php`, `app/Mail/CoAdminInviteMail.php` (new), `resources/views/emails/co-admin-invite.blade.php` (new), `resources/views/emails/co-admin-promoted.blade.php` (new), `resources/views/admin/dashboard/dashboard.blade.php`, `resources/views/superadmin/dashboard.blade.php`, `resources/views/accountant/dashboard.blade.php`, `resources/views/reception/dashboard.blade.php`, `resources/views/student/dashboard/dashboard.blade.php`, `resources/views/library/dashboard.blade.php`, `database/migrations/2026_06_20_000001_add_school_id_to_whatsapp_users.php`, `app/Models/WhatsAppUser.php`, `app/Http/Controllers/Admin/UserProfileController.php`, `composer.json`, `knowledge.md`, `.env.example`
- **Key decisions**: OnboardingAgent renamed to AgentToshi for role-agnostic naming. Session-based state persistence (not database) keeps messages across refreshes. Assistant mode is placeholder — needs handleGeneralQuery() to be functional. Maximized modal uses two-column Claude layout. Plans updated: freemium $0, growth $30, premium contact sales.
- **Status**: ✅ All 7 dashboards refreshed. Toshi renamed + assistant mode wired. Next: build handleGeneralQuery() for real assistant functionality.

### 2026-06-20: Items 12, 14-17 complete — final Toshi features
- **Work done**: Completed remaining Toshi 2.0 items: Item 14 (persistent reminders — amber banner on admin dashboard with dismiss), Item 12 (mandatory/optional step enforcement — 7 mandatory steps, skip blocked), Item 15 (XLSX/DOCX/PDF parsing via PhpSpreadsheet + smalot/pdfparser + PhpOffice/PhpWord), Item 16 (school_id on whatsapp_users — migration + model + all creation points), Item 17 (co-admin invite step — dual-mode: create new admin or promote existing teacher via button selection).
- **Files modified**: `app/Livewire/OnboardingAgent.php`, `app/Helpers/OnboardingHelper.php` (new), `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/partials/onboarding-reminder.blade.php` (new), `routes/web.php`, `database/migrations/2026_06_20_000001_add_school_id_to_whatsapp_users.php` (new), `app/Models/WhatsAppUser.php`, `app/Http/Controllers/Admin/UserProfileController.php`, `resources/views/livewire/onboarding-agent.blade.php`, `composer.json` (added phpoffice/phpword, smalot/pdfparser)
- **Key decisions**: PDF/DOCX names extracted via parseNameList (line-based, not column-based). Co-admin uses dual-mode: create new User (create mode) vs promote existing teacher via usergroup_id 5→3 (complete mode). Onboarding reminders use session flag for dismiss. Step enforcement uses centralized skip guard in send() plus mandatory handler modifications.
- **Status**: ✅ Items 1-17 complete. Item 18 (Premium API) deferred. Remaining work: missing dashboards, bar chart data, navbar scroll fix, mobile audit.

### 2026-06-19: Toshi 2.0 items 1-11 complete, plan enforcement, landing fixes
- **Work done**: Completed Toshi 2.0 items 1-11: critical fixes (fee persistence, step machine), plan selection, button-driven confirm/edit flow across all steps, input validation helpers (validateRequired, validateEmail, normalizeUgandaPhone, isDuplicateSchool), styled review card, error handling with specific QueryException messages, WhatsApp verification step with OTP send/verify, dual-mode detection (super admin create vs school admin complete), progress persistence via onboarding_sessions table. Built plan enforcement system: migration for no_of_students/no_of_users columns, StudentController limit check, dashboard usage banner. Restored landing pricing, favicon/Toshi avatar, real school names, real testimonials.
- **Files modified**: `app/Livewire/OnboardingAgent.php`, `resources/views/livewire/onboarding-agent.blade.php`, `database/migrations/2026_06_19_190534_add_plan_limits_to_plans_table.php`, `database/migrations/2026_06_19_222331_create_onboarding_sessions_table.php`, `app/Models/OnboardingSession.php`, `app/Http/Controllers/Admin/StudentController.php`, `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/partials/plan-banner.blade.php`, `resources/views/admin/dashboard/dashboard.blade.php`, `resources/views/landing.blade.php`, `resources/views/landing2.blade.php`, `resources/views/layouts/superadmin-app.blade.php`, `resources/views/layouts/partials/favicon.blade.php`, `knowledge.md`
- **Key decisions**: Dual-mode architecture — super admin creates, school admin completes. Auth via usergroup_id. Progress persisted via onboarding_sessions. WhatsApp OTP uses sendTextSafe with template fallback. Confirm/edit pattern uses $substep with even=collect, odd=confirm.
- **Status**: ✅ Items 1-11 done. Next session: Item 14 (persistent reminders) then 12, 15-17.

### 2026-06-18: Toshi onboarding agent fixes (textarea, Livewire/Vue/Alpine conflict)
- **Work done**: Fixed Toshi AI onboarding agent — three compounding issues: (1) Missing `</textarea>` in maximize modal caused the browser to treat the voice button, form, messages area, and submit button as raw textarea content. (2) Duplicate Alpine instances (manual CDN Alpine + Livewire v3 bundled Alpine) caused `Livewire.all()` to return 0 components, breaking all Livewire interactions (maximize, close, restore, send). (3) Vue 2 mounted on `#app` wrapping the Livewire component compiled Alpine's `@keydown.enter.prevent` as Vue template expressions, breaking Enter-to-send with `"$wire is not defined"`.
- **Files modified**: `resources/views/livewire/onboarding-agent.blade.php` (new — added `</textarea>`, replaced Alpine `@keydown` with vanilla JS `onkeydown`), `app/Livewire/OnboardingAgent.php` (new), `resources/views/layouts/app.blade.php` (removed duplicate Alpine CDN, added `@yield('outside-app')` after `#app` closes), `resources/views/admin/dashboard/dashboard.blade.php` (moved `@livewire` to `@section('outside-app')` outside Vue's `#app`)
- **Key decisions**: Livewire component rendered outside `#app` via new `@yield('outside-app')` section to avoid Vue template compilation conflicts. Removed manual Alpine CDN — Livewire v3 bundles its own Alpine. Used vanilla JS `onkeydown` dispatching form submit events / `Livewire.find()` directly instead of Alpine's `@keydown.enter.prevent` syntax that Vue intercepts.
- **Status**: ✅ Done — collapse/expand, maximize/restore, Enter-to-send all verified working
- **Edge cases flagged**: Livewire v3 auto-injects Alpine, so manual Alpine CDN causes `"Detected multiple instances of Alpine"` warning and breaks component registration. Vue 2's `el: '#app'` recompiles the entire DOM tree inside `#app`, which conflicts with any non-Vue framework directives in that subtree.

### 2026-06-17: Meta WhatsApp Business API — webhook delivery fix
- **Work done**: Fixed inbound webhook delivery for Meta Cloud API. Root cause: config had the **Business Portfolio ID** (`856846937044672`) as `WHATSAPP_BUSINESS_WABA_ID` instead of the actual **WABA ID** (`1709193870117417`). App → WABA subscription (`POST /{waba-id}/subscribed_apps`) was silently failing because it was pointing at the wrong object. Also fixed flock() cache error by switching `CACHE_DRIVER` from `file` to `database`. Fixed PHP `parse_str()` dot-to-underscore bug in webhook verification handler. Added `WhatsAppBusinessService` and dual-transport `OutboundWhatsAppService`. Added full reply flow in `handleMetaInbound()`.
- **Files modified**: `.env` (WABA ID, CACHE_DRIVER), `app/Http/Controllers/Api/WhatsAppController.php`, `app/Services/OutboundWhatsAppService.php`, `app/Services/WhatsAppBusinessService.php`
- **Key decisions**: Separate `WhatsAppBusinessService` from `WhatsAppService` — both transports coexist during migration. Dual-transport in OutboundWhatsAppService (Business API first, Evolution fallback). Webhook endpoint auto-detects payload format via `object: "whatsapp_business_account"`. Used database cache instead of file to resolve persistent flock() errors.
- **Status**: ✅ Done — inbound and outbound both working
- **Edge cases flagged**: Meta has THREE separate IDs (Business Portfolio, WABA, App) and they are NOT interchangeable. The WABA subscription endpoint returns `{"success": true}` only when called with the correct WABA ID. Webhook verification GETs send query params with dots (PHP's `$_GET` converts dots to underscores — fixed by parsing raw `QUERY_STRING`).

### 2026-06-10: Dashboard redesign, WhatsApp webhook fix, production deploy
- **Work done**: Redesigned admin dashboard (CSS + Blade), fixed WhatsApp webhook 302 redirect, fixed login maintenance banner, deployed to production (165.245.250.16), provisioned Evolution API on 46.101.130.70, connected WhatsApp instance
- **Files modified**: `dashboard-refresh.css`, `app.blade.php`, `admin/dashboard/dashboard.blade.php`, `admin/sidebar.blade.php`, `navigation.blade.php`, `auth/login.blade.php`, `app/Http/Kernel.php`, `routes/api.php`
- **Key decisions**: Kept Chart.js 2.6 (no upgrade to avoid breaking changes). Used vanilla JS for hamburger (avoid Tailwind compilation issues). Changed Evolution API DB from MongoDB to PostgreSQL on production.
- **Status**: ✅ Done
- **Edge cases flagged**: WhatsApp webhook 302 was caused by Sanctum's `EnsureFrontendRequestsAreStateful` in API middleware group. Evolution API image name is `evoapicloud/evolution-api` not `evolutionapi/evolution-api`.

### 2026-06-18 pt2: Landing fixes + mobile audit + PR merge resolution
- **Work done**: Fixed navbar scroll direction (v1 & v2 — now direction-aware), fixed typewriter cursor blink (pauses during typing via `.paused` CSS class, resumes after), mobile touch target audit (audience tabs `py-2`→`py-3`, hamburger `p-2`→`p-3`, mobile nav links `py-3` — all now ≥44px), dashboard visual polish across 7 roles (brand colors, empty states, responsive breakpoints), merged origin/main into whatsapp resolving 6-way conflicts
- **Files modified**: `landing.blade.php` (scroll + typewriter + tabs + hamburger + mobile menu), `landing2.blade.php` (scroll + typewriter + tabs + hamburger + mobile menu), `admin/dashboard/dashboard.blade.php` (brand badges + headings), `teacher/dashboard/dashboard.blade.php`, `accountant/dashboard.blade.php`, `reception/dashboard.blade.php`, `library/dashboard.blade.php`, `dashboard-refresh.css` (responsive breakpoints + design tokens), `knowledge.md` (merge resolution)
- **Key decisions**: Used vanilla JS `onkeydown` instead of Alpine for Enter-to-send (Vue conflict). Kept direction-based scroll on v2 (Flare-style), position-based on v1 (HTML restructured). Audience tabs now `py-3` (48px) meeting WCAG 44px touch target. Merged origin/main (40+ commits) into whatsapp — kept HEAD for dashboard files (superset), combined knowledge.md entries.
- **Status**: ✅ Done — PR #104 now rebased, all conflicts resolved, pushed
- **Edge cases flagged**: `navLogo` variable absent from v1 after main restructure — used `.site-header` scrolled class toggle only. Playwright artifacts (.playwright-mcp/) inadvertently committed then removed.
