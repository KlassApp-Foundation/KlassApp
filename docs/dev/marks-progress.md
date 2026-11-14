# Marks and report cards — progress

Draft work. Nothing here is merged or deployed.

## Open stack

| PR | What it does |
|---|---|
| #1342 | Per-school report type: Progressive report or Set-based reports. |
| #1343 | Progressive report rules: bands, division ranges, ranking. |
| #1344 | A blank or an absence counts as 0 on the next progressive card. |
| #1345 | Columns use the school's own exam-set names. |
| #1346 | Parity test for points, division and position. |
| #1347 | A release stores the bands, division ranges and ranking. A reprint uses that copy. https://github.com/KlassApp-Foundation/KlassApp/pull/1347 |
| #1348 | One division table on the card, the mark sheets, the download, and the parent and learner marks page. https://github.com/KlassApp-Foundation/KlassApp/pull/1348 |

## 2026-11-15 — rules stored on the release

A release copies the grade bands, the division ranges and the ranking method. A reprint of that term uses the copy. Editing **Progressive report rules** changes the next release.

A release from before this column has no copy and the row is not rewritten. Its reprint keeps the default division table and automatic ranking, so a later edit to those settings does not move it. Its grade bands still come from the school, because no copy was stored.

## 2026-11-15 — one division table

The progressive card, the class mark sheet, the class marks page, the student result page, the combined mark sheet, its spreadsheet, the parent and learner marks page, and `student-report.blade.php` read one division table. A released term uses the copy stored on that release. Ranking and the blank-mark rule on those other screens are unchanged.
