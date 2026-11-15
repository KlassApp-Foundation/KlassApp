# n8n operations runbook

**Audience:** the n8n owner and anyone covering for them.
**Workspace:** `https://klassapp.app.n8n.cloud`

These are internal automations. They send internal email and post to the owner's connected LinkedIn account. They send nothing to schools, parents or learners.

> **Still to confirm:** the **PR review** workflow. Its details are not in the 15 Nov report; every cell for it stays **Confirm** until the owner fills it in.

## The workflows

| Workflow | When | What it does |
|---|---|---|
| **Weekly brief** | Monday 07:00 | Sends one email per active contributor who has an email on file. Each copy has **Your tasks** plus the team section: completed this week, moved to In review, PRs merged and open, unlinked PRs, and overdue tasks with owners. The owner is copied. People with no email are listed in the footer. A failed send raises a one-line alert. A preview run sends every copy to the owner only. |
| **Daily digest** | Daily — time **Confirm** | Sends only to the owner. Includes a Social line. |
| **Error alert** | On error | Sends a one-line error to the owner. It also covers errors in the early-adopters workflow. |
| **GitHub to tracker** | On a signed webhook from a GitHub Action | Matches `T-` numbers in PR text to Tasks rows and sets Status, PRs, PR state, Progress and Last activity. Unlinked PRs go to the **Unlinked PRs** tab. A PR closed without merging never moves a task to Done by itself. A forged or unsigned request gets **401**. Has a dry-run switch; with dry run on it writes nothing. |
| **PR backfill** | Manual, run once | Fills the tracker with older pull requests. Dry run first; compare the plan with the real rows before turning dry run off. |
| **Social posts** | Daily 08:00 | An **Idea** row with an empty **Draft** gets an AI draft and becomes **Draft**. A row posts only when **Status** is **Approved** and its date is today or earlier. LinkedIn posts go out; other channels are emailed to the owner to paste, and the row stays **Approved** with a note until the owner sets **Posted**. Posted links go in the **Posted link** column. Nothing posts without a row set to **Approved**. |
| **PR review** | **Confirm** | **Confirm.** The 15 Nov report does not describe this workflow. Do not guess its trigger or destination; ask the owner. |

Do not rename a workflow without updating this table.

## Dry run

Dry run is a switch inside each workflow, the `DRY_RUN` setting, plus the editor's own test run. It is not "disable the send node".

1. Open the workflow and check `DRY_RUN` is on.
2. Use **Execute workflow** or **Test step**, with pinned test data.
3. Read every node's output. Confirm the destination: right mailbox, right repository, right tracker project, right social account.
4. With dry run on, the workflow writes nothing to the destination. Check the run's output says so, rather than assuming.
5. For **Social posts**, dry run must not publish. Confirm no live post was created.
6. For **GitHub to tracker**, a dry run still receives the signed request; check that the plan lists the right rows and that no cell changed.
7. For **PR backfill**, run with a small limit, compare the plan with the tracker's real rows, then turn dry run off.

A workflow may be published and active while it is still in dry-run mode. That is allowed for testing.

## Publishing and turning dry run off

1. Save the workflow.
2. Confirm the credential on every node is the production one, not a personal token.
3. Activate it — still with `DRY_RUN` on — and watch the first run in **Executions**.
4. Read the output. A green run is not proof.
5. **Turning `DRY_RUN` off needs the owner's explicit approval, one workflow at a time.** Never turn it off for several workflows in one go.
6. Record: workflow, what changed, who approved, time.

## When a workflow fails

1. Open **Executions** and read the failing node and its error.
2. If the failure is a credential, rotate it (see `secrets-and-webhooks.md`) and re-run.
3. If the failure is a destination (mailbox, repository, tracker, social account), fix the destination and re-run.
4. If the failure is data, correct the input and re-run from the failed node where n8n allows it.
5. Tell the owner:
   - **Weekly brief / daily digest** — the owner named in the workflow; the brief already sends a one-line alert on a failed send.
   - **Error alert** — the platform owner, if the alert itself is failing.
   - **GitHub to tracker / PR backfill** — the tracker owner.
   - **Social posts** — the owner of the connected LinkedIn account.
   - **PR review** — **Confirm** with the n8n owner.

If an automation has been failing for more than a day, say so in the team channel rather than silently retrying.

## Safety

- These workflows send **internal email** and post to the **owner's connected LinkedIn account**. They send nothing to schools or parents.
- Never put a token in a workflow's plain field; use the credential entry.
- A LinkedIn post is public. Only an **Approved** row posts, and only its owner can approve it.
- Keep the error alert working: it is the only signal that another workflow stopped.
- No email address, token or account id belongs in this page.
