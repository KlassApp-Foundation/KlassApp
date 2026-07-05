# KlassApp Production Readiness Audit

**Date:** June 20, 2026
**Audited by:** Goose (AAIF Agentic AI)
**Server IP:** 46.101.111.131
**Production URL:** https://klassapp.xyz
**Laravel Version:** 10.50.2
**PHP Version:** 8.3 (server) / 8.4 (local)

---

## Overall Verdict: ⚠️ NOT YET READY FOR SCHOOL ONBOARDING

**6 critical blockers** must be resolved before a real school can safely use KlassApp.

---

## 1. Authentication

| Check | Status | Notes |
|---|---|---|
| Email/password login | ✅ | `LoginController` with `AuthenticationProcess` trait |
| Registration flow | ✅ | `RegisterController` handles school creation, user, subscription, academic year |
| Google OAuth | ✅ | `GoogleAuthController` with onboarding interstitial |
| Email verification | ⚠️ | OTP is primary channel; email verification flow needs end-to-end testing |
| Password reset | ✅ | `ForgotPasswordController` exists |
| Impersonation | ✅ | `ImpersonateController` for superadmin support |
| Impersonation boundaries | ❓ | Can School Admin impersonate teacher/librarian/student? Confirm which roles, and confirm reverse (teacher→admin) is impossible. |
| Role-capability scoping | ❓ | If any module uses a capability/permission system similar to Toshi's `getRoleCapabilities()`, verify School Admin's actual allowed actions match documented expectations — not just that routes are gated, but the capability list itself is correct. |
| Multi-role routing | ✅ | `MustBeSchoolAdmin` middleware correctly routes usergroup_id → dashboard |

### Recommendation
- Test the full email verification flow (OTP → verify → redirect)
- Test Google OAuth from a fresh incognito session with a Gmail account not already in the database

---

## 2. Registration & Onboarding

| Check | Status | Notes |
|---|---|---|
| School creation | ✅ | `createSchool()` generates slug, creates `School` record |
| User profile creation | ✅ | `createUserProfile()` links user to school |
| Subscription creation | ⚠️ | Defaults to free plan (`plan_id=1`, status `pending`) if no plan selected |
| Academic year creation | ✅ | Auto-creates current year with Feb–Dec dates |
| Plan selection during registration | ⚠️ | Session-based; fragile across redirects |
| Toshi onboarding wizard | ✅ | Livewire `agent-toshi` component on admin dashboard |
| Google onboarding interstitial | ✅ | `auth.onboarding` view collects school details |
| Onboarding reminder banner | ✅ | Dismissible banner on admin dashboard |

### Issues
- **No payment integration.** Schools always sign up on the free plan. Stripe/Flutterwave/Mpesa integration is needed before paid plans can be sold.
- **Subscription defaults to `pending` status** — no activation workflow.
- Plan selection uses session (`session('selected_plan')`) which can be lost across redirects.

### Recommendation
- Implement plan billing before onboarding paid schools
- Add Stripe or Flutterwave checkout integration
- Add subscription activation webhook

---

## 3. Admin Dashboards

| Dashboard | Controller | View | Status | Notes |
|---|---|---|
| **Admin** | `DashboardController` | `admin/dashboard/dashboard.blade.php` | ✅ Complete | KPI cards, charts, tables |
| **Superadmin** | `DashboardSuperController` | `superadmin/dashboard.blade.php` | ✅ Complete | Multi-school overview |
| **Teacher** | `DashboardController` | `teacher/dashboard/dashboard.blade.php` | ✅ Complete | Tasks, students |
| **Student** | `DashboardController` | `student/dashboard/dashboard.blade.php` | ✅ Complete | Grades, attendance |
| **Accountant** | `DashboardController` | `accountant/dashboard.blade.php` | ⚠️ Incomplete | No main dashboard view |
| **Receptionist** | `DashboardController` | `reception/dashboard.blade.php` | ⚠️ Incomplete | Partial views only |
| **Librarian** | `DashboardController` | `library/dashboard.blade.php` | ⚠️ Incomplete | No main view |
| **WhatsApp Admin** | `WhatsAppDashboardController` | `admin/whatsapp/dashboard.blade.php` | ✅ Complete | WhatsApp stats and logs |

### Issues
- **Accountant, Receptionist, and Librarian dashboards are incomplete.** Users of these roles will see empty or broken pages.
- Bar chart on admin dashboard uses hardcoded placeholder data — needs real attendance/revenue wiring.

### Recommendation
- Complete accountant dashboard (fee collection, payment reports)
- Complete receptionist dashboard (visitor logs, front desk)
- Complete librarian dashboard (book lending, inventory)
- Wire real data to admin bar chart

---

## 4. WhatsApp / WABA Integration

| Check | Status | Notes |
|---|---|---|
| Meta WABA credentials | ✅ | Permanent token, Phone Number ID: `1192586767270209`, WABA ID: `1709193870117417` |
| Webhook endpoint | ✅ | `https://klassapp.xyz/api/whatsapp/inbound` — 200 OK |
| Webhook verification | ✅ | `klassapp_verify_2026` registered in Meta |
| Inbound routing | ✅ | Keyword-based: `menu`, `grades`, `fees`, `attendance`, `timetable` |
| Interactive lists | ✅ | `sendList()` via `WhatsAppService` — sections with rows |
| 24hr service window detection | ✅ | `WhatsAppBusinessService` checks `last_inbound_at` |
| Template fallback | ✅ | `sendTextSafe()` falls back to template when service window closed |
| Outbound notifications | ✅ | `OutboundWhatsAppService` for proactive grade/fee push |
| Message delivery logging | ✅ | `MessageDeliveryLog` model with delivery webhook |
| Delivery webhook | ✅ | `POST /api/whatsapp/delivery` — `deliveryWebhook()` |

### Issues

#### 🔴 Critical: No webhook signature verification
```php
// app/Http/Controllers/Api/WhatsAppController.php
public function handleInbound(Request $request, WhatsAppService $whatsAppService)
{
    // No HMAC signature check — anyone can POST to this endpoint
```
**Anyone can spoof inbound WhatsApp messages** by sending POST requests to the webhook URL. Meta provides `X-Hub-Signature-256` header with SHA256 HMAC of the payload.

#### ⚠️ WhatsAppService still references Evolution API
`WhatsAppService.php` contains `sendList()` and `sendText()` methods that use Evolution API format (`baseUrl}/message/sendText/{instanceName}`). The `OutboundWhatsAppService` uses `WhatsAppBusinessService` for WABA, but old Evolution paths remain in code.

#### ⚠️ `whatsapp_users` table empty
0 users linked to WhatsApp — the auto-reply correctly tells users their number is not linked, but there's no UI for admins to link parent/student phone numbers.

### Recommendation
- **Implement HMAC verification immediately** — compare `hash_hmac('sha256', $payload, $appSecret)` against `X-Hub-Signature-256` header
- Remove or sunset Evolution API code paths
- Add WhatsApp number linking UI in admin panel (parent/student profiles)
- Add WhatsApp onboarding step to Toshi wizard (item 16)

---

## 5. Code Quality

### 🔴 Critical: `dd()` / `dump()` calls in production code

| File | Count | Risk |
|---|---|---|
| `Librarian/TaskController.php` | 5 | Will dump sensitive data to browser |
| `Receptionist/TaskController.php` | 5 | Will dump sensitive data to browser |
| `LiveStreamController.php` | 1 | Will crash page |
| `Librarian/BookController.php` | 8 | Will crash book operations |
| `Librarian/BookCategoryController.php` | 3 | Will crash category operations |
| `Librarian/BookLendingController.php` | 4 | Will crash lending operations |
| `Librarian/HolidaysController.php` | 2 | Will crash holiday views |
| `Receptionist/NotificationController.php` | 3 | Will crash notifications |
| `Receptionist/EmailRecordController.php` | 2 | Will crash email records |
| `TestController.php` | 1 | Test page only — low risk |
| **Total** | **~35 unique `dd()` calls** | **🔴 Production danger** |

All `dd()` calls are in `catch` blocks — meaning **any exception in librarian or receptionist workflows will dump raw error data and stack traces to the browser.**

### ⚠️ PHP 8.4 Deprecation Warnings (local only)

```
Deprecated: activity(): Implicitly marking parameter $logName as nullable
Deprecated: array_first(): Implicitly marking parameter $callback as nullable
Deprecated: array_last(): Implicitly marking parameter $callback as nullable
```

Approximately 20 deprecation notices in local console. Server runs PHP 8.3 so these are local-only. Will become issues when server upgrades to 8.4.

### ⚠️ Unstable Dependencies

| Package | Current | Risk |
|---|---|---|
| `predis/predis` | `v3.0.0-alpha1` | Alpha in production |
| `pusher/pusher-php-server` | `7.1.0-beta` | Beta in production |
| `barryvdh/laravel-dompdf` | `dev-master` | No stable tag |
| `aws/aws-sdk-php-laravel` | `dev-master` | No stable tag |
| `laracasts/presenter` | `dev-master` | No stable tag |
| `maatwebsite/excel` | `3.1.x-dev` | Dev branch |
| `santigarcor/laratrust` | `7.x-dev` | Dev branch |
| `titasgailius/search-relations` | `dev-master` | No stable tag |
| `twilio/sdk` | `dev-main` | No stable tag |

### Recommendation
- Replace all `dd()`/`dump()` with `Log::error()` **before onboarding any school**
- Lock dev-master packages to stable tagged versions
- Replace predis alpha with predis 2.x stable
- Replace pusher beta with pusher 7.2.x stable
- Upgrade Laravel 10 → 11 for PHP 8.4 compatibility

---

## 6. Security

| Check | Status | Notes |
|---|---|---|
| HTTPS enforcement | ✅ | Nginx redirects HTTP → HTTPS |
| SSL certificate | ✅ | Let's Encrypt, auto-renew via certbot |
| `.env` exposed | ✅ | 403 from nginx |
| `composer.json` exposed | ✅ | Blocked by nginx |
| Sanctum API middleware | ✅ | Removed from api routes (was causing webhook 302) |
| Password hashing | ✅ | `Hash::make()` in RegisterController |
| CSRF protection | ✅ | `web` middleware group |
| API rate limiting | ✅ | `throttle:60,1` on api routes |
| Hardcoded secrets in code | ✅ | None found in core controllers |
| XSS protection | ⚠️ | Blade auto-escapes; no CSP header configured |
| **APP_DEBUG in production** | 🔴 | **Currently `true` on server** — exposes stack traces |
| **WABA HMAC verification** | 🔴 | **Missing** — webhook is unauthenticated |
| CORS headers | ⚠️ | No CORS policy configured |

### Recommendation
- **Set `APP_DEBUG=false` on production server** — 5-minute fix
- **Add HMAC signature verification to webhook** — critical
- Add Content-Security-Policy header
- Add CORS policy for API routes

---

## 7. Infrastructure

| Check | Status | Notes |
|---|---|---|
| Droplet | ✅ | Ubuntu 24.04 LTS, 1 vCPU, 1 GB RAM |
| Nginx | ✅ | 1.24.0, SSL via Let's Encrypt |
| PHP-FPM | ✅ | 8.3, 3 idle workers |
| MySQL | ✅ | 8.0, 127 tables |
| Redis | ✅ | Running on localhost:6379 |
| Supervisor | ✅ | Horizon for Laravel queues |
| DNS | ✅ | `klassapp.xyz` → `46.101.111.131` |
| UFW Firewall | ⚠️ | Installed but **inactive** |
| iptables | ✅ | Clean (Docker DROP rules flushed and persisted) |
| Docker | ✅ | Removed (was Evolution API) |

### Issues

#### 🔴 Critical: No Swap Space
```
Mem:  961 MB total, 650 MB used, 99 MB free, 0 MB swap
```
With only 99 MB free and zero swap, **the droplet will crash under load**. A database migration or composer operation could trigger OOM kill.

#### 🔴 Critical: No Backups
No DO automated backups configured. If the droplet is destroyed (which happened once already), all data is lost.

#### ⚠️ Memory Pressure
67% memory usage at idle (650 MB / 961 MB). MySQL uses 210 MB alone. With PHP-FPM workers, Redis, and nginx, there's very little headroom.

### Recommendation
- **Add swap space** — 2 GB swap file: `fallocate -l 2G /swapfile && mkswap /swapfile && swapon /swapfile`
- **Enable DO automated backups** — $1.20/month for the $6 droplet
- **Increase droplet to 2 GB RAM** or add PHP-FPM `pm.max_children` limit
- Enable UFW with rules for ports 22, 80, 443

---

## 8. Test Coverage

| Check | Status |
|---|---|
| PHPUnit tests | ⚠️ 5 test files — minimal coverage |
| Feature tests (WhatsApp) | ❌ None |
| Feature tests (Authentication) | ❌ None |
| Feature tests (Registration) | ❌ None |
| Browser tests | ❌ None |
| CI/CD pipeline | ❌ None |

### Current test files
```
tests/Feature/ExampleTest.php
tests/Unit/ExampleTest.php
tests/CreatesApplication.php
tests/TestCase.php
```

### Recommendation
- Write minimum 20 feature tests covering:
  - Registration flow (email + Google)
  - Login/logout
  - WhatsApp inbound webhook (with HMAC)
  - WhatsApp outbound message
  - Admin dashboard access control
  - School setup onboarding
  - Parent linking students
- Set up GitHub Actions for CI on push to main

---

## 9. Data Integrity

| Check | Status | Notes |
|---|---|---|
| Migrations run | ✅ | 127 tables in `klassapp_local` |
| Foreign keys | ✅ | `country_id`, `state_id`, `city_id` on schools |
| Soft deletes | ✅ | `deleted_at` on users, schools, subscriptions |
| Seed data | ✅ | 5 test schools, 4 plans, site admins, teachers |
| Plan pricing | ✅ | Free: $0, Standard: $45,000, Extended: $95,000, Premium: $150,000 |

### Issues
- **Column mismatch:** `GoogleAuthController` references `$school->country` but `schools` table has `registration_country` and `country_id` — the Google onboarding flow may fail on school update.
- **No database backups configured** — single point of failure.

---

## 10. Payload / Third-Party Dependencies

| Dependency | Status | Notes |
|---|---|---|
| Meta WhatsApp Cloud API | ✅ | v21.0, permanent token |
| Google OAuth | ✅ | Configured, working after .env fix |
| Mailtrap SMTP | ✅ | Transactional email working |
| Algolia | ⚠️ | App ID and Secret empty in .env |
| AWS S3 | ⚠️ | All AWS credentials empty in .env |
| Firebase | ⚠️ | Credentials empty in .env |
| Twilio | ⚠️ | SID and token empty in .env |
| Pusher | ⚠️ | App ID, key, secret empty — real-time features disabled |
| Chart.js | ✅ | 2.6 (per project rules, do NOT upgrade) |
| Filament | ✅ | 3.x for admin tables |
| Livewire | ✅ | 3.x |

### Issues
- **Algolia, AWS, Firebase, Twilio, Pusher are not configured** — features relying on these (search, file storage, push notifications, SMS, real-time) are non-functional.
- SMS gateway is set to MSG91 but `REMINDER_API_KEY` is empty.

### Recommendation
- Decide which third-party services are essential for launch
- Configure at minimum: AWS S3 (file storage) and Pusher (real-time notifications)
- Document which features are intentionally deferred

---

## Fix Priority Matrix

### 🔴 Critical — Must Fix Before Onboarding

| # | Issue | Effort | Risk if Not Fixed |
|---|---|
| 1 | Remove all `dd()`/`dump()` calls → `Log::error()` | 1 hour | Stack traces exposed to users |
| 2 | Set `APP_DEBUG=false` on production | 5 min | Stack traces + sensitive data exposed |
| 3 | Add WABA webhook HMAC verification | 2 hours | Anyone can spoof WhatsApp messages |
| 4 | Add swap space (2 GB) | 10 min | Droplet crashes under load |
| 5 | Enable DO automated backups | 5 min | Total data loss on failure |
| 6 | Fix Accountant/Receptionist/Librarian dashboards | 2-4 hours | Users see broken pages |

### ⚠️ Important — Should Fix Within First Week

| # | Issue | Effort |
|---|---|---|
| 7 | Add WhatsApp number linking UI in admin | 3 hours |
| 8 | Wire real data to admin bar chart | 1 hour |
| 9 | Enable UFW firewall (ports 22, 80, 443) | 5 min |
| 10 | Fix `GoogleAuthController` school column mismatch | 30 min |
| 11 | Add Content-Security-Policy header | 30 min |

### 🔵 Nice to Have — Within First Month

| # | Issue | Effort |
|---|---|---|
| 12 | Payment integration (Stripe/Flutterwave) | 1-2 weeks |
| 13 | Complete Toshi onboarding items 12-17 | 1 week |
| 14 | Replace dev-master deps with stable tags | 4-6 hours |
| 15 | Write 20+ feature tests | 1-2 weeks |
| 16 | Upgrade Laravel 10 → 11 | 4-6 hours |
| 17 | GitHub Actions CI pipeline | 2-4 hours |
| 18 | Configure AWS S3, Pusher, Algolia | 2-4 hours |

---

## Appendix: Useful Commands

### Deploy to Production
```bash
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 \
  "cd /var/www/klassapp && git pull origin main && php artisan optimize:clear && systemctl restart php8.3-fpm"
```

### Run App Locally
```bash
cd ~/projects/KlassApp
brew services start mysql
php artisan serve
```

### Test Credentials
| Role | Email | Password |
|---|---|---|
| Super Admin | `siteadmin@gmail.com` | `password` |
| Super Admin | `superadmin@gmail.com` | `password` |
| School Admin | `admin@testschoolone.sch.ug` | `password123` |
| Teacher | `teacher_test_school_one@testschoolone.edu` | `password` |

### WhatsApp Test Number
- KlassApp business: `+256765275289`
- Send a WhatsApp message → auto-reply with menu

---

*Generated June 20, 2026 — Next audit recommended after critical items 1-6 are resolved.*
