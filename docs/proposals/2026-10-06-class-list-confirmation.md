# Proposal: class-list confirmation (not built)

Status: proposal, 2026-10-06. Nothing here is built. The roster portal exists; a
confirmation step does not.

## What the class roster portal does today (verified in-repo)

- **Pages.** `routes/admin.php:173,184` and `routes/teacher.php:15,18` render the
  Livewire components `App\Livewire\ClassRoster\Index` and `App\Livewire\ClassRoster\Show`
  through the thin `class-roster.index` / `class-roster.show` views
  (`resources/views/livewire/class-roster/*.blade.php`).
- **Index** lists the classes a viewer may see for a school and academic year.
  Filters: search, academic year, level, assignment status; 12 per page. The
  visible set comes from `RosterScopeService::visibleSections()` (line 21):
  admins see all active sections of the school; teachers see sections where they
  are the class teacher (`sections.class_teacher_id`) or teach a stream via the
  current-year Teacherlink (`applyTeacherStreamVisibility`).
- **Show** is one class, with an optional stream selection
  (`selectedStreamId`), resolved academic year and an authorization check on
  mount. Students come from `RosterScopeService::studentsForStream()` (line 182);
  per-student access is re-checked by `actorCanAccessStudent()` (line 103). The
  effective class teacher is stream-first via `effectiveClassTeacher()` (line 149).
- **What it does not do today:** the roster lists students but does not show the
  KLS number (`users.registration_number`), has no confirmation state, no flags,
  and no print view.

## Proposal: a "Confirm class list" action

- **Who can confirm.** The class teacher of that class, resolved stream first
  (`effectiveClassTeacher(section, stream)`), falling back to the section's class
  teacher; or any school admin (the roster already treats usergroup 1/3 as
  admins). Subject teachers cannot confirm, even when the scope lets them view
  part of the roster: the confirm control is rendered and enforced only for the
  class teacher and admins.
- **Surface.** On `ClassRoster\Show` for the selected stream (or the section when
  it has no streams): a confirmation panel listing every student with their KLS
  number, marking any student missing one. Each row can be flagged as wrong
  class, duplicate, missing student or missing KLS number. Two outcomes: the list
  is correct (confirm) or flags are submitted for follow-up.
- **Storage.** New table `class_list_confirmations`: `school_id`,
  `academic_year_id`, optional `academic_term_id`, `section_id`, nullable
  `standard_link_id` (stream), `confirmed_by`, `confirmed_at`, `student_count`,
  `flagged_rows` (json: student id plus flag codes), `status`
  (`confirmed` | `flagged`), timestamps. Activity-log entries on confirm and on
  flag submission, scoped to the school.
- **What resets a confirmation.** Adding, moving or removing a student in that
  class. Rather than chasing every write path with events, store a roster hash
  (sorted student ids plus KLS numbers) at confirmation time; the page compares
  the live roster against it and shows "roster changed since confirmation,
  confirm again". Event-based resets can come later as belt and braces.
- **Printable class list.** A print view with the same scope checks: school
  header, class and stream, academic year and term, every student with the KLS
  number, and a footer carrying the confirmation stamp (confirmed by and when).
- **Marks import.** The import preview shows a notice when the class list has
  not been confirmed by the class teacher, but never blocks: the import is
  roster-driven and the notice is informational only.

## Open questions

- Confirm per stream, or per section when a class has several streams? (Proposal:
  stream first, section fallback, one confirmation row either way.)
- Mid-term joins and leaves: after a roster change, who re-confirms (class
  teacher, or the admin who added the student)?
- Do flagged rows need a resolution workflow (who closes a flag), or is history
  enough for now?
- Should parents see the confirmation state on their side? (Not proposed.)
- Retention: keep every confirmation revision, or just the latest per class list.

## Risks

- Concurrent roster edits during confirmation (the hash comparison addresses the
  stale case; a transaction around confirm keeps it honest).
- Notification noise if flags email admins (start in-app and in the activity log
  only).
- Extra writes on class changes are avoided by the hash approach.

## Suggested PR order (smallest first, when picked up)

1. Migration, model, activity logging, service to compute the roster hash (tests).
2. `ClassRoster\Show`: show KLS numbers, add the confirm panel with the
   class-teacher/admin gate (tests; subject teachers denied).
3. Reset-on-change via the roster hash plus the marks-import notice.
4. Printable class list with the confirmation stamp.
5. Flags workflow polish (row flags UI, follow-up list for admins).
