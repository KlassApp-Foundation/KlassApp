# Proposal: Toshi MCP Server & Connector Completion

**Author:** KlassApp Team (AI-assisted analysis)
**Date:** 2026-08-28
**Status:** Proposed — awaiting team review
**Companion to:** PR #377 (landing page, brand identity, dashboard proposals)

---

## Executive Summary

Toshi is a real agentic system — orchestrator, 7 role-based agents, 9 skill domains, 50+ tools, safety guards, auditing. But it operates as an **MCP client only** (it calls external tools). To become the "agentic protocol for education" we're positioning KlassApp as, Toshi needs to be exposed as an **MCP server** — so any tool in the ecosystem (Claude Desktop, n8n, Cursor, custom agents) can call KlassApp's education tools directly.

Additionally, the multi-channel connector story (WhatsApp, Drive, Slack, Email, SMS) is incomplete. WhatsApp is 80% done. The rest are stubs.

This proposal covers:
1. Completing existing connectors (WhatsApp, Email, SMS)
2. Building missing connectors (Google Drive, Slack)
3. Exposing Toshi as an MCP server
4. Authentication, transport, and documentation

---

## 1. Current State Assessment

### 1.1 Toshi Agent System — What Exists

```
User Query
    ↓
ToshiOrchestrator (Laravel AI SDK Agent)
    ↓ classifies & routes
RouteTo*SkillTool (9 routing tools)
    ↓
Skill Agents (Academic, Fee, Grading, Reporting, Comms, etc.)
    ↓
Tools (50+ action tools)
    ↓
Database (Eloquent models)
```

**Architecture quality: Strong.** This is comparable to CrewAI/LangChain patterns.

### 1.2 Connector Status

| Connector | Transport | Inbound | Toshi Integration | Overall |
|-----------|-----------|---------|-------------------|---------|
| **WhatsApp** | ✅ Meta Cloud API | ✅ Webhook | ✅ ToshiChannelService | **80%** |
| **Email** | ✅ SMTP/SES/Postmark | ❌ No inbound parsing | ⚠️ Partial | **60%** |
| **SMS** | ✅ Twilio | ❌ No inbound webhook | ⚠️ Partial | **50%** |
| **Google Drive** | ❌ Not built | ❌ | ❌ | **0%** |
| **Slack** | ⚠️ Mock only | ❌ | ❌ | **10%** |
| **Calendar** | ❌ Not built | ❌ | ❌ | **0%** |
| **Firebase Push** | ✅ FCM | N/A | ✅ | **90%** |

### 1.3 MCP Status

| Capability | Status |
|------------|--------|
| **MCP Client** (Toshi calls external tools) | ✅ Working (AuditingMcpClient) |
| **MCP Server** (external tools call Toshi) | ❌ Not built |
| **Slack MCP** | ⚠️ Mock server only (SpikeSlackMockServer) |
| **MCP Auth** | ❌ Not built |
| **MCP Transport** | ❌ Not configured |

---

## 2. Connector Completion

### 2.1 WhatsApp — Complete the Last 20%

**What's done:**
- `WhatsAppBusinessService` — Meta Cloud API transport ✅
- `WhatsAppToshiChannelService` — routes inbound to Toshi ✅
- `WhatsAppConfirmationBridge` — write confirmations ✅
- `WhatsAppHumanEscalationService` — fallback ✅

**What's needed:**

| Task | Effort | Priority |
|------|--------|----------|
| Verify Meta webhook endpoint routing | 1 day | Critical |
| Add template message support (outside 24hr window) | 2 days | High |
| Add media handling (images, documents, voice) | 3 days | High |
| Add multi-language support (local language detection) | 2 days | Medium |
| Add interactive button/list message parsing | 2 days | Medium |

**New files:**
```
app/Http/Controllers/WhatsAppWebhookController.php (verify existing)
app/Services/WhatsApp/WhatsAppTemplateService.php
app/Services/WhatsApp/WhatsAppMediaHandler.php
app/AiAgents/Tools/WhatsApp/SendTemplateMessageTool.php
```

### 2.2 Email — Add Inbound + Toshi

**What's done:**
- Laravel mail transport ✅
- Mail templates ✅
- `SendMail` model ✅

**What's needed:**

| Task | Effort | Priority |
|------|--------|----------|
| Add inbound email parsing (SendGrid/Mailgun webhook) | 2 days | High |
| Create `EmailToshiService` — route inbound to Toshi | 1 day | High |
| Create `SendEmailTool` for Toshi | 1 day | High |
| Add email-to-Toshi response flow | 2 days | Medium |

**New files:**
```
app/Services/Email/InboundEmailService.php
app/Services/Email/EmailToshiService.php
app/Http/Controllers/InboundEmailWebhookController.php
app/AiAgents/Tools/Comms/SendEmailTool.php
config/inbound-email.php
```

**Config:**
```env
INBOUND_EMAIL_DRIVER=sendgrid|mailgun
INBOUND_EMAIL_WEBHOOK_SECRET=...
```

### 2.3 SMS — Add Inbound + Toshi

**What's done:**
- Twilio credentials ✅
- SMS templates ✅
- `SmsProcess` trait ✅

**What's needed:**

| Task | Effort | Priority |
|------|--------|----------|
| Add Twilio inbound webhook | 1 day | High |
| Create `SmsToshiService` — route inbound to Toshi | 1 day | High |
| Create `SendSmsTool` for Toshi | 1 day | High |
| Add SMS-to-Toshi response flow | 2 days | Medium |

**New files:**
```
app/Services/Sms/InboundSmsService.php
app/Services/Sms/SmsToshiService.php
app/Http/Controllers/TwilioWebhookController.php
app/AiAgents/Tools/Comms/SendSmsTool.php
```

### 2.4 Google Drive — Build from Scratch

**What's needed:**

| Task | Effort | Priority |
|------|--------|----------|
| Google OAuth2 flow (school-level) | 3 days | High |
| `GoogleDriveService` — upload, list, create folders | 2 days | High |
| Report card auto-upload to Drive | 2 days | High |
| Document sync (fee receipts, letters) | 2 days | Medium |
| Toshi tools (upload, list, search) | 2 days | Medium |
| Drive → Toshi (parent asks "find my receipt") | 2 days | Low |

**New files:**
```
app/Services/Google/GoogleDriveService.php
app/Services/Google/GoogleOAuthService.php
app/Http/Controllers/GoogleDriveController.php
app/AiAgents/Tools/Drive/UploadToDriveTool.php
app/AiAgents/Tools/Drive/ListDriveFilesTool.php
app/AiAgents/Tools/Drive/SearchDriveTool.php
config/google-drive.php
```

**Config:**
```env
GOOGLE_DRIVE_CLIENT_ID=...
GOOGLE_DRIVE_CLIENT_SECRET=...
GOOGLE_DRIVE_REDIRECT_URI=...
```

**Dependencies:**
```json
"google/apiclient": "^2.15"
```

### 2.5 Slack — Replace Mock with Real

**What's done:**
- Mock MCP server (SpikeSlackMockServer) ✅
- MCP client plumbing ✅

**What's needed:**

| Task | Effort | Priority |
|------|--------|----------|
| Replace mock with real Slack MCP client | 1 day | High |
| Slack OAuth2 workspace installation | 2 days | High |
| Slack event handler (inbound messages) | 2 days | High |
| Toshi tools (send message, list channels, post notice) | 2 days | Medium |
| Slack → Toshi (teacher asks via Slack) | 2 days | Medium |

**New files:**
```
app/Services/Slack/SlackService.php
app/Services/Slack/SlackOAuthService.php
app/Http/Controllers/SlackWebhookController.php
app/AiAgents/Tools/Slack/SendSlackMessageTool.php
app/AiAgents/Tools/Slack/ListSlackChannelsTool.php
config/slack.php
```

**Config:**
```env
SLACK_BOT_TOKEN=xoxb-...
SLACK_SIGNING_SECRET=...
SLACK_APP_ID=...
```

**MCP integration:**
```php
// config/mcp.php
'clients' => [
    'slack' => [
        'transport' => 'sse',
        'url' => 'https://mcp.slack.com/mcp',
        'token' => env('SLACK_BOT_TOKEN'),
    ],
],
```

---

## 3. Toshi as MCP Server

### 3.1 What This Means

**Current:** Toshi is an MCP client — it can call external tools.
```
KlassApp (Toshi) ──MCP──→ Slack
KlassApp (Toshi) ──MCP──→ (future) Drive
```

**Target:** Toshi is also an MCP server — external tools can call it.
```
Claude Desktop ──MCP──→ KlassApp/Toshi (list students, enter marks, check fees)
n8n ──MCP──→ KlassApp/Toshi (automate report generation)
Cursor ──MCP──→ KlassApp/Toshi (query school data from IDE)
Custom Agent ──MCP──→ KlassApp/Toshi (any education tool)
```

### 3.2 Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    KlassApp                              │
│                                                         │
│  ┌──────────────┐     ┌──────────────────────────────┐  │
│  │  MCP Server  │     │  Toshi Agent System          │  │
│  │              │     │                              │  │
│  │  Tools:      │────→│  Orchestrator                │  │
│  │  - FindStudent│     │  → Skills                    │  │
│  │  - ListClasses│     │  → Tools                     │  │
│  │  - EnterMark │     │  → Database                  │  │
│  │  - RecordPay │     │                              │  │
│  │  - ...       │     │                              │  │
│  └──────┬───────┘     └──────────────────────────────┘  │
│         │                                               │
│  ┌──────┴───────┐                                       │
│  │  Transports  │                                       │
│  │  - stdio     │  (Claude Desktop, Cursor)             │
│  │  - SSE       │  (n8n, web clients)                  │
│  │  - HTTP      │  (simple request/response)            │
│  └──────────────┘                                       │
└─────────────────────────────────────────────────────────┘
```

### 3.3 Implementation

#### Step 1: Create KlassApp MCP Server

```php
<?php
// app/Mcp/Servers/KlassAppServer.php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('KlassApp Education Protocol')]
#[Version('1.0.0')]
#[Instructions(<<<'PROMPT'
KlassApp is an open-source school management system with an AI agent called Toshi.
Use these tools to manage students, teachers, exams, fees, attendance, and more.
All operations are scoped to the authenticated school. Ask for clarification
if the query is ambiguous.
PROMPT)]
class KlassAppServer extends Server
{
    protected array $tools = [
        // Student tools
        \App\AiAgents\Tools\FindStudentTool::class,
        \App\AiAgents\Tools\AddStudentTool::class,
        \App\AiAgents\Tools\GetStudentCountTool::class,

        // Academic tools
        \App\AiAgents\Tools\ListClassesTool::class,
        \App\AiAgents\Tools\ListSectionsTool::class,
        \App\AiAgents\Tools\CreateExamTool::class,
        \App\AiAgents\Tools\EnterMarkTool::class,
        \App\AiAgents\Tools\CreateTermTool::class,
        \App\AiAgents\Tools\CreateSubjectTool::class,
        \App\AiAgents\Tools\CreateStreamTool::class,

        // Fee tools
        \App\AiAgents\Tools\CreateFeeTool::class,
        \App\AiAgents\Tools\RecordPaymentTool::class,
        \App\AiAgents\Tools\GetFeeBalanceTool::class,

        // Attendance tools
        \App\AiAgents\Tools\RecordAttendanceTool::class,
        \App\AiAgents\Tools\RecordBulkAttendanceTool::class,

        // Teacher tools
        \App\AiAgents\Tools\AddTeacherTool::class,
        \App\AiAgents\Tools\AssignTeacherTool::class,
        \App\AiAgents\Tools\ListTeachersTool::class,

        // Communication tools
        \App\AiAgents\Tools\CreateNoticeTool::class,
        \App\AiAgents\Tools\CreateEventTool::class,
        \App\AiAgents\Tools\CreateHolidayTool::class,

        // Homework tools
        \App\AiAgents\Tools\CreateHomeworkTool::class,
        \App\AiAgents\Tools\ApproveHomeworkTool::class,
        \App\AiAgents\Tools\ListHomeworkTool::class,

        // Timetable tools
        \App\AiAgents\Tools\CreateTimetableSlotTool::class,
        \App\AiAgents\Tools\ListTimetableSlotsTool::class,

        // Grading tools
        \App\AiAgents\Tools\SetGradingScaleTool::class,
        \App\AiAgents\Tools\ViewGradingScaleTool::class,
        \App\AiAgents\Tools\SeedDefaultGradingTool::class,

        // Reporting tools
        \App\AiAgents\Tools\GenerateReportTool::class,

        // Admin tools
        \App\AiAgents\Tools\AddCoAdminTool::class,
        \App\AiAgents\Tools\AddParentTool::class,
        \App\AiAgents\Tools\SetCurriculumTool::class,
    ];

    protected array $resources = [];
    protected array $prompts = [];
}
```

#### Step 2: Add Authentication

```php
<?php
// app/Mcp/Auth/McpTokenAuthenticator.php

namespace App\Mcp\Auth;

use App\Models\School;
use App\Models\User;

class McpTokenAuthenticator
{
    /**
     * Authenticate an MCP request using an API token.
     *
     * Tokens are scoped to:
     * - school_id (which school)
     * - user_id (which user — for audit trail)
     * - allowed_tools (optional — restrict to specific tools)
     */
    public function authenticate(string $token): ?McpAuthContext
    {
        $record = \DB::table('mcp_tokens')
            ->where('token', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return null;
        }

        return new McpAuthContext(
            schoolId: $record->school_id,
            userId: $record->user_id,
            allowedTools: $record->allowed_tools ? json_decode($record->allowed_tools, true) : null,
        );
    }
}
```

**Migration:**
```php
Schema::create('mcp_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('school_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('token', 64)->unique();
    $table->json('allowed_tools')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

#### Step 3: Add Transports

**stdio** (for Claude Desktop, Cursor):
```php
// config/mcp.php
'servers' => [
    'klassapp' => [
        'transport' => 'stdio',
        'command' => 'php',
        'args' => ['artisan', 'mcp:serve', 'klassapp'],
    ],
],
```

**SSE** (for n8n, web clients):
```php
// routes/mcp.php
Route::post('/mcp/sse', [McpSseController::class, 'handle']);
Route::get('/mcp/sse', [McpSseController::class, 'stream']);
```

#### Step 4: Tool Schema Enhancement

Each tool needs proper JSON Schema for MCP discovery:

```php
// Example: FindStudentTool
public function schema(JsonSchema $schema): array
{
    return [
        'query' => $schema->string()
            ->description('Student name, ID, or registration number to search for')
            ->required(),
        'class' => $schema->string()
            ->description('Filter by class (e.g., "P.6", "S.3")')
            ->required(false),
    ];
}
```

#### Step 5: MCP Prompts (Optional)

Pre-built prompts for common queries:

```php
// app/Mcp/Prompts/StudentReportPrompt.php
class StudentReportPrompt extends Prompt
{
    public function name(): string { return 'student-report'; }
    public function description(): string { return 'Generate a student academic report'; }

    public function arguments(): array
    {
        return [
            'student_name' => 'Name or ID of the student',
            'term' => 'Academic term (e.g., "Term 1 2026")',
        ];
    }
}
```

---

## 4. Implementation Roadmap

### Phase 1: Complete Existing Connectors (Week 1-2)
- [ ] WhatsApp: Verify webhook, add template messages, media handling
- [ ] Email: Add inbound parsing (SendGrid webhook), Toshi response tool
- [ ] SMS: Add Twilio inbound webhook, Toshi response tool
- [ ] Create unified `CommsSkill` that routes across all channels

### Phase 2: Build Missing Connectors (Week 3-5)
- [ ] Google Drive: OAuth2, file service, report upload, Toshi tools
- [ ] Slack: Replace mock with real MCP client, OAuth, event handler, Toshi tools
- [ ] Calendar: Google Calendar sync for events

### Phase 3: Toshi MCP Server (Week 6-8)
- [ ] Create `KlassAppServer` MCP server class
- [ ] Expose all 50+ tools via MCP
- [ ] Add MCP token authentication
- [ ] Add stdio transport (Claude Desktop, Cursor)
- [ ] Add SSE transport (n8n, web clients)
- [ ] Add HTTP transport (simple request/response)

### Phase 4: Protocol Polish (Week 9-10)
- [ ] MCP tool descriptions (for AI discovery)
- [ ] MCP resource schemas (student data, grade data)
- [ ] MCP prompt templates (common queries)
- [ ] Integration tests with Claude Desktop, n8n
- [ ] MCP documentation (how to connect)
- [ ] Publish to MCP registry

---

## 5. Dependencies

### New Composer Packages
```json
{
    "google/apiclient": "^2.15",
    "laravel/mcp": "^1.0"
}
```

### New Environment Variables
```env
# WhatsApp (existing)
WHATSAPP_BUSINESS_API_TOKEN=...
WHATSAPP_BUSINESS_PHONE_NUMBER_ID=...

# Email Inbound
INBOUND_EMAIL_DRIVER=sendgrid|mailgun
INBOUND_EMAIL_WEBHOOK_SECRET=...

# Twilio Inbound
TWILIO_AUTH_TOKEN=...

# Google Drive
GOOGLE_DRIVE_CLIENT_ID=...
GOOGLE_DRIVE_CLIENT_SECRET=...
GOOGLE_DRIVE_REDIRECT_URI=...

# Slack
SLACK_BOT_TOKEN=xoxb-...
SLACK_SIGNING_SECRET=...
SLACK_APP_ID=...

# MCP Server
MCP_SERVER_ENABLED=true
MCP_SERVER_AUTH_REQUIRED=true
```

### New Database Tables
```sql
CREATE TABLE mcp_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    allowed_tools JSON NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 6. Security Considerations

### 6.1 MCP Authentication
- All MCP requests require a valid token
- Tokens are scoped to school_id (no cross-tenant access)
- Tokens have optional expiry
- Tokens can be restricted to specific tools

### 6.2 Tool Authorization
- MCP tool calls go through the same authorization as Toshi tool calls
- Role-based access (admin tools only for admin tokens)
- Write tools require confirmation (ConfirmsBeforeWrite)
- All calls are audited (AuditingMcpClient)

### 6.3 Rate Limiting
- Per-token rate limits (prevent abuse)
- Per-school daily limits (reuse Toshi budget system)
- Per-tool rate limits (prevent bulk abuse)

### 6.4 Data Isolation
- Every MCP call is scoped to the token's school_id
- No cross-school data access
- Sensitive fields (passwords, tokens) are never exposed via MCP

---

## 7. Success Metrics

| Metric | Target |
|--------|--------|
| MCP tools exposed | 50+ |
| Connectors completed | 6 (WhatsApp, Email, SMS, Drive, Slack, Calendar) |
| MCP transports | 3 (stdio, SSE, HTTP) |
| Claude Desktop integration | Working |
| n8n integration | Working |
| MCP registry published | Yes |
| External agent calls Toshi | First call within 2 weeks of launch |

---

## 8. What This Unlocks

### For Schools
- **"Ask your school anything"** from Claude Desktop, Cursor, or any MCP client
- **Automate reports** via n8n workflows (weekly fee summaries, attendance alerts)
- **Integrate with existing tools** — schools that already use Slack/Drive get native integration

### For Developers
- **Build on KlassApp** — any developer can build tools that call KlassApp's API via MCP
- **Extend Toshi** — community can contribute new tools and connectors
- **Fork and self-host** — open source protocol, not a walled garden

### For KlassApp
- **Protocol positioning** — becomes real, not just marketing
- **Network effects** — every MCP client is a potential distribution channel
- **Community contributions** — MCP tools are easy to write and contribute
- **Competitive moat** — no other school management system has this

---

## 9. Conclusion

Toshi is already an agentic system. The connectors are half-built. The MCP server is the missing piece that turns KlassApp from a school management tool into an education protocol.

**The ask:** Approve this roadmap so we can start building. Phase 1 (complete existing connectors) can begin immediately — it's mostly webhook wiring and tool creation. Phase 3 (MCP server) is the game-changer.

---

## Appendix: File Inventory

### New Files (Estimated)
```
app/Mcp/Servers/KlassAppServer.php
app/Mcp/Auth/McpTokenAuthenticator.php
app/Mcp/Auth/McpAuthContext.php
app/Http/Controllers/McpSseController.php
app/Http/Controllers/InboundEmailWebhookController.php
app/Http/Controllers/TwilioWebhookController.php
app/Http/Controllers/SlackWebhookController.php
app/Http/Controllers/GoogleDriveController.php
app/Services/Email/InboundEmailService.php
app/Services/Email/EmailToshiService.php
app/Services/Sms/InboundSmsService.php
app/Services/Sms/SmsToshiService.php
app/Services/Google/GoogleDriveService.php
app/Services/Google/GoogleOAuthService.php
app/Services/Slack/SlackService.php
app/Services/Slack/SlackOAuthService.php
app/Services/WhatsApp/WhatsAppTemplateService.php
app/Services/WhatsApp/WhatsAppMediaHandler.php
app/AiAgents/Tools/Comms/SendEmailTool.php
app/AiAgents/Tools/Comms/SendSmsTool.php
app/AiAgents/Tools/Drive/UploadToDriveTool.php
app/AiAgents/Tools/Drive/ListDriveFilesTool.php
app/AiAgents/Tools/Drive/SearchDriveTool.php
app/AiAgents/Tools/Slack/SendSlackMessageTool.php
app/AiAgents/Tools/Slack/ListSlackChannelsTool.php
app/AiAgents/Skills/CommsSkill.php
database/migrations/xxxx_create_mcp_tokens_table.php
config/mcp.php
config/google-drive.php
config/slack.php
config/inbound-email.php
```

### Modified Files (Estimated)
```
config/toshi.php (add MCP server config)
routes/web.php (add MCP routes)
composer.json (add dependencies)
.env.example (add new env vars)
```
