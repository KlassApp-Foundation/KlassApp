# Class teacher flow

Phase 1 of [`marks-flow.md`](marks-flow.md). One service, `ClassTeacherAssignmentService`, assigns the class teacher. The admin class page, the invite form, the wizard, and Toshi call it. Controllers and Blade views render and authorise. They do not decide who the class teacher is.

## Plan

1. On `/admin/classes` the admin picks an existing teacher, invites a new one, or replaces the current one.
2. Each of those asks for confirmation and writes one `class_teacher` log row, scoped by `school_id` and `section_id`.
3. A new teacher gets an email link. The link stores only a SHA-256 hash, works once, and expires 72 hours after it is issued.
4. Replacing the class teacher, or sending a new invite for that class, revokes any pending invite for the class.
5. `/admin/sections` redirects to `/admin/classes`. `/admin/class-setup` still shows this school's class progress and staff-link approvals. The class page links the invite form and the stream edit form.
6. Add-class options are the canonical levels (P.1, P.2, … and S.1–S.6, plus nursery), the school's type preset, and any name it already uses, sorted by level. A school with P.1–P.6 can add P.7. P.10 sorts after P.9, not after P.1.

## Flow

```mermaid
flowchart TD
  page["Admin opens /admin/classes"]
  choice{"Pick, invite, or replace"}
  confirm["Confirms the change"]
  service["ClassTeacherAssignmentService"]
  existing["Existing teacher: both class-teacher columns, email notice, log"]
  invite["New teacher: hashed 72-hour link by email"]
  revoke["Pending invite for that class is revoked"]
  claim["Teacher sets a password once"]

  page --> choice
  choice --> confirm
  confirm --> service
  service -->|existing teacher| existing
  service -->|new email| revoke
  revoke --> invite
  invite --> claim
  service -->|replace| revoke
  existing --> revoke
```

The notice to an existing teacher names who assigned them, which class, and how to raise a mistake. It does not contain a password.

Email is the channel. `staff_invite` is not an approved WhatsApp template, so WhatsApp sending stays off (`invites.staff_whatsapp_enabled`, default false). A test that opens that branch uses a fake. Nothing in this phase calls Meta.

The invite page sends `Referrer-Policy: no-referrer` and a matching meta tag. The raw token is not written to logs, exception reports, or analytics: `InviteTokenScrubber` redacts `/invite/teacher/{token}` and `/invite/co-admin/{token}` before those records are stored. Invite creation and acceptance are rate limited.

The wizard's class-teacher file and Toshi's add-teacher path write the same two columns through this service. Those two paths do not send a second email: the file upload stays silent, and Toshi already sends its own set-password link. Toshi's add-teacher path still stores a random password. That product decision is unchanged.

`ClassesController@store` saves a class when the name is one `namesForSchool()` offers that school (canonical levels, the type preset, and names already in use), the same list as the create form. A name outside that list is a validation error. Saving the stream form with the same class teacher leaves a pending invite in place. The stream form does not write a class-teacher column itself. An id that is not a teacher in the school, or an assignment with nobody signed in, is a validation error.
