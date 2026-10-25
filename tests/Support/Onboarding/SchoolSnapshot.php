<?php

namespace Tests\Support\Onboarding;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassStructureService;

/**
 * Saved onboarding data with ids and timestamps removed and lists sorted.
 *
 * School name, EMIS, admin contact, and toshi_mode stay on the identity bag.
 * They are allowed to differ between two faces of the same fixture.
 */
final class SchoolSnapshot
{
    /**
     * @param  array<string, mixed>  $comparable
     * @param  array<string, mixed>  $identity
     */
    private function __construct(
        private array $comparable,
        private array $identity,
    ) {}

    public static function capture(School $school): self
    {
        $school = $school->fresh();
        $year = AcademicYear::query()->where('school_id', $school->id)->orderBy('id')->first();
        $admin = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 3)
            ->orderBy('id')
            ->first();

        return new self(
            [
                'settings' => [
                    'student_size' => (string) $school->student_size,
                    'country' => (string) $school->registration_country,
                    'curriculum' => (string) $school->curriculum,
                    'category' => (string) $school->school_category,
                    'uneb_center_number' => (string) ($school->uneb_center_number ?? ''),
                    'toshi_enabled' => (int) $school->toshi_enabled,
                    'academic_year' => $year === null ? null : [
                        'name' => (string) $year->name,
                        'start' => optional($year->start_date)?->toDateString(),
                        'end' => optional($year->end_date)?->toDateString(),
                        'description' => (string) $year->description,
                    ],
                ],
                'classes' => self::classes($school, $year),
                'subjects' => self::subjects($school),
                'students' => self::students($school),
                'parents' => self::parents($school),
                'terms' => self::terms($school),
                'fees' => self::fees($school),
                'plan' => self::planName($school),
            ],
            [
                'name' => (string) $school->name,
                'emis' => (string) $school->ministry_code,
                'admin_name' => (string) ($admin->name ?? ''),
                'admin_email' => (string) ($admin->email ?? ''),
                'admin_phone' => (string) ($admin->mobile_no ?? ''),
                'toshi_mode' => $school->toshi_mode instanceof \BackedEnum
                    ? $school->toshi_mode->value
                    : (string) $school->toshi_mode,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function comparable(): array
    {
        return $this->comparable;
    }

    /**
     * @return array<string, mixed>
     */
    public function identity(): array
    {
        return $this->identity;
    }

    /**
     * @return list<array{name: string, tier: string, streams: list<string>, class_teacher: ?string}>
     */
    private static function classes(School $school, ?AcademicYear $year): array
    {
        if ($year === null) {
            return [];
        }

        $rows = [];
        foreach (app(ClassStructureService::class)->structureSnapshot($school, $year) as $row) {
            $link = StandardLink::query()
                ->where('school_id', $school->id)
                ->whereKey($row['standard_link_id'])
                ->first();
            $tier = $link ? (string) Standard::query()->where('school_id', $school->id)->whereKey($link->standard_id)->value('name') : '';
            $labels = array_map(fn (array $stream): string => (string) $stream['label'], $row['streams']);
            sort($labels, SORT_STRING);

            $rows[] = [
                'name' => (string) $row['name'],
                'tier' => $tier,
                'streams' => $labels,
                'class_teacher' => ($row['class_teacher_name'] ?? null) !== null && $row['class_teacher_name'] !== ''
                    ? (string) $row['class_teacher_name']
                    : null,
            ];
        }

        usort($rows, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @return list<array{name: string, class: string, stream: string}>
     */
    private static function subjects(School $school): array
    {
        $structure = app(ClassStructureService::class);
        $rows = [];

        $subjects = Subject::query()->where('school_id', $school->id)->get();
        foreach ($subjects as $subject) {
            $section = Section::query()
                ->where('school_id', $school->id)
                ->whereKey($subject->section_id)
                ->first();
            if ($section === null) {
                continue;
            }

            $base = $structure->resolveBaseSection($section);
            $baseName = trim((string) $base->name);
            $sectionName = trim((string) $section->name);
            $prefix = $baseName.' ';
            $stream = (int) $section->id === (int) $base->id
                ? ''
                : (str_starts_with($sectionName, $prefix) ? trim(substr($sectionName, strlen($prefix))) : $sectionName);

            $rows[] = [
                'name' => (string) $subject->getRawOriginal('name'),
                'class' => $baseName,
                'stream' => $stream,
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['class'], $a['stream'], $a['name']] <=> [$b['class'], $b['stream'], $b['name']]);

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function students(School $school): array
    {
        $structure = app(ClassStructureService::class);
        $rows = [];

        $academics = StudentAcademic::query()
            ->where('school_id', $school->id)
            ->with(['user.userprofile', 'standardLink.section'])
            ->get();

        foreach ($academics as $academic) {
            $user = $academic->user;
            if ($user === null || (int) $user->school_id !== (int) $school->id || (int) $user->usergroup_id !== 6) {
                continue;
            }

            $section = $academic->standardLink?->section;
            $class = '';
            $stream = '';
            if ($section !== null && (int) $section->school_id === (int) $school->id) {
                $base = $structure->resolveBaseSection($section);
                $class = trim((string) $base->name);
                $sectionName = trim((string) $section->name);
                $prefix = $class.' ';
                $stream = (int) $section->id === (int) $base->id
                    ? ''
                    : (str_starts_with($sectionName, $prefix) ? trim(substr($sectionName, strlen($prefix))) : $sectionName);
            }

            $profile = $user->userprofile;
            $dob = $profile?->getRawOriginal('date_of_birth');
            $dobText = is_string($dob) && $dob !== '' ? substr($dob, 0, 10) : '';

            $rows[] = [
                'name' => (string) $user->name,
                'gender' => (string) ($profile->gender ?? ''),
                'date_of_birth' => $dobText,
                'class' => $class,
                'stream' => $stream,
                'school_student_id' => (string) ($academic->school_student_id ?? ''),
                'board_registration_number' => (string) ($academic->board_registration_number ?? ''),
                'lin' => (string) ($academic->lin ?? ''),
                'phone' => (string) ($user->mobile_no ?? ''),
            ];
        }

        usort($rows, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @return list<array{name: string, phone: string, students: list<string>, status: string}>
     */
    private static function parents(School $school): array
    {
        $links = StudentParentLink::query()->where('school_id', $school->id)->get();
        $grouped = [];

        foreach ($links as $link) {
            $parent = User::query()
                ->where('school_id', $school->id)
                ->where('usergroup_id', 7)
                ->whereKey($link->parent_id)
                ->first();
            $student = User::query()
                ->where('school_id', $school->id)
                ->where('usergroup_id', 6)
                ->whereKey($link->student_id)
                ->first();
            if ($parent === null || $student === null) {
                continue;
            }

            $key = (int) $parent->id;
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'name' => (string) $parent->name,
                    'phone' => (string) ($parent->mobile_no ?? ''),
                    'students' => [],
                    'status' => (string) $link->status,
                ];
            }
            $grouped[$key]['students'][] = (string) $student->name;
        }

        $rows = array_values($grouped);
        foreach ($rows as &$row) {
            sort($row['students'], SORT_STRING);
        }
        unset($row);
        usort($rows, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @return list<array{name: string, start: ?string, end: ?string, status: string}>
     */
    private static function terms(School $school): array
    {
        $rows = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->get()
            ->map(fn (AcademicTerm $term): array => [
                'name' => (string) $term->name,
                'start' => optional($term->starts_on)?->toDateString(),
                'end' => optional($term->ends_on)?->toDateString(),
                'status' => (string) $term->status,
            ])
            ->all();

        usort($rows, fn (array $a, array $b): int => [$a['start'], $a['name']] <=> [$b['start'], $b['name']]);

        return $rows;
    }

    /**
     * @return list<array{name: string, amount: string, tier: string, section: ?string, term: ?string}>
     */
    private static function fees(School $school): array
    {
        $rows = [];
        $fees = FeesCategories::query()->where('school_id', $school->id)->get();

        foreach ($fees as $fee) {
            $tier = (string) Standard::query()->where('school_id', $school->id)->whereKey($fee->standard_id)->value('name');
            $section = $fee->section_id
                ? Section::query()->where('school_id', $school->id)->whereKey($fee->section_id)->value('name')
                : null;
            $term = $fee->academic_term_id
                ? AcademicTerm::query()->where('school_id', $school->id)->whereKey($fee->academic_term_id)->value('name')
                : null;

            $rows[] = [
                'name' => (string) $fee->name,
                'amount' => number_format((float) $fee->amount, 2, '.', ''),
                'tier' => $tier,
                'section' => $section !== null ? (string) $section : null,
                'term' => $term !== null ? (string) $term : null,
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['name'], $a['tier'], (string) $a['section'], (string) $a['term']]
            <=> [$b['name'], $b['tier'], (string) $b['section'], (string) $b['term']]);

        return $rows;
    }

    private static function planName(School $school): string
    {
        $planId = CurrentPlan::query()->where('school_id', $school->id)->value('plan_id');
        if (! $planId) {
            return '';
        }

        return (string) Plan::query()->whereKey($planId)->value('name');
    }
}
