<?php

namespace App\Livewire;

use App\Models\OnboardingPlan;
use App\Models\School;
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

    public string $sheetNote = '';

    public function mount(ChecklistOnboardingGate $gate): void
    {
        $school = $this->school();
        $this->enabled = $gate->enabledFor($school);
    }

    #[On('checklist-onboarding-open')]
    public function start(): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->resetCardUi();
        $this->agreed = false;
        $this->planId = null;
        $this->follow = null;
        $this->sheetUrl = '';
        $this->sheetNote = '';
        $this->phase = 'agreement';
        $this->card = ['kind' => 'agreement'];
    }

    public function confirmAgreement(): void
    {
        if (! $this->guard()) {
            return;
        }

        $this->agreed = true;
        $this->phase = 'upload';
        $this->card = ['kind' => 'upload'];
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

        $this->sheetNote = $this->sheetFallback($url);
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
            $result = $service->ingest($this->school(), auth()->user(), $path, 'google-sheet.xlsx');
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
        if (! $this->enabled || $this->phase !== 'idle') {
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

        $this->validate([
            'workbook' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $school = $this->school();
        $actor = auth()->user();
        $result = $service->ingest($school, $actor, $this->workbook->getRealPath(), $this->workbook->getClientOriginalName());
        $this->applyResult($result, $service, $school);
    }

    public function skipFlags(ChecklistOnboardingService $service): void
    {
        $this->withPlan($service, fn ($plan, $actor) => $service->skipFlags($plan, $actor));
    }

    public function stopForFix(ChecklistOnboardingService $service): void
    {
        $this->withPlan($service, fn ($plan, $actor) => $service->abandon($plan, $actor));
    }

    public function confirmStep(ChecklistOnboardingService $service): void
    {
        $this->withPlan($service, fn ($plan, $actor) => $service->apply($plan, $actor));
    }

    public function skipStep(ChecklistOnboardingService $service): void
    {
        $this->withPlan($service, fn ($plan, $actor) => $service->skipStep($plan, $actor));
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
        return view('livewire.checklist-onboarding');
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
        $this->phase = (string) ($result['card']['kind'] ?? 'failed');
        $this->follow = null;

        if ($this->phase === 'summary' && empty($this->card['needs_flag_choice']) && $this->planId && $school) {
            $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
            if ($plan && auth()->user()) {
                $this->follow = $service->previewCard($plan, auth()->user());
            }
        }

        if ($this->phase === 'result' && $this->planId && $school) {
            $plan = OnboardingPlan::query()->where('school_id', $school->id)->whereKey($this->planId)->first();
            if ($plan && auth()->user()) {
                $this->follow = $service->previewCard($plan, auth()->user());
            }
        }
    }

    private function resetCardUi(): void
    {
        $this->showAll = false;
        $this->showChange = false;
        $this->showOther = false;
        $this->questionCleared = false;
        $this->otherText = '';
        $this->otherNote = '';
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

    private function sheetFallback(string $url): string
    {
        $url = trim($url);
        $isSheet = preg_match('#^https://docs\.google\.com/spreadsheets/#i', $url) === 1;
        if (! $isSheet) {
            return 'That is not a Google Sheets link. Upload the checklist as an .xlsx file.';
        }

        return 'This demo cannot read a Google Sheet directly. In the sheet, choose File, then Download, then Microsoft Excel (.xlsx), and upload that file here. Share the sheet only with the people who should see the children\'s data.';
    }
}
