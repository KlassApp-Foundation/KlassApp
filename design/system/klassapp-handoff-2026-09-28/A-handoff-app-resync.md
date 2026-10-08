# Handoff — app component library resync with the landing (Task A)

**Base:** `KlassApp-Foundation/KlassApp@dbe68419`, read from the repo on 2026-09-28.

**Not used:** the design inventory (69 screenshots and its catalog) didn't reach this project. Nothing here was checked against screenshots. Re-check phases 3 and 5 against the inventory before merging.

**How ratios were worked out:** every contrast ratio below is **computed from the hex values** using the WCAG relative-luminance formula. None were measured in a browser. Backgrounds are white `#FFFFFF` and canvas `#FAFAF5`.

---

## 0. Findings

### 0.1 Contrast of every `--d-*` colour used for text
| Token | Hex | On white | On canvas | As text |
|---|---|---|---|---|
| `--d-text` | #1E293B | 14.6 | 14.0 | ✅ |
| `--d-text-secondary` | #64748B | 4.76 | 4.55 | ✅ (just) |
| `--d-muted` | #94A3B8 | **2.56** | **2.45** | ❌ Not for text |
| `--d-accent` / `--d-success` | #15803D | 5.02 | 4.80 | ✅ |
| `--d-blue` | #1E6FD9 | 4.85 | 4.65 | ✅ |
| `--d-warning` | #B45309 | 5.02 | 4.80 | ✅ |
| `--d-red` | #DC2626 | 4.83 | 4.62 | ✅ |
| `--d-amber` | #D97706 | **3.19** | 3.05 | ❌ Icons/fills only |
| `--d-green` | #22C55E | **2.28** | 2.18 | ❌ Dark sidebar and fills only |
| `--d-accent-lt` | #4ADE80 | **1.9** | — | ❌ |
| `--toshi-accent` (clay) | #c96442 | **4.08** | 3.9 | ❌ At body size (passes 3:1 for large text only) |

**Proposed app rule (the same as the landing):**
- Body, help, meta and caption text use `--d-text-secondary`, never `--d-muted`.
- `--d-muted` stays for placeholders, disabled states, borders, dots and decoration.
- `--d-amber`, `--d-green` and `--d-accent-lt` are never text on light backgrounds.

**Where it breaks today:** `color: var(--d-muted)` appears **at least 25 times** in `dashboard-refresh.css` (the search was capped, so the real number may be higher). Examples: L166, 201, 287, 464 (`.ds-badge-inactive`), 743, 1047, 1204, 1673, 1787, 1836, 1879, 1922, 1963, 1987, 2002, 2097, 2102 (`.dt-pagination-info`), 2160, 2175, 2235, 2275, 2292, 2301.

Hard-coded `#94A3B8`/`#94a3b8` also appears at L2432, 2439, 3212 and 3216.

L2967 is a real bug: `var(--d-muted, #64748B)`. The fallback is the AA grey, but the token resolves to the failing one.

`.ds-form-input::placeholder` (L542) correctly keeps `--d-muted`.

### 0.2 Badge tints fail too (a new finding)
| Class | Text on fill | Ratio |
|---|---|---|
| `.dt-badge-paid` / `-active` | #15803D on #F0FDF4 | 4.87 ✅ |
| `.dt-badge-partial` | #B45309 on #FFFBEB | 4.9 ✅ |
| `.dt-badge-present` | #1D4ED8 on #EFF6FF | 6.2 ✅ |
| `.dt-badge-unpaid` / `-absent` | #DC2626 on #FEF2F2 | **4.42** ❌ |
| `.dt-badge-pending` / `-inactive` | #64748B on #F1F5F9 | **4.34** ❌ |

**Fixes:**
- Red text → `#B91C1C` (5.9).
- Grey text → `#475569` (6.9).

Both are folded into the phase 1 tokens.

### 0.3 `--brand-green-text` vs `--d-accent`
- **They're equal:** both are `#15803D`, and so is `--d-success`. `--brand-green-text` is defined in `resources/css/landing-preview.css:26`, and `--d-accent`/`--d-success` in `public/css/dashboard-refresh.css:25,30`.
- **Recommendation: don't alias them with `var()`.** The landing doesn't load `dashboard-refresh.css`, and the app doesn't load `landing-preview.css`, so a cross-file `var()` resolves to nothing.
  - Keep three names for three roles: marketing text, app accent, and app status.
  - Pin their equality with **one contract test** that parses both files and asserts that `--brand-green-text == --d-accent == --d-success`.
  - Add a comment next to each declaration naming the others.
  - A drift then fails CI, instead of relying on someone remembering.

### 0.4 Debox: the app stays boxed (**agree** with your leaning)
The landing removed boxes because each section carries one idea on a lot of whitespace. Dashboards are the opposite.
1. **Scanning dense data needs edges.** Tables of 20–50 rows, KPI grids and forms rely on the box to show where one record's information ends. Hairlines alone at 28px gaps would push a typical results table past one screen.
2. **Selection and state live on the box:** selected rows, hover rails, inline errors and focus rings all need a surface to sit on.
3. **The shell is already cream** (`--d-canvas #FAFAF5`), while cards are white. That contrast *is* the app's depth system, and it costs nothing.
4. **Toshi's split layout** already sits in its own column with its own chrome. Deboxing the main column would blur that boundary.

**Borrow from the landing, without deboxing:**
- Table row dividers become the landing hairline `rgba(120,95,60,.14)` inside `.ds-table-ledger`.
- Page sections get 28px gaps.
- **Empty states and onboarding-type pages** (low density, one idea each) *may* float on the canvas like the landing.

### 0.5 Empty state → `<x-empty-state>` (**recommended**)
It should become a component for the same reason `<x-chart>` did: the copy rule ("name what's missing, plus the next action") is only enforceable when a component can refuse to render without it. Today at least 19 bare "No records found" strings sit next to `.ds-empty-state`, and nothing stops more.
- **Props:**
  - `title`: required. It throws in local/testing, as `emptyMessage` does.
  - `description` and `icon`: optional; `icon` comes from the Icon set, never an emoji.
  - `size`: `default|compact`.
  - Up to 2 actions, through the `actions` slot.
- **Contract test `EmptyStateComponentTest`:**
  - It throws without `title`.
  - It renders `.ds-empty-state.v2`.
  - The description uses `--d-text-secondary`.
  - It has at most 2 `.ds-btn` elements, and at most one primary.
  - It has `role="status"`.
  - A **blocklist** of generic titles, checked case-insensitively: "No records found", "No data available", "No data found", "No record found".

---

## Phase 1 — Tokens, buttons and forms
**Add to `resources/assets/design-system/tokens/colors.css`** (and the `dashboard-refresh.css :root` mirror):
```css
/* Table */
--d-row-selected: #E8F0FE;   /* was literal: .ds-table-ledger tr.dt-selected (L2022) */
--d-row-alt:      #F8F5F0;   /* was literal: tr.dt-row-alt (L2026), .ds-grid-marks even rows (L2296) */
--d-row-rule:     rgba(120, 95, 60, 0.14); /* landing hairline, for row dividers */
/* Badges: bg / fg pairs, all ≥ 4.5:1 */
--d-badge-success-bg: #F0FDF4; --d-badge-success-fg: #15803D; /* 4.87 */
--d-badge-warning-bg: #FFFBEB; --d-badge-warning-fg: #B45309; /* 4.9  */
--d-badge-danger-bg:  #FEF2F2; --d-badge-danger-fg:  #B91C1C; /* 5.9 — was #DC2626 at 4.42 */
--d-badge-neutral-bg: #F1F5F9; --d-badge-neutral-fg: #475569; /* 6.9 — was #64748B at 4.34 */
--d-badge-info-bg:    #EFF6FF; --d-badge-info-fg:    #1D4ED8; /* 6.2  */
```
**Then:**
- **Tables and badges:** point `.dt-selected`, `.dt-row-alt`, `.ds-grid-marks` even rows, `.dt-badge-*` and `.ds-badge-*` at these tokens.
- **Muted text:** replace every text use of `--d-muted` and the `#94A3B8` literals (the §0.1 list) with `--d-text-secondary`, and fix L2967's fallback.
- **Buttons and forms:** ship the approved `controls-v2` changes (inputs `min-height:44px`, help text `--d-text-secondary`, disabled tokens, focus ring in green, not clay).

**Acceptance:**
- A new `TableTokenContractTest` asserts that none of `#E8F0FE`, `#F8F5F0`, `#FEF2F2` or `#F1F5F9` remain as literals in the `.ds-table-ledger`/`.dt-badge`/`.ds-badge` rules.
- `grep -n "color: var(--d-muted)" public/css/dashboard-refresh.css` returns only placeholder, disabled or decorative rules. List the remaining lines in the PR description.
- The `--brand-green-text == --d-accent == --d-success` contract test (§0.3) passes.
- The existing `TableAndButtonClassContractTest`, `FormLabelTypeScaleTest` and `ChartComponentContractTest` pass.
- Every input, select and textarea renders at least 44px tall, measured with `getBoundingClientRect` (box-sizing: the Tailwind preflight is present in the app).
- Screenshots at 390 and 1280: a form page, a ledger table with a selected row, and all five badge kinds.

## Phase 2 — Alert consolidation (behind the existing events)
**What exists:** `layouts/app.blade.php:89–108` and `layouts/superadmin-app.blade.php:69–88` both listen for `window` `alert` and `registeralert` and call `toastr[type](message, title)`.

**Keep that contract** (`{type, message, title}`) and swap only the renderer:
1. Add a `ds-toast` renderer (`.ds-toast`, from the dialogs review) and point both listeners at it. Map `type` values `success|error|warning|info` to the toast variants. Delete the `toastr.options` blocks.
2. Add `window.dsConfirm({title, body, confirmLabel, danger}) → Promise<boolean>`, rendering `.ds-dialog`. Cancel is the default and gets initial focus.
3. SiteAdmin: the `DispatchesAlerts` trait replaces `livewire-alert` and dispatches `alert` (decided earlier).
4. Migrate swal v1 page by page to `dsConfirm` plus an `alert` event. Start with pages that load the unpinned `unpkg.com/sweetalert` copy (`admin/bulletins`).

**Acceptance:**
- `toastr` isn't referenced in either layout.
- Firing `window.dispatchEvent(new CustomEvent('alert',{detail:{type:'success',message:'Saved'}}))` renders one `.ds-toast` with `role="status"` (`role="alert"` for errors). It auto-dismisses after 5s, and never auto-dismisses errors.
- `registeralert` behaves identically.
- A Livewire test confirms the trait dispatches `alert` with the same payload shape.
- Each migrated swal page makes one confirm, then one toast: no "Cancelled" popup and no second success popup.
- The Toshi split layout, the mobile menu (opens exactly once) and the sidebar footer are unaffected.

## Phase 3 — Tables, in batches
**Prerequisite:** phase 1's tokens are merged.
- **Scope:** raw `<table>` elements, 37 in `admin/` (a full scan) and about 110 app-wide (the earlier figure, not re-counted here).
- **Batches:** about 10 tables per PR, grouped by module (approvals/fees, students/members, marks/reports, library/stock, everything else).
- **Excluded:** print and PDF templates (`buspass/print`, `id-card/*print*`, report templates).

**Each table gets:**
- `<x-table>` / `.ds-table-ledger`;
- row dividers in `--d-row-rule`;
- 44px row actions through `<x-button size="sm">`;
- the mobile card layout at 640px and below;
- empty rows through `<x-empty-state>` (after phase 4; until then `.ds-empty-state.v2`).

**Acceptance for each batch:**
- The count of raw `<table>` in the batch's files is 0 (except print).
- Screenshots at 390 and 1280 of each converted page.
- Sort, filter and pagination still round-trip through the query string.
- No horizontal scroll at 390px.

## Phase 4 — Empty and loading states
1. `resources/views/components/empty-state.blade.php` plus `EmptyStateComponentTest` (§0.5).
2. The shared `.ds-skel` skeleton is the only loading pattern. `x-table`'s skeleton rows and `x-chart`'s loading state both use it.
3. Replace the at least 19 bare empty strings, writing each one's copy per screen, and the accountant dashboard's hand-made empty states (L58/95/127).

**Acceptance:**
- The contract test passes.
- `grep -rniE "No (records?|data) (found|available)" resources/views` finds matches only in email and print templates.
- `.ds-skel` animations stop under `prefers-reduced-motion`.
- Screenshots of three converted screens.

## Phase 5 — Sidebar footers for teacher, parent and student
**What's live:**
- **Admin** (`layouts/admin/sidebar.blade.php`) uses `hidden md:flex md:flex-col h-full`, a `flex-1` menu and `@include('layouts.partials.sidebar-footer', ['notifyMode' => 'admin'])` in both the desktop and the `#res_sidebar` copies.
- **Teacher, parent and student sidebars** are the older `w-full h-full … hidden lg:block md:block` wrapper with no footer. Their layouts call `layouts.partials.navigation` with `notifyMode` `teacher`, `parent` and `student`.
- `navigation.blade.php:143` already hides the header bell when `$chromeInSidebar` is set.

**Per role:**
1. **Sidebar:** copy the admin structure: flex column, `flex-1` menu, then the footer include with the role's `notifyMode`. Do this in both the desktop and the `#res_sidebar` copies. **Keep `id="res_sidebar"` and its `hidden` class exactly as they are**, because the delegated `#mobile-menu-trigger` handler toggles it. **Don't copy admin's inline `#FFFCF5`/`#141413` backgrounds.** Keep each role's existing class-based background until the cream decision lands.
2. **Layout:** pass `chromeInSidebar => true` to `layouts.partials.navigation`. **Before building,** read `navigation.blade.php` L20–40 to confirm the variable name and default, and confirm that the header profile dropdown (L166) is suppressed by the same flag.
3. **Parent only:** `familyMenu => true` stays in the header (children and schools switcher). Only the bell and profile move.

**Acceptance for each role:**
- `data-testid="dashboard-sidebar-footer"` appears exactly once in the desktop sidebar and once in `#res_sidebar`.
- The header has no `<notification>` and no profile dropdown. For parent, the header still has the family menu.
- The mobile menu opens exactly once per tap.
- The profile menu opens upwards from the footer.
- The notification drawer opens full height.
- The collapsed rail shows icons only.
- Rendering the partial as a guest renders nothing (the existing `@guest` guard).
- A feature test per role (following the admin one), plus screenshots at 390, 768 and 1280.

---

**Suggested order:** phase 1, then phase 2 (independent), then phase 4 (so tables can use `<x-empty-state>`), then phase 3 in batches, then phase 5 (independent; it can run in parallel with 2–4).
