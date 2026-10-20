# Google Workspace MCP, OAuth, and verification

Research date: **2026-10-20**. This note records what Google's live documentation said that day. It does not change application code. Claims are linked to the page they came from. Anything not published, or inferred from two pages that do not quite agree, is marked **Uncertain:**.

**NEEDS REVIEW.** Scope classifications for Calendar, Classroom, Tasks, and People are not published as sensitivity tables the way Drive, Gmail, Docs, Sheets, and Slides are. Assessor prices below are third-party list prices, not Google fees. No one on this pass called a live MCP `tools/list` (that needs a Developer Preview project and a user token).

## 1. Google's MCP APIs

### What "official" means here

Google runs **remote MCP servers on Google infrastructure**. For Workspace, each product has its own HTTPS endpoint, transport **Streamable HTTP**, and OAuth 2.0. The index is [Configure the Google Workspace MCP servers](https://developers.google.com/workspace/guides/configure-mcp-servers) (page updated **2026-10-19 UTC**). Every server on that page, and on the [Developer Preview Program feature list](https://developers.google.com/workspace/preview), is **Developer Preview**, not GA, beta, or experimental under some other name. The preview page says features usually stay in the program for 3–6 months, with exceptions.

A second official catalog, [Google Cloud MCP servers](https://docs.cloud.google.com/mcp/supported-products), lists remote MCP endpoints for Cloud products (BigQuery, Cloud Run, and many others). Those are not Workspace school-data APIs and are out of scope for a KlassApp connector, except as a reminder that "Google MCP" is bigger than Workspace.

Google's own GitHub org also publishes things that are **not** these hosted servers:

- [`googleworkspace/cli`](https://github.com/googleworkspace/cli) says in the README: "This is **not** an officially supported Google product." Its changelog shows a `gws mcp` stdio server added in 0.3.0 and **removed in 0.8.0** (`Remove mcp command`).
- [`googleworkspace/developer-mcp`](https://github.com/googleworkspace/developer-mcp) is an archived docs-search server. Its README says "This is not an official Google product."

Third-party npm and GitHub "Google MCP" servers exist. They are not Google-operated. This note does not inventory them.

### Status of the products asked about

| Product | Official remote MCP on 2026-10-20 | Status | Endpoint | What the docs expose |
| --- | --- | --- | --- | --- |
| Gmail | Yes | Developer Preview | `https://gmailmcp.googleapis.com/mcp/v1` | Tools only (see below). No send tool. |
| Drive | Yes | Developer Preview | `https://drivemcp.googleapis.com/mcp/v1` | Tools only, in the configure guide. |
| Docs | Yes | Developer Preview | `https://docsmcp.googleapis.com/mcp/v1` | Tools in the configure guide. Release notes also describe comment tools. |
| Sheets | Yes | Developer Preview | `https://sheetsmcp.googleapis.com/mcp/v1` | Tools. The [MCP reference](https://developers.google.com/workspace/sheets/api/reference/mcp) is much wider than the short name list. |
| Slides | Yes | Developer Preview | `https://slidesmcp.googleapis.com/mcp/v1` | Tools in the configure guide. Release notes also describe comment tools. |
| Calendar | Yes | Developer Preview | `https://calendarmcp.googleapis.com/mcp/v1` | Tools, including writes. Setup scopes listed are read-only (see §2). |
| Chat | Yes | Developer Preview | `https://chatmcp.googleapis.com/mcp/v1` | Tools. Also requires a Chat app configuration. |
| People / Contacts | Yes | Developer Preview | `https://people.googleapis.com/mcp/v1` | Tools: profile, contacts, directory search. |
| Classroom | Yes | Developer Preview | `https://classroommcp.googleapis.com/mcp/v1` | Tools. Two official pages disagree on the tool list (see below). |
| Universal Search | Yes | Developer Preview | `https://workspacemcp.googleapis.com/mcp/v1` | One tool, `search_corpus`, across Gmail, Drive, Calendar, and Chat. |
| Tasks | No page found | Not on the preview list or the configure guide | — | REST API only ([Tasks scopes](https://developers.google.com/workspace/tasks/auth)). |
| Meet | No page found | Not on the preview list | — | REST / Meet APIs on the [product index](https://developers.google.com/workspace/products). |
| Forms | No page found | Not on the preview list | — | Forms API on the product index. |
| Keep | No page found | Not on the preview list | — | Keep API on the product index. |
| Admin SDK | No page found | Not on the preview list | — | [Admin SDK](https://developers.google.com/admin-sdk/overview) is REST (Directory, Reports, and others). |
| Sites, Vault, Cloud Search | No Workspace MCP page found | Not on the preview MCP list | — | Not researched beyond their absence from the MCP index. |

**Uncertain:** "No page found" means these products are absent from the Workspace MCP configure guide (updated 2026-10-19) and from the preview-program MCP table. It is not a Google statement that an MCP server will never exist.

### Tools, and the gap versus REST

Official references that were opened (Sheets, Gmail) document **tools**. They show `tools/list` and `tools/call`. They do not document MCP **resources** or **prompts**. [Sheets MCP reference](https://developers.google.com/workspace/sheets/api/reference/mcp), [Gmail MCP reference](https://developers.google.com/workspace/gmail/api/reference/mcp).

**Uncertain:** a live `resources/list` or `prompts/list` was not called, so unpublished primitives are possible.

Tool names below are from [Configure the Google Workspace MCP servers](https://developers.google.com/workspace/guides/configure-mcp-servers) unless another page is named. That guide's "Supported products" list is the cross-product snapshot. Product pages and MCP references are newer or more detailed and sometimes disagree with it.

**Gmail** (configure guide and [Gmail MCP reference](https://developers.google.com/workspace/gmail/api/reference/mcp)): `create_draft`, `get_message`, `get_thread`, `label_message`, `label_thread`, `list_drafts`, `list_labels`, `search_threads`, `unlabel_message`, `unlabel_thread`. [`create_draft`](https://developers.google.com/workspace/gmail/api/reference/mcp/tools_list/create_draft) creates a draft and returns its id. The same page says creating drafts with attachments is not supported yet, while the schema still lists an `attachments` field. There is **no send tool**. REST [`users.messages.send`](https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages/send) can send. The Gmail configure guide's shorter tool list omits `get_message`; the MCP reference includes it.

**Drive** (configure guide): `copy_file`, `create_file`, `download_file_content`, `get_file_metadata`, `get_file_permissions`, `list_recent_files`, `read_file_content`, `search_files`. Writes exist (`create_file`, `copy_file`). This is not the full Drive REST surface (no permissions-update tool is named, no changes.watch, and so on).

**Docs** (configure guide): `read_doc`, `update_doc`. [Release notes](https://developers.google.com/workspace/release-notes) additionally say the Docs MCP server supports reading and managing comments. **Uncertain:** those comment tool names were not copied off a Docs MCP reference page in this pass.

**Sheets.** The configure guide names six tools: `get_values`, `get_spreadsheet`, `update_spreadsheet`, `update_values`, `update_formulas`, `insert_dimension`. The [Sheets MCP reference](https://developers.google.com/workspace/sheets/api/reference/mcp) says `update_spreadsheet` corresponds to REST `spreadsheets.batchUpdate` and lists a large request set inside that one tool (formatting, sheets, charts, filters, comments via `insertComment` / replies, and more). `get_values` maps to `spreadsheets.values.get`. `update_values` and `update_formulas` map to `spreadsheets.values.update`. So Sheets MCP is a **coarser tool surface** over a large slice of REST, not a 1:1 method mirror, and not "read-only". A tool named like `spreadsheets.create` is not in that list. **Uncertain:** whether Drive MCP `create_file` can create a Google Sheet mime type. That schema was not opened.

**Slides** (configure guide): `read_presentation`, `update_presentation`. Release notes say comment tools were added. **Uncertain:** comment tool names were not taken from a Slides MCP reference page here.

**Calendar** ([Calendar MCP guide](https://developers.google.com/workspace/calendar/api/guides/configure-mcp-server), updated 2026-10-12): `create_event`, `delete_event`, `get_event`, `list_calendars`, `list_events`, `respond_to_event`, `suggest_time`, `update_event`. The cross-product configure guide also lists `search_events`. The Calendar page's tool list does not. Both pages say the server can create, update, and delete events. REST Calendar is a larger surface (ACLs, settings, freebusy as its own method, and so on).

**Chat** (configure guide): `search_conversations`, `list_messages`, `search_messages`, `send_message`, `mark_as_read`, `mark_as_unread`, `list_memberships`. `send_message` is a write. Setup also requires a Chat app in the Cloud project with interactive features off ([configure guide](https://developers.google.com/workspace/guides/configure-mcp-servers)).

**People** (configure guide): `get_user_profile`, `search_contacts`, `search_directory_people`.

**Classroom.** The cross-product guide (2026-10-19) lists: `create_announcement`, `create_course_work`, `create_course_work_rubric`, `create_topic`, `get_announcements`, `get_course_roster`, `get_course_work`, `get_course_work_rubric`, `get_courses`, `get_guardian_info`, `list_announcement_summaries`, `list_course_summaries`, `list_course_work_submissions`, `list_course_work_summaries`, `list_topics`, `update_course_work`. The [Classroom MCP guide](https://developers.google.com/workspace/classroom/guides/mcp/configure-mcp-server) (updated 2026-10-13) lists a different set, including `list_courses` and `search_course_standards`, and does not list `get_courses` or `list_course_summaries`. **Uncertain:** which list a live server returns. `tools/list` was not called.

**Universal Search** ([guide](https://developers.google.com/workspace/guides/universal-search-mcp)): one tool, `search_corpus`, over Gmail, Drive, Calendar, and Chat, limited to the scopes the user actually granted. It does not write.

### Rate limits, price, and how MCP differs from REST

MCP tools are fewer and coarser than REST methods. Several wrap one REST method; Sheets' `update_spreadsheet` wraps many batch-update requests. Writes are real on Gmail (drafts and labels, not send), Drive, Docs, Sheets, Slides, Calendar, Chat, and Classroom. Gmail MCP does not send mail.

Quotas are separate numbers, published at least for Gmail and Drive:

- [Gmail usage limits](https://developers.google.com/workspace/gmail/api/reference/quota) (updated 2026-10-16): REST is 1,200,000 quota units per minute per project and 6,000 per minute per user per project, plus a daily threshold of 80,000,000 units before planned charges. Gmail MCP uses a **query-cost** budget with the same two per-minute ceilings (1,200,000 project, 6,000 user). Example tool costs: `search_threads` 10, `get_thread` 40, `create_draft` 10, `list_labels` 1. The MCP cost table on that page does not list `get_message`. REST `messages.send` costs 100 units and is capped at 500 recipients per message; MCP has no send tool.
- [Drive usage limits](https://developers.google.com/workspace/drive/api/guides/limits) (updated 2026-09-11, as returned in search): REST is 1,000,000 quota units per minute per project and 325,000 per minute per user per project, plus 1 TB per day per project before charges. Drive MCP query-cost ceilings on that page are **325,000 per minute per project** and **1,000,000 per minute per user per project**. Tool costs include `search_files` and `list_recent_files` at 100, `read_file_content` and `download_file_content` at 200, `create_file` and `copy_file` at 50, metadata and permissions gets at 5.

**Uncertain:** MCP quota tables for Docs, Slides, Calendar, Chat, People, Classroom, and Universal Search were not opened one by one. Do not assume they match Gmail or Drive.

[Google Workspace standardized model for agent tools and APIs](https://developers.google.com/workspace/tools-safety) (updated 2026-09-03): from 1 May 2026, new Cloud projects get the documented Gmail, Calendar, and Drive quotas. Projects that used those APIs between November 2025 and April 2026 keep prior quotas for at least 60 days. Later in 2026, after 90 days' notice, quota **increases** require Cloud billing, and usage over the standard daily threshold is planned to be billed. The page says Google expects to publish the paid overage option with 90 days' notice. It does not name a price. [Gmail usage limits](https://developers.google.com/workspace/gmail/api/reference/quota) say standard Gmail API use has no additional cost today, and that exceeding quota limits is planned to incur charges later in 2026. No separate "MCP SKU" or billing-account requirement for ordinary use was found. A billing account is described as a future requirement for **raising** quotas, not for calling the API inside the standard quota.

Developer Preview is an access gate on top of quotas. See §2.

## 2. Authentication

### Same scopes, not the same OAuth client

Workspace MCP uses **OAuth 2.0 user authorization**. The scope strings in the setup guides are the ordinary REST scope URLs (`https://www.googleapis.com/auth/...`), not a separate `mcp.*` scope. Sources: the scope lists in [Configure the Google Workspace MCP servers](https://developers.google.com/workspace/guides/configure-mcp-servers).

The OAuth **client** is not interchangeable with a normal sign-in client:

- Create a Web application client and select **Use this client for an AI-powered agent**. [Configure guide](https://developers.google.com/workspace/guides/configure-mcp-servers).
- [Workspace release notes, 12 October 2026](https://developers.google.com/workspace/release-notes): starting **11 November 2026**, Workspace MCP servers reject requests whose OAuth client is not marked agentic. Clients not marked by **12 November 2026** stay non-agentic and cannot call MCP; a new client is required. REST-only apps are not affected. If one client is used for both an agent and ordinary features, Google says to split them and mark only the agent client.
- A Directory API quickstart says the agentic checkbox cannot be changed after the client is created: [JavaScript quickstart](https://developers.google.com/workspace/admin/directory/v1/quickstart/js) ("You can't change this setting after you create the client").

Calling the server with a user access token is documented. The Gmail `create_draft` sample sends `Authorization: Bearer YOUR_ACCESS_TOKEN` to `https://gmailmcp.googleapis.com/mcp/v1` with `Content-Type: application/json` and `Accept: application/json, text/event-stream`, and a JSON-RPC `tools/call` body ([create_draft reference](https://developers.google.com/workspace/gmail/api/reference/mcp/tools_list/create_draft)). The Sheets reference shows the same style of POST for `tools/list`, without an `Authorization` header in that particular snippet ([Sheets MCP reference](https://developers.google.com/workspace/sheets/api/reference/mcp)).

That is **3-legged OAuth**: the person signs in and consents, and the MCP call uses that person's token. The guides' test flow is "Authenticate, sign in to your Google Account". The configure guide's troubleshooting section also says Apps Script's default token cannot be used, and that a script must send an access token from an agentic OAuth client instead.

**Service accounts.** The same troubleshooting section says service accounts are not managed under Google Auth Platform > Clients, so they cannot get the agentic checkbox. Requests from service accounts that use **domain-wide delegation** are not rejected for that reason; a Workspace admin controls them with domain-wide delegation. The [preview program FAQ](https://developers.google.com/workspace/preview) says service accounts cannot be added to the program; domain-wide delegation is the suggested alternative, and if a service account is included in an application Google will drop it and register only the Workspace account.

**Uncertain:** whether a domain-wide-delegated service account can call MCP **before** the Cloud project is in the Developer Preview Program. The configure guide lists program membership as a prerequisite for the servers themselves.

### Per-product scopes the MCP guides tell you to add

These are the scopes Google tells you to paste for MCP. Sensitivity in §4 is for the same URLs. "Read" and "write" here describe the scope, not a promise that every MCP tool works with the narrowest one.

| Server | Scopes named in the setup guide |
| --- | --- |
| Gmail | `gmail.readonly`, `gmail.compose` ([configure guide](https://developers.google.com/workspace/guides/configure-mcp-servers)) |
| Drive | `drive.readonly`, `drive.file` |
| Docs | `drive.readonly`, `drive.file`, `documents.readonly`, `documents` |
| Sheets | `drive.readonly`, `drive.file`, `spreadsheets.readonly`, `spreadsheets` |
| Slides | `drive.readonly`, `drive.file`, `presentations.readonly`, `presentations` |
| Calendar | `calendar.calendarlist.readonly`, `calendar.events.freebusy`, `calendar.events.readonly` ([Calendar MCP guide](https://developers.google.com/workspace/calendar/api/guides/configure-mcp-server)) |
| Chat | `chat.spaces.readonly`, `chat.memberships.readonly`, `chat.messages.readonly`, `chat.messages.create`, `chat.users.readstate` |
| People | `directory.readonly`, `userinfo.profile`, `contacts.readonly` |
| Classroom (cross-product guide) | `classroom.announcements`, `classroom.courses.readonly`, `classroom.coursework.students`, `classroom.courseworkmaterials`, `classroom.rosters.readonly`, `classroom.student-submissions.students.readonly`, `classroom.topics.readonly` |
| Classroom (product guide, "scopes needed for the sample prompts") | `classroom.announcements`, `classroom.courses.readonly` only ([Classroom MCP guide](https://developers.google.com/workspace/classroom/guides/mcp/configure-mcp-server)) |
| Universal Search | One or more of `gmail.readonly`, `drive.readonly`, `calendar.readonly`, `chat.messages.readonly` ([Universal Search guide](https://developers.google.com/workspace/guides/universal-search-mcp)) |

Calendar is inconsistent on its own pages: the tool list includes `create_event`, `update_event`, and `delete_event`, and the guide says the server can create, update, and delete events, but the scopes it tells you to add are list/freebusy/read. **Uncertain:** which scope a write tool actually requires. REST [`events.insert`](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert) accepts `calendar`, `calendar.events`, `calendar.app.created`, or `calendar.events.owned`. None of those four is in the MCP setup list.

### Enablement, admin, edition, preview terms

For each MCP server you enable the product API **and** a separate MCP service (`gmailmcp.googleapis.com`, `drivemcp.googleapis.com`, and so on). People is the exception in the enablement command: the guide enables `people.googleapis.com` for both the API and the MCP service. Universal Search enables `workspacemcp.googleapis.com` plus the product APIs you want to search. [Configure guide](https://developers.google.com/workspace/guides/configure-mcp-servers), [Universal Search guide](https://developers.google.com/workspace/guides/universal-search-mcp).

Chat additionally needs a Chat app record. [Configure guide](https://developers.google.com/workspace/guides/configure-mcp-servers).

[Preview program](https://developers.google.com/workspace/preview): you apply with a Workspace account and a Cloud project number; Google adds you to a group and registers the project. The FAQ says this usually takes a couple of days, and to write to the program if a week passes. Workspace for Education accounts may join (the FAQ says they used to be refused and that changed). The program is all-or-nothing: you cannot apply for one feature only.

Program terms on that page, which matter more than the API shape:

- (ii) Preview features may not be included in public applications before the GA announcement.
- (iv) For testing, you may not grant end users **outside your domain or company** access to applications built on pre-GA APIs, unless Google specifically says you can ask and then grants that permission for that feature. The FAQ answers "Can I share my integration with my customers before GA?" with **"No."**
- (vi) Google may use data submitted via pre-GA APIs to provide, test, analyze, develop, and improve those APIs.
- (viii) Pre-GA APIs are "AS IS", without warranties.

A [May 2026 Workspace Updates post](https://workspaceupdates.googleblog.com/2026/05/agent-tools-and-security-updates-for-workspace-developers.html) said the Workspace MCP server was opening to public developer preview, available to all Workspace customers and developers, and that admins need take no action then because API access can be managed under Security > API controls. That post does not waive term (iv). The configure guide still carries the Developer Preview banner as of 2026-10-19.

**Edition.** The Classroom MCP guide says Classroom can be used with consumer accounts such as `@gmail.com`, and recommends a Workspace for Education account for realistic tests ([Classroom MCP guide](https://developers.google.com/workspace/classroom/guides/mcp/configure-mcp-server)). Gmail and Drive REST scopes are not Workspace-only. `directory.readonly` is a directory scope and is not something a consumer Gmail account can exercise. **Uncertain:** a single matrix of "which MCP servers accept consumer Gmail" was not found. Do not treat the May 2026 "all Workspace customers" line as "consumer Gmail is in" or "consumer Gmail is out".

**Security screening.** [Configure security for Google Workspace MCP servers](https://developers.google.com/workspace/guides/configure-mcp-security) (updated 2026-10-13) says you must screen prompts and responses for prompt injection, using Model Armor or your own documented control. Model Armor can log full payloads. Routing may differ for MCP servers in regions Model Armor does not support, which can affect data-residency claims. The [Workspace user data and developer policy](https://developers.google.com/workspace/workspace-api-user-data-developer-policy) still applies, including minimum scopes and the prohibition on using Google user data to train or improve a foundation model (see also the [OAuth verification FAQ](https://support.google.com/cloud/answer/13463817?hl=en)).

## 3. Fit for KlassApp

Checked on `main` at `f14c93a3` (2026-10-20). Open draft PRs for Google connectors (#1137 Drive, #1138 Sheets, #1139 foundation, #1140 Classroom, #1149 Calendar) are **not** on `main` and were not reviewed as code. This section describes what is already merged.

### How Toshi calls tools

Toshi's school agent is `App\AiAgents\ToshiOrchestrator` (`app/AiAgents/ToshiOrchestrator.php`). It is a `laravel/ai` agent (`Agent`, `HasTools`, `Promptable`). Locked versions in `composer.lock`: **`laravel/ai` v0.10.2**, **`laravel/mcp` v0.8.2**. `composer.json` allows `laravel/ai: ^0.10` and `laravel/mcp: ^0.8`.

Remote tools are not called from the orchestrator directly. The live pattern is Slack:

- `config/toshi.php` `mcp_connectors.slack` holds the endpoint, token URL, OAuth client env vars, read-tool names, and write-tool names. `enabled` defaults off.
- `routes/ai.php` is the only place that may construct an MCP client (`Mcp::registerClient`). In live mode it uses `Laravel\Mcp\Client::web($url)->withOAuth(...)->withToken(...)`. The token closure calls `SchoolMcpConnector::resolveTokenForRequest('slack')`.
- `App\AiAgents\Skills\SlackSkill` (`app/AiAgents/Skills/SlackSkill.php`) calls `Mcp::client('slack')->tools()` and wraps each tool in `App\Ai\Tools\Toshi\ApprovableMcpTool`. Writes pause for human approval when the write gate is on. The orchestrator reaches Slack through `RouteToSlackSkillTool`, not by embedding the MCP client in the orchestrator.
- `App\Services\Toshi\AuditingMcpClientManager` wraps named clients so `Mcp::client($name)` is audited. `App\Models\SchoolMcpConnector` stores one active row per school and connector type (`school_id`, encrypted `credentials`, `token_expires_at`). `App\Models\McpConnectorTokenRefreshService` POSTs `grant_type=refresh_token` to the catalog `token_url`.

`routes/ai.php` registers **only** the Slack client. It does not register a Google client.

### Google code that already exists

**Sign-in, not a Workspace connector.** `config/services.php` `google` is `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and a redirect to `/auth/google/callback`. `App\Http\Controllers\Auth\GoogleAuthController` uses Laravel Socialite. On success it stores `users.google_id` and, except for demo schools, `google_avatar`. It does not request Drive, Gmail, Sheets, or Classroom scopes. `users.google_token` exists (`database/migrations/2026_06_20_171448_add_google_fields_to_users_table.php`; fillable on `App\Models\User`). The callback that was read does not save an access token into it. Signup bootstrap writes an empty `google_token` when the column exists (`app/Services/SchoolSignupBootstrapService.php`). This client is the sign-in client the October 2026 release notes say to keep **separate** from an agentic MCP client.

**Drive catalog, not a client.** `config/toshi.php` `mcp_connectors.google-drive` points at `https://drivemcp.googleapis.com/mcp/v1` (that host matches Google's current Drive MCP URL) and `https://oauth2.googleapis.com/token`. `enabled` defaults off (`TOSHI_GOOGLE_DRIVE_ENABLED`). `read_tools` is `list_files`, `search_files`, `read_document`. `write_tools` is empty. The write gate for `google-drive` is hard-coded `deny`. Google's configure guide names `list_recent_files`, `search_files`, and `read_file_content`. Only `search_files` matches. `App\Contracts\Toshi\GoogleDriveConnectorContract` declares `listFiles`, `search`, and `readDocument`. A comment on the contract says it is for the Developer Preview and that nothing outside a preview implementation may reference the concrete client. No class implements the contract. No `Mcp::registerClient('google-drive', ...)` exists in `routes/ai.php`.

There is no merged Classroom, Sheets, Calendar, Docs, Slides, Tasks, or Gmail API client in `config/services.php` or `config/toshi.php`.

### Could Toshi pass a user's access token to Google's MCP server?

**Partly, and not for customers while the servers are pre-GA.**

What lines up:

- Google's sample calls are Streamable HTTP POSTs with `Authorization: Bearer <access token>` and `Accept: application/json, text/event-stream` ([Gmail create_draft sample](https://developers.google.com/workspace/gmail/api/reference/mcp/tools_list/create_draft)).
- `laravel/mcp` 0.8.0 added a Streamable HTTP web client that POSTs JSON-RPC, accepts JSON or `text/event-stream`, sends `Authorization: Bearer` via `withToken()`, and sends `MCP-Session-Id` and `MCP-Protocol-Version` ([laravel/mcp PR 227](https://github.com/laravel/mcp/pull/227), [changelog](https://github.com/laravel/mcp/blob/main/CHANGELOG.md)). v0.8.2 is the version installed. The 0.9.0 upgrade notes say the client supports protocol versions `2025-11-25` and `2025-06-18`.
- The app already has the hook: a named `Client::web()->withToken(closure)` whose closure reads a per-school token and refreshes it (`routes/ai.php`, `SchoolMcpConnector`, `McpConnectorTokenRefreshService`). A Google connector would be another named client, not a new agent framework.
- Token refresh against Google is the normal `grant_type=refresh_token` POST to `https://oauth2.googleapis.com/token`, which is what `McpConnectorTokenRefreshService` already does, using the catalog client id and secret. Google only issues that refresh token when the **authorization** request includes `access_type=offline`. The OIDC scope `offline_access` is not enough. To mint a new refresh token for someone who already consented, also send `prompt=consent`. Access tokens otherwise force a sign-in about every hour ([configure guide](https://developers.google.com/workspace/guides/configure-mcp-servers)).

What does not line up:

- **Preview term (iv)** forbids giving this to schools outside KlassApp's own domain until GA, unless Google grants an exception ([preview terms](https://developers.google.com/workspace/preview)). A per-school product cannot ship on these servers first.
- The installed client's OAuth helper was not shown to send `access_type=offline`. **Uncertain:** `laravel/mcp` 0.8 `withOAuth()` query parameters were not read from the installed vendor tree (`vendor/` is not present in this checkout). Google's own warning is that clients which cannot add that parameter will not get a refresh token.
- Google's curl samples call `tools/list` / `tools/call` directly. The Laravel client first sends `initialize` and then echoes `MCP-Session-Id`. **Uncertain:** whether Google's servers accept that 2025-11-25 session handshake. This was not tested. `routes/ai.php` comments that `laravel/mcp` 1.0.0 (2026-09-14) speaks a 2026-07-28 stateless revision. The v1.0.0 changelog section that was read does not say that in those words; it does say the 1.0 server can serve legacy initialize clients ([changelog](https://github.com/laravel/mcp/blob/main/CHANGELOG.md)). Treat protocol compatibility as untested either way.
- **Multi-tenant.** `resolveTokenForRequest` loads one active row for `(school_id, connector_type)`. That matches "a school admin connected Slack once". It does not match "each teacher consents to their own Drive or Classroom". A school-level Google token would act as whichever person completed OAuth, for every later Toshi turn in that school. User-level tokens would need a different key than the current one-row-per-school lookup. Standing rule in this repo: scope by `school_id`, and do not let one request write another school's `school_id`.
- **Agentic client split.** The Socialite client in `config/services.php` must stay a non-agent client if it is only sign-in. An MCP client has to be a new client id, marked agentic, with its own redirect URI ([release notes, 12 October 2026](https://developers.google.com/workspace/release-notes)).
- **Write gate.** New Google tools have to be listed in `read_tools` / `write_tools` or the gate fails closed (`config/toshi.php`). The current Drive names would mis-classify Google's real tool names. Gmail MCP `create_draft` and Calendar `create_event` are writes even when they do not send mail or when the setup scopes look read-only.

### What KlassApp would build, and what it would take from Google

| Piece | Use from Google | Build in KlassApp |
| --- | --- | --- |
| Sheets, Drive, Docs, Slides, Calendar, Gmail send, Tasks, Classroom **while MCP is pre-GA** | GA REST APIs and the published OAuth scopes | OAuth consent (3-legged, `access_type=offline`), encrypted per-school or per-user token storage, refresh, and a **local MCP server** (`Mcp::local`, the same pattern as `app/Mcp/Servers/SpikeSlackMockServer.php`) or a `laravel/ai` tool that calls REST. The local server is the piece Toshi already knows how to wrap with audit and the write gate. |
| Those same products **after MCP is GA** and term (iv) no longer applies | Hosted MCP endpoints, tool schemas, query-cost quotas | The agentic OAuth client, consent, token storage, refresh, school/user scoping, tool-name allow lists, and the write gate. Not a second implementation of Sheets itself. |
| Tasks, Meet, Forms, Keep, Admin | No hosted MCP was found | REST behind a local MCP server or a native tool, if a product need exists. Admin directory access is a different trust decision (domain-wide delegation, admin scopes) and is not a teacher connector. |
| Gmail **send** specifically | REST `messages.send` with `gmail.send` | Not the Gmail MCP server. That server drafts and reads; its setup scopes are the restricted ones (§4). |
| Universal Search, Gmail read, full-Drive search | Hosted MCP only after GA, and only with restricted scopes plus CASA | Do not wrap these in REST either, if the goal is to avoid a restricted-scope assessment. |

Prompt-injection screening is required for the hosted MCP servers ([security guide](https://developers.google.com/workspace/guides/configure-mcp-security)). A local server that feeds mail or doc text into Toshi has the same risk even though that guide is written for Google's servers.

## 4. Verification

Google classifies scopes as non-sensitive, sensitive, or restricted. The classification is shown when you add a scope in the Cloud console ([OAuth verification help center](https://support.google.com/cloud/answer/9110914?hl=en)). Product pages below are the published tables. Where a product page has **no** sensitivity column, this note does not invent one.

### What each level requires

From [scope categories](https://developers.google.com/workspace/guides/configure-oauth-consent) and the [verification FAQ](https://support.google.com/cloud/answer/13463817?hl=en):

| Category | Brand verification | Extra scope review | Security assessment (CASA) |
| --- | --- | --- | --- |
| Non-sensitive | Only if you want the app name and logo on the consent screen ([help center](https://support.google.com/cloud/answer/9110914?hl=en)) | No | No |
| Sensitive | Yes | Yes | No |
| Restricted | Yes | Yes | Yes, and again every year |

FAQ expected times, "not guaranteed": brand verification 2–3 business days; sensitive-scope verification 10 business days; restricted-scope verification 6 weeks. The [sensitive-scope verification guide](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification) instead says the sensitive process typically takes **3–5 business days**. **Uncertain:** which clock Google is actually using. Both pages are current Google pages and they disagree.

Sensitive and restricted review also wants a privacy policy, a YouTube demo of the consent screen and the feature, a justification for why a narrower scope is not enough, and limited-use compliance ([verification requirements](https://support.google.com/cloud/answer/13464321?hl=en), [sensitive-scope guide](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification)). Restricted data that is stored or transmitted needs the security assessment ([Gmail scopes](https://developers.google.com/workspace/gmail/api/auth/scopes): "If you store restricted scope data on servers (or transmit), then you must go through a security assessment.").

### The 100-user cap, testing, and production

Two different limits, both from Google:

1. **Publishing status = Testing.** [Manage App Audience](https://support.google.com/cloud/answer/15549945?hl=en): at most 100 test users listed on the consent screen. Adding a test user consumes the project's test-user quota. Google shows a warning. Their grant, and a refresh token, **expire after seven days**. The exception is a client that asks only for `openid` / `userinfo.email` / `userinfo.profile`: those users need not be listed, see no warning, and tokens do not expire in seven days. Any other scope drops you out of that exception.
2. **Unverified app cap.** [Unverified apps](https://support.google.com/cloud/answer/7454865?hl=en): apps that show the unverified-app screen are limited to **100 new users in total**. The [FAQ](https://support.google.com/cloud/answer/13463817?hl=en) says this cap applies for the **lifetime of the project and cannot be reset**. It applies when the app requests sensitive or restricted scopes that are not yet approved. Exhausting it can disable Google sign-in. Checking usage is Cloud Console → OAuth consent screen → OAuth user cap.

**In production** (after "Publish app") any Google account can be asked to consent, but sensitive or restricted scopes still show the unverified warning and count toward the 100 until verification for those scopes finishes ([Manage App Audience](https://support.google.com/cloud/answer/15549945?hl=en)). Adding a new sensitive or restricted scope and using it before approval puts the app back under the warning and the cap ([FAQ](https://support.google.com/cloud/answer/13463817?hl=en)).

Non-sensitive scopes do not, by themselves, put you in that unverified sensitive/restricted cap. Brand verification is still required to show the app's name and logo ([help center](https://support.google.com/cloud/answer/9110914?hl=en)).

### Exemptions

[When is verification not needed](https://support.google.com/cloud/answer/13464323?hl=en):

- **Personal use**, fewer than 100 users: verification is optional; users can click through the warning; growing past 100 requires verification.
- **Development, testing, staging**: verification not required, but the unverified screen and the 100-user cap still apply. Google asks for a separate production project, submitted only when its publishing status is In production.
- **Service-account data only**, no end-user Google account data: verification not required.
- **Internal**: OAuth user type Internal, project owned by the same Workspace or Cloud Identity organization. No unverified screen and no 100-user cap. Only users in that organization can use the app. An admin may still have to approve it.
- **Admin-trusted or admin-installed Marketplace apps**: an admin can allow an unverified app; Google still discloses the unverified status.

The [User Data Policy](https://developers.google.com/terms/api-services-user-data-policy) says the extra sensitive/restricted requirements do not apply to an app used only by users inside your own domain. KlassApp's customers are other schools, so an internal-only app in KlassApp's own Workspace org does not cover them.

### CASA

[Security Assessment](https://support.google.com/cloud/answer/13465431?hl=en): restricted scopes need an **annual** assessment. A passing app gets a Letter of Validation from the assessor. Google uses the App Defense Alliance [CASA](https://appdefensealliance.dev/casa/casa-tiering) framework (OWASP ASVS). Google's help page says apps are assigned **AL1 or AL2**. The required level can rise as users or data handling change. Once an app has been validated at AL2, later years stay at AL2.

[ADA assurance levels](https://appdefensealliance.dev/casa/casa-tiering) (page updated 2026-06-27):

- **AL1** — lab tested and lab verified. The developer contacts an authorized lab.
- **AL2** — lab tested and lab verified, broader: the lab tests the application, the deployment, and user-data storage against CASA requirements.

ADA says the **framework user** (Google), not the developer, decides the level, using data sensitivity, user counts, risk tolerance, and other signals. Google's [FAQ](https://support.google.com/cloud/answer/13463817?hl=en) says the same inputs ("How is my application tier calculated?") and that the Trust and Safety team tells you when to start the assessment. You do not pick the tier to avoid a harder review.

Google's FAQ still says **"Tier 2"** and **"Tier 3"** in places (self-scan versus assessor access to production). ADA still publishes a [Tier 2 process](https://appdefensealliance.dev/casa/tier-2/tier2-overview) and says the self-scan path is deprecated for compliance: follow the notification. [CASA revalidation](https://appdefensealliance.dev/casa/casa-start) is annual. [Annual recertification](https://support.google.com/cloud/answer/13463816?hl=en): every 12 months from the previous letter's effective date. Google does not charge a fee; you pay the assessor directly ([FAQ](https://support.google.com/cloud/answer/13463817?hl=en)).

**Uncertain:** how Google's current "Tier 2 / Tier 3" words map onto "AL1 / AL2" for a new applicant. Both vocabularies are on official pages. The notification email is the assignment that matters.

### Assessor prices

The [ADA authorized-labs page](https://appdefensealliance.dev/casa/casa-assessors) describes how a lab becomes authorized. **The name roster did not render as text in the fetch used for this note.** Prices below are from the assessors' own sites. They are **third-party**, not Google prices. Inclusion here does not confirm the lab is on today's ADA roster.

**TAC Security** — [casa.tacsecurity.com](https://casa.tacsecurity.com/site/home), fetched 2026-10-20. The page calls TAC a Google-preferred ADA CASA assessor. Figures are the current price next to a higher comparison price ("Saving …"):

| Offer on that page | Price shown | Timeline on that page |
| --- | --- | --- |
| Basic, AL1, one time, 2 revalidation cycles | **$675** (comparison $1,800) | Assessment to letter in 2–3 weeks |
| Enterprise AL1, unlimited revalidation | **$3,600** (comparison $18,000) | 1–2 weeks |
| Premium, AL1, one time, unlimited revalidation | **Uncertain:** the plan card did not show a dollar amount in the text extract (it showed "Saving $1,845" only) | 2–3 weeks |
| Enterprise AL2, one time | **Uncertain:** no dollar amount in the text extract (it showed "Saving $12,600") | 2–4 weeks |
| Premium bundled with SOC 2 Type I | **$3,555** (comparison $9,900) | 2–3 weeks |
| Enterprise AL1 bundled with SOC 2 Type I | **$4,500** (comparison $25,200) | 1–2 weeks |
| Enterprise AL2 bundled with SOC 2 Type I | **$5,400** (comparison $18,000) | 2–4 weeks |

**Leviathan Security Group** — two of their own pages disagree:

- [CASA program page](https://www.leviathansecurity.com/programs/google-casa-cloud-application-security-assessment): **$3,000** (start within 30 days), **$4,500** (start within 10 days), **$6,000** (start within 2 days). Each includes one retest. The page says restricted-scope apps reassess every 12 months from the letter date.
- [Assessment Hub](https://hub.leviathansecurity.com/): CASA plans listed as **$2,000** ("No Rush", start within 3 months), **$3,000** ("Standard", start within 10 days), **$6,000** ("Priority", start within 48 hours).

**Uncertain:** which Leviathan page is the checkout price. Do not quote a single Leviathan number without checking both URLs again.

**Bishop Fox** — [CASA service page](https://bishopfox.com/services/vendor-assessments/casa) describes scoped quotes. **No public price** was found.

Other labs are named in a November 2024 third-party roundup ([Switch Labs post](https://switch-labs.wixsite.com/mysite-2/post/casa-tier-2-tier-3-security-review-providers-pricing-and-the-cheapest-option)). That post is not an assessor's price list and is not used for figures here. Reddit anecdotes are not used.

### Candidate scopes for the connectors in the question

"Narrowest that does the job" is from the API's authorization section, not from the MCP setup lists (those lists include restricted scopes the connector may not need).

#### Sheets

Published table: [Choose Google Sheets API scopes](https://developers.google.com/workspace/sheets/api/scopes) (updated 2026-09-03).

| Scope | Class | Job it actually does |
| --- | --- | --- |
| `drive.file` | **Non-sensitive** | Create spreadsheets, or open ones the user picks with the Google Picker or the app's file picker, then read and edit those files. Sheets values.update accepts `drive.file` ([method page](https://developers.google.com/workspace/sheets/api/reference/rest/v4/spreadsheets.values/update)). This is the narrowest scope for "KlassApp creates the marksheet" or "the teacher picks the file". |
| `spreadsheets.readonly` | **Sensitive** | Read every spreadsheet the user can open, not only files the app created. |
| `spreadsheets` | **Sensitive** | Read and write every spreadsheet the user can open. Narrowest scope if the teacher must edit an existing sheet they did not create through KlassApp and you do not want to use the Picker. |
| `drive.readonly` | **Restricted** | Read all Drive files. Not required for Sheets values if `spreadsheets.readonly` or `drive.file` fits. |
| `drive` | **Restricted** | Full Drive. Not required for cell read/write. |

Sheets scopes apply to the whole spreadsheet file, not one tab ([Sheets scopes](https://developers.google.com/workspace/sheets/api/scopes)).

#### Drive

Published table: [Choose Google Drive API scopes](https://developers.google.com/workspace/drive/api/guides/api-specific-auth).

| Scope | Class | Job |
| --- | --- | --- |
| `drive.file` | **Non-sensitive** | Create files, or modify files the user opens with the app or shares through the Picker. Narrowest scope for "put this term's report-card PDFs in a folder the app created, or a folder the user picked". |
| `drive.appdata` / `drive.appfolder` | **Non-sensitive** | The app's own hidden config folder. Not for teacher-visible PDFs. |
| `drive.install` | **Non-sensitive** | "Open with" / "New" menu. Not required to upload a PDF. |
| `drive.apps.readonly` | **Sensitive** | See which apps can access Drive. Not a file scope. |
| `drive.readonly` | **Restricted** | Read and download every file the user can access. |
| `drive` | **Restricted** | Full access to every file. |
| `drive.metadata`, `drive.metadata.readonly`, `drive.activity`, `drive.activity.readonly`, `drive.scripts`, `drive.meet.readonly` | **Restricted** | Metadata, activity, Apps Script, or Meet files. Not needed to file a PDF. |

Official Drive MCP setup asks for `drive.readonly` **and** `drive.file`. `search_files` and `list_recent_files` over the user's whole Drive are what `drive.readonly` is for. **Uncertain:** whether those MCP tools return only Picker/app files when the token has `drive.file` alone. The scope definition says that is all `drive.file` can see.

#### Classroom

[Choose Google Classroom API scopes](https://developers.google.com/workspace/classroom/guides/auth) lists scopes and meanings. **It does not label them non-sensitive, sensitive, or restricted.** A roster-import tutorial says that because one of `classroom.courses.readonly`, `classroom.rosters.readonly`, and `classroom.profile.emails` is sensitive, the app must be verified ([Import rosters](https://developers.google.com/workspace/classroom/tutorials/import-rosters)). It does not say which one, and it does not mention restricted.

**Uncertain:** the Cloud Console is the classification Google tells developers to trust ([help center](https://support.google.com/cloud/answer/9110914?hl=en)). Do not treat the table below as a CASA decision. It is the narrowest scope for each job, with class left unknown except for that one sentence.

| Scope | Published class | Narrowest job |
| --- | --- | --- |
| `classroom.courses.readonly` | **Uncertain.** Part of a set the import tutorial calls sensitive as a group. | List the signed-in teacher's classes. |
| `classroom.courses` | **Uncertain.** | Create and delete classes. Not required to match existing classes. |
| `classroom.rosters.readonly` | **Uncertain.** | Read the class roster (teachers and students). Required for a roster match. |
| `classroom.rosters` | **Uncertain.** | Change membership. Not required for a read-only match. |
| `classroom.profile.emails` | **Uncertain.** | See email addresses of people in the class. Needed if the match key is email. |
| `classroom.profile.photos` | **Uncertain.** | Profile photos. Not required for a roster match. |
| `classroom.coursework.students.readonly` | **Uncertain.** | View coursework and grades for students in classes you teach or administer. |
| `classroom.coursework.students` | **Uncertain.** | Manage that coursework and those grades. |
| `classroom.coursework.me.readonly` | **Uncertain.** | The signed-in student's own work and grades. Wrong scope for a teacher's class list. |
| `classroom.student-submissions.students.readonly` | **Uncertain.** | View student submissions and grades in classes you teach or administer. |
| `classroom.announcements.readonly` / `classroom.announcements` | **Uncertain.** | Read, or read and manage, announcements. |
| `classroom.topics.readonly` / `classroom.topics` | **Uncertain.** | Read, or manage, topics. |
| `classroom.courseworkmaterials.readonly` / `classroom.courseworkmaterials` | **Uncertain.** | Read, or manage, materials. |
| `classroom.guardianlinks.students.readonly` | **Uncertain.** | View guardians of students you teach. |
| `classroom.guardianlinks.students` | **Uncertain.** | Manage those guardians. |
| `classroom.guardianlinks.me.readonly` | **Uncertain.** | The signed-in user's own guardians. |

`get_guardian_info` and `get_course_roster` on the hosted Classroom MCP server are the tools that pull this wider surface in. They are not required for "list my courses".

#### Calendar

[Choose Google Calendar API scopes](https://developers.google.com/workspace/calendar/api/auth) (updated 2026-09-03) has **no** sensitivity column. The [sensitive-scope guide](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification) gives "reading events stored in Google Calendar" as an example of a sensitive scope, and its walkthrough uses `https://www.googleapis.com/auth/calendar` as the example of a scope that needs a sensitive-scope justification. No Google page found in this pass marks a Calendar scope as **restricted**.

**Uncertain:** every row below except the qualitative "reading events / `calendar` is treated as sensitive in the verification guide". Confirm the badge in the Cloud Console before building.

| Scope | Published class | Narrowest job |
| --- | --- | --- |
| `calendar.app.created` | **Uncertain.** Not named in the sensitive-scope examples. | Create secondary calendars and see, create, change, and delete events **on those calendars**. Accepted by [`events.insert`](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert). This is the narrowest scope for "publish the term dates onto a calendar KlassApp created". It does not edit the teacher's personal primary calendar. |
| `calendar.events.owned` | **Uncertain.** | Create and edit events on calendars the user owns, including ones the app did not create. |
| `calendar.events` | **Uncertain,** but "reading events" is the sensitive-scope example, and this scope is "view and edit events on all your calendars". | Read and write events on every calendar the user can access. Broader than `calendar.app.created`. |
| `calendar.events.readonly` | **Uncertain** (same "reading events" example). | Read events on all calendars. The Calendar MCP setup asks for this. |
| `calendar.events.freebusy` | **Uncertain.** | Free/busy only, no event titles. |
| `calendar.calendarlist.readonly` | **Uncertain.** | See which calendars the user is subscribed to. The Calendar MCP setup asks for this. |
| `calendar` | Treated as a **sensitive** scope in the verification walkthrough's example justification. | See, edit, share, and permanently delete all calendars. Broader than event write. |
| `calendar.readonly` | **Uncertain.** | Read and download any calendar the user can access. Universal Search asks for this. |
| `calendar.acls`, `calendar.calendars`, and the other list/ACL scopes | **Uncertain.** | Sharing and calendar properties. Not required to insert an event on an app-created calendar. |

#### Gmail send-only

Published table: [Choose Gmail API scopes](https://developers.google.com/workspace/gmail/api/auth/scopes) (updated 2026-09-10).

| Scope | Class | Job |
| --- | --- | --- |
| `gmail.send` | **Sensitive** | Send mail as the user. [`users.messages.send`](https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages/send) accepts it. **This is the narrowest scope for send-only.** It does not read the mailbox. |
| `gmail.labels` | **Non-sensitive** | See and edit labels. Does not send. |
| `gmail.compose` | **Restricted** | Drafts and send. Broader than send-only. This is what the Gmail MCP setup asks for, together with `gmail.readonly`. |
| `gmail.readonly` | **Restricted** | Read messages and settings. Required by the Gmail MCP setup and by Universal Search. Not required to send. |
| `gmail.modify` | **Restricted** | Read, compose, and send. |
| `gmail.metadata` | **Restricted** | Headers and labels, not the body. |
| `gmail.insert` | **Restricted** | Insert mail into the mailbox. |
| `https://mail.google.com/` | **Restricted** | Full mailbox, including permanent delete. The FAQ says SMTP-only sending must move to `gmail.send` instead of this scope. |
| `gmail.settings.basic`, `gmail.settings.sharing` | **Restricted** | Filters and delegation. `gmail.settings.sharing` is admin-only via domain-wide delegation. |

The hosted Gmail MCP server cannot do send-only. It has `create_draft` and read/label tools, and its setup scopes are restricted.

#### Docs

Published table: [Choose Google Docs API scopes](https://developers.google.com/docs/api/auth).

| Scope | Class | Job |
| --- | --- | --- |
| `drive.file` | **Non-sensitive** | Docs the app created or the user picked. Narrowest for "generate a letter in a Doc KlassApp created". |
| `documents.readonly` | **Sensitive** | Read every Doc the user can open. |
| `documents` | **Sensitive** | Read and edit every Doc. Narrowest if you must edit Docs the user did not open via the app or the Picker. |
| `drive.readonly`, `drive` | **Restricted** | All Drive files. Not required if `documents` or `drive.file` fits. |

#### Slides

Published table: [Choose Google Slides API scopes](https://developers.google.com/workspace/slides/api/scopes). Same pattern: `presentations` and `presentations.readonly` are **sensitive**; `drive.file` is **non-sensitive**; `drive` and `drive.readonly` are **restricted**. That page also repeats `spreadsheets` and `spreadsheets.readonly` as sensitive. Narrowest for an app-created deck is `drive.file`. Narrowest for every deck the user owns, without the Picker, is `presentations` (read/write) or `presentations.readonly`.

#### Tasks

[Choose Google Tasks API scopes](https://developers.google.com/workspace/tasks/auth) lists two scopes and **does not classify them**:

| Scope | Published class | Job |
| --- | --- | --- |
| `tasks.readonly` | **Uncertain.** | View tasks. Narrowest for read-only. |
| `tasks` | **Uncertain.** | Create, edit, organize, and delete all tasks. Narrowest for write. |

There is no official Tasks MCP server to compare. Confirm the Cloud Console badge before requesting either scope in production.

## 5. Recommendation

Do **not** point Toshi at Google's hosted MCP servers for customer schools yet. They are Developer Preview, and [program term (iv)](https://developers.google.com/workspace/preview) says you may not grant end users outside your domain or company access to an app built on those APIs before GA. The FAQ answer is "No." REST equivalents are generally available and use the same scope strings, so a connector can ship without the hosted MCP server.

Use the Slack shape when a connector is built: OAuth in the app, tokens in `school_mcp_connectors` (or a per-user variant if the consent is the teacher's), a local MCP server or native tool, audit, and the write gate. Take Google's hosted MCP endpoints only after a GA notice removes term (ii) and term (iv).

### Launch first, without CASA

These stay on non-sensitive or sensitive scopes **as published by Google**. Sensitive still means brand verification plus sensitive-scope review (days, not a security assessment), a privacy policy, and a demo video. It does not mean CASA.

1. **Sheets for class marks, with `drive.file`.** KlassApp creates the spreadsheet (or the teacher picks it with the Picker). `spreadsheets.values.get` / `update` accept `drive.file` ([Sheets scopes](https://developers.google.com/workspace/sheets/api/scopes), [values.update](https://developers.google.com/workspace/sheets/api/reference/rest/v4/spreadsheets.values/update)). Non-sensitive: no sensitive-scope review and no CASA. Trade-off: the app cannot search the teacher's Drive for an arbitrary existing marksheet. If that search is a requirement, the next step is **`spreadsheets`** (sensitive, still not restricted), not `drive` or `drive.readonly`.
2. **Drive for report-card PDFs, with `drive.file`.** Create the term/class folder from the app, or let the user pick a folder with the Picker ([Drive scopes](https://developers.google.com/workspace/drive/api/guides/api-specific-auth)). Same trade-off: no whole-Drive browser. That is the point of avoiding `drive.readonly`.
3. **Gmail send-only, with `gmail.send`, via REST.** Sensitive, not restricted ([Gmail scopes](https://developers.google.com/workspace/gmail/api/auth/scopes)). Do not connect the Gmail MCP server for this. It does not send, and its documented scopes (`gmail.readonly`, `gmail.compose`) are restricted.
4. **Calendar, only as an app-created calendar, and only after the Cloud Console badge is checked.** `calendar.app.created` is the narrowest REST scope that can create a secondary calendar and write events on it ([events.insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert)). **Uncertain:** Google has not published whether that scope is non-sensitive or sensitive. The verification guide treats broader calendar access as sensitive, and no page found here calls a Calendar scope restricted. If the console marks `calendar.app.created` sensitive, it is still a "launch without CASA" scope. If it is marked restricted, defer it. Do not request `calendar` or `calendar.readonly` for "publish our term dates".
5. **Docs or Slides, only if a real workflow needs them.** Same as Sheets: `drive.file` for files the app creates. `documents` or `presentations` (sensitive) only when the user must edit files they did not pick.

Build the OAuth client for these as an ordinary Web client (not the agentic MCP checkbox) until you actually call a hosted MCP server. Keep it off the Socialite sign-in client so sign-in scopes stay minimal ([release notes](https://developers.google.com/workspace/release-notes)).

Store tokens per consenting user if the Google account is a teacher's, not one token per school. The current `SchoolMcpConnector` lookup is one active row per school and connector type (§3).

### Defer, because of restricted scopes or because the class is unknown

1. **Hosted Gmail, Drive, Docs, Sheets, Slides, Calendar, Chat, People, Classroom, and Universal Search MCP** until GA, because of term (iv), even for scopes that would otherwise be non-sensitive.
2. **`drive`, `drive.readonly`, and the other restricted Drive scopes.** Whole-Drive search and "drop this PDF into a folder the app cannot see" need them, and they need CASA. Universal Search as documented also asks for `drive.readonly` and `gmail.readonly`.
3. **Gmail read, drafts, or modify** (`gmail.readonly`, `gmail.compose`, `gmail.modify`, `gmail.metadata`, `https://mail.google.com/`). Send-only does not need them.
4. **Classroom roster, emails, guardians, and student coursework** until each scope's console badge is known. The import tutorial only says the combination is sensitive enough to need verification. It does not say CASA is avoided. `classroom.profile.emails`, `classroom.rosters`, `classroom.rosters.readonly`, and the guardian scopes are the ones to check before any design that matches students by email. Listing courses with `classroom.courses.readonly` is the smallest read, and even that scope's class is **Uncertain**.
5. **Tasks**, until `tasks` / `tasks.readonly` are classified in the console. No hosted MCP exists to wait for.
6. **People `directory.readonly`.** That is directory data, not a teacher's contact picker. `contacts.readonly` is the narrower contacts scope, and its class was not on a published sensitivity table (**Uncertain**). The sensitive-scope guide's examples include storing a contact, which is a hint that contact writes are sensitive, not a classification of `contacts.readonly`.

Sensitive-scope review is still real work (policy, demo, a few business days, and the unverified 100-user cap until it finishes). It is a different project from an annual CASA letter.

## Decision (Mucu, 20 Oct 2026)

KlassApp uses Google's REST APIs now for the per-school Google connectors: Drive, Sheets, Classroom, and Calendar. Revisit Google's MCP servers after they reach general availability.

## Sources

Official Google and ADA pages, opened or retrieved on 2026-10-20 unless the page itself gives an "updated" date in the notes above.

- [Configure the Google Workspace MCP servers](https://developers.google.com/workspace/guides/configure-mcp-servers) — updated 2026-10-19 UTC
- [Google Workspace Developer Preview Program](https://developers.google.com/workspace/preview) — terms (i)–(ix) and the MCP feature table
- [Workspace developer release notes](https://developers.google.com/workspace/release-notes) — 12 October 2026 agentic-client requirement; 16 October 2026 is the top dated note on the fetch
- [Configure the Calendar MCP server](https://developers.google.com/workspace/calendar/api/guides/configure-mcp-server) — updated 2026-10-12 UTC
- [Configure the Classroom MCP server](https://developers.google.com/workspace/classroom/guides/mcp/configure-mcp-server) — updated 2026-10-13 UTC
- [Universal Search MCP](https://developers.google.com/workspace/guides/universal-search-mcp)
- [Configure security for Google Workspace MCP servers](https://developers.google.com/workspace/guides/configure-mcp-security) — updated 2026-10-13 UTC
- [Workspace standardized model for agent tools and APIs](https://developers.google.com/workspace/tools-safety) — updated 2026-09-03 UTC
- [Gmail MCP reference](https://developers.google.com/workspace/gmail/api/reference/mcp) and [create_draft](https://developers.google.com/workspace/gmail/api/reference/mcp/tools_list/create_draft)
- [Sheets MCP reference](https://developers.google.com/workspace/sheets/api/reference/mcp)
- [Gmail usage limits](https://developers.google.com/workspace/gmail/api/reference/quota) — updated 2026-10-16 UTC
- [Drive usage limits](https://developers.google.com/workspace/drive/api/guides/limits)
- [Gmail messages.send](https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages/send)
- [Calendar events.insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert)
- [Sheets values.update](https://developers.google.com/workspace/sheets/api/reference/rest/v4/spreadsheets.values/update)
- [Configure the OAuth consent screen](https://developers.google.com/workspace/guides/configure-oauth-consent)
- [Directory API JavaScript quickstart](https://developers.google.com/workspace/admin/directory/v1/quickstart/js) — agentic checkbox cannot be changed later
- [Gmail scopes](https://developers.google.com/workspace/gmail/api/auth/scopes) — updated 2026-09-10 UTC
- [Drive scopes](https://developers.google.com/workspace/drive/api/guides/api-specific-auth)
- [Docs scopes](https://developers.google.com/docs/api/auth)
- [Sheets scopes](https://developers.google.com/workspace/sheets/api/scopes) — updated 2026-09-03 UTC
- [Slides scopes](https://developers.google.com/workspace/slides/api/scopes)
- [Calendar scopes](https://developers.google.com/workspace/calendar/api/auth) — updated 2026-09-03 UTC
- [Classroom scopes](https://developers.google.com/workspace/classroom/guides/auth)
- [Classroom import-rosters tutorial](https://developers.google.com/workspace/classroom/tutorials/import-rosters)
- [Tasks scopes](https://developers.google.com/workspace/tasks/auth)
- [OAuth scopes index](https://developers.google.com/identity/protocols/oauth2/scopes)
- [Sensitive scope verification](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification)
- [Verification requirements](https://support.google.com/cloud/answer/13464321?hl=en)
- [OAuth verification FAQ](https://support.google.com/cloud/answer/13463817?hl=en)
- [Unverified apps](https://support.google.com/cloud/answer/7454865?hl=en)
- [When is verification not needed](https://support.google.com/cloud/answer/13464323?hl=en)
- [Manage App Audience](https://support.google.com/cloud/answer/15549945?hl=en)
- [OAuth verification help center](https://support.google.com/cloud/answer/9110914?hl=en)
- [Security Assessment](https://support.google.com/cloud/answer/13465431?hl=en)
- [Annual recertification](https://support.google.com/cloud/answer/13463816?hl=en)
- [Google API Services User Data Policy](https://developers.google.com/terms/api-services-user-data-policy)
- [Workspace API user data and developer policy](https://developers.google.com/workspace/workspace-api-user-data-developer-policy)
- [Admin SDK overview](https://developers.google.com/admin-sdk/overview)
- [Workspace developer products](https://developers.google.com/workspace/products)
- [Enable Workspace APIs](https://developers.google.com/workspace/guides/enable-apis)
- [Google Cloud MCP supported products](https://docs.cloud.google.com/mcp/supported-products)
- [Workspace Updates, 1 May 2026](https://workspaceupdates.googleblog.com/2026/05/agent-tools-and-security-updates-for-workspace-developers.html)
- [ADA CASA assurance levels](https://appdefensealliance.dev/casa/casa-tiering) — updated 2026-06-27 UTC
- [ADA CASA Tier 2 process](https://appdefensealliance.dev/casa/tier-2/tier2-overview)
- [ADA user-initiated assessment](https://appdefensealliance.dev/casa/casa-start)
- [ADA authorized labs](https://appdefensealliance.dev/casa/casa-assessors) — roster names did not render
- [laravel/mcp changelog](https://github.com/laravel/mcp/blob/main/CHANGELOG.md) and [PR 227](https://github.com/laravel/mcp/pull/227)

Third-party price pages:

- [TAC Security CASA plans](https://casa.tacsecurity.com/site/home)
- [Leviathan CASA program](https://www.leviathansecurity.com/programs/google-casa-cloud-application-security-assessment)
- [Leviathan Assessment Hub](https://hub.leviathansecurity.com/)
- [Bishop Fox CASA](https://bishopfox.com/services/vendor-assessments/casa) — no public price found

Not official Google products, cited only to separate them from hosted MCP:

- [googleworkspace/cli](https://github.com/googleworkspace/cli)
- [googleworkspace/developer-mcp](https://github.com/googleworkspace/developer-mcp)

Repo files cited in §3: `composer.json`, `composer.lock`, `config/services.php`, `config/toshi.php`, `routes/ai.php`, `app/AiAgents/ToshiOrchestrator.php`, `app/AiAgents/Skills/SlackSkill.php`, `app/Http/Controllers/Auth/GoogleAuthController.php`, `app/Services/SchoolSignupBootstrapService.php`, `app/Models/User.php`, `app/Models/SchoolMcpConnector.php`, `app/Models/McpConnectorTokenRefreshService.php`, `app/Contracts/Toshi/GoogleDriveConnectorContract.php`, `app/Mcp/Servers/SpikeSlackMockServer.php`, `database/migrations/2026_06_20_171448_add_google_fields_to_users_table.php`.
