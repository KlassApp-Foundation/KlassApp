# Merged handoff: shell, dashboard deltas, Toshi frame, profiles, tables, bulk message, and the staging polish list (2026-10-14)

**This file replaces** `handoff-2026-10-13-header-profile-dashboard.md` (v2.1) and `handoff-2026-10-14-surface-toshi-profiles.md` (items 1–7). Both are marked superseded and kept for history. It also covers **item 8**: the review of today's staging captures.

- **Sources:**
  - **Staging captures of 14 Oct**, 18 images in `uploads/claude-design-bundle (1)/` (Chrome, demo school, admin). Main's tip at capture time was `95888896`; the staging commit isn't exposed.
  - **Not visible in them, because not merged yet:**
    - **#1067:** title-case names, initials tile, neutral Toshi handle, 44px year select;
    - **#1076:** the seven cheap fixes;
    - **#1074:** the teacher profile.
- **Concepts:**
  - `concept-v21/` (header, dashboard, shared profile);
  - `concept-v22/` (page surface, sidebar, Toshi frame, KPI reflow, profiles, table deletes, bulk message).
- **Rules still in force:**
  - global wording;
  - currency from school settings;
  - phone numbers are examples only;
  - Gender is Female, Male or Not specified;
  - **no orange anywhere, Toshi included**;
  - 44px targets, except 36px nav rows and 36px compact buttons inside cards;
  - text contrast at least 4.5:1 (calculated from hex);
  - keyboard access;
  - no animation under reduced motion.
- **Measured:** nothing in this file was measured on staging; it's read from the screenshots. Pixel sizes below are estimates from 1280-wide captures. The v21 concept was measured in a browser (26 states). The v22 concept measures itself when it opens.

## Decisions, current as of 2026-10-14
1. **Toshi handle:** it **stays visible**, collapsed by default, **from 1280px up in every mode**, as a quiet neutral handle with no orange (S3b). It's hidden below 1280px in every mode. **This reverses the 13 Oct decision to remove it in preview.** The polish handoff (A8.1) and the Toshi phone handoff now say the same.
2. **Header:** **H0 (Plan A, the top bar stays) is the demo path.** H1 (Plan B, no top bar on laptops) moves to **after the demo**, unless the developer says it's cheap in the shared layout.
3. **Already merged and live: D1–D4 and P1.** This file specifies **only the remaining deltas** for them (section 2).
4. **Earlier decisions still standing:**
   - B items wait until after the demo;
   - the school-name item is superseded by the header work;
   - the old fees-chart item is moot;
   - T1 is a 44px K-only button between Search and the avatar;
   - only the parent profile has Delete;
   - the bulk composer comes after the demo.

## Which brief items this covers
| Brief item | Where |
|---|---|
| 1 Page surface and layout rules | S1 |
| 2 Sidebar highlight rules | S2 |
| 3 Toshi frame (handle, panel, maximise, colours) | S3a, S3b, S3c |
| 4 KPI tile responsive rules, gender per class | S4a, S4b, plus the D1/D4 deltas |
| 5 People profiles | S5, plus the P1 deltas and P2–P5 |
| 6 Destructive actions in tables | S6 |
| 7 Bulk message composer | S7 |
| 8 Review of staging | section 1, the polish list |
| Header | H0 (demo), H1 (after) |

---

## 1 · Polish list from today's staging (item 8)
**A** = before 17 October; **B** = after. **First, merge #1067 and #1076**, then re-check the rows marked *(#1067)*: they may already be fixed. Estimates are for one developer.

### A · before 17 October
| # | Page and state | What differs | Exact fix | Est. |
|---|---|---|---|---|
| A1 | Dashboard, **Toshi panel open** (images 2, 14) | KPI tiles narrow to about 100px. "Generate" prints over "of 66 · End of Term", "Send on WhatsApp" over "Examination", and "Record payment" wraps under the money line. | **S4a:** the grid never makes tiles narrower than 176px (`minmax(max(176px,(100% - 4*gap)/5),1fr)`), so the row wraps to 2–3 columns. The tile is a container; the footer stacks (meta line, then action) below 260px. No absolute positioning in the footer. | 45 min |
| A2 | Dashboard, Toshi open (image 2) | The chart grid stays 2 columns in about 545px. The bars shrink to about 50px, and "Enter marks" wraps onto 2 lines beside the card title. | **S1 container query:** chart cards are 2 columns only when the content is at least 696px wide, otherwise 1. "Enter marks" stays on one line (`white-space:nowrap`); the title wraps first. | 20 min |
| A3 | Dashboard, **Students by gender** (images 1, 9, 14) | **Every class row is exactly 50/50,** the same as the school total, so the class rows appear to reuse the school split. **This is a data bug, not only CSS.** The 14px rounded bars touch, so they merge into one striped block. No counts per row; the legend shows "Girls 50% Boys 50% Not specified 0%". | **S4b.** **Data:** group by `class_id` and gender for each class. **Layout:** 8px between rows, 2px between segments, and a 16px school bar with a divider. Counts in words at the end of each row ("5 girls, 6 boys"), the same counts in the `aria-label`, and a legend with no percentages. | 45 min |
| A4 | Dashboard, **Fees collection** (images 1, 9, 14) | Value labels collide: "38.7M40.5M", "13.5M13.5M", and on the phone "27M27M". | Label **only the collected bar**, above it. Show the expected value in the tooltip and the `aria-label` ("Oct: 38.7M collected of 40.5M expected"). Below 420px chart width, hide the bar labels and keep the axis. | 20 min |
| A5 | Every page at 1280, right edge (images 1, 4, 8) | **Orange ▶ Toshi tab.** | **S3b handle:** 36 × 96px, white, 1px #CBD5E1 border with none on the right, 12px left radius, the 22px K mark, `aria-label="Open Toshi"`. Hover is #F0FDF4 with an #86EFAC border, plus the tooltip. It's collapsed by default **in every mode from 1280**, and hidden below. It sits inside the 40px Toshi gutter so it never covers the scrollbar. *(#1067)* | 30 min |
| A6 | Toshi panel, open (images 2, 5, 6, 7) | It starts below the top bar, with an **orange line on top** and **orange "Toshi"**. "Founding schools will be the first to try Toshi." appears twice. The composer placeholder ("Coming soon — messaging disabled in preview") wraps and is clipped at the bottom. | **S3a:** remove the top line, the name becomes #0F172A, and both promo sentences go. The placeholder becomes **"Messaging is paused"** on one line; the note under it already explains why. **S3b:** the panel is flush with the content column's top. | 30 min |
| A7 | Sidebar, active row (images 1, 3, 10) | The active row is a green pill **plus a left bar**. On Subjects (image 8) **the whole open group is tinted green and the child is a darker green pill: two greens.** | **S2:** one highlight only. The active row is an inset #DCFCE7 pill, #14532D text at 700, and **no left bar**. Its parent gets a bold label and **no background**. Delete the left-bar pseudo-element and the group background. | 30 min |
| A8 | Students list, **Select mode** (image 6) versus **all selected** (image 16) | **Two different bulk bars:** one has Export, Clear and Done; the other has Message parents, Move to class, Export and Clear. | **One bar in every state:** "{n} selected · **Message parents** · **Move to class** · **Export** · **Clear selection** · **Done**". Done leaves Select mode. While S7 isn't built, Message parents opens the existing WhatsApp page with the selection. 44px buttons, sticky under the toolbar. | 30 min |
| A9 | Student profile, **Parents and guardians** card (images 5, 11) | Reads "Not given · 0701498675 · WhatsApp Not opted in": an empty relation is printed as "Not given", the phone isn't a link, the WhatsApp status is plain text, and there's no avatar or "Link another". | **S5 row:** a 40px initials circle; the name; a relation chip **only when it's known**; "Primary contact" where it applies; the phone as a `tel:` link with a phone icon; a chip **On WhatsApp** (green) or **Not on WhatsApp** (grey); and a ghost **Message** on the right. "Link another" goes in the card header. **Never print "Not given" or "Not opted in".** | 40 min |
| A10 | Student profile, tabs (images 5, 11; sheet 5) | **5 dark filled pills** that **wrap onto 2–3 rows on the phone**; the concept has **8 underlined tabs**. **Decided:** the **underlined** style stays. Show **only the tabs that are built** (Overview, Academics, Attendance, Fees, Parents and guardians); add Health and support, Documents and Notes in that order when each ships, never as disabled or empty tabs. | **Underlined tabs:** 44px, #475569, the active tab #0F172A with a 3px #15803D underline, `role="tablist"` with arrow keys. **One row that scrolls sideways** on phones, with a right-edge fade. | 30 min |
| A11 | Student profile, KPI "Latest exam" (images 5, 11) | "52 C6": no "%" and an unlabelled grade. | "**52% · C6**", with a line under it naming the exam ("End of term") and, if available, the position in the class. | 10 min |
| A12 | Student profile header (image 5) | Actions are only Edit and More. P1 also specifies **Message parent**. | Add **Message parent** (44px, `message-circle`) between Edit and More, opening the WhatsApp page for the primary contact. | 15 min |
| A13 | Account card and menu (images 3, 10) | **Name in capitals** ("TARITWEBWA ERIC"), a **grey silhouette** avatar, and the menu is wider than the card and offset from it. | Name from `userprofiles.firstname` + `lastname`, no `text-transform`. A 36px initials tile. The popover is anchored to the card (same left edge, the sidebar's inner width, 8px above). *(#1067)* | 20 min |
| A14 | Every avatar without a photo: lists, account card and menu, rail, profile headers (images 3, 6, 10, 12, 16; sheet 5) | **Grey silhouettes**, where the concept has initials. | **Make the initials circle the default of `<x-profile-photo>`** (decided 2026-10-14; this replaces the square 12px frame for initials *and* photos). **Colour rule, unchanged from the avatar spec:** `user id % 4` picks #1E6FD9, #15803D, #B45309 or #1E293B, white letters (all at least 4.85:1, from hex); no name gives a plain #64748B circle with no letters and `aria-hidden`. Letters are the first of `userprofiles.firstname` plus the first of `lastname`, Sora 600 at 40% of the size. **Sizes:** 28 in lists, 32 in the rail and phone top bar, 36 on the account card, 40 in guardian rows, 88 on profile headers (64 on phones). Delete `default-user.jpg` from every non-print use. *(#1067)* | with #1067 |
| A15 | Top bar, **Search** (every laptop image) | The pill is about 24px tall: under 44px. | 44px tall and at least 240px wide, with the "Search" label and the `Ctrl K` or `⌘K` hint. The hit area is the whole pill. | 10 min |
| A16 | Top bar, **Academic year** on lists and profiles (images 5–8) | It sits in the top bar, about 36px tall, while on the dashboard it's in the page header. | One place: **the right of the page header row** on every page (H0), 44px tall with its visible label. Remove it from the top bar. *(#1067 fixes the height)* | 20 min |
| A17 | Dashboard heading (images 1, 9) | The greeting is about 34px bold, which dwarfs the 17px school name in the top bar. | **H0:** greeting `h1` at **26px/600**, with the term on the line beneath. The school name stays in the top bar at 17px/700. | 5 min |
| A18 | Subjects (image 8) | A **red Delete button on every row.** | **S6, Subjects table only for now:** the last cell is one 44px ⋮ menu (Edit, then a separator, then **Delete…**), with the confirm dialog. | 30 min |
| A19 | **Old teacher page** (image 7; teacher and parent pairs: both are still the old pages) | **Legacy page:** a 192px silhouette; tiny blue Edit and red Delete buttons; the name in capitals mid-page; Deactivate (red), "Exit" (teal text), Reset password (blue) and **Login As Teacher** (purple) in one row; DOB "09-09-1988"; and "--" for a missing address. | **Merge #1074** (the shared profile, teacher role: S5 + P1). **If it can't land before the demo:** keep the teacher page off the demo click path, and at minimum move Delete, Deactivate and Login As Teacher into one **More** menu, Delete last behind the confirm. | #1074, or 30 min fallback |
| A21 | **Phone drawer** (image 10, drawer pair) | **No header row and no close button**: the list starts straight at Dashboard, so the only way out is tapping the dimmed page. | Add a **56px header row**: the KlassApp horizontal logo (24px tall) on the left and a **44px Close (✕)** button on the right, `aria-label="Close menu"`. On open, focus moves to Close; Esc and Close shut the drawer and return focus to the menu button; the page behind is `inert`. The nav list scrolls under the header with the footer pinned. | 30 min |
| A20 | **Old parent profile** (image 13, 7 Oct; the page is unchanged) | Profession, Designation, Organization, Official address, **Annual income** and Qualification, all empty. Red Delete and Deactivate side by side. | Ideally S5 parent. **Fallback before the demo:** hide those seven fields on the page and its edit form, and move Delete and Deactivate into More. | 20 min fallback |

**Dashboard pair:** your sheet compares staging with the **old admin concept frame** (with quick actions). Judge the dashboard against the **v2.1 concept** (`concept-v21/`): quick actions removed, tiles as links with one action, Enter marks on the Performance card. Against v2.1, the dashboard gaps are A1–A4, A15 and A17, and B1–B4.

**Total for A:** about 7.5 hours, without #1074 and #1067. If time is short, do them in this order: A3 (data), A1, A5, A6, A7, A14 (with #1067), A21, A9, A10, A18, A19 fallback, A20 fallback, then the rest.

### B · after 17 October
| # | Page | What differs | Fix |
|---|---|---|---|
| B1 | Dashboard tiles | Text is centred and the actions are underlined text links, so collisions happen at narrow widths. | Left-align label, value and footer (S4a order). Actions are 36px compact buttons or links with a 36px hit area. |
| B2 | Report cards tile | Shows **Generate** and **Send on WhatsApp** together. | One action at a time, chosen by state (D1 delta). |
| B3 | Attendance trend | The axis runs W1–W8, but the data starts at W3, leaving an empty band on the left. | Start the axis at the first week with a register, or label W1–W2 "No registers". |
| B4 | Phone dashboard (image 9) | The stacked cards keep equal-height padding, leaving 40–60px empty at the bottom of each. | **D3 delta:** apply `align-items:stretch` only in the 2-column layout. |
| B5 | Phone dashboard | The Academic year select is both on the page and in the drawer. | Keep it only in the drawer on phones. |
| B6 | Recent activity | "SOCIAL STUDIES marks entered": the subject in capitals. | Show subject names in title case. |
| B7 | Folded rail (image 4) | The Students icon has an **orange dot** for admissions. | #B45309 with a 2px white ring. The tooltip and `aria-label` include "2 admissions waiting". |
| B8 | Students list with Toshi open (image 6) | The search box shrinks to "Search by", the chips are cut off at "Inactive (", and the ⋮ column is pushed out of view. | **S1:** the toolbar wraps (search full width, then chips scroll in their own row with a fade). The table keeps the ⋮ column and drops Parent or guardian first. |
| B9 | Students list | Names wrap onto two lines at narrow widths; the KLS numbers use a monospace font. | Names don't wrap (ellipsis with a `title`); KLS in DM Sans with tabular figures. |
| B10 | Phone students list (image 12) | The chips row is cut off at "Ina" with no cue; "Previous page" and "Next page" wrap. | A right-edge fade on the chips; 44px icon buttons for pagination, with "1–10 of 66" on one line. |
| B11 | Phone drawer (image 10) | The list ends at WhatsApp, with Approvals, Transport and Settings below and no scroll cue. | Check that the nav list scrolls while the footer stays pinned; add a bottom fade while there's more. |
| B12 | Student profile, phone (image 11) | The three KPI tiles stack full width. | 2 columns on phones, as in the v21 concept. |
| B13 | Student profile | There's no facts row, only "P.6 · KLS…". "Joined" says "Not recorded". Health, Documents and Notes are missing for the admin. | The facts `dl` (class, KLS, age and date of birth, gender, joined). Empty values read "Not given". Add the restricted tabs for admins (P1). |
| B14 | Student profile surface | It sits on a pale #F8FAFC panel, but the dashboard uses a white panel. | **S1:** one page surface, cards directly on it. |
| B15 | Subjects page (image 8) | One row per subject per class, names in capitals, "core" in lowercase, a separate Search button. | Batch-two B1: each subject once, with filters. |
| B16 | Other legacy tables | Red row buttons on Exams, Classes, Library, Transport and Fees structures. | Apply S6 table by table, about 15 min each. |

---

## 2 · Deltas for what's already live (D1–D4, P1)
- **D1:**
  - **Report cards:** one action by state (B2).
  - **Footer:** the tile footer reflows per S4a (A1).
  - **Alignment:** left-align (B1).
  - **Unchanged:** the whole-tile link and the focus ring around the whole tile.
- **D2:** no change.
- **D3:** stretch to equal height only in the 2-column layout (B4).
- **D4:** per-class data and the S4b layout (A3).
- **P1:**
  - **This week:** underlined tabs (A10), the Message action (A12) and the exam label (A11).
  - **Later:** the facts row and the restricted tabs (B13), and 2-column tiles on phones (B12).

## 3 · Header
- **H0 · Plan A (demo path).** The top bar is mostly live; the remaining work is A15, A16 and A17.
  - **Top bar:** KlassApp mark · school logo (28px, if any) · school name 17px/700 · term 13px/500 #475569 · Search (44px) on the right.
  - **Page header row:** the `h1` on the left (dashboard greeting at 26px/600, other pages their title at 26px/600) and the Academic year select on the right.
  - **Accept:**
    - the school name appears exactly once per page;
    - Search and the Academic year select are each at least 44px;
    - the Academic year select isn't in the top bar.
- **H1 · Plan B (after the demo).** Unchanged from v2.1: no top bar at 761px and up; the KlassApp logo at the top of the sidebar (K in the rail); the dashboard `h1` is the school name (28px/700, logo first); other pages get a quiet 15px/600 school line above a 26px/600 title. Search and the Academic year select move to the right of the header row. Its acceptance checks are also unchanged.

## 4 · Items 1–7 (S1–S7)
**These sections are carried over unchanged** from the 14 Oct items 1–7 handoff, except **S3b, which now reflects decision 1.** The full text follows.

| PR | Piece | Est. | Before 17 Oct? |
|---|---|---|---|
| S2 | Sidebar: one highlight | 30 min | yes (A7) |
| S4a | KPI tile reflow | 45 min | yes (A1) |
| S4b | Students by gender per class | 45 min | yes (A3) |
| S3a | Toshi colours and copy | 30 min | yes (A6) |
| S6 | Table row menus and confirms | 2 h, then 15 min per table | the rule and Subjects yes (A18); the rest after (B16) |
| S1 | Page surface and layout | 2 h | partly (A2); the full surface after (B14) |
| S3b | Toshi handle, flush panel, maximise | 3 h | handle and flush top yes (A5, A6); maximise after |
| S5 | Profiles | 3 h | the guardians card yes (A9); teacher via #1074; parent after (fallback A20) |
| S3c | Toshi setup-guide and assistant screens | later | no |
| S7 | Bulk message composer | later | no |

## S2 · Sidebar: one highlight
| State | Treatment |
|---|---|
| **Active row** (the page you're on, at any depth) | inset pill: 2px side margin, 8px radius, 36px tall; background #DCFCE7, text #14532D (8.6:1) at weight 700, icon #15803D; **no left bar** |
| **Parent of the active row** | label weight 700 #0F172A, icon #0F172A, **no background** |
| Hover | #F1F5F9 background, text #0F172A |
| Focus | 3px #1E6FD9 ring, inset 1px |
| Open group | chevron down; children indented 38px with a 1px #E2E8F0 guide line at 20px |
| Closed group containing the active page | the parent keeps its bold label (it's still the parent); no pill shows until the group is opened |

- **Rail:**
  - **Active parent:** a 44 × 40px green pill.
  - **Hover or focus:** shows the name in a tooltip after 300ms. Parents open their flyout, with the active child as the pill inside it.
  - **Avatar:** its tooltip and `aria-label` read "{name} · {role}".
- **Delete:** `.nr.act::before` (the left bar), and the background on the parent row.

**Accept:**
1. On any page, exactly one element in the sidebar has the green background.
2. The parent row's computed `background-color` is transparent, and its `font-weight` is 700.
3. No element in the sidebar has a coloured left border.

## S4a · KPI tile reflow
- **Grid:** `grid-template-columns: repeat(auto-fit, minmax(max(var(--d-kpi-min), (100% - 4 × gap) / 5), 1fr))`. It never shows more than 5 tiles per row, and no tile is narrower than **176px**.
- **Tile:** `container-type: inline-size`.
- **Order inside the tile:**
  1. label (icon, then the stretched link);
  2. value (26px/600);
  3. meter, if there is one;
  4. the footer: meta line, then action.
- **Footer by tile width:**

  | Tile width | Footer layout | Height |
  |---|---|---|
  | 260px and up (`--d-kpi-inline`) | meta (flex 1, wraps) and the 36px action on **one line**, action right-aligned and bottom-aligned | fixed **150px** |
  | below 260px | meta on its own line, then the action on its **own line**, left-aligned | **grows** (min 132px); no cap |

- **Never** use absolute positioning or negative margins in the footer. That's what causes today's overprint.
- **Expected widths:**

  | Viewport | Tile width |
  |---|---|
  | 1280, Toshi closed | about 176px, so the action stacks |
  | 1440 | about 200px |
  | 1920 | about 296px, so one line |
  | 1280, Toshi open | the content is about 528px, giving 2 or 3 columns |

**Accept:**
- For every tile at 1280 and 1440, Toshi open and closed, and 1920: the rects of `.m` and `.a` don't intersect. The concept reports this as "collisions 0".
- No tile is narrower than 176px.
- Tile height is 150px only when the tile is at least 260px wide.

## S4b · Students by gender per class
- **Layout:**
  - **School total on top:** a 16px bar, bold label, and a divider below.
  - **One row per class:** a 12px bar with **8px between rows** and **2px between segments**, so the segments read separately.
- **Counts at the end of each row,** in words: "5 girls, 6 boys". Add ", 1 not specified" only when it's above 0, and use the singulars "1 girl" and "1 boy".
  - **Below 380px card width,** the counts move under the bar.
  - **Screen readers:** the bar's `aria-label` repeats the counts, and the visible text is `aria-hidden`.
- **Legend:** Girls #B45309, Boys #1E6FD9, Not specified #64748B. The legend has no percentages; the counts carry the numbers.
- **Data:** each class row uses **that class's** counts. The concept shows 7 different splits.

**Accept:**
- The class counts sum to the school total.
- No two rows render identical widths unless their counts are identical.
- Each row's text matches its counts.

## S3a · Toshi colours and copy (safe now)
- **Remove:**
  - the orange line at the top of the panel;
  - the orange "Toshi" text and the orange handle;
  - the sentence "Founding schools will be the first to try Toshi." everywhere (card and composer note).
- **New palette:**

  | Element | Treatment |
  |---|---|
  | Name | #0F172A |
  | K mark | in a 32px circle with a 1px #22C55E ring |
  | Mode chip | Preview: #F1F5F9 / #334155; Setup guide: #DCFCE7 / #14532D; Early access: #DBEAFE / #1E3A8A |
  | Send button | the primary green |

- **Supersedes the clay palette:** this replaces the clay accents in `[data-toshi-root]` (`--toshi-accent`, `--toshi-accent-deep`) and in the Toshi phone handoff's colour notes. The layout and behaviour in that handoff are unchanged.
- **Preview copy:**
  - the "Coming soon" chip;
  - "Toshi is in preview";
  - the agreed description, word for word;
  - "Everything in KlassApp works without Toshi."
- **Composer note:** "Messaging is paused while Toshi is in preview."

**Accept:**
- A grep of the Toshi views and CSS finds no `#c96442`, `#A84D32`, `#9A4A2E` or "Founding schools".
- The panel's top border is 0 or #E2E8F0.

## S6 · Destructive actions in tables (one rule)
1. **No red buttons in table rows.** Each row's **last cell** is one 44px ghost ⋮ button: `aria-label="Actions for {row name}"`, `aria-haspopup="menu"`.
2. **The menu:**
   - **Order:** everyday actions first (View, Edit, …), then reversible removals (Archive, Deactivate, Remove from class), then a separator, then **Delete…** last.
   - **Destructive styling:** destructive items are #B91C1C text (6.5:1) with no fill. The "…" means a dialog follows.
   - **Keyboard:** the arrow keys move between items, Esc closes and returns focus to ⋮, and so does a click outside.
3. **Reversible actions:** a one-step confirm.
   - **Title:** "{Action} {name}?".
   - **Body:** one sentence on what changes, plus "you can restore it at any time from {place}".
   - **Buttons:** **Cancel** (focused first) and a primary button naming the action.
4. **Irreversible deletes:** a typed confirmation.
   - **Title:** "Delete {name}?".
   - **Body:** what's lost, **with counts** ("1,240 marks in 3 exams, 66 report cards"), then "This can't be undone.", then the reversible alternative.
   - **Input:** "Type **{name}** to confirm" (or **DELETE** for people). The match is case-sensitive, ignoring outer spaces.
   - **Buttons:** **Cancel**, **{Reversible alternative} instead**, and **Delete for good** (red), which is disabled until the text matches.
5. **Server rule:** the delete endpoint re-checks permission, and refuses if the record has dependants the dialog didn't count.
6. **Rollout:** apply to each legacy table as a mechanical change: Subjects, Exams, Classes, Library, Transport, Fees structures. Use the existing confirm dialog component from resync phase 2.

**Accept:**
- **No red row buttons:** across the listed pages, `document.querySelectorAll('td .btn-danger, td .bg-red-600, td button.text-red-600')` returns 0.
- **Dialogs:** every Delete opens a dialog. An irreversible delete with a mismatched name keeps its button `disabled`.

## S1 · Page surface and layout
- **Surface:** the page background is `--d-page-surface` (#FAFAF5, the same as `--d-canvas`).
  - **Delete the white page panel.** Cards (#fff, 1px #E7E5DF, 14px radius) sit **directly on the surface**.
  - **Lists, profiles and forms all use this same surface:** a list's table is one card; a profile is a header card, then tiles, tabs and cards.
- **Spacing scale:**

  | Measure | Value |
  |---|---|
  | page padding | 24px (content width below 1180), 32px (1180–1639), 40px (1640+) |
  | between sections | 24px |
  | between cards | 16px, or 20px from 1180 |
  | inside cards | 18px / 20px |

- **Width:** the content column is centred with **`--d-content-max: 1600px`**. At 1920 that leaves roughly 36px of surface each side, after the 248px sidebar.
- **Toshi gutter:** from 1280px, the right padding is `--d-toshi-gutter` (**40px**) wider. That's where the handle lives, so it never covers a page action. With the panel open, the gutter is dropped and the panel takes its place.
- **Grids** (by the content's own width, using container queries):

  | Grid | Columns |
  |---|---|
  | KPI tiles | as in S4a |
  | Chart cards | 2 columns when the content is at least 696px, else 1; equal heights (D3) |
  | Profile tiles | 3 columns of at least 200px |
  | Overview cards | 2 columns of at least 300px |

- **Expected content widths:**

  | Viewport | Content width |
  |---|---|
  | 1280 | about 944px (1280 − 248 − 24 − 64) |
  | 1280 with Toshi | about 560px |
  | 1440 | about 1088px |
  | 1920 | 1600px |

- **Header row:** H0 (Plan A) for the demo. See section 3 of this file:
  - **Top bar:** KlassApp, the school name 17px/700, the term, and Search.
  - **Content's first row:** the page `h1` (dashboard: the school name 28px/700, or "Good afternoon, Daniel" in Plan A; other pages: the title 26px/600). The **Academic year** select, with its label, is on the right.
  - **Plan B:** as in the 2026-10-13 handoff.
- **Phones:** unchanged. Cards sit on the surface with a 16px page padding.

**Accept:**
1. **No page panel:** no element between `<main>` and the first card has a white background.
2. **Width cap:** at 1920 the content width is 1600 ± 1px, and it's centred.
3. **Handle clearance:** at 1280 the handle's rect doesn't intersect any button, link or input.
4. **Same surface everywhere:** the student list, the student profile and the dashboard render on the same surface colour.

## S3b · Toshi handle, panel, maximise (1280 and up)
**Decision 2026-10-14:** the handle stays **visible, collapsed by default, in every mode from 1280px** (preview, setup guide, assistant). It reverses the 13 Oct removal. **Below 1280 there's no handle** (see the Toshi phone handoff).

**Handle:**
- **Size and look:** 36 × 96px, on the right edge and vertically centred, white, a 1px #CBD5E1 border with no right border, a 12px left radius, and the 22px K mark.
- **Semantics:** `aria-label="Open Toshi"`, `aria-expanded`.
- **States:**

  | State | Treatment |
  |---|---|
  | Hover | #F0FDF4 background, #86EFAC border, and a tooltip "Open Toshi" after 300ms |
  | Focus | 3px blue ring, and the tooltip |
  | Open | the handle is removed, and focus moves to the panel's Close button |

  It sits inside the 40px gutter (S1), so it never covers page actions or the scrollbar. Keep it 4px from the scrollbar.

**Panel:**
- **Position:** a right column, **flush under the top bar** (or the window top in Plan B), at the same top edge as the content column. There's no gap above it and no coloured line. It runs to the bottom of the window.
- **Width:** 400px by default, draggable from 320 to 560 (the existing split layout). The separator has `role="separator"` and arrow-key resizing. The content column narrows beside it.
- **Header (56px):** the K mark, "Toshi", the mode chip, **New conversation** (hidden in Preview), **Maximise** and **Close**, all 44px.
- **Closing:** Esc closes the panel and focus returns to the handle.
- **Modes** (exactly as the concept):
  - **Preview:** the card in S3a, plus the disabled composer with its note.
  - **Setup guide:**
    - **Tabs:** Checklist and Conversation.
    - **Checklist:** "4 of 7 steps done" with a progress bar. The **step count comes from `OnboardingStepsService`**, the same count as the setup bar.
    - **Conversation:** quick replies (44px chips) above the composer.
  - **Assistant:** messages; tool results (title, source line, rows, a link to open the page); and an approval card for anything that changes records or sends messages ("Approve before Toshi does this", what will happen, **Reject** and **Approve and send**).

**Maximised:**
- **Size:** a modal 880px wide and up to 720px tall (90vh), over a rgba(15,23,42,.45) scrim.
- **Contents:** the same header, with **Restore to side panel** in place of Maximise.
- **Behaviour:** focus is trapped, Esc restores the panel, and the page behind is `inert`.

**1024–1279:** no handle, no docked panel. The launcher is the 44px "Toshi" row in the sidebar, which opens the 400px side sheet from the Toshi phone handoff (an overlay). Below 761px, the phone launcher and sheet.

**Accept:**
1. At 1280, 1440 and 1920 with the panel open: the panel's `top` equals the content column's `top`, its `bottom` equals the window height, and its computed `border-top` is none.
2. With the panel open, the content narrows and nothing scrolls sideways.
3. The handle is visible in all three modes at 1280 and up, and absent below 1280.
4. Maximise opens the modal, Esc restores the panel, and focus returns to Maximise.

## S5 · People profiles
**Shared:** the layout from the 2026-10-13 handoff P1, on the S1 surface:
- **Header card:** an 88px initials circle (a photo uses the same frame); the name in title case as `h1`; a role chip and a status chip; the facts row; Edit, Message (or Call) and More.
- **Below it:** 3 KPI tiles (S4a rules), **underlined tabs** (replace the filled pills), and cards.

**Student · Parents and guardians card.** Each row has:
- **Avatar:** an initials avatar (40px circle).
- **Name and chips:** the name, a **relation chip only when the relation is known**, and "Primary contact" where it applies. **Never print "Not given".**
- **Phone:** a `tel:` link with a phone icon, in its stored format.
- **WhatsApp:** **On WhatsApp** (green chip) or **Not on WhatsApp** (grey chip). "Opted in" and "Not opted in" are retired, because parents don't understand them.
- **Message:** a quiet ghost **Message** action on the right, which opens the bulk composer (S7) with this one parent. Until S7 ships, it goes to the existing WhatsApp page.
- **Header link:** "Link another".

**Teacher:**
- **Facts:** class teacher of, phone, email, joined.
- **Tiles:** **Classes** (count and names), **Subjects** (count and names), **Marks pending** (count and the first one).
- **Tabs:** **Overview** (details card, with invite status) and **Teaches** (class, subject, and marks status for the current exam).

**Parent:**
- **Facts:** children, phone, WhatsApp, last login.
- **Tiles:**
  - **Children:** the count and classes.
  - **Total fees balance:** across all children in the school's currency, with "Paid X of Y"; #92400E when owed.
  - **WhatsApp status.**
- **Tabs:** **Overview** and **My children**: one card per child with the class, KLS number, balance, and **Report card** and **Fees**.
- **No** profession, income, organisation, designation or qualification fields anywhere on the page or its edit form.
- **More:** Reset password, Change login details, a separator, **Deactivate account**, then **Delete parent…**. Delete uses the S6 typed confirmation with **DELETE**. Its body names the children who will have no parent linked.

**Shared versus different:**

| | Shared | Differs |
|---|---|---|
| Header | layout, avatar, title case, chips, actions | the facts and the Message action per role |
| Tiles | 3, using S4a | which three |
| Tabs | underlined, keyboard | Student: 5 (+ restricted ones); Teacher: Overview, Teaches; Parent: Overview, My children |
| More | the separator, then red items, then dialogs | items per role; only the parent has Delete in this brief |

**Accept:**
1. **No legacy guardian text:** the profile DOM contains no "Not given" or "Not opted in".
2. **Guardian rows:** each phone is an `a[href^="tel:"]` at least 32px tall, and each row has exactly one WhatsApp chip.
3. **Parent page:** contains none of the words Profession, Income, Organisation or Organization.
4. **Parent delete:** Delete is reachable only through More, and needs DELETE typed.

## S7 · Bulk message composer (after the demo)
Opened from **Message parents** on the bulk bar (students list), and **Message** (teachers and parents lists).
1. **Recipients:**
   - **Three counts:** students selected, people reachable on WhatsApp, skipped.
   - **Skip list, with a reason for each:** no parent linked; not on WhatsApp; opted out of school messages.
   - **Shared parents:** "Two students share a parent, so 8 parents get one message each."
2. **Message:**
   - **Template:** a radio list of **approved WhatsApp templates**: Announcement, Fee reminder, Event, with each template's text and its `{variables}`.
   - **Variable fields:** filled automatically per recipient; the ones the school fills in, such as the due date or the event details, are inputs.
   - **Why no free text:** a short line explains it: WhatsApp only allows approved templates outside the 24-hour window.
   - **Preview:** a WhatsApp-style preview for the first recipient, with variables highlighted. The school name is the sender line.
3. **Confirm:** "Send to {n} parents on WhatsApp?", the template name and the skipped count, and "Messages can't be recalled once sent." Buttons are **Back** and **Send {n} messages**.
4. **Sending:** a progress bar, "Sending… x of n" (`role="status"`), and "You can close this window."
5. **Result:**
   - **Counts:** delivered, failed (with the reason and a fix link) and skipped.
   - **Buttons:** **View in WhatsApp history** and **Done**.
- **Demo schools:** a notice on every step: "**Demo school: nothing is sent.** You'll see the full flow and a result summary, but no WhatsApp message leaves KlassApp." The server short-circuits sending for demo schools.
- **Empty:** "None of the selected parents can get WhatsApp messages", with how to fix it.
- **Error:** "Templates couldn't be loaded. Nothing was sent.", with **Try again**.

**Bulk bar buttons:**
- **Message parents:** opens the composer.
- **Move to class:**
  - **Opens** a dialog with Class and Stream selects (with their labels) and "From {k} classes today. Their marks and attendance stay with the class they were recorded in."
  - **Buttons:** **Cancel** and **Move {n} students**.
  - **Afterwards:** a success toast with **Undo** for 10 seconds.
- **Export** and **Clear:** unchanged.

**Accept:**
- **Demo sends nothing:** in a demo school, sending makes no outbound API call (feature test).
- **Counts:** the recipient count equals unique reachable parents.
- **No free text:** the send request references a template id.

## Assumptions
- **Toshi handle:** visible and collapsed from 1280 in every mode (decision 1); hidden below.
- **No orange, Toshi included:** the clay accents are replaced by neutrals and green.
- **Report cards tile:** one action at a time, chosen by state.
- **Record payment:** only for roles that can record payments.
- **Delete on profiles:** only the parent profile has Delete; students and teachers keep Deactivate only.
- **Message on a guardian row:** opens the WhatsApp page until S7 exists, then the composer with one recipient.
- **Message parents in the bulk bar:** same rule.
- **#1067 and #1076:** I haven't seen them. Rows marked *(#1067)* may already be fixed there; #1076's seven fixes may overlap other A rows.
- **Avatar shape:** the initials circle is now the default for every avatar (A14). The 32px nav-button exception no longer applies, since everything is a circle.
- **Student tabs:** underlined, built tabs only (A10).
- **The A7 green group background on Subjects:** may be a separate rule for open groups. Delete it wherever it lives.

