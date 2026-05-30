# Admin Delivery Dashboard

The WhatsApp delivery dashboard gives school admins visibility into all WhatsApp message activity — sent volumes, delivery rates, failure monitoring, and flow-type breakdowns.

**Route**: `GET /admin/whatsapp/dashboard`
**Controller**: `App\Http\Controllers\Admin\WhatsAppDashboardController@index`

---

## Dashboard Sections

### KPI Cards (Top Row)

| KPI | Description | Source |
|---|---|---|
| **Total Sent** | Total outbound messages in the selected period | `message_delivery_log` where `direction = 'outbound'` |
| **Delivered** | Messages with status `delivered` or `read` | Same table, `status IN ('delivered', 'read')` |
| **Failure Rate** | Percentage of outbound messages that failed | `failed / total * 100` |
| **Pending** | Messages sent but not yet delivered/read | `status = 'sent'` |
| **Inbound Received** | Messages received from users | `direction = 'inbound'` |

### Delivery Rate

Shown as a percentage: `(delivered / total) * 100`. A healthy system should see 85%+ delivery rate. Rates below 70% indicate issues with recipient reachability or Evolution API connection.

### Failure Rate

Shown as a percentage: `(failed / total) * 100`. Investigate if this exceeds 10%. Common causes:
- Recipient phone has no WhatsApp
- Evolution API instance disconnected
- Rate limiting by WhatsApp

### Linked Users

| Metric | Description |
|---|---|
| **Total Linked** | Total `whatsapp_users` records |
| **Opted In** | Users who have opted in to notifications (`opted_in = true`) |

### Flow-Type Breakdown

Messages grouped by `flow_type` column. Each row shows:

| Flow Type | Total Sent | Failed | Success Rate |
|---|---|---|---|
| `grades` | 145 | 2 | 98.6% |
| `fee_reminder` | 89 | 1 | 98.9% |
| `attendance` | 67 | 3 | 95.5% |
| `event` | 34 | 0 | 100% |
| `timetable` | 12 | 1 | 91.7% |
| `menu` | 203 | 0 | 100% |

This helps identify which notification types have delivery problems.

### Daily Volume Trend

A 7-day bar chart showing sent messages per day. Columns:

| Date | Sent | Delivered | Failed |
|---|---|---|---|
| 2026-05-23 | 45 | 42 | 1 |
| 2026-05-24 | 62 | 58 | 2 |
| 2026-05-25 | 78 | 72 | 1 |
| 2026-05-26 | 51 | 48 | 0 |
| 2026-05-27 | 93 | 88 | 3 |
| 2026-05-28 | 87 | 83 | 2 |
| 2026-05-29 | 34 | 32 | 1 |

### Recent Activity Log

The most recent 50 messages across all flows, showing:

- **Timestamp** — when the message was sent/received
- **Phone** — masked recipient/sender (last 4 digits only)
- **Flow Type** — category of the message
- **Status** — sent, delivered, read, failed
- **Direction** — inbound or outbound

---

## Period Filtering

Use the `period` query parameter to switch time ranges:

| Value | Range | Use Case |
|---|---|---|
| `24h` | Last 24 hours | Real-time monitoring |
| `7d` | Last 7 days | Weekly review (default) |
| `30d` | Last 30 days | Monthly reporting |
| `90d` | Last 90 days | Quarterly trends |

Example: `GET /admin/whatsapp/dashboard?period=30d`

---

## Interpreting the Dashboard

### Healthy System Indicators

- **Delivery rate**: 85%+
- **Failure rate**: < 5%
- **Linked users growing**: week-over-week increase
- **Daily volume consistent**: no sudden drops (could indicate opt-outs or disconnects)

### Warning Signs

| Signal | What It Means | Action |
|---|---|---|
| Failure rate spikes to 15%+ | Evolution API may be disconnected | Check instance health, restart if needed |
| Daily volume drops to 0 | Queue not processing or scheduler down | Check crontab, verify `schedule:run` is executing |
| Delivery rate below 70% | Many messages not reaching recipients | Check phone number quality, verify Evolution API connection |
| Pending queue growing | 24-hour windows not being opened | Check if users are still active; verify inbound webhook is working |

---

## Admin Menu Navigation

The dashboard is accessible from the admin sidebar under **WhatsApp → Dashboard**. The sidebar also includes **WhatsApp → Phone** for phone linking.

---

## CSV Export

Future: The dashboard will support exporting filtered data to CSV for offline analysis and compliance reporting.
