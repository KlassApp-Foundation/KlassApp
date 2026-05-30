# Service Layer Reference

The WhatsApp integration has two service classes: `WhatsAppService` for low-level HTTP transport and `OutboundWhatsAppService` for business logic and notifications.

---

## WhatsAppService

**File**: `app/Services/WhatsAppService.php`
**Purpose**: HTTP transport layer. Communicates with Evolution API to send messages. Logs every outbound to `message_delivery_log`.

### Constructor

```php
public function __construct()
```

Reads configuration from `config/services.php`:

| Property | Config Key | Default |
|---|---|---|
| `$baseUrl` | `services.whatsapp.evolution_url` | `env('EVOLUTION_API_URL')` |
| `$apiKey` | `services.whatsapp.evolution_api_key` | `env('EVOLUTION_API_KEY')` |
| `$instanceName` | `services.whatsapp.instance_name` | `env('EVOLUTION_INSTANCE_NAME', 'klassapp')` |

### Methods

#### sendText()

```php
public function sendText(
    string $phone,        // E.164 format (e.g. +256701234567)
    string $message,      // Plain text (supports *bold*, _italic_, ~strikethrough~, ```code```)
    ?string $flowType,    // Analytics category: 'grades', 'attendance', 'fee_reminder', etc.
    ?int $userId,         // KlassApp user ID for delivery tracking
): array
```

Sends a free-form text message via `POST /message/sendText/{instance}` on Evolution API. Logs to `message_delivery_log` with category `service`.

**Returns**: `['success' => bool, 'message_id' => string, 'log_id' => int, 'status' => 'sent'|'failed']`

**Rate limiting**: A `delay` parameter of 1200ms (configurable) is sent to Evolution API between messages.

---

#### sendList()

```php
public function sendList(
    string $phone,          // E.164 format
    string $title,          // Header text
    array $sections,        // Sections with title + rows (max 10 rows total)
    ?string $description,   // Body text
    ?string $footerText,    // Footer text
    string $buttonText,     // CTA button label (default: 'View Options')
    ?string $flowType,      // Analytics category
    ?int $userId,
): array
```

Sends an interactive WhatsApp List Message via `POST /message/sendList/{instance}`.

**Section structure**:

```php
$sections = [
    [
        'title' => 'Section Name',
        'rows' => [
            ['title' => 'Option 1', 'description' => 'Description text'],
            ['title' => 'Option 2', 'description' => 'Description text'],
            // max 10 rows total across all sections
        ],
    ],
];
```

**Limits**:
- Max 10 rows across all sections
- Row titles: max 24 characters
- Row descriptions: max 72 characters
- Section titles: max 24 characters
- Description text: max 1024 characters

Logs with category `interactive`.

---

#### sendTemplate()

```php
public function sendTemplate(
    string $phone,
    string $templateName,     // Meta-approved template name
    array $variables,         // Template variables in order
    string $category,         // 'utility' | 'marketing' | 'authentication'
    ?int $userId,
): array
```

Sends a Meta-approved template message via `POST /template/send/{instance}`. Used for proactive outbound messages outside the 24-hour service window.

**Cost estimation** (built into the method):

| Category | Cost per delivered |
|---|---|
| `utility` | $0.006 |
| `marketing` | $0.025 |
| `authentication` | $0.004 |

The estimated cost is stored in `message_delivery_log.cost_usd`.

**Returns**: Includes `cost_usd` in the response array alongside `success`, `message_id`, `log_id`, `status`.

---

#### sendTextSafe()

```php
public function sendTextSafe(
    string $phone,
    string $message,
    ?string $fallbackTemplate,    // Template name if window is closed
    array $templateVariables,
    ?string $flowType,
    ?int $userId,
): array
```

Smart send that checks the 24-hour service window:

1. If `isWithinServiceWindow($phone)` is true → calls `sendText()` (free)
2. If window closed and `$fallbackTemplate` is provided → calls `sendTemplate()` (paid)
3. If window closed and no fallback → logs warning and sends as free-form anyway

---

#### sendToUser()

```php
public function sendToUser(
    int $userId,
    string $message,
    ?string $flowType,
): array
```

Convenience method that resolves a KlassApp user ID to their WhatsApp phone number (only opted-in users) and sends via `sendText()`.

Returns `['success' => false, 'error' => 'User has no linked WhatsApp number']` if the user has no linked phone.

---

#### isWithinServiceWindow()

```php
public function isWithinServiceWindow(string $phone): bool
```

Checks if the user's `last_inbound_at` timestamp is within the last 24 hours.

```php
// Logic:
$whatsappUser = WhatsAppUser::findByPhone($phone);
if (!$whatsappUser || !$whatsappUser->last_inbound_at) return false;
return $whatsappUser->last_inbound_at->gt(now()->subHours(24));
```

#### sendMedia()

```php
public function sendMedia(
    string $phone,
    string $mediaUrl,       // Public URL of the media file
    string $mediaType,      // 'image' | 'document' | 'audio' | 'video'
    ?string $caption,       // Optional caption
    ?string $flowType,
    ?int $userId,
): array
```

Sends media messages via Evolution API. Currently available for future use.

---

#### cleanPhone()

```php
protected function cleanPhone(string $phone): string
```

Strips all non-digit characters and leading zeros from the phone number for Evolution API:

- `+256701234567` → `256701234567`
- `0701234567` → `256701234567`

---

## OutboundWhatsAppService

**File**: `app/Services/OutboundWhatsAppService.php`
**Purpose**: Business logic layer for proactive notifications. Depends on `WhatsAppService` for HTTP delivery.

### Constructor

```php
public function __construct(
    protected WhatsAppService $whatsApp,
)
```

### Methods

#### queueOrSend()

```php
private function queueOrSend(
    string $phone,
    ?int $userId,
    string $message,
    string $flowType,
    ?string $templateName,
    array $templateVariables,
): int
```

Core cost-optimization method used by all notification methods:

1. Checks if the 24-hour window is open
2. If open → sends immediately via `sendText()` (free)
3. If closed → queues via `queueNotification()` for later delivery

Returns: `1` if sent immediately, `0` if queued, `-1` on error.

#### queueNotification()

```php
public function queueNotification(
    int $whatsappUserId,
    string $flowType,
    ?string $message,
    ?string $templateName,
    array $templateVariables,
    ?Carbon $sendAfter,
): WhatsAppPendingNotification
```

Creates a pending notification record in `whatsapp_pending_notifications`.

#### flushPending()

```php
public function flushPending(string $phone): int
```

Delivers all pending notifications for a given phone number. Called from `handleInbound()` after keyword routing — when a user messages in, their window opens and all queued items are delivered free.

Returns: number of notifications flushed.

#### sendExpiredQueue()

```php
public function sendExpiredQueue(): int
```

Force-sends pending notifications that have passed their `send_after` time, regardless of the 24-hour window. These will be cold sends (paid via template).

Returns: number of notifications sent.

#### flushAllOpenWindows()

```php
public function flushAllOpenWindows(): int
```

Batch method that finds all users with open 24-hour windows and flushes their pending queues. Called by `whatsapp:send-pending --flush-open` every 15 minutes.

Returns: total number of notifications flushed across all users.

---

### Notification Methods

These are the public notification methods called by event listeners and commands.

#### notifyGradesPublished()

```php
public function notifyGradesPublished(int $examId): void
```

Called by `SendGradesToWhatsApp` listener when `GradesPublished` event fires. Uses `queueOrSend()` internally.

#### notifyFeeReminder()

```php
public function notifyFeeReminder(
    User $student,
    string $type,      // 'reminder' | 'overdue'
): void
```

Called by `SendFeeReminders` command. Sends fee notification to the parent.

#### notifyComprehensiveGrades()

```php
public function notifyComprehensiveGrades(
    User $student,
    Exam $exam,
): void
```

Sends detailed grade breakdown. Uses `queueOrSend()`.

---

### Phone Resolution

```php
public function getParentPhones(User $student): array
```

Returns an array of parent phone numbers for a given student, checking:
- The student's linked parents via `studentAcademic`
- Each parent's `whatsapp_phone` on the user record
- Whether the parent has opted in to WhatsApp notifications

---

## Dependency Diagram

```
WhatsAppController (handles inbound HTTP)
        |
        |-- WhatsAppService (sends outbound HTTP)
        |       |
        |       |-- Http::post() to Evolution API
        |       |-- MessageDeliveryLog::create()
        |
        |-- OutboundWhatsAppService (business logic)
                |
                |-- WhatsAppService (for actual sending)
                |-- WhatsAppPendingNotification (queue)
                |-- WhatsAppUser (resolve phones)
                |-- WhatsAppPhoneHelper (format/validate)
```
