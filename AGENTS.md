# KlassApp — Session Memory & Project Context

## Active Branch: `main`

---

## DASHBOARD REDESIGN (COMPLETED — June 2026)

- Sora + DM Sans fonts aligned with landing page
- Brand colors: Blue #1E6FD9, Green #22C55E, Dark #0F172A, Amber #D97706
- White navbar with subtle border and shadow
- Dark sidebar (#0F172A) with green active accent
- KPI cards with colored icon circles, hover lift effect
- Chart.js 2.6 — do NOT upgrade (breaking API changes)
- Vanilla JS hamburger (replaced Tailwind + jQuery dependency)
- Dashboard at `/admin/dashboard`

### Files Modified
- `public/css/dashboard-refresh.css` — full rewrite
- `resources/views/layouts/app.blade.php` — fonts
- `resources/views/admin/dashboard/dashboard.blade.php`
- `resources/views/layouts/admin/sidebar.blade.php`
- `resources/views/layouts/partials/navigation.blade.php`
- `resources/views/auth/login.blade.php` — fixed false maintenance banner
- `app/Http/Kernel.php` — removed Sanctum from API middleware (fixes webhook 302)
- `routes/api.php` — added delivery webhook route

---

## WHATSAPP INTEGRATION (ACTIVE — June 2026)

### Production (Meta Cloud API only — Evolution removed)
- Webhook: https://klassapp.xyz/api/whatsapp/inbound
- Business Number: +256765275289
- Meta WABA fully active, Evolution API decommissioned
- ⚠️ Facebook Business Account banned (June 26). Need new number + fresh Meta account.
- Pending: Replace business number once new WABA is set up

### Deploy Command
```bash
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 \
  "cd /var/www/klassapp && git pull origin main && php artisan optimize:clear && systemctl restart php8.3-fpm"
```

### Local Dev
```bash
colima start                    # Docker
brew services start mysql       # MySQL
php artisan serve               # Laravel on :8000
```

### Test Credentials
- Super Admin: siteadmin@gmail.com / password
- School Admin: admin@testschoolone.sch.ug / password123

---

## LANDING PAGES (COMPLETED — June 2026)

### Two versions live

| Route | Version | Description |
|---|---|---|
| `http://localhost:8000/` | v1 | Unified landing/welcome merge |
| `http://localhost:8000/landing` | v1 | Direct landing URL |
| `http://localhost:8000/landing2` | **v2** | **Recommended** — gradient hero, horizontal navbar |

### v1 Features (`/`)
- Light theme: transparent → white navbar, warm-glow hero, slate footer
- Typewriter H1: "The school in every parent's pocket."
- Looping keyword typewriter: Grades → fees → attendance → health → canteen → discipline → notifications → timetables → exams → reports
- Audience tabs: Admin/Teacher/Parent with green active highlight
- CTAs: Join (green) + Portal (blue) + Book a demo (Calendly) + WhatsApp
- Navbar: vertical shrink on scroll (py-5 → py-3, h-14 → h-10)
- Footer: oversized "KlassApp" wordmark (22vw, mint, opacity 0.05)
- "Smarter schools start here." + "All rights reserved."
- "across Africa" (not East Africa), USD (not UGX)

### v2 Features (`/landing2`)
- Same content as v1
- **Gradient H1**: Animated blue → green → amber (gradientShift keyframes)
- **Horizontal navbar shrink**: Left-to-right scaleX on scroll DOWN, restore on scroll UP (Flare-style from https://flareapp.io)
- **Brand-colored footer**: Blue-green-dark gradient wordmark at 0.03 opacity
- **Pure white**: All backgrounds white (no warm glow)

---

## TECHNICAL FIXES APPLIED

### AppServiceProvider.php
```php
// Added try/catch around DB settings query
if (!App::runningInConsole()) {
    try {
        if (Schema::hasTable('settings')) {
            $settings = Setting::all();
            // ...
        }
    } catch (\Exception $e) {
        // Silently skip settings when DB unavailable
    }
}
```
Prevents boot crash when MySQL is not running.

### WelcomeController.php
Returns `view('landing')` instead of `view('welcome')`.

### Routes
- `routes/web.php`: Added `/landing2` route
- `routes/admin.php`: Removed premium-page routes

---

## FILES MODIFIED (13 total)

| File | Change |
|---|---|
| `resources/views/landing.blade.php` | v1 unified (1550+ lines) |
| `resources/views/landing2.blade.php` | v2 gradient/horizontal (1550+ lines) |
| `resources/views/welcome.blade.php` | Old file, not used by routes |
| `app/Http/Controllers/WelcomeController.php` | Points to landing |
| `app/Providers/AppServiceProvider.php` | DB-safe boot |
| `routes/web.php` | Added /landing2 |
| `routes/admin.php` | Removed premium routes |
| `docs/dev/digitalocean-deployment.md` | New |
| `scripts/provision-evolution.sh` | New |
| `scripts/provision-klassapp.sh` | New |

---

## PENDING WORK

### 1. WhatsApp Cloud API Migration ✅
- Evolution API (Baileys) fully removed. Meta Cloud API is the sole transport.

### 2. Incomplete Dashboards
- Accountant dashboard (no main view)
- Receptionist dashboard (partial)
- Librarian dashboard (no view)
- Superadmin dashboard (controller exists, no dedicated view)

### 3. Bar Chart Data
Admin dashboard bar chart uses hardcoded placeholder data. Needs real attendance or revenue data wiring.

### 4. Landing Page v1 ↔ v2
v2 (`/landing2`) has different navbar JS (direction-aware). Not aligned with v1 style. Consider merging into single canonical landing.

### 5. School Pay Signature Enforcement
SchoolPayWebhookController silently accepts unsigned webhooks during pilot. Add `SCHOOLPAY_ENFORCE_SIGNATURE=true` env flag or `school_pay_enforce_signature` toggle on School model to reject unsigned webhooks with 403 once payload format is confirmed.

### 6. WhatsAppPendingParentLink Table
`whatsapp_pending_parent_links` table and model exist but flow was changed to direct linking. Table is dead schema weight — either drop migration or keep as note.

---

## TECHNICAL NOTES

- **Native interactive list messages**: Max 10 rows across all sections. Meta Cloud API enforces this natively.

## KLASSAPP STUDENT ID SYSTEM

- Format: `KLS{school_code_3}{sequential_4}` (e.g., KLS0010427 — no dashes)
- Auto-generated during Toshi onboarding for each student
- Stored in `student_academics.klassapp_student_id` (unique, indexed)
- Primary identifier for WhatsApp parent linking (no School Pay code needed)
- School's own IDs supported via `id_card_number` / `board_registration_number`
- School admin responsible for distributing KlassApp IDs to parents (report cards, SMS)

## MINISTRY SCHOOL CODES

- `schools.ministry_code` — 4-digit Ministry of Education code (Uganda)
- Added migration June 29, 2026
- Used in KlassApp ID and WhatsApp school lookup
- Optional — schools without MoE codes use auto-generated codes

## BRAND ASSETS

| Element | Value |
|---|---|
| Blue | `#1E6FD9` |
| Green | `#22C55E` |
| Dark | `#0F172A` |
| Amber | `#D97706` |
| Surface (v1) | `#FAFAF5` |
| Display font | Sora |
| Body font | DM Sans |
| Logo | `images/klassapp-logo-primary.svg` |

---

## CONTENT RULES (Enforced)

| Rule | Status |
|---|---|
| "across Africa" (not East Africa) | ✅ |
| "Uganda" only for Uganda facts | ✅ |
| USD pricing (not UGX) | ✅ |
| No em dashes — commas/periods | ✅ (most removed) |
| No "Built in Uganda" public | ✅ |
| Parents are first-class users | ✅ |
| "And the system your admin team operates on" | ✅ |

---

---

## WHATSAPP SCHOOL PAY INTEGRATION (COMPLETED — June 23)

### Self-Verification Flow
- Parents verify by texting their School Pay payment code to the KlassApp WhatsApp number
- Code matched against `student_academics.std_school_pay_number` → joins through `student_parent_links` → `whatsapp_users`
- No school approval needed — code match = sufficient proof of parent relationship
- First-time texter flow: button message → code entry → auto-linked

### Interactive List Messages
- Welcome messages now use `sendList()` with tap-able buttons instead of "Reply FEES..." text prompts
- List buttons: Fee Balance, Exam Results, Attendance, Help & Options
- `routeInbound()` strips emojis from incoming messages so list button titles match keyword routing
- `sendListDual()` added to `OutboundWhatsAppService` for Business API fallback

### School Pay Webhook
- `SchoolPayWebhookController.php` — SHA256 HMAC verification, student join chain, WhatsApp receipt
- `schoolpay_transactions` table: dedup by receipt_no, raw_payload capture
- Route: `POST /api/schoolpay/webhook` (CSRF exempt)
- Message types: fee receipt, attendance, grades, health, student withdrawn, term opens/closes

### Free-Form Messages
- `OutboundWhatsAppService`: composeFeeBalance, composeAttendance, composeGradesOverview, composeHealthRecord, composeStudentWithdrawn, composeTermOpens, composeTermCloses
- Public notify methods: notifyFeeBalance, notifyAttendance, notifyStudentWithdrawn
- `sendButtons()` / `sendButtonsDual()` for interactive button messages via Evolution API

### Files Added/Modified
- `app/Http/Controllers/Api/SchoolPayWebhookController.php` — new
- `app/Http/Controllers/Api/WhatsAppController.php` — interactive lists, emoji matching, code verification flow
- `app/Services/WhatsAppService.php` — sendList() method
- `app/Services/OutboundWhatsAppService.php` — free-form builders + notify methods + sendListDual
- `app/Models/WhatsAppPendingParentLink.php` — new (created but flow changed to direct linking)
- `database/migrations/2026_06_23_000001_add_schoolpay_integration.php` — new
- `database/migrations/2026_06_23_074056_create_whatsapp_pending_parent_links_table.php` — new
- `database/migrations/2026_06_23_074057_make_user_id_nullable_on_whatsapp_users.php` — new
- `routes/api.php` — webhook route

---

## GITHUB STATUS

```bash
Branch: main
Commit: a58f3d0
Message: fix: replace Kampala High School with Kabale Junior School in testimonials (#105)
Status: Ahead of origin/main
```

---

## TOSHI 2.0 STATUS (June 19)

Items 1-11: ✅ Complete
- Critical fixes, plan selection, confirm/edit flow, input validation, review card, error handling, WhatsApp verification, dual-mode detection, progress persistence
- See `knowledge.md` for full spec and implementation details

## NEXT SESSION CHECKLIST

- [ ] Create new Meta Business Account with fresh email + new WhatsApp number
- [ ] Update .env with new WABA credentials (token, phone number ID, WABA ID)
- [ ] Run any new migrations on production
- [ ] Test end-to-end WhatsApp flow with new business number
- [ ] Schedule school onboarding demo with founding team
