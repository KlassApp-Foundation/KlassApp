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
 * Dual-transport: tries WhatsAppBusinessService (Meta Cloud API) first,
 * falls back to WhatsAppService (Evolution API) if Business API fails
 * or isn't configured.
 */
class OutboundWhatsAppService
{
    public function __construct(
        protected WhatsAppService $whatsApp,
        protected WhatsAppBusinessService $businessApi,
    ) {}

    // =====================================================================
    // Dual-transport helpers: Business API first, Evolution fallback
    // =====================================================================

    /**
     * Send text via Business API if configured, fall back to Evolution.
     */
    protected function sendTextDual(
        string $phone,
        string $message,
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        if ($this->businessApi->isConfigured()) {
            $result = $this->businessApi->sendText($phone, $message, $flowType, $userId);
            if ($result['success']) {
                return $result;
            }
            Log::warning("Business API send failed, falling back to Evolution", [
                'phone' => $phone,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }

        return $this->whatsApp->sendText($phone, $message, $flowType, $userId);
    }

    /**
     * Send interactive buttons via Business API if configured, fall back to Evolution.
     */
    protected function sendButtonsDual(
        string $phone,
        string $message,
        array $buttons,
        ?string $title = null,
        ?string $footer = null,
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        if ($this->businessApi->isConfigured()) {
            // Business API uses interactive reply buttons
            $result = $this->businessApi->sendInteractiveButtons(
                $phone, $message, $buttons, $title, $footer, $flowType, $userId
            );
            if ($result['success']) {
                return $result;
            }
            Log::warning("Business API buttons send failed, falling back to Evolution", [
                'phone' => $phone,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }

        return $this->whatsApp->sendButtons($phone, $message, $buttons, $title, $footer, $flowType, $userId);
    }

    /**
     * Send interactive List Message via Evolution API (Business API not yet supported).
     */
    public function sendListDual(
        string $phone,
        string $title,
        array $sections,
        ?string $description = null,
        ?string $footerText = null,
        string $buttonText = 'View Options',
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        // Business API does not yet support list messages; route directly to Evolution.
        return $this->whatsApp->sendList(
            phone: $phone,
            title: $title,
            sections: $sections,
            description: $description,
            footerText: $footerText,
            buttonText: $buttonText,
            flowType: $flowType,
            userId: $userId,
        );
    }

    /**
     * Send template via Business API if configured, fall back to Evolution.
     */
    protected function sendTemplateDual(
        string $phone,
        string $templateName,
        array $variables = [],
        string $category = 'utility',
        ?int $userId = null,
    ): array {
        if ($this->businessApi->isConfigured()) {
            // Business API: sendTemplate($phone, $name, $vars, $language, $flowType)
            $result = $this->businessApi->sendTemplate(
                $phone,
                $templateName,
                $variables,
                null, // language — uses config default
                $category,
            );
            if ($result['success']) {
                return $result;
            }
            Log::warning("Business API template send failed, falling back to Evolution", [
                'phone'    => $phone,
                'template' => $templateName,
                'error'    => $result['error'] ?? 'unknown',
            ]);
        }

        // Evolution: sendTemplate($phone, $name, $vars, $category, $userId)
        return $this->whatsApp->sendTemplate($phone, $templateName, $variables, $category, $userId);
    }

    /**
     * Check service window — Business API if configured, Evolution otherwise.
     */
    protected function isWithinServiceWindow(string $phone): bool
    {
        if ($this->businessApi->isConfigured()) {
            return $this->businessApi->isWithinServiceWindow($phone);
        }
        return $this->whatsApp->isWithinServiceWindow($phone);
    }

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

        if ($whatsappUser && $this->isWithinServiceWindow($phone)) {
            // Window is open — send immediately (FREE)
            try {
                $this->sendTextDual($phone, $message, $flowType, $userId);
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
                    $this->sendTemplateDual(
                        $user->phone,
                        $notification->template_name,
                        $notification->template_variables ?? [],
                        'utility',
                        $user->user_id,
                    );
                } elseif ($notification->message) {
                    $this->sendTextDual(
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
                    $this->sendTemplateDual(
                        $user->phone,
                        $notification->template_name,
                        $notification->template_variables ?? [],
                        'utility',
                        $user->user_id,
                    );
                } elseif ($notification->message) {
                    $this->sendTextDual(
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
            ? 'Fee Payment Overdue'
            : 'Fee Payment Reminder';

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

    // =====================================================================
    //  Free-form message builders (no Meta approval required, zero cost)
    //  These are used within the 24hr customer service window.
    // =====================================================================

    /**
     * Fee balance snapshot — parent texts FEES.
     */
    public function composeFeeBalance(User $student, array $categories, float $totalPaid, float $totalBalance): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $rows = [];

        foreach ($categories as $cat) {
            $status = ($cat['balance'] ?? 0) <= 0 ? 'Paid' : 'Outstanding';
            $rows[] = "• {$cat['name']}: UGX " . number_format($cat['amount'] ?? 0, 0)
                    . " — {$status}";
        }

        $rows[] = '';
        $rows[] = "*Total Paid:* UGX " . number_format($totalPaid, 0);
        $rows[] = "*Balance:* UGX " . number_format($totalBalance, 0);

        return WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "Fee Balance — {$className}",
            $rows,
            'Reply PAY to pay via Mobile Money.'
        );
    }

    /**
     * Attendance summary — parent texts ATTENDANCE.
     */
    public function composeAttendance(User $student, int $present, int $absent, int $total, array $recentAbsences = []): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        $lines = [];
        $lines[] = "This term: {$present} present, {$absent} absent out of {$total} days";
        $lines[] = "Attendance rate: " . ($total > 0 ? round(($present / $total) * 100) : 100) . "%";

        if (!empty($recentAbsences)) {
            $lines[] = '';
            $lines[] = '*Recent Absences:*';
            foreach (array_slice($recentAbsences, 0, 5) as $a) {
                $lines[] = "• {$a['date']}" . ($a['reason'] ? " — {$a['reason']}" : '');
            }
        }

        return WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "Attendance — {$className}",
            $lines,
            'Contact the school to report a reason.'
        );
    }

    /**
     * Grades overview — parent texts GRADES.
     * Shows latest exam results across all subjects.
     */
    public function composeGradesOverview(User $student, string $examName, array $subjects): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $rows = [];

        foreach ($subjects as $sub) {
            $rows[] = "• {$sub['name']}: {$sub['score']}/{$sub['total']} ({$sub['grade']})";
        }

        return WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "{$examName} — {$className}",
            $rows,
            'Reply REPORT for the full report card.'
        );
    }

    /**
     * Health record — parent texts HEALTH.
     */
    public function composeHealthRecord(User $student, array $records): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $rows = [];

        foreach (array_slice($records, 0, 5) as $r) {
            $rows[] = "• {$r['date']}: {$r['type']}";
            if (!empty($r['details'])) {
                $rows[] = "  {$r['details']}";
            }
        }

        if (empty($records)) {
            $rows[] = 'No health records on file.';
        }

        $footer = count($records) > 5
            ? 'Showing last 5 records. Reply ALLHEALTH for the full history.'
            : 'Reply ALLHEALTH for the full history.';

        return WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "Health Records — {$className}",
            $rows,
            $footer
        );
    }

    /**
     * Student withdrawal notification — student leaves/transfers mid-term.
     */
    public function composeStudentWithdrawn(User $student, string $withdrawalDate, string $reason = '', string $destination = ''): string
    {
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';
        $rows = [];

        $rows[] = "Date of departure: {$withdrawalDate}";
        if ($reason) {
            $rows[] = "Reason: {$reason}";
        }
        if ($destination) {
            $rows[] = "Destination: {$destination}";
        }
        $rows[] = '';
        $rows[] = 'Academic records and transfer documents are available on KlassApp.';

        return WhatsAppPhoneHelper::formatMessage(
            $student->name,
            "Student Withdrawn — {$className}",
            $rows,
            'Reply RECORDS to request official documents.'
        );
    }

    /**
     * Term opens — beginning of term notification.
     */
    public function composeTermOpens(string $schoolName, string $term, string $openingDate, string $reportingTime = '', string $requirements = ''): string
    {
        $rows = [];
        $rows[] = "Opening date: {$openingDate}";
        if ($reportingTime) {
            $rows[] = "Reporting time: {$reportingTime}";
        }
        if ($requirements) {
            $rows[] = "Requirements: {$requirements}";
        }
        $rows[] = '';
        $rows[] = 'Please ensure your child reports on time.';

        return WhatsAppPhoneHelper::formatMessage(
            $schoolName,
            "{$term} Begins",
            $rows,
            'Reply TERMDATES for the full academic calendar.'
        );
    }

    /**
     * Term closes — end of term notification.
     */
    public function composeTermCloses(string $schoolName, string $term, string $closingDate, string $closingTime = '', string $reopeningDate = ''): string
    {
        $rows = [];
        $rows[] = "Closing date: {$closingDate}";
        if ($closingTime) {
            $rows[] = "Closing time: {$closingTime}";
        }
        if ($reopeningDate) {
            $rows[] = "School reopens: {$reopeningDate}";
        }
        $rows[] = '';
        $rows[] = 'Report cards will be available via KlassApp.';

        return WhatsAppPhoneHelper::formatMessage(
            $schoolName,
            "{$term} Closes",
            $rows,
            'Reply REPORT to view results early.'
        );
    }

    // =====================================================================
    //  Public notify methods using free-form messages
    // =====================================================================

    /**
     * Send fee balance to a parent's WhatsApp.
     */
    public function notifyFeeBalance(int $studentId, array $categories, float $totalPaid, float $totalBalance): int
    {
        $student = User::with(['studentAcademic.standard'])->find($studentId);
        if (!$student) {
            return 0;
        }

        $message = $this->composeFeeBalance($student, $categories, $totalPaid, $totalBalance);
        $sent = 0;

        foreach ($this->getParentPhones($student) as $phone) {
            $sent += $this->queueOrSend($phone, $studentId, $message, 'fee_balance');
        }

        return $sent;
    }

    /**
     * Send attendance summary to a parent's WhatsApp.
     */
    public function notifyAttendance(int $studentId, int $present, int $absent, int $total, array $recentAbsences = []): int
    {
        $student = User::with(['studentAcademic.standard'])->find($studentId);
        if (!$student) {
            return 0;
        }

        $message = $this->composeAttendance($student, $present, $absent, $total, $recentAbsences);
        $sent = 0;

        foreach ($this->getParentPhones($student) as $phone) {
            $sent += $this->queueOrSend($phone, $studentId, $message, 'attendance');
        }

        return $sent;
    }

    /**
     * Send student withdrawal notification to parents.
     */
    public function notifyStudentWithdrawn(int $studentId, string $withdrawalDate, string $reason = '', string $destination = ''): int
    {
        $student = User::with(['studentAcademic.standard'])->find($studentId);
        if (!$student) {
            return 0;
        }

        $message = $this->composeStudentWithdrawn($student, $withdrawalDate, $reason, $destination);
        $sent = 0;

        foreach ($this->getParentPhones($student) as $phone) {
            $sent += $this->queueOrSend($phone, $studentId, $message, 'student_withdrawn');
        }

        return $sent;
    }
}
