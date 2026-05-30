# EMIS / LIN Integration Strategy

The Education Management Information System (EMIS) is Uganda's national database for schools, students, and staff. The **Learner Identification Number (LIN)** is a unique 12-digit identifier assigned to every student. The **National Identification Number (NIN)** links parents/guardians to their children.

This document describes the strategy for integrating KlassApp with EMIS/LIN to onboard parents and verify identities.

---

## Overview

### What is EMIS?

EMIS (Education Management Information System) is managed by Uganda's Ministry of Education and Sports. It contains:
- All registered schools in Uganda
- All enrolled students with their unique LIN
- Teacher and staff records
- Examination and assessment data

### What are LIN and NIN?

| Identifier | Length | Who It Identifies | Format |
|---|---|---|---|
| **LIN** | 12 digits | Student | `123456789012` (numeric) |
| **NIN** | 14 characters | Parent / Guardian | `CF1234567890AB` (alphanumeric) |

---

## Onboarding Paths

Three parallel paths are planned, ranked by speed to value:

| Path | Approach | Timeline | Effort | Status |
|---|---|---|---|---|
| **Path 2** | CSV bulk import via admin panel | Days | Low | Schema ready |
| **Path 3** | Parent self-registration via LIN + NIN | Weeks | Medium | Decided / To implement |
| **Path 1** | Ministry API partnership | Months | High | Not started |

---

## Path 2: CSV Bulk Import (Ready to Implement)

### Purpose

Allow school admins to mass-onboard parents by uploading a CSV file exported from the school's EMIS records.

### Trigger

Admin panel → Students → Import from EMIS

### CSV Schema

```csv
student_lin,parent_phone,parent_nin_hash
123456789012,+256701234567,a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1
123456789013,+256712345678,b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2
```

| Column | Format | Required | Description |
|---|---|---|---|
| `student_lin` | 12 numeric digits | Yes | The student's unique Learner Identification Number |
| `parent_phone` | E.164 (+256...) | Yes | Parent's WhatsApp phone number |
| `parent_nin_hash` | SHA-256 hex (64 chars) | Yes | SHA-256 hash of the parent's NIN |

### Import Flow

```
Admin uploads CSV
  ↓
Validate:
  - LIN: 12 digits, must exist in student_records.lin (or flag as external)
  - Phone: +2567[0578]XXX XXX format
  - NIN hash: exactly 64 hex characters
  ↓
Batch insert into staging/import table
  ↓
Match students by LIN:
  - If student found → link parent WhatsApp number
  - If student not found → flag as "external student" for manual mapping
  ↓
For each matched student:
  - Create or update WhatsAppUser record
  - Send welcome message via WhatsApp (if opted in)
  ↓
Show import summary:
  - Total rows
  - Successfully linked
  - Failed (with error reasons)
```

### Validation Rules

| Field | Rule |
|---|---|
| `student_lin` | Must be exactly 12 numeric characters |
| `parent_phone` | Must pass `WhatsAppPhoneHelper::validate()` |
| `parent_nin_hash` | Must match `/^[a-f0-9]{64}$/i` |

### NIN Privacy

- NIN is **never** stored in plaintext — only SHA-256 hash
- Hash should be generated client-side before upload when possible
- If hashed server-side, plaintext NIN is discarded immediately after hashing
- Future enhancement: salted hashes or zero-knowledge proofs

---

## Path 3: Self-Registration via LIN + NIN (Decided)

### Purpose

Allow parents to register themselves by sending their child's LIN to the WhatsApp bot. The bot verifies the LIN and NIN against school records, then links the parent's WhatsApp number to their children.

### Registration Flow

```
Parent sends "lin" or "register" keyword
  ↓
Bot asks: "Please send your child's 12-digit LIN (Learner Identification Number)"
  ↓
Parent sends 12-digit LIN
  ↓
Bot validates format and looks up in student_records.lin
  ↓
LIN found? → Bot asks for parent's NIN
  │             ↓
  │        Parent sends NIN
  │             ↓
  │        Bot hashes NIN → SHA-256
  │             ↓
  │     Hash matches student_records.parent_nin_hash?
  │       ├── YES → Link parent's WhatsApp to ALL children with matching NIN hash
  │       │         → Send confirmation + main menu
  │       └── NO  → "NIN does not match our records. Contact your school."
  │
  └── Not found? → "LIN not found. Contact your school or try again."
```

### Keyword Handling

The following new keywords are reserved for Path 3:

| Keyword | Purpose |
|---|---|
| `lin` | Start the LIN registration flow |
| `register` | Alternative trigger for registration |
| `cancel` | Cancel an in-progress registration |

A conversation state (`awaiting_lin`, `awaiting_nin`, `confirmed`) would be tracked — either in Redis (via Typebot/n8n) or in a `registration_state` column on `whatsapp_users`.

### Database Requirements

The following columns are needed on `student_records` (or equivalent):

```php
Schema::table('student_records', function (Blueprint $table) {
    $table->string('lin', 12)->nullable()->unique()->after('student_id');
    $table->string('parent_nin_hash', 64)->nullable()->after('lin');
});
```

Both columns are nullable initially — existing students won't have them. A migration should populate them from EMIS import data.

### Multi-Child Scenario

When a parent's NIN hash matches multiple students (e.g., they have 3 children in the school), all matching students are linked to the same WhatsApp number:

```php
$linkedStudents = StudentRecord::where('parent_nin_hash', $hashedNIN)->get();
foreach ($linkedStudents as $student) {
    WhatsAppUser::firstOrCreate([
        'phone'   => $phone,
        'user_id' => $student->parent_user_id ?? createParentUser($student),
    ]);
}
```

### Edge Cases

| Scenario | Handling |
|---|---|
| LIN not found in system | Bot responds: "LIN not recognized. Contact your school administrator or try again." |
| NIN hash mismatch | Bot responds: "The NIN does not match our records. Please check and try again, or contact your school." |
| Phone already linked | Bot responds: "This phone is already linked to an account. Reply MENU for options." |
| Multiple children with same NIN hash | All children are linked. Bot confirms: "Linked to 3 students." |
| Registration abandoned mid-flow | Conversation times out after 5 minutes (handled by n8n/Typebot conversation state) |
| Parent tries to register wrong child's LIN | LIN not linked to their NIN → hash mismatch → "Contact your school" |

### Cost Optimization

The entire Path 3 flow is **free** because every interaction is user-initiated (staying within the 24-hour service window):

1. Parent sends "lin" → user-initiated (opens window)
2. Bot asks for LIN → service reply (free)
3. Parent sends LIN → user-initiated (extends window)
4. Bot asks for NIN → service reply (free)
5. Parent sends NIN → user-initiated (extends window)
6. Bot confirms → service reply (free)

After registration, the school has 24 hours to send proactive notifications at **zero cost**.

---

## Path 1: Ministry API Partnership (Long-Term)

### Vision

A direct API integration with the Ministry of Education's EMIS database for real-time verification.

### Requirements

- Memorandum of Understanding (MoU) with Ministry of Education
- Data protection and privacy compliance (Uganda Data Protection and Privacy Act)
- API credentials and service-level agreement
- Regular data synchronization

### What It Unlocks

| Capability | Benefit |
|---|---|
| Real-time LIN/NIN verification | Eliminates hash comparison against local data |
| Automatic student roster sync | No more CSV uploads |
| Official parent contact data | Higher data quality and coverage |
| Nationwide student searches | Support transfers between schools |

### Status

- Not started. Requires stakeholder engagement with the Ministry of Education.

---

## Implementation Priority

1. **Path 2 first** — CSV import gives admins immediate value with minimal development effort. The schema and validation rules are ready.
2. **Path 3 second** — Self-registration unlocks cost-free onboarding and expands the parent base without admin overhead. The WhatsApp flow design is complete; implementation requires:
   - `lin` and `parent_nin_hash` columns on `student_records`
   - `lin` and `register` keyword handlers in `WhatsAppController::handleInbound()`
   - Registration state management (Redis or DB)
   - `WhatsAppUser` creation or linking logic
3. **Path 1 third** — Long-term strategic goal. Begin stakeholder conversations early, but don't block on it.

---

## Database Changes Summary

| Change | Path | Migration Needed |
|---|---|---|
| Add `lin` (VARCHAR(12), unique, nullable) to students table | 2, 3 | Yes |
| Add `parent_nin_hash` (CHAR(64), nullable) to students table | 2, 3 | Yes |
| Create `lin_registrations` tracking table | 2, 3 | Yes |
| Add `registration_state` to `whatsapp_users` (for Path 3) | 3 | Optional |
| WhatsApp keyword handlers for `lin`, `register` | 3 | No (code change) |
