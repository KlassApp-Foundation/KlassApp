---
title: Packages
---

# Packages

## On main today

There is one Laravel package:

| Path | Composer name | What it is |
|---|---|---|
| `packages/toshi-ui` | `klassapp/toshi-ui` | Assistant UI only: suggestion chips and the tool confirmation card. No school logic. |

The root `composer.json` loads it as a path repository and requires `klassapp/toshi-ui`. The service provider publishes views and CSS.

Other seams exist in the app and are not packages a contributor can drop in:

- Per-school feature toggles, with a short fixed list of keys.
- A connector catalogue in `config/toshi.php` (Slack, Notion, Google Drive). Each one is off unless that environment flag is set. Only Slack has a skill class.
- Per-school grading rows (`SchoolGradingSystem`). The default scale shipped in config is not yet a package.
- Toshi skills registered in PHP, not discovered from a package.

## Proposal, not built

[Pull request #1119](https://github.com/KlassApp-Foundation/KlassApp/pull/1119) is a docs-only proposal. It is not merged. Nothing in it runs on `main`.

It suggests eight extension points the core would own and a package would implement:

1. Grading scales
2. Curriculum packs
3. Report card templates
4. Connectors
5. Payment gateways
6. WhatsApp commands
7. Toshi tools
8. Dashboard cards

A package would be built, registered, versioned, enabled per school, and tested. The core would still own tenancy, sign-in, permissions, student identity, the marks release gate, WhatsApp transport, and the rule that AI stays off until a school turns it on.

The first extraction it sketches is a grading package. That extraction is not in the pull request. It also waits on the marks-flow proposal ([#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118)), which is not on `main`. Marks and report-card help stays a placeholder until that flow lands.

Do not add a package, a registry, or a curriculum pack from this page. Wait until #1119 is accepted.

## Related

[Architecture](/community/architecture) · [Connectors](/community/connectors)
