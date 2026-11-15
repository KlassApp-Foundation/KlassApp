# n8n operations runbook

**Audience:** the n8n owner and anyone covering for them.
**Scope:** the internal automations in the n8n workspace. These are not the WhatsApp conversation path and they do not send school messages.

> **Confirm before relying on this page:** the workspace URL, each workflow's id, its schedule, its destination, and the credential names. Those details live in n8n and in the credential store, not in this repository. The table below records what each workflow is for; treat every "Confirm" cell as a task for the owner.

## The workflows

| Workflow | What it does | Typical trigger | Owner |
|---|---|---|---|
| **Weekly brief** | Sends the team a weekly summary of the week's work and numbers | Weekly schedule — confirm day and time | n8n owner |
| **Daily digest** | Sends a short daily digest of activity | Daily schedule — confirm time | n8n owner |
| **Error alert** | Posts an alert when a workflow or a watched service fails | Error trigger or schedule — confirm | n8n owner |
| **GitHub to tracker** | Copies new or changed GitHub issues and pull requests into the tracker | GitHub webhook or poll — confirm | Tracker owner |
| **PR backfill** | Fills the tracker with older pull requests that predate the sync | Manual, run once per backfill | Tracker owner |
| **Social posts** | Publishes or queues scheduled social posts | Schedule or manual — confirm | n8n owner |
| **PR review** | Reminds the team about pull requests waiting for review | Daily schedule — confirm | n8n owner |

Do not rename a workflow without updating this table. Do not change a trigger's schedule without telling the people who read its output.

## Dry run

Every workflow can be run without publishing:

1. Open the workflow in n8n.
2. Use **Execute workflow** with test data, or open the trigger node and use **Test step**. Pin the sample data you used.
3. Read every node's output. Check the destination: the right channel, the right repository, the right tracker project.
4. For **Social posts**, do not execute a publish node against a live account. Stop before the publish step, or use the provider's test mode.
5. For **GitHub to tracker**, run against a test repository or a dry-run copy of the tracker project first.
6. For **PR backfill**, run with a small limit first and compare the created items with GitHub.

A dry run writes nothing to the destination when the publish or send node is disabled. Check that the node is disabled before you run.

## Publish

1. Save the workflow.
2. Confirm the credential selected on every node is the production one, not a personal token.
3. Turn the workflow **Active**.
4. Watch the first scheduled run in **Executions**. A green first run is not proof; read the output.
5. Record the change: workflow name, what changed, who approved, time.

## When a workflow fails

1. Open **Executions** and find the failed run. Read the node and the error, not just the red status.
2. If the failure is a credential, rotate it (see `secrets-and-webhooks.md`) and re-run.
3. If the failure is a destination (channel, repository, tracker), fix the destination and re-run.
4. If the failure is data, correct the input and re-run from the failed node where n8n allows it.
5. Tell the owner of the destination:
   - **Weekly brief / daily digest** — the team channel's owner.
   - **Error alert** — the platform owner, if the alert itself is failing.
   - **GitHub to tracker / PR backfill** — the tracker owner.
   - **Social posts / PR review** — the n8n owner.

If an automation has been failing for more than a day, say so in the team channel rather than silently retrying.

## Safety

- Nothing in this workspace sends school or parent messages.
- Never put a token in a workflow's plain field; use the credential entry.
- A workflow that posts publicly (social posts) needs a person to approve the content before it is activated.
- Keep the error alert working: it is the only signal that another workflow stopped.
