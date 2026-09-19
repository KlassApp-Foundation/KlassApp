# KlassApp Design System

> **Canonical, production-validated.** Sourced from the Claude Design export (synced against `public/css/dashboard-refresh.css` and the Blade `x-*` components). Agents: read this file — do **not** invent tokens, badge colours, or table classes from memory. Brand SVGs live in `resources/assets/brand/` (canonical) and are mirrored under `public/images/`.

**KlassApp** is the school platform that operates in the tools educationists already use — WhatsApp, Google Drive, Slack, plus in-app **Toshi**. Surfaces this system covers:

1. **Staff dashboard** — one shell per role (`--admin`, `--teacher`, `--student`, `--accountant`, …). KPI fold, ledger tables, marks grids, forms.
2. **Toshi** — docked AI assistant (warm clay palette; suggestion chips; plan + confirmation cards).
3. **Manual onboarding wizard** — `manual-wizard-*` Livewire flow.

---

## Stack context (the Laravel app)

| Layer | Technology | Notes |
|---|---|---|
| Design CSS | `public/css/dashboard-refresh.css` | Source of truth for `ds-*` / `--d-*` tokens (~364 selectors) |
| CSS framework | Tailwind CSS **v4.3.3** | CSS-first `@theme` in `resources/css/tailwind.css`; **separate** from the `ds-*` bundle |
| Build | **Vite 8** + `laravel-vite-plugin` | `npm run dev` / `npm run build` — no Mix |
| Components | Anonymous Blade | `resources/views/components/*.blade.php` → `<x-*>` |
| Fonts | Sora (display) + DM Sans (body) | Google Fonts |
| Vue (app shell) | Vue 3.5.40 via `@vue/compat` MODE 2 | Options API |
| Toshi docking (≥1280px) | `packages/toshi-ui/resources/css/toshi-ui.css` | Published copy; overrides some KPI/table rules (“Pulse”) |

**Rule:** use `ds-*` classes for anything the design system covers. Tailwind is fine for layout glue (`flex`, `gap-4`, …) in Blade/Vue — but **primary CTAs, badges, ledger tables, and KPI tiles must use `ds-*`**, not invented Tailwind colour utilities.

Token CSS mirrors (for agents / prototyping): `resources/assets/design-system/tokens/`.

---

## Visual foundations

**Colour.** Two brand hues with strict jobs: **green acts** (`--d-accent` — every primary button; darkened to `#15803D` on 2026-09-18 for WCAG AA white-text contrast — measured 5.02:1) and **blue `#1E6FD9` informs** (links, sort arrows, table header rule, pagination, focus). Amber `#D97706` warns, red `#DC2626` destroys. Purple `#8B5CF6` is a KPI icon tint only. Hover always goes **darker**, never lighter.

**Action-button contrast (AA, verified 2026-09-19).** Every solid button variant must clear **4.5:1** with white text: `.ds-btn-primary` `#15803D` (5.02:1) · `.ds-btn-success` **`--d-success #15803D`** (5.02:1) · `.ds-btn-warning` **`--d-warning #B45309`** (5.02:1) · `.ds-btn-danger` `--d-red #DC2626` (4.83:1, already compliant). Hovers darken further (`--d-success-dk #166534`, `--d-warning-dk #92400E`). Do **not** reintroduce the light `#22C55E` / `#D97706` / `#16a34a` / `#b45309` fills on white-on-solid buttons — they measured 2.28:1 and 3.19:1.

**Surfaces.** Canvas is warm parchment `#FAFAF5`. Cards are pure white. Dark shells use `#0F172A` / `#1E293B`. Exactly one gradient: the admin LIVE badge (`#15803d → #22C55E → #4ade80`) with an animated sheen.

**Type.** **Sora** for display (dashboard titles, page heads, section heads, card titles, KPI values, uppercase table headers). **DM Sans** for body, labels, help. Ledger figures: `font-variant-numeric: tabular-nums`.

**Spacing (literal, not a strict 4/8 grid).** Shell padding 24 · card 20 (sm 14 / lg 28) · grid gutter 16 · ledger cells 18×20 / compact 12×16 · button padding 8×18.

**Corners.** 8 buttons/inputs · 10 banners · 12 table wrappers/icon chips · 14 cards/KPI · 16 Toshi panel · 18 topfold · 20 shell · 999 pills.

**Shadows.** Default elevation is a **hairline ring**: `0 0 0 1px var(--d-border)`. `md` / `lg` add soft drop shadows; only floating Toshi goes to `0 8px 40px rgba(0,0,0,.12)`. Focus: 3px `rgba(30,111,217,.25)` on inputs; 2px blue outline at 2px offset on buttons.

**Motion.** Hover `translateY(-1px)` / 0.2s; press `scale(0.97)` / 0.15s. Infinite loops: LIVE badge sheen, LIVE pulsing dot, loading-dot bounce, save-indicator `d-pulse`, Toshi plan-card `toshi-spin`. Under `@media (prefers-reduced-motion: reduce)` all five are disabled (sheen pseudo removed; others static / `animation: none`).

**Layout.** Sidebar ~252px; content under ~58px top bar. KPIs: `repeat(auto-fill, minmax(220px, 1fr))`. Tables scroll in `.ds-table-wrap`; restack as cards ≤767px (`data-label` on every `<td>`). Touch target token: `--d-touch-target-min: 44px` (baked into `.ds-btn`).

### Verified `:root` tokens (spot-checked 2026-09-13 against `dashboard-refresh.css`)

| Token | Value |
|---|---|
| `--d-blue` | `#1E6FD9` |
| `--d-green` | `#22C55E` (non-text accents, success) |
| `--d-accent` | `#15803D` (primary CTA — AA, **changed 2026-09-18**) |
| `--d-accent-dk` | `#166534` (hover) |
| `--d-success` / `--d-success-dk` | `#15803D` / `#166534` (AA success button — **added 2026-09-19**) |
| `--d-warning` / `--d-warning-dk` | `#B45309` / `#92400E` (AA warning button — **added 2026-09-19**) |
| `--d-amber` | `#D97706` (warn accents; not for white text) |
| `--d-red` | `#DC2626` (danger button — 4.83:1 with white, compliant) |
| `--d-canvas` / `--d-surface` | `#FAFAF5` |
| `--d-white` | `#FFFFFF` |
| `--d-text` | `#1E293B` |
| `--d-text-secondary` | `#64748B` |
| `--d-muted` | `#94A3B8` |
| `--d-dark` | `#0F172A` |
| `--d-dark-surface` | `#1E293B` |
| `--d-border` | `#E2E8F0` |
| `--d-border-strong` | `#CBD5E1` |
| `--d-touch-target-min` | `44px` |

Toshi scope (`[data-toshi-root]`): clay accent `#c96442`, warm bg `#f5f4ed`, border `#e8e6dc` — deliberately different from app chrome. **Toshi plan-widget action blue (AA, verified 2026-09-19):** execute button `#0369A1` with white text (**5.93:1**), hover `#075985`; blue text on the `#BAE6FD` tint (plan count, active step) also `#075985` (**5.70:1**). **Do not reintroduce `#0284C7`** — it measured 4.10:1 on white and 3.09:1 on `#BAE6FD`.

---

## Content fundamentals

- **Sentence case** everywhere. Title Case only on short button labels; ALL CAPS for table headers, sidebar group labels, LIVE badge.
- **Actions are verbs with objects**: *Record Payment · Save Changes · Publish to parents*. Cancel = "Cancel"; back = "← Back".
- **Money:** `UGX 450,000` (currency code first). Counts comma-grouped.
- **Empty states** name the gap and the next action — never "No data available."
- **Statuses** = single words in a badge: Paid · Unpaid · Part paid · Pending · Approved · Rejected · Active.
- **Emoji:** only inside Toshi chips/plan steps — never product chrome.
- Em dash `—` = empty KPI value (not zero). Middle dot `·` separates metadata.

---

## Responsiveness (real media queries)

| Query | Behaviour |
|---|---|
| `max-width: 767px` | Ledger tables restack as cards (`data-label` required) |
| `max-width: 768px` | Ledger cell padding / pagination tighten |
| `max-width: 640px` | Toshi full-screen; wizard nav restacks |
| `min-width: 640px` | Wizard plan cards → 3 columns |
| `min-width: 1280px` | Fixed-height three-column shell; Toshi docks at 380px (`toshi-ui.css`) |

`(hover: none) and (pointer: coarse)` forces 44px targets inside ledger/marks grids. Nav hover states live under `(hover: hover) and (pointer: fine)` only.

### Pulse overrides (`toshi-ui.css` when published)

Where Toshi CSS loads after dashboard-refresh: KPI hover can be `-2px`, KPI values can render green, sticky thead gets blur. Base rules above describe `dashboard-refresh.css`; check which layer applies before matching a screen.

---

## Brand assets

Canonical copies: **`resources/assets/brand/`** (see `README.md` + `FAVICONS.md` there). Served mirrors: **`public/images/`**.

| File | What it is |
|---|---|
| `klassapp-icon.svg` | Icon only (K + mortarboard). Also aliased as `klassapp-logo.svg` / `klassapp-logo-primary.svg` |
| `klassapp-icon-reversed.svg` | White icon for dark surfaces |
| `klassapp-horizontal-dark.svg` | Horizontal lockup on dark plate → also `klassapp-logo-dark.svg` |
| `klassapp-horizontal-light.svg` | Transparent light-surface lockup |
| `klassapp-horizontal-transparent.svg` | Dark-surface lockup without plate (**dark only** — “Klass” is `#FEFEFE`) |
| `klassapp-stacked.svg` | **Misnamed — horizontal lockup** on light social canvas (OG/Twitter). Kept as `klassapp-logo-stacked.svg` for meta images — **do not rename without checking references** |
| `klassapp-stacked-light.svg` / `-dark.svg` | Genuinely stacked lockups (derived; vertical gap = 20% of icon height — open judgement call) |
| `whatsapp.svg` / `slack.svg` / `google-drive.svg` | Official third-party marks — never recolour |

**Wordmark colours (from official files):** light → Klass `#0E2347` / App `#22B560`; dark → Klass `#FEFEFE` / App `#26B45F`.

Blade: `<x-brand.whatsapp />`, `<x-brand.slack />`, `<x-brand.google-drive />`.

Favicons: regenerate rasters from `klassapp-icon.svg` only — see `resources/assets/brand/FAVICONS.md`.

---

## Blade components

All styling uses the `ds-*` namespace in `dashboard-refresh.css`.

### `<x-button />`

```blade
<x-button variant="primary" size="md" href="/url" type="button">Save Changes</x-button>
```

- **variant:** `primary` (default, **green**) \| `success` \| `danger` \| `warning` \| `outline` \| `ghost`
- **size:** `sm` \| `md` (default) \| `lg`
- **href:** renders `<a>`; disabled → `aria-disabled` + `tabindex="-1"`
- Classes: `.ds-btn .ds-btn-{variant} .ds-btn-{size}`
- **`.ds-btn-md` has a real rule** (padding 8×18, 0.85rem) — restates the base size so the scale is explicit. Every button has `min-height: 44px`.

Primary is green, not blue. Blue is informational only.

---

### `<x-card />`

```blade
<x-card title="Optional Title" padding="default" shadow="sm" :hover="true">…</x-card>
```

- **padding:** `default` \| `sm` \| `none` \| `lg`
- **shadow:** `sm` (default) \| `md` \| `lg` \| `none`
- **hover:** boolean → `.ds-card-hover` (1px lift when the card is a link)
- Classes: `.ds-card .ds-card-padding-* .ds-card-shadow-*`

---

### `<x-badge />`

```blade
<x-badge variant="paid" size="sm">Paid</x-badge>
```

- **variant:** `pending` \| `approved` \| `rejected` \| `paid` \| `unpaid` \| `active` \| `inactive` \| `warning` \| `info` (unknown → `info`)
- **size:** `sm` (default) \| `md`

**Status colour map (verified against CSS — not the old Tailwind-blue docs):**

| Variants | Background | Text |
|---|---|---|
| `pending`, `info` | `#f0eee6` | `--d-text-secondary` |
| `approved`, `paid`, `active` | `#e8f5e9` | `#2e7d32` |
| `rejected`, `unpaid` | `#fbe9e7` | `--d-red` |
| `warning` | `#fff8e1` | `--d-amber` |
| `inactive` | `#f0eee6` | `--d-muted` |

Sentence case labels only. For a coloured dot + text, use `.ds-dot .ds-dot-green` (etc.), not a badge.

---

### `<x-table />`

```blade
<x-table :headers="['#', 'Student', 'Amount', 'Status']" :striped="true" density="comfortable">
    <tr>
        <td data-label="#">1</td>
        <td data-label="Student">Nakato Sarah</td>
        <td class="dt-cell-num" data-label="Amount">UGX 450,000</td>
        <td class="dt-cell-badge" data-label="Status"><x-badge variant="paid">Paid</x-badge></td>
    </tr>
</x-table>
```

- **Emits `.ds-table-ledger`**, not `.ds-table`. Header rule = 2px `--d-blue` underline; row hover = 4% green wash + inset 3px green rail (intrinsic to `.ds-table-ledger` — **no `hover` prop**).
- **Props:** `headers`, `striped` (boolean → `.ds-table-striped`), `density` (`comfortable` \| `compact`), `selectable`, `sortable`, `cardMobile` (default true → `.ds-table-card-mobile`).
- Put `data-label` on every `<td>` for ≤767px card restack.
- Numbers: `.dt-cell-num`. Status pills: wrap in `.dt-cell-badge`.
- Split active/archived shells: raw `.ds-table-wrap` + `.ds-table-ledger` (or `.ds-table` where an older view still uses it — both exist in CSS).

---

### `<x-form-group />`

```blade
<x-form-group label="Full Name" name="name" required :error="$errors->first('name')" />
<x-form-group label="Class" name="class_id" type="select" :options="$options" />
```

- **type:** `text` (default) \| `email` \| `select` \| `textarea` \| `number` \| `date`
- Classes: `.ds-form-group` / `.ds-form-label` / `.ds-form-input` (+ `-error`, `-select`, `-textarea`) / `.ds-form-help` / `.ds-form-error`

---

### `<x-ds-kpi-card />`

```blade
<x-ds-kpi-card icon="users" value="42" label="Students" color="blue" :link="url('/admin/students')" />
```

- **color:** `blue` \| `green` \| `amber` \| `red` \| `purple` (10% tint behind icon chip)
- Empty value: em dash `—` (default)

---

### Brand marks

```blade
<x-brand.whatsapp class="w-5 h-5" />
<x-brand.slack class="w-5 h-5" />
<x-brand.google-drive class="w-5 h-5" />
```

---

### Raw utility classes

| Class | Purpose |
|---|---|
| `.ds-page-head` / `-title` / `-sub` | Page header row |
| `.ds-card-title` | Title inside a card |
| `.ds-empty-state` | Empty list treatment |
| `.ds-dot` + `.ds-dot-{green\|blue\|amber\|red\|gray}` | Status dot |
| `.ds-save-indicator` | Unsaved / saving chrome |

---

## Architecture rules

1. **`dashboard-refresh.css` tokens are SoT** for brand colour/type/elevation — not Tailwind theme guesses.
2. **Primary CTA is green** (`--d-accent`). Blue buttons are wrong.
3. **Table component → `.ds-table-ledger`.** Don't document or generate `.ds-table` as the component output.
4. **Badge colours are warm neutrals / Material-ish greens** (`#f0eee6`, `#e8f5e9`), not Tailwind blue/green utility pairs.
5. Prefer Blade `<x-*>` over duplicating class strings; when raw HTML is required, copy the class contract above.
6. Anonymous Blade only — no `app/View/Components/` classes needed for these primitives.
7. Never recolour, rotate, outline, or redraw the logo or third-party marks.

---

## Migration pattern

1. Headings → `.ds-page-head-title`
2. Actions → `<x-button>`
3. Panels → `<x-card>`
4. Tables → `<x-table>` or `.ds-table-wrap` + `.ds-table-ledger`
5. Status text → `<x-badge>`
6. Inputs → `<x-form-group>`
7. Legacy `.custom-green` / `.blue-bg` / `.tw-form-*` → `ds-*` equivalents

---

## Onboarding constants (canonical PHP — replace any Claude Design / kit placeholders)

Do **not** use inferred size buckets or category labels from the Claude Design export React kit. Read these from PHP:

### `OnboardingStepsService::STUDENT_SIZE_OPTIONS`
1. `Under 100 students`
2. `100-300 students`
3. `300-500 students`
4. `500+ students`

### `SchoolCategorySeeder::CATEGORIES` (key → label)
| Key | Label |
|---|---|
| `nursery` | Nursery only |
| `primary` | Primary |
| `primary_nursery` | Primary + Nursery |
| `o_level` | O-Level |
| `o_a_level` | O-Level + A-Level |

### `OnboardingStepsService::ALL_STEPS` order (keys)
`school_name` → `student_size` → `country` → `curriculum` → `school_category` → `emis` → `uneb_center` → `academic_year` → `standards` → `subjects` → `teachers` → `students` → `terms` → `fees` → `whatsapp_verify` → `plan_selection`

Note: wizard UI may show a **review** screen after these; `review` is **not** a key in `ALL_STEPS`. `emis` / `uneb_center` are conditionally filtered via `applicableSteps(School)`.

---

## Open items / caveats

- Official purpose-built **light horizontal** lockup still preferred over the extract from `klassapp-stacked.svg` (`klassapp-horizontal-light.svg` is the current mechanical extract).
- **Stacked vertical gap** (20% of icon height) needs design sign-off.
- Narrow-width **sidebar** behaviour is not fully specified in CSS — layout Blade owns it below 1280px.
- No photography/illustration system — empty states with actions, not stock art.
- Icons: Heroicons v1 outline, 24×24, stroke 2, `currentColor` (KPI glyphs inline in `<x-ds-kpi-card>`).
- Reduced-motion coverage in `dashboard-refresh.css` is complete for all five infinite loops in that file (LIVE sheen/dot, loading dots, save `d-pulse`, Toshi `toshi-spin`).

---

## Verification discipline

Before trusting a claim in this file (or changing one), spot-check:

```bash
# Tokens
rg -n '--d-accent:|--d-green:|--d-blue:' public/css/dashboard-refresh.css | head
# Badges
rg -n 'ds-badge-paid|ds-badge-pending|ds-badge-active' public/css/dashboard-refresh.css
# Table contract
rg -n 'ds-table-ledger|ds-table-striped|ds-btn-md' public/css/dashboard-refresh.css resources/views/components/table.blade.php
```

Last full sync vs Claude Design export + production CSS: **2026-09-13**.
