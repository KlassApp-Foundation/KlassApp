<?php

namespace App\Livewire;

use App\Models\OnboardingPlan;
use App\Models\School;
use App\Models\UserPreference;
use App\Onboarding\Country\CountryProfiles;
use App\Services\FinishSchoolOnboarding;
use App\Services\Onboarding\ChecklistAssistantPrompts;
use App\Services\Onboarding\ChecklistOnboardingGate;
use App\Services\Onboarding\ChecklistOnboardingService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ChecklistOnboarding extends Component
{
    use WithFileUploads;

    public bool $enabled = false;

    public string $phase = 'idle';

    #[Locked]
    public ?int $planId = null;

    /** @var array<string, mixed> */
    public array $card = [];

    /** @var array<string, mixed>|null */
    public ?array $follow = null;

    public $workbook = null;

    public bool $agreed = false;

    public bool $showAll = false;

    public bool $showChange = false;

    public bool $showOther = false;

    public bool $questionCleared = false;

    public string $otherText = '';

    public string $otherNote = '';

    public string $sheetUrl = '';

    public string $sheetFileId = '';

    public string $sheetNote = '';

    /** @var list<array{step: string, line: string, label: string, status: string, result: ?string}> */
    public array $progressLines = [];

    /**
     * Earlier cards on this checklist. Back restores one. It never writes.
     *
     * @var list<array{phase: string, card: array<string, mixed>, follow: ?array<string, mixed>, progress: list<array<string, mixed>>}>
     */
    #[Locked]
    public array $history = [];

    /**
     * The wizard form open in the panel. Plan edits do not write the school.
     *
     * @var array{step: string, persist: string, field: string, terms: list<array<string, mixed>>}|null
     */
    public ?array $editor = null;

    /**
     * Small gaps the panel can collect, one at a time.
     *
     * @var list<array{key: string, label: string, step: string}>
     */
    public array $details = [];

    public bool $reviewed = false;

    public bool $showCaptured = false;

    public string $whatsappPhone = '';

    public string $assistantQuestion = '';

    public string $assistantReply = '';

    /** @var array<int, string> */
    public array $inviteEmails = [];

    public function mount(ChecklistOnboardingGate $gate): void
    {
        $school = $this->school();
        $this->enabled = $gate->enabledFor($school);
        $this->restore();
    }

    public static function sessionKey(int $schoolId, int $userId): string
    {
        return 'checklist.onboarding.'.$schoolId.'.'.$userId;
    }

    #[On('checklist-onboarding-open')]
    public function start(): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->resetCardUi();
        $this->assistantQuestion = '';
        $this->assistantReply = '';
        $this->agreed = false;
        $this->planId = null;
        $this->follow = null;
        $this->history = [];
        $this->sheetUrl = '';
        $this->sheetFileId = '';
        $this->sheetNote = '';
        $this->progressLines = [];
        if ($this->setupIsFinished()) {
            $this->phase = 'idle';
            $this->card = [];
            $this->publishStep();

            return;
        }
        if ($this->agreementAccepted()) {
            $this->agreed = true;
            $this->phase = 'upload';
            $this->card = ['kind' => 'upload'];
            $this->publishStep();

            return;
        }
        $this->phase = 'agreement';
        $this->card = ['kind' => 'agreement'];
        $this->publishStep();
    }

    public function updatedAgreed(bool $agreed): void
    {
        $this->recordAgreement($agreed);
    }

    public function confirmAgreement(): void
    {
        if (! $this->guard() || ! $this->agreed) {
            $this->agreed = false;

            return;
        }

        $this->recordAgreement(true);
        $this->pushHistory();
        $this->phase = 'upload';
        $this->card = ['kind' => 'upload'];
        $this->publishStep();
        $this->openPastedLink();
    }

    #[On('checklist-sheet-link')]
    public function pasteSheetLink(string $url = ''): void
    {
        if (! $this->guard()) {
            return;
        }

        if ($this->phase === 'idle') {
            $this->start();
        }

        $this->sheetUrl = trim($url);
        $this->openPastedLink();
    }

    public function updatedSheetUrl(): void
    {
        if (! $this->guard() || $this->phase !== 'upload' || ! $this->agreed) {
            return;
        }

        $this->openPastedLink();
    }

    public function clearSheetLink(): void
    {
        $this->sheetUrl = '';
        $this->sheetFileId = '';
        $this->sheetNote = '';
    }

    public function submitSheetLink(): void
    {
        $this->pasteSheetLink($this->sheetUrl);
    }

    /**
     * Keep the picker token in the session only. It is never a public property.
     */
    public function rememberSheetAccess(string $token): void
    {
        if (! $this->guard() || $this->phase !== 'upload' || ! $this->agreed) {
            return;
        }

        $token = trim($token);
        if ($token === '' || strlen($token) > 2048 || preg_match('/\s/', $token) === 1) {
            return;
        }

        session()->put('checklist_google_token', $token);
    }

    public function noteSheet(string $code): void
    {
        if (! $this->guard()) {
            return;
        }

        if (preg_match('/^google:([a-z0-9_]{1,64})$/', $code, $matches) === 1) {
            $this->sheetNote = 'Google refused the connection ('.$matches[1].'). Upload the .xlsx file.';

            return;
        }

        $this->sheetNote = match ($code) {
            'picker-key' => 'The Google Picker key is not set on this demo. Upload the .xlsx file.',
            'picker-failed' => 'Google did not open the file picker. Upload the .xlsx file.',
            default => 'Upload the .xlsx file.',
        };
    }

    public function importPickedSheet(string $fileId, ChecklistOnboardingService $service): void
    {
        if (! $this->guard()) {
            return;
        }

        if ($this->phase !== 'upload' || ! $this->agreed) {
            $this->phase = 'agreement';
            $this->card = ['kind' => 'agreement'];

            return;
        }

        $this->pushHistory();
        $token = (string) session()->pull('checklist_google_token', '');
        if ($token === '' || preg_match('/^[A-Za-z0-9_-]{10,128}$/', $fileId) !== 1) {
            $this->sheetNote = 'Choose a Google Sheet from the picker, or upload the .xlsx file.';

            return;
        }

        $path = $this->exportSheet($fileId, $token);
        if ($path === null) {
            $this->sheetNote = 'That sheet could not be read. Upload the .xlsx file.';

            return;
        }

        try {
            $this->progressLines = [];
            $result = $service->ingest($this->school(), auth()->user(), $path, 'google-sheet.xlsx', $this->trackProgress());
            $this->applyResult($result, $service, $this->school());
        } catch (ValidationException $e) {
            $this->phase = 'failed';
            $this->card = [
                'kind' => 'failed',
                'message' => collect($e->errors())->flatten()->first() ?: 'That sheet was not read.',
            ];
        } catch (Throwable) {
            $this->sheetNote = 'That sheet could not be read. Upload the .xlsx file.';
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    #[On('checklist-onboarding-hint')]
    public function hint(): void
    {
        if (! $this->enabled || $this->phase !== 'idle' || $this->setupIsFinished()) {
            return;
        }

        $this->card = [
            'kind' => 'hint',
            'message' => 'Ask me to onboard this school from its checklist, or use the button.',
        ];
    }

    public function ingest(ChecklistOnboardingService $service): void
    {
        if (! $this->guard()) {
            return;
        }

        if ($this->phase !== 'upload' || ! $this->agreed) {
            $this->phase = 'agreement';
            $this->card = ['kind' => 'agreement'];

            return;
        }

        if ($this->workbook === null) {
            if ($this->sheetFileId !== '') {
                $this->dispatch('checklist-open-picker', fileId: $this->sheetFileId);
            }

            return;
        }

        $this->pushHistory();
        $this->validate([
            'workbook' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $school = $this->school();
        $actor = auth()->user();
        $this->progressLines = [];
        $result = $service->ingest($school, $actor, $this->workbook->getRealPath(), $this->workbook->getClientOriginalName(), $this->trackProgress());
        $this->applyResult($result, $service, $school);
    }

    public function skipFlags(ChecklistOnboardingService $service): void
    {
        if (! $this->passesReview()) {
            return;
        }

        $this->pushHistory();
        $this->withPlan($service, fn ($plan, $actor) => $service->skipFlags($plan, $actor));
    }

    public function stopForFix(ChecklistOnboardingService $service): void
    {
        $this->pushHistory();
        $this->withPlan($service, fn ($plan, $actor) => $service->abandon($plan, $actor));
    }

    public function confirmStep(ChecklistOnboardingService $service): void
    {
        if (! $this->passesReview()) {
            return;
        }

        $this->progressLines = [];
        $this->withPlan($service, fn ($plan, $actor) => $service->apply($plan, $actor, $this->trackProgress()));
    }

    public function confirmReviewed(ChecklistOnboardingService $service): void
    {
        if (! $this->passesReview()) {
            return;
        }

        $key = (string) ($this->card['step_key'] ?? '');
        if ($key === 'teacher_invites') {
            $this->sendTeacherInvites($service);

            return;
        }
        if ($key === 'whatsapp') {
            $this->openDone($service);

            return;
        }

        $this->confirmStep($service);
    }

    public function openNext(): void
    {
        if (! $this->guard() || ! is_array($this->follow) || ! $this->passesReview()) {
            return;
        }

        $followKind = (string) ($this->follow['kind'] ?? '');
        if (! in_array($followKind, ['preview', 'done'], true)) {
            return;
        }

        $this->pushHistory();
        $this->progressLines = [];
        $this->card = $this->follow;
        $this->phase = $followKind === 'done' ? 'done' : 'preview';
        $this->follow = null;
        $this->resetCardUi();
        $this->seedInviteEmails();
        $this->publishStep();
    }

    public function skipStep(ChecklistOnboardingService $service): void
    {
        $this->pushHistory();
        $this->withPlan($service, fn ($plan, $actor) => $service->skipStep($plan, $actor));
    }

    public function back(ChecklistOnboardingService $service): void
    {
        if (! $this->guard() || $this->history === []) {
            return;
        }

        $frame = array_pop($this->history);
        if (! is_array($frame)) {
            return;
        }

        $this->resetCardUi();
        $this->phase = (string) ($frame['phase'] ?? 'idle');
        $this->card = is_array($frame['card'] ?? null) ? $frame['card'] : [];
        $this->follow = is_array($frame['follow'] ?? null) ? $frame['follow'] : null;
        $this->progressLines = is_array($frame['progress'] ?? null) ? $frame['progress'] : [];
        $this->showAppliedResult($service);
        $this->publishStep();
    }

    public function finishSetup(FinishSchoolOnboarding $finish): void
    {
        if (! $this->guard()) {
            return;
        }

        $school = $this->school();
        $kind = (string) ($this->card['kind'] ?? '');
        $done = isset($this->card['checks']) ? $this->card : (is_array($this->card['completion'] ?? null) ? $this->card['completion'] : null);
        if ($school === null || ($kind !== 'done' && ! is_array($done))) {
            return;
        }
        if (! is_array($done)) {
            $done = $this->card;
        }

        $this->pushHistory();
        $finish->finish($school);
        $school->refresh();
        $stats = [];
        foreach ($done['stats'] ?? [] as $stat) {
            if (is_array($stat) && isset($stat['label'])) {
                $stats[(string) $stat['label']] = $stat['value'] ?? 0;
            }
        }

        $this->phase = 'finished';
        $this->follow = null;
        $this->card = [
            'kind' => 'finished',
            'school_name' => (string) $school->name,
            'terms' => (int) ($stats['Terms'] ?? 0),
            'classes' => (int) ($stats['Classes'] ?? 0),
            'streams' => (int) ($stats['Streams'] ?? 0),
            'subjects' => (int) ($stats['Subjects'] ?? 0),
            'teachers' => (int) ($stats['Teachers'] ?? 0),
            'learners' => (int) ($stats['Learners'] ?? 0),
            'parents' => (int) ($stats['Parents'] ?? 0),
            'invites_ready' => (int) ($done['invites_ready'] ?? 0),
            'teacher_invites_sent' => (int) ($done['teacher_invites_sent'] ?? 0),
            'later' => is_array($done['later'] ?? null) ? $done['later'] : [],
            'links' => [
                ['label' => 'Attendance registers', 'href' => route('admin.attendance.index')],
                ['label' => 'Marks and report cards', 'href' => url('/admin/reports/cards')],
                ['label' => 'Fees', 'href' => url('/admin/fees/payments')],
                ['label' => 'Parents on WhatsApp', 'href' => url('/admin/whatsapp/dashboard')],
            ],
        ];
        $this->dispatch('checklist-setup-finished');
        $this->publishStep();
    }

    public function continueToDashboard(): void
    {
        if (! $this->guard() || ($this->card['kind'] ?? '') !== 'finished') {
            return;
        }

        $key = $this->rememberKey();
        if ($key !== null) {
            session()->forget($key);
        }

        $this->dispatch('checklist-continue-dashboard');
    }

    public function openEditor(): void
    {
        if (! $this->guard()) {
            return;
        }

        $key = (string) ($this->card['step_key'] ?? '');
        if ($key === 'teacher_invites' || $key === 'whatsapp') {
            return;
        }
        $step = match ($key) {
            'terms' => 'terms',
            'standards' => 'standards',
            'subjects' => 'subjects',
            'teachers' => 'teachers',
            'students' => 'students',
            default => '',
        };
        if ($step === '') {
            return;
        }

        $persist = ($this->card['kind'] ?? '') === 'preview' ? 'plan' : 'school';
        $this->editor = [
            'step' => $step,
            'persist' => $persist,
            'field' => '',
            'terms' => $step === 'terms' && $persist === 'plan' ? $this->planTermRows() : [],
        ];
    }

    public function openDetail(): void
    {
        if (! $this->guard()) {
            return;
        }

        $this->refreshDetails();
        $first = $this->details[0] ?? null;
        if (! is_array($first)) {
            return;
        }

        $this->editor = [
            'step' => (string) $first['step'],
            'persist' => 'school',
            'field' => $first['key'] === 'emis' ? '' : (string) $first['key'],
            'terms' => [],
        ];
    }

    public function closeEditor(): void
    {
        $this->editor = null;
    }

    #[On('checklist-plan-fields')]
    public function planFields(ChecklistOnboardingService $service, string $step, array $terms = []): void
    {
        if ($step !== 'terms') {
            return;
        }

        $this->editor = null;
        $this->withPlan($service, fn ($plan, $actor) => $service->replaceTermRows($plan, $actor, $terms));
    }

    #[On('checklist-school-saved')]
    public function schoolSaved(): void
    {
        $this->editor = null;
        $this->refreshDetails();
        $this->publishStep();
    }

    public function answerQuestion(string $choice, ChecklistOnboardingService $service): void
    {
        if (! $this->guard()) {
            return;
        }

        if ($choice === 'skip') {
            $this->skipStep($service);

            return;
        }

        if ($choice === 'leave' || $choice === 'keep') {
            $this->questionCleared = true;
            $this->showChange = false;
            $this->showOther = false;

            return;
        }

        $this->showOther = true;
    }

    public function openChange(): void
    {
        if (! $this->guard()) {
            return;
        }

        $this->showChange = true;
    }

    public function keepSheet(): void
    {
        $this->showChange = false;
        $this->showOther = false;
    }

    public function saveNote(): void
    {
        if (! $this->guard()) {
            return;
        }

        $this->validate(['otherText' => 'required|string|max:200']);
        $this->otherNote = trim($this->otherText);
        $this->showOther = false;
        $this->otherText = '';
    }

    public function toggleShown(): void
    {
        $this->showAll = ! $this->showAll;
    }

    public function render()
    {
        $finished = $this->setupIsFinished();

        return view('livewire.checklist-onboarding', [
            'shellStep' => $this->shellStep(),
            'setupFinished' => $finished,
            'assistant' => $finished && $this->phase === 'idle'
                ? ChecklistAssistantPrompts::forSchool($this->school())
                : ['welcome' => '', 'questions' => []],
        ]);
    }

    private function guard(): bool
    {
        $school = $this->school();
        $actor = auth()->user();
        if (! $school || ! $actor || ! app(ChecklistOnboardingGate::class)->enabledFor($school)) {
            $this->enabled = false;

            return false;
        }
        if ((int) $actor->usergroup_id !== 3 || (int) $actor->school_id !== (int) $school->id) {
            return false;
        }

        return true;
    }

    private function school(): ?School
    {
        $user = auth()->user();
        if (! $user || ! $user->school_id) {
            return null;
        }

        return School::query()->find($user->school_id);
    }

    /**
     * @param  callable(\App\Models\OnboardingPlan, \App\Models\User): array{ok: bool, code: string, card: array<string, mixed>}  $action
     */
    private function withPlan(ChecklistOnboardingService $service, callable $action): void
    {
        if (! $this->guard() || $this->planId === null) {
            return;
        }

        $actor = auth()->user();
        $plan = OnboardingPlan::query()
            ->where('school_id', $actor->school_id)
            ->whereKey($this->planId)
            ->first();
        if (! $plan) {
            $this->phase = 'failed';
            $this->card = ['kind' => 'failed', 'message' => 'That checklist is no longer available.'];

            return;
        }

        try {
            $result = $action($plan, $actor);
        } catch (ValidationException $e) {
            $this->phase = 'failed';
            $this->card = [
                'kind' => 'failed',
                'message' => collect($e->errors())->flatten()->first() ?: 'That step was not applied.',
            ];

            return;
        }

        $this->applyResult($result, $service, $plan->school);
        $this->publishStep();
    }

    /**
     * @param  array{ok: bool, code: string, plan_id?: int, card: array<string, mixed>}  $result
     */
    private function applyResult(array $result, ChecklistOnboardingService $service, ?School $school): void
    {
        if (isset($result['plan_id'])) {
            $this->planId = (int) $result['plan_id'];
        }

        $this->resetCardUi();
        $this->card = $result['card'];
        if (($this->card['step_key'] ?? '') === 'students' && ($this->card['kind'] ?? '') === 'done') {
            $this->card['kind'] = 'result';
            unset($this->card['completion']);
        }
        $this->phase = (string) ($this->card['kind'] ?? 'failed');
        $this->follow = null;
        $actor = auth()->user();

        if ($this->phase === 'summary' && empty($this->card['needs_flag_choice']) && $this->planId && $school && $actor) {
            $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
            if ($plan) {
                $this->follow = $service->previewCard($plan, $actor);
            }
        }

        if ($this->phase === 'result' && $this->planId && $school && $actor) {
            $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
            if ($plan) {
                $this->follow = $this->followFor($service, $plan, $actor, (string) ($this->card['step_key'] ?? ''));
            }
        }

        $this->seedInviteEmails();
        $this->publishStep();
    }

    public function sendTeacherInvites(ChecklistOnboardingService $service): void
    {
        if (! $this->guard() || ! $this->passesReview() || ! empty($this->card['emails_blocked'])) {
            return;
        }
        if (($this->card['step_key'] ?? '') !== 'teacher_invites') {
            return;
        }

        $this->progressLines = [];
        $this->withPlan($service, fn ($plan, $actor) => $service->sendTeacherInvites($plan, $actor));
    }

    public function askSuggestion(string $id): void
    {
        if (! $this->guard() || ! $this->setupIsFinished() || $this->phase !== 'idle') {
            return;
        }
        if (! in_array($id, ['learners', 'teacher', 'term'], true)) {
            return;
        }

        $match = ChecklistAssistantPrompts::find($this->school(), $id);
        if ($match === null) {
            return;
        }

        $this->assistantQuestion = $match['text'];
        $this->assistantReply = $match['answer'];
    }

    private function openDone(ChecklistOnboardingService $service): void
    {
        if (! $this->guard() || ! $this->passesReview()) {
            return;
        }
        if (($this->card['step_key'] ?? '') !== 'whatsapp') {
            return;
        }

        $school = $this->school();
        $actor = auth()->user();
        if ($school === null || $actor === null || $this->planId === null) {
            return;
        }

        $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
        if ($plan === null) {
            return;
        }

        $this->follow = $service->doneCard($plan, $actor);
        $this->openNext();
    }

    public function saveInviteEmail(ChecklistOnboardingService $service, int $index): void
    {
        if (! $this->guard() || $this->planId === null) {
            return;
        }

        $email = trim((string) ($this->inviteEmails[$index] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError('inviteEmails.'.$index, 'Enter a valid email.');

            return;
        }

        $school = $this->school();
        $actor = auth()->user();
        if ($school === null || $actor === null) {
            return;
        }

        $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
        if ($plan === null) {
            return;
        }

        $this->card = $service->saveTeacherEmail($plan, $actor, $index, $email);
        $this->phase = 'preview';
        $this->seedInviteEmails();
        $this->publishStep();
    }

    public function saveWhatsappNumber(ChecklistOnboardingService $service): void
    {
        if (! $this->guard() || ($this->card['step_key'] ?? '') !== 'whatsapp') {
            return;
        }

        $school = $this->school();
        $actor = auth()->user();
        if ($school === null || $actor === null || $this->planId === null) {
            return;
        }

        $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
        if ($plan === null) {
            return;
        }

        $country = (string) ($plan->payload['school']['country'] ?? $school->registration_country ?? '');
        try {
            $saved = $service->saveSchoolWhatsapp($plan, $actor, $this->whatsappPhone, $country);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->addError('whatsappPhone', is_string($message) ? $message : \App\Support\PhoneCountry::error($country, true));

            return;
        }

        $this->card = array_merge($this->card, $saved);
        $this->card['show_editor'] = false;
        $this->publishStep();
    }

    private function trackProgress(): \Closure
    {
        return function (array $event): void {
            $this->noteProgress($event);
        };
    }

    /**
     * @param  array{step?: string, line?: string, label?: string, status?: string, result?: ?string}  $event
     */
    private function noteProgress(array $event): void
    {
        $line = [
            'step' => (string) ($event['step'] ?? ''),
            'line' => (string) ($event['line'] ?? ''),
            'label' => (string) ($event['label'] ?? ''),
            'status' => (string) ($event['status'] ?? 'done'),
            'result' => isset($event['result']) && $event['result'] !== null ? (string) $event['result'] : null,
        ];
        if (! in_array($line['status'], ['running', 'done', 'problem'], true)) {
            $line['status'] = 'done';
        }

        $replaced = false;
        foreach ($this->progressLines as $index => $existing) {
            if ($line['line'] !== '' && $existing['line'] === $line['line']) {
                $this->progressLines[$index] = $line;
                $replaced = true;
                break;
            }
        }
        $previous = null;
        if ($replaced) {
            $previous = $existing['status'] ?? null;
        }
        if (! $replaced) {
            $this->progressLines[] = $line;
        }

        if (app()->runningUnitTests()) {
            return;
        }

        $html = '';
        foreach ($this->progressLines as $progressLine) {
            $html .= view('livewire.partials.checklist-progress-lines', ['line' => $progressLine])->render();
        }
        $this->stream('checklist-progress', $html, true);
        $pace = ! $replaced || ($previous === 'running' && $line['status'] !== 'running');
        if ($pace) {
            usleep(250000);
        }
    }

    private function publishStep(): void
    {
        $this->refreshDetails();
        $this->remember();
        $this->dispatch('checklist-shell', step: $this->shellStep())->to(AgentToshi::class);
    }

    /**
     * @return list<array{name: string, start: string, end: string}>
     */
    private function planTermRows(): array
    {
        $school = $this->school();
        if ($school === null || $this->planId === null) {
            return [];
        }

        $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
        $payload = $plan && is_array($plan->payload) ? $plan->payload : [];
        $rows = [];
        foreach ($payload['steps']['terms']['rows'] ?? [] as $term) {
            if (! is_array($term)) {
                continue;
            }
            $rows[] = [
                'name' => (string) ($term['name'] ?? ''),
                'start' => (string) ($term['start'] ?? ''),
                'end' => (string) ($term['end'] ?? ''),
            ];
        }

        return $rows;
    }

    private function refreshDetails(): void
    {
        $school = $this->school();
        if ($school === null) {
            $this->details = [];

            return;
        }

        $school = $school->fresh() ?? $school;
        $gaps = [];
        if (trim((string) $school->phone) === '') {
            $gaps[] = ['key' => 'phone', 'label' => 'School phone', 'step' => 'school_contact'];
        }
        if (trim((string) $school->email) === '') {
            $gaps[] = ['key' => 'email', 'label' => 'School email', 'step' => 'school_contact'];
        }
        if ($school->mottoText() === null) {
            $gaps[] = ['key' => 'motto', 'label' => 'Motto', 'step' => 'school_contact'];
        }
        if (CountryProfiles::requiresMinistryCode($school->registration_country) && trim((string) $school->ministry_code) === '') {
            $gaps[] = ['key' => 'emis', 'label' => 'EMIS number', 'step' => 'emis'];
        }
        $this->details = $gaps;
    }

    private function shellStep(): string
    {
        $kind = (string) ($this->card['kind'] ?? '');
        if ($this->phase === 'idle' || $kind === 'hint' || ($kind === '' && $this->phase === 'idle')) {
            return 'welcome';
        }
        if (in_array($kind, ['done', 'finished'], true) || in_array($this->phase, ['done', 'finished'], true)) {
            return 'done';
        }
        if ($kind === 'agreement' || $this->phase === 'agreement') {
            return 'agreement';
        }
        if (in_array($kind, ['upload', 'summary', 'refused'], true) || $this->phase === 'upload') {
            return 'checklist';
        }

        $key = (string) ($this->card['step_key'] ?? '');

        return match ($key) {
            'terms' => 'terms',
            'standards' => 'classes',
            'subjects' => 'subjects',
            'teachers', 'teacher_invites' => 'teachers',
            'students' => 'learners',
            'whatsapp' => 'checklist',
            default => 'checklist',
        };
    }

    private function pushHistory(): void
    {
        if ($this->phase === 'idle' || ($this->card['kind'] ?? '') === '') {
            return;
        }

        $this->history[] = [
            'phase' => $this->phase,
            'card' => $this->card,
            'follow' => $this->follow,
            'progress' => $this->progressLines,
        ];
        if (count($this->history) > 20) {
            $this->history = array_slice($this->history, -20);
        }
    }

    private function showAppliedResult(ChecklistOnboardingService $service): void
    {
        $key = (string) ($this->card['step_key'] ?? '');
        if ($key === '' || ($this->card['kind'] ?? '') !== 'preview' || $this->planId === null) {
            return;
        }

        $school = $this->school();
        $actor = auth()->user();
        if ($school === null || $actor === null) {
            return;
        }

        $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
        if ($plan === null) {
            return;
        }

        try {
            $view = $service->storedResult($plan, $actor, $key);
        } catch (ValidationException) {
            return;
        }
        if ($view === null) {
            return;
        }

        $this->card = $view;
        $this->phase = 'result';
        $this->follow = $this->followFor($service, $plan, $actor, (string) ($view['step_key'] ?? $key));
    }

    private function passesReview(): bool
    {
        return ! $this->needsReview() || $this->reviewed;
    }

    private function needsReview(): bool
    {
        if (($this->card['connect_notice'] ?? '') !== '') {
            return false;
        }

        $kind = (string) ($this->card['kind'] ?? '');
        $key = (string) ($this->card['step_key'] ?? '');
        if ($kind === 'summary') {
            return true;
        }

        return $kind === 'preview' && in_array($key, ['terms', 'standards', 'teachers', 'teacher_invites', 'whatsapp'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function followFor(ChecklistOnboardingService $service, OnboardingPlan $plan, \App\Models\User $actor, string $stepKey): ?array
    {
        if ($stepKey === 'teachers') {
            return $service->teacherInvitesCard($plan, $actor);
        }
        if ($stepKey === 'teacher_invites') {
            try {
                $next = $service->previewCard($plan, $actor);
            } catch (ValidationException) {
                return null;
            }

            return ($next['kind'] ?? '') === 'preview' ? $next : null;
        }
        if ($stepKey === 'students') {
            return $service->whatsappCard($plan, $actor);
        }

        try {
            $next = $service->previewCard($plan, $actor);
        } catch (ValidationException) {
            return null;
        }

        return ($next['kind'] ?? '') === 'preview' ? $next : null;
    }

    private function seedInviteEmails(): void
    {
        if (($this->card['step_key'] ?? '') !== 'teacher_invites') {
            return;
        }

        $this->inviteEmails = [];
        foreach ($this->card['teachers'] ?? [] as $index => $person) {
            if (! is_array($person)) {
                continue;
            }
            $this->inviteEmails[(int) $index] = (string) ($person['email'] ?? '');
        }
    }

    private function remember(): void
    {
        $key = $this->rememberKey();
        if ($key === null) {
            return;
        }
        if ($this->phase === 'idle') {
            session()->forget($key);

            return;
        }

        session()->put($key, [
            'plan_id' => $this->planId,
            'phase' => $this->phase,
            'card' => $this->card,
            'follow' => $this->follow,
            'agreed' => $this->agreed,
            'progress' => $this->progressLines,
            'history' => $this->history,
            'shell' => $this->shellStep(),
            'editor' => $this->editor,
            'details' => $this->details,
        ]);
    }

    private function restore(): void
    {
        if (! $this->enabled) {
            return;
        }

        $key = $this->rememberKey();
        if ($key === null) {
            return;
        }

        $saved = session()->get($key);
        if (! is_array($saved)) {
            return;
        }

        $phase = (string) ($saved['phase'] ?? 'idle');
        if ($phase === '' || $phase === 'idle') {
            return;
        }

        $school = $this->school();
        $planId = $saved['plan_id'] ?? null;
        if ($planId !== null && $school !== null) {
            $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey((int) $planId)->first();
            if ($plan === null) {
                session()->forget($key);

                return;
            }
            $this->planId = (int) $plan->id;
        }

        $this->phase = $phase;
        $this->card = is_array($saved['card'] ?? null) ? $saved['card'] : [];
        $this->follow = is_array($saved['follow'] ?? null) ? $saved['follow'] : null;
        $this->agreed = (bool) ($saved['agreed'] ?? false);
        $this->progressLines = is_array($saved['progress'] ?? null) ? $saved['progress'] : [];
        $this->history = is_array($saved['history'] ?? null) ? $saved['history'] : [];
        $this->editor = is_array($saved['editor'] ?? null) ? $saved['editor'] : null;
        $this->details = is_array($saved['details'] ?? null) ? $saved['details'] : [];
    }

    private function rememberKey(): ?string
    {
        $school = $this->school();
        $user = auth()->user();
        if ($school === null || $user === null) {
            return null;
        }

        return self::sessionKey((int) $school->id, (int) $user->id);
    }

    private function resetCardUi(): void
    {
        $this->showAll = false;
        $this->showChange = false;
        $this->showOther = false;
        $this->questionCleared = false;
        $this->otherText = '';
        $this->otherNote = '';
        $this->reviewed = false;
        $this->showCaptured = false;
        $this->inviteEmails = [];
    }

    /**
     * Read one picked Drive file. A native Google Sheet is exported as .xlsx.
     * An .xlsx file stored as itself is downloaded with files.get alt=media.
     * Each call is one attempt. The token is not logged.
     */
    private function exportSheet(string $fileId, string $token): ?string
    {
        $mime = $this->driveMimeType($fileId, $token);
        if ($mime === 'application/vnd.google-apps.spreadsheet') {
            return $this->driveBytes($fileId, $token, true);
        }
        if ($mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            return $this->driveBytes($fileId, $token, false);
        }

        return null;
    }

    private function driveMimeType(string $fileId, string $token): ?string
    {
        try {
            $response = $this->driveRequest($token)->get(
                'https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId),
                ['fields' => 'mimeType']
            );
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $mime = $response->json('mimeType');

        return is_string($mime) ? $mime : null;
    }

    private function driveBytes(string $fileId, string $token, bool $export): ?string
    {
        try {
            $request = $this->driveRequest($token);
            $response = $export
                ? $request->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId).'/export', [
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                : $request->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), [
                    'alt' => 'media',
                ]);
        } catch (Throwable) {
            return null;
        }

        $body = $response->body();
        if (! $response->successful() || strlen($body) > 10 * 1024 * 1024 || ! str_starts_with($body, "PK\x03\x04")) {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'sheet');
        if ($path === false) {
            return null;
        }

        $xlsx = $path.'.xlsx';
        rename($path, $xlsx);
        file_put_contents($xlsx, $body);

        return $xlsx;
    }

    private function driveRequest(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->withOptions(['allow_redirects' => false])
            ->timeout(15);
    }

    private function recordAgreement(bool $agreed): void
    {
        if (! $this->guard()) {
            $this->agreed = false;

            return;
        }

        $actor = auth()->user();
        $school = $this->school();
        if (! $actor || ! $school) {
            return;
        }

        $key = 'checklist.school_agreement.'.$school->id;
        if ($agreed) {
            if (UserPreference::get($actor, $key) === null) {
                UserPreference::set($actor, $key, now()->toIso8601String());
            }

            return;
        }

        if ($this->agreementAccepted()) {
            $this->agreed = true;

            return;
        }

        UserPreference::forget($actor, $key);
    }

    private function setupIsFinished(): bool
    {
        $school = $this->school();

        return $school !== null && $school->fresh()->onboarding_finished_at !== null;
    }

    private function agreementAccepted(): bool
    {
        $school = $this->school();
        if ($school === null) {
            return false;
        }

        return UserPreference::query()
            ->where('key', 'checklist.school_agreement.'.$school->id)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->exists();
    }

    private function openPastedLink(): void
    {
        if ($this->phase !== 'upload' || ! $this->agreed) {
            return;
        }

        $url = trim($this->sheetUrl);
        if ($url === '') {
            $this->sheetFileId = '';
            $this->sheetNote = '';

            return;
        }

        $fileId = $this->fileIdFromLink($url);
        if ($fileId === null) {
            $this->sheetFileId = '';
            $this->sheetNote = 'That is not a Google Drive or Sheets link. Upload the checklist as an .xlsx file.';

            return;
        }

        $this->sheetNote = '';
        if ($fileId === $this->sheetFileId) {
            return;
        }

        $this->sheetFileId = $fileId;
        $this->dispatch('checklist-open-picker', fileId: $fileId);
    }

    private function fileIdFromLink(string $url): ?string
    {
        if (preg_match('#^https://(?:docs|drive)\.google\.com/#i', $url) !== 1) {
            return null;
        }

        if (preg_match('#/d/([A-Za-z0-9_-]{10,128})#', $url, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('#[?&]id=([A-Za-z0-9_-]{10,128})#', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
