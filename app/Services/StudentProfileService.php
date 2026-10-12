<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use Illuminate\Support\Carbon;

class StudentProfileService
{
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

        return [
            'student' => $student,
            'name' => $name,
            'initials' => $initials,
            'class_name' => $link?->section?->name,
            'kls' => $academic?->klassapp_student_id ?: $student->registration_number,
            'status' => (string) $student->status,
            'status_label' => $this->statusLabel((string) $student->status),
            'attendance' => $this->attendance($schoolId, $student->id, $term),
            'exam' => $this->exam($schoolId, $student->id, $sectionId, $standardId, $term?->id),
            'fees' => $this->fees($schoolId, $student->id, $standardId),
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
     * @return array{empty: bool, average: ?string, grade: ?string}
     */
    private function exam(int $schoolId, int $studentId, ?int $sectionId, ?int $standardId, ?int $termId): array
    {
        $empty = ['empty' => true, 'average' => null, 'grade' => null];
        if (! $sectionId || ! $termId) {
            return $empty;
        }

        $marks = $this->termMarks($schoolId, $studentId, $sectionId, $termId);
        if ($marks->isEmpty()) {
            return $empty;
        }

        $latestExamId = (int) $marks->max('exam_id');
        $latest = $marks->where('exam_id', $latestExamId)->values();
        $average = round((float) $latest->avg('marks'), 1);
        $averageLabel = fmod($average, 1.0) === 0.0
            ? (string) (int) $average
            : number_format($average, 1);

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

        return [
            'empty' => false,
            'average' => $averageLabel,
            'grade' => $grade,
        ];
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
            'paid' => number_format($paid),
        ];
    }

    /**
     * @return list<array{name: string, phone: string, whatsapp: string}>
     */
    private function parents(int $schoolId, User $student): array
    {
        $links = $student->parents()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->with('userParent.userprofile')
            ->get();

        $rows = [];
        foreach ($links as $link) {
            $parent = $link->userParent;
            if (! $parent || (int) $parent->school_id !== $schoolId) {
                continue;
            }
            $profile = $parent->userprofile;
            $name = $this->readableName(trim(($profile->firstname ?? '').' '.($profile->lastname ?? '')));
            $optedIn = WhatsAppUser::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $parent->id)
                ->where('opted_in', true)
                ->exists();
            $rows[] = [
                'name' => $name !== '' ? $name : 'Parent',
                'phone' => $parent->mobile_no ? (string) $parent->mobile_no : '—',
                'whatsapp' => $optedIn ? 'Opted in' : 'Not opted in',
            ];
        }

        return $rows;
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

    private function markLabel(float $marks): string
    {
        $rounded = round($marks, 1);

        return fmod($rounded, 1.0) === 0.0
            ? (string) (int) $rounded
            : number_format($rounded, 1);
    }
}
