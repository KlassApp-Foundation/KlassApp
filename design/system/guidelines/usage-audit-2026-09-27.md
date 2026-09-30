# Usage audit — how the design system is actually used (2026-09-27)

Scope: where the live app diverges from its own system. Nothing new is built here. Source: `KlassApp-Foundation/KlassApp@main` (d377e2e).

**Evidence limits.** The repo search is bounded. Scans of the whole `resources/views/` tree covered about 330–350 of 889 files per query before hitting the time budget, so **every count marked "≥" is a floor, not a total**. Scans limited to `resources/views/admin/` completed in full. One query was rate-limited and retried. Nothing below is inferred from files that weren't read.

Legend: **SAFE** = mechanical fix with no visible design decision · **DECIDE** = needs a call first.

---

## 1. A real component exists, but pages use something else

| # | Where | What's wrong | Fix |
|---|---|---|---|
| 1.1 | **≥84 matches** of `swal(` / `sweetalert` across views (e.g. `admin/bulletins`, `feedbacks/view`, `leavetypes/list`, `member/show`, `parent/show`, `marks/promotion`) | SweetAlert v1 confirms. They use an info icon on deletes and a generic "OK" button, show a separate "Cancelled" popup, and a separate popup for success. `admin/bulletins` also loads **its own unpinned copy** from `unpkg.com/sweetalert`, on top of the shell's `js/sweetalert.min.js`. | **SAFE** per page → `.ds-dialog` + toast (migration map in `component-review-dialogs.html`). Start with the pages that load the unpinned CDN copy. |
| 1.2 | ~9 SiteAdmin Livewire components (`AdminForm`, `CreateSchool`, `ChangePassword`, `SubscriptionForm`, `CityForm`…) | `livewire-alert` → SweetAlert2: a second, differently styled alert system | **SAFE** — the `DispatchesAlerts` trait swap (decided). |
| 1.3 | **37 raw `<table>`s in `admin/` alone** (full scan), e.g. `approvals/inbox` (`w-full text-sm`), `buspass/bus_pass` | No ledger header rule, no hover rail, no mobile card layout, no 44px row actions. `buspass/print` is a print sheet and correctly stays raw. | **SAFE** for list tables → `<x-table>` + `tables-v2`. Print/PDF templates are excluded. |
| 1.4 | **50 matches** in `admin/` of ad-hoc Tailwind buttons (`bg-green-600`, `bg-red-500`, `custom-green`…), e.g. `approvals/inbox` L124–141 | Hand-rolled Approve/Reject buttons: `px-3 py-1 text-xs` renders at about **24px tall** (the minimum is 44), and the colours fail — see 2.1. | **SAFE** → `<x-button variant="primary|danger" size="sm">` (sm still renders 44px tall). |
| 1.5 | **≥19** bare empty strings ("No records found", "No Records Found", "No data available"), e.g. `accountant/activity_log/show_list`, `accountant/feed/feed`, `feed/filter`, `admin/bulletins` | Break the documented copy rule and ignore `.ds-empty-state`. Capitalisation isn't even consistent. | **SAFE** structurally → `.ds-empty-state.v2`. **DECIDE** copy for each screen (it has to name that screen's gap). |
| 1.6 | `accountant/dashboard.blade.php` L58/95/127 | Hand-built empty states: 48px `text-gray-300` icons at stroke-width 1 — a third empty-state look, next to `.ds-empty-state` and `.ds-chart-empty` | **SAFE** → `.ds-empty-state.v2.is-compact`. |

## 2. Contrast failures not already fixed tonight

| # | Where | Pair | Ratio | Fix |
|---|---|---|---|---|
| 2.1 | `admin/approvals/inbox` Approve button | white on `bg-green-600` #16A34A, hover `green-500` #22C55E | **3.30 / 2.28** | **SAFE** — folded into 1.4 |
| 2.2 | same, Reject button | white on `bg-red-500` #EF4444, hover `red-400` | **3.76 / lower** | **SAFE** — folded into 1.4 |
| 2.3 | `admin/reports/missing-marks` `.empty` | `#22C55E` text on white (a PDF "all marks in" message) | **2.28** | **SAFE** → `#15803D` |
| 2.4 | `admin/otp/create` (L88, 159) | `color:#22C55E` text | **2.28** | **SAFE** → `--d-accent` #15803D, if it's text (L105/135 are border/background — check whether white text sits on the L135 fill) |
| 2.5 | `marks/report-templates/{formal,warm}` `.powered` | 9px/800 `#22C55E` on white | **2.28** | **DECIDE** — it's printed brand-mark text, so it may be treated as a logo (exempt). If it's meant to be read, → #15803D. |
| 2.6 | **≥122 matches** of `text-gray-300/400`, `#9CA3AF`, `#94A3B8` | Many are **decorative icons** (allowed); some are text. `text-gray-400` #9CA3AF on white is **2.54**. | **DECIDE** — needs a per-instance pass. It can't be a blind find/replace, because icons must keep their lighter tone. |
| 2.7 | Frozen Pulse `.ds-kpi-value` (already flagged) | `#22C55E` on white | **2.28** | Still open — owner decision |

## 3. Spacing & sizing against the token scale

| # | Where | What's wrong | Fix |
|---|---|---|---|
| 3.1 | Legacy list pages (e.g. `admin/bulletins` search: `px-10 py-1 text-sm … rounded`; Reset link `py-1 px-4`) | ~28px-tall controls, 4px radius, Tailwind grey borders — none from the scale (inputs are 8px radius / 44px min in v2) | **SAFE** → `.dt-search` / `.ds-form-input.v2` / `.ds-btn-outline` |
| 3.2 | `approvals/inbox` action buttons (`px-2`/`px-3 py-1 text-xs`) | Three different button sizes in one row, all below 44px | **SAFE** — part of 1.4 |
| 3.3 | Form label size `0.82rem` (every `.ds-form-label`) | Off the scale (0.78 / 0.85) | **DECIDE** — used system-wide, so changing it is visible everywhere |
| 3.4 | `.dt-page-btn` / `.dt-density-btn` declare 36×36 but `min-*: 44px` | The declared size is dead code; they always render 44 | **SAFE** — delete the 36px declarations so the CSS says what renders |

## 4. The system breaking its own documented rules

| # | Rule | Where it's broken | Fix |
|---|---|---|---|
| 4.1 | **Sora + DM Sans only** (the full-alignment decision) | `public/css/landing.css` imports **Bricolage Grotesque + Inter** and sets `font-family: Inter` on `body` | **DECIDE** — the landing page may be deliberately separate from the app. The earlier landing work assumed Sora/DM Sans, so they currently disagree. |
| 4.2 | Tokens, not literals | `superadmin-app.blade.php` — inline `.tw-form-control` override (decided: remove); the page-loader dots in both shells are `#9b2c2c` (a legacy maroon, not a token); the admin sidebar is inline `#FFFCF5` against `--d-canvas #FAFAF5` | **SAFE** — the loader colour → `--d-blue` or `--d-text-secondary`. **DECIDE** sidebar cream: token it, or align to canvas. |
| 4.3 | One alert system | Four are live: swal v1, SweetAlert2, toastr, jquery.toastmessage (calendar) | Covered by 1.1 / 1.2. jquery.toastmessage is **SAFE** once the calendar is migrated. |
| 4.4 | Copy rule for empty states | `<x-chart>`'s default `emptyMessage` (decided: made required); dashboard doughnut "No gender data" | **SAFE** — rewrite to "No students enrolled yet" |
| 4.5 | Toshi palette stays inside Toshi | `.ds-form-input:focus` uses a clay glow; `.dashboard-kpi-icon--accent` uses a clay background `rgba(201,100,66,.10)` with `--d-accent` green text | **SAFE** — the focus fix is in `controls-v2`. The KPI accent icon is a mixed clay/green pairing → pick one. |
| 4.6 | Hard-coded tints | `tr.dt-selected #E8F0FE`, `tr.dt-row-alt #F8F5F0`, badge fills | **SAFE** — add tint tokens before the 37-table migration copies them again |
| 4.7 | Frozen Pulse block's own drift | drop-shadow KPI cards, 2px lift (the token says 1px), off-scale 0.85/0.75rem labels | Owner decision (already flagged) |

## 5. Navigation — sidebar footer missing for three roles

| # | Where | What's wrong | Fix |
|---|---|---|---|
| 5.1 | `layouts/teacher/sidebar`, `layouts/parent/sidebar`, `layouts/student/sidebar` | Only `layouts/admin/sidebar` includes `layouts.partials.sidebar-footer` (#830). The other three roles still route profile and notifications through the header, so the same action lives in two different places depending on who's logged in. Their sidebars also lack the admin's `md:flex md:flex-col` column (needed to pin a footer to the bottom), and the mobile `#res_sidebar` markup differs. SiteAdmin (`superadmin/sidebar`) doesn't include the footer either. | **DECIDE** — the partial is role-aware (`$notifyMode`) and guest-safe, so adding it is mechanical. But removing the header copies changes where parents look for notifications, and parents also have `familyMenu` in the header. The safe order: add the footer to all three, then remove from the header only once each role's header no longer needs it. |

---

## Suggested order

1. **All SAFE items in `admin/approvals/inbox`** (1.3, 1.4, 2.1, 2.2, 3.2). One page, four problems, very visible to admins.
2. **2.3, 2.4, 4.4** — one-line colour and copy fixes.
3. **1.2** — the livewire-alert trait swap (9 files, one line each).
4. **1.1** — swal → dialog, page by page, starting with the pages that load the unpinned CDN copy.
5. **4.6**, then the 37-table migration (1.3).
6. DECIDE items: **5.1** (sidebar footer), **4.1** (landing fonts), **2.6** (grey text pass), **3.3** (label size), **4.2** (sidebar cream), **2.5** (report "powered" mark).
