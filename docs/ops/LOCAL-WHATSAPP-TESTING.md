# Local WhatsApp testing

How to test the KlassApp WhatsApp flows on your own machine, without staging and
without the production WhatsApp number.

- **Level 1, simulator (no Meta account needed).** A local mock of the WhatsApp Cloud
  API records every message the app sends, and a CLI or a one-page web UI sends
  correctly signed inbound webhooks to the app. Nothing leaves your machine.
- **Level 2, a separate Meta development app.** A free Meta test number talks to
  your local app through a temporary Cloudflare quick tunnel.

This is a public repository: never commit tokens, app secrets, verify tokens, real
phone numbers, transcripts or screenshots. Keep them in your local `.env` (git-ignored)
or outside the repository.

> **Never point the production WhatsApp number, the production Meta app or its
> webhook at a local machine or a tunnel.** Level 2 uses its own development app and
> its own free test number only. If you are not sure which app you are looking at in
> the Meta dashboard, stop.

## Environment variables the app reads

All WhatsApp settings live under `services.whatsapp` in `config/services.php`. These
are the exact names (line numbers as of this change):

| Variable | Config key | Where | What it is |
| --- | --- | --- | --- |
| `WHATSAPP_BUSINESS_PHONE_NUMBER_ID` | `business_phone_number_id` | `config/services.php:50` | Phone number ID of the sending number (Cloud API) |
| `WHATSAPP_BUSINESS_API_TOKEN` | `business_api_token` | `config/services.php:49` | Access token sent as `Authorization: Bearer` |
| `WHATSAPP_APP_SECRET` | `app_secret` | `config/services.php:54` | Meta app secret; inbound POSTs must carry a valid `X-Hub-Signature-256` made with it |
| `WHATSAPP_VERIFY_SIGNATURE` | `verify_signature` | `config/services.php:55` | Signature check on (default `true`). Keep it on, also locally |
| `WHATSAPP_BUSINESS_VERIFY_TOKEN` | `business_verify_token` | `config/services.php:52` | Verify token for Meta's GET handshake. Required. The handshake returns 403 when this is unset |
| `WHATSAPP_BUSINESS_API_VERSION` | `business_api_version` | `config/services.php:56` | Graph API version in the outbound URL (default `v21.0`) |
| `WHATSAPP_GRAPH_API_BASE_URL` | `graph_api_base_url` | `config/services.php:59` | Outbound Cloud API base URL. Default `https://graph.facebook.com`; set it only locally, to the simulator |
| `WHATSAPP_REPLIES_ENABLED` | `replies_enabled` | `config/services.php:68` | Kill switch. `false` stops every outbound WhatsApp message, replies included (default `true`) |
| `WHATSAPP_DEMO_PARENT_USER_ID` | `demo_parent_user_id` | `config/services.php:64` | User that Try Demo links a stranger's number to (a parent of a demo school) |
| `PRIVACY_NOTICE_URL` | `privacy_notice_url` | `config/services.php:66` | Privacy link appended to the demo greeting |
| `WHATSAPP_BUSINESS_WABA_ID` | `business_waba_id` | `config/services.php:51` | WhatsApp Business Account ID (not needed for these flows) |
| `WHATSAPP_TEMPLATE_LANGUAGE` | `template_language` | `config/services.php:60` | Template language code (default `en`) |
| `WHATSAPP_PARENT_LINK_FLOW_ID` | `parent_link_flow_id` | `config/services.php:61` | Published WhatsApp Flow for "Request Link" (optional) |
| `WHATSAPP_PARENT_LINK_FLOW_SCREEN` | `parent_link_flow_screen` | `config/services.php:62` | First screen of that Flow (default `LINK_REQUEST`) |
| `WHATSAPP_BUSINESS_NUMBER` | `business_number` | `config/services.php:45` | Display number used in copy |
| `WHATSAPP_BUSINESS_NAME` | `business_name` | `config/services.php:46` | Display name used in copy |

`.env.example` also lists `WHATSAPP_HMAC_SECRET`; no code reads it.

**Webhook path.** The webhook is a fixed route, not a variable:

- `GET  /api/whatsapp/inbound`: Meta's verify-token handshake (`hub.mode`, `hub.verify_token`, `hub.challenge`)
- `POST /api/whatsapp/inbound`: inbound messages and statuses, signature checked
- `POST /api/whatsapp/delivery`: delivery callbacks, signature checked

(`routes/api.php:298-307`, under the `api` prefix from `app/Providers/RouteServiceProvider.php:96`;
signature check in `app/Http/Middleware/VerifyWhatsAppWebhookSignature.php`.)

## Level 1: the simulator

Files: `e2e/whatsapp-sim/` (Node 20, no dependencies).

| File | What it does |
| --- | --- |
| `server.cjs` | Mock Cloud API (`POST /<version>/<phone-number-id>/messages`), signed-webhook sender (`POST /__sim/send`), outbox (`GET /__sim/outbox`), web UI (`/`). Listens on 127.0.0.1 only |
| `cli.cjs` | `send` (text, button reply, list reply), `outbox`, `reset`, `handshake` |
| `scenarios.cjs` | Plays the demo scenarios and writes one transcript per scenario |
| `setup-local-env.cjs` | Writes the simulator keys to your local `.env` and generates a local app secret |
| `prepare-local-sqlite.php` | Local SQLite only, see below |
| `whatsapp-sim.spec.cjs` | The same scenarios as a Playwright spec (no browser) |

Every URL the kit uses must be a loopback address; it refuses anything else and makes
no external requests.

### Set up once

```bash
cp .env.example .env && php artisan key:generate
# Local database. SQLite works:
#   DB_CONNECTION=sqlite
#   DB_DATABASE=/absolute/path/outside/the/repo/klassapp.sqlite
#   APP_ENV=local
#   APP_URL=http://127.0.0.1:8010
#   QUEUE_CONNECTION=sync
php artisan migrate
php artisan db:seed --class='Database\Seeders\DemoJuniorSchoolSeeder'
php e2e/whatsapp-sim/prepare-local-sqlite.php      # SQLite only, safe to run twice
node e2e/whatsapp-sim/setup-local-env.cjs           # writes the WHATSAPP_* keys below
php artisan config:clear
```

`setup-local-env.cjs` refuses unless `APP_ENV=local`, prints key names only, and sets:
`WHATSAPP_GRAPH_API_BASE_URL=http://127.0.0.1:9123`, a clearly fake
`WHATSAPP_BUSINESS_API_TOKEN` and `WHATSAPP_BUSINESS_PHONE_NUMBER_ID`, a random
`WHATSAPP_APP_SECRET` generated on your machine (only if empty), `WHATSAPP_VERIFY_SIGNATURE=true`,
`WHATSAPP_REPLIES_ENABLED=true` and `WHATSAPP_DEMO_PARENT_USER_ID` (the first parent of the
local demo school).

Why `prepare-local-sqlite.php`: `message_delivery_log.category` and `.status` are enum
columns, and the WhatsApp code also logs values outside those lists. MySQL here runs
with `strict => false` and accepts them; SQLite enforces the enum as a CHECK constraint,
so every such insert fails and the webhook answers 500. The script rebuilds that one
local table without those two CHECKs. It refuses unless `APP_ENV=local` and
`DB_CONNECTION=sqlite`.

### Run

```bash
php artisan serve --host=127.0.0.1 --port=8010
node e2e/whatsapp-sim/server.cjs --port 9123 --app http://127.0.0.1:8010
# open http://127.0.0.1:9123/ for the chat UI, or use the CLI:
node e2e/whatsapp-sim/cli.cjs handshake
node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --text "Hi"
node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --button demo --title "Try Demo"
node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --list REPORT --title "Report Card"
node e2e/whatsapp-sim/cli.cjs outbox --to +15550100001
```

Use fictional numbers only (`+1 555 0100` to `+1 555 0199`).

### Demo scenarios

```bash
node e2e/whatsapp-sim/scenarios.cjs --out /some/folder/outside/the/repo
```

| Scenario | Checks |
| --- | --- |
| `try-demo` | A stranger's Hi gets the welcome with Try Demo; Try Demo gets the greeting with the 30-minute notice and the parent menu |
| `bind-marks` | BIND `<code>` then MARKS. **Pending until the Task 9 Tier 1 PR**: reports "not implemented yet" |
| `report-pdf` | REPORT makes the app send a document message; the link serves a PDF from the local app |
| `stop-optin` | STOP unsubscribes; a keyword then gets only the opted-out notice; OPTIN confirms in one line; the menu works again |
| `unknown-code` | An unknown BIND code gets a friendly reply with the follow-along URL. **Pending until the Task 9 Tier 1 PR** |
| `unknown-kls-id` | An unknown KlassApp ID gets the not-found reply with Link help (the existing path) |
| `fee-reminder-demo` | `whatsapp:send-fee-reminders` for the demo school records no message; a queued fee reminder for a demo parent stays blocked while the reply to that parent goes out |
| `kill-switch-off` | A second local app process with `WHATSAPP_REPLIES_ENABLED=false` sends nothing, not even replies (port 8019 by default, `--kill-switch-port` to change) |

The runner refuses `APP_ENV` other than `local`. Before it starts it removes the
local WhatsApp records of the kit's own fictional numbers (and only those), so each run
starts from a stranger. It exits 1 only when a scenario fails; pending scenarios do not
fail the run. Transcripts are evidence: the runner refuses an `--out` folder inside the
repository.

As a Playwright spec (no browser is started; the pending scenarios are skipped with a note):

```bash
WHATSAPP_SIM_E2E=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:8010 \
  npx playwright test e2e/whatsapp-sim --project=chromium
```

Stop `php artisan serve` and the simulator when you are done.

Before running the PHP test suite on the same checkout, remove `WHATSAPP_GRAPH_API_BASE_URL`
from `.env` (or comment it out): PHPUnit reads `.env`, and tests that assert the Meta URL
would otherwise see the simulator URL.

## Level 2: a separate Meta development app

Only when you need the real WhatsApp client on a phone. Ask the owner for the
development app's credentials through a private channel; never paste them into chat,
issues, pull requests or logs.

### 1. The development app (once)

1. In the Meta developer dashboard, create a **new** app for development only (use case:
   connect with customers through WhatsApp). Do not reuse or modify the production app.
2. Open **WhatsApp > API Setup**. Meta gives the app a free **test phone number**. Note
   its **Phone number ID** and copy the **temporary access token**.
3. Under **To > Manage phone number list**, add the phone you will test with (up to 5
   recipients) and confirm it with the code WhatsApp sends.
4. In **App settings > Basic**, copy the **App secret**.

### 2. Point your local app at it

In your local `.env` (never committed):

```
WHATSAPP_BUSINESS_PHONE_NUMBER_ID=<test number's phone number ID>
WHATSAPP_BUSINESS_API_TOKEN=<temporary access token>
WHATSAPP_APP_SECRET=<development app secret>
WHATSAPP_BUSINESS_VERIFY_TOKEN=<a long random string you make up>
WHATSAPP_VERIFY_SIGNATURE=true
WHATSAPP_BUSINESS_API_VERSION=v21.0
WHATSAPP_REPLIES_ENABLED=true
# remove WHATSAPP_GRAPH_API_BASE_URL (or comment it out) so the default Meta host is used
```

Then `php artisan config:clear`.

### 3. Expose the local app with a Cloudflare quick tunnel

```bash
php artisan serve --host=127.0.0.1 --port=8010
cloudflared tunnel --url http://127.0.0.1:8010
```

`cloudflared` prints a `https://<random>.trycloudflare.com` URL. Set
`APP_URL=https://<random>.trycloudflare.com` and run `php artisan config:clear`, so the
report card links the app sends are reachable by Meta.

### 4. Register the webhook

In the development app, **WhatsApp > Configuration > Webhook**:

1. **Callback URL**: `https://<random>.trycloudflare.com/api/whatsapp/inbound`
2. **Verify token**: the same value as `WHATSAPP_BUSINESS_VERIFY_TOKEN`
3. **Verify and save**. The app answers the GET handshake by echoing `hub.challenge`.
4. **Webhook fields**: subscribe to **messages**.

Send "Hi" from your allowlisted phone to the test number. The app's replies come back
to that phone.

### Limits

- **Recipient allowlist.** The test number only messages the numbers on its list (up to 5).
- **Temporary token.** It expires after about 24 hours; copy a new one from API Setup.
- **The tunnel URL changes on every `cloudflared` run.** Update the callback URL in the
  webhook settings and `APP_URL` each time.
- **24-hour window.** Free-form replies need the user to have written first, which is how
  every flow here starts.
- **Country code.** Any country works: the inbound handler keeps Meta's full international
  sender number (`wa_id`) and replies to it (`WhatsAppPhoneHelper::fromWaId`, #1104). Only a
  local-looking number (leading 0, or fewer than 11 digits) is still normalised to `+256`.
- **Your data.** The local app uses your local database and demo seeders, never staging
  or production data.

### Afterwards

Stop `cloudflared` and `php artisan serve`. In the development app, remove or change the
callback URL. Remove the development credentials from your local `.env` if you no longer
need them.
