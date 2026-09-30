# KlassApp design

**Source of truth for all design work.** Import Claude Design exports here; do not keep parallel handoff ZIPs elsewhere.

| Path | What |
|---|---|
| `system/` | Full design-system export: tokens, components, guidelines, concepts, assets, `SKILL.md`, `handoff/`. |
| `system/guidelines/` | Handoff guides (including dated `handoff-*.md`). |
| `system/handoff/` | Implementation packages (e.g. `handoff/emails/`). |
| `handoffs/2026-09-30-avatar/` | Earlier small avatar handoff (#908); superseded for new work by `system/`. |

## Drift check (required before each implementation PR)

`system/github.md` records the last design↔repo sync (**2026-09-28**, commit `dbe68419`). Before starting a PR from a handoff, run `git log --oneline dbe68419..main` (and re-read any listed files that moved) so you do not implement against a stale tree.
