# SchoolPay Integration Spec

## Overview

SchoolPay (Fincom Technologies Ltd, licensed by Bank of Uganda) is a payment aggregator used by 20,000+ schools and 5M+ parents across Uganda, Kenya, and Zambia. It sits between schools and payment channels (MTN MoMo, Airtel Money, 10+ banks, payment gateways).

A parent pays fees via USSD or bank → SchoolPay records the transaction → **KlassApp detects the payment and sends a WhatsApp receipt to the parent**.

This spec covers the API integration to make that happen.

---

## SchoolPay API Surface

### 1. Transaction Sync (REST — Polling)

| Endpoint | Method | Purpose |
|---|---|---|
| `/AndroidRS/SyncSchoolTransactions/{schoolCode}/{date}/{hash}` | GET | Transactions for a single date |
| `/AndroidRS/SchoolRangeTransactions/{schoolCode}/{fromDate}/{toDate}/{hash}` | GET | Transactions within a range (max 31 days) |

**Auth**: MD5(schoolCode + date + password)

**Response**:
```json
{
  "returnCode": 0,
  "returnMessage": "3 transaction(s) found (1 regular, 2 supplementary fee)",
  "transactions": [
    {
      "amount": "500000.00",
      "paymentDateAndTime": "2026-01-29 20:01:52",
      "schoolpayReceiptNumber": "18847257",
      "settlementBankCode": "TROPICAL",
      "sourceChannelTransDetail": "John Doe",
      "sourceChannelTransactionId": "TXN_9876543220",
      "sourcePaymentChannel": "MTN MobileMoney",
      "studentClass": "S1",
      "studentName": "Jeremy Kyagulanyi",
      "studentPaymentCode": "1006480152",
      "studentRegistrationNumber": "HIS-252",
      "transactionCompletionDateAndTime": "2026-01-29 20:01:52",
      "transactionCompletionStatus": "Completed"
    }
  ],
  "supplementaryFeePayments": [ /* same shape */ ]
}
```

### 2. Webhook (Push — Real-time)

SchoolPay POSTs to a registered URL for every successful payment.

**Security**: SHA256 HMAC signature over `schoolpayReceiptNumber` using the school's API password as the key.

**Payload**:
```json
{
  "signature": "903203ed81d54a0916dd562886cf8e69a831aaf2f81da501408e93cf25d75b08",
  "payment": {
    "amount": "50000",
    "paymentDateAndTime": "2026-01-29 20:01:52",
    "schoolpayReceiptNumber": "18847257",
    "settlementBankCode": "TROPICAL",
    "sourceChannelTransDetail": "John Doe",
    "sourceChannelTransactionId": "TXN_9876543220",
    "sourcePaymentChannel": "MTN MobileMoney",
    "studentName": "Jeremy Kyagulanyi Soran",
    "studentPaymentCode": "1006480152",
    "studentRegistrationNumber": "HIS-252",
    "transactionCompletionStatus": "Completed"
  }
}
```

**Constraints**:
- Single attempt only — no retries if your endpoint is down
- Requires active SchoolPay subscription
- Must respond `200 OK` to acknowledge

### 3. Adhoc One-Time Payments

Endpoints exist but details are not publicly documented. Likely used for initiating payments programmatically. Out of scope for v1 — rely on SchoolPay's existing payment channels.

---

## Data Model

### SchoolPay Student Record (per school)

| Field | Source | Maps To (KlassApp) |
|---|---|---|
| `studentPaymentCode` (10 digits) | SchoolPay upload | `students.schoolpay_code` |
| `studentRegistrationNumber` | School's own ID | `students.registration_number` |
| `studentName` | SchoolPay upload | Alias lookup only |
| `studentClass` | SchoolPay upload | `students.class` cross-check |

### Transaction Record

| Field | Type | Notes |
|---|---|---|
| `schoolpay_receipt` | string, unique PK | Deduplication key |
| `school_id` | FK → schools | Which school |
| `student_id` | FK → students | Matched via payment code |
| `amount` | decimal | In UGX |
| `channel` | string | MTN MobileMoney, Airtel Money, etc. |
| `paid_at` | datetime | From SchoolPay |
| `synced_at` | datetime | When KlassApp recorded it |
| `source` | enum | webhook | sync_poll |

---

## Architecture

```
Parent pays via MTN USSD
        │
        ▼
   SchoolPay
        │
        ├── Webhook POST ──────► KlassApp Webhook Receiver
        │                           │
        │                           ├── Verify SHA256 signature
        │                           ├── Dedup by schoolpay_receipt
        │                           ├── Match studentPaymentCode → student
        │                           ├── Record transaction
        │                           └── Send WhatsApp receipt to parent
        │
        └── (fallback) Sync API (polling daemon)
                                    │
                                    ├── Every N minutes per school
                                    ├── GET /SchoolRangeTransactions/
                                    ├── Find new receipts not yet recorded
                                    └── Same flow as webhook
```

### Components

#### A. Webhook Receiver

**Endpoint**: `POST /api/schoolpay/webhook`

**School-level configuration** (stored per school in KlassApp):
- `schoolpay_school_code` — SchoolPay's school identifier
- `schoolpay_api_password` — API password (hashed at rest)
- `schoolpay_webhook_enabled` — boolean
- `schoolpay_last_receipt_id` — last processed receipt (dedup)

**Flow**:
1. Receive POST with JSON payload
2. Verify signature — SHA256(`schoolpayReceiptNumber` + school's API password) against the `signature` field
3. Reject if invalid (return 403)
4. Dedup: check if `schoolpayReceiptNumber` already exists in `schoolpay_transactions`
5. Match student: look up `studentPaymentCode` in `students.schoolpay_code` for this school
6. If no match → store in an `unmatched_transactions` queue (admin can manually link)
7. Record transaction in `schoolpay_transactions`
8. Send WhatsApp message to parent using the existing fee receipt template
9. Return 200

#### B. Sync Poller (Fallback)

A scheduled job that polls SchoolPay's Sync API for each configured school.

**Cron**: `*/15 * * * *` (every 15 minutes)

**Flow**:
1. For each school with `schoolpay_school_code` set:
2. Compute hash: MD5(schoolCode + fromDate + password)
3. GET `/AndroidRS/SchoolRangeTransactions/{schoolCode}/{fromDate}/{today}/{hash}`
4. Filter transactions where `schoolpayReceiptNumber` not already in `schoolpay_transactions`
5. Process each new transaction same as webhook flow

**Why polling?** SchoolPay webhooks are single-attempt with no retry. If KlassApp is briefly down, the webhook is lost forever. Polling catches these gaps.

#### C. Student Code Mapping

Schools need a way to associate SchoolPay payment codes with KlassApp student records.

**Option A — CSV import enhancement** (recommended):
- Extend the existing EMIS CSV import to include a `schoolpay_code` column
- School uploads: `student_lin, parent_phone, parent_nin, schoolpay_code`
- This ties the SchoolPay payment code to the same student record that LIN identifies

**Option B — Dashboard field**:
- Admin can edit a student's profile to add/update their SchoolPay payment code
- Bulk assignment via CSV upload

**Option C — Auto-match by registration number**:
- If SchoolPay `studentRegistrationNumber` matches KlassApp's `registration_number`, auto-link
- Flag for admin confirmation

---

## WhatsApp Notification Templates

### Payment Receipt (v1)

```
├── ✅ Payment Received
│
├── School: St. Mary's School
├── Student: Amope Nandawula (P.5A)
├── Amount: UGX 150,000
├── Paid via: MTN MobileMoney
├── Receipt: SP-18847257
├── Date: 29 Jan 2026, 8:01 PM
│
├── Fee Breakdown
│   ├── Tuition: UGX 100,000
│   └── Development Levy: UGX 50,000
│
├── Total Paid This Term: UGX 650,000
├── Balance: UGX 150,000
│
└── Reply FEES to check your balance
```

The breakdown requires SchoolPay to return fee categorization (regular transactions vs supplementary). If the SchoolPay response doesn't include category details, v1 can send a simpler receipt:

```
✅ Payment Received

St. Mary's School
Amope Nandawula — P.5A

UGX 150,000 received via MTN MobileMoney
Receipt: SP-18847257

Reply FEES for your full balance.
```

### Outstanding Balance

Powered by SchoolPay's sync data:
```
Fee Balance — Amope Nandawula (P.5A)

Tuition:         UGX 500,000  ✅ Paid
Development:     UGX 200,000  ❌ Outstanding
Transport:       UGX 100,000  ❌ Outstanding
Lunch:           UGX 50,000   ✅ Paid

Total Due:       UGX 300,000
Due Date:        15 Mar 2026

Reply PAY for payment options.
```

---

## Implementation Phases

### Phase 1 — Webhook Receiver + WhatsApp Receipt

**Effort**: 2-3 days
**What**:
- `POST /api/schoolpay/webhook` endpoint
- Signature verification
- Dedup by receipt number
- Student matching by `studentPaymentCode`
- Basic WhatsApp receipt template
- Migration for `schoolpay_transactions` table

### Phase 2 — Sync Poller

**Effort**: 1-2 days
**What**:
- Scheduled command for polling
- Date-range sync with dedup
- Same processing pipeline as webhook

### Phase 3 — Admin Dashboard + Student Mapping

**Effort**: 2-3 days
**What**:
- School settings page (SchoolPay school code, API password, enable/disable)
- Student profile field for SchoolPay payment code
- CSV import column for `schoolpay_code`
- Unmatched transactions queue with manual linking

### Phase 4 — Fee Balance Dashboard

**Effort**: 2-3 days
**What**:
- Track per-student fee balance using SchoolPay transaction history
- WhatsApp "FEES" command returns current balance
- Overdue escalation notifications using SchoolPay data

**Total estimated effort**: 7-11 days

---

## Database Migrations

### `schoolpay_transactions`

```php
Schema::create('schoolpay_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('school_id')->constrained();
    $table->foreignId('student_id')->nullable()->constrained();
    $table->string('schoolpay_receipt')->unique();
    $table->decimal('amount', 12, 2);
    $table->string('channel');               // MTN MobileMoney, Airtel Money, etc.
    $table->string('student_payment_code');
    $table->string('student_registration_number')->nullable();
    $table->string('student_name');
    $table->string('student_class')->nullable();
    $table->string('settlement_bank')->nullable();
    $table->string('source_channel_transaction_id')->nullable();
    $table->enum('source', ['webhook', 'sync_poll']);
    $table->timestamp('paid_at');
    $table->timestamps();
});
```

### `schools` table additions

```php
$table->string('schoolpay_school_code')->nullable();
$table->string('schoolpay_api_password_encrypted')->nullable();
$table->boolean('schoolpay_webhook_enabled')->default(false);
$table->timestamp('schoolpay_last_sync_at')->nullable();
$table->string('schoolpay_last_receipt_processed')->nullable();
```

### `students` table additions

```php
$table->string('schoolpay_code', 10)->nullable()->unique();
```

---

## Security Considerations

| Concern | Mitigation |
|---|---|
| **API password exposure** | Encrypt at rest using Laravel's `encrypt()` / app key. Never log the password. |
| **Webhook signature forgery** | Verify SHA256 HMAC on every request. Reject missing/invalid signatures with 403. |
| **Duplicate processing** | Unique constraint on `schoolpay_receipt`. Webhook + poller share the same dedup. |
| **Webhook replay** | Nonce not provided by SchoolPay. Dedup by receipt handles it. |
| **Down endpoint (no retry)** | Polling fallback catches missed webhooks. |
| **Student payment code collisions** | `schoolpay_code` is unique per student, but codes are per-school. Composite unique: `(school_id, schoolpay_code)`. |

---

## Open Questions

1. **Fee categorization** — Does the SchoolPay API return fee type (tuition vs development vs transport) or just a lump amount? The `supplementaryFeePayments` endpoint suggests categorization exists but needs testing.
2. **Balance endpoint** — Is there a way to get the current outstanding balance per student from SchoolPay, or do we need to compute it from transaction history?
3. **Webhook test mode** — Does SchoolPay provide a sandbox/test environment for webhook development?
4. **Rate limits** — What are the rate limits on the Sync API?
5. **Multiple schools per SchoolPay account** — Can a SchoolPay account manage multiple schools under one API credential?

---

## Success Criteria

- [ ] Parent pays via MTN MoMo → SchoolPay webhook fires → KlassApp receives it → WhatsApp receipt delivered in under 30 seconds
- [ ] If webhook is missed, poller catches it within 15 minutes
- [ ] Zero duplicate WhatsApp receipts for the same payment
- [ ] Unmatched payments (unknown studentPaymentCode) queued for admin review
- [ ] School admin can configure SchoolPay credentials from KlassApp dashboard
- [ ] School admin can bulk-import student payment codes via CSV
