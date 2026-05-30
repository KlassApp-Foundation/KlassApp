# API Reference

Endpoints exposed by the WhatsApp integration layer. These are consumed by Evolution API (inbound/delivery webhooks) and n8n/Typebot (data queries).

---

## Authentication

### HMAC Middleware (Internal Endpoints)

All endpoints under `/api/whatsapp/` **except** `/inbound` are protected by the `WhatsAppHmac` middleware.

**Header**: `X-Hub-Signature-256`

**Value**: HMAC-SHA256 of the raw JSON request body, using `WHATSAPP_HMAC_SECRET` as the key.

```bash
# Generate signature
echo -n '{"phone":"+256701234567"}' | openssl dgst -sha256 -hmac "your-secret"
```

### Inbound Webhook

`POST /api/whatsapp/inbound` uses **Evolution API key** authentication (the `apikey` header sent by Evolution API) instead of HMAC. The request is also validated by `StoreWhatsAppWebhookRequest` FormRequest.

---

## Endpoints

### Identify User

Resolves a phone number to a KlassApp user, including their role, school, and linked children/classes.

**POST** `/api/whatsapp/identify-user`

**Auth**: HMAC

**Request Body**:

```json
{
  "phone": "+256701234567"
}
```

**Response (200) — Identified**:

```json
{
  "identified": true,
  "user_id": 42,
  "name": "Joseph Mukasa",
  "user_type": "parent",
  "school_id": 1,
  "school_name": "St. Mary's School",
  "children": [
    {
      "student_id": 101,
      "maaif_id": 101,
      "name": "Amope Nandawula",
      "class": "Primary 5",
      "section": "A"
    }
  ],
  "children_count": 1
}
```

**Response (200) — Not Identified**:

```json
{
  "identified": false,
  "message": "Phone number not linked to any account. Please contact your school to link your WhatsApp number."
}
```

**Response (200) — Opted Out**:

```json
{
  "identified": false,
  "message": "You have opted out of WhatsApp notifications. Reply OPTIN to re-enable."
}
```

**user_type values**: `parent`, `teacher`, `student`, `admin`, `receptionist`, `accountant`

**Teacher response** additionally includes `linked_classes`:

```json
{
  "linked_classes": [
    {
      "standard_id": 5,
      "name": "Primary 5",
      "sections": ["A", "B"]
    }
  ]
}
```

---

### Get Student Grades

**GET** `/api/whatsapp/student/{studentId}/grades?term=current`

**Auth**: HMAC

**Parameters**:

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `studentId` | integer | Yes (path) | — | KlassApp student user ID |
| `term` | string | No | `current` | Academic term filter |

**Response (200)**:

```json
{
  "student_id": 101,
  "student_name": "Amope Nandawula",
  "class": "Primary 5",
  "section": "A",
  "term": "current",
  "subjects": [
    { "name": "Mathematics", "score": 85, "grade": "A" },
    { "name": "English", "score": 72, "grade": "B+" },
    { "name": "Science", "score": 80, "grade": "A-" },
    { "name": "Social Studies", "score": 68, "grade": "B" }
  ],
  "total_marks": 305,
  "percentage": 76.25,
  "grade": "B+"
}
```

**Response (404)**:

```json
{
  "error": "Student not found",
  "message": "No student found with ID: 999"
}
```

---

### Get Student Attendance

**GET** `/api/whatsapp/student/{studentId}/attendance`

**Auth**: HMAC

**Parameters**:

| Parameter | Type | Required | Description |
|---|---|---|---|
| `studentId` | integer | Yes (path) | KlassApp student user ID |

**Response (200)**:

```json
{
  "student_id": 101,
  "student_name": "Amope Nandawula",
  "total_days": 42,
  "present": 38,
  "absent": 3,
  "late": 1,
  "attendance_percentage": 90.5
}
```

---

### Get Fee Balance

**GET** `/api/whatsapp/fees/{studentId}/balance`

**Auth**: HMAC

**Parameters**:

| Parameter | Type | Required | Description |
|---|---|---|---|
| `studentId` | integer | Yes (path) | KlassApp student user ID |

**Response (200)**:

```json
{
  "student_id": 101,
  "student_name": "Amope Nandawula",
  "total_fees": 500000,
  "paid": 350000,
  "balance": 150000,
  "status": "partial"
}
```

**Status values**: `paid`, `partial`, `unpaid`

---

### Get School Events

**GET** `/api/whatsapp/school/{schoolId}/events`

**Auth**: HMAC

**Parameters**:

| Parameter | Type | Required | Description |
|---|---|---|---|
| `schoolId` | integer | Yes (path) | KlassApp school ID |

**Response (200)**:

```json
{
  "school_id": 1,
  "school_name": "St. Mary's School",
  "events": [
    {
      "id": 10,
      "title": "Parents Meeting",
      "date": "2026-06-15",
      "description": "End of term parents-teacher meeting",
      "type": "meeting"
    }
  ],
  "event_count": 1
}
```

---

### Inbound Webhook

Receives messages from Evolution API when a user sends a WhatsApp message.

**POST** `/api/whatsapp/inbound`

**Auth**: Evolution API key (`apikey` header)

**Validation**: `StoreWhatsAppWebhookRequest` FormRequest enforces:

| Rule | Constraint |
|---|---|
| `event` | Must be `messages.upsert` |
| `remoteJid` | Must not contain `@g.us` (group messages ignored) |
| `fromMe` | Must be `false` (ignore own messages) |
| `key.id` | Required |
| `message` | Required, max 1MB payload |
| Phone format | Must match `+2567[0578]...` or be ignored |

**Guards**:

- Group messages (`@g.us`) → silently ignored
- Own messages (`fromMe=true`) → silently ignored
- Non-message events → silently ignored

**Routing**: The controller dispatches based on keyword:

| Keyword | Action |
|---|---|
| `menu` | `sendMenu()` |
| `grades` | `sendGrades()` |
| `fees` | `sendFees()` |
| `attendance` | `sendAttendance()` |
| `events` | `sendEvents()` |
| `timetable` | `sendTimetable()` |
| `lin` | Start Path 3 registration flow |
| `register` | Start Path 3 registration flow |
| `optin` | Re-enable notifications |
| `optout` | Disable notifications |

**Response**: The controller returns `200 OK` to Evolution API (acknowledging receipt). The actual reply message is sent out-of-band via `WhatsAppService::sendText()` or `sendList()`.

**Window update**: On every valid inbound, `whatsapp_users.last_inbound_at` is updated (24-hour service window tracking).

**Queue flush**: After keyword routing, `flushPending()` is called — all queued notifications for that parent are delivered free within the open window.

---

### Delivery Webhook

Receives delivery status updates from Evolution API.

**POST** `/api/whatsapp/delivery`

**Auth**: Evolution API key (`apikey` header)

**Events handled**:

| Event | Action |
|---|---|
| `messages.ack` (delivered) | `MessageDeliveryLog::markDelivered()` |
| `messages.ack` (read) | `MessageDeliveryLog::markRead()` |
| `messages.error` | `handleDeliveryFailure()` |

**Failure Escalation** (`handleDeliveryFailure()`):

```php
// Tracks consecutive failures per phone in the last hour
// Triggers high-severity alert at 3+ failures:
"⚠️ WhatsApp delivery failure: {phone} ({count} failures in 1hr)"
```

Alerts are logged via `Log::alert()` and should be picked up by your monitoring system (Sentry, Slack, etc.).

**Delivery Log Status Values**:

| Status | Meaning |
|---|---|
| `sent` | Sent to Evolution API, awaiting delivery receipt |
| `delivered` | Delivered to recipient's phone |
| `read` | Read by recipient |
| `failed` | Failed (see `error_message` for reason) |

---

## Response Codes

| Code | Meaning | Typically From |
|---|---|---|
| 200 | Success / Ack | All endpoints |
| 401 | HMAC signature mismatch / Invalid API key | `WhatsAppHmac` middleware, Evolution API auth |
| 404 | Student not found | `/student/{id}/grades`, `/fees/{id}/balance` |
| 422 | Validation failure | `StoreWhatsAppWebhookRequest` |
| 429 | Rate limited | WhatsApp rate limiting |

---

## Evolution API Endpoints (Called by Laravel)

These are Evolution API endpoints that KlassApp's `WhatsAppService` calls outbound.

| Laravel Method | Evolution API Endpoint | Purpose |
|---|---|---|
| `sendText()` | `POST /message/sendText/{instance}` | Send free-form text |
| `sendList()` | `POST /message/sendList/{instance}` | Send interactive list message |
| `sendTemplate()` | `POST /template/send/{instance}` | Send Meta-approved template |
| `sendMedia()` | `POST /message/sendMedia/{instance}` | Send image/document/audio |

All requests require the `apikey` header matching `EVOLUTION_API_KEY`. The phone number is sent in clean format (no `+` prefix, digits only).
