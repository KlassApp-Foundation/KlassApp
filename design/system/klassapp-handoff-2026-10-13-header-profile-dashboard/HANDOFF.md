# Handoff: header, shared person profile, dashboard v2.1 (2026-10-13)

- **Designed from:** the staging build `main @ 261fad07` (screenshots of 12 Oct) and the admin MVP pack. Before starting, run `git log --oneline 261fad07..main -- resources/views/layouts resources/views/admin resources/views/components`.
- **Concept:** `concepts/v21/index.html` shows every state side by side at 1280 and 375. It has switches for page, header plan, school logo, attendance fallback, report-card state, tile hover, role, tab, empty data and the More menu or confirm. The click-through itself is `concepts/v21/app.html`.
- **Global rules still apply:**
  - currency comes from school settings (UGX in the concept);
  - phone numbers are examples only;
  - Gender is Female, Male or Not specified;
  - no country-specific wording;
  - targets are at least 44px, except the 36px compact buttons inside cards (see D1), which sit next to a larger hit area;
  - text contrast is at least 4.5:1;
  - every animation is disabled under reduced motion.
- **Measured in a browser:** in my preview with `getBoundingClientRect`, at frame widths of 1278 and 373 (1280 and 375 minus the frame border). I measured 26 states:
  - dashboard: Plan B and Plan A, attendance taken this week and none yet this week, school logo on and off;
  - another page: Plan B and Plan A;
  - student profile: all 5 designed tabs, filled and empty;
  - teacher profile: Overview, Teaching and Attendance;
  - parent profile: Overview, Children, Fees, Messages, and Children empty;
  - staff profile: Overview;
  - the More menu and the Deactivate confirm.

  **Results:**
  - nothing scrolls sideways;
  - every button, tab, select and search target is at least 44px (compact buttons at least 36px);
  - the dashboard h1 is 28px/700; the page and profile h1 is 26px/600 (22px and 21px on phones);
  - the quiet school line is 15px/600;
  - the two side-by-side chart cards are equal in height (0px difference in both rows);
  - all 5 KPI tiles are links, 4 with one secondary action, and there are no quick actions.

  **One fix found and made:** long emails in the profile facts row made the parent and staff profiles scroll sideways at 375. `overflow-wrap:anywhere` fixed it.

  **Not measured:** contrast (calculated from hex), and anything in the real app.
- **Screenshots:** your message mentions review screenshots of the build, but only the three from 12 October were in the project. This handoff works from those.

## Order and independence
Every piece below ships on its own, and the app keeps working if only some are built by 14 October. The **suggested order for the demo** is:

| PR | Piece | Est. | Needs | If it's not built |
|---|---|---|---|---|
| H0 | Header Plan A (fallback): school line in the top bar | 30 min | none | the current top bar stays |
| D1 | KPI tiles as links + one action each; remove quick actions; Enter marks on the Performance card | 2 h | none | quick actions stay |
| D2 | Attendance tile falls back to "last week" | 30 min | none | the tile shows "–" on Mondays |
| D3 | Equal-height chart cards | 15 min | none | uneven cards |
| D4 | Students by gender, per class | 1 h | none | the single school bar stays |
| H1 | Header Plan B: no top bar on laptops | 3 h | replaces H0 (H0 can be skipped) | Plan A (H0) or the current bar |
| P1 | Shared person profile: shell (header, facts, actions, KPIs, tabs) | 3 h | none | the old profiles stay |
| P2 | Overview + Parents and guardians / Children tabs | 1.5 h | P1 | the old tab bodies render inside the new shell |
| P3 | Academics tab | 1.5 h | P1 | the old marks table |
| P4 | Attendance heat map | 2 h | P1 | the old attendance list |
| P5 | Fees statement | 1.5 h | P1 | the old fees tab |

**Before 14 October, if time is short:** H0, D1, D2 and D3 (about 3 h 15 min). Then H1 if the shared-layout change fits in about 3 hours; otherwise H0 stays until after the demo. The profile pieces (P1–P5) can land in any order after P1.

---

## Header
### H1 · Plan B (preferred): no top bar on laptops (761px and up)
**Remove** the 56px top bar at **761px and up**. At 760px and below the phone top bar stays exactly as specified (menu · school name 16px/700 · Search · Toshi K button when T1 ships · avatar).

**The sidebar** gets the KlassApp logo at the top:
- **Expanded:** `klassapp-horizontal-light.svg` at 26px tall, in a 44px row. The rail toggle sits on the right of the same row.
- **Rail:** only the K mark (`klassapp-icon.svg`, 24px), centred.
- **Logo link:** the logo links to the dashboard and has `alt="KlassApp"`.

**Header row:** the first thing in the content column on every page, flex with wrap and a 12px/16px gap, inside the existing 24px/28px page padding.
- **Left:**
  - **On the dashboard:**
    - **School name as the heading:** `<h1>`, **28px, weight 700**, Sora, #0F172A, letter-spacing −0.02em, line-height 1.2.
    - **School logo:** when one exists, a **28 × 28px** logo sits first (`object-fit:contain`, 6px radius, 1px #E2E8F0 border, 12px gap, `alt=""`). No placeholder when there's none.
    - **Line beneath:** "Good morning, Daniel · Term III, 2026" at 15px/400, #475569 (7.24:1 on white).
    - **Greeting and term:** the greeting uses the first name and the local time of day (morning before 12:00, afternoon before 17:00, evening after). The term comes from settings.
  - **On every other page:**
    - **Quiet line:** **15px, weight 600**, Sora, #475569, with a 20px logo first when one exists. It's a `<p>`, not a heading.
    - **Page title:** the `<h1>` stays below the quiet line at **26px/600**, with a 4px gap.
    - **Profiles:** the breadcrumb comes first, then the quiet line, then the profile header card.
- **Right** (aligned to the bottom of the left block):
  - **Search:** a 44px button, 280px wide, showing "Search" and the key hint: `⌘K` on Mac and iPad, `Ctrl K` elsewhere.
  - **Academic year:** a select with its visible label "Academic year" (12px/700 above it), 44px tall, with the current year selected and marked "(current)".
  - **Hidden on profiles:** neither shows on profile pages, so the header card has the full width. **Ctrl K** still opens search there.
- **761–1279:** the right block wraps under the left when space runs out. It never overlaps.

**Accept:**
1. At 761, 1024, 1280 and 1440: no element with the top bar's class exists, and the sidebar logo is visible (expanded) or the K is (rail).
2. Dashboard `h1` computed style is 28px/700. On every other page the quiet line is 15px/600 and the `h1` is 26px/600, and there's exactly one `h1` per page.
3. Search and the Academic year select are at least 44px tall, and Ctrl K and ⌘K open search from any page.
4. At 375 and 414 the phone top bar is unchanged (56px; menu, school name, Search, avatar).
5. No sideways scroll at any width from 761 to 1440.
6. With no school logo: no empty box. With a logo: 28px on the dashboard and 20px on other pages.

### H0 · Plan A (fallback): the top bar stays
- **School line in the top bar:** the KlassApp mark, then the school logo (28px, if any), then the **school name at 17px/700**, Sora, #0F172A, then the term at 13px/500 in #475569. Search sits on the right. This is the A1 treatment in the polish handoff.
- **Content:** the dashboard `h1` is the greeting, "Good morning, Daniel", at **26px/600**, with the term on the line beneath. **The school name is removed from the greeting line** (A8.14). Other pages keep their 26px/600 `h1`, with no school line, because the bar shows it.
- **Accept:** the bar is 56px tall, the school name is 17px/700, and the school name appears exactly once on the page.

---

## Dashboard v2.1
### D1 · KPI tiles as links, one action each; remove the quick actions
**Remove the quick-actions row.** Its five actions move:
- **Add students:** to the Students tile.
- **Enter marks:** to the **Performance by class** card header.
- **Generate report cards** and **Send report cards on WhatsApp:** to the Report cards tile.
- **Fees:** the Fees tile itself links there, and it carries **Record payment**.

**Each tile is one link:**
- **Markup:** an `<a class="kl">` on the label, with `::after{inset:0}` stretched over the whole tile, so the whole tile is clickable, middle-click works, and screen readers announce one link: "{label}, open {page}".
- **Secondary action:** a separate real button or link **above** the stretched link (`position:relative; z-index:2`), never nested inside it.
- **Hover:** border #94A3B8, the card-surface v2 green corner tint, and an `arrow-up-right` icon fading in at the **top right** of the tile. The label keeps 20px clear for it, so the arrow never covers the label or the action button (150ms; none under reduced motion).
- **Labels:** may wrap to two lines at 1280 ("Attendance this week" in a 182px tile). They're never ellipsized, and every tile in the row stretches to the same height. The label link has no minimum height of its own: the 44px rule doesn't apply because the whole tile is the target.
- **Focus:** a 3px #1E6FD9 outline around the **whole tile** (`.kt:has(.kl:focus-visible)`). The secondary button gets its own focus ring.

**Tile links and actions:**

| Tile | Links to | Secondary action |
|---|---|---|
| Students | Students list | **Add students** (`user-plus`) |
| Staff | Teachers and staff | none |
| Attendance | Attendance overview | **View registers** (`clipboard-check`) |
| Fees collected | Fees. Detail line in short amounts with the currency, "UGX 9.5M of 54M", so it never wraps | **Record payment** (`banknote`), only for roles that can record payments |
| Report cards ready | Exams and reports | **Generate** (`file-plus`) while any report card is missing; **Send on WhatsApp** (`message-circle`) once all are generated. **One action at a time, chosen by state** (my reading of "Generate and Send on WhatsApp"). |

**Compact buttons:** the secondary buttons are 36px tall and 13.5px. **They're the one exception to 44px:** each sits inside a tile whose whole area is a larger link target, and 36px meets WCAG 2.2's 24px minimum. On phones they stretch to the tile width.

**Enter marks:** a 36px compact button on the right of the **Performance by class** card header, linking to the exams page for the latest exam.

**Accept:**
1. No quick-actions container exists in the DOM.
2. Each of the 5 tiles has exactly one `a.kl`, and 4 tiles have exactly one secondary action.
3. Clicking anywhere on a tile except its secondary button navigates to the tile's page.
4. Tab order goes tile link, then its secondary action, then the next tile.
5. The focus ring wraps the whole tile, and the secondary button is at least 36px tall.

### D2 · Attendance fallback
- **When no register has been taken yet this week:**
  - **Label:** "Attendance last week".
  - **Value:** last week's whole-school rate.
  - **Flag:** a small amber chip, "No register taken yet this week" (#78350F on #FEF3C7, 8.0:1).
  - **Detail line:** the date range, "Mon 5 – Fri 9 Oct".
- **When there's no data for last week either:** "–" with "No registers yet this term".
- **Accept:** on a Monday before any register, the tile shows last week's rate with the chip. After the first register it shows "Attendance this week".

### D3 · Equal-height chart cards
- **Layout:** the chart grid uses `align-items:stretch`. Each card is `display:flex; flex-direction:column`, and the chart area (`.grow`) takes the spare height, centred.
- **Accept:** at 1280 the two cards in each row have a `getBoundingClientRect().height` difference of 0px (measured 0 and 0 in the concept). At 760 and below the cards stack.

### D4 · Students by gender, per class
- **School total:** one stacked bar for the school at the top (18px, bold label, with a divider below).
- **Per class:** then **one 14px stacked bar per class**, in the class order. Each row is the label, the bar and the count.
- **Colours:**

  | Segment | Colour | Contrast on white |
  |---|---|---|
  | Girls | #B45309 | 5.0:1 |
  | Boys | #1E6FD9 | 4.9:1 |
  | Not specified | #64748B | 4.8:1 |

  The legend shows the counts.
- **Bar label:** each bar has `role="img"` and an `aria-label` such as "P.4: 4 girls, 4 boys, 1 not specified".
- **Accept:** the per-class counts sum to the school total, and a class with 0 students shows "No students" instead of an empty bar.

---

## Shared person profile (student, teacher, parent, staff)
### P1 · Shell
One Blade component, `<x-person-profile :person :role>`, renders every role. It reuses the admin MVP pack's rules for menus, confirm dialogs and avatars.

**What's shared:**
- **Breadcrumb**, then the quiet school line (Plan B only).
- **Header card** (16px radius, 24px padding):
  - **Avatar:** the initials tile, **96px** at 761px and up (12px radius), **64px** on phones. A photo replaces it with the same frame.
  - **Name:** in **title case** from `userprofiles.firstname` + `lastname` (`Str::title()` at display time if stored in capitals), as an `<h1>` at 26px/600 (21px on phones).
  - **Chips:** a **role chip** (#DBEAFE / #1E3A8A) and a status chip (Active, Inactive).
  - **Key facts row:** a `<dl>` with labels at 12px/700 uppercase #475569 and values at 600 #0F172A. On phones it's 2 columns, and long values wrap.
  - **Actions:** **Edit**, **Message** (the role's channel) and **More** (44px icon, `aria-haspopup="menu"`). More holds the role's items, a separator, and **Deactivate**, in red, which opens the confirm dialog: Cancel is focused first, and the dialog names the person and says the change can be undone.
- **Three KPI tiles** per role. These are plain tiles, not links, since the tabs below already hold the detail.
- **Tabs:** `role="tablist"`, 44px tall, with Left and Right arrow keys and a 3px green underline. On phones they scroll inside their own row. Each tab has an empty state that says what's missing and the next step.

**What differs per role:**

| | Student | Teacher | Parent | Staff (bursar, librarian, office) |
|---|---|---|---|---|
| Role chip | Student | Teacher / Head teacher | Parent | the staff role |
| Key facts | Class · KLS number · Age (born {date}) · Gender · Joined | Class teacher of · Teaches · Staff number · Phone · Joined | Children · Phone · WhatsApp status · Email · Last login | Role · Staff number · Phone · Email · Joined |
| Message | Message parent | Message | Message on WhatsApp | Message |
| More | Edit details, Reset password, Move to class, Print report card | Edit details, Assign classes and subjects, Reset password, Resend invite | Edit details, Link a child, Reset password | Edit details, Change role, Reset password |
| KPI tiles | Attendance this term (with meter) · Latest exam (% · grade, position) · Fees balance (status, paid of due) | Classes and subjects · Marks still pending · Registers this week | Children · Fees due across all children · WhatsApp this term | Role-specific; for a bursar: Payments recorded · Unmatched payments · Reminders sent |
| Tabs | Overview · Academics · Attendance · Fees · Parents and guardians · Health and support · Documents · Notes | Overview · Teaching · Attendance · Documents | Overview · Children · Fees · Messages | Overview · Attendance · Documents |

- **Unchanged from the admin MVP pack:** Health and support, Documents and Notes, including their visibility and logging rules.
- **Student age:** computed from the date of birth. With no date of birth, "Not given", never "1970".

**Accept for P1:**
- One component renders all four roles.
- The name is in title case for a stored "DANIEL MUKASA".
- The avatar is 96px or 64px with initials.
- Deactivate opens the confirm with Cancel focused, and Esc returns focus to More.
- At 375 and 1280 there's no sideways scroll, including with a 40-character email in the facts.

### P2 · Overview, Parents and guardians (student), Children (parent)
- **Overview:** two equal cards.
  - **Details:** a `<dl>`. Empty values show "Not given".
  - **Second card:**

    | Role | Second card |
    |---|---|
    | Student | **Parents and guardians:** avatar 44, name, relationship, a WhatsApp chip, and "See all" |
    | Parent | **Children:** a link to each child's profile, class, and balance or "Fees cleared" |
    | Teacher | **Teaching this term:** class, subject, students |
    | Staff | **Access:** sees, doesn't see, last login |

- **Parents and guardians (student):** contact cards (at least 300px, auto-fill).
  - **Each card:** a 48px avatar, name, and relationship, plus "primary contact" where it applies.
  - **Phone:** a 44px `tel:` link.
  - **WhatsApp status chip:** "opted in on {date}", "asked" or "not opted in". "Not opted in" also gets a **Send opt-in request** button.
  - **Empty:** "No parent or guardian linked yet", with **Link a parent**.
- **Children (parent):** the same cards, with the class, KLS number, balance, and **Report card** and **Fees** buttons. Empty: "No children linked yet", with **Link a child**.

### P3 · Academics
- **Chart:** grouped bars per subject, one bar per term (Term I #94A3B8, II #1E6FD9, III #15803D), a 0–100 axis, a legend, and an `aria-label` listing every value.
- **Subject table:** subject, one column per term (tabular figures), grade, and an **Average** footer row. On phones the table fits at 343px, with no inner scroll.
- **Empty:** "No marks yet. Marks appear here once teachers enter them for an exam."

### P4 · Attendance heat map
- **Layout:** a month calendar with **Mon–Fri columns** and one row per week, and **Previous / Next month** buttons (44px; Next is disabled on the current month). The cells are about 1.25:1, with an 8px radius.
- **Each cell** shows the day number **and a letter**, so colour is never the only cue:

  | State | Letter | Colours | Contrast |
  |---|---|---|---|
  | Present | P | #15803D, white | 5.0:1 |
  | Late | L | #B45309, white | 5.0:1 |
  | Absent | A | #B91C1C, white | 6.5:1 |
  | No school | – | grey hatching | |
  | Not taken yet | ? | dashed outline | |
  | Future days | none | plain outline | |

- **Labels:** each cell has `role="img"` and an `aria-label` such as "Mon 5 Oct: Absent". The grid is `role="group"`, labelled with the month.
- **Legend and summary:** a legend with the same letters, then one summary line: "This term: 91% present · 5 days absent · 2 late".
- **Teachers and staff** use the same component, for their own attendance where the school records it.
- **Empty:** "No registers taken yet this term".

### P5 · Fees statement
- **Summary:** three boxes: **Due this term**, **Paid**, **Balance** (#92400E when owed, 7.1:1).
- **Statement table:** date, description (with the payment method and reference), charged, paid, and an **Amount** column (+ charged / − paid). Each row has a hidden running balance for screen readers.
- **Footer:** a **Balance** row.
- **Phones:** each row becomes a two-line item (date above; description and amount), and the charged and paid columns are hidden.
- **Actions:** **Record payment** (primary) and **Send reminder on WhatsApp**.
- **Currency:** amounts use the school's currency from settings, and the card says so.
- **Empty:** "No fee statement yet", with **Set up fees**.
- **Parents:** the parent's Fees tab shows the same statement grouped by child, with a total.

---

## Decisions recorded
- **Post-demo items:** the polish handoff's B items stay after the demo.
- **School name in the top bar:** A8.14 is superseded by H1 (or H0).
- **Fee chart:** A8.20 is moot; the demo fee data changed.
- **Toshi edge tab:** none in **preview** mode at any width, and hidden **below 1280** in every mode. It stays only at 1280 and up in onboarding and assistant modes. This is in the polish handoff A8.1 and the Toshi phone handoff.
- **Toshi phone launcher (T1):** a **44px K-only button between Search and the avatar**, leaving the school name about 160px at 375. This is updated in the Toshi phone handoff.

## Assumptions
- **Report cards tile:** shows **one** action, chosen by state (Generate, then Send on WhatsApp), not both at once.
- **Record payment:** appears only for roles allowed to record payments (admin, bursar).
- **Search and Academic year on profiles:** not shown, so the header card has the full width. Ctrl K still works there.
- **Staff profile:** the concept draws a bursar. Librarian and office roles use the same shell with their own three KPI tiles, to be defined with those pages.
