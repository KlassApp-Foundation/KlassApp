# Assign classes to a teacher

Design only. No code changes. Serves **T-098**. Read together with `docs/product/class-teacher-capabilities.md` (who a class teacher is) and `docs/product/subjects-and-teachers.md` (the subject and teacher model this form writes to; a design proposal in its own PR, not yet on `main`).

References are to `main` at `01d87832`. The Monday integration branch (#1285) touches nearby files, so re-check line numbers before building.

## What happens today

1. The Teachers list has a row menu with **Assign classes** (`app/Services/People/PeopleListQuery.php:169`). It is a plain link to `/admin/teacher/show/classes/{name}`.
2. That route is `TeacherShowController@showClasses` (`routes/admin.php:372`, `app/Http/Controllers/Admin/TeacherShowController.php:87`). It finds the teacher in the admin's school by login name, loads the teacher's `Teacherlink` rows and returns them through `App\Http\Resources\TeacherClasses` (`app/Http/Resources/TeacherClasses.php`).
3. So the admin clicks **Assign classes** and the browser shows **raw JSON**: a list of `{standard, section, subject}` for classes the teacher already has. There is nothing to click, nothing to add, no form. (It is a data endpoint left from an older single-page interface, linked from a menu as if it were a screen.)
4. The same data model is written in four other places, none of which starts from a teacher:
   - **Edit class details** on a class (`AcademicProcess::editStandardLink()`, `app/Traits/AcademicProcess.php:267`): sets the stream's class teacher and the class head, and deletes and re-creates every subject-teacher link of that stream (details in the subjects doc).
   - **Class teacher invite** (`ClassTeacherInviteController` and `ClassTeacherInviteService`): from a class, invite or reassign one class teacher by email; new teachers get a setup link, existing teachers get a notice.
   - **Class setup requests** (`/admin/class-setup`, `ClassTeacherSetupController@approve`): a teacher who used the school's staff link asks for classes; the admin approves.
   - **Teacher-links import** (`/admin/teacher-links`, `TeacherLinkImportController`): paste or upload rows of teacher, class, subject.
   - The onboarding wizard and Toshi (`OnboardingEngine`, `ToshiActionService`) also create links during setup.
5. The teacher's own profile (`TeacherProfileService`) shows the classes they have, read-only.

So there are five ways to put a teacher in a class and no way to start from the teacher.

## What the admin needs

On a teacher, one screen answering: **which classes and streams does this person teach, which subjects in each, and are they a class teacher or class head?** With add and remove, saved once.

## Proposal: an Assign classes form

Route: `GET /admin/teachers/{teacher}/classes` (by id, scoped to the admin's school; another school's teacher is a 404) and `PUT` to save. The old `/admin/teacher/show/classes/{name}` URL redirects to it for the list menu and keeps returning its JSON when asked with `Accept: application/json`, so nothing that reads it breaks.

### Layout

Header: the teacher's name and status, a link back to their profile. Below, a repeatable **Class block** for each class the teacher has, plus **Add a class**.

Each block:

| Field | Behaviour |
| --- | --- |
| **Class** | Dropdown of the school's base classes in level order (Baby Class, P.1 ... S.6). Required. |
| **Stream** | Dropdown of that class's streams, loaded when the class changes, with **All streams** (the teacher is not tied to one) and one entry per stream. Hidden when the class has no streams. |
| **Subjects** | Chips picked from the school's subjects offered in that class (from the subject catalog once it exists, otherwise from today's per-class subject rows). "None" is allowed: a class teacher who teaches no subject there. |
| **Role** | Checkboxes: **Class teacher of this stream** and, when the class has more than one stream, **Class head of this class**. |
| **Periods a week** | Optional number per subject, as today. |

Footer: **Save** and **Cancel**. A summary line above Save reads, for example, "Ruth will teach Mathematics and Science in P.5 East and English in P.6, and be class teacher of P.5 East."

Phone: one block per card, dropdowns full width, chips wrap, Save sticky at the bottom (the app's usual pattern below 768 px).

### Rules

- **Class teacher** writes `standards_link.class_teacher_id`; **class head** writes `sections.class_teacher_id`. These are the two columns defined in `class-teacher-capabilities.md`; this form is another writer of them and must go through the same code as Edit class details (one implementation per behaviour, AGENTS.md rule 40), not a copy.
- A stream has one class teacher. If the box is ticked for a stream that already has one, the form says "Daniel is class teacher of P.5 East. Replace him?" and replaces only on confirm; the previous teacher keeps their subject links. This is today's `check_unique_class_teacher` rule turned into a visible choice. The student-leave designation moves with the role, as it does now.
- The class teacher role gives the capabilities in `class-teacher-capabilities.md`. This form does not grant anything else; it shows a one-line reminder of what the role allows ("Can manage students, see the combined mark sheet, and release marks for this stream").
- Class teacher and subject teaching are separate: ticking class teacher does not add subjects, and a subject link does not make someone a class teacher.
- Only teachers (group 5) of the admin's school can be assigned, and only classes of the admin's school and the current academic year. Every id from the form is re-checked against the school on save, never trusted (rules 13 and 18).
- Save is a **diff**: blocks unchanged leave their rows alone; removed subjects soft-delete their link row, refused with a message if the teacher has an open exam or unsubmitted marks for that subject and class; added subjects add rows. It does not delete and rewrite the teacher's links.
- One transaction; on a validation error the form comes back with the admin's choices kept and the failing block marked.

### Where it fits with the other ways in

| Way in | Today | After |
| --- | --- | --- |
| Teachers list, **Assign classes** | JSON page | This form |
| Teacher profile | Read-only classes | Shows classes and an **Assign classes** button |
| Edit class details | Class side, all subjects | Unchanged in purpose: the class side of the same data. It and this form write through one service |
| Class teacher invite | From a class, by email | Unchanged; a new teacher who accepts lands in the Assign classes form's data model |
| Class setup requests | Teacher asks, admin approves | On approve, the admin is offered this form pre-filled from the request |
| Teacher-links import | Bulk | Unchanged; for many teachers at once |

The service behind the form (`TeacherClassAssignment`, new) takes `(school, teacher, blocks)` and is the only place that writes `class_teacher_links` and the two class teacher columns. Edit class details, the invite service and this form all call it. That is the design's main point: today five writers drift apart.

### Tests (when it is built)

- A teacher from another school is a 404; a class id from another school is rejected.
- Saving with no change rewrites no rows.
- Class teacher on a stream that has one: not replaced without the confirm flag; replaced with it; the old teacher keeps their subjects.
- Class head only offered when the class has more than one stream.
- Removing a subject with an open exam is refused with the exam named.
- The old JSON URL still returns JSON for an API client.
- Role capabilities: after assigning, the teacher passes the class-teacher rule used by marks release and the roster.

## Open questions for Rasta

1. **One screen or two?** Should Assign classes be the main way and Edit class details stay as the class-side view, or should Edit class details stop editing teachers and link here?
2. **Several class teachers.** Large classes have a class head and a stream teacher each; can a stream have two class teachers (for example morning and afternoon), or is one per stream a firm rule?
3. **Replacing a class teacher.** Confirm-and-replace as proposed, or block until the admin removes the old one first?
4. **Subjects not offered yet.** If the teacher's subject is not offered in that class, may this form create the offering (it depends on the subject catalog design), or only pick existing ones?
5. **Notifying the teacher.** When an admin assigns classes, should the teacher get an email or WhatsApp notice? The class teacher invite already sends one; the staff-link flow sends a setup link. A silent assignment may surprise a teacher.
6. **Past years.** Should the form show last year's classes read-only, or only the current year?
7. **Teachers without an account yet.** The invite flow creates an account on the spot. Should this form also let the admin type an email for a new teacher, or only pick existing ones?
8. **JSON endpoint.** Is anything (the app or an integration) reading `/admin/teacher/show/classes/{name}` as JSON? The design keeps it working; if nothing does, it can go.
