# Handoff: admin batch two (subjects, attendance, exams, person forms), 2026-10-08

- **Designed from:** `main @ 254aa194a7ef`. Before starting, run `git log --oneline 254aa194a7ef..main -- resources/views/admin app/Http/Controllers/Admin`.
- **Concept:** `concepts/admin-mvp/batch2.html`: every screen and state side by side at 1280 and 375. It uses the same click-through app as batch one (`app.html`), so rows, menus, the disclosure rows and the forms all work.
- **Same rules as batch one** (`handoff-2026-10-07-admin-mvp.md`, "Shared rules" and "Updated 2026-10-08"):
  - the shell, sidebar groups, Lucide icons and avatars;
  - menus, and Delete never in a row;
  - confirm dialogs;
  - global wording, with no country-specific fields;
  - currency from settings;
  - KLS `KLS` + school number + 4 digits.
- **Measured in the concept** with `getBoundingClientRect` at 1280 and 375. That covers 18 states:
  - subjects (filled, empty), subject page, attendance (filled, empty), exams (filled, empty);
  - student, teacher and parent forms (add, add with errors, edit), and the staff form (add, edit).
  - **Result:** the page never scrolls sideways, and every button, link, input, select, radio and checkbox row is at least 44px tall. On mobile the filter chips scroll inside their own row, as in batch one, and every menu stays inside the viewport.
- **Not measured:** contrast is calculated from hex, using the same pairs as batch one (all at least 4.5:1).

---

## B1 · `feat(subjects): one subject, all its classes`
### Subjects index (`/admin/subjects`)
- **Each subject appears once**, however many classes take it. Today's page has one row per subject per class: LANGUAGE for Baby Class, then again for Middle Class.
- **Header:** "Subjects", "{n} subjects · each listed once, across all its classes", and **Add subject**.
- **Toolbar (the batch-one list pattern):**
  - **Search:** "Search by subject or code".
  - **Chips:** All, Core, Optional, one chip per level from the school's own levels (Nursery, Primary, …), and **Missing a teacher**.
- **Columns:**

  | Column | Content |
  |---|---|
  | Subject | a code tile, name (stretched link to the subject page) and code |
  | Type | Core / Optional, in normal case |
  | Classes | count and range ("9 · Primary 1–6"), plus a "{n} without a teacher" warning badge |
  | Teachers | stacked avatars, up to 3, then "+n" |
  | Average | latest exam: "71% · B ▲ 2". Nursery subjects show "Not examined" |
  | Marks | progress of the current exam, "6 of 9" classes entered |
  | ⋮ | View subject, Edit, Assign teachers, a separator, **Archive subject** (through the confirm dialog) |

- **No Delete.** Archiving keeps marks and report history.
- **Mobile:** card rows: code tile, name, levels, a status badge, ⋮.
- **Empty:** "No subjects yet", one line, and **Add subject**.

### Subject page (`/admin/subjects/{id}`)
- **Header:** breadcrumb, then the name, with a type badge, code and levels.
  - **Actions:** Edit, Assign teachers, and More: Download all marksheets, then a separator and Archive subject.
- **Tiles:**
  - Classes;
  - Teachers (names);
  - Students taking it;
  - Average for the latest exam, with its change since the previous exam.
- **Exams this term:** one row per exam (Beginning of term, Mid-term, End of term, or the school's own exam names).
  - **Row contents:** dates, average, a progress bar ("6 of 9" classes with marks), and a status (Complete, In progress, Scheduled).
  - **Actions:** **Enter marks** (primary) while in progress, or **View marks** once complete. The ⋮ menu has Import from spreadsheet, Download marksheet and Remind teachers.
- **Classes:** one row per class·stream.

  | Column | Content |
  |---|---|
  | Class | a link to the class page |
  | Teacher | for this subject |
  | Average | |
  | Marks | Entered n/n · {a} of {n} · Not started |
  | Report card | Ready · Waiting for marks |

  **Below 760px,** Teacher, Average and Report card are hidden, so the table fits with no sideways scroll.
- **Average by class:** horizontal bars with value labels, and a line saying how many classes have no marks yet.
- **This is the one place where a subject's exams, marks and reports come together** for all its classes.

## B2 · `feat(attendance): admin overview` (`/admin/attendance`)
- **Header:** "Attendance", "Today · {weekday date}".
  - **Period select:** This week, This term, Last 8 weeks; it has a visible label.
  - **Export.**
- **Tiles:**
  - **Present today:** %, a meter, and "231 of 248 · 17 absent".
  - **Registers taken today:** "9 of 11", a meter, and "2 not taken yet" in #92400E.
  - **This week:** with the change on last week.
  - **This term.**
- **Who has taken today:** one row per class·stream, **not-taken first**.
  - **Each row:** class·stream and class teacher (the stream's own, or the class default).
  - **Status:** "Taken 08:12" (green badge), or "Not yet" with a 44px **Remind** button. The reminder goes to the teacher on WhatsApp or email.
- **By day of the week:** vertical bars Mon–Fri for the selected period, with value labels. The lowest day is drawn in #B45309, with a one-line note computed from the data, e.g. "Fridays average 5 points below the rest of the week". Show the note only when the gap is at least 3 points.
- **By class:** horizontal bars per class·stream for the period.
- **Trend:** a line over the last 8 weeks, 80–100% axis.
- **Empty:** "No attendance taken yet this term", one line, and **Remind class teachers**.
- **Not reviewed against today's page:** no screenshot of the current admin attendance page was attached.

## B3 · `feat(exams): subject-first exams page` (`/admin/exams`)
- **Header:** "Exams and marks", "{term} · by subject", the **Term** select with its label, and **Add exam**.
- **Exam chips:** one per exam in the term, with its state ("Mid-term · In progress", "End of term · From 24 Nov"). The current exam is selected.
- **Summary card:**
  - **Progress:** "Mid-term exams · {dates}", "Marks entered for **25 of 46** subject classes", and an 8px progress bar.
  - **Report cards:** "{n} of {m} classes have every mark".
  - **Buttons:** **Remind teachers** and **Generate for {n} classes**. This generates only for complete classes; the others follow when their marks are in.
- **Toolbar:** search subjects, plus chips All, Not started, In progress, Complete, with counts.
- **Subject rows:** one per subject in the exam.
  - **Toggle:** a 44px disclosure button (`aria-expanded`, `aria-controls`).
  - **Row contents:** subject name (a link to the subject page), teacher avatars and class count, a progress bar "x of y", a status badge, the average so far, and ⋮ (Download all marksheets, Import from spreadsheet, Remind teachers).
  - **Expanded:** one row per class·stream with its teacher, a marks badge ("Entered 31/31", "8 of 16", "Not started"), **Enter marks** (primary) or **View**, and ⋮ (Import from spreadsheet, Download marksheet, Remind teacher).
- **Wording and actions:**
  - Words, not codes: "Mid-term", not MID/EOT/BOT; "Not started", not "Undone"; levels in normal case.
  - No edit or delete icons in rows. Exam edit and delete live on the exam itself, behind More, with the confirm dialog.
- **Subjects with no exam:** a single line at the end, e.g. "Language, Numbers and Reading (Nursery and Reception) have no Mid-term exam."
- **Empty:** "No exams this term", one line, and **Add exam**.

## B4 · `feat(people): shared add and edit person form`
- **One Blade form component** (`<x-person-form kind="student|teacher|parent|staff" mode="add|edit">`) **replaces** today's add/edit student, add/edit teacher and edit parent forms.
- **Layout:**
  - **Page:** breadcrumb, title ("Add student" / "Edit Amara Okafor") and one line: "Fields are required unless marked Optional."
  - **Sections:** white cards, 2 columns at 1280 and 1 at 375.
  - **Labels:** above the fields, 14px/700, with "Optional" in `--d-text-secondary`.
  - **Controls:** inputs and selects 44px; textareas 88px.
  - **Radio and checkbox rows:** 44px, with an 18px control and the label inside the hit area.
  - **Focus:** a 3px `--d-blue` outline.
- **Errors:**
  - **Summary:** a box at the top (`role="alert"`) listing each problem as a link to its field.
  - **Field:** a red border and a message with an icon, linked with `aria-invalid` and `aria-describedby`.
  - **No input is lost** on error.
- **Action bar:** sticky at the bottom: **Cancel**, **Save and add another** (add only), and the primary button. The primary is "Add student", "Save and send invite", "Save and send opt-in" or "Save changes".
- **No dates or IDs are pre-filled.** Today date of birth comes pre-filled with 07/10/2001 and Employee ID with "EMP001". Joining date defaults to today and says so.

**Fields by form:**

**Student**
- **Student:** First name, Last name, Gender (Female, Male, Not specified; defaults to Not specified), Date of birth (*optional*), Photo (*optional*; initials are shown until a photo is added).
- **Class:** Class, Stream (only for classes with streams), Joining date (defaults to today), and the **KLS number**, read-only: "Created when you save" when adding, the number when editing.
- **Parent or guardian:**
  - **Find an existing parent** (search by name or phone, *optional*), **or add a new one:** First name, Last name, Relationship (Mother, Father, Guardian, Other), Phone (with country code), "This number is on WhatsApp" (ticked by default), and "Send a WhatsApp opt-in message after saving" (ticked by default).
  - **When editing,** the linked parents are listed, with **Link another**.
- **Health and support** (*optional*; only rendered for the admin and head teacher roles; "Each view is logged"): Allergies or medical conditions, and Support needs.

**Teacher**
- **Teacher:** First name, Last name, Role (Teacher, Head teacher, Deputy head teacher), Staff number (*optional*), Phone, Email (one of the two is required to send the invite), Photo (*optional*).
- **Teaching** (*optional*): class and subject pairs (add or remove rows; the remove button is 44px and labelled), and Class teacher of (one stream, or None).
- **Invite:** "Send an invite": **By email** (default), **By WhatsApp**, or **Not yet**. "They choose their own password. The invite link works for 72 hours. You can send or resend it later from their profile."
- **When editing,** Invite becomes **Login**: "Joined on {date}" and **Reset password**, or "Invited {date}" and **Resend invite**.

**Parent**
- **Parent or guardian:** First name, Last name, Phone, Email (*optional*), "This number is on WhatsApp", Photo (*optional*).
- **Children:** link at least one child (search by name or KLS number). Each child row has its own Relationship select and a 44px unlink button.
- **Invite:**
  - "Send a WhatsApp opt-in message after saving" (ticked by default): they reply YES to start receiving messages.
  - "Also send a login invite by email" (*optional*): needs an email.
- **When editing:** "Opted in to WhatsApp on {date}", and **Send a login invite by email**.

**Staff** (non-teaching: bursar, librarian, school admin, office staff)
- First name, Last name, Role, Staff number (*optional*), Phone, Email, Photo (*optional*). The role decides what they can see.
- **No invite section**, because the brief asked for invites on teachers and parents only (see open questions).

**Removed everywhere:** these legacy and country-specific fields aren't in any form, and aren't shown on profiles:
- Pincode, Country, District, Mode of transport, Siblings;
- LIN number, School Pay number, School student ID, Library card number, Bank details;
- Employee ID as required, Designation, Educational qualification, Address tab;
- Profession, Organization name, Official address, Annual income, Qualification.

**Keep the data:** the columns stay in the database for now. Only the UI stops asking for them.

**Accept for B4:**
1. **One component:** the same component renders all four kinds in both modes.
2. **No removed fields:** none of the removed fields appears in any form's HTML.
3. **Validation:** on the server, with the error summary; each error links to its field.
4. **Student:** saving creates a student with a KLS number in the `KLS{school}{0001}` format.
5. **Teacher and parent invites:** saving a teacher with "By email" sends an invite whose link expires in 72 hours. Saving a parent with opt-in ticked sends the WhatsApp opt-in.
6. **Edit teacher:** loads, which depends on batch one's PR0 404 fix.
7. **Layout:** at 375 and 1280, no sideways scroll and every control ≥ 44px. The keyboard reaches every field in order. The sticky bar never covers the focused field (use `scroll-padding-bottom`).

---

## Every PR
- **Regression checks:** the mobile menu opens exactly once, the Toshi split-layout still collapses and resizes (where Toshi is on), the sidebar footer still works, and the design-system feature tests pass.
- **Screenshots:** before/after at 375 and 1280.
- **knowledge.md:** stamped with the branch-point SHA.

## What the current pages get wrong
From your screenshots, 7 Oct.
- **Subjects:**
  - One row per subject per class.
  - Names in capitals.
  - A red Delete button on every row next to a plain "Edit".
  - No teachers, averages or links to the subject's exams.
  - The title is "Subject Details", and type is lowercase "core".
- **Exams:**
  - One row per exam × class × subject.
  - Codes (MID, EOT, BOT, "Undone"), level in lowercase, and "Term III" wrapping onto two lines.
  - Four actions per row in two styles, including a delete icon.
  - The floating Toshi pill covers the actions column.
- **Edit student:**
  - Country and District are required, plus Pincode.
  - Siblings is a required select, and there's Mode of transport.
  - Gender is required, Male or Female only (now Female, Male, Not specified everywhere).
  - The avatar is a broken image.
- **Add teacher:**
  - Date of birth and Employee ID are pre-filled.
  - Designation, Mobile, Email, Gender and Joining date are all required.
  - Four tabs.
  - No invite.
- **Edit teacher:** returns 404.
- **Parent profile:** stores Profession, Designation, Organization, Official address, Annual income and Qualification.
- **Attendance:** not reviewed; no screenshot was attached.

## Open questions
1. **Staff logins:** a bursar or librarian can't use KlassApp without a login. Should the staff form get the same Invite section as teachers? The brief asked for invites on teachers and parents only, so it's left out.
2. **Archive versus delete for subjects:** is Archive enough, or do admins also need a true Delete for a subject created by mistake that has no marks? If so: More → Delete, shown only when there are no marks, behind the confirm dialog.
