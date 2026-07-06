---
name: klassapp-conventions
user-invocable: false
description: |
  KlassApp codebase conventions and patterns. Load when working on KlassApp services,
  Livewire components, helpers, or controllers. Covers service return contracts,
  state persistence patterns, validation patterns, and Uganda phone normalization.
  Triggers on: 'KlassApp conventions', 'service pattern', 'return contract',
  'state persistence', 'OnboardingSession', 'Uganda phone', 'validation pattern'.
---

# KlassApp Conventions

> **This skill documents the ACTUAL patterns used in KlassApp.**
> Do not suggest patterns that contradict this document without explicit discussion.

---

## 1. Service Method Return Contract

Every service method in KlassApp returns a standardized array:

```php
public static function result(bool $success, string $message, array $data = []): array
{
    return array_merge(['success' => $success, 'message' => $message], $data);
}
```

**Source:** `app/Services/ToshiActionService.php`

### Contract Shape

```php
[
    'success' => bool,    // true = operation succeeded
    'message' => string,  // human-readable result message
    'data'    => array,   // optional payload (merged at top level)
]
```

### Usage Pattern

```php
// In service methods:
return self::result(true, 'Student added successfully', ['student_id' => $student->id]);
return self::result(false, 'Class not found');

// In callers (Livewire, controllers):
$result = ToshiActionService::addStudent($user, $data);
if ($result['success']) {
    $this->botSay($result['message']);
} else {
    $this->botSay("Error: {$result['message']}");
}
```

### Rules

- **Always** use `ToshiActionService::result()` — never construct the array inline
- The `data` keys are merged at the **top level** of the return array (via `array_merge`)
- Callers check `$result['success']` boolean, not truthiness of the whole array
- This contract is used by both onboarding flow and assistant mode action handlers

---

## 2. State Persistence Patterns

KlassApp uses three tiers of state persistence. Choose the right one for the scope.

### Tier 1: Livewire Component Properties (Transient, Per-Request)

**Use for:** Data that only needs to survive within a single Livewire component's lifecycle.

```php
// app/Livewire/AgentToshi.php
public $step = 0;
public $schoolName = '';
public $standards = [];
public $input = '';
public $messages = [];
```

- Stored in Livewire's encrypted payload sent to the browser
- Automatically serialized/deserialized between requests
- **Do not** store sensitive data (passwords, tokens) here — they're in the DOM

### Tier 2: OnboardingSession Model (Persistent, Cross-Session)

**Use for:** Onboarding state that must survive page reloads, browser closes, and resume later.

```php
// app/Models/OnboardingSession.php
class OnboardingSession extends Model
{
    protected $fillable = ['user_id', 'school_id', 'step', 'substep', 'data', 'status'];
    protected $casts = ['data' => 'array', 'step' => 'integer', 'substep' => 'integer'];
}
```

**Save pattern** (from `AgentToshi.php`):

```php
private function saveDraft(): void
{
    $session = OnboardingSession::updateOrCreate(
        ['user_id' => auth()->id(), 'status' => 'draft'],
        [
            'school_id' => $this->schoolId,
            'step'      => $this->step,
            'substep'   => $this->substep,
            'data'      => $this->collectDraftData(),
        ]
    );
    $this->draftSessionId = $session->id;
}
```

**Resume pattern:**

```php
private function loadDraft(): bool
{
    $draft = OnboardingSession::where('user_id', auth()->id())
        ->where('status', 'draft')
        ->latest()
        ->first();
    // ... populate component properties from $draft->data
}
```

### Tier 3: Database Tables (Permanent, Business Data)

**Use for:** Actual business entities — schools, students, teachers, fees, etc.

- Created via `ToshiActionService` methods (addStudent, createSchool, etc.)
- Wrapped in `DB::transaction()` for multi-table operations
- **Never** bypass the service layer to write directly from Livewire

### Decision Table

| Scope | Mechanism | Example |
|---|---|---|
| Current step number | Livewire property | `$this->step` |
| Form input (current screen) | Livewire property | `$this->schoolName` |
| Draft onboarding progress | `OnboardingSession` model | `saveDraft()` / `loadDraft()` |
| Business entities | DB tables via service | `ToshiActionService::addStudent()` |
| Feature flags | `config()` / `.env` | `config('toshi.laragent_enabled')` |

---

## 3. Validation Patterns

### Uganda Phone Normalization

**Format:** `+256 7[0578] XXX XXX` (12 chars with `+`, 9 digits after 256)

**Validation regex:**
```php
/^\+256(7[0578]\d{7})$/
```

**wa.me links:** strip the `+` prefix → `2567XXXXXXXX`

**Normalization** (from `AgentToshi.php`):
```php
// Strip spaces, dashes, parentheses
$phone = preg_replace('/[\s\-\(\)]/', '', $phone);
// Ensure +256 prefix
if (str_starts_with($phone, '0')) {
    $phone = '+256' . substr($phone, 1);
} elseif (str_starts_with($phone, '256')) {
    $phone = '+' . $phone;
}
```

### Duplicate School Name Checks

```php
$existing = School::where('name', 'like', $schoolName . '%')->first();
if ($existing) {
    return self::result(false, "A school with a similar name already exists: {$existing->name}");
}
```

### Email Validation

```php
preg_match('/^[\w\.\-]+@[\w\.\-]+\.\w+$/', $email)
```

### File Upload Validation

```php
$this->validate([
    'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv|max:5120',
]);
```

### Fuzzy Step Matching (Edit Flow)

When users edit previous answers, the system uses fuzzy matching:

```php
private function fuzzyMatchStep(string $input): ?int
{
    $keywords = [
        'plan'     => 0,   // plan_selection
        'school'   => 1,   // school_info
        'admin'    => 2,   // admin_account
        'co.admin' => 3,   // co_admin_invite
        'year'     => 4,   // academic_year
        'class'    => 5,   // standards
        'subject'  => 6,   // subjects
        'teacher'  => 7,   // teachers
        'student'  => 9,   // students
        'term'     => 10,  // terms
        'fee'      => 11,  // fees
        'exam'     => 12,  // exams
        'whatsapp' => 13,  // whatsapp_verify
        'review'   => 14,  // review
    ];
    // ... match input against keywords
}
```

---

## 4. Role Capability System

Roles are defined by `usergroup_id` and enforced via `ToshiActionService::getRoleCapabilities()`:

| ID | Role | Scope | Key Actions |
|---|---|---|---|
| 1 | SiteAdmin | platform | create_school, platform_reports, list_schools |
| 2 | SiteSubadmin | platform | same as SiteAdmin |
| 3 | SchoolAdmin | school | add_student, add_teacher, create_fee, record_attendance, etc. |
| 4 | SchoolSubadmin | school | list_classes, list_teachers, record_attendance |
| 5 | Teacher | school | record_attendance, record_payment |

**Check pattern:**
```php
$capabilities = ToshiActionService::getRoleCapabilities($user->usergroup_id);
if (!in_array($action, $capabilities['actions'])) {
    return self::result(false, 'You do not have permission to perform this action.');
}
```

---

## 5. Naming Conventions

| Entity | Convention | Example |
|---|---|---|
| Models | Singular, PascalCase | `StudentAcademic`, `FeesCategories` |
| Services | `{Domain}ActionService` | `ToshiActionService` |
| Livewire | `{Feature}` | `AgentToshi` |
| Helpers | `{Domain}Helper` | `OnboardingHelper`, `SiteHelper` |
| AI Agents | `{Name}Agent` | `ToshiAssistantAgent` |
| Controllers | `{Resource}Controller` | `SchoolPayWebhookController` |
| CSS variables | `--d-{name}` | `--d-blue`, `--d-green` |
