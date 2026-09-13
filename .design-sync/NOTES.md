# Claude Design sync — KlassApp notes

## What this repo is, for sync purposes

KlassApp is a Laravel app, **not** a React component library. Its design system is:

- **Tokens + all component CSS**: `public/css/dashboard-refresh.css` (~77 KB, 3223 lines).
  39 `--d-*` custom properties in `:root`, plus the whole `ds-*` class namespace.
  This file is the source of truth — treat it as such.
- **Components**: anonymous Blade files in `resources/views/components/`
  (`button`, `card`, `badge`, `table`, `form-group`, `ds-kpi-card`, `brand/*`).
- **Tailwind v4.3.3** `@theme` in `resources/css/tailwind.css` holds only three
  font variables. It is **not** where the brand tokens live — do not go looking
  for them there.

Claude Design renders React. Blade cannot be bundled, so `.design-sync/shim/` holds
a **thin React port** of each Blade component, emitting the identical `ds-*` class
contract. All styling is the real `dashboard-refresh.css`. The port is committed and
is a durable sync input — if a Blade component's props or classes change, update the
matching `shim/src/<Name>.tsx` or the two drift apart silently.

Component ↔ Blade mapping lives in each shim file's JSDoc (`<x-button>` etc.).

## Gotchas hit during the first sync (2026-09-13)

- **`cfg.cssEntry` is bounded to the DS package directory.** Pointing it at
  `../../public/css/dashboard-refresh.css` prints
  `! cssEntry: … resolves outside the package — skipped` and the build silently
  falls through to `[CSS_RUNTIME]` with **no CSS at all**. Fix in place:
  `shim/copy-css.mjs` copies the canonical file into `shim/dist/` on every build
  and `cssEntry` points at that copy. Never commit a second copy of the
  stylesheet — the copy is gitignored and regenerated.
- **`typescript` v7 breaks validate's `.d.ts` parse check.** The native-port API
  has no compatible `createSourceFile`, and `package-validate.mjs` swallows the
  failure in a bare `catch`, printing the misleading
  `(.d.ts parse check skipped — typescript not in node_modules)` even though it
  *is* installed. `.ds-sync/` pins `typescript@5` for this reason. The shim
  itself compiles fine on v7.
- **Fonts are remote.** `dashboard-refresh.css` opens with a Google Fonts
  `@import` for Sora + DM Sans, so validate reports `[FONT_REMOTE]`, not
  `[FONT_MISSING]`. No action — this matches what the real app does. See
  Re-sync risks.

## Known render warns (checked on every re-sync — an unlisted warn is new)

- **`[FONT_REMOTE] "DM Sans", "Sora"`** — expected and correct. The stylesheet's
  first line is a Google Fonts `@import`; the families load at runtime exactly
  as they do in the real app. Not `[FONT_MISSING]`, no action.
- `[GRID_OVERFLOW]` on `FormGroup` fired once on the first render check and is
  **resolved** by `cfg.overrides.FormGroup.cardMode = "column"`. If it returns,
  the override was lost, not a new problem.

## No Tailwind utilities ship — the single biggest authoring trap

`_ds_bundle.css` is `dashboard-refresh.css` and nothing else: **364 class
selectors, zero Tailwind utilities.** Verified absent: `mt-4`, `md:mt-6`,
`flex`, `grid`, `gap-4`, `p-4`, `text-sm`, `text-center`, `font-bold`,
`rounded`, `w-6`, `h-6`, `group`.

The Blade sources use these freely (the app loads Tailwind v4 separately, and
`ds-kpi-card.blade.php` even puts `w-6 h-6` on its icon SVG and `group` on the
root). None of it reaches a design built from this bundle. `conventions.md`
tells the design agent to use `var(--d-*)` inline styles for layout glue
instead — do not weaken that guidance.

Also absent despite being emitted by components: `brand-mark`,
`brand-mark--whatsapp` / `--slack` / `--drive` (the brand marks carry the class
but no rule exists, which is why they need explicit `style={{width,height}}`),
and `parent-panel` (an app-level class the Blade views pass in).

## Repo findings surfaced by the sync (real, not sync artifacts)

These are drift in the repo itself, reported to the user on 2026-09-13. None are
fixed by the sync — the port reproduces current behaviour faithfully.

- **`.ds-btn-md` has no CSS rule.** `button.blade.php` applies it as the *default*
  size, so every default-size button in KlassApp carries a dead class and renders
  at the `.ds-btn` base size. 58 of the other 59 `ds-*` classes verify.
- **`table.blade.php` contradicts `DESIGN_SYSTEM.md`.** The component emits
  `.ds-table-ledger` + `dt-comfortable|dt-compact` + `.ds-table-card-mobile` and
  takes `density` / `selectable` / `sortable` / `cardMobile`. The doc describes
  `.ds-table` with `striped` / `hover`. The doc is stale.
- **`table.blade.php` has two dead props.** `striped` and `hover` are declared in
  `@props` but never referenced in the template. Kept in `TableProps` for API
  parity, documented as no-ops.
- **`DESIGN_SYSTEM.md`'s entire badge colour table is wrong.** Every background
  hex in it differs from `dashboard-refresh.css:406-429`, and `pending`/`info`
  changed semantically from blue to warm grey:

  | Variant | Doc claims | Actual CSS |
  |---|---|---|
  | `pending`, `info` | `#EFF6FF` / `#1D4ED8` (blue) | `#f0eee6` / `--d-text-secondary` (warm grey) |
  | `approved`, `paid`, `active` | `#F0FDF4` / `#15803D` | `#e8f5e9` / `#2e7d32` |
  | `rejected`, `unpaid` | `#FEF2F2` / `#DC2626` | `#fbe9e7` / `--d-red` |
  | `warning` | `#FFFBEB` / `#D97706` | `#fff8e1` / `--d-amber` |
  | `inactive` | `#F1F5F9` / `#64748B` | `#f0eee6` / `--d-muted` |

  Documentation drift only — the CSS is the truth and renders correctly. Not
  logged as a production bug, unlike the two `TRACKED ISSUE` entries in
  `knowledge.md`.
- **`.ds-btn-secondary` and `.ds-table-striped` / `.ds-table-hover`** — see the
  two `2026-09-13: TRACKED ISSUE` entries in `knowledge.md`. Short version:
  the striped/hover CSS exists and works for raw-markup tables but `<x-table>`
  never emits it; `.ds-btn-secondary` is applied in three views but has no
  colour rule at all.
- **`DESIGN_SYSTEM.md:173-174` claims Tailwind v1.4.6** ("no `@apply`", "no JIT",
  "no arbitrary values") while `package.json` runs **4.3.3** — the same doc's own
  stack table (line 9) says 4.3.3. The architecture rules it derives from that
  version claim are unreliable.

## Re-sync risks

- **The React shim is a hand-written port, not a generated artifact.** It cannot
  detect Blade changes. Any prop added/removed/renamed in
  `resources/views/components/*.blade.php`, or any change to the emitted class
  strings, must be mirrored into `.design-sync/shim/src/`. Diff the Blade sources
  against the shim JSDoc on every re-sync.
- **Fonts load from fonts.googleapis.com at render time.** If Claude Design's
  renderer is offline or the CDN is blocked, every design falls back to
  `system-ui`. Vendoring Sora + DM Sans into `fonts/` via `cfg.extraFonts` is the
  fix if that ever bites.
- **`public/css/dashboard-refresh.css` is checked in, not built.** It is edited by
  hand. A re-sync picks up whatever is on disk — there is no build step that
  would catch a syntax error in it first.
- **Only 6 of the DS's Blade components are ported.** `landing-layout.blade.php`
  (1315 lines) is a full page layout, not a component, and is deliberately out of
  scope. `icons/sidebar.blade.php` is likewise unported.
- **The shim pins React 19 and TypeScript 7** in its own isolated
  `node_modules`; the repo's own `package.json` is untouched (it is a Vue 3
  codebase). Do not merge these deps into the repo.
