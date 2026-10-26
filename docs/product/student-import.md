# Student import

Proposal only. The measurement below was run on a synthetic school. No production data was read.

## What the school can do

An admin, or a class teacher for their own class or stream, imports students in one of three scopes:

- the whole school
- one class
- one stream of a class

They pick the scope, download a template labelled with that class or stream, upload a file, see row-level errors, and confirm. Nothing is saved while the preview has errors, unless they choose “import valid rows only”. Running the same file again updates the matching students. It does not create a second copy.

The same import is used by the onboarding wizard, the admin students page, and a class teacher limited to their own class or stream.

A single file may hold 20,000 students. A class may be about 1,000 learners, split into streams of 300–500.

## What the code does today

There are two writers, and neither previews.

**Admin spreadsheet.** `GET /admin/import` is `ImportMemberController` (the general student import). `POST /admin/importUsers` runs `app/Imports/UsersImport.php`. `UsersImport` implements `ToCollection` (`collection()` at line 31), so the whole file is loaded before the first student is saved. Each row:

- is skipped, with a log line and no message in the page, when `firstname` or `class` is empty (lines 45–47)
- looks up the country with `LIKE` (line 76)
- maps the class text onto a standard by prefix (`p` → primary, nursery prefixes, `s.1`–`s.6` maps at lines 96–125) and then a section by name (line 125)
- calls `CreateUser` (lines 191–197)

There is no stream column. There is no row-level error returned to the admin. A database error is not turned into a preview. Matching for a second run is not “student ID, then update”. The teacher import is a different route (`GET /admin/import/teacher`) and is not this flow.

**Onboarding.** The wizard and `OnboardingEngine::saveStudents` accept a pasted list or an upload of their own. That path does not call `UsersImport`. A class teacher has no student-import route. `routes/teacher.php` has marks import, not students.

`routes/admin.php` is the place those routes are registered. This document does not change them.

## Measured path

Run on 2026-10-26 against the real `UsersImport` (`Excel::import`) inside PHPUnit, SQLite in memory, synthetic school only. One primary standard, 50 streams named `P.1 Stream N` (not 1,000 classes), an academic year, Uganda, and a CSV of 20,000 students spread across those 50 streams. No parent phone column, so the parent-create branch was not taken. Query counting is a listener that increments a counter. It does not keep the SQL text. The probe file was not committed.

| | |
| --- | --- |
| Rows in the file | 20,000 |
| Streams prepared | 50 |
| Time | 156.42 seconds |
| Peak memory | 187 MB |
| Queries | 269,991 |
| Student users still saved | 9,999 |
| Exception from `Excel::import` | none |

The import returned normally. The database kept 9,999 student users. The other 10,001 rows were rolled back inside `CreateUser` and never shown as errors.

`StudentIdGeneratorService::next` (`app/Services/StudentIdGeneratorService.php` line 54) builds `KLS` plus a 3-digit school id plus a 4-digit sequence (`sprintf('KLS%03d%04d', …)`). For school id 1, sequence 9,999 is `KLS0019999`. Sequence 10,000 is `KLS00110000`. `User::saving` (`app/Models/User.php` lines 92–102) only allows `KLS` plus 7 digits (`/^KLS\d{7}$/i`). The log line for the first rejected row is `registration_number must match format KLS####### (e.g. KLS0010427), got: KLS00110000`. `users.registration_number` is a nullable string in `database/migrations/2020_02_18_000000_create_users_table.php` (line 28). The stop is that format check, not a short column.

`CreateUser` (`app/Traits/RegisterUser.php` lines 206–211) catches `Exception`, rolls the row back, logs the message, and returns null. `UsersImport` (`app/Imports/UsersImport.php` lines 191–203) then increments its inserted counter, including when `CreateUser` returned null. Its `dd()` (lines 208–210) runs only if an exception leaves `CreateUser`. That did not happen, so the probe’s error field stayed null. The session counter itself was not read.

## Proposal

One import service. The wizard, the admin page, and the class teacher all call it. The class teacher’s call is rejected unless the scope is a class or stream the class-teacher rule assigns to them (`docs/product/class-teacher-capabilities.md`).

- The template is generated for the chosen school, class, or stream. The class or stream name is on the sheet, not only in the admin’s head.
- The file is read as a stream, not loaded as one collection.
- Work runs in queued chunks. The page shows how many rows are done. A failed chunk can be resumed. A finished chunk is not imported again.
- Each row is checked before any write. Errors name the row and the column. If any row has an error, nothing is saved, unless the admin chooses “import valid rows only”.
- A second run updates. Match the school’s student ID number, then update that student. Do not insert another user with the same ID.
- Every query includes `school_id`.

Released marks and payments are not part of this import. The import creates or updates students and their class or stream placement only.
