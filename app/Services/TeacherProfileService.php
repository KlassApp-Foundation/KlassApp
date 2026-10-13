<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\StudentAcademic;
use App\Models\Teacherlink;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TeacherProfileService
{
    public function find(int $schoolId, string $name): User
    {
        $teacher = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->where('name', $name)
            ->first();

        if ($teacher === null && ctype_digit($name)) {
            $teacher = User::query()
                ->where('id', (int) $name)
                ->where('school_id', $schoolId)
                ->where('usergroup_id', 5)
                ->first();
        }

        abort_unless($teacher, 404);

        return $teacher;
    }

    /**
     * @return array<string, mixed>
     */
    public function page(int $schoolId, User $teacher): array
    {
        $teacher->load('userprofile');
        $profile = $teacher->userprofile;
        $year = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();
        $term = null;
        if ($year) {
            $term = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->where('status', 'current')
                ->orderByDesc('id')
                ->first();
        }

        $record = $this->staffRecord($schoolId, $teacher->id, $year?->id);
        $name = $this->readableName($this->personName($teacher, $profile));
        $joinedOn = $this->dateLabel($profile?->joining_date);
        $assignments = $this->assignments($schoolId, $teacher->id, $year, $term);
        $pendingRows = array_values(array_filter(
            $assignments,
            fn (array $row): bool => $row['marks_key'] === 'pending'
        ));
        $hasExam = collect($assignments)->contains(fn (array $row): bool => $row['marks_key'] !== 'no-exam');

        return [
            'teacher' => $teacher,
            'name' => $name,
            'initials' => $this->initials($profile?->firstname, $profile?->lastname, $name),
            'role' => $record ? $this->designationLabel($record->designation) : 'Teacher',
            'joined' => $joinedOn === 'Not recorded' ? 'Joined date not recorded' : 'Joined '.$joinedOn,
            'status' => (string) $teacher->status,
            'status_label' => $this->statusLabel((string) $teacher->status),
            'employee_id' => filled($record?->employee_id) ? (string) $record->employee_id : 'Not recorded',
            'designation' => $record && filled($record->designation)
                ? $this->designationLabel($record->designation)
                : 'Not recorded',
            'gender' => $this->genderLabel($profile?->gender),
            'date_of_birth' => $this->dateLabel($profile?->date_of_birth),
            'joining_date' => $this->dateLabel($profile?->joining_date),
            'job_type' => $this->jobTypeLabel($record?->job_type),
            'phone' => filled($teacher->mobile_no) ? (string) $teacher->mobile_no : 'Not recorded',
            'email' => filled($teacher->email) ? (string) $teacher->email : 'Not recorded',
            'last_login' => $this->lastLogin($schoolId, $teacher->id),
            'classes' => $this->classesTile($assignments),
            'subjects' => $this->subjectsTile($assignments),
            'pending' => $this->pendingTile($assignments, $pendingRows, $hasExam),
            'teaches' => $assignments,
        ];
    }

    public function deactivate(int $schoolId, string $name): User
    {
        $teacher = $this->find($schoolId, $name);
        $teacher->status = 'inactive';
        $teacher->save();

        Userprofile::query()
            ->where('user_id', $teacher->id)
            ->where('school_id', $schoolId)
            ->update(['status' => 'inactive']);

        return $teacher->fresh(['userprofile']);
    }

    private function staffRecord(int $schoolId, int $teacherId, ?int $yearId): ?TeacherProfile
    {
        $query = TeacherProfile::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $teacherId)
            ->orderByDesc('id');

        if ($yearId) {
            $forYear = (clone $query)->where('academic_year_id', $yearId)->first();
            if ($forYear) {
                return $forYear;
            }
        }

        return $query->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function assignments(int $schoolId, int $teacherId, ?AcademicYear $year, ?AcademicTerm $term): array
    {
        if (! $year) {
            return [];
        }

        $links = Teacherlink::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $year->id)
            ->where('status', 1)
            ->with(['subject', 'standardLink.section'])
            ->get()
            ->filter(function (Teacherlink $link) use ($schoolId): bool {
                return $link->standardLink
                    && (int) $link->standardLink->school_id === $schoolId
                    && (int) $link->standardLink->section_id > 0;
            })
            ->values();

        if ($links->isEmpty()) {
            return [];
        }

        $linkIds = $links->pluck('standardLink_id')->unique()->values();
        $academics = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->whereIn('standardLink_id', $linkIds)
            ->whereHas('user', function ($query) use ($schoolId): void {
                $query->where('school_id', $schoolId)
                    ->where('usergroup_id', 6)
                    ->where('status', 'active');
            })
            ->get(['standardLink_id', 'user_id']);

        $studentsByLink = $academics->groupBy('standardLink_id')->map(
            fn (Collection $rows): array => $rows->pluck('user_id')->unique()->values()->all()
        );

        $sectionIds = $links->map(fn (Teacherlink $link) => (int) $link->standardLink->section_id)->unique()->values();
        $subjectIds = $links->pluck('subject_id')->unique()->values();
        $exams = Exam::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->when($term, fn ($query) => $query->where('academic_term_id', $term->id))
            ->whereIn('section_id', $sectionIds)
            ->whereIn('subject_id', $subjectIds)
            ->orderByDesc('id')
            ->get();

        $latestByPair = [];
        foreach ($exams as $exam) {
            $pair = $exam->section_id.'-'.$exam->subject_id;
            if (! isset($latestByPair[$pair])) {
                $latestByPair[$pair] = $exam;
            }
        }

        $examIds = collect($latestByPair)->pluck('id');
        $activeIds = $academics->pluck('user_id')->unique()->values();
        $marked = $examIds->isEmpty()
            ? collect()
            : Marks::query()
                ->where('school_id', $schoolId)
                ->whereIn('exam_id', $examIds)
                ->whereIn('student_id', $activeIds)
                ->get(['exam_id', 'student_id', 'section_id']);

        $rows = [];
        $groups = $links->groupBy(fn (Teacherlink $link): string => $link->standardLink->section_id.'-'.$link->subject_id);
        foreach ($groups as $group) {
            $link = $group->first();
            $sectionId = (int) $link->standardLink->section_id;
            $className = $this->classLabel($link->standardLink->section?->name);
            $subjectName = $this->titleLabel($link->subject?->name ?? 'Subject');
            $studentIds = $group
                ->flatMap(fn (Teacherlink $item): array => $studentsByLink->get($item->standardLink_id, []))
                ->unique()
                ->values()
                ->all();
            $studentCount = count($studentIds);
            $exam = $latestByPair[$sectionId.'-'.$link->subject_id] ?? null;
            $entered = 0;
            if ($exam) {
                $entered = $marked
                    ->where('exam_id', $exam->id)
                    ->where('section_id', $sectionId)
                    ->whereIn('student_id', $studentIds)
                    ->pluck('student_id')
                    ->unique()
                    ->count();
            }

            [$marksKey, $marksLabel] = $this->marksState($exam, $term, $studentCount, $entered);

            $rows[] = [
                'section_id' => $sectionId,
                'subject_id' => (int) $link->subject_id,
                'class_name' => $className,
                'subject' => $subjectName,
                'slug' => Str::slug($subjectName),
                'students' => $studentCount,
                'marks_key' => $marksKey,
                'marks_label' => $marksLabel,
            ];
        }

        return $rows;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function marksState(?Exam $exam, ?AcademicTerm $term, int $studentCount, int $entered): array
    {
        if (! $exam) {
            return ['no-exam', $term ? 'No exam this term' : 'No exam yet'];
        }
        if ($studentCount === 0) {
            return ['no-students', 'No students'];
        }
        if ($entered >= $studentCount) {
            return ['entered', 'Entered'];
        }

        return ['pending', 'Pending'];
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @return array{empty: bool, count: int, names: string}
     */
    private function classesTile(array $assignments): array
    {
        $names = collect($assignments)->unique('section_id')->pluck('class_name')->values();
        if ($names->isEmpty()) {
            return ['empty' => true, 'count' => 0, 'names' => 'No classes yet'];
        }

        return ['empty' => false, 'count' => $names->count(), 'names' => $names->implode(', ')];
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @return array{empty: bool, count: int, names: string}
     */
    private function subjectsTile(array $assignments): array
    {
        $names = collect($assignments)->unique('subject_id')->pluck('subject')->values();
        if ($names->isEmpty()) {
            return ['empty' => true, 'count' => 0, 'names' => 'No subjects yet'];
        }

        return ['empty' => false, 'count' => $names->count(), 'names' => $names->implode(', ')];
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<array<string, mixed>>  $pendingRows
     * @return array{empty: bool, count: int, detail: string}
     */
    private function pendingTile(array $assignments, array $pendingRows, bool $hasExam): array
    {
        if ($assignments === []) {
            return ['empty' => true, 'count' => 0, 'detail' => 'No marks to enter'];
        }
        if (! $hasExam) {
            return ['empty' => true, 'count' => 0, 'detail' => 'No exam this term'];
        }

        $first = $pendingRows[0] ?? null;

        return [
            'empty' => false,
            'count' => count($pendingRows),
            'detail' => $first ? $first['subject'].' · '.$first['class_name'] : 'None pending',
        ];
    }

    private function lastLogin(int $schoolId, int $teacherId): string
    {
        $seen = ActivityLog::query()
            ->where('school_id', $schoolId)
            ->where('log_name', 'login')
            ->where('causer_id', $teacherId)
            ->orderByDesc('id')
            ->value('created_at');

        if (! $seen) {
            return 'Never';
        }

        return $this->dateLabel($seen);
    }

    private function personName(User $teacher, ?Userprofile $profile): string
    {
        if ($profile) {
            $combined = trim((string) $profile->firstname.' '.(string) $profile->lastname);
            if ($combined !== '') {
                return $combined;
            }
        }

        $name = trim((string) $teacher->full_name);

        return $name !== '' ? $name : 'Teacher';
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
        foreach (array_slice($words, 0, 2) as $word) {
            $letters .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $letters !== '' ? $letters : 'T';
    }

    private function readableName(string $name): string
    {
        if ($name === '') {
            return $name;
        }
        if ($name === mb_strtoupper($name) || $name === mb_strtolower($name)) {
            return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
        }

        return $name;
    }

    private function titleLabel(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 'Subject';
        }
        if ($value === mb_strtoupper($value) || $value === mb_strtolower($value)) {
            return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE);
        }

        return $value;
    }

    private function classLabel(?string $name): string
    {
        $name = trim((string) $name);

        return $name !== '' ? $this->titleLabel($name) : 'Class';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'exit' => 'Left',
            default => ucfirst($status),
        };
    }

    private function genderLabel(?string $gender): string
    {
        return match (strtolower(trim((string) $gender))) {
            'female', 'f', 'girl' => 'Female',
            'male', 'm', 'boy' => 'Male',
            default => 'Not specified',
        };
    }

    private function designationLabel(?string $designation): string
    {
        $designation = trim((string) $designation);
        if ($designation === '') {
            return 'Teacher';
        }

        return match ($designation) {
            'assistant_teacher' => 'Assistant teacher',
            'co_ordinator' => 'Coordinator',
            'head_of_the_department' => 'Head of department',
            'senior_teacher' => 'Senior teacher',
            'vice_principal' => 'Vice principal',
            'physical_education_teacher' => 'Physical education teacher',
            'transport_coordinator' => 'Transport coordinator',
            'lab_assistant' => 'Lab assistant',
            'stock_keeper' => 'Stock keeper',
            default => $this->titleLabel(str_replace('_', ' ', $designation)),
        };
    }

    private function jobTypeLabel(?string $jobType): string
    {
        return match ($jobType) {
            'full_time' => 'Full time',
            'part_time' => 'Part time',
            default => 'Not recorded',
        };
    }

    private function dateLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Not recorded';
        }
        $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        if ($date->year <= 1970) {
            return 'Not recorded';
        }

        return $date->format('j M Y');
    }
}
