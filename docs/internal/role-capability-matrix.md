# Role capability matrix (reference)

**Status:** reference document, derived from real code on 2026-09-22. No functional testing was performed; this is the baseline the testing pass will follow.

## How this was derived

Nothing here is copied from other documentation. Every cell comes from one of these sources:

| Source | What it established |
|---|---|
| `config/navigation.php` | the real sidebar destinations per role |
| `php artisan route:list --json` | every real route, method and URI, per role prefix |
| `app/Providers/RouteServiceProvider.php` | the middleware group each role file sits behind |
| `app/Services/ToshiActionService.php::getRoleCapabilities()` | the real per-role Toshi action set and scope |
| `app/Helpers/SiteHelper.php`, `ReportCardsController`, `ClassStreamController` | the real scope helpers and server-side checks |

Roles: **SchoolAdmin** (usergroup 3), **Class Teacher** (usergroup 5 with homeroom responsibility), **Teacher** (usergroup 5), **Parent** (7), **Student** (6).

## Important: there is no separate Class Teacher role

Class Teacher is **not a role**. It is a `condition => 'class_teacher'` flag on two teacher sidebar items, plus server-side checks that use homeroom ownership. So "Class Teacher" is always a superset of Teacher, never a different actor. The only Class-Teacher-only surface is Report Cards and Class Streams.

## Sidebar destinations as the code defines them

| Role | Destinations defined in `config/navigation.php` |
|---|---|
| SchoolAdmin | Dashboard, Students, Parents, Classes & Streams, Subjects, Timetable, Attendance, Exams & Marks, Grading, Report Cards, Library, Health, Transport, Fees & Payments, Unmatched Payments, Messaging, Calendar, Approvals, Data Exports, Settings (20) |
| Teacher | Dashboard, Classes, Timetable, Attendance, Exams, Homework, Marks, Report Cards (CT only), Class Streams (CT only), Students, Notices, Events, Library (13) |
| Parent | Dashboard, Children (2) |
| Student | Dashboard, Homework, Assignments, Events, Notices, Library, Holidays, Chats, Activity (9) |

## Capability table

Legend: **FULL** = create/edit/delete present in routes and permitted. **WRITE-LIMITED** = creates or updates but not deletes, or restricted subset. **VIEW** = read only. **SCOPED** = allowed but bounded by ownership, school, or a setting. **SELF** = own record only. **NONE** = not available.

| Capability | SchoolAdmin | Class Teacher | Teacher | Parent | Student |
|---|---|---|---|---|---|
| Student list | FULL | SCOPED (own classes) | SCOPED (own classes) | SELF (own children, via Children) | SELF |
| Create / edit / delete student | FULL | NONE | NONE | NONE | NONE |
| Parent records | FULL | NONE | NONE | SELF | NONE |
| Teacher / staff records | FULL | NONE | NONE | NONE | NONE |
| Attendance: view | FULL (school wide) | SCOPED by `attendance_scope` | SCOPED by `attendance_scope` | VIEW (per child) | NONE in nav |
| Attendance: record | FULL | SCOPED by `attendance_scope` | SCOPED by `attendance_scope` | NONE | NONE |
| Attendance: export | FULL | SCOPED by `attendance_scope` | SCOPED by `attendance_scope` | NONE | NONE |
| Marks: view / enter | FULL | WRITE-LIMITED (own classes) | WRITE-LIMITED (own classes) | VIEW (per child) | NONE in nav |
| Report cards | FULL | VIEW (own class, enforced) | NONE | NONE | NONE |
| Class streams (create / rename) | FULL | WRITE-LIMITED | NONE | NONE | NONE |
| Class streams (delete / merge) | FULL | NONE | NONE | NONE | NONE |
| Homework | FULL | FULL (own classes) | FULL (own classes) | NONE | WRITE-LIMITED (submit, reply) |
| Assignments | FULL | FULL (own classes) | FULL (own classes) | NONE | WRITE-LIMITED (add) |
| Lesson plans | FULL | FULL | FULL | NONE | NONE |
| Timetable: view | FULL | VIEW | VIEW | NONE | NONE |
| Timetable: manage | FULL | NONE | NONE | NONE | NONE |
| Exams: create / manage | FULL | NONE | NONE | NONE | NONE |
| Grading scales | FULL | NONE | NONE | NONE | NONE |
| Fees and payments | FULL | NONE | NONE | VIEW (per child) | NONE |
| Library | FULL (books, lends, cards) | VIEW (activity) | VIEW (activity) | NONE | VIEW (activity) |
| Notices | FULL | VIEW | VIEW | NONE | VIEW |
| Events | FULL | VIEW | VIEW | NONE | VIEW |
| Calendar | FULL | NONE | NONE | NONE | NONE |
| Messaging / conversations | FULL | VIEW | VIEW | NONE | FULL (chats) |
| Class wall (posts, pages) | FULL | FULL | FULL | NONE | FULL |
| Approvals queue | FULL | NONE | NONE | NONE | NONE |
| Leave: apply | NONE | FULL | FULL | NONE | NONE |
| Leave: approve / reject | FULL | FULL (own classes) | FULL (own classes) | NONE | NONE |
| Tasks (self) | FULL | FULL | FULL | NONE | FULL |
| Visitor log / call log / postal record | FULL | WRITE-LIMITED (see finding 2) | WRITE-LIMITED (see finding 2) | NONE | NONE |
| Health records | FULL | NONE | NONE | NONE | NONE |
| Transport | FULL | NONE | NONE | NONE | NONE |
| School settings | FULL | NONE | NONE | NONE | NONE |
| Data exports / reports | FULL | NONE | NONE | NONE | NONE |

## Attendance scope: the one genuinely configurable row

Attendance is the only capability whose boundary is a per-school setting. `school_details` meta key `attendance_scope`, resolved by `SiteHelper::resolveAttendanceScope()`, enforced identically in the web request, the web controller (`store`, `export`) and the teacher API:

| Mode | A teacher may record attendance for | Notes |
|---|---|---|
| `class_teacher_only` | their homeroom classes only | the originally shipped scope |
| `classes_i_teach` **(default)** | homeroom **union** classes they are assigned as a subject teacher (`class_teacher_links`) | missing or unrecognised values resolve here |
| `school_wide` | any active class in the school | explicit opt-in only |

An inactive class (`status = 0`) is refused in **every** mode. A teacher from another school is refused in **every** mode. Scope is always read from `Auth::user()->school_id`, never from input.

## Toshi capabilities by role

From `ToshiActionService::getRoleCapabilities()`. The Toshi component refuses to load for a role whose action list is empty or whose scope is `none`.

| Role | Scope | Actions |
|---|---|---|
| SchoolAdmin | school | ~40, the widest set: people, fees, terms, attendance, payments, exams, marks, subjects, classes, reports, notices, events, holidays, timetable slots, homework, approvals |
| Class Teacher | school | identical to Teacher; **no class-teacher-specific Toshi action** |
| Teacher | school | 12: `mark_attendance`, `enter_marks`, `manage_lesson_plans`, `manage_assignments`, `manage_homework`, `apply_leave`, `manage_class_wall`, `view_students`, `view_timetable`, `view_events`, `manage_tasks`, `manage_noticeboard` |
| Parent | children | 11, reads across children: fee balance, grades, attendance, and similar |
| Student | self | 11, own record only: dashboard, assignments, homework, tasks, events, notices, marks, attendance, library activity, class wall, conversations |

Blocked roles, for completeness: usergroup 2 (scope `none`) and usergroup 12 Stock Keeper (empty action list).

## Findings worth flagging

1. **The teacher attendance overview page shipped in #799 is unreachable from the sidebar.** `config/navigation.php` points the teacher "Attendance" item at `teacher/dashboard` with a `#attendance` anchor, so the real `/teacher/attendance` page exists and works but no nav entry leads to it.
2. **Teacher routes carry receptionist-domain write access.** `/teacher/visitorlog/add|edit|update|delete`, `/teacher/calllog/*` and `/teacher/postalrecord/*` accept POST/PUT/DELETE and sit behind **only** `web, auth, teacher`. Any teacher can write visitor logs, call logs and postal records, and none of it appears in the teacher sidebar.
3. **The sidebar's `class_teacher` condition ignores the attendance scope.** It calls `getClassTeacherStandardLinks()`, which is homeroom-only. A teacher who only subject-teaches a class will not see Report Cards or Class Streams, even under `classes_i_teach` or `school_wide`.
4. **Parent's sidebar is far thinner than Parent's real capabilities.** The nav has 2 items, but the routes include per-child fees, grades and attendance. Those are reachable only by navigating through Children first.
5. **Student has no Marks or Attendance nav item**, while the routes and Toshi actions both cover marks and attendance. The capability exists; the entry point does not.
6. **Students can add assignments** (`/student/assignment/add`) and post to the class wall. Worth confirming that is intended rather than inherited.
7. **Admin "Health" points at `admin/students`**, duplicating the Students destination, even though `/admin/student/health` exists.
8. **Duplicate teacher destinations:** "Exams" and "Marks" both resolve to `teacher/exam/marks`; "Classes" and "Students" both resolve to `teacher/classes`.
9. **Admin's "Students" nav item claims parents, teachers, staff and alumni as active aliases**, so Students stays highlighted while those pages are open.
10. **Gating is inconsistent between role groups:** the admin group runs `schooladmin` plus `privilegeconditions`, the teacher group runs only `teacher`, so the academic-year and standards gate is absent for teachers.

## Measured population (local fixture data, 2026-09-22)

The findings above were quantified with Laravel Boost queries so the testing pass knows the current blast radius rather than only the theoretical one.

| Measurement | Value | What it means |
|---|---|---|
| Homeroom teachers (`standards_link.class_teacher_id`) | 6 | teachers who pass the `class_teacher` nav condition today |
| Teachers with subject assignments (`class_teacher_links`) | 1 | and that one is also a homeroom teacher |
| **Subject-only teachers (assigned, no homeroom)** | **0** | finding 3 currently bites nobody locally, because every subject-assigned teacher here is also a homeroom teacher |
| Subject assignments on record | 1 | the fixture, not real imported data |
| `visitor_log` rows | 0 | finding 2 is latent, not exploited |
| `call_log` rows | 0 | as above |
| `postal_record` rows | 0 | as above |
| Rows in `visitor_log` with an author column | **none exist** | the table has no recorded-by column at all, only `employee_id` for the visited staff member |

Two consequences for the testing pass:

1. **Finding 3 needs specific fixtures to reproduce.** With zero subject-only teachers in local data, the nav bug is invisible until a teacher is created who subject-teaches a class they do not homeroom. That is the normal case in a real secondary school, and it is the case the test must construct.
2. **Finding 2 cannot be caught by inspecting data.** Since `visitor_log` records no author, and all three tables are empty, the only way to show the exposure is to sign in as a teacher and write a row. Attribution would then be impossible after the fact, which is the sharper half of the finding.

### Also observed

`ToshiActionService::getRoleCapabilities()` defines roles for usergroups **2 (SiteSubadmin)** and **13 (Non Teaching)** that do not exist in the `usergroups` table. Usergroup 4 (SchoolSubadmin) exists with zero users. Neither affects the five roles in this matrix, but the map is not a faithful picture of the role list.
