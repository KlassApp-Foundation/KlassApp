<?php

namespace App\Livewire;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\OnboardingNameListExtractor;
use App\Services\OnboardingStepsService;
use App\Services\StudentIdGeneratorService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    public string $curriculum = 'uneb';

    public string $countryName = 'Uganda';

    public string $ministryCode = '';

    public string $unebCenterNumber = '';

    public string $academicYearDescription = 'Current Academic Year';

    public string $academicYearStart = '';

    public string $academicYearEnd = '';

    public string $className = 'P1';

    public string $subjectName = 'Mathematics';

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

    /** @var list<array{name: string, class: string, stream: string, parent: string, parent_phone: string}> */
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
        $this->teacherEmail = 'teacher.'.Str::lower(Str::random(6)).'@'.($school->slug ?: 'school').'.test';

        // Land on first incomplete step
        foreach ($this->steps as $i => $step) {
            if (! $step['is_complete']) {
                $this->setStepIndex($i);
                break;
            }
        }

        // Blocking checklist complete → land on the synthetic review screen (do not auto-finish).
        // Teachers/students are optional and do not block review.
        if (! OnboardingStepsService::hasBlockingIncompleteSteps($school->fresh(), Auth::id())) {
            $this->completedDuringSession = collect($this->steps)
                ->where('key', '!=', 'review')
                ->where('is_complete', true)
                ->pluck('key')
                ->all();
            $this->setStepIndex($this->reviewStepIndex());
            $this->buildReviewSummary();
            $this->finished = false;
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

    public function addTeacherDraft(): void
    {
        $name = trim($this->teacherName);
        if ($name === '') {
            $this->errorMessage = 'Enter a teacher name.';

            return;
        }

        $email = trim($this->teacherEmail);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errorMessage = 'Enter a valid teacher email.';

            return;
        }

        $this->teacherDrafts[] = [
            'name' => $name,
            'email' => $email,
            'phone' => trim($this->teacherPhone),
        ];
        $this->teacherName = '';
        $this->teacherPhone = '';
        $this->teacherEmail = 'teacher.'.Str::lower(Str::random(6)).'@'.($this->school()->slug ?: 'school').'.test';
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
                'email' => 'teacher.'.Str::lower(Str::random(6)).'@'.($this->school()->slug ?: 'school').'.test',
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
        ];
        $this->studentName = '';
        $this->studentStream = '';
        $this->studentParent = '';
        $this->studentParentPhone = '';
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
                'stream' => '',
                'parent' => '',
                'parent_phone' => '',
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
            // Still visit incomplete optional steps in sequence when advancing normally
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
                'title' => 'Add more classes',
                'body' => 'You started with '.$this->className.'. Add streams or higher levels next.',
                'href' => url('/admin/standard/create'),
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

        if ($this->stepIndex > 0) {
            $this->setStepIndex($this->stepIndex - 1);
            if ($this->currentKey() === 'review') {
                $this->buildReviewSummary();
            }
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
        $teachers = Teacherlink::with('teacher')
            ->where('school_id', $sid)
            ->get()
            ->map(fn ($tl) => $tl->teacher?->name)
            ->filter()
            ->unique()
            ->values();
        $students = User::query()
            ->where('school_id', $sid)
            ->where('usergroup_id', 6)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'exit');
            })
            ->orderBy('id')
            ->limit(20)
            ->pluck('name')
            ->filter()
            ->values();
        $studentCount = OnboardingStepsService::countActiveStudents($sid);
        $terms = AcademicTerm::where('school_id', $sid)->get();
        $fees = FeesCategories::where('school_id', $sid)->get();
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
            : $fees->map(fn ($f) => $f->name.' ('.number_format((float) $f->amount).')')->implode(', ');

        $planValue = $plan
            ? ($plan->display_name ?: ucfirst((string) $plan->name))
            : '—';

        $this->reviewSummary = [
            ['key' => 'school_name', 'label' => 'School name', 'icon' => '🏫', 'value' => (string) ($school->name ?: '—')],
            ['key' => 'curriculum', 'label' => 'Curriculum', 'icon' => '📚', 'value' => strtoupper((string) ($school->curriculum ?: '—'))],
            ['key' => 'country', 'label' => 'Country', 'icon' => '🌍', 'value' => (string) ($school->registration_country ?: '—')],
            ['key' => 'emis', 'label' => 'EMIS / Ministry code', 'icon' => '🔢', 'value' => $emis !== '' ? $emis : '—'],
            ['key' => 'uneb_center', 'label' => 'UNEB centre', 'icon' => '🎓', 'value' => $unebDisplay],
            ['key' => 'academic_year', 'label' => 'Academic year', 'icon' => '📅', 'value' => $yearValue],
            ['key' => 'standards', 'label' => 'Classes', 'icon' => '🏷️', 'value' => $classNames->isEmpty() ? '—' : $classNames->implode(', ')],
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
                $link = StandardLink::with('section')->where('school_id', $sid)->first();
                if ($link?->section?->name) {
                    $this->className = (string) $link->section->name;
                }
            })(),
            'subjects' => (function () use ($sid) {
                $subject = Subject::where('school_id', $sid)->first();
                if ($subject) {
                    $this->subjectName = (string) $subject->name;
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
        $this->curriculum = $school->curriculum ?: 'uneb';
        $this->countryName = $school->registration_country ?: 'Uganda';
        $this->ministryCode = (string) ($school->ministry_code ?: '');
        $this->unebCenterNumber = (string) ($school->uneb_center_number ?: '');
    }

    private function persistCurrentStep(string $key): void
    {
        $school = $this->school();

        match ($key) {
            'school_name' => $this->saveSchoolName($school),
            'curriculum' => $this->saveCurriculum($school),
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
        $name = trim($this->schoolName);
        if ($name === '' || OnboardingStepsService::isPlaceholderSchoolName($name)) {
            throw ValidationException::withMessages(['schoolName' => 'Enter your real school name.']);
        }

        $school->name = $name;
        $school->save();
    }

    private function saveCurriculum(School $school): void
    {
        $curriculum = strtolower(trim($this->curriculum));
        if ($curriculum === '') {
            throw ValidationException::withMessages(['curriculum' => 'Choose a curriculum.']);
        }

        $school->curriculum = $curriculum;
        $school->save();
    }

    private function saveCountry(School $school): void
    {
        $country = trim($this->countryName);
        if ($country === '') {
            throw ValidationException::withMessages(['countryName' => 'Choose a country.']);
        }

        OnboardingStepsService::persistCountry($school, $country);
    }

    private function saveEmis(School $school): void
    {
        $code = trim($this->ministryCode);
        if ($code === '') {
            throw ValidationException::withMessages(['ministryCode' => 'Enter your EMIS / ministry code.']);
        }

        $school->ministry_code = $code;
        $school->save();
    }

    private function saveUneb(School $school): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('schools', 'uneb_center_number')) {
            return;
        }

        // Empty string marks “asked/skipped”; null means not asked.
        $school->uneb_center_number = trim($this->unebCenterNumber);
        $school->save();
    }

    private function saveAcademicYear(School $school): void
    {
        if (AcademicYear::where('school_id', $school->id)->exists()) {
            return;
        }

        $this->validate([
            'academicYearStart' => 'required|date',
            'academicYearEnd' => 'required|date|after:academicYearStart',
        ]);

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => date('Y', strtotime($this->academicYearStart)),
            'description' => $this->academicYearDescription ?: 'Current Academic Year',
            'start_date' => $this->academicYearStart,
            'end_date' => $this->academicYearEnd,
            'status' => 1,
        ]);

        // Observer also forgets; keep an explicit forget so a cached null from the
        // pre-AY empty-state dashboard cannot stick across this wizard step.
        Cache::forget('academic_year_for_school_'.$school->id);
    }

    private function saveClass(School $school): void
    {
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

        $phase = Standard::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'primary'],
            ['order' => 1, 'status' => '1']
        );

        $section = Section::firstOrCreate(
            ['school_id' => $school->id, 'name' => $name],
            ['status' => '1']
        );

        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);
    }

    private function saveSubject(School $school): void
    {
        if (Subject::where('school_id', $school->id)->exists()) {
            return;
        }

        $name = trim($this->subjectName);
        if ($name === '') {
            throw ValidationException::withMessages(['subjectName' => 'Enter a subject name.']);
        }

        $link = StandardLink::where('school_id', $school->id)->first();
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $link || ! $year) {
            throw ValidationException::withMessages(['subjectName' => 'Add a class first.']);
        }

        Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $link->standard_id,
            'section_id' => $link->section_id,
            'name' => $name,
        ]);
    }

    private function saveTeachers(School $school): void
    {
        // Flush a pending single-add form into the draft list.
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
        $subject = Subject::where('school_id', $school->id)->first();
        if (! $year || ! $link || ! $subject) {
            throw ValidationException::withMessages(['teacherName' => 'Add class and subject first.']);
        }

        foreach ($this->teacherDrafts as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            $email = trim((string) ($draft['email'] ?? ''));
            if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (User::where('school_id', $school->id)->where('email', $email)->exists()) {
                $email = 'teacher.'.Str::lower(Str::random(6)).'@'.($school->slug ?: 'school').'.test';
            }

            $teacher = User::create([
                'school_id' => $school->id,
                'usergroup_id' => 5,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(16)),
                'status' => 'active',
                'email_verified' => 1,
                'mobile_no' => trim((string) ($draft['phone'] ?? '')) ?: null,
            ]);

            Teacherlink::firstOrCreate([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standardLink_id' => $link->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
            ]);
        }

        $this->teacherDrafts = [];
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
        $links = StandardLink::with('section')->where('school_id', $school->id)->get();
        $firstLink = $links->first();
        if (! $year || ! $firstLink) {
            throw ValidationException::withMessages(['studentName' => 'Add a class first.']);
        }

        foreach ($this->studentDrafts as $index => $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $email = 'student.'.($index + 1).'.'.Str::lower(Str::random(4)).'@'.($school->slug ?: 'school').'.test';
            $student = User::create([
                'school_id' => $school->id,
                'usergroup_id' => 6,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(16)),
                'status' => 'active',
                'email_verified' => 1,
            ]);

            Userprofile::create([
                'school_id' => $school->id,
                'user_id' => $student->id,
                'usergroup_id' => 6,
                'firstname' => $name,
                'lastname' => '',
                'status' => 'active',
            ]);

            $klassappId = StudentIdGeneratorService::nextForStudent($student);

            $targetLink = $firstLink;
            $studentClass = trim((string) ($draft['class'] ?? ''));
            if ($studentClass !== '') {
                $matched = $links->first(function ($link) use ($studentClass) {
                    $sectionName = (string) ($link->section?->name ?? '');

                    return strcasecmp($sectionName, $studentClass) === 0
                        || str_contains(strtolower($sectionName), strtolower($studentClass));
                });
                if ($matched) {
                    $targetLink = $matched;
                }
            }

            StudentAcademic::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'user_id' => $student->id,
                'standardLink_id' => $targetLink->id,
                'klassapp_student_id' => $klassappId,
            ]);
        }

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

        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'starts_on' => $this->termStartsOn,
            'ends_on' => $this->termEndsOn,
            'status' => 'current',
        ]);
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

        $phase = Standard::where('school_id', $school->id)->first();
        if (! $phase) {
            throw ValidationException::withMessages(['feeName' => 'Add a class first.']);
        }

        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $phase->id,
            'name' => $name,
            'amount' => $amount,
        ]);
    }

    private function saveWhatsApp(School $school): void
    {
        if (WhatsAppUser::where('user_id', Auth::id())->exists()) {
            return;
        }

        $phone = trim($this->whatsappPhone);
        if ($phone === '') {
            throw ValidationException::withMessages(['whatsappPhone' => 'Enter your WhatsApp number (+256…).']);
        }

        if (WhatsAppUser::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'whatsappPhone' => 'This WhatsApp number is already registered',
            ]);
        }

        try {
            WhatsAppUser::updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'phone' => $phone,
                    'school_id' => $school->id,
                    'opted_in' => true,
                    'verified_at' => now(),
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'whatsappPhone' => 'This WhatsApp number is already registered',
            ]);
        }
    }

    private function savePlan(School $school): void
    {
        foreach (OnboardingStepsService::incompleteSteps($school->fresh(), Auth::id()) as $step) {
            if (in_array($step['key'], OnboardingStepsService::OPTIONAL_STEPS, true)) {
                continue;
            }
            if ($step['key'] !== 'plan_selection') {
                throw ValidationException::withMessages(['plan' => 'Finish the earlier setup steps before choosing a plan.']);
            }
        }

        $this->defaultSelectedPlan();
        if (! $this->selectedPlanId) {
            throw ValidationException::withMessages(['plan' => 'Select a plan to continue.']);
        }

        $plan = Plan::query()->where('id', $this->selectedPlanId)->where('is_active', 1)->first();
        if (! $plan) {
            throw ValidationException::withMessages(['plan' => 'That plan is not available.']);
        }

        $existing = CurrentPlan::where('school_id', $school->id)->first();
        if ($existing) {
            $existing->plan_id = $plan->id;
            $existing->status = 'running';
            $existing->save();

            return;
        }

        // Visible confirmation step — no payment gate. Persist the chosen plan.
        CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);
    }
}
