---
title: Connectors and MCP
---

# Connectors and MCP

Toshi, KlassApp's assistant, can call a connector only when that connector is turned on for the instance and connected for the school. Reads are audited. A write waits for a person to approve it.

## What is live

| Connector | On `main` |
|---|---|
| WhatsApp (Meta Cloud API) | Live. It is a core channel, not a row in the MCP catalogue. See [WhatsApp](/community/whatsapp). |
| Slack | Code and the **Integrations** screen exist. The catalogue entry is off unless `TOSHI_SLACK_CHANNEL_ENABLED` is set. A default checkout shows **No integrations available yet.** When Slack is enabled and the instance is in live mode, the button is **Connect Slack**. Otherwise the label is **Connect unavailable (instance not in live mode)**. **Disconnect** removes a connected workspace. |
| Google Drive | **Coming soon.** The catalogue key `google-drive` exists and defaults off (`TOSHI_GOOGLE_DRIVE_ENABLED`). There is no Drive client in `app/`, and the Integrations page has no Google connect button. |
| Google Classroom | **Coming soon.** Not on `main`. An older branch exists as [pull request #728](https://github.com/KlassApp-Foundation/KlassApp/pull/728) and is not merged. |
| Google Sheets | **Coming soon.** Mark import reads an uploaded spreadsheet file. It does not call the Google Sheets API. |
| Notion | Catalogue key only, off by default. No connect button. Treat it as **Coming soon**. |

**Continue with Google** on the register page creates a login. It does not connect Drive, Classroom, or Sheets.

School admins open **Settings**, then **Integrations**. Head teachers do not see **Settings**.

## How a connector is shaped today

`config/toshi.php` lists `slack`, `notion`, and `google-drive`. Each entry can name an endpoint, OAuth, read tools, write tools, an `enabled` flag, and an optional skill class. Only Slack sets a skill.

Connected schools store one `SchoolMcpConnector` row per connector, scoped by `school_id`, with encrypted credentials. `routes/ai.php` registers the Slack client. It does not loop the catalogue. Adding a connector means editing that config and that route file. That is one reason [package proposal #1119](https://github.com/KlassApp-Foundation/KlassApp/pull/1119) treats connectors as a future extension point. The proposal is not built.

The write-gate master switch (`TOSHI_MCP_WRITE_GATES_ENABLED`) defaults off. While it is off, the gate does not mark tools as writes. When it is on, an unknown connector, a missing catalogue entry, or an unknown mode is treated as a write.

## Human approval

Posting through a connected workspace pauses for approval. The approver is stored in the audit log. Disconnecting blocks further calls. Connecting again asks for permission again.

Inside the assistant panel, a tool write shows **Confirm** and **Cancel** before it runs. That card ships in `packages/toshi-ui`. It still requires the assistant to be on for the school, which is not the default.
