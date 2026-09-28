Refactor plan

Goal

-   Move the stream field from the Section model to the standards_link model.
-   Keep the system design consistent with the project rules in knowledge.md.
-   Keep the teacher UX and backend changes, while reverting unrelated admin/backend work to match the main branch.

Scope

-   Refactor from database layer to presentation layer: migrations, models, controllers, requests, services, seeders, factories, views, and any related logic.
-   Check the landing page 500 error and fix only the root cause tied to the refactor or current branch changes.
-   Compare admin dashboard, backend, and migrations against main and keep only the teacher-related changes needed.

Execution plan

1. Audit current state

-   Identify every place where stream exists on Section.
-   Trace every query, relationship, form, and UI that reads or writes stream.
-   Confirm the current branch behavior before changing anything.

2. Database and migration refactor

-   Move stream ownership to the standards_link model.
-   Update the relevant migration(s), foreign keys, indexes, and data migration logic.
-   Preserve existing data safely and avoid destructive changes.
-   Verify the schema and actual records afterward.

3. Model and relationship updates

-   Remove stream logic from the Section model.
-   Add/adjust the standards_link model fields, relationships, scopes, casts, and validation logic.
-   Ensure every query uses the correct ownership and the correct tenant scoping.

4. Controller and service changes

-   Update all controller/service logic to read and write stream through standards_link.
-   Check for section_id vs standard_id confusion and avoid ambiguous matching.
-   Update business rules and request validation accordingly.

5. Seeder, factory, and data fixture updates

-   Update seeders and factories to populate the stream on standards_link.
-   Ensure seeded data reflects the new model relationship.
-   Review all demo/test data paths for consistency.

6. View and UI updates

-   Update all affected Blade views, filters, forms, and display logic to use the new stream location.
-   Keep the teacher UX design and backend behavior intact.
-   Revert any non-teacher UI or backend changes that differ from main without justification.

7. Admin revert check

-   Check out main and compare admin dashboard, backend, and migration code against the current branch.
-   Keep only the teacher-related work that is intentionally needed.
-   Revert any other drift so the branch matches the intended system design.

8. Validation

-   Run the relevant checks for the refactor area.
-   Confirm the landing page no longer throws the 500 error.
-   Review the final diff to make sure it stays narrow and aligned with the plan.
-   Follow knowledge.md and do not hallucinate.

Notes
    
-   Maintain the existing system design rather than inventing a new pattern.
-   Do not delete or alter unrelated work without a clear reason.
-   The main goal is a clean stream migration and a safe revert of admin drift to main.
