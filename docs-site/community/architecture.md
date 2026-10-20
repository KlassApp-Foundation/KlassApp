---
title: Architecture
---

# Architecture

KlassApp is a multi-tenant Laravel 12 application. Each school's rows are scoped by `school_id`. Staff use a role portal in the browser. Parents can use that portal and WhatsApp.

This page is the short map. It describes `main` today.

## The pieces

| Piece | Where it lives |
|---|---|
| HTTP | `app/Http/Controllers`, split by role (`Admin`, `Teacher`, `Parent`, `Student`, `Api`, `Superadmin`) |
| Routes | `routes/web.php`, `routes/api.php`, and one file per portal (`admin.php`, `teacher.php`, `parent.php`, `student.php`, `accountant.php`, …), loaded from `RouteServiceProvider` |
| Domain logic | `app/Services` |
| Menus | `config/navigation.php`, rendered by the sidebar partials |
| School assistant UI | Livewire `AgentToshi`, plus the `packages/toshi-ui` views |
| WhatsApp | Meta Cloud API. Inbound `POST /api/whatsapp/inbound`. Outbound through `WhatsAppBusinessService` |

Sign-in sends each account to its own dashboard: platform admin, school admin, head teacher (deputy admin), teacher, parent, student, or bursar. The menu for each one is documented under [Help](/help/).

## Multi-tenancy

Queries, jobs, cache keys, and lists that belong to a school carry `school_id`. Route bindings for school users resolve a record only inside the signed-in school. Another school's id is a 404, not a neighbour's row.

A request must not set `school_id` on a record that already exists.

`standard_id` is a grading band. `section_id` is the class. A screen that means "this class" filters on the section, not the band alone.

## Channels

WhatsApp on the Meta Cloud API is the live parent channel. The local stand-in is the [simulator](/community/whatsapp). Older Evolution API notes under `docs/dev/` are archived. Do not use them for the current design.

Google Drive, Google Classroom, and Google Sheets are **Coming soon**. They are not school connectors on `main`. See [Connectors](/community/connectors).

Toshi, KlassApp's assistant, can run a scripted setup chat. Free-form assistant mode is off for a new school until someone turns it on for that school. The default mode is preview, which shows **Coming soon** rather than a model call.

## One package today

`packages/toshi-ui` (`klassapp/toshi-ui`) is a path repository. It publishes the assistant's chips and confirmation card. It does not hold school logic. A wider package layout is an open proposal, not this tree. See [Packages](/community/packages).

## Read next

- [Local setup](/community/getting-started)
- [Tests](/community/tests)
- [DeepWiki — KlassApp](https://deepwiki.com/KlassApp-Foundation/KlassApp)
- In-repo bridge: `docs/architecture.md` on GitHub
