# AI Integration Roadmap — School Pay & LIN

## Vision

KlassApp should never depend on external identifiers (School Pay payment codes, Learning Institution Numbers) for core functionality. Instead, AI fills the gap: fuzzy matching, OCR, and natural language understanding resolve parent-to-student links and school identification without requiring parents or schools to know external codes.

---

## 1. Smart Parent-Student Linking (School Pay AI)

### Problem Today
Parents link to their child by entering a 10-digit School Pay payment code. This requires them to:
- Know what a payment code is
- Find it on an SMS or receipt
- Type it correctly

### AI Solution — Three-Layer Resolution

| Layer | Method | Confidence | Fallback To |
|---|---|---|---|
| 1 | **Name + Class match** (implemented) | High when unique | Layer 2 |
| 2 | **Fuzzy name search + school context** | Medium | Layer 3 |
| 3 | **LLM extraction from payment description** | Low→High with training | Manual verification |

**Layer 2 — Fuzzy name matching:**
```
Parent texts: "My son John in P.5"
→ AI searches: students named "John" in Primary 5 classes
→ If multiple: "Which John? (John Ssali, John Okello, John Wasswa)"
→ If one: confirm and link
```

**Layer 3 — School Pay receipt parsing:**
```
Parent texts their School Pay payment SMS/email:
"SchoolPay receipt: GX7K2L. Paid UGX 350,000 for John Ssali, P.5 — Kabale Junior School"

→ AI extracts: student name, amount, school, class
→ Matches against student_academics
→ Creates parent-student link automatically
```

### Implementation

**Phase 1 — Simple fuzzy match (current):**
- `student_academics.name` + `student_academics.class` search
- No AI dependency, works offline
- ~80% success rate for unique names

**Phase 2 — Embedding-based matching:**
- Store student name embeddings (vector search)
- Match parent's text against embeddings
- Handles typos, partial names, nicknames
- Requires: pgvector or similar vector store

**Phase 3 — LLM receipt parsing:**
```php
// Pseudocode
$prompt = "Extract: student name, amount, school, class from this payment text";
$result = $llm->extract($receiptText);
$student = StudentSearch::match($result->studentName, $result->school, $result->class);
```

---

## 2. School Identification (LIN Resolution)

### Problem Today
Schools may not know or have their Learning Institution Number readily available. LIN is a government-issued ID that most school admins don't have memorized.

### AI Solution — Three Approaches

**Approach A — Document OCR:**
```
School admin uploads:
- School license/registration certificate
- Tax document with school name
- MoE inspection report

→ OCR extracts: school name, LIN, registration number
→ Matches against known schools or creates record
```

**Approach B — Domain/Social verification:**
```
School email domain (kabalejunior.sch.ug)
OR
School Facebook page
OR
Known phone number

→ Cross-reference with Ministry of Education database
→ Infer LIN from known school directory
```

**Approach C — Self-attestation + admin approval:**
```
School admin enters their school name and location
→ AI searches for LIN in public databases
→ If found, pre-fills; if not, school enters manually
→ Admin must approve before activation
```

### Implementation

```php
class LinResolver
{
    public function resolve(string $schoolName, ?string $district): ?string
    {
        // 1. Check local cache
        // 2. OCR from uploaded document
        // 3. Web search MoE database
        // 4. Fallback: generate placeholder
    }
}
```

---

## 3. Architecture — AI Service Layer

### Proposed Structure

```
app/Services/AI/
├── AIService.php              # Orchestrator
├── Providers/
│   ├── OpenAIProvider.php     # GPT-4o / GPT-4o-mini
│   ├── AnthropicProvider.php  # Claude (fallback)
│   └── LocalProvider.php      # llama.cpp (offline, self-hosted)
├── Matchers/
│   ├── StudentMatcher.php     # Name + class + school matching
│   ├── SchoolMatcher.php      # LIN + document resolution
│   └── ReceiptParser.php      # School Pay SMS/email parsing
└── config/
    └── ai.php                 # Provider config, rate limits, cost tracking
```

### Key Design Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Provider-first | OpenAI, fallback to Anthropic, offline as last resort | Cost + latency tradeoff: OpenAI is cheapest for short structured extraction |
| Caching | Cache all resolved LIN/student matches for 30 days | 90% of lookups are repeat — parents check multiple times |
| Human-in-loop | Any match < 85% confidence goes to school admin for approval | Prevents wrong parent-student linking |
| Cost model | ~$0.002 per text-based resolution, ~$0.01 per document OCR | At 1000 lookups/month = ~$2-10/month at scale |

---

## 4. Data Model Extensions

### Student Academic Table — Additional Columns

```sql
ALTER TABLE student_academics ADD COLUMN:
  - `ai_name_embedding` vector(384) NULL    -- For vector search
  - `alternate_names` json NULL              -- Nicknames, known misspellings
  - `lin_verified_at` timestamp NULL         -- When LIN was validated
  - `lin_verification_method` varchar(50)    -- 'ocr', 'manual', 'ai_match'
```

### New Tables

```sql
CREATE TABLE ai_match_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  match_type VARCHAR(50),           -- 'student_linking', 'lin_resolution', 'receipt_parsing'
  input_text TEXT,                   -- The raw input (SMS, name, etc.)
  matched_entity_type VARCHAR(50),   -- 'student', 'school'
  matched_entity_id BIGINT,
  confidence DECIMAL(5,2),          -- 0-100
  method VARCHAR(50),               -- 'fuzzy', 'embedding', 'llm', 'ocr'
  resolution VARCHAR(50),           -- 'auto_linked', 'pending_approval', 'failed'
  created_at TIMESTAMP DEFAULT NOW(),
  resolved_at TIMESTAMP NULL,
  resolved_by BIGINT NULL           -- admin user_id if manual
);
```

---

## 5. Demo Flow for Wednesday

For the Wednesday presentation, demonstrate:

1. **Parent texts "Amope Nandawula"** → system finds student by name → confirms → linked in 2 taps
2. **School admin uploads license** → OCR extracts school info → pre-fills school profile
3. **Roadmap slide** — show the three-layer AI resolution pyramid

No School Pay, no LIN required at any step. Everything works with system IDs and natural language.

---

*Last updated: June 29, 2026*
