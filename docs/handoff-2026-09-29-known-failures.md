# Handoff — known-failures refresh after #887 (2026-09-29)

## State right now

- PR #888 (AGENTS.md rule 31 + knowledge.md session stamp) squash-merged as `8bc3c86f`, branch deleted, confirmed `merged: true`.
- User instruction (pending): after #888, run `bash scripts/test-guard.sh` on current main; if any test in `tests/known-failures.txt` now passes (since #887 "green the full suite"), remove them from the list in a small PR, merge, confirm `merged: true`.

## What a suite run on current main showed (evidence at /tmp, re-run if missing)

- Guard comparison on `main` = `8bc3c86f`: `known=137 current_failing=51 recovered=86 new=0` — extractor: junit XML from `vendor/bin/phpunit --log-junit`.
- The 86 recovered names were captured in `/tmp/recovered.txt` (may not survive a reboot; re-extract the same way if needed).
- #888's `tests/known-failures.txt` on main still lists all 137 (it predates #887's suite greening).

## Branch carrying the in-progress refresh

- Branch: `chore/known-failures-refresh-887` (from `origin/main` at `8bc3c86f`).
- Working-tree edit already applied: `tests/known-failures.txt` trimmed by the exact 86 recovered entries -> **137 -> 51** lines. NOT yet committed/pushed.

## Remaining steps (any contributor, no judgement calls left)

1. Verify the guard stays green with the 51-entry list: `bash scripts/test-guard.sh` — expect `[test-guard] OK: 51 failing test(s)... 0 previously-known failures now passing` approx (exact wording: OK with 0 NEW). Run it from the branch checkout; expect exit 0.
2. Commit `tests/known-failures.txt` (and this handoff file or delete it after pickup), push branch, `gh pr create --base main --head chore/known-failures-refresh-887 --title "chore: refresh tests/known-failures.txt (86 recovered by #887, 137 -> 51)" --body "..."`.
3. Squash-merge with `--admin --delete-branch`, confirm `merged: true` via the API before updating any status docs.

## Known local-run gotchas

- Full suite takes about nine minutes on this machine (`vendor/bin/phpunit --log-junit /tmp/junit.xml`), and long-running phpunit child processes have twice been SIGTERM'd from outside; run detached with nohup and a marker file, poll, don't block on the tool timeout.
- Do not re-audit #887 (owner knows it).
- The bash workspace shell still exports `.env` values (`APP_ENV=local` etc.) — the harness (phpunit.xml `force` + CreatesApplication guard) protects runs; see AGENTS.md rule 31 and the #886 trail.
