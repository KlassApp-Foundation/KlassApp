---
title: Make a term current and link a class
role: admin
draft: "Draft until #1356 merges"
---
<Role r="admin" />

# Make a term current and link a class

Two setup tasks that used to need a database write now have a screen with a confirmation, plus a command for operators. Both are for school admins only.

## Make this the current term

1. Open **Academic Terms** (`/admin/academic-term`).
2. The current term is marked **Current term**. Every other term has a button, **Make this the current term**.
3. Press it. The confirmation names what changes, for example "First Term stops being the current term and becomes a past term".
4. Tick **Yes, make this the current term**.
5. Press **Make this the current term**.

A term that is already current says so and changes nothing. A term that starts earlier than the current one moves the old one to past; a later term moves it to next.

## Link a class

1. Open **Classes** (`/admin/classes`).
2. If any sections have no link to a class for the current academic year, a yellow box lists them.
3. Press **Link to a class** next to the section.
4. Choose the **Class**, then press **Continue**.
5. Read "Link SECTION to CLASS for YEAR?" and tick **Yes, link this section**.
6. Press **Link section to class**.

Linking twice does nothing the second time.

## If you run the server

Both actions have a command. Without `--apply` they print what would change and write nothing.

```bash
php artisan terms:set-current {school} {term} [--apply]
php artisan classes:link-section {school} {section} {standard} [--year=] [--apply]
```

With `--apply` they write and add one log line (`setup.current-term`, `setup.link-section`) with the school, the ids, the actor and `via`. The screens write the same line plus an activity-log entry under `setup`.

Before any write: take a verified backup, run the dry run, and have the person who owns the deployment approve it.

## Safety

Every lookup is scoped to your school. A term, section, class level or year from another school gets the same answer as a missing one, and nothing changes.
