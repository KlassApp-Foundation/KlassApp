<?php

namespace App\Services;

use App\Contracts\Results\ReleaseGate;
use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\ParentProfile;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\StudentProfile\StudentEnrollment;
use Illuminate\Support\Carbon;

class StudentProfileService
{
    private const NOT_GIVEN = 'Not given';

    public function find(int $schoolId, int $studentId): User
    {
        $student = User::query()
            ->where('id', $studentId)
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->whereNull('deleted_at')
            ->first();

        abort_unless($student, 404);

        return $student;
    }

    public function __construct(private StudentEnrollment $enrollment, private ReleaseGate $gate)
    {
    }

    /**
     * Everything the admin student profile shows.
     *
     * Marks reach this page only through the release gate (performance) or as an approved
     * progress report, labelled as not released: draft, submitted and returned marks are never
     * listed here.
     *
     * @return array<string, mixed>
     */
    public function page(int $schoolId, User $student): array
    {
        $student->load('userprofile');
        $year = $this->enrollment->year($schoolId);
        $academic = $this->enrollment->current($student, $year);
        $term = $this->enrollment->term($schoolId, $year);

        $link = $academic?->standardLink;
        $name = trim((string) $student->full_name);
        if ($name === '') {
            $name = $student->display_name !== '' ? $student->display_name : 'Student';
        }
        $name = $this->readableName($name);
        $profile = $student->userprofile;
        $initials = $this->initials($profile?->firstname, $profile?->lastname, $name);
        $className = $this->classLabel($link?->section?->name, $link?->stream);
        $kls = $academic?->klassapp_student_id ?: $student->registration_number;
        $parents = $this->parents($schoolId, $student);
        $linked = count(array_filter($parents, fn (array $p) => $p['whatsapp'] === 'Linked'));
        $currency = app(DashboardV2DataService::class)->currencyFor($schoolId);

        return [
            'student' => $student,
            'name' => $name,
            'initials' => $initials,
            'class_name' => $className,
            'kls' => $kls,
            'gender' => $this->genderLabel($profile?->gender),
            'date_of_birth' => $this->dateLabel($profile?->date_of_birth),
            'joined' => $this->joinedLabel($student, $schoolId),
            'class_teacher' => $this->classTeacher($link),
            'age_label' => $this->ageLabel($profile?->date_of_birth),
            'status' => (string) $student->status,
            'status_label' => $this->statusLabel((string) $student->status),
            'whatsapp_line' => $linked === 0 ? 'No parent on WhatsApp yet' : 'WhatsApp linked to '.$linked.' '.($linked === 1 ? 'parent' : 'parents'),
            'term_name' => $term?->name,
            'attendance' => $this->attendance($schoolId, $student->id, $term),
            'performance' => $this->performance($schoolId, $student, $academic, $term),
            'fees' => $this->fees($schoolId, $student, $term?->id ? (int) $term->id : null, $currency),
            'currency' => $currency,
            'parents' => $parents,
            'payments' => $this->payments($schoolId, $student->id, $currency),
        ];
    }

    /** What the student still owes for the current term, 0.0 when nothing is owed or no fees exist. */
    public function outstanding(int $schoolId, User $student): float
    {
        $term = $this->enrollment->term($schoolId, $this->enrollment->year($schoolId));
        $fees = $this->fees($schoolId, $student, $term?->id ? (int) $term->id : null, null);

        return $fees['error'] || $fees['empty'] ? 0.0 : (float) $fees['owed'];
    }

    public function deactivate(int $schoolId, int $studentId): User
    {
        $student = $this->find($schoolId, $studentId);
        $student->status = 'inactive';
        $student->save();

        return $student;
    }

    /**
     * This term's attendance: percent present, days counted, and the latest absences with their
     * comments. One row is one recorded day (the register's morning session).
     *
     * @return array{empty: bool, percent: ?int, present: int, total: int, absences: int, recent: list<array{date: string, comment: ?string, by: ?string}>}
     */
    private function attendance(int $schoolId, int $studentId, ?AcademicTerm $term): array
    {
        $empty = ['empty' => true, 'percent' => null, 'present' => 0, 'total' => 0, 'absences' => 0, 'recent' => []];
        if (! $term) {
            return $empty;
        }

        $start = $term->getRawOriginal('starts_on');
        $end = $term->getRawOriginal('ends_on');
        if (! $start || ! $end) {
            return $empty;
        }

        $rows = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->whereNull('deleted_at')
            ->whereDate('date', '>=', Carbon::parse($start)->toDateString())
            ->whereDate('date', '<=', Carbon::parse($end)->toDateString())
            ->orderByDesc('date')
            ->get(['id', 'date', 'status', 'remarks', 'recorded_by']);

        if ($rows->isEmpty()) {
            return $empty;
        }

        $present = $rows->where('status', 1)->count();
        $absent = $rows->where('status', 0);
        $recorders = User::query()
            ->whereIn('id', $absent->take(3)->pluck('recorded_by')->filter()->unique())
            ->get()
            ->keyBy('id');

        return [
            'empty' => false,
            'percent' => (int) round($present / $rows->count() * 100),
            'present' => $present,
            'total' => $rows->count(),
            'absences' => $absent->count(),
            'recent' => $absent->take(3)->map(fn ($row) => [
                'date' => $row->date->format('j M'),
                'comment' => filled($row->remarks) ? (string) $row->remarks : null,
                'by' => ($u = $recorders->get($row->recorded_by)) ? $this->readableName(trim((string) ($u->full_name ?: $u->name))) : null,
            ])->values()->all(),
        ];
    }

    /**
     * Performance: released sittings only (through the release gate), and the current progress
     * report, built from approved marks that are not released yet.
     *
     * @return array{released: list<array<string, mixed>>, chart: list<array{label: string, average: float}>, latest: ?array<string, mixed>, progress: ?array<string, mixed>}
     */
    private function performance(int $schoolId, User $student, ?StudentAcademic $academic, ?AcademicTerm $term): array
    {
        $released = [];
        $marks = $this->gate->applyToMarks(
            Marks::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->whereNotNull('marks'),
            $student,
        )->with(['exam.examType', 'exam.academicTerm'])->get();

        $groups = $marks->filter(fn (Marks $m) => $m->exam !== null)
            ->groupBy(fn (Marks $m) => $m->exam->academic_term_id.':'.$m->exam->exam_type_id);

        foreach ($groups as $key => $rows) {
            /** @var Exam $exam */
            $exam = $rows->first()->exam;
            $examTerm = $exam->academicTerm;
            $average = round((float) $rows->avg('marks'), 1);
            $released[] = [
                'key' => (string) $key,
                'label' => trim(($examTerm?->name ? $examTerm->name.' · ' : '').$this->sentenceCase((string) ($exam->examType?->name ?: 'Exam'))),
                'average' => $this->markLabel($average),
                'average_value' => $average,
                'term_id' => (int) $exam->academic_term_id,
                'download_url' => $this->termReportUrl($schoolId, $student, $exam),
                'sort' => ($examTerm?->getRawOriginal('starts_on') ?? '').'-'.str_pad((string) $exam->id, 9, '0', STR_PAD_LEFT),
            ];
        }
        usort($released, fn (array $a, array $b) => strcmp($b['sort'], $a['sort']));

        $chart = array_map(fn (array $r) => ['label' => $r['label'], 'average' => $r['average_value']], array_reverse(array_slice($released, 0, 6)));

        return [
            'released' => array_map(fn (array $r) => array_diff_key($r, ['sort' => 1, 'average_value' => 1]), $released),
            'chart' => $chart,
            'latest' => $released === [] ? null : ['label' => $released[0]['label'], 'average' => $released[0]['average']],
            'progress' => $this->progress($schoolId, $student, $term),
        ];
    }

    /**
     * The current progress report: this term's APPROVED marks that no release covers yet.
     * Draft, submitted and returned subjects are left out, so nothing unapproved shows.
     *
     * @return array{label: string, average: string, subjects: int, download_url: string}|null
     */
    private function progress(int $schoolId, User $student, ?AcademicTerm $term): ?array
    {
        if (! $term) {
            return null;
        }

        $rows = $this->approvedUnreleasedMarks($schoolId, $student, (int) $term->id);
        if ($rows->isEmpty()) {
            return null;
        }

        $types = $rows->map(fn (Marks $m) => $this->sentenceCase((string) ($m->exam->examType?->name ?: 'Exam')))->unique()->values();

        return [
            'label' => $types->count() === 1 ? (string) $types->first() : 'Current exams',
            'average' => $this->markLabel(round((float) $rows->avg('marks'), 1)),
            'subjects' => $rows->count(),
            'download_url' => url('/admin/students/'.$student->id.'/progress-report'),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Marks>
     */
    public function approvedUnreleasedMarks(int $schoolId, User $student, int $termId)
    {
        return Marks::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->whereNotNull('marks')
            ->whereHas('exam', fn ($q) => $q->where('school_id', $schoolId)->where('academic_term_id', $termId)->whereNull('deleted_at'))
            ->with(['exam.examType', 'exam.subject'])
            ->get()
            ->filter(function (Marks $mark) use ($schoolId, $student) {
                $exam = $mark->exam;
                if (! $exam || $this->gate->isReleased($student, $exam)) {
                    return false;
                }

                return ExamMarksSubmission::query()
                    ->where('school_id', $schoolId)
                    ->where('exam_id', $exam->id)
                    ->where('class_id', $exam->section_id)
                    ->where('subject_id', $exam->subject_id)
                    ->where('approval_status', 'approved')
                    ->exists();
            })
            ->values();
    }

    /** The term's report card PDF for a released sitting, through the existing admin download. */
    private function termReportUrl(int $schoolId, User $student, Exam $exam): ?string
    {
        $link = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('standard_id', $exam->standard_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->orderBy('id')
            ->get()
            ->first(fn (StandardLink $l) => StudentAcademic::query()
                ->where('school_id', $schoolId)->where('user_id', $student->id)->where('standardLink_id', $l->id)->exists())
            ?? StandardLink::query()->where('school_id', $schoolId)->where('section_id', $exam->section_id)->orderBy('id')->first();

        return $link ? url('/admin/reports/cards/'.$link->id.'/student/'.$student->id.'/download?term='.$exam->academic_term_id) : null;
    }

    /** "SOCIAL STUDIES" reads "Social studies"; names already written in mixed case stay as written. */
    private function sentenceCase(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value !== mb_strtoupper($value)) {
            return $value;
        }

        $lower = mb_strtolower($value);

        return mb_strtoupper(mb_substr($lower, 0, 1)).mb_substr($lower, 1);
    }

    private function ageLabel(mixed $dateOfBirth): ?string
    {
        $born = \App\Support\DateOfBirth::parse($dateOfBirth);

        return $born ? $born->age.' '.($born->age === 1 ? 'yr' : 'yrs') : null;
    }

    /** Joined month and the class they joined in, when known. The date defaults to the onboarding date. */
    private function joinedLabel(User $student, int $schoolId): string
    {
        $joined = $student->userprofile?->joining_date;
        $date = null;
        if ($joined !== null && $joined !== '') {
            $candidate = $joined instanceof Carbon ? $joined : Carbon::parse((string) $joined);
            $date = $candidate->year > 1970 ? $candidate : null;
        }
        $date ??= $student->created_at;
        if ($date === null) {
            return self::NOT_GIVEN;
        }

        $first = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->with('standardLink.section')
            ->first();
        $class = $this->classLabel($first?->standardLink?->section?->name, null);

        return $date->format('M Y').($class ? ' · '.$class : '');
    }

    private function classTeacher(?StandardLink $link): ?string
    {
        if (! $link) {
            return null;
        }
        if (! $link->section) {
            return null;
        }
        $teacher = app(RosterScopeService::class)->effectiveClassTeacher($link->section, $link);
        if (! $teacher) {
            return null;
        }
        $name = $this->readableName(trim((string) ($teacher->full_name ?: $teacher->name)));

        return $name !== '' ? $name : null;
    }

    private function classLabel(?string $section, ?string $stream): ?string
    {
        $section = trim((string) $section);
        $stream = trim((string) $stream);
        if ($section === '') {
            return null;
        }
        $title = fn (string $value): string => mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');

        // Same label as the students list ("P.6 · B").
        return $stream === '' ? $title($section) : $title($section).' · '.$title($stream);
    }

    /**
     * The term's fees: the term total, what has been paid, what is still owed. Amounts carry the
     * school's currency ("UGX 300,000"). A fees failure is reported as `error` so the rest of the
     * profile still renders.
     *
     * @return array<string, mixed>
     */
    private function fees(int $schoolId, User $student, ?int $academicTermId, ?string $currency): array
    {
        $none = [
            'empty' => true, 'error' => false, 'expected' => 0.0, 'paid' => 0.0, 'owed' => 0.0, 'percent' => 0,
            'status' => null, 'expected_label' => null, 'paid_label' => null, 'owed_label' => null, 'last_payment' => null,
        ];

        try {
            // $academicTermId is the current term already loaded for this profile.
            // Null means the school has no current term, so only untied fees count.
            $expected = (float) app(StudentFeeResolver::class)->forStudent($student, $schoolId, $academicTermId)->sum('amount');
            $payments = FeePayment::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $student->id);
            $paid = (float) (clone $payments)->sum('amount');
            $last = (clone $payments)->orderByDesc('paid_on')->orderByDesc('id')->first();
        } catch (\Throwable $e) {
            report($e);

            return ['error' => true] + $none;
        }

        if ($expected <= 0 && $paid <= 0) {
            return $none;
        }

        $owed = max(0, $expected - $paid);
        $status = 'Paid';
        if ($expected > 0 && $paid <= 0) {
            $status = 'Not paid';
        } elseif ($expected > 0 && $paid < $expected) {
            $status = 'Partly paid';
        }

        return [
            'empty' => false,
            'error' => false,
            'expected' => $expected,
            'paid' => $paid,
            'owed' => $owed,
            'percent' => $expected > 0 ? (int) min(100, round($paid / $expected * 100)) : 100,
            'status' => $status,
            'expected_label' => $expected > 0 ? $this->money($expected, $currency) : null,
            'paid_label' => $this->money($paid, $currency),
            'owed_label' => $this->money($owed, $currency),
            'last_payment' => $last?->paid_on?->format('j M'),
        ];
    }

    public function money(float $amount, ?string $currency): string
    {
        return ($currency !== null && $currency !== '' ? $currency.' ' : '').number_format($amount);
    }

    /**
     * @return list<array{name: string, initials: string, relation: ?string, phone: ?string, phone_href: ?string, whatsapp: string}>
     */
    private function parents(int $schoolId, User $student): array
    {
        $links = $student->parents()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->with('userParent.userprofile')
            ->get();

        $parents = [];
        foreach ($links as $link) {
            $parent = $link->userParent;
            if (! $parent) {
                continue;
            }
            // WhatsApp-created parents have no school of their own. The link
            // row is already scoped to this school. A parent who belongs to a
            // different school stays hidden.
            if ($parent->school_id !== null && (int) $parent->school_id !== $schoolId) {
                continue;
            }
            $parents[] = $parent;
        }

        $parentIds = array_map(fn (User $parent) => $parent->id, $parents);
        $whatsappRows = $parentIds === []
            ? collect()
            : app(\App\Services\WhatsApp\LinkRequestBoard::class)->linkedRowsForParents($schoolId, $parentIds);
        $optedIn = $whatsappRows->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $profileNames = $whatsappRows
            ->filter(fn ($row) => filled($row->whatsapp_profile_name))
            ->mapWithKeys(fn ($row) => [(int) $row->user_id => (string) $row->whatsapp_profile_name]);
        $relations = $parentIds === []
            ? collect()
            : ParentProfile::query()
                ->where('school_id', $schoolId)
                ->whereIn('user_id', $parentIds)
                ->pluck('relation', 'user_id');

        $rows = [];
        foreach ($parents as $parent) {
            $profile = $parent->userprofile;
            $first = trim((string) ($profile->firstname ?? ''));
            $last = trim((string) ($profile->lastname ?? ''));
            $name = $this->readableName(trim($first.' '.$last));
            $relation = trim((string) ($relations[$parent->id] ?? ''));
            $phone = $this->phoneLink($parent->mobile_no ? (string) $parent->mobile_no : null);
            $rows[] = [
                'user' => $parent,
                'name' => $name !== '' ? $name : 'Parent',
                'initials' => $this->initials($first, $last, $name !== '' ? $name : 'Parent'),
                'relation' => $relation !== '' ? ucfirst($relation) : null,
                'phone' => $phone['label'] ?? null,
                'phone_href' => $phone['href'] ?? null,
                'whatsapp' => in_array((int) $parent->id, $optedIn, true) ? 'Linked' : 'Not linked',
                'whatsapp_profile_name' => $profileNames[(int) $parent->id] ?? null,
                'profile_url' => url('/admin/parent/show/'.$parent->name),
            ];
        }

        return $rows;
    }

    /**
     * @return array{label: string, href: string}|null
     */
    private function phoneLink(?string $raw): ?array
    {
        $label = trim((string) $raw);
        if ($label === '' || $label === '—') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $label) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '256'.substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $digits = '256'.$digits;
        }

        return ['label' => $label, 'href' => 'tel:+'.$digits];
    }

    /**
     * @return list<array{paid_on: string, amount: string, method: ?string, reference: ?string}>
     */
    private function payments(int $schoolId, int $studentId, ?string $currency): array
    {
        return FeePayment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (FeePayment $payment) => [
                'paid_on' => optional($payment->paid_on)->format('j M Y') ?? '—',
                'amount' => $this->money((float) $payment->amount, $currency),
                'method' => \App\Services\StudentProfile\StudentPaymentRecorder::METHODS[$payment->payment_method] ?? null,
                'reference' => filled($payment->reference) ? (string) $payment->reference : null,
            ])
            ->all();
    }

    private function initials(?string $first, ?string $last, string $fallback): string
    {
        $letters = '';
        foreach ([$first, $last] as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }
        if ($letters !== '') {
            return mb_substr($letters, 0, 2);
        }
        $words = preg_split('/\s+/', trim($fallback)) ?: [];
        $letters = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $letters .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $letters !== '' ? $letters : 'S';
    }

    private function readableName(string $name): string
    {
        if ($name === '' || $name !== mb_strtoupper($name)) {
            return $name;
        }

        return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Active',
            'inactive' => 'Left school',
            default => ucfirst($status),
        };
    }

    private function genderLabel(?string $gender): string
    {
        return match (strtolower(trim((string) $gender))) {
            'female', 'f', 'girl' => 'Female',
            'male', 'm', 'boy' => 'Male',
            default => self::NOT_GIVEN,
        };
    }

    private function dateLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return self::NOT_GIVEN;
        }
        $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        if ($date->year <= 1970) {
            return self::NOT_GIVEN;
        }

        return $date->format('j M Y');
    }

    private function markLabel(float $marks): string
    {
        $rounded = round($marks, 1);

        return fmod($rounded, 1.0) === 0.0
            ? (string) (int) $rounded
            : number_format($rounded, 1);
    }
}
