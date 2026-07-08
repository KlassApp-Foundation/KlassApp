---
name: laragent-patterns
user-invocable: false
description: |
  How KlassApp uses maestroerror/laragent (NOT the official laravel/ai SDK).
  Covers #[Tool] attribute pattern, multi-provider config, complexity routing,
  caching, and testing with LarAgent fakes.
  Triggers on: 'LarAgent', 'laragent', 'maestroerror', 'AI agent', 'ToshiAssistantAgent',
  'Tool attribute', 'LLM integration', 'complexity routing'.
---

# LarAgent Patterns — KlassApp

> **CRITICAL: This project uses `maestroerror/laragent`, NOT `laravel/ai`.**
> The official `laravel/ai` SDK requires Laravel ^12.0|^13.0. This project runs Laravel 10.50.2.
> The two packages have different APIs, capabilities, and requirements.
> **Never suggest or write code using `laravel/ai` patterns.**

---

## 1. Package Identity

| Field | Value |
|---|---|
| **Package** | `maestroerror/laragent` |
| **Namespace** | `LarAgent\` |
| **Base class** | `LarAgent\Agent` |
| **Tool attribute** | `#[Tool('description')]` |
| **Supported Laravel** | 10.x+ |
| **LLM provider** | OpenAI-compatible (Nvidia NIM via env config) |

---

## 2. Agent Class Structure

**Source:** `app/AiAgents/ToshiAssistantAgent.php`

```php
<?php

namespace App\AiAgents;

use LarAgent\Agent;
use LarAgent\Tools\Tool;

class ToshiAssistantAgent extends Agent
{
    protected $model = 'meta/llama-3.1-8b-instruct';
    protected $history = 'in_memory';
    protected $provider = 'default';
    protected $tools = [];
    protected bool $laragentEnabled = false;

    public function __construct()
    {
        parent::__construct('toshi-assistant');
        $this->laragentEnabled = config('toshi.laragent_enabled', false);
    }

    public function instructions(): string
    {
        return config('toshi.system_prompt_template', "You are Toshi, the AI assistant for KlassApp.");
    }
}
```

### Key Properties

| Property | Purpose |
|---|---|
| `$model` | Default LLM model identifier |
| `$history` | Chat history storage (`'in_memory'` = transient per-request) |
| `$provider` | Which provider from `config/laragent.php` to use |
| `$tools` | Array of tool definitions (populated via `#[Tool]` methods) |

---

## 3. #[Tool] Attribute Pattern

Methods decorated with `#[Tool('description')]` become callable tools for the LLM:

```php
#[Tool('Add a student to the school. Requires: name, class. Optional: stream, parent name, parent phone.')]
public function toolAddStudent(string $name, string $class, ?string $stream = null, ?string $parentName = null, ?string $parentPhone = null): array
{
    // Implementation calls ToshiActionService
    return ToshiActionService::addStudent($this->user, [
        'name' => $name,
        'class' => $class,
        'stream' => $stream,
        // ...
    ]);
}

#[Tool('List all classes in the school with student counts')]
public function toolListClasses(): array
{
    return ToshiActionService::listClasses($this->user);
}
```

### Tool Method Rules

- **Return type:** Always `array` (matches the service return contract: `['success' => bool, 'message' => string, ...]`)
- **Parameter types:** Use typed parameters with `?string` for optional fields
- **Description:** Single sentence in the `#[Tool()]` attribute — this is what the LLM sees
- **Authorization:** Tools receive `$this->user` from the agent — always pass it to service methods for role checks
- **No direct DB access:** Tools delegate to `ToshiActionService`, never query models directly

### Current Tools (18 total)

| Tool | Description |
|---|---|
| `toolAddStudent` | Add a student to the school |
| `toolListClasses` | List all classes with student counts |
| `toolListTeachers` | List teachers with assigned classes |
| `toolListSections` | List class sections |
| `toolAddTeacher` | Add a teacher |
| `toolCreateSubject` | Create a subject |
| `toolAssignTeacher` | Assign teacher to class/subject |
| `toolRecordAttendance` | Record attendance for a student |
| `toolCreateFee` | Create fee category |
| `toolCreateTerm` | Create academic term |
| `toolRecordPayment` | Record fee payment |
| `toolGetFeeBalance` | Get fee balance for a student |
| `toolGenerateReport` | Generate school report |
| `toolCreateExam` | Create an exam |
| `toolAddParent` | Add a parent and link to student |
| `toolEnterMark` | Enter a mark for a student on an exam |
| `toolSetGradingScale` | Set grading scale for a class |
| `toolViewGradingScale` | View grading scale |
| `toolSeedDefaultGrading` | Seed default MoE grading scales |

### Tool Safety Tiers

Every tool added to ToshiAssistantAgent MUST be classified into one of three tiers before being wired up. This is enforced by code review — no exceptions.

**Tier 1 — Additive (safe, no data loss risk)**

Creates new records. Worst case if the LLM misfires: junk data gets created, which is recoverable by deletion through the admin panel.

| Tool | Classification |
|---|---|
| `toolAddStudent` | Tier 1 |
| `toolAddTeacher` | Tier 1 |
| `toolCreateTerm` | Tier 1 |
| `toolCreateFee` | Tier 1 |
| `toolCreateSubject` | Tier 1 |
| `toolAssignTeacher` | Tier 1 |
| `toolRecordPayment` | Tier 1 |
| `toolRecordAttendance` | Tier 1 |
| `toolRecordBulkAttendance` | Tier 1 |
| `toolCreateExam` | Tier 1 |
| `toolAddParent` | Tier 1 |
| `toolEnterMark` | Tier 1 |
| `toolSeedDefaultGrading` | Tier 1 |

**Tier 2 — Destructive (requires explicit user confirmation before executing)**

Modifies or deletes existing data. The LLM MUST NOT execute these in a single turn. It must:
1. State what will happen
2. Ask "Should I proceed?"  
3. Wait for an explicit "Yes" from the user
4. THEN call the tool

| Tool | Classification | Risk |
|---|---|---|
| `toolSetGradingScale` | Tier 2 | Deletes all existing grading rules for the standard |
| `toolAddCoAdmin` | Tier 2 | Promotes a teacher (role change is irreversible via UI) |

**Implementation:** When adding a Tier 2 tool to `handleQuery()`, use this pattern:

```php
'toolDeleteStudent' => function ($args) use ($user, $socketId) {
    // Tier 2: require confirmation first
    $this->requireConfirmation(
        "This will permanently delete student ID {$args['student_id']} and all their records. Are you sure?",
        fn() => ToshiActionService::deleteStudent(auth()->user() ?? $user, $args)
    );
},
```

The `requireConfirmation()` method stores the pending action and sends a confirmation prompt. The next user message that says "yes" executes it.

**Tier 3 — Informational (read-only, no side effects)**

Queries and returns data. Safe to execute without restriction.

| Tool | Classification |
|---|---|
| `toolListClasses` | Tier 3 |
| `toolListSections` | Tier 3 |
| `toolListTeachers` | Tier 3 |
| `toolGenerateReport` | Tier 3 |
| `toolFindStudent` | Tier 3 |
| `toolGetStudentCount` | Tier 3 |
| `toolGetFeeBalance` | Tier 3 |
| `toolViewGradingScale` | Tier 3 |

### Adding a New Tool — Checklist

1. Add the `#[Tool('description')]` method to `ToshiAssistantAgent`
2. Classify the tool as Tier 1, 2, or 3
3. Add the function definition to the `tools` array in `handleQuery()`
4. Add the `match` case in the tool-call handler
5. If Tier 2: wrap in requireConfirmation
6. Add the service method to `ToshiActionService` (or reuse existing)
7. If it creates new data: add the capability to `getRoleCapabilities()` in ToshiActionService
8. Test with a real conversational trigger — not just a direct method call

---

## 4. Multi-Provider Configuration

**Source:** `config/laragent.php`

```php
return [
    'default_driver' => \LarAgent\Drivers\OpenAi\OpenAiCompatible::class,
    'default_chat_history' => \LarAgent\History\InMemoryChatHistory::class,

    'providers' => [
        'default' => [
            'label' => 'nvidia-nim',
            'api_key' => env('TOSHI_LLM_API_KEY'),
            'base_url' => env('TOSHI_LLM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
            'model' => env('TOSHI_LLM_MODEL', 'meta/llama-3.1-8b-instruct'),
            'default_context_window' => 32000,
            'default_max_completion_tokens' => 500,
            'default_temperature' => 0.3,
        ],
    ],

    'toshi' => [
        'model' => env('TOSHI_LLM_MODEL', 'meta/llama-3.1-8b-instruct'),
        'request_timeout' => env('TOSHI_REQUEST_TIMEOUT', 15),
        'max_tokens' => env('TOSHI_MAX_TOKENS', 500),
        'daily_llm_limit' => env('TOSHI_DAILY_LLM_LIMIT', 100),
        'fallback_model' => env('TOSHI_LLM_FALLBACK_MODEL'),
    ],
];
```

### Adding a New Provider

To add a fallback provider (e.g., OpenAI, DeepSeek):

```php
'providers' => [
    'default' => [ /* Nvidia NIM */ ],
    'openai' => [
        'label' => 'openai',
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => 'https://api.openai.com/v1',
        'model' => env('TOSHI_FALLBACK_MODEL', 'gpt-4o-mini'),
        'default_context_window' => 128000,
        'default_max_completion_tokens' => 500,
        'default_temperature' => 0.3,
    ],
],
```

Then change `$this->provider` on the agent class or implement fallback logic.

---

## 5. Complexity Routing Heuristic

**Source:** `ToshiAssistantAgent::classifyComplexity()`

The agent classifies queries into tiers to decide which model to use:

```php
private function classifyComplexity(string $query): string
{
    $lower = strtolower($query);

    // Simple: greetings, basic facts, single-entity queries
    if (preg_match('/^(hi|hello|hey|thanks|thank you|what is|who is|how many)/i', $lower)) {
        return 'simple';
    }

    // Complex: multi-step, analysis, comparisons, "how to" with conditions
    if (preg_match('/(compare|analyze|why|explain|how to|what if|recommend)/i', $lower)) {
        return 'complex';
    }

    return 'medium';
}
```

### Tier Routing

| Tier | Model | Use Case |
|---|---|---|
| `simple` | Cheap model (llama-3.1-8b) | Greetings, basic facts, counts |
| `medium` | Default model | Standard queries, list operations |
| `complex` | Escalated model (if configured) | Analysis, comparisons, recommendations |

---

## 6. Caching Pattern

**Source:** `ToshiAssistantAgent::checkResponseCache()` / `maybeCacheResponse()`

### Cache Check (Before LLM Call)

```php
$cached = $this->checkResponseCache($query, $schoolId);
if ($cached !== null) {
    $this->lastQueryCached = true;
    $this->lastQueryTier = 'cache_hit';
    return $cached;
}
```

- Uses exact match + normalized match (lowercase, trimmed)
- Cached per-school to avoid cross-school data leakage
- Stored in Laravel's Cache facade

### Cache Write (After LLM Response)

```php
$this->maybeCacheResponse($query, $schoolId, $response);
```

- Only caches successful, non-empty responses
- Does not cache error messages or empty responses

---

## 7. Budget Enforcement

```php
if (!$this->consumeDailyBudget($user, $schoolId)) {
    Log::info('LarAgent: daily budget exhausted', ['user_id' => $user->id]);
    return null;
}
```

- Counter stored in cache: `toshi_llm_daily_{user_id}_{date}`
- Limit from config: `toshi.daily_llm_limit` (default: 100)
- Returns `null` when exhausted — caller falls back to legacy path

---

## 8. Testing Approach

**Source:** `tests/Feature/Toshi/ToshiAssistantAgentTest.php`

```php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ToshiAssistantAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => 1]);
        $this->actingAs($this->admin);
    }

    public function it_returns_null_when_laragent_disabled()
    {
        config(['toshi.laragent_enabled' => false]);
        $agent = app(ToshiAssistantAgent::class);
        $result = $agent->handleQuery($this->admin, 1, 'query', []);
        $this->assertNull($result);
    }

    public function tool_add_student_returns_correct_structure()
    {
        $result = ToshiActionService::addStudent(
            $this->admin, ['name' => 'Test Student', 'class' => 'P1']
        );
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }
}
```

### Test Patterns

- **Feature tests** for end-to-end agent behavior
- **Unit tests** for individual service methods (verify return contract structure)
- **Cache isolation:** `Cache::flush()` in setUp to prevent cross-test pollution
- **Config overrides:** `config(['toshi.laragent_enabled' => true/false])` per test
- **Budget tests:** Seed cache counter to simulate exhausted budget

---

## 9. Feature Flag

```php
// .env
TOSHI_LARAGENT_ENABLED=false  # default — falls back to legacy ToshiAssistantService

// When enabled:
TOSHI_LARAGENT_ENABLED=true
TOSHI_LLM_API_KEY=your-key
TOSHI_LLM_BASE_URL=https://integrate.api.nvidia.com/v1
TOSHI_LLM_MODEL=meta/llama-3.1-8b-instruct
```

- When `false`: `handleQuery()` returns `null`, caller uses legacy path
- When `true`: Full LarAgent flow with tools, caching, budget enforcement
- Monitor via `Assistant path:` logs for LarAgent vs legacy distribution
