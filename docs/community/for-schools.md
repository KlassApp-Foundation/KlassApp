# For schools

How KlassApp works for school admins and staff — based on what is **shipped today**.

---

## What KlassApp is

KlassApp is the school’s operating system in the browser: multi-tenant by `school_id`, with role portals for admins, teachers, and other staff. It is **not** only a WhatsApp bot bolted onto someone else’s ERP.

Parents use **WhatsApp** (Meta Cloud API) for the questions they ask most. Google Drive and Slack appear in the product UI as first-class channels in the connector model; **WhatsApp is the connector live in production** today.

Live site: [klassapp.xyz](https://klassapp.xyz)

---

## Four surfaces (shipped)

The coordinated design cutover covers:

1. **Marketing / auth / errors** — public landing and login shells on the design system  
2. **Admin dashboards** — KPI cards, rosters, fees, exams/marks kit  
3. **Onboarding wizard** — guided school setup (terms, classes, teachers, students, fees, plan)  
4. **Toshi panel** — in-dashboard AI agent chrome (docked / pill / mobile)

See the public [`roadmap.md`](../roadmap.md) for what is shipped vs still future.

---

## Day-to-day operations

| Area | What staff can do |
|---|---|
| **Academics** | Classes/streams, subjects, terms, exams, marks, report cards |
| **People** | Students, teachers, parent links (approvals inbox) |
| **Fees** | Structures, recording payments, reminders over WhatsApp |
| **Attendance** | Class attendance; parent-facing summaries on WhatsApp when linked |
| **Toshi** | Guided setup and known-method flows in the web panel (human confirmation on sensitive steps) |

**Toshi free-form chat** (open-ended LLM answers) is a separate capability and remains **gated** until intentionally enabled. Guided/onboarding flows do **not** require that gate.

---

## Parents on WhatsApp

Once a parent is linked to a student, they can use interactive menus / keywords for common asks (fees, grades, attendance, report PDF where enabled). Delivery is logged for the school.

Parent linking is school-mediated (link requests + approvals) — not “type a national ID and you’re in for every school automatically.”

---

## Plans

| Plan | Price |
|---|---|
| Freemium | Free |
| Growth | **$35** / cycle |
| Premium | Custom pricing |

---

## Next steps

- New school? [School onboarding](school-onboarding.md) or [book a session](book-onboarding.md)  
- Product direction: [`docs/roadmap.md`](../roadmap.md)  
- Technical depth: [Architecture bridge](../architecture.md) · [DeepWiki](https://deepwiki.com/KlassApp-Foundation/KlassApp)
