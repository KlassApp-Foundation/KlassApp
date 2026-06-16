<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api;

use App\Helpers\WhatsAppPhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppUser;
use App\Models\MessageDeliveryLog;
use App\Models\User;
use App\Models\StudentAcademic;
use App\Models\Attendance;
use App\Models\FeesCategories;
use App\Models\Events;
use App\Models\Academics\Marks;
use App\Models\Academics\Exam;
use App\Models\Academics\Classes;
use App\Services\OutboundWhatsAppService;
use App\Services\WhatsAppService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * WhatsApp API Controller
 *
 * Data-only endpoints for the WhatsApp layer (n8n → Typebot/Flowise → Laravel).
 * Authenticated via HMAC middleware — NOT Sanctum.
 * Laravel is a data API only; conversation state lives in Redis/Typebot.
 */
class WhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppBusinessService $businessApi,
    ) {}
    /**
     * Identify a WhatsApp user by phone number.
     *
     * n8n calls this when a new message arrives to resolve
     * the phone number to a KlassApp user + role + linked students.
     *
     * POST /api/whatsapp/identify-user
     * Body: { "phone": "+256701234567" }
     */
    public function identify(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = WhatsAppPhoneHelper::normalise($request->input('phone'));

        $whatsappUser = WhatsAppUser::with(['user.userprofile', 'user.school'])
            ->where('phone', $phone)
            ->first();

        if (!$whatsappUser) {
            return response()->json([
                'identified' => false,
                'message'    => 'Phone number not linked to any account. Please contact your school to link your WhatsApp number.',
            ], 200);
        }

        if (!$whatsappUser->opted_in) {
            return response()->json([
                'identified' => false,
                'message'    => 'You have opted out of WhatsApp notifications. Reply OPTIN to re-enable.',
            ], 200);
        }

        $user = $whatsappUser->user;
        $userType = $this->resolveUserType($user);
        $response = [
            'identified' => true,
            'user_id'    => $user->id,
            'name'       => $user->name,
            'user_type'  => $userType,
            'school_id'  => $user->school_id,
            'school_name' => $user->school?->name,
        ];

        // If parent, include linked children
        if ($userType === 'parent') {
            $children = $user->children()
                ->with(['studentAcademic.standard', 'studentAcademic.section'])
                ->get()
                ->map(function ($child) {
                    return [
                        'student_id'   => $child->id,
                        'maaif_id'     => $child->maaif_id ?? $child->id,
                        'name'         => $child->name,
                        'class'        => $child->studentAcademic?->standard?->name ?? 'N/A',
                        'section'      => $child->studentAcademic?->section?->name ?? 'N/A',
                    ];
                });

            $response['children'] = $children;
            $response['children_count'] = $children->count();
        }

        // If teacher, include linked classes
        if ($userType === 'teacher') {
            $teacherProfile = $user->teacherProfile;
            if ($teacherProfile) {
                $linkedStandards = $teacherProfile->standards()
                    ->with('sections')
                    ->get()
                    ->map(function ($standard) {
                        return [
                            'standard_id' => $standard->id,
                            'name'        => $standard->name,
                            'sections'    => $standard->sections->pluck('name')->toArray(),
                        ];
                    });

                $response['linked_classes'] = $linkedStandards;
            }
        }

        // Auto-register if not already in whatsapp_users table
        if (!$whatsappUser->verified_at) {
            $whatsappUser->update(['verified_at' => now()]);
        }

        return response()->json($response);
    }

    /**
     * Get student grades via WhatsApp.
     *
     * GET /api/whatsapp/student/{studentId}/grades?term=current
     */
    public function grades(string $studentId, Request $request)
    {
        $student = User::with(['studentAcademic.standard', 'studentAcademic.section'])
            ->where('id', $studentId)
            ->first();

        if (!$student) {
            return response()->json([
                'error'   => 'Student not found',
                'message' => 'No student found with ID: ' . $studentId,
            ], 404);
        }

        $term = $request->query('term', 'current');

        // Resolve academic term
        $examQuery = Exam::where('student_id', $studentId);

        if ($term === 'current') {
            // Get current academic year's latest exam
            $academicYear = \App\Helpers\SiteHelper::getAcademicYear($student->school_id);
            if ($academicYear) {
                $examQuery->where('academic_year_id', $academicYear->id);
            }
        }

        $exams = $examQuery->with(['marks', 'examType'])->latest()->take(5)->get();

        if ($exams->isEmpty()) {
            return response()->json([
                'student_name' => $student->name,
                'class'        => $student->studentAcademic?->standard?->name ?? 'N/A',
                'message'      => 'No results available yet for ' . $student->name . '.',
                'exams'        => [],
            ]);
        }

        $formattedExams = $exams->map(function ($exam) {
            $marks = $exam->marks->map(function ($mark) {
                return [
                    'subject' => $mark->subject_name ?? 'Unknown',
                    'score'   => $mark->marks_obtained,
                    'total'   => $mark->marks_total ?? 100,
                    'grade'   => $mark->grade,
                    'remark'  => $mark->remark,
                ];
            });

            return [
                'exam_name' => $exam->name ?? $exam->examType?->name ?? 'Exam',
                'term'      => $exam->term ?? 'N/A',
                'marks'     => $marks,
                'total'     => $marks->sum('score'),
                'average'   => $marks->avg('score') ? round($marks->avg('score'), 1) : 0,
                'position'  => $exam->position ?? 'N/A',
            ];
        });

        return response()->json([
            'student_name' => $student->name,
            'class'        => $student->studentAcademic?->standard?->name ?? 'N/A',
            'exams'        => $formattedExams,
        ]);
    }

    /**
     * Get student attendance via WhatsApp.
     *
     * GET /api/whatsapp/student/{studentId}/attendance?period=week|month|term
     */
    public function attendance(string $studentId, Request $request)
    {
        $student = User::find($studentId);

        if (!$student) {
            return response()->json([
                'error'   => 'Student not found',
                'message' => 'No student found with ID: ' . $studentId,
            ], 404);
        }

        $period = $request->query('period', 'week');

        $query = Attendance::where('student_id', $studentId);

        switch ($period) {
            case 'week':
                $query->where('date', '>=', Carbon::now()->subWeek());
                break;
            case 'month':
                $query->where('date', '>=', Carbon::now()->subMonth());
                break;
            case 'term':
                $academicYear = \App\Helpers\SiteHelper::getAcademicYear($student->school_id);
                if ($academicYear) {
                    $query->whereBetween('date', [
                        $academicYear->start_date ?? Carbon::now()->startOfYear(),
                        $academicYear->end_date ?? Carbon::now()->endOfYear(),
                    ]);
                }
                break;
        }

        $records = $query->orderBy('date', 'desc')->get();

        $totalDays = $records->count();
        $presentDays = $records->where('status', 'present')->count();
        $absentDays = $records->where('status', 'absent')->count();
        $lateDays = $records->where('status', 'late')->count();
        $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

        $recentAbsent = $records->where('status', '!=', 'present')
            ->take(5)
            ->map(function ($record) {
                return [
                    'date'   => Carbon::parse($record->date)->format('D d M Y'),
                    'status' => ucfirst($record->status),
                    'remark' => $record->remark ?? '',
                ];
            });

        return response()->json([
            'student_name'    => $student->name,
            'class'           => $student->studentAcademic?->standard?->name ?? 'N/A',
            'period'          => $period,
            'total_days'      => $totalDays,
            'present'         => $presentDays,
            'absent'          => $absentDays,
            'late'            => $lateDays,
            'attendance_rate' => $attendanceRate . '%',
            'recent_absences' => $recentAbsent,
        ]);
    }

    /**
     * Get fee balance via WhatsApp.
     *
     * GET /api/whatsapp/fees/{studentId}/balance
     */
    public function feeBalance(string $studentId)
    {
        $student = User::find($studentId);

        if (!$student) {
            return response()->json([
                'error'   => 'Student not found',
                'message' => 'No student found with ID: ' . $studentId,
            ], 404);
        }

        $feeCategories = FeesCategories::where('school_id', $student->school_id)
            ->where('standard_id', function ($q) use ($student) {
                $q->select('standard_id')
                    ->from('student_academics')
                    ->where('student_id', $student->id)
                    ->latest()
                    ->limit(1);
            })
            ->get();

        $totalFees = $feeCategories->sum('amount');

        // TODO: Integrate with actual payment/transaction tables
        // For now, return the fee structure. Payment tracking should be
        // connected to your existing transaction/payroll tables.

        $fees = $feeCategories->map(function ($category) {
            return [
                'name'   => $category->name,
                'amount' => number_format($category->amount, 0),
                'due_by' => $category->due_date ?? 'N/A',
            ];
        });

        return response()->json([
            'student_name' => $student->name,
            'class'        => $student->studentAcademic?->standard?->name ?? 'N/A',
            'total_fees'   => number_format($totalFees, 0),
            'currency'     => 'UGX',
            'fee_breakdown' => $fees,
            'note'         => 'Contact school office for exact payment status.',
        ]);
    }

    /**
     * Get school events via WhatsApp.
     *
     * GET /api/whatsapp/school/{schoolId}/events?upcoming=1
     */
    public function schoolEvents(string $schoolId, Request $request)
    {
        $query = Events::where('school_id', $schoolId);

        if ($request->boolean('upcoming')) {
            $query->where('start_date', '>=', Carbon::today())
                ->orderBy('start_date', 'asc');
        } else {
            $query->orderBy('start_date', 'desc');
        }

        $events = $query->limit(20)->get()->map(function ($event) {
            return [
                'title'       => $event->title,
                'description' => $event->description ?? '',
                'start_date'  => Carbon::parse($event->start_date)->format('D d M Y'),
                'end_date'    => $event->end_date ? Carbon::parse($event->end_date)->format('D d M Y') : null,
                'time'        => $event->start_time ?? 'All day',
                'venue'       => $event->venue ?? 'TBA',
                'type'        => $event->event_type ?? 'general',
            ];
        });

        return response()->json([
            'school_id' => $schoolId,
            'events'    => $events,
            'count'     => $events->count(),
        ]);
    }

    /**
     * Mark attendance via WhatsApp (for teachers).
     *
     * POST /api/whatsapp/attendance/mark
     * Body: {
     *   "teacher_id": 123,
     *   "class_id": 45,
     *   "date": "2026-05-16",
     *   "absent_student_ids": [10, 15, 22]
     * }
     */
    public function markAttendance(Request $request)
    {
        $request->validate([
            'teacher_id'         => 'required|integer',
            'class_id'           => 'required|integer',
            'date'               => 'required|date',
            'absent_student_ids' => 'array',
            'absent_student_ids.*' => 'integer',
        ]);

        $teacherId = $request->input('teacher_id');
        $classId = $request->input('class_id');
        $date = $request->input('date');
        $absentIds = $request->input('absent_student_ids', []);

        // Get all students in this class
        $studentsInClass = StudentAcademic::where('standard_id', $classId)
            ->pluck('student_id');

        $marked = 0;
        $errors = [];

        // Mark present students
        $presentIds = $studentsInClass->diff($absentIds);

        foreach ($presentIds as $studentId) {
            try {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date'       => $date,
                    ],
                    [
                        'status'     => 'present',
                        'marked_by'  => $teacherId,
                    ]
                );
                $marked++;
            } catch (\Exception $e) {
                $errors[] = "Failed to mark student {$studentId} as present: " . $e->getMessage();
            }
        }

        // Mark absent students
        foreach ($absentIds as $studentId) {
            try {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date'       => $date,
                    ],
                    [
                        'status'     => 'absent',
                        'marked_by'  => $teacherId,
                    ]
                );
                $marked++;
            } catch (\Exception $e) {
                $errors[] = "Failed to mark student {$studentId} as absent: " . $e->getMessage();
            }
        }

        return response()->json([
            'success'       => empty($errors),
            'marked'        => $marked,
            'total'         => $studentsInClass->count(),
            'present'       => $presentIds->count(),
            'absent'        => count($absentIds),
            'date'          => $date,
            'class_id'      => $classId,
            'errors'        => $errors,
        ]);
    }

    /**
     * Receive delivery status webhooks from Evolution API / n8n.
     *
     * POST /api/whatsapp/delivery-webhook
     * Body: {
     *   "whatsapp_message_id": "msg_abc123",
     *   "status": "delivered|read|failed",
     *   "phone": "+256701234567"
     * }
     *
     * Tracks retries for failed messages and alerts after 3 consecutive failures.
     */
    public function deliveryWebhook(Request $request)
    {
        Log::info('WhatsApp delivery webhook received', $request->all());

        $request->validate([
            'whatsapp_message_id' => 'nullable|string',
            'phone'               => 'required|string',
            'direction'           => 'nullable|in:inbound,outbound',
            'status'              => 'required|in:sent,delivered,read,failed,received',
            'template_name'       => 'nullable|string',
            'error'               => 'nullable|string',
            'error_code'          => 'nullable|string',
        ]);

        // Inbound: create a new log entry (these are the messages that open the 24hr window)
        if ($request->input('direction') === 'inbound') {
            MessageDeliveryLog::create([
                'whatsapp_message_id' => $request->input('whatsapp_message_id'),
                'phone'               => $request->input('phone'),
                'direction'           => 'inbound',
                'status'              => 'received',
            ]);
            return response()->json(['status' => 'logged']);
        }

        // Outbound: update existing log from outbound-notifier
        $log = MessageDeliveryLog::where('whatsapp_message_id', $request->input('whatsapp_message_id'))->first();

        if (!$log) {
            Log::warning('WhatsApp delivery webhook: message not found', [
                'message_id' => $request->input('whatsapp_message_id'),
            ]);
            return response()->json(['error' => 'Message not found'], 404);
        }

        $status = $request->input('status');

        match ($status) {
            'delivered' => $log->markDelivered(),
            'read'      => $log->markRead(),
            'failed'    => $this->handleDeliveryFailure($log, $request),
            'sent'      => $log->update(['status' => 'sent']),
        };

        return response()->json(['status' => 'updated']);
    }

    /**
     * Handle a delivery failure with retry tracking and alerting.
     *
     * Counts consecutive failures per phone. After 3 failures,
     * logs a high-severity alert for operational review.
     */
    private function handleDeliveryFailure(MessageDeliveryLog $log, Request $request): void
    {
        $error = $request->input('error', 'Unknown error');
        $errorCode = $request->input('error_code', '');
        $phone = $request->input('phone');

        $log->markFailed($errorCode ? "[{$errorCode}] {$error}" : $error);

        // Count consecutive failures for this phone in the last hour
        $consecutiveFailures = MessageDeliveryLog::where('phone', $phone)
            ->where('direction', 'outbound')
            ->where('status', 'failed')
            ->where('sent_at', '>=', now()->subHour())
            ->count();

        Log::warning('WhatsApp delivery failure', [
            'phone'               => $phone,
            'message_id'          => $log->whatsapp_message_id,
            'error'               => $error,
            'error_code'          => $errorCode,
            'consecutive_failures' => $consecutiveFailures,
        ]);

        // Alert after 3+ consecutive failures in an hour — likely a systemic issue
        if ($consecutiveFailures >= 3 && $consecutiveFailures % 3 === 0) {
            Log::error('WHATSAPP DELIVERY: High failure rate detected', [
                'phone'                => $phone,
                'consecutive_failures' => $consecutiveFailures,
                'recent_error'         => $error,
                'alert_threshold'      => 3,
                'action_required'      => 'Check Evolution API status, phone validity, or WhatsApp account health.',
            ]);
        }
    }

    public function checkWindow(Request $request)
    {
        $phone = $request->query('phone');
        if (!$phone) {
            return response()->json(['error' => 'phone required'], 422);
        }

        $whatsappUser = WhatsAppUser::findByPhone($phone);
        $open = $whatsappUser && $whatsappUser->last_inbound_at
            && $whatsappUser->last_inbound_at->gt(now()->subHours(24));

        return response()->json([
            'window_open'     => $open,
            'phone'           => $phone,
            'last_inbound_at' => $whatsappUser?->last_inbound_at?->toIso8601String(),
        ]);
    }

    // =====================================================================
    // INBOUND WEBHOOK HANDLERS (Meta Cloud API + Evolution API)
    // =====================================================================

    /**
     * Handle Meta Cloud API webhook verification (GET) and inbound messages (POST).
     *
     * - GET:  Meta sends ?hub.mode=subscribe&hub.verify_token=...&hub.challenge=...
     *        Respond with hub.challenge if verify_token matches.
     * - POST: Incoming messages/statuses from either Meta Cloud API or Evolution API.
     *        Payload format is auto-detected and routed to the correct parser.
     */
    public function handleInbound(Request $request, WhatsAppService $whatsAppService)
    {
        // ── Meta Cloud API webhook verification (GET) ──
        if ($request->isMethod('get')) {
            // Parse raw query string manually — PHP's $_GET and parse_str()
            // both convert dots in param names to underscores (hub.mode →
            // hub_mode), which breaks Meta's hub.verify_token etc.
            $raw = $request->server('QUERY_STRING', '');
            $params = [];
            foreach (explode('&', $raw) as $pair) {
                $parts = explode('=', $pair, 2);
                if (count($parts) === 2) {
                    $params[urldecode($parts[0])] = urldecode($parts[1]);
                }
            }

            $mode = $params['hub.mode'] ?? null;
            $token = $params['hub.verify_token'] ?? null;
            $challenge = $params['hub.challenge'] ?? null;

            $expectedToken = config('services.whatsapp.business_verify_token', env('WHATSAPP_BUSINESS_VERIFY_TOKEN', 'klassapp_verify_2026'));

            if ($mode === 'subscribe' && $token === $expectedToken && $challenge) {
                Log::info('WhatsApp Business API: webhook verified');
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            Log::warning('WhatsApp Business API: webhook verification failed', [
                'mode'  => $mode,
                'token' => $token ? '(provided)' : '(empty)',
            ]);
            return response('Forbidden', 403);
        }

        // ── POST: incoming message or status update ──
        $allData = $request->all();

        // Detect payload source
        $isMetaPayload = isset($allData['object']) && $allData['object'] === 'whatsapp_business_account';

        if ($isMetaPayload) {
            return $this->handleMetaInbound($allData);
        }

        return $this->handleEvolutionInbound($request, $allData, $whatsAppService);
    }

    /**
     * Parse an inbound message or status from the Meta Cloud API.
     */
    protected function handleMetaInbound(array $payload)
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                // ── Incoming messages ──
                $messages = $value['messages'] ?? [];
                foreach ($messages as $msg) {
                    $phone = WhatsAppPhoneHelper::normalise($msg['from'] ?? '');
                    $messageId = $msg['id'] ?? '';
                    $msgType = $msg['type'] ?? '';
                    $body = '';

                    if ($msgType === 'text') {
                        $body = $msg['text']['body'] ?? '';
                    } elseif ($msgType === 'interactive') {
                        $body = $msg['interactive']['button_reply']['id']
                            ?? $msg['interactive']['list_reply']['id']
                            ?? '';
                    }

                    if (empty($phone) || empty($body)) {
                        continue;
                    }

                    // Log inbound message
                    MessageDeliveryLog::create([
                        'whatsapp_message_id' => $messageId,
                        'phone'               => $phone,
                        'direction'           => 'inbound',
                        'status'              => 'received',
                        'content_preview'     => \Illuminate\Support\Str::limit($body, 200),
                    ]);

                    // Track last inbound activity (24hr service window)
                    WhatsAppUser::where('phone', $phone)->update(['last_inbound_at' => now()]);

                    // ── Full reply flow (same as Evolution handler) ──
                    $this->processMetaMessage($phone, $body, $messageId);
                }

                // ── Delivery status updates ──
                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $status) {
                    $this->processMetaStatusUpdate($status);
                }
            }
        }

        // Meta expects 200 OK within 20 seconds
        return response()->json(['status' => 'ok']);
    }

    /**
     * Process a single Meta inbound message: identify user, route, flush queue.
     */
    protected function processMetaMessage(string $phone, string $body, string $messageId)
    {
        $whatsappUser = WhatsAppUser::with(['user.userprofile', 'user.school'])
            ->where('phone', $phone)
            ->first();

        if (!$whatsappUser) {
            // Use Business API if configured, otherwise Evolution
            if ($this->businessApi->isConfigured()) {
                $this->businessApi->sendText(
                    $phone,
                    "👋 Welcome to KlassApp!\n\nYour phone number is not linked to any school account. Please contact your school office to link your WhatsApp number.",
                    'unrecognized',
                );
            } else {
                app(WhatsAppService::class)->sendText(
                    $phone,
                    "👋 Welcome to KlassApp!\n\nYour phone number is not linked to any school account. Please contact your school office to link your WhatsApp number.",
                    'unrecognized',
                );
            }
            return;
        }

        if (!$whatsappUser->opted_in) {
            if ($this->businessApi->isConfigured()) {
                $this->businessApi->sendText(
                    $phone,
                    "You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                    'opted_out',
                );
            } else {
                app(WhatsAppService::class)->sendText(
                    $phone,
                    "You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                    'opted_out',
                );
            }
            return;
        }

        // Handle OPTIN/OPTOUT keywords
        $trimmedBody = strtolower(trim($body));
        if ($trimmedBody === 'optin') {
            $whatsappUser->update(['opted_in' => true]);
            if ($this->businessApi->isConfigured()) {
                $this->businessApi->sendText(
                    $phone,
                    "✅ You have opted in to WhatsApp notifications.\n\nSend *MENU* to see available options.",
                    'optin',
                    $whatsappUser->user_id,
                );
            } else {
                app(WhatsAppService::class)->sendText(
                    $phone,
                    "✅ You have opted in to WhatsApp notifications.\n\nSend *MENU* to see available options.",
                    'optin',
                    $whatsappUser->user_id,
                );
            }
            return;
        }

        if ($trimmedBody === 'optout') {
            $whatsappUser->update(['opted_in' => false]);
            if ($this->businessApi->isConfigured()) {
                $this->businessApi->sendText(
                    $phone,
                    "❌ You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                    'optout',
                    $whatsappUser->user_id,
                );
            } else {
                app(WhatsAppService::class)->sendText(
                    $phone,
                    "❌ You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                    'optout',
                    $whatsappUser->user_id,
                );
            }
            return;
        }

        // Route to appropriate handler
        $this->routeInbound($whatsappUser, $phone, $body, app(WhatsAppService::class));

        // Flush any queued notifications — parent's inbound just opened a free window
        $outboundService = app(OutboundWhatsAppService::class);
        $flushed = $outboundService->flushPending($whatsappUser);
        if ($flushed > 0) {
            Log::info("handleMetaInbound: flushed {$flushed} pending notifications for {$phone}");
        }
    }

    /**
     * Process a delivery status update from Meta Cloud API.
     */
    protected function processMetaStatusUpdate(array $status): void
    {
        $messageId = $status['id'] ?? '';
        $statusName = $status['status'] ?? '';
        $recipientId = $status['recipient_id'] ?? '';

        if (empty($messageId)) {
            return;
        }

        $log = MessageDeliveryLog::where('whatsapp_message_id', $messageId)->first();

        if (!$log) {
            Log::info('WhatsApp Business: status for unknown message', [
                'message_id' => $messageId,
                'status'     => $statusName,
            ]);
            return;
        }

        match ($statusName) {
            'sent'      => $log->update(['status' => 'sent']),
            'delivered' => $log->markDelivered(),
            'read'      => $log->markRead(),
            'failed'    => $log->markFailed($status['errors'][0]['title'] ?? 'Unknown error'),
            default     => $log->update(['status' => $statusName]),
        };

        Log::info('WhatsApp Business: status updated', [
            'message_id' => $messageId,
            'status'     => $statusName,
        ]);
    }

    /**
     * Parse an inbound message from Evolution API webhook.
     */
    protected function handleEvolutionInbound(Request $request, array $allData, WhatsAppService $whatsAppService)
    {
        Log::info('WhatsApp Evolution inbound received');

        $remoteJid = data_get($allData, 'data.key.remoteJid')
            ?? data_get($allData, 'key.remoteJid')
            ?? data_get($allData, 'data.remoteJid')
            ?? '';

        // Ignore group messages entirely
        if (str_contains($remoteJid, '@g.us')) {
            return response()->json(['status' => 'ignored', 'reason' => 'group message']);
        }

        // Ignore messages sent by the bot itself
        $fromMe = data_get($allData, 'data.key.fromMe', false);
        if ($fromMe) {
            return response()->json(['status' => 'ignored', 'reason' => 'own message']);
        }

        // Strip WhatsApp suffix: "256700000000@s.whatsapp.net" → "256700000000"
        $phone = preg_replace('/@.*$/', '', $remoteJid);

        if (empty($phone)) {
            return response()->json(['status' => 'ignored', 'reason' => 'no remoteJid']);
        }

        $phone = WhatsAppPhoneHelper::normalise($phone);

        $body = data_get($allData, 'data.message.conversation')
            ?? data_get($allData, 'data.message.extendedTextMessage.text')
            ?? data_get($allData, 'message.conversation')
            ?? '';

        $messageId = data_get($allData, 'data.key.id')
            ?? data_get($allData, 'key.id', '');

        if (empty($body)) {
            Log::info('WhatsApp inbound: empty message body, ignoring');
            return response()->json(['status' => 'ignored', 'reason' => 'empty body']);
        }

        // Log inbound message to delivery log (opens 24hr window)
        MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'direction'           => 'inbound',
            'status'              => 'received',
            'content_preview'     => \Illuminate\Support\Str::limit($body, 200),
        ]);

        // Track last inbound activity (opens 24hr service window)
        WhatsAppUser::where('phone', $phone)->update(['last_inbound_at' => now()]);

        // Identify user
        $whatsappUser = WhatsAppUser::with(['user.userprofile', 'user.school'])
            ->where('phone', $phone)
            ->first();

        if (!$whatsappUser) {
            $whatsAppService->sendText(
                $phone,
                "👋 Welcome to KlassApp!\n\nYour phone number is not linked to any school account. Please contact your school office to link your WhatsApp number.",
                'unrecognized',
            );
            return response()->json(['status' => 'sent_unrecognized']);
        }

        if (!$whatsappUser->opted_in) {
            $whatsAppService->sendText(
                $phone,
                "You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                'opted_out',
            );
            return response()->json(['status' => 'opted_out']);
        }

        // Handle OPTIN/OPTOUT keywords
        $trimmedBody = strtolower(trim($body));
        if ($trimmedBody === 'optin') {
            $whatsappUser->update(['opted_in' => true]);
            $whatsAppService->sendText(
                $phone,
                "✅ You have opted in to WhatsApp notifications.\n\nSend *MENU* to see available options.",
                'optin',
                $whatsappUser->user_id,
            );
            return response()->json(['status' => 'opted_in']);
        }

        if ($trimmedBody === 'optout') {
            $whatsappUser->update(['opted_in' => false]);
            $whatsAppService->sendText(
                $phone,
                "❌ You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                'optout',
                $whatsappUser->user_id,
            );
            return response()->json(['status' => 'opted_out']);
        }

        // Route to appropriate handler
        $this->routeInbound($whatsappUser, $phone, $body, $whatsAppService);

        // Flush any queued notifications — parent's inbound just opened a free window
        $outboundService = app(OutboundWhatsAppService::class);
        $flushed = $outboundService->flushPending($whatsappUser);
        if ($flushed > 0) {
            Log::info("handleInbound: flushed {$flushed} pending notifications for {$phone}");
        }

        return response()->json(['status' => 'routed']);
    }

    /**
     * Route inbound message to the appropriate handler based on keyword.
     *
     * @param WhatsAppUser $user The identified WhatsApp user
     * @param string $phone E.164 phone number
     * @param string $body Raw message body
     * @param WhatsAppService $whatsAppService
     */
    private function routeInbound(WhatsAppUser $user, string $phone, string $body, WhatsAppService $whatsAppService): void
    {
        $trimmed = strtolower(trim($body));
        $role = $user->user->usergroup_id;
        $hasChildren = $user->user->children()->exists();

        // Universal: menu/help
        if (in_array($trimmed, ['menu', 'help', 'start', 'options'])) {
            $this->sendMenu($user, $phone, $whatsAppService);
            return;
        }

        // Universal: optin / optout
        if ($trimmed === 'optin') {
            $user->update(['opted_in' => true]);
            $whatsAppService->sendText(
                $phone,
                "✅ You have re-enabled WhatsApp notifications.\n\nSend *MENU* to see available options.",
                'opted_in',
                $user->user_id,
            );
            Log::info("WhatsApp opt-in: user {$user->user_id}");
            return;
        }

        if (in_array($trimmed, ['optout', 'stop', 'unsubscribe'])) {
            $user->update(['opted_in' => false]);
            $whatsAppService->sendText(
                $phone,
                "You have been unsubscribed from WhatsApp notifications. Reply *OPTIN* to re-enable.",
                'opted_out',
                $user->user_id,
            );
            Log::info("WhatsApp opt-out: user {$user->user_id}");
            return;
        }

        // Universal: events (any role)
        if (in_array($trimmed, ['events', 'event', 'calendar', 'news'])) {
            $this->sendEvents($user, $phone, $whatsAppService);
            return;
        }

        // -- Role-specific routing --

        // SchoolAdmin (3)
        if ($role === 3) {
            if (in_array($trimmed, ['students', 'student', 'learner'])) {
                $this->sendStudentList($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['staff', 'teacher', 'employees'])) {
                $this->sendStaffList($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['exams', 'exam', 'marks', 'results', 'report'])) {
                $this->sendAdminExams($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['fees', 'fee', 'balance', 'payment', 'pay'])) {
                $this->sendAdminFees($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['reports', 'analytics', 'stats'])) {
                $this->sendAdminReports($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['notices', 'announcements', 'bulletin'])) {
                $this->sendNotices($user, $phone, $whatsAppService);
                return;
            }
        }

        // Teacher (5)
        if ($role === 5) {
            if (in_array($trimmed, ['marks', 'exam', 'results', 'grades'])) {
                $this->sendTeacherMarks($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['attendance', 'absent', 'present', 'late'])) {
                $this->sendAttendance($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['timetable', 'schedule', 'periods'])) {
                $this->sendTimetable($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['assignments', 'homework', 'tasks'])) {
                $this->sendAssignments($user, $phone, $whatsAppService);
                return;
            }
        }

        // Student (6)
        if ($role === 6) {
            if (in_array($trimmed, ['grades', 'results', 'marks', 'exam'])) {
                $this->sendStudentGrades($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['attendance', 'absent', 'present', 'late'])) {
                $this->sendStudentAttendance($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['fees', 'fee', 'balance', 'payment'])) {
                $this->sendStudentFees($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['timetable', 'schedule', 'periods'])) {
                $this->sendTimetable($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['homework', 'hw', 'assignment'])) {
                $this->sendHomework($user, $phone, $whatsAppService);
                return;
            }
        }

        // Parent (7)
        if ($role === 7) {
            if (in_array($trimmed, ['grades', 'results', 'marks', 'report', 'exams'])) {
                $this->sendGrades($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['fees', 'fee', 'balance', 'payment', 'pay'])) {
                $this->sendFees($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['attendance', 'absent', 'present', 'late'])) {
                $this->sendAttendance($user, $phone, $whatsAppService);
                return;
            }
        }

        // Receptionist/Secretary (10)
        if ($role === 10) {
            if (in_array($trimmed, ['notices', 'announcements', 'bulletin'])) {
                $this->sendNotices($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['calls', 'call', 'phone', 'visitor'])) {
                $this->sendCallLog($user, $phone, $whatsAppService);
                return;
            }
        }

        // Accountant/Bursar (11)
        if ($role === 11) {
            if (in_array($trimmed, ['fees', 'fee', 'balance', 'payment', 'pay', 'collections'])) {
                $this->sendAccountantFees($user, $phone, $whatsAppService);
                return;
            }
            if (in_array($trimmed, ['reports', 'analytics', 'financial', 'summary'])) {
                $this->sendAccountantReports($user, $phone, $whatsAppService);
                return;
            }
        }

        // Dual-role: any staff member with children can access parent features
        if ($hasChildren && !in_array($role, [6, 7])) {
            if (in_array($trimmed, ['my children', 'children', 'kids', 'my kids'])) {
                $this->sendGrades($user, $phone, $whatsAppService);
                return;
            }
        }

        // Unknown keyword — send menu
        $whatsAppService->sendText(
            $phone,
            "🤔 I didn't understand that.\n\nSend *MENU* to see available options.",
            'unknown_keyword',
            $user->user_id,
        );
    }

    /**
     * Send the main menu to a WhatsApp user.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppService $whatsAppService
     */
    private function sendMenu(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $name = $user->user->name ?? 'User';
        $role = $user->user->usergroup_id;
        $hasChildren = $user->user->children()->exists();

        // Send a brief greeting first
        $greeting = "👋 Hello, *{$name}*! What would you like to do?";
        $whatsAppService->sendText($phone, $greeting, 'menu_greeting', $user->user_id);

        // Build interactive list sections per role
        $sections = $this->buildMenuSections($role, $hasChildren);

        // Send the interactive list
        $whatsAppService->sendList(
            phone: $phone,
            title: '🏫 KlassApp Menu',
            sections: $sections,
            description: 'Tap an option below:',
            footerText: 'Reply OPTIN / OPTOUT anytime',
            buttonText: 'View Options',
            flowType: 'menu',
            userId: $user->user_id,
        );
    }

    /**
     * Build role-specific interactive menu sections.
     *
     * @param int $role usergroup_id
     * @param bool $hasChildren Whether user has linked children
     * @return array Sections array for sendList()
     */
    private function buildMenuSections(int $role, bool $hasChildren): array
    {
        $menu = match ($role) {
            // — SchoolAdmin (3) —
            3 => [
                'Management' => [
                    ['title' => 'STUDENTS',   'description' => 'Student list & search'],
                    ['title' => 'STAFF',      'description' => 'Staff list & management'],
                    ['title' => 'EXAMS',      'description' => 'Exam overview & results'],
                    ['title' => 'FEES',       'description' => 'Fee collection report'],
                    ['title' => 'REPORTS',    'description' => 'Attendance & grade reports'],
                    ['title' => 'NOTICES',    'description' => 'School announcements'],
                ],
                'School Life' => [
                    ['title' => 'EVENTS',     'description' => 'Upcoming school events'],
                    ['title' => 'MENU',       'description' => 'Show this menu again'],
                ],
            ],

            // — Teacher (5) —
            5 => [
                'Teaching' => [
                    ['title' => 'MARKS',      'description' => 'Enter or view exam marks'],
                    ['title' => 'ATTENDANCE', 'description' => 'Mark class attendance'],
                    ['title' => 'TIMETABLE',  'description' => 'View my timetable'],
                ],
                'Resources' => [
                    ['title' => 'ASSIGNMENTS','description' => 'View assignments'],
                    ['title' => 'HOMEWORK',   'description' => 'View homework tasks'],
                    ['title' => 'NOTICES',    'description' => 'School announcements'],
                    ['title' => 'EVENTS',     'description' => 'Upcoming school events'],
                ],
            ],

            // — Student (6) —
            6 => [
                'My Data' => [
                    ['title' => 'GRADES',     'description' => 'View my exam results'],
                    ['title' => 'ATTENDANCE', 'description' => 'View my attendance'],
                    ['title' => 'FEES',       'description' => 'Check my fee balance'],
                ],
                'School Life' => [
                    ['title' => 'TIMETABLE',  'description' => 'View class timetable'],
                    ['title' => 'HOMEWORK',   'description' => 'View pending homework'],
                    ['title' => 'EVENTS',     'description' => 'Upcoming school events'],
                ],
            ],

            // — Parent (7) —
            7 => [
                'My Children' => [
                    ['title' => 'GRADES',     'description' => 'View child\'s exam results'],
                    ['title' => 'FEES',       'description' => 'Check fee balance'],
                    ['title' => 'ATTENDANCE', 'description' => 'View attendance record'],
                    ['title' => 'EVENTS',     'description' => 'Upcoming school events'],
                ],
            ],

            // — Receptionist/Secretary (10) —
            10 => [
                'Front Desk' => [
                    ['title' => 'CALLS',      'description' => 'Call log entries'],
                    ['title' => 'NOTICES',    'description' => 'School announcements'],
                    ['title' => 'EVENTS',     'description' => 'Upcoming school events'],
                ],
            ],

            // — Accountant/Bursar (11) —
            11 => [
                'Finance' => [
                    ['title' => 'FEES',       'description' => 'Fee collection summary'],
                    ['title' => 'REPORTS',    'description' => 'Financial reports'],
                    ['title' => 'EVENTS',     'description' => 'Upcoming school events'],
                ],
            ],

            default => [
                'General' => [
                    ['title' => 'MENU',       'description' => 'Show this menu'],
                ],
            ],
        };

        // Add dual-role option for staff who are also parents
        if ($hasChildren && !in_array($role, [6, 7])) {
            $childrenSection = [];
            // Insert into the first section, or create one
            $firstKey = array_key_first($menu);
            $menu[$firstKey][] = [
                'title' => 'MY CHILDREN',
                'description' => 'View your children\'s data',
            ];
        }

        // Add OPTOUT as last item in the last section
        $lastKey = array_key_last($menu);
        // Convert to array so we can modify
        $lastSection = $menu[$lastKey];
        // Only add OPTOUT if the section won't exceed 10 total rows across all sections
        // (WhatsApp limit is 10 rows total)
        $totalRows = collect($menu)->flatten(1)->count();
        if ($totalRows < 10) {
            $lastSection[] = ['title' => 'OPTOUT', 'description' => 'Stop notifications'];
            $menu[$lastKey] = $lastSection;
        }

        // Convert to API format
        return collect($menu)->map(fn($rows, $title) => [
            'title' => $title,
            'rows'  => $rows,
        ])->values()->toArray();
    }

    /**
     * Send grades/results to a parent for ALL their children.
     * Sends one message per child to keep each message focused.
     */
    private function sendGrades(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $children = $user->user->children()
            ->with(['studentAcademic.standard', 'studentAcademic.section'])
            ->get();

        if ($children->isEmpty()) {
            $whatsAppService->sendText(
                $phone,
                "📊 No children linked to your account.\n\nPlease contact your school office to link your children.",
                'grades_no_children',
                $user->user_id,
            );
            return;
        }

        $sentAny = false;

        foreach ($children as $student) {
            $studentName = $student->name;
            $className = $student->studentAcademic?->standard?->name ?? 'N/A';

            $examQuery = Exam::where('student_id', $student->id);
            $academicYear = \App\Helpers\SiteHelper::getAcademicYear($user->user->school_id);
            if ($academicYear) {
                $examQuery->where('academic_year_id', $academicYear->id);
            }

            $exams = $examQuery->with(['marks', 'examType'])->latest()->take(3)->get();

            if ($exams->isEmpty()) {
                $whatsAppService->sendText(
                    $phone,
                    "📊 *Results for {$studentName}*\n_{$className}_\n\nNo results available yet.\n\nResults will be sent here once published by the school.",
                    'grades_none',
                    $user->user_id,
                );
                continue;
            }

            $message = "📊 *Results for {$studentName}*\n_{$className}_\n\n";

            foreach ($exams as $exam) {
                $examName = $exam->name ?? $exam->examType?->name ?? 'Exam';
                $term = $exam->term ?? 'N/A';
                $message .= "📝 *{$examName}* ({$term})\n";

                $marks = $exam->marks->take(5);
                foreach ($marks as $mark) {
                    $subject = $mark->subject_name ?? 'Unknown';
                    $score = $mark->marks_obtained ?? '-';
                    $total = $mark->marks_total ?? 100;
                    $grade = $mark->grade ?? '-';
                    $message .= "• {$subject}: {$score}/{$total} ({$grade})\n";
                }

                $avg = $marks->avg('marks_obtained');
                if ($avg) {
                    $message .= "_Average: " . round($avg, 1) . "%_\n";
                }
                $message .= "\n";
            }

            $message .= "_Send GRADES for latest results._";
            $whatsAppService->sendText($phone, $message, 'grades', $user->user_id);
            $sentAny = true;
        }

        if (!$sentAny) {
            $whatsAppService->sendText(
                $phone,
                "📊 No results found for any of your children.\n\nResults will appear here once published by the school.",
                'grades_none_all',
                $user->user_id,
            );
        }
    }

    /**
     * Send fee balance to a parent for ALL their children.
     * Sends one message per child.
     */
    private function sendFees(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $children = $user->user->children()
            ->with(['studentAcademic.standard'])
            ->get();

        if ($children->isEmpty()) {
            $whatsAppService->sendText(
                $phone,
                "💰 No children linked to your account.\n\nPlease contact your school office.",
                'fees_no_children',
                $user->user_id,
            );
            return;
        }

        $sentAny = false;

        foreach ($children as $student) {
            $studentName = $student->name;
            $className = $student->studentAcademic?->standard?->name ?? 'N/A';

            $feeCategories = FeesCategories::where('school_id', $user->user->school_id)
                ->where('standard_id', function ($q) use ($student) {
                    $q->select('standard_id')
                        ->from('student_academics')
                        ->where('student_id', $student->id)
                        ->latest()
                        ->limit(1);
                })
                ->get();

            $totalFees = $feeCategories->sum('amount');

            $message = "💰 *Fee Balance for {$studentName}*\n_{$className}_\n\n";

            if ($feeCategories->isEmpty()) {
                $message .= "No fee structure found.\n\nContact the school office for details.";
            } else {
                foreach ($feeCategories as $category) {
                    $dueDate = $category->due_date ? date('d M Y', strtotime($category->due_date)) : 'N/A';
                    $message .= "• {$category->name}: UGX " . number_format($category->amount, 0) . "\n";
                    $message .= "  Due: {$dueDate}\n";
                }
                $message .= "\n💵 *Total: UGX " . number_format($totalFees, 0) . "*\n";
                $message .= "\n_Contact school office for payment status._";
            }

            $whatsAppService->sendText($phone, $message, 'fees', $user->user_id);
            $sentAny = true;
        }

        if (!$sentAny) {
            $whatsAppService->sendText(
                $phone,
                "💰 No fee information found for any of your children.\n\nContact the school office for details.",
                'fees_none_all',
                $user->user_id,
            );
        }
    }

    /**
     * Send attendance summary to a parent.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppService $whatsAppService
     */
    /**
     * Send attendance summary to a parent for ALL their children.
     * Sends one message per child.
     */
    private function sendAttendance(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $children = $user->user->children()
            ->with(['studentAcademic.standard'])
            ->get();

        if ($children->isEmpty()) {
            $whatsAppService->sendText(
                $phone,
                "📅 No children linked to your account.\n\nPlease contact your school office.",
                'attendance_no_children',
                $user->user_id,
            );
            return;
        }

        $sentAny = false;

        foreach ($children as $student) {
            $studentName = $student->name;
            $className = $student->studentAcademic?->standard?->name ?? 'N/A';

            $records = Attendance::where('student_id', $student->id)
                ->where('date', '>=', Carbon::now()->subMonth())
                ->orderBy('date', 'desc')
                ->get();

            if ($records->isEmpty()) {
                $whatsAppService->sendText(
                    $phone,
                    "📅 *Attendance for {$studentName}*\n_{$className}_\n_This month_\n\nNo attendance records found.",
                    'attendance_none',
                    $user->user_id,
                );
                continue;
            }

            $totalDays = $records->count();
            $presentDays = $records->where('status', 'present')->count();
            $absentDays = $records->where('status', 'absent')->count();
            $lateDays = $records->where('status', 'late')->count();
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

            $message = "📅 *Attendance for {$studentName}*\n_{$className}_\n_This month_\n\n";
            $message .= "✅ Present: {$presentDays} days\n";
            $message .= "❌ Absent: {$absentDays} days\n";
            $message .= "⏰ Late: {$lateDays} days\n";
            $message .= "📊 Rate: {$attendanceRate}%\n";

            $recentAbsent = $records->where('status', '!=', 'present')->take(3);
            if ($recentAbsent->isNotEmpty()) {
                $message .= "\n📌 *Recent absences:*\n";
                foreach ($recentAbsent as $record) {
                    $date = Carbon::parse($record->date)->format('D d M Y');
                    $status = ucfirst($record->status);
                    $remark = $record->remark ? " ({$record->remark})" : '';
                    $message .= "• {$date}: {$status}{$remark}\n";
                }
            }

            $message .= "\n_Send ATTENDANCE for updated record._";
            $whatsAppService->sendText($phone, $message, 'attendance', $user->user_id);
            $sentAny = true;
        }

        if (!$sentAny) {
            $whatsAppService->sendText(
                $phone,
                "📅 No attendance records found for any of your children.",
                'attendance_none_all',
                $user->user_id,
            );
        }
    }

    /**
     * Send upcoming school events.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppService $whatsAppService
     */
    private function sendEvents(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $schoolId = $user->user->school_id;

        // Reuse the same query logic as schoolEvents() method
        $events = Events::where('school_id', $schoolId)
            ->where('start_date', '>=', Carbon::today())
            ->orderBy('start_date', 'asc')
            ->limit(5)
            ->get();

        if ($events->isEmpty()) {
            $whatsAppService->sendText(
                $phone,
                "🎉 *Upcoming Events*\n\nNo upcoming events at the moment.\n\nCheck back later!",
                'events_none',
                $user->user_id,
            );
            return;
        }

        $message = "🎉 *Upcoming Events*\n\n";

        foreach ($events as $event) {
            $startDate = Carbon::parse($event->start_date)->format('D d M Y');
            $time = $event->start_time ?? 'All day';
            $venue = $event->venue ?? 'TBA';
            $message .= "📌 *{$event->title}*\n";
            $message .= "📅 {$startDate} at {$time}\n";
            $message .= "📍 {$venue}\n";
            if ($event->description) {
                $message .= "_" . \Illuminate\Support\Str::limit($event->description, 100) . "_\n";
            }
            $message .= "\n";
        }

        $message .= "_Send EVENTS for latest updates._";

        $whatsAppService->sendText($phone, $message, 'events', $user->user_id);
    }

    // ----------------------------------------------------------------
    //  Student handlers
    // ----------------------------------------------------------------

    /**
     * Send grades/results to a student for their own record.
     */
    private function sendStudentGrades(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $student = $user->user;
        $studentName = $student->name;
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        $examQuery = Exam::where('student_id', $student->id);
        $academicYear = \App\Helpers\SiteHelper::getAcademicYear($student->school_id);
        if ($academicYear) {
            $examQuery->where('academic_year_id', $academicYear->id);
        }

        $exams = $examQuery->with(['marks', 'examType'])->latest()->take(5)->get();

        if ($exams->isEmpty()) {
            $whatsAppService->sendText(
                $phone,
                "📊 *My Results*\n_{$className}_\n\nNo results available yet.",
                'grades_none',
                $user->user_id,
            );
            return;
        }

        $message = "📊 *My Results — {$studentName}*\n_{$className}_\n\n";
        foreach ($exams as $exam) {
            $examName = $exam->name ?? $exam->examType?->name ?? 'Exam';
            $message .= "📝 *{$examName}*\n";
            foreach ($exam->marks as $mark) {
                $subject = $mark->subject_name ?? 'Unknown';
                $score = $mark->marks_obtained ?? '-';
                $total = $mark->marks_total ?? 100;
                $grade = $mark->grade ?? '';
                $message .= "  • {$subject}: {$score}/{$total} {$grade}\n";
            }
            $message .= "\n";
        }

        $whatsAppService->sendText($phone, $message, 'grades', $user->user_id);
    }

    /**
     * Send attendance summary to a student for their own record.
     */
    private function sendStudentAttendance(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $student = $user->user;
        $academicYear = \App\Helpers\SiteHelper::getAcademicYear($student->school_id);

        $records = Attendance::where('student_id', $student->id)
            ->when($academicYear, fn ($q) => $q->whereBetween('date', [
                $academicYear->start_date ?? Carbon::now()->startOfYear(),
                $academicYear->end_date ?? Carbon::now()->endOfYear(),
            ]))
            ->orderBy('date', 'desc')
            ->get();

        $totalDays = $records->count();
        $presentDays = $records->where('status', 'present')->count();
        $absentDays = $records->where('status', 'absent')->count();
        $rate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

        $message = "📅 *My Attendance*\n_{$student->name}_\n\n";
        $message .= "✅ Present: {$presentDays}\n";
        $message .= "❌ Absent: {$absentDays}\n";
        $message .= "📊 Rate: {$rate}%\n\n";

        $recentAbsent = $records->where('status', '!=', 'present')->take(3);
        if ($recentAbsent->isNotEmpty()) {
            $message .= "Recent absences:\n";
            foreach ($recentAbsent as $record) {
                $message .= "  • " . Carbon::parse($record->date)->format('d M Y') . "\n";
            }
        }

        $whatsAppService->sendText($phone, $message, 'attendance', $user->user_id);
    }

    /**
     * Send fee balance to a student for their own record.
     */
    private function sendStudentFees(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $student = $user->user;

        $feeCategories = FeesCategories::where('school_id', $student->school_id)
            ->where('standard_id', function ($q) use ($student) {
                $q->select('standard_id')
                    ->from('student_academics')
                    ->where('student_id', $student->id)
                    ->latest()
                    ->limit(1);
            })
            ->get();

        $totalFees = $feeCategories->sum('amount');
        $message = "💰 *My Fee Balance*\n_{$student->name}_\n\n";

        if ($feeCategories->isEmpty()) {
            $message .= "No fee structure found.";
        } else {
            foreach ($feeCategories as $category) {
                $dueDate = $category->due_date ? date('d M Y', strtotime($category->due_date)) : 'N/A';
                $message .= "• {$category->name}: UGX " . number_format($category->amount, 0) . "\n";
                $message .= "  Due: {$dueDate}\n";
            }
            $message .= "\n💵 *Total: UGX " . number_format($totalFees, 0) . "*";
            $message .= "\n\n_Contact school office for payment status._";
        }

        $whatsAppService->sendText($phone, $message, 'fees', $user->user_id);
    }

    // ----------------------------------------------------------------
    //  Teacher handlers
    // ----------------------------------------------------------------

    /**
     * Send marks overview for the teacher's subjects.
     */
    private function sendTeacherMarks(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $teacher = $user->user;

        $exams = Exam::with(['standard', 'subject', 'examType'])
            ->where('teacher_id', $teacher->id)
            ->where('school_id', $teacher->school_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($exams->isEmpty()) {
            $whatsAppService->sendText(
                $phone,
                "📝 *My Exams*\n\nNo exams assigned to you yet.",
                'marks_none',
                $user->user_id,
            );
            return;
        }

        $message = "📝 *My Exams & Marks*\n\n";
        foreach ($exams as $exam) {
            $marksCount = Marks::where('exam_id', $exam->id)->count();
            $status = $exam->status === 'completed' ? '✅' : '⏳';
            $message .= "{$status} {$exam->subject?->name} — {$exam->standard?->name}\n";
            $message .= "  {$marksCount} mark(s) entered\n\n";
        }
        $message .= "_Log into the portal to enter marks._";

        $whatsAppService->sendText($phone, $message, 'marks', $user->user_id);
    }

    /**
     * Send timetable for a teacher or student.
     */
    private function sendTimetable(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $message = "📋 *My Timetable*\n\n";
        $message .= "Timetable is available on the KlassApp portal.\n\n";
        $message .= "Log in to view or download your full schedule.";

        $whatsAppService->sendText($phone, $message, 'timetable', $user->user_id);
    }

    /**
     * Send assignments overview for a teacher.
     */
    private function sendAssignments(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $message = "📖 *My Assignments*\n\n";
        $message .= "Assignments can be managed via the KlassApp portal.\n\n";
        $message .= "Log in to create or review assignments.";

        $whatsAppService->sendText($phone, $message, 'assignments', $user->user_id);
    }

    /**
     * Send homework overview for a student.
     */
    private function sendHomework(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $message = "📝 *Pending Homework*\n\n";
        $message .= "Homework can be viewed on the KlassApp portal.\n\n";
        $message .= "Log in to see assigned homework and due dates.";

        $whatsAppService->sendText($phone, $message, 'homework', $user->user_id);
    }

    // ----------------------------------------------------------------
    //  Admin handlers
    // ----------------------------------------------------------------

    /**
     * Send student list summary to admin.
     */
    private function sendStudentList(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $schoolId = $user->user->school_id;
        $totalStudents = User::where('school_id', $schoolId)->where('usergroup_id', 6)->count();
        $totalStaff = User::where('school_id', $schoolId)->where('usergroup_id', 5)->count();

        $message = "👥 *Student Summary*\n\n";
        $message .= "Total students: {$totalStudents}\n";
        $message .= "Total teachers: {$totalStaff}\n\n";
        $message .= "_Use the portal for detailed student management._";

        $whatsAppService->sendText($phone, $message, 'students', $user->user_id);
    }

    /**
     * Send staff list summary to admin.
     */
    private function sendStaffList(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $schoolId = $user->user->school_id;
        $teachers = User::where('school_id', $schoolId)->where('usergroup_id', 5)->count();
        $accountants = User::where('school_id', $schoolId)->where('usergroup_id', 11)->count();
        $receptionists = User::where('school_id', $schoolId)->where('usergroup_id', 10)->count();

        $message = "👔 *Staff Summary*\n\n";
        $message .= "Teachers: {$teachers}\n";
        $message .= "Accountants: {$accountants}\n";
        $message .= "Receptionists: {$receptionists}\n\n";
        $message .= "_Use the portal for detailed staff management._";

        $whatsAppService->sendText($phone, $message, 'staff', $user->user_id);
    }

    /**
     * Send exam overview to admin.
     */
    private function sendAdminExams(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $schoolId = $user->user->school_id;

        $activeExams = Exam::where('school_id', $schoolId)
            ->where('status', '!=', 'completed')
            ->count();

        $completedExams = Exam::where('school_id', $schoolId)
            ->where('status', 'completed')
            ->count();

        $message = "📝 *Exam Overview*\n\n";
        $message .= "Active exams: {$activeExams}\n";
        $message .= "Completed: {$completedExams}\n\n";
        $message .= "_Use the portal for detailed exam management._";

        $whatsAppService->sendText($phone, $message, 'exams', $user->user_id);
    }

    /**
     * Send fee report to admin.
     */
    private function sendAdminFees(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $schoolId = $user->user->school_id;
        $totalCategories = FeesCategories::where('school_id', $schoolId)->count();
        $totalFees = FeesCategories::where('school_id', $schoolId)->sum('amount');

        $message = "💰 *Fee Report*\n\n";
        $message .= "Fee categories: {$totalCategories}\n";
        $message .= "Total expected: UGX " . number_format($totalFees, 0) . "\n\n";
        $message .= "_Use the portal for detailed fee tracking._";

        $whatsAppService->sendText($phone, $message, 'fees_report', $user->user_id);
    }

    /**
     * Send reports summary to admin.
     */
    private function sendAdminReports(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $message = "📊 *School Reports*\n\n";
        $message .= "Reports are available on the KlassApp portal:\n\n";
        $message .= "📈 Attendance reports\n";
        $message .= "📝 Grade/result reports\n";
        $message .= "💰 Financial reports\n\n";
        $message .= "_Log into the portal to generate detailed reports._";

        $whatsAppService->sendText($phone, $message, 'reports', $user->user_id);
    }

    // ----------------------------------------------------------------
    //  Receptionist / Secretary handlers
    // ----------------------------------------------------------------

    /**
     * Send notices/announcements.
     */
    private function sendNotices(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $message = "📢 *School Announcements*\n\n";
        $message .= "Notices are available on the KlassApp portal.\n\n";
        $message .= "_Log in to view all announcements._";

        $whatsAppService->sendText($phone, $message, 'notices', $user->user_id);
    }

    /**
     * Send call log summary to receptionist.
     */
    private function sendCallLog(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $message = "📞 *Call Log*\n\n";
        $message .= "Call logs can be managed via the KlassApp portal.\n\n";
        $message .= "_Log in to view or add call entries._";

        $whatsAppService->sendText($phone, $message, 'call_log', $user->user_id);
    }

    // ----------------------------------------------------------------
    //  Accountant handlers
    // ----------------------------------------------------------------

    /**
     * Send fee collection summary for accountant.
     */
    private function sendAccountantFees(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $schoolId = $user->user->school_id;
        $categories = FeesCategories::where('school_id', $schoolId)->count();
        $total = FeesCategories::where('school_id', $schoolId)->sum('amount');

        $message = "💰 *Fee Collection Summary*\n\n";
        $message .= "Active fee categories: {$categories}\n";
        $message .= "Total expected revenue: UGX " . number_format($total, 0) . "\n\n";
        $message .= "_Use the portal to track payments and arrears._";

        $whatsAppService->sendText($phone, $message, 'fees_accountant', $user->user_id);
    }

    /**
     * Send financial reports summary for accountant.
     */
    private function sendAccountantReports(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        $message = "📊 *Financial Reports*\n\n";
        $message .= "Reports available on the KlassApp portal:\n\n";
        $message .= "💰 Fee collection reports\n";
        $message .= "📋 Payment tracking\n";
        $message .= "📈 Revenue analytics\n\n";
        $message .= "_Log into the portal for detailed financial reports._";

        $whatsAppService->sendText($phone, $message, 'reports_accountant', $user->user_id);
    }

    /**
     * Derive a human-readable user type from the users.usergroup_id.
     */
    private function resolveUserType(User $user): string
    {
        return match ($user->usergroup_id) {
            3  => 'admin',
            5  => 'teacher',
            6  => 'student',
            7  => 'parent',
            10 => 'receptionist',
            11 => 'accountant',
            default => 'unknown',
        };
    }
}
