# Production demo runbook

The owner sets every production variable and runs every production command
from the Laravel Cloud dashboard. This file lists names and the values to
type. It has no secrets and no environment identifiers.

`demo:refresh` stays a staging command. On production use `demo:provision`
and `demo:reset` only.

## Variables

| Name | Value | When |
|---|---|---|
| `DEMO_PROVISIONING_ENABLED` | `true` while provisioning, then `false` | The provision command refuses to run unless this is `true`. |
| `SIGNUP_ENABLED` | `true` by default. `false` closes public registration | `/register` shows the early access page; sign-up posts and the verification routes create nothing and go to that page. Google sign-in works for existing users only. Login stays open. |
| `WHATSAPP_REPLIES_ENABLED` | `true` by default. `false` stops every outbound WhatsApp message, including replies | Business sends and replies both stop. |
| `PRIVACY_NOTICE_URL` | the public privacy notice address | Shown under the demo welcome line "Your number is removed after 30 minutes." and linked from the early access consent box. Empty means no link. |

A variable change is read on the next deployment. Set it, then deploy, then
run the command.

## Order

1. Deploy the release that contains this command. Confirm the deployment succeeded.
2. Set `DEMO_PROVISIONING_ENABLED` to `true` and deploy again so the command can see it.
3. Commands tab, dry run first. Nothing is written:

```
php artisan demo:provision --dry-run --name="Riverside Primary" --city="Entebbe" --admin-name="Ada Nakato" --admin-email="ada.riverside@example.com" --terms="2026-02-02,2026-04-30,2026-05-18,2026-08-21,2026-08-24,2026-11-28"
```

Expected: `would create schools=1 (dry run — nothing changed)`.

4. Same command with `--confirm`. Leave the password off the command line so
   the prompt hides it. The command prints counts only.

```
php artisan demo:provision --confirm --name="Riverside Primary" --city="Entebbe" --admin-name="Ada Nakato" --admin-email="ada.riverside@example.com" --terms="2026-02-02,2026-04-30,2026-05-18,2026-08-21,2026-08-24,2026-11-28"
```

A second run prints `nothing changed` and does not rewrite the school.
5. Set `DEMO_PROVISIONING_ENABLED` back to `false` and deploy.

The name does not need to contain the word demo. The command creates one
`is_demo` school, refuses a name that already belongs to a real school, and
refuses an admin email that already belongs to a real school.

## Reset

Pass the one demo school's id. Dry run first.

```
php artisan demo:reset {school} --dry-run
php artisan demo:reset {school}
```

It refuses a school that is not `is_demo`. It does not change any other
school, and it keeps the password hashes already on the demo school.

## Smoke tests (read only)

1. Sign in as the admin created above. The dashboard loads.
2. The people list shows the seeded learners (about 60) and parents on
   `example.com`.
3. A fee reminder on that school says nothing was sent.
4. No command in this runbook prints a password.

## Kill switch

Set `WHATSAPP_REPLIES_ENABLED` to `false` and deploy. Every outbound WhatsApp
message stops, including a reply to someone who just wrote in. Set it back to
`true` and deploy to restore replies.

Set `SIGNUP_ENABLED` to `false` and deploy to close public school
registration. Login is unchanged.

## Rollback

1. Kill switch first if messages are leaving: `WHATSAPP_REPLIES_ENABLED=false`, then deploy.
2. Close registration the same way with `SIGNUP_ENABLED=false` if new schools must stop.
3. `demo:reset {school}` restores that one demo school. It will not touch a real school.
4. A code problem is a revert of the merge, green checks, then a deploy. That does not delete the demo school.
