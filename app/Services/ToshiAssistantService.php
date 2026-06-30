<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToshiAssistantService
{
    private string $baseUrl;
    private string $apiKey;
    private string $model;
    private ?string $fallbackModel;
    private int $dailyLimit;
    private int $maxTokens;
    private int $timeout;
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = config('toshi.llm_enabled', false);
        $this->baseUrl = rtrim(config('toshi.base_url', 'https://api.openai.com/v1'), '/');
        $this->apiKey = config('toshi.api_key', '');
        $this->model = config('toshi.model', 'gpt-4o-mini');
        $this->fallbackModel = config('toshi.fallback_model');
        $this->dailyLimit = (int) config('toshi.daily_llm_limit', 100);
        $this->maxTokens = (int) config('toshi.max_tokens', 500);
        $this->timeout = (int) config('toshi.request_timeout', 15);
    }

    /**
     * Ask Toshi a question and get a text response.
     * Returns null if LLM is disabled, limit reached, or API fails — caller falls back to keyword router.
     */
    public function ask(User $user, ?int $schoolId, string $query, array $history = []): ?string
    {
        if (!$this->enabled || empty($this->apiKey)) {
            return null;
        }

        if (!$this->consumeDailyBudget($user, $schoolId)) {
            return null;
        }

        $context = $this->buildContext($user, $schoolId);
        $systemPrompt = $this->buildSystemPrompt($context);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach (array_slice($history, -10) as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg['text'] ?? $msg['content'] ?? ''];
        }

        $messages[] = ['role' => 'user', 'content' => $query];

        return $this->callLLM($messages);
    }

    /**
     * Ask Toshi with a streaming response. Yields chunks as they arrive.
     * Falls back to non-streaming ask() if stream fails.
     */
    public function askStreamed(User $user, ?int $schoolId, string $query, array $history = []): \Generator
    {
        if (!$this->enabled || empty($this->apiKey)) {
            yield from [];
            return;
        }

        if (!$this->consumeDailyBudget($user, $schoolId)) {
            yield from [];
            return;
        }

        $context = $this->buildContext($user, $schoolId);
        $systemPrompt = $this->buildSystemPrompt($context);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach (array_slice($history, -10) as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg['text'] ?? $msg['content'] ?? ''];
        }

        $messages[] = ['role' => 'user', 'content' => $query];

        $yieldedAny = false;
        foreach ($this->streamLLM($messages) as $chunk) {
            $yieldedAny = true;
            yield $chunk;
        }

        if (!$yieldedAny) {
            $full = $this->callLLM($messages);
            if ($full !== null) {
                yield $full;
            }
        }
    }

    /**
     * Build context data for the system prompt based on user role and scope.
     */
    public function buildContext(User $user, ?int $schoolId): array
    {
        $isSuperAdmin = $user->usergroup_id === 1;

        if ($isSuperAdmin) {
            $totalSchools = School::count();
            $activeSchools = School::where('status', 1)->count();
            $inactiveSchools = $totalSchools - $activeSchools;
            $totalUsers = User::count();
            $schoolAdmins = User::where('usergroup_id', 3)->count();
            $teachers = User::where('usergroup_id', 5)->count();

            $incompleteCount = 0;
            try {
                $incompleteCount = School::where('status', 1)
                    ->whereDoesntHave('currentPlan')
                    ->count();
            } catch (\Exception $e) {
                $incompleteCount = 0;
            }

            return [
                'role' => 'platform',
                'data' => [
                    'Total schools' => "{$totalSchools} ({$activeSchools} active, {$inactiveSchools} inactive)",
                    'Total users' => number_format($totalUsers),
                    'School admins' => number_format($schoolAdmins),
                    'Teachers' => number_format($teachers),
                    'Schools with incomplete setup' => (string) $incompleteCount,
                ],
            ];
        }

        if ($schoolId) {
            $school = School::find($schoolId);
            if (!$school) {
                return [
                    'role' => 'school',
                    'data' => ['Note' => 'School data not available.'],
                ];
            }

            $studentCount = 0;
            $teacherCount = 0;
            $classCount = 0;
            $feeTotal = 0;
            $termCount = 0;
            $linkedParents = 0;

            try {
                $studentCount = \App\Models\StudentAcademic::where('school_id', $schoolId)->count();
                $teacherCount = User::where('school_id', $schoolId)->where('usergroup_id', 5)->count();
                $classCount = \App\Models\StandardLink::where('school_id', $schoolId)->count();
                $feeTotal = \App\Models\FeesCategories::where('school_id', $schoolId)->sum('amount');
                $termCount = \App\Models\AcademicTerm::where('school_id', $schoolId)->count();
                $linkedParents = \App\Models\WhatsAppUser::where('school_id', $schoolId)->count();
            } catch (\Exception $e) {
                // Gracefully degrade if tables don't exist yet
            }

            return [
                'role' => 'school',
                'data' => [
                    'School' => $school->name,
                    'Students' => (string) $studentCount,
                    'Teachers' => (string) $teacherCount,
                    'Classes' => (string) $classCount,
                    'Total fees configured' => number_format($feeTotal, 0) . ' UGX',
                    'Active terms' => (string) $termCount,
                    'Parents linked via WhatsApp' => (string) $linkedParents,
                ],
            ];
        }

        return [
            'role' => 'school',
            'data' => ['Note' => 'No school context available.'],
        ];
    }

    /**
     * Build the system prompt with injected context data.
     */
    public function buildSystemPrompt(array $context): string
    {
        $template = config('toshi.system_prompt_template', '');

        $roleLabel = $context['role'] === 'platform'
            ? 'Platform assistant. You have access to platform-wide statistics and can answer questions about the entire KlassApp network.'
            : 'School assistant. You help school administrators manage their school on KlassApp.';

        $dataLines = '';
        foreach ($context['data'] as $label => $value) {
            $dataLines .= "- {$label}: {$value}\n";
        }

        $prompt = str_replace(
            ['{role}', '{context_data}'],
            [$roleLabel, trim($dataLines)],
            $template
        );

        return $prompt;
    }

    /**
     * Is the current time within work hours (configurable, default 08:00-16:00)?
     */
    public function isWorkHours(): bool
    {
        $workStart = (int) config('toshi.work_hours_start', 8);
        $workEnd = (int) config('toshi.work_hours_end', 16);
        $hour = (int) now()->format('G');
        return $hour >= $workStart && $hour < $workEnd;
    }

    /**
     * Get the effective budget per rolling 8-hour window.
     * - Base: daily_limit / 3
     * - Work hours: 2x base
     * - Premium schools: 5x base (for the window budget)
     */
    public function getWindowBudget(?int $schoolId = null): int
    {
        $basePerWindow = (int) floor($this->dailyLimit / 3);
        if ($basePerWindow < 1) $basePerWindow = 1;

        $budget = $basePerWindow;

        if ($this->isWorkHours()) {
            $budget *= 2;
        }

        if ($schoolId && $this->isPremiumSchool($schoolId)) {
            $budget *= 5;
        }

        return $budget;
    }

    /**
     * Check if a school is on a premium plan (higher limits).
     */
    private function isPremiumSchool(int $schoolId): bool
    {
        try {
            $currentPlan = \App\Models\CurrentPlan::where('school_id', $schoolId)
                ->where('status', 1)
                ->with('plan')
                ->first();
            if ($currentPlan && $currentPlan->plan) {
                $name = strtolower($currentPlan->plan->name);
                return in_array($name, ['premium', 'growth']);
            }
        } catch (\Exception $e) {
            // If tables don't exist yet, silently degrade
        }
        return false;
    }

    /**
     * Get the user's school plan name for upgrade messaging.
     */
    public function getSchoolPlanLabel(?int $schoolId): string
    {
        if (!$schoolId) return 'Free';

        try {
            $currentPlan = \App\Models\CurrentPlan::where('school_id', $schoolId)
                ->where('status', 1)
                ->with('plan')
                ->first();
            if ($currentPlan && $currentPlan->plan) {
                return ucfirst($currentPlan->plan->name);
            }
        } catch (\Exception $e) {}

        return 'Free';
    }

    /**
     * Return a contextual upgrade suggestion based on school plan.
     */
    public function getUpgradeSuggestion(\App\Models\User $user, ?int $schoolId): string
    {
        $planLabel = $this->getSchoolPlanLabel($schoolId);

        if ($planLabel === 'Premium') {
            return '';
        }

        // Super admin manages plans; school admin can request upgrade
        if ($user->usergroup_id === 1) {
            return 'Upgrade the school\'s plan for higher limits.';
        }

        return 'Upgrade to Premium for higher limits.';
    }

    /**
     * Get or start a rolling 8-hour window for the user.
     * Each user gets their own independent 8-hour window starting from their first query.
     * Returns ['start' => Carbon, 'count' => int, 'reset_at' => Carbon].
     */
    private function getRollingWindow(User $user, ?int $schoolId): array
    {
        $cacheKey = 'toshi_rolling_' . $user->id . '_' . ($schoolId ?? 'platform');
        $data = Cache::get($cacheKey);

        $now = now();

        if ($data && isset($data['start'])) {
            $start = \Carbon\Carbon::parse($data['start']);
            $resetAt = $start->copy()->addHours(8);

            if ($now->greaterThanOrEqualTo($resetAt)) {
                // Window expired — start a fresh one
                $data = [
                    'start' => $now->toISOString(),
                    'count' => 0,
                ];
                Cache::put($cacheKey, $data, $now->addHours(8));
                return [
                    'start' => $now,
                    'count' => 0,
                    'reset_at' => $now->copy()->addHours(8),
                ];
            }

            // Window still active
            $remainingTtl = $now->diffInSeconds($resetAt);
            if ($remainingTtl > 0) {
                Cache::put($cacheKey, $data, $remainingTtl);
            }

            return [
                'start' => $start,
                'count' => (int) ($data['count'] ?? 0),
                'reset_at' => $resetAt,
            ];
        }

        // No active window — start one
        $data = [
            'start' => $now->toISOString(),
            'count' => 0,
        ];
        Cache::put($cacheKey, $data, $now->addHours(8));

        return [
            'start' => $now,
            'count' => 0,
            'reset_at' => $now->copy()->addHours(8),
        ];
    }

    /**
     * Get the reset time for the user's current rolling window.
     */
    public function getWindowResetTime(User $user, ?int $schoolId): \DateTime
    {
        $window = $this->getRollingWindow($user, $schoolId);
        return $window['reset_at'];
    }

    /**
     * Super admins are never limited.
     */
    private function isUnlimited(User $user): bool
    {
        return $user->usergroup_id === 1;
    }

    /**
     * Check and consume a unit from the user's rolling LLM budget.
     * Returns true if the call is allowed, false if limit exceeded.
     * Super admins are never limited.
     */
    private function consumeDailyBudget(User $user, ?int $schoolId): bool
    {
        if ($this->isUnlimited($user)) {
            return true;
        }

        $windowBudget = $this->getWindowBudget($schoolId);
        if ($windowBudget <= 0) {
            return true;
        }

        $window = $this->getRollingWindow($user, $schoolId);
        $count = $window['count'];

        if ($count >= $windowBudget) {
            Log::info('Toshi LLM window limit reached', [
                'user_id' => $user->id,
                'school_id' => $schoolId,
                'budget' => $windowBudget,
                'reset_at' => $window['reset_at']->toIso8601String(),
            ]);
            return false;
        }

        // Increment count in cache
        $cacheKey = 'toshi_rolling_' . $user->id . '_' . ($schoolId ?? 'platform');
        $data = Cache::get($cacheKey, []);
        $data['count'] = ($data['count'] ?? 0) + 1;
        $remainingTtl = now()->diffInSeconds($window['reset_at']);
        Cache::put($cacheKey, $data, max(1, $remainingTtl));

        return true;
    }

    /**
     * Get remaining LLM queries for the user's current rolling window.
     * Super admins always show unlimited.
     */
    public function getRemainingBudget(User $user, ?int $schoolId): int
    {
        if ($this->isUnlimited($user)) {
            return PHP_INT_MAX;
        }

        $windowBudget = $this->getWindowBudget($schoolId);
        if ($windowBudget <= 0) {
            return PHP_INT_MAX;
        }

        $window = $this->getRollingWindow($user, $schoolId);
        return max(0, $windowBudget - $window['count']);
    }

    /**
     * Check if LLM is available (enabled + key set + budget remaining in current rolling window).
     * Super admins are always available.
     */
    public function isAvailable(User $user, ?int $schoolId): bool
    {
        if (!$this->enabled || empty($this->apiKey)) {
            return false;
        }

        if ($this->isUnlimited($user)) {
            return true;
        }

        return $this->getRemainingBudget($user, $schoolId) > 0;
    }

    /**
     * Call the LLM API (non-streaming).
     */
    private function callLLM(array $messages, ?string $retryModel = null): ?string
    {
        $model = $retryModel ?? $this->model;

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $this->maxTokens,
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                // Primary model failed and we have a fallback — retry once
                if ($retryModel === null && $this->fallbackModel) {
                    Log::info('Toshi LLM primary model failed, trying fallback', [
                        'primary' => $this->model,
                        'fallback' => $this->fallbackModel,
                        'status' => $response->status(),
                    ]);
                    return $this->callLLM($messages, retryModel: $this->fallbackModel);
                }

                Log::warning('Toshi LLM API error', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? null;

            if ($text === null) {
                Log::warning('Toshi LLM empty response', ['response' => $data]);
                return null;
            }

            return trim($text);

        } catch (\Exception $e) {
            // Primary model threw exception and we have a fallback — retry once
            if ($retryModel === null && $this->fallbackModel) {
                Log::info('Toshi LLM primary model exception, trying fallback', [
                    'primary' => $this->model,
                    'fallback' => $this->fallbackModel,
                    'error' => $e->getMessage(),
                ]);
                return $this->callLLM($messages, retryModel: $this->fallbackModel);
            }

            Log::error('Toshi LLM request failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Stream the LLM response via SSE. Yields content chunks as strings.
     */
    private function streamLLM(array $messages): \Generator
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $this->timeout * 2,
                'stream' => true,
            ]);

            $response = $client->post("{$this->baseUrl}/chat/completions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => $this->maxTokens,
                    'temperature' => 0.7,
                    'stream' => true,
                ],
            ]);

            $body = $response->getBody();

            while (!$body->eof()) {
                $line = $body->read(1024);
                $lines = explode("\n", $line);

                foreach ($lines as $eventLine) {
                    $eventLine = trim($eventLine);
                    if (empty($eventLine)) continue;
                    if ($eventLine === 'data: [DONE]') break 2;

                    if (str_starts_with($eventLine, 'data: ')) {
                        $json = json_decode(substr($eventLine, 6), true);
                        if ($json === null) continue;

                        $delta = $json['choices'][0]['delta']['content'] ?? '';
                        if ($delta !== '') {
                            yield $delta;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Toshi LLM stream failed', [
                'error' => $e->getMessage(),
            ]);
            yield from [];
        }
    }
}
