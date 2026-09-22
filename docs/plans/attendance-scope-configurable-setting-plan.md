# Configurable teacher attendance scope: decision record

**Status:** decided, **not implemented**. This document exists so the decision survives independently of the code.
**Date:** 2026-09-22
**Related:** PR #788 (external contributor, review only), PRs #712 / #713 (per-school access switches pattern)

## Why this record exists

An external PR (#788) changed teacher attendance authorization from "class teacher of this class" to "any active class in the school". The direction of travel was reasonable: schools genuinely expect a teacher to record attendance for the classes they teach, not only their homeroom class. But the change shipped as a silent new default, deleted the comment documenting the old scope, and performed no relationship check at all. This record decides what the correct behaviour is, and in what order it should be built.

## Finding 1: the naming trap

**`class_teacher_links` is the subject-teacher assignment table. It is not the homeroom designation.**

The table is reached through the `Teacherlink` model (`app/Models/Teacherlink.php`), which sets `protected $table = 'class_teacher_links'`. The name is misleading and has already caused confusion in this area.

| Concept | Where it actually lives |
|---|---|
| Homeroom / designated class teacher | `standards_link.class_teacher_id` (and `sections.class_teacher_id`) |
| Subject-teacher-class assignment | `class_teacher_links` via the `Teacherlink` model |

`class_teacher_links` columns: `school_id`, `academic_year_id`, `standardLink_id`, `subject_id`, `teacher_id`. The model uses `SoftDeletes` and eager-loads `subject`, `teacher` and `standardLink`.

Anyone touching attendance authorization should read this table first and not assume the name describes the homeroom role.

## Finding 2: the real data model already supports this

`SiteHelper::getStandardSubjectList($school_id, $teacher_id)` (around line 388 of `app/Helpers/SiteHelper.php`) already answers "which classes does this teacher teach" by **unioning both relationships**:

1. `StandardLink` rows where `class_teacher_id = $teacher_id` (homeroom), then
2. `Teacherlink` rows where `teacher_id = $teacher_id`, plucked to their `standardLink_id` (subject assignments).

Both legs are scoped to `school_id` and the current `academic_year_id`.

`Teacherlink` has **122 usages** across the app: timetables, lesson plans, dashboards, noticeboards, subject listings, admin standards-link details, and `TeacherLinkImportController` which already provides the admin import path for these assignments.

So the relationship is **load-bearing infrastructure**, not an unused table. The desired default is already computable with existing code.

### Measured caveats

- Locally, `class_teacher_links` holds **0 rows** while all 200 `standards_link` rows have `class_teacher_id` set. Real subject-assignment data exists only where it has been imported (see the `2026_08_12_164737_import_teacher_subject_assignments` migration, which seeds a real school's assignments).
- Consequence: a strict "subject-teacher only" scope would give teachers **zero** attendance access in any school that has not imported assignments. This drives the union design below.
- `users.teacher_designations` exists as a column but is unused (0 populated locally) and is not a viable source for this.

## Decision: three configurable values

| Value | Meaning | Notes |
|---|---|---|
| `class_teacher_only` | Homeroom classes only | The currently shipped scope, retained for conservative schools |
| `classes_i_teach` | Homeroom **union** subject assignments | **The default.** Correct semantics, and safe for schools with no imported assignments |
| `school_wide` | Any active class in the school | Explicit opt-in only, never a default |

The union in `classes_i_teach` is the key design move. It makes "subject teacher of that class" the default **without** breaking schools whose assignment data is not yet imported, because the homeroom leg keeps them working. It is also exactly what `getStandardSubjectList()` already computes, so the list UI and the authorization check agree by construction.

## Where the setting lives

Stored as `school_details` meta with key `attendance_scope`, the same pattern established for the per-school access switches (`login_status`, `maintenance`) in PR #712.

- The SchoolAdmin of each school controls the setting for **their own school only**, matching every other per-school setting.
- SiteAdmin can set it for any school via `php artisan school:access`.
- Usergroup 1 is exempt from being locked out by these switches, as with the existing access switches.

## Resolution rules (fail-safe)

- A missing row or an unrecognised value resolves to `classes_i_teach`. **Never** to `school_wide`.
- The resolved scope is always read from `Auth::user()->school_id`, never from request input.
- The resolved scope is cached per school and forgotten on write, following the #789 lesson about never serving a stale or empty cached setting.

## Authorization surface

One new `SiteHelper` method, used by **both** the web and API attendance paths so they cannot diverge:

```php
SiteHelper::canTeacherRecordAttendance(int $school_id, int $teacher_id, int $standardLink_id): bool
```

Behaviour by resolved scope:

- `class_teacher_only`: `isClassTeacherOfStandardLink(...)`
- `classes_i_teach`: homeroom check, **or** a `Teacherlink` row matching `school_id` + `academic_year_id` + `teacher_id` + `standardLink_id`
- `school_wide`: an active `StandardLink` (`status = 1`) in the same school and academic year

Every branch is tenant-scoped by `school_id` taken from the authenticated user.

### Why the web and API paths matter

The teacher API attendance index and the web request currently share the class-teacher filter, and a comment in `SiteHelper::getClassTeacherStandardLinks()` records that ("Same filter the teacher API attendance index uses"). PR #788 deleted an equivalent comment. Routing both paths through one shared method keeps them consistent and makes that comment unnecessary.

## Decisions confirmed

1. **`classes_i_teach` is the default**, even though it is a real behaviour change. It arguably fixes an existing bug: today a teacher cannot record attendance for a class they genuinely teach but do not homeroom.
2. **The `status = 1` filter stays** as a data-integrity safeguard. Inactive classes should not accept attendance, in any scope.
3. **Each school's own SchoolAdmin controls the setting** for their own school, consistent with the per-school pattern used everywhere else.

## Settings hub placement

A new row in the settings hub, in the **Academics** group, presented as a radio choice of the three values with plain-language descriptions. This sits alongside the existing access-switch card rather than introducing a new surface.

## Relationship to PR #788

PR #788's authorization rewrite is functionally the `school_wide` mode. Rather than being discarded, it becomes **one legitimate mode, off by default**.

What was actually wrong with it, and is fixed here:

- No relationship check at all: it widened access without consulting any teacher-to-class relationship.
- It deleted the comment that documented the narrower scope, removing the signal that a decision had been made.
- It would have silently become the default for every school.

Honouring the intent while correcting the method is the point of this record.

## Implementation outline (not yet built)

1. Add `SiteHelper::canTeacherRecordAttendance()` implementing the three branches.
2. Make `AttendanceAddRequest::authorize()` and the teacher API attendance index call it.
3. Make the teacher attendance list UI use the same resolved scope (the union is available from `getStandardSubjectList()`).
4. Add the settings hub card under Academics, with SchoolAdmin write access to their own school.
5. Extend the existing access-switch command path if SiteAdmin control is wanted via CLI.

## Verification plan

- Feature tests for each of the three scopes, including a teacher who teaches a class by subject assignment but is not its homeroom teacher, and a teacher from another school (must be refused).
- A missing or unknown `attendance_scope` value must resolve to `classes_i_teach`.
- An inactive class must be refused in every scope.
- Real browser check on the teacher attendance flow, local first, then staging.
