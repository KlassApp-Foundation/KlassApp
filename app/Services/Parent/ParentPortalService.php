<?php

namespace App\Services\Parent;

use App\Helpers\SiteHelper;
use App\Models\Academics\Exam;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\StudentAcademic;
use App\Models\StudentHealthIncident;
use App\Models\StudentParentLink;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared read-only parent portal data layer for WhatsApp (via ParentActionService)
 * and the authenticated web parent dashboard.
 *
 * Every child-scoped query resolves ownership through resolveChild() — never trust
 * a route or request student_id without verifying student_parent_links.
 */
class ParentPortalService
{
    /**
     * @return Collection<int, User>
     */
    public function linkedStudents(User $parent): Collection
    {
        return StudentParentLink::query()
            ->where('parent_id', $parent->id)
            ->where('status', 1)
            ->with(['userStudent.studentAcademicLatest.standardLink.standard'])
            ->get()
            ->map(fn (StudentParentLink $link) => $link->userStudent)
            ->filter()
            ->values();
    }

    /**
     * Resolve a child by optional name / ordinal / explicit student id against linked students only.
     *
     * @return array{ok: bool, student?: User, message?: string, students?: Collection<int, User>, denied?: bool}
     */
    public function resolveChild(User $parent, ?string $childName = null, ?int $studentId = null): array
    {
        $students = $this->linkedStudents($parent);

        if ($students->isEmpty()) {
            return [
                'ok' => false,
                'denied' => false,
                'message' => 'No children are linked to your account. Contact the school office to link a student.',
                'students' => $students,
            ];
        }

        if ($studentId !== null) {
            $match = $students->firstWhere('id', $studentId);
            if (! $match) {
                return [
                    'ok' => false,
                    'denied' => true,
                    'message' => 'That student is not linked to your account.',
                    'students' => $students,
                ];
            }

            return ['ok' => true, 'student' => $match, 'students' => $students];
        }

        if ($childName !== null && trim($childName) !== '') {
            $needle = mb_strtolower(trim($childName));

            if (ctype_digit($needle)) {
                $ordinal = (int) $needle;
                if ($ordinal >= 1 && $ordinal <= $students->count()) {
                    return ['ok' => true, 'student' => $students[$ordinal - 1], 'students' => $students];
                }
            }

            $matches = $students->filter(function (User $student) use ($needle) {
                return str_contains(mb_strtolower((string) $student->name), $needle);
            })->values();

            if ($matches->count() === 1) {
                return ['ok' => true, 'student' => $matches->first(), 'students' => $students];
            }

            if ($matches->isEmpty()) {
                $names = $students->map(fn (User $s) => $s->name)->implode(', ');

                return [
                    'ok' => false,
                    'denied' => false,
                    'message' => "No linked child matched \"{$childName}\". Your children: {$names}.",
                    'students' => $students,
                ];
            }

            $names = $matches->map(fn (User $s) => $s->name)->implode(', ');

            return [
                'ok' => false,
                'denied' => false,
                'message' => "Several children matched \"{$childName}\" ({$names}). Please be more specific.",
                'students' => $students,
            ];
        }

        if ($students->count() === 1) {
            return ['ok' => true, 'student' => $students->first(), 'students' => $students];
        }

        return [
            'ok' => false,
            'denied' => false,
            'message' => 'You have more than one linked child. Specify which one.',
            'students' => $students,
        ];
    }

    /**
     * @return array{success: bool, message?: string, children?: list<array<string, mixed>>, count?: int}
     */
    public function listChildren(User $parent): array
    {
        $students = $this->linkedStudents($parent);

        if ($students->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No children linked to your account.',
                'children' => [],
                'count' => 0,
            ];
        }

        $children = $students->values()->map(function (User $student, int $i) use ($parent) {
            $schoolId = $this->schoolIdForLinkedChild($parent, $student);

            return [
                'ordinal' => $i + 1,
                'student_id' => $student->id,
                'name' => $student->name,
                'school_id' => $schoolId,
                'class' => $student->studentAcademicLatest?->standardLink?->StandardSection ?? 'N/A',
            ];
        })->all();

        return [
            'success' => true,
            'children' => $children,
            'count' => count($children),
        ];
    }

    /**
     * @return array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}
     */
    public function feeBalance(User $parent, ?string $childName = null, ?int $studentId = null): array
    {
        $resolved = $this->resolveChild($parent, $childName, $studentId);
        if (! $resolved['ok']) {
            return $this->failure($resolved);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $schoolId = $this->schoolIdForLinkedChild($parent, $student);
        if ($schoolId === null) {
            return [
                'success' => false,
                'message' => 'Could not determine the school for this child.',
            ];
        }

        $academic = StudentAcademic::where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->first();

        $standardId = $academic?->standardLink?->standard_id
            ?? $student->studentAcademicLatest?->standardLink?->standard_id;

        $categories = collect();
        if ($standardId) {
            $categories = FeesCategories::where('school_id', $schoolId)
                ->where('standard_id', $standardId)
                ->get();
        }

        $totalFees = (float) $categories->sum('amount');
        $totalPaid = (float) FeePayment::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->sum('amount');
        $totalBalance = max(0, $totalFees - $totalPaid);

        $categoryRows = $categories->map(fn ($cat) => [
            'name' => $cat->name,
            'amount' => (float) $cat->amount,
            'balance' => $totalBalance <= 0 ? 0.0 : max(0, (float) $cat->amount),
        ])->values()->all();

        return [
            'success' => true,
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'school_id' => $schoolId,
                'total_fees' => $totalFees,
                'total_paid' => $totalPaid,
                'total_balance' => $totalBalance,
                'categories' => $categoryRows,
            ],
        ];
    }

    /**
     * @return array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}
     */
    public function attendance(User $parent, ?string $childName = null, ?int $studentId = null): array
    {
        $resolved = $this->resolveChild($parent, $childName, $studentId);
        if (! $resolved['ok']) {
            return $this->failure($resolved);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $schoolId = $this->schoolIdForLinkedChild($parent, $student);

        $records = Attendance::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where('user_id', $student->id)
            ->where('date', '>=', Carbon::now()->subMonth())
            ->orderByDesc('date')
            ->get();

        $present = $records->where('status', 1)->count();
        $absent = $records->where('status', 0)->count();
        $recentAbsences = $records->where('status', '!=', 1)->take(5)->map(fn ($r) => [
            'date' => Carbon::parse($r->date)->format('d M Y'),
            'reason' => (int) $r->status === 2 ? 'Late' : 'Absent',
        ])->values()->all();

        return [
            'success' => true,
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'school_id' => $schoolId,
                'present' => $present,
                'absent' => $absent,
                'total' => $records->count(),
                'recent_absences' => $recentAbsences,
            ],
        ];
    }

    /**
     * @return array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}
     */
    public function grades(User $parent, ?string $childName = null, ?int $studentId = null): array
    {
        $resolved = $this->resolveChild($parent, $childName, $studentId);
        if (! $resolved['ok']) {
            return $this->failure($resolved);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $schoolId = $this->schoolIdForLinkedChild($parent, $student);
        if ($schoolId === null) {
            return [
                'success' => false,
                'message' => 'Could not determine the school for this child.',
            ];
        }

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        $examQuery = Exam::whereHas('marks', fn ($q) => $q->where('student_id', $student->id))
            ->where('school_id', $schoolId)
            ->with([
                'marks' => fn ($q) => $q->where('student_id', $student->id)->with('subject'),
                'examType',
                'subject',
            ])
            ->latest('id');

        if ($academicYear) {
            $examQuery->where('academic_year_id', $academicYear->id);
        }

        $exams = $examQuery->take(15)->get();
        if ($exams->isEmpty()) {
            return [
                'success' => true,
                'data' => [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'school_id' => $schoolId,
                    'exam_groups' => [],
                ],
                'message' => "No results published yet for {$student->name}.",
            ];
        }

        $examGroups = [];
        foreach ($exams->groupBy(fn ($e) => $e->examType?->name ?? 'Exam') as $typeName => $typeExams) {
            $subjects = [];
            foreach ($typeExams as $exam) {
                foreach ($exam->marks as $mark) {
                    $subjects[] = [
                        'name' => $mark->subject?->name ?? $exam->subject?->name ?? 'Subject',
                        'score' => (float) ($mark->marks ?? 0),
                        'total' => 100,
                        'grade' => $mark->grade ?? '-',
                    ];
                }
            }

            $examGroups[] = [
                'exam_type' => (string) $typeName,
                'subjects' => $subjects,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'school_id' => $schoolId,
                'exam_groups' => $examGroups,
            ],
        ];
    }

    /**
     * @return array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}
     */
    public function health(User $parent, ?string $childName = null, ?int $studentId = null): array
    {
        $resolved = $this->resolveChild($parent, $childName, $studentId);
        if (! $resolved['ok']) {
            return $this->failure($resolved);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $schoolId = $this->schoolIdForLinkedChild($parent, $student);
        if ($schoolId === null) {
            return [
                'success' => false,
                'message' => 'Could not determine the school for this child.',
            ];
        }

        $incidents = StudentHealthIncident::whereSchool($schoolId)
            ->where('user_id', $student->id)
            ->orderByDesc('incident_date')
            ->limit(10)
            ->get();

        $records = $incidents->map(fn (StudentHealthIncident $incident) => [
            'date' => optional($incident->incident_date)->format('d M Y'),
            'type' => ucfirst((string) ($incident->severity ?? 'checkup')),
            'notes' => trim(($incident->description ?? '').($incident->action_taken ? ' — '.$incident->action_taken : '')),
        ])->all();

        return [
            'success' => true,
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'school_id' => $schoolId,
                'records' => $records,
                'count' => count($records),
            ],
            'message' => $records === [] ? "No health records found for {$student->name}." : null,
        ];
    }

    /**
     * Tenant scope for a linked child: prefer student_parent_links.school_id, then the student's school.
     */
    public function schoolIdForLinkedChild(User $parent, User $student): ?int
    {
        $link = StudentParentLink::query()
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->where('status', 1)
            ->first();

        if ($link?->school_id) {
            return (int) $link->school_id;
        }

        if ($student->school_id) {
            return (int) $student->school_id;
        }

        return $parent->school_id ? (int) $parent->school_id : null;
    }

    /**
     * @param  array{ok: bool, message?: string, denied?: bool}  $resolved
     * @return array{success: bool, message: string, denied?: bool}
     */
    private function failure(array $resolved): array
    {
        return [
            'success' => false,
            'message' => $resolved['message'] ?? 'Unable to resolve child.',
            'denied' => (bool) ($resolved['denied'] ?? false),
        ];
    }
}
