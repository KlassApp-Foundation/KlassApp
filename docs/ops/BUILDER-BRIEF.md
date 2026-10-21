# Builder brief

Public. No passwords, tokens, environment identifiers, or audit findings.

Read this before planning. `AGENTS.md` is still the rule set. `knowledge.md` is the history. If they disagree on hosting, `knowledge.md` wins.

## Mission

A stable, tested demo on staging for 17 October 2026. The recording window is Friday 16 October, 13:00–17:00 EAT. Staging must already be idle and freshly refreshed before that window.

## Roles

- The Cursor agent on this checkout is the only agent that edits application code and the only agent that merges.
- Grok captures and audits. Grok does not merge.
- Rasta decides scope. While Rasta is away, follow the away policy below.

## Queue and status (14 October 2026, evening EAT)

Done and on `main`:

- Account menu, drawer, tabs, fees labels, gender counts, exam line, Message buttons, search, greeting, Clear selection, WhatsApp STOP/OPTIN keywords, and the "Open WhatsApp links" label.
- Toshi launcher: 36×96 handle from 1280px while the panel is closed, and the pill by mode. The first-paint fix is merged.
- Parent profile is merged.

Still to do, in this order:

1. This brief, through a docs-only pull request, on green checks.
2. Confirm the staging onboarding helpers on a throwaway school. Change no real school.
3. The gate on the `main` commit that contains the parent profile and the pill fix: five untraced primary-manual runs at 1280, then 14 runs with traces off. Purge the throwaway schools the gate created (dry run first).
4. Freeze. Post `FREEZE REACHED` with the commit. After that, only reverts and blocker fixes.
5. Friday 16 October. See Freeze and Friday below.

WhatsApp work is parked until Rasta returns (known item: the demo-school guard also blocks replies to a user's own message). Do not start a consent, template, metering, or guard change.

If Rasta posts a final polish list in chat before 18:00 EAT on Thursday 15 October, build that list as one pull request first (each test red without the fix and green with it), then run the gate on that commit. Otherwise run the gate on current `main`.

## Merge discipline

One merge at a time. Check, scan, and test-guard must be finished green on the commit you merge. Pending is not a pass. Staging's newest deployment must not be running. Confirm `merged: true` and that the merge commit is an ancestor of `origin/main`. A stacked pull request is retargeted to `main` first. If a merge breaks staging, open a revert first and tell Rasta.

While Rasta is away, admin merge is allowed only for a pull request on the list above, and only after those checks are green.

## Rule 48

Every bug fix ships with a test named after its ID. The test is red without the fix and green with it. Behaviour bugs get a PHPUnit or Livewire test. Visual and interaction bugs get a browser test with measurements (`getBoundingClientRect`, `elementFromPoint`, a real pointer path). A screenshot alone is not the test. A bug that cannot be tested is recorded, with the reason, outside this public repo.

## Specs to build from

1. `design/system/SOURCE-OF-TRUTH.md` when that file is in the tree. It is the named index. It was not in the tree when this brief was written.
2. The merged handoff under `design/system/`, including `design/system/klassapp-handoff-2026-10-01-documents/HANDOFF.md` and `design/system/guidelines/`.
3. Concept frames under `design/system/concepts/`.
4. `docs/ops/demo-walkthrough.md`.
5. `docs/ops/production-deploy-checklist.md`.

## Decisions already made

- No fake buttons. A control that does not work is not drawn.
- The people bulk bar has Export, Clear selection, and Done only. No Message parents. No Move to class.
- The top bar stays.
- Toshi is one launcher with two shapes. From 1280px up, while the panel is closed, the handle is a 36×96 white edge tab with a 1px slate border and a K in `#15803D`, centred in the space below any sticky header. The pill is white, bottom right, just left of that gutter, with the green K in a ring and a 14px/700 label in `#0F172A`. Preview says "Meet Toshi" until that user has opened Toshi once, then the handle only. Setup guide says "Setup guide" with an n/N chip that uses the same count as the setup bar, while setup is incomplete. Assistant says "Talk". "Early access" stays in the panel header and the screen-reader label. Below 1280px there is no handle and no floating pill. The pill sits 16px above a bottom bar's top edge, computed from the `[data-sticky-bar]` element. While the pill shows, the page gets 100px of extra bottom padding.
- Demo schools never send messages. The fee reminder for a demo or test school reads: "Reminder ready for {n} parent(s). This is a demo school, so nothing was sent."
- The parent page stays on `GET /admin/parent/show/{name}`. Deactivate sets the user and the profile to inactive and keeps the row. It does not hard-delete. Children come from the parent link, same school, active students. Class comes from the section through `standards_link`. Fees that were never recorded stay an empty state. Do not render profession, annual income, organisation, designation, or official address.
- Wording is global. Do not frame the product as one country or as Africa.
- Tagline: "An open education protocol for humans and agents."

## Failure playbook

- Staging is red after a merge: open a revert first, then tell Rasta.
- A required check fails once for a reason that looks like a flake: re-run that check once.
- GitHub returns 500 while downloading a package during a deploy: retry the deploy once.
- A deploy stays running for more than 30 minutes: stop and report. Do not start another one.

## Credentials

Names only. Never write values into a file, a log, a pull request, an issue, or a chat message.

- Cloud API token — read from Doppler. The secret name is in the private repo KlassApp-Foundation/internal or the Cloud dashboard.
- `MAIL_MAILER` — mail driver name.
- `TOSHI_DEFAULT_MODE` — creation default for Toshi. Do not change it.
- `E2E_ACCOUNT_PASSWORD` — throwaway journey password, generated in the shell, never stored.
- `E2E_STG_ENV_ID` — which Cloud environment the staging bridge may call. The bridge refuses anything that is not staging.

## Away policy

You may continue the queue in this brief and merge a listed pull request whose checks are finished green, in the order above. You may re-run the gate. On an ambiguity, take the safest default and log it.

You may not change scope, touch production, change Laravel Cloud settings, create or reset accounts, or merge anything that is not on this list.

If a decision blocks you for more than two hours, take the safe default, log it, and go to the next item.

## Freeze and Friday

The freeze commit is `main` after the gate passes. Post `FREEZE REACHED` with that commit in the status log. After the freeze, the only merges are reverts and blocker fixes.

Friday 16 October, EAT:

- 12:00 — `demo:refresh` for schools 38 and 39, dry run first, then the role crawl. Check that the demo admin can log in and that the dashboard shows the 72 percent and 70 percent fees tiles and varied gender rows.
- 12:30 — tell Rasta that staging is idle.
- 12:30–17:30 — no merges, deploys, journeys, or refreshes on staging.
- 19:00 — a second `demo:refresh` for schools 38 and 39, dry run first, and the same checks. Move this to 07:00 Saturday only if Rasta says so.

## Status log

Milestones go on the GitHub issue titled "Builder status log", one comment each:

`time EAT | what | state | next`

No secrets in that issue.
