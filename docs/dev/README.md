# KlassApp WhatsApp Integration

The WhatsApp layer connects KlassApp to parents and staff via the WhatsApp Business Platform through a self-hosted [Evolution API](https://doc.evolution-api.com/) instance. It handles outbound notifications (grades, fees, attendance), inbound interactive menus, a cost-optimized message queue leveraging Meta's 24-hour service window, and an admin delivery dashboard.

---

## Architecture Overview

```
[WhatsApp User] ↔ Evolution API ↔ [Laravel Webhook / Outbound Service]
                                       ↕
                              WhatsAppService (HTTP transport)
                                       ↕
                           OutboundWhatsAppService (business logic)
                                       ↕
                           MessageDeliveryLog (DB tracking)
                           WhatsAppPendingNotification (queue)

[Admin Browser] → WhatsAppDashboardController → MessageDeliveryLog (read)
[n8n/Typebot]   → WhatsAppController endpoints   → Student data (grades, fees, etc.)
```

- **Evolution API** — self-hosted middleware that bridges WhatsApp's Business API and KlassApp via HTTP webhooks. Manages connection states, QR pairing, and message delivery receipts.
- **WhatsAppService** — low-level HTTP transport. Sends text, template, list, and media messages via Evolution API endpoints. Logs every outbound to `message_delivery_log`.
- **OutboundWhatsAppService** — business logic layer for proactive notifications. Implements the cost-optimized queue pattern: sends immediately if the 24-hour service window is open (free), otherwise queues for later delivery.
- **WhatsAppController** — stateless data API used by n8n/Typebot (or any conversation flow engine). Endpoints resolve users by phone, return grades/fees/attendance, and send interactive menus.
- **WhatsAppDashboardController** — admin-only delivery dashboard showing KPIs, daily volume trends, and flow-type breakdowns.
- **WhatsAppPendingNotification** — database-backed queue for cost-optimized outbound delivery.

---

## Quick Start

1. **Set environment variables** in `.env`:

   ```ini
   EVOLUTION_API_URL=http://localhost:8081
   EVOLUTION_API_KEY=your-evolution-api-key
   WHATSAPP_HMAC_SECRET=your-hmac-secret
   WHATSAPP_BUSINESS_NUMBER=+256793844906
   ```

2. **Run migrations**:

   ```bash
   php artisan migrate
   ```

3. **Configure Evolution API webhooks**:
   - Message webhook → `POST https://your-app.com/api/whatsapp/inbound`
   - Delivery status webhook → `POST https://your-app.com/api/whatsapp/delivery`
   - Both webhooks must include the `apikey` header matching `EVOLUTION_API_KEY`

4. **Add the queue processor to crontab**:

   ```cron
   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```

   This runs `whatsapp:send-pending --flush-open` every 15 minutes (defined in `Console/Kernel.php`).

5. **Verify inbound** — send any message to the business WhatsApp number. The bot should reply with a menu.

6. **Link an admin phone** — navigate to Admin → Settings → WhatsApp Phone in the admin panel to link a user's WhatsApp number.

7. **Test fee reminders**:

   ```bash
   php artisan whatsapp:send-fee-reminders --dry-run
   ```

   Remove `--dry-run` to send live.

---

## Documentation Index

| Doc | Purpose | Audience |
|---|---|---|
| [setup.md](setup.md) | Evolution API deployment, env config, webhook setup | Ops / Admin |
| [api-reference.md](api-reference.md) | Webhook payloads, endpoint signatures, response codes | Developer |
| [interactive-menu.md](interactive-menu.md) | List Message menu, role routing, keyword handling | Admin / Developer |
| [cost-optimization.md](cost-optimization.md) | 24-hour service window, notification queue, Meta pricing | Ops / Admin |
| [admin-dashboard.md](admin-dashboard.md) | Delivery dashboard KPIs, charts, monitoring | Admin |
| [service-layer.md](service-layer.md) | WhatsAppService and OutboundWhatsAppService method reference | Developer |
| [emis-lin-onboarding.md](emis-lin-onboarding.md) | EMIS/LIN integration, Path 2 CSV import, Path 3 self-registration | Admin |
| [models.md](models.md) | Database table schemas and relationships | Developer |
| [testing.md](testing.md) | PHPUnit test setup, factories, and running | Developer |
| [digitalocean-deployment.md](digitalocean-deployment.md) | DO $200 credit — 10-school testing environment provisioning | Ops / Admin |
| [schoolpay-integration.md](schoolpay-integration.md) | SchoolPay API integration spec (payment aggregator) | Developer |
| [ai-agent-layer.md](ai-agent-layer.md) | AI Agent Layer spec — marksheet ingestion, analysis, enrichment | Developer |

---

## Key Environment Variables

| Variable | Default | Required | Description |
|---|---|---|---|
| `EVOLUTION_API_URL` | `http://localhost:8081` | Yes | Base URL of the self-hosted Evolution API instance |
| `EVOLUTION_API_KEY` | — | Yes | API key for Evolution API authentication |
| `EVOLUTION_INSTANCE_NAME` | `klassapp` | No | Evolution API instance name |
| `WHATSAPP_HMAC_SECRET` | — | Yes | Secret key for HMAC-signing webhook payloads |
| `WHATSAPP_BUSINESS_NUMBER` | `+256793844906` | Yes | Business WhatsApp number in E.164 format |
| `WHATSAPP_BUSINESS_NAME` | `KlassApp` | No | Display name for the business |

---

## Related Files

| File | Purpose |
|---|---|
| `config/services.php` | WhatsApp configuration (business_number, business_name, evolution_url, evolution_api_key, instance_name, send_delay, template_language) |
| `app/Services/WhatsAppService.php` | HTTP transport — `sendText()`, `sendList()`, `sendTemplate()`, `sendTextSafe()`, `sendToUser()`, `isWithinServiceWindow()`, `sendMedia()` |
| `app/Services/OutboundWhatsAppService.php` | Business logic — `queueOrSend()`, `queueNotification()`, `flushPending()`, `sendExpiredQueue()`, `flushAllOpenWindows()`, notification methods |
| `app/Http/Controllers/Api/WhatsAppController.php` | API endpoints — `handleInbound()`, `identify()`, `sendMenu()`, `buildMenuSections()`, `grades()`, `attendance()`, `feeBalance()`, `schoolEvents()`, `deliveryWebhook()`, `handleDeliveryFailure()` |
| `app/Http/Controllers/Admin/WhatsAppDashboardController.php` | Admin delivery dashboard with KPI aggregation |
| `app/Http/Requests/WhatsApp/StoreWhatsAppWebhookRequest.php` | Inbound webhook payload validation |
| `app/Helpers/WhatsAppPhoneHelper.php` | Phone normalisation and Uganda (+256) validation |
| `app/Models/WhatsAppUser.php` | WhatsApp user phone linking model |
| `app/Models/MessageDeliveryLog.php` | Outbound/inbound message delivery tracking |
| `app/Models/WhatsAppPendingNotification.php` | Cost-optimized notification queue model |
| `app/Console/Commands/SendFeeReminders.php` | Scheduled fee reminder dispatcher |
| `app/Console/Commands/SendWhatsAppPendingNotifications.php` | Queue processor for pending notifications |
| `app/Events/GradesPublished.php` | Event fired when grades are published |
| `app/Listeners/SendGradesToWhatsApp.php` | Listener that dispatches grade notifications |

---

## Related Configuration

- **`config/services.php`** — WhatsApp settings array: `business_number`, `business_name`, `evolution_url`, `evolution_api_key`, `instance_name`, `send_delay` (1200ms), `template_language` (en)
- **`routes/api.php`** — `Route::prefix('whatsapp')` group: `/inbound` (POST), `/identify-user` (POST), `/student/{id}/grades` (GET), `/student/{id}/attendance` (GET), `/fees/{id}/balance` (GET), `/school/{id}/events` (GET)
- **`routes/admin.php`** — `/whatsapp/phone` (GET + POST), `/whatsapp/dashboard` (GET)
- **`app/Console/Kernel.php`** — Schedule: fee reminders weekly (Mondays) and daily (overdue), pending queue flush every 15 minutes — all with `withoutOverlapping()`
