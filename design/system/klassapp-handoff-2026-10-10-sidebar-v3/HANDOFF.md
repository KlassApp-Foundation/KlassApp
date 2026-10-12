# Handoff: sidebar v3 for the admin shell, 2026-10-10

- **Concept:** `concepts/admin-mvp/sidebar-v3.html`, with switches for screen size and state. It runs `sidebar-v3-frame.html`, whose `window.__m()` returns the measurements used in the checks below.
- **Cards:** five in the Design System tab under "01 · Build these (Admin MVP)": 1280 × 680, nested open with the account menu, the rail, the phone drawer, and card surface v2.
- **Replaces:** the sidebar parts of `handoff-2026-10-08-admin-mvp-combined.md` (Shared rules → Shell, and PR7). Both are marked superseded there.
- **Rules from the combined handoff still apply:** currency from settings, example phone numbers, no country-specific wording, Lucide icons, the default avatar, and Toshi off by default.
- **Measured:** see "Measured in a browser" under Sizes. The concept page re-measures itself whenever it loads.
- **Missing attachments:** the TinyFish DevTools measurements and the new screenshot of the current build weren't in the upload. The TinyFish column below is read off the two TinyFish screenshots, which are 2×. Re-attach both and I'll replace those estimates with your measured values.

## Structure
**Top level: 14 rows, 4 section labels.** Labels are always visible and never collapse. Children appear only where an item has children.

| Section | Rows (children) |
|---|---|
| (none) | Dashboard |
| People | **Students** (All students, Admissions, Health records) · Teachers and staff · Parents |
| Academics | **Classes and subjects** (Classes and streams, Subjects, Timetable, Calendar) · Attendance · **Exams and reports** (Exams and marks, Grading, Report cards) · Library |
| Money | Fees · Unmatched payments (count badge) |
| School | WhatsApp · Approvals (count badge) · Transport · **Settings** (School details, Academic years and terms, Data exports) |
| Account menu | Edit profile, Change password, **Help**, Log out |

Every item in the brief is still there and within two clicks: one for a top-level row, two for a child or for Help.

**Why these parents:**
- **Classes and subjects:** together they're the school's structure, and the timetable is built from them.
- **Exams and reports:** grading and report cards only exist after marks are entered.
- **Settings:** the setup pages admins rarely open.
- **Calendar:** sits next to the Timetable, under Classes and subjects (decided 2026-10-11). **WhatsApp** is a plain row with no children.
- **Students:** see the next table.

**Where Admissions, Health records and Transport go, re-checked by role:**

| Item | Admin | Teacher | Bursar | Decision |
|---|---|---|---|---|
| Admissions | expects it next to Students: an accepted application becomes a student | doesn't see it | doesn't see it | **Child of Students**, with a count of applications waiting |
| Health records | a list of students' notes, opened rarely | sees allergies in the student profile, not as a list | doesn't see it | **Child of Students**, admin and head teacher only |
| Transport | routes, vehicles and which students ride: a school service, not a student list | may need the route for a child | expects transport *charges* in Fees, not routes | **Moved out of Students** to a top-level row under School. Transport charges stay fee items in Fees. |

**Rows by role:** rows a role can't use are **left out**, never shown disabled. A parent left with one child becomes a plain row; with none, it disappears.
- A **teacher** sees Dashboard, Students (own classes; no children rows), Attendance, Exams and reports, Library and Messages.
- A **bursar** sees Dashboard, Students (list only), Parents, Fees, Unmatched payments and Messages.

**The group holding the current page opens by itself; every other parent is closed.** Opening a parent by hand lasts until you change page. **Nothing about groups is saved.**

**School name and search** (there's no top bar any more):
- **School name (2026-10-11):** above the page title, in the page header, and more prominent than the old small grey line:
  - **School logo first, when the school has uploaded one:** 28 × 28px, `object-fit:contain`, 6px radius, 1px #E2E8F0 border, `alt` = the school name. **No logo: no placeholder.** The name starts the line.
  - **Name:** "Demo Junior School" at **15px, weight 600**, #0F172A, line-height 1.3.
  - **Term:** "Term 3, 2026" at 13px, weight 500, #475569 (7.58:1 on white), 10px after the name.
  - **Wrapping:** the line wraps on narrow panels and never truncates the school name.
  - **The page title stays the h1.** The school line is text above it, not a heading.
- **Search box:** a 36px search button on the right of the page header, next to the Academic year select. It reads "Search students, classes, pages" and shows **Ctrl K** (⌘K on a Mac; no hint on touch devices). Either the button or the shortcut opens the command palette.
- **On a phone:** search is an icon button in the 56px top bar, and the school name and Academic year sit at the top of the drawer.

## Sizes (laptop)
| Part | Value |
|---|---|
| Sidebar width | 248px expanded; 64px rail |
| Top | 48px: logo 26px tall, collapse button 34 × 34 (`panel-left-close` / `panel-left-open`) |
| Section label | 11px/700, uppercase, letter-spacing .08em, #64748B (4.55:1 on #FAFAF5); padding 8 top and 2 bottom; 24px block |
| Row | **34px tall, 2px gap, so a 36px pitch**; 10px side padding; 18px Lucide icon, then a 10px gap and the 14px/500 label; 8px radius |
| Hover | background `rgba(15,23,42,.05)` |
| Active | background `rgba(15,23,42,.08)`, weight 600, icon #0F172A, `aria-current="page"` |
| Keyboard focus | an **inset** 2px `--d-blue` (#1E6FD9) ring, so the scrolling list never clips it |
| Parent | a `chevron-right` at the right, turning 90° when open (no motion under reduced motion); a `<button>` with `aria-expanded` and `aria-controls` |
| Children | 30px rows, 2px gap, no icons, 14px/400 #334155; under a 1px #E2E8F0 guide line 19px from the row's left edge (under the icon's centre); child text lines up with the parent's label |
| Count badge | 11px/700 pill, #FEF3C7/#78350F (Unmatched, Admissions) or #DBEAFE/#1E3A8A (Approvals); a collapsed parent shows its children's total |
| Footer, pinned | a 1px #E7E5DF top border; the quiet line "Toshi, your school's AI assistant, is coming soon" (12px, #64748B, 16px K mark, 2 lines); then the account card, 48px tall, with a 32px initials avatar, name (13.5px/600) and email (12px) truncated on the right, and a 28px `ellipsis-vertical` |
| Account menu | **224px wide, 8px above the card**, never a full-screen overlay. Identity row, then 40px items: Edit profile, Change password, Help, a separator, Log out. Focus starts on Edit profile; Esc closes and returns focus to the card |

**Measured in a browser on 2026-10-10** (your preview; `getBoundingClientRect` via the concept's `window.__m()`, each size on its own freshly loaded frame):

| Size, state | Mode | Top-level rows fully visible | Row height / pitch | Child row height / pitch | Account card (top–bottom) | Page scrolls | Targets under 24px |
|---|---|---|---|---|---|---|---|
| 1209 × 690, Dashboard | expanded, 248px | **12 of 14** | 34 / 36 | n/a | 632–680, inside | no | none |
| 1280 × 680, Dashboard | expanded | **12 of 14** | 34 / 36 | n/a | 622–670, inside | no | none |
| 1280 × 720, Dashboard | expanded | 13 of 14 | 34 / 36 | n/a | 662–710, inside | no | none |
| 1440 × 900, Dashboard | expanded | 14 of 14 | 34 / 36 | n/a | 842–890, inside | no | none |
| 1280 × 680, Report cards (Exams and reports open) | expanded | 10 of 14; the list scrolls | 34 / 36 | 30 / 32 | 622–670, inside | no | none |
| 1280 × 680, account menu open | expanded | 12 of 14 | 34 / 36 | n/a | menu 224px wide, top at 374, inside | no | none |
| 900 × 700, no saved choice | **rail, 64px** | 12 of 14 | 34 / 36 | n/a | 650–690, inside | no | none |
| 900 × 700, flyout open | rail | 12 of 14 | 34 / 36 | n/a | flyout 220px, inside and on top | no | none |
| 375 × 812, phone drawer | drawer, 319px | 8 of 14; the list scrolls | **48 / 50** | **44 / 46** | 746–802, inside | no | none |

- **A saved choice wins:** at 900 × 700 with a saved `{"rail":false}` in `ka:sidebar:v3` (left by an earlier toggle), the sidebar opens expanded. That's the specified behaviour.
- **Contrast** is still calculated from hex, not measured.

**Why 34px rows:** 36px rows with a 38px pitch fit only 11 rows at 680, below your minimum of 12. Rows are 34px with a 36px pitch instead. That's still above the 24px minimum WCAG 2.2 AA sets for targets.

## Card surface v2 (tokens in `tokens/surfaces.css`, class `.ds-surface`)
| Token | Value |
|---|---|
| `--d-card-surface-border` / `--d-card-surface-border-hover` | #E2E8F0 / #B6D9C2 |
| `--d-card-dot-color` · `--d-card-dot-size` · `--d-card-dot-gap` | rgba(15,23,42,.11) · 1px · 14px |
| `--d-card-dot-fade` | 62% (a mask fades the dots out from the top) |
| `--d-card-hover-tint` · `--d-card-hover-tint-reach` | rgba(34,197,94,.10) from the top-right corner · 70% |
| `--d-card-transition` | 150ms ease-out; none under `prefers-reduced-motion` |

- **Decorative only:** both layers sit behind the text (`isolation:isolate`, pseudo-elements at `z-index:-1`). The darkest dot over white is about #E2E4E7, so card text keeps its own contrast (#334155 is 10.4:1 on white).
- **Hover and focus:** the tint also shows on `:focus-within`, so keyboard users see it.
- **Use it on:** the dashboard KPI tiles, the quick actions, the people-list header card and the empty states. Add `.ds-surface--static` to keep the dots without the hover tint, on cards that aren't interactive.

## TinyFish measured against ours
Read off the two TinyFish screenshots (2×, so halved). **These are estimates until the DevTools file arrives.**

| | TinyFish (estimated) | KlassApp v3 |
|---|---|---|
| Sidebar width | about 208px | 248px (our labels are longer) |
| Row pitch | about 30px | 36px (34 + 2) |
| Child row pitch | about 27px | 32px (30 + 2) |
| Icon / label | about 18px / 14px | 18px Lucide / 14px DM Sans |
| Section label | about 11px uppercase, grey | 11px/700 uppercase, #64748B |
| Hover and active | soft grey pill, grey for active | the same pattern; active adds weight 600 and `aria-current` |
| Nesting | thin guide line, no child icons, 1 parent open | the same; the current page's parent opens by itself |
| Footer | pinned wallet card plus account | pinned Toshi line plus account |
| Top bar | none; the page title sits in the content panel | none; title, school, search and year sit in the page header |
| Cards | faint dot print, warm glow on one card | card surface v2: dots fading down, green corner tint on hover |

---

## PRs, smallest first
**Storage, in every PR:** the new build reads and writes **one key, `ka:sidebar:v3`, holding `{ "rail": boolean }`**. Old saved collapsed-group states are ignored: remove every read of the old sidebar keys, so returning users never see everything collapsed. Groups aren't stored at all.

### S1 · `feat(shell): sidebar v3 structure + pinned footer`
- **Layout:** the sidebar is a 3-row grid (`auto / minmax(0,1fr) / auto`), 100vh tall. The nav list scrolls inside itself (`overflow-y:auto`, `overscroll-behavior:contain`). The footer is pinned.
- **Remove the top bar** on desktop. Its contents move:
  - the school name and Academic year go to the page header;
  - search becomes the page-header search button;
  - the account goes to the sidebar footer.
- **Account menu:** as in the sizes table. Help is added; Settings comes out, because it's in the nav.
- **Accept** (measured with `getBoundingClientRect`, at 1280 × 680 and 1209 × 690):
  1. The account card's box is entirely inside the viewport (`top ≥ 0`, `bottom ≤ innerHeight`), and the page doesn't scroll (`scrollHeight ≤ innerHeight`).
  2. The open account menu is 224px wide and entirely inside the viewport, with the name and email visible.
  3. With a parent open, the list scrolls while the footer's box doesn't move.
  4. Storage: `localStorage` holds only `ka:sidebar:v3` for the sidebar, and a user with an old key set sees the default state.

### S2 · `feat(shell): rows, sections and nesting`
- **Build:** the 14 rows, 4 labels, children, count badges, hover, active, focus and chevrons from the tables above. Rows are filtered by role.
- **Accept:**
  1. **Top-level rows:** the admin shell has exactly **14** top-level rows (`[data-top]`).
  2. **Pitch:** row-to-row pitch (`row[n+1].top − row[n].top`) is 36px ±1, and the row height is 34px. Child pitch is 32px and child height 30px.
  3. **Visible without scrolling:** at least **12** top-level rows fully visible at 1280 × 680. Report the actual count at 1209 × 690, 1280 × 680, 1280 × 720 and 1440 × 900.
  4. **Opening by page:** opening a child page (Report cards) shows its parent open and every other parent closed. Reloading does the same, so nothing is stored.
  5. **Accessibility:** every row is at least 24px tall. Labels reach 4.5:1 (#64748B on #FAFAF5). Focus is visible on every row with the keyboard only.
  6. **By role:** a teacher and a bursar get the rows listed above, with no disabled rows.

### S3 · `feat(shell): icon rail`
- **Rail:** 64px wide, toggled by the collapse button and saved in `ka:sidebar:v3`.
  - **Top:** the K mark replaces the logo, with the expand button under it.
  - **Section labels:** become 1px dividers.
  - **Rows:** 40 × 34, icon only, each with an `aria-label` and a tooltip on hover and focus (right side, #0F172A, 12.5px).
  - **Parents:** open a **flyout** to the right with a heading and their children (`role="menu"`); Esc or a click outside closes it. Counts become a dot.
  - **Tooltips and the flyout are rendered outside the scrolling nav list** (as children of `<body>` or `.sb`), with `position:fixed`: `left` is the rail's right edge + 8px, `top` comes from the row's `getBoundingClientRect()`, and the flyout is kept 8px inside the viewport. Inside `.sb-list` they'd be clipped, because `overflow-y:auto` also clips sideways.
  - **Footer:** the K mark (the Toshi line moves into its tooltip) and the 32px avatar. The account menu opens to the right of the rail.
  - **Avatar tooltip (2026-10-11):** hovering or focusing the rail avatar shows a tooltip with the user's **name and role**, e.g. "Mucunguzi Moses · School admin". It uses the same tooltip as the rows: right of the rail, vertically centred on the avatar, `role="tooltip"`, `pointer-events:none`, and Esc dismisses it.
  - **Avatar label:** the button's `aria-label` is the **same text**, so screen readers hear it without the tooltip. The button keeps `aria-haspopup="menu"` and `aria-expanded`.
  - **Role text:** the user's role label, as on the Teachers and staff list ("School admin", "Head teacher", "Bursar"…). Keep the order name, then role.
  - **Expanded sidebar:** the name and email are visible on the card, so it has no tooltip and no extra `aria-label`.
- **Tablet, 761–1279:**
  - **1024–1279** starts expanded, so 1209 is expanded.
  - **761–1023** starts as the rail.
  - Once the user toggles it, `ka:sidebar:v3` wins.
  - With Toshi on, the Toshi launcher row replaces the quiet line (Toshi phone handoff, T1).
- **Accept:**
  1. The rail is 64px wide (`getBoundingClientRect().width`). At 900 × 700 with no saved choice, the sidebar starts as the rail; at 1209 it starts expanded.
  2. The flyout's rect is entirely inside the viewport and on top: `document.elementFromPoint` at its centre returns the flyout.
  3. Every rail row has an accessible name, and its tooltip shows on keyboard focus.
  3a. **The rail avatar:** its `aria-label` is "{name} · {role}", the same tooltip text appears on hover and on keyboard focus, the tooltip is entirely inside the viewport, and Esc hides it.
  4. The account menu is entirely inside the viewport at 900 × 700.
  5. Toggling and reloading keep the choice.

### S4 · `feat(shell): phone drawer`
- **Drawer, ≤ 760:** a light (#FFFFFF) drawer, `min(320px, 100vw − 56px)` wide, opened by the 44px menu button in a 56px top bar.
  - **Top:** logo and close, then the school name and a 48px **Academic year** select with a visible label.
  - **List:** **48px rows, 44px children**, the same nesting as an accordion.
  - **Footer:** pinned, with a 56px account card.
  - **While open:** the page behind is `inert` and doesn't scroll. Esc and the scrim close it. Focus returns to the menu button.
- **Accept** (at 375 × 812):
  1. Rows are 48px and children 44px.
  2. The account card is inside the viewport.
  3. No sideways scroll.
  4. The Academic year select is 48px tall and labelled.
  5. The menu opens exactly once per tap (the earlier double-toggle bug).

## Decisions (2026-10-11)
1. **Settings children:** "Academic years and terms" and "Data exports" both exist today as pages under Settings. **The developer maps the two rows to their existing URLs.** If terms have no page of their own, the row is "Academic years" and terms stay inside it.
2. **Calendar:** moves next to Timetable, as the **fourth child of Classes and subjects**. Messages becomes a **plain WhatsApp row** with no children. The top level stays **14 rows under 4 labels**.
3. **Rows:** **keep 34px rows** with a 36px pitch.
4. **Rail avatar:** a tooltip and an `aria-label` with the user's name and role (S3).
5. **School name:** a bold 15px / 600 line in the page header, with the school's logo in front when one exists (Structure, "School name and search").

**Not re-measured after these changes:** your preview stopped responding. The measured table above is from before them. None of these changes affects row count or heights:
- Calendar moves inside an existing group.
- Messages becomes a row of the same height.
- The school line is in the page header, not the sidebar.

`concept/sidebar-v3.html` re-measures itself when it loads. It now has two extra states: **Rail, avatar tooltip** and **School name with logo**. The logo in that state is a dashed placeholder marked "LOGO", standing in for a school's uploaded file. KlassApp has no school logos to show.
