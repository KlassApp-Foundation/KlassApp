# Marks-flow notes

These notes used to live at the top of `knowledge.md` on the open marks pull requests. Those pull requests no longer edit `knowledge.md`, so merging one does not conflict with the others. Merge this page last.

`#1122` and `#1124` are on `main`. No behaviour change is in this pull request.

## #1125 Dead ungated paths

Unrouted `WhatsAppController::grades` and `::report`, `GradesPublished`, `SendGradesToWhatsApp`, `notifyGradesPublished`, the legacy showmark routes, and `schoolMarksOverview` are removed. Live GRADES and MARKS keywords stay. `notifyComprehensiveGrades` stays removed. The release-gate tests no longer call the deleted methods. No migration.

## #1134 Submission rows

Teacher Submit writes one `exam_marks_submissions` row (`submitted` / `pending`) and does not change `exams.status` or message parents. Saving marks no longer calls `changeExamStatus()`. The old status-toggle URL is gone. The admin queue lists those rows. Exams and reports → Mark submissions shows the pending count. The pinned sidebar fixture includes that child. No migration.

## #1135 Release requires approval

Stacked on #1134. A release is refused until every subject in scope is approved. A school admin may give a reason of at least 10 characters; it is stored on the marks-release activity log. A class teacher cannot override. The release page lists each subject as Approved, Pending, Rejected, or Not submitted. A release of several classes checks every class before it writes any release row. No migration.

## #1127 Results service

`ResultsService` is the read-only sheet for grades, totals, averages, aggregates, divisions, and class and stream positions. A decimal that falls between integer grading bands stays on the lower band. A mark above the top maximum matches nothing. Default rank is the total, higher better. The service does not apply the release gate. No migration.

## #1130 Report cards on the results service

Stacked on #1127. Report-card aggregate, division, class position, stream position, and the mid-term row come from `ResultsService`. A mid-term sitting totals every subject. Stream position uses the same ranking as the class. Ranking stays "points when the school has them, otherwise the mark total". No migration.

## #1142 Combined mark sheet

Stacked on #1127. `ResultsPolicy::$splitMidTermByMonth` defaults off, so a term report still has one sitting per exam type. With the flag on, a non-contributing type is one sitting per calendar month. An undated exam stays its own sitting. Class totals still use contributing types only. The combined workbook opts in. The per-student page reads the same sheet. `/admin/report/student/...` redirects to it. `DownloadStudentReport` is removed. No migration.

## #1144 Release, WhatsApp, and dashboard

Stacked on #1142. The release sheet, WhatsApp mark lines, class marks, and the dashboard performance and report-card tiles read `ResultsService`. The overall grade uses the school scale. A stored subject letter remains only when the scale has no band. No migration.

## #1145 Menus and promotion

Stacked on #1144. Upcoming Exams and both old exam-list URLs open `/teacher/exam/marks`. Approvals keeps one top-level row and gains a Mark submissions child. Promotion is POST only; GET returns 405. This stack does not include #1134's submission-row rewrite. No migration.

## #1150 Shared mark save

Stacked on #1145. Admin and teacher Toshi mark entry, the teacher form's numeric save, and the single-mark update write through `ExamMarkWriter`. The scale supplies the letter, or `-` when the scale has no band. A lock is refused. A submitted exam needs a correction reason. The student must belong to the exam's school. Parents are not notified from this write. No migration.

## #1151 Exam-type settings

Stacked on #1150. `ResultsService` reads `schools.exam_type_preferences`. A saved `"0"` is off. A type with no preference still uses the global flag. The sheet letter stays the scale. Stored `marks.grade` values are not rewritten. The WhatsApp term query uses the same contribution helper. No migration.
