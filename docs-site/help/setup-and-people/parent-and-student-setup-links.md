---
title: Setup links for parents and students
role: admin
---
<Role r="admin" />

# Setup links for parents and students

When the school imports parents or students, KlassApp creates their accounts. Nobody is given a password. Each person gets a one-time setup link by email and chooses their own password.

## How the link reaches them

- **Parent or student with a real email:** the import emails a setup link. The link opens `/invite/account/{token}` and asks them to set a password.
- **Email the app generated or a local test address:** nothing is sent. The person can still sign in with the WhatsApp login for their number.
- **No email at all:** the WhatsApp login is the way in.

## What the person sees

1. They open the link.
2. The page shows the school and says they are setting up **as a parent** or **as a student**.
3. They choose a password and press **Set password**. The password needs at least 8 characters, one lowercase letter, one uppercase letter and one number, typed twice.
4. They are sent to the sign-in page with a confirmation. They sign in with that email and password.

A link is single use and expires. An expired, replaced or already-used link shows a plain message and sends them back to sign in. Ask the school to send a new one.

## Send or resend a link

1. Open the student's profile, or the parent's profile.
2. Press **Resend setup link**.

The previous link stops working when a new one is sent. The same button is on a teacher's profile; staff setup links are the same flow.

## If the person says the link does not work

- **"Expired"** — send a new link from the profile.
- **"Already used"** — they have a password. Send them to the normal sign-in; if they forgot it, use the password reset.
- **"Replaced"** — a newer link exists. Use the latest email only.
- **"This email cannot be used"** — the email belongs to someone at another school. Ask the school for a different email address; the page does not name the other school.

## What the school should tell families

- The link comes by email, not WhatsApp.
- Open it once, choose a password, and keep it.
- If it expires, ask the school office for another.
