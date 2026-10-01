# Handoff for Claude Code — chart colours, approvals inbox, token decisions (2026-09-27)

Source: `KlassApp-Foundation/KlassApp@main` (d377e2e). Written from Claude Design, which **can read the repo but can't write to it, open PRs, read git history, or screenshot the running app**. Everything below is for Claude Code to apply, verify and ship.

## Suggested PRs
1. `fix(charts): AA-contrast series colours + EOT value labels` — item 1
2. `fix(approvals): inbox on x-table + ds buttons, AA contrast` — item 2
3. `chore(ds): form label onto type scale` — item 3a
4. `chore(ds): admin sidebar cream → --d-canvas` — item 3b, **only after the history check**
5. `fix(reports): printed greys → #64748B` — item 5 (approved)
6. `feat(ds): x-profile-photo + replace divergent avatar frames` — item 6 (approved)

Each PR: before/after screenshots at 1280 and 390, the existing DesignSystem feature tests green (`TableAndButtonClassContractTest`, `DashboardGenderChartTest`), and a `knowledge.md` stamp.

---

## 1. Charts
| File | Change |
|---|---|
| `admin/dashboard/dashboard.blade.php` — fee trend | `#22C55E` → `#15803D` (border + point); fill `rgba(34,197,94,.06)` → `rgba(21,128,61,.06)` |
| same — gender doughnut + class bar | Girls `#ffa601` → `#B45309`; Unspecified `#cbd5e1` → `#64748B`; Boys `#304ffe` unchanged |
| same — gender empty message | "No gender data" → "No students enrolled yet" |
| `admin/dashboard/_eot-kpi-card.blade.php` — `$eotHues` | `#CA8A04` → `#A16207` |
| same — value labels | Chart.js 4 has no built-in data labels, and the chartjs-plugin-datalabels plugin isn't loaded. Add an inline `afterDatasetsDraw` plugin that draws each bar's value 4px above it (DM Sans 600 11px, `#1E293B`). Don't add a dependency for this. |
| `components/chart.blade.php` | `emptyMessage` default → `null`; throw in local/testing when it's missing (decided earlier). Optional: fall back to palette `['#1E6FD9','#B45309','#15803D','#64748B']` when a dataset gives no colour. No live chart relies on the Chart.js defaults, so this fallback changes nothing on screen. |

## 2. `admin/approvals/inbox.blade.php`
Read in full. Problems confirmed in the source:
- The table is a raw `<table class="w-full text-sm">` with Tailwind grey header and cells.
- **Approve** (`px-3 py-1 text-xs`, `bg-green-600`/`hover:bg-green-500`): white text at 3.30:1, dropping to 2.28:1 on hover.
- **Reject** (`px-3 py-1`, `bg-red-500`/`hover:bg-red-400`): 3.76:1 and lower on hover.
- **Confirm** (`px-2 py-1`): a third button size.
- The student `<select>` and the reason `<input>` are `text-xs px-2 py-1`, so about 26px tall.
- The empty state is `text-gray-400` (2.54:1).

Changes:
- **Table:** `<x-table>` using the props in `components/table.blade.php`. Keep all six columns and the whole `@php` block unchanged. The ledger header and the stacked mobile layout come from the component.
- **Approve:** `<x-button variant="primary" size="sm">`. This uses `--d-accent #15803D`, so white text is 5.02:1, and hover goes darker to `--d-accent-dk #166534`, never lighter.
- **Reject and Confirm:** `<x-button variant="danger" size="sm">`. This uses `--d-red #DC2626` at 4.83:1, with a darker hover (`#B91C1C`).
- **All three buttons:** the same size step, rendering at least 44px tall; check with `getBoundingClientRect`, not computed width.
- **Select and reason input:** the `ds-form-select` / `ds-form-input` classes (44px minimum height).
- **Requester email, dates and "—":** `text-gray-400` → `var(--d-text-secondary)` `#64748B` (4.76:1).
- **Empty state:** `.ds-empty-state`, with the title "No approval requests yet" and the description "Staff leave, parent-link and marks requests you need to review will appear here."
- **Out of scope:** leave `onclick="return confirm(...)"` as it is. The switch to the new dialog is its own PR.

## 3. Token decisions
- **a. Form label size:** `.ds-form-label` `0.82rem` → **`0.85rem`**. That's the nearest step on the scale (0.03 away, versus 0.04 to 0.78). Every form label grows by 0.03rem, about 0.5px, so check visually that no labels start wrapping.
- **b. Admin sidebar cream `#FFFCF5` → `var(--d-canvas)` `#FAFAF5`:** first run `git log -S 'FFFCF5' --all -p`. If a commit message or comment says the difference was deliberate (for example, to separate the sidebar from the canvas), stop and report it back. If not, apply the change. I couldn't run this check from here.

## 4. Investigations
- **a. Landing fonts (Bricolage Grotesque + Inter):** I can't check this from here, because I have no access to git history. Run `git log -S 'Bricolage' -- public/css/landing.css`, then check whether the introducing commit or PR says the choice was deliberate. Report back before changing anything.
- **b. Grey-text triage:** see the results in chat. The admin scope has been read. The rest of the app still needs to be triaged.

## 5. Printed report greys (approved)
All `#94A3B8` → `#64748B` (4.76:1 on white) in:
- `admin/marks/student-report.blade.php`: `.badge-year`, `.info-label`, `.marks-table td.empty`, `.comments-label`, `.footer-table td`. Leave `.sign-line`'s border alone — it's a rule line, not text.
- `admin/marks/report-templates/formal.blade.php`: `.ledger td.empty`.
- `admin/reports/missing-marks.blade.php`: `.meta`, `.footer`, and the inline standard label in the `<h2>`.

Verify with a PDF export of each template, before and after. The 7–8px sizes are left alone: changing the size would change the page layout, so it's a separate decision.

## 6. `<x-profile-photo>` (approved)
New file: `resources/views/components/profile-photo.blade.php`.
- Props: `user` (required), `size` = `sm` 40 | `md` 64 | `lg` 128 | `xl` 192 (px).
- Renders `<img src="{{ $user->userprofile?->AvatarPath ?? asset('uploads/user/avatar/default-user.jpg') }}" alt="{{ $user->name }}" width height>`.
- Style: `aspect-ratio:1`, `object-fit:cover`, `border-radius:var(--d-radius-lg)` (12px), 1px `var(--d-border)` ring.
- ~~If `$user` is null, show the default image with `alt=""`.~~ **Superseded 2026-09-30:** with no photo, show initials on a colour tile. With no name, or a null user, show a blank `--d-avatar-none` tile with `aria-hidden`. Adds the size `xs` (32). Full spec: `handoff-2026-09-30-avatar-initials.md`.

Where to use it:
| File | Today | Becomes |
|---|---|---|
| `admin/member/show` L21 | `w-full max-h-48 w-auto` (conflicting width classes) | `size="xl"` |
| `admin/teacher/show` L21 | same | `size="xl"` |
| `admin/staff/show` L21 | `w-32 h-32 lg:w-full lg:h-48` | `size="lg"` on mobile, `xl` from lg up. Or just `xl` — confirm by screenshot. |
| `admin/feedbacks/view` L23 | hard-coded `default-user.jpg` at 60px | `size="md" :user="$feedback->parent"` (relation verified above) |
| `admin/id-card/id-card-new` L135, `idcard-print` L107 | inline 100px, radius 10px | radius → 12px only. These are print templates, so keep the fixed 100px and don't use the component (it depends on CSS variables, which the PDF renderer may not resolve). |
| `admin/buspass/bus_pass` L115 | inline 75px, radius 10px | radius → 12px only; same reason. |

**Search limit:** the scan covered 332 of 889 view files. Before this PR, grep the whole tree for `AvatarPath` so no avatar frames are missed. (A full-tree retry from Claude Design was rate-limited, so this is still open.) **Retry, 2026-09-27T03:40Z:** 7 hits, but still only 329 of 889 files scanned, so treat this as a floor, not the full list: `buspass/bus_pass` L115, `buspass/print` L94 (**inside an HTML comment — leave it**), `id-card/id-card-new` L135, `id-card/idcard-print` L107, `member/show` L21, `staff/show` L21 (the real class is `w-32 h-32 lg:w-full lg:max-h-48 lg:h-48 object-cover mx-auto`), `teacher/show` L21. Everything found is already in scope. Run `grep -rn AvatarPath resources/ app/ packages/` locally before starting.

### Pre-checks done 2026-09-27 (read, verified)
- **Feedback → user:** `App\Models\Feedback` has `parent()`, `student()` and `admin()`, each a `belongsTo(User)` on `parent_id` / `student_id` / `admin_id`. `admin/feedbacks/view` already shows `$feedback->parent->FullName` next to the image, so the photo is **`:user="$feedback->parent"`**. Not `->user` — that relation doesn't exist.
- **The sidebar footer has no photo of its own.** It includes `layouts/partials/profile-dropdown`, the shared partial for **every role's navigation**. It has two avatars:
  - The trigger: `w-8 h-8 rounded-full`, 32px, **circular**, with an inline 2px border `rgba(34,197,94,.3)`.
  - The menu header: `.user-avatar`.
  - Neither has `alt`. Both check `userprofile->avatar != null` and wrap the path in `url()`; the show pages do neither.
- **Fallback check:** the component must use the same test as the dropdown, `$user->userprofile && $user->userprofile->avatar != null ? url(...AvatarPath) : asset('uploads/user/avatar/default-user.jpg')`. The `?->AvatarPath ?? default` I specified earlier would break if the `AvatarPath` accessor returns a non-null value when there's no avatar. I haven't confirmed how the accessor behaves.
- **✅ Resolved 2026-09-27 (user):** option **(b)**. `<x-profile-photo shape="circle">` is used **only** for the 32px nav trigger in `profile-dropdown`. Every other use, including the dropdown's `.user-avatar` header photo, is square with a 12px radius. Both dropdown photos get `alt`, and the trigger's inline `rgba(34,197,94,.3)` border becomes a token.

## Regression checks for every PR
- Mobile nav delegated tap (`#mobile-menu-trigger`) still opens `#res_sidebar` exactly once.
- Toshi split layout: collapsed by default, resizable between 300 and 640px.
- Admin sidebar footer (#830) renders, and collapses to icons.
- The DesignSystem feature tests pass.
