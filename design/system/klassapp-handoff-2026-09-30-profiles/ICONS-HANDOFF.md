# Handoff: one icon set (Lucide), 2026-10-01

- **Designed from:** `main @ 3cf3025bf5b0`.
- **Spec and mapping:** `concepts/icons/index.html`. Every Lucide name on that page was checked live against `lucide@0.460.0` in the browser and all of them exist. When you install a newer version, re-check the names, because Lucide sometimes renames icons (for example `check-square` became `square-check`).
- **Contrast:** calculated from hex, not browser-measured.

## Choice
- **Library:** **Lucide.** Its licence is **ISC** (a permissive licence like MIT, not MIT itself). Keep the licence notice with the package.
- **Why Lucide:**
  - The sidebar is already hand-copied Feather, and Lucide is Feather's maintained fork, so those glyphs move over almost unchanged.
  - It has one consistent 24px grid at stroke 2.
  - It has maintained Blade and Vue packages.
- **Packages:**
  - **Blade:** `mallardduck/blade-lucide-icons`. It needs `blade-ui-kit/blade-icons`, which isn't installed today. Pin exact versions.
  - **Vue 3:** `lucide-vue-next`, with per-icon imports only so the bundle tree-shakes. The app runs Vue 3.5 with `@vue/compat`; check that it works under compat.
- **One wrapper per stack**, so a future library swap touches one file:
  - **Blade:** `<x-icon name="house" size="20" />` in `resources/views/components/icon.blade.php`. It renders `<x-dynamic-component :component="'lucide-'.$name" …>`, adds `aria-hidden="true"` and `focusable="false"`, and applies the size and stroke rules below.
  - **Vue:** `KaIcon.vue` with `name` and `size` props.
  - **Don't call the package components directly** in views.

## Rules
- **Sizes:**
  - **16:** dense tables, badges, inline text.
  - **20 (default):** nav, buttons, list rows, inputs.
  - **24:** empty-state icons inside a 48px box, and page headers.
  - The control around the icon is always at least 44px.
- **Stroke:** 2 at 16 and 20, and 1.75 at 24 and up. Round caps and joins; `absoluteStrokeWidth` off.
- **Colour:** always `currentColor`, via tokens (in the design system's `tokens/colors.css`):

  | Token | Value | Contrast |
  |---|---|---|
  | `--d-icon` | #1E293B | 14.6:1 |
  | `--d-icon-muted` | #64748B | 4.76:1 |
  | `--d-icon-active` | #14532D | 9.0:1 on the #E8EFE7 active nav row |
  | `--d-icon-danger` | #B91C1C | 6.47:1 |
  | `--d-icon-warning` | #B45309 | 5.02:1 |
  | `--d-icon-on-accent` | #FFFFFF | 5.02:1 on #15803D |

  - **Meaningful icons:** at least 3:1 against their background.
  - **#94A3B8 is retired for icons.** `sidebar-group.blade.php` uses it today, at 2.56:1 on white.
- **No fills and no duotone.** Drop the `icon-fill-targets` layer and `--icon-fill-opacity` from the sidebar. The active state comes from the row background (#E8EFE7) and font weight 700.
- **Brand marks stay brand marks:** WhatsApp, Google Drive and Slack use `<x-brand.*>`, never a Lucide stand-in. The KPI "WhatsApp Parents" chat-bubble icon becomes the real WhatsApp mark.
- **Currency:** don't use the dollar sign. Fees, Subscriptions and Plans use `banknote`, because schools pay in UGX.
- **Decorative vs meaningful:** an icon next to a text label is decorative (`aria-hidden`). An icon-only button carries `aria-label` on the **button**, not on the svg.

## PRs (in priority order)
### I1 · `feat(icons): add Lucide + <x-icon> wrapper`
- **Scope:** the packages, the Blade wrapper, `KaIcon.vue` and the colour tokens.
- **Accept:**
  - `<x-icon name="house" />` renders a 20px svg with stroke 2, `aria-hidden` and `currentColor`.
  - `size="24"` renders with stroke 1.75.
  - An unknown name renders `info` and logs a warning in dev only.
  - A unit test covers all three.

### I2 · `refactor(nav): sidebar icons → Lucide`
- **Scope:**
  - Rewrite `components/icons/sidebar.blade.php` as a lookup that maps each `config/navigation.php` key to a Lucide name (table 1 on the concept page), rendered through `<x-icon>`.
  - Split shared keys so each item has its own (table 4). For example Notices goes from `messages` to `notices`, which renders `megaphone`.
  - **Teachers and students** are identical glyphs today. They become `presentation` and `graduation-cap`.
  - **Group headers:** remove the icons from `sidebar-group.blade.php`. Group labels are text-only in dashboard v2.
  - **Top bar:** the filled hamburger in `navigation.blade.php` L67 becomes `<x-icon name="menu" />`.
- **Accept:**
  - Screenshots of every role's sidebar at 1280 and 375, before and after.
  - Each item is 44px tall and each icon 20px, measured with `getBoundingClientRect`.
  - No `heroicon-ui` class remains.
  - The collapsed rail still shows icons with tooltips.

### I3 · `refactor(dashboard): KPI + dashboard icons → Lucide`
- **Scope:** `x-ds-kpi-card` icon keys (table 3), the dashboard's inline notice-board bell (`megaphone`), and the WhatsApp KPI using `<x-brand.whatsapp>`.
- **Order:** do this together with the dashboard v2 PR (B1) if that lands first.
- **Accept:** screenshots; no inline `<svg>` path data left in `admin/dashboard/*.blade.php` except brand marks.

### I4 · `refactor(icons): shared UI glyphs`
- **Scope:** the search, close, chevron, edit, delete, upload, download, filter, sort, more and logout icons in the components (`button`, table, dialog, form inputs, account card), all moved to `<x-icon>`.
- **Accept:** a grep for inline `<path d=` in `resources/views/components/` returns only brand marks.

### I5 · `chore(icons): remove legacy SVG files`
1. For each file in `resources/assets/icons/`, run `grep -rn "icons/{name}" resources/ public/ app/`.
2. If it's used, replace it with the Lucide icon from table 5.
3. If it's unused, delete it.
4. **Delete** `church`, `baptism`, `cross` and `e-prayer` either way; they're leftovers from the church product.

Four files weren't visible in my tree listing, so list them with `ls` and add them to the table.

- **Accept:** the directory is empty or gone, and the build passes.

### I6 · `refactor(vue): Vue components → KaIcon`
- **Scope:** replace inline SVGs and any icon-font usage in `resources/assets/js/components/**` with `KaIcon`.
- **Accept:** the bundle size grows by no more than ~15 KB gzipped (an estimate; measure it).

## Every PR
- **Regressions:** the mobile menu opens exactly once, the Toshi split-layout still collapses and resizes, the sidebar footer still works, and the design-system tests pass.
- **Screenshots:** before/after at 1280 and 375.
- **knowledge.md:** stamped with the branch-point SHA.
