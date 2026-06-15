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

### Production
- Evolution API: 46.101.130.70:8081 (Docker, evoapicloud/evolution-api:latest)
- API Key: 78E5A6FF-BA89-45C6-987C-C31407BD22B4
- Instance: klassapp
- Webhook: https://klassapp.xyz/api/whatsapp/inbound
- Business Number: +256765275289

### Deploy Command
```bash
ssh -i ~/.ssh/id_ed25519_do root@165.245.250.16 \
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

### 1. WhatsApp Cloud API Migration
Current: Evolution API + Baileys (unofficial WhatsApp Web)
Target: Official Meta WhatsApp Business API
- Need Meta Business Account, Phone Number ID, WABA ID, Permanent Access Token

### 2. Incomplete Dashboards
- Accountant dashboard (no main view)
- Receptionist dashboard (partial)
- Librarian dashboard (no view)
- Superadmin dashboard (controller exists, no dedicated view)

### 3. Bar Chart Data
Admin dashboard bar chart uses hardcoded placeholder data. Needs real attendance or revenue data wiring.

### 4. Landing Page v1 ↔ v2
v2 (`/landing2`) has different navbar JS (direction-aware). Not aligned with v1 style. Consider merging into single canonical landing.

---

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

## GITHUB STATUS

```bash
Branch: whatsapp
Commit: d752d9c
Message: feat(landing): Unified landing page v1 and v2
Status: Pushed to origin/whatsapp
Files: +3,648 lines, -310 lines
```

---

## NEXT SESSION CHECKLIST

- [ ] Fix navbar scroll direction (up=restore, down=compact)
- [ ] Verify typewriter cursor blink behavior
- [ ] Start dashboard redesign
- [ ] Mobile responsive audit
- [ ] Performance: lazy load dashboard mockups
- [ ] Accessibility: aria-labels on audience tabs
- [ ] SEO: verify meta tags, Open Graph

---

*Last updated: June 2026*
*Next: Dashboard redesign + navbar direction fix*
