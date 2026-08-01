<?php

namespace App\AiAgents;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Enums\ToshiScope;
use App\Models\User;
use App\Services\Toshi\ToshiAvailabilityGate;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\TextDelta;

/**
 * Service layer that provides a consistent interface for the Livewire
 * component to interact with the Laravel AI SDK v2 agent stack.
 *
 * Handles:
 *   - Running queries through ToshiOrchestrator
 *   - Tier 2 confirmation flow (pending tool confirmations)
 *   - Daily budget checks (reuses existing ToshiActionService budget)
 *   - Streaming responses (via the package's Agent::stream())
 */
class ToshiSdkV2Service
{
    private bool $enabled;

    public function __construct(
        private readonly ToshiAvailabilityGate $availabilityGate,
    ) {
        $this->enabled = config('toshi.sdk_v2_enabled', false);
    }

    /**
     * Check if the SDK v2 path is available for this user.
     *
     * Default scope is School so existing call sites keep identical behaviour.
     * Pass ToshiScope::Platform for siteadmin platform-scope (independent of school_id).
     */
    public function isAvailable(User $user, ?int $schoolId, ToshiScope $scope = ToshiScope::School): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->availabilityGate->allows($user, $scope, $schoolId)) {
            return false;
        }

        if (empty(config('ai.providers.openai-compatible.key'))) {
            return false;
        }

        return true;
    }

    /**
     * Run a query through the ToshiOrchestrator and return the response text.
     * Returns null if the path is disabled or fails.
     *
     * If a write tool triggered a pending confirmation during execution,
     * the payload is stored in ToshiActionService::$pendingConfirmPayload
     * (bypassed the LLM loop that might reformat it). We detect that here
     * and return the __tier2_confirm JSON to the Livewire component.
     */
    public function ask(User $user, ?int $schoolId, string $query, array $history = [], ToshiScope $scope = ToshiScope::School): ?string
    {
        if (!$this->isAvailable($user, $schoolId, $scope)) {
            return null;
        }

        try {
            // Reset the side-channel before each query
            \App\Services\ToshiActionService::$pendingConfirmPayload = null;

            // Scope router (deterministic PHP — not SDK Sub-Agents / CanActAsTool):
            // Platform → PlatformOperationsAgent; ug5 → TeacherOperationsAgent;
            // ug11 → AccountantOperationsAgent; ug8 → LibrarianOperationsAgent;
            // ug10 → ReceptionistOperationsAgent; else school-admin ToshiOrchestrator.
            $agent = $scope === ToshiScope::Platform
                ? new PlatformOperationsAgent
                : match ((int) $user->usergroup_id) {
                    5 => new TeacherOperationsAgent,
                    11 => new AccountantOperationsAgent,
                    8 => new LibrarianOperationsAgent,
                    10 => new ReceptionistOperationsAgent,
                    default => new ToshiOrchestrator,
                };
            $response = $agent->run($query);

            Log::info('SDK v2 path: agent dispatched', [
                'user_id' => $user->id,
                'usergroup_id' => $user->usergroup_id,
                'query' => substr($query, 0, 100),
                'scope' => $scope->value,
                'agent' => $agent::class,
            ]);

            // Check if a write tool stored a confirmation payload
            // (more reliable than parsing the LLM's potentially reformatted response)
            $payload = \App\Services\ToshiActionService::$pendingConfirmPayload;
            if ($payload !== null && isset($payload['tool'])) {
                \App\Services\ToshiActionService::$pendingConfirmPayload = null;
                return json_encode([
                    '__tier2_confirm' => true,
                    'tool' => $payload['tool'],
                    'args' => $payload['args'],
                    'preview' => $payload['preview'],
                ]);
            }

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
     * Run a query through the ToshiOrchestrator with streaming.
     *
     * Uses the package's Agent::stream() under the hood, which handles
     * the full tool-calling loop internally. Text deltas are forwarded to
     * $onChunk for real-time display (e.g. via Livewire's stream()).
     *
     * After the stream completes, checks for pending tool confirmations
     * (tier 2). If one exists, returns the __tier2_confirm JSON instead
     * of the full text so the caller can show a confirmation card.
     *
     * Returns null on failure.
     */
    public function askStreamed(
        User $user,
        ?int $schoolId,
        string $query,
        array $history,
        callable $onChunk,
        ToshiScope $scope = ToshiScope::School,
    ): ?string {
        if (!$this->isAvailable($user, $schoolId, $scope)) {
            return null;
        }

        try {
            \App\Services\ToshiActionService::$pendingConfirmPayload = null;

            // Scope router (same as ask()) — not an SDK Sub-Agent.
            $agent = $scope === ToshiScope::Platform
                ? new PlatformOperationsAgent
                : match ((int) $user->usergroup_id) {
                    5 => new TeacherOperationsAgent,
                    11 => new AccountantOperationsAgent,
                    8 => new LibrarianOperationsAgent,
                    10 => new ReceptionistOperationsAgent,
                    default => new ToshiOrchestrator,
                };
            $fullText = '';

            $agent
                ->stream($query)
                ->each(function ($event) use ($onChunk, &$fullText) {
                    if ($event instanceof TextDelta) {
                        $fullText .= $event->delta;
                        $onChunk($event->delta);
                    }
                })
                ->then(function ($response) use (&$fullText) {
                    if (!empty($response->text)) {
                        $fullText = $response->text;
                    }
                });

            Log::info('SDK v2 path: streamed', [
                'user_id' => $user->id,
                'usergroup_id' => $user->usergroup_id,
                'query' => substr($query, 0, 100),
                'scope' => $scope->value,
                'agent' => $agent::class,
            ]);

            // Check for pending tool confirmation
            $payload = \App\Services\ToshiActionService::$pendingConfirmPayload;
            if ($payload !== null && isset($payload['tool'])) {
                \App\Services\ToshiActionService::$pendingConfirmPayload = null;
                return json_encode([
                    '__tier2_confirm' => true,
                    'tool' => $payload['tool'],
                    'args' => $payload['args'],
                    'preview' => $payload['preview'],
                ]);
            }

            return $fullText;
        } catch (\Throwable $e) {
            Log::warning('SDK v2 stream failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the remaining daily budget for the user.
     * Delegates to ToshiActionService (single source of truth).
     */
    public function getRemainingBudget(User $user, ?int $schoolId): int
    {
        return \App\Services\ToshiActionService::getRemainingBudget($user->id, $schoolId);
    }

    /**
     * Consume one unit from the daily budget.
     * Delegates to ToshiActionService (single source of truth).
     */
    public function consumeBudget(User $user, ?int $schoolId): bool
    {
        return \App\Services\ToshiActionService::consumeBudget($user->id, $schoolId);
    }
}
