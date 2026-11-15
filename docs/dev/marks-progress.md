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
| next | How a new school prints with the defaults. No calculation change. |

## 2026-11-15 — a new school and the defaults

No screen and no calculation changed. A new school starts on Progressive report, and the card template is Formal.

On Formal and Warm, a set whose exam type does not count toward the term total prints under MONTHLY RESULTS. A set whose type does count prints under END OF TERM. The shared catalog leaves Beginning Of Term, Weekly Exams, Mid Term, Monthly Exams, Mock Exam and Pre Mock Exam out of the term total. End Of Term and End Of Year count, so they print in the end-of-term block and feed the term total. A column appears once the school has an exam of that type for the class and the term, and at least one learner has a mark or an absence in it. The column title is the name stored on the exam type. A non-counting type with sittings in two different months becomes two columns, and the combined download adds the month to that sheet's title.

Those three toggles already match a school that wants Beginning Of Term and Mid Term out of the term total and End Of Term in it. They live on Exam Type Settings, in the column Contributes to Report Total. The button is Save Preferences. While the report type stays Progressive report, the columns Show on Report Cards and Weight % stay hidden, and Save Preferences leaves any stored weights in place.

When two or more counting types already have a weight, the Progressive term total is their weighted mean. Weights of 30 and 70 on marks of 60 and 80 give 74. Formal and Warm show that figure as the end-of-term total when more than one counting set is on the card. Progressive (new look) shows the same figure on the line that starts "Term total", in that same case. Each set's own columns keep that set's marks.

A new primary standard is seeded from the product bands. The second band is 85 to 94 and 2 points, and the card shows it as D2. The handoff sheet for a school such as Kabale uses 90 to 94 for D2. The school confirms that on Progressive report rules. This stack does not write those cutoffs in. Division stays 1 for an aggregate of 12 or less, 2 for 13 to 24, 3 for 25 to 28, 4 for 29 to 32, and U from 33. A primary standard ranks by aggregate, lowest first, until someone saves another choice.

A school admin who is happy with this does nothing. To move a type into or out of the term total, they open Exam Type Settings, change Contributes to Report Total, and press Save Preferences. Grade bands and division ranges are edited from the Progressive report rules link on Report cards. A head teacher opens the same Report cards screen and sees the card those defaults produce. A released term keeps the copy stored on that release.

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
