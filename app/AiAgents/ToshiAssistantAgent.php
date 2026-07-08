<?php

namespace App\AiAgents;

use App\Models\School;
use App\Models\ToshiPersona;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LarAgent\Agent;
use LarAgent\Message;
use LarAgent\Tools\Tool;
use LarAgent\Core\Contracts\Message as MessageInterface;

class ToshiAssistantAgent extends Agent
{
    protected $model = 'meta/llama-3.1-8b-instruct';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    protected bool $laragentEnabled = false;

    // ── Tier tracking (Steps 8 & 9) ──

    private string $lastQueryTier = 'unknown';

    private bool $lastQueryCached = false;
    private ?int $currentSchoolId = null;

    public function __construct()
    {
        parent::__construct('toshi-assistant');
        $this->laragentEnabled = config('toshi.laragent_enabled', false);
    }

    private function getSchoolId(): ?int
    {
        return $this->currentSchoolId;
    }

    public function instructions(): string
    {
        return config('toshi.system_prompt_template', "You are Toshi, the AI assistant for KlassApp.");
    }

    // ══════════════════════════════════════════════════
    //  Main entry point
    // ══════════════════════════════════════════════════

    public function handleQuery(User $user, ?int $schoolId, string $query, array $history = []): ?string
    {
        $this->currentSchoolId = $schoolId;

        if (!$this->laragentEnabled) {
            return null;
        }

        if (!$this->consumeDailyBudget($user, $schoolId)) {
            Log::info('LarAgent: daily budget exhausted', ['user_id' => $user->id]);
            return null;
        }

        // Step 9: Check static response cache (exact + normalized match)
        $cached = $this->checkResponseCache($query, $schoolId);
        if ($cached !== null) {
            $this->lastQueryCached = true;
            $this->lastQueryTier = 'cache_hit';
            Log::info('LarAgent tier: cache_hit', ['user_id' => $user->id]);
            return $cached;
        }
        $this->lastQueryCached = false;

        try {
            // Step 11: Static context (cached per-school)
            $context = $this->buildCachedContext($user, $schoolId);
            $persona = $this->getPersonaSummary($user);
            $systemPrompt = str_replace(
                ['{role}', '{context_data}', '{persona}'],
                [
                    $schoolId ? 'school administrator' : 'platform administrator',
                    $context,
                    $persona,
                ],
                $this->instructions()
            );

            // Set instructions via the agent's internal property
            $this->instructions = $systemPrompt;

            // Inject conversation history (system prompt is handled by the driver via instructions())
            foreach (array_slice($history, -10) as $msg) {
                $role = $msg['role'] ?? 'user';
                $content = $msg['text'] ?? $msg['content'] ?? '';
                if ($role === 'user') {
                    $this->chatHistory()->addMessage(\LarAgent\Message::user($content));
                } else {
                    $this->chatHistory()->addMessage(\LarAgent\Message::assistant($content));
                }
            }

            // Step 8: Complexity routing — pick model tier
            $tier = $this->classifyComplexity($query);
            $this->lastQueryTier = $tier;

            // Build messages array with system prompt, history, and current query
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach (array_slice($history, -10) as $msg) {
                $role = match ($msg['role'] ?? 'user') {
                    'bot', 'assistant' => 'assistant',
                    'system' => 'system',
                    default => 'user',
                };
                $messages[] = [
                    'role' => $role,
                    'content' => $msg['text'] ?? $msg['content'] ?? '',
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $query];

            // Call the LLM API directly (bypasses LarAgent driver which has compatibility issues with Nvidia NIM)
            $baseUrl = config('toshi.base_url', 'https://integrate.api.nvidia.com/v1');
            $apiKey = config('toshi.api_key', '');
            $model = config('toshi.model', 'meta/llama-3.1-8b-instruct');
            $timeout = (int) config('toshi.request_timeout', 15);

            $httpResponse = \Illuminate\Support\Facades\Http::timeout($timeout)
                ->withToken($apiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'tools' => [[
                        'type' => 'function',
                        'function' => [
                            'name' => 'toolCreateExam',
                            'description' => 'Create an exam. Provide name (e.g. "End of Term 1"), and optionally scheduled_at (Y-m-d).',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string', 'description' => 'Exam name'],
                                    'scheduled_at' => ['type' => 'string', 'description' => 'Date Y-m-d'],
                                ],
                                'required' => ['name'],
                            ],
                        ],
                    ], [
                        'type' => 'function',
                        'function' => [
                            'name' => 'toolAddParent',
                            'description' => 'Add a parent and link to a student. Provide parent name, phone (+256...), and optionally student_id.',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string', 'description' => 'Parent full name'],
                                    'phone' => ['type' => 'string', 'description' => 'Phone number starting with +256'],
                                    'student_id' => ['type' => 'integer', 'description' => 'Student ID to link to'],
                                ],
                                'required' => ['name', 'phone'],
                            ],
                        ],
                    ], [
                        'type' => 'function',
                        'function' => [
                            'name' => 'toolEnterMark',
                            'description' => 'Enter a mark for a student on an exam. Provide student_id, exam_id, and marks (score 0-100).',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => [
                                    'student_id' => ['type' => 'integer', 'description' => 'Student ID'],
                                    'exam_id' => ['type' => 'integer', 'description' => 'Exam ID'],
                                    'marks' => ['type' => 'number', 'description' => 'Score 0-100'],
                                ],
                                'required' => ['student_id', 'exam_id', 'marks'],
                            ],
                        ],
                    ]],
                    'tool_choice' => 'auto',
                    'max_tokens' => 500,
                    'temperature' => 0.3,
                ]);

            if (!$httpResponse->successful()) {
                Log::warning('LarAgent API error', [
                    'model' => $model,
                    'status' => $httpResponse->status(),
                    'body' => $httpResponse->body(),
                ]);
                return null;
            }

            $data = $httpResponse->json();
            $choice = $data['choices'][0] ?? [];

            // Handle tool calls
            if (!empty($choice['message']['tool_calls'])) {
                foreach ($choice['message']['tool_calls'] as $toolCall) {
                    $toolName = $toolCall['function']['name'] ?? '';
                    $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];

                    $toolResult = match ($toolName) {
                        'toolCreateExam' => \App\Services\ToshiActionService::createExam(auth()->user() ?? $user, $toolArgs),
                        'toolAddParent' => \App\Services\ToshiActionService::addParent(auth()->user() ?? $user, $toolArgs),
                        'toolEnterMark' => \App\Services\ToshiActionService::enterMark(auth()->user() ?? $user, $toolArgs),
                        default => ['success' => false, 'message' => "Unknown tool: {$toolName}"],
                    };

                    return $toolResult['message'] ?? 'Tool executed.';
                }
            }

            $reply = $choice['message']['content'] ?? '';
            $reply = trim($reply);

            // Step 9: Cache the response if it's generic (no school-specific data)
            $this->maybeCacheResponse($query, $reply, $schoolId);

            // Step 10: Batched persona extraction
            $this->learnFromInteraction($user, $query, $reply, $history);

            Log::info('LarAgent tier: ' . $tier, [
                'user_id' => $user->id,
                'query_length' => strlen($query),
            ]);

            return $reply;
        } catch (\Throwable $e) {
            Log::error('LarAgent query failed', [
                'user_id' => $user->id,
                'tier' => $this->lastQueryTier,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ══════════════════════════════════════════════════
    //  Step 8 — Complexity-based model routing
    // ══════════════════════════════════════════════════

    private function classifyComplexity(string $query): string
    {
        $len = strlen($query);

        // Very short queries are always cheap
        if ($len < 30) {
            return 'cheap';
        }

        // Check for complexity markers
        $complexityScore = 0;

        // Multiple clauses
        if (preg_match_all('/\b(and|or|but|however|whereas|meanwhile)\b/i', $query) > 2) {
            $complexityScore += 2;
        }

        // Nested conditions
        if (preg_match('/\b(if|when|unless|provided that|assuming)\b/i', $query)) {
            $complexityScore += 2;
        }

        // Multiple questions
        if (substr_count($query, '?') > 1) {
            $complexityScore += 2;
        }

        // Long query
        if ($len > 200) {
            $complexityScore += 1;
        }

        // Comparison or analysis requests
        if (preg_match('/\b(compare|contrast|difference|versus|vs\.?|better|worse)\b/i', $query)) {
            $complexityScore += 2;
        }

        // Data analysis / reporting requests (these need the stronger model)
        if (preg_match('/\b(trend|analyze|analysis|forecast|predict|pattern|distribution|breakdown)\b/i', $query)) {
            $complexityScore += 2;
        }

        // Escalate if score >= 4
        return $complexityScore >= 4 ? 'escalated' : 'cheap';
    }

    public function getLastQueryTier(): string
    {
        return $this->lastQueryTier;
    }

    public function wasLastQueryCached(): bool
    {
        return $this->lastQueryCached;
    }

    // ══════════════════════════════════════════════════
    //  Step 9 — Response caching
    // ══════════════════════════════════════════════════

    private function checkResponseCache(string $query, ?int $schoolId): ?string
    {
        $key = $this->normalizeCacheKey($query);

        // Only cache truly generic queries — skip anything with school-specific data markers
        if ($this->queryNeedsLiveData($query)) {
            return null;
        }

        return Cache::get('toshi_response_' . $key);
    }

    private function maybeCacheResponse(string $query, string $response, ?int $schoolId): void
    {
        if ($this->queryNeedsLiveData($query)) {
            return;
        }

        $key = $this->normalizeCacheKey($query);
        Cache::put('toshi_response_' . $key, $response, now()->addDays(7));
    }

    private function normalizeCacheKey(string $query): string
    {
        // Normalize: lowercase, strip punctuation, collapse whitespace
        $normalized = preg_replace('/[^\w\s]/u', '', $query);
        $normalized = preg_replace('/\s+/', ' ', strtolower(trim($normalized)));

        // Truncate to first 100 chars to keep cache keys bounded
        $normalized = substr($normalized, 0, 100);

        return md5($normalized);
    }

    private function queryNeedsLiveData(string $query): bool
    {
        // Keywords that indicate the query needs fresh school-specific data
        $livePatterns = [
            'balance', 'fee', 'payment', 'owed', 'owe',
            'count', 'total', 'number of', 'how many',
            'attendance', 'present', 'absent',
            'report', 'list', 'show me',
            'my school', 'our school',
            'student', 'teacher', 'class',
            'grade', 'score', 'mark', 'result',
            'timetable', 'schedule',
        ];

        $lower = strtolower($query);
        foreach ($livePatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    // ══════════════════════════════════════════════════
    //  Step 10 — Batched persona extraction (audited)
    // ══════════════════════════════════════════════════

    private function learnFromInteraction(User $user, string $query, string $response, array $history): void
    {
        if (!config('toshi.persona_enabled', true)) {
            return;
        }

        // Already batched: fires every N interactions (controlled by persona_update_interval)
        // This is correct — it does NOT fire on every single query.
        $interval = (int) config('toshi.persona_update_interval', 5);
        $key = 'toshi_persona_pending_' . $user->id;

        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addDay());

        if ($count < $interval) {
            return;
        }

        Cache::forget($key);

        // Also: skip persona extraction if the response was cached
        // (no new interaction data to learn from)
        if ($this->lastQueryCached) {
            return;
        }

        try {
            $existing = ToshiPersona::where('user_id', $user->id)->first();
            $summary = $existing?->summary ?? 'New user.';

            $prompt = "Summarize this user's communication style, concerns, and role based on their recent queries. "
                . "Existing summary: {$summary}\n\n"
                . "Recent query: {$query}\n"
                . "Assistant response: {$response}\n\n"
                . "Write one paragraph updating the summary.";

            $this->addUserMessage($prompt);
            $response = $this->run();
            $newSummary = $response->content() ?? $summary;

            ToshiPersona::updateOrCreate(
                ['user_id' => $user->id],
                ['summary' => $newSummary, 'model' => $this->model]
            );
        } catch (\Throwable $e) {
            Log::warning('Persona update failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════
    //  Step 11 — Static context caching
    // ══════════════════════════════════════════════════

    private function buildCachedContext(User $user, ?int $schoolId): string
    {
        if (!$schoolId) {
            $schoolCount = School::count();
            return "Platform overview: {$schoolCount} schools registered.";
        }

        // Static context per-school, cached with invalidation-aware TTL
        $cacheKey = 'toshi_context_' . $schoolId;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $school = School::find($schoolId);
        if (!$school) {
            return 'School not found.';
        }

        // Only cache genuinely static data here
        $static = [];
        $static[] = "School: {$school->name}";
        $static[] = "Type: {$school->type}";
        $static[] = "Country: {$school->registration_country}";

        $context = implode("\n", $static);

        // Cache for 1 hour — short enough that changes are reflected reasonably,
        // long enough to avoid re-querying on every single interaction
        Cache::put($cacheKey, $context, now()->addHour());

        return $context;
    }

    // ══════════════════════════════════════════════════
    //  Budget (shared, unchanged from Step 2)
    // ══════════════════════════════════════════════════

    private function consumeDailyBudget(User $user, ?int $schoolId): bool
    {
        $limit = (int) config('toshi.daily_llm_limit', 100);
        if ($limit <= 0) {
            return true;
        }

        $key = 'toshi_llm_daily_' . $user->id . '_' . date('Y-m-d');
        $count = (int) Cache::get($key, 0);

        if ($count >= $limit) {
            return false;
        }

        Cache::put($key, $count + 1, now()->endOfDay());
        return true;
    }

    // ══════════════════════════════════════════════════
    //  Persona (unchanged)
    // ══════════════════════════════════════════════════

    private function getPersonaSummary(User $user): string
    {
        if (!config('toshi.persona_enabled', true)) {
            return '';
        }

        $persona = ToshiPersona::where('user_id', $user->id)->first();
        return $persona?->summary ?? '';
    }

    // ══════════════════════════════════════════════════
    //  Tools: ToshiActionService wrappers
    // ══════════════════════════════════════════════════

    #[Tool('Add a new student. Provide name, class, and optionally stream, type, parent name, parent phone.')]
    public function toolAddStudent(string $name, string $class, string $stream = '', string $type = '', string $parent = '', string $parentPhone = ''): array
    {
        return ToshiActionService::addStudent(auth()->user(), compact('name', 'class', 'stream', 'type', 'parent', 'parentPhone'));
    }

    #[Tool('Add a new teacher. Provide name, email, phone, subjects (comma-separated), classes (comma-separated).')]
    public function toolAddTeacher(string $name, string $email = '', string $phone = '', string $subjects = '', string $classes = ''): array
    {
        return ToshiActionService::addTeacher(auth()->user(), compact('name', 'email', 'phone', 'subjects', 'classes'));
    }

    #[Tool('Promote an existing teacher to co-admin by user ID.')]
    public function toolAddCoAdmin(int $userId): array
    {
        return ToshiActionService::addCoAdmin(auth()->user(), ['user_id' => $userId]);
    }

    #[Tool('Record a fee payment. Provide student ID, amount, method (cash/cheque/mobile_money/bank_transfer).')]
    public function toolRecordPayment(int $studentId, float $amount, string $method = 'cash'): array
    {
        return ToshiActionService::recordPayment(auth()->user(), ['student_id' => $studentId, 'amount' => $amount, 'method' => $method]);
    }

    #[Tool('Record attendance. Provide student ID, date (Y-m-d), status (present/absent/late/excused).')]
    public function toolRecordAttendance(int $studentId, string $date, string $status = 'present'): array
    {
        return ToshiActionService::recordAttendance(auth()->user(), ['student_id' => $studentId, 'date' => $date, 'status' => $status]);
    }

    #[Tool('Record bulk attendance. Provide comma-separated student IDs, date, status.')]
    public function toolRecordBulkAttendance(string $studentIds, string $date, string $status = 'present'): array
    {
        return ToshiActionService::recordBulkAttendance(auth()->user(), ['student_ids' => explode(',', $studentIds), 'date' => $date, 'status' => $status]);
    }

    #[Tool('Create an academic term. Provide name, start date (Y-m-d), end date (Y-m-d).')]
    public function toolCreateTerm(string $name, string $startDate, string $endDate): array
    {
        return ToshiActionService::createTerm(auth()->user(), ['name' => $name, 'start' => $startDate, 'end' => $endDate]);
    }

    #[Tool('Create a fee category. Provide name, amount, and optionally level, class, term.')]
    public function toolCreateFee(string $name, float $amount = 0, string $level = '', string $class = '', string $term = ''): array
    {
        return ToshiActionService::createFee(auth()->user(), compact('name', 'amount', 'level', 'class', 'term'));
    }

    #[Tool('Create a subject. Provide name and code.')]
    public function toolCreateSubject(string $name, string $code = ''): array
    {
        return ToshiActionService::createSubject(auth()->user(), compact('name', 'code'));
    }

    #[Tool('Assign a teacher to a subject and class.')]
    public function toolAssignTeacher(string $teacherName, string $subject, string $class): array
    {
        return ToshiActionService::assignTeacher(auth()->user(), ['teacher' => $teacherName, 'subject' => $subject, 'class' => $class]);
    }

    #[Tool('List all classes/sections in the school.')]
    public function toolListClasses(): array
    {
        return ToshiActionService::listClasses(auth()->user());
    }

    #[Tool('List all sections (streams).')]
    public function toolListSections(): array
    {
        return ToshiActionService::listSections(auth()->user());
    }

    #[Tool('List all teachers in the school.')]
    public function toolListTeachers(): array
    {
        return ToshiActionService::listTeachers(auth()->user());
    }

    #[Tool('Generate a report. Type: students/attendance/fees/exams. Optional term/class filters.')]
    public function toolGenerateReport(string $type, string $term = '', string $class = ''): array
    {
        return ToshiActionService::generateReport(auth()->user(), compact('type', 'term', 'class'));
    }

    #[Tool('Find a student by name, email, or KlassApp ID.')]
    public function toolFindStudent(string $query): array
    {
        $result = ToshiActionService::findStudentSimple(auth()->user(), $query);
        if ($result instanceof \App\Models\User) {
            return ['success' => true, 'message' => 'Found: ' . $result->name, 'data' => ['user' => $result]];
        }
        return ['success' => false, 'message' => $result ?? 'Student not found.'];
    }

    #[Tool('Get student count for the school.')]
    public function toolGetStudentCount(): array
    {
        $schoolId = auth()->user()?->school_id;
        if (!$schoolId) {
            return ['success' => false, 'message' => 'No school context.'];
        }
        $count = \App\Models\StudentAcademic::where('school_id', $schoolId)->count();
        return ['success' => true, 'message' => "{$count} students enrolled.", 'data' => ['count' => $count]];
    }

    #[Tool('Get fee balance for a student by ID.')]
    public function toolGetFeeBalance(int $studentId): array
    {
        $student = \App\Models\StudentAcademic::find($studentId);
        if (!$student) {
            return ['success' => false, 'message' => 'Student not found.'];
        }
        $totalFees = \App\Models\FeesCategories::where('school_id', $student->school_id)->sum('amount');
        $paid = \App\Models\FeePayment::where('student_id', $studentId)->sum('amount');
        $balance = $totalFees - $paid;
        return ['success' => true, 'message' => "Balance: " . number_format(max(0, $balance), 0) . " UGX", 'data' => ['balance' => max(0, $balance)]];
    }

    // ══════════════════════════════════════════════════
    //  Grading system tools
    // ══════════════════════════════════════════════════

    #[Tool('Set the grading scale for a class/standard. Provide standard name, and array of grade definitions with grade label, min_score, max_score, points (optional), remark (optional). Example: [{"grade":"A","min_score":80,"max_score":100,"points":1,"remark":"Excellent"},...]. Returns the new grade rules.')]
    public function toolSetGradingScale(string $standardName, array $gradeDefinitions): array
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return ['success' => false, 'message' => 'No school context.'];
        }
        $standard = \App\Models\Standard::where('school_id', $schoolId)->where('name', $standardName)->first();
        if (!$standard) {
            return ['success' => false, 'message' => "Standard '{$standardName}' not found."];
        }

        // Remove existing rules for this standard
        \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $standard->id)->delete();

        foreach ($gradeDefinitions as $def) {
            \App\Models\Academics\SchoolGradingSystem::create([
                'school_id'   => $schoolId,
                'standard_id' => $standard->id,
                'grade'       => $def['grade'],
                'min_score'   => (int) ($def['min_score'] ?? 0),
                'max_score'   => (int) ($def['max_score'] ?? 100),
                'points'      => isset($def['points']) ? (int) $def['points'] : null,
                'remark'      => $def['remark'] ?? '',
            ]);
        }

        \Illuminate\Support\Facades\Cache::forget("school_grading_{$schoolId}");

        return ['success' => true, 'message' => "Grading scale updated for {$standardName}."];
    }

    #[Tool('View the current grading scale for a class/standard. Provide the standard name (e.g. "Primary One", "Senior Three"). Returns grade rules.')]
    public function toolViewGradingScale(string $standardName): array
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return ['success' => false, 'message' => 'No school context.'];
        }
        $standard = \App\Models\Standard::where('school_id', $schoolId)->where('name', $standardName)->first();
        if (!$standard) {
            return ['success' => false, 'message' => "Standard '{$standardName}' not found."];
        }
        $rules = \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $standard->id)
            ->orderByDesc('max_score')
            ->get(['grade', 'min_score', 'max_score', 'points', 'remark']);

        return ['success' => true, 'message' => "Grading scale for {$standardName}", 'data' => ['grades' => $rules->toArray()]];
    }

    #[Tool('Seed default MoE Uganda grading scales for all standards in the school. Uses the Ministry of Education recommended grade boundaries per level (primary/o-level/a-level/nursery).')]
    public function toolSeedDefaultGrading(): array
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return ['success' => false, 'message' => 'No school context.'];
        }
        \App\Helpers\GradingHelper::seedAllGradingForSchool($schoolId);
        return ['success' => true, 'message' => 'Default MoE grading scales seeded for all standards.'];
    }

    // ══════════════════════════════════════════════════
    //  New tools: Exams, Parents, Marks
    // ══════════════════════════════════════════════════

    #[Tool('Create an exam. Provide name (e.g. "End of Term 1"), and optionally scheduled_at (Y-m-d).')]
    public function toolCreateExam(string $name, string $scheduledAt = ''): array
    {
        return ToshiActionService::createExam(auth()->user(), [
            'name' => $name,
            'scheduled_at' => $scheduledAt ?: now()->toDateString(),
        ]);
    }

    #[Tool('Add a parent and link to a student. Provide parent name, phone (+256...), and student_id.')]
    public function toolAddParent(string $name, string $phone, int $studentId = 0): array
    {
        return ToshiActionService::addParent(auth()->user(), [
            'name' => $name, 'phone' => $phone, 'student_id' => $studentId,
        ]);
    }

    #[Tool('Enter a mark for a student on an exam. Provide student_id, exam_id, and marks (score 0-100).')]
    public function toolEnterMark(int $studentId, int $examId, float $marks): array
    {
        return ToshiActionService::enterMark(auth()->user(), [
            'student_id' => $studentId, 'exam_id' => $examId, 'marks' => $marks,
        ]);
    }

}
