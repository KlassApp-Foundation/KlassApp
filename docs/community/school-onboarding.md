# School onboarding

Get a school from empty tenant to usable operations using what’s **shipped**: the web **onboarding wizard** and **Toshi** in the dashboard.

Live product: [klassapp.xyz](https://klassapp.xyz) · Help: [community@klassapp.xyz](mailto:community@klassapp.xyz) · [Book a session](book-onboarding.md)

---

## Paths

| Path | When to use |
|---|---|
| **Self-serve wizard** | You have an admin account and can enter school structure yourself |
| **Toshi-assisted** | Same wizard goals, conversational help in the dashboard panel |
| **Booked session** | You want KlassApp staff to walk through with you |

Guided Toshi / wizard flows work **without** enabling free-form LLM chat. Free-form chat is a separate, gated capability — see [`docs/roadmap.md`](../roadmap.md).

---

## Typical setup order

1. **School + academic year** — name, logo optional, current year  
2. **Terms** — any number/names; the product model is configurable (sensible defaults are the intended UX direction; schools can edit)  
3. **Classes / streams** — sections are the real class identity (`section_id`), not grading bands alone  
4. **Teachers** — create accounts; optional **class-teacher invite by email** (Phase 1 shipped)  
5. **Students + parent links** — roster import or manual; parent WhatsApp linking via requests/approvals  
6. **Fees** — structures and amounts for the year/term  
7. **Plan** — Freemium, Growth (**$35** / cycle), or Premium (custom)

Exact wizard step names can evolve; this is the operational order that matches how schools go live today.

---

## After go-live

- Mark attendance and publish results as usual  
- Parents message WhatsApp once linked  
- Use Toshi for known flows; keep humans in the loop on sensitive actions  

Ongoing product direction (invites Phase 2, WhatsApp OAuth linking, etc.): [`docs/roadmap.md`](../roadmap.md).

---

## Plans (reminder)

| Plan | Price |
|---|---|
| Freemium | Free |
| Growth | **$35** / cycle |
| Premium | Custom |

---

## Need a walkthrough?

[Book onboarding](book-onboarding.md) or email [community@klassapp.xyz](mailto:community@klassapp.xyz).
