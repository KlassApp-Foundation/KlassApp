# KlassApp Design System

**KlassApp is the school platform that operates in the tools educationists already use.** It is a school-management product for East African schools — student rosters, classes, fee ledgers, exam marks, report cards — whose distinguishing move is that it doesn't demand a new habit: parents are reached on **WhatsApp**, documents land in **Google Drive**, staff chatter stays in **Slack**, and an in-app assistant called **Toshi** does the multi-step office work on request.

The product surfaces this system covers:

1. **The staff dashboard** — one shell rendered per role (`--admin`, `--teacher`, `--student`, `--accountant`, `--library`, `--reception`, `--alumni`, `--stock`, `--superadmin`). KPI fold, ledger tables, marks grids, forms.
2. **Toshi**, the in-app AI assistant — a docked panel with its own warm clay palette, suggestion chips, plan cards and tool-confirmation cards.
3. **Manual onboarding wizard** — the `manual-wizard-*` flow a school walks through when it sets itself up without Toshi.

## Sources used

- **Attached codebase: `ds-bundle/`** — the published `@klassapp/ds@0.0.0` React library as synced by `cc-design-sync`. Contents read: `README.md`, `styles.css`, `_ds_bundle.css` (3,224 lines — the app's real `dashboard-refresh.css`, 364 selectors), `_ds_bundle.js` (all 9 component implementations), every `<Name>.d.ts` / `.prompt.md`, and the render-check screenshots in `_screenshots/`.
- The upstream app is **Laravel Blade**; the React components are ports that emit the identical class contract (`<x-button>` → `.ds-btn`, etc.).
- **Logo supplied separately by the user** — three official files in `assets/brand/`, plus five mechanically derived variants. They are not in the codebase.
- No Figma file, deck or brand guide was supplied.
- **Supplied separately by the user:** `ManualOnboardingWizard.php` (the real
  Livewire component), `manual-wizard-step-fields.blade.php` (the step partial —
  17 step keys, including the `standards` structure checkpoint), and
  `toshi-ui.css` (the ≥1280px docking layer plus the "Pulse" override set). All
  three are incorporated; see Responsiveness and the wizard kit's README.

Everything in this project is derived from those files. Where the source was silent, the gap is marked rather than filled — see **Caveats**.

---

## Content fundamentals

**Voice: a competent school secretary, not a SaaS brand.** Copy is plain, short, and assumes the reader runs a school — it says "Senior 2", "Term 2 2026", "aggregate", "SchoolPay" without explaining them.

- **Sentence case everywhere.** "Record Payment", "Add student", "Publish to parents". Title Case appears only on short button labels; ALL CAPS is reserved for table headers, sidebar group labels and the LIVE badge.
- **Second person, implied.** Copy addresses the user as *you* but usually drops the pronoun: "Publish reports", "Nothing here yet — add your first class to get started." The product never says *I* outside Toshi.
- **Toshi speaks in first person and confirms before acting**: "Got it — here is what I will do." Then a plan card, then a confirmation card with the exact parameters. Nothing is written without a Yes.
- **Actions are verbs with objects**, never bare "Submit": *Record Payment · Save Changes · Publish to parents · Remind teachers · Import class list*. Cancel is always "Cancel"; back is "← Back" with the arrow in the label.
- **Numbers carry their unit.** Money is `UGX 450,000` in ledgers, `UGX 18.4M` in KPI tiles — currency code first, never a symbol. Counts are comma-grouped: `1,284`.
- **Empty states name the gap and hand over the next action**: "No students in Senior 2 yet — import a class list, or ask Toshi to add them for you." Never "No data available."
- **Statuses are single words in a badge**: Paid · Unpaid · Part paid · Pending · Approved · Rejected · Active.
- **Emoji: not in product chrome.** The one sanctioned place is inside Toshi's suggestion chips and plan/step cards (`📋`, `👥`, `💰`), where they act as row markers. Never in headings, buttons, table cells or marketing copy.
- **Em dash and middle dot do real work**: the em dash is the empty value in a KPI tile (`—` means "not synced yet", not zero); `·` separates metadata — "Term 2 2026 · Senior 2 · 46 students".

---

## Visual foundations

**Colour.** Two brand hues with strict jobs: **green acts** — CTAs use `--d-accent` **`#15803D`** (darkened upstream 2026-09-18 so white button text clears 4.5:1; bright `#22C55E` is `--d-green`, for non-text accents only) and **blue `#1E6FD9` informs** (links, sort arrows, table header rule, pagination, focus). Amber `#D97706` warns, red `#DC2626` destroys. Purple `#8B5CF6` exists as a KPI icon tint only and nowhere else. Hover always goes **darker** (`--d-accent-dk #16A34A`), never lighter.

**Surfaces.** The canvas is warm parchment `#FAFAF5`, not grey — cards are pure white on top of it, so the whole app reads slightly cream. The dark palette (`#0F172A` / `#1E293B`) is for the app's dark shells and the Toshi review header; the current dashboard sidebar is cream with a dark-text/green-active treatment. There is exactly one gradient in the system: the admin LIVE badge (`#15803d → #22C55E → #4ade80`) with an animated sheen. No other gradient, anywhere.

**Type.** **Sora** for display (dashboard titles 1.8rem/700 at −0.02em, page heads 1.4rem/700, section 1.1rem/600, card titles 1rem/600, KPI values 1.6rem/700, and — unusually — uppercase table headers at 0.72rem/600). **DM Sans** for everything else (body and table cells 0.85rem, labels 0.78rem/600, help 0.78rem). Both from Google Fonts. No third family, ever. Ledger figures use `font-variant-numeric: tabular-nums`.

**Spacing.** Not a strict 4/8 grid — 6, 10, 14 and 18 are load-bearing. Shell padding 24, card padding 20 (sm 14 / lg 28), grid gutter 16, ledger cells 18×20 comfortable / 12×16 compact, button padding 8×18. Copy the literal; don't snap it.

**Corners.** 8 buttons and inputs · 10 banners · 12 table wrappers and icon chips · 14 cards and KPI tiles · 16 Toshi panel and review card · 18 topfold · 20 shell · 999 pills. Radius grows with the size of the container.

**Shadows.** The house elevation is a **hairline ring**, not a drop shadow: `0 0 0 1px var(--d-border)` sits under every card at rest. Elevation is earned — `md` adds `0 2px 8px rgba(0,0,0,.04)`, `lg` adds `0 8px 24px rgba(0,0,0,.06)`, and only the floating Toshi panel/pill go to `0 8px 40px rgba(0,0,0,.12)`. Nothing in the system uses an inner shadow. Focus is a ring too: 3px `rgba(30,111,217,.25)` on inputs, 2px blue outline at 2px offset on buttons.

**Borders.** Everything is bounded by a 1px `#E2E8F0` hairline; hover promotes it to `#CBD5E1`. The one heavy rule in the system is the **2px blue underline beneath ledger table headers**. Row hover adds an **inset 3px green rail** plus a 4% green wash; selected rows swap it for blue.

**Motion.** Restrained and short. Hover lifts exactly `translateY(-1px)` over 0.2s ease; press is `scale(0.97)` over 0.15s; transitions are 0.15s (interactive) or 0.2–0.3s (entrances). The shell fades in and slides 8px; KPI tiles fade up 12px staggered 60ms apart. Looping animation exists in three places only: the LIVE badge sheen (3.4s), its pulsing dot (1.8s), and the loading-dot bounce. Nothing bounces, springs or scales up on hover.

**Transparency & blur.** Used sparingly and always functionally: 10% colour fills behind KPI icons, 4% green wash on hovered rows, `rgba(255,255,252,0.97)` on sticky table headers so rows read through them, and a single `backdrop-filter: blur(2px)` behind the maximised Toshi modal. No frosted panels, no glass cards.

**Logo.** A green-and-blue **K** wearing a mortarboard, built from five linear gradients (green `#13904D→#2DC35E`, blue `#004093→#0273D4`) over flats `#29BF5D` and `#199D52`. Three official files ship — icon, a dark-surface horizontal lockup, and a light-surface horizontal lockup (misnamed `klassapp-stacked.svg`) — from which five transparent variants are mechanically derived: a reversed icon, light and dark horizontal lockups, and light and dark stacked lockups. Wordmark colours are context-dependent and confirmed from the official files: **`#0E2347` “Klass” / `#22B560` “App” on light**, **`#FEFEFE` / `#26B45F` on dark**. Never recolour, rotate, outline or redraw it. Full rules in `assets/README.md`; specimens in `guidelines/brand-logo*.card.html`.

**Imagery.** Beyond the logo and the third-party marks, there is none. The product ships no photography, illustration, pattern or texture — surfaces are flat colour, and the only graphics are stroked icons and third-party brand marks at full colour. Don't introduce stock imagery or generated illustration; if a screen feels empty, the answer is an empty state with an action, not a picture.

**Cards, in one line:** white, 14px radius, 1px `#E2E8F0` border *and* a matching 1px ring, 20px padding, Sora 600 title, 1px lift on hover only if the card is a link.

**Layout rules.** Sidebar is a fixed 252px column with its own scroll; the content area scrolls independently under a 58px top bar. KPI tiles use `repeat(auto-fill, minmax(220px, 1fr))`. Tables scroll horizontally inside `.ds-table-wrap` and restack as cards below 767px (`data-label` on every `<td>` becomes the field name). Every interactive target is at least 44px — the app states this as a token, `--d-touch-target-min`.

---

## Responsiveness

Every value below is a **real media query from `styles/classes.css`** — no
invented breakpoints. The system has no tidy breakpoint ladder; it has a handful
of purpose-specific queries, and two of them sit one pixel apart (767/768) on
purpose.

### The real breakpoints

| Query | What it does |
|---|---|
| `max-width: 767px` | **Ledger tables restack as cards.** `<thead>` is absolutely positioned off-screen and each `<td>` renders its `data-label` as the field name. This is why every `<td>` needs `data-label`. |
| `max-width: 768px` | Ledger cell padding drops to 12px left/right; `.dt-pagination` becomes a column, left-aligned. |
| `max-width: 640px` | Toshi panel goes **fixed full-screen** (100vw/100vh, radius 0) with a sticky composer. Wizard nav restacks to a prev/next row with the progress dots above it. `.setup-banner` becomes a column. |
| `min-width: 640px` | Wizard plan cards go from 1 column to **exactly 3**. |
| `min-width: 641px` | Toshi panel returns to its 16px radius floating form. |
| `max-width: 480px` | Toshi confirmation-card padding tightens to 14px. |

Note the 767/768 pair: the restack (767) fires *before* the padding change (768)
stops applying, so there is no width at which a table is both a card list and a
padded table.

### Capability queries, not width queries

The system gates interaction on **input capability**, which matters more than
screen size:

- `(hover: none) and (pointer: coarse)` — on touch devices, links and buttons
  inside `.ds-table-ledger` and `.ds-grid-marks` are forced to the 44px minimum.
- `(hover: hover) and (pointer: fine)` — **hover states are only defined inside
  this query** for `.dashboard-menu-item` and `.sidebar-group-header`. Don't write
  a bare `:hover` for nav; a touch device would latch it.

### Touch targets

`--d-touch-target-min: 44px` is a declared token, and `min-height: 44px` is baked
into `.ds-btn` unconditionally — not just on mobile. Checkboxes are 18px minimum.
Honour 44px for anything tappable.

### Fluid layout, no breakpoint needed

- KPI tiles: `repeat(auto-fill, minmax(220px, 1fr))` — reflows with no query.
- Tables scroll horizontally inside `.ds-table-wrap` (`overflow-x: auto`) above
  the restack width.
- The marks grid freezes its first two columns with `position: sticky` (left 0 and
  left 48px) over a `rgba(255,255,252,0.97)` background.
- Toshi's maximised modal is `min(96vw, 1280px)` × `min(92vh, 920px)`.
- The wizard is capped at 880px; `.manual-wizard-plan-grid` handles its own
  columns.

### ≥1280px — the desktop docking layout (`toshi-ui.css`)

Real rules from `toshi-ui.css`, the companion stylesheet. Above 1280px the app
stops being a scrolling page and becomes a **fixed-height three-column shell**:

- `body` gets `overflow: hidden` and `height: 100vh`; `#app` becomes a full-height
  flex column; `.navbar` is `position: sticky` at `z-index: 31`, `flex-shrink: 0`.
  `:root` declares `--nav-height: 83px`.
- `main` is `flex: 1; min-height: 0; overflow: hidden` — so the page itself never
  scrolls.
- **`.sidebar` becomes `position: static`** with `height: 100%`,
  `overflow-y: auto`, `align-self: stretch` — it scrolls independently.
- `.dashboard-content-area` / `.superadmin-content` also scroll independently
  (`overflow-y: auto; height: 100%; flex: 1`), and their right margin and width
  overrides are cleared so Toshi occupies real layout space rather than overlaying.
- **`[data-toshi-root]` becomes `position: static`, `width: 380px`**, full height,
  explicitly overriding the `position: fixed !important` from the main stylesheet.
  `.toshi-panel` goes to `width: 380px`, `height: 100%`, **`border-radius: 0`**, a
  left border `1px #E5E7EB`, shadow `-4px 0 16px rgba(0,0,0,0.04)`, and a 3px
  `#22C55E` top rule via `::before`. `.toshi-messages-area` is the only scroller
  inside it.
- **Collapsed state** is driven by `body.toshi-collapsed`: the root animates to
  `width: 0` (`transition: width 0.25s ease`), the panel drops its border and
  shadow, and `.toshi-toggle-wrapper` — a zero-width flex child — expands to
  **28px**, revealing a 28×64px green tab (`#22C55E`, radius `8px 0 0 8px`,
  widening to 36px on hover). The toggle is `display: none` unless collapsed; the
  floating `.toshi-pill` is hidden at this width *except* when collapsed, where it
  returns `position: fixed` at 24px from bottom-right.
- Content dropdowns (`select`, `.dropdown-menu`, `[class*="select"]`, …) are
  floored at `z-index: 50` so they render above the docked panel.

Below 1280px none of this applies: Toshi reverts to the fixed floating widget from
the main stylesheet, and full-screen at ≤640px.

### The "Pulse" override layer — a real conflict to know about

`toshi-ui.css` is **not only responsive rules**. It re-styles components the main
stylesheet already defines, and it loads after, so these win wherever it is
published:

| Element | `dashboard-refresh.css` | `toshi-ui.css` (Pulse) |
|---|---|---|
| `.ds-kpi-card` | hairline ring, radius 14 | real drop shadow `0 1px 3px / 0 4px 12px`, border `#F1F5F9` |
| `.ds-kpi-card:hover` | 1px lift | **`translateY(-2px)`** + green-tinted shadow |
| `.ds-kpi-value` | `--d-dark` ink | **`#22C55E` green**, plus a `d-countUp` entrance staggered 0.05–0.20s |
| KPI icon chip | 44×44, radius 12 | 40×40, radius 10 |
| `.ds-table thead` | opaque sticky header | `backdrop-filter: blur(12px)` over `rgba(255,255,255,0.85)` |
| Suggestion chips | — | `.toshi-suggestion-chip`: 1.5px green outline, pill, transparent fill |

**So the "hover lifts exactly 1px" and "KPI values are always dark ink" rules in
Visual foundations hold for the base system, but not on a surface where
`toshi-ui.css` is loaded.** Check which layer applies before matching a screen.
The UI kits here follow the base stylesheet.

### Sidebar behaviour — still partly unknown

`toshi-ui.css` resolves the ≥1280px question above: the sidebar becomes
`position: static`, full-height and independently scrollable. What it does **not**
contain is any **narrow-width** sidebar rule — no collapse, drawer, or off-canvas
behaviour at tablet or phone widths exists in either stylesheet. Below 1280px the
sidebar simply keeps whatever the Blade layout gives it. **Supply the sidebar
Blade partial** if that behaviour needs documenting; it is not inferable from CSS.

### Gap: reduced motion

There is **no `prefers-reduced-motion` query anywhere** in the stylesheet, yet the
system runs three looping animations (LIVE badge sheen 3.4s, its pulsing dot 1.8s,
loading-dot bounce) plus staggered entrance animations. That is a genuine
accessibility gap in the source, not an omission in this documentation. Adding one
is a product decision, so nothing was invented here.

---


- **System (decided 2026-10-01): Lucide** (lucide.dev, **ISC licence**; it's the maintained fork of Feather). It replaces the three sets in the app today:
  - **Sidebar:** `x-icons.sidebar`, hand-copied Feather at stroke 2 with a duotone fill layer.
  - **KPI cards:** `x-ds-kpi-card`, Heroicons v1.
  - **Legacy files:** 44 church-era SVGs in `resources/assets/icons/`.

  The spec and the full current → Lucide mapping (sidebar, groups, dashboard, other roles, legacy files) is `concepts/icons/index.html`. The handoff is `guidelines/handoff-2026-10-01-icons.md`.
- **Sizes:**
  - **16:** dense tables, badges, inline text.
  - **20 (default):** nav, buttons, rows, inputs.
  - **24:** empty states in a 48px box, page headers.
  - The icon is never the target; its control is at least 44px.
- **Stroke:** 2 at 16 and 20, 1.75 at 24 and up. Caps and joins are round. No fills, no duotone.
- **Colour:** always `currentColor`, set through `--d-icon`, `--d-icon-muted`, `--d-icon-active`, `--d-icon-danger`, `--d-icon-warning` and `--d-icon-on-accent`. Meaningful icons must reach at least 3:1. **#94A3B8 is retired for icons.**
- **The React `Icon` component still uses Heroicons v1 paths.** It remains until the app migrates, and is scheduled to move to Lucide names in the same PR series.
- **No icon font, no sprite sheet, no PNG icons.** Icons are inline SVG (Blade: `blade-lucide-icons`; Vue: `lucide-vue-next`), both behind one wrapper per stack. Nothing is filled.
- **Icons never carry colour of their own.** They inherit text colour, except inside the 44×44 KPI chip, where a 10% tint sits behind a solid stroke.
- **Brand marks are the exception**: WhatsApp, Google Drive and Slack render at full official colour and must never be recoloured, outlined or monochromed. They are in `assets/brand/` and as React components.
- **Unicode as icon:** two cases only — `▴` as the table sort arrow (`.dt-sort-arrow`) and `—` as the empty KPI value.
- **Emoji as icon:** only inside Toshi's chips and plan steps. Not in product chrome.
- **Favicons and app icons** are a separate, specified system — all generated from `klassapp-icon.svg`. See `guidelines/favicons.md`; it exists because legacy GeGoK12 orange icons and a broken manifest shipped to production after the rebrand.

---

## Docs and brand materials (Task D, 2026-09-29)

**Source:** `main @ a083be197980`. The docs site today is **Docsify 4**: the `vue.css` theme plus `docs/shared/docsify-klassapp.css`, with `docs/community/` and `docs/dev/` each having an `index.html` and a `_sidebar.md`. It has no search plugin and no right-hand contents list; `subMaxLevel: 2` puts the headings in the sidebar. A GitBook link sits in the community sidebar.

- **`styles/docs.css`** (imported from `styles.css`) adds the `kd-*` docs components. **Shell:** header, search, left nav, "on this page" contents, breadcrumbs, pager and footer. **Content:** callouts (`--tip`, `--note`, `--warning`, `--toshi`), `kd-steps`, `kd-shot` (browser or phone frame, plus `kd-mark` numbered markers, a `kd-hl` highlight box and `kd-legend`), `kd-kbd`, `kd-btnref`, `kd-path`, `kd-wa` ("Do this on WhatsApp instead"), `kd-role--{admin,teacher,bursar,parent,student}`, `kd-feedback`, `kd-cards`, `kd-faq`, `kd-trouble` and `kd-release`/`kd-tag`.
- **New tokens (docs scope):**
  - `--kd-link` #1D4ED8 (replaces #1E6FD9 for links on paper).
  - `--kd-text-2` #475569.
  - `--kd-mark` #B91C1C.
  - Callout pairs for tip, note, warning and Toshi.
- **Contrast:** every pair is ≥ 4.5:1, calculated from hex and not browser-measured.
- **Usage rules:**
  - The Toshi callout is only for capabilities listed as shipped in `docs/internal/role-capability-matrix.md`.
  - A screenshot gets at most 3 markers and 1 highlight box, and every marker needs a legend line.
  - Callouts use a full tint with a 1px border, never a coloured left border alone.
  - Role badges carry text; colour is never the only signal.
- **Cards:** the `Docs` group (`guidelines/docs-*.card.html`).
- **Concepts:**
  - `concepts/docs/`: the shell and 6 templates at 1280/375, plus screenshot and accessibility rules.
  - `concepts/whatsapp-howto/`: the WhatsApp how-to format.
  - `concepts/quickstart/{admin,teacher,parent}.html`: A4, black-and-white, with a QR code.
  - `concepts/social/`: social templates.
  - `concepts/voice/`: the voice and copy guide.
- **Template:** `templates/pitch-deck/PitchDeck.dc.html` (16:9 master with 6 layouts).
- **Open-source line, verbatim everywhere:** "The source is public on GitHub; supported self-hosting opens after an independent security review."

**Update 2026-09-29 (v2): one VitePress site for all docs.** Read at `main @ 9f3297546d8c`. Docsify and GitBook are being replaced by a single VitePress site at `/docs/`, in two sections sharing one shell: **Help** (`/docs/help/`, user manuals, with `/help` redirecting there) and **Community** (`/docs/community/`, contributors).
- **Implementation:** the `kd-*` classes in `styles/docs.css` remain the HTML reference. The VitePress build is a theme that maps these tokens onto `--vp-c-*` variables, plus one Vue component per docs component.
- **Callouts:** these use VitePress's built-in `::: tip`, `::: info` and `::: warning` containers, restyled with a full 1px border.
- **Where the files are:** the scaffold, `MIGRATION.md` and `HANDOFF.md` are in the Task D ZIP (`klassapp-handoff-2026-09-29-docs/`), not in this design system.
- **Quick starts:** the QR codes now encode `https://klassapp.xyz/help`.

**Default avatar (2026-09-30).** When a person has no photo, `<x-profile-photo>` shows their initials instead of `default-user.jpg`.
- **Initials:** from `userprofiles.firstname` + `userprofiles.lastname`, in white Sora 600 at 40% of the tile size. An empty lastname gives one letter; both empty give the grey tile. **Never `users.name`**, which is the login handle.
- **Colour:** one of four existing tokens, aliased as `--d-avatar-1…4`: blue #1E6FD9, green #15803D, amber #B45309 and navy #1E293B. All clear 4.5:1 with white letters (calculated from hex, not browser-measured).
- **Choosing the colour:** `user id % 4`, so it stays the same even if the name is corrected.
- **Excluded:** red, and any pink/blue gender coding.
- **No name:** a blank #64748B tile with `aria-hidden`. No silhouette or gendered image, ever.
- **Shape (decided):** follows the photo rule, so a photo and its initials never change shape. It's square with a 12px radius (8px at the 32px size) everywhere except the round 32px nav trigger.
- **Sizes:** nav 32, tables 32, lists 40, feedback 64, profile 128/192. On the ID card (100) and bus pass (75) the colour is written as inline hex.
- **Specs:** card `guidelines/brand-avatar-default.card.html`; implementation spec `guidelines/handoff-2026-09-30-avatar-initials.md`.

**Profile pages (2026-09-30).** Student, staff and parent profiles share one layout:
- **Header:** `<x-profile-photo>` at 128 (64 on mobile), the name from `userprofiles`, and a meta row: class or "No class", admission number, and a status badge.
- **Tabs:** 44px tall, with an `--d-accent` underline. The order and the default tab depend on the viewer: the bursar opens on Fees, the class teacher on Attendance.
- **Hidden tabs and fields:** anything a role never sees is left out of the page, and the server doesn't send it.
- **Empty states:** every tab has one.
- **Parent profile:** leads with the children, each with Report card and Fees links.
- **Files:** concepts `concepts/profiles/index.html` and `concepts/profiles/account-dashboard.html`; spec `guidelines/handoff-2026-09-30-profiles.md`.

**Account card:** the sidebar-footer menu uses a 40px avatar and the name and email on one line each, truncated on the right. Below them come 44px rows: Change password, Edit profile, Settings, a divider, and Log out in red. It works from the keyboard and closes on a click outside.

**Dashboard v2 (`concepts/profiles/dashboard-v2.html`):**
- **Header:** a small "Welcome back, {Firstname}" over the school name as the title, with a labelled academic-year select on the right.
- **Setup:** one dismissible setup banner. Once dismissed, progress moves to a "Finish setup 3/7" chip at the bottom of the sidebar.
- **Layout:** quick-action cards on the left, a School snapshot on the right.
- **Sidebar:** grouped, with uppercase labels and "New" badges (at most 2).
- **Empty states:** one pattern: icon, one line, one action.
- **Sign-up page:** value points on the left, Google and email on the right.

**Dashboard v1 with Toshi off (superseded by v2):** nothing on the page mentions Toshi. In its place is a setup card ("{done} of {total} steps done", from one source), a "No students yet" empty state, and five quick actions. The greeting uses the first name in normal case, and the academic-year selector has a label and shows on mobile too.

**Brand rule (2026-10-01): KlassApp is a global product.**
- No country-only or level-only framing in marketing, blog, social or docs. KlassApp is for schools from nursery to secondary.
- Sample data uses the seeded demo schools: "Demo Junior School" (nursery and primary) and "Demo Senior School" (O and A level), and currency comes from settings.
- The founding-schools offer has no stated limit.
- Product features specific to one curriculum, such as LIN, PLE or UCE fields, can be described in that school's own settings and docs. They're never the headline.

**Logo on docs and files (2026-10-01):** use the **stacked light** lockup (`klassapp-stacked-light.svg`) on documents, quick starts and the docs site, not the horizontal one. Sizes: formal documents 19 mm, quick starts 16 mm, docs header 44 px, docs footer 48 px. Social posts use stacked too (96 px at 1080 wide), except the wide X post (1600 × 900), which keeps the horizontal lockup. App screens and emails keep the horizontal lockup.

**Formal document template (2026-10-01).** `templates/formal-document/FormalDocument.dc.html` covers policies, terms, DPAs, letters and invoices on A4 or US Letter.
- **First page:** a header with the stacked logo and a metadata block.
- **Later pages:** a running header (icon, title, version), and a footer on every page ("Page X of Y").
- **Styles:** Sora and DM Sans heading and body styles, the `KA Table` style, and signature blocks.
- **Draft:** a "Draft for review" band, tag and watermark.
- **Signed:** a `signed` state shows the acceptance block (name, title, school, version, date and time with UTC offset, reference, acceptance ID) in place of handwritten signature lines. The in-app click-to-accept page is `concepts/contracts/dpa-accept.html`.
- **Word:** a `.dotx` spec with exact fonts, fallbacks, sizes and hex colours is in `guidelines/handoff-2026-10-01-formal-documents.md`.

---

## Index

| Path | What it is |
|---|---|
| `styles.css` | The entry point — `@import`s only. Link this one file. |
| `tokens/colors.css` | Brand, surface, text, dark and Toshi palettes (verbatim `:root`). |
| `tokens/fonts.css` | Sora + DM Sans from Google Fonts. |
| `tokens/typography.css` | Families, weights, the rem size scale, tracking. |
| `tokens/spacing.css` | The spacing literals that actually recur. |
| `tokens/radii-shadows.css` | Radii, the ring-based elevation set, motion values. |
| `styles/base.css` | Body font, link colours. |
| `styles/classes.css` | **The app's real stylesheet, verbatim** — 364 selectors: shell, `ds-*` primitives, ledger tables, marks grid, Toshi, manual wizard. |
| `assets/brand/` | Logo (icon, horizontal, stacked, reversed) and the WhatsApp, Google Drive, Slack marks. See `assets/README.md`. |
| `guidelines/favicons.md` | Favicon / app-icon spec: required sizes, head tags, `manifest.json` fields. |
| `guidelines/*.card.html` | 25 foundation specimen cards (Colors, Type, Spacing, Brand). |
| `ui_kits/school-dashboard/` | Click-through admin dashboard: home, students, fees, marks. |
| `ui_kits/toshi-assistant/` | The docked AI assistant panel, interactive. |
| `ui_kits/onboarding-wizard/` | The manual school-setup wizard: 4 steps, plan picker, review card. |
| `SKILL.md` | Agent-skill entry point. |

### Components

All nine come from `@klassapp/ds@0.0.0` — that inventory **is** the component list.

| Component | Group | What it is |
|---|---|---|
| `Button` | actions | 6 variants × 3 sizes; green primary. |
| `Card` | surfaces | White surface, 4 paddings, 4 shadows, optional title. |
| `Badge` | surfaces | 9-variant status pill. |
| `FormGroup` | forms | Label + input/select/textarea + error/help. |
| `KpiCard` | data-display | Dashboard metric tile with tinted icon chip. |
| `Table` | data-display | Ledger table: density, selectable, sortable, mobile cards. |
| `WhatsAppMark` | brand | Official WhatsApp mark. |
| `GoogleDriveMark` | brand | Official Google Drive mark. |
| `SlackMark` | brand | Official Slack mark. |

**Intentional additions**

- `Icon` (`components/core/`) — a wrapper around the Heroicons v1 outline family the app already inlines, added so sidebars and toolbars don't need hand-drawn SVG. **To be re-pointed at Lucide** (decided 2026-10-01).
- Token files for typography, spacing, radii, shadows and motion — the app repeats these as literals; naming them changes no value.
- `--d-transition-normal` — referenced (with a fallback) by the app CSS but never declared upstream; declared here as `200ms ease`.

**Not added on purpose:** Toast, Avatar, Tabs, Tooltip, Dialog, Switch, Checkbox, Select. The source defines none of them. Raw classes exist for some of the gaps (`.ds-input`, `.dt-checkbox`, `.ds-empty-state`, `.ds-save-indicator`, `.ds-dot`) — use those rather than inventing a component.

---

## ⚠ Known accessibility violation — frozen Pulse block (owner decision required)

**`.ds-kpi-value` renders `#22C55E` text on `#FFFFFF` — ~2.3:1 contrast, failing WCAG AA (4.5:1 for text; even the 3:1 large-text floor).** It lives in the **FROZEN — Pulse design system** section of `packages/toshi-ui/resources/css/toshi-ui.css`, which forbids edits in Toshi-panel PRs. Frozen means "don't change casually", **not** "exempt from the system's own accessibility rules". This design system does not fix it and does not endorse it: the block's owner must make a deliberate call — most direct fix is `color: var(--d-accent)` (`#15803D`, 5.02:1), the same correction already applied to buttons.

Same block, lesser drifts from the system's own rules (not accessibility failures): KPI cards use drop shadows instead of the hairline ring; hover lifts `-2px` vs the `--d-lift` `-1px`; `.ds-kpi-label` 0.85rem and table `th` 0.75rem vs tokens 0.78rem / 0.72rem.

**Suggestion chips:** the canonical name is **`.toshi-chip-suggestion`** (clay kit — matches the Toshi palette and the rest of Piece 2). **`.toshi-suggestion-chip`** (Pulse, green, in the frozen block) is **deprecated** — do not use it in new designs.

## Rules that will silently break your work

1. **No Tailwind — and that includes its `box-sizing` reset.** `mt-4`, `flex`, `grid`, `p-4`, `text-sm` do not exist in this CSS; the app loads Tailwind separately and this bundle does not ship it. The consequence that actually bites: Tailwind's preflight sets `*{box-sizing:border-box}`, and the source CSS declares `box-sizing` on only two selectors, so **any rule combining a fixed size with a border depends on that global reset**. Measured example: `.toshi-pill-avatar` declares `width:38px` plus a 1px border and renders 38px in the app, but **40px in a bare page**. Form controls and buttons are unaffected (the UA stylesheet already gives them border-box), so the risk is in `div`/`span` containers. **Put `*{box-sizing:border-box}` in any page that consumes this stylesheet.** Use inline styles with the tokens for layout glue, and `ds-*` classes for anything the system covers.
2. **Primary is green.** Blue buttons are wrong.
3. **`.ds-btn-md` has no rule** — the default size is the `.ds-btn` base size. Pass `sm`/`lg` deliberately.
4. **`Table`'s `striped` and `hover` props do nothing** — declared upstream, never referenced. Row hover comes from `.ds-table-ledger` itself.
5. **`Table` emits `.ds-table-ledger`, not `.ds-table`.** Both exist in the CSS; the component uses the former.

## What I need from you

**Nothing blocking.** Every earlier item is closed:

- **Stacked-lockup gap — signed off** at 58px light / 60px dark (20% of icon height).
- **Light-background lockup — closed.** `klassapp-horizontal-light.svg`, the
  mechanical extraction from the official `klassapp-stacked.svg`, is accepted as
  final. No further official export expected.
- **Wizard Blade source and `toshi-ui.css`** — supplied and fully incorporated.

Optional, would improve fidelity but nothing is waiting on them:

- **Two PHP constants** the wizard reads but that weren't in the supplied files:
  `OnboardingStepsService::STUDENT_SIZE_OPTIONS` and
  `SchoolCategorySeeder::CATEGORIES`. The kit uses plausible values, flagged in
  `ui_kits/onboarding-wizard/README.md`.
- **`OnboardingStepsService` itself**, to confirm the canonical step order and the
  `OPTIONAL_STEPS` list (the kit infers order from the Blade `@elseif` chain plus
  the component's checkpoint logic).
- **The sidebar Blade partial**, if narrow-width sidebar behaviour matters — no
  CSS for it exists in either stylesheet.
- **The marketing landing page**, to verify the tower concept's fit against the
  section it replaces (`concepts/toshi-tower/`). The concept is grounded in real
  tokens and real brand marks, but its composition against the live section is
  unverified.

One product decision still open: there is **no `prefers-reduced-motion` query** in
either stylesheet, despite the looping animations and the Pulse layer's staggered
`d-countUp`. Your call; nothing was invented here.

---

## Caveats

- **Logo:** three official files (`klassapp-icon.svg`, `klassapp-horizontal-dark.svg`, `klassapp-stacked.svg`) plus five derived variants — reversed icon, horizontal light/transparent, stacked light/dark. All derived files reuse official path data byte-for-byte: plates deleted, counters converted to true `evenodd` knockouts, art translated. No letterform was re-set, so **no typeface was ever identified or guessed** — both official files are already outlines. Extending the wordmark (a tagline, a new word) would need the real typeface from the design source; nothing here answers that. **Both logo decisions are now closed:** the stacked gap is signed off at 58/60px, and `klassapp-horizontal-light.svg` is accepted as the final light lockup.
- **`klassapp-stacked.svg` is misnamed — it is a horizontal lockup**, not a stacked one (icon and wordmark sit side by side on a 2048×1117 light plate). It was **kept under its original name** because that canvas and opaque plate suit OG/Twitter meta images and something may reference it; check before renaming. The genuinely stacked files are the derived `klassapp-stacked-light.svg` / `-dark.svg`.
- **One open judgement call:** the stacked lockup's vertical gap. No source has a stacked arrangement, so it is derived as 20% of icon height (58/60px) — corroborated by the official files' own horizontal mark-to-wordmark gaps of 59px and 67px. Everything else in both stacked files is mechanical. **Signed off — confirmed final.**
- **Fonts are Google-hosted**, not self-hosted binaries — that is how the source ships them. No font substitution was needed.
- **Toshi message bubbles** are composed from the two bubble tokens; their exact Blade markup wasn't in the bundle.
- **The onboarding wizard kit** is built from the `manual-wizard-*` CSS alone — the bundle ships that styling but no Blade markup, so step *content* (which fields, which copy) is my reconstruction from the class names. Structure, colours and spacing are verbatim; the wording is not.
- **Brand imagery beyond the logo** — no photography, illustration or pattern exists. If a layout needs art, ask rather than generating it.
