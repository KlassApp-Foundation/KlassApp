<?php

namespace App\Services\WhatsApp;

use App\AiAgents\AccountantOperationsAgent;
use App\AiAgents\LibrarianOperationsAgent;
use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\ReceptionistOperationsAgent;
use App\AiAgents\StudentOperationsAgent;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\AiAgents\WhatsApp\WhatsAppReadOnlyAgent;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Enums\ToshiScope;
use App\Models\User;
use App\Models\WhatsAppPendingConfirmation;
use App\Models\WhatsAppUser;
use App\Services\Toshi\ToshiAvailabilityGate;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;

/**
 * WhatsApp → Toshi channel (reads + wave-1 allowlisted task writes).
 *
 * Extends the keyword router: only unmatched free-form text reaches here.
 * Maps WhatsAppUser → role OperationsAgent (mirrors ToshiSdkV2Service + Parent).
 * Structural filtering via WhatsAppWriteExclusion / WhatsAppReadOnlyAgent.
 *
 * Wave-1 writes: WRITE_ALLOWLIST tools may return __tier2_confirm; ask() then
 * dispatches via existing WhatsAppConfirmationBridge::sendConfirmation (no new bridge).
 * Inbound Approve/Reject is handled by the bridge (controller + tryHandlePendingApproval).
 */
class WhatsAppToshiChannelService
{
    /**
     * ask() return sentinel: confirmation buttons already sent via the bridge;
     * the webhook must not fall through to "unknown keyword".
     */
    public const CONFIRMATION_DISPATCHED = '__toshi_wa_confirm_dispatched__';

    public function __construct(
        private readonly ToshiAvailabilityGate $availabilityGate,
        private readonly WhatsAppConfirmationBridge $confirmationBridge,
        private readonly WhatsAppHumanEscalationService $humanEscalation,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('toshi.whatsapp_channel_enabled', false)
            && (bool) config('toshi.sdk_v2_enabled', false);
    }

    public function isAvailableFor(User $user): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        // ug4 (deputy) has no Toshi surface — fail closed.
        if ((int) $user->usergroup_id === 4) {
            return false;
        }

        if (! $this->resolveAgentClass((int) $user->usergroup_id)) {
            return false;
        }

        if (empty(config('ai.providers.openai-compatible.key'))) {
            return false;
        }

        return $this->availabilityGate->allows($user, ToshiScope::School, $user->school_id);
    }

    /**
     * Track 2 — pending approval (button ty_/tn_ ids or typed YES/NO coded reply).
     * Delegates to WhatsAppConfirmationBridge (no new bridge logic).
     */
    public function tryHandlePendingApproval(WhatsAppUser $whatsAppUser, string $phone, string $body): bool
    {
        return $this->confirmationBridge->handleInbound($whatsAppUser, $phone, $body);
    }

    /**
     * Ask Toshi for unmatched free-form text.
     *
     * Returns reply text, CONFIRMATION_DISPATCHED when a wave-1 write was
     * routed to the confirmation bridge, or null on miss/failure.
     *
     * Explicit human-escalation phrases are checked before the agent tool loop
     * so that turn never continues autonomous tool use. Escalation does not
     * require an AI provider key (only channel + role eligibility).
     */
    public function ask(WhatsAppUser $whatsAppUser, string $query): ?string
    {
        $user = $whatsAppUser->user;
        if (! $user || ! $this->isEnabled()) {
            return null;
        }

        // ug4 (deputy) has no Toshi surface — fail closed (matches isAvailableFor).
        if ((int) $user->usergroup_id === 4) {
            return null;
        }

        if (! $this->resolveAgentClass((int) $user->usergroup_id)) {
            return null;
        }

        // Explicit human handoff — halt tool-calling for this turn (≠ confirmation).
        // Runs before AI-key availability so escalation still works without a provider.
        $escalationAck = $this->humanEscalation->tryEscalate($whatsAppUser, $query);
        if ($escalationAck !== null) {
            return $escalationAck;
        }

        if (! $this->isAvailableFor($user)) {
            return null;
        }

        $agent = $this->makeAgent($user);
        if (! $agent) {
            return null;
        }

        $this->bindAuth($user);

        try {
            Log::info('WhatsApp Toshi channel: dispatching', [
                'whatsapp_user_id' => $whatsAppUser->id,
                'acting_user_id' => $user->id,
                'usergroup_id' => $user->usergroup_id,
                'agent' => $agent instanceof WhatsAppReadOnlyAgent
                    ? $agent->innerAgentClass()
                    : $agent::class,
                'query' => substr($query, 0, 100),
            ]);

            // Reset side-channel before each query (mirrors ToshiSdkV2Service).
            ToshiActionService::$pendingConfirmPayload = null;

            if (method_exists($agent, 'run')) {
                $text = $agent->run($query);
            } else {
                $text = $agent->prompt($query)->text;
            }

            $dispatched = $this->dispatchTier2ConfirmationIfPending($whatsAppUser, $user);
            if ($dispatched) {
                return self::CONFIRMATION_DISPATCHED;
            }

            // Fallback: parse __tier2_confirm from response text if side-channel missed.
            if (is_string($text) && $this->dispatchTier2ConfirmationFromText($whatsAppUser, $user, $text)) {
                return self::CONFIRMATION_DISPATCHED;
            }

            return is_string($text) ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Toshi channel failed', [
                'whatsapp_user_id' => $whatsAppUser->id,
                'acting_user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            Auth::logout();
            ToshiActionService::$pendingConfirmPayload = null;
        }
    }

    /**
     * If a ConfirmsBeforeWrite tool stored a pending payload, send Approve/Reject
     * via the existing confirmation bridge. Returns true when dispatched.
     */
    public function dispatchTier2ConfirmationIfPending(WhatsAppUser $whatsAppUser, User $user): bool
    {
        $payload = ToshiActionService::$pendingConfirmPayload;
        if ($payload === null || ! isset($payload['tool'])) {
            return false;
        }

        ToshiActionService::$pendingConfirmPayload = null;

        return $this->sendTier2Confirmation($whatsAppUser, $user, $payload);
    }

    /**
     * @param  array{tool: string, args?: array<string, mixed>, preview?: string}  $payload
     */
    public function sendTier2Confirmation(WhatsAppUser $whatsAppUser, User $user, array $payload): bool
    {
        $toolName = (string) ($payload['tool'] ?? '');
        if ($toolName === '' || $this->confirmationBridge->isBlockedTool($toolName)) {
            return false;
        }

        $this->confirmationBridge->sendConfirmation(
            $whatsAppUser->phone,
            $user,
            WhatsAppPendingConfirmation::MECHANISM_TIER2,
            [
                'tool' => $toolName,
                'args' => is_array($payload['args'] ?? null) ? $payload['args'] : [],
                'preview' => (string) ($payload['preview'] ?? 'Confirm this action?'),
                'school_id' => $user->school_id,
            ],
        );

        return true;
    }

    /**
     * Build the WhatsApp-scoped agent for a user (write exclusion applied).
     */
    public function makeAgent(User $user): ?Agent
    {
        $ug = (int) $user->usergroup_id;

        // ug4 — no agent
        if ($ug === 4) {
            return null;
        }

        $inner = match ($ug) {
            3 => new SchoolAdminWhatsAppReadAgent,
            5 => new TeacherOperationsAgent,
            6 => new StudentOperationsAgent,
            7 => new ParentOperationsAgent,
            8 => new LibrarianOperationsAgent,
            10 => new ReceptionistOperationsAgent,
            11 => new AccountantOperationsAgent,
            default => null,
        };

        if (! $inner) {
            return null;
        }

        // Parent agent is already read-only by construction; still wrap for
        // consistent channel suffix + future-proof filtering.
        if ($inner instanceof HasTools) {
            return new WhatsAppReadOnlyAgent($inner);
        }

        return $inner;
    }

    /**
     * @return class-string|null
     */
    public function resolveAgentClass(int $usergroupId): ?string
    {
        return match ($usergroupId) {
            3 => SchoolAdminWhatsAppReadAgent::class,
            5 => TeacherOperationsAgent::class,
            6 => StudentOperationsAgent::class,
            7 => ParentOperationsAgent::class,
            8 => LibrarianOperationsAgent::class,
            10 => ReceptionistOperationsAgent::class,
            11 => AccountantOperationsAgent::class,
            default => null,
        };
    }

    /**
     * Tools exposed on the WhatsApp path for this user (after write exclusion).
     *
     * @return list<class-string>
     */
    public function exposedToolClasses(User $user): array
    {
        $agent = $this->makeAgent($user);
        if (! $agent instanceof HasTools) {
            return [];
        }

        return collect($agent->tools())->map(fn ($t) => $t::class)->values()->all();
    }

    /**
     * Whether a tool class is invocable on the WhatsApp channel for this user.
     *
     * @param  class-string  $toolClass
     */
    public function canInvokeTool(User $user, string $toolClass): bool
    {
        if (! WhatsAppWriteExclusion::allowsClass($toolClass)) {
            return false;
        }

        return in_array($toolClass, $this->exposedToolClasses($user), true);
    }

    /**
     * @param  array{tool?: string, args?: array<string, mixed>, preview?: string}  $decoded
     */
    private function dispatchTier2ConfirmationFromText(WhatsAppUser $whatsAppUser, User $user, string $text): bool
    {
        $trimmed = ltrim($text);
        if (! str_starts_with($trimmed, '{"__tier2_confirm')) {
            return false;
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded) || empty($decoded['__tier2_confirm']) || empty($decoded['tool'])) {
            return false;
        }

        return $this->sendTier2Confirmation($whatsAppUser, $user, [
            'tool' => (string) $decoded['tool'],
            'args' => is_array($decoded['args'] ?? null) ? $decoded['args'] : [],
            'preview' => (string) ($decoded['preview'] ?? 'Confirm this action?'),
        ]);
    }

    private function bindAuth(User $user): void
    {
        Auth::login($user);
        request()->setUserResolver(static fn () => $user);
    }
}
