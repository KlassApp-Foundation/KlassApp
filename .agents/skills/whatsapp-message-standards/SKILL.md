---
name: whatsapp-message-standards
user-invocable: false
description: |
  WhatsApp message formatting standards for KlassApp. Living document — not yet finalized.
  Covers bold/italic conventions, footer/signature, chunking rules, tone by message type.
  Based on audit findings until WhatsApp rework is formally scoped.
  Triggers on: 'WhatsApp message', 'message format', 'WhatsApp tone', 'WhatsApp standards',
  'WhatsApp audit', 'OutboundWhatsAppService', 'WhatsApp compose'.
---

# WhatsApp Message Standards — KlassApp

> **LIVING DOCUMENT — Not yet finalized.**
> This skill is based on audit findings from the current codebase.
> Standards become official only when the WhatsApp rework is formally scoped.
> Until then, treat this as a proposal, not a mandate.

---

## 1. Current Architecture

### Transport Layer

```
User ↔ Meta Cloud API (Business API) ↔ Laravel
                              ↕
              WhatsAppBusinessService (HTTP client)
                              ↕
              OutboundWhatsAppService (message composition)
                              ↕
              MessageDeliveryLog (DB tracking)
```

### Key Components

| Component | Location | Role |
|---|---|---|
| `OutboundWhatsAppService` | `app/Services/OutboundWhatsAppService.php` | Message composition + sending |
| `WhatsAppBusinessService` | `app/Services/WhatsAppBusinessService.php` | Meta Cloud API HTTP client |
| `WhatsAppController` | `app/Http/Controllers/Api/WhatsAppController.php` | Inbound webhook handler |
| `SchoolPayWebhookController` | `app/Http/Controllers/Api/SchoolPayWebhookController.php` | Payment webhook |

### Message Types Currently Implemented

| Method | Purpose | Format |
|---|---|---|
| `composeFeeBalance()` | Fee balance inquiry | Text + summary |
| `composeAttendance()` | Attendance report | Text + stats |
| `composeGradesOverview()` | Exam results | Text + grades |
| `composeHealthRecord()` | Health notification | Text + details |
| `composeStudentWithdrawn()` | Withdrawal notice | Text + formal |
| `composeTermOpens()` | Term opening | Text + dates |
| `composeTermCloses()` | Term closing | Text + dates |
| `notifyFeeBalance()` | Proactive fee reminder | Buttons |
| `notifyAttendance()` | Attendance alert | Buttons |

### Interactive Message Types

| Method | API Type | Max Items |
|---|---|---|
| `sendButtons()` | Interactive buttons | 3 buttons |
| `sendList()` | Interactive list | 10 rows total (Meta limit) |
| `sendTemplate()` | Template message | Per template |

---

## 2. Formatting Conventions (Proposed)

### Bold / Italic (WhatsApp Markdown)

WhatsApp supports limited markdown:

| Syntax | Result | Use For |
|---|---|---|
| `*text*` | **bold** | Headers, key values, student names |
| `_text_` | _italic_ | Secondary info, disclaimers |
| `~text~` | ~~strikethrough~~ | Corrections, cancelled items |
| ```text``` | `monospace` | Codes, IDs, reference numbers |

### Proposed Convention

```
*Header / Section Title*

Key information with *important values* emphasized.

_Details:_
• Item one: value
• Item two: value

_Footer / signature_
```

### Examples (Based on Current Implementation)

**Fee Balance:**
```
*Fee Balance — Kabale Junior School*

Student: _John Mukasa_
Class: P4 — Blue Stream

*Total Fees:* UGX 850,000
*Paid:* UGX 500,000
*Balance:* UGX 350,000

_Please ensure fees are paid before the term ends._
— KlassApp
```

**Attendance Report:**
```
*Attendance Report — John Mukasa*

This term: _45/50 days_ (90%)

Recent:
• Mon: ✅ Present
• Tue: ✅ Present
• Wed: ❌ Absent
• Thu: ✅ Present
• Fri: ✅ Present

— KlassApp
```

**Exam Results:**
```
*Term 1 Results — John Mukala*

Class: P4 — Blue Stream

*Mathematics:* 85% — Distinction
*English:* 72% — Credit
*Science:* 68% — Credit
*Social Studies:* 90% — Distinction

*Overall:* 79% — Credit

— KlassApp
```

---

## 3. Footer / Signature Requirement

**Proposed rule:** Every outbound WhatsApp message ends with:

```
— KlassApp
```

This is currently inconsistently applied. Some compose methods include it, others don't.

### Rationale
- Brand consistency across all messages
- Parents know the message is from KlassApp, not the school directly
- Professional appearance

---

## 4. Chunking Rules

### Meta Cloud API Limits

| Constraint | Limit |
|---|---|
| Interactive list rows | **10 max** (across all sections) |
| Interactive buttons | **3 max** |
| Message body text | ~4096 characters |
| Template variables | 10 max |

### Proposed Chunking Strategy

- **Lists > 10 items:** Split into multiple messages or use summary + "view more" link
- **Fee items > 10:** Group by category, send summary first
- **Student lists:** Send count + top 5, offer "view all" option
- **Long text:** Break into logical sections with headers

---

## 5. Tone Guidance by Message Type

| Message Type | Tone | Example |
|---|---|---|
| **Fee receipt** | Confirming, warm | "Payment received! Thank you." |
| **Fee reminder** | Professional, gentle | "A friendly reminder that..." |
| **Attendance alert** | Informative, neutral | "John was marked absent today." |
| **Exam results** | Celebratory for good, neutral for poor | "Great results this term!" / "Results are in." |
| **Health notification** | Urgent, caring | "Important health notice for John." |
| **Student withdrawn** | Formal, sensitive | "We confirm John's withdrawal." |
| **Term opens/closes** | Informative, clear | "Term 2 begins on Monday." |
| **Welcome / onboarding** | Warm, helpful | "Welcome to KlassApp!" |

---

## 6. Current Inconsistencies (Audit Findings)

| Issue | Location | Impact |
|---|---|---|
| No consistent footer | `OutboundWhatsAppService` compose methods | Brand inconsistency |
| Mixed emoji usage | Some messages use emojis, others don't | Tone inconsistency |
| No character count enforcement | Long messages may be truncated | Delivery failures |
| Inconsistent bold usage | Some headers bold, others not | Visual inconsistency |
| List button titles vs. keyword matching | `routeInbound()` strips emojis to match | Fragile routing |
| No template standardization | Each compose method has unique format | Maintenance burden |

---

## 7. Phone Number Format

**Uganda format:** `+256 7[0578] XXX XXX`

**wa.me links:** Strip the `+` → `2567XXXXXXXX`

**Helper:** `WhatsAppPhoneHelper` handles normalization.

---

## 8. Status: Living Document

This skill will be updated when:
- [ ] WhatsApp rework is formally scoped
- [ ] Message templates are standardized
- [ ] Footer/signature policy is decided
- [ ] Tone guidelines are reviewed and approved
- [ ] Chunking rules are tested with real messages

**Until then:** Use current `OutboundWhatsAppService` compose methods as the reference implementation, and treat the conventions above as proposals.
