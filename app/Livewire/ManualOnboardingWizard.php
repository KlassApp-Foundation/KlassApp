<?php

namespace App\Livewire;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\StandardLink;

use App\Models\Subject;
use App\Models\User;

use App\Models\WhatsAppUser;
use App\Services\ClassStructureService;
use App\Services\ClassTeacherInviteService;
use App\Services\OnboardingNameListExtractor;
use App\Services\OnboardingEngine;
use App\Support\PhoneCountry;
use App\Services\ToshiActionService;
use App\Services\FinishSchoolOnboarding;
use App\Services\OnboardingStepsService;
use App\Onboarding\Steps\StepRegistry;
use App\Services\SchoolCategorySeeder;
use App\Services\WhatsApp\WhatsAppUserInitiatedVerification;

use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Wave 3 manual onboarding wizard shell.
 * Step sequence + completion rules come from OnboardingStepsService;
 * persistence writes the same models existing admin forms use.
 */
class ManualOnboardingWizard extends Component
{
    use WithFileUploads;

    public int $stepIndex = 0;

    /**
     * Steps unlocked by the last answer. The applicable step list grows as answers
     * arrive (country unlocks EMIS, a UNEB curriculum unlocks UNEB centre and school
     * category), so surface the growth instead of letting the counter jump.
     */
    public int $stepsGrewBy = 0;

    public bool $finished = false;

    /** Non-blocking plan over-limit notice shown after students are saved. */
    public ?string $overLimitNotice = null;

    /** When set, Next on an edited checklist step returns here (wizard review). */
    public ?int $returnToStepIndex = null;

    /** Backup key for return-after-edit (survives index shifts). */
    public ?string $returnToStepKey = null;

    /**
     * Summary rows for the synthetic review step (wizard-only; not in OnboardingStepsService).
     *
     * @var list<array{key: string, label: string, icon: string, value: string}>
     */
    public array $reviewSummary = [];

    /** @var array<int, array{key: string, label: string, icon: string, is_complete: bool, route: ?string}> */
    public array $steps = [];

    /** @var list<string> */
    public array $completedDuringSession = [];

    // Form fields (bound per step)
    public string $schoolName = '';

    public string $studentSize = '';

    public string $curriculum = 'uneb';

    public string $schoolCategory = '';

    public string $countryName = 'Uganda';

    public string $ministryCode = '';

    public string $unebCenterNumber = '';

    public string $academicYearDescription = 'Current Academic Year';

    public string $academicYearStart = '';

    public string $academicYearEnd = '';

    public string $className = 'P1';

    /**
     * Structure step: base classes with streams + CT (from ClassStructureService snapshot).
     *
     * @var list<array<string, mixed>>
     */
    public array $structureClasses = [];

    /**
     * @var array<string, string>
     */
    public array $structureStreamDrafts = [];

    /**
     * @var array<string, array{email: string, existing_teacher_id: string, name: string, phone: string}>
     */
    public array $structureCtDrafts = [];

    /**
     * Active teachers for CT invite select.
     *
     * @var list<array{id: int, name: string, email: string}>
     */
    public array $structureTeachers = [];

    /** True when any base class has at least one stream (Students help / defaults). */
    public bool $schoolHasStreams = false;

    public string $structureFlash = '';

    public string $subjectName = 'Mathematics';

    /**
     * Base section id chosen on the subjects step. Subjects attach to this
     * class record (and its streams), not to a free-text code.
     */
    public int $subjectSectionId = 0;

    /** Result of the last add/remove on the subjects step. */
    public string $subjectFlash = '';

    /**
     * Names already in DB for the subjects step (auto-seeded or previously saved).
     * On the subjects step this is the selected class only. The teachers step
     * reloads the school-wide unique list.
     *
     * @var list<string>
     */
    public array $existingSubjectNames = [];

    public string $teacherName = '';

    public string $teacherEmail = '';

    public string $teacherPhone = '';

    /** @var list<string> Selected class names (section) for the teacher being added. */
    public array $teacherSelectedClasses = [];

    /** @var list<string> Selected subject names for the teacher being added. */
    public array $teacherSelectedSubjects = [];

    /** @var list<array{name: string, email: string, phone: string, classes: list<string>, subjects: list<string>}> */
    public array $teacherDrafts = [];

    public string $teacherPaste = '';

    /** @var mixed */
    public $teacherUpload = null;

    /** @var mixed */
    public $classTeacherUpload = null;

    /** Result line after a teacher file is read, e.g. "2 teachers added, 1 skipped". */
    public string $teacherUploadReport = '';

    /** Result line after a class-teacher file is read, e.g. "2 class teachers added, 1 skipped". */
    public string $classTeacherUploadReport = '';

    /**
     * Rows from the last class-teacher file that were not saved.
     *
     * @var list<array{row: int, reason: string}>
     */
    public array $classTeacherUploadSkips = [];

    /**
     * Parsed class-teacher rows kept so "Create missing streams and retry" can
     * run the same file again without asking for another upload.
     *
     * @var list<array<string, mixed>>
     */
    public array $classTeacherUploadRows = [];

    /**
     * Unique class + stream pairs the last class-teacher file could not place.
     *
     * @var list<array{class: string, stream: string}>
     */
    public array $classTeacherMissingStreams = [];

    /**
     * Rows from the last teacher file that were not saved.
     *
     * @var list<array{row: int, reason: string}>
     */
    public array $teacherUploadSkips = [];

    /**
     * Class or subject cells that did not match this school. The teacher was still saved.
     *
     * @var list<array{row: int, reason: string}>
     */
    public array $teacherUploadNotes = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $teacherUploadRows = [];

    /**
     * @var list<array{class: string, stream: string}>
     */
    public array $teacherMissingStreams = [];

    public string $studentName = '';

    public string $studentClass = '';

    public string $studentStream = '';

    public string $studentParent = '';

    public string $studentParentPhone = '';

    public string $studentSchoolStudentId = '';

    public string $studentLin = '';

    public string $studentBoardRegNumber = '';

    public string $studentGender = '';

    public string $studentDateOfBirth = '';

    /** @var list<array{name: string, class: string, stream: string, parent: string, parent_phone: string, school_student_id: string, board_registration_number: string, gender: string, date_of_birth: string}> */
    public array $studentDrafts = [];

    public string $studentPaste = '';

    /** @var mixed */
    public $studentUpload = null;

    public string $studentUploadReport = '';

    /**
     * @var list<array{row: int, reason: string}>
     */
    public array $studentUploadSkips = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $studentUploadRows = [];

    /**
     * @var list<array{class: string, stream: string}>
     */
    public array $studentMissingStreams = [];

    public string $termName = '';

    public string $termStartsOn = '';

    public string $termEndsOn = '';

    /** @var list<array{name: string, start: string, end: string}> */
    public array $termDrafts = [];

    /** Name of the term marked as current (must match a draft). */
    public string $currentTermName = '';

    public string $feeName = 'Tuition';

    public string $feeAmount = '100000';

    /** whole_school | class */
    public string $feeScope = 'whole_school';

    public string $feeClass = '';

    public string $feeTerm = '';

    public bool $feeIsYearly = false;

    /** @var list<array{name: string, amount: string, scope: string, class: string, term: string, is_yearly: bool}> */
    public array $feeDrafts = [];

    /** @var list<string> */
    public array $availableTermNames = [];

    public string $whatsappPhone = '';

    /** OTP entered by the admin (wizard WhatsApp step). */
    public string $whatsappOtpInput = '';

    /**
     * Code the admin sends from their own WhatsApp. Shown on this step only.
     * The stored copy is a hash. Inbound verification sets verified_at.
     */
    public string $whatsappOtpDisplay = '';

    /** wa.me link that opens WhatsApp with the code filled in. */
    public string $whatsappVerifyUrl = '';

    public string $whatsappOtpStatus = '';

    /** True after a send that did not go out, so the step can offer Retry. */
    public bool $whatsappSendFailed = false;

    /** Set true only after a successful OTP match (or hydrate of an already-verified link). */
    #[Locked]
    public bool $whatsappVerified = false;

    public string $errorMessage = '';

    /** Selected plan id for the visible plan_selection step (Freemium defaulted in mount). */
    public ?int $selectedPlanId = null;

    public function mount(): void
    {
        $school = $this->school();
        $user = Auth::user();

        $this->refreshSteps();
        $this->hydrateFieldsFromSchool($school);
        $this->defaultSelectedPlan();

        $this->academicYearStart = now()->startOfYear()->toDateString();
        $this->academicYearEnd = now()->endOfYear()->toDateString();
        $this->teacherEmail = $this->freshTeacherEmail($school);
        $this->prefillDefaultTerms();

        $school = $school->fresh();

        // Persist finish across reloads (A4: "is ready" then remount landed on Teachers).
        if (OnboardingStepsService::isOnboardingFinished($school)) {
            $this->completedDuringSession = collect($this->steps)
                ->where('key', '!=', 'review')
                ->pluck('key')
                ->all();
            $this->setStepIndex($this->reviewStepIndex());
            $this->buildReviewSummary();
            $this->finished = true;
            unset($user);

            return;
        }

        // Land on the first incomplete step (including optional teachers/students).
        // Skipping optional steps on mount jumped users from Teachers → Terms on reload
        // and made Previous appear to "go forward" after a remount mid-flow.
        // Optional skips are persisted on the school so they survive remount.
        $next = OnboardingStepsService::nextIncompleteStep($school, Auth::id());
        if ($next === null) {
            // Checklist complete → land on the synthetic review screen (do not auto-finish).
            $this->completedDuringSession = collect($this->steps)
                ->where('key', '!=', 'review')
                ->where('is_complete', true)
                ->pluck('key')
                ->all();
            $this->setStepIndex($this->reviewStepIndex());
            $this->buildReviewSummary();
            $this->finished = false;
        } else {
            // Explicit ?step=<key> deep-link (setup banner, sidebar chip, quick
            // actions). Only an existing, still-incomplete step is honoured;
            // anything else falls back to the next incomplete step.
            $requestedKey = strtolower(trim((string) request()->query('step', '')));
            $jumpIndex = $requestedKey !== ''
                ? array_search($requestedKey, array_column($this->steps, 'key'), true)
                : false;

            if ($jumpIndex !== false && empty($this->steps[$jumpIndex]['is_complete'])) {
                $this->setStepIndex((int) $jumpIndex);
            } else {
                foreach ($this->steps as $i => $step) {
                    if ($step['key'] === $next['key']) {
                        $this->setStepIndex($i);
                        break;
                    }
                }
            }
        }

        unset($user);
    }

    public function selectPlan(int $planId): void
    {
        if (! Plan::query()->where('id', $planId)->where('is_active', 1)->exists()) {
            $this->errorMessage = 'That plan is not available.';

            return;
        }

        $this->selectedPlanId = $planId;
        $this->errorMessage = '';
    }

    /**
     * Button-driven student-size selection (card radiogroup; mirrors selectSchoolCategory).
     * Livewire 3 has no public set() — wire:click="set(...)" was a no-op and blocked Next.
     */
    public function selectStudentSize(string $size): void
    {
        if (! in_array($size, OnboardingStepsService::STUDENT_SIZE_OPTIONS, true)) {
            $this->errorMessage = 'Please choose an approximate school size.';

            return;
        }

        $this->studentSize = $size;
        $this->errorMessage = '';
        $this->resetErrorBag('studentSize');
    }

    /**
     * Button-driven school category selection (same shape as AgentToshi::selectSchoolCategory).
     * Livewire 3 has no public set() — wire:click="set(...)" was a no-op and blocked Next.
     */
    public function selectSchoolCategory(string $category): void
    {
        if (! array_key_exists($category, SchoolCategorySeeder::CATEGORIES)) {
            $this->errorMessage = 'Please choose a school category.';

            return;
        }

        $this->schoolCategory = $category;
        $this->errorMessage = '';
        $this->resetErrorBag('schoolCategory');
    }

    public function addTeacherDraft(): void
    {
        $name = trim($this->teacherName);
        if ($name === '') {
            $this->errorMessage = 'Enter a teacher name.';

            return;
        }

        // Soft guard: phone typed into the name field (common demo mix-up).
        if (preg_match('/^\+?\d[\d\s\-]{6,}$/', $name) === 1) {
            $this->errorMessage = 'That looks like a phone number. Put the teacher name in Teacher name and the number in Phone.';

            return;
        }

        $email = trim($this->teacherEmail);
        $emailMissing = $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL);

        // After "+ Add", Next often re-syncs the cleared name with a blank deferred email.
        // Treat that as "already listed" — do not create a second draft or block Next.
        if ($emailMissing) {
            foreach ($this->teacherDrafts as $draft) {
                if (strcasecmp((string) ($draft['name'] ?? ''), $name) === 0) {
                    $this->teacherName = '';
                    $this->teacherPhone = '';
                    $this->teacherEmail = $this->freshTeacherEmail();
                    $this->errorMessage = '';

                    return;
                }
            }
            $email = $this->freshTeacherEmail();
            $this->teacherEmail = $email;
        }

        $phone = $this->normaliseOptionalPhone($this->teacherPhone, false);
        if ($phone === null) {
            return;
        }

        $this->teacherDrafts[] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'classes' => array_values(array_filter(array_map('trim', $this->teacherSelectedClasses))),
            'subjects' => array_values(array_filter(array_map('trim', $this->teacherSelectedSubjects))),
        ];
        $this->teacherName = '';
        $this->teacherPhone = '';
        $this->teacherEmail = $this->freshTeacherEmail();
        $this->teacherSelectedClasses = [];
        $this->teacherSelectedSubjects = [];
        $this->errorMessage = '';
    }

    public function removeTeacherDraft(int $index): void
    {
        if (! isset($this->teacherDrafts[$index])) {
            return;
        }
        unset($this->teacherDrafts[$index]);
        $this->teacherDrafts = array_values($this->teacherDrafts);
    }

    public function applyTeacherPaste(): void
    {
        $names = app(OnboardingNameListExtractor::class)->parseNameList($this->teacherPaste);
        foreach ($names as $name) {
            $this->teacherDrafts[] = [
                'name' => $name,
                'email' => $this->freshTeacherEmail(),
                'phone' => '',
                'classes' => [],
                'subjects' => [],
            ];
        }
        $this->teacherPaste = '';
        $this->errorMessage = $names === [] ? 'Could not find any names in the paste list.' : '';
    }

    public function updatedTeacherUpload(): void
    {
        $this->ingestUpload('teacher');
    }

    public function updatedClassTeacherUpload(): void
    {
        $this->ingestClassTeacherUpload();
    }

    /**
     * Create the streams named in the confirmation list, then run that upload again.
     * Does nothing for a class that is not already a base class.
     */
    public function createMissingStreamsAndRetry(string $upload): void
    {
        if (! in_array($upload, ['class_teachers', 'students', 'teachers'], true)) {
            return;
        }

        $this->errorMessage = '';
        $school = $this->school();
        $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::where('school_id', $school->id)->first();
        if (! $year || (int) $year->school_id !== (int) $school->id) {
            $this->errorMessage = 'Create an academic year first.';

            return;
        }

        $pairs = match ($upload) {
            'class_teachers' => $this->classTeacherMissingStreams,
            'students' => $this->studentMissingStreams,
            'teachers' => $this->teacherMissingStreams,
            default => [],
        };
        $pendingTeacherRows = $upload === 'teachers' ? $this->teacherRowsPendingStreams() : [];

        if ($pairs !== []) {
            app(OnboardingEngine::class)->createMissingStreams($school, $year, $pairs);
        }

        if ($upload === 'class_teachers' && $this->classTeacherUploadRows !== []) {
            $this->importClassTeacherUpload($this->classTeacherUploadRows);
        } elseif ($upload === 'students') {
            $this->retryStudentUpload($school);
        } elseif ($upload === 'teachers' && $pairs !== [] && $pendingTeacherRows !== []) {
            $this->importTeacherUpload($pendingTeacherRows, false);
        }

        $this->refreshStructureSnapshot();
    }

    public function addStudentDraft(): void
    {
        $name = trim($this->studentName);
        if ($name === '') {
            $this->errorMessage = 'Enter a student name.';

            return;
        }

        $gender = strtolower(trim($this->studentGender));
        if (! in_array($gender, ['male', 'female'], true)) {
            $gender = '';
        }

        $parentPhone = $this->normaliseOptionalPhone($this->studentParentPhone, false);
        if ($parentPhone === null) {
            return;
        }

        $this->studentDrafts[] = [
            'name' => $name,
            'class' => trim($this->studentClass),
            'stream' => trim($this->studentStream),
            'parent' => trim($this->studentParent),
            'parent_phone' => $parentPhone,
            'school_student_id' => trim($this->studentSchoolStudentId),
            'lin' => trim($this->studentLin),
            'board_registration_number' => trim($this->studentBoardRegNumber),
            'gender' => $gender,
            'date_of_birth' => trim($this->studentDateOfBirth),
        ];
        $this->studentName = '';
        $this->studentClass = '';
        $this->studentStream = '';
        $this->studentParent = '';
        $this->studentParentPhone = '';
        $this->studentSchoolStudentId = '';
        $this->studentLin = '';
        $this->studentBoardRegNumber = '';
        $this->studentGender = '';
        $this->studentDateOfBirth = '';
        $this->errorMessage = '';
    }

    public function removeStudentDraft(int $index): void
    {
        if (! isset($this->studentDrafts[$index])) {
            return;
        }
        unset($this->studentDrafts[$index]);
        $this->studentDrafts = array_values($this->studentDrafts);
    }

    public function applyStudentPaste(): void
    {
        $names = app(OnboardingNameListExtractor::class)->parseNameList($this->studentPaste);
        foreach ($names as $name) {
            $this->studentDrafts[] = [
                'name' => $name,
                'class' => trim($this->studentClass),
                'stream' => trim($this->studentStream),
                'parent' => '',
                'parent_phone' => '',
                'school_student_id' => '',
                'lin' => '',
                'board_registration_number' => '',
                'gender' => '',
                'date_of_birth' => '',
            ];
        }
        $this->studentPaste = '';
        $this->errorMessage = $names === [] ? 'Could not find any names in the paste list.' : '';
    }

    public function updatedStudentUpload(): void
    {
        $this->ingestUpload('student');
    }

    /**
     * Skip an optional step (teachers / students) without creating records.
     */
    public function skipOptionalStep(): void
    {
        $key = $this->currentKey();
        if (! in_array($key, OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return;
        }

        // Discard in-memory drafts on skip. The Skip button uses wire:confirm when the
        // list is non-empty so this is never a silent wipe from the UI.
        if ($key === 'teachers') {
            $this->teacherDrafts = [];
            $this->teacherName = '';
            $this->teacherPhone = '';
            $this->teacherPaste = '';
        }
        if ($key === 'students') {
            $this->studentDrafts = [];
            $this->studentName = '';
            $this->studentPaste = '';
        }

        $this->errorMessage = '';
        OnboardingStepsService::markStepSkipped($this->school()->fresh(), $key);
        if (! in_array($key, $this->completedDuringSession, true)) {
            $this->completedDuringSession[] = $key;
        }

        if ($this->returnToStepIndex !== null || $this->returnToStepKey !== null) {
            $this->returnToReviewAfterEdit();

            return;
        }

        $this->refreshSteps();
        if (! OnboardingStepsService::hasBlockingIncompleteSteps($this->school()->fresh(), Auth::id())) {
            $this->setStepIndex($this->reviewStepIndex());
            $this->buildReviewSummary();

            return;
        }

        $this->advanceToNextIncompleteOrReview($key);
    }

    private function ingestUpload(string $kind): void
    {
        $file = $kind === 'teacher' ? $this->teacherUpload : $this->studentUpload;
        if (! $file) {
            return;
        }

        try {
            $this->validate([
                ($kind === 'teacher' ? 'teacherUpload' : 'studentUpload') => 'file|max:10240',
            ]);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Upload failed.';

            return;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, OnboardingNameListExtractor::ACCEPTED_MIMES, true)) {
            $this->errorMessage = 'Supported files: CSV, XLSX, TXT, DOCX, PDF.';

            return;
        }

        $rows = app(OnboardingNameListExtractor::class)->extractNamesFromFile($file->getRealPath(), $ext);
        if ($rows === []) {
            $this->errorMessage = 'No names found in that file.';

            return;
        }

        $unreadable = [];

        if ($kind === 'teacher') {
            $this->importTeacherUpload($rows);
            $this->teacherUpload = null;
            $this->errorMessage = '';

            return;
        } else {
            $this->studentUploadRows = $rows;
            foreach ($rows as $row) {
                if (trim((string) ($row['name'] ?? '')) === '') {
                    continue;
                }
                $rawPhone = trim((string) ($row['parent_phone'] ?? ''));
                $parentPhone = $this->normaliseOptionalPhone($rawPhone, false, false);
                if ($parentPhone === null) {
                    $unreadable[] = (string) ($row['name'] ?? '');
                    $parentPhone = '';
                }
                $this->studentDrafts[] = [
                    'name' => $row['name'],
                    'class' => (string) ($row['class'] ?? ''),
                    'stream' => (string) ($row['stream'] ?? ''),
                    'parent' => (string) ($row['parent'] ?? ''),
                    'parent_phone' => $parentPhone,
                    'school_student_id' => (string) ($row['school_student_id'] ?? ''),
                    'lin' => (string) ($row['lin'] ?? ''),
                    'board_registration_number' => (string) ($row['board_registration_number'] ?? ''),
                    'gender' => (string) ($row['gender'] ?? ''),
                    'date_of_birth' => (string) ($row['date_of_birth'] ?? ''),
                    'row' => (int) ($row['row'] ?? 0),
                ];
            }
            $this->studentUpload = null;
            $this->refreshStudentMissingStreams();
        }

        $phoneMessage = $this->unreadablePhonesMessage($unreadable);
        $this->errorMessage = $phoneMessage;
    }

    /**
     * Built only from the names this upload could not parse. An earlier
     * errorMessage is replaced, not inspected.
     *
     * @param  list<string>  $names
     */
    private function unreadablePhonesMessage(array $names): string
    {
        $labels = [];
        foreach ($names as $name) {
            $name = trim($name);
            $labels[] = $name !== '' ? $name : 'a row with no name';
        }
        if ($labels === []) {
            return '';
        }

        $list = count($labels) === 1
            ? $labels[0]
            : implode(', ', array_slice($labels, 0, -1)).' and '.$labels[array_key_last($labels)];
        $kept = count($labels) === 1 ? 'The row was kept' : 'Those rows were kept';
        $example = PhoneCountry::example($this->phoneCountryName());
        $how = $example !== null
            ? "Enter the number with the country code, e.g. {$example}."
            : 'Enter the number with the country code.';

        return "Could not read the phone for {$list}. {$kept} without that phone. {$how}";
    }

    private function advanceToNextIncompleteOrReview(string $fromKey): void
    {
        $keys = array_column($this->steps, 'key');
        $currentIndex = array_search($fromKey, $keys, true);
        $start = $currentIndex === false ? 0 : ((int) $currentIndex + 1);

        for ($i = $start; $i < count($this->steps); $i++) {
            if (($this->steps[$i]['key'] ?? '') === 'review') {
                continue;
            }
            if (! $this->steps[$i]['is_complete'] && ! in_array($this->steps[$i]['key'], OnboardingStepsService::OPTIONAL_STEPS, true)) {
                $this->setStepIndex($i);

                return;
            }
        }

        for ($i = $start; $i < count($this->steps); $i++) {
            if (($this->steps[$i]['key'] ?? '') === 'review') {
                continue;
            }
            if (! $this->steps[$i]['is_complete']) {
                $this->setStepIndex($i);

                return;
            }
        }

        $this->setStepIndex($this->reviewStepIndex());
        $this->buildReviewSummary();
    }

    public function getCurrentStepProperty(): ?array
    {
        return $this->steps[$this->stepIndex] ?? null;
    }

    public function getStepCountProperty(): int
    {
        return count($this->steps);
    }

    /**
     * Active plans for the visible plan_selection step.
     *
     * @return \Illuminate\Support\Collection<int, Plan>
     */
    public function getPlansProperty()
    {
        return Plan::query()->where('is_active', 1)->orderBy('order')->get();
    }

    /**
     * @return list<array{title: string, body: string, href: string}>
     */
    public function getSuggestionsProperty(): array
    {
        $school = $this->school()->fresh();
        $done = $this->completedDuringSession !== []
            ? array_values(array_filter($this->completedDuringSession, fn ($k) => $k !== 'review'))
            : collect($this->steps)->where('key', '!=', 'review')->where('is_complete', true)->pluck('key')->all();

        $suggestions = [];

        if (in_array('teachers', $done, true)) {
            $suggestions[] = [
                'title' => 'Invite more teachers',
                'body' => 'Add the rest of your teaching staff so class assignments stay accurate.',
                'href' => url('/admin/teacher/add'),
            ];
        }

        if (in_array('students', $done, true) || OnboardingStepsService::countActiveStudents($school->id) > 0) {
            $suggestions[] = [
                'title' => 'Add more students',
                'body' => 'Keep enrollment current — bulk upload more learners anytime.',
                'href' => url('/admin/student/add'),
            ];
        }

        if (in_array('standards', $done, true)) {
            $suggestions[] = [
                'title' => 'Manage classes & streams',
                'body' => 'Add streams or invite class teachers anytime from Classes.',
                'href' => url('/admin/classes'),
            ];
        }

        if (in_array('fees', $done, true)) {
            $suggestions[] = [
                'title' => 'Review fee structures',
                'body' => 'Tune amounts and categories before collecting payments.',
                'href' => url('/admin/fees-categories'),
            ];
        }

        if (in_array('whatsapp_verify', $done, true)) {
            $suggestions[] = [
                'title' => 'Message parents on WhatsApp',
                'body' => 'Your number is linked — send a welcome notice to a parent group.',
                'href' => url('/admin/whatsapp/dashboard'),
            ];
        }

        if (in_array('plan_selection', $done, true) || OnboardingStepsService::isStepComplete('plan_selection', $school, Auth::id())) {
            $suggestions[] = [
                'title' => 'Review your plan',
                'body' => 'Confirm capacity limits match how many students you expect this term.',
                'href' => url('/admin/subscriptions'),
            ];
        }

        if ($suggestions === []) {
            $suggestions[] = [
                'title' => 'Open your dashboard',
                'body' => $school->name.' is ready — check enrollment pulse and today’s tasks.',
                'href' => url('/admin/dashboard'),
            ];
        }

        return array_slice($suggestions, 0, 4);
    }

    public function previous(): void
    {
        $this->errorMessage = '';
        if ($this->finished) {
            $this->finished = false;
            $this->refreshSteps();
            $this->setStepIndex($this->reviewStepIndex());
            $this->buildReviewSummary();

            return;
        }

        if ($this->returnToStepIndex !== null || $this->returnToStepKey !== null) {
            $this->returnToReviewAfterEdit();

            return;
        }

        if ($this->stepIndex <= 0) {
            return;
        }

        // Navigate by key so a mid-request steps refresh cannot turn "back" into "forward".
        $keys = array_column($this->steps, 'key');
        $currentKey = $keys[$this->stepIndex] ?? null;
        $currentPos = $currentKey !== null ? array_search($currentKey, $keys, true) : false;
        $target = $currentPos === false ? $this->stepIndex - 1 : ((int) $currentPos - 1);
        if ($target < 0) {
            return;
        }

        $this->setStepIndex($target);
        if ($this->currentKey() === 'review') {
            $this->buildReviewSummary();
        }
    }

    public function next(): void
    {
        $this->errorMessage = '';
        // Read steps[] directly — do not touch $this->currentStep here.
        // Livewire memoizes legacy get*Property() computeds for the request; accessing
        // currentStep before changing stepIndex leaves the render stuck on the old step.
        $step = $this->steps[$this->stepIndex] ?? null;
        if (! $step) {
            return;
        }

        if (($step['key'] ?? '') === 'review') {
            $this->confirmReview();

            return;
        }

        // Teachers with an empty list can still auto-skip (explicit Skip exists too).
        // Teachers already saved from a file are not a reason to mark the step skipped.
        if (($step['key'] ?? '') === 'teachers' && $this->teacherDrafts === [] && trim($this->teacherName) === '' && $this->savedTeacherRows() === []) {
            $this->skipOptionalStep();

            return;
        }
        // Students: never silently skip on Continue — require add or explicit Skip for now.
        if (($step['key'] ?? '') === 'students' && $this->studentDrafts === [] && trim($this->studentName) === '') {
            $this->errorMessage = 'Add at least one student, or click “Skip for now” if you’ll enrol later.';

            return;
        }

        // No active plans: the step's @empty alert already explains this. Do not also
        // push the same string into the shell error banner (duplicate on Continue).
        if (($step['key'] ?? '') === 'plan_selection'
            && Plan::query()->where('is_active', 1)->doesntExist()) {
            return;
        }

        // WhatsApp verification is not required to finish setup. Continue
        // leaves the step unverified. Parent messaging still waits on verified_at.
        $leavingWhatsAppUnverified = ($step['key'] ?? '') === 'whatsapp_verify'
            && ! $this->whatsappAlreadyVerified();

        if (! $leavingWhatsAppUnverified) {
            try {
                $this->persistCurrentStep($step['key']);
            } catch (ValidationException $e) {
                $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Please check the form.';

                return;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                report($e);
                $this->errorMessage = app(\App\Services\OnboardingEngine::class)
                    ->describeUniqueConstraintViolation($e);

                return;
            } catch (\Throwable $e) {
                report($e);
                // Never surface raw SQL / connection strings to the wizard UI.
                $this->errorMessage = 'Could not save this step. Please try again.';

                return;
            }
        }

        if (! $leavingWhatsAppUnverified && ! in_array($step['key'], $this->completedDuringSession, true)) {
            $this->completedDuringSession[] = $step['key'];
        }

        // Do NOT auto-assign Freemium on every Next — plan_selection must remain a
        // visible decision step. Assignment happens only in savePlan() when confirmed.
        $this->refreshSteps();

        if ($this->returnToStepIndex !== null || $this->returnToStepKey !== null) {
            $this->returnToReviewAfterEdit();

            return;
        }

        // Structure checkpoint: after Academic Year, always land on standards once
        // even when StandardLinks were auto-seeded (step is_complete but actions are optional).
        if (($step['key'] ?? '') === 'academic_year') {
            foreach ($this->steps as $i => $candidate) {
                if (($candidate['key'] ?? '') === 'standards') {
                    $this->setStepIndex($i);

                    return;
                }
            }
        }

        // Subjects checkpoint: after Structure, always land on subjects once even when
        // category auto-seed already created them — admin can review / add more.
        if (($step['key'] ?? '') === 'standards') {
            foreach ($this->steps as $i => $candidate) {
                if (($candidate['key'] ?? '') === 'subjects') {
                    $this->setStepIndex($i);

                    return;
                }
            }
        }

        if (! OnboardingStepsService::hasBlockingIncompleteSteps($this->school()->fresh(), Auth::id())) {
            $this->setStepIndex($this->reviewStepIndex());
            $this->buildReviewSummary();

            return;
        }

        $keys = array_column($this->steps, 'key');
        $currentIndex = array_search($step['key'], $keys, true);
        $start = $currentIndex === false ? 0 : ((int) $currentIndex + 1);

        for ($i = $start; $i < count($this->steps); $i++) {
            if (($this->steps[$i]['key'] ?? '') === 'review') {
                continue;
            }
            if ($leavingWhatsAppUnverified && ($this->steps[$i]['key'] ?? '') === 'whatsapp_verify') {
                continue;
            }
            if (! $this->steps[$i]['is_complete']) {
                $this->setStepIndex($i);

                return;
            }
        }

        foreach ($this->steps as $i => $candidate) {
            if (($candidate['key'] ?? '') === 'review') {
                continue;
            }
            if ($leavingWhatsAppUnverified && ($candidate['key'] ?? '') === 'whatsapp_verify') {
                continue;
            }
            if (! $candidate['is_complete']) {
                $this->setStepIndex($i);

                return;
            }
        }

        $this->setStepIndex($this->reviewStepIndex());
        $this->buildReviewSummary();
    }

    public function goToStep(int $index): void
    {
        if ($index < 0 || $index >= count($this->steps)) {
            return;
        }

        $this->finished = false;
        $this->errorMessage = '';
        $this->setStepIndex($index);
        if (($this->steps[$index]['key'] ?? '') === 'review') {
            $this->returnToStepIndex = null;
            $this->returnToStepKey = null;
            $this->buildReviewSummary();
        }
    }

    /**
     * Jump to a checklist step from the review screen; Next returns to review.
     */
    public function editSection(string $key): void
    {
        $this->refreshSteps();
        $reviewIndex = $this->reviewStepIndex();

        foreach ($this->steps as $index => $step) {
            if (($step['key'] ?? '') === $key && $key !== 'review') {
                $this->returnToStepIndex = $reviewIndex;
                $this->returnToStepKey = 'review';
                $this->finished = false;
                $this->errorMessage = '';
                $this->hydrateFieldsForEdit($key);
                $this->setStepIndex($index);

                return;
            }
        }
    }

    public function confirmReview(): void
    {
        $this->errorMessage = '';
        $this->refreshSteps();

        $school = $this->school()->fresh();
        $blocking = OnboardingStepsService::blockingIncompleteSteps($school, Auth::id());
        if ($blocking !== []) {
            $labels = collect($blocking)->pluck('label')->filter()->implode(', ');
            $this->errorMessage = $labels !== ''
                ? "Finish these incomplete steps before creating your school: {$labels}."
                : 'Finish the remaining setup steps before creating your school.';

            return;
        }

        $finishedNow = app(FinishSchoolOnboarding::class)->finish($school);
        $this->finished = true;
        $this->returnToStepIndex = null;
        $this->returnToStepKey = null;
        if ($finishedNow) {
            $this->notifyToshiOnboardingFinished();
        }
    }

    private function returnToReviewAfterEdit(): void
    {
        $this->refreshSteps();
        $target = $this->reviewStepIndex();
        if ($this->returnToStepIndex !== null && $this->returnToStepIndex >= 0 && $this->returnToStepIndex < count($this->steps)) {
            $target = $this->returnToStepIndex;
        }
        if ($this->returnToStepKey) {
            foreach ($this->steps as $i => $step) {
                if (($step['key'] ?? '') === $this->returnToStepKey) {
                    $target = $i;
                    break;
                }
            }
        }
        $this->returnToStepIndex = null;
        $this->returnToStepKey = null;
        $this->setStepIndex($target);
        $this->buildReviewSummary();
    }

    /**
     * Assign stepIndex and bust Livewire's request-memoized currentStep computed.
     */
    private function setStepIndex(int $index): void
    {
        $this->stepIndex = $index;
        unset($this->currentStep);

        $key = $this->currentKey();
        if (in_array($key, ['standards', 'students'], true)) {
            $this->refreshStructureSnapshot();
            if ($key === 'students') {
                $this->applyStudentStreamDefaultForClass();
            }
        }
        if ($key === 'subjects') {
            $this->subjectFlash = '';
            $this->refreshStructureSnapshot();
            $this->ensureSubjectClassSelected();
            $this->refreshExistingSubjects();
        }
        if ($key === 'teachers') {
            $this->refreshStructureSnapshot();
            $this->refreshExistingSubjects();
        }
        if ($key === 'terms') {
            $this->ensureTermDraftsPrefill();
        }
        if ($key === 'fees') {
            $this->refreshStructureSnapshot();
            $this->refreshAvailableTermNames();
        }
    }

    private function refreshExistingSubjects(): void
    {
        if ($this->currentKey() === 'subjects') {
            $this->refreshSubjectsForSelectedClass();

            return;
        }

        $this->existingSubjectNames = $this->subjectNamesForSchool((int) $this->school()->id);

        // Checkpoint land via setStepIndex does not run prepareStepFields — clear the
        // default "Mathematics" so Next is a true no-op when subjects already exist.
        if ($this->existingSubjectNames !== []) {
            $this->subjectName = '';
        }
    }

    /**
     * Pick a real base section from the classes step.
     *
     * A stored name wins when it is one of those records. Otherwise the classes
     * field (often the untouched default "P1") is resolved through the same
     * aliases the rest of onboarding uses, so "P1" lands on "Primary One" when
     * that is the row the classes step created. Selection by id always wins
     * once the admin has chosen a class.
     */
    private function ensureSubjectClassSelected(): void
    {
        $ids = array_map(
            fn (array $row): int => (int) ($row['section_id'] ?? 0),
            $this->structureClasses
        );
        $ids = array_values(array_filter($ids, fn (int $id): bool => $id > 0));

        if ($this->subjectSectionId > 0 && in_array($this->subjectSectionId, $ids, true)) {
            return;
        }

        $school = $this->school();
        $year = AcademicYear::where('school_id', $school->id)->first();
        $hint = trim($this->className);
        if ($year && $hint !== '') {
            $link = app(OnboardingEngine::class)->resolveStandardLinkForClass($school, $year, $hint);
            $section = $link?->section;
            if ($section && (int) $section->school_id === (int) $school->id) {
                $baseId = (int) app(ClassStructureService::class)->resolveBaseSection($section)->id;
                if (in_array($baseId, $ids, true)) {
                    $this->subjectSectionId = $baseId;

                    return;
                }
            }
        }

        $this->subjectSectionId = $ids[0] ?? 0;
    }

    public function updatedSubjectSectionId(mixed $value): void
    {
        $this->subjectSectionId = (int) $value;
        $this->subjectFlash = '';
        $this->errorMessage = '';
        if ($this->structureClasses === []) {
            $this->refreshStructureSnapshot();
        }
        $this->ensureSubjectClassSelected();
        $this->refreshSubjectsForSelectedClass();
    }

    /**
     * Add every subject in the field now, so the list updates before Next.
     * Commas and new lines both separate names.
     */
    public function addSubjects(): void
    {
        $this->errorMessage = '';
        $this->subjectFlash = '';

        try {
            $message = $this->persistEnteredSubjects($this->school(), (string) $this->subjectName);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Could not add subjects.';

            return;
        }

        $this->subjectName = '';
        $this->subjectFlash = $message;
        $this->refreshSubjectsForSelectedClass();
    }

    public function removeSubject(int $index): void
    {
        $this->errorMessage = '';
        $this->subjectFlash = '';

        $name = trim((string) ($this->existingSubjectNames[$index] ?? ''));
        if ($name === '') {
            return;
        }

        try {
            $school = $this->school();
            $baseId = $this->selectedSubjectSectionId($school);
            $sectionIds = $this->schoolSectionIds($school, $this->sectionIdsForBase($baseId));
            Subject::query()
                ->where('school_id', $school->id)
                ->whereIn('section_id', $sectionIds)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->get()
                ->each(fn (Subject $subject) => $subject->delete());
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Could not remove that subject.';

            return;
        }

        $this->subjectFlash = 'Removed '.$name.' from '.$this->subjectClassName($baseId).'.';
        $this->refreshSubjectsForSelectedClass();
    }

    public function render()
    {
        return view('livewire.manual-onboarding-wizard', [
            'countries' => Country::query()->orderBy('order')->orderBy('name')->get(['id', 'name']),
            'school' => $this->school(),
        ]);
    }

    /**
     * Tell AgentToshi to leave "Completing Setup" and re-read OnboardingStepsService.
     */
    private function notifyToshiOnboardingFinished(): void
    {
        $this->dispatch('manual-onboarding-finished');
    }

    private function defaultSelectedPlan(): void
    {
        if ($this->selectedPlanId) {
            return;
        }

        $freemium = Plan::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['freemium'])
                    ->orWhereRaw('LOWER(display_name) = ?', ['freemium']);
            })
            ->orderBy('order')
            ->first();

        if ($freemium) {
            $this->selectedPlanId = (int) $freemium->id;

            return;
        }

        $first = Plan::query()->where('is_active', 1)->orderBy('order')->first();
        $this->selectedPlanId = $first ? (int) $first->id : null;
    }

    public function phoneCountryName(): string
    {
        return trim((string) ($this->school()->registration_country ?? ''));
    }

    public function phoneExample(): string
    {
        return PhoneCountry::example($this->phoneCountryName()) ?? '';
    }

    public function phoneFormatHint(bool $whatsapp = true, ?string $label = null): string
    {
        return PhoneCountry::hint($this->phoneCountryName(), $whatsapp, $label);
    }

    /**
     * Empty stays empty. A number the school's country cannot accept returns null.
     * When $report is true, errorMessage is set. When the school has no country
     * yet, the typed value is kept so a later step can still store it.
     */
    private function normaliseOptionalPhone(string $phone, bool $whatsapp, bool $report = true): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        $country = $this->phoneCountryName();
        if ($country === '') {
            return $phone;
        }

        $normalised = PhoneCountry::normalise($phone, $country);
        if ($normalised === null) {
            if ($report) {
                $this->errorMessage = PhoneCountry::error($country, $whatsapp);
            }

            return null;
        }

        return $normalised;
    }

    private function school(): School
    {
        return School::query()->findOrFail(Auth::user()->school_id);
    }

    private function refreshSteps(): void
    {
        $school = $this->school()->fresh();
        $serviceRows = collect(OnboardingStepsService::steps($school, Auth::id()))
            ->keyBy('key');

        // Order and applicability come from the step registry (the same list
        // the Toshi v2 driver walks, so both paths render the same steps in
        // the same order); the service remains the source of display
        // metadata — label/icon/is_complete/route — plus the hard guard that
        // registry and service cannot silently drift apart.
        $registryKeys = collect(app(StepRegistry::class)->forSchool($school))
            ->map(fn ($step) => $step->key());
        $missing = $registryKeys->diff($serviceRows->keys());
        if ($missing->isNotEmpty()) {
            throw new \RuntimeException(
                'Step registry keys missing from OnboardingStepsService::steps(): '
                .$missing->implode(', ')
            );
        }

        $checklist = $registryKeys
            ->map(fn (string $key) => $serviceRows[$key])
            ->values()
            ->all();

        // Wizard-only synthetic review step — not part of OnboardingStepsService /
        // Toshi "Completing Setup" checklist (would never complete otherwise).
        $checklist[] = [
            'key' => 'review',
            'label' => 'Review & confirm',
            'icon' => '📋',
            'is_complete' => false,
            'route' => null,
        ];

        $previousStepCount = count($this->steps);
        $this->stepsGrewBy = $previousStepCount > 0 && count($checklist) > $previousStepCount
            ? count($checklist) - $previousStepCount
            : 0;

        $this->steps = $checklist;
    }

    private function currentKey(): ?string
    {
        return $this->steps[$this->stepIndex]['key'] ?? null;
    }

    private function reviewStepIndex(): int
    {
        foreach ($this->steps as $i => $step) {
            if (($step['key'] ?? '') === 'review') {
                return $i;
            }
        }

        return max(count($this->steps) - 1, 0);
    }

    private function buildReviewSummary(): void
    {
        $school = $this->school()->fresh();
        $sid = $school->id;

        $year = AcademicYear::where('school_id', $sid)->first();
        $links = StandardLink::with('section')->where('school_id', $sid)->get();
        $subjects = Subject::where('school_id', $sid)
            ->pluck('name')
            ->filter()
            ->unique()
            ->values();
        $subjectRowCount = Subject::where('school_id', $sid)->count();
        $teachers = User::query()
            ->with('userprofile')
            ->where('school_id', $sid)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(fn (User $user) => $user->displayName ?: $user->name)
            ->filter()
            ->values();
        $students = User::query()
            ->with('userprofile')
            ->where('school_id', $sid)
            ->where('usergroup_id', 6)
            ->ByActive()
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(fn (User $u) => $u->displayName ?: $u->name)
            ->filter()
            ->values();
        $studentCount = OnboardingStepsService::countActiveStudents($sid);
        $terms = AcademicTerm::where('school_id', $sid)->get();
        $fees = FeesCategories::with('standard')->where('school_id', $sid)->get();
        $whatsapp = WhatsAppUser::where('user_id', Auth::id())->first();
        $currentPlan = CurrentPlan::with('plan')->where('school_id', $sid)->first();
        $plan = $currentPlan?->plan;

        $classNames = $links->map(fn ($l) => $l->section?->name)->filter()->unique()->values();
        $emis = trim((string) ($school->ministry_code ?: ''));
        $unebRaw = $school->uneb_center_number;
        $unebDisplay = '—';
        if ($unebRaw !== null) {
            $unebDisplay = trim((string) $unebRaw) === '' ? 'Skipped' : trim((string) $unebRaw);
        }

        $yearValue = '—';
        if ($year) {
            $start = $year->start_date ? substr((string) $year->start_date, 0, 10) : '';
            $end = $year->end_date ? substr((string) $year->end_date, 0, 10) : '';
            $yearValue = trim(($year->description ?: $year->name).($start || $end ? " · {$start} → {$end}" : ''));
        }

        $feeValue = $fees->isEmpty()
            ? '—'
            : $fees->map(fn ($f) => $f->labeledName().' · '.number_format((float) $f->amount).' UGX')->implode(', ');

        $planValue = $plan
            ? ($plan->display_name ?: ucfirst((string) $plan->name))
            : '—';

        $this->reviewSummary = [
            ['key' => 'school_name', 'label' => 'School name', 'icon' => '🏫', 'value' => (string) ($school->name ?: '—')],
            ['key' => 'student_size', 'label' => 'School size', 'icon' => '👥', 'value' => (string) ($school->student_size ?: '—')],
            ['key' => 'curriculum', 'label' => 'Curriculum', 'icon' => '📚', 'value' => strtoupper((string) ($school->curriculum ?: '—'))],
            [
                'key' => 'school_category',
                'label' => 'School category',
                'icon' => '🏭',
                'value' => SchoolCategorySeeder::CATEGORIES[$school->school_category] ?? '—',
            ],
            ['key' => 'country', 'label' => 'Country', 'icon' => '🌍', 'value' => (string) ($school->registration_country ?: '—')],
            ['key' => 'emis', 'label' => 'EMIS / Ministry code', 'icon' => '🔢', 'value' => $emis !== '' ? $emis : '—'],
            ['key' => 'uneb_center', 'label' => 'UNEB centre', 'icon' => '🎓', 'value' => $unebDisplay],
            ['key' => 'academic_year', 'label' => 'Academic year', 'icon' => '📅', 'value' => $yearValue],
            ['key' => 'standards', 'label' => 'Structure & Class Teachers', 'icon' => '🏷️', 'value' => $classNames->isEmpty() ? '—' : $classNames->implode(', ')],
            [
                'key' => 'subjects',
                'label' => 'Subjects',
                'icon' => '📖',
                // Subjects are stored per class/section (intentional). Review shows unique
                // names — repeating "English" once per P.1–P.7 is noise, not a roster.
                'value' => $subjects->isEmpty()
                    ? '—'
                    : ($subjects->implode(', ')
                        .($subjectRowCount > $subjects->count()
                            ? ' · '.$subjects->count().' unique across '.$subjectRowCount.' class rows'
                            : '')),
            ],
            ['key' => 'teachers', 'label' => 'Teachers', 'icon' => '👩‍🏫', 'value' => $teachers->isEmpty() ? '—' : $teachers->implode(', ')],
            [
                'key' => 'students',
                'label' => 'Students',
                'icon' => '🎒',
                'value' => $studentCount === 0
                    ? '—'
                    : ($studentCount.' · '.$students->take(5)->implode(', ').($studentCount > 5 ? '…' : '')),
            ],
            ['key' => 'terms', 'label' => 'Terms', 'icon' => '🗓️', 'value' => $terms->isEmpty() ? '—' : $terms->pluck('name')->implode(', ')],
            ['key' => 'fees', 'label' => 'Fees', 'icon' => '💰', 'value' => $feeValue],
            ['key' => 'whatsapp_verify', 'label' => 'WhatsApp', 'icon' => '📱', 'value' => $whatsapp?->phone ?: 'Not linked'],
            ['key' => 'plan_selection', 'label' => 'Selected plan', 'icon' => '✨', 'value' => $planValue],
        ];
    }

    private function hydrateFieldsForEdit(string $key): void
    {
        $school = $this->school()->fresh();
        $this->hydrateFieldsFromSchool($school);

        $sid = $school->id;

        match ($key) {
            'academic_year' => (function () use ($sid) {
                $year = AcademicYear::where('school_id', $sid)->first();
                if ($year) {
                    $this->academicYearDescription = (string) ($year->description ?: 'Current Academic Year');
                    $this->academicYearStart = $year->start_date ? substr((string) $year->start_date, 0, 10) : $this->academicYearStart;
                    $this->academicYearEnd = $year->end_date ? substr((string) $year->end_date, 0, 10) : $this->academicYearEnd;
                }
            })(),
            'standards' => (function () use ($sid) {
                $this->refreshStructureSnapshot();
                $link = StandardLink::with('section')->where('school_id', $sid)->first();
                if ($link?->section?->name) {
                    $this->className = (string) $link->section->name;
                }
            })(),
            'subjects' => (function () use ($sid) {
                $this->refreshExistingSubjects();
                // Leave the add field empty when subjects already exist so Next is a
                // clear no-op review; only prefill Mathematics when starting from scratch.
                if ($this->existingSubjectNames !== []) {
                    $this->subjectName = '';
                } elseif (trim($this->subjectName) === '') {
                    $this->subjectName = 'Mathematics';
                }
            })(),
            'teachers' => null,
            'terms' => (function () use ($sid) {
                $term = AcademicTerm::where('school_id', $sid)->first();
                if ($term) {
                    $this->termName = (string) $term->name;
                    $this->termStartsOn = $term->starts_on ? substr((string) $term->starts_on, 0, 10) : $this->termStartsOn;
                    $this->termEndsOn = $term->ends_on ? substr((string) $term->ends_on, 0, 10) : $this->termEndsOn;
                }
            })(),
            'fees' => (function () use ($sid) {
                $fee = FeesCategories::where('school_id', $sid)->first();
                if ($fee) {
                    $this->feeName = (string) $fee->name;
                    $this->feeAmount = (string) $fee->amount;
                }
            })(),
            'whatsapp_verify' => (function () {
                $wa = WhatsAppUser::where('user_id', Auth::id())->first();
                if ($wa) {
                    $this->whatsappPhone = (string) $wa->phone;
                    // Already linked + verified → no need to re-OTP when revisiting the step.
                    $this->whatsappVerified = $wa->verified_at !== null;
                }
            })(),
            'plan_selection' => (function () use ($sid) {
                $current = CurrentPlan::where('school_id', $sid)->first();
                if ($current) {
                    $this->selectedPlanId = (int) $current->plan_id;
                }
            })(),
            'emis' => null,
            'uneb_center' => null,
            default => null,
        };

        // Country/EMIS/UNEB combined review row edits country by default;
        // dedicated emis/uneb rows still hydrate from school above.
        if ($key === 'country') {
            // Prefer jumping to country; emis/uneb stay editable via progress dots.
        }
    }

    private function hydrateFieldsFromSchool(School $school): void
    {
        $this->schoolName = OnboardingStepsService::isPlaceholderSchoolName($school->name)
            ? ''
            : (string) $school->name;
        $this->studentSize = (string) ($school->student_size ?: '');
        $this->curriculum = $school->curriculum ?: 'uneb';
        $this->schoolCategory = (string) ($school->school_category ?: '');
        $this->countryName = $school->registration_country ?: 'Uganda';
        $this->ministryCode = (string) ($school->ministry_code ?: '');
        $this->unebCenterNumber = (string) ($school->uneb_center_number ?: '');
    }

    private function persistCurrentStep(string $key): void
    {
        $school = $this->school();

        match ($key) {
            'school_name' => $this->saveSchoolName($school),
            'student_size' => $this->saveStudentSize($school),
            'curriculum' => $this->saveCurriculum($school),
            'school_category' => $this->saveSchoolCategory($school),
            'country' => $this->saveCountry($school),
            'emis' => $this->saveEmis($school),
            'uneb_center' => $this->saveUneb($school),
            'academic_year' => $this->saveAcademicYear($school),
            'standards' => $this->saveClass($school),
            'subjects' => $this->saveSubject($school),
            'teachers' => $this->saveTeachers($school),
            'students' => $this->saveStudents($school),
            'terms' => $this->saveTerm($school),
            'fees' => $this->saveFee($school),
            'whatsapp_verify' => $this->saveWhatsApp($school),
            'plan_selection' => $this->savePlan($school),
            default => null,
        };
    }

    private function saveSchoolName(School $school): void
    {
        try {
            app(OnboardingEngine::class)->saveSchoolName($school, $this->schoolName);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['schoolName' => $e->getMessage()]);
        }
    }

    private function saveStudentSize(School $school): void
    {
        try {
            app(OnboardingEngine::class)->saveStudentSize($school, $this->studentSize);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['studentSize' => collect($e->errors())->flatten()->first()]);
        }
    }

    private function saveCurriculum(School $school): void
    {
        try {
            app(OnboardingEngine::class)->saveCurriculum($school, $this->curriculum);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['curriculum' => $e->getMessage()]);
        }
    }

    private function saveSchoolCategory(School $school): void
    {
        try {
            app(OnboardingEngine::class)->saveSchoolCategory($school, $this->schoolCategory);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['schoolCategory' => $e->getMessage()]);
        }
    }

    private function saveCountry(School $school): void
    {
        try {
            app(OnboardingEngine::class)->saveCountry($school, $this->countryName);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['countryName' => $e->getMessage()]);
        }
    }

    private function saveEmis(School $school): void
    {
        try {
            app(OnboardingEngine::class)->saveEmis($school, $this->ministryCode);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['ministryCode' => $e->getMessage()]);
        }
    }

    private function saveUneb(School $school): void
    {
        app(OnboardingEngine::class)->saveUnebCenter($school, $this->unebCenterNumber);
    }

    public function updatedStudentClass(): void
    {
        $this->applyStudentStreamDefaultForClass();
        if (! OnboardingEngine::isCandidateClass(trim($this->studentClass))) {
            $this->studentBoardRegNumber = '';
        }
    }

    /**
     * @return list<string>
     */
    public function streamsForStudentClass(): array
    {
        $class = trim($this->studentClass);
        if ($class === '') {
            return [];
        }

        foreach ($this->structureClasses as $row) {
            if (strcasecmp((string) ($row['name'] ?? ''), $class) === 0) {
                return array_values(array_map(
                    fn (array $stream) => (string) $stream['label'],
                    $row['streams'] ?? []
                ));
            }
        }

        return [];
    }

    public function addStructureStream(int $sectionId): void
    {
        $this->errorMessage = '';
        $this->structureFlash = '';

        $key = (string) $sectionId;
        $label = trim((string) ($this->structureStreamDrafts[$key] ?? ''));
        if ($label === '') {
            $this->errorMessage = 'Enter a stream name (e.g. A, East, Science).';

            return;
        }

        $school = $this->school()->fresh();
        $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->errorMessage = 'Create an academic year first.';

            return;
        }

        $section = Section::query()
            ->where('school_id', $school->id)
            ->whereKey($sectionId)
            ->first();
        if (! $section) {
            $this->errorMessage = 'Class not found.';

            return;
        }

        try {
            $result = app(OnboardingEngine::class)->addClassStream($school, $year, $section, $label);
            $this->structureStreamDrafts[$key] = '';
            $this->structureFlash = $result['created']
                ? 'Added stream “'.$label.'” to '.$section->name.'.'
                : 'Stream “'.$label.'” is already set up for '.$section->name.'.';
            $this->refreshStructureSnapshot();
        } catch (ValidationException $e) {
            $this->errorMessage = (string) collect($e->errors())->flatten()->first();
        }
    }

    public function inviteStructureClassTeacher(int $sectionId): void
    {
        $this->errorMessage = '';
        $this->structureFlash = '';

        $key = (string) $sectionId;
        $draft = $this->structureCtDrafts[$key] ?? [
            'email' => '',
            'existing_teacher_id' => '',
            'name' => '',
            'phone' => '',
        ];

        $school = $this->school()->fresh();
        $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->errorMessage = 'Create an academic year first.';

            return;
        }

        $link = StandardLink::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('section_id', $sectionId)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->first();

        if (! $link) {
            $this->errorMessage = 'Class not found for the current academic year.';

            return;
        }

        $phone = $this->normaliseOptionalPhone((string) ($draft['phone'] ?? ''), false);
        if ($phone === null) {
            return;
        }

        $result = ClassTeacherInviteService::invite(Auth::user(), $link, [
            'email' => $draft['email'] ?? '',
            'existing_teacher_id' => $draft['existing_teacher_id'] ?? '',
            'name' => $draft['name'] ?? '',
            'phone' => $phone,
        ]);

        if (! ($result['success'] ?? false)) {
            $this->errorMessage = (string) ($result['message'] ?? 'Could not invite class teacher.');

            return;
        }

        $this->structureCtDrafts[$key] = [
            'email' => '',
            'existing_teacher_id' => '',
            'name' => '',
            'phone' => '',
        ];
        $this->structureFlash = (string) ($result['message'] ?? 'Class teacher invited.');
        $this->refreshStructureSnapshot();
    }

    private function refreshStructureSnapshot(): void
    {
        $school = $this->school()->fresh();
        $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::where('school_id', $school->id)->first();

        $this->structureClasses = [];
        $this->schoolHasStreams = false;
        $this->structureTeachers = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
            ])
            ->values()
            ->all();

        if (! $year) {
            return;
        }

        $this->structureClasses = app(ClassStructureService::class)->structureSnapshot($school, $year);

        foreach ($this->structureClasses as $row) {
            $sid = (string) $row['section_id'];
            if (! isset($this->structureStreamDrafts[$sid])) {
                $this->structureStreamDrafts[$sid] = '';
            }
            if (! isset($this->structureCtDrafts[$sid])) {
                $this->structureCtDrafts[$sid] = [
                    'email' => '',
                    'existing_teacher_id' => '',
                    'name' => '',
                    'phone' => '',
                ];
            }
            if (($row['streams'] ?? []) !== []) {
                $this->schoolHasStreams = true;
            }
        }
    }

    private function applyStudentStreamDefaultForClass(): void
    {
        $streams = $this->streamsForStudentClass();
        if ($streams === []) {
            return;
        }

        // Default to first stream when the class is split; blank remains selectable.
        if ($this->studentStream === '' || ! in_array($this->studentStream, $streams, true)) {
            $this->studentStream = $streams[0];
        }
    }

    private function saveAcademicYear(School $school): void
    {
        app(OnboardingEngine::class)->saveAcademicYear(
            $school,
            date('Y', strtotime($this->academicYearStart)),
            $this->academicYearStart,
            $this->academicYearEnd,
            $this->academicYearDescription
        );
    }

    private function saveClass(School $school): void
    {
        // Structure checkpoint: when classes already exist (auto-seed), Next is a no-op.
        // Stream/CT actions persist immediately via addStructureStream / inviteStructureClassTeacher.
        if (StandardLink::where('school_id', $school->id)->exists()) {
            return;
        }

        $name = trim($this->className);
        if ($name === '') {
            throw ValidationException::withMessages(['className' => 'Enter a class name (e.g. P1).']);
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            throw ValidationException::withMessages(['className' => 'Create an academic year first.']);
        }

        try {
            app(OnboardingEngine::class)->saveStandards($school, $year, [
                ['name' => $name],
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['className' => collect($e->errors())->flatten()->first()]);
        }
    }

    private function saveSubject(School $school): void
    {
        $raw = trim($this->subjectName);

        // Already seeded/saved and add field left blank → Next is a review no-op.
        if ($raw !== '') {
            $this->subjectFlash = $this->persistEnteredSubjects($school, $raw);
            $this->subjectName = '';
        }

        if (! Subject::where('school_id', $school->id)->exists()) {
            throw ValidationException::withMessages(['subjectName' => 'Enter a subject name.']);
        }
    }

    /**
     * Parse the subjects field and attach each new name to the selected class record.
     *
     * @throws ValidationException
     */
    private function persistEnteredSubjects(School $school, string $raw): string
    {
        [$names, $repeated] = $this->parseSubjectInput($raw);
        if ($names === []) {
            throw ValidationException::withMessages(['subjectName' => 'Enter a subject name.']);
        }

        $this->refreshStructureSnapshot();
        $baseId = $this->selectedSubjectSectionId($school);
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            throw ValidationException::withMessages(['subjectName' => 'Add a class on the classes step first.']);
        }

        $links = $this->linksForSubjectClass($school, $year, $baseId);
        $baseLink = $links->first(fn (StandardLink $link): bool => (int) $link->section_id === $baseId);
        if (! $baseLink) {
            throw ValidationException::withMessages(['subjectName' => 'Add a class on the classes step first.']);
        }

        $present = [];
        foreach (Subject::query()->where('school_id', $school->id)->where('section_id', $baseId)->get() as $subject) {
            $present[mb_strtolower((string) $subject->name)] = true;
        }

        $added = [];
        $already = [];
        foreach ($names as $name) {
            $key = mb_strtolower($name);
            if (isset($present[$key])) {
                $already[] = $name;
                $this->backfillSubjectOnLinks($school, $year, $links, $name);

                continue;
            }

            foreach ($links as $link) {
                $this->firstOrRestoreSubject($school, $year, $link, $name);
            }
            $present[$key] = true;
            $added[] = $name;
        }

        return $this->subjectResultMessage($this->subjectClassName($baseId), $added, $already, $repeated);
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function parseSubjectInput(string $raw): array
    {
        $parts = preg_split('/[\r\n,]+/u', $raw) ?: [];
        $names = [];
        $repeated = [];
        $seen = [];

        foreach ($parts as $part) {
            $name = trim((string) $part);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                $repeated[] = $name;

                continue;
            }
            $seen[$key] = true;
            $names[] = $name;
        }

        return [$names, $repeated];
    }

    /**
     * The admin's choice when it is one of this school's class records.
     * Otherwise the classes-step hint (including a legacy short code).
     *
     * @throws ValidationException
     */
    private function selectedSubjectSectionId(School $school): int
    {
        $ids = array_map(
            fn (array $row): int => (int) ($row['section_id'] ?? 0),
            $this->structureClasses
        );

        if ($this->subjectSectionId > 0 && in_array($this->subjectSectionId, $ids, true)) {
            $owned = Section::query()
                ->where('school_id', $school->id)
                ->where('id', $this->subjectSectionId)
                ->exists();
            if ($owned) {
                return $this->subjectSectionId;
            }
        }

        $this->ensureSubjectClassSelected();
        if ($this->subjectSectionId > 0 && in_array($this->subjectSectionId, $ids, true)) {
            return $this->subjectSectionId;
        }

        throw ValidationException::withMessages([
            'subjectName' => 'Add a class on the classes step first.',
        ]);
    }

    /**
     * @return list<int>
     */
    private function sectionIdsForBase(int $baseSectionId): array
    {
        foreach ($this->structureClasses as $class) {
            if ((int) ($class['section_id'] ?? 0) !== $baseSectionId) {
                continue;
            }
            $ids = [$baseSectionId];
            foreach ($class['streams'] ?? [] as $stream) {
                $streamId = (int) ($stream['section_id'] ?? 0);
                if ($streamId > 0) {
                    $ids[] = $streamId;
                }
            }

            return array_values(array_unique($ids));
        }

        return [$baseSectionId];
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function schoolSectionIds(School $school, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Section::query()
            ->where('school_id', $school->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function linksForSubjectClass(School $school, AcademicYear $year, int $baseSectionId): \Illuminate\Support\Collection
    {
        return app(OnboardingEngine::class)->standardLinksForClass($school, $year, $baseSectionId);
    }

    private function backfillSubjectOnLinks(School $school, AcademicYear $year, \Illuminate\Support\Collection $links, string $name): void
    {
        foreach ($links as $link) {
            if ($link instanceof StandardLink) {
                $this->firstOrRestoreSubject($school, $year, $link, $name);
            }
        }
    }

    private function firstOrRestoreSubject(School $school, AcademicYear $year, StandardLink $link, string $name): void
    {
        $row = Subject::withTrashed()
            ->where('school_id', $school->id)
            ->where('section_id', $link->section_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($row) {
            if ($row->trashed()) {
                $row->restore();
            }

            return;
        }

        Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $link->standard_id,
            'section_id' => $link->section_id,
            'name' => $name,
            'code' => null,
            'type' => 'core',
        ]);
    }

    private function subjectClassName(int $sectionId): string
    {
        foreach ($this->structureClasses as $class) {
            if ((int) ($class['section_id'] ?? 0) === $sectionId) {
                $name = trim((string) ($class['name'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return 'this class';
    }

    /**
     * @param  list<string>  $added
     * @param  list<string>  $already
     * @param  list<string>  $repeated
     */
    private function subjectResultMessage(string $className, array $added, array $already, array $repeated): string
    {
        $parts = [];
        if ($added !== []) {
            $parts[] = 'Added to '.$className.': '.implode(', ', $added).'.';
        }
        if ($already !== []) {
            $parts[] = 'Already on '.$className.': '.implode(', ', $already).'.';
        }
        if ($repeated !== []) {
            $parts[] = 'Repeated in the list (kept once): '.implode(', ', $repeated).'.';
        }

        return implode(' ', $parts);
    }

    private function refreshSubjectsForSelectedClass(): void
    {
        $sectionId = (int) $this->subjectSectionId;
        if ($sectionId <= 0) {
            $this->existingSubjectNames = [];

            return;
        }

        $this->existingSubjectNames = Subject::query()
            ->where('school_id', $this->school()->id)
            ->where('section_id', $sectionId)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($this->existingSubjectNames !== []) {
            $this->subjectName = '';
        }
    }

    /**
     * @return list<string>
     */
    private function subjectNamesForSchool(int $schoolId): array
    {
        return Subject::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Save each readable teacher now. One bad row does not roll the others back,
     * and nothing here sends an invite — that stays on the class-teacher form.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTeacherUpload(array $rows, bool $storeRows = true): void
    {
        if ($storeRows) {
            $this->teacherUploadRows = $rows;
        }
        $this->teacherMissingStreams = [];
        $added = 0;
        $skipped = [];
        $notes = [];
        $seenEmails = [];
        $seenPhones = [];

        foreach ($this->teacherDrafts as $draft) {
            $draftEmail = strtolower(trim((string) ($draft['email'] ?? '')));
            if ($draftEmail !== '') {
                $seenEmails[$draftEmail] = true;
            }
            $draftPhone = trim((string) ($draft['phone'] ?? ''));
            if ($draftPhone !== '') {
                $seenPhones[$draftPhone] = true;
            }
        }

        $school = $this->school();
        $year = AcademicYear::where('school_id', $school->id)->first();

        foreach ($rows as $index => $row) {
            $line = (int) ($row['row'] ?? ($index + 2));
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $skipped[] = ['row' => $line, 'reason' => 'missing name'];

                continue;
            }

            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '') {
                $email = app(OnboardingEngine::class)->allocateGeneratedEmail($school, 'teacher', '.test');
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped[] = ['row' => $line, 'reason' => 'invalid email'];

                continue;
            }

            $emailKey = strtolower($email);
            if (isset($seenEmails[$emailKey])) {
                $skipped[] = ['row' => $line, 'reason' => 'duplicate email'];

                continue;
            }
            if (User::withTrashed()->whereRaw('LOWER(email) = ?', [$emailKey])->exists()) {
                $skipped[] = ['row' => $line, 'reason' => 'already exists'];

                continue;
            }

            $phone = $this->normaliseOptionalPhone(trim((string) ($row['phone'] ?? '')), false, false);
            if ($phone === null) {
                $skipped[] = ['row' => $line, 'reason' => 'invalid phone'];

                continue;
            }
            if ($phone !== '' && (isset($seenPhones[$phone]) || $this->teacherPhoneTaken($school->id, $phone))) {
                $skipped[] = ['row' => $line, 'reason' => 'duplicate phone'];

                continue;
            }

            $classes = $this->splitCsvList((string) ($row['classes'] ?? ''));
            if ($classes === []) {
                $classes = $this->splitCsvList((string) ($row['class'] ?? ''));
            }

            if ($year && (int) $year->school_id === (int) $school->id) {
                $streamHits = app(OnboardingEngine::class)->collectMissingStreams(
                    $school,
                    $year,
                    $this->teacherStreamRows($school, $year, $row, $line, $classes),
                );
                if ($streamHits['pairs'] !== []) {
                    foreach ($streamHits['skips'] as $skip) {
                        $skipped[] = ['row' => $line, 'reason' => $skip['reason']];
                    }
                    foreach ($streamHits['pairs'] as $pair) {
                        $this->rememberMissingStream($this->teacherMissingStreams, $pair);
                    }

                    continue;
                }
            }

            $rowStream = trim((string) ($row['stream'] ?? ''));
            if ($year && $rowStream !== '') {
                $classes = array_map(function (string $className) use ($school, $year, $rowStream) {
                    $classified = app(OnboardingEngine::class)->classifyClassAndStream($school, $year, $className, $rowStream);
                    $sectionName = trim((string) ($classified['link']?->section?->name ?? ''));
                    if ($classified['status'] === 'resolved' && $sectionName !== '') {
                        return $sectionName;
                    }

                    return OnboardingEngine::composeClassAndStream($className, $rowStream);
                }, $classes);
            }

            $draft = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'classes' => $classes,
                'subjects' => $this->splitCsvList((string) ($row['subjects'] ?? '')),
            ];

            if (! $year) {
                $this->teacherDrafts[] = $draft;
                $seenEmails[$emailKey] = true;
                if ($phone !== '') {
                    $seenPhones[$phone] = true;
                }
                $added++;

                continue;
            }

            $result = $this->commitTeacherDraft($draft, $year);
            if (! $result['ok']) {
                $skipped[] = ['row' => $line, 'reason' => $result['reason']];

                continue;
            }

            $seenEmails[$emailKey] = true;
            if ($phone !== '') {
                $seenPhones[$phone] = true;
            }
            $added++;
            foreach ($result['notes'] as $note) {
                $notes[] = ['row' => $line, 'reason' => $note];
            }
        }

        if ($year && $added > 0) {
            $this->noteTeacherPlanLimit($school);
        }

        $this->teacherUploadSkips = $skipped;
        $this->teacherUploadNotes = $notes;
        $this->teacherUploadReport = ($year || $skipped !== [] || $added > 0)
            ? $this->formatTeacherUploadReport($year ? $added : 0, count($skipped))
            : '';
        if (! $year && $added > 0 && $skipped === []) {
            $this->teacherUploadReport = '';
        }
    }

    /**
     * @param  array{name: string, email: string, phone: string, classes: list<string>, subjects: list<string>}  $draft
     * @return array{ok: bool, reason: string, detail: string, notes: list<string>}
     */
    private function commitTeacherDraft(array $draft, AcademicYear $year): array
    {
        $school = $this->school();
        $resolved = $this->resolveTeacherAssignmentLinks(
            $school,
            $year,
            $draft['classes'],
            $draft['subjects'],
        );

        try {
            $result = app(OnboardingEngine::class)->saveTeachers($school, $year, [[
                'name' => $draft['name'],
                'email' => $draft['email'],
                'phone' => $draft['phone'],
                'links' => $resolved['links'],
            ]]);
        } catch (ValidationException $e) {
            $detail = (string) (collect($e->errors())->flatten()->first() ?: 'could not save');

            return [
                'ok' => false,
                'reason' => $this->shortTeacherSkipReason($detail),
                'detail' => $detail,
                'notes' => [],
            ];
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return [
                'ok' => false,
                'reason' => 'already exists',
                'detail' => 'already exists',
                'notes' => [],
            ];
        }

        if (($result['created'] ?? []) === []) {
            $detail = (string) (collect($result['skipped'] ?? [])->pluck('reason')->filter()->first() ?: 'could not save');

            return [
                'ok' => false,
                'reason' => $this->shortTeacherSkipReason($detail),
                'detail' => $detail,
                'notes' => [],
            ];
        }

        return ['ok' => true, 'reason' => '', 'detail' => '', 'notes' => $resolved['problems']];
    }

    private function teacherPhoneTaken(int $schoolId, string $phone): bool
    {
        return User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->where('mobile_no', $phone)
            ->exists();
    }

    private function shortTeacherSkipReason(string $reason): string
    {
        $reason = trim($reason);
        $lower = strtolower($reason);
        if (str_contains($lower, 'already registered') || str_contains($lower, 'already exists')) {
            return 'already exists';
        }
        if (str_contains($lower, 'phone')) {
            return 'invalid phone';
        }
        if (str_contains($lower, 'email') && str_contains($lower, 'more than once')) {
            return 'duplicate email';
        }
        if (str_contains($lower, 'invalid name') || $lower === 'invalid name or email') {
            return 'missing name';
        }
        if (str_contains($lower, 'email')) {
            return 'invalid email';
        }

        return $reason !== '' ? $reason : 'could not save';
    }

    private function formatTeacherUploadReport(int $added, int $skipped): string
    {
        $addedText = $added === 1 ? '1 teacher added' : $added.' teachers added';
        $skippedText = $skipped === 1 ? '1 skipped' : $skipped.' skipped';

        return $addedText.', '.$skippedText;
    }

    /**
     * Save each readable class-teacher row now. One bad row does not roll the
     * others back. Nothing here sends an invite or a message — the teacher
     * file does not either. Assignment uses standards_link.class_teacher_id
     * and sections.class_teacher_id, the same columns the class-teacher
     * invite writes when it assigns someone who already has an account.
     */
    private function ingestClassTeacherUpload(): void
    {
        $file = $this->classTeacherUpload;
        if (! $file) {
            return;
        }

        try {
            $this->validate([
                'classTeacherUpload' => 'file|max:10240',
            ]);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Upload failed.';

            return;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, OnboardingNameListExtractor::ACCEPTED_MIMES, true)) {
            $this->errorMessage = 'Supported files: CSV, XLSX, TXT, DOCX, PDF.';

            return;
        }

        $rows = app(OnboardingNameListExtractor::class)->extractNamesFromFile($file->getRealPath(), $ext);
        $this->classTeacherUpload = null;
        if ($rows === []) {
            $this->errorMessage = 'No names found in that file.';
            $this->classTeacherUploadReport = '';
            $this->classTeacherUploadSkips = [];

            return;
        }

        $this->importClassTeacherUpload($rows);
        $this->errorMessage = '';
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importClassTeacherUpload(array $rows): void
    {
        $this->classTeacherUploadRows = $rows;
        $this->classTeacherMissingStreams = [];
        $added = 0;
        $skipped = [];
        $created = 0;
        $school = $this->school();
        $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::where('school_id', $school->id)->first();

        foreach ($rows as $index => $row) {
            $line = (int) ($row['row'] ?? ($index + 2));
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $skipped[] = ['row' => $line, 'reason' => 'missing name'];

                continue;
            }

            $email = trim((string) ($row['email'] ?? ''));
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped[] = ['row' => $line, 'reason' => 'bad email'];

                continue;
            }

            $phone = $this->normaliseOptionalPhone(trim((string) ($row['phone'] ?? '')), false, false);
            if ($phone === null) {
                $skipped[] = ['row' => $line, 'reason' => 'invalid phone'];

                continue;
            }

            if (! $year || (int) $year->school_id !== (int) $school->id) {
                $skipped[] = ['row' => $line, 'reason' => 'unknown class'];

                continue;
            }

            $resolved = $this->resolveClassTeacherLink(
                $school,
                $year,
                (string) ($row['class'] ?? ''),
                (string) ($row['stream'] ?? ''),
            );
            if (! $resolved['link'] instanceof StandardLink) {
                $skipped[] = ['row' => $line, 'reason' => $resolved['reason']];
                if (is_array($resolved['missing'] ?? null)) {
                    $this->rememberMissingStream($this->classTeacherMissingStreams, $resolved['missing']);
                }

                continue;
            }

            $teacher = $this->findOrCreateClassTeacher($school, $year, $name, $email, $phone);
            if (! $teacher['user'] instanceof User) {
                $skipped[] = ['row' => $line, 'reason' => $teacher['reason']];

                continue;
            }

            if (! $this->writeClassTeacherAssignment($school, $resolved['link'], $teacher['user'])) {
                $skipped[] = ['row' => $line, 'reason' => 'could not save'];

                continue;
            }

            if ($teacher['created']) {
                $created++;
            }
            $added++;
        }

        if ($created > 0) {
            $this->noteTeacherPlanLimit($school);
        }

        $this->classTeacherUploadSkips = $skipped;
        $this->classTeacherUploadReport = $this->formatClassTeacherUploadReport($added, count($skipped));
    }

    /**
     * @return array{link: ?StandardLink, reason: string, missing?: array{class: string, stream: string}}
     */
    private function resolveClassTeacherLink(School $school, AcademicYear $year, string $className, string $stream): array
    {
        $className = trim($className);
        $stream = trim($stream);
        if ($className === '') {
            return ['link' => null, 'reason' => 'unknown class'];
        }

        $engine = app(OnboardingEngine::class);
        $links = $engine->standardLinksForClass($school, $year, $className)
            ->filter(fn (StandardLink $link) => $this->classTeacherLinkIsAssignable($school, $link))
            ->values();
        if ($links->isEmpty()) {
            return ['link' => null, 'reason' => 'unknown class'];
        }

        if ($stream !== '') {
            $classified = $engine->classifyClassAndStream($school, $year, $className, $stream);
            if ($classified['status'] === 'missing_stream') {
                return [
                    'link' => null,
                    'reason' => $classified['reason'],
                    'missing' => [
                        'class' => $classified['class'],
                        'stream' => $classified['stream'],
                    ],
                ];
            }
            $streamLink = $classified['link'];
            if (! $streamLink instanceof StandardLink || ! $this->classTeacherLinkIsAssignable($school, $streamLink)) {
                return ['link' => null, 'reason' => $classified['reason'] !== '' ? $classified['reason'] : 'unknown stream'];
            }
            $belongs = $links->contains(fn (StandardLink $link) => (int) $link->id === (int) $streamLink->id);
            if (! $belongs) {
                return [
                    'link' => null,
                    'reason' => OnboardingEngine::missingStreamMessage($classified['class'] !== '' ? $classified['class'] : $className, $classified['stream'] !== '' ? $classified['stream'] : $stream),
                    'missing' => [
                        'class' => $classified['class'] !== '' ? $classified['class'] : $className,
                        'stream' => $classified['stream'] !== '' ? $classified['stream'] : $stream,
                    ],
                ];
            }

            return ['link' => $streamLink, 'reason' => ''];
        }

        $inputKeys = array_map('strtolower', OnboardingEngine::classNameCandidates($className));
        $exact = $links->filter(function (StandardLink $link) use ($inputKeys) {
            $sectionName = trim((string) ($link->section?->name ?? ''));
            if ($sectionName === '') {
                return false;
            }
            $sectionKeys = array_map('strtolower', OnboardingEngine::classNameCandidates($sectionName));

            return array_intersect($inputKeys, $sectionKeys) !== [];
        })->values();

        $chosen = $exact->isNotEmpty() ? $exact : $links;
        $base = $chosen->first(function (StandardLink $link) {
            return $link->section && app(ClassStructureService::class)->isBaseSection($link->section);
        });
        $link = $base ?? $chosen->sortBy(fn (StandardLink $link) => strlen(trim((string) ($link->section?->name ?? ''))))->first();
        if (! $link instanceof StandardLink) {
            return ['link' => null, 'reason' => 'unknown class'];
        }

        return ['link' => $link, 'reason' => ''];
    }

    private function classTeacherLinkIsAssignable(School $school, StandardLink $link): bool
    {
        if ((int) $link->school_id !== (int) $school->id || (string) $link->status !== '1') {
            return false;
        }

        $section = $link->section;
        if (! $section || (int) $section->school_id !== (int) $school->id) {
            return false;
        }

        return (string) $section->status === '1' || (int) $section->status === 1;
    }

    /**
     * Find an active teacher in this school by email, then by phone. A new
     * email creates a teacher the same way the teacher upload does. An email
     * that already belongs to someone else is not reused.
     *
     * @return array{user: ?User, reason: string, created: bool}
     */
    private function findOrCreateClassTeacher(School $school, AcademicYear $year, string $name, string $email, string $phone): array
    {
        $emailKey = strtolower($email);
        $byEmail = null;
        if ($emailKey !== '') {
            $byEmail = $this->activeSchoolTeacherQuery($school)
                ->whereRaw('LOWER(email) = ?', [$emailKey])
                ->first();
            if (! $byEmail && User::withTrashed()->whereRaw('LOWER(email) = ?', [$emailKey])->exists()) {
                return ['user' => null, 'reason' => 'already exists', 'created' => false];
            }
        }

        if ($byEmail instanceof User) {
            return ['user' => $byEmail, 'reason' => '', 'created' => false];
        }

        if ($phone !== '') {
            $byPhone = $this->activeSchoolTeacherQuery($school)
                ->where('mobile_no', $phone)
                ->limit(2)
                ->get();
            if ($byPhone->count() > 1) {
                return ['user' => null, 'reason' => 'duplicate phone', 'created' => false];
            }
            if ($byPhone->count() === 1) {
                if ($emailKey === '') {
                    return ['user' => $byPhone->first(), 'reason' => '', 'created' => false];
                }

                return ['user' => null, 'reason' => 'duplicate phone', 'created' => false];
            }
        }

        $emailForCreate = $email !== ''
            ? $email
            : app(OnboardingEngine::class)->allocateGeneratedEmail($school, 'teacher', '.test');

        try {
            $result = app(OnboardingEngine::class)->saveTeachers($school, $year, [[
                'name' => $name,
                'email' => $emailForCreate,
                'phone' => $phone,
                'links' => [],
            ]]);
        } catch (ValidationException $e) {
            $detail = (string) (collect($e->errors())->flatten()->first() ?: 'could not save');

            return ['user' => null, 'reason' => $this->classTeacherSkipReason($detail), 'created' => false];
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return ['user' => null, 'reason' => 'already exists', 'created' => false];
        }

        $userId = (int) ($result['created'][0]['user_id'] ?? 0);
        $user = $userId > 0
            ? $this->activeSchoolTeacherQuery($school)->whereKey($userId)->first()
            : null;
        if (! $user instanceof User) {
            $detail = (string) (collect($result['skipped'] ?? [])->pluck('reason')->filter()->first() ?: 'could not save');

            return ['user' => null, 'reason' => $this->classTeacherSkipReason($detail), 'created' => false];
        }

        return ['user' => $user, 'reason' => '', 'created' => true];
    }

    private function classTeacherSkipReason(string $detail): string
    {
        $reason = $this->shortTeacherSkipReason($detail);

        return $reason === 'invalid email' ? 'bad email' : $reason;
    }

    private function activeSchoolTeacherQuery(School $school): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active');
    }

    private function writeClassTeacherAssignment(School $school, StandardLink $link, User $teacher): bool
    {
        if ((int) $teacher->school_id !== (int) $school->id || (int) $teacher->usergroup_id !== 5) {
            return false;
        }

        $link = StandardLink::query()
            ->where('school_id', $school->id)
            ->whereKey($link->id)
            ->first();
        if (! $link || ! $this->classTeacherLinkIsAssignable($school, $link)) {
            return false;
        }

        $section = Section::query()
            ->where('school_id', $school->id)
            ->whereKey($link->section_id)
            ->first();
        if (! $section || (int) $section->school_id !== (int) $school->id) {
            return false;
        }

        $link->class_teacher_id = $teacher->id;
        $link->save();
        $section->class_teacher_id = $teacher->id;
        $section->save();

        return true;
    }

    /**
     * @param  array{class?: string, stream?: string}  $pair
     * @param  list<array{class: string, stream: string}>  $pairs
     */
    private function rememberMissingStream(array &$pairs, array $pair): void
    {
        $class = trim((string) ($pair['class'] ?? ''));
        $stream = trim((string) ($pair['stream'] ?? ''));
        if ($class === '' || $stream === '') {
            return;
        }
        foreach ($pairs as $existing) {
            if (strcasecmp((string) $existing['class'], $class) === 0
                && strcasecmp((string) $existing['stream'], $stream) === 0) {
                return;
            }
        }
        $pairs[] = ['class' => $class, 'stream' => $stream];
    }

    /**
     * @param  list<string>  $classes
     * @return list<array{row: int, class: string, stream: string}>
     */
    private function teacherStreamRows(School $school, AcademicYear $year, array $row, int $line, array $classes): array
    {
        $engine = app(OnboardingEngine::class);
        $stream = trim((string) ($row['stream'] ?? ''));
        $out = [];
        foreach ($classes as $className) {
            $className = trim((string) $className);
            if ($className === '') {
                continue;
            }
            if ($stream !== '') {
                $out[] = ['row' => $line, 'class' => $className, 'stream' => $stream];

                continue;
            }
            $split = $engine->splitStreamedClassToken($school, $year, $className);
            if ($split['stream'] !== '') {
                $out[] = ['row' => $line, 'class' => $split['class'], 'stream' => $split['stream']];
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function teacherRowsPendingStreams(): array
    {
        $wanted = [];
        foreach ($this->teacherUploadSkips as $skip) {
            if (! str_contains((string) ($skip['reason'] ?? ''), "doesn't exist yet")) {
                continue;
            }
            $wanted[(int) ($skip['row'] ?? 0)] = true;
        }

        return array_values(array_filter(
            $this->teacherUploadRows,
            fn (array $row): bool => isset($wanted[(int) ($row['row'] ?? 0)])
        ));
    }

    private function refreshStudentMissingStreams(): void
    {
        $school = $this->school();
        $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::where('school_id', $school->id)->first();
        if (! $year || (int) $year->school_id !== (int) $school->id) {
            return;
        }

        $found = app(OnboardingEngine::class)->collectMissingStreams($school, $year, $this->studentDrafts);
        $this->studentMissingStreams = $found['pairs'];
        $this->studentUploadSkips = $found['skips'];
        if ($found['skips'] !== []) {
            $this->studentUploadReport = $this->formatStudentUploadReport(0, count($found['skips']));
        }
    }

    private function retryStudentUpload(School $school): void
    {
        if ($this->studentDrafts === []) {
            return;
        }

        $before = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 6)
            ->count();
        $this->saveStudents($school);
        $added = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 6)
            ->count() - $before;
        $this->studentMissingStreams = [];
        $this->studentUploadSkips = [];
        $this->studentUploadReport = $this->formatStudentUploadReport($added, 0);
    }

    private function formatStudentUploadReport(int $added, int $skipped): string
    {
        $addedText = $added === 1 ? '1 student added' : $added.' students added';
        $skippedText = $skipped === 1 ? '1 skipped' : $skipped.' skipped';

        return $addedText.', '.$skippedText;
    }

    private function formatClassTeacherUploadReport(int $added, int $skipped): string
    {
        $addedText = $added === 1 ? '1 class teacher added' : $added.' class teachers added';
        $skippedText = $skipped === 1 ? '1 skipped' : $skipped.' skipped';

        return $addedText.', '.$skippedText;
    }

    private function noteTeacherPlanLimit(School $school): void
    {
        $teacherOver = ToshiActionService::enforcePlanLimit($school->id, 'teachers');
        if (! $teacherOver['success']) {
            $this->overLimitNotice = $teacherOver['message'];
        }
    }

    /**
     * Active teachers already stored for this school. The step list reads this,
     * not only the unsaved draft rows.
     *
     * @return list<array{name: string, email: string, phone: string}>
     */
    public function savedTeacherRows(): array
    {
        $schoolId = Auth::user()?->school_id;
        if (! $schoolId) {
            return [];
        }

        return User::query()
            ->with('userprofile')
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => [
                'name' => trim((string) $user->name) !== ''
                    ? (string) $user->name
                    : (string) $user->displayName,
                'email' => (string) $user->email,
                'phone' => (string) ($user->mobile_no ?? ''),
            ])
            ->all();
    }

    /**
     * Class-teacher assignments already stored for this school. The step list
     * reads standards_link.class_teacher_id, the same column the invite uses.
     *
     * @return list<array{standard_link_id: int, class: string, stream: string, name: string, email: string, phone: string}>
     */
    public function savedClassTeacherRows(): array
    {
        $schoolId = Auth::user()?->school_id;
        if (! $schoolId) {
            return [];
        }

        $year = AcademicYear::where('school_id', $schoolId)->where('status', 1)->first()
            ?? AcademicYear::where('school_id', $schoolId)->first();
        if (! $year || (int) $year->school_id !== (int) $schoolId) {
            return [];
        }

        $structure = app(ClassStructureService::class);

        return StandardLink::query()
            ->with(['section', 'teacher'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->whereNotNull('class_teacher_id')
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->orderBy('id')
            ->get()
            ->filter(function (StandardLink $link) use ($schoolId) {
                return $link->section
                    && (int) $link->section->school_id === (int) $schoolId
                    && $link->teacher
                    && (int) $link->teacher->school_id === (int) $schoolId
                    && (int) $link->teacher->usergroup_id === 5;
            })
            ->map(function (StandardLink $link) use ($structure) {
                $section = $link->section;
                $base = $structure->resolveBaseSection($section);
                $baseName = trim((string) $base->name);
                $sectionName = trim((string) $section->name);
                $isBase = (int) $base->id === (int) $section->id
                    && (int) $base->school_id === (int) $section->school_id;
                $stream = '';
                if (! $isBase) {
                    $prefix = $baseName.' ';
                    $stream = str_starts_with($sectionName, $prefix)
                        ? trim(substr($sectionName, strlen($prefix)))
                        : $sectionName;
                }

                return [
                    'standard_link_id' => (int) $link->id,
                    'class' => $baseName !== '' ? $baseName : $sectionName,
                    'stream' => $stream,
                    'name' => trim((string) $link->teacher->name),
                    'email' => (string) $link->teacher->email,
                    'phone' => (string) ($link->teacher->mobile_no ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function saveTeachers(School $school): void
    {
        // Flush a pending single-add form into the draft list (auto-email if needed).
        if (trim($this->teacherName) !== '') {
            $this->addTeacherDraft();
            if ($this->errorMessage !== '') {
                throw ValidationException::withMessages(['teacherName' => $this->errorMessage]);
            }
        }

        if ($this->teacherDrafts === []) {
            return;
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            throw ValidationException::withMessages(['teacherName' => 'Create an academic year first.']);
        }

        $skipped = [];
        $kept = [];
        $saved = 0;
        foreach ($this->teacherDrafts as $index => $draft) {
            $email = trim((string) ($draft['email'] ?? ''));
            if ($email === '') {
                $email = app(OnboardingEngine::class)->allocateGeneratedEmail($school, 'teacher', '.test');
            }
            $result = $this->commitTeacherDraft([
                'name' => trim((string) ($draft['name'] ?? '')),
                'email' => $email,
                'phone' => trim((string) ($draft['phone'] ?? '')),
                'classes' => is_array($draft['classes'] ?? null) ? $draft['classes'] : [],
                'subjects' => is_array($draft['subjects'] ?? null) ? $draft['subjects'] : [],
            ], $year);
            if ($result['ok']) {
                $saved++;

                continue;
            }
            $kept[] = $draft;
            $skipped[] = [
                'row' => $index + 1,
                'reason' => $result['reason'],
                'detail' => $result['detail'] !== '' ? $result['detail'] : $result['reason'],
            ];
        }

        $this->teacherDrafts = $kept;
        if ($saved > 0) {
            $this->noteTeacherPlanLimit($school);
        }
        if ($skipped !== []) {
            $this->teacherUploadSkips = array_map(
                fn (array $skip) => ['row' => $skip['row'], 'reason' => $skip['reason']],
                $skipped
            );
            $this->teacherUploadNotes = [];
            $this->teacherUploadReport = $this->formatTeacherUploadReport($saved, count($skipped));
            $details = array_map(
                fn (array $skip) => 'Row '.$skip['row'].': '.$skip['detail'],
                $skipped
            );
            throw ValidationException::withMessages([
                'teacherName' => $this->teacherUploadReport.' '.implode(' ', $details),
            ]);
        }

        $this->teacherDrafts = [];
    }


    /**
     * @param  list<string|mixed>  $classes
     * @param  list<string|mixed>  $subjects
     * @return array{links: list<array{standardLink_id: int, subject_id: int}>, problems: list<string>}
     */
    private function resolveTeacherAssignmentLinks(School $school, AcademicYear $year, array $classes, array $subjects): array
    {
        $classNames = array_values(array_unique(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $classes
        ))));
        $subjectNames = array_values(array_unique(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $subjects
        ))));

        if ($classNames === [] || $subjectNames === []) {
            return ['links' => [], 'problems' => []];
        }

        $engine = app(OnboardingEngine::class);
        $links = [];
        $problems = [];
        $seen = [];

        foreach ($classNames as $className) {
            $standardLink = $engine->resolveStandardLinkForClass($school, $year, $className);
            if (! $standardLink) {
                // Fall back: match section display name on existing links.
                $standardLink = StandardLink::with('section')
                    ->where('school_id', $school->id)
                    ->where('academic_year_id', $year->id)
                    ->get()
                    ->first(function ($link) use ($className) {
                        $sec = trim((string) ($link->section?->name ?? ''));

                        return $sec !== '' && strcasecmp($sec, $className) === 0;
                    });
            }
            if (! $standardLink) {
                $problems[] = "class '{$className}' was not found";

                continue;
            }

            foreach ($subjectNames as $subjectName) {
                $subject = $engine->resolveOrCreateSubjectForClass($school, $year, $standardLink, $subjectName);
                if (! $subject) {
                    $problems[] = "subject '{$subjectName}' was not found for class '{$className}'";

                    continue;
                }

                $key = $standardLink->id.':'.$subject->id;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $links[] = [
                    'standardLink_id' => (int) $standardLink->id,
                    'subject_id' => (int) $subject->id,
                ];
            }
        }

        return ['links' => $links, 'problems' => $problems];
    }

    /**
     * @return list<string>
     */
    private function splitCsvList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;|]+/', $raw) ?: [])));
    }

    public function addTermDraft(): void
    {
        $name = trim($this->termName);
        if ($name === '') {
            $this->errorMessage = 'Enter a term name.';

            return;
        }

        if ($this->termStartsOn === '' || $this->termEndsOn === '') {
            $this->errorMessage = 'Enter start and end dates for the term.';

            return;
        }

        try {
            $this->validate([
                'termStartsOn' => 'required|date',
                'termEndsOn' => 'required|date|after:termStartsOn',
            ]);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Check the term dates.';

            return;
        }

        foreach ($this->termDrafts as $draft) {
            if (strcasecmp((string) ($draft['name'] ?? ''), $name) === 0) {
                $this->errorMessage = "Term '{$name}' is already in the list.";

                return;
            }
        }

        $this->termDrafts[] = [
            'name' => $name,
            'start' => $this->termStartsOn,
            'end' => $this->termEndsOn,
        ];
        if ($this->currentTermName === '') {
            $this->currentTermName = $name;
        }
        $this->termName = '';
        $this->termStartsOn = '';
        $this->termEndsOn = '';
        $this->errorMessage = '';
    }

    public function removeTermDraft(int $index): void
    {
        if (! isset($this->termDrafts[$index])) {
            return;
        }
        $removed = trim((string) ($this->termDrafts[$index]['name'] ?? ''));
        unset($this->termDrafts[$index]);
        $this->termDrafts = array_values($this->termDrafts);
        if ($removed !== '' && strcasecmp($removed, $this->currentTermName) === 0) {
            $this->currentTermName = trim((string) ($this->termDrafts[0]['name'] ?? ''));
        }
    }

    public function markTermCurrent(string $name): void
    {
        $name = trim($name);
        foreach ($this->termDrafts as $draft) {
            if (strcasecmp(trim((string) ($draft['name'] ?? '')), $name) === 0) {
                $this->currentTermName = trim((string) $draft['name']);
                $this->errorMessage = '';

                return;
            }
        }
        $this->errorMessage = 'That term is not in the list.';
    }

    public function addFeeDraft(): void
    {
        $name = trim($this->feeName);
        if ($name === '') {
            $this->errorMessage = 'Enter a fee name.';

            return;
        }
        if (! is_numeric($this->feeAmount) || (float) $this->feeAmount <= 0) {
            $this->errorMessage = 'Enter a fee amount greater than zero.';

            return;
        }
        if ($this->feeScope === 'class' && trim($this->feeClass) === '') {
            $this->errorMessage = 'Choose a class for this fee, or switch to Whole school.';

            return;
        }
        if (! $this->feeIsYearly && trim($this->feeTerm) === '' && $this->availableTermNames !== []) {
            $this->errorMessage = 'Choose an academic term, or mark this as a yearly fee.';

            return;
        }

        $this->feeDrafts[] = [
            'name' => $name,
            'amount' => (string) $this->feeAmount,
            'scope' => $this->feeScope === 'class' ? 'class' : 'whole_school',
            'class' => trim($this->feeClass),
            'term' => $this->feeIsYearly ? '' : trim($this->feeTerm),
            'is_yearly' => (bool) $this->feeIsYearly,
        ];
        $this->feeName = '';
        $this->feeAmount = '';
        $this->feeClass = '';
        $this->feeTerm = '';
        $this->feeIsYearly = false;
        $this->feeScope = 'whole_school';
        $this->errorMessage = '';
    }

    public function removeFeeDraft(int $index): void
    {
        if (! isset($this->feeDrafts[$index])) {
            return;
        }
        unset($this->feeDrafts[$index]);
        $this->feeDrafts = array_values($this->feeDrafts);
    }

    private function prefillDefaultTerms(): void
    {
        $year = $this->termPrefillYear();
        if ($this->termDrafts !== [] && ! $this->draftsUseDefaultDatesForAnotherYear($year)) {
            return;
        }

        $current = trim($this->currentTermName);
        $this->termDrafts = $this->defaultTermDrafts($year);
        $names = array_column($this->termDrafts, 'name');
        $this->currentTermName = in_array($current, $names, true) ? $current : 'Term 1';
    }

    /**
     * @return list<array{name: string, start: string, end: string}>
     */
    private function defaultTermDrafts(int $year): array
    {
        return [
            ['name' => 'Term 1', 'start' => "{$year}-02-03", 'end' => "{$year}-05-02"],
            ['name' => 'Term 2', 'start' => "{$year}-05-26", 'end' => "{$year}-08-29"],
            ['name' => 'Term 3', 'start' => "{$year}-09-22", 'end' => "{$year}-12-19"],
        ];
    }

    private function termPrefillYear(): int
    {
        $saved = AcademicYear::query()
            ->where('school_id', $this->school()->id)
            ->orderByDesc('id')
            ->first();

        if ($saved) {
            return $saved->calendarYear();
        }

        if ($this->academicYearStart !== '') {
            $parsed = strtotime($this->academicYearStart);
            if ($parsed !== false) {
                $year = (int) date('Y', $parsed);
                if ($year >= 1990) {
                    return $year;
                }
            }
        }

        return (int) now()->year;
    }

    private function draftsUseDefaultDatesForAnotherYear(int $year): bool
    {
        $shape = [
            ['Term 1', '02-03', '05-02'],
            ['Term 2', '05-26', '08-29'],
            ['Term 3', '09-22', '12-19'],
        ];

        if (count($this->termDrafts) !== count($shape)) {
            return false;
        }

        $differentYear = false;
        foreach ($shape as $index => [$name, $startMonthDay, $endMonthDay]) {
            $draft = $this->termDrafts[$index] ?? null;
            if (! is_array($draft) || strcasecmp(trim((string) ($draft['name'] ?? '')), $name) !== 0) {
                return false;
            }

            $start = (string) ($draft['start'] ?? '');
            $end = (string) ($draft['end'] ?? '');
            $startPattern = '/^(\d{4})-'.preg_quote($startMonthDay, '/').'$/';
            $endPattern = '/^(\d{4})-'.preg_quote($endMonthDay, '/').'$/';
            if (! preg_match($startPattern, $start, $startMatch) || ! preg_match($endPattern, $end, $endMatch)) {
                return false;
            }
            if ((int) $startMatch[1] !== $year || (int) $endMatch[1] !== $year) {
                $differentYear = true;
            }
        }

        return $differentYear;
    }

    private function ensureTermDraftsPrefill(): void
    {
        $school = $this->school();
        if (AcademicTerm::where('school_id', $school->id)->exists()) {
            // Already saved — keep drafts empty so Continue is a no-op via early exist check?
            // Rehydrate from DB so the admin can still see what was saved when revisiting.
            if ($this->termDrafts === []) {
                $this->termDrafts = AcademicTerm::where('school_id', $school->id)
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($t) => [
                        'name' => (string) $t->name,
                        'start' => optional($t->starts_on)->toDateString() ?? '',
                        'end' => optional($t->ends_on)->toDateString() ?? '',
                    ])
                    ->all();
                $current = AcademicTerm::where('school_id', $school->id)->where('status', 'current')->value('name');
                $this->currentTermName = (string) ($current ?: ($this->termDrafts[0]['name'] ?? ''));
            }

            return;
        }

        $this->prefillDefaultTerms();
    }

    private function refreshAvailableTermNames(): void
    {
        $names = AcademicTerm::where('school_id', $this->school()->id)
            ->orderBy('id')
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        if ($names === [] && $this->termDrafts !== []) {
            $names = array_values(array_filter(array_map(
                fn ($d) => trim((string) ($d['name'] ?? '')),
                $this->termDrafts
            )));
        }

        $this->availableTermNames = $names;
        if ($this->feeTerm === '' && $names !== []) {
            $this->feeTerm = (string) $names[0];
        }
    }

    private function freshTeacherEmail(?School $school = null): string
    {
        $slug = ($school ?? $this->school())->slug ?: 'school';

        return 'teacher.'.Str::lower(Str::random(6)).'@'.$slug.'.test';
    }

    private function saveStudents(School $school): void
    {
        if (trim($this->studentName) !== '') {
            $this->addStudentDraft();
            if ($this->errorMessage !== '') {
                throw ValidationException::withMessages(['studentName' => $this->errorMessage]);
            }
        }

        if ($this->studentDrafts === []) {
            return;
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            throw ValidationException::withMessages(['studentName' => 'Create an academic year first.']);
        }

        $links = StandardLink::with('section')->where('school_id', $school->id)->get();
        if ($links->isEmpty()) {
            throw ValidationException::withMessages(['studentName' => 'Add a class first.']);
        }

        $drafts = array_map(function ($draft) {
            return [
                'name' => trim((string) ($draft['name'] ?? '')),
                'class' => trim((string) ($draft['class'] ?? '')),
                'stream' => trim((string) ($draft['stream'] ?? '')),
                'parent' => trim((string) ($draft['parent'] ?? '')),
                'parent_phone' => trim((string) ($draft['parent_phone'] ?? $draft['phone'] ?? '')),
                'school_student_id' => trim((string) ($draft['school_student_id'] ?? '')),
                'lin' => trim((string) ($draft['lin'] ?? '')),
                'board_registration_number' => trim((string) ($draft['board_registration_number'] ?? '')),
                'gender' => trim((string) ($draft['gender'] ?? '')),
                'date_of_birth' => trim((string) ($draft['date_of_birth'] ?? '')),
                'row' => (int) ($draft['row'] ?? 0),
            ];
        }, $this->studentDrafts);

        $missing = app(OnboardingEngine::class)->collectMissingStreams($school, $year, $drafts);
        if ($missing['pairs'] !== []) {
            $this->studentMissingStreams = $missing['pairs'];
            $this->studentUploadSkips = $missing['skips'];
            $this->studentUploadReport = $this->formatStudentUploadReport(0, count($missing['skips']));
            $reason = (string) ($missing['skips'][0]['reason'] ?? "A stream doesn't exist yet.");
            throw ValidationException::withMessages(['studentName' => $reason]);
        }

        app(OnboardingEngine::class)->saveStudents($school, $year, $drafts);
        $this->studentMissingStreams = [];
        $this->studentUploadSkips = [];

        $overLimit = ToshiActionService::enforcePlanLimit($school->id, 'students');
        $this->overLimitNotice = $overLimit['success'] ? null : $overLimit['message'];

        $this->studentDrafts = [];
    }

    private function saveTerm(School $school): void
    {
        // Flush pending single-term form into drafts (does not advance by itself).
        if (trim($this->termName) !== '') {
            $this->addTermDraft();
            if ($this->errorMessage !== '') {
                throw ValidationException::withMessages(['termName' => $this->errorMessage]);
            }
        }

        if ($this->termDrafts === []) {
            if (AcademicTerm::where('school_id', $school->id)->exists()) {
                return;
            }
            throw ValidationException::withMessages(['termName' => 'Add at least one academic term.']);
        }

        $current = trim($this->currentTermName);
        $draftNames = array_map(fn ($d) => trim((string) ($d['name'] ?? '')), $this->termDrafts);
        if ($current === '' || ! in_array($current, $draftNames, true)) {
            throw ValidationException::withMessages([
                'currentTermName' => 'Choose which term is current before continuing.',
            ]);
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            throw ValidationException::withMessages(['termName' => 'Create an academic year first.']);
        }

        $payload = [];
        foreach ($this->termDrafts as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            $payload[] = [
                'name' => $name,
                'start' => (string) ($draft['start'] ?? ''),
                'end' => (string) ($draft['end'] ?? ''),
                'status' => $name === $current ? 'current' : 'next',
            ];
        }

        try {
            app(OnboardingEngine::class)->saveTerms($school, $year, $payload);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['termName' => collect($e->errors())->flatten()->first()]);
        }
    }

    private function saveFee(School $school): void
    {
        if (trim($this->feeName) !== '' && trim($this->feeAmount) !== '') {
            $this->addFeeDraft();
            if ($this->errorMessage !== '') {
                throw ValidationException::withMessages(['feeName' => $this->errorMessage]);
            }
        }

        if ($this->feeDrafts === []) {
            if (FeesCategories::where('school_id', $school->id)->exists()) {
                return;
            }
            throw ValidationException::withMessages(['feeName' => 'Add at least one fee.']);
        }

        $payload = [];
        foreach ($this->feeDrafts as $index => $draft) {
            $row = [
                'name' => trim((string) ($draft['name'] ?? '')),
                'amount' => (float) ($draft['amount'] ?? 0),
                'row' => $index + 1,
            ];
            $scope = (string) ($draft['scope'] ?? 'whole_school');
            if ($scope === 'class') {
                $row['class'] = trim((string) ($draft['class'] ?? ''));
            }
            if (empty($draft['is_yearly'])) {
                $term = trim((string) ($draft['term'] ?? ''));
                if ($term !== '') {
                    $row['term'] = $term;
                }
            }
            $payload[] = $row;
        }

        try {
            app(OnboardingEngine::class)->saveFees($school, $payload);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['feeName' => collect($e->errors())->flatten()->first()]);
        }

        $this->feeDrafts = [];
    }

    /**
     * Older tests and callers still use this name. It does not send a message.
     * The admin sends the code to KlassApp.
     */
    public function sendWhatsAppVerificationCode(): void
    {
        $this->issueWhatsAppVerificationCode();
    }

    /**
     * Show a code and a wa.me link. Nothing is sent to the admin.
     */
    public function issueWhatsAppVerificationCode(): void
    {
        $this->errorMessage = '';
        $this->whatsappOtpStatus = '';
        $this->whatsappSendFailed = false;
        $phone = $this->normaliseOptionalPhone($this->whatsappPhone, true);
        if ($phone === null) {
            $this->whatsappOtpDisplay = '';
            $this->whatsappVerifyUrl = '';
            $this->whatsappVerified = false;

            return;
        }
        if ($phone === '') {
            $this->whatsappOtpDisplay = '';
            $this->whatsappVerifyUrl = '';
            $this->errorMessage = PhoneCountry::error($this->phoneCountryName(), true);

            return;
        }
        $this->whatsappPhone = $phone;

        $result = app(WhatsAppUserInitiatedVerification::class)->issue(
            (int) Auth::id(),
            $phone,
            (string) request()->ip(),
        );

        if (! $result['ok']) {
            $this->whatsappOtpDisplay = '';
            $this->whatsappVerifyUrl = '';
            $this->whatsappVerified = false;
            $this->whatsappSendFailed = true;
            $this->errorMessage = $result['message'];
            $this->whatsappOtpStatus = $result['message'];

            return;
        }

        $this->whatsappPhone = $result['phone'];
        $this->whatsappVerified = false;
        $this->whatsappOtpInput = '';
        $this->whatsappOtpDisplay = (string) $result['code'];
        $this->whatsappVerifyUrl = (string) $result['url'];
        $this->whatsappOtpStatus = $result['message'];
    }

    /**
     * wire:poll. Moves on once the inbound message has verified this number.
     * An expired code stays on the step with a cause and a fix.
     */
    public function pollWhatsAppVerification(): void
    {
        if ($this->currentKey() !== 'whatsapp_verify' || $this->whatsappVerified) {
            return;
        }

        $status = app(WhatsAppUserInitiatedVerification::class)->statusFor(
            (int) Auth::id(),
            $this->whatsappPhone,
        );

        if ($status['verified']) {
            $this->whatsappPhone = $status['phone'];
            $this->whatsappVerified = true;
            $this->whatsappOtpDisplay = '';
            $this->whatsappVerifyUrl = '';
            $this->errorMessage = '';
            $this->whatsappOtpStatus = 'Verified. You can continue.';
            $this->next();

            return;
        }

        if ($status['expired']) {
            $this->whatsappOtpDisplay = '';
            $this->whatsappVerifyUrl = '';
            $this->whatsappVerified = false;
            $this->errorMessage = WhatsAppUserInitiatedVerification::EXPIRED_MESSAGE;
            $this->whatsappOtpStatus = WhatsAppUserInitiatedVerification::EXPIRED_MESSAGE;
        }
    }

    private function whatsappAlreadyVerified(): bool
    {
        $phone = $this->normaliseOptionalPhone($this->whatsappPhone, true, false);
        if (! is_string($phone) || $phone === '') {
            return false;
        }

        $verified = WhatsAppUser::query()
            ->where('user_id', Auth::id())
            ->where('phone', $phone)
            ->whereNotNull('verified_at')
            ->exists();

        if ($verified) {
            $this->whatsappVerified = true;
        }

        return $verified;
    }

    private function saveWhatsApp(School $school): void
    {
        $phone = $this->normaliseOptionalPhone($this->whatsappPhone, true);
        if ($phone === null) {
            throw ValidationException::withMessages([
                'whatsappPhone' => $this->errorMessage,
            ]);
        }
        if ($phone === '') {
            throw ValidationException::withMessages([
                'whatsappPhone' => PhoneCountry::error($this->phoneCountryName(), true),
            ]);
        }
        $this->whatsappPhone = $phone;

        $existing = WhatsAppUser::where('user_id', Auth::id())->whereNotNull('verified_at')->first();
        if ($existing && $existing->phone === $phone) {
            $this->whatsappVerified = true;

            return;
        }

        // verified_at is written by the inbound webhook, not by this form.
        $this->whatsappVerified = false;
    }

    private function savePlan(School $school): void
    {
        if (Plan::query()->where('is_active', 1)->doesntExist()) {
            // Prefer the in-step empty alert; next() short-circuits before this when empty.
            // Keep a guard for any direct persist path without duplicating UX intent.
            throw ValidationException::withMessages([
                'plan' => 'No plans are available yet. Contact support.',
            ]);
        }

        $this->defaultSelectedPlan();
        if (! $this->selectedPlanId) {
            throw ValidationException::withMessages(['plan' => 'Select a plan to continue.']);
        }

        app(OnboardingEngine::class)->savePlan($school, (int) $this->selectedPlanId, skipCompletionCheck: false, userId: Auth::id());
    }
}
