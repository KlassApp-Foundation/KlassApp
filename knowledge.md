# KlassApp Project Knowledge

## Current Status: June 2026

### Landing Pages (COMPLETED)
Two versions live at http://localhost:8000/

#### v1 (`/` and `/landing`)
- **Theme**: Light throughout (white navbar, warm-glow hero, slate footer)
- **Hero**: Typewriter headline "The school in every parent's pocket." + looping keyword typewriter (Grades, fees, attendance, health, canteen, discipline, notifications, timetables, exams, reports)
- **Audience Tabs**: Admin/Teacher/Parent with green active highlight (`text-brand-green`, `border-brand-green`)
- **CTAs**: Join (green) + Portal (blue) + Book a demo (Calendly) + Chat on WhatsApp
- **Navbar**: Transparent → white on scroll (vertical shrink: py-5 → py-3)
- **Footer**: Oversized "KlassApp" wordmark (22vw, mint green, opacity 0.05), "Smarter schools start here."
- **Geography**: "across Africa" (not East Africa)
- **Currency**: USD $12.4K, $0 (not UGX)
- **No**: "Built in Uganda", em dashes (replaced with commas/periods)
- **Content**: "And the system your admin team operates on."

#### v2 (`/landing2`) — RECOMMENDED
- Same content as v1
- **Gradient hero headline**: Animated 3-color gradient (blue → green → amber, `gradientShift` keyframes)
- **Horizontal navbar shrink**: Left-to-right (scaleX) on scroll down, restores on scroll up (Flare-style)
- **Brand-colored footer wordmark**: Blue-green-dark gradient at 0.03 opacity
- **Pure white backgrounds**: No warm glow, all white/slate

### Technical Fixes Applied
- `AppServiceProvider.php`: Added try/catch around DB settings query (prevents boot crash when MySQL unavailable)
- `WelcomeController.php`: Returns `view('landing')` instead of `view('welcome')`
- `routes/web.php`: Added `/landing2` route
- `routes/admin.php`: Removed premium-page routes (replaced with seodetailsettings)

### Git Status
- **Branch**: `whatsapp`
- **Last commit**: `d752d9c` — "feat(landing): Unified landing page v1 and v2"
- **Pushed**: Yes, to origin/whatsapp

### Files Modified (13 files)
- `resources/views/landing.blade.php` — v1 unified landing
- `resources/views/landing2.blade.php` — v2 gradient/horizontal shrink
- `resources/views/welcome.blade.php` — old (still exists, not used)
- `app/Http/Controllers/WelcomeController.php` — points to landing view
- `app/Providers/AppServiceProvider.php` — DB-safe boot
- `routes/web.php` — added /landing2
- `routes/admin.php` — cleaned premium routes
- `docs/dev/digitalocean-deployment.md` — new
- `scripts/provision-evolution.sh` — new
- `scripts/provision-klassapp.sh` — new

### Pending Work
1. **Dashboard improvements** — User wants to work on admin/teacher dashboards next
2. **Navigation scroll direction** — Need to fix: on scroll DOWN → compact (shrink), on scroll UP → restore. Currently triggers on position, not direction.
3. **Typewriter cursor** — Need to verify it doesn't blink during typing (should stop blinking while typing, resume after)

### Brand Colors
| Color | Hex | Usage |
|---|---|---|
| Blue | `#1E6FD9` | CTAs, links, brand accent |
| Green | `#22C55E` | Join button, active tabs, keyword highlights |
| Dark | `#0F172A` | Headings, dark mode |
| Amber | `#D97706` | Cursor, accent bar, warmth |
| Surface | `#FAFAF5` | Light section backgrounds (v1) |

### Fonts
- **Display**: Sora (headlines)
- **Body**: DM Sans (body text)
- **Fallback**: Inter, system-ui

### Key Directives
- "across Africa" for all market claims
- "Uganda" only for Uganda-specific facts
- No em dashes — use commas or periods
- No "Built in Uganda" in public-facing copy
- USD pricing (not UGX)
- Parents are first-class users

### Next Session Priority
1. Fix navbar scroll direction logic (up = restore, down = compact)
2. Start dashboard redesign work
3. Test all animations on mobile
