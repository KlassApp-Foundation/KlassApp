<?php

namespace App\Services;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\Academics\Marks;
use App\Models\Academics\Exam;
use App\Models\FeesCategories;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppPendingNotification;
use App\Models\WhatsAppUser;
use Illuminate\Support\Facades\Log;

/**
 * OutboundWhatsAppService — Proactive notifications via WhatsApp.
 *
 * Composes and sends WhatsApp messages for business events:
 *   - Grade/results published
 *   - Fee reminders
 *
 * Relies on WhatsAppService for actual HTTP delivery.
 */
class OutboundWhatsAppService
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    // =====================================================================
    // Cost-optimised queue: send free within window, queue for later if cold
    // =====================================================================

    /**
     * Queue a notification for later delivery.
     *
     * @return WhatsAppPendingNotification
     */
    public function queueNotification(
        int $whatsappUserId,
        string $flowType,
        ?string $message = null,
        ?string $templateName = null,
        array $templateVariables = [],
        ?\Carbon\Carbon $sendAfter = null,
    ): WhatsAppPendingNotification {
        return WhatsAppPendingNotification::create([
            'whatsapp_user_id'   => $whatsappUserId,
            'flow_type'          => $flowType,
            'message'            => $message,
            'template_name'      => $templateName,
            'template_variables' => $templateVariables ?: null,
            'send_after'         => $sendAfter,
        ]);
    }

    /**
     * If the 24hr service window is open, send immediately (free).
     * Otherwise, queue for later delivery when a window opens.
     */
    private function queueOrSend(
        string $phone,
        ?int $userId,
        string $message,
        string $flowType,
        ?string $templateName = null,
        array $templateVariables = [],
    ): int {
        $whatsappUser = WhatsAppUser::findByPhone($phone);

        if ($whatsappUser && $this->whatsApp->isWithinServiceWindow($phone)) {
            // Window is open — send immediately (FREE)
            try {
                $this->whatsApp->sendText($phone, $message, $flowType, $userId);
                return 1;
            } catch (\Throwable $e) {
                Log::error("queueOrSend: immediate send failed for {$phone}", [
                    'error' => $e->getMessage(),
                    'flow'  => $flowType,
                ]);
                return 0;
            }
        }

        // Window closed — queue for later (avoid cold send cost)
        if ($whatsappUser) {
            $this->queueNotification(
                whatsappUserId: $whatsappUser->id,
                flowType: $flowType,
                message: $templateName ? null : $message,
                templateName: $templateName,
                templateVariables: $templateVariables,
            );
            Log::info("queueOrSend: queued {$flowType} for {$phone} (window closed)");
            return 0;
        }

        // No WhatsApp user linked — can't queue, can't send
        Log::warning("queueOrSend: no linked user for {$phone}");
        return 0;
    }

    /**
     * Send all pending notifications for a user immediately.
     * Call this inside handleInbound() after the user sends a message —
     * their reply opens the 24hr window, so all queued items send FREE.
     *
     * @param  WhatsAppUser $user  The user whose queue to flush
     * @return int                 Number of notifications sent
     */
    public function flushPending(WhatsAppUser $user): int
    {
        $pending = WhatsAppPendingNotification::where('whatsapp_user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('send_after')
                  ->orWhere('send_after', '<=', now());
            })
            ->get();

        if ($pending->isEmpty()) {
            return 0;
        }

        $sent = 0;
        foreach ($pending as $notification) {
            try {
                if ($notification->template_name) {
                    $this->whatsApp->sendTemplate(
                        $user->phone,
                        $notification->template_name,
                        $notification->template_variables ?? [],
                        'utility',
                        $user->user_id,
                    );
                } elseif ($notification->message) {
                    $this->whatsApp->sendText(
                        $user->phone,
                        $notification->message,
                        $notification->flow_type,
                        $user->user_id,
                    );
                } else {
                    $notification->delete();
                    continue;
                }
                $notification->delete();
                $sent++;
            } catch (\Throwable $e) {
                Log::error("flushPending: failed for {$user->phone}", [
                    'notification_id' => $notification->id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0) {
            Log::info("flushPending: sent {$sent} pending notifications to {$user->phone}");
        }

        return $sent;
    }

    /**
     * Flush pending notifications for ALL WhatsApp users who have an open window.
     * Called by the cron when a batch window is available.
     *
     * @param  int $limit Max users to process
     * @return int Total notifications sent
     */
    public function flushAllOpenWindows(int $limit = 50): int
    {
        $users = WhatsAppUser::optedIn()
            ->where('last_inbound_at', '>=', now()->subHours(24))
            ->limit($limit)
            ->get();

        $total = 0;
        foreach ($users as $user) {
            $total += $this->flushPending($user);
        }

        return $total;
    }

    /**
     * Send queued notifications whose send_after deadline has passed
     * (cold sends — these cost $0.004 each).
     *
     * @param  int $limit Max pending records to process
     * @return int Total cold sends made
     */
    public function sendExpiredQueue(int $limit = 100): int
    {
        $pending = WhatsAppPendingNotification::where('send_after', '<=', now())
            ->whereNotNull('send_after')
            ->with('whatsappUser')
            ->limit($limit)
            ->get();

        $sent = 0;
        foreach ($pending as $notification) {
            $user = $notification->whatsappUser;
            if (!$user) {
                $notification->delete();
                continue;
            }

            try {
                if ($notification->template_name) {
                    $this->whatsApp->sendTemplate(
                        $user->phone,
                        $notification->template_name,
                        $notification->template_variables ?? [],
                        'utility',
                        $user->user_id,
                    );
                } elseif ($notification->message) {
                    $this->whatsApp->sendText(
                        $user->phone,
                        $notification->message,
                        $notification->flow_type,
                        $user->user_id,
                    );
                }
                $notification->delete();
                $sent++;
            } catch (\Throwable $e) {
                Log::error("sendExpiredQueue: failed", [
                    'notification_id' => $notification->id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0) {
            Log::info("sendExpiredQueue: cold-sent {$sent} expired notifications");
        }

        return $sent;
    }

    // =====================================================================
    //  Public notification methods — now use queueOrSend
    // =====================================================================

    /**
     * Notify parents when a student's exam results are published.
     *
     * @param  int   $studentId
     * @param  int   $examId
     * @return int   Number of messages sent
     */
    public function notifyGradesPublished(int $studentId, int $examId): int
    {
        $student = User::with(['studentAcademic.standard'])->find($studentId);
        if (!$student) {
            return 0;
        }

        $exam = Exam::with(['marks' => function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        }, 'examType'])->find($examId);

        if (!$exam || $exam->marks->isEmpty()) {
            return 0;
        }

        $message = $this->composeGradesMessage($student, $exam);
        $sent = 0;

        foreach ($this->getParentPhones($student) as $phone) {
            $sent += $this->queueOrSend($phone, $studentId, $message, 'grades');
        }

        return $sent;
    }

    /**
     * Notify parents about overdue or upcoming fee payments.
     *
     * @param  int      $studentId
     * @param  string   $type      'reminder' | 'overdue'
     * @param  int|null $feeId     Specific fee category or all
     * @return int      Number of messages sent
     */
    public function notifyFeeReminder(int $studentId, string $type = 'reminder', ?int $feeId = null): int
    {
        $student = User::with(['studentAcademic.standard'])->find($studentId);
        if (!$student) {
            return 0;
        }

        $fees = FeesCategories::where('school_id', $student->school_id)
            ->where('standard_id', $student->studentAcademic?->standard_id)
            ->when($feeId, fn ($q) => $q->where('id', $feeId))
            ->get();

        if ($fees->isEmpty()) {
            return 0;
        }

        $message = $this->composeFeeMessage($student, $fees, $type);
        $sent = 0;

        foreach ($this->getParentPhones($student) as $phone) {
            $sent += $this->queueOrSend($phone, $studentId, $message, 'fee_reminder');
        }

        return $sent;
    }

    /**
     * Get WhatsApp phone numbers for a student's parents.
     *
     * @return string[]
     */
    public function getParentPhones(User $student): array
    {
        $phones = [];

        // Load the parent relationship
        $parents = $student->parents()
            ->wherePivot('school_id', $student->school_id)
            ->get();

        foreach ($parents as $parent) {
            $waUser = WhatsAppUser::optedIn()
                ->where('user_id', $parent->id)
                ->first();

            if ($waUser?->phone) {
                $phones[] = $waUser->phone;
            }

            // Fallback: if parent has a mobile_no in User but no WhatsAppUser yet
            if (!$waUser && $parent->mobile_no) {
                $normalised = WhatsAppPhoneHelper::normalise($parent->mobile_no);
                if (WhatsAppPhoneHelper::validate($normalised)) {
                    $phones[] = $normalised;
                }
            }
        }

        return array_unique($phones);
    }

    /**
     * Notify parents with a comprehensive grades report across all subjects.
     * Called when the LAST subject's marks are entered for a student.
     *
     * @param  int   $studentId
     * @param  int   $examId     The triggering exam (last subject completed)
     * @return int   Number of messages sent
     */
    public function notifyComprehensiveGrades(int $studentId, int $examId): int
    {
        $student = User::with(['studentAcademic.standard'])->find($studentId);
        if (!$student) {
            return 0;
        }

        $triggerExam = Exam::find($examId);
        if (!$triggerExam) {
            return 0;
        }

        // Find all exams for this period (same exam type + term + class)
        $periodExams = Exam::with(['examType', 'subject'])
            ->where('standard_id', $triggerExam->standard_id)
            ->where('exam_type_id', $triggerExam->exam_type_id)
            ->where('academic_term_id', $triggerExam->academic_term_id)
            ->where('school_id', $triggerExam->school_id)
            ->get();

        if ($periodExams->isEmpty()) {
            return 0;
        }

        // Get all marks for this student across those exams
        $examIds = $periodExams->pluck('id');
        $marks = Marks::whereIn('exam_id', $examIds)
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('exam_id');

        $rows = [];
        foreach ($periodExams as $exam) {
            $mark = $marks->get($exam->id);
            $subjectName = $exam->subject->name ?? 'Unknown';
            if ($mark) {
                $grade = $mark->grade ?? 'N/A';
                $score = $mark->marks ?? 'N/A';
                $rows[] = "• {$subjectName}: {$score} ({$grade})";
            } else {
                $rows[] = "• {$subjectName}: —";
            }
        }

        $examTypeName = $triggerExam->examType?->name ?? 'Exam';
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        $message = WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "{$examTypeName} Results — {$className}",
            $rows,
            'All subjects completed! Check the KlassApp portal for details.'
        );

        $sent = 0;
        foreach ($this->getParentPhones($student) as $phone) {
            $sent += $this->queueOrSend($phone, $studentId, $message, 'grades');
        }

        return $sent;
    }

    /**
     * Compose a formatted grades message for WhatsApp.
     */
    protected function composeGradesMessage(User $student, Exam $exam): string
    {
        $rows = [];
        foreach ($exam->marks as $mark) {
            $grade = $mark->grade ?? 'N/A';
            $score = $mark->marks_obtained ?? 0;
            $total = $mark->marks_total ?? 100;
            $rows[] = "• {$mark->subject_name}: {$score}/{$total} ({$grade})";
        }

        $examName = $exam->name ?? $exam->examType?->name ?? 'Exam';
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        return WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "{$examName} Results — {$className}",
            $rows,
            'Check the KlassApp parent portal for full details.'
        );
    }

    /**
     * Compose a formatted fee reminder message for WhatsApp.
     */
    protected function composeFeeMessage(User $student, iterable $fees, string $type): string
    {
        $title = $type === 'overdue'
            ? '⚠️ Fee Payment Overdue'
            : '📋 Fee Payment Reminder';

        $rows = [];
        $total = 0;
        foreach ($fees as $fee) {
            $rows[] = "• {$fee->name}: UGX " . number_format($fee->amount, 0) . ($fee->due_date ? " (due: {$fee->due_date})" : '');
            $total += $fee->amount;
        }
        $rows[] = '';
        $rows[] = "*Total: UGX " . number_format($total, 0) . "*";

        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        return WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "{$title} — {$className}",
            $rows,
            'Contact the school finance office for payment details.'
        );
    }
}
