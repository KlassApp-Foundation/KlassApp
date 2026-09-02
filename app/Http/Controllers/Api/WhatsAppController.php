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
use App\Models\WhatsAppPendingParentLink;
use App\Models\Events;
use App\Models\Academics\Marks;
use App\Models\Academics\Exam;
use App\Models\Academics\Classes;
use App\Services\OutboundWhatsAppService;
use App\Services\ParentLinkService;
use App\Services\ParentMagicLoginService;
use App\Services\WhatsApp\WhatsAppConfirmationBridge;
use App\Services\WhatsApp\ParentLinkRequestService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
        protected ParentLinkService $parentLinks,
        protected ParentLinkRequestService $parentLinkRequests,
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
        $examQuery = Exam::whereHas('marks', fn($q) => $q->where('student_id', $studentId))
            ->with(['marks' => fn($q) => $q->where('student_id', $studentId), 'examType', 'subject']);

        if ($term === 'current') {
            // Get current academic year's latest exam
            $academicYear = \App\Helpers\SiteHelper::getAcademicYear($student->school_id);
            if ($academicYear) {
                $examQuery->where('academic_year_id', $academicYear->id);
            }
        }

        $exams = $examQuery->latest('id')->take(5)->get();

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
                    'subject' => $mark->subject?->name ?? 'Unknown',
                    'score'   => $mark->marks,
                    'total'   => 100,
                    'grade'   => $mark->grade,
                    'remark'  => $mark->remark ?? '',
                ];
            });

            return [
                'exam_name' => $exam->subject?->name ?? $exam->examType?->name ?? 'Exam',
                'term'      => $exam->academicTerm?->name ?? 'N/A',
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

        $query = Attendance::where('user_id', $studentId);

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
        $presentDays = $records->where('status', 1)->count();
        $absentDays = $records->where('status', 0)->count();
        $lateDays = $records->where('status', 2)->count();
        $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

        $recentAbsent = $records->where('status', '!=', 1)
            ->take(5)
            ->map(function ($record) {
                return [
                    'date'   => Carbon::parse($record->date)->format('D d M Y'),
                    'status' => $record->status == 1 ? 'Present' : ($record->status == 2 ? 'Late' : 'Absent'),
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
     * Generate and deliver a report card PDF via WhatsApp.
     *
     * GET /api/whatsapp/student/{studentId}/report?phone=+256...&year_id=X&term_id=Y
     */
    public function report(string $studentId, Request $request)
    {
        $phone = WhatsAppPhoneHelper::normalise($request->input('phone', ''));
        if (empty($phone)) {
            return response()->json(['error' => 'Phone number is required.'], 422);
        }

        $whatsappUser = \App\Models\WhatsAppUser::where('phone', $phone)->with('user')->first();
        if (!$whatsappUser || !$whatsappUser->user) {
            return response()->json(['error' => 'Phone number not linked to any account.'], 404);
        }

        $parent = $whatsappUser->user;

        $student = \App\Models\User::with('studentAcademic.standardLink.standard')->where('id', $studentId)->first();
        if (!$student) {
            return response()->json(['error' => 'Student not found.'], 404);
        }

        $isLinked = \DB::table('student_parent_links')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists();
        if (!$isLinked) {
            return response()->json(['error' => 'This student is not linked to your account.'], 403);
        }

        $schoolId = $student->school_id;
        $yearId = $request->input('year_id');
        if (!$yearId) {
            $academicYear = \App\Helpers\SiteHelper::getAcademicYear($schoolId);
            $yearId = $academicYear ? $academicYear->id : null;
        }
        if (!$yearId) {
            return response()->json(['error' => 'No academic year found.'], 404);
        }

        // Check approval status
        $approvedCount = \DB::table('exam_marks_submissions')
            ->join('exams', 'exams.id', '=', 'exam_marks_submissions.exam_id')
            ->join('marks', fn($j) => $j->on('marks.exam_id', '=', 'exams.id')->where('marks.student_id', $studentId))
            ->where('exams.school_id', $schoolId)
            ->where('exams.academic_year_id', $yearId)
            ->where('exam_marks_submissions.approval_status', 'approved')
            ->distinct('exam_marks_submissions.subject_id')
            ->count('exam_marks_submissions.subject_id');

        if ($approvedCount === 0) {
            return response()->json([
                'student_name' => $student->name,
                'message'      => 'Marks are not yet finalized for this term. Please check back after your child\'s teacher has submitted and the school has approved the results.',
                'approved'     => false,
            ]);
        }

        $term = $request->input('term_id')
            ? \App\Models\AcademicTerm::find($request->input('term_id'))
            : \App\Models\AcademicTerm::where('school_id', $schoolId)->where('academic_year_id', $yearId)->first();
        if (!$term) {
            return response()->json(['error' => 'Term not found.'], 404);
        }

        $school = \App\Models\School::find($schoolId);
        $examTypePrefs = $school ? ($school->exam_type_preferences ?? []) : [];
        $contributingIds = \App\Models\Academics\ExamType::all()->filter(fn($et) => $examTypePrefs[$et->id] ?? $et->contributes_to_report_total)->pluck('id')->toArray();

        $exams = \App\Models\Academics\Exam::where('school_id', $schoolId)
            ->where('academic_term_id', $term->id)
            ->whereIn('exam_type_id', $contributingIds)
            ->with(['marks' => fn($q) => $q->where('student_id', $student->id), 'examType', 'subject'])
            ->get();

        $approvedSubjectIds = \DB::table('exam_marks_submissions')
            ->join('exams', 'exams.id', '=', 'exam_marks_submissions.exam_id')
            ->where('exams.school_id', $schoolId)
            ->where('exams.academic_term_id', $term->id)
            ->where('exam_marks_submissions.approval_status', 'approved')
            ->pluck('exam_marks_submissions.subject_id')->unique()->toArray();

        $marksData = [];
        foreach ($exams as $exam) {
            $isApproved = in_array($exam->subject_id, $approvedSubjectIds);
            $mark = $exam->marks->first();
            $marksData[] = [
                'subject'  => $exam->subject?->name ?? 'Unknown',
                'score'    => $isApproved && $mark ? $mark->marks : null,
                'grade'    => $isApproved && $mark ? $mark->grade : null,
                'approved' => $isApproved,
                'display'  => $isApproved && $mark ? $mark->marks . '%' : 'Not yet available',
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('whatsapp.report-card', [
            'student'   => $student,
            'marksData' => $marksData,
            'term'      => $term,
            'school'    => $school,
            'partial'   => $approvedCount < count($marksData),
        ]);

        $filename = 'report_' . $student->id . '_' . time() . '.pdf';
        $path = storage_path('app/public/reports/' . $filename);
        if (!is_dir(dirname($path))) { mkdir(dirname($path), 0755, true); }
        $pdf->save($path);

        $fileUrl = url('storage/reports/' . $filename);
        $caption = 'Report Card for ' . $student->name . ' — ' . ($term->name ?? '');

        $result = $this->businessApi->sendDocument($phone, $fileUrl, $caption, $filename, 'report_card', $student->id);

        return $result['success']
            ? response()->json(['student_name' => $student->name, 'message' => 'Report card sent via WhatsApp.', 'approved' => true, 'partial' => $approvedSubjectIds < count($marksData), 'message_id' => $result['message_id']])
            : response()->json(['student_name' => $student->name, 'error' => $result['error'] ?? 'Failed to send document via WhatsApp.'], 500);
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
    public function handleInbound(Request $request)
    {
        Log::info('WhatsApp inbound handler called', ['method' => $request->method()]);

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
        Log::info('WhatsApp inbound POST data', ['keys' => array_keys($allData)]);

        return $this->handleMetaInbound($allData);
    }

    /**
     * Parse an inbound message or status from the Meta Cloud API.
     */
    protected function handleMetaInbound(array $payload)
    {
        Log::info('handleMetaInbound called', ['payload_keys' => array_keys($payload)]);
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                // Extract sender's profile name from contacts array
                $senderName = 'Demo User';
                $contacts = $value['contacts'] ?? [];
                foreach ($contacts as $c) {
                    if (isset($c['profile']['name'])) {
                        $senderName = $c['profile']['name'];
                        break;
                    }
                }

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
                        $ir = $msg['interactive'] ?? [];
                        if (($ir['type'] ?? '') === 'nfm_reply') {
                            $this->processMetaFlowReply($phone, $ir['nfm_reply'] ?? [], $senderName);
                            continue;
                        }
                        $body = $ir['button_reply']['id'] ?? $ir['list_reply']['id']
                            ?? $ir['button_reply']['title'] ?? $ir['list_reply']['title']
                            ?? '';
                    }

                    Log::info('Meta message parsed', ['phone' => $phone, 'body' => $body, 'type' => $msgType]);

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
                    $this->processMetaMessage($phone, $body, $messageId, $senderName);
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
    protected function processMetaMessage(string $phone, string $body, string $messageId, string $senderName = 'Demo User')
    {
        $whatsappUser = WhatsAppUser::with(['user.userprofile', 'user.school'])
            ->where('phone', $phone)
            ->first();

        if (!$whatsappUser) {
            $this->handleUnrecognizedUserMeta($phone, $body, $senderName);
            return;
        }

        if (!$whatsappUser->opted_in) {
            $this->businessApi->sendText(
                $phone,
                "You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                'opted_out',
            );
            return;
        }

        // Handle OPTIN/OPTOUT keywords
        $trimmedBody = strtolower(trim($body));
        if ($trimmedBody === 'optin') {
            $whatsappUser->update(['opted_in' => true]);
            $this->sendMenu($whatsappUser, $phone, $this->businessApi);
            return;
        }

        if ($trimmedBody === 'optout') {
            $whatsappUser->update(['opted_in' => false]);
            $this->businessApi->sendText(
                $phone,
                "❌ You have opted out of WhatsApp notifications.\n\nReply *OPTIN* to re-enable.",
                'optout',
                $whatsappUser->user_id,
            );
            return;
        }

        // Route to appropriate handler
        $this->routeInbound($whatsappUser, $phone, $body, $this->businessApi);

        // Flush any queued notifications — parent's inbound just opened a free window
        $outboundService = app(OutboundWhatsAppService::class);
        $flushed = $outboundService->flushPending($whatsappUser);
        if ($flushed > 0) {
            Log::info("handleMetaInbound: flushed {$flushed} pending notifications for {$phone}");
        }
    }

    /**
     * Handle a completed WhatsApp Flow submission (nfm_reply).
     * @param  array<string, mixed>  $nfmReply
     */
    protected function processMetaFlowReply(string $phone, array $nfmReply, string $senderName = 'Demo User'): void
    {
        $rawJson = $nfmReply['response_json'] ?? '';
        $payload = is_string($rawJson) ? json_decode($rawJson, true) : [];
        if (! is_array($payload)) {
            $payload = [];
        }

        $linkRequest = $this->parentLinkRequests->createFromFlowSubmission(
            $phone,
            $payload,
            isset($nfmReply['flow_token']) ? (string) $nfmReply['flow_token'] : null,
            $senderName,
        );

        Log::info('WhatsApp Flow completed', [
            'phone' => $phone,
            'sender' => $senderName,
            'flow_name' => $nfmReply['name'] ?? null,
            'payload' => $payload,
            'parent_link_request_id' => $linkRequest->id,
        ]);

        $parentName = $linkRequest->parent_name !== '' ? $linkRequest->parent_name : 'Parent';
        $childName = $linkRequest->child_name !== '' ? $linkRequest->child_name : 'your child';
        $childClass = $linkRequest->child_class !== '' ? $linkRequest->child_class : 'their class';
        $schoolName = $linkRequest->schoolDisplayName();

        $this->businessApi->sendText(
            $phone,
            "✅ *Request received*\n\n"
            . "Thanks, *{$parentName}*! We've sent your link request for *{$childName}* of "
            . "*{$childClass}* at *{$schoolName}* for review. "
            . "You'll be linked to your child once the school administration approves your request.",
            'parent_link_flow_ack',
        );
    }

    /**
     * Process a delivery status update from Meta Cloud API.
     */
    /**
     * Handle an unrecognized user on the Meta WABA path.
     * Supports School Pay code verification, exit, and link_help flows.
     */
    protected function handleUnrecognizedUserMeta(string $phone, string $body, string $senderName = 'Demo User'): void
    {
        $trimmed = trim($body);

        $sendText = fn (string $message, ?string $flowType = null, ?int $userId = null) =>
            $this->businessApi->sendText($phone, $message, $flowType, $userId);

        $sendButtons = fn (string $body, array $buttons, ?string $flowType = null, ?int $userId = null) =>
            $this->businessApi->sendInteractiveButtons($phone, $body, $buttons, $flowType, $userId);

        // ── Handle button replies ──
        if ($trimmed === 'exit') {
            $sendText(
                "No problem! If you change your mind, just text us anytime.\n\n"
                . "Have your child's School Pay payment code ready — "
                . "it's the 10-digit number you use when paying school fees.",
                'exit_prompt'
            );
            return;
        }

        if ($trimmed === 'link_help') {
            $sendText(
                "🔗 *Link to your child — 3 ways:*\n\n"
                . "1️⃣ *KlassApp ID* — Type the student ID from your child's report card (e.g., KLS0010427)\n\n"
                . "2️⃣ *Full name* — Type your child's name (e.g., Amope Nandawula)\n\n"
                . "3️⃣ *School name* — Type your school name first to narrow the search\n\n"
                . "No code needed if you know your child's name or school.\n\n"
                . "Or tap *Request Link* on the welcome menu for a short form your school will review.",
                'link_help'
            );
            return;
        }

        if ($trimmed === 'parent_link_flow') {
            $result = $this->businessApi->sendParentLinkRequestFlow($phone);
            if (! ($result['success'] ?? false)) {
                $sendText(
                    "Sorry, the link request form isn't available right now.\n\n"
                    . "You can still link instantly with your child's KlassApp ID or full name — tap *Link My Number* for help.",
                    'parent_link_flow_unavailable'
                );
            }
            return;
        }

        // ── Handle "link_school_{schoolId}" button — school selected, show link options ──
        if (str_starts_with($trimmed, 'link_school_')) {
            $schoolId = (int) substr($trimmed, 12);
            $school = \App\Models\School::find($schoolId);
            $schoolName = $school?->name ?? 'your child\'s school';

            $sendButtons(
                "📚 *{$schoolName}*\n\nHow would you like to link?",
                [
                    ['title' => '🔑 KlassApp ID', 'id' => "linktype_klassapp_{$schoolId}"],
                    ['title' => '📝 Search by name', 'id' => "linktype_name_{$schoolId}"],
                ],
                'link_type_choice',
            );
            return;
        }

        // ── Handle "linktype_klassapp_{schoolId}" — prompt for KlassApp ID ──
        if (str_starts_with($trimmed, 'linktype_klassapp_')) {
            $sendText(
                "Type the *KlassApp Student ID* from your child's report card.\n\n"
                . "It looks like: KLS0010427 (no dashes, no spaces).",
                'prompt_klassapp_id'
            );
            return;
        }

        // ── Handle "linktype_name_{schoolId}" — prompt for student name ──
        if (str_starts_with($trimmed, 'linktype_name_')) {
            $sendText(
                "Type your child's *full name* and I'll search for them.\n\n"
                . "For example: Amope Nandawula",
                'prompt_student_name'
            );
            return;
        }

        // ── Handle "link_{studentId}" confirmation button ──
        if (str_starts_with($trimmed, 'link_')) {
            $studentId = (int) substr($trimmed, 5);
            if ($studentId > 0) {
                $this->linkParentToStudent($phone, $studentId, $sendText, $sendButtons, $senderName);
                return;
            }
        }

        // ── DEMO: auto-link to demo parent ──
        if (strtolower($trimmed) === 'demo') {
            $demoParent = User::find(104);
            if (!$demoParent) {
                $sendText("Demo account not available. Please contact support.");
                return;
            }

            $whatsappUser = WhatsAppUser::firstOrCreate(
                ['phone' => $phone],
                [
                    'user_id'                => 104,
                    'school_id'              => 1,
                    'opted_in'               => true,
                    'verified_via_schoolpay' => true,
                    'verified_at'            => now(),
                    'demo_name'              => $senderName,
                ],
            );
            if (!$whatsappUser->wasRecentlyCreated) {
                $whatsappUser->update(['demo_name' => $senderName]);
            }
            $whatsappUser->load(['user.userprofile', 'user.school']);
            Log::info("WhatsApp DEMO (Meta): {$senderName} ({$phone})");

            $this->sendMenu($whatsappUser, $phone, $this->businessApi);
            return;
        }

        // ── Is this a School Pay payment code? (10 digits) ──
        if (preg_match('/^\d{10}$/', $trimmed)) {
            $this->processCodeVerificationForMeta($phone, $trimmed, $sendText, $sendButtons);
            return;
        }

        // ── Try to find a student by ID or name ──
        $searchable = strlen($trimmed) >= 2 && ! in_array($trimmed, ['exit', 'link_help', 'parent_link_flow', 'demo', 'menu', 'help'], true);
        if (! $searchable) {
            // ── Default: unrecognized — offer DEMO or link ──
            $sendButtons(
                "👋 *Welcome to KlassApp!* 🎓\n\n"
                . "Tap *Try Demo* to explore with sample data.\n"
                . "Tap *Link My Number* for instant linking (KlassApp ID or name).\n"
                . "Tap *Request Link* to submit details for your school to review first.",
                [
                    ['title' => '🎯 Try Demo', 'id' => 'demo'],
                    ['title' => '🔗 Link My Number', 'id' => 'link_help'],
                    ['title' => '📋 Request Link', 'id' => 'parent_link_flow'],
                ],
                'unrecognized_prompt',
            );
            return;
        }

        // ── Priority 1: KlassApp Student ID (KLS0010427 — no dashes) ──
        if (preg_match('/^KLS\d{7}$/i', $trimmed)) {
            $academic = \App\Models\StudentAcademic::where('klassapp_student_id', strtoupper($trimmed))
                ->with(['user', 'standardLink.standard', 'school'])
                ->first();
            if ($academic && $academic->user) {
                $this->linkParentToStudent($phone, $academic->user->id, $sendText, $sendButtons, $senderName);
                return;
            }
            $sendText(
                "We couldn't find a student with KlassApp ID *{$trimmed}*.\n\n"
                . "Check the ID on your child's report card or type their name to search.",
                'klassapp_id_not_found'
            );
            return;
        }

        // ── Priority 2: School's own student ID (school_student_id) ──
        // Multi-tenant safe: group the OR so school_id scoping (when available)
        // applies to BOTH columns, not just the first. Global fallback for
        // first-time parents with no school link yet.
        $priority2Query = \App\Models\StudentAcademic::where(function ($q) use ($trimmed) {
            $q->where('school_student_id', $trimmed)
              ->orWhere('board_registration_number', $trimmed);
        });
        // If the phone already has a WhatsAppUser with a school_id, scope by it
        // (covers re-linking a parent who texts a student ID after being linked to a school).
        $existingWaUser = \App\Models\WhatsAppUser::where('phone', $phone)->whereNotNull('school_id')->first();
        if ($existingWaUser) {
            $priority2Query->where('school_id', $existingWaUser->school_id);
        }
        $schoolIdMatch = $priority2Query->with(['user', 'standardLink.standard', 'school'])->first();
        if ($schoolIdMatch && $schoolIdMatch->user) {
            // If we matched globally (no school scope), auto-learn the school onto
            // the WhatsAppUser so future lookups are scoped.
            if (! $existingWaUser && $schoolIdMatch->school_id) {
                \App\Models\WhatsAppUser::updateOrCreate(
                    ['phone' => $phone],
                    ['school_id' => $schoolIdMatch->school_id],
                );
            }
            $this->linkParentToStudent($phone, $schoolIdMatch->user->id, $sendText, $sendButtons, $senderName);
            return;
        }

        // ── Priority 3: Check if this is a school name — scope subsequent search ──
        $matchingSchool = null;
        $schools = \App\Models\School::where('name', 'LIKE', "%{$trimmed}%")->limit(3)->get();
        if ($schools->count() === 1) {
            $matchingSchool = $schools->first();
        } elseif ($schools->count() > 1) {
            $sendButtons(
                "Which school? We found " . $schools->count() . " matching \"{$trimmed}\":\n\n"
                . $schools->take(5)->map(fn($s) => "• {$s->name}")->implode("\n")
                . "\n\nType the full school name to narrow results.",
                [
                    ['title' => '🔍 Search by name', 'id' => 'link_help'],
                ],
                'school_search_results',
            );
            return;
        }

        // ── Priority 4: Exact name match (with school scope if known) ──
        $exactQuery = \App\Models\User::where('usergroup_id', 6)->where('name', $trimmed);
        if ($matchingSchool) {
            $exactQuery->where('school_id', $matchingSchool->id);
        }
        $exactMatch = $exactQuery->with(['studentAcademicLatest.standardLink.standard', 'school'])->first();

        if ($exactMatch) {
            $studentName = $exactMatch->name;
            $className = $exactMatch->studentAcademicLatest?->standardLink?->StandardSection ?? 'N/A';
            $schoolName = $exactMatch->school?->name ?? 'the school';
            $studentId = $exactMatch->id;

            $sendButtons(
                "Link to *{$studentName}* ({$className}) at {$schoolName}?",
                [
                    ['title' => '✅ Yes, link me', 'id' => "link_{$studentId}"],
                    ['title' => '🔍 Search again', 'id' => 'link_help'],
                ],
                'confirm_student_link',
            );
            return;
        }

        // ── Priority 5: Broad name search (scoped by school if known) ──
        $possibleQuery = \App\Models\User::where('usergroup_id', 6)
            ->where(function ($q) use ($trimmed) {
                $q->where('name', 'LIKE', "%{$trimmed}%")
                  ->orWhere('name', 'LIKE', "%" . str_replace(' ', '%', $trimmed) . "%");
            });
        if ($matchingSchool) {
            $possibleQuery->where('school_id', $matchingSchool->id);
        }
        $possibleStudents = $possibleQuery->with(['studentAcademicLatest.standardLink.standard', 'school'])
            ->limit(8)
            ->get();

        if ($possibleStudents->isNotEmpty()) {
            $studentLines = [];
            foreach ($possibleStudents as $student) {
                $className = $student->studentAcademicLatest?->standardLink?->StandardSection ?? 'N/A';
                $schoolName = $student->school?->name ?? 'Unknown School';
                $studentLines[] = "• {$student->name} — {$className} ({$schoolName})";
            }

            $hint = $matchingSchool
                ? "Reply with the *full name* to link."
                : "Reply with the *full name* to link, or type the school name to narrow results.";

            $sendButtons(
                "We found " . $possibleStudents->count() . " student(s) matching \"{$trimmed}\":\n\n"
                . implode("\n", $studentLines)
                . "\n\n{$hint}",
                [
                    ['title' => '🔗 Link My Number', 'id' => 'link_help'],
                    ['title' => '🎯 Try Demo', 'id' => 'demo'],
                ],
                'student_search_results',
            );
            return;
        }

        // ── Priority 6: Try matching as a school name ──
        $matchedSchools = \App\Models\School::where('name', 'LIKE', "%{$trimmed}%")
            ->orWhere('ministry_code', $trimmed)
            ->limit(5)
            ->get(['id', 'name']);

        if ($matchedSchools->isNotEmpty()) {
            $schoolList = $matchedSchools->map(fn($s) => "• {$s->name}")->implode("\n");
            $hint = $matchedSchools->count() === 1
                ? "Tap below to link to *{$matchedSchools->first()->name}*"
                : "Which school? Tap or type the full name.";

            if ($matchedSchools->count() === 1) {
                $school = $matchedSchools->first();
                $sendButtons(
                    "🏫 *{$school->name}*\n\nHow would you like to link?",
                    [
                        ['title' => '🔑 KlassApp ID', 'id' => "linktype_klassapp_{$school->id}"],
                        ['title' => '📝 Search by name', 'id' => "linktype_name_{$school->id}"],
                    ],
                    'link_type_choice',
                );
            } else {
                $sendButtons(
                    "We found {$matchedSchools->count()} schools matching \"{$trimmed}\":\n\n{$schoolList}\n\n{$hint}",
                    [
                        ['title' => '🔍 Search again', 'id' => 'link_help'],
                    ],
                    'school_search_results',
                );
            }
            return;
        }
    }

    /**
     * Link a parent's WhatsApp number to a student by system user ID.
     * Used for name-based linking (no School Pay code required).
     */
    protected function linkParentToStudent(string $phone, int $studentId, callable $sendText, callable $sendButtons, string $senderName = 'Parent'): void
    {
        $result = $this->parentLinks->linkByStudentId($phone, $studentId, $senderName);

        if ($result->outcome === 'student_not_found') {
            $sendText('Student not found. Please try again or contact the school office.', 'link_error');

            return;
        }

        if ($result->outcome === 'phone_conflict') {
            $sendText(
                "This phone number is already linked to a different parent account.\n\n"
                . 'Contact the school office to update your details.',
                'already_linked'
            );

            return;
        }

        if (! $result->linked) {
            return;
        }

        $studentName = $result->studentName;
        $className = $result->className;
        $schoolName = $result->schoolName;
        $welcomeButtons = [
            ['title' => '💰 Fee Balance', 'id' => 'FEES'],
            ['title' => '📊 Exam Results', 'id' => 'GRADES'],
            ['title' => '📋 Attendance', 'id' => 'ATTENDANCE'],
        ];

        if ($result->isNewParent) {
            $sendButtons(
                "✅ Welcome to KlassApp, *{$senderName}*!\n\n"
                . "You're now linked to *{$studentName}* ({$className}) at {$schoolName}.\n\n"
                . 'Tap an option to get started:',
                $welcomeButtons,
                'linked_welcome_new',
            );

            return;
        }

        $sendButtons(
            "✅ Linked to *{$studentName}* ({$className})!\n\n"
            . 'You can now check fees, grades, and attendance — tap an option below.',
            $welcomeButtons,
            'linked_welcome',
        );
    }

    /**
     * Verify a School Pay payment code and link the parent (Meta WABA path).
     */
    protected function processCodeVerificationForMeta(string $phone, string $code, callable $sendText, callable $sendButtons): void
    {
        $studentAcademic = StudentAcademic::where('std_school_pay_number', $code)
            ->whereNull('deleted_at')
            ->first();

        if (!$studentAcademic) {
            $sendText(
                "We couldn't find a student with payment code *{$code}*.\n\n"
                . "Either the code was entered incorrectly, or your child's school "
                . "may not use School Pay.\n\n"
                . "To help us find you:\n"
                . "• Check the code and reply with it again\n"
                . "• Or reply with your child's *SCHOOL NAME* so we can look it up",
                'code_not_found'
            );
            return;
        }

        // Find the parent linked to this student
        $parentLink = DB::table('student_parent_links')
            ->where('student_id', $studentAcademic->user_id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$parentLink) {
            // No parent linked — link directly via payment code
            $studentName = optional($studentAcademic->user)->name ?? 'your child';
            $className = optional($studentAcademic->standardLink)->StandardSection ?? '';

            WhatsAppUser::create([
                'phone'                  => $phone,
                'school_id'              => $studentAcademic->school_id,
                'student_payment_code'   => $code,
                'verified_via_schoolpay' => true,
                'verified_at'            => now(),
                'opted_in'               => true,
                'last_inbound_at'        => now(),
            ]);

            $classLine = $className ? " ({$className})" : '';
            $school = DB::table('schools')->find($studentAcademic->school_id);

            $sendButtons(
                "✅ *Welcome to KlassApp!*\n\n"
                . "Your number has been linked to *{$studentName}{$classLine}* at {$school->name}.",
                [
                    ['id' => 'FEES',        'title' => '💰 Fee Balance'],
                    ['id' => 'GRADES',      'title' => '📊 Exam Results'],
                    ['id' => 'ATTENDANCE',  'title' => '📋 Attendance'],
                ],
                'code_linked_direct'
            );
            return;
        }

        // Parent exists — check if already linked to another WhatsApp
        $existing = WhatsAppUser::where('user_id', $parentLink->parent_id)->first();

        if ($existing) {
            $sendText(
                "This student payment code is already linked to another WhatsApp number.\n\n"
                . "If you've changed your number, please contact the school office to update your details.\n\n"
                . "Send *HELP* for more options.",
                'code_already_linked'
            );
            return;
        }

        // Create the WhatsApp user record
        $school = DB::table('schools')->find($studentAcademic->school_id);
        $studentName = optional($studentAcademic->user)->name ?? 'your child';
        $className = optional($studentAcademic->standardLink)->StandardSection ?? '';

        WhatsAppUser::create([
            'phone'                  => $phone,
            'user_id'                => $parentLink->parent_id,
            'school_id'              => $studentAcademic->school_id,
            'student_payment_code'   => $code,
            'verified_via_schoolpay' => true,
            'verified_at'            => now(),
            'opted_in'               => true,
            'last_inbound_at'        => now(),
        ]);

        $classLine = $className ? " ({$className})" : '';
        $sendButtons(
            "✅ *Welcome to KlassApp!*\n\n"
            . "Your number has been linked successfully.\n"
            . "School: {$school->name}\n"
            . "Student: {$studentName}{$classLine}",
            [
                ['id' => 'FEES',        'title' => '💰 Fee Balance'],
                ['id' => 'GRADES',      'title' => '📊 Exam Results'],
                ['id' => 'ATTENDANCE',  'title' => '📋 Attendance'],
            ],
            'code_verified'
        );

        // Flush any queued notifications
        $whatsappUser = WhatsAppUser::findByPhone($phone);
        if ($whatsappUser) {
            $outboundService = app(OutboundWhatsAppService::class);
            $outboundService->flushPending($whatsappUser);
        }
    }

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
     * Route inbound message to the appropriate handler based on keyword.
     *
     * @param WhatsAppUser $user The identified WhatsApp user
     * @param string $phone E.164 phone number
     * @param string $body Raw message body
     * @param WhatsAppBusinessService $whatsAppService
     */
    private function routeInbound(WhatsAppUser $user, string $phone, string $body, $whatsAppService): void
    {
        // Strip emoji characters so list button titles (e.g. "💰 Fee Balance") match keywords
        $clean = preg_replace('/[\\x{1F600}-\\x{1F64F}\\x{1F300}-\\x{1F5FF}\\x{1F680}-\\x{1F6FF}\\x{1F1E0}-\\x{1F1FF}\\x{2600}-\\x{26FF}\\x{2700}-\\x{27BF}]/u', '', $body);
        $trimmed = strtolower(trim($body));
        $normalised = strtolower(trim($clean));
        $role = $user->user->usergroup_id;
        $hasChildren = $user->user->children()->exists();

        // Match against both raw input and emoji-stripped version
        $match = fn(array $keywords) => in_array($trimmed, $keywords) || in_array($normalised, $keywords);

        // Recognized user texting a 10-digit code → link another student
        if (preg_match('/^\d{10}$/', $normalised)) {
            $this->handleCodeForExistingUser($user, $phone, $normalised, $whatsAppService);
            return;
        }

        // Parent (ug7): KLS ID links an additional child (cross-school via ParentLinkService)
        if ($role === 7 && preg_match('/^kls\d{7}$/', $normalised)) {
            $kls = strtoupper($normalised);
            $academic = \App\Models\StudentAcademic::where('klassapp_student_id', $kls)
                ->with(['user', 'standardLink.standard', 'school'])
                ->first();

            if ($academic && $academic->user) {
                $this->linkParentToStudent(
                    $phone,
                    (int) $academic->user->id,
                    fn (string $message, ?string $flowType = null, ?int $userId = null) =>
                        $whatsAppService->sendText($phone, $message, $flowType, $userId ?? $user->user_id),
                    fn (string $bodyText, array $buttons, ?string $flowType = null, ?int $userId = null) =>
                        $this->businessApi->sendInteractiveButtons($phone, $bodyText, $buttons, $flowType, $userId ?? $user->user_id),
                    (string) ($user->user->name ?? 'Parent'),
                );

                return;
            }

            $whatsAppService->sendText(
                $phone,
                "We couldn't find a student with KlassApp ID *{$kls}*.\n\nCheck the ID on your child's report card.",
                'klassapp_id_not_found',
                $user->user_id,
            );

            return;
        }

        // Universal: menu/help
        if ($match(['menu', 'help', 'start', 'options', 'demo', '❓ help & options'])) {
            $this->sendMenu($user, $phone, $whatsAppService);
            return;
        }

        // Universal: optin / optout
        if ($trimmed === 'optin') {
            $user->update(['opted_in' => true]);
            Log::info("WhatsApp opt-in: user {$user->user_id}");
            $this->sendMenu($user, $phone, $whatsAppService);
            return;
        }

        if ($match(['optout', 'stop', 'unsubscribe'])) {
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
        if ($match(['events', 'event', 'calendar', 'news'])) {
            $this->sendEvents($user, $phone, $whatsAppService);
            return;
        }

        // -- Role-specific routing --

        // SchoolAdmin (3)
        if ($role === 3) {
            if ($match(['students', 'student', 'learner'])) {
                $this->sendStudentList($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['staff', 'teacher', 'employees'])) {
                $this->sendStaffList($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['exams', 'exam', 'marks', 'results', 'report'])) {
                $this->sendAdminExams($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['fees', 'fee', 'balance', 'payment', 'pay'])) {
                $this->sendAdminFees($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['reports', 'analytics', 'stats'])) {
                $this->sendAdminReports($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['notices', 'announcements', 'bulletin'])) {
                $this->sendNotices($user, $phone, $whatsAppService);
                return;
            }
        }

        // Teacher (5)
        if ($role === 5) {
            if ($match(['marks', 'exam', 'results', 'grades'])) {
                $this->sendTeacherMarks($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['attendance', 'absent', 'present', 'late'])) {
                $this->sendAttendance($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['timetable', 'schedule', 'periods'])) {
                $this->sendTimetable($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['assignments', 'homework', 'tasks'])) {
                $this->sendAssignments($user, $phone, $whatsAppService);
                return;
            }
        }

        // Student (6)
        if ($role === 6) {
            if ($match(['grades', 'results', 'marks', 'exam'])) {
                $this->sendStudentGrades($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['attendance', 'absent', 'present', 'late'])) {
                $this->sendStudentAttendance($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['fees', 'fee', 'balance', 'payment'])) {
                $this->sendStudentFees($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['timetable', 'schedule', 'periods'])) {
                $this->sendTimetable($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['homework', 'hw', 'assignment'])) {
                $this->sendHomework($user, $phone, $whatsAppService);
                return;
            }
        }

        // Parent (7)
        if ($role === 7) {
            if ($match(['web_login', 'web login', 'dashboard'])) {
                $this->sendParentMagicLoginLink($user, $phone, $whatsAppService);

                return;
            }
            if ($match(['grades', 'results', 'marks', 'report', 'exams'])) {
                $this->sendGrades($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['fees', 'fee', 'balance', 'payment', 'pay'])) {
                $this->sendFees($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['attendance', 'absent', 'present', 'late'])) {
                $this->sendAttendance($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['code', 'link', 'link another student'])) {
                $whatsAppService->sendText(
                    $phone,
                    "To link another student, reply with their 10-digit School Pay payment code.",
                    'link_prompt',
                    $user->user_id,
                );
                return;
            }
        }

        // Receptionist/Secretary (10)
        if ($role === 10) {
            if ($match(['notices', 'announcements', 'bulletin'])) {
                $this->sendNotices($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['calls', 'call', 'phone', 'visitor'])) {
                $this->sendCallLog($user, $phone, $whatsAppService);
                return;
            }
        }

        // Accountant/Bursar (11)
        if ($role === 11) {
            if ($match(['fees', 'fee', 'balance', 'payment', 'pay', 'collections'])) {
                $this->sendAccountantFees($user, $phone, $whatsAppService);
                return;
            }
            if ($match(['reports', 'analytics', 'financial', 'summary'])) {
                $this->sendAccountantReports($user, $phone, $whatsAppService);
                return;
            }
        }

        // Dual-role: any staff member with children can access parent features
        if ($hasChildren && !in_array($role, [6, 7])) {
            if ($match(['my children', 'children', 'kids', 'my kids'])) {
                $this->sendGrades($user, $phone, $whatsAppService);
                return;
            }
        }

        // Greetings — acknowledge and show menu
        $greetings = ['hello', 'hi', 'hey', 'greetings', 'good morning', 'good afternoon', 'good evening'];
        foreach ($greetings as $greeting) {
            if (str_starts_with($trimmed, $greeting) || str_starts_with($normalised, $greeting)) {
                $this->sendMenu($user, $phone, $whatsAppService);
                return;
            }
        }

        // Pending WhatsApp confirmation (buttons ty_/tn_ or coded YES|NO {token}).
        // After keywords so menu/FEES/opt-out stay reachable; before free-form Toshi / fallthrough.
        if (app(WhatsAppConfirmationBridge::class)
            ->handleInbound($user, $phone, $trimmed)) {
            return;
        }

        // Option B: unmatched free-form → Toshi (reads + wave-1 allowlisted task writes).
        $toshiChannel = app(\App\Services\WhatsApp\WhatsAppToshiChannelService::class);
        if ($toshiChannel->isAvailableFor($user->user)) {
            $reply = $toshiChannel->ask($user, $body);
            if ($reply === \App\Services\WhatsApp\WhatsAppToshiChannelService::CONFIRMATION_DISPATCHED) {
                // Approve/Reject buttons already sent via WhatsAppConfirmationBridge.
                return;
            }
            if (is_string($reply) && $reply !== '') {
                $whatsAppService->sendText(
                    $phone,
                    $reply,
                    'toshi_reply',
                    $user->user_id,
                );

                return;
            }
        }

        // Unknown keyword — send the actual menu with buttons
        $whatsAppService->sendText(
            $phone,
            "🤔 Sorry, I didn't understand \"{$body}\".",
            'unknown_keyword',
            $user->user_id,
        );
        $this->sendMenuButtons($phone, $role, $user->user_id);
    }

    /**
     * Send the main menu to a WhatsApp user.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppBusinessService $whatsAppService
     */
    private function sendMenu(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $name = $user->demo_name ?? $user->user->name ?? 'User';
        $role = $user->user->usergroup_id;
        $hasChildren = $user->user->children()->exists();

        // Send a brief greeting then show role-specific buttons
        $greeting = "👋 Hello, *{$name}*! What would you like to do?";
        $whatsAppService->sendText($phone, $greeting, 'menu_greeting', $user->user_id);
        $this->sendMenuButtons($phone, $role, $user->user_id);
    }

    /**
     * Send interactive menu buttons based on user role (Meta Cloud API).
     * Max 3 buttons per message — shows primary actions for the role.
     */
    private function sendMenuButtons(string $phone, int $role, ?int $userId): void
    {
        $buttons = match ($role) {
            3 => [ // SchoolAdmin
                ['id' => 'STUDENTS',  'title' => '👥 Students'],
                ['id' => 'STAFF',     'title' => '👤 Staff'],
                ['id' => 'FEES',      'title' => '💰 Fees'],
            ],
            5 => [ // Teacher
                ['id' => 'MARKS',       'title' => '📝 Marks'],
                ['id' => 'ATTENDANCE',  'title' => '✅ Attendance'],
                ['id' => 'TIMETABLE',   'title' => '🗓️ Timetable'],
            ],
            6 => [ // Student
                ['id' => 'GRADES',      'title' => '📊 Results'],
                ['id' => 'ATTENDANCE',  'title' => '✅ Attendance'],
                ['id' => 'FEES',        'title' => '💰 Fees'],
            ],
            7 => [ // Parent
                ['id' => 'FEES',        'title' => '💰 Fee Balance'],
                ['id' => 'GRADES',      'title' => '📊 Exam Results'],
                ['id' => 'ATTENDANCE',  'title' => '📋 Attendance'],
            ],
            10 => [ // Receptionist
                ['id' => 'CALLS',   'title' => '📞 Call Log'],
                ['id' => 'NOTICES', 'title' => '📢 Notices'],
                ['id' => 'EVENTS',  'title' => '🎉 Events'],
            ],
            11 => [ // Accountant
                ['id' => 'FEES',    'title' => '💰 Fees'],
                ['id' => 'REPORTS', 'title' => '📊 Reports'],
                ['id' => 'EVENTS',  'title' => '🎉 Events'],
            ],
            default => [
                ['id' => 'MENU', 'title' => '🏠 Menu'],
                ['id' => 'HELP', 'title' => '❓ Help'],
            ],
        };

        $label = match ($role) {
            3 => 'School Admin',
            5 => 'Teacher',
            6 => 'Student',
            7 => 'Parent',
            10 => 'Receptionist',
            11 => 'Accountant',
            default => 'User',
        };

        $this->businessApi->sendInteractiveButtons(
            $phone,
            "🏫 *KlassApp Menu* — {$label}\n\nTap a button below or type any option keyword.",
            $buttons,
            'menu',
            $userId,
        );
    }

    /**
     * Send follow-up action buttons after a response (fees, grades, attendance).
     */
    private function sendActionButtons(string $phone, ?int $userId, string $context = 'fees'): void
    {

        $buttons = match ($context) {
            'fees' => [
                ['id' => 'GRADES',      'title' => '📊 Exam Results'],
                ['id' => 'ATTENDANCE',  'title' => '📋 Attendance'],
                ['id' => 'MENU',        'title' => '🏠 Main Menu'],
            ],
            'grades' => [
                ['id' => 'FEES',        'title' => '💰 Fee Balance'],
                ['id' => 'ATTENDANCE',  'title' => '📋 Attendance'],
                ['id' => 'MENU',        'title' => '🏠 Main Menu'],
            ],
            'attendance' => [
                ['id' => 'FEES',        'title' => '💰 Fee Balance'],
                ['id' => 'GRADES',      'title' => '📊 Exam Results'],
                ['id' => 'MENU',        'title' => '🏠 Main Menu'],
            ],
            default => [
                ['id' => 'MENU', 'title' => '🏠 Main Menu'],
            ],
        };

        $this->businessApi->sendInteractiveButtons(
            $phone,
            "What would you like to do next?",
            $buttons,
            "{$context}_followup",
            $userId,
        );
    }
    private function sendGrades(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $children = $user->user->children()
            ->with(['userStudent.studentAcademicLatest.standardLink.standard'])
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

        foreach ($children as $link) {
            $student = $link->userStudent;
            $studentName = $user->demo_name ? "{$user->demo_name} Demo" : ($student?->name ?? 'Unknown Student');
            $academic = $student?->studentAcademicLatest;
            $className = $academic?->standardLink?->StandardSection ?? 'N/A';
            $schoolId = (int) ($link->school_id ?? $student?->school_id);

            // Find exams that have marks for this student
            $academicYear = \App\Helpers\SiteHelper::getAcademicYear($schoolId);
            $examQuery = Exam::whereHas('marks', fn($q) => $q->where('student_id', $student->id))
                ->where('school_id', $schoolId)
                ->with(['marks' => fn($q) => $q->where('student_id', $student->id), 'examType', 'subject'])
                ->latest('id');
            if ($academicYear) {
                $examQuery->where('academic_year_id', $academicYear->id);
            }
            $exams = $examQuery->take(15)->get();

            if ($exams->isEmpty()) {
                $whatsAppService->sendText(
                    $phone,
                    "📊 *{$studentName}* — _{$className}_\n\nNo results published yet.\n\nResults will appear here automatically once the school releases them.",
                    'grades_none',
                    $user->user_id,
                );
                continue;
            }

            $message = "📊 *Results for {$studentName}*\n_{$className}_\n\n";

            // Group exams by exam type for cleaner display
            $grouped = $exams->groupBy(fn($e) => $e->examType?->name ?? 'Exam');
            foreach ($grouped as $typeName => $typeExams) {
                $term = $typeExams->first()->academicTerm?->name ?? 'Term';
                $message .= "📝 *{$typeName}* — {$term}\n";

                $allMarks = collect();
                foreach ($typeExams as $exam) {
                    foreach ($exam->marks as $mark) {
                        $allMarks->push($mark);
                    }
                }

                $sorted = $allMarks->sortByDesc('marks')->take(10);
                foreach ($sorted as $mark) {
                    $subject = $mark->subject?->name ?? 'Subject';
                    $score = $mark->marks ?? 0;
                    $grade = $mark->grade ?? '-';
                    $message .= "• {$subject}: {$score}/100 ({$grade})\n";
                }

                $avg = $allMarks->avg('marks');
                if ($avg) {
                    // Class rank: count students with higher total in same exams
                    $totalScore = $allMarks->sum('marks');
                    $rank = null;
                    $totalStudents = null;
                    $examIds = $typeExams->pluck('id');
                    if ($examIds->isNotEmpty()) {
                        $allStudentsScores = \Illuminate\Support\Facades\DB::table('marks')
                            ->whereIn('exam_id', $examIds)
                            ->where('student_id', '!=', $student->id)
                            ->select('student_id', \Illuminate\Support\Facades\DB::raw('SUM(marks) as total'))
                            ->groupBy('student_id')
                            ->get()
                            ->pluck('total');
                        $totalStudents = $allStudentsScores->count() + 1;
                        $rank = $allStudentsScores->filter(fn($s) => $s > $totalScore)->count() + 1;
                    }

                    $avgRounded = round($avg, 1);
                    $celebration = '';
                    if ($avg >= 80) {
                        $celebration = "🎉 *Well done, {$studentName}!* ";
                        $celebration .= "{$avgRounded}% average is a strong performance";
                        if ($rank && $totalStudents) {
                            $celebration .= " — ranked {$rank}" . match ($rank) {1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'} . " out of {$totalStudents}";
                        }
                        $celebration .= ".\n";
                    }

                    $message .= "· · · · · · · · · · · · · · · ·\n";
                    $message .= "_Average: {$avgRounded}%_";
                    if ($rank && $totalStudents) {
                        $message .= " | #{$rank} of {$totalStudents}";
                    }
                    $message .= "\n";
                    if ($celebration) {
                        $message .= "{$celebration}";
                    }
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

        $this->sendActionButtons($phone, $user->user_id, 'grades');
    }

    /**
     * Send fee balance to a parent for ALL their children.
     * Sends one message per child.
     */
    private function sendFees(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $children = $user->user->children()
            ->with(['userStudent.studentAcademicLatest.standardLink.standard'])
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

        foreach ($children as $link) {
            $student = $link->userStudent;
            $studentName = $user->demo_name ? "{$user->demo_name} Demo" : ($student?->name ?? 'Unknown Student');
            $academic = $student?->studentAcademicLatest;
            $className = $academic?->standardLink?->StandardSection ?? 'N/A';
            $schoolId = (int) ($link->school_id ?? $student?->school_id);

            $standardId = $academic?->standardLink?->standard_id;

            $feeCategories = collect();
            if ($standardId) {
                $feeCategories = FeesCategories::where('school_id', $schoolId)
                    ->where('standard_id', $standardId)
                    ->get();
            }

            $totalFees = $feeCategories->sum('amount');

            $message = "💰 *Fee Balance — {$studentName}*\n_{$className}_\n\n";

            if ($feeCategories->isEmpty()) {
                $message .= "No fee structure found.\n\nContact the school office for details.";
            } else {
                foreach ($feeCategories as $category) {
                    $dueDate = $category->due_date ? date('d M Y', strtotime($category->due_date)) : 'N/A';
                    $message .= "• {$category->name}: UGX " . number_format($category->amount, 0) . "\n";
                    $message .= "  \xE2\x97\x8F Due: {$dueDate}\n";
                }
                $message .= "\n· · · · · · · · · · · · · · · ·\n";
                $message .= "💵 *Total fees: UGX " . number_format($totalFees, 0) . "*\n";
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

        // Follow-up action buttons
        $this->sendActionButtons($phone, $user->user_id, 'fees');
    }

    /**
     * Send attendance summary to a parent.
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppBusinessService $whatsAppService
     */
    /**
     * Send attendance summary to a parent for ALL their children.
     * Sends one message per child.
     */
    private function sendAttendance(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $children = $user->user->children()
            ->with(['userStudent.studentAcademicLatest.standardLink.standard'])
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

        foreach ($children as $link) {
            $student = $link->userStudent;
            $studentName = $user->demo_name ? "{$user->demo_name} Demo" : ($student?->name ?? 'Unknown Student');
            $academic = $student?->studentAcademicLatest;
            $className = $academic?->standardLink?->StandardSection ?? 'N/A';

            $records = Attendance::where('user_id', $student->id)
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
        $presentDays = $records->where('status', 1)->count();
        $absentDays = $records->where('status', 0)->count();
        $lateDays = $records->where('status', 2)->count();
        $rate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

        $tone = '';
        if ($rate < 80) {
            $tone = "⚠️ *{$studentName} has missed {$absentDays} school day(s)* this month.\nThat's " . (100 - $rate) . "% of learning time lost.\n\n";
        } elseif ($rate > 90) {
            $tone = "✅ *Great attendance!* {$studentName} has been in class {$presentDays} out of {$totalDays} days.\n\n";
        }

        $message = "📅 *Attendance — {$studentName}*\n_{$className}_\n_This month_\n\n";
        $message .= "✅ Present: {$presentDays}\n";
        $message .= "❌ Absent: {$absentDays}\n";
        $message .= "⏰ Late: {$lateDays}\n";
        $message .= "📊 Rate: {$rate}%\n";

        if ($tone) {
            $message .= "\n{$tone}";
        }

        $recentAbsent = $records->where('status', '!=', 1)->take(3);
        if ($recentAbsent->isNotEmpty()) {
            $message .= "· · · · · · · · · · · · · · · ·\n";
            $message .= "Recent non-attendance:\n";
            foreach ($recentAbsent as $record) {
                $status = $record->status == 2 ? 'Late' : 'Absent';
                $message .= "  • " . Carbon::parse($record->date)->format('d M Y') . " ({$status})\n";
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

        $this->sendActionButtons($phone, $user->user_id, 'attendance');
    }

    /**
     * Send events list (upcoming school events).
     *
     * @param WhatsAppUser $user
     * @param string $phone
     * @param WhatsAppBusinessService $whatsAppService
     */
    private function sendEvents(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendStudentGrades(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $student = $user->user;
        $studentName = $student->name;
        $className = $student->studentAcademic?->standard?->name ?? 'N/A';

        // Find exams that have marks for this student
        $academicYear = \App\Helpers\SiteHelper::getAcademicYear($student->school_id);
        $examQuery = Exam::whereHas('marks', fn($q) => $q->where('student_id', $student->id))
            ->with(['marks' => fn($q) => $q->where('student_id', $student->id), 'examType', 'subject']);
        if ($academicYear) {
            $examQuery->where('academic_year_id', $academicYear->id);
        }

        $exams = $examQuery->latest('id')->take(15)->get();

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
        $grouped = $exams->groupBy(fn($e) => $e->examType?->name ?? 'Exam');
        foreach ($grouped as $typeName => $typeExams) {
            $term = $typeExams->first()->academicTerm?->name ?? 'Term';
            $message .= "📝 *{$typeName}* — {$term}\n";

            $allMarks = collect();
            foreach ($typeExams as $exam) {
                foreach ($exam->marks as $mark) {
                    $allMarks->push($mark);
                }
            }

            $sorted = $allMarks->sortByDesc('marks')->take(10);
            foreach ($sorted as $mark) {
                $subject = $mark->subject?->name ?? 'Subject';
                $score = $mark->marks ?? 0;
                $grade = $mark->grade ?? '-';
                $message .= "• {$subject}: {$score}/100 ({$grade})\n";
            }

            $avg = $allMarks->avg('marks');
            if ($avg) {
                $message .= "_Average: " . round($avg, 1) . "%_\n";
            }
            $message .= "\n";
        }

        $whatsAppService->sendText($phone, $message, 'grades', $user->user_id);
    }

    /**
     * Send attendance summary to a student for their own record.
     */
    private function sendStudentAttendance(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $student = $user->user;
        $academicYear = \App\Helpers\SiteHelper::getAcademicYear($student->school_id);

        $records = Attendance::where('user_id', $student->id)
            ->when($academicYear, fn ($q) => $q->whereBetween('date', [
                $academicYear->start_date ?? Carbon::now()->startOfYear(),
                $academicYear->end_date ?? Carbon::now()->endOfYear(),
            ]))
            ->orderBy('date', 'desc')
            ->get();

        $totalDays = $records->count();
        $presentDays = $records->where('status', 1)->count();
        $absentDays = $records->where('status', 0)->count();
        $rate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

        $message = "📅 *My Attendance*\n_{$student->name}_\n\n";
        $message .= "✅ Present: {$presentDays}\n";
        $message .= "❌ Absent: {$absentDays}\n";
        $message .= "📊 Rate: {$rate}%\n\n";

        $recentAbsent = $records->where('status', '!=', 1)->take(3);
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
    private function sendStudentFees(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendTeacherMarks(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendTimetable(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $message = "📋 *My Timetable*\n\n";
        $message .= "Timetable is available on the KlassApp portal.\n\n";
        $message .= "Log in to view or download your full schedule.";

        $whatsAppService->sendText($phone, $message, 'timetable', $user->user_id);
    }

    /**
     * Send assignments overview for a teacher.
     */
    private function sendAssignments(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $message = "📖 *My Assignments*\n\n";
        $message .= "Assignments can be managed via the KlassApp portal.\n\n";
        $message .= "Log in to create or review assignments.";

        $whatsAppService->sendText($phone, $message, 'assignments', $user->user_id);
    }

    /**
     * Send homework overview for a student.
     */
    private function sendHomework(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendStudentList(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendStaffList(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendAdminExams(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendAdminFees(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendAdminReports(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendNotices(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $message = "📢 *School Announcements*\n\n";
        $message .= "Notices are available on the KlassApp portal.\n\n";
        $message .= "_Log in to view all announcements._";

        $whatsAppService->sendText($phone, $message, 'notices', $user->user_id);
    }

    /**
     * Send call log summary to receptionist.
     */
    private function sendCallLog(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendAccountantFees(WhatsAppUser $user, string $phone, $whatsAppService): void
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
    private function sendAccountantReports(WhatsAppUser $user, string $phone, $whatsAppService): void
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

    /**
     * Send a signed parent dashboard magic link (15 min, single-use).
     */
    private function sendParentMagicLoginLink(WhatsAppUser $user, string $phone, $whatsAppService): void
    {
        $parent = $user->user;

        if (! $parent || (int) $parent->usergroup_id !== 7) {
            $whatsAppService->sendText(
                $phone,
                'Parent web login is only available for linked parent accounts.',
                'parent_magic_login_denied',
                $user->user_id,
            );

            return;
        }

        $magicLogin = app(ParentMagicLoginService::class);

        if (! $magicLogin->canIssueLink($parent)) {
            $whatsAppService->sendText(
                $phone,
                "Link at least one child to your WhatsApp number first (send a KlassApp ID such as KLS1020001), then reply *WEB_LOGIN*.",
                'parent_magic_login_denied',
                $user->user_id,
            );

            return;
        }

        $rateKey = ParentMagicLoginService::rateLimitKey($phone);
        if (RateLimiter::tooManyAttempts($rateKey, ParentMagicLoginService::RATE_LIMIT_PER_HOUR)) {
            $whatsAppService->sendText(
                $phone,
                'Too many login links requested. Please wait an hour and try again.',
                'parent_magic_login_rate_limited',
                $user->user_id,
            );

            return;
        }

        $url = $magicLogin->issueLinkForPhone($phone, $parent);

        if (! $url) {
            $whatsAppService->sendText(
                $phone,
                'Unable to generate a login link right now. Please try again shortly.',
                'parent_magic_login_error',
                $user->user_id,
            );

            return;
        }

        $whatsAppService->sendText(
            $phone,
            "🌐 *Parent dashboard login*\n\n"
            ."Tap the link below to open your dashboard. Valid for ".ParentMagicLoginService::TTL_MINUTES." minutes, one use only:\n\n"
            .$url,
            'parent_magic_login',
            $parent->id,
            previewUrl: true,
        );
    }

    /**
     * Handle a 10-digit payment code from an already-recognized user
     * wanting to link another student.
     */
    private function handleCodeForExistingUser(
        WhatsAppUser $user,
        string $phone,
        string $code,
        $whatsAppService,
    ): void {
        $result = $this->parentLinks->linkByPaymentCodeForExistingUser($user, $code);

        if ($result->outcome === 'code_not_found') {
            $whatsAppService->sendText(
                $phone,
                "We couldn't find a student with payment code *{$code}*.\n\n"
                . 'Check the code and try again, or contact the school office for help.',
                'link_code_not_found',
                $user->user_id,
            );

            return;
        }

        if ($result->alreadyLinkedToThisParent) {
            $whatsAppService->sendText(
                $phone,
                'This student is already linked to your account.',
                'link_already_linked',
                $user->user_id,
            );

            return;
        }

        if (! $result->linked) {
            return;
        }

        $studentName = $result->studentName ?? 'your child';

        $whatsAppService->sendList(
            phone: $phone,
            title: '✅ Another student linked!',
            sections: [
                [
                    'title' => 'Quick Actions',
                    'rows' => [
                        ['title' => '💰 Fee Balance', 'description' => 'Check fees for this student'],
                        ['title' => '📊 Exam Results', 'description' => 'View grades and reports'],
                        ['title' => '📋 Attendance', 'description' => 'See attendance records'],
                        ['title' => '❓ Help & Options', 'description' => 'Full menu and support'],
                    ],
                ],
            ],
            description: "Student: {$studentName}",
            footerText: 'Tap an option to get started',
            flowType: 'link_another_student',
            userId: $user->user_id,
        );
    }
}
