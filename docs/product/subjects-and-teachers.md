# Subjects and teachers

Design only. No code changes. Serves **T-099** (Subjects page and form) and **T-100** (Edit class details form), and sets the data model both need.

Everything under "What exists today" was read from `main` (commit `01d87832`). File and line references are to `main`; the Monday integration branch (#1285) moves some of these files, so re-check lines before building.

## The problem in one paragraph

A school has one "Mathematics", taught in Primary 5 East by Ruth and in Primary 5 West by Daniel, and also in Primary 6. Today each class has its own Mathematics row, the Subjects page lists them as unrelated lines, a class can only show one teacher per subject row in a form that deletes and rewrites everything on save, and nothing says that two teachers share a subject. The school sees a long repeated list and cannot answer "who teaches Mathematics, and where?".

## What exists today

### Tables

| Table | Shape | Notes |
| --- | --- | --- |
| `subjects` | One row per **school, year, standard, section, name** (`database/migrations/2020_02_18_053001_create_subjects_table.php`). Columns: `name`, `code` (nullable), `type` core or elective, `status`, soft deletes. Unique on `(school_id, section_id, name)`. | So "Mathematics" in 6 classes is 6 rows. The subject is really a **class offering**, not a school-level subject. |
| `class_teacher_links` (model `Teacherlink`, `app/Models/Teacherlink.php:20`) | One row per **stream (`standardLink_id`), subject, teacher**, plus `no_of_periods`, `remaining_periods`, `subject_type`, `status`, soft deletes. | Nothing stops two rows for the same stream and subject with different teachers, and no unique index forbids a duplicate. The form never creates two. |
| `school_subject` (`database/migrations/2026_03_18_063523_create_school_subject_table.php`) | `school_id`, `subject_id` pivot. | **Not referenced anywhere in `app/`.** An unused start at a school-level subject list. |
| `exams` | One row per **subject and section** with a single `teacher_id` (`database/migrations/2026_03_14_062144_create_exams_table.php`). | The marker is one person. |
| `exam_marks_submissions` | One row per exam, class and subject, with `teacher_id`. | Submit and approve work per subject per class. |
| `timetable_slots` | `subject_id` and `teacher_id` per slot. | A second place that pairs a teacher with a subject. |
| `lesson_plans` | Points at a `class_teacher_links` row (`teacher_link_id`). | Deleting a link row can orphan or cascade into a lesson plan. |
| `standards_link` and `sections` | `class_teacher_id` on each: the stream teacher and the class head. | Covered in `docs/product/class-teacher-capabilities.md`. |

### The Subjects page (`/admin/subjects`)

`UgSubjectController` (`app/Http/Controllers/Admin/UgSubjectController.php`): `index()` lists every `Subject` row of the school, searched by name or code, with archived rows below. The list has columns Subject Name, Class, Subject Code, Type, Actions (`resources/views/admin/subject/list.blade.php`). There is no teacher column and no grouping.

### The add and edit subject form

`resources/views/admin/subject/form.blade.php`: pick **one** class (a section), a name, a code (required), a type. A script copies the section's standard into a hidden field. `CreateUgSubjectRequest` takes the year from the first academic year row it finds (`->pluck('id')->first()`), not from the current year, and checks name uniqueness per school and **standard** (`app/Http/Requests/CreateUgSubjectRequest.php:46-49`), while the database constraint is per **section**. To add Mathematics to five classes you submit the form five times.

### Edit class details

`/admin/standardLink/edit/{id}` renders a Vue component (`<edit-class>`, `resources/views/admin/school/standardlinks/edit.blade.php:17`). The save is `AcademicProcess::editStandardLink()` (`app/Traits/AcademicProcess.php:267-370`):

1. Sets the stream's class teacher and, if sent, the section's class head, and adds or removes the `student_leave_checker` designation.
2. Stream: only read when the standard is `'11'` or `'12'` (`StandardDetailUpdateRequest.php:62`). That is an Indian-curriculum rule left from the fork; Uganda has no standard 11 or 12 (AGENTS.md rule 14).
3. **Deletes every `Teacherlink` of the stream and re-creates one per submitted row.** Each row needs both a subject and a teacher (`subject_id{i}`, `teacher_id{i}` are required). So one teacher per subject per row, and any link that carried history (periods, a lesson plan) is destroyed and rebuilt on every save.
4. The class teacher must be unique in the school (`check_unique_class_teacher`): a teacher cannot be class teacher of two streams.

### How marks entry and the register use it

- **Marks entry:** `ExamAuthorization::canActOnExam()` allows the exam's `teacher_id` or the class teacher of that exam's class (`app/Services/ExamAuthorization.php:20-31`). The default teacher for a new exam is looked up from the `Teacherlink` for that subject and stream (`:128-141`), so only **one** teacher is ever picked.
- **Register (attendance):** `SiteHelper::attendanceScopeStandardLinkQuery()` (`app/Helpers/SiteHelper.php:296-340`) offers three school-level scopes: homeroom only, or homeroom plus every stream the teacher has a `Teacherlink` for ("classes I teach"). It is about classes, not subjects, so more teachers per subject do not change it.
- **Rosters and dashboards:** `RosterScopeService::applyTeacherStreamVisibility()` (`app/Services/RosterScopeService.php:241-264`) already reads `teacherlink` with `teacher_id`, so two teachers on one subject both see the stream.

## Proposal

### Principles

1. **Additive only.** Nothing is dropped or merged away. Every existing `subjects.id` keeps working, because exams, marks, submissions, timetable slots and homework all point at it.
2. **A subject is named once per school; a class offering is a row that points at it.** Keep today's `subjects` rows as the offerings, and add the school-level subject above them.
3. **A class can have several teachers for a subject**, stored as several `class_teacher_links` rows, which the table already allows.
4. Every query scoped by `school_id`; lookups by id, never by name (AGENTS.md rules 13 and 18). Names are for display and for the one-time grouping in the migration.

### Data model change

1. New table `subject_catalog`: `id`, `school_id`, `name`, `normalized_name` (lower case, collapsed spaces), `code` (nullable), `type` (core, elective, nullable), `status`, timestamps, soft deletes. Unique on `(school_id, normalized_name)`.
   - This is the school-level subject. The unused `school_subject` pivot is not reused: its name suggests school to subject, but it has no name or code to hold. Decision for Rasta below.
2. `subjects` gets a nullable `catalog_id` (foreign key, indexed). A row with a null `catalog_id` is a legacy row not yet grouped.
3. `class_teacher_links` gets:
   - a unique index on `(school_id, academic_year_id, standardLink_id, subject_id, teacher_id)` for live rows, added only after the dedupe report below is clean;
   - `is_lead` (boolean, default false): the teacher who answers by default (for example the default marker of a new exam). At most one lead per stream and subject.
4. Nothing changes on `exams`, `exam_marks_submissions`, `timetable_slots` or `lesson_plans` in the first release.

### Safe migration

Three steps, each shipped on its own, each reversible:

1. **Expand (schema only).** Create `subject_catalog`, add `subjects.catalog_id`, add `class_teacher_links.is_lead`. No data written. `down()` drops them. Safe on MySQL and SQLite with `hasTable`/`hasColumn` guards, like the other 2026 migrations.
2. **Backfill by command, dry run first**, in the style of `phones:to-e164`: `subjects:group` prints, per school, the groups it would make (same normalised name) with the classes in each, and the **disagreements** (same name, different code or type), then `--apply` writes. Rules:
   - group only inside one school;
   - never rename, merge, archive or delete a `subjects` row;
   - a group whose rows disagree on code or type is **left ungrouped and listed**, for a person to decide;
   - archived (soft-deleted) rows are grouped but never revived;
   - set `is_lead` only where a stream and subject has exactly one teacher link (that teacher is the lead); leave it false where there are several.
3. **Index the links.** `class_teacher_links:dedupe` (dry run first) lists duplicate stream, subject, teacher rows, and only when it reports zero is the unique index migration run.

Rollback for each step is its `down()` or, for step 2, the command's own `--undo` (sets `catalog_id` back to null for rows it grouped; it records which rows it touched in a log table). A database snapshot before step 2 is still the real safety net on production (AGENTS.md rule 2).

New subjects created through the new form always get a `catalog_id`. Old code paths that create a subject keep working and leave it null until the backfill is run again.

### The Subjects page: a roster

One row per catalog subject, not per class.

| Subject | Code | Type | Classes | Teachers |
| --- | --- | --- | --- | --- |
| Mathematics | MATH | Core | P.5 East, P.5 West, P.6 | Ruth Okoro, Daniel Okafor (+1) |

- **Classes** shows base classes as chips, with their streams inside ("P.5: East, West"). More than four collapse to "6 classes".
- **Teachers** shows up to two names and a count; the row expands to one line per class with that class's teachers and the lead marked.
- Search by subject name, code or teacher. Filter by class and by teacher. Rows with a null `catalog_id` show in a separate "Not grouped yet" section at the bottom, each as today, so nothing disappears while the backfill is pending.
- Actions: Edit (the form below), Archive. Archiving a subject archives its offerings that have no exams or marks; if any offering has exams, the page says which classes block it and archives none.
- Phone: each subject is a card with the same facts, classes and teachers collapsed.
- Empty state: "No subjects yet. Add the subjects your school teaches." with the Add subject button.

### The add and edit subject form

- **Subject name** and **Code** (code stays required, as today), **Type**.
- **Taught in**: a checklist of the school's base classes with a "select all" and a "Streams" disclosure for classes that have streams. Ticking P.5 offers P.5 as a whole; opening it lets you tick only East.
- **Teachers** (optional here): per ticked class, a teacher multi-select. They can be left empty and filled in on the class page.
- Save creates or updates the catalog row and every offering in **one transaction**. Editing the name or code changes the catalog row and updates the offerings' copies (the offerings keep a copy of `name` and `code` because exams and reports read them).
- Unticking a class archives that offering only when it has no exams or marks; otherwise save is refused with the reason, so no marks are orphaned.
- The year comes from the school's current year (`status = 1`), not the first row.

### The redesigned Edit class details form

- **Header:** the class and stream are shown and fixed ("Primary 5, East"). The form edits one stream; it is reached from the Classes page.
- **Streams:** a dropdown of the school's own streams, never the standard-number rule. Adding a new stream is a link to the class page, not a free text box.
- **Class teacher** (stream) and **Class head** (class, shown when the class has more than one stream) as two dropdowns of teachers, with the uniqueness message kept but phrased as "Ruth is already class teacher of P.5 West".
- **Subjects** as a table, one row per subject offered in this class:

  | Subject | Teachers | Lead | Periods a week |
  | --- | --- | --- | --- |
  | Mathematics | Ruth Okoro, Daniel Okafor | Ruth | 6 |

  - "Teachers" is a multi-select chip list; "Add subject" picks from the school's catalog (a subject not yet offered in this class), and "Create new subject" opens the subject form.
  - Saving is a **diff**, not delete and re-create: links that did not change are left alone (keeping periods, lesson plans and timetable ties); added teachers add rows; removed teachers soft-delete their row, and are refused if the teacher has an open exam or an unsubmitted mark for that subject in this class (message names the exam).
- Validation: a subject row needs at least one teacher only when the school setting "every subject needs a teacher" is on; today's "teacher required" becomes optional by default so a class can be saved while a teacher is still being hired. (Open question.)
- Phone: the subjects table becomes cards; teachers open a bottom sheet to pick.

### How marks entry and the register use it

**Marks entry.**
- `canActOnExam()` gains one rule: an actor may act on the exam if they have a live `class_teacher_links` row for that school, the exam's subject and the stream (or the exam's base class). The class-teacher rule and the exam's own `teacher_id` stay.
- A new exam's default marker stays the **lead** teacher of that stream and subject; if there is none, the first teacher link; if there is none, the actor (today's fallback).
- Submission and approval stay per exam, class and subject, with the submitting teacher recorded. If two teachers cover different streams of one class exam, each submits their own stream (this matches the stream marks entry on the integration branch). If two teachers share one stream, the first submit closes the marks for both; the other is told who submitted and that an admin can reopen it.
- Mark edits record the user who made them (the audit already exists). Nothing about release changes: released marks still come only through the release gate.

**Register.**
- No change to the scope rule: it is about classes. A subject teacher with several classes appears in "classes I teach" exactly as now; extra teachers on a subject only widen who counts as teaching a class.
- A per-lesson register by subject is out of scope here.

### Rollout order

1. Expand migration, plus the roster page reading `catalog_id` where present and falling back to today's rows.
2. Backfill command, run on staging, read the disagreement list, then production with a snapshot.
3. New subject form.
4. Redesigned class form, with the diff save.
5. Marks entry authorization change, behind a school setting at first.
6. Unique index on links, once the dedupe report is empty.

### Tests (when it is built)

- Backfill: groups same-name rows inside one school only; never touches another school's rows; leaves disagreeing rows ungrouped; second run changes nothing; `--undo` restores nulls.
- Subject form: five classes create five offerings and one catalog row in one transaction; unticking a class with exams is refused; a teacher id from another school is rejected.
- Class form: saving with no change rewrites no link rows (ids and timestamps unchanged); removing a teacher with an open exam is refused; two teachers on one subject both see the stream in the roster.
- Marks entry: a second teacher on the same stream and subject can enter marks; a teacher with no link cannot; cross-school returns 404.
- Roster page: one row per catalog subject; ungrouped legacy rows still listed.

## Open questions for Rasta

1. **Subject across levels.** Is "Mathematics" one school subject for Primary and Secondary, or two (different codes, different grading)? The backfill groups by name inside a school; a school with `MATH` in primary and `MTC` in secondary would be left ungrouped.
2. **What "several teachers" means.** Co-teaching one class, splitting by stream, splitting by term, or all three? The model handles all, but the form wording and who may enter marks differ.
3. **Who may enter marks** when two teachers share a stream and subject: either, only the lead, or the lead plus anyone the class teacher adds?
4. **Reuse `school_subject`?** It is unused. Replace it with `subject_catalog` (this proposal), or fill it in?
5. **Does a school's current year roll over its offerings?** `subjects.academic_year_id` ties an offering to one year; at year end should the offerings and teacher links be copied forward, or re-created from the catalog?
6. **Is a teacher required on every subject row?** Today yes (validation). This proposal makes it optional so classes can be saved while teachers are hired.
7. **Core or elective per class or per school?** `type` is on the offering today; the catalog also carries one. Which wins when they differ?
8. **Archive versus delete.** Today there are archive, restore and force delete on a subject. Should force delete exist once marks reference a subject?
9. **Periods per week** (`no_of_periods`) live on the link: does that stay per teacher, or per subject and class?
10. **Timing.** This touches the same classes and marks files as the exam sets and base-class work on #1285. Build after Monday's release lands, or start with the Subjects page roster only (step 1)?
