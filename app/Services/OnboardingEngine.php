<?php

namespace App\Services;

use App\Models\AcademicYear;
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
     * Persist standards (grading tiers), sections (classes), and standard_links
     * for the given academic year.
     *
     * Each entry in $classes creates a Section and a StandardLink. If the school
     * has a school_category set, SchoolCategorySeeder is run first as a baseline,
     * and user-supplied classes supplement (not replace) the seeded defaults.
     * When no category is set, a 'primary' standard is created as fallback.
     *
     * Streams: if 'streams' is non-empty, each stream gets its own Section
     * (named "ClassName StreamLetter") and StandardLink. If empty, one Section
     * per class name with one StandardLink.
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

            if (is_array($streams) && count($streams) > 0) {
                // Create a section per stream
                foreach ($streams as $stream) {
                    $streamName = trim((string) $stream);
                    if ($streamName === '') {
                        continue;
                    }
                    $sectionName = $className.' '.$streamName;

                    $section = Section::firstOrCreate(
                        ['school_id' => $school->id, 'name' => $sectionName],
                        ['status' => '1']
                    );

                    StandardLink::firstOrCreate(array_filter([
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'standard_id' => $standard->id,
                        'section_id' => $section->id,
                        'status' => '1',
                        'sub_group' => $subGroup,
                    ]));
                }
            } else {
                // No streams — single section per class
                $section = Section::firstOrCreate(
                    ['school_id' => $school->id, 'name' => $className],
                    ['status' => '1']
                );

                StandardLink::firstOrCreate(array_filter([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $standard->id,
                    'section_id' => $section->id,
                    'status' => '1',
                    'sub_group' => $subGroup,
                ]));
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

        // Find a StandardLink whose section name matches the class
        $link = StandardLink::where('school_id', $school->id)
            ->whereHas('section', function ($query) use ($school, $className) {
                $query->where('school_id', $school->id)
                    ->where(function ($q) use ($className) {
                        $q->where('name', $className)
                          ->orWhere('name', 'like', $className.' %');
                    });
            })
            ->first();

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
     * Save a whole-school fee: one row per Standard with section_id = NULL.
     *
     * This matches StudentReportHelperService::fees() which reads school-wide
     * fees as whereNull('section_id'), and WhatsApp queries that filter by
     * standard_id. A fee on just one Standard would be invisible to students
     * in other grading tiers.
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
     * Optional keys: 'class' (section name to resolve StandardLink), 'email', 'phone'.
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

            // Resolve class assignment
            $className = trim((string) ($draft['class'] ?? ''));
            $standardLinkId = null;

            if ($className !== '') {
                // Find StandardLink whose section name matches the class
                $link = StandardLink::where('school_id', $school->id)
                    ->where('academic_year_id', $year->id)
                    ->whereHas('section', function ($query) use ($school, $className) {
                        $query->where('school_id', $school->id)
                            ->where(function ($q) use ($className) {
                                $q->where('name', $className)
                                  ->orWhere('name', 'like', $className.' %');
                            });
                    })
                    ->first();

                if ($link) {
                    $standardLinkId = $link->id;
                }
            }

            // Fallback: first StandardLink for this school/year
            if (! $standardLinkId && $firstLink) {
                $standardLinkId = $firstLink->id;
            }

            if ($standardLinkId) {
                StudentAcademic::create([
                    'school_id'            => $school->id,
                    'academic_year_id'     => $year->id,
                    'user_id'              => $student->id,
                    'standardLink_id'      => $standardLinkId,
                    'klassapp_student_id'  => $klassappId,
                ]);
            }

            $created[] = [
                'name'         => $name,
                'email'        => $email,
                'user_id'      => $student->id,
                'klassapp_id'  => $klassappId,
            ];
        }

        return ['created' => $created, 'skipped' => $skipped];
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
