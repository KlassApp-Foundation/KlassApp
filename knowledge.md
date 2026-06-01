# KlassApp Knowledge Base

> **Purpose**: Canonical project knowledge base — read at the start of every session.
> **Maintainers**: Append session summaries to the Session Log at the bottom after each work session.

---

## 1. Project Overview

| Field | Value |
|---|---|
| **Name** | KlassApp (formerly Gegok12) |
| **Description** | WhatsApp-based school-parent communication platform for Uganda schools |
| **Stack** | Laravel 10 + Blade + Tailwind 1.4 + Vue 2 + MySQL |
| **PHP** | 8.1+ (production Docker uses php:8.3-fpm) |
| **Hosting** | VPS (Hetzner) — domain via Cloudflare (ugasch.com) |
| **Laravel Vapor** | Configured for `gegok12` (id: 10390) — production + staging on AWS Lambda |
| **Timezone** | `Asia/Kolkata` (legacy — set in .env, not Uganda time) |
| **Auth** | Laravel Sanctum (API) + session-based (web) |
| **Roles** | Multi-role via Laratrust (superadmin, schooladmin, teacher, parent, student, librarian, accountant, etc.) |
| **Logo** | SVG in `public/images/` — `klassapp-logo.svg`, `klassapp-logo-primary.svg`, `klassapp-logo-dark.svg` |
| **Context file** | `AGENTS.md` — project context, content decisions, session memory |
| **Working branch** | `whatsapp` |

---

## 2. Infrastructure

### 2.1 Docker (Local Dev)

- **`docker-compose.yml`** — Laravel app (PHP-FPM) + MySQL 8.0 + Nginx
- Run: `docker compose up -d`
- App: `http://localhost:8080`

### 2.2 Docker (Production)

- **`docker-compose.prod.yml`** — Full stack for Portainer deployment
- Services: `app` (Laravel), `nginx`, `mysql:8.0`, `redis:7-alpine`, `postgres:15-alpine` (Evolution), `evolution` (WhatsApp API)
- Requires env vars: `APP_KEY`, `EVOLUTION_API_KEY`, `WHATSAPP_HMAC_SECRET`

### 2.3 WhatsApp Stack

- **`whatsapp/docker-compose.yml`** — Evolution API + n8n + Postgres + Redis
- Ports: Evolution on `8081`, n8n on `5678`
- Evolution instance name: `klassapp`
- Webhook URL (dev): `http://host.docker.internal:8000/api/whatsapp/inbound`
- n8n workflows: `whatsapp/docker-compose.yml` mounts `whatsapp/n8n-workflows/`

### 2.4 Nginx

- **`nginx/default.conf`** — Local dev (port 80 → app:9000). Production config is commented out — SSL via Let's Encrypt.

### 2.5 CI/CD

- **`.github/workflows/klassapp-ci.yml`** — **DISABLED** (fully commented out). No active CI pipeline.
- **`vapor.yml`** — Laravel Vapor deployment (gegok12, id: 10390). Production + staging environments on AWS Lambda.

---

## 3. Key Dependencies

### 3.1 Composer (PHP)

| Package | Purpose |
|---|---|
| `laravel/framework ^10.0` | Core framework |
| `livewire/livewire ^3.4` | Livewire components |
| `santigarcor/laratrust 7.x` | Roles & permissions |
| `spatie/laravel-medialibrary v10.x` | Media/file management |
| `spatie/laravel-activitylog 4.7.3` | Activity logging |
| `kreait/laravel-firebase ^5.7` | Firebase push notifications |
| `maatwebsite/excel 3.1.x` | Excel import/export |
| `barryvdh/laravel-dompdf` | PDF generation |
| `laravel/sanctum ^3.2` | API tokens |
| `pusher/pusher-php-server 7.1.0-beta` | Real-time events |
| `predis/predis v3.0.0-alpha1` | Redis client |
| `league/flysystem-aws-s3-v3 ^3.24` | S3 storage |
| `guzzlehttp/guzzle ^7.0` | HTTP client |
| `laravel/scout *` | Full-text search (Algolia) |

### 3.2 NPM (Frontend)

| Package | Purpose |
|---|---|
| `vue ^2.6.12` | Vue 2 SPA components |
| `tailwindcss 1.4.6` | Utility CSS (v1 — purge disabled) |
| `laravel-mix ^4.1.4` | Asset compilation |
| `chart.js ^2.9.3` | Charts |
| `highcharts ^8.2.0` | Advanced charts |
| `@fullcalendar/* ^5.5` | Calendar UI |
| `pusher-js ^6.0.3` | WebSockets (realtime) |

---

## 4. Routing Structure (19 route files)

| File | Routes | Prefix/Middleware |
|---|---|---|
| `routes/web.php` | 78 | Public + auth routes, landing page, premium school pages |
| `routes/admin.php` | 523 | Admin dashboard (school admin) |
| `routes/teacher.php` | 249 | Teacher routes |
| `routes/teacherapi.php` | 121 | Teacher API (included in api.php) |
| `routes/api.php` | 105 | Parent API + v2 Sanctum-authed API |
| `routes/student.php` | 77 | Student portal |
| `routes/receptionist.php` | 97 | Receptionist dashboard |
| `routes/accountant.php` | 50 | Accountant dashboard |
| `routes/payroll.php` | 51 | Payroll management |
| `routes/librarian.php` | 39 | Library management |
| `routes/static.php` | 35 | Static/usecase pages |
| `routes/setting.php` | 22 | Settings |
| `routes/superadmin.php` | 2 | Superadmin dashboard |
| `routes/addon.php` | 2 | Addon installer |
| `routes/stock.php` | 0 | (empty) |
| `routes/alumni.php` | 0 | (empty) |
| `routes/inventory.php` | 0 | (empty) |

### 4.1 Key Public Routes

```
GET  /                                  → WelcomeController
GET  /landing                           → landing.blade.php (marketing page)
GET  /schools/{slug}                    → SchoolPageController@show (premium pages)
POST /api/whatsapp/inbound              → WhatsAppController@handleInbound (no HMAC)
```

### 4.2 Admin WhatsApp Route

```
GET  /admin/whatsapp/phone              → UserProfileController@phoneLink
POST /admin/whatsapp/phone              → UserProfileController@linkWhatsApp
```

---

## 5. WhatsApp Integration

### 5.1 Architecture

```
[WhatsApp User] ↔ Evolution API ↔ [Laravel Webhook / Outbound Service]
                                       ↕
                              WhatsAppService (HTTP transport)
                                       ↕
                           OutboundWhatsAppService (business logic)
                                       ↕
                           MessageDeliveryLog (DB tracking)
                           WhatsAppPendingNotification (queue)
```

### 5.2 Services

| File | Purpose |
|---|---|
| `app/Services/WhatsAppService.php` | HTTP transport layer — `sendText()`, `sendTemplate()`, `sendList()`, `sendMedia()` via Evolution API. Logs to `message_delivery_log`. |
| `app/Services/OutboundWhatsAppService.php` | Business logic — `notifyGradesPublished()`, `notifyFeeReminder()`, `notifyComprehensiveGrades()`, `queueOrSend()`, `flushPending()`, `getParentPhones()`. Depends on `WhatsAppService`. |
| `app/Helpers/WhatsAppPhoneHelper.php` | Phone utilities — `normalise()`, `validate()` (regex: `\+256(7[0578]\d{7})`), `formatMessage()`. |

### 5.3 Interactive List Menu

- **Controller**: `WhatsAppController@sendMenu()` — sends greeting text + interactive List Message via `sendList()`
- **Menu builder**: `WhatsAppController@buildMenuSections()` — generates role-specific sections/rows based on `users.usergroup_id`
- **Roles routed**:
  - `usergroup_id 3` → admin menu
  - `usergroup_id 5` → teacher menu
  - `usergroup_id 6` → student menu
  - `usergroup_id 7` → parent menu
  - `usergroup_id 10` → receptionist menu
  - `usergroup_id 11` → accountant menu
- **Dual-role handling**: Any staff role (admin, teacher, accountant, receptionist) who also has children gets parent menu options mixed in
- **`whatsapp_users.user_type` column eliminated** — routing is entirely by `users.usergroup_id`
- **List Message format**: Evolution API interactive list — sections with title, rows, description, footer, button text

### 5.4 Inbound Webhook

- **Endpoint**: `POST /api/whatsapp/inbound` (outside HMAC middleware)
- **Controller**: `WhatsAppController@handleInbound`
- **Validation**: `StoreWhatsAppWebhookRequest` — validates `event=messages.upsert`, `remoteJid`, message content, payload size (1MB), phone format
- **Guards**: Ignores group messages (`@g.us`), own messages (`fromMe=true`), non-message events
- **Keywords**: `menu`, `grades`, `fees`, `attendance`, `events`, `timetable`, `optin`, `optout`
- **Window update**: On every inbound, `whatsapp_users.last_inbound_at` is updated (24hr service window tracking)
- **Queue flush**: After routing, `flushPending()` is called — all queued notifications for the parent send free inside the open 24hr window

### 5.5 24-Hour Service Window

- **Mechanism**: `WhatsAppUser.last_inbound_at` column — timestamped on every inbound message
- **Check**: `WhatsAppService::isWithinServiceWindow($phone)` — returns true if last inbound was < 24hrs ago
- **Safe send**: `sendTextSafe()` — sends directly if window open, falls back to template message if closed
- **Free vs paid**: Service messages inside 24hr window = FREE. Cold proactive templates outside window = $0.004/delivered (Meta's per-template pricing, July 2025)
- **Meta pricing (July 2025)**: Utility templates $0.004/delivered, Marketing $0.0225, Authentication $0.004. Service messages within 24hr window = FREE.

### 5.6 Cost-Optimized Notification Queue

- **Table**: `whatsapp_pending_notifications`
- **Model**: `WhatsAppPendingNotification` — fields: `id`, `school_id`, `user_id`, `phone`, `message_type`, `message_data` (JSON), `status` (pending/sent/expired), `scheduled_at`, `sent_at`, `created_at`, `updated_at`
- **Flow**:
  1. `queueOrSend()` — if window is open, sends immediately (free). If closed, queues for later.
  2. `flushPending()` — called from `handleInbound()`. When a parent messages in, their window opens — all their queued notifications send free.
  3. `sendExpiredQueue()` — cold-sends queued items past deadline ($0.004 each)
  4. `flushAllOpenWindows()` — batch process that checks all open windows and flushes matching queues
- **Refactored methods** now use `queueOrSend()`: `notifyGradesPublished()`, `notifyFeeReminder()`, `notifyComprehensiveGrades()`
- **Command**: `SendWhatsAppPendingNotifications` — `whatsapp:send-pending` with `--flush-open` flag
- **Cron**: `whatsapp:send-pending --flush-open` every 15 minutes

### 5.7 Delivery Webhook & Failure Handling

- **Endpoint**: `POST /api/whatsapp/delivery` — receives status callbacks from Evolution API
- **Controller**: `WhatsAppController@deliveryWebhook()`
- **Failure escalation**: `handleDeliveryFailure()` — counts consecutive failures per phone in last hour, triggers high-severity alert `⚠️ WhatsApp delivery failure: {phone} ({count} failures in 1hr)` at 3+ failures
- **Error codes**: `error_code` field on delivery log
- **Webhook validation**: HMAC-signed payloads, Evolution API key verification

### 5.8 Multi-Child Parent Flow

- `sendGrades()`, `sendFees()`, `sendAttendance()` — rewritten to iterate ALL children of the parent, sending one message per child
- Each message personalised per child (name, class, scores/balance/days)

### 5.9 Admin Delivery Dashboard

- **Route**: `GET /admin/whatsapp/dashboard`
- **Controller**: `WhatsAppDashboardController@index`
- **KPIs**: Total sent, delivery rate, failure rate, linked WhatsApp users
- **Trend**: Daily message volume bar chart (24h/7d/30d/90d period filter)
- **Flow breakdown**: Messages by flow type (grades/fees/attendance/events/timetable)
- **Activity**: Recent message log with status, phone, timestamp
- **Sidebar**: WhatsApp Dashboard + WhatsApp Phone entries in admin menu

### 5.10 Outbound Hooks

| Component | File | Purpose |
|---|---|---|
| **Event** | `app/Events/GradesPublished.php` | Dispatched when marks published (`$student`, `$examId`) |
| **Listener** | `app/Listeners/SendGradesToWhatsApp.php` | Calls `OutboundWhatsAppService::notifyGradesPublished()` |
| **Command** | `app/Console/Commands/SendFeeReminders.php` | `whatsapp:send-fee-reminders` with `--type=reminder\|overdue`, `--school-id`, `--dry-run` |
| **Command** | `app/Console/Commands/SendWhatsAppPendingNotifications.php` | `whatsapp:send-pending` with `--flush-open` |
| **Schedule** | `app/Console/Kernel.php` | Weekly fee reminders (Mondays), daily overdue, pending queue flusher every 15min — all `withoutOverlapping()` |

### 5.11 Models

| Model | Table | Key Fields |
|---|---|---|
| `WhatsAppUser` | `whatsapp_users` | `phone`, `user_id`, `opted_out`, `unsubscribed_at`, `last_inbound_at` |
| `MessageDeliveryLog` | `message_delivery_log` | `whatsapp_message_id`, `phone`, `category`, `status`, `direction`, `flow_type`, `error_code` |
| `WhatsAppPendingNotification` | `whatsapp_pending_notifications` | `school_id`, `user_id`, `phone`, `message_type`, `message_data` (JSON), `status` (pending/sent/expired), `scheduled_at`, `sent_at` |
| *(Note: `$timestamps = false` on MessageDeliveryLog — migration has no `created_at`/`updated_at`)* |

### 5.12 WhatsApp Business Config

| Key | Value |
|---|---|
| **Business Number** | `+256767538805` (in `config/services.php` + `.env`) |
| **Evolution URL** | `http://localhost:8081` (configurable via `EVOLUTION_API_URL`) |
| **Evolution API Key** | Set in `.env` |
| **HMAC Secret** | Set in `.env` — used by `WhatsAppHmac` middleware (not applied to inbound webhook) |
| **Send Delay** | 1200ms (rate limiting between messages) |

### 5.13 Phone Format

- Uganda mobile: `+256 7[0578] XXX XXX` (12 chars with `+`, 9 digits after +256)
- `WhatsAppPhoneHelper::normalise()`: strips non-digits, ensures E.164
- `WhatsAppPhoneHelper::validate()`: regex = `/^\+256(7[0578]\d{7})$/`
- wa.me links: `str_replace('+', '', $phone)` to strip `+` prefix

### 5.14 Key Files

| File | Purpose |
|---|---|
| `app/Http/Controllers/Api/WhatsAppController.php` | Main controller — `handleInbound`, `sendMenu`, `buildMenuSections`, `sendGrades`, `sendFees`, `sendAttendance`, `deliveryWebhook`, `handleDeliveryFailure` |
| `app/Services/WhatsAppService.php` | HTTP transport — `sendText`, `sendTemplate`, `sendList`, `sendTextSafe`, `isWithinServiceWindow` |
| `app/Services/OutboundWhatsAppService.php` | Business logic — `queueOrSend`, `queueNotification`, `flushPending`, `flushAllOpenWindows`, `sendExpiredQueue`, notification methods |
| `app/Models/WhatsAppUser.php` | WhatsApp user model — `last_inbound_at` |
| `app/Models/WhatsAppPendingNotification.php` | Notification queue model |
| `app/Http/Controllers/Admin/WhatsAppDashboardController.php` | Delivery dashboard |
| `resources/views/admin/whatsapp/dashboard.blade.php` | Dashboard view |
| `app/Console/Commands/SendWhatsAppPendingNotifications.php` | Queue processor command |
| `database/migrations/2026_05_29_000003_add_last_inbound_at_to_whatsapp_users.php` | Window tracking migration |
| `database/migrations/2026_05_29_000004_create_whatsapp_pending_notifications_table.php` | Queue table migration |
| `database/migrations/2026_05_29_000002_drop_user_type_from_whatsapp_users.php` | Drop redundant user_type column |

---

## 6. Documentation

### 6.1 Docs Directory

The `docs/` folder contains two docsify sites:

| Site | Path | Port | Audience |
|---|---|---|---|
| Community | `docs/community/` | 4000 | Clients & investors |
| Dev | `docs/dev/` | 4001 | Internal developers/ops |

**Serve locally**:
```bash
cd docs/community && npx docsify-cli serve . --port 4000
cd docs/dev && npx docsify-cli serve . --port 4001
```

### 6.2 Community Docs

**Files**: `README.md`, `for-schools.md`, `for-parents.md`, `ecosystem.md`, `roadmap.md`, `faq.md`, `_sidebar.md`, `index.html`, `klassapp-logo.svg`

**Key positioning**:
- "KlassApp: The school in every parent's pocket."
- "Two products, one platform." — schools get management, parents get WhatsApp
- Verbatim: "KlassApp doesn't replace a school's existing system — it adds a parent-facing communication layer on top."
- "Built in Uganda."
- ERP integration is the core differentiator
- EMIS/LIN integration is the key moat
- Geography rule: "East Africa" for market claims, "Uganda" only for Uganda-specific facts
- Meta 24-hour window is NOT exposed publicly (rephrased as "cost-effective")
- Revenue model removed from all public docs

### 6.3 Dev Docs

**Files** in `docs/dev/`:

| File | Content |
|---|---|
| `README.md` | Architecture overview |
| `interactive-menu.md` | WhatsApp role-based List Message menu system |
| `service-layer.md` | WhatsAppService + OutboundWhatsAppService |
| `models.md` | WhatsApp models (WhatsAppUser, MessageDeliveryLog, etc.) |
| `api-reference.md` | API endpoints |
| `admin-dashboard.md` | Delivery dashboard |
| `cost-optimization.md` | 24hr window, notification queue |
| `emis-lin-onboarding.md` | EMIS/LIN integration paths |
| `schoolpay-integration.md` | SchoolPay API integration spec (designed May 2026) |
| `ai-agent-layer.md` | AI Agent Layer spec (designed May 2026) |
| `setup.md` | Dev setup |
| `testing.md` | Test guide |

### 6.4 AGENTS.md

`AGENTS.md` at repo root is the project context file. It captures content decisions, brand assets, roadmap, session history, and positioning rules for the community docs. Maintained alongside `knowledge.md`.

---

## 7. EMIS / LIN Integration Strategy

### 6.1 What is EMIS/LIN

The **Education Management Information System (EMIS)** is Uganda's national database for all schools, students, and staff — managed by the Ministry of Education. **LIN (Learner Identification Number)** is a unique 12-digit identifier assigned to every student in Uganda. **NIN (National Identification Number)** links parents/guardians to their children via the national ID system.

### 6.2 Onboarding Paths (ranked by speed to value)

| Path | Approach | Timeline | Effort |
|---|---|---|---|
| **Path 2** (NOW) | Bulk CSV import via admin panel | Days | Low |
| **Path 3** (NEXT) | Parent self-registration via LIN + NIN verification | Weeks | Medium |
| **Path 1** (LONG-TERM) | Ministry partnership / API integration | Months | High |

### 6.3 Path 2 — CSV Bulk Import (Schema Ready)

- **Purpose**: Admin uploads a CSV with student LIN, parent phone, parent NIN to mass-onboard parents
- **Trigger**: Admin panel → "Import via EMIS"
- **Schema** (`lin_registrations` / `emiser_data` or similar):
  - `student_lin` (12-digit LIN)
  - `parent_phone` (Uganda mobile)
  - `parent_nin_hash` (SHA-256 of NIN — never store plaintext)
  - `school_id` (FK to schools)
  - `status` (pending/verified/imported)
- **Flow**: Validate CSV format → batch insert → match students by LIN in student_records (if exists) → create/link WhatsApp user → send welcome via WhatsApp
- **Validation**:
  - LIN: 12-digit numeric, must exist in `student_records.lin` (or flagged as external)
  - Phone: `+2567[0578]\d{7}`
  - NIN: SHA-256 hashed on client side or immediately at server entry

### 6.4 Path 3 — Self-Registration via LIN + NIN (Just Decided)

- **Purpose**: Parent texts their child's LIN to the WhatsApp bot → bot looks up the student → asks for parent NIN → verifies against national ID → links parent's WhatsApp number to the student
- **Flow**:
  1. Parent sends LIN (12-digit number) via WhatsApp
  2. Bot validates LIN format, looks up in `student_records.lin`
  3. If found, bot asks for parent NIN
  4. Parent sends NIN
  5. Bot hashes NIN → SHA-256 → compares against stored hash in `student_records.parent_nin_hash`
  6. If match → parent's WhatsApp phone is linked to all children with that NIN hash
  7. Bot sends confirmation + main menu
- **Key decisions**:
  - NIN is SHA-256 hashed before storage — never stored as plaintext
  - No third-party NIN API call at Path 3 stage (Path 1 would add gov API verification)
  - Student matched via `student_records.lin` field — if column doesn't exist yet, it needs a migration
  - Parent linked to ALL children sharing same `parent_nin_hash` (multi-child scenario)
  - WhatsApp keywords: new reserved keywords `lin`, `register` for the flow
  - Fallback: if LIN not found in system, bot responds "LIN not recognized. Contact your school administrator or try again."

### 6.5 Path 1 — Ministry Partnership (LONG-TERM)

- **Goal**: Direct API integration with Ministry of Education's EMIS database
- **Requires**: MoU with Ministry, data protection compliance, API credentials
- **Unlocks**: Real-time LIN/NIN verification, automatic student roster sync, official parent contact data
- **Status**: ⏸️ Not started — requires stakeholder engagement

### 6.6 Database Changes Needed

- `student_records.lin` (or equivalent) — 12-char VARCHAR, unique index
- `student_records.parent_nin_hash` — CHAR(64), SHA-256 hash
- `lin_registrations` table — tracks import/registration history
- Migration for existing students: `lin` and `parent_nin_hash` columns nullable initially

### 6.7 NIN Privacy

- NIN is **never** stored in plaintext — only SHA-256 hash
- Hash is generated client-side when possible, or immediately hashed at server entry
- Plaintext NIN is discarded after hash generation
- Future audit may require zero-knowledge proofs or salted hashes

---

## 8. SchoolPay Integration

**Spec**: `docs/dev/schoolpay-integration.md` (designed May 2026)

SchoolPay (Fincom Technologies Ltd) is a real payment aggregator licensed by Bank of Uganda. It processes school fees via MTN MoMo, Airtel Money, and 10+ banks. 20K+ schools, 5M+ parents.

**Key facts**:
- API has webhook (single attempt, no retry) + sync polling endpoints
- Students identified by 10-digit payment code
- SchoolPay ERP pricing is not public
- NO existing integration in the codebase — it's a Phase 5 roadmap item
- Community docs correctly frame this as "LIN & Fee Management" (fee notifications via WhatsApp, payment handled externally by school's existing channels)

**Integration spec**: 4 phases, ~7-11 days effort. Webhook for real-time payment updates, sync for reconciliation.

---

## 9. AI Agent Layer (Premium)

**Spec**: `docs/dev/ai-agent-layer.md` (designed May 2026)

A staff-only premium intelligence layer built on top of the existing system.

**Design decisions**:
- Parents keep existing deterministic WhatsApp menus (no AI needed)
- Agent is **web primary, WhatsApp secondary** — web for heavy work, WhatsApp for quick queries/notifications
- **Marksheet ingestion** (vision LLM reads scanned/excel marksheets, extracts names+scores, writes to database) is the killer feature
- **Report enrichment** — AI doesn't generate reports (they auto-generate), but appends edge cases: discipline notes, achievements (sports medals), exceptional comments
- **Performance analysis** — natural language queries on trends, anomalies, comparisons
- **Voice pipeline** — WhatsApp voice messages transcribed via Whisper, processed same as text
- **Premium gating** — all AI features are premium-tier only
- Cost model: ~$1-5/month per school in LLM costs, supporting $50-200/month premium pricing

**4-phase roadmap**: Foundation (web+text) → Vision+Voice → Alerts+Proactive → Parent Voice Access

---

## 10. Premium School Pages

- **5 templates** in `resources/views/schools/templates/template-{1..5}.blade.php`
- `_shared.blade.php` provides `$whatsappLink` helper (auto-normalises school phone → wa.me link)
- WhatsApp buttons: hero CTA + floating widget + clickable contact phone
- School phone in templates comes from `School` model — auto-normalised (strips non-digits, prepends 256 if leading zero)

---

## 11. Database

### 11.1 Migration Count

129 migration files in `database/migrations/`

### 11.2 Key Tables (WhatsApp)

| Migration | Table |
|---|---|
| `2026_05_16_000001_create_whatsapp_users_table.php` | `whatsapp_users` |
| `2026_05_16_000002_create_message_delivery_log_table.php` | `message_delivery_log` |
| `2026_05_27_000001_create_premium_pages_table.php` | `premium_pages` |

### 11.3 Test DB

- `phpunit.xml` uses SQLite in-memory for tests
- WhatsApp env vars configured in `phpunit.xml` for testing

---

## 12. Testing

- **Framework**: PHPUnit 10
- **Suites**: Unit (`tests/Unit`), Feature (`tests/Feature`)
- **WhatsApp tests**:
  - `tests/Feature/WhatsApp/WebhookValidationTest.php` — 7 tests (FormRequest rules)
  - `tests/Feature/WhatsApp/OutboundNotificationTest.php` — 4 tests (phone helpers)
- **Factories**: `WhatsAppUserFactory`, `MessageDeliveryLogFactory`
- **Run**: `php artisan test tests/Feature/WhatsApp/`
- **Note**: 3 tests SKIP when DB-dependent (need `whatsapp_users` / pivot tables). Run with `--env=testing` and migrated DB to enable.

---

## 13. Known Issues & Edge Cases

### 13.1 Phone Validation (premium templates)
School phone numbers in non-Uganda format (e.g. `+254...` Kenya) pass through without validation in `_shared.blade.php`. Should add Uganda-only validation in `UserProfileController@linkWhatsApp` before save:
```php
preg_match('/^2567[0578]\d{7}$/', $cleaned)
```

### 13.2 CI Pipeline Disabled
`.github/workflows/klassapp-ci.yml` is fully commented out. No CI runs.

### 13.3 `.env.example` Outdated
Missing WhatsApp env vars (`EVOLUTION_API_KEY`, `WHATSAPP_HMAC_SECRET`, `WHATSAPP_BUSINESS_NUMBER`, etc.) — these exist only in `.env` (git-ignored).

### 13.4 Timezone
`.env` has `TIMEZONE=Asia/Kolkata` — Uganda is `Africa/Kampala` (UTC+3, no DST). May cause date mismatches.

### 13.5 Tailwind v1
`tailwind.config.js` uses Tailwind 1.4.6 with `purge: false` — all classes included. Upgrade would reduce CSS size significantly.

---

## 14. Key Files & Locations

### App
| Area | Location |
|---|---|
| Controllers (Web) | `app/Http/Controllers/` |
| Controllers (API) | `app/Http/Controllers/Api/` |
| Controllers (Admin) | `app/Http/Controllers/Admin/` |
| Models | `app/Models/` (111 models) |
| Services | `app/Services/` |
| Middleware | `app/Http/Middleware/` |
| Form Requests | `app/Http/Requests/` |
| Events | `app/Events/` |
| Listeners | `app/Listeners/` |
| Console Commands | `app/Console/Commands/` |
| Helpers | `app/Helpers/` |
| Nova Components | `nova-components/` |

### Frontend
| Area | Location |
|---|---|
| Views | `resources/views/` |
| Landing Page | `resources/views/landing.blade.php` |
| Admin Views | `resources/views/admin/` |
| Premium Templates | `resources/views/schools/templates/` |
| Admin Menu | `resources/views/layouts/admin/menu.blade.php` |
| Public CSS | `public/css/landing.css` |
| SVG Logos | `public/images/klassapp-logo*.svg` |
| Nova Logo | `resources/views/vendor/nova/partials/logo.blade.php` |

---

## 15. Session Log

Append summaries here after each work session. Format:

```markdown
### YYYY-MM-DD: Brief title
- **Work done**: Summary of changes
- **Files modified**: List
- **Key decisions**: List
- **Status**: ✅ Done / 🚧 In progress / ⏸️ Blocked
```

### 2026-05-28: WhatsApp inbound validation, outbound hooks, feature tests
- **Work done**: Built #4 (inbound webhook FormRequest), #5 (OutboundWhatsAppService + GradesPublished event + SendFeeReminders command), #6 (WebhookValidationTest + OutboundNotificationTest + factories)
- **Files modified**: WhatsAppController (FormRequest injection), UserProfileController (phone-linking), WhatsAppPhoneHelper (regex fix), MessageDeliveryLog ($timestamps=false), School (premiumPage relationship), EventServiceProvider, Console/Kernel, routes, views (landing, premium templates, admin menu, phone-link), config/services.php, phpunit.xml
- **Key decisions**: FormRequest validation over middleware (webhook outside HMAC), separate OutboundWhatsAppService for business logic, tests use Laravel validator directly (avoids DB dependency)
- **Status**: ✅ Done
- **Edge case flagged**: Non-Uganda phone numbers bypass validation in premium templates

### 2026-05-28: Created knowledge.md + klassapp-knowledge skill
- **Work done**: Created `knowledge.md` as canonical project knowledge base. Created `klassapp-knowledge` skill (`~/.agents/skills/klassapp-knowledge/SKILL.md`) that enforces reading knowledge.md at session start and appending session summaries on exit. Verified project structure, CI/CD, Docker, WhatsApp stack, routes, and services are all documented.
- **Files modified**: knowledge.md (NEW), .agents/skills/klassapp-knowledge/SKILL.md (NEW), ~/.agents/skills/klassapp-knowledge/SKILL.md (NEW, global copy), .sisyphus/memory/session-context.md (updated header)
- **Key decisions**: Skill at global `~/.agents/skills/` for auto-discovery by OpenCode. Project copy kept in `.agents/skills/` for version control. knowledge.md is the single source of truth — session-context.md is secondary cache.
- **Status**: ✅ Done
- **Edge cases flagged**: Skill won't appear in available list until next session (cached at session start). CI pipeline fully commented out (no CI runs).

### 2026-05-28: Landing page WhatsApp CTA + premium template WhatsApp buttons
- **Work done**: Added wa.me links to landing page (floating widget, hero, nav, CTA section, footer) and all 5 premium templates (hero button + floating widget + clickable contact phones). Created `_shared.blade.php` with `$whatsappLink` helper. Updated business number to `+256767538805`.
- **Files modified**: landing.blade.php, template-{1..5}.blade.php, _shared.blade.php, config/services.php, .env
- **Key decisions**: wa.me links use `str_replace('+', '', config(...))` to strip `+` prefix. Premium templates use school phone (not business number).
- **Status**: ✅ Done

### 2026-05-28: Admin phone-linking UI
- **Work done**: Created phone-linking page at `/admin/whatsapp/phone` with linked/unlinked states. Added sidebar menu item.
- **Files modified**: UserProfileController (phoneLink, linkWhatsApp), admin menu blade, new phone-link.blade.php, routes/admin.php
- **Key decisions**: Placed under Settings in admin sidebar. Phone saved to existing `users.whatsapp_phone` column.
- **Status**: ✅ Done

### 2026-05-29: Phase 2 WhatsApp features + EMIS/LIN strategy
- **Work done**: Built WhatsApp interactive List Menu (sendMenu, buildMenuSections with 6 role-specific menus, dual-role handling for staff+parents). Added 24-hour service window tracking (last_inbound_at, isWithinServiceWindow, sendTextSafe). Built cost-optimized pending notification queue (whatsapp_pending_notifications table, queueOrSend/flushPending/sendExpiredQueue). Added delivery webhook failure escalation (3+ failures in 1hr triggers alert). Implemented multi-child parent flow (sendGrades/Fees/Attendance per child). Built admin delivery dashboard (KPI cards, daily volume trend chart, flow-type breakdown, recent activity log). Defined EMIS/LIN integration strategy — Path 2 (CSV bulk import) schema-ready, Path 3 (parent self-registration via LIN+NIN) decided and designed, Path 1 (ministry API) long-term.
- **Files modified**: WhatsAppController (list menu, inbound routing, keyword handling, delivery webhook, multi-child methods), WhatsAppService (sendList, isWithinServiceWindow, sendTextSafe), OutboundWhatsAppService (queueOrSend, queueNotification, flushPending, flushAllOpenWindows, sendExpiredQueue, refactored notification methods), WhatsAppUser (last_inbound_at scope), WhatsAppPendingNotification (new model), WhatsAppDashboardController (new), dashboard.blade.php (new), SendWhatsAppPendingNotifications command, Console/Kernel (queue flush cron), admin menu blade, 3 new migrations (last_inbound_at, pending_notifications table, drop user_type), routes/api.php, knowledge.md (expanded WhatsApp §5, added EMIS/LIN §6, renumbered §§7–12)
- **Key decisions**: Role routing by usergroup_id only (dropped whatsapp_users.user_type). Service messages inside 24hr window free (Meta July 2025 pricing). Queue+flush pattern avoids cold outbound costs. NIN SHA-256 hashed on client side, never stored plaintext. Path 3 self-registration via WhatsApp conversation flow — no web app needed for parent onboarding.
- **Status**: ✅ Done
- **Edge cases flagged**: Multi-role users (staff+parent) get combined menus. Pending notifications expire after 7 days. NIN hash comparison requires pre-existing student_records.parent_nin_hash column.

### 2026-05-30: Community docs rewrite + docsify setup
- **Work done**: Created full community-facing documentation site (docs/community/) with docsify. Rewrote all 6 files (README, for-schools, for-parents, ecosystem, roadmap, faq) with new product positioning ("Two products, one platform"), East Africa geography expansion, outcome-based roadmap reframing (blockchain→PTA elections, IPFS→Digital Records), concrete pricing tiers, Mermaid timeline diagrams, emotional parent framing, LIN context, and new FAQ structure (parents first). Created AGENTS.md as project context file. Moved developer docs from docs/whatsapp/ to docs/dev/. Setup two docsify servers at ports 4000 (community) and 4001 (dev). Added favicon, sidebar logos, pagination, dark mode toggle.
- **Files modified**: 6 community docs files (rewritten), 2 index.html configs, 2 _sidebar.md, AGENTS.md (NEW), klassapp-logo.svg, post-rewrite fixes across all files
- **Key decisions**: Community docs are public (clients+investors). Dev docs are gated/private. Meta 24hr window not exposed. Revenue model removed from public docs. "East Africa" for market claims, "Uganda" only for Uganda-specific facts.
- **Status**: ✅ Done

### 2026-05-30: SchoolPay deep research + integration spec
- **Work done**: Discovered SchoolPay (Fincom Technologies) is a real payment aggregator with 20K+ schools and 5M+ parents. Corrected aspirational "School Pay" feature in community docs to "LIN & Fee Management" (only fee balance notifications via WhatsApp, no payment processing). Designed full 4-phase integration spec at docs/dev/schoolpay-integration.md. Researched payment collection options for KlassApp's own billing (MarzPay 3.0%, Pesapal ~3.5%, Flutterwave ~4.8%).
- **Files modified**: docs/community/ecosystem.md, roadmap.md, AGENTS.md, docs/dev/schoolpay-integration.md (NEW)
- **Key decisions**: SchoolPay is Phase 5 roadmap. Community docs correctly frame as LIN & Fee Management. MarzPay (3.0% fee) is best option for KlassApp's own billing.
- **Status**: ✅ Done

### 2026-05-31: AI Agent Layer spec design (v1 → v2 rewrite)
- **Work done**: Designed initial AI Agent Layer spec (v1 — parent-facing chatbot on WhatsApp, function calling with GPT-4o-mini, RAG for school policies). After discussion pivoted to v2 — staff-only premium intelligence layer. Core features: marksheet ingestion (vision LLM), performance analysis (natural language queries), report enrichment (discipline notes, achievements), voice pipeline (Whisper transcription). Web primary, WhatsApp secondary. Premium gating. ~$1-5/month per school LLM cost supporting $50-200/month premium pricing.
- **Files modified**: docs/dev/ai-agent-layer.md (NEW, 558 lines rewritten), AGENTS.md (added session history, roadmap item #10)
- **Key decisions**: Parents keep existing deterministic menus (no AI). AI is staff-only premium feature. Marksheet ingestion is the killer feature. Voice supports low-literacy users. Agent types: Ingestion, Analysis, Enrichment, Alerts. 4-phase roadmap: Foundation→Vision+Voice→Alerts→Parent Voice. No code written — design spec only.
- **Status**: ✅ Done
