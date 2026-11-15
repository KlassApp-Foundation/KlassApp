# Marks and report cards — progress

Draft work. Nothing here is merged or deployed.

## Open stack

| PR | What it does |
|---|---|
| #1342 | Per-school report type: Progressive report or Set-based reports. |
| #1343 | Progressive report rules: bands, division ranges, ranking. On a phone, each field stacks under its own label. |
| #1344 | A blank or an absence counts as 0 on the next progressive card. |
| #1345 | Columns use the school's own exam-set names. |
| #1346 | Parity test for points, division and position. |
| #1347 | A release stores the bands, division ranges and ranking. A reprint uses that copy. https://github.com/KlassApp-Foundation/KlassApp/pull/1347 |
| #1348 | One division table on the card, the mark sheets, the download, and the parent and learner marks page. https://github.com/KlassApp-Foundation/KlassApp/pull/1348 |
| #1350 | An absence is shown as ABS. The combined marksheet names each sheet after the school's exam set. https://github.com/KlassApp-Foundation/KlassApp/pull/1350 |
| #1351 | Exam-set columns follow the exam date. The term's start date is the tie-break. https://github.com/KlassApp-Foundation/KlassApp/pull/1351 |
| #1352 | A pilot release tells only the parents or learners the school chooses. https://github.com/KlassApp-Foundation/KlassApp/pull/1352 |
| #1353 | A template option, Progressive (new look). Formal stays the default. https://github.com/KlassApp-Foundation/KlassApp/pull/1353 |

## 2026-11-15 — rules stored on the release

A release copies the grade bands, the division ranges and the ranking method. A reprint of that term uses the copy. Editing **Progressive report rules** changes the next release.

A release from before this column has no copy and the row is not rewritten. Its reprint keeps the default division table and automatic ranking, so a later edit to those settings does not move it. Its grade bands still come from the school, because no copy was stored.

## 2026-11-15 — rules screen on a phone

Clicked through Report cards as a school admin and a head teacher at 375px and 1280px. The report type, its history line, a card with Missing and ABS, and a reprint after editing the ranges all held. The reprint text did not change. On a phone the rules tables were wider than the screen and the page hid the overflow. #1343 now stacks Division, From aggregate, To aggregate, and the grade bands under their own labels. Nothing else on that screen changed.

## 2026-11-15 — Progressive (new look)

Report Cards, under **Report Card Template**, gains **Progressive (new look)**. **Formal** stays selected until a school admin or a head teacher chooses the new look and presses **Save**. **Open full preview** is the final card. **Open sample** adds the tag SAMPLE FOR APPROVAL and a grading key built from that school's bands and division ranges.

The new card is one table. Each exam set is a Marks and Grade group, in exam-date order. It shows the total, the aggregate, the division and the position for each set, then the comment boxes and the three signature lines. A blank prints Missing and an absence prints ABS. The old Formal and Warm cards are unchanged, including their MONTHLY RESULTS and END OF TERM headings. Draft pull request: https://github.com/KlassApp-Foundation/KlassApp/pull/1353

## 2026-11-15 — a pilot release tells only the people who were chosen

On **Marks Release**, a school admin or a class teacher can choose **Only the people I choose**. The preview names each person and the message. **Confirm and release** messages only that list. The rest of the class is not told. A parent who is not linked to the learner, or who has no number, is skipped and written on the release log. **Everyone in this class** is unchanged. A head teacher still cannot open Marks Release.

Nothing was sent on WhatsApp from this work. The local click left the send switched off, so the log says the messages are switched off. Draft pull request: https://github.com/KlassApp-Foundation/KlassApp/pull/1352

## 2026-11-15 — exam-set columns follow the exam date

A column uses the earliest **Scheduled Date & Time** of that set. A set with no date goes last. If two sets share a date, the term that starts earlier comes first, and then the term's id. The earliest exam id is only used when the date and the term are the same, so the order stays put. The order the sets were created is not the order.

On the progressive card the monthly block is still above the end-of-term block. Inside each block the columns follow the date. The combined download is one list, so a counting set dated before another set comes first there too.

## 2026-11-15 — ABS, and the school's set names on the download

An absence is shown as ABS on Enter Marks, the class mark sheet, Report cards, the release preview, the parent marks list, and both spreadsheets. The stored grade stays AB. Typing AB or ABS both save an absence.

The combined marksheet no longer has sheets named MONTHLY RESULTS or END OF TERM. Each exam set is its own sheet, titled with the name the school stored. A set that was split by month keeps that month in the title. A set that counts toward the term total keeps the division column. The report card itself still uses the headings MONTHLY RESULTS and END OF TERM EXAMINATION.

## 2026-11-15 — one division table

The progressive card, the class mark sheet, the class marks page, the student result page, the combined mark sheet, its spreadsheet, the parent and learner marks page, and `student-report.blade.php` read one division table. A released term uses the copy stored on that release. Ranking and the blank-mark rule on those other screens are unchanged.

## 2026-11-15 — report card defects

Draft pull request: https://github.com/KlassApp-Foundation/KlassApp/pull/1359 on branch `feat/report-card-defects`, stacked on #1358. Not merged. Not deployed.

A learner who is absent or missing in a set that counts toward the term total still scores 0. The card and the report list say Incomplete and name that set. A set that does not count is not named.

A reprint after a rules edit keeps the position sentence from the release. Another school's class and a missing class both answer 404 on the card preview. A results sheet with no policy of its own counts a blank the same way the card does. The whole-class PDF includes a learner who has no mark in the pinned subject. The card prints when no set counts toward the term total, and says that no combined mark applies. The exam edit page answers 404 when the exam is not in the signed-in school. The three card templates print the head teacher's name when the school has saved one.

A set that has a weight and has not been released is named. The combined mark is not shown. Each set's own columns stay. The weighted mean still applies once every weighted set has been released.

Clicked Report cards as the school admin at 375 and at 1280. Closing paper shows Incomplete under the learner, and the downloaded card says the same. The class teacher page is covered by the test. Nothing was sent. Nothing was written to production.

## 2026-11-16 — primary default grade table

A new primary standard is seeded with 2 points from 90 to 94 and 3 points from 75 to 89. The other seven bands are unchanged. The printed aggregate label is still D1 through F9 from the points. The grading screen preset stays "App default". Opening that screen does not rewrite bands a school already stored. Reset puts the new table back, and the school can still edit every band. No school has confirmed its own scale.

Still to do, in order: the report builder, the comment bank, the calendar import, the read-only production check, the full rehearsal, then the named merge order. Sections 14 and 15 are not in the re-delivered handoff.
