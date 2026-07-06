# KlassApp Project Knowledge

## Current Status: July 6, 2026

### Git
- **Branch**: `main`
- **HEAD**: `d9bd1e5` — "feat: nursery descriptive assessment grading + PDF report rendering, fix ReportsController bug" (committed + pushed)
- **Remote**: `origin/main` (GitHub: Elijah-ug/KlassApp)

---

## Production

| Component | Server | URL |
|---|---|---|
| KlassApp App | 46.101.111.131 | https://klassapp.xyz |
| KlassApp (WABA) | 46.101.111.131 | https://klassapp.xyz |
| ~~Evolution API~~ | ~~decommissioned~~ | ~~Replaced by Meta WABA~~ |
| Deploy key | `~/.ssh/id_ed25519_do` | |

### Deploy Command
```bash
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 \
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
1. **Role 2 — School Admin full audit**: student/parent management, class/subject setup, reports, messaging, library, health modules. Check for same bug classes (compact($id), unguarded ->first(), Blade syntax, unscoped latest()).
2. Run the three new migrations on production (toshi_personas, any pending)
3. Configure School Pay webhook forwarding on production
4. Test School Pay → WhatsApp receipt flow end-to-end
5. Add `SCHOOLPAY_ENFORCE_SIGNATURE` toggle (env or School model) to reject unsigned webhooks
6. Clean up `whatsapp_pending_parent_links` dead table (drop or document)
7. Toshi: personalize greeting with persona on mount
8. Run `composer update` to remove laratrust from lock file after verifying no regressions

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
4. **Landing pages** — Both have broken HTML artifacts from previous merges (stray `</nav>` tags, duplicate mobile menus, garbled WhatsApp link fragments). Have been cleaned up but v2 navbar scroll direction still differs from v1.

---

## Environment Variable Gotchas

### TOSHI_LARAGENT_ENABLED env var leak (fixed July 2026)

**Symptom:** `ToshiAssistantAgentTest::feature_flag_defaults_to_disabled` fails intermittently — asserts `config('toshi.laragent_enabled')` is false but gets true, even though `.env` doesn't set it or sets it correctly.

**Root cause:** Laravel 10's `Dotenv\Repository` caches env values from THREE sources — `getenv()`, `$_SERVER`, and `$_ENV` — at boot, and caches them immutably. If `TOSHI_LARAGENT_ENABLED` was ever exported in a parent shell (e.g. the terminal that launched opencode/your dev tooling), it persists in `$_SERVER` even after `putenv()` clears `getenv()`. A shell-level export silently overrides `.env` for any Laravel process spawned from that shell — **this is NOT specific to Toshi, it applies to any env var**.

**Fix applied:**
1. `.env` explicitly has `TOSHI_LARAGENT_ENABLED` commented out (not just absent) with a note on intended usage (per-school-admin toggle, not global).
2. The specific test was hardened to clear all three sources (`getenv`, `$_SERVER`, `$_ENV`) plus reset `Illuminate\Support\Env`'s cached repository via reflection, so it tests the true default regardless of ambient shell state. See `tests/Feature/Toshi/ToshiAssistantAgentTest.php::feature_flag_defaults_to_disabled` for the implementation.

**If this pattern recurs elsewhere:** any test asserting a config "default" (not an explicitly-set value) is vulnerable to the same class of bug if the corresponding env var has ever been exported in a shell that spawns test runs. The fix pattern (clear `getenv`/`$_SERVER`/`$_ENV` + reset the Env repository) is reusable — don't assume `.env` alone is the source of truth when debugging config default mismatches.

**Caution for Laravel 11 upgrade:** the reflection-based repository reset (`ReflectionClass(\Illuminate\Support\Env::class)->setStaticPropertyValue('repository', null)`) depends on Laravel 10's internal implementation of `Env`. If this test starts failing after the Laravel 11 composer update, check this reflection call first — the internal structure of `Illuminate\Support\Env` may have changed.

---

## Session Log

### 2026-07-06: Nursery descriptive assessment grading + PDF report rendering + ReportsController fix
- **Work done**: Implemented descriptive assessment grading for Nursery (4 domains, 4-level scale with no points/percentages). Created NurseryAssessment model/migration. Made points/min_score/max_score nullable in school_grading_systems. Added nursery detection and conditional rendering to PDF report card generation (controller + view). Fixed ReportsController@index typo bug. Browser-verified: Reports page load, CSV export download, fee reconciliation page, messaging page. Health aggregate dashboard explicitly deferred.
- **Files modified**: `.gitignore`, `config/grading_uganda.php`, `app/Helpers/GradingHelper.php` (seeding loop), `app/Http/Controllers/Admin/DownloadStudentReport.php` (nursery detection), `app/Http/Controllers/Admin/ReportsController.php` (bug fix), `resources/views/admin/marks/student-report.blade.php` (nursery conditional block)
- **Files created**: `app/Models/Academics/NurseryAssessment.php`, `database/migrations/2026_07_06_022733_make_points_nullable_in_school_grading_systems.php`, `database/migrations/2026_07_06_030000_create_nursery_assessments_table.php`
- **Key decisions**: Nursery uses separate NurseryAssessment table (not exam_marks) for clean separation of descriptive vs numeric assessment. Single student-report template with `@if(!empty($isNursery))` conditional rather than separate nursery template. Health aggregate dashboard explicitly deferred (sidebar Health link redirects to per-student records via /admin/students).
- **Status**: ✅ Committed (d9bd1e5) and pushed to origin/main. Nursery PDF report card click-test deferred until assessment entry UI exists.
- **Edge cases flagged**: Points column forced NOT NULL in SchoolGradingSystem prevented seeding nursery grades with null points — fixed via migration making points/min_score/max_score nullable. Reports page route commented out — fix prevents crash if re-enabled. No assessment entry UI for nursery yet (no UI to enter domain ratings).

### 2026-07-04: Role 2 School Admin audit — 7 modules + 2 carried-forward checks
- **Work done**: Performed systematic functional and UI audit of School Admin role. Covered impersonation boundaries, role-capability scoping, student management, parent management, class/subject setup, reports, messaging, library, and health modules. Identified: 1 HIGH (Library has models but no admin UI — dead sidebar link), 2 MEDIUM (Messaging dead sidebar link; StudentController destroy() has empty catch blocks + missing school_id on sub-record deletion), 1 LOW (ParentController ungrouped orWhere in count query). Confirmed 4 dead sidebar links (library, health, messaging, transport). Documented all findings in new `## Role 2 Audit` section.
- **Files modified**: `knowledge.md`
- **Key decisions**: findings stored as permanent reference section (not just session log) because the audit will inform feature planning. Health module definitively confirmed as absent — only student-level medical history exist, no school-level health management. Library has data model but zero admin UI.
- **Status**: 🚧 Audit complete (findings reported, not fixed). Session log entry also notes this but permanent audit reference is the canonical source.
- **Edge cases flagged**: `orWhere` operator precedence bug in ParentController count query; `is_admin()` function name misleading (checks for School Admin, not Super Admin); `schoolAdminimpersonate()` has inverted condition logic but is safely gated by superadmin middleware.

### 2026-07-04: TOSHI_LARAGENT_ENABLED env var leak documented
- **Work done**: Documented the `TOSHI_LARAGENT_ENABLED` env var leak — root cause (Laravel 10 immutable Dotenv\Repository caches from `$_SERVER`/`$_ENV`/`getenv`), fix (triple-source scrub + Env repository reset via reflection), and reusable pattern for other config-default tests.
- **Files modified**: `knowledge.md` (new section), `tests/Feature/Toshi/ToshiAssistantAgentTest.php` (hardened test)
- **Key decisions**: Stored as permanent gotcha in dedicated `## Environment Variable Gotchas` section (not just session log) because the debugging pattern applies project-wide to any env var, not just Toshi.
- **Status**: ✅ Done

### 2026-06-20: Favicon fix, nav cleanup, landing HTML repair
- **Work done**: Fixed favicon to use SVG as primary with proper cross-browser fallback. Removed "KlassApp" wordmark from nav headers, enlarged logo (52px/44px). Repaired broken HTML on both landing pages — stray `</nav>` tags, duplicate mobile menus, garbled WhatsApp link fragment that rendered as visible text.
- **Files modified**: `resources/views/layouts/partials/favicon.blade.php`, `resources/views/landing.blade.php`, `resources/views/landing2.blade.php`, `knowledge.md`
- **Key decisions**: SVG favicon as single source of truth with `type="image/svg+xml"`. PNG fallback for older browsers. Apple touch icon unchanged.
- **Status**: ✅ Favicon fixed, nav cleaned up. Landing v1/v2 alignment still outstanding.

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

### 2026-06-26: Full WABA migration + WhatsApp UI upgrades + Facebook ban
- **Work done**: Greeting handler (Hello, Hi, Hey → menu buttons), wa.me link texts simplified to "Hello KlassApp", production IP updated (165.245.250.16 → 46.101.111.131), fully migrated from Evolution API to Meta Cloud API (removed WhatsAppService.php, all isConfigured() fallbacks, handleEvolutionInbound, buildMenuSections, processCodeVerificationForEvolution, Evolution config), added native sendList + sendTextSafe + sendToUser to WhatsAppBusinessService, switched all consumers (NotifyAdminMarksUpdated, AgentToshi, SchoolPayWebhookController, OutboundWhatsAppService) to Business API only. UI upgrades: fee balance with visual separators, attendance tonal warnings (⚠️ < 80%, ✅ > 90%), grades celebration with class rank for avg > 80%, distinct message type patterns. Recovered 9 compose methods dropped during migration. Facebook Business Account banned — need new Meta account + new WhatsApp number.
- **Files modified**: `app/Services/WhatsAppBusinessService.php`, `app/Services/OutboundWhatsAppService.php`, `app/Http/Controllers/Api/WhatsAppController.php`, `app/Listeners/NotifyAdminMarksUpdated.php`, `app/Livewire/AgentToshi.php`, `app/Http/Controllers/Api/SchoolPayWebhookController.php`, `config/services.php`, `resources/views/landing.blade.php`, `resources/views/landing2.blade.php`, `AGENTS.md`, `scripts/deploy-manual.sh`, `scripts/provision-klassapp.sh`, `scripts/provision-evolution.sh`
- **Deleted**: `app/Services/WhatsAppService.php` (Evolution API, 395 lines)
- **Key decisions**: Stripped Evolution entirely instead of leaving fallback code. No intermediate transition — Meta is the only transport now. sendList upgraded to native Meta interactive lists instead of text fallback. Class rank calculated by comparing SUM(marks) across same exam in DB.
- **Status**: ✅ WABA migration complete. Pending: new Meta Business Account + new WhatsApp number
- **Next**: Set up new Meta Business Account with new SIM number, update .env credentials, redeploy

### 2026-06-18 pt2: Landing fixes + mobile audit + PR merge resolution
- **Work done**: Fixed navbar scroll direction (v1 & v2 — now direction-aware), fixed typewriter cursor blink (pauses during typing via `.paused` CSS class, resumes after), mobile touch target audit (audience tabs `py-2`→`py-3`, hamburger `p-2`→`p-3`, mobile nav links `py-3` — all now ≥44px), dashboard visual polish across 7 roles (brand colors, empty states, responsive breakpoints), merged origin/main into whatsapp resolving 6-way conflicts
- **Files modified**: `landing.blade.php` (scroll + typewriter + tabs + hamburger + mobile menu), `landing2.blade.php` (scroll + typewriter + tabs + hamburger + mobile menu), `admin/dashboard/dashboard.blade.php` (brand badges + headings), `teacher/dashboard/dashboard.blade.php`, `accountant/dashboard.blade.php`, `reception/dashboard.blade.php`, `library/dashboard.blade.php`, `dashboard-refresh.css` (responsive breakpoints + design tokens), `knowledge.md` (merge resolution)
- **Key decisions**: Used vanilla JS `onkeydown` instead of Alpine for Enter-to-send (Vue conflict). Kept direction-based scroll on v2 (Flare-style), position-based on v1 (HTML restructured). Audience tabs now `py-3` (48px) meeting WCAG 44px touch target. Merged origin/main (40+ commits) into whatsapp — kept HEAD for dashboard files (superset), combined knowledge.md entries.
- **Status**: ✅ Done — PR #104 now rebased, all conflicts resolved, pushed
- **Edge cases flagged**: `navLogo` variable absent from v1 after main restructure — used `.site-header` scrolled class toggle only. Playwright artifacts (.playwright-mcp/) inadvertently committed then removed.

### 2026-06-20: Droplet rebuild, WABA migration, full audit
- **Work done**: Rebuilt destroyed droplet (new IP 46.101.111.131), full LEMP + Laravel provision, migrated from Evolution API to Meta WABA (direct), fixed Docker iptables DROP blocking external traffic, fixed Google OAuth (malformed .env line), fixed mobile hamburger menus on both landings + admin, fixed Str::plural for PHP 8.4 compat, added google_id migration locally, created full audit at `audit.md`
- **Key discoveries**: Production .env had concatenated line (`WHATSAPP_BUSINESS_API_VERSION=v21.0GOOGLE_CLIENT_ID=...`), Docker left iptables FORWARD DROP rules after removal, landing2 was missing mobileMenu div entirely, admin res_sidebar toggle JS ran before DOM ready
- **Verdict**: ⚠️ NOT READY for school onboarding — 6 critical blockers (35 dd() calls, APP_DEBUG=true, no HMAC webhook verification, 0 swap, no backups, 3 incomplete dashboards)
- **Next**: Fix critical items 1-6, deploy docs to production, add WhatsApp linking UI, wire real chart data

### 2026-06-23: School Pay webhook + interactive WhatsApp lists
- **Work done**: Built School Pay webhook controller (HMAC verification, student join chain, WhatsApp receipt), added free-form message builders (composeFeeBalance, composeAttendance, composeGradesOverview, composeHealthRecord, composeStudentWithdrawn, composeTermOpens, composeTermCloses), added sendButtons() and sendList() for interactive messages, replaced text-based "Reply FEES..." prompts with interactive List messages on welcome/verification, added emoji-stripping to routeInbound() so list button titles match keyword routing, added sendListDual() to OutboundWhatsAppService. Added "Link Another Student" button to all welcome/receipt lists + 10-digit code handling for recognized users in routeInbound + 'code'/'link' keywords in parent routing. Webhook receipt also uses sendList instead of sendText.
- **Files modified**: SchoolPayWebhookController.php (new), WhatsAppController.php, WhatsAppService.php, OutboundWhatsAppService.php, WhatsAppPendingParentLink.php (new), routes/api.php, 3 new migrations
- **Key decisions**: Direct linking without school approval when code matches student. Free-form messages for all 24hr-window interactions (no Meta cost). List buttons replace text-based "Reply KEYWORD" prompts for better UX. Emoji stripping in routing allows lists and typed keywords to share the same route table. Recognized users can also link additional students via 10-digit codes (routeInbound catches codes before keyword matching).
- **Status**: ✅ Done — School Pay integration complete, list buttons deployed, Link Another Student flow working for all users
- **Edge cases flagged**: School Pay webhook payload format unconfirmed (raw_payload column for inspection). List button titles with emojis need stripping before keyword matching. SCHOOlPAY_ENFORCE_SIGNATURE toggle needed before production. whatsapp_pending_parent_links table is dead schema weight (flow changed to direct linking)

### 2026-06-29 pt2: KlassApp Student IDs + ministry codes + teacher links + admin parity + onboarding audit
- **Work done**: KlassApp Student ID system (KLS0010427 format, auto-generated, unique, indexed). Migration added klassapp_student_id to student_academics. Ministry school codes (ministry_code on schools table). Toshi teacher-subject-class linking with Teacher | Subject | Class | Phone format + CSV/XLSX upload. Fixed Toshi commitAll to persist teacher links + students. Admin panel: bulk teacher link import page, WhatsApp parent management page, KlassApp ID in student edit view. Question phrasing fixes, step indicator, password collection, dead code removal, teacher link file auto-advance. Full WABA migration completed (Evolution removed). WhatsApp UI upgrades (separators, tonal warnings, class rank). Name-based WhatsApp linking with school-scoped search. Docs: ai-integration-roadmap.md, Toshi walkthrough updated. FB Business Account banned — pending new Meta account + new WhatsApp number.
- **Files modified**: 30+ files across controllers, services, livewire, migrations, blade templates, docs
- **Key decisions**: KlassApp IDs use no-dash format. School Ministry codes optional. Toshi now parity with admin panel (or better). WhatsApp linking prioritizes KlassApp ID → school ID → name+school → name.
- **Status**: ✅ Demo-ready for Wednesday. All features end-to-end verified.
- **Next**: Wednesday — onboard real school, present to founding team. After: new Meta account setup, complete Admin panel → whatsbusiness integration.

### 2026-06-29: WABA migration complete + WhatsApp UI upgrades + name-based linking + Toshi fix
- **Work done**: Greeting handler (Hello, Hi, Hey → menu buttons), wa.me links simplified to "Hello KlassApp", production IP updated, full WABA migration (WhatsAppService.php deleted, Evolution removed, all isConfigured() checks stripped, handleEvolutionInbound/buildMenuSections/processCodeVerificationForEvolution deleted). WhatsApp UI upgrades: fee separators, attendance tonal warnings, grades celebration + class rank, message type differentiation. Student name-based WhatsApp linking (replaced School Pay code dependency). AI integration roadmap at docs/ai-integration-roadmap.md. Fixed Toshi commitAll() to persist students. Facebook Business Account banned — switching to new number.
- **Files modified**: WhatsAppController.php, WhatsAppBusinessService.php, OutboundWhatsAppService.php, AgentToshi.php, config/services.php, NotifyAdminMarksUpdated.php, SchoolPayWebhookController.php, landing.blade.php, landing2.blade.php, AGENTS.md, scripts/*
- **Deleted**: app/Services/WhatsAppService.php (Evolution)
- **New**: docs/ai-integration-roadmap.md
- **Key decisions**: Name search replaces School Pay for WhatsApp linking. Stripped Evolution entirely instead of fallback. Toshi now creates students during onboarding.
- **Status**: ✅ Ready for Wednesday demo. WhatsApp name linking, Toshi onboarding, and parent messaging flow all functional.
- **Next**: Wednesday — onboard real school, demo to founding team. Then: new Meta account + WhatsApp number.

### 2026-06-30: Toshi E2E Test Passes + 6 bugs fixed in commitAll()
- **Work done**: Created Playwright E2E test for full 15-step Toshi onboarding. Fixed 3 PHP bugs (saveDraft not capturing model ID → duplicate drafts; handleTeacherLinks/handleWhatsAppVerify missing skip at substep=1 → infinite loops). Fixed 3 DB schema mismatches in commitAll() (subscriptions.user_id FK, sections.value column missing, standards_link.class_teacher_id NOT NULL). Test now creates a school end-to-end with all data (classes, subjects, teachers, students, fees, exams, subscription).
- **Files modified**: `app/Livewire/AgentToshi.php` — saveDraft(), handleTeacherLinks() skip substep=1, handleWhatsAppVerify() skip substep=1, commitAll() reordered (admin user before subscription), Section::firstOrCreate() removed bogus `value` column. `database/migrations/2026_06_30_003133_make_class_teacher_id_nullable_in_standards_link.php` — new (but not migratable due to pre-existing broken FK migration).
- **Key decisions**: E2E test lives outside repo at `/var/folders/d1/.../toshi-test.mjs`. Uses Livewire JS API (`lw.call()`) instead of DOM clicks (unreliable with Livewire 3). Always "reset" on draft (no generic resume handler). Unique timestamp-suffixed data per run to avoid DB collisions.
- **Status**: ✅ Toshi onboarding working end-to-end. Known critical issues remain: editBeforeCommit() dead end, no secondary school curriculum defaults, zero PHPUnit coverage.
- **Next**: Fix editBeforeCommit() to allow step navigation from review card. Add secondary curriculum. Write PHPUnit tests.

### 2026-06-30: Toshi architecture audit, universal layout refactor, LLM activation
- **Work done**: Full Toshi UI audit across admin and superadmin dashboards. Found Vue 2 strips `<style>` tags inside `#app` — Toshi was inside `#app` on superadmin (styles deleted), outside `#app` on admin (styles survived). Refactored Toshi from per-dashboard inclusion to universal layout-level component. Added `defer` to `app.js` on both main layouts. Fixed superadmin layout missing `@yield('outside-app')`. Gated Toshi to usergroups 1, 2, 3 at both view and component levels. Activated LLM with Nvidia NIM key across local and production. Fixed auth page styles being stripped by Vue (moved `<style>` to `@push('styles')` outside `#app`). Fixed Google Fonts `@import` inside body `<style>` by converting to `<link>` in layout `<head>`. Production asset rebuild (JS 35MB→4.5MB, CSS 1.6MB→66KB, sourcemaps removed). Added gzip + 1-year cache headers for static assets. Set session cookie Secure flag for HTTPS. Created mucu super admin on production DB.
- **Files modified**: `resources/views/layouts/app.blade.php` (add Toshi + defer), `resources/views/layouts/superadmin-app.blade.php` (add Toshi + `@yield('outside-app')` + defer), `resources/views/superadmin/dashboard.blade.php` (remove per-dashboard Toshi), `resources/views/admin/dashboard/dashboard.blade.php` (remove per-dashboard Toshi), `app/Livewire/AgentToshi.php` (usergroup gate), `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/auth/passwords/reset.blade.php`, `resources/views/auth/passwords/email.blade.php`, `resources/views/auth/verify.blade.php` (move styles outside Vue `#app`), `resources/views/layouts/empty.blade.php` (fonts to `<link>`), `webpack.mix.js` (conditional sourcemaps), `public/` (production rebuild), `.env.example`
- **Key decisions**: Toshi lives at layout level, not per-dashboard — any authenticated page using `layouts/app.blade.php` or `layouts/superadmin-app.blade.php` gets it automatically. LLM base URL is `https://integrate.api.nvidia.com/v1` (not `api.nvcf.nvidia.com`). Toshi access gated to usergroups 1, 2, 3 — expands later. Draft resume via `OnboardingSession` DB table is superadmin-only.
- **Status**: ✅ Done
- **Edge cases flagged**: Shell env vars override `.env` because `phpdotenv` doesn't overwrite existing environment variables. NIM base URL changed from `api.nvcf.nvidia.com` to `integrate.api.nvidia.com`.

### 2026-07-01: Toshi persona memory system
- **Work done**: Built persistent persona memory for Toshi. Each user gets a `toshi_personas` row that stores a one-paragraph summary of their communication style, what they care about, and their general vibe. The summary is injected into the LLM system prompt as `{persona}` so Toshi adapts tone and depth to each user. After every LLM interaction, a counter increments; every 5th interaction (configurable via `TOSHI_PERSONA_UPDATE_INTERVAL`), a lightweight extraction LLM call (gpt-4o-mini, 200 max tokens) reads the conversation and updates the persona. Also: user chat bubbles changed from black to off-white (`#F8FAFC`) to match Toshi's style; three composer icons stripped of backgrounds/borders per user request.
- **Files modified**:
  - `database/migrations/2026_07_01_000001_create_toshi_personas_table.php` — new
  - `app/Models/ToshiPersona.php` — new
  - `app/Services/ToshiAssistantService.php` — `getPersonaSummary()`, `learnFromInteraction()`, `buildSystemPrompt()` persona injection, `callLLM()` maxTokens param
  - `config/toshi.php` — added `persona_enabled`, `persona_update_interval`, `persona_model`
  - `resources/views/livewire/agent-toshi.blade.php` — user msg bg `#F8FAFC`, icon cleanup
- **Key decisions**: Persona learning happens inside `ToshiAssistantService::ask()`/`askStreamed()`, not in Livewire — zero changes needed in AgentToshi controller. Persona extraction uses a separate model config (defaults to same as main). Disabled via `TOSHI_PERSONA_ENABLED=false` with no cost impact. Extraction LLM call is wrapped in try-catch — API failures degrade silently, existing persona survives.
- **Status**: ✅ Done
- **Edge cases flagged**: `users.id` is `INT UNSIGNED` (not `BIGINT`) — `foreignId()` creates `BIGINT UNSIGNED` causing FK constraint failure; fixed by using `integer('user_id')->unsigned()` manually. All DB data was wiped during migration testing via `php artisan db:wipe` — required re-seeding.

### 2026-07-02: Onboarding fixes, student class assignment bug, LarAgent migration, design overhaul
- **Work done**: Fixed WhatsApp TypeError (nullable token properties + non-blocking OTP). Created `commit()` public method for Confirm button. Added pre-flight duplicate checks (school name + admin email). Fixed critical student class assignment bug (all students were dumped into P1 regardless of class field). Fixed edit flow with fuzzy step matching. Deduplicated teachers/students from file uploads. Created missing userprofile for mucu super admin. Reset Kabale Junior School admin password. Phase 2 design implementation: fixed Toshi pill color (H2), applied DM Sans to dashboard body (M1), CSS consolidation plan documented. Admin dashboard: unified KPI card padding (M2), reduced icon sizes, standardized empty states. Toshi widget: extracted 6 CSS classes (pill/panel/modal), fixed state-transition bugs (removed inline JS pill onclick, fixed modal overlay close, added mobile responsive widths with dvh units, touch targets 44px min). LarAgent migration (Steps 1-7): installed LarAgent, created `ToshiAssistantAgent` with 18 #[Tool]-annotated action methods, Nvidia NIM provider config, feature flag (`TOSHI_LARAGENT_ENABLED`), parallel routing with path logging. Steps 8-11: complexity-based model routing, response caching, batched persona extraction, static context caching. 17 tests passing. Sidebar overhaul: replaced 171 inline SVGs with Blade component (`<x-icons.sidebar>`), centralized active state helper across all 9 role menus. Internal pages: wrapped students list and standardlinks views in dashboard card pattern. Toshi CSS: 20+ new classes for messages, composer, buttons, progress, badges. Open Design audit applied: Navy header (#0D1526) with green pulsing status dot, message animations (fade+slide), three-axis bubble differentiation (alignment + background tone + green left border accent), composer surface matching chat area, safe-area-aware pill positioning (`env(safe-area-inset-bottom)`).
- **Files modified**:
  - `app/Livewire/AgentToshi.php` — commit(), pre-flight checks, student class fix, edit flow fuzzy match, handleAssistantQuery LarAgent routing
  - `app/Services/WhatsAppBusinessService.php` — nullable token properties
  - `app/Services/ToshiAssistantService.php` — unchanged (legacy path preserved)
  - `app/Services/ToshiActionService.php` — unchanged
  - `app/AiAgents/ToshiAssistantAgent.php` — NEW (LarAgent agent with tools, budget, context caching, complexity routing, response caching)
  - `config/toshi.php` — added `laragent_enabled` flag
  - `config/laragent.php` — NEW (Nvidia NIM provider config)
  - `resources/views/livewire/agent-toshi.blade.php` — button bindings, Navy header, message CSS classes, composer classes, progress bar classes, modal overlay fix, remove pill inline onclick
  - `resources/views/layouts/admin/menu.blade.php` — rewritten with icon component
  - `resources/views/layouts/superadmin/menu.blade.php` — rewritten with icon component
  - `resources/views/layouts/teacher/menu.blade.php` — rewritten
  - `resources/views/layouts/student/menu.blade.php` — rewritten
  - `resources/views/layouts/accountant/menu.blade.php` — rewritten
  - `resources/views/layouts/reception/menu.blade.php` — rewritten
  - `resources/views/layouts/library/menu.blade.php` — rewritten
  - `resources/views/layouts/stock/menu.blade.php` — rewritten
  - `resources/views/layouts/alumni/menu.blade.php` — rewritten
  - `resources/views/components/icons/sidebar.blade.php` — NEW (17 SVG icons for sidebar)
  - `resources/views/admin/dashboard/dashboard.blade.php` — KPI card padding unified, icon size reduced
  - `resources/views/admin/member/index.blade.php` — dashboard card wrapper, page-header
  - `resources/views/admin/school/standardlinks/index.blade.php` — card wrapper, button styles
  - `resources/views/layouts/partials/navigation.blade.php` — removed inline script
  - `resources/views/layouts/superadmin/menu.blade.php` — removed inline script
  - `public/css/dashboard-refresh.css` — body DM Sans, Toshi CSS classes (pill/panel/modal/header/messages/composer/buttons/progress/badges), Navy header, safe-area, animations
  - `public/js/custom.js` — mobile menu toggle, sidebar accordion from moved scripts
  - `tests/Feature/Toshi/ToshiAssistantAgentTest.php` — NEW (9 tests)
  - `resources/views/layouts/superadmin-app.blade.php` — removed sidebar `<script>` (moved to custom.js)
  - `.env` — added `TOSHI_LARAGENT_ENABLED=false`
  - `composer.json` — added `maestroerror/laragent`
- **Key decisions**: Student class assignment now reads `actionData['students']` class field and maps to correct StandardLink via `$classLinkMap`. Confirm button uses `wire:click="commit"` (direct Livewire call, proven working via server logs). LarAgent gated behind `TOSHI_LARAGENT_ENABLED` (default false) — legacy path untouched. Keyword router runs before both LLM paths (shared, zero API cost). Nvidia NIM maintained as sole provider (no Groq/Claude switch). Sidebar icons extracted to Blade component for DRY maintenance across 9 role menus. Navy header replaces green per Open Design recommendation.
- **Status**: ✅ Done
- **Edge cases flagged**: `Log::` needs `\Log::` prefix (global namespace) in AgentToshi.php. LarAgent requires PHP ^8.3 (composer installs with `--ignore-platform-req=php`). `extractNamesFromFile()` doesn't deduplicate — `array_unique()` added at assignment points. `doneStudents()` strips class field via `->pluck('name')` — `actionData['students']` preserved for commitAll(). `env(safe-area-inset-*)` may not be supported on all Android browsers — `max()` fallback ensures minimum 32px.

### 2026-07-03: Three-fix pass + payroll batch E2E verification
- **Work done**: Fixed 3 code bugs (noticeboard 500 crash, StudentDetailsController unscoped latest, Feedback unscoped latest), fixed 2 fillable omissions (Salary.gross_salary, Payroll.percentage/leave/late/leave_deduction), fixed payslip view unquoted array keys (PHP 8.3 fatal), removed DOMPDF-incompatible CSS. Created DemoPayrollSeeder + PayrollBatchEndToEndTest (19 assertions). Batch preview/confirm/payslip download verified end-to-end. 34/37 feature tests pass (3 LoginRegressionTest failures are pre-existing — missing RefreshDatabase).
- **Files modified**:
  - `app/Traits/Dashboard.php` — default empty collections for noticeboard/events/booklendings
  - `app/Http/Controllers/Admin/StudentDetailsController.php` — academic-year scoped query
  - `app/Models/Feedback.php` — subquery instead of orderByDesc()->limit(1)
  - `app/Models/Salary.php` — added gross_salary to fillable
  - `app/Models/Payroll.php` — added percentage/leave/late/leave_deduction to fillable
  - `resources/views/accountant/payroll/payslip/payslip.blade.php` — quoted employee_id and designation keys, removed DOMPDF-incompatible CSS
  - `database/seeders/DemoPayrollSeeder.php` — NEW
  - `tests/Feature/PayrollBatchEndToEndTest.php` — NEW (2 tests, 19 assertions)
  - `knowledge.md` — session log
- **Key decisions**: Fillable fixes are genuine omissions (batch feature added recently, original code always used property assignment). Payslip view unquoted keys confirmed fatal on PHP 8.3. LoginRegressionTest not our bug — missing RefreshDatabase. Payroll batch is now verified end-to-end with assertions on computed gross/net, DB records, and PDF download.
- **Status**: ✅ Done
- **Edge cases flagged**: payslip view also had DOMPDF-incompatible `:not(:first-child):before` CSS (removed). LoginRegressionTest needs RefreshDatabase to run on SQLite. Admin dashboard content below KPIs is still raw/inconsistent — flagged for follow-up.

### July 3, 2026: Super Admin Full Audit + Bugfixes
- **Work done**: Systematic functional and UI audit of the Super Admin role (35 routes checked). Found 4 HIGH, 4 MEDIUM, 5 LOW issues. Fixed all HIGH and MEDIUM bugs.
- **Files modified**:
  - `routes/web.php` — fixed `compact($id)` → `compact('id')` on lines 180, 184
  - `resources/views/superadmin/dashboard.blade.php` — fixed Blade syntax errors (lines 60, 121), fixed Users KPI link (was 404), fixed Recent Schools link (was 404)
  - `resources/views/livewire/superadmin/academics/school-list.blade.php` — scoped `->latest()->first()` with `where('status', 'active')`
  - `resources/views/layouts/superadmin/menu.blade.php` — removed redundant/mislabeled "Users" nav item
- **Key decisions**: Removed sidebar "Users" item since it linked to the same URL as "Schools > All Schools" with no dedicated users page. Fixed KPI links to working routes instead of creating new routes. Scoped subscription query by `active` status rather than removing `->latest()` entirely.
- **Status**: ✅ Done (fixes applied, not yet committed)
- **Laravel Boost**: Not compatible with Laravel 10 (requires ^11.45.3). Context7 provides equivalent docs lookup.

### 2026-07-03: Replace santigarcor/laratrust with teacher_designations JSON column
- **Work done**: Removed laratrust dependency (was blocking Laravel 11 upgrade). Replaced with `teacher_designations` JSON column on `users` table. Added 3 helper methods to User model (`hasDesignation`, `addDesignation`, `removeDesignation`). Converted all 14 call sites across 10 files. Data migration backfills existing role assignments from `role_user` table. Wrote 11 unit tests covering all helper methods.
- **Files modified**: `database/migrations/2026_07_03_000001_add_teacher_designations_to_users.php` (new), `app/Models/User.php`, `app/Models/Role.php`, `app/Models/Permission.php`, `app/Traits/RegisterUser.php`, `app/Traits/AcademicProcess.php`, `app/Http/Controllers/Admin/TeacherEditController.php`, `app/Http/Controllers/Teacher/LeaveController.php`, `app/Http/Controllers/Teacher/LessonPlanController.php`, `app/Http/Controllers/Teacher/Approval/AssignmentController.php`, `app/Http/Controllers/Api/Teacher/LeaveController.php`, `app/Http/Controllers/Api/Teacher/LessonPlanController.php`, `app/Http/Controllers/Api/Teacher/MeController.php`, `app/Http/Controllers/Api/Teacher/LoginController.php`, `config/app.php`, `composer.json`, `tests/Unit/Models/TeacherDesignationTest.php` (new)
- **Files deleted**: `config/laratrust.php`, `config/laratrust_seeder.php`
- **Key decisions**: Used JSON column (not pivot table) because only 7 fixed values exist with no dynamic role creation. `saveQuietly()` in helper methods to avoid firing unnecessary model events. Role/Permission models converted to plain Eloquent (were extending Laratrust base classes) to avoid breaking any remaining references.
- **Pre-upgrade check also completed**: Analyzed 6 composer blockers for Laravel 10→11 (laratrust removed, sanctum needs ^4.0, larastan fine, openai-php/laravel fine, maestroerror/laragent fine). Scanned Carbon internals — no problematic usage found. Added impersonation boundaries and role-capability scoping to audit.md checklist.
- **Status**: ✅ Done. Next: run `composer update` to sync lock file, then proceed with Laravel 11 upgrade.

## Role 2 Audit: School Admin — Functional & UI Audit (July 2026)

### Carried-Forward Checks

**1. Impersonation Boundaries — ✅ PASS**
- Route gating: `schooladmin` middleware protects impersonation of teachers, students, librarians. `superadmin` middleware protects impersonation of School Admin.
- School Admin CAN impersonate: teachers (`/teacher/{id}/impersonate`), librarians (`/library/{id}/impersonate`), students (`/student/{id}/impersonate`)
- School Admin CANNOT impersonate: other School Admins (blocked by `is_admin()` check in generic `impersonate()`)
- Only Super Admin can impersonate School Admin (`/schooladmin/{id}/impersonate` gated by `superadmin` middleware)
- `stopImpersonate()` returns user to their original dashboard by `usergroup_id` (notable: School Admin returns to `/superadmin/dashboard`, not `/admin/dashboard`)
- ⚠️ Note: `is_admin()` function in `app/Traits/Common.php` is misleadingly named — it checks `usergroup_id == 3` (School Admin), not Super Admin. `schoolAdminimpersonate()` has an inverted condition (`if($is_admin == true)` allows) but is safely gated by `superadmin` middleware.

**2. Role-Capability Scoping — ✅ PASS**
- Primary gating: `usergroup_id` integer on `users` table + per-role middleware
- Admin routes gated by `['web', 'auth', 'schooladmin', 'privilegeconditions']` middleware stack
- `teacher_designations` JSON column (laratrust replacement) is teacher-specific only — NOT used for School Admin access
- `getRoleCapabilities()` in ToshiActionService is Toshi-specific, does not gate admin panel routes or UI
- Simple usergroup-based system is effective with no capability gaps found for School Admin

### Module 1: Student Management — ✅ PASS (1 MEDIUM issue)

**CRUD completeness:** Full CRUD with index/find/blocked-lists, create, show (full profile with tabs for relations, siblings, activity, discipline, attendance, library, fees, medical history), edit, update, delete, plus promotion rules.

**Key patterns verified:**
- `school_id` scoping: ✅ Consistently applied via `Auth::user()->school_id` across all student queries
- Academic year scoping: ✅ `SiteHelper::getAcademicYear()` used in index/find/store — scoped at StandardLink level (correct)
- Anti-pattern scan results across all Admin controllers:
  - **No** `compact($id)` / `compact($variable)` bugs found — the pattern from earlier audits (`compact($id)` instead of `compact('id')`) was NOT found in Admin controllers
  - **No** unscoped `->latest()->first()` found in Admin controllers
  - `school_id` is consistently applied in queries across all major Admin controllers
- Bulk import: ✅ Separate from Toshi onboarding — `GET /admin/import` → `ImportMemberController@importUsers` (CSV/XLSX via `UsersImport` class)

**MEDIUM issue — StudentController::destroy():**
- Line 351: Deletes `StudentAcademic` and `StudentParentLink` by `user_id` WITHOUT `school_id` scoping (low risk since user IDs are unique, but inconsistent)
- Lines 341-344, 379-382: Empty `catch(Exception $e)` blocks silently swallow errors — partial deletion failure would give user a success message while DB is inconsistent

### Module 2: Parent Management — ✅ FIXED + TESTED + COMMITTED (commit 98f5758)

**Initial audit found:**
- 🔴 CRITICAL: `dd($test)` in `index()` blocking the page
- 🔴 CRITICAL: `dd($e->getMessage())` in `store()` catch block
- 🔴 HIGH: No `school_id` scoping on 8 methods — cross-school data leak (any school admin could view/edit/delete any parent by guessing their slug)
- 🔴 HIGH: Silent empty catch blocks in `update()` and `destroy()` — production errors silently swallowed
- 🟢 LOW: `orWhere` precedence bug on line 83

**All fixed in commit 98f5758 (July 4, 2026):**
- `index()`: removed `dd($test)` + unused `$test = ParentProfile::all()`
- `store()`: replaced `dd($e->getMessage())` with `Log::info` + session flash + redirect back
- `create()`: fixed `orWhere` with grouped where closure
- `show()`, `showChildren()`, `showFeedbacks()`, `showActivityLog()`, `editList()`, `edit()`, `update()`, `destroy()`: added `->where('school_id', $schoolId)->firstOrFail()`
- `update()` and `destroy()`: replaced silent catch blocks with `Log::error` + user-facing error flash + redirect
- `destroy()`: moved `firstOrFail()` before `DB::beginTransaction()` so `ModelNotFoundException` produces a clean 404
- `lang/en/messages.php`: added `add_error_msg`, `update_error_msg`, `delete_error_msg`
- `ParentCrossSchoolIsolationTest.php`: 5 tests (4 cross-school blocks, 1 legitimate access) — all passing

**404 vs 403 design choice:** `firstOrFail()` returns 404 rather than 403 because returning 403 would confirm record existence to an unauthorized school (enumeration vector). A 404 denies knowledge of the record entirely.

### Module 3: Class/Subject Setup — ✅ PASS

- **Standards (classes):** Full CRUD at `/admin/standards` → `StandardController` — works independently of Toshi onboarding
- **StandardLinks (class sections):** Full CRUD at `/admin/standardlinks` → `StandardsLinkController` — with details views for timetable, teachers, students, attendance, events, exams, fees, class wall
- **Subjects:** Full CRUD at `/admin/subjects` → `UgSubjectController` — the newer @UG version (old SubjectController routes are commented out)
- **Classes/Streams:** `/admin/classes` → `SectionController` — a separate route for managing class sections
- **40-standardLink seeder:** The cartesian-product seed data from the seeder creates many more StandardLinks than a real school would have. The `StandardsLinkController` paginates results, so the UI should handle this gracefully. No choking hazard confirmed — the list view handles large data sets via standard Laravel pagination.

### Module 4: Reports — ⚠️ PASS with note

**Report types available:**
| Route | Type |
|---|---|
| `/admin/reports` | Main reports index view |
| `/admin/report/fees` | Fee export (CSV) |
| `/admin/report/holidays` | Holiday list import/export |
| `/admin/report/birthday/{type}` | Birthday list export |
| `/admin/report/anniversary` | Work anniversary export |
| `/admin/report/activeStudents` | Active students CSV |
| `/admin/report/exitStudents` | Exited students CSV |
| `/admin/report/suspendedStudents` | Suspended students CSV |
| `/admin/report/parents` | Parents CSV |
| `/admin/report/events` | Events report |
| `/admin/report/currentstock` | Current stock report |
| `/admin/report/monthlypurchase` | Monthly purchases report |
| `/admin/report/monthlysales` | Monthly sales report |

- **School_id scoping:** ✅ All reports scope by `school_id` via `Auth::user()->school_id`
- **Academic year scoping:** ✅ Holiday reports use `SiteHelper::getAcademicYear()`, student reports use `MemberFilter` which is academic-year-aware
- **Date-range filtering:** Student export reports offer class/status filters but not explicit date-range or term selection
- ⚠️ **No dedicated academic report cards or termly report generation** — reports are primarily CSV exports of records, not formatted academic documents. If report card generation is expected, it doesn't exist here.

### Module 5: Messaging — ❌ PARTIAL (1 MEDIUM issue)

- **`SendMessageController`** exists with two routes:
  - `POST /admin/student/sendMessageToAll` — `SendMessageController@store`
  - `POST /admin/teacher/sendMessageToAll` — `SendMessageController@storeTeacher`
- Sidebar link points to `/admin/messages` which has **NO route** — leads to 404
- No in-app messaging module, no announcements module under admin, no email sending
- Noticeboard is a separate feature (announcements visible to all, not targeted messaging)
- The messaging feature that exists is "send message to all students/teachers" — likely a bulk notification/SMS trigger

**MEDIUM issue:** The sidebar link to "Messaging" (`/admin/messages`) is a dead link. Either the route needs to be created or the sidebar should point to the existing send-message routes, or be removed.

### Module 6: Library — ❌ PARTIAL (1 HIGH issue)

**Models exist:**
- `Book`, `BookCategory`, `BookLending`, `LibraryCard` — all present in `app/Models/`
- Student library activity shown via `/admin/student/show/libraryactivity/{name}` → `StudentDetailsController@showBookLent`

**No admin library routes exist:** The sidebar "Library" link points to `/admin/library` which has **NO route** — leads to 404. There are:
- No routes for browsing books, checking out/in, managing LibraryCards, or library fines
- No admin library controller
- No admin library views

**HIGH issue:** The Library module has a data model but NO School Admin UI. The sidebar link is dead. Either the admin library module needs building or the sidebar link should be removed.

### Module 7: Health Records — ❌ ABSENT

**Definitive confirmation:** No standalone health/medical module exists for School Admin.

**What does exist:**
- 3 routes under individual student profiles (not a standalone module):
  - `GET /admin/student/show/medicalHistory/{name}` → view history
  - `GET /admin/student/add/medicalHistory/{name}` → create form
  - `POST /admin/student/add/medicalHistory/{name}` → store
- No health-specific model in `app/Models/`
- No health-specific controller
- No health-specific views directory

**School Pay webhook** notifies about "health record" events, but the underlying module for managing health records at a school level doesn't exist. The WhatsApp notification trigger fires in isolation — there's no data model backing it beyond whatever gets attached to a student's user profile.

**Sidebar "Health" link** at `/admin/health` is a dead link — **no route, 404**.

**Result:** The health records notification trigger (`health` message type in School Pay webhook) exists but the School Admin management UI for health records does not exist. The student-level medical history view is the only interface.

---

## Summary: Dead Sidebar Links

| Sidebar Label | URL | Status |
|---|---|---|
| Messaging | `/admin/messages` | ❌ 404 — no route |
| Library | `/admin/library` | ❌ 404 — no route |
| Health | `/admin/health` | ❌ 404 — no route |
| Transport | `/admin/transport` | ❌ 404 — no route |

These are rendered in `resources/views/layouts/admin/menu.blade.php` but have no corresponding routes in `routes/admin.php` or the `RouteServiceProvider`. The sidebar was likely built from a feature roadmap rather than implemented features.

---

## AUDIT & FIX SEQUENCE (July 2026)

This sequence is deliberate — do not skip ahead to step 4, 5, or 6 while step 1 or 2 is incomplete, without explicit confirmation.

### Step 1: Role 2 — School Admin Full Audit (IN PROGRESS)
Covering: Student Management (done), Parent Management (done), Class/Subject Setup, Reports, Messaging, Library, Health Records.
- Report progressively, one module at a time, actual pass/fail results only (no placeholder rows).
- Carry forward the standing anti-pattern watch (orderByDesc/latest()->first()/compact($var)) into every remaining module.
- **Module 2 all fixes committed** (`98f5758`, pushed to origin/main Jul 4 2026) — ParentController CRITICAL/HIGH, ToshiAct‌ionService bugs, cross-school isolation test.

### Step 2: Codebase-Wide Anti-Pattern Sweep
Search EVERY controller/model (not just audited roles) for:
- `orderByDesc('id')->limit(1)` — unscoped latest query
- Unscoped `->latest()->first()` — same bug class
- `compact($variable)` instead of `compact('variable')` — PHP variable-name-as-string bug
This pattern has now been found in 5+ separate files (Toshi/User.php, Feedback.php, StudentDetailsController.php, superadmin routes/web.php, SendMessageController.php) across different roles — treat as a systemic issue worth one dedicated pass rather than finding it audit-by-audit.

### Step 3: Triage and Fix Pile 1 (Bugs Already Found)
Batch by severity: HIGH first, MEDIUM bundled, LOW deferred. Confirm each fix with tests before moving to the next severity tier.

### Step 4: Laravel 10 → 11 Upgrade
All known blockers cleared (laratrust removed, Sanctum bump identified, Carbon/LarAgent confirmed compatible). Run `composer update` during a quiet week, not mid-audit. Not urgent, but ready.

### Step 5: Continue Role-by-Role Audits
Teacher, Bursar/Accountant, Nurse, Secretary/Receptionist, Parent web portal (if one exists). Can run in parallel with Step 3's fixes since audits are read-only.

### Step 6: Toshi Dual-Authorization Architecture Decision
Scope `getRoleCapabilities()` vs actual route enforcement across ALL usergroups (not just School Admin). Present Option A (unify) vs Option B (formalize the split as advisory-only) with tradeoffs. Wait until more roles are audited (Step 5) before doing this, since it needs capability data across all roles to be useful.

---

## Session Log — July 4, 2026: enforcePlanLimit() Implementation

### Summary
Built and deployed a shared plan-limit enforcement method (`ToshiActionService::enforcePlanLimit()`) that reads from CurrentPlan (the canonical runtime source), wired it into all student/teacher/admin creation paths including Toshi add*, bulk imports, and StudentController. Removed the old Subscription-based check from StudentController. Fixed `dd()` in both import controller catch blocks.

### Git
- **HEAD**: `191886f` — pushed to `origin/main`
- **Parent**: `98f5758` (Module 2 + Toshi bug fixes from previous session)

### Files Changed (committed)
- `app/Services/ToshiActionService.php` — added enforcePlanLimit(), PLAN_TYPES const, CurrentPlan import, wired into addStudent/addTeacher/addCoAdmin (+74 lines)
- `app/Http/Controllers/Admin/StudentController.php` — migrated store() from Subscription:: to ToshiActionService::enforcePlanLimit(); removed Subscription import
- `app/Http/Controllers/Admin/ImportMemberController.php` — added ToshiActionService import, upfront plan limit check, replaced dd() with Log::error + redirect
- `app/Http/Controllers/Admin/TeacherImportExportController.php` — same import limit + dd() fix; added Log import
- `tests/Feature/PlanLimitEnforcementTest.php` — new (348 lines, 12 tests)

### Tests (12 new, all passing)
1. `enforce_plan_limit_passes_when_under_limit`
2. `enforce_plan_limit_blocks_when_at_limit`
3. `enforce_plan_limit_passes_when_no_plan_configured`
4. `enforce_plan_limit_blocks_teachers_separately`
5. `enforce_plan_limit_blocks_admins_separately`
6. `toshi_add_student_blocked_when_at_plan_limit`
7. `toshi_add_teacher_blocked_when_at_plan_limit`
8. `toshi_add_coadmin_blocked_when_at_plan_limit`
9. `student_controller_store_blocked_when_at_plan_limit`
10. `student_controller_store_succeeds_when_under_limit`
11. `enforcement_uses_current_plan_not_stale_subscription` (divergence regression)
12. `enforce_plan_limit_message_is_safe_for_toshi_and_http`

### Key Decisions
- **CurrentPlan is canonical source** for plan limits (confirmed via scoping analysis — Subscription is billing audit trail, diverges when admin changes plan via CurrentPlanController)
- **Bulk import rejects whole batch upfront** before processing any rows (not per-row)
- **Messages are plain-text** with no HTML, no route links — safe for HTTP flash, Toshi, and WhatsApp
- **Divergence flagged as intentional design** — RegisterController and 5-vs-3 write imbalance remain unfixed per prior decision

### Remaining / Flagged
- RegisterController divergence (createSchool vs createSchoolSubscription) — separate data-integrity concern
- 5-vs-3 write imbalance between Subscription(5) and CurrentPlan(3) create() calls
- per-row enforcement in UsersImport and TeachersImport not yet wired (not needed with upfront batch reject, but could be added for mixed-batch scenarios)

---

## Technical Discovery (July 4, 2026): Alpine.js Method Name Interception

### Summary
Livewire v3 requires Alpine.js v3. Alpine reserves certain keywords that **silently intercept** Livewire method calls with the same name. A method named `commit()` on a Livewire component would appear to work (no error, no exception) but would never execute — the `/livewire/update` request would arrive with an empty `calls: []` array because Alpine's interceptor swallowed the method name before Livewire could dispatch it.

### Affected Keywords
Alpine.js v3 intercepts these at the component level:
- `commit` — intercepted as a store mutation keyword (the one caught here)
- `init`, `destroy` — lifecycle hooks
- `$data`, `$el`, `$refs`, `$store`, `$watch`, `$dispatch`, `$nextTick`, `$root`, `$id` — magic properties
- `data` — component data initializer

Methods `show()` and `hide()` are safe — they are not Alpine-reserved, despite being common DOM method names.

### Detection
The bug manifests as: `c.call('methodName')` resolves without error but no server-side method executes. The request body at `/livewire/update` will show `calls: []`. Compare with a working method whose name appears in the `calls` array.

### Prevention
Never name a public Livewire method with any Alpine-reserved keyword. When in doubt, prefix with a unique word (e.g., `confirmOnboarding` instead of `commit`, `togglePanel` instead of `toggle`).

### Scope
Only the Toshi component's `commit()` method was affected. All 43 other `wire:click` method names in `agent-toshi.blade.php` were audited and confirmed safe. This applies to ALL Livewire components in the app, not just Toshi — any new Livewire method named `commit`, `init`, or `data` would silently fail with the same symptom.

---

## Route Verification Lesson (July 2026)

`routes/superadmin.php` is entirely dead/commented out, but this does **not** mean superadmin routes don't exist — the actual live registrations are in `routes/web.php` lines 137–139 under a `prefix='superadmin'` group within a `Route::group(['middleware' => ['superadmin','auth'], ...])` block. This caused a false "dead route" conclusion earlier this session that was later corrected when `php artisan route:list --path=superadmin/dashboard` revealed 35 active superadmin routes.

It gets worse: `routes/admin.php` (870 lines) is also not loaded directly — it's loaded via `RouteServiceProvider::mapAdminRoutes()` which wraps it in a `prefix='admin'` group with its own middleware stack. `routes/superadmin.php` is loaded by `mapSuperadminRoutes()` the same way. Neither file's paths are relative to the project root — they're relative to the group prefix + any inline prefix in the file itself.

**Standing rule**: never conclude a route is dead by reading route FILES and inferring. Always confirm via `php artisan route:list --path=<path>` or by testing `route()` resolution directly, bypassing the file layer entirely. This codebase has route registrations split across `web.php`, `admin.php`, `superadmin.php`, `payroll.php`, `teacher.php`, and several more — file-based inspection alone is unreliable.

---

## Dual-Write Pattern: Leave/LessonPlan Approvals (July 2026)

`LeaveController` and `LessonPlanApprovalController` now write to **both** the legacy status field (`TeacherLeaveApplication.status`, LessonPlan's old approval tracking) **and** the unified `Approval` model, for backward compatibility during migration.

This is intentional but creates a drift risk: any future code that updates ONE of these without the other will cause the two to disagree (same failure mode as the `CurrentPlan`/`Subscription` divergence found earlier this session).

**Standing rule**: any new code touching leave or lesson plan approval status must update **both** fields, OR this should be scheduled for full consolidation (drop the legacy field, read/write only through `Approval`) once confidence is high enough — flagged as a post-sprint cleanup candidate, not urgent now.

`AssignmentApproval` remains fully on the legacy pattern (not migrated) — its Teacher-facing UI is Vue/API-based (`Teacher/Approval/AssignmentController`), requiring an API contract change that needs separate, explicit testing. Deferred, not forgotten.

---

## Standing Watch: "Click the Actual Button" (July 2026)

The Spatie ModelStates hydration bug (`$casts` missing `'state' => ApprovalState::class`) was initially misdiagnosed as "test methodology problem — the record was inserted via raw SQL, so state hydration failed." It was only caught as a real code bug because a second, more rigorous browser test insisted on verifying the actual button click, not just that the record appeared in the rendered table.

**Lesson**: A rendered record is not proof the corresponding action button works. The approve/reject buttons were invisible for every approval type (leave, lesson plan, homework) because `$approval->state` returned a string instead of a `Pending` object — but the record itself displayed fine. Only clicking the button would have revealed the gap.

**Standing rule for any approval-related work going forward**: verify the click, not just the display. A record rendering in a list proves the READ path works. It does not prove the WRITE path (approve, reject, transition, submit) works. Test both, or test neither.

---

## Session: July 5, 2026 — Click-Verification Pass + MoE Grading + Sidebar Fixes

### Work Done
- **Sidebar link audit**: Found and fixed 5 broken sidebar links (Health, Messaging, Library in admin sidebar; Marks, Attendance, Timetable, Exams, Homework, Students, Notices in teacher sidebar). All now point to real routes.
- **Student page rendering**: Fixed `StudentController@index` — dangling `->//count()` syntax error causing empty student count. Fixed `$birthday`/`$standard` undefined variable warnings. Cleaned `standards_link` cross-product seed data (40 records → 4).
- **Subscription import bug**: Fixed missing `use App\Models\Subscription;` in `StudentController@create` — Add Student page was crashing.
- **Approval engine verified**: Full approve button click-through confirmed working (Pending → Approved with `approved_by` + `resolved_at`).
- **Click-verified modules**: Student import, Parent create + link, Class/Subject add, Approval lifecycle.

### MoE Grading System Built
- `config/grading_uganda.php` — config-driven grade scales per level type
- `app/Helpers/GradingHelper.php` — level type detection, config seeding, grade lookup
- Auto-seeded in Toshi's `commitAll()` for new schools
- Toshi tools: `toolSetGradingScale`, `toolViewGradingScale`, `toolSeedDefaultGrading`
- Admin route: `POST /admin/grades/seed-defaults`
- **Pending confirmation**: A-Level percentage boundaries and Nursery assessment approach

### Parent Form Simplified
- Removed 8 fields (profession, qualification, annual_income, designation, organization_name, sub_occupation, alternate_no, email)
- Remaining: firstname, lastname, mobile_no, relation + student link
- `ParentAddRequest` validation simplified to match

### Subject Form — Level Removed
- Removed redundant Level dropdown — `standard_id` auto-populated from selected Class via JS
- `UgSubjectController@shipData` updated with `sectionStandardMap`

### Sidebar Bug Pattern Discovered
Multiple sidebar links across admin and teacher menus pointed to routes that either don't exist or have different URLs than expected. Both `menu.blade.php` files need auditing whenever routes change. Fix pattern: check actual route list with `php artisan route:list`, don't guess URLs.

### Files Modified
| File | Change |
|---|---|
| `app/Http/Controllers/Admin/StudentController.php` | Fixed `->//count()` syntax, undefined variables |
| `app/Http/Controllers/Admin/UgSubjectController.php` | Added `sectionStandardMap`, eager-loaded sections |
| `app/Http/Requests/ParentAddRequest.php` | Simplified validation to minimal fields |
| `resources/assets/js/components/parent/Create.vue` | Removed 8 extra fields from form + submit |
| `resources/views/admin/subject/form.blade.php` | Removed Level dropdown, auto-fill from Class |
| `resources/views/layouts/admin/menu.blade.php` | Fixed Health, Messaging, Library links |
| `resources/views/layouts/teacher/menu.blade.php` | Fixed Marks, Attendance, Timetable, Exams, Homework, Students, Notices links |
| `routes/admin.php` | Added redirect routes for /health, /messages, /library, /grades/seed-defaults |
| `config/grading_uganda.php` | NEW — MoE grading scales per level |
| `app/Helpers/GradingHelper.php` | NEW — level type detection + seeding + grade lookup |
| `app/AiAgents/ToshiAssistantAgent.php` | Added grading tools, school context property |
| `app/Livewire/AgentToshi.php` | Auto-seed grading in commitAll() |
| `resources/views/admin/subject/form.blade.php` | Removed Level dropdown, JS auto-fill from Class |

### Status
- Tests: 91 passed, 3 pre-existing failures (LoginRegressionTest — missing RefreshDatabase)
- All 4 product items built (Item 2 was already complete)
- Production: NOT deployed (changes are pending)

---

## Session: July 6, 2026 — Grading Boundaries Confirmed, Payroll Batch Engine, Fee Reconciliation

### Summary
Continued from July 5 uncommitted work. A-Level boundaries confirmed (A=6 through F=0). Nursery approach deferred to tomorrow. 4 standing items tracked — 2 fully done/tested, 2 partial pending decision.

### Decision Record
- **A-Level boundaries approved**: A=6, B=5, C=4, D=3, E=2, F=0. GradingHelper ready with config `config/grading_uganda.php`. ToolSetGradingScale tool operational.
- **Nursery approach**: Held for tomorrow's decision. Grading scale and PDF reports both blocked on this decision.

### 4 Standing Items (from July 5 Product Work)
| Item | Status | Notes |
|------|--------|-------|
| Parent form simplification | ✅ Done & tested | 8 fields removed, validation simplified, Vue form cleaned |
| Subject form (Level removed) | ✅ Done & tested | Level dropdown removed, auto-populated from Class via JS |
| Grading scale (MoE) | 🚧 Partial | A-Level boundaries now confirmed; blocked on Nursery decision |
| PDF reports | 🚧 Partial | Seeding and lookup complete; report rendering blocked on Nursery decision |

### New Work Tonight (July 6)
- **Payroll batch processing engine** — `PayrollController` now has `batchIndex()`, `batchPreview()`, `batchRun()` with `UgandaPayrollCalculator` computing PAYE, NSSF (employee), LST, and net pay per Uganda statutory rates. `PayrollTemplate` integration for salary template selection.
- **Fee reconciliation** — `FeePaymentController::unmatched()` and `matchTransaction()` for matching untagged `SchoolPayTransaction` records to `FeesCategories`. Route: `/admin/fees/payments/unmatched`.
- **Dashboard pending approvals KPI** — Approval count (Pending state) displayed as amber KPI card linked to `/admin/approvals`.
- **SchoolPayWebhookController** — adjusted for reconciliation flow.
- **User model** — updated with additional casts/relationships (+34 lines).
- **TeacherEditController** — fixes applied.
- **AcademicProcess / RegisterUser** trait adjustments.
- **Model fixes** across FeesCategories, Permission, Role, School, Task, TeacherLeaveApplication.
- **Laratrust fully removed** — config files deleted, composer.lock updated, app.php cleansed.
- **Route additions**: fee matching, health/messages/library redirects, grade seed endpoint, payroll batch routes.

### 6 Click-Verification Gaps (Still Open — Untouched Tonight)
These remain unverified — known to exist, not yet tested with actual button clicks:

| Gap | Reason |
|-----|--------|
| Payroll (batch run) | Needs accountant login — no test credentials set up |
| Health aggregate view | Doesn't exist yet — per-student only via `/admin/student/health/{id}` |
| Fee reconciliation match button | UI exists, route added — untested end-to-end |
| Messaging send | Route redirect exists — send flow untested |
| Reports export buttons | No test run — reports module pending full audit |
| ReportsController@index typo bug | `compact($variable)` bug — identified but unfixed |

### Files Modified (44 changed, +1884/−926)
| File | Change |
|---|---|
| `app/Http/Controllers/Payroll/PayrollController.php` | +277 lines — batch payroll run with Uganda statutory computations |
| `app/Http/Controllers/Admin/FeePaymentController.php` | +38 lines — unmatched transactions + match endpoint |
| `app/Http/Controllers/Admin/DashboardController.php` | +9 lines — pending approvals KPI count |
| `app/Http/Controllers/Admin/StudentController.php` | Fixes (syntax, undefined vars, Subscription import) |
| `app/Http/Controllers/Admin/TeacherEditController.php` | Fixes |
| `app/Http/Controllers/Admin/UgSubjectController.php` | sectionStandardMap, eager loading |
| `app/Http/Controllers/Api/SchoolPayWebhookController.php` | Reconciliation adjustments |
| `app/Http/Controllers/Api/Teacher/LeaveController.php` | Minor fix |
| `app/Http/Requests/ParentAddRequest.php` | Simplified validation |
| `app/AiAgents/ToshiAssistantAgent.php` | +76 lines — grading tools, school context property |
| `app/Livewire/AgentToshi.php` | Auto-seed grading in commitAll() |
| `app/Models/User.php` | +34 lines — casts, relationships |
| `app/Models/FeesCategories.php` | Fixes |
| `app/Models/Permission.php`, `Role.php`, `School.php`, `Task.php`, `TeacherLeaveApplication.php` | Model fixes |
| `app/Traits/AcademicProcess.php`, `RegisterUser.php` | Adjustments |
| `config/grading_uganda.php` | NEW — MoE grading scales per level |
| `app/Helpers/GradingHelper.php` | NEW — level detection + seeding + lookup |
| `config/app.php` | Laratrust removal cleanup |
| `config/laratrust.php` | DELETED |
| `config/laratrust_seeder.php` | DELETED |
| `composer.json` / `composer.lock` | Laratrust removed, dependencies updated |
| `resources/assets/js/components/parent/Create.vue` | Simplified (removed 8 fields) |
| `resources/views/admin/subject/form.blade.php` | Level dropdown removed, JS auto-fill |
| `resources/views/admin/dashboard/dashboard.blade.php` | Pending approvals KPI card |
| `resources/views/layouts/admin/menu.blade.php` | Fixed Health/Messaging/Library links |
| `resources/views/layouts/teacher/menu.blade.php` | Fixed sidebar links |
| `routes/admin.php` | Fee matching, sidebar redirects, grade seed |
| `routes/payroll.php` | Batch payroll routes |
| `audit.md` | Impersonation + role-capability notes |
| `AGENTS.md` | Substantial update (+300 lines) |
| `knowledge.md` | This session log |
| `tests/Feature/Toshi/ToshiAssistantAgentTest.php` | Extended |

### Key Decisions
- **A-Level standard**: A=6, B=5, C=4, D=3, E=2, F=0 — matches Uganda MoE NCDC convention
- **Nursery deferred**: Assessment approach needs separate thinking (competency-based vs score-based)
- **Laratrust fully removed**: Config files deleted as dead weight; no runtime regression found
- **Payroll calculator**: Statutory computations centralized in `UgandaPayrollCalculator` service class
- **Fee reconciliation**: Direct match via `matched_fee_category_id` on `schoolpay_transactions` — no intermediate matching table needed

### Remaining / Flagged
- Nursery grading approach will unblock both grading scale completeness and PDF report generation
- Payroll batch run needs accountant login credentials to click-verify
- Health aggregate view needs a new page + route
- ReportsController@index `compact($variable)` bug remains unfixed
- 3 pre-existing test failures (LoginRegressionTest) — unrelated
- Production not yet deployed (all changes pending)

