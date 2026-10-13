# Polish list: built sidebar v3 and shell, 2026-10-12

**For:** the developer, about 3 hours. **A** = before the 17 October demo, **B** = after.

**What I could and couldn't review.** `claude-design-pack.zip` (the staging screenshots and `INDEX.md`) **didn't arrive**. Only the two console reference screenshots reached the project, so the browser, width and commit for each staging screen are unknown.
- **From your description:** items A1, A2, A6 and A7 come from what you described: the school name at 20px in the top bar; ellipsized labels at 232px; checkboxes and square tiles in the lists; and the surface on more cards than planned.
- **To check against the spec:** A8 is a checklist of exact spec values for the screens I couldn't see (Students open, rail flyout, account menu, phone drawer, KPI surface). It covers them without inventing findings.
- **Re-attach the pack** and I'll replace A8 with real differences.

**Assumptions (safest reading):**
- The build follows `handoff-2026-10-10-sidebar-v3.md` except where noted.
- The sidebar is 232px wide.
- Parents with children carry a count chip as well as a chevron.
- The top bar is still there.

## The list
| # | Page and state | What differs from the spec | Exact fix | Est. | Pri |
|---|---|---|---|---|---|
| A1 | Every admin page, top bar | **School name** at 20px/600, no logo, no term | See "School name" below: **17px/700** #0F172A, 28px logo first if uploaded, term in grey after it. Checked against the 26px/600 page title. | 20 min | A |
| A2 | Sidebar, expanded, 1280 | At 232px, "Classes and subjects" and "Unmatched payments" ellipsize | Width 232 → **248px**, plus **a row shows a chevron or a count chip, never both**. Numbers in "Label truncation" below. | 20 min | A |
| A3 | Sidebar, Academics and School | Calendar sits under Messages | **Calendar** becomes the 4th child of **Classes and subjects**, after Timetable. **Messages** becomes a plain **WhatsApp** row, with no chevron and no children. Still 14 top-level rows under 4 labels. | 10 min | A |
| A4 | Sidebar, Settings | Children point at placeholder URLs | **Academic years and terms** and **Data exports** link to the **existing Settings pages**. If terms have no page of their own, the row is "Academic years" and terms stay inside it. | 10 min | A |
| A5 | Folded rail, 761–1279 | Spec addition | **Every icon shows its name** in a tooltip on hover **and** keyboard focus after **300ms**. Parents keep their flyout. **New:** the avatar shows "{name} · {role}" (e.g. "Mucunguzi Moses · School admin") in the same tooltip, and the avatar button's `aria-label` is the same text. Esc hides any tooltip. | 30 min | A |
| A6 | Students, Teachers and staff, Parents lists | Checkbox column always shown; large square initials tile | See "People lists" below: **Select mode** reveals the checkboxes and bulk bar; **28px circle** avatar; **48px** rows; **sticky header**. | 60 min | A |
| A7 | Lists and empty states | Card surface v2 on more than the dashboard | **Dotted print and hover tint only on the dashboard KPI tiles and quick actions for now.** Remove `ds-surface` from the people-list header card and every empty state. | 10 min | A |
| A8 | Staging screenshots (`261fad07`) | 20 real differences | See "A8 · Differences seen on staging" below: 11 are A, 7 are B, plus 2 to check. | about 2 h 20 min (A items: about 1 h 45 min) | A |
| B1 | Top bar | Kept for the demo | After 17 Oct: remove the top bar. The school line moves above the page title (same 17px/700 treatment). **Search** (Cmd K) moves to the sidebar header as a 34px row "Search" with a ⌘K/Ctrl K hint. | 45 min | B |
| B2 | Sidebar | Truncation fallback | Any label that still ellipsizes gets a `title` with the full label, and the rail tooltip shows it in full. **No wrapping:** wrapping would break the 34px row and the 12-row minimum. | 10 min | B |
| B3 | People lists, mobile | 28px circle on cards | Check on a real phone that the 56px card rows still read well with the 28px circle. If not, use a **32px** circle on cards only. | 10 min | B |

**Total for A:** about 3 hours (3h 05m by the estimates above).

## A1 · School name: final treatment (revised 2026-10-12)
- **Until 17 October** it stays in the top bar, so there's no layout change before the demo.
- **Line:** `[logo 28 × 28] Demo Junior School  Term 3, 2026`, vertically centred in the 56px bar.
  - **Logo:** only when the school has uploaded one. 28 × 28px, `object-fit:contain`, 6px radius, 1px #E2E8F0 border, 10px before the name, `alt=""` (the name next to it already says it). **No logo, no placeholder.**
  - **Name:** **17px, weight 700**, Sora, #0F172A, line-height 1.25, letter-spacing −0.01em. Contrast 17.85:1 on white.
  - **Term:** 13px/500, DM Sans, #475569, 10px after the name, on the same baseline. Contrast 7.58:1 on white.
- **Against the 26px page title:**
  - **Size and weight:** the title is 26px/600 Sora, about 1.5× the name's size. The name is now the second-largest text on the page, but still clearly below the title.
  - **Height:** at 17px the name's cap height is about 12px, against about 19px for the title.
  - **Bolder but smaller:** the name is bolder (700 against 600) but much smaller, so the eye still lands on the title first. The logo makes the school line feel like an identity, not a second heading.
  - **What would break it:** going above 18px, or matching the title's size at a heavier weight, would make the two compete. **Don't go above 18px.**
- **The page title stays the only h1.** The school line is a `<p>` (or a `<div>` with the name in a `<span>`), not a heading.
- **Phones (below 760), revised 2026-10-13: the school name is never hidden.** On a phone it's the only place the school's identity shows.
  - **Phone top bar, 56px, exactly these four:** **menu** (44px) · **school name** (16px, weight 700, Sora, #0F172A, one line, ellipsis, `title` with the full name, no logo, no term) · **Search** (44px icon, `aria-keyshortcuts`) · **avatar** (44px button, 32px circle initials, `aria-label` "{name} · {role}", opens the drawer at the account card).
  - **Page title:** stays in the page body as the `h1`, not in the bar.
  - **Drawer:** no second school name (the bar already shows it); the drawer keeps the Academic year select.
  - **Toshi:** when the Toshi mobile launcher (T1 in the Toshi handoff) ships, it goes between Search and the avatar as a **44px icon-only K button** (`aria-label` "Open Toshi"). That leaves the name about 160px at 375. **The Toshi handoff's labelled "Toshi" pill doesn't fit with this bar and needs updating before T1 is built.** Between 761 and 1279 the line wraps the term under the name and never truncates the name.
- **After 17 October (B1):** the same line, at the same 17px/700, moves above the page title when the top bar goes. The 8px gap to the title keeps the two reading as a pair.

## A2 · Label truncation, with the numbers
Row space at **248px**:
- The list has 10px padding each side, leaving a 228px row. The row has 10px padding each side, leaving 208px.
- The 18px icon plus its 10px gap leaves **180px** for the label and anything after it.

| Row | Trailing item | Space left for the label | Label width (est.) | Fits? |
|---|---|---|---|---|
| Classes and subjects | chevron 16 + gap 10 | **154px** | ≈ 135px | yes, 19px spare |
| Unmatched payments | count chip ≈ 20 + gap 10 | **150px** | ≈ 128px | yes, 22px spare |
| Unmatched payments at 232px, with chip | | 134px | ≈ 128px | 6px spare: one more digit (10+) ellipsizes |
| Classes and subjects at 232px, chevron and chip | | ≈ 108px | ≈ 135px | **no**: this is the build's ellipsis |

- **Fix:**
  - **Width:** 248px.
  - **Chevron or count, never both:** a parent row shows the chevron; its count moves to the child (Admissions 5 on the Admissions row).
  - **Collapsed parents:** a parent whose children have counts shows the 7px amber dot while collapsed, as the rail already does.
- **Not chosen:**
  - **Shorter labels:** "Classes", "Unmatched" lose meaning for a new admin.
  - **Wrapping:** it breaks the 34px row height.
- **Rows visible:** **unchanged at 12 of 14 at 1280 × 680.** Width doesn't change heights, and that figure was measured at 248px on 2026-10-10.
- **Label widths are estimates:** DM Sans 14px/500 at about 0.48em per character, not measured. Confirm in a browser: `[...document.querySelectorAll('.sb-list .t')].filter(e=>e.scrollWidth>e.clientWidth)` must return an empty list at 248px.

## A5 · Rail tooltips (added to the S3 rail spec)
- **Rows:** hover or keyboard focus shows the row name after **300ms**, right of the rail, vertically centred on the row, `role="tooltip"`, `pointer-events:none`. Leaving or blurring hides it at once; Esc hides it.
- **Parents:** they keep their 220px flyout. The tooltip doesn't show while the flyout is open.
- **Avatar (new):**
  - **Tooltip:** the same component, with text "{first name} {last name} · {role}". The role label is the one on the Teachers and staff list ("School admin", "Head teacher", "Bursar"…).
  - **Button:** `aria-label` has **exactly the same text**, so screen-reader users get it without the tooltip. It keeps `aria-haspopup="menu"` and `aria-expanded`.
  - **Expanded sidebar:** the name and email are visible there, so no tooltip and no extra label.
- **Accept:** each row and the avatar show their tooltip on focus within 300–400ms. The avatar's `aria-label` equals its tooltip text. Every tooltip is fully inside the viewport at 900 × 700.

## A6 · People lists (the developer is building them now)
- **Checkboxes: Select mode.**
  - **Default:** no checkbox column.
  - **Select button:** a **Select** button (44px, `list-checks` icon, `aria-pressed`) sits at the start of the toolbar, after the search. It's shown only on lists that have bulk actions: Students, Teachers and staff, Parents.
  - **Turning it on:** reveals the checkbox column (a 44 × 44px hit area around an 18px box, with select-all in the header) and the **bulk bar** in place of the toolbar. The bar is sticky at the top of the list and the button reads **Done**.
  - **Bulk bar:** "Select people", becoming "{n} selected" (`role="status"`), then the actions, **at least Export and Message**, and Cancel.

    | List | Bulk actions |
    |---|---|
    | Students | Export, Message parents, Move to class |
    | Teachers and staff | Export, Message, Send invites |
    | Parents | Export, Message, Send WhatsApp opt-in |

    Actions are disabled (#94A3B8 on #1E293B, with `disabled`) until at least one row is selected.
  - **Leaving:** Done, Cancel or Esc leave Select mode, clear the selection and return focus to the Select button.
- **Initials tile:**
  - **In lists:** a **28px circle**, initials at 11px (40% of the size), colour by `id % 4`.
  - **Photos in lists:** also 28px circles, so a photo and its initials still match within a list.
  - **Profile headers:** keep the **large square** (112px, 64px on mobile, 12px radius).
  - **Spec change:** this narrows the "square everywhere" rule from 2026-09-30 (`handoff-2026-09-30-avatar-initials.md`) for lists only.
- **Row height:** **48px** on desktop: a 28px avatar plus 6px padding top and bottom, with one line of name. Teachers show the role on the same row in a second column, not a second line. Mobile cards are at least **56px** tall.
- **Sticky header:**
  - **Table header:** `position:sticky; top:0; z-index:3`, background #F8FAFC, 1px #E2E8F0 bottom border.
  - **In Select mode:** the bulk bar is sticky above it at `top:0`, and the table header sticks below the bar (`top: var(--bulk-h, 0)`).
  - **Scroll container:** the page, never the table, so there's no inner scrollbar.
- **Accept:**
  - With Select off, the checkbox column is absent. Select on shows the checkboxes and bulk bar; Esc leaves.
  - The list avatar is 28px with `border-radius:50%`, and the profile header avatar is square.
  - Rows are 48px (`getBoundingClientRect`).
  - The table header stays visible after scrolling 600px.
  - Every target is 44px or more, and nothing scrolls sideways at 375 and 1280.

## A7 · Card surface v2 scope (confirmed)
- **For now:** **only the dashboard KPI tiles and the quick-action tiles** carry `.ds-surface`:
  - the dotted print in the top part of the card, masked out toward the bottom;
  - the green corner tint and stronger border on hover, over 150ms, off under reduced motion.
- **Not on:** the people-list header card and empty states, which go back to plain white cards with a 1px #E2E8F0 border. Revisit after the demo.

## A8 · Differences seen on staging (Chrome, `main @ 261fad07`, 2026-10-12)
**Decided 2026-10-13:** the B items stay after the demo; A8.14 is superseded by the header handoff; A8.20 is dropped; A8.1 as revised in the table.
**Received:** three screenshots: the expanded sidebar with the account menu open, the folded rail with the Students flyout, and the expanded sidebar at rest. All are at a 1210px-wide window.
- **Not received:** the phone drawer, the KPI hover state and the expanded sidebar with Students open. Those stay on the checklist further down.
- **Sizes are estimated from the images:** the scale is 1.67 image pixels per CSS pixel, taken from the 232px sidebar. Treat them as about ±2px, and confirm with `getBoundingClientRect` before fixing.

| # | Page and state | What differs from the spec | Exact fix | Est. | Pri |
|---|---|---|---|---|---|
| A8.1 | Every page, right edge | **The orange Toshi edge tab is still showing** with Toshi in preview. | **Decided 2026-10-13:** no edge tab in **preview** mode at any width; hidden **below 1280 in every mode**; it stays only at 1280+ in **onboarding** and **assistant** modes (`toshi-ui.css`, `dashboard-refresh.css`). | 15 min | **A** |
| A8.2 | Sidebar footer, account card | **Name in capitals** ("DANIEL M…"), and a **grey silhouette** avatar. | Name from `userprofiles.firstname` + `lastname`, in normal case. **No `text-transform`;** if the stored value is in capitals, apply `Str::title()` at display time. Avatar: the initials tile (`<x-profile-photo>` fallback, colour from `id % 4`). | 15 min | **A** |
| A8.3 | Account menu, open | **Same capitals and silhouette** in the menu's identity row ("DANIEL MUKASA"). The menu is about **253px wide** and hangs **about 70px past the sidebar** over the chart. | Same name and avatar fix as A8.2. `width:100%` of the account card's container (224px at a 232px sidebar), `left:0; right:0`, never wider than the sidebar. | 10 min | **A** |
| A8.4 | Account card, menu open | A **2px blue ring stays on the card** while the menu is open, even after a mouse click. | Use the ring for `:focus-visible` only. While the menu is open, show `aria-expanded="true"` as a #F1F5F9 background, not a ring. | 5 min | B |
| A8.5 | Account menu items | **Help** is in the account menu. The spec has it as a row in the sidebar's School group. | **Keep Help in the account menu**, because the build has already removed it from the sidebar, which saves a row at 680px. Only update the spec. | 0 | (spec) |
| A8.6 | Sidebar footer | A **bell with sound waves** (`bell-ring`) left of the account card, with no count. | Use Lucide `bell`. Add a 16px count chip (#B91C1C, white 11px/700) only when there are unread items, and `aria-label="Notifications, {n} unread"`. The button is 44px. | 10 min | B |
| A8.7 | Sidebar, School group | **Messages has a chevron** (children). | Decision 2: **Messages becomes a plain "WhatsApp" row** (`message-circle`, no chevron). Calendar moves to the 4th child of Classes and subjects (A3). | 10 min | **A** |
| A8.8 | Sidebar, Money group | **Fees has a chevron**, and Unmatched payments isn't a row of its own. | The spec has the MONEY label with **two rows: Fees and Unmatched payments**, the second with its count chip. If the build nests Unmatched payments under Fees, lift it out. | 10 min | **A** |
| A8.9 | Sidebar, rows | The row pitch is **about 34px**, so the rows touch. The spec is 34px rows on a **36px pitch**. | Add `margin-block:1px` to each row (or `gap:2px` on the list). **Re-measure: at least 12 of 14 top-level rows visible at 1280 × 680.** | 5 min | B |
| A8.10 | Folded rail | **Hover looks the same as active.** The hovered Students tile has the same green background as the active Dashboard tile. | Hover: #F1F5F9. Active: #DCFCE7 with #14532D icon. Focus-visible: 2px #1E6FD9 ring. **Never use green for hover.** | 5 min | **A** |
| A8.11 | Folded rail | The rail is **about 56px wide**, against the spec's 64px. | `width:64px` (icons stay 20px, tiles 40px centred). | 5 min | B |
| A8.12 | Rail flyout (Students) | The flyout is **about 200px** wide (spec 220), and its item pitch is about 32px (spec 34 / 36). The heading "Students" is in the same weight as the items. | `width:220px`; items 34px on a 36px pitch; the heading is 11px/700 uppercase #475569 (matching the section labels) and not a link. | 10 min | B |
| A8.13 | Rail, avatar | **No tooltip or label could be checked** from the screenshot. The avatar is the grey silhouette. | Decision 4: `aria-label` and a hover or focus tooltip "Mucunguzi Moses · School admin" (name · role) after 300ms. Avatar as in A8.2. | 10 min | **A** |
| A8.14 | Top bar | **The school name is about 22px/600**, and the greeting line below repeats it ("Demo Junior School · Term III, 2026"). | A1: the top bar shows the name at **17px/700**, the logo first if one exists, and the term in grey after it. **Remove the school name from the greeting line**; the greeting keeps only "Good evening, {first name}". | **Superseded 2026-10-13** by the header decision in `handoff-2026-10-13-header-profile-dashboard.md` (H1 Plan B, or H0 Plan A as the fallback). | **A** | **(see H0/H1)** | A8.15 | Top bar | About **65px tall** (spec 56); Search shows **⌘K** on every platform. | `height:56px`. The key hint is `⌘K` on macOS and iPadOS, otherwise `Ctrl K` (`navigator.userAgentData?.platform`, falling back to `navigator.platform`). | 10 min | B |
| A8.16 | Dashboard header | The **Academic year** select is about **42px** tall. | `min-height:44px`. | 2 min | **A** |
| A8.17 | Quick actions | "Send report cards on WhatsApp" **wraps to two lines** at 1210, and its icon sits apart from the centred text. | Shorten the label to **"Send reports on WhatsApp"**, with `white-space:nowrap` and `text-overflow:ellipsis` plus a `title`. Left-align icon and text in every quick action, so wrapping never separates them. | 10 min | B |
| A8.18 | KPI "Report cards ready" | The subline wraps ("of 66 · End of Term / Examination"), so this tile's text sits lower than its neighbours'. | One line, with ellipsis and a `title`. Use the exam's short name if one exists ("End of term"). | 5 min | B |
| A8.19 | KPI tiles and quick actions | **The dotted surface shows** on both, as specified, and only there (A7). | No change. Check the hover tint on a real pointer (not in the screenshots). | n/a | (check) |
| A8.20 | Fees collection chart | **No green "collected" bars are visible.** Only the dashed expected outlines show, even though the Fees tile says 18% collected (UGX 9.5M). | Check the collected series is bound and drawn in #15803D *inside* the outlines. If it's zero for the months shown, that's correct; otherwise it's a bug. | **Moot (2026-10-13):** the demo fee data changed on staging. | **A** | (dropped) | Screen | Spec value to check |
|---|---|
| Expanded, Students open | Parent row 34px tall with a 36px pitch. Children 30px tall with a 32px pitch, 14px/400 #334155. The guide line is 1px #E2E8F0, 19px from the row's left edge, and children are indented 9px past it. The current child has #DCFCE7 / #14532D. |
| Folded rail with a flyout | Rail 64px, icons centred in 40px buttons, section labels become 1px dividers. Flyout 220px wide, 8px right of the rail, top aligned to the parent row, kept inside the viewport, `role="menu"`, Esc closes it and returns focus to the parent. |
| Account menu | 224px wide, opening **upward** from the account card in the pinned footer (to the right of the rail in rail mode). 44px menu rows. The sidebar never moves, and the menu is entirely inside the viewport at 1280 × 680. |
| Phone drawer | 319px wide, rows 48px with a 50px pitch, children 44px with a 46px pitch. School switcher at the top, 48px tall. Focus is trapped, Esc and the scrim close it, and the page behind doesn't scroll. |
| KPI tiles, surface and hover | Dots: radial-gradient 1px #CBD5E1 on a 12px grid, top 55%, masked to transparent. Hover: a radial #22C55E tint at about 8% from the top-right corner, border #E2E8F0 → #CBD5E1, 150ms; no movement under reduced motion. Text still 4.5:1 or more over the dots. |

## Reference used (principles only)
From the two console screenshots, measured at 2× and halved:
- **Sidebar:** about 215px wide; rows about 27px tall with a 30px pitch; 11px uppercase section labels.
- **Nested items:** a 1px guide line on the left.
- **Footer:** a pinned card and the account row.
- **Cards:** dots only in the upper part, and a corner glow on the highlighted card.

**Ours:**
- **Sidebar:** 248px wide, with **34px rows** at a 36px pitch.
- **Section labels and footer:** the same 11px uppercase labels and pinned footer.
- **Cards:** the same top-only dots, in KlassApp tokens and Lucide icons.
- **Why taller rows:** 34 > 27 because KlassApp's admins include non-technical school staff on laptops with trackpads. That's still 12 of 14 rows at 1280 × 680.
