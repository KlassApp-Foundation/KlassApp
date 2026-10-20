---
title: WhatsApp integration
---

# WhatsApp integration

Parents message the school's KlassApp number. The app talks to the Meta Cloud API. This page is the developer view. Parents should read [Use KlassApp on WhatsApp](/help/parents/whatsapp).

## Live routes

Under the `api` prefix, with signature checks:

| Method | Path | Role |
|---|---|---|
| GET | `/api/whatsapp/inbound` | Meta's verify handshake (`hub.mode`, `hub.verify_token`, `hub.challenge`) |
| POST | `/api/whatsapp/inbound` | Inbound messages |
| POST | `/api/whatsapp/delivery` | Delivery and status callbacks |

Outbound sends go through `WhatsAppBusinessService` to the Graph API (text, buttons, lists, documents, templates). `WHATSAPP_GRAPH_API_BASE_URL` defaults to `https://graph.facebook.com`. Point it at the simulator only on your machine.

`WHATSAPP_REPLIES_ENABLED=false` stops outbound sends, including replies.

Older `/api/whatsapp/...` helper routes are not registered. They answer 404.

Admin screens, for a school admin, are `/admin/whatsapp/dashboard` (**WhatsApp** in the menu), `/admin/whatsapp/parents`, and `/admin/whatsapp/phone`.

## What a linked parent can trigger

The parent menu rows are **Fee Balance**, **Exam Results**, **Attendance**, **Report Card**, **Help**, **Demo**, and **Dashboard**. Typed words include `fees`, `marks`, `grades`, `attendance`, and `report`.

`report` can return a report-card PDF. Unfinalized marks get the "not yet finalized" reply. A KlassApp number (KLS…) files a parent-link request instead of linking immediately.

Fee reminders have a scheduled command (`whatsapp:send-fee-reminders`). Queued outbound rows flush with `whatsapp:send-pending`.

Free-form Toshi replies on WhatsApp stay off unless that channel, the assistant, and a model key are all turned on for the school. New schools do not get that by default.

## Local simulator

Level 1 needs no Meta account. Nothing leaves the machine. The kit is `e2e/whatsapp-sim/` (Node 20, no extra dependencies). It refuses any URL that is not a loopback address.

| File | Role |
|---|---|
| `server.cjs` | Mock Graph API, signed webhook sender, outbox, and a one-page chat UI |
| `cli.cjs` | `handshake`, `send`, `outbox`, `reset` |
| `scenarios.cjs` | Demo scenarios and a transcript per scenario |
| `setup-local-env.cjs` | Writes local WhatsApp keys into `.env`. Refuses unless `APP_ENV=local` |

The longer notes, including a separate Meta development app, are in `docs/ops/LOCAL-WHATSAPP-TESTING.md` in the repository. Do not aim a production WhatsApp number or a production Meta app at your laptop.

### Run

Use a local database and a demo school seeder. Fictional numbers only, in the `+1 555 0100` to `+1 555 0199` range.

```bash
php artisan serve --host=127.0.0.1 --port=8010
node e2e/whatsapp-sim/server.cjs --port 9123 --app http://127.0.0.1:8010
```

Open `http://127.0.0.1:9123/` or use the CLI:

```bash
node e2e/whatsapp-sim/cli.cjs handshake
node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --text "Hi"
node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --button demo --title "Try Demo"
node e2e/whatsapp-sim/cli.cjs outbox --to +15550100001
```

`setup-local-env.cjs` sets `WHATSAPP_GRAPH_API_BASE_URL` to `http://127.0.0.1:9123` and fills clearly fake token values. It prints key names, not secrets. Do not commit the `.env` it writes.

SQLite needs `php e2e/whatsapp-sim/prepare-local-sqlite.php` once, because a delivery-log enum is stricter there than on MySQL. The script refuses to run unless `APP_ENV=local` and `DB_CONNECTION=sqlite`.

::: warning
Never commit tokens, app secrets, verify tokens, real phone numbers, or a transcript from a real school.
:::
