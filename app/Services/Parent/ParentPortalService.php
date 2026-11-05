<?php

namespace App\Services\Parent;

use App\Contracts\Results\ReleaseGate;
use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\StudentHealthIncident;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Services\StudentFeeResolver;
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
    public function __construct(private ReleaseGate $releases) {}

    /**
     * @return Collection<int, User>
     */
    public function linkedStudents(User $parent): Collection
    {
        return StudentParentLink::query()
            ->where('parent_id', $parent->id)
            ->where('status', 1)
            ->with(['userStudent.userprofile', 'userStudent.studentAcademicLatest.standardLink.standard'])
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
                return str_contains(mb_strtolower((string) $student->name), $needle)
                    || str_contains(mb_strtolower((string) $student->displayName), $needle);
            })->values();

            if ($matches->count() === 1) {
                return ['ok' => true, 'student' => $matches->first(), 'students' => $students];
            }

            if ($matches->isEmpty()) {
                $names = $students->map(fn (User $s) => $s->displayName ?: $s->name)->implode(', ');

                return [
                    'ok' => false,
                    'denied' => false,
                    'message' => "No linked child matched \"{$childName}\". Your children: {$names}.",
                    'students' => $students,
                ];
            }

            $names = $matches->map(fn (User $s) => $s->displayName ?: $s->name)->implode(', ');

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
                'message' => 'No children are linked to your account. Contact the school office to link a student.',
                'children' => [],
                'count' => 0,
                'grouped_by_school' => [],
            ];
        }

        $schoolNames = School::query()
            ->whereIn('id', $students->map(fn (User $s) => $this->schoolIdForLinkedChild($parent, $s))->filter()->unique())
            ->pluck('name', 'id');

        $children = $students->values()->map(function (User $student, int $i) use ($parent, $schoolNames) {
            $schoolId = $this->schoolIdForLinkedChild($parent, $student);

            return [
                'ordinal' => $i + 1,
                'student_id' => $student->id,
                'name' => $student->displayName ?: $student->name,
                'school_id' => $schoolId,
                'school_name' => $schoolId ? ($schoolNames[$schoolId] ?? 'School') : 'School',
                'class' => $student->studentAcademicLatest?->standardLink?->StandardSection ?? 'N/A',
            ];
        })->all();

        return [
            'success' => true,
            'children' => $children,
            'count' => count($children),
            'grouped_by_school' => $this->groupChildrenBySchool($children),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return list<array{school_id: int|null, school_name: string, children: list<array<string, mixed>>}>
     */
    public function groupChildrenBySchool(array $children): array
    {
        $groups = [];
        foreach ($children as $child) {
            $key = (string) ($child['school_id'] ?? 'none');
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'school_id' => $child['school_id'] ?? null,
                    'school_name' => $child['school_name'] ?? 'School',
                    'children' => [],
                ];
            }
            $groups[$key]['children'][] = $child;
        }

        return array_values($groups);
    }

    /**
     * Resolve the selected child for the web dashboard.
     * Unlinked / missing child query params fall back to the first linked child
     * (never leak whether an unlinked student_id exists).
     *
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>|null
     */
    public function selectChild(array $children, ?int $requestedStudentId): ?array
    {
        if ($children === []) {
            return null;
        }

        if ($requestedStudentId !== null) {
            foreach ($children as $child) {
                if ((int) $child['student_id'] === $requestedStudentId) {
                    return $child;
                }
            }
        }

        return $children[0];
    }

    /**
     * Full dashboard payload for one linked child (fees, grades, attendance).
     *
     * @return array{child: array<string, mixed>, fees: array<string, mixed>|null, grades: array<string, mixed>|null, attendance: array<string, mixed>|null}
     */
    public function childDashboard(User $parent, int $studentId): array
    {
        $fees = $this->feeBalance($parent, null, $studentId);
        $grades = $this->grades($parent, null, $studentId);
        $attendance = $this->attendance($parent, null, $studentId);

        return [
            'fees' => ($fees['success'] ?? false) ? ($fees['data'] ?? null) : null,
            'fees_message' => $fees['message'] ?? null,
            'grades' => ($grades['success'] ?? false) ? ($grades['data'] ?? null) : null,
            'grades_message' => $grades['message'] ?? null,
            'attendance' => ($attendance['success'] ?? false) ? ($attendance['data'] ?? null) : null,
            'attendance_message' => $attendance['message'] ?? null,
            'denied' => ($fees['denied'] ?? false) || ($grades['denied'] ?? false) || ($attendance['denied'] ?? false),
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

        return [
            'success' => true,
            'data' => $this->feeSummary($student, $schoolId),
        ];
    }

    /**
     * Fee structure, amount paid, and balance for one student at one school.
     * Parent and student pages both read this so the figures cannot drift.
     *
     * @return array<string, mixed>
     */
    public function feeSummary(User $student, int $schoolId): array
    {
        $fees = app(StudentFeeResolver::class);
        // One balance for the term in progress. A fee with no term still counts.
        $categories = $fees->forStudent($student, $schoolId, $fees->currentTermId($schoolId));

        $totalFees = (float) $categories->sum('amount');
        // fee_payments.user_id is the student user id (see FeePayment::student()).
        $totalPaid = (float) FeePayment::where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->sum('amount');
        $totalBalance = max(0, $totalFees - $totalPaid);

        $categoryRows = $categories->map(fn ($cat) => [
            'name' => $cat->name,
            'amount' => (float) $cat->amount,
            'balance' => $totalBalance <= 0 ? 0.0 : max(0, (float) $cat->amount),
        ])->values()->all();

        return [
            'student_id' => $student->id,
            'student_name' => $student->displayName ?: $student->name,
            'school_id' => $schoolId,
            'total_fees' => $totalFees,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'categories' => $categoryRows,
            'chart' => [
                'labels' => ['Paid', 'Due'],
                'paid' => $totalPaid,
                'due' => $totalBalance,
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

        return [
            'success' => true,
            'data' => $this->attendanceSummary($student, $schoolId),
        ];
    }

    /**
     * Last-month attendance for one student. Parent and student pages share this.
     *
     * @return array<string, mixed>
     */
    public function attendanceSummary(User $student, ?int $schoolId): array
    {
        $since = Carbon::now()->subMonth();
        $records = Attendance::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where('user_id', $student->id)
            ->where('date', '>=', $since->toDateString())
            ->orderBy('date')
            ->get();

        $present = $records->where('status', 1)->count();
        $absent = $records->where('status', 0)->count();
        $late = $records->where('status', 2)->count();

        $buckets = [];
        foreach ($records as $record) {
            $label = Carbon::parse($record->date)->startOfWeek()->format('d M');
            $buckets[$label] ??= ['present' => 0, 'absent' => 0];
            if ((int) $record->status === 1) {
                $buckets[$label]['present']++;
            } elseif ((int) $record->status === 0) {
                $buckets[$label]['absent']++;
            }
        }

        $recentAbsences = $records->where('status', '!=', 1)->sortByDesc('date')->take(5)->map(fn ($r) => [
            'date' => Carbon::parse($r->date)->format('d M Y'),
            'reason' => (int) $r->status === 2 ? 'Late' : 'Absent',
        ])->values()->all();

        return [
            'student_id' => $student->id,
            'student_name' => $student->displayName ?: $student->name,
            'school_id' => $schoolId,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'total' => $records->count(),
            'since' => $since->format('d M Y'),
            'recent_absences' => $recentAbsences,
            'recent' => $records->sortByDesc('date')->take(20)->map(fn ($r) => [
                'date' => Carbon::parse($r->date)->format('d M Y'),
                'session' => ucfirst((string) $r->session),
                'status' => (int) $r->status === 1 ? 'Present' : ((int) $r->status === 2 ? 'Late' : 'Absent'),
            ])->values()->all(),
            'chart' => [
                'labels' => array_keys($buckets),
                'present' => array_values(array_map(fn ($row) => $row['present'], $buckets)),
                'absent' => array_values(array_map(fn ($row) => $row['absent'], $buckets)),
            ],
        ];
    }

    /**
     * @return array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}
     */
    public function grades(User $parent, ?string $childName = null, ?int $studentId = null, string|int|null $year = null): array
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

        $pack = $this->releasedResults($student, $schoolId, $year);
        $name = $student->displayName ?: $student->name;

        return [
            'success' => true,
            'data' => $pack,
            'message' => $pack['exam_groups'] === []
                ? "No results published yet for {$name}."
                : null,
        ];
    }

    /**
     * Every released exam and test set for one student.
     *
     * Nothing unreleased is included: the release gate is applied before the
     * year filter and there is no row cap. `$year` null or "current" selects
     * the school's current academic year. "all" returns every year. An id
     * selects that year when it belongs to the school.
     *
     * @return array<string, mixed>
     */
    public function releasedResults(User $student, int $schoolId, string|int|null $year = null): array
    {
        $emptyChart = ['labels' => [], 'scores' => []];
        $name = $student->displayName ?: $student->name;
        $years = $schoolId > 0
            ? AcademicYear::query()
                ->where('school_id', $schoolId)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get()
            : collect();
        $current = $schoolId > 0 ? SiteHelper::getAcademicYear($schoolId) : null;
        $currentId = $current ? (int) $current->id : null;
        $yearRows = $years->map(fn (AcademicYear $row) => [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'current' => $currentId !== null && (int) $row->id === $currentId,
        ])->values()->all();
        $selected = $this->resolveResultsYear($year, $currentId, array_column($yearRows, 'id'));

        $base = [
            'student_id' => $student->id,
            'student_name' => $name,
            'school_id' => $schoolId > 0 ? $schoolId : null,
            'exam_groups' => [],
            'years' => $yearRows,
            'selected_year' => $selected,
            'current_year_id' => $currentId,
            'chart' => $emptyChart,
        ];

        if ($schoolId <= 0) {
            return $base;
        }

        $examQuery = Exam::query()
            ->where('school_id', $schoolId)
            ->whereHas('marks', fn ($q) => $q->where('school_id', $schoolId)->where('student_id', $student->id))
            ->with([
                'marks' => fn ($q) => $q->where('school_id', $schoolId)->where('student_id', $student->id)->with('subject'),
                'examType',
                'subject',
                'academicYear',
                'academicTerm',
            ])
            ->orderBy('id');

        // Release gate stays exactly as it is. The year filter never widens it.
        $this->releases->applyToExams($examQuery, $student);

        if ($selected !== 'all') {
            $examQuery->where('academic_year_id', $selected);
        }

        $exams = $examQuery->get()->sortBy(function (Exam $exam) {
            $yearStart = (string) ($exam->academicYear?->start_date ?? '');
            $termStart = (string) ($exam->academicTerm?->starts_on ?? '');

            return $yearStart.'|'.$termStart.'|'.str_pad((string) $exam->id, 10, '0', STR_PAD_LEFT);
        })->values();

        $examGroups = [];
        foreach ($exams->groupBy(function (Exam $exam) {
            return implode('|', [
                (string) ($exam->academic_year_id ?? ''),
                (string) ($exam->academic_term_id ?? ''),
                (string) ($exam->exam_type_id ?? $exam->examType?->name ?? 'Exam'),
            ]);
        }) as $typeExams) {
            $first = $typeExams->first();
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

            $label = implode(' · ', array_filter([
                $first->academicYear?->name,
                $first->academicTerm?->name,
                $first->examType?->name ?? 'Exam',
            ], fn ($part) => $part !== null && $part !== ''));

            $average = $subjects === []
                ? 0.0
                : round(array_sum(array_column($subjects, 'score')) / count($subjects), 1);

            $examGroups[] = [
                'exam_type' => $label !== '' ? $label : 'Exam',
                'year_id' => $first->academic_year_id ? (int) $first->academic_year_id : null,
                'subjects' => $subjects,
                'average' => $average,
            ];
        }

        $base['exam_groups'] = $examGroups;
        $base['chart'] = [
            'labels' => array_column($examGroups, 'exam_type'),
            'scores' => array_map(fn ($group) => $group['average'], $examGroups),
        ];

        return $base;
    }

    /**
     * @param  list<int>  $schoolYearIds
     */
    private function resolveResultsYear(string|int|null $year, ?int $currentYearId, array $schoolYearIds): string|int
    {
        if ($year === null || $year === '' || $year === 'current') {
            return $currentYearId ?? 'all';
        }

        if ($year === 'all') {
            return 'all';
        }

        $id = (int) $year;
        if ($id > 0 && in_array($id, $schoolYearIds, true)) {
            return $id;
        }

        return $currentYearId ?? 'all';
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
                'student_name' => $student->displayName ?: $student->name,
                'school_id' => $schoolId,
                'records' => $records,
                'count' => count($records),
            ],
            'message' => $records === [] ? 'No health records found for '.($student->displayName ?: $student->name).'.' : null,
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
