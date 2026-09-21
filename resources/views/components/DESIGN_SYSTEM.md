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
{{-- cosmetic tint only --}}
<x-ds-kpi-card icon="users" value="42" label="Students" color="blue" :link="url('/admin/students')" />

{{-- semantic tone + trend: use for anything that carries meaning --}}
<x-ds-kpi-card icon="users" :value="$kpis['arrears_label']" label="Students in arrears"
    tone="negative" :direction="$kpis['arrears_direction']" :invert-direction="true"
    :delta="$kpis['arrears_delta_label']" :hint="$kpis['arrears_hint']" />

{{-- real sparkline (e.g. a Laravel Trend series) --}}
<x-ds-kpi-card icon="dollar" value="UGX 500K" label="Collected this term"
    tone="positive" :spark="$kpis['collected_spark']" spark-label="Weekly collections this term" />
```

| Prop | Default | Meaning |
|---|---|---|
| `icon` | `''` | inline Heroicons v1 glyph: `users` · `classes`/`door` · `exam`/`calendar` · `whatsapp`/`message` · `book`/`library` · `bell`/`notice` · `dollar`/`money` · `check`/`tasks` |
| `value` | `—` | the figure (em dash when empty) |
| `label` | `''` | caption |
| `link` | `''` | renders the card as an `<a>` (never assemble the href by string concatenation — see `knowledge.md` on the `%22https:…%22` 404) |
| `color` | `blue` | **legacy cosmetic tint only**: `blue` \| `green` \| `amber` \| `red` \| `purple` (10% chip tint) |
| `tone` | `null` | **semantic tone — the component owns the colour mapping** (table below) |
| `direction` | `null` | `up` \| `down` \| `flat` → trend indicator; the arrow always shows the **real** movement |
| `invertDirection` | `false` | flips the trend's **sentiment colour only** (metrics where down is good) |
| `delta` / `hint` | `null` | optional text beside the arrow (e.g. `-1`, `vs term start`) |
| `spark` | `[]` | numbers → inline SVG sparkline, drawn in the sentiment colour |
| `sparkLabel` | `null` | accessible label for the sparkline |

**Tone → token mapping. Never hand-pick these colours at the call site** — the point of `tone` is that a stat cannot read "warning" on one page and plain on another:

| `tone` | Rendered colour | Token |
|---|---|---|
| `neutral` | `#64748B` | `--d-text-secondary` |
| `positive` | `#15803D` | `--d-success` |
| `negative` | `#DC2626` | `--d-red` |
| `warning` | `#B45309` | `--d-warning` |
| `info` | `#1E6FD9` | `--d-blue` |

`tone` tints the icon chip **and** the value/indicator colour. `color` remains for purely decorative tints (e.g. the purple KPI tint).

**`direction` + `invertDirection` semantics — two deliberately separate things:**
- `tone` = the metric's **nature** (what the card is about).
- The trend indicator's colour = what the **movement means**: `up` → positive, `down` → negative, `flat` → neutral — **unless** `invertDirection` is set, which swaps the up/down sentiment. The arrow keeps showing the true movement either way.
- Worked example (fee arrears, shipped): *Students in arrears* is `tone="negative"` with `direction="down"` + `invert-direction` → the chip stays red (negative nature) while the falling trend renders **green** `--d-success`, because fewer students in arrears is good.
- With only `direction` (no `tone`) the sentiment is derived from the movement; with neither, the card renders exactly like the legacy `color` version.

---

### `<x-chart />`

The **only** way charts are created now — pages must not call `new Chart(...)` inline any more. One shared wrapper around **Chart.js v4** (`public/js/chart.umd.min.js`; the v2 `Chart.min.js` is gone).

```blade
<x-chart type="line" :height="180" aria-label="Fee collection trend"
         empty-message="No fee collections recorded yet"
         :labels="$points->pluck('label')->all()"
         :datasets="[[ 'label' => 'Fee Collection', 'data' => $points->pluck('amount')->all(),
                       'borderColor' => '#22C55E', 'tension' => 0.3, 'fill' => true ]]"
         :options="['plugins' => ['tooltip' => ['mode' => 'index']]]" />
```

| Prop | Default | Notes |
|---|---|---|
| `id` | auto uuid | also the handle key: `window.__dsCharts[id]` |
| `type` | `line` | `line` \| `bar` \| `doughnut` \| `pie` (verified shipped usages) |
| `labels` / `datasets` | `[]` | Chart.js v4 data (JSON-safe) |
| `options` | `[]` | v4 options, deep-merged over KlassApp defaults (DM Sans ticks, `#F1F5F9` grid, slate tooltip) |
| `optionsJs` | `null` | JS object literal merged at init — **the only place callbacks can live**, since JSON cannot carry functions |
| `height` | `260` | shell height in px |
| `ariaLabel` / `emptyMessage` | `null` / `No data yet` | accessibility + a real empty state instead of a blank canvas |
| `centerValue` | `null` | value drawn in the middle of a doughnut |

Operational notes — each of these was a real bug, do not "simplify" them away:
- Chart.js loads **once** (`@once @push('scripts')`), so pages without a chart do not pay for it.
- The config rides on the shell element's **data attributes**: the app mounts `#app` with Vue, which replaces server-rendered nodes **and drops in-DOM `<script>` tags** (a `<script type="application/json">` config silently disappears → blank canvas).
- Boot is idempotent **per live node** (`Chart.getChart(canvas)`), re-checked on load, on timeouts, on a short settle interval and via `MutationObserver`.
- Dynamic/tabbed charts update the shared instance through `window.__dsCharts[id]` (see the report-cards EOT card).

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

## Sidebar & navigation (menu as data)

**There is no per-role menu file any more.** Every role's sidebar is data in [`config/navigation.php`](../../../config/navigation.php) rendered by one shared partial. Do **not** look for — or re-create — `layouts/<role>/menu.blade.php`; those 10 files were retired (PR #734) along with their per-role `segment('2')` active-state helpers.

```
config/navigation.php                                   ← SOURCE OF TRUTH (roles → items/groups)
resources/views/layouts/partials/sidebar-menu.blade.php        ← the renderer (flat + grouped + submenu + footer)
resources/views/layouts/partials/sidebar-menu-item.blade.php   ← one item
resources/views/components/icons/sidebar-group.blade.php       ← admin group-header glyphs
resources/views/layouts/<role>/sidebar.blade.php               ← shell only:
    @include('layouts.partials.sidebar-menu', ['role' => 'admin'])
```

A role is: `layout` (`flat` \| `grouped`), `prefix` (URL first segment), `item_class`, `active_class`, `items` and/or `groups`, and an optional `footer` (the admin sidebar's bottom *Help & Docs* link).

| Item key | Meaning |
|---|---|
| `label`, `icon` | menu text and `<x-icons.sidebar name="…">` glyph |
| `route` **or** `url` | named route, or a path for `url()`; `hash` appends `#anchor` |
| `active` | extra URL second-segments that should also highlight this item (transcribed from the retired helpers) |
| `paths` | explicit `request()->is()` patterns (used where the old code matched `segment(3)`) |
| `class` / `a_class` / `title` / `testid` | overrides for special rows |
| `condition` | `class_teacher` → item rendered only for class teachers |
| `children` + `submenu` | nested collapsible submenu (accountant *Payroll*) |

Rules:
- **Active state comes from route/path patterns**, never a hard-coded URL segment index — it no longer breaks when a URL gains a level. Adding a page is a config edit; the renderer does not change.
- The role's `item_class` carries the base padding + hover treatment (`py-3 px-3 dashboard-menu-item`, `hover:bg-green-100`, `hover:font-semibold`). An item-level `class` **replaces** the role default — that is how the `text-xs` sub-row and its `pl-6` indent are expressed.
- Admin groups keep their collapse state in `localStorage` (`sidebar-group-<key>`) with a hover preview on fine-pointer devices; the behaviour lives in **`x-data` methods** (`toggle()`, `hoverOn()`, `hoverOff()`). A multi-statement `x-on:click` string is re-parsed by Alpine as an expression and throws `Unexpected token ';'` — do not put logic back into the attribute.
- Role shells keep their own wrapper (`#admin-sidebar` / `#res_sidebar`, desktop + mobile copies of the same include).

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
# KPI card semantic props + tone tokens
rg -n "toneMap = \[|invertDirection|'spark'" resources/views/components/ds-kpi-card.blade.php
rg -n -- '--d-success:|--d-warning:|--d-text-secondary:' public/css/dashboard-refresh.css | head
# Sidebar is data-driven (no per-role menu files should exist)
rg -n "'roles' =>" config/navigation.php | head
ls resources/views/layouts/*/menu.blade.php 2>/dev/null || echo "OK: per-role menu files retired"
# Charts go through the shared component (no inline new Chart)
rg -n "new Chart\(" resources/views/ -g '!*.md' ; echo "(no matches = good)"
rg -n "chart.umd.min.js|data-chart-config" resources/views/components/chart.blade.php | head
```

Last full sync vs Claude Design export + production CSS: **2026-09-13**. Append-only verified additions **2026-09-21** (checked against the shipped code, *not* from the design export): `<x-ds-kpi-card>` semantic props (`tone`/`direction`/`invertDirection`/`delta`/`hint`/`spark`), `<x-chart>` (Chart.js v4), and the config-driven sidebar (`config/navigation.php`).
