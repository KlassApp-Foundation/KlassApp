# Secrets and webhooks runbook

**Names only. Never paste a value into this file, a ticket, a log or a chat.**
If a value is ever exposed, treat it as compromised and rotate it the same day.

## Where secrets live

| Store | What belongs there | How it reaches the app |
|---|---|---|
| **Doppler**, project `klassapp`, configs `dev`, `stg`, `prd` | Application secrets and switches | Mirrored by hand into the Laravel Cloud environment, then a real deployment. There is no automatic Doppler-to-Cloud sync. |
| **Laravel Cloud**, environment variables | The values the running app reads | A change applies only after a deployment, not after `config:clear`. |
| **GitHub**, Actions secrets | Values used by workflows, not by the app | Read by Actions at run time |
| **n8n**, credentials store | Tokens used by automations | Read by each workflow's credential entry |

The same name can exist in more than one store with different values. Treat each store as its own source of truth and rotate all of them together.

## Application secrets

| Name | Lives in | Used by | Owner | Rotation |
|---|---|---|---|---|
| `APP_KEY` | Doppler `prd`, Cloud | Laravel encryption, sessions | Platform owner | Plan first: rotating invalidates encrypted columns and sessions. Schedule a window, rotate, redeploy, force sign-in. |
| `DB_PASSWORD` | Cloud (managed database) | Database | Platform owner | Rotate in the database, update Cloud, redeploy, check the app connects. |
| `MAIL_PASSWORD` | Doppler `prd`, Cloud | Transactional email | Platform owner | Rotate with the mail provider, update, redeploy, send one test. |
| `GOOGLE_CLIENT_SECRET` | Doppler `prd`, Cloud | Google sign-in and Drive | Platform owner | Rotate in the Google console, update, redeploy, test one sign-in. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_DEFAULT_REGION` | Cloud | Object storage (uploads, report PDFs) | Platform owner | New key in object storage, update Cloud, redeploy, upload and re-open a file, then revoke the old key. |
| `KLASSAPP_OPS_WEBHOOK_URL`, `KLASSAPP_OPS_WEBHOOK_SECRET` | Doppler, Cloud | Outbound ops webhook | Platform owner | Rotate both ends together; the receiver must accept the new secret before the app sends it. |

## WhatsApp

Names only. The Cloud environment holds these; Doppler is the source for staging and production values.

| Name | Purpose |
|---|---|
| `WHATSAPP_BUSINESS_API_TOKEN` | Sending messages |
| `WHATSAPP_BUSINESS_PHONE_NUMBER_ID` | The sending number's id |
| `WHATSAPP_BUSINESS_WABA_ID` | The business account id |
| `WHATSAPP_APP_SECRET` | Verifying inbound webhook signatures |
| `WHATSAPP_VERIFY_TOKEN` | Webhook verification handshake |
| `WHATSAPP_VERIFY_SIGNATURE` | Switch: verify inbound signatures |
| `WHATSAPP_REPLIES_ENABLED` | Kill switch for all outbound replies |
| `WHATSAPP_DEMO_ONLY_RECIPIENTS` | Switch: refuse every real-school recipient |
| `WHATSAPP_APPROVED_TEMPLATES` | List of template names Meta has approved |
| `WHATSAPP_TEMPLATE_LANGUAGE` | Template language code |
| `WHATSAPP_PARENT_LINK_FLOW_ID`, `WHATSAPP_PARENT_LINK_FLOW_SCREEN` | Parent link flow |

Rotation: create the new token in Meta, update Doppler and Cloud, deploy, send one test to an internal number, then revoke the old token. Never rotate by editing only Cloud; the next Doppler mirror would undo it.

## Tracker signing secret

The tracker accepts signed webhooks. The same secret has two names:

| Store | Name |
|---|---|
| Doppler, project `klassapp` | `KLASSAPP_TRACKER_WEBHOOK_SECRET` |
| GitHub, Actions secrets | `TRACKER_WEBHOOK_SECRET` |

Both must hold the same value. Rotation:

1. Generate a new value.
2. Put it in the tracker's receiver configuration first, so the receiver accepts it.
3. Update Doppler and the GitHub Actions secret in the same change window.
4. Trigger one signed test event; confirm the tracker accepts it.
5. Remove the old value from the receiver.

If only one side is rotated, signed events fail with a signature error. That is the expected symptom of a half-finished rotation.

## n8n credentials

Stored in the n8n credentials area, never in a workflow's plain fields. Names to confirm with the n8n owner:

- Tracker API token (or the tracker signing secret, if n8n signs)
- GitHub token (issues and pull requests)
- Mail or chat credential for digests and alerts
- Social account credentials for scheduled posts

Rotation: update the credential entry in n8n, run one workflow in test mode, then activate. Revoke the old token at the provider after the test passes.

## Webhooks, in one list

| Direction | Endpoint or receiver | Signed with |
|---|---|---|
| Inbound WhatsApp | App webhook | `WHATSAPP_APP_SECRET` |
| Outbound ops events | `KLASSAPP_OPS_WEBHOOK_URL` | `KLASSAPP_OPS_WEBHOOK_SECRET` |
| Tracker events | Tracker receiver | `KLASSAPP_TRACKER_WEBHOOK_SECRET` / `TRACKER_WEBHOOK_SECRET` |

## Who owns what

Role names, not people. Confirm the current holder of each role before a rotation.

- **Platform owner** — Cloud, Doppler, database, object storage, mail, Google.
- **Messaging owner** — the WhatsApp business account and template approvals.
- **Tracker owner** — the tracker receiver and its signing secret.
- **n8n owner** — workflows and their credentials.

## Rules

- One change at a time, one store at a time, with a test after each.
- Record what was rotated, when, by whom, and where the old value was revoked.
- Never commit a value. Never print one in a command's output. Use the provider's UI or a secret manager.
