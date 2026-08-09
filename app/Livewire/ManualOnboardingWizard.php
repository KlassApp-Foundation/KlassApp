<?php

namespace App\Livewire;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\FreeTierPlanService;
use App\Services\OnboardingStepsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Wave 3 manual onboarding wizard shell.
 * Step sequence + completion rules come from OnboardingStepsService;
 * persistence writes the same models existing admin forms use.
 */
class ManualOnboardingWizard extends Component
{
    public int $stepIndex = 0;

    public bool $finished = false;

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

    public string $termName = 'Term 1';

    public string $termStartsOn = '';

    public string $termEndsOn = '';

    public string $feeName = 'Tuition';

    public string $feeAmount = '100000';

    public string $whatsappPhone = '';

    public string $errorMessage = '';

    public function mount(): void
    {
        $school = $this->school();
        $user = Auth::user();

        $this->refreshSteps();
        $this->hydrateFieldsFromSchool($school);

        $this->academicYearStart = now()->startOfYear()->toDateString();
        $this->academicYearEnd = now()->endOfYear()->toDateString();
        $this->termStartsOn = now()->startOfYear()->toDateString();
        $this->termEndsOn = now()->startOfYear()->addMonths(4)->toDateString();
        $this->teacherEmail = 'teacher.'.Str::lower(Str::random(6)).'@'.($school->slug ?: 'school').'.test';

        // Land on first incomplete step
        foreach ($this->steps as $i => $step) {
            if (! $step['is_complete']) {
                $this->stepIndex = $i;
                break;
            }
        }

        if ($this->steps !== [] && collect($this->steps)->every(fn ($s) => $s['is_complete'])) {
            $this->finished = true;
            $this->completedDuringSession = array_column($this->steps, 'key');
        }

        unset($user);
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
     * @return list<array{title: string, body: string, href: string}>
     */
    public function getSuggestionsProperty(): array
    {
        $school = $this->school()->fresh();
        $done = $this->completedDuringSession !== []
            ? $this->completedDuringSession
            : collect($this->steps)->where('is_complete', true)->pluck('key')->all();

        $suggestions = [];

        if (in_array('teachers', $done, true)) {
            $suggestions[] = [
                'title' => 'Invite more teachers',
                'body' => 'Add the rest of your teaching staff so class assignments stay accurate.',
                'href' => url('/admin/teacher/add'),
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
            $this->stepIndex = max(0, count($this->steps) - 1);

            return;
        }

        if ($this->stepIndex > 0) {
            $this->stepIndex--;
        }
    }

    public function next(): void
    {
        $this->errorMessage = '';
        $step = $this->currentStep;
        if (! $step) {
            return;
        }

        try {
            $this->persistCurrentStep($step['key']);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Please check the form.';

            return;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage() ?: 'Could not save this step.';

            return;
        }

        if (! in_array($step['key'], $this->completedDuringSession, true)) {
            $this->completedDuringSession[] = $step['key'];
        }

        app(FreeTierPlanService::class)->assignIfEligible($this->school()->fresh(), Auth::id());
        $this->refreshSteps();

        if (! OnboardingStepsService::hasIncompleteSteps($this->school()->fresh(), Auth::id())) {
            $this->finished = true;

            return;
        }

        $keys = array_column($this->steps, 'key');
        $currentIndex = array_search($step['key'], $keys, true);
        $start = $currentIndex === false ? 0 : ((int) $currentIndex + 1);

        for ($i = $start; $i < count($this->steps); $i++) {
            if (! $this->steps[$i]['is_complete']) {
                $this->stepIndex = $i;

                return;
            }
        }

        // Remaining steps already complete — land on first incomplete from start, or finish
        foreach ($this->steps as $i => $candidate) {
            if (! $candidate['is_complete']) {
                $this->stepIndex = $i;

                return;
            }
        }

        $this->finished = true;
    }

    public function goToStep(int $index): void
    {
        if ($index < 0 || $index >= count($this->steps)) {
            return;
        }

        $this->finished = false;
        $this->errorMessage = '';
        $this->stepIndex = $index;
    }

    public function render()
    {
        return view('livewire.manual-onboarding-wizard', [
            'countries' => Country::query()->orderBy('order')->orderBy('name')->get(['id', 'name']),
            'school' => $this->school(),
        ]);
    }

    private function school(): School
    {
        return School::query()->findOrFail(Auth::user()->school_id);
    }

    private function refreshSteps(): void
    {
        $this->steps = OnboardingStepsService::steps($this->school()->fresh(), Auth::id());
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
            'teachers' => $this->saveTeacher($school),
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

    private function saveTeacher(School $school): void
    {
        if (Teacherlink::where('school_id', $school->id)->exists()) {
            return;
        }

        $name = trim($this->teacherName);
        if ($name === '') {
            throw ValidationException::withMessages(['teacherName' => 'Enter a teacher name.']);
        }

        $email = trim($this->teacherEmail);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['teacherEmail' => 'Enter a valid teacher email.']);
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        $link = StandardLink::where('school_id', $school->id)->first();
        $subject = Subject::where('school_id', $school->id)->first();
        if (! $year || ! $link || ! $subject) {
            throw ValidationException::withMessages(['teacherName' => 'Add class and subject first.']);
        }

        $teacher = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
            'mobile_no' => trim($this->teacherPhone) ?: null,
        ]);

        Teacherlink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $link->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
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

        WhatsAppUser::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'phone' => $phone,
                'school_id' => $school->id,
                'opted_in' => true,
                'verified_at' => now(),
            ]
        );
    }

    private function savePlan(School $school): void
    {
        $assigned = app(FreeTierPlanService::class)->assignIfEligible($school->fresh(), Auth::id());
        if ($assigned) {
            return;
        }

        if (OnboardingStepsService::isStepComplete('plan_selection', $school->fresh(), Auth::id())) {
            return;
        }

        // Informational fallback: attach Freemium when eligible content is done
        // but FreeTierPlanService declined (e.g. missing freemium row). Tests seed plans.
        $plan = Plan::query()->where('is_active', 1)->orderBy('order')->first();
        if (! $plan) {
            throw ValidationException::withMessages(['plan' => 'No plans are available yet. Contact support.']);
        }

        // Only create when content onboarding is otherwise complete — mirrors FreeTier contract.
        foreach (OnboardingStepsService::incompleteSteps($school->fresh(), Auth::id()) as $step) {
            if ($step['key'] !== 'plan_selection') {
                throw ValidationException::withMessages(['plan' => 'Finish the earlier setup steps before choosing a plan.']);
            }
        }

        \App\Models\CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);
    }
}
