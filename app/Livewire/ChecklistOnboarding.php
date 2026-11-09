<?php

namespace App\Livewire;

use App\Models\OnboardingPlan;
use App\Models\School;
use App\Services\Onboarding\ChecklistOnboardingGate;
use App\Services\Onboarding\ChecklistOnboardingService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

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

        $this->phase = 'upload';
        $this->planId = null;
        $this->follow = null;
        $this->card = ['kind' => 'upload'];
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
}
