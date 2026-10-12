# Sidebar v3: working plan (2026-10-10). Not yet built.

## Proposed nesting: 12 top-level rows, 3 section labels
- Dashboard (no label)
- PEOPLE
  - Students ▸ All students, Admissions, Health records, Transport
  - Teachers and staff
  - Parents
- ACADEMICS
  - Classes and subjects ▸ Classes and streams, Subjects, Timetable
  - Attendance
  - Exams and reports ▸ Exams and marks, Grading, Report cards
  - Library
- SCHOOL
  - Fees ▸ Overview, Unmatched payments (count badge)
  - Messages ▸ WhatsApp, Calendar
  - Approvals (count badge)
  - Settings ▸ School details, Academic years and terms, Data exports
- Help: in the account popover (Edit profile, Change password, Help, Log out); two clicks.

## Height budget at 1209x690 (target: everything fits on Dashboard, no parent open)
- Top: logo + collapse button 52px.
- List: 12 rows × 36px + 2px gaps within sections (8px) + 3 labels × 26px (8 top + 14 + 4) + 8px padding = about 526px.
- Footer, pinned: 1px border + 8 + Toshi line (2 lines, 32) + 6 + account card 52 + 8 = about 107px.
- Total about 685px of 690. Tight: measure, and trim label padding if needed.
- With a parent open, the list scrolls inside itself; the footer stays pinned.

## Decisions to state in the handoff
- **School name:** in the page header (with the Academic year select), not in the sidebar. On the phone, the drawer top shows it.
- **Active and hover:** a grey pill, rgba(15,23,42,.08) for active (text weight 600, `aria-current`) and .05 for hover. Focus is an inset 2px #1E6FD9 ring.
- **Section labels:** 11.5px/700 uppercase, #64748B (4.55 on #FAFAF5); never collapsible.
- **Children:** 32px rows, indented 28px, a 1px #E2E8F0 guide line, no icons. Only the current page's parent opens automatically.
- **Storage:** key `ka:sidebar:v3` = `{rail:boolean}` only. Parents aren't persisted. Old keys are ignored.
- **Rail:** 64px wide; section labels become 1px dividers; tooltips on hover and focus. A parent in the rail opens a flyout with its children. The footer shows the K icon and the avatar.
- **Tablet:** 1024–1279 starts expanded (so 1209 is expanded); 761–1023 starts as the rail. The user's choice overrides. With Toshi on at 761–1279, the Toshi launcher row replaces the quiet line (Toshi handoff).
- **Phone drawer (375):** min(320px, 100vw − 56px) wide; 48px rows, 44px children; accordion; footer pinned; a 48px Academic year select under the logo.

## Note to report
Only the two TinyFish screenshots were attached. The screenshot of the current build wasn't in the upload.
