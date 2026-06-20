# Setup Guide

Deploying and configuring the KlassApp WhatsApp integration — from bare metal to working bot.

---

## Prerequisites

- A **WhatsApp Business Account** (WABA) approved by Meta
- A **phone number** registered with the WABA (cannot be linked to another WhatsApp account)
- A **server** for Evolution API (1 vCPU / 2 GB RAM minimum; MongoDB required)
- KlassApp instance with database access and Laravel scheduler running
- **Meta-approved templates** if you plan to send proactive notifications outside the 24-hour window

---

## 1. Evolution API Deployment

Evolution API is the self-hosted middleware that bridges WhatsApp's Web/Cloud API to KlassApp via HTTP.

### 1.1 Docker Deployment (Recommended)

Single container for testing:

```bash
docker run --name evolution-api \
  -p 8081:8081 \
  -e AUTHENTICATION_API_KEY="your-secure-key" \
  -e DATABASE_ENABLED=true \
  -e DATABASE_CONNECTION_URI="mongodb://host.docker.internal:27017/evolution" \
  -d \
  evolutionapi/evolution-api:latest
```

Production Docker Compose:

```yaml
version: "3.8"
services:
  evolution-api:
    image: evolutionapi/evolution-api:latest
    ports:
      - "8081:8081"
    environment:
      AUTHENTICATION_API_KEY: "${EVOLUTION_AUTH_KEY}"
      DATABASE_ENABLED: "true"
      DATABASE_CONNECTION_URI: "mongodb://mongo:27017/evolution"
    restart: unless-stopped
    depends_on:
      - mongo
  mongo:
    image: mongo:6
    volumes:
      - mongo-data:/data/db
    restart: unless-stopped
volumes:
  mongo-data:
```

> **Important**: Protect the Evolution API behind a firewall or VPN in production. It should not be publicly accessible on port 8081. The Laravel application should reach it via an internal network.

### 1.2 Create and Connect an Instance

Create a new instance:

```bash
curl -X POST http://localhost:8081/instance/create \
  -H "apikey: your-secure-key" \
  -H "Content-Type: application/json" \
  -d '{"instanceName": "klassapp", "qrcode": true}'
```

The response includes an `instance.apiKey`. Save this value — it becomes your `EVOLUTION_API_KEY`.

Connect the instance by scanning the QR code:

```bash
# Get QR code (returns base64 image or a URL)
curl http://localhost:8081/instance/qrcode/klassapp \
  -H "apikey: your-secure-key"
```

Open the returned URL in a browser and scan with the WhatsApp mobile app (**Linked Devices → Link a Device**).

Verify connection:

```bash
curl http://localhost:8081/instance/connectionState/klassapp \
  -H "apikey: your-secure-key"
```

Expected response: `{"instance": "klassapp", "state": "open"}`

### 1.3 Configure Webhooks

**Inbound message webhook** (Evolution API sends new messages here):

```bash
curl -X POST http://localhost:8081/webhook/set/klassapp \
  -H "apikey: your-secure-key" \
  -H "Content-Type: application/json" \
  -d '{
    "webhookUrl": "https://your-klassapp.com/api/whatsapp/inbound",
    "webhookEvents": ["messages.upsert"],
    "webhookHeaders": {
      "apikey": "your-secure-key"
    }
  }'
```

**Delivery status webhook** (delivery receipts, read receipts, errors):

```bash
curl -X POST http://localhost:8081/webhook/set/klassapp \
  -H "apikey: your-secure-key" \
  -H "Content-Type: application/json" \
  -d '{
    "webhookUrl": "https://your-klassapp.com/api/whatsapp/delivery",
    "webhookEvents": ["messages.ack", "messages.error"],
    "webhookHeaders": {
      "apikey": "your-secure-key"
    }
  }'
```

> **Note**: If your Evolution API version only supports one webhook per instance, configure a single webhook URL with both event types and let your application route internally. KlassApp's inbound webhook controller ignores non-message events.

### 1.4 Connection Health Monitoring

Set up a cron job to check if the Evolution API instance is still connected:

```bash
*/5 * * * * curl -sf http://localhost:8081/instance/connectionState/klassapp \
  -H "apikey: your-key" | grep -q '"open"' || echo "Evolution API disconnected" | mail -s "Alert" admin@school.com
```

---

## 2. Laravel Configuration

### 2.1 Environment Variables

Add to your `.env`:

```ini
EVOLUTION_API_URL=http://evolution-api:8081
EVOLUTION_API_KEY=<api-key-from-step-1.2>
EVOLUTION_INSTANCE_NAME=klassapp
WHATSAPP_HMAC_SECRET=<generate: openssl rand -hex 32>
WHATSAPP_BUSINESS_NUMBER=+256765275289
WHATSAPP_BUSINESS_NAME=KlassApp
WHATSAPP_SEND_DELAY=1200
WHATSAPP_TEMPLATE_LANGUAGE=en
```

Generate the HMAC secret:

```bash
openssl rand -hex 32
```

### 2.2 Run Migrations

```bash
php artisan migrate
```

WhatsApp-related migrations:

| Migration File | Table / Change | Purpose |
|---|---|---|
| `2026_05_16_000001_create_whatsapp_users_table.php` | `whatsapp_users` | Phone → user linking table |
| `2026_05_16_000002_create_message_delivery_log_table.php` | `message_delivery_log` | Full audit log of all WhatsApp messages |
| `2026_05_27_000001_create_premium_pages_table.php` | `premium_pages` | School premium landing pages |
| `2026_05_29_000002_drop_user_type_from_whatsapp_users.php` | `whatsapp_users` | Drops redundant `user_type` column |
| `2026_05_29_000003_add_last_inbound_at_to_whatsapp_users.php` | `whatsapp_users` | Adds 24-hour window tracking column |
| `2026_05_29_000004_create_whatsapp_pending_notifications_table.php` | `whatsapp_pending_notifications` | Cost-optimized notification queue |

### 2.3 Schedule Jobs

Add to your system crontab:

```cron
* * * * * cd /var/www/klassapp && php artisan schedule:run >> /dev/null 2>&1
```

The following tasks run on schedule:

| Command | Frequency | Purpose |
|---|---|---|
| `whatsapp:send-fee-reminders --type=reminder` | Mondays 08:00 | Weekly fee reminders |
| `whatsapp:send-fee-reminders --type=overdue` | Daily 09:00 | Overdue fee notices |
| `whatsapp:send-pending --flush-open` | Every 15 min | Deliver queued notifications when 24hr windows open |

All are configured with `withoutOverlapping()` in `app/Console/Kernel.php`.

### 2.4 Verify Configuration

```bash
# Check config is loaded correctly
php artisan tinker
> config('services.whatsapp.business_number')
> "+256765275289"
> config('services.whatsapp.evolution_url')
> "http://localhost:8081"
```

---

## 3. Webhook Security

### 3.1 HMAC Signing

All internal data endpoints (`/api/whatsapp/identify-user`, `/api/whatsapp/student/{id}/grades`, etc.) are protected by the `WhatsAppHmac` middleware. This ensures only authenticated callers (n8n, Typebot, internal services) can access student data.

How to generate a signature:

```bash
# Given the raw JSON body
BODY='{"phone":"+256701234567"}'
SECRET="your-hmac-secret"

# Compute HMAC-SHA256
SIGNATURE=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $NF}')

# Call the endpoint
curl -X POST https://your-app.com/api/whatsapp/identify-user \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: $SIGNATURE" \
  -d "$BODY"
```

### 3.2 Inbound Webhook Authentication

The inbound webhook (`POST /api/whatsapp/inbound`) is NOT behind HMAC middleware. Instead, it relies on:

1. The `apikey` header sent by Evolution API (matched against `EVOLUTION_API_KEY`)
2. Payload validation via `StoreWhatsAppWebhookRequest` FormRequest (validates event type, phone format, payload size, content structure)

### 3.3 Firewall Rules

Recommended network segmentation:

```
Internet → Cloudflare/CDN → Laravel (443)
                               ↓ (internal)
                        Evolution API (8081) → MongoDB
```

Evolution API should never be directly exposed to the internet. It should only be reachable from the Laravel application servers.

---

## 4. WhatsApp Templates

Proactive outbound messages sent when the 24-hour service window is closed require Meta-approved templates.

### 4.1 Creating Templates

Go to **Meta Business Manager → WhatsApp → Message Templates** and create templates with these categories:

| Template Name | Category | Body Variables | Example Content |
|---|---|---|---|
| `fee_reminder` | Utility | `{{1}}` parent name, `{{2}}` amount, `{{3}}` due date | "Dear {{1}}, fee payment of UGX {{2}} is due on {{3}}." |
| `grade_published` | Utility | `{{1}}` parent name, `{{2}}` student name, `{{3}}` exam term | "Dear {{1}}, {{2}}'s {{3}} grades are now available." |
| `attendance_update` | Utility | `{{1}}` parent name, `{{2}}` student name, `{{3}}` status, `{{4}}` date, `{{5}}` details | "Dear {{1}}, {{2}} was {{3}} on {{4}} ({{5}})." |

### 4.2 Template Requirements for Approval

- Include opt-out language: "Reply STOP to unsubscribe"
- Use accurate category (Utility for transactional, Marketing for promotional)
- Variable placeholders must represent actual values (no empty or misleading variables)
- Comply with Meta's [Commerce Policy](https://www.whatsapp.com/legal/commerce-policy/)

### 4.3 Sending Templates

```php
$this->whatsApp->sendTemplate(
    phone: '+256701234567',
    templateName: 'fee_reminder',
    variables: ['Mukasa Joseph', '500,000', '15 June 2026'],
    category: 'utility',
);
```

---

## 5. Testing Setup

### 5.1 WhatsApp Test Numbers

Meta provides test numbers in the WhatsApp Business Account dashboard. You can:

1. Add test numbers under **WABA → Settings → Test Numbers**
2. Configure them to auto-reply to confirm delivery
3. Use them for integration testing without real user impact

### 5.2 Dry-Run Mode

All outbound commands support `--dry-run`:

```bash
php artisan whatsapp:send-fee-reminders --dry-run --school-id=1
php artisan whatsapp:send-pending --dry-run
```

Dry-run mode logs what would be sent without making HTTP calls to Evolution API.

### 5.3 PHPUnit Tests

```bash
# Run all WhatsApp tests
php artisan test tests/Feature/WhatsApp/
```

See [testing.md](testing.md) for detailed test documentation.

---

## 6. Troubleshooting

### QR Code Issues

| Symptom | Likely Cause | Fix |
|---|---|---|
| QR not displaying | Instance not fully created | Recreate instance: `POST /instance/create` |
| QR expired | Waited too long to scan | Restart instance: `POST /instance/restart/klassapp` |
| "Disconnected" after scanning | Network issue or phone changed | Delete and recreate instance |

### Webhook Issues

| Symptom | Likely Cause | Fix |
|---|---|---|
| 401 on inbound webhook | `apikey` header wrong or missing | Verify `apikey` in Evolution webhook config matches `EVOLUTION_API_KEY` |
| 401 on data endpoints | HMAC signature mismatch | Recompute signature with the exact request body and `WHATSAPP_HMAC_SECRET` |
| No webhook received | Evolution API cannot reach your server | Check network, firewall, and DNS. Use `ngrok` for local testing |

### Message Delivery Issues

| Symptom | Likely Cause | Fix |
|---|---|---|
| "Pending" in delivery log | Evolution API hasn't sent ack yet | Wait for delivery receipt. Messages sent outside 24hr window won't show as sent |
| "Failed" in delivery log | Evolution API returned error | Check Evolution API logs. Verify recipient has WhatsApp |
| Queue not draining | 24-hour window closed for all users | Wait for users to message in, or run `whatsapp:send-pending` without `--flush-open` |
| Rate limited | Sending too fast | Increase `WHATSAPP_SEND_DELAY` (default 1200ms) |

### User Linking Issues

| Symptom | Likely Cause | Fix |
|---|---|---|
| "Phone not linked" on identify | User hasn't gone through linking | Direct to admin panel → WhatsApp Phone |
| "Opted out" response | User sent OPTOUT | User must send OPTIN to re-enable |
| Wrong menu shown | `usergroup_id` is incorrect | Check the user's role in the Users table |

---

## 7. Production Checklist

- [ ] Evolution API deployed behind internal network (not internet-facing)
- [ ] HTTPS enforced on all webhook URLs
- [ ] `EVOLUTION_API_KEY` rotated from default
- [ ] `WHATSAPP_HMAC_SECRET` is a strong random string
- [ ] Laravel scheduler running in crontab
- [ ] All Meta templates approved
- [ ] Fee reminder dry-run tested with a real school
- [ ] Admin WhatsApp number linked and menu tested
- [ ] Delivery webhook endpoint verified with Evolution API
- [ ] Logs monitored for 24 hours post-deployment
- [ ] `.env.example` updated with new WhatsApp variables
