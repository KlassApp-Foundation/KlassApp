# PhpStorm MCP direct-HTTP

Use this skill whenever you need structured PHP/Laravel code access in KlassApp and the platform-level `phpstorm` MCP server type is unavailable.

## When to use

- Reading Laravel source files via `get_file_text_by_path`.
- Inspecting Eloquent models via `laravel_idea_get_eloquent_model`.
- Looking up Laravel routes via `laravel_idea_get_routes`.
- Running PhpStorm inspections via `run_inspections`.
- Any other situation where `mcp_call_tool` for the `phpstorm` server fails with "The agent does not support the following MCP servers: phpstorm".

## Important caveats

- Devin's platform does **not** support the `phpstorm` MCP server type directly. This is not a config or header issue.
- Use the **native PhpStorm MCP server** built into PhpStorm 2026.1+, running at `http://127.0.0.1:64342/stream`.
- Do **not** confuse this with the third-party "MCP Server AI Companion" plugin, which returns `405` and is a different problem.

## Initialize

```bash
init_resp=$(curl -s -i -X POST http://127.0.0.1:64342/stream \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"jsonrpc":"2.0","method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"devin","version":"1.0"}},"id":1}')

session_id=$(echo "$init_resp" | grep -i 'mcp-session-id' | cut -d: -f2 | tr -d ' \r')
```

## Call a tool

```bash
curl -s -i -X POST http://127.0.0.1:64342/stream \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -H "mcp-session-id: $session_id" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/call",
    "params": {
      "name": "get_file_text_by_path",
      "arguments": {
        "projectPath": "/Users/mac/projects/KlassApp",
        "pathInProject": "app/Models/User.php",
        "maxLinesCount": 200
      }
    },
    "id": 2
  }'
```

## Confirmed tools and exact parameters

- `get_file_text_by_path` — requires `projectPath` and `pathInProject` (not `path`). Optional `maxLinesCount` and `truncateMode`.
- `laravel_idea_get_eloquent_model` — requires `projectPath` and `modelFqn` (e.g. `App\\Models\\User`).
- `laravel_idea_get_routes` — requires `projectPath`; optional `urlPattern` or `routeTargetPattern`.
- `run_inspections` — confirmed available.

## Verified examples

- `get_file_text_by_path` on `knowledge.md` returned real file content.
- `laravel_idea_get_eloquent_model` on `App\\Models\\User` returned ~12 KB of real fields, relations, and related files.
