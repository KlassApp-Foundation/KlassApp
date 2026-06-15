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

### Webhook
- **Inbound**: `POST /api/whatsapp/inbound` → `WhatsAppController@handleInbound`
- **Delivery**: `POST /api/whatsapp/delivery` → `WhatsAppController@deliveryWebhook`
- **Critical fix**: Removed `EnsureFrontendRequestsAreStateful` from API middleware group (was causing 302 redirects)

### Key Files
| File | Purpose |
|---|---|
| `app/Services/WhatsAppService.php` | Evolution API client — sendText, sendList, sendTemplate |
| `app/Services/OutboundWhatsAppService.php` | Proactive notifications — grades, fees, attendance |
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

## Known Issues

1. **Chart.js 2.6** — Do NOT upgrade to v4. API has breaking changes (legend→plugins.legend, tooltips→plugins.tooltip, scale→scales)
2. **Bar chart hardcoded** — Admin dashboard bar chart uses fake data (January-July). Needs real data wiring.
3. **WhatsApp Cloud API** — Currently using Evolution API with Baileys (unofficial). User wants to switch to official Meta WhatsApp Business API.
4. **Incomplete dashboards** — Accountant, Receptionist, Librarian have no full dashboard views.
5. **Landing v2** — Has different navbar JS (direction-aware). Not aligned with v1 style.

---

## Session Log

### 2026-06-10: Dashboard redesign, WhatsApp webhook fix, production deploy
- **Work done**: Redesigned admin dashboard (CSS + Blade), fixed WhatsApp webhook 302 redirect, fixed login maintenance banner, deployed to production (165.245.250.16), provisioned Evolution API on 46.101.130.70, connected WhatsApp instance
- **Files modified**: `dashboard-refresh.css`, `app.blade.php`, `admin/dashboard/dashboard.blade.php`, `admin/sidebar.blade.php`, `navigation.blade.php`, `auth/login.blade.php`, `app/Http/Kernel.php`, `routes/api.php`
- **Key decisions**: Kept Chart.js 2.6 (no upgrade to avoid breaking changes). Used vanilla JS for hamburger (avoid Tailwind compilation issues). Changed Evolution API DB from MongoDB to PostgreSQL on production.
- **Status**: ✅ Done
- **Edge cases flagged**: WhatsApp webhook 302 was caused by Sanctum's `EnsureFrontendRequestsAreStateful` in API middleware group. Evolution API image name is `evoapicloud/evolution-api` not `evolutionapi/evolution-api`.
