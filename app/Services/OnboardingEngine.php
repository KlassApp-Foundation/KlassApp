<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\StudentIdGeneratorService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingEngine
{
    /**
     * Persist the real school name, rejecting placeholders and duplicate names.
     *
     * @throws ValidationException
     */
    public function saveSchoolName(School $school, string $name): School
    {
        $name = trim($name);

        $validator = Validator::make(
            ['name' => $name],
            ['name' => 'required|string|min:3'],
            [
                'name.required' => 'Enter your real school name.',
                'name.min' => 'The school name must be at least 3 characters.',
            ]
        );
        $validator->validate();

        if (OnboardingStepsService::isPlaceholderSchoolName($name)) {
            throw ValidationException::withMessages(['name' => 'Enter your real school name.']);
        }

        // If the name is changing and the new name collides with another school,
        // resolve a unique suffix the same way new signups do.
        if ($school->name !== $name
            && School::where('name', $name)->where('id', '!=', $school->id)->exists()
        ) {
            $name = app(SchoolSignupBootstrapService::class)->uniqueSchoolName($name);
        }

        $school->name = $name;
        $school->slug = Str::slug($name);
        $school->save();

        return $school;
    }

    /**
     * Persist approximate school size (onboarding segmentation).
     *
     * @throws ValidationException
     */
    public function saveStudentSize(School $school, string $studentSize): void
    {
        $studentSize = trim($studentSize);

        if ($studentSize === '' || ! in_array($studentSize, OnboardingStepsService::STUDENT_SIZE_OPTIONS, true)) {
            throw ValidationException::withMessages([
                'studentSize' => 'Choose an approximate school size.',
            ]);
        }

        $school->student_size = $studentSize;
        $school->save();
    }

    /**
     * Persist the school's registration country and linked country_id if known.
     *
     * @throws ValidationException
     */
    public function saveCountry(School $school, string $country): void
    {
        $country = trim($country);

        if ($country === '') {
            throw ValidationException::withMessages(['country' => 'Choose a country.']);
        }

        OnboardingStepsService::persistCountry($school, $country);
    }

    /**
     * Persist the selected curriculum.
     *
     * @throws ValidationException
     */
    public function saveCurriculum(School $school, string $curriculum): void
    {
        $curriculum = strtolower(trim($curriculum));

        if ($curriculum === '') {
            throw ValidationException::withMessages(['curriculum' => 'Choose a curriculum.']);
        }

        if (! in_array($curriculum, ['uneb', 'cambridge', 'montessori', 'other'], true)) {
            throw ValidationException::withMessages(['curriculum' => 'Choose a valid curriculum.']);
        }

        $school->curriculum = $curriculum;
        $school->save();
    }

    /**
     * Persist the selected school category and seed canonical defaults.
     *
     * @throws ValidationException
     */
    public function saveSchoolCategory(School $school, string $category): void
    {
        $category = trim($category);

        if ($category === '' || ! array_key_exists($category, SchoolCategorySeeder::CATEGORIES)) {
            throw ValidationException::withMessages(['schoolCategory' => 'Choose a school category.']);
        }

        $school->school_category = $category;
        $school->save();

        SchoolCategorySeeder::seed($school);
    }

    /**
     * Persist the EMIS / Ministry code for Ugandan schools.
     *
     * @throws ValidationException
     */
    public function saveEmis(School $school, string $ministryCode): void
    {
        $ministryCode = trim($ministryCode);

        if (! OnboardingStepsService::isUganda($school->registration_country)) {
            return;
        }

        if ($ministryCode === '') {
            throw ValidationException::withMessages(['ministryCode' => 'Enter your EMIS / ministry code.']);
        }

        $school->ministry_code = $ministryCode;
        $school->save();
    }

    /**
     * Persist the UNEB centre number if the column exists.
     *
     * null means "not asked yet" and is a no-op.
     * '' means "asked and skipped" and is stored as the empty string.
     */
    public function saveUnebCenter(School $school, ?string $unebCenterNumber): void
    {
        if (! Schema::hasColumn('schools', 'uneb_center_number')) {
            return;
        }

        if ($unebCenterNumber === null) {
            return;
        }

        $school->uneb_center_number = trim($unebCenterNumber);
        $school->save();
    }

    /**
     * Persist or update the school's academic year.
     *
     * @throws ValidationException
     */
    public function saveAcademicYear(School $school, string $name, ?string $start = null, ?string $end = null, ?string $description = null): AcademicYear
    {
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages(['academicYear' => 'Enter an academic year.']);
        }

        $hasStart = $start !== null && trim($start) !== '';
        $hasEnd = $end !== null && trim($end) !== '';

        if ($hasStart !== $hasEnd) {
            throw ValidationException::withMessages(['academicYear' => 'Provide both start and end dates.']);
        }

        if ($hasStart) {
            $validator = Validator::make(
                ['academicYearStart' => $start, 'academicYearEnd' => $end],
                [
                    'academicYearStart' => 'required|date',
                    'academicYearEnd' => 'required|date|after:academicYearStart',
                ],
                [
                    'academicYearEnd.after' => 'The academic year end date must be after the start date.',
                ]
            );
            $validator->validate();
        }

        $yearName = preg_match('/\d{4}/', $name, $matches) ? $matches[0] : (string) now()->year;

        $startDate = $hasStart
            ? Carbon::parse($start)->startOfDay()
            : now()->setYear((int) $yearName)->startOfYear();

        $endDate = $hasStart
            ? Carbon::parse($end)->endOfDay()
            : now()->setYear((int) $yearName)->endOfYear();

        $year = AcademicYear::firstOrCreate(
            ['school_id' => $school->id],
            [
                'name' => $name,
                'description' => $description ?? 'Current Academic Year',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 1,
            ]
        );

        if (! $year->wasRecentlyCreated) {
            $year->name = $name;

            if ($description !== null && trim($description) !== '') {
                $year->description = trim($description);
            }

            if ($hasStart) {
                $year->start_date = $startDate;
                $year->end_date = $endDate;
            }

            $year->save();
        }

        Cache::forget('academic_year_for_school_'.$school->id);

        if ($school->school_category) {
            SchoolCategorySeeder::seed($school);
        }

        return $year;
    }

    /**
     * Determine whether a class/section name corresponds to a UNEB
     * candidate year — P.7 (PLE), S.4 (UCE), or S.6 (UACE).
     *
     * Uses the same normalisation approach as standardNameForClass()
     * but targets the specific exam-candidate classes rather than
     * broad grading tiers. This is intentionally hardcoded: the
     * standing discipline is not to over-build ahead of real need,
     * and these three are the only UNEB exam classes in Uganda.
     *
     * NOTE: The previous validation gated board_registration_number
     * to Indian-system standards '10'/'11'/'12' — this was a
     * production bug that made the field effectively always-required
     * (because the Standard.name comparison never matched the
     * actual Ugandan class names in the database). This method
     * replaces that with the correct Ugandan candidate-class check.
     */
    public static function isCandidateClass(string $className): bool
    {
        $lower = strtolower(trim($className));

        // PLE: Primary Seven — P.7, P7, P 7, Primary Seven
        if (in_array($lower, ['p.7', 'p7', 'p 7', 'primary seven'], true)) {
            return true;
        }

        // UCE: Senior Four — S.4, S4, S 4, Senior Four
        if (in_array($lower, ['s.4', 's4', 's 4', 'senior four'], true)) {
            return true;
        }

        // UACE: Senior Six — S.6, S6, S 6, Senior Six
        if (in_array($lower, ['s.6', 's6', 's 6', 'senior six'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Map a class/section name to a standard (grading-tier) name.
     *
     * Follows the same heuristics as SchoolCategorySeeder and
     * AgentToshi's commitAll fuzzy-match logic. Nursery names map
     * to 'nursery', Senior names to 'o-level' or 'a-level', and
     * everything else defaults to 'primary'.
     */
    private function standardNameForClass(string $className): string
    {
        $lower = strtolower(trim($className));

        // Nursery: Baby Class, Middle Class, Top Class
        if (in_array($lower, ['baby class', 'middle class', 'top class'])
            || str_starts_with($lower, 'nursery')
            || in_array($lower[0] ?? '', ['b', 'm', 't']) && (
                str_contains($lower, 'baby') || str_contains($lower, 'middle') || str_contains($lower, 'top class')
            )
        ) {
            return 'nursery';
        }

        // A-Level: Senior Five, Senior Six, S.5, S.6, S5, S6
        if (in_array($lower, ['senior five', 'senior six', 's.5', 's.6', 's5', 's6', 'a-level', 'a level'])) {
            return 'a-level';
        }

        // O-Level: Senior One through Senior Four, S.1–S.4, S1–S4
        if (preg_match('/^s\.?[1-4](?:\s|$)/i', $lower)
            || in_array($lower, ['senior one', 'senior two', 'senior three', 'senior four', 'o-level', 'o level'])
            || str_starts_with($lower, 'senior ') && ! in_array($lower, ['senior five', 'senior six'])
        ) {
            return 'o-level';
        }

        // Default: primary
        return 'primary';
    }

    /**
     * Canonical UNEB word forms for Primary 1–7 / Senior 1–6 ordinals.
     *
     * @return array<int, string>
     */
    private static function classOrdinalWords(): array
    {
        return [
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
        ];
    }

    /**
     * Expand a class token into every known alias (S.4 ↔ Senior Four ↔ Senior 4, etc.).
     *
     * Used so Toshi short-forms and SchoolCategorySeeder long-forms resolve to the
     * same Section. Never invents a match against an arbitrary first class.
     *
     * @return list<string>
     */
    public static function classNameCandidates(string $className): array
    {
        $raw = trim($className);
        if ($raw === '') {
            return [];
        }

        $candidates = [$raw];
        $lower = strtolower($raw);
        $words = self::classOrdinalWords();
        $wordToNum = [];
        foreach ($words as $num => $word) {
            $wordToNum[strtolower($word)] = $num;
        }

        // Primary: P.1 / P1 / Primary 1 / Primary One
        if (preg_match('/^(?:p\.?\s*|primary\s+)([1-7]|one|two|three|four|five|six|seven)\b(.*)$/i', $lower, $m)) {
            $n = ctype_digit($m[1]) ? (int) $m[1] : ($wordToNum[$m[1]] ?? 0);
            $suffix = trim($m[2] ?? '');
            if ($n >= 1 && $n <= 7) {
                $word = $words[$n];
                foreach (["P.{$n}", "P{$n}", "P {$n}", "Primary {$n}", "Primary {$word}"] as $alias) {
                    $candidates[] = $suffix !== '' ? "{$alias} {$suffix}" : $alias;
                }
            }
        }

        // Senior: S.4 / S4 / Senior 4 / Senior Four
        if (preg_match('/^(?:s\.?\s*|senior\s+)([1-6]|one|two|three|four|five|six)\b(.*)$/i', $lower, $m)) {
            $n = ctype_digit($m[1]) ? (int) $m[1] : ($wordToNum[$m[1]] ?? 0);
            $suffix = trim($m[2] ?? '');
            if ($n >= 1 && $n <= 6) {
                $word = $words[$n];
                foreach (["S.{$n}", "S{$n}", "S {$n}", "Senior {$n}", "Senior {$word}"] as $alias) {
                    $candidates[] = $suffix !== '' ? "{$alias} {$suffix}" : $alias;
                }
            }
        }

        // Nursery fixed names
        if (in_array($lower, ['baby', 'baby class', 'bc'], true)) {
            $candidates[] = 'Baby Class';
        }
        if (in_array($lower, ['middle', 'middle class', 'mc'], true)) {
            $candidates[] = 'Middle Class';
        }
        if (in_array($lower, ['top', 'top class', 'tc'], true)) {
            $candidates[] = 'Top Class';
        }

        $unique = [];
        foreach ($candidates as $c) {
            $trimmed = trim($c);
            if ($trimmed === '') {
                continue;
            }
            $key = strtolower($trimmed);
            if (! isset($unique[$key])) {
                $unique[$key] = $trimmed;
            }
        }

        return array_values($unique);
    }

    /**
     * Compose a section lookup name from class + optional stream.
     *
     * Onboarding streams create sections named "{Class} {Stream}" (e.g. "P1 A").
     * If $className already ends with the stream token, it is returned unchanged
     * so "P1 A" + stream "A" does not become "P1 A A".
     */
    public static function composeClassAndStream(string $className, string $stream): string
    {
        $className = trim($className);
        $stream = trim($stream);
        if ($className === '' || $stream === '') {
            return $className;
        }

        $classLower = strtolower($className);
        $streamLower = strtolower($stream);
        if ($classLower === $streamLower || str_ends_with($classLower, ' '.$streamLower)) {
            return $className;
        }

        return $className.' '.$stream;
    }

    /**
     * Resolve a StandardLink for a class/section name with short-form aliases.
     *
     * When $stream is provided, matches the specific stream section (exact / alias
     * equality only — no prefix LIKE) so "P1"+"A" lands on "P1 A", not the first
     * "P1 …" section. When $stream is empty, keeps the legacy first-match behaviour
     * including prefix LIKE for backward compatibility.
     *
     * Returns null when no section matches — callers must not invent a fallback.
     */
    public function resolveStandardLinkForClass(
        School $school,
        AcademicYear $year,
        string $className,
        ?string $stream = null
    ): ?StandardLink {
        $className = trim($className);
        if ($className === '') {
            return null;
        }

        $stream = trim((string) $stream);
        $lookupName = $stream !== ''
            ? self::composeClassAndStream($className, $stream)
            : $className;
        $allowPrefixMatch = $stream === '';

        $candidates = self::classNameCandidates($lookupName);

        foreach ($candidates as $candidate) {
            $link = StandardLink::with(['standard', 'section'])
                ->where('school_id', $school->id)
                ->where('academic_year_id', $year->id)
                ->whereHas('section', function ($query) use ($school, $candidate, $allowPrefixMatch) {
                    $query->where('school_id', $school->id)
                        ->where(function ($q) use ($candidate, $allowPrefixMatch) {
                            $q->whereRaw('LOWER(name) = ?', [strtolower($candidate)]);
                            if ($allowPrefixMatch) {
                                $q->orWhereRaw('LOWER(name) LIKE ?', [strtolower($candidate).' %']);
                            }
                        });
                })
                ->first();

            if ($link) {
                return $link;
            }
        }

        // Bidirectional: existing section aliases include the input (e.g. DB "Senior Four", input "S.4")
        $links = StandardLink::with(['standard', 'section'])
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->get();

        $inputKeys = array_map('strtolower', $candidates);
        foreach ($links as $link) {
            $sectionName = trim((string) ($link->section?->name ?? ''));
            if ($sectionName === '') {
                continue;
            }
            $sectionKeys = array_map('strtolower', self::classNameCandidates($sectionName));
            if (array_intersect($inputKeys, $sectionKeys) !== []) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Persist standards (grading tiers), sections (classes), and standard_links
     * for the given academic year.
     *
     * Each entry in $classes creates a Section and a StandardLink. If the school
     * has a school_category set, SchoolCategorySeeder is run first as a baseline,
     * and user-supplied classes supplement (not replace) the seeded defaults.
     * When no category is set, a 'primary' standard is created as fallback.
     *
     * Streams: if 'streams' is non-empty, keeps the undivided base Section + link
     * and adds each stream via ClassStructureService::addStream ("{Class} {Label}").
     * If empty, one Section per class name with one StandardLink.
     *
     * @param  School  $school
     * @param  AcademicYear  $year
     * @param  array  $classes  [ ['name' => 'P1', 'streams' => ['A','B']], ... ]
     *
     * @throws ValidationException
     */
    public function saveStandards(School $school, AcademicYear $year, array $classes): void
    {
        if (empty($classes)) {
            throw ValidationException::withMessages(['classes' => 'Add at least one class.']);
        }

        // Validate class names and check for duplicates
        $seenNames = [];
        foreach ($classes as $class) {
            $name = trim((string) ($class['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages(['classes' => 'Each class must have a name.']);
            }
            if (in_array($name, $seenNames, true)) {
                throw ValidationException::withMessages(['classes' => "Duplicate class name: {$name}."]);
            }
            $seenNames[] = $name;
        }

        // If school has a category, run the seeder first as a baseline
        // (SchoolCategorySeeder is idempotent — early-returns if links already exist)
        if ($school->school_category) {
            SchoolCategorySeeder::seed($school);
        }

        foreach ($classes as $class) {
            $className = trim((string) ($class['name'] ?? ''));
            $streams = $class['streams'] ?? [];
            $subGroup = $class['sub_group'] ?? null;

            $standardName = $this->standardNameForClass($className);

            // Ensure the grading-tier Standard exists
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

            // Always keep the undivided base section (ClassStructureService parity).
            // Streams are additive name-encoded children ("{Class} {Label}"), never
            // replacements for the base.
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

            if (is_array($streams) && count($streams) > 0) {
                $structure = app(ClassStructureService::class);
                foreach ($streams as $stream) {
                    $streamName = trim((string) $stream);
                    if ($streamName === '') {
                        continue;
                    }
                    $result = $structure->addStream($school, $year, $baseSection, $streamName);
                    if ($subGroup !== null && $subGroup !== '') {
                        $link = $result['standard_link'];
                        if ($link->sub_group !== $subGroup) {
                            $link->sub_group = $subGroup;
                            $link->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Persist subjects for one or more classes.
     *
     * Each key in $subjectsByClass is a class name that must correspond to at
     * least one existing Section/StandardLink for this school+year. Subjects
     * are attached to every section under that class name (so stream classes
     * each get their own copy). firstOrCreate is used throughout for idempotency.
     *
     * If SchoolCategorySeeder already created core subjects, firstOrCreate will
     * not duplicate them.
     *
     * @param  School  $school
     * @param  AcademicYear  $year
     * @param  array  $subjectsByClass  [ 'P1' => ['Mathematics', 'English'], ... ]
     *
     * @throws ValidationException
     */
    public function saveSubjects(School $school, AcademicYear $year, array $subjectsByClass): void
    {
        if (empty($subjectsByClass)) {
            throw ValidationException::withMessages(['subjects' => 'Add at least one subject.']);
        }

        // Validate all class keys and subject names upfront
        foreach ($subjectsByClass as $className => $subjectNames) {
            $className = trim((string) $className);
            if ($className === '') {
                throw ValidationException::withMessages(['subjects' => 'Class name cannot be empty.']);
            }

            if (! is_array($subjectNames) || empty($subjectNames)) {
                throw ValidationException::withMessages(['subjects' => "Class '{$className}' must have at least one subject."]);
            }

            foreach ($subjectNames as $subjectName) {
                $subjectName = trim((string) $subjectName);
                if ($subjectName === '') {
                    throw ValidationException::withMessages(['subjects' => 'Subject name cannot be empty.']);
                }
            }
        }

        // Resolve each class name to its StandardLinks and create subjects
        foreach ($subjectsByClass as $className => $subjectNames) {
            $className = trim((string) $className);

            // Find all StandardLinks for this school+year whose section name
            // starts with the class name (covers "P1" and "P1 A" stream sections)
            $links = StandardLink::where('school_id', $school->id)
                ->where('academic_year_id', $year->id)
                ->whereHas('section', function ($query) use ($school, $className) {
                    // Section name is either the exact class name (no streams)
                    // or "ClassName StreamLetter" (with streams)
                    $query->where('school_id', $school->id)
                        ->where(function ($q) use ($className) {
                            $q->where('name', $className)
                              ->orWhere('name', 'like', $className.' %');
                        });
                })
                ->get();

            if ($links->isEmpty()) {
                throw ValidationException::withMessages([
                    'subjects' => "Class '{$className}' does not exist. Add it on the classes step first.",
                ]);
            }

            foreach ($links as $link) {
                foreach ($subjectNames as $subjectName) {
                    $subjectName = trim((string) $subjectName);

                    Subject::firstOrCreate([
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'standard_id' => $link->standard_id,
                        'section_id' => $link->section_id,
                        'name' => $subjectName,
                    ], [
                        'code' => null,
                        'type' => 'core',
                        'status' => 1,
                    ]);
                }
            }
        }
    }

    /**
     * Persist academic terms for a school year.
     *
     * Additive: each call creates new terms or returns existing ones via
     * firstOrCreate on (school_id, name). Multiple calls accumulate — unlike
     * the wizard's early-return pattern, this allows adding Term 2, Term 3, etc.
     *
     * @param  School  $school
     * @param  AcademicYear  $year
     * @param  array  $terms  [ ['name' => 'Term 1', 'start' => '2025-02-01', 'end' => '2025-05-01'], ... ]
     *
     * @throws ValidationException
     */
    public function saveTerms(School $school, AcademicYear $year, array $terms): void
    {
        if (empty($terms)) {
            throw ValidationException::withMessages(['terms' => 'Add at least one term.']);
        }

        foreach ($terms as $index => $term) {
            $name = trim((string) ($term['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages(['terms' => 'Term name cannot be empty.']);
            }

            $start = $term['start'] ?? null;
            $end = $term['end'] ?? null;
            $hasStart = $start !== null && trim((string) $start) !== '';
            $hasEnd = $end !== null && trim((string) $end) !== '';

            if ($hasStart !== $hasEnd) {
                throw ValidationException::withMessages(['terms' => "Term '{$name}': provide both start and end dates, or neither."]);
            }

            if ($hasStart && $hasEnd) {
                $startParsed = Carbon::parse($start);
                $endParsed = Carbon::parse($end);
                if ($endParsed->lte($startParsed)) {
                    throw ValidationException::withMessages(['terms' => "Term '{$name}': end date must be after start date."]);
                }
            }
        }

        foreach ($terms as $term) {
            $name = trim((string) ($term['name'] ?? ''));
            $start = $term['start'] ?? null;
            $end = $term['end'] ?? null;

            $hasStart = $start !== null && trim((string) $start) !== '';
            $hasEnd = $end !== null && trim((string) $end) !== '';

            $createAttrs = [
                'academic_year_id' => $year->id,
                'status' => 'current',
            ];

            if ($hasStart) {
                $createAttrs['starts_on'] = Carbon::parse(trim((string) $start))->startOfDay();
            }
            if ($hasEnd) {
                $createAttrs['ends_on'] = Carbon::parse(trim((string) $end))->endOfDay();
            }

            AcademicTerm::firstOrCreate(
                ['school_id' => $school->id, 'name' => $name],
                $createAttrs,
            );
        }
    }

    /**
     * Persist fee categories for a school.
     *
     * Each fee entry creates a FeesCategories row. If 'class' is provided, it
     * resolves to a Standard (grading tier) and optionally a Section (class).
     * If 'term' is provided, it resolves to an AcademicTerm.
     *
     * Whole-school fees (no 'class'): when no class is specified, the fee is
     * genuinely school-wide — one row per Standard with section_id = NULL.
     * This matches StudentReportHelperService::fees() which reads school-wide
     * fees as whereNull('section_id'), and WhatsApp queries that filter by
     * standard_id. A fee scoped to one arbitrary Standard would be invisible
     * to students in other grading tiers.
     *
     * Idempotent via firstOrCreate on the unique constraint columns:
     * (school_id, standard_id, section_id, name).
     *
     * @param  School  $school
     * @param  array  $fees  [ ['name' => 'Tuition', 'amount' => 500000, 'class' => 'P1', 'term' => 'Term 1'], ... ]
     *
     * @throws ValidationException
     */
    public function saveFees(School $school, array $fees): void
    {
        if (empty($fees)) {
            throw ValidationException::withMessages(['fees' => 'Add at least one fee.']);
        }

        foreach ($fees as $index => $fee) {
            $name = trim((string) ($fee['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages(['fees' => 'Fee name cannot be empty.']);
            }

            $amount = $fee['amount'] ?? 0;
            if (! is_numeric($amount) || (float) $amount < 0) {
                throw ValidationException::withMessages(['fees' => "Fee '{$name}': amount must be zero or a positive number."]);
            }
        }

        foreach ($fees as $fee) {
            $name = trim((string) ($fee['name'] ?? ''));
            $amount = isset($fee['amount']) ? (float) $fee['amount'] : 0.00;

            $className = trim((string) ($fee['class'] ?? ''));
            $termName = trim((string) ($fee['term'] ?? ''));
            $level = strtolower(trim((string) ($fee['level'] ?? '')));

            // Resolve academic_term_id from term name if provided
            $academicTermId = null;
            if ($termName !== '') {
                $term = AcademicTerm::where('school_id', $school->id)
                    ->where('name', $termName)
                    ->first();
                if ($term) {
                    $academicTermId = $term->id;
                }
            }

            if ($className !== '') {
                // Class-specific fee: resolve to one Standard + Section
                $this->saveFeeForClass($school, $name, $amount, $className, $academicTermId);
            } elseif ($level !== '' && $level !== 'all') {
                // Level-scoped fee (nursery / primary / o-level / a-level / secondary)
                $this->saveFeeForLevels($school, $name, $amount, $level, $academicTermId);
            } else {
                // Whole-school fee: one row per Standard, section_id = NULL
                $this->saveFeeSchoolWide($school, $name, $amount, $academicTermId);
            }
        }
    }

    /**
     * Save a class-specific fee scoped to the Standard and Section for that class.
     */
    private function saveFeeForClass(School $school, string $name, float $amount, string $className, ?int $academicTermId): void
    {
        $standardId = null;
        $sectionId = null;

        $year = AcademicYear::where('school_id', $school->id)->orderByDesc('id')->first();
        $link = $year
            ? $this->resolveStandardLinkForClass($school, $year, $className)
            : null;

        if ($link) {
            $standardId = $link->standard_id;
            $sectionId = $link->section_id;
        } else {
            // Fallback: try to find a Standard by matching the class to a grading tier
            $standardName = $this->standardNameForClass($className);
            $standard = Standard::where('school_id', $school->id)
                ->where('name', $standardName)
                ->first();

            if ($standard) {
                $standardId = $standard->id;
            }
        }

        if ($standardId === null) {
            throw ValidationException::withMessages([
                'fees' => "Class '{$className}' does not exist. Add it on the classes step first.",
            ]);
        }

        // Class was named but no section matched — refuse silent tier-only placement.
        if ($sectionId === null && $year) {
            throw ValidationException::withMessages([
                'fees' => "Class '{$className}' does not match any class at this school. Use the exact name or a known short form (e.g. S.4 for Senior Four).",
            ]);
        }

        FeesCategories::firstOrCreate(
            [
                'school_id' => $school->id,
                'standard_id' => $standardId,
                'section_id' => $sectionId,
                'name' => $name,
            ],
            [
                'amount' => $amount,
                'academic_term_id' => $academicTermId,
            ],
        );
    }

    /**
     * Save a fee scoped to one or more grading-tier Standards (section_id = NULL).
     *
     * Level values match Standard.name / Toshi feeFormLevel: nursery, primary,
     * o-level, a-level. Legacy "secondary" expands to both O'Level and A'Level.
     */
    private function saveFeeForLevels(
        School $school,
        string $name,
        float $amount,
        string $level,
        ?int $academicTermId
    ): void {
        $tiers = match ($level) {
            'o-level', 'o_level', 'olevel' => ['o-level'],
            'a-level', 'a_level', 'alevel' => ['a-level'],
            'secondary' => ['o-level', 'a-level'],
            'nursery', 'primary' => [$level],
            default => [$level],
        };

        $found = false;
        foreach ($tiers as $tier) {
            $standard = Standard::where('school_id', $school->id)
                ->where('name', $tier)
                ->first();

            if (! $standard) {
                continue;
            }

            $found = true;
            FeesCategories::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'standard_id' => $standard->id,
                    'section_id' => null,
                    'name' => $name,
                ],
                [
                    'amount' => $amount,
                    'academic_term_id' => $academicTermId,
                ],
            );
        }

        if (! $found) {
            throw ValidationException::withMessages([
                'fees' => "Level '{$level}' does not exist for this school. Add matching classes first.",
            ]);
        }
    }

    /**
     * Save a whole-school fee: one row per Standard with section_id = NULL.
     *
     * This matches StudentReportHelperService::fees() which reads school-wide
     * fees as whereNull('section_id'), and WhatsApp queries that filter by
     * standard_id. A fee on just one Standard would be invisible to students
     * in other grading tiers.
     */
    /**
     * Save a whole-school fee as one row per Standard (section_id = NULL).
     *
     * WhatsApp and some fee queries filter by standard_id, so a single null-standard
     * row would be invisible to students in other tiers. The admin list therefore
     * shows one row per grading tier for "All Levels" fees — that is intentional,
     * not accidental duplication. Display uses tierDisplayLabel() / labeledName().
     */
    private function saveFeeSchoolWide(School $school, string $name, float $amount, ?int $academicTermId): void
    {
        $standards = Standard::where('school_id', $school->id)->get();

        if ($standards->isEmpty()) {
            throw ValidationException::withMessages([
                'fees' => 'Add a class first before adding fees.',
            ]);
        }

        foreach ($standards as $standard) {
            FeesCategories::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'standard_id' => $standard->id,
                    'section_id' => null,
                    'name' => $name,
                ],
                [
                    'amount' => $amount,
                    'academic_term_id' => $academicTermId,
                ],
            );
        }
    }

    // ── Phase 1C: Teacher / Student / WhatsApp / Plan ──────────────────

    /**
     * Create teacher users with random passwords and is_reset=1.
     *
     * Each entry in $teachers must contain at least 'name' and 'email'.
     * Optional keys: 'phone', 'standardLink_id', 'subject_id'.
     *
     * Returns ['created' => [...], 'skipped' => [...]].
     */
    public function saveTeachers(School $school, AcademicYear $year, array $teachers): array
    {
        $created = [];
        $skipped = [];

        $link = StandardLink::where('school_id', $school->id)->first();
        $subject = Subject::where('school_id', $school->id)->first();

        foreach ($teachers as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            $email = trim((string) ($draft['email'] ?? ''));
            $phone = trim((string) ($draft['phone'] ?? ''));

            if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped[] = ['name' => $name, 'email' => $email, 'reason' => 'Invalid name or email'];
                continue;
            }

            // Email dedup within this school: generate a fallback
            if (User::where('school_id', $school->id)->where('email', $email)->exists()) {
                $email = 'teacher.'.Str::lower(Str::random(6)).'@'.($school->slug ?: 'school').'.test';
            }

            $teacher = User::create([
                'school_id'       => $school->id,
                'usergroup_id'   => 5,
                'name'            => $name,
                'email'           => $email,
                'password'        => bcrypt(Str::random(16)),
                'status'          => 'active',
                'email_verified'  => 1,
                'is_reset'        => 1,
                'mobile_no'       => $phone ?: null,
            ]);

            Userprofile::firstOrCreate(
                ['user_id' => $teacher->id],
                [
                    'school_id'     => $school->id,
                    'usergroup_id'  => 5,
                    'firstname'     => $name,
                    'lastname'      => '',
                    'profession'    => 'teacher',
                    'status'        => 'active',
                    'alternate_no'  => $phone ?: null,
                ]
            );

            // Teacherlink if class + subject provided
            $standardLinkId = $draft['standardLink_id'] ?? null;
            $subjectId = $draft['subject_id'] ?? null;

            if ($standardLinkId && $subjectId) {
                Teacherlink::firstOrCreate([
                    'school_id'        => $school->id,
                    'academic_year_id'  => $year->id,
                    'standardLink_id'  => $standardLinkId,
                    'subject_id'       => $subjectId,
                    'teacher_id'       => $teacher->id,
                ]);
            }

            $created[] = ['name' => $name, 'email' => $email, 'user_id' => $teacher->id];
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Create student users with random passwords, is_reset=1, and KlassApp IDs.
     *
     * Each entry in $students must contain at least 'name'.
     * Optional keys: 'class' (section name to resolve StandardLink), 'email',
     * 'phone', 'school_student_id', 'board_registration_number'.
     *
     * board_registration_number is persisted only for UNEB candidate classes
     * (P.7 / S.4 / S.6) via isCandidateClass().
     *
     * Returns ['created' => [...], 'skipped' => [...]].
     */
    public function saveStudents(School $school, AcademicYear $year, array $students): array
    {
        $created = [];
        $skipped = [];

        $firstLink = StandardLink::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->first();

        // Pass 1: refuse unmatched class names before creating anyone.
        // Silent first-class fallback when a name was provided is never acceptable.
        $unmatchedClasses = [];
        foreach ($students as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            $className = trim((string) ($draft['class'] ?? ''));
            $stream = trim((string) ($draft['stream'] ?? ''));
            if ($name === '' || $className === '') {
                continue;
            }
            if (! $this->resolveStandardLinkForClass($school, $year, $className, $stream !== '' ? $stream : null)) {
                $label = $stream !== '' ? "{$className} / stream {$stream}" : "'{$className}'";
                $unmatchedClasses[] = "{$name} → {$label}";
            }
        }
        if ($unmatchedClasses !== []) {
            throw ValidationException::withMessages([
                'students' => 'Could not place student(s) into a class: '
                    .implode('; ', $unmatchedClasses)
                    .'. Use the exact class/section name (e.g. Senior Four, P1 A) or class + stream (e.g. class P1 and stream A). '
                    .'Known short forms (e.g. S.4) are accepted — students were not silently assigned to another class.',
            ]);
        }

        foreach ($students as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));

            if ($name === '') {
                $skipped[] = ['name' => '', 'reason' => 'Name is required'];
                continue;
            }

            $email = trim((string) ($draft['email'] ?? ''));
            if ($email === '') {
                // Generate a placeholder email for students (who may not have email)
                $email = 'student.'.Str::lower(Str::random(8)).'@'.($school->slug ?: 'school').'.local';
            }

            // Email dedup within this school
            if (User::where('school_id', $school->id)->where('email', $email)->exists()) {
                $email = 'student.'.Str::lower(Str::random(8)).'@'.($school->slug ?: 'school').'.local';
            }

            $phone = trim((string) ($draft['phone'] ?? ''));

            $student = User::create([
                'school_id'       => $school->id,
                'usergroup_id'   => 6,
                'name'            => $name,
                'email'           => $email,
                'password'        => bcrypt(Str::random(16)),
                'status'          => 'active',
                'email_verified'  => 1,
                'is_reset'        => 1,
                'mobile_no'       => $phone ?: null,
            ]);

            Userprofile::firstOrCreate(
                ['user_id' => $student->id],
                [
                    'school_id'     => $school->id,
                    'usergroup_id'  => 6,
                    'firstname'     => $name,
                    'lastname'      => '',
                    'profession'    => 'student',
                    'status'        => 'active',
                    'alternate_no'  => $phone ?: null,
                ]
            );

            // Generate KlassApp student ID
            $klassappId = StudentIdGeneratorService::nextForStudent($student);

            $className = trim((string) ($draft['class'] ?? ''));
            $stream = trim((string) ($draft['stream'] ?? ''));
            $link = null;

            if ($className !== '') {
                $link = $this->resolveStandardLinkForClass(
                    $school,
                    $year,
                    $className,
                    $stream !== '' ? $stream : null
                );
            } elseif ($firstLink) {
                // No class provided: keep legacy first-link assignment for paste-name paths.
                $link = StandardLink::with(['standard', 'section'])->find($firstLink->id) ?? $firstLink;
            }

            if ($link) {
                $schoolStudentId = trim((string) ($draft['school_student_id'] ?? ''));
                $boardReg = trim((string) ($draft['board_registration_number'] ?? ''));
                $stdName = trim((string) ($link->standard?->name ?? ''));
                $secName = trim((string) ($link->section?->name ?? $className));

                // Only persist UNEB board reg for candidate classes (P.7 / S.4 / S.6)
                if ($boardReg !== '' && ! (self::isCandidateClass($stdName) || self::isCandidateClass($secName))) {
                    $boardReg = '';
                }

                StudentAcademic::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'user_id' => $student->id,
                    'standardLink_id' => $link->id,
                    'klassapp_student_id' => $klassappId,
                    'school_student_id' => $schoolStudentId !== '' ? $schoolStudentId : null,
                    'board_registration_number' => $boardReg !== '' ? $boardReg : null,
                ]);
            }

            $created[] = [
                'name'         => $name,
                'email'        => $email,
                'user_id'      => $student->id,
                'klassapp_id'  => $klassappId,
                'class'        => $link?->section?->name,
                'standardLink_id' => $link?->id,
            ];
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Persist exams collected during Toshi onboarding.
     *
     * Each entry should include at least 'type' (exam type / label) and 'class'.
     * Optional: 'term', 'subject', 'teacher', 'status', 'scheduled_at'.
     *
     * Fails loudly when class/subject cannot be resolved — never invents defaults.
     *
     * @param  array<int, array<string, mixed>>  $exams
     * @return array{created: list<array{exam_id: int, type: string, class: string}>}
     *
     * @throws ValidationException
     */
    public function saveExams(School $school, AcademicYear $year, array $exams, ?int $fallbackTeacherId = null): array
    {
        if ($exams === []) {
            return ['created' => []];
        }

        $created = [];

        foreach ($exams as $index => $draft) {
            $typeName = trim((string) ($draft['type'] ?? $draft['name'] ?? ''));
            $className = trim((string) ($draft['class'] ?? ''));
            $termName = trim((string) ($draft['term'] ?? ''));
            $subjectName = trim((string) ($draft['subject'] ?? ''));
            $teacherName = trim((string) ($draft['teacher'] ?? ''));
            $status = trim((string) ($draft['status'] ?? 'undone')) ?: 'undone';

            if ($typeName === '') {
                throw ValidationException::withMessages([
                    'exams' => 'Each exam needs a type/name (e.g. Mid-Term, End of Term).',
                ]);
            }

            if ($className === '') {
                throw ValidationException::withMessages([
                    'exams' => "Exam '{$typeName}' is missing a class. Pick the class before confirming.",
                ]);
            }

            $link = $this->resolveStandardLinkForClass($school, $year, $className);
            if (! $link || ! $link->section_id || ! $link->standard_id) {
                throw ValidationException::withMessages([
                    'exams' => "Exam '{$typeName}': class '{$className}' does not match any class at this school.",
                ]);
            }

            $examType = ExamType::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($typeName).'%'])
                ->orWhereRaw('LOWER(code) = ?', [strtolower($typeName)])
                ->first();
            if (! $examType) {
                $examType = ExamType::query()->first();
            }
            if (! $examType) {
                throw ValidationException::withMessages([
                    'exams' => "Exam '{$typeName}': no exam types are configured on this platform.",
                ]);
            }

            $term = null;
            if ($termName !== '') {
                $term = AcademicTerm::where('school_id', $school->id)
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($termName).'%'])
                    ->first();
                if (! $term) {
                    throw ValidationException::withMessages([
                        'exams' => "Exam '{$typeName}': term '{$termName}' was not found. Add terms first.",
                    ]);
                }
            } else {
                $term = AcademicTerm::where('school_id', $school->id)->orderBy('id')->first();
            }

            $subject = null;
            if ($subjectName !== '') {
                $subject = Subject::where('school_id', $school->id)
                    ->where('section_id', $link->section_id)
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($subjectName).'%'])
                    ->first();
                if (! $subject) {
                    // School-wide / standard-scoped subject rows
                    $subject = Subject::where('school_id', $school->id)
                        ->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($subjectName).'%'])
                        ->first();
                }
                if (! $subject) {
                    throw ValidationException::withMessages([
                        'exams' => "Exam '{$typeName}': subject '{$subjectName}' was not found for class '{$className}'.",
                    ]);
                }
            } else {
                $subject = Subject::where('school_id', $school->id)
                    ->where('section_id', $link->section_id)
                    ->orderBy('id')
                    ->first()
                    ?? Subject::where('school_id', $school->id)->orderBy('id')->first();
                if (! $subject) {
                    throw ValidationException::withMessages([
                        'exams' => "Exam '{$typeName}': no subjects exist for class '{$className}'. Add subjects first.",
                    ]);
                }
            }

            $teacherId = $fallbackTeacherId;
            if ($teacherName !== '') {
                $teacher = User::where('school_id', $school->id)
                    ->where('usergroup_id', 5)
                    ->where(function ($q) use ($teacherName) {
                        $q->where('name', $teacherName)
                            ->orWhereRaw('LOWER(name) LIKE ?', [strtolower($teacherName).'%']);
                    })
                    ->first();
                if (! $teacher) {
                    throw ValidationException::withMessages([
                        'exams' => "Exam '{$typeName}': teacher '{$teacherName}' was not found.",
                    ]);
                }
                $teacherId = $teacher->id;
            }
            if (! $teacherId) {
                throw ValidationException::withMessages([
                    'exams' => "Exam '{$typeName}': assign a teacher (or confirm as a school admin so one can be used).",
                ]);
            }

            $exam = Exam::create([
                'school_id' => $school->id,
                'standard_id' => $link->standard_id,
                'academic_year_id' => $year->id,
                'academic_term_id' => $term?->id,
                'exam_type_id' => $examType->id,
                'section_id' => $link->section_id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacherId,
                'scheduled_at' => $draft['scheduled_at'] ?? now(),
                'status' => in_array($status, ['undone', 'ongoing', 'completed'], true) ? $status : 'undone',
            ]);

            $created[] = [
                'exam_id' => $exam->id,
                'type' => $typeName,
                'class' => $link->section?->name ?? $className,
            ];
        }

        return ['created' => $created];
    }

    // ── Phase 1C methods: saveWhatsApp, savePlan ─────────────────────────

    /**
     * Link a WhatsApp number to a user for this school.
     *
     * Uses updateOrCreate by user_id so re-submitting is idempotent.
     * Catches phone-unique constraint violations and returns them as skipped.
     *
     * Returns ['linked' => [...], 'skipped' => [...]].
     */
    public function saveWhatsApp(School $school, int $userId, string $phone): array
    {
        $phone = trim($phone);

        if ($phone === '') {
            return ['linked' => null, 'skipped' => [['reason' => 'Phone number is required']]];
        }

        // Check if this user already has a WhatsApp record
        if (WhatsAppUser::where('user_id', $userId)->exists()) {
            return ['linked' => null, 'skipped' => [['reason' => 'User already has a WhatsApp record']]];
        }

        try {
            $whatsapp = WhatsAppUser::updateOrCreate(
                ['user_id' => $userId],
                [
                    'phone'       => $phone,
                    'school_id'   => $school->id,
                    'opted_in'    => true,
                    'verified_at' => now(),
                ]
            );

            return ['linked' => ['user_id' => $userId, 'phone' => $phone], 'skipped' => null];
        } catch (UniqueConstraintViolationException $e) {
            return ['linked' => null, 'skipped' => [['reason' => 'This WhatsApp number is already registered']]];
        }
    }

    /**
     * Persist the selected plan for a school.
     *
     * For paid plans (amount > 0), starts a trial via TrialService.
     * For free plans, creates a CurrentPlan with status='running'.
     *
     * If a CurrentPlan already exists for this school, updates its plan_id and status.
     *
     * @throws ValidationException if incompleteSteps blocks plan selection
     *                            (unless skipCompletionCheck is true)
     */
    public function savePlan(School $school, int $planId, bool $skipCompletionCheck = false, ?int $userId = null): CurrentPlan
    {
        if (! $skipCompletionCheck) {
            foreach (OnboardingStepsService::incompleteSteps($school->fresh(), $userId) as $step) {
                if (in_array($step['key'], OnboardingStepsService::OPTIONAL_STEPS, true)) {
                    continue;
                }
                if ($step['key'] !== 'plan_selection') {
                    throw ValidationException::withMessages(['plan' => 'Finish the earlier setup steps before choosing a plan.']);
                }
            }
        }

        $plan = Plan::query()->where('id', $planId)->where('is_active', 1)->first();
        if (! $plan) {
            throw ValidationException::withMessages(['plan' => 'That plan is not available.']);
        }

        // Paid plan → start a trial
        if ($plan->amount > 0) {
            return TrialService::startTrial($school->id, $plan->id);
        }

        // Free plan → create/update CurrentPlan directly
        return CurrentPlan::updateOrCreate(
            ['school_id' => $school->id],
            [
                'plan_id' => $plan->id,
                'status'  => 'running',
            ]
        );
    }
}
