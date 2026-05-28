<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api;

use App\Helpers\WhatsAppPhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest;
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
use App\Services\WhatsAppService;
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
        $response = [
            'identified' => true,
            'user_id'    => $user->id,
            'name'       => $user->name,
            'user_type'  => $whatsappUser->user_type,
            'school_id'  => $user->school_id,
            'school_name' => $user->school?->name,
        ];

        // If parent, include linked children
        if ($whatsappUser->user_type === 'parent') {
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
        if ($whatsappUser->user_type === 'teacher') {
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
            'failed'    => $log->markFailed($request->input('error', 'Unknown error')),
            'sent'      => $log->update(['status' => 'sent']),
        };

        return response()->json(['status' => 'updated']);
    }

    public function checkWindow(Request $request)
    {
        $phone = $request->query('phone');
        if (!$phone) {
            return response()->json(['error' => 'phone required'], 422);
        }
        $open = MessageDeliveryLog::where('phone', $phone)
            ->where('direction', 'inbound')
            ->where('sent_at', '>=', now()->subHours(24))
            ->exists();
        return response()->json(['window_open' => $open, 'phone' => $phone]);
    }

    // =====================================================================
    // INBOUND WEBHOOK HANDLERS (Evolution API → Laravel, no HMAC)
    // =====================================================================

    /**
     * Handle inbound WhatsApp messages from Evolution API webhook.
     *
     * POST /api/whatsapp/inbound
     * Evolution API sends this when a user sends a message.
     * This route is OUTSIDE HMAC middleware since Evolution does not send HMAC headers.
     *
     * Expected Evolution payload:
     * {
     *   "instance": { "instanceName": "klassapp" },
     *   "data": {
     *     "remoteJid": "256701234567@s.whatsapp.net",
     *     "pushName": "John Doe",
     *     "message": { "conversation": "grades" }
     *   }
     * }
     */
    public function handleInbound(StoreWhatsAppWebhookRequest $request, WhatsAppService $whatsAppService)
    {
        $payload = $request->validated();
        $allData = $request->all();
        \Log::info('WhatsApp inbound received', ['event' => $payload['event'] ?? 'unknown']);

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

        // Keyword matching
        if (in_array($trimmed, ['menu', 'help', 'start', 'options'])) {
            $this->sendMenu($user, $phone, $whatsAppService);
            return;
        }

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

        if (in_array($trimmed, ['events', 'event', 'calendar', 'news'])) {
            $this->sendEvents($user, $phone, $whatsAppService);
            return;
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
        $name = $user->user->name ?? 'Parent';
        $userType = $user->user_type;

        if ($userType === 'parent') {
            $menu = "👋 Hello, *{$name}*!\n\n";
            $menu .= "📚 *KlassApp WhatsApp Menu*\n\n";
            $menu .= "Send any of these keywords:\n\n";
            $menu .= "📊 *GRADES* — View student results\n";
            $menu .= "💰 *FEES* — Check fee balance\n";
            $menu .= "📅 *ATTENDANCE* — View attendance record\n";
            $menu .= "🎉 *EVENTS* — Upcoming school events\n";
            $menu .= "📋 *MENU* — Show this menu again\n";
            $menu .= "❌ *OPTOUT* — Stop notifications\n\n";
            $menu .= "_Reply with a keyword to get started._";
        } elseif ($userType === 'teacher') {
            $menu = "👋 Hello, *{$name}*!\n\n";
            $menu .= "📚 *KlassApp WhatsApp Menu (Teacher)*\n\n";
            $menu .= "Send any of these keywords:\n\n";
            $menu .= "📅 *ATTENDANCE* — Mark class attendance\n";
            $menu .= "🎉 *EVENTS* — Upcoming school events\n";
            $menu .= "📋 *MENU* — Show this menu again\n";
            $menu .= "❌ *OPTOUT* — Stop notifications\n\n";
            $menu .= "_Reply with a keyword to get started._";
        } else {
            $menu = "👋 Hello, *{$name}*!\n\n";
            $menu .= "📚 *KlassApp WhatsApp Menu*\n\n";
            $menu .= "Send *MENU* to see available options.\n";
            $menu .= "❌ *OPTOUT* — Stop notifications";
        }

        $whatsAppService->sendText($phone, $menu, 'menu', $user->user_id);
    }

    /**
     * Send grades/results to a parent for their children.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppService $whatsAppService
     */
    private function sendGrades(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        if ($user->user_type !== 'parent') {
            $whatsAppService->sendText(
                $phone,
                "📊 Grades are only available for parents.\n\nIf you are a parent, contact your school to update your account type.",
                'grades_unauthorized',
                $user->user_id,
            );
            return;
        }

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

        // Get grades for the first child (or all if only one)
        $student = $children->first();
        $studentName = $student->name;
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        // Reuse the same query logic as grades() method
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
            return;
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
    }

    /**
     * Send fee balance to a parent.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppService $whatsAppService
     */
    private function sendFees(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        if ($user->user_type !== 'parent') {
            $whatsAppService->sendText(
                $phone,
                "💰 Fee information is only available for parents.\n\nIf you are a parent, contact your school to update your account type.",
                'fees_unauthorized',
                $user->user_id,
            );
            return;
        }

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

        $student = $children->first();
        $studentName = $student->name;
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        // Reuse the same query logic as feeBalance() method
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
    }

    /**
     * Send attendance summary to a parent.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppService $whatsAppService
     */
    private function sendAttendance(WhatsAppUser $user, string $phone, WhatsAppService $whatsAppService): void
    {
        if ($user->user_type !== 'parent') {
            $whatsAppService->sendText(
                $phone,
                "📅 Attendance is only available for parents.\n\nIf you are a parent, contact your school to update your account type.",
                'attendance_unauthorized',
                $user->user_id,
            );
            return;
        }

        $children = $user->user->children()->get();

        if ($children->isEmpty()) {
            $whatsAppService->sendText(
                $phone,
                "📅 No children linked to your account.\n\nPlease contact your school office.",
                'attendance_no_children',
                $user->user_id,
            );
            return;
        }

        $student = $children->first();
        $studentName = $student->name;
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        // Reuse the same query logic as attendance() method
        $records = Attendance::where('student_id', $student->id)
            ->where('date', '>=', Carbon::now()->subMonth())
            ->orderBy('date', 'desc')
            ->get();

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

        // Show recent absences
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
}
