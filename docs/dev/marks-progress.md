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

## 2026-11-15 — rules stored on the release

A release copies the grade bands, the division ranges and the ranking method. A reprint of that term uses the copy. Editing **Progressive report rules** changes the next release.

A release from before this column has no copy and the row is not rewritten. Its reprint keeps the default division table and automatic ranking, so a later edit to those settings does not move it. Its grade bands still come from the school, because no copy was stored.

## 2026-11-15 — rules screen on a phone

Clicked through Report cards as a school admin and a head teacher at 375px and 1280px. The report type, its history line, a card with Missing and ABS, and a reprint after editing the ranges all held. The reprint text did not change. On a phone the rules tables were wider than the screen and the page hid the overflow. #1343 now stacks Division, From aggregate, To aggregate, and the grade bands under their own labels. Nothing else on that screen changed.

## 2026-11-15 — one division table

The progressive card, the class mark sheet, the class marks page, the student result page, the combined mark sheet, its spreadsheet, the parent and learner marks page, and `student-report.blade.php` read one division table. A released term uses the copy stored on that release. Ranking and the blank-mark rule on those other screens are unchanged.
