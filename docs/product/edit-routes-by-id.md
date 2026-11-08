# Routes and links that address a person by name

Audit and proposal only. No code changes. Serves **T-104**. References are to `main` at `01d87832`.

> Public repository: this document lists routes and what they do, not how to misuse them. Anything that needs a private write-up goes to `KlassApp-Foundation/internal` (standing rules 29 and 37).

## Result first

**No cross-school access path found.** Every route below resolves the person with the signed-in user's own `school_id` (`User::where('name', $name)->where('school_id', ...)`, `exactNameInSchool`, or the profile services). A name belonging to another school returns 404 (or 403 on the roster-checked teacher routes). Nothing here needs an urgent flag. What I could **not** check is listed under "Not covered".

What does need fixing:

1. **Two people can share a name inside one school, and every route picks the first.** `users.name` stopped being unique in March 2026 (`2026_03_19_060000_fix_users_name_constraints_for_full_names.php` drops `users_name_unique`), and imports now create full names. Every lookup below ends in `first()` or `firstOrFail()` with no check for a second match, so with two "Grace Nakato" in one school the edit, deactivate, reset-password, status and **delete** routes act on whichever row the database returns first, not on the person the admin clicked.
2. **Several lookups do not filter by user group.** For example `/admin/parent/delete/{name}` and `/admin/parent/edit/{name}` look up any user in the school with that name, so a teacher's or student's name works in a "parent" URL. Inside one school this is a wrong-record risk, not a data leak across schools, and is worth closing at the same time (see the table's last column).
3. **The name is the URL.** A name that changes breaks bookmarks; a name with `/`, `?`, `#` or `.` or non-ASCII characters needs encoding; `deactivate` redirects to `/.../show/<name>` (`ParentProfileController`, `TeacherProfileController`). Names are also shown in the browser address bar and server logs.

## What I looked at

- All `{name}` person routes in `routes/admin.php` and `routes/teacher.php`: **69 route registrations**. (`/admin/addon/{name}/detail`, `routes/admin.php:1045`, is an add-on slug, not a person, and is out of scope.)
- The controller method behind each, to see how the person is found and whether the lookup carries `school_id` and a user group.
- Where links to these routes are built: about 80 places in `resources/views/**` (for example `admin/parent/edit.blade.php`, `admin/teacher/edit.blade.php`, `admin/staff/*.blade.php`, `admin/member/*.blade.php`, `admin/school/standardlinks/list.blade.php`) and `resources/assets/js/components/**` (for example `parent/Edit.vue`, `parent/profile/*.vue`, `staff/List.vue`, `teacher/Edit.vue`, `todolist/List.vue`).

## Already moving to ids (use as the pattern)

- Students already have an id profile: `GET /admin/students/{student}` (`routes/admin.php:268`, `->whereNumber('student')`, name `admin.students.profile`), and the old `GET /admin/student/show/{name}` is `StudentDetailsController@redirectToIdProfile`, which redirects only when the name matches exactly one student in the school and gives 404 otherwise.
- `ParentProfileService::find()` and `TeacherProfileService::find()` already fall back to the id when the value is all digits. That fallback is ambiguous for someone whose stored name is digits only; id routes should be separate and numeric-only.

## The routes

Lookup column: what the controller does today. "any group" means the lookup does not restrict to parents, teachers or students.

| Route file | Route | Controller | Lookup |
| --- | --- | --- | --- |
| `routes/admin.php:277` | GET `/admin/student/show/details/{name}` | `StudentDetailsController@showDetails` | name + school + group 6 (helper) |
| `routes/admin.php:278` | GET `/admin/student/show/relations/{name}` | `StudentDetailsController@showRelations` | name + school + group 6 (helper) |
| `routes/admin.php:279` | GET `/admin/student/show/siblings/{name}` | `StudentDetailsController@showSiblings` | name + school + group 6 (helper) |
| `routes/admin.php:280` | GET `/admin/student/show/activity/{name}` | `StudentDetailsController@showActivity` | name + school + group 6 (helper) |
| `routes/admin.php:281` | GET `/admin/student/show/discipline/{name}` | `StudentDetailsController@showDisciplines` | name + school + group 6 (helper) |
| `routes/admin.php:282` | GET `/admin/student/show/attendance/{name}` | `StudentDetailsController@showAttendance` | name + school + group 6 (helper) |
| `routes/admin.php:283` | GET `/admin/student/show/libraryactivity/{name}` | `StudentDetailsController@showBookLent` | name + school + group 6 (helper) |
| `routes/admin.php:284` | GET `/admin/student/show/{name}` | `StudentDetailsController@redirectToIdProfile` | name + school + group 6 (helper) |
| `routes/admin.php:285` | GET `/admin/student/show/fees/{name}` | `StudentDetailsController@showFees` | name + school + group 6 (helper) |
| `routes/admin.php:286` | GET `/admin/student/show/medicalHistory/{name}` | `StudentDetailsController@showMedicalHistory` | name + school + group 6 (helper) |
| `routes/admin.php:287` | GET `/admin/student/add/medicalHistory/{name}` | `StudentDetailsController@createMedicalHistory` | name + school + group 6 (helper) |
| `routes/admin.php:288` | POST `/admin/student/add/medicalHistory/{name}` | `StudentDetailsController@addMedicalHistory` | name + school + group 6 (helper) |
| `routes/admin.php:297` | GET `/admin/student/editStudent/{name}` | `StudentController@editStudent` | name + school + group 6 |
| `routes/admin.php:298` | GET `/admin/student/edit/{name}` | `StudentController@edit` | name + school + group 6 |
| `routes/admin.php:299` | POST `/admin/student/edit/validationUser/{name}` | `StudentController@editValidationUser` | name + school, any group |
| `routes/admin.php:300` | POST `/admin/student/edit/{name}` | `StudentController@update` | name + school, any group |
| `routes/admin.php:303` | DELETE `/admin/student/delete/{name}` | `StudentController@destroy` | name + school, any group |
| `routes/admin.php:308` | POST `/admin/user/updateStatus/{name}` | `UserController@updateStatus` | name + school, any group |
| `routes/admin.php:314` | GET `/admin/credentials/get/{name}` | `UserProfileController@getCredentials` | name + school, any group; `->first()` with no null check |
| `routes/admin.php:315` | POST `/admin/credentials/add/{name}` | `UserProfileController@updateCredentials` | name + school, any group; `->first()` with no null check |
| `routes/admin.php:322` | DELETE `/admin/parent/delete/{name}` | `ParentController@destroy` | name + school, any group |
| `routes/admin.php:329` | GET `/admin/parent/show/{name}` | `ParentController@show` | name + school, any group |
| `routes/admin.php:330` | POST `/admin/parent/show/{name}/deactivate` | `ParentProfileController@deactivate` | name + school + group 7 (service; numeric fallback to id) |
| `routes/admin.php:331` | POST `/admin/parent/show/{name}/reset-password` | `ParentProfileController@resetPassword` | name + school + group 7 (service; numeric fallback to id) |
| `routes/admin.php:332` | GET `/admin/parent/show/children/{name}` | `ParentController@showChildren` | name + school, any group |
| `routes/admin.php:333` | GET `/admin/parent/show/activity/{name}` | `ParentController@showActivityLog` | name + school, any group |
| `routes/admin.php:334` | GET `/admin/parent/show/feedback/{name}` | `ParentController@showFeedbacks` | name + school, any group |
| `routes/admin.php:336` | GET `/admin/parent/editlist/{name}` | `ParentController@editList` | name + school, any group |
| `routes/admin.php:337` | POST `/admin/parent/edit/validationUser/{name}` | `ParentController@editValidationUser` | name + school, any group |
| `routes/admin.php:338` | GET `/admin/parent/edit/{name}` | `ParentController@edit` | name + school, any group |
| `routes/admin.php:339` | POST `/admin/parent/edit/{name}` | `ParentController@update` | name + school, any group |
| `routes/admin.php:346` | DELETE `/admin/teacher/delete/{name}` | `TeacherListController@destroy` | name + school, any group |
| `routes/admin.php:359` | GET `/admin/teacher/show/details/{name}` | `TeacherShowController@showDetails` | name + school + group 5 (helper) |
| `routes/admin.php:360` | GET `/admin/teacher/show/timetable/{name}` | `TeacherShowController@showTimetable` | name + school + group 5 (helper) |
| `routes/admin.php:372` | GET `/admin/teacher/show/classes/{name}` | `TeacherShowController@showClasses` | name + school + group 5 (helper) |
| `routes/admin.php:373` | GET `/admin/teacher/show/classteacher/{name}` | `TeacherShowController@showClassTeacher` | name + school + group 5 (helper) |
| `routes/admin.php:374` | GET `/admin/teacher/show/leave/{name}` | `TeacherShowController@showLeaveHistory` | name + school + group 5 (helper) |
| `routes/admin.php:375` | GET `/admin/teacher/show/activity/{name}` | `TeacherShowController@showActivity` | name + school + group 5 (helper) |
| `routes/admin.php:376` | GET `/admin/teacher/show/logactivity/{name}` | `TeacherShowController@showActivityLog` | name + school + group 5 (helper) |
| `routes/admin.php:377` | POST `/admin/teacher/show/{name}/deactivate` | `TeacherProfileController@deactivate` | name + school + group 5 (service; numeric fallback to id) |
| `routes/admin.php:378` | GET `/admin/teacher/show/{name}` | `TeacherProfileController@show` | name + school + group 5 (service; numeric fallback to id) |
| `routes/admin.php:380` | GET `/admin/teacher/editTeacher/{name}` | `TeacherEditController@editTeacher` | name + school + group 5 |
| `routes/admin.php:381` | GET `/admin/teacher/edit/{name}` | `TeacherEditController@edit` | name + school + group 5 |
| `routes/admin.php:382` | POST `/admin/teacher/edit/validationProfile/{name}` | `TeacherEditController@editValidationProfile` | name + school + group 5 |
| `routes/admin.php:383` | POST `/admin/teacher/edit/validationQualification/{name}` | `TeacherEditController@editValidationQualification` | name + school + group 5 |
| `routes/admin.php:384` | POST `/admin/teacher/edit/validationNote/{name}` | `TeacherEditController@editValidationNote` | name + school + group 5 |
| `routes/admin.php:385` | POST `/admin/teacher/edit/validationAddress/{name}` | `TeacherEditController@editValidationAddress` | name + school + group 5 |
| `routes/admin.php:386` | POST `/admin/teacher/edit/{name}` | `TeacherEditController@update` | name + school + group 5 |
| `routes/admin.php:412` | GET `/admin/document/get/{name}` | `DocumentsController@index` | name + school, any group |
| `routes/admin.php:414` | POST `/admin/document/add/{name}` | `DocumentsController@store` | name + school, any group |
| `routes/admin.php:417` | POST `/admin/document/edit/{name}/{id}` | `DocumentsController@update` | name + school, any group |
| `routes/admin.php:854` | GET `/admin/bankdetails/get/{name}` | `BankDetailController@index` | name + school, any group |
| `routes/admin.php:855` | POST `/admin/bankdetails/add/{name}` | `BankDetailController@store` | name + school, any group |
| `routes/admin.php:869` | GET `/admin/teacher/attendances/show/{name}` | `StaffAttendanceController@getStudentAttendance` | name + school, any group |
| `routes/admin.php:871` | GET `/admin/teacher/show/attendance/{name}` | `StaffAttendanceController@showAttendance` | name + school, any group |
| `routes/admin.php:875` | GET `/admin/staff/show/{name}` | `StaffController@show` | name + school, any group |
| `routes/admin.php:876` | DELETE `/admin/staff/delete/{name}` | `StaffController@destroy` | name + school, any group |
| `routes/admin.php:879` | GET `/admin/staff/edit/{name}` | `StaffController@edit` | name + school, any group |
| `routes/admin.php:880` | POST `/admin/staff/edit/{name}` | `StaffController@update` | name + school, any group |
| `routes/teacher.php:354` | GET `/teacher/student/show/{name}` | `StudentDetailsController@show` | name + school + group 6 + roster scope |
| `routes/teacher.php:355` | GET `/teacher/student/show/details/{name}` | `StudentDetailsController@showDetails` | name + school + group 6 + roster scope |
| `routes/teacher.php:356` | GET `/teacher/student/show/relations/{name}` | `StudentDetailsController@showRelations` | name + school + group 6 + roster scope |
| `routes/teacher.php:357` | GET `/teacher/student/show/siblings/{name}` | `StudentDetailsController@showSiblings` | name + school + group 6 + roster scope |
| `routes/teacher.php:358` | GET `/teacher/student/show/activity/{name}` | `StudentDetailsController@showActivity` | name + school + group 6 + roster scope |
| `routes/teacher.php:359` | GET `/teacher/student/show/discipline/{name}` | `StudentDetailsController@showDisciplines` | name + school + group 6 + roster scope |
| `routes/teacher.php:360` | GET `/teacher/student/show/attendance/{name}` | `StudentDetailsController@showAttendance` | name + school + group 6 + roster scope |
| `routes/teacher.php:361` | GET `/teacher/student/show/libraryactivity/{name}` | `StudentDetailsController@showBookLent` | name + school + group 6 + roster scope |
| `routes/teacher.php:362` | GET `/teacher/student/show/medicalHistory/{name}` | `StudentDetailsController@showMedicalHistory` | name + school + group 6 + roster scope |
| `routes/teacher.php:363` | GET `/teacher/document/get/{name}` | `StudentDetailsController@showDocuments` | name + school + group 6 + roster scope |

Every row above uses the signed-in user's `school_id`. None reads `school_id` from the request.

## What breaks when two people share a name

| Action | Effect with a duplicate name |
| --- | --- |
| `DELETE .../student/delete`, `.../parent/delete`, `.../teacher/delete`, `.../staff/delete` | Deletes (soft or hard, per controller) the first match, possibly the wrong person and their links |
| `POST .../deactivate`, `POST /admin/user/updateStatus/{name}` | Deactivates or re-activates the wrong person; `updateStatus` also resets the person's token and writes `userprofile->status` without a null check |
| `POST .../parent/show/{name}/reset-password` | Resets the wrong parent's password |
| `POST .../edit/{name}` (student, parent, teacher, staff) | Saves the form over the wrong person |
| `GET .../show`, `.../edit` pages and the JSON tabs | Show the wrong person's data to the admin |
| `GET/POST /admin/credentials/*` | Returns or changes the wrong person's email and phone |
| `/admin/document/*`, `/admin/bankdetails/*` | Attaches or shows documents and bank details of the wrong person |

Two people sharing a name inside a school is likely for parents (two guardians with the same name, or one parent recorded twice) and for students in large streams.

## Permission gaps

- **Across schools: none found** on these routes (see "Result first").
- **Within a school:** lookups without a user group (marked "any group" in the table) accept any person's name on a route meant for one role. `ParentController`, `StudentController@update/destroy/editValidationUser`, `TeacherListController@destroy`, `UserController@updateStatus`, `UserProfileController@getCredentials`, `StaffController`, `DocumentsController`, `BankDetailController` and `StaffAttendanceController` are in this group. Which signed-in users reach these routes depends on the `full-school-admin` gate (`AuthServiceProvider.php:68`) and the middleware on each group; I did not map every group, so whether a sub-role (for example an accountant or librarian) can reach the delete and status routes is **open** (question 2).
- **Teacher routes** (`routes/teacher.php`, rows 354 to 363): the lookup also passes `RosterScopeService::actorCanAccessStudent`, so a teacher is limited to their own roster. This is the right shape.
- `UserProfileController@getCredentials` returns email and phone for any user found by name in the school, with a bare `first()` and no null check (an unknown name raises and the error is only logged).

## Proposal: switch to ids, keep old links working

1. **Add id routes** beside the name routes, numeric only (`->whereNumber('id')`), scoped by `school_id` and by user group in one place (a school-scoped route binding or a policy; standing rule 41): `/admin/parents/{id}`, `/admin/teachers/{id}`, `/admin/staff/{id}` and the matching edit, deactivate, reset-password, delete and tab endpoints. Students already have `/admin/students/{student}`.
2. **Old name routes become redirects (GET).** Resolve the name in the school and group. Exactly one match: redirect to the id route (302 first, 301 later). No match: 404. **More than one match: do not pick one**; show a small "Which one?" page listing the matches (name, class or phone, status) with links by id, scoped to the school. This is the `redirectToIdProfile` pattern extended with a duplicate guard.
3. **Old name routes for POST and DELETE stop acting.** A redirect would silently turn a write into a GET. Return 404 with a short message ("This link is out of date. Open the person from the list and try again") and log the hit so remaining callers can be found. Do this only after step 4 so nothing in the app still posts to them.
4. **Change the link builders** (the ~80 places in Blade and Vue) to use `$user->id`. Keep a `route()` helper or a single JS helper so there is one place that builds a person URL (standing rule 40).
5. **Remove the digit fallback** in the profile services once id routes exist.
6. **Tests** (rule 48, named after the tracker item): another school's user gets 404 on every id route; two people with the same name in one school never get the wrong record from a name link (the "Which one?" page appears); each old name link redirects to the id route when unique; delete, deactivate and status act only on the id in the URL; a teacher's id on a parent route returns 404.
7. **Order of work**, smallest first: (a) add the duplicate guard and group filter to the existing name routes (no UI change, closes risks 1 and 2 immediately); (b) id routes and redirects for parents, then teachers and staff; (c) switch the link builders; (d) retire the name POST/DELETE routes; (e) after a release or two with no logged hits, remove the name routes.

## Not covered

- Routes that take a person by a name in a request body or query string (for example `ref_name` on `/admin/parent/edit/{name}`), API routes under `routes/api*.php`, Livewire actions and site-admin routes. These need their own pass.
- Admin routes that use an id but do not scope by `school_id` (outside the question asked).
- Whether any non-admin role can reach the "any group" routes (open question 2).

## Open questions for Rasta

1. Is "Which one?" on a duplicate acceptable, or should the app block creating a second person with the same name in a school (rule 4: never force an ambiguous match)?
2. Which roles can reach `/admin/*/delete/{name}` and `/admin/user/updateStatus/{name}` today? If any sub-role can, step 7(a) should go first as its own small change.
3. How long should old name links keep redirecting (suggested: two releases, then remove)?
4. Should parents with the same name and phone be merged (a data task) before the id routes ship?
