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
use App\Models\WhatsAppUser;
use App\Services\Toshi\ToshiAvailabilityGate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;

/**
 * WhatsApp → Toshi channel (Track 1 / Part B read-only).
 *
 * Extends the keyword router: only unmatched free-form text reaches here.
 * Maps WhatsAppUser → role OperationsAgent (mirrors ToshiSdkV2Service + Parent).
 * Structural write exclusion via WhatsAppWriteExclusion / WhatsAppReadOnlyAgent.
 *
 * Track 2 hook: tryHandlePendingApproval() — confirmation bridge
 * (ty_/tn_ buttons + YES/NO coded token) is intentionally not implemented here.
 */
class WhatsAppToshiChannelService
{
    public function __construct(
        private readonly ToshiAvailabilityGate $availabilityGate,
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
     * Track 2 hook — pending approval (button ty_/tn_ ids or typed YES/NO coded reply).
     * Always returns false in Track 1 so free-form can proceed to Toshi reads.
     */
    public function tryHandlePendingApproval(WhatsAppUser $whatsAppUser, string $phone, string $body): bool
    {
        // TODO(Track 2): resolve opaque token from ty_/tn_ button ids or YES/NO coded reply,
        // verify phone + user_id, resume Approvable / Tier-2 bypassConfirm, reply, STOP.
        unset($whatsAppUser, $phone, $body);

        return false;
    }

    /**
     * Ask Toshi for unmatched free-form text. Returns reply text or null on miss/failure.
     */
    public function ask(WhatsAppUser $whatsAppUser, string $query): ?string
    {
        $user = $whatsAppUser->user;
        if (! $user || ! $this->isAvailableFor($user)) {
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

            if (method_exists($agent, 'run')) {
                return $agent->run($query);
            }

            $response = $agent->prompt($query);

            return $response->text;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Toshi channel failed', [
                'whatsapp_user_id' => $whatsAppUser->id,
                'acting_user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            Auth::logout();
        }
    }

    /**
     * Build the WhatsApp-scoped agent for a user (read-only wrapper applied).
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

    private function bindAuth(User $user): void
    {
        Auth::login($user);
        request()->setUserResolver(static fn () => $user);
    }
}
