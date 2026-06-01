# AI Agent Layer

## Why

KlassApp's existing system already handles WhatsApp communication well:

- **Parents** get deterministic role-based menus (grades, fees, attendance). The List Message pattern is fast, works on any phone, needs no AI.
- **Staff** get auto-generated reports, structured dashboards, and the same WhatsApp menus. Reports generate themselves.

The AI Agent layer exists for **what the system doesn't already do**:

- A teacher uploads a scanned marksheet → AI extracts names and scores → populates the database
- A coach sends "Amope won gold in 100m dash" via WhatsApp → AI adds it to the report's achievements section
- A head teacher asks "Which 5 students declined most in math this term?" → AI analyzes and responds
- A voice message: "Ivan was involved in a fight on March 15, suspended for 3 days" → transcribed, entered into discipline records, flagged for admin review

This is a **premium service**. Schools on premium plans get AI-powered ingestion, analysis, enrichment, and alerts.

---

## Architecture

```
                    ┌──────────────────────────────────┐
                    │   Staff & Admin Users              │
                    │  ┌─────────┐  ┌────────────────┐  │
                    │  │   Web   │  │  WhatsApp       │  │
                    │  │  (primary) │  │  (quick access) │  │
                    │  └────┬────┘  └───────┬────────┘  │
                    └───────┼───────────────┼────────────┘
                            │               │
                    ┌───────▼───────────────▼────────────┐
                    │        KlassApp API / Webhook       │
                    └───────┬────────────────────────────┘
                            │
                    ┌───────▼────────────────────────────┐
                    │    AI Gateway Service                │
                    │  ──────────────────────              │
                    │  - Authenticates premium tier        │
                    │  - Routes to correct agent           │
                    │  - Tracks token usage for billing     │
                    └───────┬────────────────────────────┘
                            │
          ┌─────────────────┼──────────────────┐
          │                 │                   │
  ┌───────▼───────┐ ┌──────▼────────┐ ┌───────▼──────────┐
  │  Analysis      │ │  Ingestion    │ │  Enrichment      │
  │  Agent         │ │  Agent        │ │  Agent           │
  │ ──────────     │ │ ─────────    │ │ ───────────      │
  │ Text queries   │ │ Vision (GPT-4o) │ Report additions  │
  │ Performance    │ │ Marksheet     │ │ Discipline notes  │
  │ Trends         │ │ parsing       │ │ Achievements     │
  │ Anomalies      │ │ Data entry    │ │ Comments         │
  └───────┬────────┘ └──────┬────────┘ └───────┬──────────┘
          │                 │                   │
          └─────────────────┼───────────────────┘
                            │
                    ┌───────▼────────────────────────────┐
                    │    Service Layer (unchanged)         │
                    │  Exam, Marks, Attendance, Student,   │
                    │  Class, DisciplineRecord, Reports    │
                    └─────────────────────────────────────┘
```

### Core Principle: Web Primary, WhatsApp Secondary

| Aspect | Web | WhatsApp |
|---|---|---|
| **Marksheet upload** | Drag-and-drop file, preview extracted data before confirming | Upload image → AI processes → "Done. 38 marks entered. 2 names unmatched — check web." |
| **Analysis** | Charts, tables, exportable reports | Text summary ("P.5A math scores dropped 12% this term") |
| **Report enrichment** | Form with fields, history | Quick messages ("Amope won gold") trigger auto-fill |
| **Daily use** | Heavy operations | Quick queries, notifications, confirmations |

---

## Agent Types

### 1. Ingestion Agent

**Purpose**: Extract structured data from unstructured inputs (scanned/excel marksheets, report cards, paper records).

**Model**: GPT-4o (vision) for scanned documents. GPT-4o-mini for structured Excel files.

**Capabilities**:

| Input | Output | Example |
|---|---|---|
| Scanned marksheet PDF/photo | Student names + subject scores matched to database | Upload photo → 38 students' marks entered, 2 unmapped flagged |
| Excel marksheet (.xlsx/.csv) | Same, without vision needed | Drag-and-drop file → data extracted and confirmed |
| Voice message (discipline report) | Structured discipline record | "Ivan fought on March 15" → DisciplineRecord created, admin alerted |
| Text message (achievement) | Achievement attached to student record | "Amope won gold at districts" → added to report |

**Marksheet flow**:
```
Staff uploads marksheet (web) or sends photo (WhatsApp)
        │
        ▼
Ingestion Agent receives file
        │
        ├── PDF/image → GPT-4o Vision extracts text
        │   └── Row-by-row: student name + subject + score
        │
        ├── Excel/CSV → Parse directly, no LLM needed
        │
        ├── Entity resolution: match names to database students
        │   └── Fuzzy matching on name + class
        │   └── Unmatched names flagged for manual review
        │
        ├── Validate scores (0-100 range, no outliers)
        │
        ├── Write to Exam/Marks tables
        │
        └── Notify staff: "38 marks entered for P.5A. 
            2 names unmatched: 'Akello' (maybe Akello Sarah?), 
            'Ochieng J' (maybe Ochieng John?). Please verify."
```

**Cost**: A scanned marksheet (~1 page) = ~5K vision tokens + ~2K text tokens ≈ $0.03 at GPT-4o. A school with 10 classes × 4 terms = 40 per year = **$1.20/year/school**.

---

### 2. Analysis Agent

**Purpose**: Answer natural language questions about school data. Spot trends and anomalies.

**Model**: GPT-4o-mini (text only, function calling).

**Capabilities**:

| Query | Response |
|---|---|
| "Which 5 students declined most in math this term?" | Ranked list with before/after scores, % change |
| "Show me attendance trends for S.1A this month" | Week-by-week breakdown with notable absences |
| "Compare P.5 vs P.6 end-of-term performance" | Subject-by-subject comparison with averages |
| "Which students have 3+ discipline incidents?" | List with incident summaries |
| "Fee collection dropped — who's behind?" | Table of defaulters by class |
| "Summarize P.5B teacher's end-of-term comments" | Aggregated, flag generic/empty ones |

**Pattern**:
```
1. LLM receives query + school context
2. Decides which function(s) to call:
   - query_grades(filters)
   - query_attendance(filters)  
   - query_discipline(filters)
   - query_fees(filters)
   - compare_periods(metric, class, term_a, term_b)
3. Executes function against database
4. LLM formats response from results
```

**Functions are SQL generators**, not hardcoded queries — the LLM describes the data shape it needs, and a thin layer translates that to safe, parameterized SQL. Output is constrained to prevent injection.

---

### 3. Enrichment Agent

**Purpose**: Add unstructured narrative data to existing structured records — discipline notes, achievements, teacher comments, exceptions.

**Model**: GPT-4o-mini.

**Capabilities**:

| Trigger | Action |
|---|---|
| Teacher WhatsApp: "Amope won gold in 100m dash at district games" | Achievement record created → appears on report card |
| Teacher WhatsApp: "Ivan suspended 3 days for fighting" | Discipline record created → admin alerted if 3+ incidents |
| Head teacher: "Add 'Excellent improvement' to Nakato's report" | Teacher comment appended to student's term report |
| Voice message: "P.6A was caught cheating during midterms" | Discipline record for class → flag for review |

**Report enrichment flow**:
```
Existing reports are auto-generated by the system (no AI needed).
The Enrichment Agent only touches edge cases:
  - A unique achievement (sports, community service)
  - A discipline incident that warrants a report note
  - An exceptional teacher comment

The agent appends to the relevant section of the existing report,
it does not regenerate the entire report.
```

---

### 4. Alert Engine (Phase 4)

**Purpose**: Proactive monitoring that pushes notifications to staff via WhatsApp/web.

**Not a chatbot** — a scheduled cron that evaluates metrics and decides what to flag.

| Alert | Trigger |
|---|---|
| Attendance crash | Class attendance drops below 70% for 3 consecutive days |
| Performance cliff | 5+ students drop >20% in a subject mid-term |
| Discipline threshold | A student reaches 3 discipline incidents in a term |
| Fee collection lag | Class falls behind 50%+ on fee collection vs last term |
| Marksheet pending | A term exam has no marks entered 2 weeks after end-of-term |

**Flow**:
```
Daily cron → Evaluate metrics per school
          → LLM judges: "Is this worth alerting?"
          → If yes → draft message → push to WhatsApp/web
          → Admin can reply "stop alerts for this" (per-school setting)
```

---

## Voice Pipeline

### Why

Voice notes are the dominant informal communication channel in Ugandan schools. Teachers voice-note each other; parents voice-note teachers. A WhatsApp voice message to the KlassApp agent should be treated the same as text.

### Flow

```
Staff sends WhatsApp voice message (1-5 min)
        │
        ▼
Evolution API delivers voice file (media URL, .ogg format)
        │
        ▼
Transcription Service
        ├── OpenAI Whisper API ($0.006/min)
        │   └── Converts speech to text
        │   └── Detects language (Luganda, English, Runyankole)
        │
        └── Fast model (faster-whisper via WhisperX for 
            latency-sensitive paths) — optional optimization
        │
        ▼
Text sent to AI Gateway (same pipeline as text messages)
        │
        ▼
Response sent back as text (WhatsApp text message, not voice)
```

### Cost

Whisper API is $0.006 per minute of audio. A typical voice note is 30-60 seconds ($0.003-$0.006 per message). At 500 voice messages/month/school ≈ **$1.50-$3.00/month**.

### Language Support

Whisper handles Ugandan English and mixed Luganda/English code-switching well. For pure Luganda/Runyankole responses, we'd need a translation step (translate transcribed text → English → process → translate back → respond). Phase 2 consideration.

---

## Premium Gating

All AI features are premium-tier only. Non-premium schools get the existing system unchanged.

```
Feature Matrix:

                    Basic Plan    Premium (this layer)
                    ──────────    ────────────────────
Marksheet upload    Manual entry  AI ingestion (vision)
Performance queries  Dashboard     Natural language AI
Report enrichment   None          AI append (achievements, discipline)
Voice support       None          Transcribe + process
Alerts              None          Proactive anomaly detection
```

**Enforcement**: The AI Gateway checks school subscription before routing to any agent. Non-premium queries are responded to with: "This is a premium feature. Contact sales to upgrade."

**Pricing model options** (to decide):
- Per-school monthly fee (flat rate)
- Per-marksheet fee (usage-based)
- Token-based (per-message/per-analysis)

---

## Technical Implementation

### Stack

| Component | Technology |
|---|---|
| LLM (text) | GPT-4o-mini (default), GPT-4o (complex analysis) |
| LLM (vision) | GPT-4o (marksheet OCR, image understanding) |
| Voice transcription | OpenAI Whisper API |
| LLM SDK | OpenAI PHP SDK (`openai-php/client`) |
| Web framework | Laravel (existing) — Filament admin panel for web UI |
| Queue | Laravel Horizon (for async ingestion, report gen, alerts) |
| Vector store | pgvector (for potential RAG, though not needed in Phase 1) |
| Storage | Laravel filesystem — S3 for uploaded marksheets/voice files |

### Web UI (Filament)

```
/dashboard/ai
├── /marksheet          # Upload, preview, confirm ingestion
│   ├── Upload form (drag-and-drop, PDF/Excel/image)
│   ├── Preview table (extracted names, scores — editable)
│   ├── Unmatched names section
│   └── Confirm/Reject
│
├── /analysis           # Query interface
│   ├── Chat-like text input
│   ├── History of past queries and responses
│   └── Export results
│
├── /enrichment         # Report additions
│   ├── Recent additions feed
│   ├── By student lookup
│   └── Undo action
│
└── /alerts             # Configure
    ├── Alert types (on/off per type)
    ├── Thresholds (customize)
    └── Recent alert log
```

### WhatsApp Integration

Staff message the same KlassApp WhatsApp number they already use. The `AiAgentGateway` classifies inbound messages:

```
Inbound webhook
    │
    ├── From parent → existing menu handler (unchanged)
    │
    └── From staff → AI Gateway
        │
        ├── Premium check → reject if not premium
        │
        ├── Voice note → Whisper → text
        │
        ├── Text → classify intent
        │   ├── Quick query → Analysis Agent → respond via WhatsApp
        │   ├── Marksheet photo → Ingestion Agent → "Processing..." + notify when done
        │   ├── Enrichment (achievement/discipline mention) → Enrichment Agent → confirm
        │   └── Unclear → "I can help with: marksheet upload, performance analysis, 
        │              report additions. What would you like?"
```

**WhatsApp responses are terse** (asynchronous heavy work runs in the background). The agent returns: "Processing marksheet — 38 students, 2 names need review. Check the dashboard." The staff member opens the web app to verify and confirm.

---

## File Map

New files in `app/Services/AiAgent/`:

```
app/Services/
├── AiAgent/
│   ├── AiAgentService.php           # Main orchestrator
│   ├── AiAgentGateway.php           # Intent router (webhook classifier)
│   ├── AiAgentPremiumMiddleware.php # Subscription gate
│   │
│   ├── Agents/
│   │   ├── IngestionAgent.php       # Marksheet processing
│   │   ├── AnalysisAgent.php        # Query + response
│   │   └── EnrichmentAgent.php      # Discipline/achievement additions
│   │
│   ├── Functions/
│   │   ├── QueryGrades.php
│   │   ├── QueryAttendance.php
│   │   ├── QueryDiscipline.php
│   │   ├── QueryFees.php
│   │   ├── InsertMarks.php
│   │   ├── InsertAchievement.php
│   │   ├── InsertDisciplineRecord.php
│   │   └── ComparePeriods.php
│   │
│   ├── Ingestion/
│   │   ├── MarksheetParser.php       # Vision LLM → structured data
│   │   ├── EntityResolver.php         # Name fuzzy matching → DB students
│   │   └── MarksheetValidator.php    # Score range, duplicates
│   │
│   ├── Voice/
│   │   ├── VoiceTranscriber.php      # Whisper API wrapper
│   │   └── VoiceFileDownloader.php   # Evolution API media fetch
│   │
│   ├── Analysis/
│   │   ├── SqlGenerator.php          # Safe SQL from LLM intent
│   │   └── TrendDetector.php         # Compare periods, flag changes
│   │
│   └── Alerts/
│       ├── AlertEngine.php           # Cron-based evaluation
│       └── AlertDispatcher.php       # Push to WhatsApp/web
│
├── AiAgentAlert.php                  # Alert model (table: ai_agent_alerts)
├── AiAgentConversation.php           # Conversation history model
└── AiAgentUsage.php                  # Token usage tracking model
```

---

## Cost Model

### Per-Interaction Costs

| Operation | Model | Tokens | Cost |
|---|---|---|---|
| Text query (analysis) | GPT-4o-mini | ~800 in / ~200 out | ~$0.0003 |
| Marksheet (vision, 1 page) | GPT-4o | ~5K vision / ~2K text | ~$0.03 |
| Voice transcription (1 min) | Whisper | — | ~$0.006 |
| Report enrichment | GPT-4o-mini | ~500 in / ~100 out | ~$0.0001 |
| Alert evaluation (batch) | GPT-4o-mini | ~300 / school | ~$0.00005/school |

### Monthly Per-School Estimates

| School type | Marksheets | Queries | Voice msgs | Enrichment | Monthly cost |
|---|---|---|---|---|---|
| Small (10 classes) | 10 | 100 | 50 | 30 | ~$1.00 |
| Medium (30 classes) | 30 | 300 | 150 | 90 | ~$2.50 |
| Large (60 classes) | 60 | 600 | 300 | 180 | ~$5.00 |

At these costs, premium pricing of **$50-200/month per school** has 90%+ margin.

---

## Roadmap

### Phase 1 — Foundation (Web + Text)

**Effort**: 7-10 days

**Delivery**:
- `AiAgentService` + `AiAgentGateway` + premium middleware
- **Ingestion Agent**: Excel marksheet parsing (no vision), structured data entry with name matching
- **Analysis Agent**: Text queries on grades, attendance, discipline
- **Enrichment Agent**: Discipline notes, achievements via WhatsApp text
- **Web UI** (Filament): Marksheet upload (Excel), analysis chat, enrichment feed
- **WhatsApp**: Staff text messages processed (no voice yet)
- Conversation history + token usage tracking

### Phase 2 — Vision + Voice

**Effort**: 5-7 days

**Delivery**:
- **Vision marksheet ingestion**: Scanned PDFs, photos of marksheets (GPT-4o)
- **Voice pipeline**: WhatsApp voice → Whisper → AI processing
- **Web UI**: Voice message history, playback
- **Language support**: Luganda/English transcription

### Phase 3 — Alerts + Proactive

**Effort**: 7-10 days

**Delivery**:
- **Alert Engine**: Scheduled evaluation, anomaly detection
- **WhatsApp alert push**: Proactive notifications to staff admins
- **Web UI**: Alert configuration, thresholds, mute controls
- **Alert history + audit log**

### Phase 4 — Parent Voice Access

**Effort**: 3-5 days

**Delivery**:
- Parents can voice-note the KlassApp number
- AI transcribes → routes to existing menu equivalent
- "My child's grades" → transcribed → grades response
- Low-literacy parents become first-class users
- Optional: premium unlock for parents

---

## Open Questions

1. **Staff identification on WhatsApp** — How do we distinguish a staff message from a parent message on the same number? A user can be both a parent and a staff member. Current model links phone → user → roles. The gateway checks the user's roles to decide routing.

2. **Marksheet format support** — Do we standardize on a template (column order: name, subject1, subject2...) or handle arbitrary layouts? Arbitrary is harder but more practical (schools use different formats).

3. **Should marksheet ingestion write immediately or stage for confirmation?** Staging is safer (preview → confirm) but adds a step. WhatsApp ingestion should confirm automatically and flag edge cases for web review.

4. **Whisper model choice** — OpenAI Whisper API (simple, $0.006/min) vs WhisperX self-hosted (free but needs GPU). At projected volume, API is cheaper than a GPU instance.

5. **Alert thresholds** — Should they be automated (LLM decides what's anomalous) or rule-based (attendance < 70%, fee collection < 50%)? Hybrid is best: rules capture known patterns, LLM catches novel ones.

6. **WhatsApp delivery of alerts** — This requires Meta-approved templates (and their 24-hour window rules). Alerts that don't fit templates may need to be opted-in broadcast messages. Need to evaluate WhatsApp Business API constraints for proactive push.
