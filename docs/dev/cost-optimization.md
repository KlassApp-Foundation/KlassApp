# Cost Optimization: 24-Hour Service Window & Notification Queue

The WhatsApp integration is designed to minimize messaging costs by leveraging Meta's **24-hour customer service window** and a database-backed pending notification queue.

---

## Meta WhatsApp Pricing Model (July 2025)

WhatsApp charges per conversation, not per message. A conversation opens when a business sends a template message or when a user messages the business.

| Conversation Category | Cost (Uganda / Rest of Africa) | When It Applies |
|---|---|---|
| **Service** (user-initiated) | **FREE** | Within 24 hours of the user's last inbound message |
| **Utility** | $0.004 per delivered | Proactive template messages outside the 24hr window |
| **Marketing** | $0.0225 per delivered | Promotional messages (not applicable for school notifications) |
| **Authentication** | $0.004 per delivered | OTPs and verification codes |

### Key Insight

**Service conversations (inside the 24-hour window) cost nothing.** If a parent messages the bot asking for grades, the reply — and any proactive notifications sent within 24 hours of that message — are free.

The entire cost-optimization architecture is built around maximizing free service conversations and minimizing paid template sends.

---

## 24-Hour Service Window Tracking

### How It Works

1. Every inbound message from a user updates `whatsapp_users.last_inbound_at` to the current timestamp
2. `WhatsAppService::isWithinServiceWindow($phone)` checks if `last_inbound_at` is within the last 24 hours
3. If within the window → send free-form text messages (free)
4. If outside the window → send a Meta-approved template (paid) or queue for later

### Code Flow

```
User sends "menu" → handleInbound()
  → WhatsAppUser::where('phone', $phone)->update(['last_inbound_at' => now()])
  → sendMenu() → WhatsAppService::sendList()  (FREE)

Cron: flushAllOpenWindows()
  → for each user with last_inbound_at > 24hrs
  → WhatsAppService::isWithinServiceWindow($phone) === true
  → flushPending() → sendText()  (FREE — window is open)
```

### sendTextSafe()

The safe-send method handles the window check automatically:

```php
// If window is open → sendText (free)
// If window is closed → sendTemplate (paid, requires fallback template)
$this->whatsApp->sendTextSafe(
    phone: '+256701234567',
    message: 'Your child\'s grades are now available.',
    fallbackTemplate: 'grade_published',
    templateVariables: ['Parent Name', 'Student Name', 'Term 1'],
);
```

---

## Pending Notification Queue

### Table Structure

**Table**: `whatsapp_pending_notifications`

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT PK | Auto-increment |
| `whatsapp_user_id` | BIGINT FK | References `whatsapp_users.id` |
| `flow_type` | VARCHAR(50) | `grades`, `fee_reminder`, `attendance`, `event` |
| `message` | TEXT | The message content to send |
| `template_name` | VARCHAR(100) | Meta template name (for cold sends) |
| `template_variables` | JSON | Variables for the template |
| `status` | VARCHAR(20) | `pending`, `sent`, `expired` |
| `send_after` | DATETIME | Don't send before this time |
| `created_at` | DATETIME | When the notification was queued |
| `sent_at` | DATETIME | When it was actually sent |

### Queue Flow

```
queueOrSend($phone, $message, $flowType, $templateName):
  ↓
isWithinServiceWindow($phone)?
  ├── YES → sendText() immediately (FREE)
  └── NO  → queueNotification() to DB (pending)

later:
  User sends any message → handleInbound()
    → updates last_inbound_at
    → calls flushPending($phone)
    → delivers all queued items (FREE - window now open)
```

### When Queued Items Get Sent

| Trigger | Method | Timing |
|---|---|---|
| User sends any message | `flushPending()` in `handleInbound()` | Within seconds |
| Cron every 15 minutes | `flushAllOpenWindows()` via `whatsapp:send-pending --flush-open` | Every 15 min |
| Manual command | `php artisan whatsapp:send-pending --flush-open` | On demand |

### Queue Expiry

- Queued notifications older than **7 days** with status `pending` are automatically expired
- Expired items are logged but not sent
- This prevents stale notifications (e.g., a fee reminder from 2 weeks ago)

---

## Twitter/LIN Onboarding Cost Implications

The Path 3 self-registration flow (LIN + NIN verification) is specifically designed to be cost-free:

| Step | Cost | Reason |
|---|---|---|
| Parent sends LIN | Free | User-initiated message (starts service window) |
| Bot asks for NIN | Free | Service reply within window |
| Bot sends confirmation | Free | Still within 24-hour window |
| Bot sends main menu | Free | Still within window |
| **Window is now open for 24 hours** | **Free** | Any proactive notifications in the next 24hrs are free |

A single self-registration unlocks a 24-hour window, during which the school can send grades, fee reminders, and attendance updates at **zero cost**.

---

## Cost Projection Examples

### Scenario A: School with 500 parents, all opted in

| Activity | Monthly Volume | Inside Window | Outside Window | Estimated Cost |
|---|---|---|---|---|
| Grade publish (monthly) | 500 | 300 (60%) | 200 (40%) | 200 x $0.004 = $0.80 |
| Fee reminders (weekly) | 2,000 | 1,200 (60%) | 800 (40%) | 800 x $0.004 = $3.20 |
| Attendance alerts | 1,000 | 600 (60%) | 400 (40%) | 400 x $0.004 = $1.60 |
| Bot interactions | 500 | 500 (100%) | 0 | $0 |
| **Total** | **4,000** | **2,600 (65%)** | **1,400 (35%)** | **$5.60/month** |

### Scenario B: School with 2,000 parents, low engagement (20% in-window)

| Activity | Monthly Volume | Inside Window | Outside Window | Estimated Cost |
|---|---|---|---|---|
| Grade publish | 2,000 | 400 (20%) | 1,600 (80%) | $6.40 |
| Fee reminders | 8,000 | 1,600 (20%) | 6,400 (80%) | $25.60 |
| Bot interactions | 500 | 500 (100%) | 0 | $0 |
| **Total** | **10,500** | **2,500 (24%)** | **8,000 (76%)** | **$32.00/month** |

Strategies to improve in-window percentage:
1. Encourage parents to message the bot (reply MENU to any school SMS)
2. Use Path 3 self-registration (every registration opens a window)
3. Batch notifications to flush during known high-activity hours

---

## Commands

```bash
# Flush pending notifications for all users with open windows
php artisan whatsapp:send-pending --flush-open

# Force-send pending notifications regardless of window (will cost $)
php artisan whatsapp:send-pending

# Send fee reminders (with cost estimate in dry-run)
php artisan whatsapp:send-fee-reminders --dry-run

# Check how many users have open windows
php artisan tinker
> use App\Models\WhatsAppUser;
> WhatsAppUser::where('last_inbound_at', '>', now()->subHours(24))->count();
```

---

## Monitoring Costs

The `message_delivery_log` table tracks cost per message:

```sql
-- Total cost for the current month
SELECT SUM(cost_usd) AS total_cost
FROM message_delivery_log
WHERE sent_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
  AND direction = 'outbound'
  AND cost_usd > 0;

-- Cost by flow type
SELECT flow_type, SUM(cost_usd) AS cost, COUNT(*) AS messages
FROM message_delivery_log
WHERE direction = 'outbound'
  AND cost_usd > 0
GROUP BY flow_type
ORDER BY cost DESC;
```

The admin dashboard shows delivery rates — cross-reference with cost data to optimize.
