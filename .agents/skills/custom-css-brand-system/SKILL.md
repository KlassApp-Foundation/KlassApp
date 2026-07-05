---
name: custom-css-brand-system
user-invocable: false
description: |
  KlassApp's actual CSS theming layer: brand tokens in dashboard-refresh.css,
  Tailwind v1.4.6 constraints, and design gap tracking.
  NOT DaisyUI. NOT Tailwind v2/v3/v4. Custom CSS properties only.
  Triggers on: 'CSS', 'styling', 'brand colors', 'dashboard-refresh', 'Tailwind version',
  'design tokens', 'theming', 'fonts', 'card padding', 'inline styles'.
---

# Custom CSS Brand System — KlassApp

> **CRITICAL: This project does NOT use DaisyUI, Tailwind v2+, or any component library.**
> The theming layer is Tailwind v1.4.6 utility classes + custom CSS properties in `dashboard-refresh.css`.
> **Never suggest or write DaisyUI components, Tailwind v3+ config syntax, or JIT-only classes.**

---

## 1. Stack Reality

| Component | Version | Notes |
|---|---|---|
| **Tailwind CSS** | **v1.4.6** | NOT v2, v3, or v4. No JIT mode. No `@apply` with v3 syntax. |
| **Build tool** | Laravel Mix v4 + `laravel-mix-tailwind` | NOT Vite. NOT PostCSS standalone. |
| **PurgeCSS** | `laravel-mix-purgecss` v4 | `purge: false` in config — does NOT tree-shake |
| **DaisyUI** | ❌ Not installed | Evaluated and skipped (requires Tailwind v3+) |
| **Custom CSS** | `public/css/dashboard-refresh.css` | Primary theming layer — CSS custom properties |

### What This Means

- **NO** `@tailwind base/components/utilities` v3 syntax — use v1 `@tailwind preflight`, `@tailwind components`, `@tailwind utilities`
- **NO** JIT-only classes like `bg-[#1E6FD9]`, `w-[200px]`, `top-[50%]` — these don't exist in Tailwind v1
- **NO** DaisyUI components (`btn`, `card`, `modal`, etc.) — not installed
- **NO** Tailwind v3 config format (`content: [...]`) — v1 uses `purge: []`
- **NO** `tailwind.config.ts` — v1 uses `tailwind.config.js`
- **YES** to Tailwind v1 utility classes: `flex`, `grid`, `px-4`, `py-2`, `text-sm`, `bg-blue-500`, etc.
- **YES** to CSS custom properties: `var(--d-blue)`, `var(--d-green)`, etc.

---

## 2. Brand Tokens (Source of Truth)

**Source:** `public/css/dashboard-refresh.css` — `:root` block

### Color Palette

| Variable | Value | Usage |
|---|---|---|
| `--d-blue` | `#1E6FD9` | Primary actions, links, headers |
| `--d-green` | `#22C55E` | Success states, active sidebar, CTAs |
| `--d-dark` | `#0F172A` | Sidebar background, dark text |
| `--d-amber` | `#D97706` | Warnings, accents, highlights |
| `--d-surface` | `#FAFAF5` | Dashboard background |
| `--d-shell` | `#F8FAFC` | Card/container background |
| `--d-border` | `#E2E8F0` | Borders, dividers |
| `--d-text` | `#1E293B` | Primary text |
| `--d-muted` | `#64748B` | Secondary text, labels |
| `--d-white` | `#FFFFFF` | Cards, overlays |

### Shadows

| Variable | Value | Usage |
|---|---|---|
| `--shadow-sm` | `0 1px 3px rgba(0,0,0,0.06)` | Subtle card shadows |
| `--shadow-md` | `0 4px 12px rgba(0,0,0,0.08)` | Hover states, dropdowns |
| `--shadow-lg` | `0 12px 32px rgba(0,0,0,0.10)` | Modals, overlays |

### Fonts

| Font | Weights | Usage |
|---|---|---|
| **Sora** | 400, 600, 700, 800 | Headings, KPI values, emphasis |
| **DM Sans** | 400, 500, 600, 700 | Body text, labels, navigation |

Import:
```css
@import url("https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap");
```

### Toshi-Specific Token

| Variable | Value | Usage |
|---|---|---|
| `--toshi-navy` | `#0D1526` | Toshi header bar background |

---

## 3. CSS Architecture

### File Landscape

| File | Lines | Type | Role |
|---|---|---|---|
| `public/css/app.css` | 77,538 | Compiled | Tailwind v1 utilities + normalize.css |
| `public/css/admin.css` | 29,058 | Compiled | Admin panel styles (Laravel Mix output) |
| `public/css/dashboard-refresh.css` | 724 | Hand-written | **Primary theming layer** — custom properties + component classes |
| Toshi inline styles | 489 instances | Inline `style="..."` | All Toshi UI styling (needs extraction) |
| Landing page inline styles | ~200 instances | Inline `style="..."` | Hero, sections, footer |

### Dashboard Shell Classes

```css
.dashboard-shell          /* Base container: DM Sans, padding, border-radius, border */
.dashboard-shell--admin   /* Admin variant: green + blue radial gradients */
.dashboard-shell--teacher /* Teacher variant: amber + blue radial gradients */
.dashboard-shell--superadmin /* Superadmin variant: amber + blue (lighter) */
.dashboard-shell--student /* Student variant: blue + green */
.dashboard-shell--reception /* Reception variant: green + amber */
.dashboard-shell--library /* Library variant: blue + green (lighter) */
```

### KPI Card Classes

```css
.dashboard-kpi-card       /* Card: padding, border-radius, background, hover lift */
.dashboard-kpi-icon       /* Icon circle: w-14, colored bg, centered */
.dashboard-kpi-value      /* Value text: Sora 700, large */
.dashboard-kpi-label      /* Label text: DM Sans, muted color */
.dashboard-kpi-trend      /* Trend indicator: green up / amber down */
```

### Sidebar Classes

```css
.dashboard-themed-sidebar  /* DM Sans applied, dark bg (#0F172A), green active accent */
```

---

## 4. Rules for Writing CSS

### Rule 1: No Ad-Hoc Hex Values

**WRONG:**
```html
<div style="color: #1E6FD9;">  <!-- hardcoded hex -->
```

**RIGHT:**
```html
<div class="text-primary">     <!-- use Tailwind utility -->
<!-- OR in CSS: -->
<div style="color: var(--d-blue);">
```

### Rule 2: Reference Existing CSS Custom Properties

When adding new styles, always reference the token system:

```css
/* WRONG — new hardcoded color */
.new-card { background: #3B82F6; }

/* RIGHT — use existing token */
.new-card { background: var(--d-blue); }
```

### Rule 3: Tailwind v1 Compatibility

**Available in v1:**
- Flexbox: `flex`, `flex-col`, `items-center`, `justify-between`
- Grid: `grid`, `grid-cols-{1-12}`, `gap-{0-8}`
- Spacing: `p-{0-8}`, `m-{0-8}`, `px-{0-8}`, `py-{0-8}`
- Colors: `bg-blue-500`, `text-green-600`, `border-gray-300`
- Typography: `text-sm`, `text-lg`, `font-bold`, `font-semibold`
- Borders: `rounded`, `rounded-lg`, `rounded-xl`, `border`, `border-2`
- Shadows: `shadow`, `shadow-md`, `shadow-lg`
- Sizing: `w-full`, `h-screen`, `max-w-md`, `min-h-0`

**NOT available in v1:**
- Arbitrary values: `bg-[#1E6FD9]`, `w-[300px]`, `top-[50%]`
- `aspect-ratio`, `container` queries
- `@layer` directives with v3 syntax
- `tailwind.config.js` `content` array (v1 uses `purge`)
- Dark mode `dark:` variant (not in v1 default config)

### Rule 4: Blade Inline Style Extraction

When fixing inline styles in Blade templates:

1. **Check** if a CSS custom property already exists for the value
2. **Create** a new CSS class in `dashboard-refresh.css` if the pattern repeats
3. **Replace** inline `style="..."` with the class
4. **Track** the fix in the Design Gap Checklist below

---

## 5. Design Gap Checklist (Living Document)

> Update this checklist as each gap is fixed. Mark with ✅ when resolved.

### Font Gaps
- [ ] **DM Sans imported but not applied everywhere** — `body` rule added in dashboard-refresh.css, but some Blade templates override with Nunito or system fonts
- [ ] **Sora not applied to all headings** — KPI values use Sora, but page titles and section headers still use default

### Layout Gaps
- [ ] **Inconsistent card padding** — 3 different values found: `px-4 py-3`, `px-5 py-4`, `p-4`
- [ ] **Mixed grid systems** — some dashboards use Tailwind `grid`, others use custom flexbox, others use Bootstrap columns
- [ ] **Hardcoded px values** — 14+ instances of `style="width: 200px"` or similar that should use Tailwind scale

### Toshi UI Gaps
- [ ] **489 inline styles** in `agent-toshi.blade.php` — needs scoped stylesheet extraction
- [ ] **Toshi widget position** — inline positioning should be a CSS class
- [ ] **~200 inline styles** in landing page templates

### Consolidation Gaps
- [ ] **Button patterns** — 4 different button styles across admin.css + inline styles, need unification
- [ ] **Empty state wordings** — inconsistent across dashboard blades
- [ ] **Chart canvas height** — hardcoded, needs responsive height
- [ ] **Landing page v1 vs v2** — two versions exist, need canonical choice

### Completed
- [x] CSS custom properties defined in `:root` (dashboard-refresh.css)
- [x] Dashboard shell with role-specific gradient backgrounds
- [x] KPI card hover lift effect
- [x] Sidebar DM Sans application
- [x] Navbar scroll shrink (landing page)
- [x] Font import: Sora + DM Sans (replaced Nunito)

---

## 6. Tailwind v1 Config Reference

**Current config** (`tailwind.config.js`):
```js
module.exports = {
    purge: false,  // NOT tree-shaking
    theme: {
        extend: {
            // Custom extensions here
        },
    },
    variants: {},
    plugins: [],
}
```

### Adding Custom Utilities in v1

```js
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                'brand-blue': '#1E6FD9',
                'brand-green': '#22C55E',
            },
            fontFamily: {
                'sora': ['Sora', 'sans-serif'],
                'dm-sans': ['DM Sans', 'sans-serif'],
            },
        },
    },
}
```

Or add directly to CSS:
```css
@responsive {
    .text-brand-blue { color: #1E6FD9; }
}
```

---

## 7. Build Commands

```bash
# Development (watch mode)
npm run watch

# Production build
npm run production

# With PurgeCSS (production only)
npm run prod
```

**Note:** `purge: false` means all Tailwind classes are included in the compiled CSS regardless of usage. This is intentional — the project has dynamic class generation in Blade templates that PurgeCSS can't detect.
