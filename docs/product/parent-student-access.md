# Safe first sign-in for imported parents and students

Design only. No code changes. Serves **T-094**. References are to `main` at `01d87832`.

## The problem

Parents and students who arrive through an import (onboarding upload, admissions, Toshi, the wizard) are created with a random password that nobody sees (`UserProvisioning::randomPasswordAttributes()`, `app/Support/UserProvisioning.php`: `Str::random(16)`, `is_reset = 1`). That is the right default: no shared or guessable password. But there is no safe way in afterwards for most of them:

- **The only recovery path is an email code.** `ForgotPasswordController` and `PasswordResetCodeController` (`routes/web.php:304-309`) look the user up by `email`, send a 6-digit code (valid 5 minutes) to that address, then allow a new password. Nothing in the reset path uses a phone number or WhatsApp.
- **Many imported people have no real email.** When a student or parent row has no email, the importer invents one: `allocateGeneratedEmail()` (`app/Services/OnboardingEngine.php:2638`) makes `student.xxxxxxxx@<school-slug>.local` or `parent.xxxxxxxx@...local`. The reset code goes to an address that cannot receive mail, so those people cannot sign in at all.
- **The force-change page cannot be used.** `ForceChangePasswordController` (`routes/web.php`, "Force password change") asks for the *current password* before accepting a new one. An imported account's current password is unknown to everyone, including the person.
- **Login accepts several identifiers** (`resolveLoginUser()` in `app/Traits/AuthenticatesUsers.php:232`: email, KLS/registration number, name, phone), but all of them still need a password the person does not have.
- **Teachers already have a better path.** `TeacherInviteLinkService` issues a one-time link (64 random characters, stored as a SHA-256 hash, single use, expires after `invites.expiry_hours`, default 72, `config/invites.php`), sent by email and WhatsApp, and the teacher chooses a password on claim. `ClassTeacherInviteService` and `CoAdminInviteLinkService` follow the same pattern. Parents and students have nothing equivalent, and the teacher invite creates a *new* user on claim rather than activating an *existing* imported one.

So today an imported parent or student can sign in only if the school gave a real email, and then only through the reset code.

## What a safe first sign-in needs

1. The person proves they hold a channel the school recorded (an inbox, or a WhatsApp number that Meta reports as the sender), without the school ever seeing or sending a password.
2. The proof is single-use, short-lived and tied to **one** account in **one** school.
3. The person chooses their own password.
4. A wrong parent cannot get in as someone else's parent. For parents this leans on the linking rules in `docs/product/parent-child-linking.md`.
5. Nothing is stored in plain text, and nothing about the account is revealed to a stranger.

## Proposal

### 1. One invite for an existing account (the core)

A new `AccountInviteLinkService`, modelled on `TeacherInviteLinkService` but for a user who already exists:

- `issue(User $user, string $channel)` creates a token (64 random characters), stores only `token_hash` (SHA-256), `user_id`, `school_id`, `channel`, `expires_at`, `claimed_at`. The raw token is returned once to the sender and never logged. Reissue replaces the previous token.
- Link: `/invite/account/{token}`. `GET` shows a page naming the school (not the child); `POST` sets the password and signs the person in. Opening the link (for example a WhatsApp or email preview) must not consume it, only the `POST`.
- On claim: set the password, `is_reset = 0`, mark the invite claimed, record how and when. A claimed or expired link shows one neutral message ("This link is no longer valid. Ask the school for a new one.").
- Lookup always by `user_id` and `school_id`, never by name (standing rule 18). Expiry follows the existing configuration pattern; suggested 72 hours for email, 7 days for hand-delivered links.

### 2. Three delivery channels

| Channel | Used when | How the person is proven |
| --- | --- | --- |
| **Email** | The account has a real email (not a generated `.local` one) | Control of the inbox |
| **WhatsApp** | The account's phone is known and the number has a linked `WhatsAppUser` (or a template message to a new number is allowed) | The number Meta reports as the sender or recipient |
| **Admin hand-over** | Neither works, or the school prefers it | An admin or class teacher shows or prints the link or QR from the profile page; the person claims it in front of them. Single use, 7 days |

A parent whose number the school holds but who has not messaged the school yet gets a WhatsApp invite template (needs Meta approval). The link contains no child name.

### 3. Parents without email: first contact on WhatsApp

For a parent with no real email, WhatsApp is the front door:

1. The parent messages the school's WhatsApp number (or taps the "Link on WhatsApp" button from `parent-child-linking.md`). Meta supplies the verified number.
2. The existing link flow runs (`ParentLinkRequest`, then admin approval or the two-factor confirm).
3. Once linked, the bot offers: "Want to see fees and results on the web too? Tap to set up sign-in." The link is an account invite (channel `whatsapp`) for **that** parent user, bound to the same number.
4. The parent chooses a password on the web page. After that the normal login works, and password recovery can use WhatsApp (see 5).

This reuses the verification the parent already passed, and adds no new way for a stranger to get an account.

### 4. Students

Students are different: many are children, and many schools will not want a child's own login (Baby Class to P.3 in particular).

- **Default: no student sign-in is sent.** The student account exists, owned by the school.
- **Opt in per class or school:** upper classes (for example P.4 and above) can get a link, delivered by the class teacher (printed slip or QR), or to the linked parent's WhatsApp, never to a number or email the student typed. A parent can ask for it for their child.
- A student invite uses the same service, `channel = handover` or `whatsapp-to-parent`, and expires faster.

### 5. Recovery after first sign-in (follow-on)

Add a WhatsApp reset code beside the email code for accounts whose email is a placeholder: a parent messages "RESET" from the linked number; the bot sends a one-time link (not a code to type in a form). The email path stays for accounts with a real address. Both reuse the `AccountInviteLinkService` token, with `purpose = reset`.

### 6. Fix the placeholder emails

The generated `...@<slug>.local` addresses make every "email" feature misleading. Options, smallest first: (a) keep them but have the reset form, the email senders and the profile page recognise the `.local` suffix and say "no email on file, use WhatsApp or ask the school"; (b) store a real `email_is_placeholder` flag on `users` and use that instead of matching the suffix; (c) allow `email` to be null for parents and students. (a) needs no migration and is the first step.

### 7. Fix the force-change page

For an invited first sign-in the "current password" box makes no sense. When the session came from an invite claim, skip it (the claim already proved the person). Keep it for the normal "admin set a temporary password" case.

## Safety rules

- Tokens: 64 random characters, SHA-256 at rest, single use, expiry, never logged or put in a URL query that analytics could capture; reissue invalidates the old token.
- Rate limits on the claim `POST` and on issuing (per account and per phone/email), with a neutral message that does not say whether the account exists.
- Every invite is scoped by `school_id`; a token from one school never signs in to another. A parent who spans schools gets one invite per school or a clear choice on claim (open question 5).
- A link grants only a password choice and a session for that one user, with the usual permissions. Marks still come only through the release gate.
- The school sees "invite sent / opened / claimed / expired" per person (status only, never the token), and can reissue or revoke.
- Placeholder addresses never receive mail.
- Nothing is sent to a student's own number or email unless the school enabled it (consistent with the "off by default" rule for new behaviour).

## Where it shows up

- Admin: parent and student profile pages get an "Account access" card (status, Send invite by email/WhatsApp, Copy link, Reissue, Revoke), and the import summary shows how many imported people still need an invite and by which channel.
- Onboarding: after an import, "Invite parents" and "Invite students" are optional next steps with a count of who can be reached by email, WhatsApp or only by hand-over.
- Parent portal: first screen after claim goes to the dashboard (`routes/parent.php`).

## Tests (when built)

- Claim sets the password, clears `is_reset`, and the link cannot be used twice.
- Expired, revoked and reissued tokens are rejected with the same message.
- A `GET` on the link does not consume it.
- A token for school A never signs in anyone at school B; lookups are by id.
- A generated `.local` email never receives mail or a reset code.
- WhatsApp channel: an invite is only created for a number linked to that parent.
- Rate limits return the same response for existing and unknown accounts.
- Force-change skips "current password" only after an invite claim.

## Suggested order

1. `AccountInviteLinkService` with admin hand-over and email (smallest working path, solves the real-email case and the school-in-the-room case).
2. Placeholder-email recognition and the force-change fix.
3. WhatsApp channel for linked parents (depends on `parent-child-linking.md`).
4. Student opt-in and the import summary.
5. WhatsApp recovery.

## Open questions for Rasta

1. **Students:** should students ever get their own sign-in, and from which class up? Who may receive the link (class teacher slip, parent's WhatsApp)?
2. **Placeholder emails:** keep generated `.local` addresses (with detection), add a flag, or allow no email for parents and students?
3. **Hand-over links:** are printed or on-screen links acceptable for schools whose parents have no email and are not yet on WhatsApp, and how long should they last?
4. **WhatsApp templates:** are you happy to submit an invite template for Meta approval, and is the wording "Hello {name}, {school} uses KlassApp..." acceptable?
5. **Parents in two schools:** one account with one password across schools (as the linking design assumes for the phone), or a separate sign-in per school?
6. **Expiry:** 72 hours for email and 7 days for hand-over, or the same everywhere?
7. **Reset by WhatsApp:** acceptable to let a linked WhatsApp number reset the web password, given that a lost phone then means a lost account?
8. **Existing imported accounts:** invite everyone now, or only when a school asks?
