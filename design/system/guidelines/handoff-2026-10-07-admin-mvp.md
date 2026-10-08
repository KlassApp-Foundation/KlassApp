# Handoff: admin MVP pack (demo 17 October 2026)

- **Designed from:** `main @ 254aa194a7ef` (2026-10-07). Before starting, run `git log --oneline 254aa194a7ef..main -- resources/views/admin resources/views/layouts app/Services/RosterScopeService.php`.
- **Concept:** `concepts/admin-mvp/index.html`. It's a click-through prototype with every screen and state, shown side by side at 1280 and 375. Rows open profiles; menus, tabs, selection, the account menu and confirm dialogs all work.
- **Extends, doesn't replace:** dashboard v2 and the profile pages (`handoff-2026-09-30-profiles.md`), the people-list rules and role matrix (Task C), the default avatar (`handoff-2026-09-30-avatar-initials.md`), Lucide icons (`handoff-2026-10-01-icons.md`), and the table, chart, empty and loading patterns from resync phases 3–4.
- **Global product:** schools anywhere, from nursery to secondary. Use no country-specific fields or wording (no Pincode, EMIS, national exam names) and no income or profession fields. Take the currency from the school's settings.
- **Measured in the concept (in the browser, `getBoundingClientRect`):** every screen and state at 1280 and 375. That covers 23 states, the 7 student tabs and the 3 teacher invite states. None scrolls sideways, every interactive element is at least 44px tall, and no menu, popover or dialog runs off screen.
- **Not measured:** contrast is calculated from hex. All text pairs are at least 4.5:1: `--d-text-secondary` #64748B on white is 4.76 and on #F8FAFC is 4.55. Badges are 7:1 or more.

## Updated 2026-10-08
- **KLS number (confirmed):** `KLS` + the school's 3-digit number + a 4-digit sequence, e.g. **KLS0070001** (Demo Junior School is 007). Show it in tabular figures. It's read-only and never changes; the add form shows "Created when you save".
- **Health and support (decided):** its own student-profile tab, **visible only to the admin and head teacher roles**. For every other role the tab is absent from the page and its data isn't sent. **Each open is logged**, and the tab shows "Last opened by {name} {when}". The "Show health notes" button on Overview is removed.
- **Teacher workload (decided):** lessons per week from the timetable. If the school has no timetable, show the number of class–subject assignments instead. A badge always says which: "From the timetable" or "No timetable yet".
- **Class teacher (decided):** set per stream, with a class-level default. Each stream on the class page shows its own teacher. A stream without one shows the class default with the label "Class default · no teacher assigned to this stream" and an **Assign a class teacher** button. The class page is now per class (Primary 5), with a Streams card. Index cards are per class too, listing each stream's teacher, with a "Class default" badge where it's inherited.
- **Reconciled with dashboard v2** (`handoff-2026-09-30-profiles.md` Part B):
  - **Kept from v2:** the shell (canvas sidebar with no border, content in a white panel inset 12px with a 16px radius; full-bleed on mobile), the sidebar groups, the **quick actions**, the **setup chip** in the sidebar after the setup bar is hidden, and one step count from `OnboardingStepsService`.
  - **Sidebar groups:** Dashboard; People: Students, Teachers and staff, Parents; Academics: Classes and streams, Subjects, Attendance, Exams and marks, Report cards; Money: Fees; Messages: WhatsApp; School: Settings, Help.
  - **Quick actions** (Add students, Enter marks, Generate report cards, Send report cards on WhatsApp, Fees):
    - **School with data:** one row of compact buttons above the KPI tiles.
    - **Mid-setup and brand new:** v2's tiles, with the missing prerequisite named in #78350F.
  - **Changed from v2 by this brief:**
    - **Setup:** the large banner becomes the slim setup bar, with a 44px ✕ "Hide setup bar. Progress stays in the sidebar." Hiding it shows the chip.
    - **Snapshot:** v2's snapshot card is replaced by the 5 KPI tiles and the charts.
    - **Greeting:** "Good morning/afternoon/evening, {first name}", as you asked, instead of v2's "Welcome back".
  - **Conflict, your call:** v2 says that with Toshi off *nothing* mentions Toshi. This brief asks for a quiet "coming soon" line. The concept follows the newer brief. If you want v2's rule back, remove the line.
- **New bug from the screenshots:** **Edit teacher returns 404** (`/admin/teacher/edit/260` shows "Page Not Found"), so admins can't edit any teacher. It's added as **PR0**, below, because it blocks the demo.

## PR0 · `fix(teachers): edit route 404` (bug, before PR1)
- **Symptom:** **Manage** on the teachers list, and the profile's Edit, both go to `/admin/teacher/edit/{id}`, which renders the 404 page.
- **Check:**
  - **Route:** the route parameter and binding. The list links by numeric id, while the student and parent routes use the name.
  - **School scope:** the teacher must belong to the current school; a scope mismatch looks the same as a 404.
- **Accept:** a feature test where the admin of Demo Junior School opens Edit for each seeded teacher and gets 200. A teacher from another school still gets 404.

## Shared rules (every screen)
- **Shell:**
  - **Desktop:** the sidebar is 248px and **fixed in place** (sticky, full height). Opening anything never moves it.
  - **Below 760px:** the sidebar becomes a drawer opened by a 44px menu button in a 56px top bar. The account menu moves to an avatar button at the top right.
  - **Nav groups (text-only labels):**
    - Home: Dashboard
    - People: Students, Teachers, Parents
    - School: Classes and streams, Subjects, Attendance, Exams and marks, Report cards, Fees
    - Messages
- **Toshi:** one quiet line above the account card: "Toshi, your school's AI assistant · coming soon" (in `--d-text-secondary`), plus the same line at the foot of the dashboard. No slides, buttons or panel. It sits behind the Toshi switch from the dashboard v2 handoff, so Early access schools see the Early access treatment instead.
- **Icons:** Lucide at 18px, stroke 2. The names used are in the concept: `layout-dashboard`, `graduation-cap`, `presentation`, `users`, `school`, `book-open`, `calendar-check`, `clipboard-list`, `file-text`, `wallet`, `message-circle`, `ellipsis-vertical`, `search`, `user-plus`, `upload`.
- **Avatars:** `<x-profile-photo>` with the initials fallback.
  - **Sizes:** 36 in tables, 40 in mobile cards, 112 in profile headers (64 on mobile), 28–32 inline.
  - **Corners:** 8px at 32px and below, 12px otherwise.
- **Destructive actions:** never a button in a row. They're grouped at the bottom of a "More" menu, after a separator, in #B91C1C. They always open the unified confirm dialog (resync phase 2), which:
  - names the person;
  - says what happens and that it can be undone;
  - has **Cancel** (focused first) and a red confirm button.
  - Focus is trapped while it's open, Esc closes it, and focus returns to the trigger.
- **Menus** (row menus, More, account): keyboard operable. Enter or Space opens; focus moves to the first item; Up and Down move between items; Esc closes and returns focus. A click outside closes. Each trigger has `aria-haspopup="menu"` and `aria-expanded`.
- **Status badges:** Active (#DCFCE7/#14532D), Inactive (#F1F5F9/#334155), warning (#FEF3C7/#78350F), info (#DBEAFE/#1E3A8A), error (#FEE2E2/#991B1B).

---

# Phase 1 · demo-critical (items 1, 2, 3, 6)

## PR1 · `feat(dashboard): school-with-data first screen` (item 1)
**First screen, top to bottom:**
1. **Header:** the greeting with the first name in normal case ("Good evening, Mucunguzi") and "{school} · {term}, {year}". On the right, the **Academic year** selector with a visible label and the current year selected.
2. **Setup bar:** only while setup is incomplete. One slim line, about 56px: a list icon, "Setup 4 of 7 done", a progress bar, "Next: {next step}" and **Continue setup**. Gone at 7 of 7. The step count must come from the real step list, the same one the wizard uses.
3. **KPI tiles (5):**

   | Tile | Value | Detail line |
   |---|---|---|
   | Students | count | change this term |
   | Staff | count | teachers · admin |
   | Attendance this week | % | change on last week |
   | Fees collected | % of expected, with a meter | "{collected} of {expected}" |
   | Report cards ready | ready | "of {students} · {exam}", with a meter |

   On mobile the tiles sit 2 per row.
4. **Charts (2 × 2 on desktop, stacked on mobile):**

   | Chart | Shows | Details |
   |---|---|---|
   | Performance by class | latest exam, average % | horizontal bars, value labels |
   | Attendance trend | 8 weeks | line, 80–100% axis |
   | Students by gender | girls #B45309, boys #1E6FD9, not recorded #64748B | one stacked bar with a legend |
   | Fees collection | by month | collected bars in #15803D against dashed expected outlines, value labels |

   **Shared rules:** every chart carries a text `aria-label` with the values. Use the `<x-chart>` wrapper with the required contextual empty message; the concept draws them as SVG only to show the design.
5. **Recent activity:** 5 rows (icon, sentence, time) and **See all**.
6. **The Toshi "coming soon" line.**

**No promo carousel, no Toshi slide.**

**States:**
- **Mid-setup:** charts without data are replaced in place by empty cards. "No marks entered yet" links to **Go to exams**; "No fee structure for Term 3" links to **Set up fees**. The fees tile reads "Set up Term 3 fees first".
- **Brand new:** setup 1 of 7, all tiles 0 or "–" with a reason, and one empty state: "Add your students to get started", with **Add student** and **Import a list**. It stays behind the DPA gate from the formal-documents handoff (DA5).

**Accept:**
- Screenshots at 1280 and 375 for each of the three states.
- The setup bar is absent at 7 of 7.
- No Toshi slide or button is in the DOM with Toshi off.
- The KPI numbers match the database for Demo Junior School and Demo Senior School.

## PR2 · `feat(people): one list pattern for students, teachers, parents` (item 2)
**Based on** today's teacher list, the clean one.

**Toolbar:** one line.
- **Search:** 44px, with a search icon. Placeholder by list: "Search by name, KLS number" (students), "…, email" (teachers), "…, phone" (parents). It filters as you type, debounced 250ms.
- **Filter chips:** next to the search, each with a count, 44px tall. Single-select within a group; the first chip is "All".

  | List | Chips |
  |---|---|
  | Students | All, Class (opens a class and stream picker), Active, Inactive, No class, No parent |
  | Teachers | All, Active, Not yet invited, Invite pending, Class teachers |
  | Parents | All, On WhatsApp, Not opted in, Never logged in |

  On mobile the chips scroll sideways on one row; the page itself never does.
- **Remove:** the filter card, the Filter button, the "Starts with" select, the `#` column, and the hard-coded `EAST`/`WEST`/`A`/`B` streams. Streams come from the school's data.

**Rows:**
- **The whole row opens the person's profile.** The name is a real `<a>` stretched over the row, so the row is keyboard-reachable, middle-click works, and screen readers announce a link.
- **Columns:**

  | List | Columns |
  |---|---|
  | Students | Student, **KLS number** (`student_academics.klassapp_student_id`, tabular figures), Class (or a "No class" badge), Parent or guardian (or a "No parent" badge), Status |
  | Teachers | Teacher with role, Teaches, Class teacher of, Invite (Not invited / Invited / Joined), Status |
  | Parents | Parent, Children, Phone, WhatsApp (Opted in / Asked / Not opted in), Last login |

  Below 1180px the 4th data column hides.
- **Row menu** (⋮, 44px, the last column):

  | List | Items |
  |---|---|
  | Students | View profile, Edit, Move to class, Message parent |
  | Teachers | View profile, Edit, Send invite, Assign classes |
  | Parents | View profile, Edit, Link a child, Send WhatsApp opt-in |

  **Never Delete in a row.**
- **Checkboxes:** a 44 × 44px hit area around an 18px box. Select-all on the page.
- **Pagination:** "Showing 1–10 of 248", with previous and next buttons (44px).

**Bulk bar:** appears only when at least one row is selected. It's dark (#0F172A), sticky at the top of the list, and shows "{n} selected", the actions and **Clear**.

| List | Bulk actions |
|---|---|
| Students | Message parents, Move to class, Export |
| Teachers | Send invites, Export |
| Parents | Send WhatsApp opt-in, Export |

**Mobile (≤ 760px):** card rows (checkbox, avatar, name, a second line, ⋮) replace the table, and the whole card opens the profile.

**States:**
- **Loading:** skeleton rows and cards with `aria-busy` and a "Loading {list}…" status. Static under reduced motion.
- **Empty:** "No {people} yet", one line, and **Add** and **Import a list**.
- **No match:** "No {people} match “…”", with **Clear filters**.

**Accept:**
- The same Blade component (`<x-people-list>`) renders all three lists.
- At 375, 768 and 1280: no sideways scroll, targets ≥ 44px.
- Enter on a focused row opens the profile.
- The bulk bar appears and disappears with the selection.
- Students show their KLS number.
- No row contains a Delete control.

## PR3 · `feat(profiles): student profile, admin view` (item 3)
**Extends** the profiles handoff, P3.

**Header:** breadcrumb, avatar, name, class (a link to the class page), "KLS {number}", status badge. Actions:
- **Edit** and **Message parent**.
- **More:** Edit details, Reset password, Move to class, then a separator and **Deactivate student**, in red, through the confirm dialog.

**KPI tiles (3):**
- **Attendance this term:** %, a meter, days absent and late.
- **Latest exam:** "74% · B" and "6th of 32 in {class}". The grade comes from the school's grading scale.
- **Fees:** the large figure is labelled **Balance**: the amount in the school's currency, plus a status badge (Paid, Partly paid, Not paid) and "{paid} of {expected}".

**Tabs (eight):** Overview (the default for admins), Academics, Attendance, Fees, Parents and guardians, Health and support (admin and head teacher only), Documents, Notes.
  - **Desktop:** the tabs wrap onto a second line rather than run off the edge; at 1280 all eight fit on one line.
  - **Mobile:** they scroll sideways inside their own row, and a fade on the right edge shows there are more.
- **Overview:** student details and parents. No health notes here.
- **Academics:** marks by subject by term (a table with an average row and "Pending" for missing marks), plus a chart of this term's marks against the class average.
- **Attendance:** term summary and a dated log.
- **Fees:** expected, paid, balance and payments, with **Record payment** and **Send reminder on WhatsApp**.
- **Parents and guardians:** linked parents with phone, WhatsApp status and **Link another**.
- **Documents:** files from the admission form plus uploads.
- **Notes:** admin-only; opening them is logged (decided 2026-09-30).

Every tab has an empty state; see the "New student" state in the concept.

- **Health and support** (between Parents and guardians and Documents; decided 2026-10-08): allergies, medical conditions, support needs, emergency contact and last updated.
  - **Who sees it:** only the admin and head teacher roles. For anyone else the tab isn't rendered and the endpoint returns 403.
  - **Logging:** opening the tab writes an audit row, and the tab shows "Last opened by {name} {when}".
  - **Empty state:** "No health or support notes", with **Add notes**.

**Accept:**
- Screenshots of every tab at 1280 and 375, filled and empty.
- Deactivate opens the confirm dialog, and Cancel is focused first.
- The KLS number matches the list.

## PR4 · `feat(classes): class page + classes and streams index` (item 6)
**Index** (`/admin/classes`):
- **Header actions:** **Add stream** and **Add class**.
- **Cards:** one per class and stream, auto-filling at least 268px wide. The whole card links to the class page. Each card shows:
  - name and stream;
  - class teacher (avatar and name), or a "No class teacher" badge;
  - student count and attendance %;
  - a performance hint: "Average 68% · C" and "▲ 3 since last exam", or "No exams for this class".
- **Empty:** "No classes yet", with **Add class**.

**Class page:**
- **Header:** breadcrumb, then actions **Take attendance**, **Enter marks** and More.
- **Header panel:**
  - 4 tiles: class teacher (a link to their profile), students with girls and boys, average with grade for the latest exam plus its change, and attendance this week with "present today".
  - **Grade distribution** as a stacked bar with counts.
  - **Students by gender.**
  - **Subjects with their teachers**, or a "No teacher" badge.
- **Below:** the PR2 people list, scoped to the class, without the Class chip. A click on a student opens their profile.

**Accept:**
- A click on a class card opens its page; a click on a student row opens the student profile.
- The numbers match the class's data.
- At 375 and 1280: no sideways scroll, targets ≥ 44px.

---

# Phase 2 (items 4, 5, 7)

## PR5 · `feat(profiles): teacher profile` (item 4)
**Header:** role badge, "Class teacher of {class}" (a link), and an invite badge:

| Invite status | Badge | Header action |
|---|---|---|
| Not invited | Not invited | **Send invite** (primary) |
| Invited | Invited {date} · not accepted yet | **Resend invite** |
| Accepted | Joined {date} | none |

Also **Edit**, and More: Edit details, Assign classes and subjects, Reset password (only once joined), then a separator and **Deactivate account**, through the confirm dialog.

**Tiles:**
- **Classes:** count and names.
- **Subjects:** count and names.
- **Workload:** lessons per week, from the timetable. **If the school has no timetable, show the number of class–subject assignments instead and say so.**
- **Still pending:** count and the first item, e.g. "Science marks · Primary 5 Blue".

**Teaches table:** class, subject, students and mid-term marks status (Entered / Pending), plus today's attendance status for their class.

**Contact:** `tel:` and `mailto:` links (44px), last login and account state. An unaccepted invite shows "Invite sent, link valid 72 hours".

## PR6 · `feat(profiles): parent profile` (item 5)
**Header:** "Parent" badge and "{n} children at {school}". Actions are **Message on WhatsApp**, **Edit** and More: Edit details, Link a child, Reset password, then a separator and **Deactivate account**.

**Children:** one card per child with:
- avatar, name (a link to the profile), class and KLS number;
- attendance, and the balance or "Fees cleared";
- **Report card** and **Fees** buttons.

Empty: "No children linked yet", with **Link a child**.

**Contact:** phone and email.

**WhatsApp and access:**
- **Opt-in status:** "Opted in on {date}", "Opt-in request sent, waiting for reply", or "Not opted in" with **Send opt-in request**.
- **Last login, and messages this term.**

**No income, profession or occupation fields.**

## PR7 · `fix(layout): account menu popover + fixed sidebar` (item 7)
**Account card** (sidebar footer):
- avatar 40, full name in normal case and email, both truncated with an ellipsis on the right;
- a `chevrons-up-down` icon;
- 56px tall.

**The popover is anchored to the card,** opening upwards, the same width as the sidebar (224px), 8px above the card.
- **Contents:** an identity row, then **Edit profile**, **Change password**, **Settings**, a separator and **Log out**. Each item is a 44px row with an 18px icon.
- **Not a full-screen overlay:** no backdrop, and **the sidebar never moves or slides.**
- **On mobile:** the same popover hangs from the top-bar avatar, right-aligned, at most `100vw − 16px` wide.

**Behaviour:** the menu rules from Shared rules, and it also closes on navigation.

**Accept:**
- Open the menu and the sidebar's `getBoundingClientRect().left` stays 0.
- The popover sits entirely inside the viewport at 375 and 1280.
- Keyboard: Tab to the card, Enter opens with focus on Edit profile, the arrow keys move, Esc closes and focus is back on the card.

---

## Every PR
- **Regression checks:**
  - the mobile menu opens exactly once;
  - the Toshi split-layout still collapses and resizes (where Toshi is on);
  - the sidebar footer still works;
  - the design-system feature tests pass.
- **Screenshots:** before/after at 375 and 1280.
- **knowledge.md:** stamped with the branch-point SHA.

## What the current pages get wrong
Checked against your screenshots of test.klassapp.xyz (7 Oct). The full list, by page, is at the bottom of `concepts/admin-mvp/index.html`. The ones that matter most:
- **Everywhere:**
  - **Toshi:** while it's "coming soon", a floating "Toshi Agent · Talk" pill covers table columns and the Manage links, and an orange tab sits on the right edge of every page.
  - **Capitals:** names print in capitals.
  - **Names in URLs:** e.g. `/admin/student/show/Aaron%20Mbabazi`.
  - **Avatars:** grey silhouettes, and a broken image on Edit student.
  - **Sidebar:** about 96px icon-over-label items.
  - **Search:** a "⌘K" hint.
- **Dashboard:** a Toshi carousel fills the first screen, and a Toshi side panel opens by default. The setup card lists **EMIS / Ministry code** and **UNEB centre number** (country-specific) with no "x of y". The account menu is a tall stack of tiles that **pushes the sidebar off screen**.
- **Students list:** about 440px of stacked filters plus a Filter button. Ticking rows does nothing. No KLS column, and a "#" column.
- **Student profile:**
  - **Tabs:** 12, including Medical History for everyone and Bank Details.
  - **Actions:** Delete beside Edit.
  - **Country-specific fields:** LIN, School Pay number.
  - **Data:** no attendance, marks or fees.
- **Edit student:** Country, District (both required) and Pincode. Siblings is a required select. Gender is required, Male or Female only.
- **Teachers:** the Filter card and "Starts with". "Manage" is the only action. One-letter grey initials. No invite status. **Edit returns 404** (PR0).
- **Add teacher:**
  - **Pre-filled values:** date of birth 07/10/2001 and Employee ID "EMP001".
  - **Required fields:** Designation, Mobile, Email, Gender and Joining date.
  - **Layout:** four tabs, and no invite.
- **Parent profile:**
  - **Fields:** Profession, Designation, Organization, Official address, **Annual income** and Qualification.
  - **Actions:** five header buttons in four colours, with Delete next to Deactivate.
  - **Children:** behind a tab.
- **Classes:**
  - **Academic year:** chosen in three places.
  - **Wording:** "Assigned streams 1/1", "Open roster" and "Fallback / Effective teacher".
  - **Missing:** student counts, attendance, performance, and Add class or Add stream.
  - **Roster:** read-only, with unlinked names and "Pass" for everyone mid-term.

**Not attached and not reviewed:** the parents list and the admin attendance page.

## Open questions
1. **Toshi with Toshi off:** keep the quiet "coming soon" line (this brief), or nothing at all (dashboard v2)?

## Batch two
Specified in `handoff-2026-10-08-admin-batch2.md`: subjects, attendance overview, exams by subject, and the shared add and edit person forms.
