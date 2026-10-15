# WhatsApp simulator (local only)

A local mock of the WhatsApp Cloud API plus a signed-webhook sender, a CLI, a one-page
chat UI and the demo scenario runner. Node 20, no dependencies, loopback addresses only.

Full guide, environment variable names and the Level 2 (Meta development app) steps:
[docs/ops/LOCAL-WHATSAPP-TESTING.md](../../docs/ops/LOCAL-WHATSAPP-TESTING.md).

```bash
node e2e/whatsapp-sim/setup-local-env.cjs            # local .env keys, local app secret
node e2e/whatsapp-sim/server.cjs                     # mock + UI on http://127.0.0.1:9123
node e2e/whatsapp-sim/cli.cjs send --from +15550100001 --text "Hi"
node e2e/whatsapp-sim/scenarios.cjs --out /folder/outside/the/repo
```
