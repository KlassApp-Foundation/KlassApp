# Class teacher capabilities

Proposal only. One place should decide what a class teacher can do. Today the same question is answered in many files, and they do not all look at the same column.

## The one rule

A class teacher is a user in group 5 (`usergroup_id` 5), in the same school, assigned to a stream or to a class.

- **Stream teacher.** `standards_link.class_teacher_id` is that teacher. They manage and import students in that stream, see that stream’s combined mark sheet, and release that stream’s marks only when the school allows it.
- **Class head.** `sections.class_teacher_id` is that teacher, and the section has more than one stream. They can see the combined sheet across those streams. They do not replace the stream teacher on a stream that already has one.
- **School setting for release.** On `main`, a teacher can release any stream where `standards_link.class_teacher_id` is their id, with no school switch (`app/Services/MarksRelease/MarksReleaseService.php` lines 95–96). Open PR #1244 (not merged) adds `teachers_may_release_marks`, off unless the school sets it. The hub should use that switch. Until it is merged, release stays as `main` has it.

Every check below should call this one rule instead of repeating the column test.

`app/Services/RosterScopeService.php` is the closest thing to that rule today (stream teacher at lines 249 and 329, section teacher when the stream has none at lines 258–260 and 333). `app/Services/ExamAuthorization.php` `isClassTeacherForExam` (lines 33–60) checks both columns and requires group 5 and the same school. New code should go through one service that those two call, not a third copy.

## Large classes

A class of about 1,000 learners is split into streams of 300–500. Each stream has its own class teacher. The class may also have one class head across the streams.

The two columns already exist (`app/Models/StandardLink.php` line 31, `app/Models/Section.php` line 29). The wizard writes both (`app/Livewire/ManualOnboardingWizard.php` lines 3238 and 3240). The rule above is how those columns should be read. A query for “this class” stays on `section_id`. `standard_id` is the grading band, not the class.

## Where the checks are today

Each of these should call the one rule. Line numbers are from `main`.

| What it allows | File | Lines | Move |
| --- | --- | --- | --- |
| Marks for the teacher’s exam, including class-teacher access | `app/Services/ExamAuthorization.php` | 15–16, 33–60, 79, 89 | Call the rule. Keep the subject-teacher check (`exam.teacher_id`) beside it |
| Which streams a teacher may release | `app/Services/MarksRelease/MarksReleaseService.php` | 95–96 | Call the rule. Today this reads only `standards_link.class_teacher_id`. It does not read `sections.class_teacher_id`, and it does not consult a school switch |
| “Is this teacher the class teacher of this stream?” | `app/Helpers/SiteHelper.php` | 186–210, and the attendance scope that filters `class_teacher_id` at lines 325, 338, 570 | `isClassTeacherOfStandardLink` becomes a wrapper around the rule |
| Roster and people lists limited to the teacher’s streams | `app/Services/RosterScopeService.php` | 55, 159–173, 249–260, 329–333 | This is the implementation to keep. Other list queries call it |
| Teacher dashboard counts | `app/Http/Controllers/Teacher/DashboardController.php` | 51, 87 | Call the rule |
| Entering marks | `app/Http/Controllers/Teacher/MarksController.php` | 101, 374–380 | Call the rule. Line 374 already checks both columns. Line 101 checks only the link |
| Report cards for the teacher’s class | `app/Http/Controllers/Teacher/ReportCardsController.php` | 140 | Already calls `SiteHelper::isClassTeacherOfStandardLink`. That helper should call the rule |
| Homework and leave, web and API | `app/Http/Controllers/Teacher/HomeWorkController.php` 43, 48, 82, 121; `StudentLeaveController.php` 43; `NoticeBoardController.php` 28; `app/Http/Controllers/Api/Teacher/HomeworkController.php` 43, 60, 83; `AssignmentController.php` 96; `AttendanceController.php` 54; approval controllers under `app/Http/Controllers/Api/Teacher/Approval/` and `app/Http/Controllers/Teacher/Approval/HomeWorkController.php` 50, 100, 261 | Those lines compare `class_teacher_id` to the signed-in teacher | Call the rule. Same for the policies in `app/Providers/AuthServiceProvider.php` lines 299 and 352 |
| Spotlight / navigation hiding class-teacher items | `app/Spotlight/NavigationCommand.php` | 78–102 | Call the rule once per request |
| Sidebar | `resources/views/layouts/partials/sidebar-menu.blade.php` | 92, 121 | The comment already says the nav must use `isClassTeacherOfStandardLink`. Keep that, pointed at the rule |
| Assigning the teacher | `app/Traits/AcademicProcess.php` | 204, 223–226, 274–316 | This writes the columns. It stays the writer. The rule only reads |
| Inviting a teacher who does not have an account yet | `app/Services/ClassTeacherInviteService.php` lines 123–136 and `app/Services/TeacherInviteLinkService.php` lines 179, 227, 232 | The invite stores the assignment. Claiming the link creates the user and sets both columns | The rule reads the columns after claim. It does not send the invite |

Homework approval and the API copies are the same check repeated. They move together.

## What a class teacher can do once the rule exists

Scoped to their stream, or to every stream of the section when they are the class head:

- Manage the students in that scope, including import (the import itself is specified in `docs/product/student-import.md`).
- View the combined mark sheet for that scope.
- Release marks for that scope when the school setting allows it.
- The things the files above already allow: attendance, homework, assignments, leave, notices, and report cards for that scope.

A class teacher does not gain another stream by being a subject teacher. Subject teaching stays on `exam.teacher_id` and `teacherlinks`.
