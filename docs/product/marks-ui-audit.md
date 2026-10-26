# Marks UI audit

Status: **awaiting the product owner's approval.** Docs only. Nothing here is a build.

Read with [`marks-flow.md`](marks-flow.md). [#1232](https://github.com/KlassApp-Foundation/KlassApp/pull/1232) is merged (`61516ec7`), and the gaps from this walk are in that file's phase plan.

Walked on `main` at `51d67da7` (25 October 2026), in a local app with synthetic demo data only:

- Demo Junior School and Demo Senior School, from `DemoJuniorSchoolSeeder` and `DemoSeniorSchoolSeeder`.
- Sunset PS, from `demo:provision`. The command created the school, classes, and staff, then stopped while creating students, so Sunset has class teachers and almost no learners. Its marks, submission, release, and report-card screens are the empty states. The class page was still walked.
- A parent on the local WhatsApp simulator (`e2e/whatsapp-sim`, the kit from the local-testing note). No production school, no production data, no Meta number.

Desktop shots are 1280 wide. Phone shots are 390 wide. Paths are under [`screenshots/marks-ui/`](screenshots/marks-ui/).

## What was checked

| Role | School | Steps opened |
|---|---|---|
| School admin | Demo Junior | Dashboard, classes and the P.7 roster, the legacy classes list, add class, class setup, the invite URL, the stream edit URL, exams, the marks filter, the class sheet, submissions, an approved detail and the new pending detail, release (default, Term II stream A, and the release result), the releases list, report cards, Approvals, spreadsheet import |
| Class teacher (P.7) | Demo Junior | Dashboard, exam list, enter for the class's mathematics and English, import, view, report cards, release |
| Subject teacher (P.7 mathematics; also class teacher of Baby Class) | Demo Junior | Dashboard, exam list, blank entry, save, submit, the other teacher's exam (refused), report cards, release |
| School admin | Demo Senior | Classes, legacy classes list, S.6 and S.1 class sheets, release, report cards |
| Class teacher (S.6) and subject teacher (S.6 biology) | Demo Senior | Exam list or entry, report cards, release for the class teacher |
| School admin | Sunset PS | Dashboard classes, release, report cards, submissions |
| Parent | Demo Junior, portal and WhatsApp | Dashboard, grades for a nursery child and a P.5 child before release. MARKS, GRADES, and REPORT before release. After one release, MARKS and REPORT from a parent of a released child |

One local release was made, on Demo Junior only: Term II, end of term, P.7 stream A. It is recorded under finding 14.

## Counts

| Severity | Findings |
|---|---|
| High | 8 |
| Medium | 7 |
| Low | 2 |

## Findings

### 1. The admin class page cannot assign a class teacher

| | |
|---|---|
| Severity | High |
| Phase | 1 |
| Role | School admin |
| Step | Class teacher assignment |
| Screen | `/admin/classes` and `/admin/classes/{section}` |

Classes and streams in the menu opens a page that calls itself a read-only view. Each card shows the class teacher and Open roster. The P.7 roster shows "Fallback teacher" and "Effective teacher" and the subject names. There is no assign, change, or invite control. The same cards, with teachers already named and no control, are on Demo Senior and on Sunset PS.

[Desktop](screenshots/marks-ui/admin-junior-classes-1280.png) · [Phone](screenshots/marks-ui/admin-junior-classes-390.png)

[P.7 roster, desktop](screenshots/marks-ui/admin-junior-class-p7-1280.png) · [Phone](screenshots/marks-ui/admin-junior-class-p7-390.png)

[Senior, desktop](screenshots/marks-ui/admin-senior-classes-1280.png) · [Phone](screenshots/marks-ui/admin-senior-classes-390.png)

[Sunset, desktop](screenshots/marks-ui/admin-sunset-classes-1280.png) · [Phone](screenshots/marks-ui/admin-sunset-classes-390.png)

Suggested fix: on this page, let the admin pick an existing teacher or send an invite, and replace a teacher who is already set. One assignment service, as phase 1 already says.

### 2. The other classes page hides the invite and its Edit link does nothing

| | |
|---|---|
| Severity | High |
| Phase | 1 |
| Role | School admin |
| Step | Class teacher assignment |
| Screen | `/admin/sections` |

This list is titled Classes. Each row has Add stream, the teacher's name, Edit, and Delete. Invite Class Teacher is not shown once a teacher is set. Edit is an anchor to `#`, so it does not open the stream form. Delete is in the same row as the only place a person might look to change the teacher. The menu does not open this URL. Classes and streams opens `/admin/classes`.

[Desktop](screenshots/marks-ui/admin-junior-sections-1280.png) · [Phone](screenshots/marks-ui/admin-junior-sections-390.png)

[Senior, desktop](screenshots/marks-ui/admin-senior-sections-1280.png) · [Phone](screenshots/marks-ui/admin-senior-sections-390.png)

Suggested fix: retire this list, or make Edit open the assignment control and remove the dead link. Do not leave Delete as the action next to a teacher's name.

### 3. An assign form exists, and nothing in the dashboard links to it

| | |
|---|---|
| Severity | Medium |
| Phase | 1 |
| Role | School admin |
| Step | Class teacher assignment |
| Screen | `/admin/class-teacher-invite/{section}/create` and `/admin/standardLink/edit/{id}` |

Opening the invite URL for P.7 shows "Assign a class teacher for P.7", an email field, and a dropdown of existing teachers. The stream edit URL has a Class Teacher dropdown. Neither is linked from the class card. The sections list links to the invite only when the class has no teacher, and its Edit link does not open the stream form.

[Invite, desktop](screenshots/marks-ui/admin-junior-invite-1280.png) · [Phone](screenshots/marks-ui/admin-junior-invite-390.png)

[Stream edit, desktop](screenshots/marks-ui/admin-junior-stream-edit-1280.png) · [Phone](screenshots/marks-ui/admin-junior-stream-edit-390.png)

Suggested fix: the dashboard class page should be the only door, and these forms should call the same service.

### 4. Class setup lists the teacher and is not in the menu

| | |
|---|---|
| Severity | Medium |
| Phase | 1 |
| Role | School admin |
| Step | Class teacher assignment |
| Screen | `/admin/class-setup` |

The page shows students, subjects with a teacher, and the class teacher. Streamed classes appear twice (P.5, P.6, and P.7 on Junior; the same shape on Sunset) with no control to change the teacher. The only action is New staff link. The item is not in the menu.

[Desktop](screenshots/marks-ui/admin-junior-class-setup-1280.png) · [Phone](screenshots/marks-ui/admin-junior-class-setup-390.png)

Suggested fix: if this page stays, link it from Classes and streams and let the teacher cell be changed. Otherwise do not leave it as a second, unlisted place to look.

### 5. Add class offers different class names from the ones the school uses

| | |
|---|---|
| Severity | Medium |
| Phase | 1 |
| Role | School admin |
| Step | Class teacher assignment |
| Screen | `/admin/classes/create` |

The legacy list's Add Class button opens Add Sections. The dropdown says Primary One through Primary Seven. The classes already in the school are P.1 through P.7. A person trying to fix a class can add a second class under another name.

[Desktop](screenshots/marks-ui/admin-junior-add-class-1280.png) · [Phone](screenshots/marks-ui/admin-junior-add-class-390.png)

Suggested fix: use the same class names the roster shows, or remove this form once the dashboard can assign a teacher.

### 6. Saving marks leaves a blank form

| | |
|---|---|
| Severity | High |
| Phase | 2 |
| Role | Subject teacher, and the class teacher on the same form |
| Step | Enter or upload |
| Screen | `/teacher/exam/{exam}/marks/enter` |

The open P.7 mathematics form lists ten students and empty mark boxes. Download template, Import from spreadsheet, Submit for review, and Save Marks are all on the page before anything is typed. Save Marks stores the mark, then opens the exam list with "Success! marks saved!". Going back to the form still shows empty boxes and the line "1 mark already saved. View them". The class teacher's copy of the same form is the same shape. Senior biology entry is the same blank form.

[Blank entry, desktop](screenshots/marks-ui/teacher-subject-enter-1280.png) · [Phone](screenshots/marks-ui/teacher-subject-enter-390.png)

[After save, desktop](screenshots/marks-ui/teacher-subject-after-save-1280.png)

[Class teacher entry, desktop](screenshots/marks-ui/teacher-class-enter-1280.png) · [Phone](screenshots/marks-ui/teacher-class-enter-390.png)

[Import, desktop](screenshots/marks-ui/teacher-class-import-1280.png) · [Phone](screenshots/marks-ui/teacher-class-import-390.png)

[Senior biology, desktop](screenshots/marks-ui/teacher-senior-subject-enter-1280.png) · [Phone](screenshots/marks-ui/teacher-senior-subject-enter-390.png)

Suggested fix: keep the person on the form, show the saved mark in the box, and do not offer Submit for review until the subject has marks.

### 7. After submit, the marks page says the work is unmarked

| | |
|---|---|
| Severity | High |
| Phase | 3 |
| Role | Subject teacher |
| Step | Teacher submits |
| Screen | `/teacher/exam/{exam}/marks/view` and the entry page after submit |

Submit for review shows "Success! Marks submitted for review." and opens the view. The view title is the exam, the saved mark is in the table, and the banner says "Unmarked work". The entry page still has Submit for review, still has empty boxes, and now requires "Reason for correcting submitted marks". It does not say that the subject was submitted, and it does not show a reason from the admin, because the return step never reached this page (finding 8).

[After submit, desktop](screenshots/marks-ui/teacher-subject-after-submit-1280.png) · [Phone](screenshots/marks-ui/teacher-subject-after-submit-390.png)

[View while marks exist, desktop](screenshots/marks-ui/teacher-class-view-1280.png) · [Phone](screenshots/marks-ui/teacher-class-view-390.png)

Suggested fix: the banner should say submitted, or returned, with the admin's reason. Hide Submit once the subject is in review. Show the saved marks in the boxes.

### 8. Lock, return, and approve do not run from the review page

| | |
|---|---|
| Severity | High |
| Phase | 3 |
| Role | School admin |
| Step | Admin approves or returns |
| Screen | `/admin/marks/submissions/{exam}` |

The new mathematics row is Submitted and Pending. The row's button says Lock and the confirm says "Lock this submission". The button's form is the bulk Approve form, not the lock form: the lock, reject, and reopen forms are nested inside that form, and the browser drops the inner forms. The click posts to the approve URL. The row stays submitted and pending. Reject is not on the page until the row is locked, and the checkbox for Approve Selected is also only there once the row is locked. An older row that is already Approved still shows Lock.

The queue itself does show the new row, "1 awaiting review", and a badge of 1 on Mark submissions. The only action on the queue is View Details. Approvals (`/admin/approvals`) says marks requests will appear there and is empty.

The detail heading says "Class: primary". The row says P.7. Primary is the level band.

[Queue, desktop](screenshots/marks-ui/admin-junior-submissions-1280.png) · [Phone](screenshots/marks-ui/admin-junior-submissions-390.png)

[Pending detail, desktop](screenshots/marks-ui/admin-junior-submission-pending-1280.png) · [Phone](screenshots/marks-ui/admin-junior-submission-pending-390.png)

[Approved row still offering Lock, desktop](screenshots/marks-ui/admin-junior-submission-approved-1280.png) · [Phone](screenshots/marks-ui/admin-junior-submission-approved-390.png)

[Approvals, desktop](screenshots/marks-ui/admin-junior-approvals-1280.png) · [Phone](screenshots/marks-ui/admin-junior-approvals-390.png)

Suggested fix: one form per action, outside any other form. The button that posts a return should say Return and require a reason. Approve should be available on a submitted row. The heading should name the class (P.7), not the level band. Point Approvals at this queue, or stop saying that marks requests appear there.

### 9. The exam list says Submitted for sittings the teacher still has open

| | |
|---|---|
| Severity | Medium |
| Phase | 3 |
| Role | School admin |
| Step | Teacher submits |
| Screen | `/admin/exams` |

The admin list marks rows Submitted and offers Marksheet and Import from spreadsheet. The teacher's list for the same kind of row says undone, and offers Enter / Edit Marks, View Marks, Marksheet, and Import. The two lists do not use the submission row.

[Desktop](screenshots/marks-ui/admin-junior-exams-1280.png) · [Phone](screenshots/marks-ui/admin-junior-exams-390.png)

[Teacher list, desktop](screenshots/marks-ui/teacher-subject-marks-1280.png) · [Phone](screenshots/marks-ui/teacher-subject-marks-390.png)

Suggested fix: show the submission state (not started, submitted, returned, approved) on both lists, and use one set of actions.

### 10. The combined sheet is not the page the menu opens, and it has no stream

| | |
|---|---|
| Severity | High |
| Phase | 4 |
| Role | School admin |
| Step | Combined mark sheet by class and stream |
| Screen | `/admin/exams`, `/admin/marks`, `/admin/marks/classes/{section}` |

Exams and marks opens the exam list, not a sheet. `/admin/marks` is a filter ("Select class, term and exam type") and is not an item in the menu. The class sheet at `/admin/marks/classes/10` is read-only, fixed to one exam type, and has no stream control. P.7 A and B are one list. On Demo Senior, S.1 East and West are one list too. Grades include P7 and P8, which read as class names. The release page, after Term II and stream A are chosen, does show "P.7 · A", a total, an average, and a grade.

[Filter, desktop](screenshots/marks-ui/admin-junior-marks-filter-1280.png) · [Phone](screenshots/marks-ui/admin-junior-marks-filter-390.png)

[P.7 sheet, desktop](screenshots/marks-ui/admin-junior-class-marks-1280.png) · [Phone](screenshots/marks-ui/admin-junior-class-marks-390.png)

[S.1 sheet, desktop](screenshots/marks-ui/admin-senior-class-marks-s1-1280.png) · [Phone](screenshots/marks-ui/admin-senior-class-marks-s1-390.png)

[S.6 sheet, desktop](screenshots/marks-ui/admin-senior-class-marks-s6-1280.png) · [Phone](screenshots/marks-ui/admin-senior-class-marks-s6-390.png)

[Release sheet for P.7 A, desktop](screenshots/marks-ui/admin-junior-release-term2-1280.png) · [Phone](screenshots/marks-ui/admin-junior-release-term2-390.png)

Suggested fix: the menu item should open one sheet for a class, with a stream choice, using the same numbers as the release sheet. Do not use the level band's letter-plus-number as a grade label that looks like a class.

### 11. The teacher's combined marksheet is a download, and the count on each row is unexplained

| | |
|---|---|
| Severity | Medium |
| Phase | 4 |
| Role | Class teacher |
| Step | Combined mark sheet by class and stream |
| Screen | `/teacher/exam/marks` |

The class teacher sees the class's subjects, including subjects they do not teach, plus Combined Marksheet for the class. That control downloads a file. It does not open the sheet. Each exam row ends with a fraction such as "3 of 3" with no label. The view page also offers + Add Marks, All Classes, and All Exams on a page that is already one exam.

[Desktop](screenshots/marks-ui/teacher-class-marks-1280.png) · [Phone](screenshots/marks-ui/teacher-class-marks-390.png)

Suggested fix: open the same class-and-stream sheet the admin sees. Label the fraction, or remove it. Drop the extra filters on a one-exam view.

### 12. Release offers to send a sitting the page itself calls not submitted

| | |
|---|---|
| Severity | Medium |
| Phase | 6 |
| Role | School admin and class teacher |
| Step | Release |
| Screen | `/admin/marks/releases/new` and `/teacher/marks/releases/new` |

The default exam is Term III, end of term. Every subject is "Not submitted", including subjects that have marks on the class sheet. The admin still gets Release to all classes, plus "Reason to release before approval". Choosing Term II shows Approved and a full sheet (finding 10). The P.7 class teacher's button says "Release to P.7 A (5 students)" while All streams is one of the choices. The subject teacher who holds Baby Class has Release marks on the dashboard, and the release page says "No exam has marks in your classes yet." Sunset's release page is that same empty line, which matches a school with no exams.

[Default release, desktop](screenshots/marks-ui/admin-junior-release-1280.png) · [Phone](screenshots/marks-ui/admin-junior-release-390.png)

[Class teacher, desktop](screenshots/marks-ui/teacher-class-release-1280.png) · [Phone](screenshots/marks-ui/teacher-class-release-390.png)

[Subject teacher's empty release, desktop](screenshots/marks-ui/teacher-subject-release-1280.png) · [Phone](screenshots/marks-ui/teacher-subject-release-390.png)

[Senior S.6, desktop](screenshots/marks-ui/admin-senior-release-1280.png) · [Phone](screenshots/marks-ui/admin-senior-release-390.png)

Suggested fix: default to a sitting whose subjects are approved, and do not offer Release while the page says Not submitted unless the override reason is filled. A subject teacher's release should not be a dead end that only looks at a nursery class they hold.

### 13. Report cards are per stream for the admin, and only for the class a teacher holds

| | |
|---|---|
| Severity | Medium |
| Phase | 5 |
| Role | School admin, class teacher, subject teacher |
| Step | Report cards |
| Screen | `/admin/reports/cards` and `/teacher/reports/cards` |

The admin page lists each class, and each stream where the class is streamed (P.5 · A, S.1 · East), with Print all, Generate, View marks, and Marksheet. The class teacher sees only their streams: P.7 A and B, or S.6. The mathematics teacher's report cards are Baby Class, "No EOT exam", because that is the class they hold, not P.7. Opening another teacher's exam as that subject teacher returns 403, "You are not authorized to enter marks for this exam." The class teacher can open that same English form. Those two checks match the intended split. The gap is that report cards can show a stream while the class sheet in finding 10 cannot.

[Admin, desktop](screenshots/marks-ui/admin-junior-reports-1280.png) · [Phone](screenshots/marks-ui/admin-junior-reports-390.png)

[Senior, desktop](screenshots/marks-ui/admin-senior-reports-1280.png) · [Phone](screenshots/marks-ui/admin-senior-reports-390.png)

[Class teacher, desktop](screenshots/marks-ui/teacher-class-reports-1280.png) · [Phone](screenshots/marks-ui/teacher-class-reports-390.png)

[Subject teacher's class, desktop](screenshots/marks-ui/teacher-subject-reports-1280.png) · [Phone](screenshots/marks-ui/teacher-subject-reports-390.png)

[Refused exam, desktop](screenshots/marks-ui/teacher-subject-forbidden-1280.png) · [Phone](screenshots/marks-ui/teacher-subject-forbidden-390.png)

[Senior class teacher, desktop](screenshots/marks-ui/teacher-senior-reports-1280.png) · [Phone](screenshots/marks-ui/teacher-senior-reports-390.png)

Suggested fix: keep report cards on the class teacher's own streams. Make the class sheet use the same stream split the report cards already use.

### 14. A release does not message the linked parent

| | |
|---|---|
| Severity | High |
| Phase | 6 |
| Role | School admin, then the parent on WhatsApp |
| Step | Release, then WhatsApp results |
| Screen | `/admin/marks/releases/1` and the simulator |

Releasing Term II, end of term, P.7 stream A completed. Status Released. Sent 0. Skipped 5. The reason on every row is "No number is following this student." The parent linked to one of those children is opted in on a fictional simulator number. The release did not use that number. The form above the button says "What parents receive".

Before any release, the portal and WhatsApp agree. A parent of a nursery child and a P.5 child, both with marks in the school, sees "No results published yet" on the portal and the same line for MARKS, GRADES, and REPORT. MARKS and GRADES then add a third line, "No results found for any of your children," which contradicts the two lines above it. MARKS and GRADES return the same text.

After the release, MARKS from the linked parent shows the released child's four subjects, an average, and "#4 of 10". The release was five students in stream A. The other child at that parent, who was not in the release, still gets "No results published yet." REPORT sends a PDF for the released child and the not-published line for the other child. The push from the release itself never arrived.

[Release result, desktop](screenshots/marks-ui/admin-junior-release-result-1280.png) · [Phone](screenshots/marks-ui/admin-junior-release-result-390.png)

[Portal before release, desktop](screenshots/marks-ui/parent-grades-p5-1280.png) · [Phone](screenshots/marks-ui/parent-grades-p5-390.png)

[Nursery child, desktop](screenshots/marks-ui/parent-grades-nursery-1280.png) · [Phone](screenshots/marks-ui/parent-grades-nursery-390.png)

[WhatsApp thread, desktop](screenshots/marks-ui/whatsapp-results-1280.png) · [Phone](screenshots/marks-ui/whatsapp-results-390.png)

Suggested fix: send the release to the linked, opted-in parent, or say on the form that only a following number is messaged. Drop the extra "no results for any child" line when each child already has a line. Decide whether "#4 of 10" is the class or the stream, and use that same pool on the sheet.

### 15. On a phone, the menu button does not receive the tap

| | |
|---|---|
| Severity | High |
| Phase | Every phase, on a phone |
| Role | School admin, teacher, and parent |
| Step | All of them |
| Screen | The top bar at 390 wide |

The menu button is in the bar. A click on its centre is taken by the search button (the command palette). The menu does not open. The pages in this audit were opened by their addresses, so the phone shots show the screen, not a path the person can tap through. This is the same control as [#1159](https://github.com/KlassApp-Foundation/KlassApp/pull/1159).

[Dashboard, phone](screenshots/marks-ui/admin-junior-dashboard-390.png) · [Desktop, for the menu that is visible there](screenshots/marks-ui/admin-junior-dashboard-1280.png)

Suggested fix: the menu and the search control both need a tap target that is not covered. Until that is true, none of the marks steps are reachable on a phone from the menu.

### 16. Upcoming exams shows zero while the marks list has open exams

| | |
|---|---|
| Severity | Low |
| Phase | 2 |
| Role | Class teacher and subject teacher |
| Step | Enter or upload |
| Screen | `/teacher/dashboard` |

The tile says 0 Upcoming Exams. The marks list for the same person has undone exams. The tile's link goes to the marks list, not to a missing page.

[Class teacher, desktop](screenshots/marks-ui/teacher-class-dashboard-1280.png) · [Phone](screenshots/marks-ui/teacher-class-dashboard-390.png)

Suggested fix: count the open exams this person can mark, or rename the tile so zero means "nothing on the calendar".

### 17. Admin import is a second door to the same file upload

| | |
|---|---|
| Severity | Low |
| Phase | 2 |
| Role | School admin |
| Step | Enter or upload |
| Screen | `/admin/exams/{exam}/marks/import` |

The exam row's Import from spreadsheet opens the same kind of template page the teacher has. That is usable. It is a second copy of the action, and the exam list is the only way to find it.

[Desktop](screenshots/marks-ui/admin-junior-import-1280.png) · [Phone](screenshots/marks-ui/admin-junior-import-390.png)

Suggested fix: keep one import, reached from the sheet and from the teacher's exam row.

## Limit of this walk

`demo:provision` named a school Sunset PS and assigned a class teacher on every class card. There is still no assign control (finding 1). Release says no exam has marks. Report cards say no end-of-term exam. Submissions say no submitted marks. That emptiness is this local run: the provision stopped before a full roster. It is not a separate product finding. The Junior and Senior screens are the ones with marks.

[Classes, desktop](screenshots/marks-ui/admin-sunset-classes-1280.png) · [Phone](screenshots/marks-ui/admin-sunset-classes-390.png)

[Release, desktop](screenshots/marks-ui/admin-sunset-release-1280.png) · [Phone](screenshots/marks-ui/admin-sunset-release-390.png)

[Report cards, desktop](screenshots/marks-ui/admin-sunset-reports-1280.png) · [Phone](screenshots/marks-ui/admin-sunset-reports-390.png)

## What matched the intended step

- A subject teacher who opens another teacher's exam gets 403. The class teacher of that class can open the same form.
- Mark submissions in the menu shows a count, and the count became 1 after submit.
- Before release, the parent portal and WhatsApp both hide marks, including for a child who has marks in the school.
- After a release, WhatsApp MARKS and REPORT show that child and still hide the sibling who was not released.
- Report cards on the admin side already split a streamed class. The release sheet does too, once a stream is chosen.
- Demo Senior uses S.1–S.6 and East/West on the report cards. The class-teacher gap is the same as Junior.

## Additions to the phase plan

Moved into the phase table in [`marks-flow.md`](marks-flow.md). Decision 6 already chooses a stream column and a stream filter, and decision 3 already sends WhatsApp to approved, linked parents, so those two lines follow that spec. The phone-menu tap is noted under the table ([#1159](https://github.com/KlassApp-Foundation/KlassApp/pull/1159)).

## Screenshot index

Every file is in [`screenshots/marks-ui/`](screenshots/marks-ui/). The finding sections above link the pair for that screen. These are the rest of the walk, so a reviewer can see the surrounding pages.

| Screen | Desktop | Phone |
|---|---|---|
| Admin dashboard | [1280](screenshots/marks-ui/admin-junior-dashboard-1280.png) | [390](screenshots/marks-ui/admin-junior-dashboard-390.png) |
| Releases list | [1280](screenshots/marks-ui/admin-junior-releases-1280.png) | [390](screenshots/marks-ui/admin-junior-releases-390.png) |
| Class teacher marks list | [1280](screenshots/marks-ui/teacher-class-marks-1280.png) | [390](screenshots/marks-ui/teacher-class-marks-390.png) |
| Senior class-teacher entry | [1280](screenshots/marks-ui/teacher-senior-enter-1280.png) | [390](screenshots/marks-ui/teacher-senior-enter-390.png) |
| Parent dashboard | [1280](screenshots/marks-ui/parent-dashboard-1280.png) | [390](screenshots/marks-ui/parent-dashboard-390.png) |
