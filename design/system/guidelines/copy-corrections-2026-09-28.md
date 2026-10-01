# Production copy corrections — proposed, not applied

**Source:** `klassapp-mail-and-sms-templates-export.md` (production, SELECT-only, 2026-09-28).
**Status:** nothing here has been written to any database. Apply by hand after review.
**Rules followed:** every `:placeholder` is kept exactly as it is, and only the leftover text changes. The export doesn't name the body column, so the SQL uses `<body_col>`. Replace it with the real column name before running.

## Required (the three you listed)

### C1 · Email `event_reminder` (mailtemplates id 9): the "church" leftover
| | Text |
|---|---|
| Now | `New event has been posted for this church.<br><br>` |
| Proposed | `A new event has been posted for :school_name.<br><br>` |

`:school_name` is already used in this row (`School Name - :school_name`), so no new placeholder is introduced.
```sql
UPDATE mailtemplates SET <body_col> = REPLACE(<body_col>, 'New event has been posted for this church.', 'A new event has been posted for :school_name.') WHERE id = 9;
```

### C2 · SMS `Event` (sms_templates id 1): church app name and third-party URL
| | Text | Length |
|---|---|---|
| Now | `Hi.. Your event has been scheduled on :date at :location. For more details log in to church social App. https://churchcms.appsexpress.net` | 139 + placeholders |
| Proposed | `Hi, your event is scheduled for :date at :location. Log in to KlassApp for the details.` | 87 + placeholders |

The third-party URL is removed completely. There's no KlassApp URL placeholder for this row, and I haven't assumed the sender passes `:url`. If it does, append ` :url` (placeholders would still fit comfortably inside one 160-character SMS).
```sql
UPDATE sms_templates SET <body_col> = 'Hi, your event is scheduled for :date at :location. Log in to KlassApp for the details.' WHERE id = 1;
```
**Seeder gap:** the SMS seeder has no Event row. Add the proposed text there as well, so a fresh install doesn't bring back the church copy.

### C3 · Email `login` (mailtemplates id 1): the `Don"t` typo
| | Text |
|---|---|
| Now | `Don"t recognize this activity? <br>` |
| Proposed (minimal) | `Don't recognize this activity? <br>` |
```sql
UPDATE mailtemplates SET <body_col> = REPLACE(<body_col>, 'Don"t', 'Don''t') WHERE id = 1;
```
Also fix the legacy source/seeder so the typo doesn't come back.

## Optional — found while reading the export (your call)

- **O1 · Login alert wording (id 1).** "Please change password for your email immediately" tells people to change their *email* password, not their KlassApp one. Proposed body:
  `Hi :name, <br>You just signed in to KlassApp. <br>Wasn't you? Reset your KlassApp password now and let your school admin know. <br>Thanks & Regards <br>Administration Team <br>`
- **O2 · Subjects that read as internal labels.** `Logged In` → `New sign-in to KlassApp`; `Admission Confirmation Mail` → `Admission approved`; `Site Expired Mail` → `Your KlassApp subscription ends soon`; `New Expired Alert` → `A school's subscription has expired`; `Send Mail to User` → `New message from your school`. All of these are 38 characters or fewer, so they fit mobile previews.
- **O3 · Sign-off.** All 40 rows end with "Administration Team". Mails a school sends to parents would read better signed `:school_name`, but only where the sender fills that placeholder. Check each row's Mailable before changing it.
- **O4 · Inline `#008CBA` buttons** (ids 3, 7, 11): **no data change needed.** The new shell restyles them in code (`MailContent::normalise`) to #15803D with a 48px height. They were 3.85:1, and they now pass.
- **O5 · `admission_confirmation` (email id 15 and SMS id 25).** "Hi Sir/Madam … for the admission in :school_name" could become "Hello, <br>Application :application_no to :school_name has been approved."
