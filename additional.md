# KlassApp — Admin Features QA & Refactor

## Objective

Review the existing KlassApp implementation and verify that the affected features work correctly. Fix the underlying issues rather than masking symptoms.

**Important:** Before making any changes, read and follow `knowledge.md`. It is the core reference for the project's architecture, design patterns, conventions, and existing implementation decisions.

Do not invent functionality, business rules, or architecture that is not supported by the existing codebase or `knowledge.md`.

---

# 1. Teacher Login

### Current issue

All teachers are currently assigned the default password:

```text
password
```

However, attempting to log in as a teacher fails.

### Requirements

* Investigate the existing teacher authentication flow.
* Identify the actual root cause of the login failure.
* Check:

  * Teacher account creation
  * Password hashing
  * Authentication guard/provider
  * Login credentials
  * Teacher role/permissions
  * Session/token handling
  * Any middleware protecting the teacher dashboard
* Fix the underlying issue.
* Do not change the authentication architecture unless the existing implementation requires it.
* Verify that an existing teacher can successfully log in using the expected credentials.
* Ensure the fix does not break admin authentication.

---

# 2. Student Upload — `/admin/students`

### Current issue

Student uploads work when the uploaded list contains empty streams.

However, when streams are populated, the upload fails.

### Requirements

* Reproduce the issue.
* Inspect the complete student-import flow.
* Determine why populated stream values cause the import to fail.
* Check:

  * Stream validation
  * Stream/class relationships
  * Import mapping
  * Database constraints
  * Existing stream records
  * Duplicate handling
  * Student promotion/import logic
* Fix the actual root cause.
* Preserve support for students without streams where the current system allows this.
* Verify both cases:

  * Student with an empty stream
  * Student with a populated stream

### Student Upload Template

Restore the **Gender** column to the student upload template.

---

# 3. Plans / Subscriptions Navigation

Make subscription plans easily accessible from the side navigation.

### Requirements

Users should be able to quickly access plans when they want to:

* View available plans
* Switch plans
* Change their subscription

Follow the existing subscription architecture and UI patterns documented in `knowledge.md`.

Do not introduce a new subscription flow if one already exists.

---

# 4. Student Details

Currently, selecting a student displays the student in an editing form.

### Required behavior

When a user taps/selects a student from the student list:

* Show the student's details in a dedicated **view/details interface**.
* Do not immediately present the student as an editing form.
* Editing should remain a separate explicit action.

The details view should follow the existing application's design conventions.

---

# 5. Teachers Navigation

### Requirements

* Add a dedicated **Teachers** link to the side navigation.
* Remove the Teachers link currently located under Students if applicable.
* Teachers should have their own dedicated administration section.

---

# 6. Admin Teachers UI

Redesign `/admin/teachers` to provide a modern, clean, standard administrative experience.

### Requirements

Use a proper paginated table.

The table should display at minimum:

* Teacher photo/avatar
* Name
* Contact

For the teacher photo/avatar:

* Use the same visual treatment/pattern currently used for the teacher dashboard profile/avatar in the upper-right area.
* Reuse existing components/patterns where possible.

### UX requirements

* Clean table layout
* Pagination
* Responsive behavior
* Clear actions
* Consistent spacing and typography
* Consistent with the existing KlassApp design system

Do not introduce a completely unrelated visual style.

---

# 7. Admin Students UI

Redesign the student administration interface using the same modern administrative approach used for the teachers section.

For the student table, display:

| Field  |
| ------ |
| ID     |
| Name   |
| Class  |
| Stream |
| Gender |

### Requirements

* Use a clean, modern table.
* Add pagination where appropriate.
* Maintain existing student actions.
* Selecting a student should open the dedicated student details view described above.
* Editing should be an explicit action.
* Follow the project's existing UI patterns and `knowledge.md`.

---

# 8. Report Card Full Preview

### Current issue

When the user selects **Open Full Preview**, the report card does not open as a proper full report-card template.

### Requirements

Refactor the full-preview behavior.

When the user selects **Open Full Preview**:

* Open the preview in a **new browser tab**.
* Display the complete report-card template.
* The new page should provide the full report-card layout rather than the current partial/embedded presentation.
* Preserve the existing report-card design and data.
* Do not create a separate unrelated template.

Verify the preview at normal desktop browser dimensions and ensure the complete report card is visible.

---

# 9. Admin Health

### Current issue

The `/admin/health` navigation currently points to `/admin/students`.

### Requirements

* Correct the Health navigation URL.
* Ensure it points to the actual Health administration page.
* Verify the route exists and loads the correct component/page.
* Redesign the Health UI to look modern and consistent with the rest of the admin dashboard.
* Preserve existing Health functionality.

Do not redirect Health to Students.

---

# 10. Admin Messages

### Current issue

There is currently a **Message Students** option under `/admin/messages`.

### Requirements

Remove **Message Students**.

Replace it with:

> **Message Parents**

The implementation should follow the existing messaging architecture.

Do not create an entirely separate messaging system if the existing messaging infrastructure can support parent messaging.

---

# 11. Admin Events — Full CRUD

Refactor `/admin/events` so administrators have complete CRUD functionality.

### Required operations

Administrators should be able to:

* View events
* Create events
* Edit events
* Delete events

Follow the project's existing CRUD patterns and authorization rules.

---

# 12. Automatically Create Ugandan Public Holidays

When a school is created, the system should automatically create the recurring Ugandan public holidays for that school's applicable year.

Create this through an appropriate service following the architecture documented in `knowledge.md`.

### Fixed-date Ugandan public holidays

```text
01-01  New Year's Day
01-26  Liberation Day
02-16  Archbishop Janani Luwum Day
03-08  International Women's Day
05-01  Labour Day
06-03  Uganda Martyrs' Day
06-09  National Heroes' Day
10-09  Independence Day
12-25  Christmas Day
12-26  Boxing Day
```

### Requirements

* Do not hard-code holiday records directly inside the school creation controller.
* Use a dedicated service/domain layer consistent with the project's architecture.
* Holidays should be associated with the newly created school.
* Ensure the operation is safe against duplicate holiday creation.
* Use the appropriate academic/calendar year already established by the application.
* Do not invent additional holidays.
* Variable-date holidays such as Easter and Eid are outside this specific fixed-date requirement unless the existing application already has a supported mechanism for them.

---

# 13. Terms Navigation

Restore the **Terms** navigation link in the sidebar.

Administrators should be able to access the Terms section to:

* Create terms
* Edit terms
* Delete terms
* View terms

Follow the existing Terms implementation and authorization rules.

---

# Implementation Constraints

## 1. Read `knowledge.md` first

Before modifying code:

```text
Read knowledge.md
```

Use it as the primary reference for:

* Architecture
* Design patterns
* Naming conventions
* Service patterns
* Authentication
* Authorization
* UI conventions
* API conventions
* Database patterns
* Existing reusable components

---

## 2. Do not hallucinate

Do not invent:

* Routes
* Database columns
* Models
* Services
* APIs
* Components
* Business rules
* Permissions
* Relationships

If something required by this brief does not exist, inspect the existing implementation and determine the smallest change necessary to support it.

---

## 3. Preserve existing functionality

Do not rewrite working features unnecessarily.

Prefer:

```text
Investigate
    ↓
Identify root cause
    ↓
Make minimal targeted change
    ↓
Test
    ↓
Refactor only where necessary
```

---

## 4. Reuse existing patterns

Before creating a new:

* Service
* Component
* Table
* Modal
* Form
* Validation rule
* API endpoint
* Authorization rule

check whether an equivalent pattern already exists elsewhere in KlassApp.

---

# Verification Checklist

After implementation, verify all of the following:

* [ ] Teacher can log in using the configured/default teacher credentials.
* [ ] Admin login still works.
* [ ] Student upload works with an empty stream.
* [ ] Student upload works with a populated stream.
* [ ] Gender exists in the student upload template.
* [ ] Plans are accessible from the sidebar.
* [ ] Student selection opens a details view rather than an edit form.
* [ ] Teachers have a dedicated sidebar navigation link.
* [ ] Teachers are removed from the Students navigation where applicable.
* [ ] Teacher administration uses a modern paginated table.
* [ ] Teacher avatar/photo follows the existing dashboard avatar pattern.
* [ ] Student administration uses a modern table.
* [ ] Student table displays ID, Name, Class, Stream, and Gender.
* [ ] Report-card full preview opens in a new browser tab.
* [ ] Full report-card template is displayed correctly.
* [ ] Admin Health points to the correct route.
* [ ] Admin Health UI is modernized.
* [ ] Message Students has been removed.
* [ ] Message Parents is available instead.
* [ ] Admin Events supports full CRUD.
* [ ] Creating a school creates the required recurring Ugandan public holidays.
* [ ] Holiday creation does not create duplicates.
* [ ] Terms navigation is available again.
* [ ] Terms CRUD remains functional.
* [ ] Existing functionality has not been unnecessarily broken.

---

# Final Rule

Do not mark a feature as fixed merely because the code compiles.

For each issue:

1. Reproduce the problem.
2. Identify the root cause.
3. Fix it.
4. Test the affected flow.
5. Check for regressions.
6. Follow `knowledge.md` and existing KlassApp patterns throughout.

