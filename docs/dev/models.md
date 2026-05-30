# Models Reference

Database models used by the WhatsApp integration.

---

## WhatsAppUser

**Table**: `whatsapp_users`
**File**: `app/Models/WhatsAppUser.php`

Tracks the link between a WhatsApp phone number and a KlassApp user account.

### Schema

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | No | Auto | Auto-increment |
| `phone` | VARCHAR(20) | No | — | WhatsApp phone number (E.164: +256...) |
| `user_id` | BIGINT UNSIGNED FK | No | — | References `users.id` |
| `verified_at` | DATETIME | Yes | NULL | When the phone was first verified |
| `opted_in` | BOOLEAN | No | `true` | Whether user has opted in to notifications |
| `unsubscribed_at` | DATETIME | Yes | NULL | When the user last opted out |
| `last_inbound_at` | DATETIME | Yes | NULL | Timestamp of last inbound message (for 24hr window tracking) |
| `created_at` | DATETIME | No | — | Laravel timestamp |
| `updated_at` | DATETIME | No | — | Laravel timestamp |

### Indexes

| Index | Columns |
|---|---|
| PK | `id` |
| Unique | `phone` |
| FK | `user_id` → `users.id` |

### Relationships

```php
// WhatsAppUser belongs to a User
public function user(): BelongsTo
```

### Scopes

```php
// Only opted-in users
public function scopeOptedIn($query)
```

### Methods

```php
// Find by phone number (E.164)
public static function findByPhone(string $phone): ?self

// Check if verified
public function isVerified(): bool
```

---

## MessageDeliveryLog

**Table**: `message_delivery_log`
**File**: `app/Models/MessageDeliveryLog.php`

Audit log for every WhatsApp message sent or received. Note: `$timestamps = false` — the migration has no `created_at`/`updated_at`; use `sent_at` instead.

### Schema

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | No | Auto | Auto-increment |
| `whatsapp_message_id` | VARCHAR(255) | No | — | Evolution API message ID (or UUID fallback) |
| `phone` | VARCHAR(20) | No | — | Phone number (E.164) |
| `template_name` | VARCHAR(100) | Yes | NULL | Meta template name (if template was used) |
| `category` | VARCHAR(50) | Yes | NULL | `service`, `interactive`, `utility`, `marketing`, `authentication` |
| `direction` | VARCHAR(10) | Yes | 'outbound' | `outbound` or `inbound` |
| `status` | VARCHAR(20) | Yes | 'sent' | `sent`, `delivered`, `read`, `failed` |
| `cost_usd` | DECIMAL(10,6) | Yes | NULL | Estimated cost in USD (for template messages) |
| `content_preview` | TEXT | Yes | NULL | Truncated message preview (200 chars) |
| `user_id` | BIGINT UNSIGNED | Yes | NULL | References `users.id` (if identifiable) |
| `flow_type` | VARCHAR(50) | Yes | NULL | `grades`, `fee_reminder`, `attendance`, `event`, `menu`, `timetable`, etc. |
| `error_message` | TEXT | Yes | NULL | Error details if status is `failed` |
| `sent_at` | DATETIME | Yes | NULL | When the message was sent |
| `delivered_at` | DATETIME | Yes | NULL | When delivery was confirmed |
| `read_at` | DATETIME | Yes | NULL | When the message was read |

### Indexes

| Index | Columns |
|---|---|
| PK | `id` |
| Index | `phone` |
| Index | `status` |
| Index | `flow_type` |
| Index | `sent_at` |

### Relationships

```php
// Optional link to KlassApp user
public function user(): BelongsTo
```

### Scopes

```php
public function scopeOfStatus($query, string $status)
public function scopeDateRange($query, string $from, string $to)
public function scopeOfFlowType($query, string $type)
```

### Methods

```php
public function markDelivered(): void   // Sets status = 'delivered', delivered_at = now()
public function markRead(): void        // Sets status = 'read', read_at = now()
public function markFailed(string $error = ''): void  // Sets status = 'failed', error_message
```

---

## WhatsAppPendingNotification

**Table**: `whatsapp_pending_notifications`
**File**: `app/Models/WhatsAppPendingNotification.php`

Cost-optimized notification queue. Notifications are deferred here when the 24-hour service window is closed, and flushed when the window opens.

### Schema

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | No | Auto | Auto-increment |
| `whatsapp_user_id` | BIGINT UNSIGNED FK | No | — | References `whatsapp_users.id` |
| `flow_type` | VARCHAR(50) | Yes | NULL | `grades`, `fee_reminder`, `attendance`, `event` |
| `message` | TEXT | Yes | NULL | Message text to send (for free-form within window) |
| `template_name` | VARCHAR(100) | Yes | NULL | Template name for cold send (when window closed) |
| `template_variables` | JSON | Yes | NULL | Template variable values |
| `status` | VARCHAR(20) | No | 'pending' | `pending`, `sent`, `expired` |
| `send_after` | DATETIME | Yes | NULL | Don't send before this time |
| `sent_at` | DATETIME | Yes | NULL | When the notification was actually sent |
| `created_at` | DATETIME | No | — | Laravel timestamp |
| `updated_at` | DATETIME | No | — | Laravel timestamp |

### Relationships

```php
public function whatsappUser(): BelongsTo
```

---

## Relationship Diagram

```
users (Laravel auth)
  │
  ├── whatsapp_users (phone linking)
  │     └── hasMany → whatsapp_pending_notifications (queue)
  │
  └── message_delivery_log (audit log)
        (user_id is nullable - linked when identifiable)

student_records
  ├── lin (12-digit LIN, nullable)
  └── parent_nin_hash (SHA-256, nullable)
        └── Links parents to students for Path 3 self-registration
```

---

## Migration Files

| Migration | Table / Change | Purpose |
|---|---|---|
| `2026_05_16_000001_create_whatsapp_users_table.php` | `whatsapp_users` | Phone-to-user linking |
| `2026_05_16_000002_create_message_delivery_log_table.php` | `message_delivery_log` | Message audit log |
| `2026_05_27_000001_create_premium_pages_table.php` | `premium_pages` | School landing pages (related but not core WhatsApp) |
| `2026_05_29_000002_drop_user_type_from_whatsapp_users.php` | — | Drop redundant `user_type` column (routing now by `users.usergroup_id`) |
| `2026_05_29_000003_add_last_inbound_at_to_whatsapp_users.php` | — | Add 24-hour window tracking column |
| `2026_05_29_000004_create_whatsapp_pending_notifications_table.php` | `whatsapp_pending_notifications` | Cost-optimized notification queue |
