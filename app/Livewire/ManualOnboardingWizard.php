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
use App\Models\Teacherlink;
use App\Models\User;

use App\Models\WhatsAppUser;
use App\Services\ClassStructureService;
use App\Services\ClassTeacherInviteService;
use App\Services\OnboardingNameListExtractor;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use App\Services\SchoolCategorySeeder;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;

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

    public bool $finished = false;

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
     * Names already in DB for the subjects step (auto-seeded or previously saved).
     *
     * @var list<string>
     */
    public array $existingSubjectNames = [];

    public string $teacherName = '';

    public string $teacherEmail = '';

    public string $teacherPhone = '';

    /** @var list<array{name: string, email: string, phone: string}> */
    public array $teacherDrafts = [];

    public string $teacherPaste = '';

    /** @var mixed */
    public $teacherUpload = null;

    public string $studentName = '';

    public string $studentClass = '';

    public string $studentStream = '';

    public string $studentParent = '';

    public string $studentParentPhone = '';

    public string $studentSchoolStudentId = '';

    public string $studentBoardRegNumber = '';

    /** @var list<array{name: string, class: string, stream: string, parent: string, parent_phone: string, school_student_id: string, board_registration_number: string}> */
    public array $studentDrafts = [];

    public string $studentPaste = '';

    /** @var mixed */
    public $studentUpload = null;

    public string $termName = 'Term 1';

    public string $termStartsOn = '';

    public string $termEndsOn = '';

    public string $feeName = 'Tuition';

    public string $feeAmount = '100000';

    public string $whatsappPhone = '';

    /** OTP entered by the admin (wizard WhatsApp step). */
    public string $whatsappOtpInput = '';

    /**
     * Code shown in the wizard UI (parity with Toshi chat always displaying the OTP).
     * Not used for verification — the authoritative code lives in session.
     */
    public string $whatsappOtpDisplay = '';

    public string $whatsappOtpStatus = '';

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
        $this->termStartsOn = now()->startOfYear()->toDateString();
        $this->termEndsOn = now()->startOfYear()->addMonths(4)->toDateString();
        $this->teacherEmail = $this->freshTeacherEmail($school);

        // Land on the first incomplete step (including optional teachers/students).
        // Skipping optional steps on mount jumped users from Teachers → Terms on reload
        // and made Previous appear to "go forward" after a remount mid-flow.
        $next = OnboardingStepsService::nextIncompleteStep($school->fresh(), Auth::id());
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
            foreach ($this->steps as $i => $step) {
                if ($step['key'] === $next['key']) {
                    $this->setStepIndex($i);
                    break;
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

        $this->teacherDrafts[] = [
            'name' => $name,
            'email' => $email,
            'phone' => trim($this->teacherPhone),
        ];
        $this->teacherName = '';
        $this->teacherPhone = '';
        $this->teacherEmail = $this->freshTeacherEmail();
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
            ];
        }
        $this->teacherPaste = '';
        $this->errorMessage = $names === [] ? 'Could not find any names in the paste list.' : '';
    }

    public function updatedTeacherUpload(): void
    {
        $this->ingestUpload('teacher');
    }

    public function addStudentDraft(): void
    {
        $name = trim($this->studentName);
        if ($name === '') {
            $this->errorMessage = 'Enter a student name.';

            return;
        }

        $this->studentDrafts[] = [
            'name' => $name,
            'class' => trim($this->studentClass),
            'stream' => trim($this->studentStream),
            'parent' => trim($this->studentParent),
            'parent_phone' => trim($this->studentParentPhone),
            'school_student_id' => trim($this->studentSchoolStudentId),
            'board_registration_number' => trim($this->studentBoardRegNumber),
        ];
        $this->studentName = '';
        $this->studentClass = '';
        $this->studentStream = '';
        $this->studentParent = '';
        $this->studentParentPhone = '';
        $this->studentSchoolStudentId = '';
        $this->studentBoardRegNumber = '';
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
                'class' => $this->studentClass ?: $this->className,
                'stream' => trim($this->studentStream),
                'parent' => '',
                'parent_phone' => '',
                'school_student_id' => '',
                'board_registration_number' => '',
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

        if ($kind === 'teacher') {
            foreach ($rows as $row) {
                $email = trim((string) ($row['email'] ?? ''));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $email = 'teacher.'.Str::lower(Str::random(6)).'@'.($this->school()->slug ?: 'school').'.test';
                }
                $this->teacherDrafts[] = [
                    'name' => $row['name'],
                    'email' => $email,
                    'phone' => (string) ($row['phone'] ?? ''),
                ];
            }
            $this->teacherUpload = null;
        } else {
            foreach ($rows as $row) {
                $this->studentDrafts[] = [
                    'name' => $row['name'],
                    'class' => (string) ($row['class'] ?? ''),
                    'stream' => (string) ($row['stream'] ?? ''),
                    'parent' => (string) ($row['parent'] ?? ''),
                    'parent_phone' => (string) ($row['parent_phone'] ?? ''),
                    'school_student_id' => (string) ($row['school_student_id'] ?? ''),
                    'board_registration_number' => (string) ($row['board_registration_number'] ?? ''),
                ];
            }
            $this->studentUpload = null;
        }

        $this->errorMessage = '';
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

        // Optional steps with an empty draft list = skip (parity with Toshi).
        if (($step['key'] ?? '') === 'teachers' && $this->teacherDrafts === [] && trim($this->teacherName) === '') {
            $this->skipOptionalStep();

            return;
        }
        if (($step['key'] ?? '') === 'students' && $this->studentDrafts === [] && trim($this->studentName) === '') {
            $this->skipOptionalStep();

            return;
        }

        try {
            $this->persistCurrentStep($step['key']);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Please check the form.';

            return;
        } catch (\Throwable $e) {
            report($e);
            // Never surface raw SQL / connection strings to the wizard UI.
            $this->errorMessage = 'Could not save this step. Please try again.';

            return;
        }

        if (! in_array($step['key'], $this->completedDuringSession, true)) {
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
            if (! $this->steps[$i]['is_complete']) {
                $this->setStepIndex($i);

                return;
            }
        }

        foreach ($this->steps as $i => $candidate) {
            if (($candidate['key'] ?? '') === 'review') {
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

        if (OnboardingStepsService::hasBlockingIncompleteSteps($this->school()->fresh(), Auth::id())) {
            $this->errorMessage = 'Finish the remaining setup steps before creating your school.';

            return;
        }

        $this->finished = true;
        $this->returnToStepIndex = null;
        $this->returnToStepKey = null;
        $this->notifyToshiOnboardingFinished();
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
            $this->refreshExistingSubjects();
        }
    }

    private function refreshExistingSubjects(): void
    {
        $this->existingSubjectNames = Subject::query()
            ->where('school_id', $this->school()->id)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Checkpoint land via setStepIndex does not run prepareStepFields — clear the
        // default "Mathematics" so Next is a true no-op when subjects already exist.
        if ($this->existingSubjectNames !== []) {
            $this->subjectName = '';
        }
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

    private function school(): School
    {
        return School::query()->findOrFail(Auth::user()->school_id);
    }

    private function refreshSteps(): void
    {
        $checklist = OnboardingStepsService::steps($this->school()->fresh(), Auth::id());

        // Wizard-only synthetic review step — not part of OnboardingStepsService /
        // Toshi "Completing Setup" checklist (would never complete otherwise).
        $checklist[] = [
            'key' => 'review',
            'label' => 'Review & confirm',
            'icon' => '📋',
            'is_complete' => false,
            'route' => null,
        ];

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
        $subjects = Subject::where('school_id', $sid)->pluck('name')->filter()->values();
        $teachers = Teacherlink::with('teacher.userprofile')
            ->where('school_id', $sid)
            ->get()
            ->map(fn ($tl) => $tl->teacher?->displayName ?: $tl->teacher?->name)
            ->filter()
            ->unique()
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
            ['key' => 'subjects', 'label' => 'Subjects', 'icon' => '📖', 'value' => $subjects->isEmpty() ? '—' : $subjects->implode(', ')],
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
            'teachers' => (function () use ($sid) {
                $link = Teacherlink::with('teacher')->where('school_id', $sid)->first();
                if ($link?->teacher) {
                    $this->teacherName = (string) $link->teacher->name;
                    $this->teacherEmail = (string) ($link->teacher->email ?: $this->teacherEmail);
                    $this->teacherPhone = (string) ($link->teacher->mobile_no ?: '');
                }
            })(),
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
            $result = app(ClassStructureService::class)->addStream($school, $year, $section, $label);
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

        $result = ClassTeacherInviteService::invite(Auth::user(), $link, [
            'email' => $draft['email'] ?? '',
            'existing_teacher_id' => $draft['existing_teacher_id'] ?? '',
            'name' => $draft['name'] ?? '',
            'phone' => $draft['phone'] ?? '',
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
        $name = trim($this->subjectName);

        // Already seeded/saved and add field left blank → Next is a review no-op.
        if (Subject::where('school_id', $school->id)->exists() && $name === '') {
            return;
        }

        if ($name === '') {
            throw ValidationException::withMessages(['subjectName' => 'Enter a subject name.']);
        }

        // Adding another subject on top of an existing seed is allowed.

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            throw ValidationException::withMessages(['subjectName' => 'Add a class first.']);
        }

        // Use the wizard's className to scope the subject to the right section
        $className = trim($this->className);

        try {
            app(OnboardingEngine::class)->saveSubjects($school, $year, [
                $className => [$name],
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['subjectName' => collect($e->errors())->flatten()->first()]);
        }
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
        $link = StandardLink::where('school_id', $school->id)->first();
        // Prefer a subject on the same section as the class link so Teacherlink rows are coherent.
        $subject = null;
        if ($link) {
            $subject = Subject::where('school_id', $school->id)
                ->where('section_id', $link->section_id)
                ->first();
        }
        $subject ??= Subject::where('school_id', $school->id)->first();
        if (! $year || ! $link || ! $subject) {
            throw ValidationException::withMessages(['teacherName' => 'Add class and subject first.']);
        }

        $drafts = array_map(function ($draft) use ($link, $subject) {
            return [
                'name'             => trim((string) ($draft['name'] ?? '')),
                'email'            => trim((string) ($draft['email'] ?? '')),
                'phone'            => trim((string) ($draft['phone'] ?? '')),
                'standardLink_id'  => $link->id,
                'subject_id'       => $subject->id,
            ];
        }, $this->teacherDrafts);

        $result = app(OnboardingEngine::class)->saveTeachers($school, $year, $drafts);
        if (($result['created'] ?? []) === []) {
            $reason = collect($result['skipped'] ?? [])->pluck('reason')->filter()->first()
                ?: 'Could not save teachers. Check the list and try again.';
            throw ValidationException::withMessages(['teacherName' => $reason]);
        }

        $this->teacherDrafts = [];
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
                'school_student_id' => trim((string) ($draft['school_student_id'] ?? '')),
                'board_registration_number' => trim((string) ($draft['board_registration_number'] ?? '')),
            ];
        }, $this->studentDrafts);

        app(OnboardingEngine::class)->saveStudents($school, $year, $drafts);

        $this->studentDrafts = [];
    }

    private function saveTerm(School $school): void
    {
        if (AcademicTerm::where('school_id', $school->id)->exists()) {
            return;
        }

        $name = trim($this->termName);
        if ($name === '') {
            throw ValidationException::withMessages(['termName' => 'Enter a term name.']);
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            throw ValidationException::withMessages(['termName' => 'Create an academic year first.']);
        }

        $this->validate([
            'termStartsOn' => 'required|date',
            'termEndsOn' => 'required|date|after:termStartsOn',
        ]);

        try {
            app(OnboardingEngine::class)->saveTerms($school, $year, [
                ['name' => $name, 'start' => $this->termStartsOn, 'end' => $this->termEndsOn],
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['termName' => collect($e->errors())->flatten()->first()]);
        }
    }

    private function saveFee(School $school): void
    {
        if (FeesCategories::where('school_id', $school->id)->exists()) {
            return;
        }

        $name = trim($this->feeName);
        if ($name === '') {
            throw ValidationException::withMessages(['feeName' => 'Enter a fee name.']);
        }

        $amount = (float) $this->feeAmount;
        if ($amount <= 0) {
            throw ValidationException::withMessages(['feeAmount' => 'Enter a fee amount greater than zero.']);
        }

        // Do not reuse $this->className from the Classes step — that silently scoped
        // Tuition to one class (e.g. leftover "P1") and skipped school-wide / tier rows.
        // School-wide save creates one FeesCategories row per Standard (section_id null)
        // so labeledName() can show Nursery/Primary/O'Level/A'Level like Toshi.
        try {
            app(OnboardingEngine::class)->saveFees($school, [
                ['name' => $name, 'amount' => $amount],
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['feeName' => collect($e->errors())->flatten()->first()]);
        }
    }

    /**
     * Send a 6-digit OTP via the same WhatsAppOnboardingOtpService Toshi uses.
     * Always surfaces the code in the wizard UI so onboarding is never blocked when
     * the Meta API is unconfigured (same policy as AgentToshi::sendWhatsAppOtp).
     */
    public function sendWhatsAppVerificationCode(): void
    {
        $this->errorMessage = '';
        $this->whatsappOtpStatus = '';
        $phone = trim($this->whatsappPhone);
        if ($phone === '') {
            $this->errorMessage = 'Enter your WhatsApp number (+256…) before sending a code.';

            return;
        }

        $otpService = app(WhatsAppOnboardingOtpService::class);
        $otp = $otpService->generateCode();

        session([
            'wizard_wa_otp.code' => $otp,
            'wizard_wa_otp.phone' => $phone,
            'wizard_wa_otp.expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $this->whatsappVerified = false;
        $this->whatsappOtpInput = '';
        $this->whatsappOtpDisplay = $otp;
        $this->whatsappOtpStatus = "Your verification code: {$otp}";

        $delivery = $otpService->deliver($phone, $otp);
        if ($delivery['sent']) {
            $this->whatsappOtpStatus .= ' — also sent to WhatsApp.';
        } else {
            $this->whatsappOtpStatus .= ' — enter this code below (WhatsApp API may be unavailable).';
        }
    }

    public function verifyWhatsAppCode(): void
    {
        $this->errorMessage = '';
        $expected = (string) session('wizard_wa_otp.code', '');
        $sessionPhone = (string) session('wizard_wa_otp.phone', '');
        $expiresAt = (int) session('wizard_wa_otp.expires_at', 0);

        if ($expected === '' || $sessionPhone === '') {
            $this->errorMessage = 'Send a verification code first.';

            return;
        }

        if ($expiresAt > 0 && now()->timestamp > $expiresAt) {
            $this->errorMessage = 'That code has expired. Send a new one.';
            $this->whatsappVerified = false;

            return;
        }

        if (trim($this->whatsappPhone) !== '' && trim($this->whatsappPhone) !== $sessionPhone) {
            $this->errorMessage = 'Phone number changed after the code was sent. Send a new code.';
            $this->whatsappVerified = false;

            return;
        }

        $otpService = app(WhatsAppOnboardingOtpService::class);
        if (! $otpService->matches($expected, $this->whatsappOtpInput)) {
            $this->errorMessage = "That code doesn't match. Try again or send a new code.";
            $this->whatsappVerified = false;

            return;
        }

        $this->whatsappPhone = $sessionPhone;
        $this->whatsappVerified = true;
        $this->whatsappOtpStatus = "WhatsApp verified for {$this->whatsappPhone}.";
        $this->whatsappOtpDisplay = '';
        session()->forget(['wizard_wa_otp.code', 'wizard_wa_otp.phone', 'wizard_wa_otp.expires_at']);
    }

    private function saveWhatsApp(School $school): void
    {
        $phone = trim($this->whatsappPhone);
        if ($phone === '') {
            throw ValidationException::withMessages(['whatsappPhone' => 'Enter your WhatsApp number (+256…).']);
        }

        $existing = WhatsAppUser::where('user_id', Auth::id())->whereNotNull('verified_at')->first();
        if ($existing && $existing->phone === $phone) {
            return;
        }

        if (! $this->whatsappVerified) {
            throw ValidationException::withMessages([
                'whatsappOtpInput' => 'Verify the code sent to your WhatsApp before continuing.',
            ]);
        }

        $result = app(OnboardingEngine::class)->saveWhatsApp($school, Auth::id(), $phone);

        if ($result['skipped'] !== null) {
            throw ValidationException::withMessages([
                'whatsappPhone' => $result['skipped'][0]['reason'],
            ]);
        }
    }

    private function savePlan(School $school): void
    {
        $this->defaultSelectedPlan();
        if (! $this->selectedPlanId) {
            throw ValidationException::withMessages(['plan' => 'Select a plan to continue.']);
        }

        app(OnboardingEngine::class)->savePlan($school, (int) $this->selectedPlanId, skipCompletionCheck: false, userId: Auth::id());
    }
}
