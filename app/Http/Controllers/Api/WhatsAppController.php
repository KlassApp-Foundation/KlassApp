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
}
