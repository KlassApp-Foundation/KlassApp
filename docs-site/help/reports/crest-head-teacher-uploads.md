---
title: Crest, head teacher name and uploads
role: admin
draft: PR #1357
---
<Role r="admin" />

# Crest, head teacher name and uploads

The report card can print your school's crest and the head teacher's name. Both are set on one screen, and both have a safe fallback.

## Set them

1. Open **Report cards**, then **Progressive report rules**.
2. Follow the link to **School details for report cards** (`/admin/school-identity`).
3. Type the **Head teacher's name**.
4. Upload a **Crest**: PNG or JPG, up to 2 MB.
5. Press **Save school details**.

The screen says whether a crest is saved, saved but missing (a deploy can lose uploads), or not set.

## What the card does

- With a crest, the card prints it.
- With no crest, the card prints a monogram of the school's initials.
- With no head teacher name, the signature area prints as before.
- A missing crest never blocks a card.

## Where files live, and why a deploy can lose them

- School settings save the crest on the **default** upload disk. The card now reads that same disk first, then the older `public` disk.
- A **local** disk does not survive a deploy, and it is not shared between servers. A file saved there disappears at the next deploy while the school's record still points at it.
- The lasting fix is object storage: the default disk must be the object-storage disk. That is an operator setting on the environment, not something you change on this screen.
- Files already saved locally are not moved. After the switch, upload the crest again on this screen; until then the card shows the initials.

The operator notes, including the environment variable and the bucket, are in `docs/dev/uploads-and-deploys.md`.

## Who can use the screen

A school admin. A head teacher, teacher, student, parent and accountant are turned away.

## What to check after a deploy

Open **School details for report cards** and look at the crest line:

- **Saved** — the file is there.
- **Saved but missing** — the record exists but the file is gone. Upload it again.
- **Not set** — no crest; the card prints the initials.
