# Marks workflow audit

Status: audit of `main` at `a6cf68e7` (25 October 2026). Docs only. Nothing here is a build.

Read with [`marks-flow.md`](marks-flow.md), which is the proposed flow and is awaiting the product owner's approval.

Counts below are rows in the tables. One row is one screen, route, service, job, or calculation site.

| Status | Rows | Meaning on this tree |
|---|---|---|
| working | 34 | Does the job its route claims, for the role that can open it. |
| partial | 21 | Runs, but the rule is split, incomplete, or limited to a side door. |
| broken | 8 | Wrong result, a crash on the happy path, or dead code the UI still depends on. |
| duplicate | 4 | A second implementation of a job that already has an owner. |

## What the older notes got right, and what has changed

Checked against this tree, not copied forward.

[`marks-flow.md`](marks-flow.md) as merged in [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) (base `073e232`) said parents see marks before release on the portal, the student portal, WhatsApp, Toshi, and an automatic "Results Published" send. Those leaks are closed on the live paths. `ReleaseGate` filters the parent portal, the student marks page, alumni marks, WhatsApp GRADES / MARKS / REPORT, and the Toshi parent, student, and alumni tools. `notifyComprehensiveGrades` is gone. Teacher save does not message parents (`ExamMarkWriter.php:16`). The only ungated marks read still in the tree is `Dashboard::studentDashboard`, and it does not run: `Gegok12\Exam\Models\Mark` is not in this repository (`app/Traits/Dashboard.php:332`).

The same note said grades, totals, positions, and divisions are computed in many places, and that report cards treat one exam row as the whole end-of-term exam. `ResultsService` now owns the read path for the class marks page, the per-student page, the combined workbook, the release sheet, the dashboard class chart, and the end-of-term block of the report card. The admin marks grid, the PDF marksheet, promotion points, the report-card mid-term block, and the end-of-term KPI SQL still compute their own numbers.

[`docs/dev/marks-flow-open-prs.md`](../dev/marks-flow-open-prs.md) describes [#1125](https://github.com/KlassApp-Foundation/KlassApp/pull/1125), [#1134](https://github.com/KlassApp-Foundation/KlassApp/pull/1134), [#1135](https://github.com/KlassApp-Foundation/KlassApp/pull/1135), [#1127](https://github.com/KlassApp-Foundation/KlassApp/pull/1127), [#1130](https://github.com/KlassApp-Foundation/KlassApp/pull/1130), [#1142](https://github.com/KlassApp-Foundation/KlassApp/pull/1142), [#1144](https://github.com/KlassApp-Foundation/KlassApp/pull/1144), [#1145](https://github.com/KlassApp-Foundation/KlassApp/pull/1145), [#1150](https://github.com/KlassApp-Foundation/KlassApp/pull/1150), and [#1151](https://github.com/KlassApp-Foundation/KlassApp/pull/1151). Those pull requests are merged. The behaviours they name are in this tree: submission rows, approval before release, `ResultsService`, report-card end-of-term numbers from that service, `ExamMarkWriter`, and school exam-type preferences on the results sheet. The page is a stack note. It does not describe the leftover grid, the PDF download, or class-teacher assignment.

[`docs/proposals/2026-10-05-marks-import-ui.md`](../proposals/2026-10-05-marks-import-ui.md) and [`docs/proposals/2026-10-05-marks-import-ui-m2-m5.md`](../proposals/2026-10-05-marks-import-ui-m2-m5.md) describe a file import that is now built (`MarksImportService`). Two claims in the first note are stale: bulk save now goes through `ExamMarkWriter`, and an import does not move an exam to `submitted`. The entry form is still blank ([`docs/proposals/2026-10-06-marks-entry-prefill.md`](../proposals/2026-10-06-marks-entry-prefill.md), `MarksController.php:145`). [`docs/proposals/2026-10-05-marks-collection-notes.md`](../proposals/2026-10-05-marks-collection-notes.md) still matches the storage shape (one exam row per subject) and the nursery ratings branch.

Google Sheets export and import is not in `app/`. The only spreadsheet path is the uploaded file. Draft [#1138](https://github.com/KlassApp-Foundation/KlassApp/pull/1138) is open and unmerged.

There is no Livewire marks-entry component. Marks screens are Blade. The Livewire surfaces that touch this workflow are the onboarding wizard (class-teacher upload) and the class roster (read-only).

## Parents and students, before release

On this commit, a parent, student, or alumni does not receive marks, grades, positions, or a report-card PDF until a `marks_releases` row covers that sitting. Staff screens are open to staff while marks are still in review. That is the intended staff view, and it is not a parent channel.

| Path | Who | Before release | Evidence |
|---|---|---|---|
| `parent.children.grades` | Parent | Hidden. Empty copy when nothing is released. | `ParentPortalService.php:366` applies `ReleaseGate` before `take(15)`. Controller is a pass-through at `ChildDataController.php:19`. |
| Parent dashboard grades panel | Parent | Same method as the grades page. | `ParentPortalService::grades()` |
| `student.marks` | Student | Hidden. | `RecordsController.php:45` |
| `alumni.marks` and alumni dashboard snippet | Alumni | Hidden. | `AlumniController.php:109` `applyToMarks` |
| `alumni.report-card.download` | Alumni | PDF is the released mark list, not the school report-card template. | `AlumniController.php:96` |
| WhatsApp GRADES / MARKS / EXAMS for a parent | Parent on a linked number | Hidden. | `WhatsAppController.php:2076` |
| WhatsApp GRADES / MARKS for a student | Student | Hidden. | `WhatsAppController.php:2376` |
| WhatsApp REPORT | Parent | PDF only after `resolveReleasedExam`. | `WhatsAppReportCardDeliveryService.php:102` |
| Toshi parent grades | Parent | Uses the portal method. | `ParentActionService.php:120` |
| Toshi student marks and dashboard count | Student | Hidden. | `StudentActionService.php:141` and `:102` |
| Toshi alumni records | Alumni | Hidden. | `AlumniActionService` calls `applyToMarks` |
| Demo follow-along MARKS / RESULTS | Demo number | Scores only for `latestReleasedExam`. | `DemoBindReplies` via `LatestExamMarks` |
| Proactive WhatsApp on release | Demo followers | Sent only after a release row exists, and only when the school is a demo. | `MarksReleaseService.php:609` |
| `Dashboard::studentDashboard` | Student, only if `Gegok12\Exam\Models\Mark` exists | The block has no release check. That class is not in this repository, so the block does not run. | `app/Traits/Dashboard.php:332` |

Staff report-card preview and download (`admin.reports.cards.student.preview`, teacher equivalent) do not call `ReleaseGate`. They are staff tools. A parent copy goes out through the WhatsApp report path above, which does call it.

Commented API routes `GET /api/marks/{student_id}/{exam_id}` and the graph route are not registered (`routes/api.php:208`). Live API exam routes return upcoming and past exams, not mark rows (`routes/api.php:202`).

## Entry and upload

School-admin routes sit behind `schooladmin` (site admin, school admin, subadmin). Teacher routes sit behind `teacher`. A class teacher may enter marks for a class they hold; `ExamAuthorization` allows the exam's subject teacher or that class teacher.

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| E1 | `GET /teacher/exam/marks` `teacher.exam.marks` | Lists exams this teacher can mark. | Subject teacher, class teacher | working | Scoped list. `MarksController.php:74` |
| E2 | `GET /teacher/exam` and `GET /teacher/exams` | Redirect to the marks list. | Teacher | working | `routes/teacher.php:417` and `:422` |
| E3 | `GET /teacher/exam/{exam}/marks/enter` | One numeric input per student, or nursery domain ratings. The form stays blank. | Subject teacher, class teacher | working | Saved-count note only. `MarksController.php:145` |
| E4 | `POST …/marks/save` numeric branch | Writes one mark per filled student. | Subject teacher, class teacher | working | `ExamMarkWriter::save`. `MarksController.php:299`. Parents are not notified. |
| E5 | `POST …/marks/save` nursery branch | Writes domain ratings. | Subject teacher, class teacher | partial | The loop, the rating whitelist, and the write sit in the controller. `MarksController.php:261` |
| E6 | `GET …/marks/view` | Read-only table of saved marks, with an edit link. | Subject teacher, class teacher | partial | The banner reads `exams.status`, which submit no longer sets. `view.blade.php:11`. Pass/fail colours are hard-coded at 70 and 50 in the view. `view.blade.php:100` |
| E7 | `GET …/marks/template` | Spreadsheet template keyed by registration number. | Subject teacher, class teacher | working | `MarksImportService` rows. `MarksController.php:213` |
| E8 | `GET/POST …/marks/import*` | Preview, then confirm a file. | Subject teacher, class teacher | working | Shared `HandlesMarksImport` trait. Confirm writes in `MarksImportService.php:665`, not through `ExamMarkWriter` (see D1). |
| E9 | `GET/POST /admin/exams/{exam}/marks/import*` | Same import for any exam in the school, plus an admin template. | School admin | working | `routes/admin.php:895`. Same service as E8. |
| E10 | `PATCH /teacher/exam/{exam}/students/{student}/mark` | Correct one saved mark. | Subject teacher, class teacher | working | `ExamMarkWriter`. Lock and correction reason live in the writer. `ExamMarkWriter.php:38` |
| E11 | Toshi `Teacher\EnterMarksTool` | Chat entry for one mark. | Subject teacher | partial | Refuses a class teacher. `TeacherActionService.php:100`. The web form allows the class teacher. |
| E12 | Toshi `EnterMarkTool` | Admin chat entry for one mark. | School admin | working | `ToshiActionService.php:1505` calls `ExamMarkWriter`. |
| E13 | Google Sheets import and export | Not a screen on this tree. | — | partial | No Sheets client under `app/`. File import is the spreadsheet path. Draft [#1138](https://github.com/KlassApp-Foundation/KlassApp/pull/1138) is open. |
| E14 | `Teacher\MarksController::enter` | Old entry action. | Nobody. Unrouted. | broken | Returns a view that is not in the tree, and the API marks routes are commented out. `MarksController.php:47` |
| E15 | `GET/POST /teacher/exam/create` and edit | Class teacher creates the per-subject exam row that entry hangs on. | Class teacher | working | `Teacher\ExamController`. `ExamAuthorization` on write. |

## Submit, approve, return

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| S1 | `POST /teacher/exam/{exam}/submit` | Writes one `exam_marks_submissions` row (`submitted`, `pending`). | Subject teacher, class teacher | working | Does not change `exams.status` and does not message parents. `MarksSubmissionService.php:11` |
| S2 | `GET /admin/marks/submissions` | Pending queue. Menu badge uses `pendingCount`. | School admin | working | `ExamMarksSubmissionController.php:19`. Nav child at `config/navigation.php:155` |
| S3 | `POST …/lock`, `reopen`, `approve`, `reject` | Lock, reopen, approve, or reject a submission. Reject and reopen are the return path. | School admin | partial | The status changes are Eloquent writes in the controller. `MarksSubmissionService` only implements `submit` and `pendingCount`. `ExamMarksSubmissionController.php:46` |
| S4 | `admin/marks/submission-detail.blade.php` | Approve-all control on the detail page. | School admin | partial | `$allLocked` and `$hasPending` are decided in the view. `submission-detail.blade.php:20` |
| S5 | `Exam::changeExamStatus` | Used to walk `undone` → `done` → `submitted`. | Nobody calls it. | broken | The method remains at `Exam.php:150` and has no callers. The admin grid still requires `exams.status = submitted` (G2). |

## Combined mark sheet and other staff views

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| G1 | `GET /admin/marks` `admin.marks` | Filter shell. | School admin | broken | `FilterMarksForm` does not pass `$filtered` or `$marksSubtitle`. The view reads both. `FilterMarksForm.php:29`, `filter.blade.php:9` |
| G2 | `GET /admin/marks/filter` | Marks grid, totals, average, position, and an end-of-year promotion hook. | School admin | broken | Marks load only when `exams.status` is `submitted` (`MarksController.php:73`). Submit no longer sets that column (S1, S5), so a school that uses Submit sees an empty grid. Totals, average, and position are computed in the controller (`MarksController.php:116`), and a missing grade counts as 0 (`GradingSystemService.php:33`). |
| G3 | `admin/marks/results-table2.blade.php` | Renders the grid. | School admin | duplicate | The view ceils each mark and falls back to summing the relation. `results-table2.blade.php:37` |
| G4 | `GET /admin/marks/classes/{section?}` | Performance by class. | School admin | working | Reads `ResultsService`. `ClassMarksController.php:44` |
| G5 | `GET /admin/marks/student/{user}/class/{class}/{exam}` | Per-student sheet. | School admin | working | `ResultsService::sheet`. `GetStudentsMarks.php:80` |
| G6 | `GET /admin/report/student/{learner}/class/{class}/{exam}` | Old per-student URL. | School admin | duplicate | Redirects to G5. `routes/admin.php:918` |
| G7 | `GET /admin/marks/download/` | PDF of the filtered grid. | School admin | broken | Looks up the year by `description = "Current Academic Year"` (`DownloadMarksSheet.php:20`). The app resolves the year by `academic_years.status = 1`. Numbers come from `MarksReportService`, not `ResultsService`. |
| G8 | `MarksReportService::buildMarksheet` | Totals, average, position for that PDF. | Called by G7 | duplicate | Same sum-and-divide shape as G2. `MarksReportService.php` |
| G9 | `GET /admin/reports/cards/combined-marksheet/{stdLink}` | Workbook of the class sheet. | School admin | working | `CombinedMarksheetExport` reads `ResultsService`. |
| G10 | `GET /teacher/exam/class/{stdLink}/combined-marksheet` | Same workbook for the teacher's class. | Class teacher | working | Same export. `MarksController.php:366` |
| G11 | `GET /admin/exams/{exam}/marksheet` and `GET /teacher/exam/{exam}/marksheet` | One subject, roster plus raw marks. | School admin, or the exam's teacher / class teacher | working | `ExamMarksheetService`. No totals, which is right for a single subject sheet. |
| G12 | `GET /admin/exams` | Exam list, with links into import and the subject sheet. | School admin | working | `Admin\ExamController@index` |

## Release

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| R1 | `GET/POST /admin/marks/releases*` and `/teacher/marks/releases*` | Pick a sitting, preview the sheet, schedule or send. | School admin for any class. Class teacher for `standards_link.class_teacher_id` on the current year. | working | `MarksReleaseService::links` at `MarksReleaseService.php:80`. Sheet rows come from `ResultsService`. |
| R2 | `MarksReleaseService::requireApproval` | Refuses a release while any subject in scope is not approved. | School admin may override with a reason of at least 10 characters. A class teacher cannot. | working | `MarksReleaseService.php:210`. The reason is stored on the activity log. |
| R3 | `MarksReleaseService::deliverOne` | Sends the parent message for one release. | Demo schools only, and only when WhatsApp replies are switched on. | partial | A real school is skipped at `MarksReleaseService.php:609`. The release row still exists, so the gate in the parent section works without a message. |
| R4 | `marks:release-due` | Runs due scheduled releases. | Scheduler, every minute. | working | `app/Console/Kernel.php:86` |
| R5 | `SendMarksReleaseMessage` | Queue job for one parent. | Queue | working | Calls `deliverOne`. Idempotent when the entry is already sent, skipped, or failed. |

## Report cards, grades, positions, divisions

`ResultsService::sheet` (`ResultsService.php:36`) is the read model: subjects, mark, grade from the school scale, missing marks excluded from the total, aggregate, division, class position, and stream position. It does not apply the release gate (`ResultsService.php:25`). Division bands are the hard-coded thresholds 12 / 24 / 28 / 32 (`ThresholdDivisionRule.php:22`). Ranking defaults to the total, higher better, ties shared.

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| C1 | `ResultsService` | The shared sheet. | Staff callers, and parent callers after the gate | working | One public method, `sheet`. School exam-type preferences via `ExamTypeContribution`. |
| C2 | `GradingSystemService::grade` | Letter stored on write. | `ExamMarkWriter`, import | partial | Casts the mark to an integer. `GradingSystemService.php:14`, `ExamMarkWriter.php:96`. The sheet grades the decimal. |
| C3 | `GradingSystemService::aggregates` | Sums points from the stored letter. | Admin grid G2 | partial | A missing letter adds 0. `GradingSystemService.php:33` |
| C4 | `GET/POST /admin/settings/exam-types` | Per-school "counts toward the report" toggles. | Full school admin | partial | `ResultsService` reads `schools.exam_type_preferences`. The report-card anchor still filters the global flag only. `StudentReportCardService.php:161` |
| C5 | Admin and teacher report-card pages | Index, class download, merged PDF, per-student preview and download. | School admin. Class teacher for own streams. | partial | End-of-term total, aggregate, division, and positions come from `ResultsService`. The mid-term block still sums and ranks in the service. `StudentReportCardService.php:345`. `generatePdf` on the admin controller delegates (`ReportCardsController.php:336`). |
| C6 | `GenerateClassReportsJob` | Batch PDFs for a class. | Queue, from the admin download actions | partial | Calls the same `generatePdf`, so it keeps the mid-term split. |
| C7 | WhatsApp REPORT PDF | Parent copy of the report card. | Parent | working | `resolveReleasedExam` then `pdfForStudent`. `WhatsAppReportCardDeliveryService.php:102` |
| C8 | `GET /alumni/report-card/download` | Alumni PDF. | Alumni | partial | Released mark rows, not `StudentReportCardService`. `AlumniController.php:96` |
| C9 | `GenerateP2Reports` | One-off P.2 PDFs. | Artisan, hard-coded school | broken | Fixed school, exam, and section. Not the shared pipeline. `app/Console/Commands/GenerateP2Reports.php` |
| C10 | `ReportKpiSnapshotService::computeLive` and `report-kpis:rebuild` | Dashboard and report-card KPI totals. | Scheduler | partial | SQL sums every contributing mark row. `ReportKpiSnapshotService.php:210`. It does not use the sheet's one-row-per-subject rule. |
| C11 | Dashboard "Performance by class" | One bar per class from the latest end-of-term sitting. | School admin | working | `ResultsService`. `DashboardV2DataService.php:369` |
| C12 | Dashboard "Report cards ready" | Count of students with any mark on the latest exam that has marks. | School admin | partial | Any exam type, not the released end-of-term sitting. `DashboardV2DataService.php:254` |
| C13 | `ThresholdDivisionRule` | Division 1–4 and U from point thresholds. | `ResultsService` | partial | Hard-coded. `ThresholdDivisionRule.php:22`. A grading package can replace the binding. The thresholds are not school settings. |
| C14 | `POST /admin/promotion` `admin.students.promote` | Moves the class after the end-of-year grid. | School admin | working | POST only. `routes/admin.php:916` |
| C15 | `StudentPromotionService::promoteStudents` | Decides who may move, from the grid's mark collection. | Called by G2 when the exam type name is `End Of Year` | partial | Points come from stored letters. Average is the sum of marks divided by the exam count. `StudentPromotionService.php:46` |
| C16 | `GET /admin/promotion/list` and `/promotion/create` | Legacy promotion list and form. | School admin | working | Display and file import/export. Finalize of a class is C14, not a GET. |

## Jobs, commands, notifications

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| J1 | `marks:lock-expired` | Locks submissions past their deadline. | Scheduler, every 15 minutes. `Kernel.php:159` | working | Does not grade or release. |
| J2 | `MarksUpdated` → `NotifyAdminMarksUpdated` → `MarksCorrectionNotification` | Tells school admins that a teacher corrected marks. | In-app, and WhatsApp where an admin number is linked | working | Dispatched from the teacher save (`MarksController.php:308`) and from import. Parents are not on this path. |
| J3 | `kabale:import-marks` and `import:kabel-marks` | Load one school's spreadsheets. | Artisan | partial | School-specific commands with their own grade lookup. The UI import is E8. |
| J4 | `BackfillMarksGrades` | Fills empty `marks.grade` from the scale. | Artisan | working | Ops command. The sheet does not trust the stored letter when the scale has a band. |
| J5 | `whatsapp:prune-report-files` | Deletes expired private report PDFs. | Scheduler, hourly. `Kernel.php:163` | working | Housekeeping for the signed report URL. |
| J6 | `GET /api/exams/upcoming/{student_id}` and `past` | Exam dates for a student. | API client | working | No mark payload. `routes/api.php:202` |
| J7 | Commented `MarksController@index` API | Old mark API. | Nobody | broken | Left in `routes/api.php:208` as comments. E14 is the matching dead controller method. |

## Class teacher assignment

The admin dashboard does not let a person assign a class teacher.

`Classes and streams` in the admin nav opens `/admin/classes` (`config/navigation.php:141`). That page is a read-only roster. Each card shows the class teacher name or "Not assigned" and links to the roster. There is no assign control (`resources/views/livewire/class-roster/index.blade.php:4` and `:93`).

Other doors exist. None of them is that dashboard page.

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| T1 | `GET /admin/classes` Livewire class roster | Shows assignment state. | Full school admin | broken | Read-only. The known gap: no way to assign a class teacher from the admin dashboard. `class-roster/index.blade.php:4` |
| T2 | `GET /admin/sections` invite | "Invite Class Teacher" when `sections.class_teacher_id` is null. Choosing an existing teacher whose email matches writes both class-teacher columns. | Full school admin | partial | Hidden once a teacher is set, and it is not the nav target. `sections/list.blade.php:27`. Write at `ClassTeacherInviteService.php:133` |
| T3 | `GET /admin/standardLink/edit/{id}` | Dropdown `class_teacher_id` on the stream form. | Full school admin, if they open the legacy URL | partial | Persists in `AcademicProcess.php:316`. The classes roster does not link here. The Edit control on the sections list is `href="#"` (`sections/list.blade.php:34`). |
| T4 | `GET /admin/class-setup` | Staff setup link, plus approve or reject a teacher's request. | School admin who knows the URL | partial | Routes at `routes/admin.php:27`. Not in `config/navigation.php`. |
| T5 | Onboarding wizard class-teacher upload and per-class invite | Assigns during setup. | School admin, during onboarding | working | `ManualOnboardingWizard.php:3142` writes `standards_link.class_teacher_id` and `sections.class_teacher_id`. |
| T6 | Toshi create-teacher attached to a stream | Assigns during chat setup. | School admin, during onboarding | working | `ToshiActionService.php:534` |

Release (R1) and teacher report cards treat `standards_link.class_teacher_id` as the class teacher. A school that finishes onboarding without that column set, and then looks at the dashboard, cannot fix it there.

## Business logic that still lives outside a service

| Place | What the screen decides | Rows |
|---|---|---|
| `Teacher\MarksController::saveExamMarks` nursery branch | Valid ratings and the `NurseryAssessment` write. | E5 |
| `ExamMarksSubmissionController` lock, reopen, approve, reject | Submission transitions. | S3 |
| `submission-detail.blade.php` | Whether bulk approve is allowed. | S4 |
| `Admin\MarksController::classExamOverview` | Which marks appear, the total, the average, the position, and whether to run promotion. | G2, C15 |
| `results-table2.blade.php` | Display total fallback and `ceil`. | G3 |
| `teacher/marks/view.blade.php` | Submitted banner and colour bands. | E6 |
| `DownloadMarksSheet` | Which academic year, then a second marksheet builder. | G7 |
| `StudentReportCardService` mid-term block | Mid-term total and rank beside the results sheet. | C5 |
| `WhatsAppController::sendGrades` and `sendStudentGrades` | Their own exam queries, then the gate. The parent web path uses `ParentPortalService`. | Parent section |
| `Student\RecordsController::marks` | Same query shape as the portal, copied into the controller. | Parent section |
| `MarksImportService::apply` | A second mark write beside `ExamMarkWriter`. | D1 below |

| ID | Path | What it does | Who | Status | Reason and evidence |
|---|---|---|---|---|---|
| D1 | `MarksImportService::apply` | Creates or updates `marks` rows on confirm. | Teacher import and admin import | duplicate | `Marks::create` at `MarksImportService.php:665`. Lock, correction reason, and the letter are reimplemented beside `ExamMarkWriter`. |
