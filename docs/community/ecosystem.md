# Ecosystem

How KlassApp’s **surfaces and channels** fit together today — not a regional market thesis.

---

## Product model

```text
Parents  ──WhatsApp (Meta Cloud API)──►  KlassApp (Laravel)
Teachers / admins ──Web dashboard + Toshi──►  same tenant data
UI also shows Drive / Slack as channel concepts ──► not separate live API clients yet
```

| Channel | Status |
|---|---|
| **WhatsApp** | Live in production (Meta Cloud API) |
| **Web (admin / teacher / portals)** | Live |
| **Toshi (dashboard)** | Guided / known methods live; free-form chat gated |
| **Google Drive / Slack** | Present in the product UI / connector story; not separate shipping API clients in `app/` yet |

That matches the README architecture diagram and [`docs/architecture.md`](../architecture.md).

---

## Four surfaces (design)

Shipped as one coordinated cutover:

1. Landing / auth / errors  
2. Admin dashboards  
3. Onboarding wizard  
4. Toshi panel  

Details and backlog: [`docs/roadmap.md`](../roadmap.md).

---

## Who it’s for

Built first under hard constraints (bandwidth, phones, multi-tenant school ops). That design is why it travels — not because the product is locked to one country.

Schools that need day-to-day SIS work **and** parent answers on WhatsApp are the primary fit.

---

## Open source + SaaS

- **SaaS:** [klassapp.xyz](https://klassapp.xyz) on Laravel Cloud  
- **Code:** MIT on GitHub — run your own copy if you prefer  

Community contact: [community@klassapp.xyz](mailto:community@klassapp.xyz)
