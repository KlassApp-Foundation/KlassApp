---
name: toshi-architecture-boundaries
user-invocable: false
description: |
  Toshi (AgentToshi) architecture boundaries — the 15-step onboarding state machine,
  assistant mode isolation, shared keyword router, and WhatsApp exclusion zone.
  Prevents accidental changes to fragile areas.
  Triggers on: 'Toshi', 'AgentToshi', 'onboarding', 'step machine', 'toshi boundaries',
  'safe to modify', 'assistant mode', 'keyword router'.
---

# Toshi Architecture & Boundaries

> **HIGHEST PRIORITY skill for Toshi work.** Read this before modifying ANY Toshi file.
> The onboarding state machine is fragile. Changes to steps 0-14 without explicit request
> will break user onboarding flows.

---

## 1. The 15-Step Onboarding State Machine (STEPS 0-14)

**⛔ OFF LIMITS unless explicitly asked to modify onboarding.**

### Step Map

| Step | Name | Handler Method | Purpose | Mandatory? |
|---|---|---|---|---|
| 0 | `plan_selection` | `handlePlanSelection()` | Choose subscription plan | ✅ Yes |
| 1 | `school_info` | `handleSchoolInfo()` | School name, type, level, gender, contact | ✅ Yes |
| 2 | `admin_account` | `handleAdminAccount()` | Create school admin user | ✅ Yes |
| 3 | `co_admin_invite` | `handleCoAdminInvite()` | Optional co-admin invitation | ❌ No |
| 4 | `academic_year` | `handleAcademicYear()` | Set academic year | ✅ Yes |
| 5 | `standards` | `handleStandards()` | Add classes/grades | ✅ Yes |
| 6 | `subjects` | `handleSubjects()` | Add subjects per class | ✅ Yes |
| 7 | `teachers` | `handleTeachers()` | Add teachers (paste or form) | ❌ No |
| 8 | `teacher_links` | `handleTeacherLinks()` | Assign teachers to classes | ❌ No |
| 9 | `students` | `handleStudents()` | Add students (paste or form) | ❌ No |
| 10 | `terms` | `handleTerms()` | Create academic terms | ✅ Yes |
| 11 | `fees` | `handleFees()` | Add fee categories | ❌ No |
| 12 | `exams` | `handleExams()` | Set up exams | ❌ No |
| 13 | `whatsapp_verify` | `handleWhatsAppVerify()` | Verify WhatsApp number | ❌ No |
| 14 | `review` | `handleReview()` | Review & confirm all data | ✅ Yes |

### Special Steps

| Step | Name | Purpose |
|---|---|---|
| 99 | `assistant` | Post-onboarding Q&A mode (safe zone) |

### Key Files (DO NOT MODIFY without explicit request)

| File | Role |
|---|---|
| `app/Livewire/AgentToshi.php` | Main Livewire component — 4000+ lines, contains ALL step handlers |
| `app/Livewire/AgentToshi.php` → `$steps` array | Step ordering — changing order breaks resume/draft logic |
| `app/Livewire/AgentToshi.php` → `$mandatorySteps` array | Required steps — affects skip behavior |
| `app/Livewire/AgentToshi.php` → `send()` | Main dispatcher — routes to step handlers, assistant, or action flow |
| `app/Livewire/AgentToshi.php` → `detectMissingSteps()` | Post-onboarding gap detection — jumps to first incomplete step |
| `app/Models/OnboardingSession.php` | Draft persistence — stores step/substep/data for resume |
| `app/Helpers/OnboardingHelper.php` | Step label definitions and missing-step detection |
| `app/Services/ToshiActionService.php` | Action execution — DB writes for all onboarding operations |
| `resources/views/livewire/agent-toshi.blade.php` | UI template — step rendering, buttons, chat area |

### State Persistence Flow

```
User input → send() → match $steps[$this->step] → call handler → advance $this->step → saveDraft()
```

---

## 2. Assistant Mode (SAFE-TO-MODIFY ISOLATED ZONE)

**✅ This is the safe zone.** After onboarding completes (step 99), Toshi operates in assistant mode.

### Entry Points

```php
// In send() dispatcher:
if ($this->mode === 'assistant') {
    $this->handleAssistantQuery($text);
    return;
}
```

### How Mode Switches Happen

1. **Natural completion:** All 15 steps done → `detectMissingSteps()` finds nothing → sets `$this->step = 99`
2. **Keyword route:** User types "help", "reports", "students" etc. → `tryKeywordRoute()` matches → sets `$this->mode = 'assistant'`, `$this->step = 99`
3. **Heuristic detection:** Input looks like a question (starts with what/how/why/etc.) → sets `$this->mode = 'assistant'`, `$this->step = 99`

### Assistant Flow

```
handleAssistantQuery() → ToshiAssistantAgent (if LarAgent enabled)
                       → ToshiAssistantService (legacy fallback)
```

### What's Safe to Modify

| Area | Files | Notes |
|---|---|---|
| Agent instructions | `ToshiAssistantAgent::instructions()` | System prompt template |
| Tool definitions | `#[Tool()]` methods on `ToshiAssistantAgent` | Add/remove AI tools |
| Response formatting | `ToshiAssistantService` | Legacy response builders |
| Assistant UI | Blade template (chat area only) | Not the onboarding step UI |
| Caching logic | `checkResponseCache()`, `maybeCacheResponse()` | Cache keys, TTL |
| Budget logic | `consumeDailyBudget()` | Limits, counters |
| Provider config | `config/laragent.php` | LLM settings |

---

## 3. Shared Keyword Router (⚠️ FLAG BEFORE MODIFYING)

**⚠️ ASK FIRST before modifying.** This affects BOTH onboarding and assistant mode.

**Source:** `AgentToshi::tryKeywordRoute()` (line ~1728)

```php
private function tryKeywordRoute(string $lower, string $original): bool
{
    // Greetings
    if (in_array($lower, ['hi', 'hello', 'hey', 'help', 'what can you do'])) {
        $this->botSay("Hi! I'm Toshi. I can help with reports, stats, students, fees, and attendance.");
        return true;
    }

    // Reports/stats
    if (in_array($lower, ['reports', 'dashboard', 'stats', 'summary'])) {
        // ... returns school summary
        return true;
    }

    // Students
    if (in_array($lower, ['students', 'student list', 'learners'])) {
        // ... returns student counts by class
        return true;
    }

    // Attendance
    if (in_array($lower, ['attendance', 'attendan'])) {
        // ... returns attendance info
        return true;
    }

    // ... more keyword routes
}
```

### Why It's Shared

- Called during **onboarding** (in `handleActionFlow`) to detect if user wants to switch to assistant
- Called during **setup mode** (in `send()`) before step handlers to detect assistant intent
- Modifying keywords affects the heuristic that decides "is this a setup answer or a question?"

### If You Need to Add Keywords

1. Add the keyword array entry in `tryKeywordRoute()`
2. Test that it doesn't conflict with valid setup answers (e.g., "yes", "skip", phone numbers, emails)
3. Verify it works in both onboarding and assistant contexts

---

## 4. WhatsApp Code (SEPARATE, EXCLUDED ZONE)

**🚫 EXCLUDED from this skill's scope.** WhatsApp has its own architecture:

| Component | Location |
|---|---|
| Inbound webhook handler | `app/Http/Controllers/Api/WhatsAppController.php` |
| Outbound service | `app/Services/OutboundWhatsAppService.php` |
| Meta Cloud API client | `app/Services/WhatsAppBusinessService.php` |
| School Pay webhook | `app/Http/Controllers/Api/SchoolPayWebhookController.php` |
| Models | `WhatsAppUser`, `WhatsAppPendingNotification`, `MessageDeliveryLog` |

WhatsApp code is **not** part of the Toshi onboarding or assistant flow. It communicates through:
- The `whatsapp_verify` onboarding step (step 13) — which only verifies the user's number
- `OnboardingHelper::getMissingSteps()` — checks if user has a verified WhatsApp number

---

## 5. Safe / Ask First / Do Not Touch Reference

| Zone | Status | Files | Notes |
|---|---|---|---|
| **Assistant mode tools** | ✅ Safe | `ToshiAssistantAgent.php` — `#[Tool]` methods | Add/remove tools freely |
| **Assistant instructions** | ✅ Safe | `ToshiAssistantAgent::instructions()` | System prompt changes |
| **LarAgent config** | ✅ Safe | `config/laragent.php` | Provider settings, limits |
| **Assistant service (legacy)** | ✅ Safe | `ToshiAssistantService.php` | Response formatting |
| **Assistant UI (chat area)** | ✅ Safe | `agent-toshi.blade.php` — chat section | Not step UI |
| **Caching/budget logic** | ✅ Safe | `ToshiAssistantAgent` cache methods | TTL, counters |
| **Keyword router** | ⚠️ Ask first | `AgentToshi::tryKeywordRoute()` | Shared by onboarding + assistant |
| **Mode detection heuristic** | ⚠️ Ask first | `AgentToshi::send()` — question detection | Affects flow routing |
| **Step handlers (0-14)** | 🚫 Do not touch | `AgentToshi.php` — `handleXxx()` methods | Breaks onboarding |
| **Step array** | 🚫 Do not touch | `AgentToshi::$steps` | Changes order, breaks resume |
| **Mandatory steps** | 🚫 Do not touch | `AgentToshi::$mandatorySteps` | Affects skip behavior |
| **Draft save/load** | 🚫 Do not touch | `AgentToshi::saveDraft()`, `loadDraft()` | Breaks session persistence |
| **detectMissingSteps** | 🚫 Do not touch | `AgentToshi::detectMissingSteps()` | Breaks post-onboarding flow |
| **OnboardingSession model** | 🚫 Do not touch | `app/Models/OnboardingSession.php` | Schema changes need migration |
| **OnboardingHelper** | 🚫 Do not touch | `app/Helpers/OnboardingHelper.php` | Step labels, missing detection |
| **ToshiActionService** | 🚫 Do not touch | `app/Services/ToshiActionService.php` | DB write operations |
| **WhatsApp code** | 🚫 Excluded | Controllers, Services, Models | Separate architecture |

---

## 6. Action Flow (Multi-Step Actions Within Assistant Mode)

Separate from the 15-step onboarding, the assistant mode has its own **action flow** for multi-step operations like "add a student" or "record marks":

```php
// In send():
if ($this->actionStep) {
    $this->handleActionFlow($text);
    return;
}
```

- `$this->actionStep` tracks the current step within an action (e.g., collecting student name → class → parent)
- `$this->actionData` accumulates data across action sub-steps
- This is **isolated** from the onboarding `$this->step` — different state machine
- **Safe to modify** action handlers, but verify they don't conflict with onboarding step names
