<?php

namespace App\Services\Parent;

use App\Contracts\Results\ReleaseGate;
use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\StudentHealthIncident;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Services\Results\FamilyReleasedCopy;
use App\Services\StudentFeeResolver;
use App\Services\StudentReportCardService;
use App\Support\AttendanceDays;
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
        // One balance for the term in progress. A fee with no term still counts,
        // except a yearly fee, which counts only in its due term.
        $categories = $fees->forStudent($student, $schoolId, $fees->currentTermId($schoolId));
        // The year total includes that yearly fee once, not once per term.
        $yearTotal = (float) $fees->forYear($student, $schoolId)->sum(fn ($fee) => (float) $fee->amount);

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

        $terms = $this->feeTerms($student, $schoolId, $totalFees, $totalPaid);
        $payments = FeePayment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(fn (FeePayment $payment) => [
                'date' => optional($payment->paid_on)->format('j M') ?? '',
                'method' => $this->paymentMethodLabel((string) $payment->payment_method),
                'amount' => (float) $payment->amount,
            ])
            ->all();

        return [
            'student_id' => $student->id,
            'student_name' => $student->displayName ?: $student->name,
            'school_id' => $schoolId,
            'total_fees' => $totalFees,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'year_total' => $yearTotal,
            'categories' => $categoryRows,
            'terms' => $terms,
            'payments' => $payments,
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

        $days = $this->attendanceDays($student, $schoolId);

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
            'days_present' => $days['days_present'],
            'days_present_label' => $days['days_present_label'],
            'days_total' => $days['days_total'],
            'days_percent' => $days['days_percent'],
            'this_week_present' => $days['this_week_present'],
            'this_week_label' => $days['this_week_label'],
            'this_week_total' => $days['this_week_total'],
            'weeks' => $days['weeks'],
            'missed_days' => $days['missed_days'],
            'term_label' => $days['term_label'],
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

        $releasedCopy = app(FamilyReleasedCopy::class);
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
                $frozen = $releasedCopy->subjectsFor($student, $schoolId, $exam);
                foreach ($exam->marks as $mark) {
                    $subjectId = (int) ($mark->subject_id ?? 0);
                    if ($frozen !== null) {
                        if (! isset($frozen[$subjectId])) {
                            continue;
                        }
                        $score = $frozen[$subjectId]['mark'];
                        $grade = $frozen[$subjectId]['grade'] ?? '-';
                    } else {
                        $score = (float) ($mark->marks ?? 0);
                        $grade = $mark->grade ?? '-';
                    }
                    $subjects[] = [
                        'name' => $mark->subject?->name ?? $exam->subject?->name ?? 'Subject',
                        'score' => $score,
                        'total' => 100,
                        'grade' => $grade,
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
     * Marks, attendance, and fees for one student at one school.
     * A failed section is flagged and left empty so the others still render.
     *
     * @return array{
     *     grades: array<string, mixed>,
     *     attendance: array<string, mixed>,
     *     fees: array<string, mixed>,
     *     headline: array<string, mixed>,
     *     errors: array{marks: bool, attendance: bool, fees: bool}
     * }
     */
    public function bundle(User $student, int $schoolId, string|int|null $year = null): array
    {
        $errors = ['marks' => false, 'attendance' => false, 'fees' => false];

        try {
            $grades = $this->releasedResults($student, $schoolId, $year);
        } catch (\Throwable $e) {
            report($e);
            $grades = [
                'student_id' => $student->id,
                'student_name' => $student->displayName ?: $student->name,
                'school_id' => $schoolId,
                'exam_groups' => [],
                'years' => [],
                'selected_year' => 'all',
                'chart' => ['labels' => [], 'scores' => []],
            ];
            $errors['marks'] = true;
        }

        try {
            $attendance = $this->attendanceSummary($student, $schoolId);
        } catch (\Throwable $e) {
            report($e);
            $attendance = ['days_present' => 0, 'days_total' => 0, 'days_percent' => null, 'weeks' => [], 'missed_days' => [], 'term_label' => ''];
            $errors['attendance'] = true;
        }

        try {
            $fees = $this->feeSummary($student, $schoolId);
        } catch (\Throwable $e) {
            report($e);
            $fees = ['total_fees' => 0, 'total_paid' => 0, 'total_balance' => 0, 'categories' => [], 'terms' => [], 'payments' => []];
            $errors['fees'] = true;
        }

        return [
            'grades' => $grades,
            'attendance' => $attendance,
            'fees' => $fees,
            'headline' => $errors['marks']
                ? ['average' => null, 'label' => '', 'delta' => null, 'since' => null, 'has_unreleased' => false]
                : $this->marksHeadline($student, $schoolId, $grades),
            'errors' => $errors,
        ];
    }

    /**
     * Headline for the marks card. Averages come only from released sittings.
     *
     * @param  array<string, mixed>  $pack
     * @return array{average: int|null, label: string, delta: int|null, since: string|null, has_unreleased: bool}
     */
    public function marksHeadline(User $student, int $schoolId, array $pack): array
    {
        $groups = $pack['exam_groups'] ?? [];
        $latest = $groups === [] ? null : $groups[array_key_last($groups)];
        $previous = count($groups) > 1 ? $groups[count($groups) - 2] : null;
        $average = $latest === null ? null : (int) round((float) $latest['average']);
        $delta = null;
        $since = null;
        if ($latest !== null && $previous !== null) {
            $delta = (int) round((float) $latest['average'] - (float) $previous['average']);
            $since = (string) $previous['exam_type'];
        }

        return [
            'average' => $average,
            'label' => $latest['exam_type'] ?? '',
            'delta' => $delta,
            'since' => $since,
            'has_unreleased' => $this->unreleasedMarksExist($student, $schoolId),
        ];
    }

    /**
     * Days present for the term in progress, or the current academic year when
     * today sits outside every term. Each date has a morning and an afternoon
     * row. Both present is one day. One present session is a half day.
     *
     * @return array{
     *     days_present: float,
     *     days_present_label: string,
     *     days_total: int,
     *     days_percent: int|null,
     *     this_week_present: float,
     *     this_week_label: string,
     *     this_week_total: int,
     *     weeks: list<array{label: string, present: float, present_label: string, full: bool}>,
     *     missed_days: list<array{date: string, comment: string}>,
     *     term_label: string
     * }
     */
    private function attendanceDays(User $student, ?int $schoolId): array
    {
        $empty = [
            'days_present' => 0.0,
            'days_present_label' => AttendanceDays::phrase(0),
            'days_total' => 0,
            'days_percent' => null,
            'this_week_present' => 0.0,
            'this_week_label' => AttendanceDays::phrase(0),
            'this_week_total' => 0,
            'weeks' => [],
            'missed_days' => [],
            'term_label' => '',
        ];
        if ($schoolId === null || $schoolId <= 0) {
            return $empty;
        }

        [$from, $to, $label] = $this->attendanceWindow($schoolId);
        $empty['term_label'] = $label;
        $rows = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $present = 0.0;
        $missed = [];
        $weeks = [];
        $thisWeekKey = Carbon::now()->startOfWeek()->toDateString();
        $thisWeekPresent = 0.0;
        $thisWeekTotal = 0;

        foreach ($rows as $date => $sessions) {
            $presentSessions = $sessions->filter(fn ($row) => in_array((int) $row->status, [1, 2], true))->count();
            $day = $presentSessions >= 2 ? 1.0 : ($presentSessions === 1 ? 0.5 : 0.0);
            $missedDay = $day === 0.0 && $sessions->contains(fn ($row) => (int) $row->status === 0);
            $weekKey = Carbon::parse($date)->startOfWeek()->toDateString();
            $weeks[$weekKey] ??= ['present' => 0.0, 'half' => false, 'missed' => 0];
            if ($day > 0) {
                $present += $day;
                $weeks[$weekKey]['present'] += $day;
                if ($day < 1) {
                    $weeks[$weekKey]['half'] = true;
                }
            } elseif ($missedDay) {
                $weeks[$weekKey]['missed']++;
                $comment = trim((string) $sessions->pluck('remarks')->filter(fn ($note) => trim((string) $note) !== '')->first());
                $missed[] = [
                    'date' => Carbon::parse($date)->format('D j M'),
                    'comment' => $comment !== '' ? $comment : 'no comment from the school',
                ];
            }
            if ($weekKey === $thisWeekKey) {
                $thisWeekPresent += $day;
                $thisWeekTotal++;
            }
        }

        $weekRows = [];
        $index = 1;
        foreach ($weeks as $week) {
            $weekRows[] = [
                'label' => 'W'.$index,
                'present' => (float) $week['present'],
                'present_label' => AttendanceDays::mark((float) $week['present']),
                'full' => ! $week['half'] && (int) $week['missed'] === 0 && $week['present'] > 0,
            ];
            $index++;
        }

        $total = $rows->count();

        return [
            'days_present' => $present,
            'days_present_label' => AttendanceDays::phrase($present),
            'days_total' => $total,
            'days_percent' => $total > 0 ? (int) round(($present / $total) * 100) : null,
            'this_week_present' => $thisWeekPresent,
            'this_week_label' => AttendanceDays::phrase($thisWeekPresent),
            'this_week_total' => $thisWeekTotal,
            'weeks' => $weekRows,
            'missed_days' => array_reverse($missed),
            'term_label' => $label,
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function attendanceWindow(int $schoolId): array
    {
        $today = Carbon::now()->toDateString();
        $year = SiteHelper::getAcademicYear($schoolId);
        $terms = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();

        $covering = $terms->first(function (AcademicTerm $term) use ($today) {
            return $term->starts_on && $term->ends_on
                && $term->starts_on->toDateString() <= $today
                && $term->ends_on->toDateString() >= $today;
        });
        if ($covering) {
            return [$covering->starts_on->toDateString(), $covering->ends_on->toDateString(), (string) $covering->name];
        }

        if ($year && $year->start_date && $year->end_date) {
            return [
                Carbon::parse($year->start_date)->toDateString(),
                Carbon::parse($year->end_date)->toDateString(),
                (string) $year->name,
            ];
        }

        $current = $terms->firstWhere('status', 'current');
        if ($current && $current->starts_on && $current->ends_on) {
            return [$current->starts_on->toDateString(), $current->ends_on->toDateString(), (string) $current->name];
        }

        return [Carbon::now()->startOfMonth()->toDateString(), $today, ''];
    }

    /**
     * @return list<array{name: string, due: float, paid: float}>
     */
    private function feeTerms(User $student, int $schoolId, float $currentDue, float $currentPaid): array
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return $currentDue > 0 || $currentPaid > 0
                ? [['name' => 'Fees', 'due' => $currentDue, 'paid' => $currentPaid]]
                : [];
        }

        $terms = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();
        if ($terms->isEmpty()) {
            return $currentDue > 0 || $currentPaid > 0
                ? [['name' => (string) $year->name, 'due' => $currentDue, 'paid' => $currentPaid]]
                : [];
        }

        $currentId = app(StudentFeeResolver::class)->currentTermId($schoolId);
        $fees = app(StudentFeeResolver::class);
        $rows = [];
        foreach ($terms as $term) {
            $isCurrent = $currentId !== null && (int) $term->id === $currentId;
            if ($isCurrent) {
                $due = $currentDue;
                $paid = $currentPaid;
            } else {
                $due = (float) $fees->forStudent($student, $schoolId, (int) $term->id)
                    ->where('academic_term_id', $term->id)
                    ->sum('amount');
                $paid = 0.0;
                if ($term->starts_on && $term->ends_on) {
                    $paid = (float) FeePayment::query()
                        ->where('school_id', $schoolId)
                        ->where('user_id', $student->id)
                        ->whereDate('paid_on', '>=', $term->starts_on->toDateString())
                        ->whereDate('paid_on', '<=', $term->ends_on->toDateString())
                        ->sum('amount');
                }
            }
            if ($due > 0 || $paid > 0) {
                $rows[] = ['name' => (string) $term->name, 'due' => $due, 'paid' => $paid];
            }
        }

        return $rows;
    }

    private function paymentMethodLabel(string $method): string
    {
        $method = str_replace(['_', '-'], ' ', trim($method));

        return $method === '' ? 'Payment' : mb_convert_case($method, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * True when this student has mark rows the release gate withholds.
     * The sitting name and the scores stay out of the result.
     */
    public function unreleasedMarksExist(User $student, int $schoolId): bool
    {
        if ($schoolId <= 0) {
            return false;
        }

        $base = Exam::query()
            ->where('school_id', $schoolId)
            ->whereHas('marks', fn ($query) => $query->where('school_id', $schoolId)->where('student_id', $student->id));
        $total = (clone $base)->count();
        if ($total === 0) {
            return false;
        }

        $this->releases->applyToExams($base, $student);

        return $base->count() < $total;
    }

    /**
     * Latest released report card for one student. Nothing is generated unless
     * the release gate accepts the sitting.
     *
     * @return array{ok: bool, message?: string, pdf?: string, filename?: string}
     */
    public function releasedReportPdf(User $student, int $schoolId): array
    {
        if ($schoolId <= 0 || (int) $student->school_id !== $schoolId) {
            return ['ok' => false, 'message' => 'No report card has been released yet.'];
        }

        $year = SiteHelper::getAcademicYear($schoolId);
        $academic = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->with('standardLink')
            ->orderByDesc('id')
            ->first();
        $link = $academic?->standardLink;
        if (! $link || (int) $link->school_id !== $schoolId) {
            return ['ok' => false, 'message' => 'No class is assigned yet, so a report card cannot be generated.'];
        }

        $reports = app(StudentReportCardService::class);
        $exam = $reports->resolveReleasedExam($schoolId, $link, $student);
        if (! $exam || ! $reports->studentHasContributingMark($exam, (int) $student->id)) {
            return ['ok' => false, 'message' => 'No report card has been released yet.'];
        }

        try {
            $pdf = $reports->pdfForStudent($schoolId, $link, $student, null, (int) $exam->academic_term_id);
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'The report card could not be generated just now. Please try again.'];
        }

        if ($pdf === '' || ! str_starts_with($pdf, '%PDF')) {
            return ['ok' => false, 'message' => 'The report card could not be generated just now. Please try again.'];
        }

        $slug = method_exists($student, 'displayNameFilenameSlug')
            ? $student->displayNameFilenameSlug('student')
            : 'student';

        return ['ok' => true, 'pdf' => $pdf, 'filename' => $slug.'_report_card.pdf'];
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
