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
| A1 | Every admin page, top bar | **School name** at 20px/600 competes with the page title (26px h1) and has no logo | See "School name" below: 15px/600 #0F172A, 28px logo first if uploaded, term after it in 13px/500 #475569. **The top bar stays until after 17 October**, so this lands in the top bar. | 15 min | A |
| A2 | Sidebar, expanded, 1280 | At 232px, "Classes and subjects" and "Unmatched payments" ellipsize | Width 232 → **248px**, plus **a row shows a chevron or a count chip, never both**. Numbers in "Label truncation" below. | 20 min | A |
| A3 | Sidebar, Academics and School | Calendar sits under Messages | **Calendar** becomes the 4th child of **Classes and subjects**, after Timetable. **Messages** becomes a plain **WhatsApp** row, with no chevron and no children. Still 14 top-level rows under 4 labels. | 10 min | A |
| A4 | Sidebar, Settings | Children point at placeholder URLs | **Academic years and terms** and **Data exports** link to the **existing Settings pages**. If terms have no page of their own, the row is "Academic years" and terms stay inside it. | 10 min | A |
| A5 | Folded rail, 761–1279 | Spec addition | **Every icon shows its name** in a tooltip on hover **and** keyboard focus after **300ms**. Parents keep their flyout. **New:** the avatar shows "{name} · {role}" (e.g. "Mucunguzi Moses · School admin") in the same tooltip, and the avatar button's `aria-label` is the same text. Esc hides any tooltip. | 30 min | A |
| A6 | Students, Teachers and staff, Parents lists | Checkbox column always shown; large square initials tile | See "People lists" below: **Select mode** reveals the checkboxes and bulk bar; **28px circle** avatar; **48px** rows; **sticky header**. | 60 min | A |
| A7 | Lists and empty states | Card surface v2 on more than the dashboard | **Dotted print and hover tint only on the dashboard KPI tiles and quick actions for now.** Remove `ds-surface` from the people-list header card and every empty state. | 10 min | A |
| A8 | Screens I couldn't see | Unknown (pack missing) | Check each against the spec values in "A8 checklist" below and fix any difference. | 30 min | A |
| B1 | Top bar | Kept for the demo | After 17 Oct: remove the top bar. The school line moves above the page title (same 15px/600 treatment). **Search** (Cmd K) moves to the sidebar header as a 34px row "Search" with a ⌘K/Ctrl K hint. | 45 min | B |
| B2 | Sidebar | Truncation fallback | Any label that still ellipsizes gets a `title` with the full label, and the rail tooltip shows it in full. **No wrapping:** wrapping would break the 34px row and the 12-row minimum. | 10 min | B |
| B3 | People lists, mobile | 28px circle on cards | Check on a real phone that the 56px card rows still read well with the 28px circle. If not, use a **32px** circle on cards only. | 10 min | B |

**Total for A:** about 3 hours (3h 05m by the estimates above).

## A1 · School name: final treatment
- **Until 17 October** it stays in the top bar. That's your preference, and it means no layout change before the demo.
- **Line:** `[logo 28 × 28] Demo Junior School · Term 3, 2026`.
  - **Logo:** only when the school has uploaded one. 28 × 28px, `object-fit:contain`, 6px radius, 1px #E2E8F0 border, `alt` = the school name. **No logo, no placeholder.**
  - **Name:** **15px, weight 600**, #0F172A, line-height 1.3.
  - **Term:** 13px/500, #475569 (7.58:1 on white), 10px after the name.
- **Why 15, not 20:** the school feels present through the logo and the weight, not the size. At 20px it rivals the 26px page title, the line people actually need to read.
- **The page title stays the h1.** The school line isn't a heading.
- **Narrow widths:** the line wraps and never truncates the name.
- **After 17 October (B1):** the same line moves above the page title when the top bar goes.

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

## A8 checklist (screens in the missing pack)
| Screen | Spec value to check |
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
