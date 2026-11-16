# Change requests (not applied)

These are inconsistencies between the draft pull requests on `scratch/report-stack-2026-11-16`. Nothing here was edited onto a pull-request branch.

## 1. Two grade vocabularies

The rules screen's app default, once saved, writes numeric grades.

- File: the bands saved from `resources/views/admin/reports/progressive-rules.blade.php` through `ProgressiveReportRulesController` (#1355, #1361).
- Stored rows: min 95 grade `1` points 1, through min 0 grade `9` points 9.
- The results sheet then shows grade `9` for a mark of 26 (`ResultCell` grade on PUPIL 076, Beginning Of Term English).
- The progressive PDF prints `F9` for that same mark (`StudentReportCardService` / `progressive.blade.php`, #1353).

The reference table uses D1, D2, C3, C4, C5, C6, P7, P8, F9 with the same points. The PDF letters match that table. The saved bands and the sheet do not.

Proposed fix, on #1361 (the primary default) and #1355 (the screen that saves it): one band list, the reference letters and points, written by the rules screen and read by the sheet and the card. Delete the numeric `1`–`9` copy.

## 2. The import keeps a provided KLS number in the wrong column

`app/Livewire/Admin/ImportStudents.php` (the screen linked as "Import a list") stores the file's admission number on `student_academics.school_student_id`. `CreateUser` mints `users.registration_number` as `KLS0010001` for school id 1. The marks importer matches the KLS number column to `registration_number`.

Proposed fix, on the students import: if the admission number matches `StudentIdGeneratorService::PATTERN`, store it as `registration_number` and `klassapp_student_id`, which `RegisterUser` already does when `registration_number` is set. The older `UsersImport` path does this. The linked screen should call that same line.

## 3. The marks importer rejects ABS

`app/Services/MarksImport/MarksImportService.php` around the numeric check (the `not_a_number` skip) does not treat ABS as absent. A file row `ABS` is "Mark is not a number" and is skipped. #1326 is the draft that teaches the importer ABS. It was not in this merge list.

Proposed fix: merge #1326, or add the same ABS token the mark-entry screen already accepts (`ExamMarkWriter::isAbsent`) inside `MarksImportService` before the numeric check.

## 4. A blank mark cannot be submitted

`resources/views/teacher/marks/enter.blade.php` line 96 disables Submit unless `blankCount` is 0. PUPIL 076's two missing marks block Beginning Of Term Mathematics and Science. The card is supposed to count a blank as 0 and show Incomplete. The teacher cannot send that sheet for approval.

Proposed fix, on #1335: let Submit proceed when the only gaps are blanks, and let #1359's incomplete marker name those sets. Do not require a typed 0.

## 5. Release waits for every subject in the set, including another class

Mid Term for Primary Five was 4 of 5 approved. The fifth is Primary Six English, created because "Whole school" was left on. The release step will not finish while that subject is unapproved.

Proposed fix, on #1338 / the release gate: count subjects for the class being released, not every class that shares the exam set.

## 6. Approved is stored twice, and the two columns disagree

`MarksSubmissionService::approveSelected` sets `approval_status` and leaves `status` as `submitted` (`app/Services/Marks/MarksSubmissionService.php` around line 182). The list still reads the sheet as submitted.

Proposed fix, on #1324: one column. When a sheet is approved, set the status the list and the release gate both read.

## 7. Template names

`StudentReportCardService::TEMPLATES` (`app/Services/StudentReportCardService.php` lines 47–51) says Formal, Warm, and Progressive (new look). The click brief asked for Standard, new look, and visual.

Proposed fix, on #1353: one set of names on the history page and in `TEMPLATES`. Say which of Formal / Warm / Progressive is Standard, new look, and visual, and use those words on the screen.

## 8. Academic terms card opens the add form

`resources/views/admin/settings/index.blade.php` line 27 links "Academic terms" to `/admin/academic-term/create`. The "Make this the current term" buttons are on `resources/views/admin/school/term/index.blade.php`, which that card does not open.

Proposed fix, on #1356: point the card at the terms list (`admin.academic-term`).

## 9. Set codes BOT, MID, and EOT are still special

`app/Services/Results/ReportComposition.php` `sittingRole` (lines 124–137) returns begin, mid, or end from the codes BOT, MID, and EOT. `app/Reference/ExamTypeCatalog.php` lines 18–22 seeds those names. The card columns themselves used the school's type name (the PDF heading was BEGINNING OF TERM because that is the type's name).

Proposed fix, on #1330: stop branching on those codes. A set counts only when the school says it counts. A column is the type's own name.

## 10. `displayMark()` is called and does not exist

#1335's `resources/views/teacher/marks/view.blade.php` called `Marks::displayMark()`. The method is not on the model. The scratch merge prints the number in the non-absent branch instead.

Proposed fix, on #1335: remove the call. Print ABS or the number the same way `enter.blade.php` does.

## 11. Two writers for "which sets make the report"

#1330 adds `ReportComposition` and `schools.report_composition`. #1342–#1347 add `ReportKindSetting`, `ReportScoring`, and `ProgressiveRulesStamp`, and freeze those on the release. The scratch merge kept the later writer and left `ReportComposition` in the tree.

Proposed fix, on #1330: rebase onto #1347 and delete the composition writer, or make it a reader of the scoring rule. Do not freeze a second JSON blob on `marks_releases`.

## 12. The class teacher's report-card list calls a method that does not exist

`Teacher/ReportCardsController.php` line 57 calls `StudentReportCardService::examSetNames()`. That method is not in the class. The list returns 500 for a class teacher who has a class. Opening one class by its id still works. The same 500 fails `TeacherReportCardsTest` and `WholeClassReportCardPdfTest`.

The call came in with "Use each school's exam set names", merged as #1339. Proposed fix, on #1339: add the method, or stop calling it and read the set names the card already prints.

## 13. A parent cannot download the released card when no exam type contributes

Mid Term was released. The parent's Marks page shows the four Mid Term marks, the average, and "Download latest report card". The download answers "No report card has been released yet."

`ParentPortalService::releasedReportPdf` asks `resolveReleasedExam` for an anchor. That method prefers an exam whose type has `contributes_to_report_total = 1`. Beginning Of Term and Mid Term are 0, and this school has no End Of Term exam, so the anchor is the first exam in the term (Beginning Of Term), which is not released. `studentHasContributingMark` then looks only at contributing exams, finds none, and refuses.

Proposed fix, on the card service (#1344 / #1359): a released sitting the parent can already see is enough to build the card. Do not require a contributing exam type.

## 14. The new-look card hides the division row

Formal and Warm print `DIVISION` for both sets. Progressive (new look) prints the row only when `$showDivision` is true, and that flag is false when the end-of-term division is `-`. With no End Of Term exam the row is omitted for all 76 learners. The aggregate number is the opposite: the new look prints it, and Formal and Warm do not (their AGG column is the grade letter).

Proposed fix, on #1345: print each set's division on the new look, and print the aggregate number on Formal and Warm.

## 15. A refused rules save still stores the ranking

`ProgressiveReportRulesController::update` saves the ranking and deletes stored division ranges before it validates the new ranges and bands. A band edit that overlaps (grade 1 moved to 90 while grade 2 still ends at 94) throws after that. The ranking stays on "total", the ranges are gone, and the bands stay as they were. A later valid save (division 1 ends at 11, division 2 starts at 12, grade 1 starts at 96, grade 2 ends at 95) did store all three.

Proposed fix, on #1355: validate first, then write, in one transaction.

## 16. An approval failure on release becomes a 405

Confirming Beginning Of Term posted to `/admin/marks/releases` and came back as a redirect to `/admin/marks/releases/preview`. That address only accepts POST, so the browser then got 405 and the "these subjects are not approved" message never appeared. Mid Term, which was fully approved, released on the second confirm.

Proposed fix, on #1338: send the validation error back to the release form, which is a GET.
