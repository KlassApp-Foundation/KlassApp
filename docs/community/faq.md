# FAQ

Answers matched to what is **shipped today**. Product direction: [`docs/roadmap.md`](../roadmap.md).

---

## Parents

### Do I need a new app?

No. Everyday parent asks go through **WhatsApp**. Your school links your number to your child.

### Is WhatsApp free for me?

Receiving school messages uses normal WhatsApp. Data costs depend on your mobile plan and country — KlassApp does not bill parents for WhatsApp messages.

### How do I check fees or grades?

Message the school’s KlassApp WhatsApp number and use the interactive menu or short commands (for example fees / grades / attendance). Your school can tell you the exact number and options they enabled.

### Can both parents get messages?

Yes, if the school links more than one parent to the student.

### I changed my phone number

Ask the school to update the linked number.

---

## Schools

### Is KlassApp only a WhatsApp layer on our old system?

No. KlassApp is a full multi-tenant school platform (academics, fees, attendance, exams, staff roles) with WhatsApp as the live parent channel. See [For schools](for-schools.md).

### What’s live for parents on WhatsApp?

Meta **WhatsApp Cloud API** messaging: menus, fees/grades/attendance-style asks, delivery logging, and report PDFs where enabled. Google Drive and Slack show in the product model UI; they are not separate live API connectors yet.

### What is Toshi?

Toshi is the in-dashboard AI agent. **Guided / known-method flows** (including onboarding help) are real today. **Open-ended free-form chat** stays gated until the school/platform enables the funded LLM path.

### How long does setup take?

Most schools finish core setup via the **onboarding wizard** and/or **Toshi** in a single sitting once they have school name, classes, and a first admin. See [School onboarding](school-onboarding.md).

### What are the plans?

| Plan | Price |
|---|---|
| Freemium | Free |
| Growth | **$35** / cycle |
| Premium | Custom |

### Where is data hosted?

Production SaaS runs on **Laravel Cloud** (`klassapp.xyz`, EU-West-1). The codebase is MIT — you can also self-host.

### How do we get help?

[community@klassapp.xyz](mailto:community@klassapp.xyz) · [Book onboarding](book-onboarding.md)

---

## Product & docs

### Where is the roadmap?

Canonical public roadmap: [`docs/roadmap.md`](../roadmap.md). The old community Docsify roadmap page is deprecated.

### Where do developers start?

[`docs/architecture.md`](../architecture.md) → [DeepWiki](https://deepwiki.com/KlassApp-Foundation/KlassApp) → [`CONTRIBUTING.md`](../../CONTRIBUTING.md).
