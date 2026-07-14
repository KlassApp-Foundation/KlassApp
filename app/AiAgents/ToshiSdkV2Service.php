<?php

namespace App\AiAgents;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service layer that provides a consistent interface for the Livewire
 * component to interact with the Laravel AI SDK v2 agent stack.
 *
 * Handles:
 *   - Running queries through ToshiOrchestrator
 *   - Tier 2 confirmation flow (pending tool confirmations)
 *   - Daily budget checks (reuses existing ToshiActionService budget)
 *   - Caching of static responses
 */
class ToshiSdkV2Service
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = config('toshi.sdk_v2_enabled', false);
    }

    /**
     * Check if the SDK v2 path is available for this user.
     */
    public function isAvailable(User $user, ?int $schoolId): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Per-school gate: check toshi_enabled flag
        if (config('toshi.per_school_gate', true)) {
            $school = $schoolId ? \App\Models\School::find($schoolId) : $user->school;
            if (!$school || !$school->toshi_enabled) {
                return false;
            }
        }

        if (empty(config('ai.providers.openai-compatible.key'))) {
            return false;
        }

        return true;
    }

    /**
     * Run a query through the ToshiOrchestrator and return the response text.
     * Returns null if the path is disabled or fails.
     */
    public function ask(User $user, ?int $schoolId, string $query, array $history = []): ?string
    {
        if (!$this->isAvailable($user, $schoolId)) {
            return null;
        }

        try {
            $orchestrator = new ToshiOrchestrator;
            $response = $orchestrator->run($query);

            Log::info('SDK v2 path: orchestrator dispatched', [
                'user_id' => $user->id,
                'query' => substr($query, 0, 100),
            ]);

            return $response;
        } catch (\Throwable $e) {
            Log::warning('SDK v2 path failed, falling back', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the remaining daily budget for the user.
     * Uses the same budget tracking as the legacy path for consistency.
     */
    public function getRemainingBudget(User $user, ?int $schoolId): int
    {
        $dailyLimit = (int) config('toshi.daily_llm_limit', 100);
        $key = 'toshi_daily_llm_' . $user->id . '_' . ($schoolId ?? 0);
        $used = (int) cache()->get($key, 0);
        return max(0, $dailyLimit - $used);
    }

    /**
     * Consume one unit from the daily budget.
     */
    public function consumeBudget(User $user, ?int $schoolId): bool
    {
        if ($this->getRemainingBudget($user, $schoolId) <= 0) {
            return false;
        }

        $key = 'toshi_daily_llm_' . $user->id . '_' . ($schoolId ?? 0);
        $ttl = now()->endOfDay()->diffInSeconds(now());

        cache()->add($key, 0, $ttl);
        cache()->increment($key);

        return true;
    }
}
