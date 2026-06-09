# KlassApp — Session Memory & Project Context

## Active Branch: `whatsapp`

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

### 1. Navbar Scroll Direction (CRITICAL FIX NEEDED)
Current behavior: Navbar compacts when scrollY > 60 (position-based)
**Required behavior**: 
- Scroll DOWN → compact (shrink)
- Scroll UP → restore (expand)

Implementation in `landing.blade.php` and `landing2.blade.php`:
```javascript
let lastScroll = 0;
window.addEventListener('scroll', () => {
    const y = window.scrollY;
    const goingDown = y > lastScroll;
    if (y > 60) {
        if (goingDown) { /* compact */ }
        else { /* restore */ }
    }
    lastScroll = y;
});
```

### 2. Typewriter Cursor
- Should NOT blink while typing (`animation: none` during type)
- Should resume blinking after typing complete

### 3. Dashboard Redesign (NEXT MAJOR TASK)
User explicitly asked: "we have to work on dashboards too"
- Admin dashboard
- Teacher dashboard
- Accountant dashboard
- Parent portal (WhatsApp is primary, but web view exists)

### 4. Mobile Testing
- Verify all animations on actual mobile devices
- Touch targets for audience tabs
- WhatsApp mockup responsive sizing

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
