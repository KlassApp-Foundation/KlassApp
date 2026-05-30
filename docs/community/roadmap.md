# Roadmap

## Student Transfers Between Schools

A parent should be able to transfer their child from one school to another — at their discretion — within the KlassApp ecosystem. When a child moves:

- The parent initiates the transfer from the WhatsApp menu
- The new school receives a verification request
- Upon approval, the child's parent link, grade history, and attendance records are shared with the new school
- The former school retains a copy — their data independence is preserved

This makes KlassApp sticky across a student's entire academic journey — not just per-school.

**Future vision**: Student academic data lives on blockchain, owned by the parent and student — not by any single school. A transfer becomes a permission grant: the parent authorizes the new school to view the student's on-chain record. No school "owns" the data; the student carries it.

---

## More Roles

The current role set (admin, teacher, bursar, librarian, nurse, secretary) will expand to cover the full school ecosystem:

| Role | Planned Features |
|---|---|
| **Guidance & Counsellor** | Referral notices, session reminders, wellness check-ins |
| **Sports Master** | Fixture broadcasts, trial invitations, match results |
| **Transport Manager** | Route updates, ETA notifications, delay alerts |
| **Boarding Master** | Weekend sign-out/sign-in, dormitory notices, visiting day info |
| **PTA Representative** | Meeting broadcasts, parent feedback collection |
| **Class Prefect / Head Boy/Girl** | Announcement relays, event coordination (student-facing) |

---

## Fix Pricing Model

The pricing structure needs refinement. Key improvements:

- **Free tier** for smaller schools (up to a threshold)
- **Fair usage caps** that scale with school size
- **Scalable pricing tiers** that match school size
- **Bundle pricing** for school groups / multi-campus institutions
- **No hidden fees** — schools know exactly what they'll pay monthly

---

## Feature Modules

### Blockchain-Based Voting

Secure, transparent voting for:
- Parent-Teacher Association (PTA) elections
- School board decisions
- Student council elections
- Policy ratification (uniform changes, fee adjustments)

The blockchain provides an immutable audit trail — any stakeholder can verify the tally without trusting a central authority.

### File Management (IPFS)

Decentralized file storage for:
- Report cards and transcripts (parent can request a verifiable copy)
- Circulars and policy documents
- Student portfolio artifacts
- Sports and event photos (with parent consent)
- Medical records and immunization certificates

Files are content-addressed on IPFS — permanent, tamper-proof, and accessible via a link from WhatsApp.

### Canteen Module

- Daily menu broadcast to parents
- Pre-order and prepayment for meals
- Dietary restriction alerts
- Balance top-up reminders
- Meal history per student

### Alumni Module

- Graduating student auto-enrollment into alumni network
- Alumni event invitations via WhatsApp
- Fundraising and donation drives
- Mentorship matching (alumni → current students)
- Career opportunity broadcasts
- Reunion coordination

---

## Integrations

| Integration | Purpose |
|---|---|
| **Google Workspace** | Sync classroom calendars, Drive documents for report attachments |
| **Notion** | School wiki, policy docs, staff knowledge base sync |
| **School Pay** | In-chat fee payment processing via mobile money |
| **Custom WhatsApp Extensions** | KlassApp's core differentiator: sits on top of any school ERP and surfaces relevant data to parents — without replacing the school's existing system |
| **Calendar (Google / Outlook)** | Two-way sync of school events and parent calendar |

This is KlassApp's core differentiator. Most Ugandan schools already run some form of digital record system, but these are built for the admin office — the parent is left out entirely. KlassApp's custom WhatsApp extension layer sits on top of any ERP (from a $50 spreadsheet setup to a $10,000 integrated platform) and surfaces grades, fees, attendance, and events directly to parents via WhatsApp. The school keeps what works. KlassApp adds the parent connection.

---

## Phases

| Phase | Status |
|---|---|
| Phase 1 — Connect (grades, fees, attendance, menu) | ✅ Complete |
| Phase 2 — Engage (queue, dashboard, multi-child, delivery) | ✅ Complete |
| Phase 3 — Scale (LIN integration, self-registration, parent network) | 🚧 In progress |
| Phase 4 — Ecosystem (transfers, roles, voting, IPFS, canteen, alumni) | 📋 Planned |
| Phase 5 — Integrations (School Pay, ERPs, Google Workspace, Notion) | 📋 Planned |
