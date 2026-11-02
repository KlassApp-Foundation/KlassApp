# Student import

Proposal only. The measurement below was run on a synthetic school. No production data was read.

## Student ID format

A KlassApp student ID is `KLS` plus 7 digits, or `KLS` plus 11 digits. `StudentIdGeneratorService::PATTERN` is `/^KLS(?:\d{7}|\d{11})$/i`. There is no hyphen.

- School id at most 999 and sequence at most 9999: `KLS` plus the school padded to 3 digits plus the sequence padded to 4. School 1, sequence 5 is `KLS0010005`.
- Otherwise: `KLS` plus the school padded to 5 digits plus the sequence padded to 6. School 100, sequence 10001 is `KLS00100010001`. School 1001, sequence 1 is `KLS01001000001`.
- A school id above 99999 or a sequence above 999999 is refused.

Existing 7-digit IDs stay as stored. The import does not ask the admin to type this ID. `CreateUser` assigns it. A failed row is shown as plain text, for example `Row 12 could not be imported. Check the student's details and try again.` The exception, the SQL, and the stack trace are written to the log with the row number and the school id. They are not shown on the page, in the session, or in a download.

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

- is skipped, with a plain message on the import page, when `firstname` or `class` is empty. The log records the row number and school id, not the row's values
- looks up the country with `LIKE` (line 76)
- maps the class text onto a standard by prefix (`p` → primary, nursery prefixes, `s.1`–`s.6` maps at lines 96–125) and then a section by name (line 125)
- calls `CreateUser` (lines 191–197)

There is no stream column. A failed row is listed on the import page as plain text (see Student ID format). A database error is not turned into a preview, and the SQL is not shown to the admin. Matching for a second run is not “student ID, then update”. The teacher import is a different route (`GET /admin/import/teacher`) and is not this flow.

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

This measurement is the code before T-060. On that build, `StudentIdGeneratorService::next` built `KLS` plus a 3-digit school id plus a 4-digit sequence. For school id 1, sequence 9,999 is `KLS0019999`. Sequence 10,000 became the rejected string `KLS00110000`, which is not an ID. `User::saving` on that build only allowed `KLS` plus 7 digits. The log line for the first rejected row quoted `got: KLS00110000` (the same rejected string, not an ID) beside the example `KLS0010427`. `users.registration_number` is a nullable string. The stop was that format check, not a short column. The current format is in Student ID format above.

On that same build, `CreateUser` caught `Exception`, rolled the row back, logged the message, and returned null. `UsersImport` then incremented its inserted counter, including when `CreateUser` returned null. A `dd()` ran only if an exception left `CreateUser`. That did not happen, so the probe’s error field stayed null. The session counter itself was not read. The import now counts a row only when `CreateUser` returns a user.

## Approved additions

The student ID format now accepts `KLS` plus 7 digits or `KLS` plus 11 digits (see Student ID format). The measurement below was taken before that change, so it stopped at 9,999 students. Existing 7-digit IDs stay as they are.

A row with no student ID is not saved as a second student in silence. The preview warns that it is a likely duplicate when the same name, date of birth, and class are already in the school. The admin decides whether that row is a new student.

Country on a row is matched by country code (the `countries.short_name` / ISO code), not by `LIKE` on the name.

The background job’s target is 20,000 students processed in under 10 minutes, with progress the admin can see.

## Proposal

One import service. The wizard, the admin page, and the class teacher all call it. The class teacher’s call is rejected unless the scope is a class or stream the class-teacher rule assigns to them (`docs/product/class-teacher-capabilities.md`).

- The template is generated for the chosen school, class, or stream. The class or stream name is on the sheet, not only in the admin’s head.
- The file is read as a stream, not loaded as one collection.
- Work runs in queued chunks. The page shows how many rows are done. A failed chunk can be resumed. A finished chunk is not imported again. 20,000 students finish in the background in under 10 minutes.
- Each row is checked before any write. Errors name the row and the column. If any row has an error, nothing is saved, unless the admin chooses “import valid rows only”.
- A row without a student ID that matches an existing student on name, date of birth, and class is a likely-duplicate warning, not a silent second student.
- A second run updates. Match the school’s student ID number, then update that student. Do not insert another user with the same ID.
- Country is matched by country code.
- Every query includes `school_id`.

Released marks and payments are not part of this import. The import creates or updates students and their class or stream placement only.

## Measurement with parent phone numbers

Run on 2026-10-27 against the same `UsersImport` path, same synthetic school, same 50 streams, and the same 20,000 student rows. Every row also had a parent first name, last name, phone, and email, so the parent-create branch ran. Query counting is the same listener. The probe file was not committed. This run is the code on `main` before the student-ID widening (T-060).

| | |
| --- | --- |
| Rows in the file | 20,000 |
| Streams prepared | 50 |
| Parent phone on every row | yes |
| Time | 222.65 seconds |
| Peak memory | 239 MB |
| Queries | 379,985 |
| Student users saved | 9,999 |
| Parent users saved | 9,999 |
| Exception from `Excel::import` | `TypeError` in `User::findByExactNameInSchool()` |

The first 9,999 students saved, each with a parent. Row 10,000 is sequence 10,000. `CreateUser` built the rejected string `KLS00110000`, which is not an ID. `User::saving` rejected it, the row rolled back, and `CreateUser` returned null. On that build, `UsersImport` still called `CreateParent` with that null student. `RegisterUser` then called `findByExactNameInSchool(null, …)` and the import aborted. The counter still treated failed rows as inserted up to that point, and the admin was not shown the failed rows. That was the code before T-060. The import now counts a row only when `CreateUser` returns a user, and a failed row is shown as plain text. The background target stays 20,000 students in under 10 minutes. This synchronous run was 222.65 seconds and stopped at 9,999.
