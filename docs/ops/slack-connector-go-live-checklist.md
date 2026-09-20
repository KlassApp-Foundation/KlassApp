# Slack MCP Connector — Production Go-Live Checklist

> **Status**: prep doc, 2026-09-20. The connector is built and dormant (PR #684, wave-1).
> This doc turns it on **safely**. Nothing here has been executed against production.
> Credentials are deliberately **not** in this file — every secret lives in Doppler.
>
> Verified against `main` @ `89481aef` (routes/ai.php live-mode block,
> `config/toshi.php` mcp_connectors + mcp_write_gates, `config/services.php` slack_mcp,
> `IntegrationsController`, `SlackSkill`, `McpWriteGate`, `ApprovableMcpTool`,
> vendor `laravel/mcp` OAuthRouteRegistrar/WebClient, prod-style `--no-dev` install re-check).

---

## 0. CRITICAL ORDERING CONSTRAINT — read before anything else

**The MCP write gate is master-switch-gated, and its default is OFF — which means "no gate".**

`McpWriteGate::isWrite()` (`app/Services/Toshi/McpWriteGate.php:23`) short-circuits:

```php
if (! config('toshi.mcp_write_gates.master_switch', false)) {
    return false;   // ← EVERY tool (including slack_post_message) classifies as "read"
}
```

With `TOSHI_MCP_WRITE_GATES_ENABLED` unset/false, `ApprovableMcpTool::needsApproval()`
returns `false` for `slack_post_message` → **posts execute immediately, no human approval,
defense-in-depth `assertExecutable()` passes too**. This is exactly the "ungated MCP write
regression" the connector plan (A.3/A.5) exists to prevent.

**Therefore the write gates MUST be on (Step 3, batch B) in the same deploy — and strictly
BEFORE `TOSHI_SLACK_CHANNEL_ENABLED=true`.** Never ship the channel flag without the gate flag.

Second hazard: `TOSHI_SLACK_CHANNEL_ENABLED=true` **while** `SLACK_MCP_MODE=mock` (the default)
leaves the named client pointing at the local mock fixture server — school admins would get
fake Slack data from a real-looking UI. **Always set `SLACK_MCP_MODE=live` and
`TOSHI_SLACK_CHANNEL_ENABLED=true` in the same batch, never one alone.**

---

## 1. Every env var that gates live mode (complete list, code-verified)

| # | Env var | Default | Where read | What it is |
|---|---------|---------|------------|-----------|
| 1 | `SLACK_MCP_MODE` | `mock` | `config/services.php:78` → `routes/ai.php:20` | Mode branch for the named `slack` MCP client. **Any value that is not exactly `mock` = live mode** (typo → accidental live). `live` selects the remote `mcp.slack.com/mcp` client + registers the per-school OAuth connect/callback routes. |
| 2 | `SLACK_MCP_URL` | `https://mcp.slack.com/mcp` | `config/services.php:79` + `config/toshi.php` catalog `endpoint` | Remote MCP endpoint override. Leave default unless Slack changes the URL. |
| 3 | `SLACK_MCP_CLIENT_ID` | unset | `config/services.php:80` + `config/toshi.php` catalog `oauth_client_id` | OAuth client ID of **our registered Slack app**. Dual consumer: OAuth flow (`routes/ai.php:42`) **and** the registry token-refresh service (`McpConnectorTokenRefreshService`). |
| 4 | `SLACK_MCP_CLIENT_SECRET` | unset | `config/services.php:81` + catalog `oauth_secret` | OAuth client secret. Same dual consumer as #3. |
| 5 | `TOSHI_SLACK_CHANNEL_ENABLED` | `false` | `config/toshi.php` catalog `enabled` | Master gate for the **feature surface**: SlackSkill instructions/tools, and the Slack tile in School Settings → Integrations (`IntegrationsController` filters the catalog on it). False = connector invisible everywhere. |
| 6 | `TOSHI_MCP_WRITE_GATES_ENABLED` | `false` | `config/toshi.php` `mcp_write_gates.master_switch` → `McpWriteGate::isWrite` | Master switch for write classification **across all connectors**. **Must be `true` for any live connector** (see §0). |
| 7 | `TOSHI_SLACK_MCP_WRITE_MODE` | `deny` | `config/toshi.php` `mcp_write_gates.connectors.slack.mode` | How Slack tools classify: `deny` = fail closed (ALL tools incl. reads pause for approval — nothing works without approvals); `classify` = catalog `read_tools` free, `write_tools` gated (**recommended**); `allowlist` = only `slack_post_message` gated, everything else free. |
| 8 | `SLACK_MCP_TIMEOUT` | `30` | `config/services.php:83` → `routes/ai.php` client timeout (s) | HTTP timeout for MCP calls. Leave default. |
| 9 | `SLACK_MCP_TOKEN` | unset | `config/services.php:82` | ⚠️ **Dead config** — no runtime consumer exists in `app/`/`routes/` (verified by grep). Spike-era leftover. **Do not set in production**; a future cleanup PR should remove it. |

Prerequisites already outside this feature's own flags (Slack rides on the Toshi v2 stack —
these are presumably already live; verify, don't assume):

| Env var | Why it matters here |
|---------|---------------------|
| `TOSHI_SDK_V2_ENABLED` | `AgentToshi`/`ToshiSdkV2Service` route to `ToshiOrchestrator`, which registers `RouteToSlackSkillTool`. Off = Slack tool unreachable no matter what Slack flags say. |
| `OPENAI_COMPATIBLE_URL` / `OPENAI_COMPATIBLE_MODEL` / `OPENAI_COMPATIBLE_API_KEY` | `SlackSkill` uses `UsesToshiLlm` — it calls `prompt()` itself and resolves the same provider/model. Misconfigured LLM = skill cannot run (and the known `AmbiguousToshiLlmConfigException` guard will fire loudly if the two env families conflict). |
| `APP_URL` | The OAuth redirect URI is built with `route('mcp.oauth.slack.callback')` (vendor `WebClient.php:73`) — it resolves against APP_URL. On Cloud this must be `https://klassapp.xyz` (staging: the `*.laravel.cloud` URL). Wrong APP_URL = redirect-URI mismatch at Slack. |
| `schools.toshi_enabled` (DB, per school) | Per-school Toshi gate (`config/toshi.php` `per_school_gate`). A school without it has no Toshi at all — no Slack either. |

## 2. What each flag controls (wiring map, verified)

- `routes/ai.php`: `mode=mock` → stdio mock client; `else` (anything ≠ 'mock') →
  `Client::web(mcp.slack.com/mcp).withOAuth(client_id, client_secret, 'mcp:read mcp:write')`
  with per-request token closure → `SchoolMcpConnector::resolveTokenForRequest('slack')`
  → throws `ConnectorNotConnected` when the school has no active registry row.
  Live mode also registers `mcp/slack/connect` + `mcp/oauth/slack/callback` (vendor
  `OAuthRouteRegistrar`, `web` middleware).
- OAuth callback handler (`routes/ai.php`): resolves `Auth::user()->school_id`, maps
  Slack `team_id` → registry row `external_team_id`, stores encrypted TokenSet,
  `status=active`, `write_mode=deny` (row-level value is display-only — see §4).
- `IntegrationsController@index`: shows the Slack tile only when catalog `enabled`;
  Connect button only when `SLACK_MCP_MODE==='live' && filled(SLACK_MCP_CLIENT_ID)`
  (`slackConnectable`). Route group is `web,auth,fullschooladmin` (`RouteServiceProvider`,
  `admin/settings/integrations`).
- `SlackSkill`: `enabled=false` → no tools + "disabled" instructions; school not connected →
  catches `ConnectorNotConnected` and returns a friendly `slack_status` tool telling the
  admin to connect in Settings → Integrations. Reads execute immediately (audited);
  writes pause for approval via `ApprovableMcpTool` when the gate classifies them as writes.
- **Per-school write gating note**: the registry row's `write_mode` column (`deny` default)
  is **not consulted by the gate** — classification is env-level only (§1 #7). The row's
  `write_mode` is currently display-only in the UI. Per-school kill switch = the row's
  `status` (`active`/`disabled`), which *is* consulted by `resolveTokenForRequest()`.

## 3. Failure modes with wrong/placeholder values (misconfiguration risk table)

| Misconfiguration | Behavior | Loud or silent? |
|---|---|---|
| `TOSHI_MCP_WRITE_GATES_ENABLED` off + Slack enabled | **Writes execute ungated.** Silent at config time; the ONLY trace is the audit row showing no approver. | **SILENT — worst case.** This is why §0 exists. |
| `TOSHI_SLACK_CHANNEL_ENABLED=true` + `SLACK_MCP_MODE=mock` | UI tile shows but says "Connect unavailable (instance not in live mode)"; Toshi's Slack answers come from the **mock fixture server**. | Semi-silent — tile visible, but connect impossible; data is fake. |
| `SLACK_MCP_MODE` typo (e.g. `Live`, `production`) | Anything ≠ `mock` = live mode. With credentials absent: `withOAuth(null, null)` → OAuth falls back to RFC 7591 dynamic client registration against Slack's auth server. Slack does not support it → the connect route errors. Connect button is already hidden (`filled(client_id)` guard) but the route itself is registered and would fail loudly if hit directly. | Loud (500 at the route), UI guarded. |
| Client ID/secret placeholder or wrong | OAuth discover → authorize redirect renders; Slack rejects the client → error page on Slack's side. Secret wrong → token exchange fails at callback with `OAuthException`. | Loud (user-visible error at OAuth). |
| School not connected (no registry row) | `resolveTokenForRequest` → null → `ConnectorNotConnected` → skill returns friendly `slack_status` message: "connect it in School Settings → Integrations". | Fail-closed, friendly, **by design**. |
| Token expired + refresh fails (e.g. secret rotated in Doppler but not deployed) | Refresh posts to `slack.com/api/oauth.v2.access`, fails, logs error, `resolveTokenForRequest` returns null → same friendly "not connected" state. Token stays active in DB — next successful refresh recovers. | Semi-silent (Laravel log has `McpConnectorTokenRefresh: refresh failed`; user sees "not connected"). |
| `APP_URL` wrong/missing | `route('mcp.oauth.slack.callback')` resolves to wrong host → Slack rejects redirect_uri mismatch at authorization. | Loud at first connect attempt. |

## 4. Slack-side app configuration (what the human creates in api.slack.com)

- Create a Slack app (api.slack.com/apps → "From scratch" or per Slack's hosted-MCP
  instructions), one per KlassApp (single global OAuth app; per-school rows are data,
  not apps — one app, many `school_mcp_connectors` rows).
- **Redirect URI must be registered exactly** (code builds it via
  `route('mcp.oauth.slack.callback')`):
  - Production: `https://klassapp.xyz/mcp/oauth/slack/callback`
  - Staging: `https://klassapp-staging-7mpoqg.laravel.cloud/mcp/oauth/slack/callback`

  (Route = `mcp/oauth/{client}/callback`, client `slack` — vendor default, no custom URI
  passed in `routes/ai.php`. Trailing slashes and path variants will fail — OAuth
  redirect_uri matching is byte-exact.)
- Scopes: the app requests the MCP-level scope string **`mcp:read mcp:write`** (hardcoded in
  `routes/ai.php:44` withOAuth call). Slack's hosted MCP server maps these to the underlying
  Slack API scopes for the tools we consume — `slack_list_channels`, `slack_search`,
  `slack_get_channel_history` (reads) and `slack_post_message` (write). Follow Slack's
  MCP get-started guide when creating the app; if the Slack app config asks for explicit
  bot/user scopes, the set implied by those four tools is `channels:read`, `search:read`,
  `channels:history` (or `groups:history` where private), `chat:write`, `team:read`.
  **Verify against the live Slack app config screen at setup time — do not guess.**
- Note the app's Client ID and Client Secret → Doppler (§5), never the repo.
- Transport-era caveat (accepted, tripwired): our client speaks the pre-2026 MCP era
  (laravel/mcp 0.8.x); `mcp.slack.com` serves it until at least the 2027-04-28 re-verification
  date (`tests/Architecture/McpTransportEraReverificationTest.php`). No action for go-live.

## 5. Where secrets live

Doppler project **`klassapp`** (configs `dev` / `stg` / `prd` — existing convention,
see knowledge.md). Keys to set in `stg` first, then `prd`:

- `SLACK_MCP_CLIENT_ID`
- `SLACK_MCP_CLIENT_SECRET`

Then mirror into Laravel Cloud environment variables (Cloud env vars only take effect
after a **real deployment** — knowledge.md rule). The other flags
(`SLACK_MCP_MODE`, `TOSHI_SLACK_CHANNEL_ENABLED`, `TOSHI_MCP_WRITE_GATES_ENABLED`,
`TOSHI_SLACK_MCP_WRITE_MODE`) are non-secret and can go straight into Cloud env vars.

Never commit secret values to the repo, knowledge.md, or PR text.

## 6. Recommended rollout order

Per-school gating **is supported by design**: the connector is per-`school_mcp_connectors`
row (school_id + team_id). The env flags are instance-wide, but a school only gets Slack
functionality when **its own admin completes the OAuth connect** — schools without a row
get the friendly "not connected" status. So the pilot mechanism = "only the pilot school
connects".

1. **Batch A (wire, still dark):** Doppler `stg`: `SLACK_MCP_CLIENT_ID/SECRET`; Cloud staging
   env: `SLACK_MCP_MODE=live`, `TOSHI_MCP_WRITE_GATES_ENABLED=true`,
   `TOSHI_SLACK_MCP_WRITE_MODE=classify`. Deploy staging. Verify: integrations tile shows,
   connect works with the staging admin, mock→live switch has no stray mock server.
2. **Staging end-to-end:** connect the staging school's workspace, run reads
   (list/search/history) + a gated `slack_post_message` → approve in the panel → message
   lands in the real channel; verify audit rows + disconnect/reconnect.
3. **Batch B (production, pilot dark):** Cloud prod env: same flags as staging
   (`SLACK_MCP_MODE=live`, `TOSHI_MCP_WRITE_GATES_ENABLED=true`,
   `TOSHI_SLACK_MCP_WRITE_MODE=classify`) but **leave `TOSHI_SLACK_CHANNEL_ENABLED=false`**
   for the first deploy so nothing user-visible changes. Verify OAuth routes exist
   (`/mcp/slack/connect` 302s to Slack, not 500) and logs are clean.
4. **Batch C (pilot on):** set `TOSHI_SLACK_CHANNEL_ENABLED=true`, deploy, have **only the
   pilot school's** fullschooladmin connect. Verify end-to-end with them: reads + one
   approved write. Confirm other schools still show "not connected" (per-school isolation).
5. **Broaden:** schools connect at their own pace; keep `classify` mode. Revisit
   `allowlist` vs `classify` only if read-volume friction appears (allowlist gates only
   `slack_post_message`, everything else free — slightly less conservative).
6. Only after stable: consider the deferred wave-2 items (inbound events, interactive
   approval blocks) — out of scope here.

## 7. Rollback plan

- **Instance-wide kill switch:** set `TOSHI_SLACK_CHANNEL_ENABLED=false` → connector invisible,
  skill tools empty, RouteTo tool answers "disabled". One env var + deploy.
- **Full revert to dormant state:** also set `SLACK_MCP_MODE=mock` → named client returns to
  the local mock server; OAuth routes unregister. (Leave `SLACK_MCP_MODE=live` +
  `TOSHI_SLACK_CHANNEL_ENABLED=false` if you want to keep the wiring warm instead.)
- **Per-school emergency off:** School Settings → Integrations → Disconnect →
  registry row `status=disabled` (never deleted — standing rule #3). Takes effect on the
  next token resolution; no deploy needed.
- **Data:** rollback is env-var + registry-status only. The `school_mcp_connectors` table and
  its rows persist harmlessly; encrypted tokens just sit unused. **No data migration is
  needed to undo any of this** (the feature shipped fully in #684; nothing to un-migrate).
- **Audit note:** every read/write already lands in the Toshi MCP audit trail regardless of
  rollback — post-incident review works even with the connector off.
