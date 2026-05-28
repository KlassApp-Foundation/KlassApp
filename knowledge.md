# KlassApp Knowledge Base

> **Purpose**: Canonical project knowledge base — read at the start of every session.
> **Maintainers**: Append session summaries to the Session Log at the bottom after each work session.

---

## 1. Project Overview

| Field | Value |
|---|---|
| **Name** | KlassApp (formerly Gegok12) |
| **Description** | Multi-tenant school management system for Uganda schools |
| **Stack** | Laravel 10 + Blade + Tailwind 1.4 + Vue 2 + MySQL |
| **PHP** | 8.1+ (production Docker uses php:8.3-fpm) |
| **Hosting** | VPS (Hetzner) — domain via Cloudflare (ugasch.com) |
| **Laravel Vapor** | Configured for `gegok12` (id: 10390) — production + staging on AWS Lambda |
| **Timezone** | `Asia/Kolkata` (legacy — set in .env, not Uganda time) |
| **Auth** | Laravel Sanctum (API) + session-based (web) |
| **Roles** | Multi-role via Laratrust (superadmin, schooladmin, teacher, parent, student, librarian, accountant, etc.) |
| **Logo** | SVG in `public/images/` — `klassapp-logo.svg`, `klassapp-logo-primary.svg`, `klassapp-logo-dark.svg` |

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
```

### 5.2 Services

| File | Purpose |
|---|---|
| `app/Services/WhatsAppService.php` | HTTP transport layer — `sendText()`, `sendTemplate()`, `sendMedia()` via Evolution API. Logs to `message_delivery_log`. |
| `app/Services/OutboundWhatsAppService.php` | Business logic — `notifyGradesPublished()`, `notifyFeeReminder()`, `getParentPhones()`. Depends on `WhatsAppService`. |
| `app/Helpers/WhatsAppPhoneHelper.php` | Phone utilities — `normalise()`, `validate()` (regex: `\+256(7[0578]\d{7})`), `formatMessage()`. |

### 5.3 Inbound Webhook

- **Endpoint**: `POST /api/whatsapp/inbound` (outside HMAC middleware)
- **Controller**: `WhatsAppController@handleInbound`
- **Validation**: `StoreWhatsAppWebhookRequest` — validates `event=messages.upsert`, `remoteJid`, message content, payload size (1MB), phone format
- **Guards**: Ignores group messages (`@g.us`), own messages (`fromMe=true`), non-message events
- **Keywords**: `menu`, `grades`, `fees`, `attendance`, `events`, `optin`, `optout`

### 5.4 Outbound Hooks

| Component | File | Purpose |
|---|---|---|
| **Event** | `app/Events/GradesPublished.php` | Dispatched when marks published (`$student`, `$examId`) |
| **Listener** | `app/Listeners/SendGradesToWhatsApp.php` | Calls `OutboundWhatsAppService::notifyGradesPublished()` |
| **Command** | `app/Console/Commands/SendFeeReminders.php` | `whatsapp:send-fee-reminders` with `--type=reminder\|overdue`, `--school-id`, `--dry-run` |
| **Schedule** | `app/Console/Kernel.php` | Weekly reminders (Mondays), daily overdue — both `withoutOverlapping()` |

### 5.5 Models

| Model | Table | Key Fields |
|---|---|---|
| `WhatsAppUser` | `whatsapp_users` | `phone`, `user_id`, `opted_out`, `unsubscribed_at` |
| `MessageDeliveryLog` | `message_delivery_log` | `whatsapp_message_id`, `phone`, `category`, `status`, `direction`, `flow_type` |
| *(Note: `$timestamps = false` on MessageDeliveryLog — migration has no `created_at`/`updated_at`)* |

### 5.6 WhatsApp Business Config

| Key | Value |
|---|---|
| **Business Number** | `+256767538805` (in `config/services.php` + `.env`) |
| **Evolution URL** | `http://localhost:8081` (configurable via `EVOLUTION_API_URL`) |
| **Evolution API Key** | Set in `.env` |
| **HMAC Secret** | Set in `.env` — used by `WhatsAppHmac` middleware (not applied to inbound webhook) |
| **Send Delay** | 1200ms (rate limiting between messages) |

### 5.7 Phone Format

- Uganda mobile: `+256 7[0578] XXX XXX` (12 chars with `+`, 9 digits after +256)
- `WhatsAppPhoneHelper::normalise()`: strips non-digits, ensures E.164
- `WhatsAppPhoneHelper::validate()`: regex = `/^\+256(7[0578]\d{7})$/`
- wa.me links: `str_replace('+', '', $phone)` to strip `+` prefix

---

## 6. Premium School Pages

- **5 templates** in `resources/views/schools/templates/template-{1..5}.blade.php`
- `_shared.blade.php` provides `$whatsappLink` helper (auto-normalises school phone → wa.me link)
- WhatsApp buttons: hero CTA + floating widget + clickable contact phone
- School phone in templates comes from `School` model — auto-normalised (strips non-digits, prepends 256 if leading zero)

---

## 7. Database

### 7.1 Migration Count

129 migration files in `database/migrations/`

### 7.2 Key Tables (WhatsApp)

| Migration | Table |
|---|---|
| `2026_05_16_000001_create_whatsapp_users_table.php` | `whatsapp_users` |
| `2026_05_16_000002_create_message_delivery_log_table.php` | `message_delivery_log` |
| `2026_05_27_000001_create_premium_pages_table.php` | `premium_pages` |

### 7.3 Test DB

- `phpunit.xml` uses SQLite in-memory for tests
- WhatsApp env vars configured in `phpunit.xml` for testing

---

## 8. Testing

- **Framework**: PHPUnit 10
- **Suites**: Unit (`tests/Unit`), Feature (`tests/Feature`)
- **WhatsApp tests**:
  - `tests/Feature/WhatsApp/WebhookValidationTest.php` — 7 tests (FormRequest rules)
  - `tests/Feature/WhatsApp/OutboundNotificationTest.php` — 4 tests (phone helpers)
- **Factories**: `WhatsAppUserFactory`, `MessageDeliveryLogFactory`
- **Run**: `php artisan test tests/Feature/WhatsApp/`
- **Note**: 3 tests SKIP when DB-dependent (need `whatsapp_users` / pivot tables). Run with `--env=testing` and migrated DB to enable.

---

## 9. Known Issues & Edge Cases

### 9.1 Phone Validation (premium templates)
School phone numbers in non-Uganda format (e.g. `+254...` Kenya) pass through without validation in `_shared.blade.php`. Should add Uganda-only validation in `UserProfileController@linkWhatsApp` before save:
```php
preg_match('/^2567[0578]\d{7}$/', $cleaned)
```

### 9.2 CI Pipeline Disabled
`.github/workflows/klassapp-ci.yml` is fully commented out. No CI runs.

### 9.3 `.env.example` Outdated
Missing WhatsApp env vars (`EVOLUTION_API_KEY`, `WHATSAPP_HMAC_SECRET`, `WHATSAPP_BUSINESS_NUMBER`, etc.) — these exist only in `.env` (git-ignored).

### 9.4 Timezone
`.env` has `TIMEZONE=Asia/Kolkata` — Uganda is `Africa/Kampala` (UTC+3, no DST). May cause date mismatches.

### 9.5 Tailwind v1
`tailwind.config.js` uses Tailwind 1.4.6 with `purge: false` — all classes included. Upgrade would reduce CSS size significantly.

---

## 10. Key Files & Locations

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

## 11. Session Log

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
