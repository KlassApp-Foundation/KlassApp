# Marks: design brief for Claude Design

*Draft. The instruction here is what each screen must do; the layout, type, colour and component shapes are yours. Every screen is described for the roles that reach it, with its states.*

Sources: [`marks-flow.md`](../product/marks-flow.md), [`marks-ui-audit.md`](../product/marks-ui-audit.md), and the Phase 1–6 pull requests (#1239 to #1244). Screenshots are from the current build and are linked per screen; they show behaviour, not the target look.

**Two rules that outrank any visual idea.**

1. A save is not a submit, a submit is not an approval, and an approval is not a release. The screen must never imply one of those happened when it did not.
2. Nothing a family can see is visible before the school releases it. "Not released" is a state with its own copy, not an empty list.

---

## Roles at a glance

| Role | Reaches | Never sees |
|---|---|---|
| School admin | Everything below except the teacher's own entry form | — |
| Class teacher | Their class dashboard, their class's sheet, release for their class, report cards | Another class's marks |
| Class head (a class teacher on a streamed class) | The same, for **every stream** of their class | Classes they do not hold |
| Subject teacher | Entry for their subject, their own submissions, the sheet read-only | Release, other subjects' entry |
| Parent (WhatsApp) | Released marks, released report cards, fee/attendance replies | Anything unreleased, any other child's marks |

"Approved, linked parent" means: the parent linked their own number to the child, and the school admin or class teacher approved that link. A number that is only in the phone book is not a recipient.

---

## 1. Admin: dashboard (marks entry points)

**Goal:** reach marks work in one tap, and see what is waiting.

**States**
- *Has marks to release:* a Release marks button, with the class or exam named.
- *Nothing to release:* the button is absent, not disabled — a disabled button implies an action exists that does not.
- *Waiting on the admin:* approvals pending, count shown.
- *Loading:* skeleton blocks in the tiles, no layout jump.
- *Error:* the tile says the count could not load; the rest of the dashboard works.

**Data:** open sittings, pending approvals, most recent release and its status, classes held.

**Actions:** open marks, open releases, open approvals, open a class.

**Rules:** the tile counts the same rows the queue shows. If the count and the queue can disagree, show no count.

**Screenshots:** [dashboard](../product/screenshots/marks-ui/admin-junior-dashboard-1280.png) · [phone](../product/screenshots/marks-ui/admin-junior-dashboard-390.png)

---

## 2. Admin: classes list and one class page

**Goal:** see who teaches each class and change it without hunting.

**States**
- *No classes yet:* an empty state that offers "add a class".
- *Class with no class teacher:* an explicit "No class teacher" line with the assign action.
- *Class with a class teacher:* name, email, and a Replace action.
- *Streamed class:* one row per stream, with the head named per stream and the class as a whole named once.
- *Invite pending:* "Invited, expires in N hours" with Resend and Revoke.
- *Not allowed:* a teacher who is not an admin never sees this page at all (404, not a locked screen).
- *Error on save:* the field keeps what was typed; the error sits next to the control, not at the top of the page.

**Data:** class name, stream, class teacher, invite state and expiry, learner count.

**Actions:** assign an existing teacher, invite a new one, replace, revoke, open the class.

**Rules:** every assignment asks for confirmation and writes a log entry. Replacing a teacher revokes that class's pending invite. Class names come from the school's own sections; the page never invents a name.

**Screenshots:** [classes](../product/screenshots/marks-ui/admin-junior-classes-1280.png) · [class page](../product/screenshots/marks-ui/admin-junior-class-p7-1280.png) · [invite](../product/screenshots/marks-ui/admin-junior-invite-1280.png)

---

## 3. Admin: exams and marks (entry and sheet)

**Goal:** find a sitting, see its state, and open the right screen for it.

**States**
- *No exams:* empty state with "create an exam".
- *Exam with no marks:* "Not started".
- *Marks saved, not submitted:* "In progress".
- *Submitted:* "Submitted", entry is closed to the teacher.
- *Returned:* "Returned", with the reason available to the owner and to admins.
- *Approved:* "Approved", release is now possible.
- *Locked:* "Locked", read-only until an admin reopens.
- *Loading:* rows keep their height; states do not pop in after the numbers.

**Data:** sitting (exam type and term), class, subject, teacher, state, marks count, last change.

**Actions:** open entry, open the sheet, approve, return with a reason, lock, reopen.

**Rules:** the state shown here is the submission row, never `exams.status`. One sitting is one exam type and one term; a whole-term view is not this screen.

**Screenshots:** [exams](../product/screenshots/marks-ui/admin-junior-exams-1280.png) · [class marks](../product/screenshots/marks-ui/admin-junior-class-marks-1280.png) · [submission pending](../product/screenshots/marks-ui/admin-junior-submission-pending-1280.png) · [approved](../product/screenshots/marks-ui/admin-junior-submission-approved-1280.png)

---

## 4. Admin and subject teacher: marks entry

**Goal:** enter or correct a mark per learner, and know it was saved.

**States**
- *Empty subject:* every box blank, Save available.
- *Saved:* the page stays, the boxes show the saved marks, a quiet "Saved" confirmation with the time.
- *Validation error:* the typed value stays, the bad box is marked, the message names the learner.
- *Closed for entry:* submitted, approved or locked — boxes read-only, with the reason ("Submitted for review", "Approved", "Locked") and who can reopen it.
- *Returned:* read-only until reopened, with the return reason shown.
- *Not allowed:* a subject teacher who does not teach this subject gets a plain "not your subject" page, not a broken form.
- *Loading:* the table renders its rows before the values, never a spinner over the whole page.
- *Offline/slow save:* the button shows progress and cannot be double-submitted.

**Data:** learner name and admission number, mark, grade, class, subject, sitting, submission state.

**Actions:** type a mark, save, submit for review (only when something is saved), open the import.

**Rules:** every numeric path writes through one writer. A save never messages a parent. Submit is absent until there is something to submit. Nursery uses the ratings form, not this grid.

**Screenshots:** [subject entry](../product/screenshots/marks-ui/teacher-subject-enter-1280.png) · [after save](../product/screenshots/marks-ui/teacher-subject-after-save-1280.png) · [after submit](../product/screenshots/marks-ui/teacher-subject-after-submit-1280.png) · [forbidden](../product/screenshots/marks-ui/teacher-subject-forbidden-1280.png)

---

## 5. Admin and teacher: import marks from a file

**Goal:** get a spreadsheet in without guessing what will happen.

**States**
- *No file:* the drop zone plus a template download.
- *Parsing:* progress, cancel available.
- *Preview:* every row with a verdict — will save, will skip (and why), will fail (and why).
- *Nothing to import:* "this file has no marks for this sitting".
- *Partial success:* imported rows listed, failed rows listed with the reason and their line numbers.
- *Error:* wrong file type, wrong columns, or a file for another school — each says which, and nothing is written.

**Data:** file name, row count, per-row verdict, learner match by admission number (never by name), subject, mark, reason for a skip.

**Actions:** download template, choose file, review, confirm import, download the failed rows.

**Rules:** the preview is the commit point; nothing is written before it. Re-importing the same file does not duplicate a mark. Rows that fail are reported by line.

**Screenshots:** [import](../product/screenshots/marks-ui/admin-junior-import-1280.png) · [teacher import](../product/screenshots/marks-ui/teacher-class-import-1280.png)

---

## 6. Admin and class teacher: combined sheet (one sitting)

**Goal:** read one class's results for one sitting, with streams separated and positions explained.

**States**
- *No learners:* empty state naming the class.
- *Learners but no marks:* the rows exist, cells are blank (never zero).
- *Partial marks:* blanks stay blank; totals are over the marks present.
- *Stream filter:* whole class by default; one stream narrows rows, and the position and "of" count follow the filter.
- *Wide class:* horizontal scroll with the learner column pinned.
- *Not allowed:* a teacher outside the class sees 404.
- *Loading:* column headers first, then values.

**Data:** learner, stream, subject marks, grade per subject, total, average, position, "of N", the sitting label, the class.

**Actions:** switch sitting, filter stream, sort by a column, download the same sheet, open a learner.

**Rules:** one sheet service computes total, average, position and "of". The sheet never adds up a second time. A stream filter changes the pool, and the "of" count must match the pool on screen. A learner promoted mid-year still appears if they sat the sitting.

**Screenshots:** [class marks](../product/screenshots/marks-ui/admin-junior-class-marks-1280.png) · [sheet](../product/screenshots/marks-ui/teacher-class-marks-1280.png) · [senior streamed](../product/screenshots/marks-ui/admin-senior-class-marks-s1-1280.png)

---

## 7. Admin: submissions and approvals

**Goal:** clear what teachers have submitted, with the reason attached when returning.

**States**
- *Empty queue:* "nothing is waiting" — not a blank table.
- *Pending:* each row shows sitting, class, subject, teacher, when submitted, and what is inside.
- *Returned:* leaves the queue, keeps its reason.
- *Approved:* leaves the queue.
- *Error:* the row stays in the queue with the error next to it; nothing half-applies.
- *Not allowed:* non-admins never see it.

**Data:** teacher, class, subject, sitting, submitted at, marks count, state, return reason.

**Actions:** approve, return (reason 10–500 characters), lock, reopen.

**Rules:** each action is its own form, not one submit with a mode. Return requires a reason. Approval is what release later reads.

**Screenshots:** [approvals](../product/screenshots/marks-ui/admin-junior-approvals-1280.png) · [submission pending](../product/screenshots/marks-ui/admin-junior-submission-pending-1280.png)

---

## 8. Admin and class teacher: release marks

**Goal:** release a sitting to families deliberately, knowing exactly who is messaged.

**States**
- *No releasable exam:* empty state naming why.
- *Subjects not approved:* the list is shown per subject with its state, and Release is refused. There is no reason field and no override.
- *Ready:* the scope (all classes, or one class), the count of learners, and one example message.
- *Past sitting:* a checkbox that says the sitting is from an earlier term and must be confirmed before Release.
- *WhatsApp off:* the switch is off by default on a real-school release; copy says the release still happens and opens the portal, Toshi and report cards, and nothing is sent.
- *Template not approved by Meta:* the send is skipped; the page says so plainly rather than failing at the end.
- *Who may release:* a class teacher sees only their class, and only when the school allows it; otherwise a plain refusal page.
- *Scheduled:* a chosen date and time in the school's timezone, shown back in words.
- *In progress:* queued count and "refresh to see each one sent".
- *Result:* per learner — sent, sending, retrying, failed with the reason, or skipped with the reason.
- *Error:* the release row is not written unless every class passed its checks.

**Data:** sitting, class, learners in scope, per-subject approval state, the message preview, who will be messaged (approved, linked, opted-in parents only), the switch state, schedule, result per family.

**Actions:** choose sitting and class, review, confirm past sitting, set the WhatsApp switch, release now or schedule, refresh the result, release again to retry failures.

**Rules:** every subject in scope approved first. No override. Only approved, linked, opted-in parents of learners in that class and sitting. Never the whole school. A number that already got that sitting is skipped. A release never messages anyone whose child has no marks in that sitting.

**Screenshots:** [release](../product/screenshots/marks-ui/admin-junior-release-1280.png) · [past sitting](../product/screenshots/marks-ui/admin-junior-release-term2-1280.png) · [result](../product/screenshots/marks-ui/admin-junior-release-result-1280.png) · [releases](../product/screenshots/marks-ui/admin-junior-releases-1280.png) · [class teacher release](../product/screenshots/marks-ui/teacher-class-release-1280.png)

---

## 9. Admin and class teacher: report cards

**Goal:** produce the document a family receives, and keep it closed until release.

**States**
- *Nothing to report:* no released sitting, or no marks.
- *Draft:* viewable by staff only, marked as not released.
- *Released:* downloadable by the family.
- *Partly released:* some sittings released, some not — the card says which.
- *Missing marks:* a blank subject cell, never a zero.
- *Loading:* the card frame first, values after.
- *Error generating a PDF:* a retry that does not lose the selection.

**Data:** learner, class, stream, term, subject marks and grades, total, average, position, division, attendance, remarks, the school's own grading scale.

**Actions:** choose class and term, preview, download, release (through the release screen, not from here).

**Rules:** division thresholds and ranking stay as they are. A hidden exam type does not appear. A family download always goes through the release gate.

**Screenshots:** [reports](../product/screenshots/marks-ui/admin-junior-reports-1280.png) · [teacher reports](../product/screenshots/marks-ui/teacher-class-reports-1280.png) · [parent grades](../product/screenshots/marks-ui/parent-grades-p5-1280.png)

---

## 10. Admin: exam type settings (composition, profile, legacy bands)

**Goal:** decide what a report card is made of, and own the school's grading scale.

**States**
- *Defaults:* contributes-to-total from the exam type, everything shown, no weights.
- *Weights set:* a weight per contributing type, with the merge rule stated in one sentence.
- *Profile:* current UNEB bands, or the old (pre-NLSC) scale.
- *Legacy bands, never saved:* the example table is shown, clearly labelled an example and not a national standard.
- *Legacy bands saved:* the school's own table, marked as saved.
- *Invalid bands:* a gap or overlap is refused with the row named; nothing is saved.
- *Error:* the previous scale stays in force.

**Data:** exam types, codes, contributes-to-total, show-on-report, weight, curriculum profile, nine legacy rows (letter, points, from, to).

**Actions:** toggle, set weight, choose profile, apply profile, edit and save legacy bands.

**Rules:** the letters and points of the legacy scale are fixed; only the marks move. Bands must run 100 down to 0, no gap, no overlap. One school's bands never reach another.

**Screenshots:** none yet for this page in the marks set.

---

## 11. Admin: school settings for releasing

**Goal:** decide whether class teachers may release.

**States**
- *Never set:* on, with copy that says why (a class teacher could already release their stream).
- *Turned off:* only a school admin releases; teacher screens refuse politely.
- *Saved:* confirmation, no page reload surprise.

**Data:** the switch, its current effect, what it does not change (a teacher still cannot release another class).

**Actions:** turn on or off, save.

**Rules:** the switch is per school. Off is an explicit choice, not a default. A class head's extra reach (all streams) is never a separate setting.

**Screenshots:** none yet.

---

## 12. Class teacher: class dashboard and class marks

**Goal:** see the class they hold, what is outstanding, and the sheet for their class.

**States**
- *No class:* "you are not a class teacher yet".
- *Class with open sittings:* each sitting and its state.
- *Release allowed / not allowed:* the release action is present or absent, never a dead button.
- *Not released:* families see nothing; the teacher still sees the sheet.
- *Loading/error:* as on the admin screens.

**Data:** class name, streams, learners, open sittings, submission states, recent releases.

**Actions:** open entry, open the sheet, release their class (when allowed), report cards preview.

**Rules:** a class teacher sees only their class. A class head sees every stream of it. They never see the whole-school release.

**Screenshots:** [class dashboard](../product/screenshots/marks-ui/teacher-class-dashboard-1280.png) · [class marks](../product/screenshots/marks-ui/teacher-class-marks-1280.png) · [class release](../product/screenshots/marks-ui/teacher-class-release-1280.png)

---

## 13. Parent on WhatsApp

**Goal:** get their child's released results, and nothing else.

**States**
- *Number not linked:* a short reply explaining how to link, with the school's own instructions.
- *Linked, nothing released:* "not released yet" — distinct from "no marks entered".
- *Released:* the mark table for the most recently released sitting, one message.
- *Several children, none released:* one reply with one line per child.
- *Several children, one released:* the released child's table, then one line for the child still waiting.
- *Opted out:* no results message at all; other replies still work.
- *Report card requested before release:* refused, in one line, with when it will be available.
- *Message fails:* the school sees it as failed on the release page; the parent sees nothing.

**Data:** child's name and class, sitting label, subject, mark, grade, total, average, and a "reply GRADES" hint.

**Actions:** reply MARKS or GRADES, ask for the report card, tap the results button.

**Rules:** only released sittings. Only the sender's own children. One reply per question. A message never carries another family's data. Template sends only when Meta has approved the template and the parent has not opted out.

**Screenshots:** [whatsapp results](../product/screenshots/marks-ui/whatsapp-results-1280.png)

---

## Components the design system needs

**Data and status**
- `StatusPill` — not started, in progress, submitted, returned, approved, locked, reopened. One vocabulary across every screen.
- `ReleaseState` — not released, sending, released, part-failed. Distinct from a submission state; the two are often on screen together and must not look alike.
- `CountBadge` — a number with its noun ("12 learners", "3 waiting"), and a variant that refuses to show when the count is not trustworthy.
- `EmptyState` — title, one sentence of cause, one action. Required for every list.
- `InlineError` / `PageError` — field-level and whole-page, with the recovery action.
- `SkeletonRow` — table rows that keep their height while loading.

**Marks-specific**
- `MarkCell` — a mark plus its grade, and a distinct blank state that is not zero.
- `MarkInput` — a numeric cell that validates on blur, keeps the typed value on error, and shows the learner's name in its error.
- `SheetTable` — sticky learner column, stream column, sortable numeric headers, horizontal scroll.
- `StreamFilter` — whole class or one stream, with the "of N" pool stated next to it.
- `PositionCell` — "3 of 48", with the pool named.
- `ApprovalList` — per-subject state with a clear blocking state for release.
- `RecipientRow` — parent, masked number, sent/sending/failed/skipped, and the reason in words.

**Release**
- `ScopePicker` — all classes or one class, with the count that follows.
- `ConfirmPastSitting` — a checkbox that cannot be missed and cannot be pre-ticked.
- `WhatsAppSwitch` — off by default, with the consequence spelled out in one line.
- `ScheduleField` — local time, timezone named, past times refused.
- `MessagePreview` — exactly what a parent receives, in WhatsApp-ish framing.

**Grading**
- `BandEditor` — nine rows, fixed letters and points, editable from/to, gap and overlap feedback while typing.
- `ProfilePicker` — current bands or the school's legacy scale, with the consequence of applying stated.

**Onboarding and identity**
- `ClassTeacherPicker` — choose an existing teacher, invite a new one, replace, revoke; pending state with expiry.
- `StudentIdChip` — a KlassApp ID with copy, and an input that accepts KLS plus 7–16 digits.

**Foundations**
- Density for a table-first product: one row height, one numeric alignment, one header treatment.
- A "quiet confirmation" pattern for saves that must not steal the page (marks save), and a loud one for actions that message families (release).
- Copy rules: never say "sent" for queued; never say "submitted" for saved; never say "approved" for locked.
