{{-- SPDX-License-Identifier: MIT --}}
{{-- Piece 3: manual onboarding wizard shell — kit chrome, real OnboardingStepsService steps --}}
<div class="manual-wizard" wire:key="manual-wizard-root" data-testid="manual-wizard-shell">
    <div class="manual-wizard-brand" data-testid="wizard-brand">
        <div class="manual-wizard-brand-mark">
            <img src="{{ asset('images/klassapp-icon.svg') }}" alt="" width="28" height="28" onerror="this.style.display='none'" />
            <span class="manual-wizard-brand-name">KlassApp</span>
        </div>
        <span class="manual-wizard-brand-aside">Setting up without Toshi</span>
    </div>

    @if($finished)
        <x-card padding="lg" shadow="md" class="max-w-3xl mx-auto manual-wizard-card">
            <div class="text-center mb-6">
                <div class="manual-wizard-done-icon" aria-hidden="true">✓</div>
                <h2 class="ds-page-head-title" style="margin-bottom:4px;">
                    {{ $school->fresh()->name }} is ready
                </h2>
                <p class="ds-page-head-sub">
                    Based on what you set up, here are sensible next moves — not a generic checklist.
                </p>
            </div>

            <ul class="space-y-3" data-testid="wizard-completion-suggestions">
                @foreach($this->suggestions as $suggestion)
                    <li>
                        <a href="{{ $suggestion['href'] }}" class="manual-wizard-suggestion">
                            <span class="manual-wizard-suggestion-title">{{ $suggestion['title'] }}</span>
                            <span class="manual-wizard-suggestion-body">{{ $suggestion['body'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8 flex justify-center">
                <x-button href="{{ url('/admin/dashboard') }}" variant="primary" size="md">
                    Go to dashboard
                </x-button>
            </div>
        </x-card>
    @elseif($this->currentStep)
        @php
            $step = $this->currentStep;
            $isOptional = in_array($step['key'], \App\Services\OnboardingStepsService::OPTIONAL_STEPS, true);
            $returningToReview = $returnToStepIndex !== null || $returnToStepKey !== null;
        @endphp
        <x-card padding="lg" shadow="md" class="max-w-3xl mx-auto manual-wizard-card" wire:key="wizard-card-{{ $stepIndex }}-{{ $step['key'] }}">
            <div class="manual-wizard-step-head mb-6">
                <div class="manual-wizard-step-title-row">
                    <h2 class="ds-page-head-title" data-testid="wizard-step-title">
                        {{ $step['label'] }}
                    </h2>
                    <span class="ds-badge ds-badge-pending ds-badge-sm" data-testid="wizard-step-counter">Step {{ $stepIndex + 1 }} of {{ $this->stepCount }}</span>
                    @if($isOptional)
                        <span class="ds-badge ds-badge-info ds-badge-sm" data-testid="wizard-step-optional">Optional</span>
                    @endif
                </div>
                @if($returningToReview && ($step['key'] ?? '') !== 'review')
                    <p class="manual-wizard-step-note" data-testid="wizard-step-note">Next returns you to review.</p>
                @endif
                @if($stepsGrewBy > 0)
                    <p class="manual-wizard-step-note manual-wizard-step-note--growth" data-testid="wizard-steps-grew">{{ $stepsGrewBy }} more {{ \Illuminate\Support\Str::plural('step', $stepsGrewBy) }} added by your answers.</p>
                @endif
            </div>

            @if($errorMessage)
                @php
                    // #601 made savePlan() reuse the empty-state copy; after Continue that
                    // would show twice (shell banner + step @empty). Prefer the in-step alert.
                    $suppressDuplicatePlanEmpty = ($step['key'] ?? '') === 'plan_selection'
                        && $errorMessage === 'No plans are available yet. Contact support.'
                        && $this->plans->isEmpty();
                @endphp
                @unless($suppressDuplicatePlanEmpty)
                    <div class="mb-4 text-sm text-red-600 font-medium" role="alert" data-testid="wizard-error">{{ $errorMessage }}</div>
                @endunless
            @endif

            <div class="manual-wizard-fields space-y-4" wire:key="step-{{ $step['key'] }}">
                @include('livewire.partials.manual-wizard-step-fields', [
                    'stepKey' => $step['key'],
                    'countries' => $countries,
                    'plans' => $this->plans,
                    'selectedPlanId' => $selectedPlanId,
                    'reviewSummary' => $reviewSummary,
                ])
            </div>

            {{-- Offered on every step that has a real admin route. It used to be suppressed on
                 exactly the steps where a form helps most (EMIS, UNEB centre, academic year). --}}
            @if(!empty($step['route']))
                <p class="mt-4 text-xs text-gray-500" style="color:#64748B;">
                    Prefer the full admin form?
                    <a href="{{ url($step['route']) }}" class="text-blue-600 underline" style="color:#1E6FD9;">Open {{ $step['label'] }}</a>
                </p>
            @endif
        </x-card>
    @endif

    {{-- Footer chrome: Previous | progress dots | Continue --}}
    @if(! $finished && $this->stepCount > 0)
        @php
            $onReview = ($this->currentStep['key'] ?? '') === 'review';
            $returningToReview = $returnToStepIndex !== null || $returnToStepKey !== null;
            if ($onReview) {
                $nextLabel = 'Confirm & finish';
                $nextVariant = 'success';
            } elseif ($returningToReview) {
                $nextLabel = 'Save & return';
                $nextVariant = 'primary';
            } else {
                $nextLabel = 'Continue →';
                $nextVariant = 'primary';
            }
            $prevLabel = ($returningToReview && ! $onReview) ? 'Cancel edit' : '← Previous';
        @endphp
        <nav class="manual-wizard-nav" aria-label="Wizard navigation" data-testid="wizard-nav">
            <x-button
                type="button"
                variant="ghost"
                size="sm"
                wire:click="previous"
                :disabled="$stepIndex === 0 && $returnToStepIndex === null && $returnToStepKey === null"
                data-testid="wizard-prev"
            >
                {{ $prevLabel }}
            </x-button>

            <div class="manual-wizard-progress" data-testid="wizard-progress">
                <div class="manual-wizard-track"
                     role="progressbar"
                     aria-valuemin="0"
                     aria-valuemax="{{ $this->stepCount }}"
                     aria-valuenow="{{ $stepIndex + 1 }}"
                     aria-label="Setup progress"
                     data-testid="wizard-track">
                    <span class="manual-wizard-track-fill" style="width: {{ $this->stepCount > 0 ? (int) round((($stepIndex + 1) / $this->stepCount) * 100) : 0 }}%"></span>
                </div>
                <label class="manual-wizard-jump">
                    <span class="manual-wizard-jump-label">Jump to</span>
                    <select class="manual-wizard-jump-select"
                            data-testid="wizard-jump"
                            aria-label="Jump to a setup step"
                            wire:change="goToStep($event.target.value)">
                        @foreach($steps as $i => $s)
                            <option value="{{ $i }}" @selected($i === $stepIndex)>{{ $i + 1 }}. {{ $s['label'] }}{{ $s['is_complete'] ? ' (done)' : '' }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <x-button
                type="button"
                :variant="$nextVariant"
                size="sm"
                wire:click="next"
                data-testid="wizard-next"
            >
                {{ $nextLabel }}
            </x-button>
        </nav>
    @elseif($finished)
        <nav class="manual-wizard-nav" aria-label="Wizard navigation">
            <x-button type="button" variant="ghost" size="sm" wire:click="previous" data-testid="wizard-prev">
                ← Previous
            </x-button>
            <div class="manual-wizard-progress" data-testid="wizard-progress">
                @foreach($steps as $i => $s)
                    <span class="manual-wizard-dot is-complete" title="{{ $s['label'] }}"></span>
                @endforeach
            </div>
            <span class="ds-btn ds-btn-primary ds-btn-sm" style="opacity:0.5;pointer-events:none;">Done</span>
        </nav>
    @endif
</div>
