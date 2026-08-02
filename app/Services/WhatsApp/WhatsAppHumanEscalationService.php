<?php

namespace App\Services\WhatsApp;

use App\Helpers\SiteHelper;
use App\Models\School;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\ToshiAuditService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;

/**
 * Explicit-intent WhatsApp human escalation (≠ confirmation bridge / Approvable).
 *
 * Trigger: small exact/keyword phrase set (substring, case-insensitive) — see PHRASES.
 * Effects: ToshiAuditService row (dual identity), optional Task for receiver,
 * optional staff WhatsApp notify, ack to requester. Tool-calling does not continue
 * for that turn because WhatsAppToshiChannelService::ask() returns the ack before
 * prompting the agent.
 *
 * MVP gaps (honest): no helpdesk table, no SLA, no live-chat transfer; if the
 * receiver has no opted-in WhatsAppUser, only ActivityLog + Task remain.
 */
class WhatsAppHumanEscalationService
{
    /**
     * Exact / keyword phrases (matched as case-insensitive substrings).
     *
     * FP risk: casual mentions containing these substrings (e.g. "is there a real person
     * at the gate?") may escalate. FN risk: paraphrases like "can I speak with staff"
     * or "connect me to reception" are not covered until the phrase set grows.
     *
     * @var list<string>
     */
    public const PHRASES = [
        'talk to a person',
        'talk to a human',
        'speak to someone',
        'speak to a person',
        'talk to someone',
        'human agent',
        'real person',
    ];

    public const ACK_MESSAGE = 'A staff member will follow up with you shortly. Toshi is pausing automated tool actions for this message.';

    public const TOOL_NAME = 'WhatsAppHumanEscalation';

    public function __construct(
        private readonly WhatsAppBusinessService $whatsApp,
    ) {}

    public function matches(string $body): bool
    {
        $normalized = mb_strtolower(trim($body));
        if ($normalized === '') {
            return false;
        }

        foreach (self::PHRASES as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * If the body matches an escalation phrase, persist handoff artifacts and
     * return the requester ack. Returns null when the message is not an escalation.
     */
    public function tryEscalate(WhatsAppUser $whatsAppUser, string $body): ?string
    {
        if (! $this->matches($body)) {
            return null;
        }

        $user = $whatsAppUser->user;
        if (! $user) {
            return null;
        }

        $receiver = $this->resolveReceiver($user);
        $school = $user->school_id ? School::find($user->school_id) : null;
        $snippet = mb_substr(trim($body), 0, 280);

        $taskId = null;
        if ($receiver !== null) {
            $taskId = $this->createEscalationTask($receiver, $user, $snippet)?->id;
            $this->notifyReceiver($receiver, $user, $snippet, $whatsAppUser->phone);
        }

        ToshiAuditService::logEscalation(
            user: $user,
            school: $school,
            arguments: [
                'phone' => $whatsAppUser->phone,
                'snippet' => $snippet,
                'requester_usergroup_id' => (int) $user->usergroup_id,
                'receiver_user_id' => $receiver?->id,
                'receiver_usergroup_id' => $receiver ? (int) $receiver->usergroup_id : null,
                'task_id' => $taskId,
                'notify_sent' => $receiver !== null,
            ],
            result: $receiver
                ? self::ACK_MESSAGE
                : 'Escalation logged only (school admin requester — no self-notify loop).',
            actingUser: $user,
            approver: null,
        );

        Log::info('WhatsApp Toshi human escalation', [
            'acting_user_id' => $user->id,
            'receiver_user_id' => $receiver?->id,
            'task_id' => $taskId,
            'school_id' => $user->school_id,
        ]);

        return self::ACK_MESSAGE;
    }

    /**
     * Routing (school-local):
     * - Parent (7) / Student (6) → Receptionist (10), fallback School Admin (3)
     * - Teacher / Librarian / Accountant / Receptionist → School Admin (3)
     * - School Admin (3) → null (log only; avoid paging the same phone)
     */
    public function resolveReceiver(User $requester): ?User
    {
        $ug = (int) $requester->usergroup_id;
        $schoolId = $requester->school_id;
        if (! $schoolId) {
            return null;
        }

        if ($ug === 3) {
            return null;
        }

        if (in_array($ug, [6, 7], true)) {
            $receptionist = $this->firstActiveSchoolUser((int) $schoolId, 10);
            if ($receptionist) {
                return $receptionist;
            }

            return $this->firstActiveSchoolUser((int) $schoolId, 3, excludeId: (int) $requester->id);
        }

        return $this->firstActiveSchoolUser((int) $schoolId, 3, excludeId: (int) $requester->id);
    }

    private function firstActiveSchoolUser(int $schoolId, int $usergroupId, ?int $excludeId = null): ?User
    {
        $query = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', $usergroupId)
            ->orderBy('id');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    private function createEscalationTask(User $receiver, User $requester, string $snippet): ?Task
    {
        try {
            return Task::create([
                'school_id' => $receiver->school_id,
                'academic_year_id' => optional(SiteHelper::getAcademicYear($receiver->school_id))->id,
                'user_id' => $receiver->id,
                'title' => 'WA escalation: '.($requester->name ?? 'WhatsApp user'),
                'type' => 'self',
                'to_do_list' => "Requester: {$requester->name} (ug{$requester->usergroup_id}, id {$requester->id})\nMessage: {$snippet}",
                'task_date' => now()->toDateString(),
                'reminder' => 'one_day_before_the_task',
                'task_status' => 0,
                'task_flag' => 2,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp escalation Task create failed', [
                'receiver_user_id' => $receiver->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function notifyReceiver(User $receiver, User $requester, string $snippet, string $requesterPhone): void
    {
        $wa = WhatsAppUser::query()
            ->where('user_id', $receiver->id)
            ->where('opted_in', true)
            ->first();

        if (! $wa) {
            return;
        }

        $message = "Toshi escalation from {$requester->name} ({$requesterPhone}):\n{$snippet}";

        try {
            $this->whatsApp->sendText(
                $wa->phone,
                $message,
                'toshi_human_escalation',
                $receiver->id,
            );
        } catch (\Throwable $e) {
            Log::warning('WhatsApp escalation staff notify failed', [
                'receiver_user_id' => $receiver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
