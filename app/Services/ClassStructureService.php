<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Onboarding\Steps\StepIssue;
use App\Onboarding\Steps\StepIssueCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Additive class/stream structure (name-encoded sections).
 *
 * Streams are sections named "{BaseClass} {StreamLabel}" (e.g. "Primary One A").
 * The undivided base section is never removed. standards_link.stream is unused —
 * section.name is the single source of truth.
 */
class ClassStructureService
{
    /**
     * Resolve the undivided base section for a section (itself, or its name prefix).
     */
    public function resolveBaseSection(Section $section): Section
    {
        $name = trim((string) $section->name);
        if ($name === '') {
            return $section;
        }

        $candidates = Section::query()
            ->where('school_id', $section->school_id)
            ->where(function ($q) use ($name) {
                $q->where('name', $name)
                    ->orWhereRaw('? LIKE CONCAT(name, \' %\')', [$name]);
            })
            ->get();

        if ($candidates->isEmpty()) {
            return $section;
        }

        return $candidates->sortBy(fn (Section $s) => strlen((string) $s->name))->first();
    }

    public function isBaseSection(Section $section): bool
    {
        return (int) $this->resolveBaseSection($section)->id === (int) $section->id;
    }

    /**
     * Read-only snapshot of base classes for the year, with stream children and CT.
     *
     * @return list<array{
     *     section_id: int,
     *     standard_link_id: int,
     *     name: string,
     *     class_teacher_id: int|null,
     *     class_teacher_name: string|null,
     *     class_teacher_email: string|null,
     *     streams: list<array{section_id: int, standard_link_id: int, label: string, name: string}>
     * }>
     */
    public function structureSnapshot(School $school, AcademicYear $year): array
    {
        $links = StandardLink::query()
            ->with(['section', 'teacher'])
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->get()
            ->filter(fn (StandardLink $link) => $link->section
                && (int) $link->section->school_id === (int) $school->id
                && (int) $link->section->status === 1);

        if ($links->isEmpty()) {
            return [];
        }

        /** @var array<int, array{base: Section, link: StandardLink, streams: list<array{section_id: int, standard_link_id: int, label: string, name: string}>}> $grouped */
        $grouped = [];

        foreach ($links as $link) {
            $section = $link->section;
            $base = $this->resolveBaseSection($section);
            $baseId = (int) $base->id;

            if (! isset($grouped[$baseId])) {
                $baseLink = $links->first(
                    fn (StandardLink $candidate) => (int) $candidate->section_id === $baseId
                );
                if (! $baseLink) {
                    // Stream exists without a linked base row for this year — skip.
                    continue;
                }
                $grouped[$baseId] = [
                    'base' => $base,
                    'link' => $baseLink,
                    'streams' => [],
                ];
            }

            if ((int) $section->id === $baseId) {
                continue;
            }

            $baseName = trim((string) $base->name);
            $sectionName = trim((string) $section->name);
            $prefix = $baseName.' ';
            $label = str_starts_with($sectionName, $prefix)
                ? trim(substr($sectionName, strlen($prefix)))
                : $sectionName;

            $grouped[$baseId]['streams'][] = [
                'section_id' => (int) $section->id,
                'standard_link_id' => (int) $link->id,
                'label' => $label,
                'name' => $sectionName,
            ];
        }

        $rows = [];
        foreach ($grouped as $entry) {
            $baseLink = $entry['link'];
            $teacher = $baseLink->teacher;
            $streams = $entry['streams'];
            usort($streams, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

            $rows[] = [
                'section_id' => (int) $entry['base']->id,
                'standard_link_id' => (int) $baseLink->id,
                'name' => trim((string) $entry['base']->name),
                'class_teacher_id' => $baseLink->class_teacher_id ? (int) $baseLink->class_teacher_id : null,
                'class_teacher_name' => $teacher?->name,
                'class_teacher_email' => $teacher?->email,
                'streams' => array_values($streams),
            ];
        }

        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return array_values($rows);
    }

    /**
     * Add a named stream under a base class. Keeps the base section; creates
     * "{Base} {Label}" section + StandardLink; copies subjects from the base.
     *
     * @return array{section: Section, standard_link: StandardLink, created: bool}
     *
     * @throws ValidationException
     */
    public function addStream(
        School $school,
        AcademicYear $year,
        Section $fromSection,
        string $streamLabel
    ): array {
        if ((int) $fromSection->school_id !== (int) $school->id) {
            throw ValidationException::withMessages([
                'stream' => 'That class does not belong to this school.',
            ]);
        }

        if ((int) $year->school_id !== (int) $school->id) {
            throw ValidationException::withMessages([
                'stream' => 'Academic year does not belong to this school.',
            ]);
        }

        $streamLabel = trim($streamLabel);
        if ($streamLabel === '') {
            throw ValidationException::withMessages([
                'stream' => 'Enter a stream name (e.g. A, East, Science).',
            ]);
        }

        if (preg_match('/[\/\\\\<>\n\r\t]/', $streamLabel)) {
            throw ValidationException::withMessages([
                'stream' => 'Stream name contains invalid characters.',
            ]);
        }

        $base = $this->resolveBaseSection($fromSection);

        $baseLink = StandardLink::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('section_id', $base->id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->first();

        if (! $baseLink) {
            throw ValidationException::withMessages([
                'stream' => 'Base class is not linked to the current academic year.',
            ]);
        }

        $sectionName = OnboardingEngine::composeClassAndStream($base->name, $streamLabel);
        if ($sectionName === trim((string) $base->name)) {
            throw ValidationException::withMessages([
                'stream' => 'Stream name must differ from the base class name.',
            ]);
        }

        return DB::transaction(function () use ($school, $year, $base, $baseLink, $sectionName) {
            $existing = Section::query()
                ->where('school_id', $school->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($sectionName)])
                ->first();

            $created = false;
            if ($existing) {
                $section = $existing;
                if ((int) $section->status !== 1) {
                    $section->status = '1';
                    $section->save();
                    $created = true;
                }
            } else {
                $section = Section::create([
                    'school_id' => $school->id,
                    'name' => $sectionName,
                    'status' => '1',
                ]);
                $created = true;
            }

            // Name-encoding only — never write standards_link.stream.
            $link = StandardLink::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $baseLink->standard_id,
                    'section_id' => $section->id,
                ],
                [
                    'status' => '1',
                ]
            );

            if ($link->wasRecentlyCreated) {
                $created = true;
            } elseif ((int) $link->status !== 1) {
                $link->status = '1';
                $link->save();
                $created = true;
            }

            $this->copySubjectsFromBase($school, $year, $base, $section, (int) $baseLink->standard_id);

            // Base must still exist after stream add.
            if (! Section::query()->whereKey($base->id)->exists()) {
                throw ValidationException::withMessages([
                    'stream' => 'Base class disappeared unexpectedly.',
                ]);
            }

            return [
                'section' => $section->fresh(),
                'standard_link' => $link->fresh(),
                'created' => $created,
            ];
        });
    }

    /**
     * Rename a stream (or any section) by updating section.name only.
     *
     * @throws ValidationException
     */
    public function renameStream(School $school, Section $section, string $newName): Section
    {
        if ((int) $section->school_id !== (int) $school->id) {
            throw ValidationException::withMessages([
                'name' => 'That class does not belong to this school.',
            ]);
        }

        $newName = trim($newName);
        if ($newName === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a class / stream name.',
            ]);
        }

        if (preg_match('/[\/\\\\<>\n\r\t]/', $newName)) {
            throw ValidationException::withMessages([
                'name' => 'Name contains invalid characters.',
            ]);
        }

        $duplicate = Section::query()
            ->where('school_id', $school->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($newName)])
            ->where('id', '!=', $section->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => "A class named \"{$newName}\" already exists.",
            ]);
        }

        $section->name = $newName;
        $section->save();

        return $section->fresh();
    }

    private function copySubjectsFromBase(
        School $school,
        AcademicYear $year,
        Section $base,
        Section $streamSection,
        int $standardId
    ): void {
        $parents = Subject::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('section_id', $base->id)
            ->get();

        foreach ($parents as $subject) {
            Subject::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $subject->standard_id ?: $standardId,
                    'section_id' => $streamSection->id,
                    'name' => $subject->name,
                ],
                [
                    'code' => $subject->code,
                    'type' => $subject->type,
                    'status' => $subject->status ?? 1,
                ]
            );
        }
    }

    /**
     * Stream problems on a classes payload. Class-name checks stay in saveClasses
     * so they keep the messages the engine already throws.
     *
     * @param  list<array<string, mixed>>  $classes
     * @return list<StepIssue>
     */
    public function issues(School $school, array $classes): array
    {
        foreach ($classes as $index => $class) {
            if (! is_array($class)) {
                continue;
            }
            $className = trim((string) ($class['name'] ?? ''));
            if ($className === '') {
                continue;
            }
            $row = is_int($index) ? $index : null;
            $seen = [];
            foreach ($this->streamLabels($class) as $label) {
                $key = strtolower($label);
                if (isset($seen[$key])) {
                    return [$this->streamIssue($className, $label, 'is listed more than once.', $row)];
                }
                $seen[$key] = true;
            }
            foreach ($this->streamRenames($class) as $from => $to) {
                if ($this->activeStreamSection($school, $className, $from) === null) {
                    return [$this->streamIssue($className, $from, "doesn't exist.", $row)];
                }
                $target = OnboardingEngine::composeClassAndStream($className, $to);
                $taken = Section::query()
                    ->where('school_id', $school->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($target)])
                    ->exists();
                if ($taken) {
                    return [$this->streamIssue($className, $to, 'is listed more than once.', $row)];
                }
            }
            foreach ($this->namedStreams($class, 'remove_streams') as $label) {
                $section = $this->activeStreamSection($school, $className, $label);
                if ($section === null) {
                    return [$this->streamIssue($className, $label, "doesn't exist.", $row)];
                }
                if ($this->streamHasStudents($school, $section)) {
                    return [$this->streamIssue($className, $label, 'still has students.', $row)];
                }
            }
        }

        return [];
    }

    /**
     * Read-only confirmation of what saveClasses would add.
     *
     * @param  list<array<string, mixed>>  $classes
     * @return array{action: string, summary: string, rows: list<array{label: string, status: string, detail: ?string}>}
     */
    public function preview(School $school, array $classes): array
    {
        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->first();
        $rows = [];
        $newClasses = 0;
        $newStreams = 0;

        foreach ($classes as $class) {
            if (! is_array($class)) {
                continue;
            }
            $className = trim((string) ($class['name'] ?? ''));
            $labels = $this->streamLabels($class);
            $detail = $labels !== [] ? 'Streams: '.implode(', ', $labels) : null;
            if ($className === '') {
                $rows[] = ['label' => '(unnamed class)', 'status' => 'skip', 'detail' => 'needs a name'];

                continue;
            }
            if ($year === null || (int) $year->school_id !== (int) $school->id) {
                $rows[] = ['label' => $className, 'status' => 'skip', 'detail' => 'needs an academic year first'];

                continue;
            }

            $exists = Section::query()
                ->where('school_id', $school->id)
                ->where('name', $className)
                ->exists();
            $added = 0;
            foreach ($labels as $label) {
                $composed = OnboardingEngine::composeClassAndStream($className, $label);
                if ($composed === $className || $this->activeStreamSection($school, $className, $label) !== null) {
                    continue;
                }
                $added++;
            }
            if (! $exists) {
                $newClasses++;
            }
            $newStreams += $added;
            $rows[] = [
                'label' => $className,
                'status' => match (true) {
                    ! $exists => 'create',
                    $added > 0 => 'update',
                    default => 'already_present',
                },
                'detail' => $detail,
            ];
        }

        $creating = $newClasses > 0 || $newStreams > 0;

        return [
            'action' => $creating ? 'change' : 'noop',
            'summary' => $creating
                ? "I'll create ".$this->countWord($newClasses, 'class', 'classes').' and '.$this->countWord($newStreams, 'stream', 'streams')
                : 'All listed classes already exist — nothing will change.',
            'rows' => $rows,
        ];
    }

    /**
     * Create and edit classes and streams for this school only.
     *
     * Streams are additive ("{Class} {Label}"). remove_streams flags a stream
     * inactive when it has no students. rename renames the base class after
     * stream edits, so existing stream names stay attached to the old prefix.
     *
     * @param  list<array<string, mixed>>  $classes
     *
     * @throws ValidationException
     */
    public function saveClasses(School $school, AcademicYear $year, array $classes): void
    {
        if ((int) $year->school_id !== (int) $school->id) {
            throw ValidationException::withMessages([
                'classes' => 'Academic year does not belong to this school.',
            ]);
        }

        if ($classes === []) {
            throw ValidationException::withMessages(['classes' => 'Add at least one class.']);
        }

        $seenNames = [];
        foreach ($classes as $class) {
            $name = trim((string) (is_array($class) ? ($class['name'] ?? '') : ''));
            if ($name === '') {
                throw ValidationException::withMessages(['classes' => 'Each class must have a name.']);
            }
            if (in_array($name, $seenNames, true)) {
                throw ValidationException::withMessages(['classes' => "Duplicate class name: {$name}."]);
            }
            $seenNames[] = $name;
        }

        $streamIssues = $this->issues($school, $classes);
        if ($streamIssues !== []) {
            throw ValidationException::withMessages(['classes' => $streamIssues[0]->cause]);
        }

        if ($school->school_category) {
            SchoolCategorySeeder::seed($school);
        }

        $engine = app(OnboardingEngine::class);
        foreach ($classes as $class) {
            $className = trim((string) ($class['name'] ?? ''));
            $subGroup = $class['sub_group'] ?? null;
            $standardName = $engine->standardNameForClass($className);
            $standard = Standard::firstOrCreate(
                ['school_id' => $school->id, 'name' => $standardName],
                [
                    'order' => match ($standardName) {
                        'nursery' => 1,
                        'primary' => 2,
                        'o-level' => 3,
                        'a-level' => 4,
                        default => 5,
                    },
                    'status' => '1',
                ]
            );
            $baseSection = Section::firstOrCreate(
                ['school_id' => $school->id, 'name' => $className],
                ['status' => '1']
            );

            StandardLink::firstOrCreate(array_filter([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $baseSection->id,
                'status' => '1',
                'sub_group' => $subGroup,
            ]));

            foreach ($this->namedStreams($class, 'remove_streams') as $label) {
                $section = $this->activeStreamSection($school, $className, $label);
                if ($section === null) {
                    continue;
                }
                $section->status = '0';
                $section->save();
                StandardLink::query()
                    ->where('school_id', $school->id)
                    ->where('section_id', $section->id)
                    ->update(['status' => '0']);
            }

            foreach ($this->streamRenames($class) as $from => $to) {
                $section = $this->activeStreamSection($school, $className, $from);
                if ($section === null) {
                    continue;
                }
                $this->renameStream(
                    $school,
                    $section,
                    OnboardingEngine::composeClassAndStream($className, $to)
                );
            }

            foreach ($this->streamLabels($class) as $label) {
                $result = $this->addStream($school, $year, $baseSection, $label);
                if ($subGroup !== null && $subGroup !== '') {
                    $link = $result['standard_link'];
                    if ($link->sub_group !== $subGroup) {
                        $link->sub_group = $subGroup;
                        $link->save();
                    }
                }
            }

            $rename = trim((string) ($class['rename'] ?? ''));
            if ($rename !== '' && strcasecmp($rename, $className) !== 0) {
                $this->renameStream($school, $baseSection, $rename);
            }
        }
    }

    private function streamIssue(string $className, string $label, string $problem, ?int $row): StepIssue
    {
        return StepIssueCatalog::fromMessage(
            'standards',
            $className.', stream '.$label.' '.$problem,
            $row,
        );
    }

    /**
     * @param  array<string, mixed>  $class
     * @return list<string>
     */
    private function streamLabels(array $class): array
    {
        return $this->namedStreams($class, 'streams');
    }

    /**
     * @param  array<string, mixed>  $class
     * @return list<string>
     */
    private function namedStreams(array $class, string $key): array
    {
        $streams = $class[$key] ?? [];
        if (! is_array($streams)) {
            return [];
        }
        $labels = [];
        foreach ($streams as $stream) {
            $label = trim((string) $stream);
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /**
     * @param  array<string, mixed>  $class
     * @return array<string, string>
     */
    private function streamRenames(array $class): array
    {
        $map = $class['rename_streams'] ?? [];
        if (! is_array($map)) {
            return [];
        }
        $renames = [];
        foreach ($map as $from => $to) {
            $from = trim((string) $from);
            $to = trim((string) $to);
            if ($from === '' || $to === '') {
                continue;
            }
            $renames[$from] = $to;
        }

        return $renames;
    }

    private function activeStreamSection(School $school, string $className, string $label): ?Section
    {
        $name = OnboardingEngine::composeClassAndStream($className, $label);
        if ($name === '' || strcasecmp($name, $className) === 0) {
            return null;
        }

        return Section::query()
            ->where('school_id', $school->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->first();
    }

    private function streamHasStudents(School $school, Section $section): bool
    {
        $linkIds = StandardLink::query()
            ->where('school_id', $school->id)
            ->where('section_id', $section->id)
            ->pluck('id');
        if ($linkIds->isEmpty()) {
            return false;
        }

        return StudentAcademic::query()
            ->where('school_id', $school->id)
            ->whereIn('standardLink_id', $linkIds)
            ->exists();
    }

    private function countWord(int $count, string $one, string $many): string
    {
        return $count.' '.($count === 1 ? $one : $many);
    }
}
