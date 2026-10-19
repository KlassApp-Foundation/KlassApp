# Early-adopter invite — production runbook

Run this **only after** the early-adopter invite PR is merged and deployed, and after `platform:create-admin` is deployed. **There is no staging environment; this production test-school run is the verification.** Keep `SIGNUP_ENABLED=false` throughout. The invite route is the only sign-up path.

Use two test inboxes you control and two test schools named exactly:

- `E2E Invite Wizard 2026-10-19`
- `E2E Invite Toshi 2026-10-19`

The `E2E ...` name and `is_test=1` flag are required by `test:purge-schools` at the end.

## 0. Pre-flight (read-only)

1. Confirm the deployed commit includes `school_invites`, `/invite/school/{token}`, and `/onboarding/choose`.
2. Open `https://klassapp.xyz/register` in a private window. It must show the **early access** page, not the registration form.
3. Confirm the queue worker is running (invite email is queued) and `MAIL_MAILER` can send from `connect@klassapp.xyz`.
4. Confirm at least one active `plans` row exists (Freemium/Growth/Premium).

## 1. Create the platform admin

In the Laravel Cloud command console, run:

```bash
php artisan platform:create-admin you@klassapp.xyz "Your Name"
```

Copy the one-time password **once**. Sign in at `https://klassapp.xyz/login`; the app forces a password change. Set a new password and keep it in your password manager.

If the command says the email already exists, stop and use that existing platform account instead.

## 2. Submit two early-access requests

In a private window, open `https://klassapp.xyz/early-access` twice and submit:

| Field | Request A | Request B |
|---|---|---|
| School name | `E2E Invite Wizard 2026-10-19` | `E2E Invite Toshi 2026-10-19` |
| Contact name | Your name | Your name |
| Role | Head Teacher | Head Teacher |
| Email | `you+wizard@yourdomain` | `you+toshi@yourdomain` |
| Phone | Your WhatsApp number | Your WhatsApp number |
| Country | Uganda | Uganda |
| Students | `120` | `120` |
| Consent | Tick | Tick |

## 3. Approve and invite both requests

1. Sign in as the platform admin.
2. Open `https://klassapp.xyz/platform/early-access`.
3. Find request A, click **Invite**. The row must change to **Invited · expires …**.
4. Repeat for request B.
5. Open both inboxes. Each email comes from `connect@klassapp.xyz` and contains one `Set up your school` link. If an email is missing, click **Resend** in the same row; do not create a second invite.

## 4. Claim invite A and complete the Wizard

1. Open the email link for request A. The page must show `E2E Invite Wizard 2026-10-19` and a fixed email address.
2. Fill your name, WhatsApp phone, password (at least 8 characters), confirm, and submit.
3. On **How would you like to set up …?**, click **Set up step by step**.
4. Complete the Wizard:

| Step | Test value |
|---|---|
| School name | Keep `E2E Invite Wizard 2026-10-19` |
| Approximate school size | `Up to 500` |
| Country | Uganda |
| Board / curriculum | UNEB |
| School category | Primary |
| EMIS / Ministry code | `TEST-EMIS-001` |
| UNEB centre number | `U0001` |
| Academic year | Current year, defaults accepted |
| Structure & Class Teachers | Add `P.1`, one stream `A`; class-teacher invite optional |
| Subjects | `Mathematics` |
| Teachers | Skip (optional) |
| Students | Add one: `Test Student`, class `P.1`, stream `A`, parent phone = your number |
| Academic terms | `Term 1`, mark current |
| Fee structures | `Tuition`, `100000`, whole school, `Term 1` |
| WhatsApp verification | Your WhatsApp number; enter the OTP shown in the chat |
| Plan selection | `Freemium` |
| Review | Check the summary, then **Confirm & finish** |

5. You should land on `/admin/dashboard` with the setup complete. Note the school id from the URL or the platform list.

## 5. Claim invite B and complete Toshi onboarding

1. Open the email link for request B and claim it with a different admin email.
2. On the choice screen, click **Set up by chatting with Toshi**.
3. You land on `/admin/dashboard` with `toshi_mode=onboarding`. Click **Set up with Toshi** if the panel is collapsed.
4. Answer Toshi's prompts in order, using the same test values as the Wizard (school name, size, country, curriculum, category, EMIS, UNEB centre, academic year, P.1/A, Mathematics, skip teachers, one student, Term 1, Tuition 100000, WhatsApp OTP, Freemium).
5. Finish the chat. Toshi should confirm the school is set up. Note the second school id.

## 6. Verify (read-only)

In the Laravel Cloud command console:

```bash
php artisan tinker --execute="echo \App\Models\School::whereIn('name',['E2E Invite Wizard 2026-10-19','E2E Invite Toshi 2026-10-19'])->get(['id','name','toshi_mode','toshi_enabled','onboarding_finished_at'])->toJson(JSON_PRETTY_PRINT);"
```

Expected:

- Wizard school: `toshi_mode` `preview`, `toshi_enabled` `0`, `onboarding_finished_at` not null.
- Toshi school: `toshi_mode` `onboarding`, `toshi_enabled` `0`, `onboarding_finished_at` not null.
- Both dashboards show the school name, one student, and the setup checklist complete.

## 7. Remove the test schools cleanly

Get the two ids from step 6. First run the purge **dry-run**:

```bash
php artisan test:purge-schools --school=ID_WIZARD --school=ID_TOSHI
```

The command refuses anything that is not `is_test=1` **and** named `E2E ...`. Flag only these two schools, then re-run the dry-run:

```bash
php artisan tinker --execute="\App\Models\School::whereIn('id',[ID_WIZARD,ID_TOSHI])->update(['is_test'=>1]);"
php artisan test:purge-schools --school=ID_WIZARD --school=ID_TOSHI
```

When the dry-run lists only the two expected schools and their dependent rows, execute:

```bash
php artisan test:purge-schools --school=ID_WIZARD --school=ID_TOSHI --force
```

Verify both schools and their users are gone:

```bash
php artisan tinker --execute="echo \App\Models\School::whereIn('id',[ID_WIZARD,ID_TOSHI])->count();"
```

Expected `0`. Also revoke any unclaimed invites at `/platform/early-access` if a step was abandoned.

## Guardrails

- Do **not** flip `SIGNUP_ENABLED` to true for this test.
- Do **not** use a real school's email or phone number.
- Do **not** delete rows manually; `test:purge-schools` owns the dependent-row order.
- Do **not** merge the invite PR until your screenshot review and this production test-school run are complete.
