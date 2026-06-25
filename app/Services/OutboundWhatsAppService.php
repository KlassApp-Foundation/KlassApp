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
 * Uses WhatsAppBusinessService (Meta Cloud API) exclusively.
 */
class OutboundWhatsAppService
{
    public function __construct(
        protected WhatsAppBusinessService $businessApi,
    ) {}

    // =====================================================================
    //  Direct sends via Business API
    // =====================================================================

    /**
     * Send text via Business API.
     */
    protected function sendText(
        string $phone,
        string $message,
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        return $this->businessApi->sendText($phone, $message, $flowType, $userId);
    }

    /**
     * Send interactive buttons via Business API.
     */
    protected function sendButtons(
        string $phone,
        string $message,
        array $buttons,
        ?string $title = null,
        ?string $footer = null,
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        return $this->businessApi->sendInteractiveButtons(
            $phone, $message, $buttons, $flowType, $userId
        );
    }

    /**
     * Send interactive list via Business API (native list message).
     */
    public function sendList(
        string $phone,
        string $title,
        array $sections,
        ?string $description = null,
        ?string $footerText = null,
        string $buttonText = 'View Options',
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        return $this->businessApi->sendList(
            phone: $phone,
            title: $title,
            sections: $sections,
            description: $description ?? '',
            footerText: $footerText ?? '',
            buttonText: $buttonText,
            flowType: $flowType,
            userId: $userId,
        );
    }

    /**
     * Send template via Business API.
     */
    protected function sendTemplate(
        string $phone,
        string $templateName,
        array $variables = [],
        string $category = 'utility',
    ): array {
        return $this->businessApi->sendTemplate(
            $phone,
            $templateName,
            $variables,
            null, // language — uses config default
            $category,
        );
    }

    /**
     * Check service window via Business API.
     */
    protected function isWithinServiceWindow(string $phone): bool
    {
        return $this->businessApi->isWithinServiceWindow($phone);
    }

    // =====================================================================
    //  Cost-optimised queue: send free within window, queue for later if cold
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

        if ($whatsappUser && $this->isWithinServiceWindow($phone)) {
            // Window is open — send immediately (FREE)
            try {
                $this->sendText($phone, $message, $flowType, $userId);
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
                    $this->sendTemplate(
                        $user->phone,
                        $notification->template_name,
                        $notification->template_variables ?? [],
                        'utility',
                    );
                } elseif ($notification->message) {
                    $this->sendText(
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
                    $this->sendTemplate(
                        $user->phone,
                        $notification->template_name,
                        $notification->template_variables ?? [],
                        'utility',
                    );
                } elseif ($notification->message) {
                    $this->sendText(
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
                $remark = $mark->remarks ?? '';
                $rows[] = "{$subjectName}: {$score} ({$grade})" . ($remark ? " — {$remark}" : '');
            } else {
                $rows[] = "{$subjectName}: Not yet available";
            }
        }

        $message = "📊 *Results Published*\n\n"
            . "Student: *{$student->name}*\n"
            . "Class: *{($student->studentAcademic?->standard?->name) ?? 'N/A'}*\n"
            . "Exam: *{$triggerExam->name}*\n\n"
            . implode("\n", $rows);

        $sent = 0;
        foreach ($this->getParentPhones($student) as $phone) {
            $sent += $this->queueOrSend($phone, $studentId, $message, 'comprehensive_grades');
        }

        return $sent;
    }

    // =====================================================================
    //  Message composers — each produces a distinct, scannable format
    // =====================================================================

    /**
     * Compose a grade results message with celebration and class rank.
     */
    protected function composeGradesMessage(User $student, Exam $exam): string
    {
        $rows = [];
        $totalScore = 0;
        $subjectCount = 0;
        foreach ($exam->marks as $mark) {
            $grade = $mark->grade ?? 'N/A';
            $score = $mark->marks_obtained ?? 0;
            $totalScore += $score;
            $subjectCount++;
            $total = $mark->marks_total ?? 100;
            $rows[] = "• {$mark->subject_name}: {$score}/{$total} ({$grade})";
        }

        $examName = $exam->name ?? $exam->examType?->name ?? 'Exam';
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $avg = $subjectCount > 0 ? round($totalScore / $subjectCount, 1) : 0;

        // Class rank calculation
        $rank = null;
        $totalStudents = null;
        if ($exam->id) {
            $allScores = \Illuminate\Support\Facades\DB::table('marks')
                ->where('exam_id', $exam->id)
                ->where('student_id', '!=', $student->id)
                ->select('student_id', \Illuminate\Support\Facades\DB::raw('SUM(marks_obtained) as total'))
                ->groupBy('student_id')
                ->get()
                ->pluck('total');
            $totalStudents = $allScores->count() + 1;
            $rank = $allScores->filter(fn($s) => $s > $totalScore)->count() + 1;
        }

        $message = "📊 *{$examName} — {$className}*\n";
        $message .= "_{$student->name}_\n\n";
        $message .= implode("\n", $rows);

        if ($subjectCount > 0) {
            $message .= "\n· · · · · · · · · · · · · · · ·\n";
            $message .= "_Average: {$avg}%_";
            if ($rank && $totalStudents) {
                $ordinal = match ($rank) {1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'};
                $message .= " | #{$rank}{$ordinal} of {$totalStudents}";
            }
            $message .= "\n";

            if ($avg >= 80) {
                $message .= "🎉 *Well done, {$student->name}!* ";
                $message .= "{$avg}% average is a strong performance";
                if ($rank && $totalStudents) {
                    $message .= " — ranked {$rank}{$ordinal} out of {$totalStudents}";
                }
                $message .= ".\n";
            }
        }

        $message .= "\n_Check the KlassApp parent portal for full details._";
        return $message;
    }

    /**
     * Compose a fee reminder with visual separators.
     */
    protected function composeFeeMessage(User $student, iterable $fees, string $type): string
    {
        $title = $type === 'overdue'
            ? '⚠️ Fee Payment Overdue'
            : '💰 Fee Payment Reminder';

        $rows = [];
        $total = 0;
        foreach ($fees as $fee) {
            $rows[] = "• {$fee->name}: UGX " . number_format($fee->amount, 0) . ($fee->due_date ? " (due: {$fee->due_date})" : '');
            $total += $fee->amount;
        }

        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        $message = "{$title}\n";
        $message .= "_{$student->name} — {$className}_\n\n";
        $message .= implode("\n", $rows);
        $message .= "\n· · · · · · · · · · · · · · · ·\n";
        $message .= "*Total: UGX " . number_format($total, 0) . "*\n";
        $message .= "\n_Contact the school finance office for payment details._";
        return $message;
    }

    /**
     * Fee balance snapshot with paid/outstanding grouping.
     */
    public function composeFeeBalance(User $student, array $categories, float $totalPaid, float $totalBalance): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $paidLines = [];
        $outstandingLines = [];

        foreach ($categories as $cat) {
            $line = "• {$cat['name']}: UGX " . number_format($cat['amount'] ?? 0, 0);
            if (($cat['balance'] ?? 0) <= 0) {
                $paidLines[] = $line;
            } else {
                $outstandingLines[] = $line;
            }
        }

        $message = "💰 *Fee Balance — {$className}*\n_{$student->name}_\n\n";

        if (!empty($paidLines)) {
            $message .= "✅ *PAID*\n" . implode("\n", $paidLines) . "\n\n";
        }

        if (!empty($outstandingLines)) {
            $message .= "❌ *OUTSTANDING*\n" . implode("\n", $outstandingLines) . "\n\n";
        }

        $message .= "· · · · · · · · · · · · · · · ·\n";
        $message .= "💵 *Total Paid:* UGX " . number_format($totalPaid, 0) . "\n";
        $message .= "💰 *Balance:* UGX " . number_format($totalBalance, 0) . "\n";
        $message .= "\n_Reply PAY to pay via Mobile Money._";
        return $message;
    }

    /**
     * Attendance summary with contextual tone.
     */
    public function composeAttendance(User $student, int $present, int $absent, int $total, array $recentAbsences = []): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $rate = $total > 0 ? round(($present / $total) * 100) : 100;

        $message = "📅 *Attendance — {$className}*\n_{$student->name}_\n\n";

        if ($rate < 80) {
            $message .= "⚠️ *{$student->name} has missed {$absent} school day(s)* — ";
            $message .= "that's " . (100 - $rate) . "% of learning time lost.\n\n";
        } elseif ($rate > 90) {
            $message .= "✅ *Great attendance!* {$present} out of {$total} days present.\n\n";
        }

        $message .= "✅ Present: {$present}\n";
        $message .= "❌ Absent: {$absent}\n";
        $message .= "📊 Rate: {$rate}%\n";

        if (!empty($recentAbsences)) {
            $message .= "\n· · · · · · · · · · · · · · · ·\n";
            $message .= "Recent absences:\n";
            foreach (array_slice($recentAbsences, 0, 5) as $a) {
                $message .= "  • {$a['date']}" . ($a['reason'] ? " — {$a['reason']}" : '') . "\n";
            }
        }

        $message .= "\n_Contact the school to report a reason._";
        return $message;
    }

    /**
     * Grades overview with celebration for strong performance.
     */
    public function composeGradesOverview(User $student, string $examName, array $subjects): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $rows = [];
        $totalScore = 0;

        foreach ($subjects as $sub) {
            $rows[] = "• {$sub['name']}: {$sub['score']}/{$sub['total']} ({$sub['grade']})";
            $totalScore += $sub['score'] ?? 0;
        }

        $avg = count($subjects) > 0 ? round($totalScore / count($subjects), 1) : 0;

        $message = "📊 *{$examName} — {$className}*\n_{$student->name}_\n\n";
        $message .= implode("\n", $rows);
        $message .= "\n· · · · · · · · · · · · · · · ·\n";
        $message .= "_Average: {$avg}%_\n";

        if ($avg >= 80) {
            $message .= "🎉 *Well done, {$student->name}!* Strong results across the board.\n";
        }

        $message .= "\n_Reply REPORT for the full report card._";
        return $message;
    }

    /**
     * Health record with structured formatting.
     */
    public function composeHealthRecord(User $student, array $records): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $rows = [];

        foreach (array_slice($records, 0, 5) as $record) {
            $date = $record['date'] ?? 'N/A';
            $type = $record['type'] ?? 'Checkup';
            $notes = $record['notes'] ?? '';
            $rows[] = "• {$date} — {$type}" . ($notes ? "\n  " . $notes : '');
        }

        $message = "🏥 *Health Records — {$className}*\n_{$student->name}_\n\n";
        $message .= implode("\n", $rows);

        $footer = count($records) > 5
            ? "\n\n_Showing last 5 records. Reply ALLHEALTH for full history._"
            : "\n\n_Reply ALLHEALTH for full history._";
        $message .= $footer;
        return $message;
    }

    /**
     * Student withdrawal notification.
     */
    public function composeStudentWithdrawn(User $student, string $withdrawalDate, string $reason = '', string $destination = ''): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $message = "📋 *Record Update — Student Withdrawn*\n_{$student->name} — {$className}_\n\n";
        $message .= "• Date of departure: {$withdrawalDate}\n";
        if ($reason) {
            $message .= "• Reason: {$reason}\n";
        }
        if ($destination) {
            $message .= "• Destination: {$destination}\n";
        }
        $message .= "\n· · · · · · · · · · · · · · · ·\n";
        $message .= "Academic records and transfer documents are available on KlassApp.\n";
        $message .= "\n_Reply RECORDS to request official documents._";
        return $message;
    }

    /**
     * Term opening notification.
     */
    public function composeTermOpens(string $schoolName, string $term, string $openingDate, string $reportingTime = '', string $requirements = ''): string
    {
        $message = "📅 *{$term} Begins*\n_{$schoolName}_\n\n";
        $message .= "• Opening date: {$openingDate}\n";
        if ($reportingTime) {
            $message .= "• Reporting time: {$reportingTime}\n";
        }
        if ($requirements) {
            $message .= "• Requirements: {$requirements}\n";
        }
        $message .= "\nPlease ensure your child reports on time.\n";
        $message .= "\n_Reply TERMDATES for the full academic calendar._";
        return $message;
    }

    /**
     * Term closing notification.
     */
    public function composeTermCloses(string $schoolName, string $term, string $closingDate, string $closingTime = '', string $reopeningDate = ''): string
    {
        $message = "📅 *{$term} Closes*\n_{$schoolName}_\n\n";
        $message .= "• Closing date: {$closingDate}\n";
        if ($closingTime) {
            $message .= "• Closing time: {$closingTime}\n";
        }
        if ($reopeningDate) {
            $message .= "• School reopens: {$reopeningDate}\n";
        }
        $message .= "\nReport cards will be available via KlassApp.\n";
        $message .= "\n_Reply REPORT to view results early._";
        return $message;
    }
}
