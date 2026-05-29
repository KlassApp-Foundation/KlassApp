---
name: klassapp-knowledge
user-invocable: true
description: |
  MANDATORY: Load this skill at the START of EVERY session when working on the KlassApp project.
  This skill injects the project knowledge base and session logging protocol.
  Triggers on: 'KlassApp', 'klassapp', 'Gegok12', 'gegok12', 'ugasch.com', 'school management',
  any session start in this project, or whenever knowledge.md is mentioned.
  Also triggers on: 'update knowledge', 'session summary', 'project context', 'append to knowledge'.
---

# KlassApp Knowledge Base Skill

> **This skill MUST be loaded at the start of every session on this project.**
> It reads the canonical `knowledge.md` and ensures session summaries are logged.

---

## 1. Session Start Protocol

1. **Read `knowledge.md`** from the project root (`/Users/mac/projects/KlassApp/knowledge.md`).
   - This is the canonical project knowledge base.
   - Contains: architecture, routes, services, database, WhatsApp stack, CI/CD, known issues, file index.
2. **If `knowledge.md` does not exist**, create it using the template in section 5.
3. **Do NOT skip reading `knowledge.md`** — it contains critical context from prior sessions that prevents regressions.

## 2. Session End Protocol

After completing work each session:

1. **Append a summary** to `knowledge.md` under `## Session Log` using this exact format:

```markdown
### YYYY-MM-DD: Brief title
- **Work done**: Summary of changes
- **Files modified**: List
- **Key decisions**: List with reasoning
- **Status**: ✅ Done / 🚧 In progress / ⏸️ Blocked
- **Edge cases flagged**: Any discovered during work
```

2. **If the project structure changed** (new routes, services, models, configs), update the relevant section in `knowledge.md` — not just the log. Keep the knowledge base accurate.

## 3. Quick Reference

### Project Identity

| Field | Value |
|---|---|
| **Name** | KlassApp (formerly Gegok12) |
| **Stack** | Laravel 10 + Blade + Tailwind 1.4 + Vue 2 + MySQL |
| **PHP** | 8.1+ |
| **Hosting** | VPS (Hetzner) — domain: ugasch.com via Cloudflare |
| **Vapor** | Configured (gegok12, id: 10390) — production + staging on AWS Lambda |
| **Roles** | Laratrust: superadmin, schooladmin, teacher, parent, student, librarian, accountant, etc. |
| **Auth** | Laravel Sanctum (API) + session (web) |
| **Timezone** | `Asia/Kolkata` (legacy — not Uganda `Africa/Kampala`) |
| **Logos** | SVG in `public/images/` |

### WhatsApp Architecture

```
User ↔ Evolution API ↔ Laravel Webhook / Outbound Service
                        ↕
               WhatsAppService (HTTP transport)
                        ↕
            OutboundWhatsAppService (business logic)
                        ↕
              MessageDeliveryLog (DB tracking)
```

### Key Environment Variables

```
EVOLUTION_API_URL=http://localhost:8081
EVOLUTION_INSTANCE_NAME=klassapp
WHATSAPP_BUSINESS_NUMBER=+256767538805
WHATSAPP_BUSINESS_NAME=KlassApp
```

### Uganda Phone Format

- Format: `+256 7[0578] XXX XXX` (12 chars with `+`, 9 digits after 256)
- Validation: `/^\+256(7[0578]\d{7})$/`
- wa.me links: strip the `+` prefix

## 4. knowledge.md Template

If `knowledge.md` needs to be created or rebuilt, include these sections:

```
# KlassApp Knowledge Base

## 1. Project Overview
## 2. Infrastructure (Docker, WhatsApp Stack, Nginx, CI/CD)
## 3. Key Dependencies (Composer, NPM)
## 4. Routing Structure (19 route files)
## 5. WhatsApp Integration
## 6. Premium School Pages
## 7. Database
## 8. Testing
## 9. Known Issues & Edge Cases
## 10. Key Files & Locations
## 11. Session Log
```
