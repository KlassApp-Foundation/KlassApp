<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\ParentProfile;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
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

    /**
     * @return array<string, mixed>
     */
    public function page(int $schoolId, User $student): array
    {
        $student->load('userprofile');
        $year = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();

        $academic = null;
        if ($year) {
            $academic = StudentAcademic::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $student->id)
                ->where('academic_year_id', $year->id)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->with('standardLink.section')
                ->first();
        }

        $link = $academic?->standardLink;
        $sectionId = $link?->section_id;
        $standardId = $link?->standard_id;
        $term = null;
        if ($year) {
            $term = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->where('status', 'current')
                ->orderByDesc('id')
                ->first();
        }

        $name = trim((string) $student->full_name);
        if ($name === '') {
            $name = $student->display_name !== '' ? $student->display_name : 'Student';
        }
        $name = $this->readableName($name);
        $profile = $student->userprofile;
        $initials = $this->initials($profile?->firstname, $profile?->lastname, $name);
        $className = $this->classLabel($link?->section?->name, $link?->stream);
        $kls = $academic?->klassapp_student_id ?: $student->registration_number;

        return [
            'student' => $student,
            'name' => $name,
            'initials' => $initials,
            'class_name' => $className,
            'kls' => $kls,
            'gender' => $this->genderLabel($profile?->gender),
            'date_of_birth' => $this->dateLabel($profile?->date_of_birth),
            'joined' => $this->dateLabel($profile?->joining_date),
            'facts' => $this->facts($className, $kls, $profile?->date_of_birth, $profile?->gender, $profile?->joining_date),
            'status' => (string) $student->status,
            'status_label' => $this->statusLabel((string) $student->status),
            'attendance' => $this->attendance($schoolId, $student->id, $term),
            'exam' => $this->exam($schoolId, $student->id, $sectionId, $standardId, $term?->id),
            'fees' => $this->fees($schoolId, $student->id, $standardId),
            'currency' => app(DashboardV2DataService::class)->currencyFor($schoolId),
            'parents' => $this->parents($schoolId, $student),
            'marks' => $this->marks($schoolId, $student->id, $sectionId, $term?->id),
            'payments' => $this->payments($schoolId, $student->id),
        ];
    }

    public function deactivate(int $schoolId, int $studentId): User
    {
        $student = $this->find($schoolId, $studentId);
        $student->status = 'inactive';
        $student->save();

        return $student;
    }

    /**
     * @return array{empty: bool, percent: ?int, absences: int}
     */
    private function attendance(int $schoolId, int $studentId, ?AcademicTerm $term): array
    {
        $empty = ['empty' => true, 'percent' => null, 'absences' => 0];
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
            ->get(['status']);

        if ($rows->isEmpty()) {
            return $empty;
        }

        $present = $rows->where('status', 1)->count();

        return [
            'empty' => false,
            'percent' => (int) round($present / $rows->count() * 100),
            'absences' => $rows->where('status', 0)->count(),
        ];
    }

    /**
     * The latest exam tile: the student's average across every subject of the latest
     * exam (one exam type in the term), its grade, and the student's position in the
     * class. When the latest marks have no exam type to group by, the tile falls back
     * to the latest single mark with its subject.
     *
     * @return array{empty: bool, label: string, average: ?string, grade: ?string, name?: string}
     */
    private function exam(int $schoolId, int $studentId, ?int $sectionId, ?int $standardId, ?int $termId): array
    {
        $empty = ['empty' => true, 'label' => 'Latest exam', 'average' => null, 'grade' => null];
        if (! $sectionId || ! $termId) {
            return $empty;
        }

        $marks = $this->termMarks($schoolId, $studentId, $sectionId, $termId);
        if ($marks->isEmpty()) {
            return $empty;
        }

        $latestExamId = (int) $marks->max('exam_id');
        $examModel = \App\Models\Academics\Exam::query()
            ->with(['subject', 'examType'])
            ->where('school_id', $schoolId)
            ->where('id', $latestExamId)
            ->first();

        $examTypeId = $examModel?->examType ? (int) $examModel->exam_type_id : null;
        if ($examTypeId === null) {
            $latest = $marks->where('exam_id', $latestExamId)->values();
            $average = round((float) $latest->avg('marks'), 1);

            return [
                'empty' => false,
                'label' => 'Latest mark',
                'average' => $this->markLabel($average),
                'grade' => $this->gradeFor($schoolId, $standardId, $average, $latest),
                'name' => $this->sentenceCase((string) ($examModel?->subject?->name ?: 'Subject')),
            ];
        }

        $examIds = \App\Models\Academics\Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('academic_term_id', $termId)
            ->where('exam_type_id', $examTypeId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $latest = $marks->whereIn('exam_id', $examIds)->values();
        $average = round((float) $latest->avg('marks'), 1);

        $classAverages = Marks::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->whereIn('exam_id', $examIds)
            ->groupBy('student_id')
            ->selectRaw('student_id, AVG(marks) as average')
            ->pluck('average', 'student_id')
            ->map(fn ($value) => round((float) $value, 1));
        $position = 1 + $classAverages->filter(fn (float $value) => $value > $average)->count();

        $examName = $this->sentenceCase((string) ($examModel->examType?->name ?: 'Exam'));

        return [
            'empty' => false,
            'label' => 'Latest exam',
            'average' => $this->markLabel($average),
            'grade' => $this->gradeFor($schoolId, $standardId, $average, $latest),
            'name' => $examName.' · '.$position.' of '.$classAverages->count(),
        ];
    }

    private function gradeFor(int $schoolId, ?int $standardId, float $average, $latest): ?string
    {
        $grade = null;
        if ($standardId) {
            $grade = SchoolGradingSystem::query()
                ->where('school_id', $schoolId)
                ->where('standard_id', $standardId)
                ->where('min_score', '<=', $average)
                ->where('max_score', '>=', $average)
                ->orderByDesc('min_score')
                ->value('grade');
        }
        if ($grade === null) {
            $stored = $latest->pluck('grade')->unique()->filter()->values();
            $grade = $stored->count() === 1 ? (string) $stored->first() : null;
        }

        return $grade;
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

    /**
     * The key facts row under the student's name: class with stream, KLS number,
     * age with the birth date, gender and joined date.
     *
     * @return array{class: string, kls: string, age: string, gender: string, joined: string}
     */
    private function facts(?string $className, ?string $kls, mixed $dateOfBirth, ?string $gender, mixed $joined): array
    {
        $born = \App\Support\DateOfBirth::parse($dateOfBirth);
        $genderLabel = $this->genderLabel($gender);
        $joinedLabel = $this->dateLabel($joined);

        return [
            'class' => $className ?: 'No class',
            'kls' => filled($kls) ? (string) $kls : 'No KLS number',
            'age' => $born ? 'Age '.$born->age.' (born '.$born->format('j M Y').')' : 'Age not given',
            'gender' => $genderLabel === self::NOT_GIVEN ? 'Gender not given' : $genderLabel,
            'joined' => $joinedLabel === self::NOT_GIVEN ? 'Joined date not given' : 'Joined '.$joinedLabel,
        ];
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
     * @return array{empty: bool, balance: ?string, status: ?string, expected: ?string, paid: ?string}
     */
    private function fees(int $schoolId, int $studentId, ?int $standardId): array
    {
        $expected = 0.0;
        if ($standardId) {
            $expected = (float) FeesCategories::query()
                ->where('school_id', $schoolId)
                ->where('standard_id', $standardId)
                ->sum('amount');
        }
        $paid = (float) FeePayment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->sum('amount');

        if ($expected <= 0 && $paid <= 0) {
            return [
                'empty' => true,
                'balance' => null,
                'status' => null,
                'expected' => null,
                'paid' => null,
            ];
        }

        $balance = max(0, $expected - $paid);
        $status = 'Paid';
        if ($expected > 0 && $paid <= 0) {
            $status = 'Not paid';
        } elseif ($expected > 0 && $paid < $expected) {
            $status = 'Partly paid';
        }

        return [
            'empty' => false,
            'balance' => number_format($balance),
            'status' => $status,
            'expected' => $expected > 0 ? number_format($expected) : null,
            'paid' => $expected > 0 ? number_format($paid) : null,
        ];
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
            if (! $parent || (int) $parent->school_id !== $schoolId) {
                continue;
            }
            $parents[] = $parent;
        }

        $parentIds = array_map(fn (User $parent) => $parent->id, $parents);
        $optedIn = $parentIds === []
            ? []
            : WhatsAppUser::query()
                ->where('school_id', $schoolId)
                ->whereIn('user_id', $parentIds)
                ->where('opted_in', true)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
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
                'whatsapp' => in_array((int) $parent->id, $optedIn, true) ? 'On WhatsApp' : 'Not on WhatsApp',
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
     * @return list<array{subject: string, marks: string, grade: string}>
     */
    private function marks(int $schoolId, int $studentId, ?int $sectionId, ?int $termId): array
    {
        if (! $sectionId || ! $termId) {
            return [];
        }

        return $this->termMarks($schoolId, $studentId, $sectionId, $termId)
            ->load('subject')
            ->map(fn (Marks $mark) => [
                'subject' => $mark->subject?->name ?? 'Subject',
                'marks' => $this->markLabel((float) $mark->marks),
                'grade' => (string) ($mark->grade ?: '—'),
            ])
            ->all();
    }

    /**
     * @return list<array{paid_on: string, amount: string}>
     */
    private function payments(int $schoolId, int $studentId): array
    {
        return FeePayment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FeePayment $payment) => [
                'paid_on' => optional($payment->paid_on)->toDateString() ?? '—',
                'amount' => number_format((float) $payment->amount),
            ])
            ->all();
    }

    private function termMarks(int $schoolId, int $studentId, int $sectionId, int $termId)
    {
        return Marks::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('section_id', $sectionId)
            ->whereHas('exam', function ($query) use ($schoolId, $sectionId, $termId) {
                $query->where('school_id', $schoolId)
                    ->where('section_id', $sectionId)
                    ->where('academic_term_id', $termId);
            })
            ->get();
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
