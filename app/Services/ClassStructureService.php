<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\Subject;
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
}
