<?php

namespace App\Services\WhatsApp;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppPendingConfirmation;
use App\Models\WhatsAppUser;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Tools\Request;

/**
 * WhatsApp confirmation bridge — opaque tokens for Approve/Reject.
 *
 * Buttons: ty_{token} / tn_{token}
 * Text:    YES|NO {token}  (same token)
 *
 * Resumes native Approvable via Decision::approve()/reject(), or Tier-2 via bypassConfirm.
 * Payroll and impersonation tools are refused (web-only).
 */
class WhatsAppConfirmationBridge
{
    public const EXPIRED_MESSAGE = 'This request has expired. Please ask again.';

    public const TTL_MINUTES = 15;

    public const TOKEN_LENGTH = 8;

    /** Alphabet avoids 0/O/1/l/i for mobile typeability. */
    private const TOKEN_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /**
     * Tools that must never be confirmed or executed over WhatsApp.
     *
     * @var list<string>
     */
    public const BLOCKED_TOOLS = [
        'toolAccountantManagePayroll',
        'ManagePayrollTool',
        'toolImpersonateSchoolAdmin',
        'ImpersonateSchoolAdminTool',
    ];

    /**
     * Tier-2 confirm key → tool class (mirrors AgentToshi::TOOL_CLASS_MAP, minus blocked).
     *
     * @var array<string, class-string>
     */
    private const TIER2_TOOL_MAP = [
        'toolCreateExam' => \App\AiAgents\Tools\CreateExamTool::class,
        'toolAddParent' => \App\AiAgents\Tools\AddParentTool::class,
        'toolEnterMark' => \App\AiAgents\Tools\EnterMarkTool::class,
        'toolAddStudent' => \App\AiAgents\Tools\AddStudentTool::class,
        'toolAddTeacher' => \App\AiAgents\Tools\AddTeacherTool::class,
        'toolAddCoAdmin' => \App\AiAgents\Tools\AddCoAdminTool::class,
        'toolCreateFee' => \App\AiAgents\Tools\CreateFeeTool::class,
        'toolCreateTerm' => \App\AiAgents\Tools\CreateTermTool::class,
        'toolRecordPayment' => \App\AiAgents\Tools\RecordPaymentTool::class,
        'toolRecordAttendance' => \App\AiAgents\Tools\RecordAttendanceTool::class,
        'toolRecordBulkAttendance' => \App\AiAgents\Tools\RecordBulkAttendanceTool::class,
        'toolAssignTeacher' => \App\AiAgents\Tools\AssignTeacherTool::class,
        'toolCreateSubject' => \App\AiAgents\Tools\CreateSubjectTool::class,
        'toolSeedDefaultGrading' => \App\AiAgents\Tools\SeedDefaultGradingTool::class,
        'toolSetGradingScale' => \App\AiAgents\Tools\SetGradingScaleTool::class,
        'toolSetCurriculum' => \App\AiAgents\Tools\SetCurriculumTool::class,
        'toolCreateStream' => \App\AiAgents\Tools\CreateStreamTool::class,
        'toolAssignStudentsToStream' => \App\AiAgents\Tools\AssignStudentsToStreamTool::class,
        'toolCreateNotice' => \App\AiAgents\Tools\CreateNoticeTool::class,
        'toolUpdateNotice' => \App\AiAgents\Tools\UpdateNoticeTool::class,
        'toolCreateEvent' => \App\AiAgents\Tools\CreateEventTool::class,
        'toolUpdateEvent' => \App\AiAgents\Tools\UpdateEventTool::class,
        'toolCreateHoliday' => \App\AiAgents\Tools\CreateHolidayTool::class,
        'toolUpdateHoliday' => \App\AiAgents\Tools\UpdateHolidayTool::class,
        'toolTeacherMarkAttendance' => \App\AiAgents\Tools\Teacher\MarkAttendanceTool::class,
        'toolTeacherEnterMarks' => \App\AiAgents\Tools\Teacher\EnterMarksTool::class,
        'toolTeacherCreateLessonPlan' => \App\AiAgents\Tools\Teacher\CreateLessonPlanTool::class,
        'toolTeacherCreateAssignment' => \App\AiAgents\Tools\Teacher\CreateAssignmentTool::class,
        'toolTeacherCreateHomework' => \App\AiAgents\Tools\Teacher\CreateHomeworkTool::class,
        'toolTeacherApplyLeave' => \App\AiAgents\Tools\Teacher\ApplyLeaveTool::class,
        'toolTeacherCreateClassWallPost' => \App\AiAgents\Tools\Teacher\CreateClassWallPostTool::class,
        'toolTeacherCreateTask' => \App\AiAgents\Tools\Teacher\CreateTaskTool::class,
        'toolAccountantRecordPayment' => \App\AiAgents\Tools\Accountant\RecordPaymentTool::class,
        'toolAccountantCreateTask' => \App\AiAgents\Tools\Accountant\CreateTaskTool::class,
        'toolLibrarianManageBooks' => \App\AiAgents\Tools\Librarian\ManageBooksTool::class,
        'toolLibrarianManageBookCategories' => \App\AiAgents\Tools\Librarian\ManageBookCategoriesTool::class,
        'toolLibrarianManageLending' => \App\AiAgents\Tools\Librarian\ManageLendingTool::class,
        'toolLibrarianCreateTask' => \App\AiAgents\Tools\Librarian\CreateTaskTool::class,
        'toolReceptionistManageVisitorLog' => \App\AiAgents\Tools\Receptionist\ManageVisitorLogTool::class,
        'toolReceptionistManageCallLog' => \App\AiAgents\Tools\Receptionist\ManageCallLogTool::class,
        'toolReceptionistManagePostalRecord' => \App\AiAgents\Tools\Receptionist\ManagePostalRecordTool::class,
        'toolReceptionistCreateTask' => \App\AiAgents\Tools\Receptionist\CreateTaskTool::class,
        'toolStudentSubmitAssignment' => \App\AiAgents\Tools\Student\SubmitAssignmentTool::class,
        'toolStudentSubmitHomework' => \App\AiAgents\Tools\Student\SubmitHomeworkTool::class,
        'toolStudentManageTasks' => \App\AiAgents\Tools\Student\ManageTasksTool::class,
        'toolStudentManageConversations' => \App\AiAgents\Tools\Student\ManageConversationsTool::class,
    ];

    public function __construct(
        protected WhatsAppBusinessService $whatsApp,
    ) {}

    /**
     * Create a pending confirmation and send Approve/Reject buttons + YES/NO body copy.
     *
     * @param  array{
     *     conversation_id?: string|null,
     *     approval_id?: string|null,
     *     agent_class?: class-string|null,
     *     tool?: string|null,
     *     args?: array<string, mixed>|null,
     *     preview?: string|null,
     *     school_id?: int|null,
     * }  $context
     */
    public function sendConfirmation(
        string $phone,
        User $user,
        string $mechanism,
        array $context = [],
        int $ttlMinutes = self::TTL_MINUTES,
    ): WhatsAppPendingConfirmation {
        if (! in_array($mechanism, [
            WhatsAppPendingConfirmation::MECHANISM_APPROVABLE,
            WhatsAppPendingConfirmation::MECHANISM_TIER2,
        ], true)) {
            throw new InvalidArgumentException("Unknown confirmation mechanism: {$mechanism}");
        }

        $toolName = (string) ($context['tool'] ?? '');
        if ($this->isBlockedTool($toolName)) {
            throw new InvalidArgumentException("Tool [{$toolName}] is not available over WhatsApp.");
        }

        if ($mechanism === WhatsAppPendingConfirmation::MECHANISM_APPROVABLE) {
            if (blank($context['conversation_id'] ?? null) || blank($context['approval_id'] ?? null) || blank($context['agent_class'] ?? null)) {
                throw new InvalidArgumentException('Approvable confirmations require conversation_id, approval_id, and agent_class.');
            }
        }

        if ($mechanism === WhatsAppPendingConfirmation::MECHANISM_TIER2) {
            if (blank($toolName) || ! is_array($context['args'] ?? null)) {
                throw new InvalidArgumentException('Tier-2 confirmations require tool and args.');
            }
            if (! isset(self::TIER2_TOOL_MAP[$toolName])) {
                throw new InvalidArgumentException("Unknown Tier-2 tool: {$toolName}");
            }
        }

        $token = $this->generateUniqueToken();
        $preview = (string) ($context['preview'] ?? 'Confirm this action?');

        $pending = WhatsAppPendingConfirmation::query()->create([
            'token' => $token,
            'phone' => $phone,
            'user_id' => $user->id,
            'mechanism' => $mechanism,
            'conversation_id' => $context['conversation_id'] ?? null,
            'approval_id' => $context['approval_id'] ?? null,
            'agent_class' => $context['agent_class'] ?? null,
            'payload' => $mechanism === WhatsAppPendingConfirmation::MECHANISM_TIER2
                ? [
                    'tool' => $toolName,
                    'args' => $context['args'],
                    'preview' => $preview,
                    'school_id' => $context['school_id'] ?? $user->school_id,
                ]
                : array_filter([
                    'tool' => $toolName !== '' ? $toolName : null,
                    'school_id' => $context['school_id'] ?? $user->school_id,
                ]),
            'preview' => $preview,
            'status' => WhatsAppPendingConfirmation::STATUS_PENDING,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        $body = $this->buildConfirmationBody($preview, $token);
        $result = $this->whatsApp->sendInteractiveButtons(
            $phone,
            $body,
            [
                ['id' => "ty_{$token}", 'title' => 'Approve'],
                ['id' => "tn_{$token}", 'title' => 'Reject'],
            ],
            'toshi_confirm',
            $user->id,
        );

        if (! empty($result['message_id'])) {
            $pending->update(['outbound_wamid' => $result['message_id']]);
        }

        return $pending->fresh();
    }

    /**
     * Parse button id or coded YES/NO text into a decision.
     *
     * @return array{token: string, approved: bool}|null
     */
    public function parseReply(string $body): ?array
    {
        $trimmed = trim($body);

        if (preg_match('/^ty_([a-z0-9]{6,16})$/i', $trimmed, $m)) {
            return ['token' => strtolower($m[1]), 'approved' => true];
        }

        if (preg_match('/^tn_([a-z0-9]{6,16})$/i', $trimmed, $m)) {
            return ['token' => strtolower($m[1]), 'approved' => false];
        }

        if (preg_match('/^\s*(yes|approve|y)\s+([a-z0-9]{6,16})\s*$/i', $trimmed, $m)) {
            return ['token' => strtolower($m[2]), 'approved' => true];
        }

        if (preg_match('/^\s*(no|reject|n)\s+([a-z0-9]{6,16})\s*$/i', $trimmed, $m)) {
            return ['token' => strtolower($m[2]), 'approved' => false];
        }

        return null;
    }

    /**
     * Handle inbound body if it is a confirmation reply.
     *
     * @return bool true when the message was consumed (matched confirm pattern)
     */
    public function handleInbound(WhatsAppUser $waUser, string $phone, string $body): bool
    {
        $decision = $this->parseReply($body);

        if ($decision === null) {
            return false;
        }

        $userId = (int) $waUser->user_id;

        $pending = WhatsAppPendingConfirmation::query()
            ->where('token', $decision['token'])
            ->first();

        // Missing, already resolved, expired, or identity mismatch → fail closed.
        if (
            $pending === null
            || $pending->status !== WhatsAppPendingConfirmation::STATUS_PENDING
            || $pending->isExpired()
            || $pending->phone !== $phone
            || (int) $pending->user_id !== $userId
        ) {
            if ($pending !== null
                && $pending->status === WhatsAppPendingConfirmation::STATUS_PENDING
                && $pending->isExpired()
                && $pending->phone === $phone
                && (int) $pending->user_id === $userId
            ) {
                $pending->update([
                    'status' => WhatsAppPendingConfirmation::STATUS_EXPIRED,
                    'resolved_at' => now(),
                ]);
            }

            $this->whatsApp->sendText($phone, self::EXPIRED_MESSAGE, 'toshi_confirm_expired', $userId);

            return true;
        }

        $claimed = $this->claimPending($pending, $decision['approved']);

        if (! $claimed) {
            $this->whatsApp->sendText($phone, self::EXPIRED_MESSAGE, 'toshi_confirm_expired', $userId);

            return true;
        }

        $user = User::query()->findOrFail($userId);
        $reply = $this->resume($claimed, $user, $decision['approved']);

        $this->whatsApp->sendText(
            $phone,
            $reply,
            $decision['approved'] ? 'toshi_confirm_approved' : 'toshi_confirm_rejected',
            $userId,
        );

        return true;
    }

    public function isBlockedTool(string $toolName): bool
    {
        if ($toolName === '') {
            return false;
        }

        foreach (self::BLOCKED_TOOLS as $blocked) {
            if (strcasecmp($toolName, $blocked) === 0) {
                return true;
            }
        }

        return false;
    }

    public function buildConfirmationBody(string $preview, string $token): string
    {
        $preview = trim($preview);
        $hint = "Reply YES {$token} or NO {$token}\nOr tap a button below.";

        $body = $preview === ''
            ? "Confirm this action?\n{$hint}"
            : "Confirm: {$preview}\n{$hint}";

        return mb_substr($body, 0, 1024);
    }

    public function generateUniqueToken(): string
    {
        do {
            $token = '';
            $max = strlen(self::TOKEN_ALPHABET) - 1;
            for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
                $token .= self::TOKEN_ALPHABET[random_int(0, $max)];
            }
        } while (WhatsAppPendingConfirmation::query()->where('token', $token)->exists());

        return $token;
    }

    protected function claimPending(WhatsAppPendingConfirmation $pending, bool $approved): ?WhatsAppPendingConfirmation
    {
        return DB::transaction(function () use ($pending, $approved) {
            $locked = WhatsAppPendingConfirmation::query()
                ->whereKey($pending->id)
                ->lockForUpdate()
                ->first();

            if (
                $locked === null
                || $locked->status !== WhatsAppPendingConfirmation::STATUS_PENDING
                || $locked->isExpired()
            ) {
                if ($locked !== null && $locked->status === WhatsAppPendingConfirmation::STATUS_PENDING && $locked->isExpired()) {
                    $locked->update([
                        'status' => WhatsAppPendingConfirmation::STATUS_EXPIRED,
                        'resolved_at' => now(),
                    ]);
                }

                return null;
            }

            $locked->update([
                'status' => $approved
                    ? WhatsAppPendingConfirmation::STATUS_APPROVED
                    : WhatsAppPendingConfirmation::STATUS_REJECTED,
                'resolved_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    protected function resume(WhatsAppPendingConfirmation $pending, User $user, bool $approved): string
    {
        Auth::login($user);

        try {
            if ($pending->mechanism === WhatsAppPendingConfirmation::MECHANISM_APPROVABLE) {
                return $this->resumeApprovable($pending, $user, $approved);
            }

            return $this->resumeTier2($pending, $user, $approved);
        } catch (\Throwable $e) {
            Log::error('WhatsApp confirmation resume failed', [
                'token' => $pending->token,
                'mechanism' => $pending->mechanism,
                'error' => $e->getMessage(),
            ]);

            return $approved
                ? '❌ Could not complete the approved action. Please try again from the app.'
                : '❌ Could not record the rejection. Please try again from the app.';
        } finally {
            Auth::logout();
        }
    }

    protected function resumeApprovable(WhatsAppPendingConfirmation $pending, User $user, bool $approved): string
    {
        $agentClass = $pending->agent_class;
        $conversationId = $pending->conversation_id;
        $approvalId = $pending->approval_id;

        if (! is_string($agentClass) || ! class_exists($agentClass) || blank($conversationId) || blank($approvalId)) {
            return '❌ This confirmation is incomplete. Please ask again.';
        }

        $decision = $approved
            ? Decision::approve()
            : Decision::reject('Rejected via WhatsApp');

        $response = (new $agentClass)
            ->continue($conversationId, as: $user)
            ->prompt(Decisions::from([
                $approvalId => $decision,
            ]));

        $text = trim((string) ($response->text ?? ''));

        if ($text !== '') {
            return $text;
        }

        return $approved ? '✅ Approved.' : '❌ Rejected.';
    }

    protected function resumeTier2(WhatsAppPendingConfirmation $pending, User $user, bool $approved): string
    {
        $payload = $pending->payload ?? [];
        $toolName = (string) ($payload['tool'] ?? '');
        $args = is_array($payload['args'] ?? null) ? $payload['args'] : [];
        $schoolId = $payload['school_id'] ?? $user->school_id;
        $school = $schoolId ? School::query()->find($schoolId) : null;

        if ($this->isBlockedTool($toolName)) {
            return '❌ This action is not available over WhatsApp.';
        }

        if (! $approved) {
            ToshiAuditService::logCancellation(
                user: $user,
                school: $school,
                toolName: $toolName,
                arguments: $args,
                actingUser: $user,
            );

            return '❌ Cancelled.';
        }

        $class = self::TIER2_TOOL_MAP[$toolName] ?? null;
        if ($class === null) {
            return "❌ Unknown tool: {$toolName}";
        }

        $tool = app($class);
        $request = new Request($args);

        ToshiActionService::$bypassConfirm = true;
        try {
            $result = (string) $tool->handle($request);

            if (
                str_starts_with($result, '✅')
                && $tool instanceof \App\AiAgents\Concerns\VerifiableTool
            ) {
                $verification = $tool->verify($request);
                if (! ($verification['verified'] ?? false)) {
                    $result = '⚠️ The action appeared to succeed, but verification could not confirm the data was saved. '
                        .($verification['message'] ?? 'You may want to check manually.');
                }
            }

            ToshiAuditService::logExecution(
                user: $user,
                school: $school,
                toolName: $toolName,
                arguments: $args,
                result: $result,
                approver: $user,
                actingUser: $user,
            );

            return $result;
        } finally {
            ToshiActionService::$bypassConfirm = false;
        }
    }
}
