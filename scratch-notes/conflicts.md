# Scratch integration conflicts

Branch: `scratch/report-stack-2026-11-16`, cut from `origin/main` at `2aaf327bb3d0852d52f28442f4265e8b97f02e1e`.

Every listed pull request was open and a draft. None were closed, merged, or missing, so none were skipped. No merge was a no-op: each head added commits that were not already on the scratch branch. `#1354` and `#1355` are siblings of `#1353` (neither contains the other). Git merged `#1355` onto the branch that already had `#1354` without a conflict.

Resolutions below are scratch-only. The owning author should apply the described change on their pull request. Do not copy this branch onto a pull-request branch.

## 1. `#1323` and `#1352` — `app/Services/MarksRelease/MarksReleaseService.php`

`#1323` (`fix/demo-release-webhook-guard`) replaced the inline demo webhook with `queueDemoWebhook` / `sendDemoWebhook` / `postWebhook`. The body is counts only, and production always refuses. `#1352` (`feat/report-pilot-release`) added `deliverChosen` and still called the old `recordDemoWebhook`, which posts learner codes.

Scratch resolution: keep `deliverChosen`, and make its third return value the same counts array the class-wide `deliver` method returns (`learners`, `classes`). Do not restore `recordDemoWebhook`. `run()` keeps `queueDemoWebhook`.

Patch for `#1352` (apply on top of `#1323`, or rebase `#1352` onto a tree that already has the guarded job):

- File: `app/Services/MarksRelease/MarksReleaseService.php`
- In `deliverChosen`, replace the `$webhookStudents` return with:

```php
$webhookCounts = ((bool) config('services.whatsapp.replies_enabled') && $isDemo) ? [
    'learners' => count($released),
    'classes' => count(array_unique(array_column($released, 'class'))),
] : null;
```

- Pass `$webhookCounts` as the third array element.
- Delete `recordDemoWebhook`. Call `MarksReleaseService::queueDemoWebhook($release->id, $webhookCounts)` from `DB::afterCommit`, the same way `run()` already does for a class-wide release.

`#1323` should keep the job, the environment gate, and the counts-only body. It should not grow a student list.

## 2. `#1330` against the report stack `#1342`–`#1361`

`#1330` (`feat/report-per-exam-set`) is an older parallel of “one combined report from the school’s own exam sets”. The later stack already stores report kind, scoring, progressive rules, set headings, missing-as-zero, incomplete sets, and withheld weighted marks. Taking `#1330`’s copies of those files would put the card back on Mid Term / End of Term headings.

Scratch resolution, file by file:

| File | Kept | Dropped from `#1330` | Why |
| --- | --- | --- | --- |
| `ExamTypeController.php`, `exam-types.blade.php` | `#1342` report-kind save | mix / preset form | The later page is the one the click-through uses. |
| `ResultsService.php` | blank cells, `countMissingAsZero`, withheld combined marks | older weighted-mean comments | Later scoring is the 16 Nov rule. |
| `StudentReportCardService.php` | set columns, incomplete names, withheld sentence | `ReportComposition` as the card’s source of columns | Same. |
| `formal.blade.php`, `warm.blade.php` | set columns and the Incomplete line | “END OF TERM” / monthly block | Acceptance check 4. |
| `MarksReleaseService.php` | counts webhook from `#1323` | `ReportComposition::freezeOn` inside `run()` | Rules already freeze through `ReportKindSetting`, `ReportScoring`, and `ProgressiveRulesStamp`. |
| `ResultCell.php` | `WITHHELD` plus `sheetLabel()` / `printedMark()` | — | Both are needed. `WITHHELD` prints “Withheld”. |
| `MarksRelease.php` | later fillable columns plus `report_composition` | — | The new migration adds the column. |
| `student.blade.php` | later table, cell text from `sheetLabel()` | em dash for a blank | A blank cell reads “Missing”. |

Kept from `#1330` without rewriting the card: `ReportComposition.php`, the three migrations (`report_composition` on releases, `exam_types.short_name`, `schools.exam_type_labels`), `short_name` / `exam_type_labels` on the models, the head-teacher exception on exam-type routes inside `MustBeFullSchoolAdmin` (later widened by `#1325`), and the “missing marks count as 0” sentence on the combined sheet.

Patch for the `#1330` author: rebase onto the report stack and delete the hunks that rewrite `StudentReportCardService`, `ResultsService`, and the formal/warm templates. Keep `ReportComposition` only if a screen still calls it, and do not let it replace progressive set columns. Add `sheetLabel()` next to `ResultCell::WITHHELD` rather than instead of it. Add `report_composition` to `$fillable` beside `progressive_rules`, do not replace those keys.

## 3. `#1335` phone mark entry

Small overlaps with `#1350` (ABS wording) and `#1330` (CSS).

| File | Resolution |
| --- | --- |
| `Teacher/MarksController.php` | Load `updated_at` when the column exists (`$markColumns` from `#1335`) and keep the ABS map from `#1350`. |
| `ExamMarkWriter.php` | Same `AB` / `ABS` check. Kept the `$token` name. |
| `enter.blade.php` | Kept the sentence “Type ABS when a student was absent. Marks are out of 100.” The phone cards around it stayed. |
| `view.blade.php` | Did not call `displayMark()`. That method is not on `Marks` even on `#1335`. The absent branch already prints ABS. The number branch prints `ceil($mark->marks)`. |
| `MarksFollowUpTest.php` | Kept the assertion that the ABS sentence is present and `value="AB"` is absent. |
| `public/css/ka-screen.css` | Kept the composition styles and appended the phone entry styles. |

Patch for `#1335`: stop calling `Marks::displayMark()`. Print ABS in the absent branch and the numeric mark in the other branch. Rebase the entry query so it still selects `updated_at` and still maps an absence to the text `ABS`. Keep the ABS hint paragraph; `#1350`’s test requires it.

## 4. `#1336` dashboard CSS

`public/css/ka-screen.css` only. Kept the composition and entry rules, then appended `.ka-marks-groups` / `.ka-marks-desk`. No PHP conflict.

Patch: append the dashboard rules; do not replace the earlier `.ka-compose` block.

## 5. `#1337` marks overview

| File | Resolution |
| --- | --- |
| `ResultsService.php` | Kept the later stack (`withholdCombined`, `countMissingAsZero`, `mergedCountingBlanks`). Did not take `countApprovedSubjects` / `sheetDisplayCell`, which would hide a draft mark and change the total. |
| `combined-sheet.blade.php` | Took the overview tabs, history link, and release link. The missing-subject banner still says each missing mark counts as 0. |
| `results-table2.blade.php` | Took the phone learner card and `sheet-cell`. Added Aggregate, Division, Pos, and the report link back onto the grid, and Division / Aggregate / position onto the phone card. |
| `sheet-cell.blade.php` | Shows ABS, Withheld, the number, or Missing. A missing status is “Missing” even when the set is not marked released. |
| CSS | Appended the learner-card rules after the existing block. |

Patch for `#1337`: rebase onto `#1359`. Do not replace `buildStudents`’s withheld / blank-as-zero path with `sheetDisplayCell`. Keep the tab UI. When you render a cell, branch on `ResultCell::ABSENT`, `WITHHELD`, `MARKED`, and `MISSING` before the “not released” dash. Put Division back on the grid; `#1348` is the one division table.

## 6. `#1338` approvals and release phone cards

| File | Resolution |
| --- | --- |
| `Teacher/MarksController.php` | Kept `learnersMissingMarkOrAbs` and `markEntryKind` from the phone entry work, and added `returnedNoteForTeacher` from `#1338`. |
| `marks-releases/create.blade.php` | One confirm button, not two. It carries `data-exam-name`, `data-class-count`, `data-release-to`, and the pilot label “Preview who will be told”. The scheduled-for error uses `ka-alert`. A cell whose mark is `AB` or `ABS` prints ABS. The when-toggle script uses the exam-name sentence unless a pilot is chosen. |
| CSS | Appended the reopen-bar rules. |

Patch for `#1338`: do not delete `learnersMissingMarkOrAbs`. Add `returnedNoteForTeacher` beside it. On the release form, keep a single submit button and, when `pilot=chosen`, leave the label as “Preview who will be told” (`#1352`).

## 7. `#1339` phone report list

Same class of overlap as `#1330`: the phone branch rewrote the progressive card. Scratch kept the later card engine and folded in the phone-only extras.

| File | Resolution |
| --- | --- |
| `StudentReportCardService.php`, formal/warm templates, `ResultsService.php`, `CombinedMarksheetExport.php` | Later stack. Export sheet names stay the school’s set names, not “Mid Term” / “End Of Term”. |
| `ClassReportScreen.php` | Kept KLS lookup from `#1339` and `incomplete` from `#1359`. Marks go through `displayCell`, which returns ABS, Missing, or the number. |
| `cards.blade.php` | Row shows KLS, the Incomplete line, and on the phone the per-subject marks. |
| `ParentPortalService.php` | Absent and a null mark both score 0. `display` / `shown` are `ABS` or `Missing`. A numeric mark keeps its score. |
| `family-marks.blade.php` | Division line stayed. Desktop table and phone rows use `$shown`. |
| `student-report.blade.php` | Did not call `displayMark()`. Kept the ABS / number / dash branches. |
| `ReportCardsController.php` | Kept the named `$termId` (same call). |
| Tests | Kept “do not show AB”, “do not show No EOT exam found”, and the set-named spreadsheet assertions. |
| CSS | Appended `.ka-mark-row`, `.ka-hit`, `.ka-own`. Dropped a second `.ka-compose { margin:0; font-size:.9rem }` because it would override the composition screen’s flex layout. |

Patch for `#1339`: rebase onto `#1359`. Add KLS and the phone subject rows to `cards.blade.php` without removing the Incomplete line. Point parent marks at `display` while still storing `shown` and a zero score for ABS and Missing. Do not reintroduce `displayMark()`.

## 8. `#1340` exam not found

`#1359` and `#1340` both 404 another school’s exam and a missing id.

| File | Resolution |
| --- | --- |
| `ExamController.php` | Kept `examInSchool()` (`firstOrFail` on `school_id` + id). Comment from `#1340` stays on `edit`: do not render the form first. |
| `ReportCardsController.php` | One 404 when the signed-in user has no school or either id belongs to another school. Did not add the extra `try` that sends the user back with a different message. |
| `ExamCrossSchoolTest.php` | Kept the two GET assertions (missing id and the other school’s id). Dropped `$response->assertNotFound()` because `$response` is not defined on that branch. |

Patch for `#1340`: assert both GETs in the existing update test, or give `$response` a real request. Do not open a second `try` in `singleStudentResponse`.

## 9. `#1324` class-teacher approval

`ExamMarksSubmissionController::index` had two versions.

Scratch resolution: call `awaitingReview($schoolId, $sections)` and `pendingCount($schoolId, $sections)` so a class teacher only sees their sections (`#1324`). Keep the class/exam filters, mark stats, release locks, and reopen flag from `#1338`. A teacher (usergroup 5) gets `teacher.marks.approvals`; everyone else gets `admin.marks.submissions`. Both views receive the full compact list. `reviewSections` returns null for usergroups 1, 2, 3, and 4 (head teacher included here so `#1325` is not locked out of the queue) and section ids for usergroup 5.

Patch for `#1324`: accept the optional section list (already on `MarksSubmissionService` after this merge) and still pass `classOptions`, `examOptions`, `markStats`, `releaseLocks`, and `canReopen` into the teacher view. Treat usergroup 4 like a school admin in `reviewSections`, matching `#1325`.

## 10. `#1325` head teacher

| File | Resolution |
| --- | --- |
| `MustBeFullSchoolAdmin.php` | Allow usergroups 1, 2, and 3, and also `SchoolAdmin::is` (1, 3, 4). A head teacher can open settings, not only the exam-type routes from `#1330`. Usergroup 2 stays allowed. |
| `MarksSubmissionService.php` | Import both `ExamAuthorization` and `SchoolAdmin`. The class uses both. |

Patch for `#1325`: in the middleware, allow usergroup 2 as well as `SchoolAdmin::is`. Do not delete the `ExamAuthorization` import from `MarksSubmissionService`; class-teacher checks still call it.

## What did not conflict

`#1342` through `#1351`, `#1353` through `#1358`, `#1354`, `#1356`, `#1357`, `#1358`, `#1359`, and `#1361` merged with Git’s ordinary auto-merge. `#1355` onto a branch that already contained its sibling `#1354` was clean.
