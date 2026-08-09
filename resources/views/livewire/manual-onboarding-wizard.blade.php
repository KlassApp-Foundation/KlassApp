{{-- SPDX-License-Identifier: MIT --}}
{{-- Wave 3 manual onboarding wizard shell --}}
<div class="manual-wizard" wire:key="manual-wizard-root">
    <div class="ds-page-head">
        <div class="flex items-center gap-3">
            <x-button href="{{ url('/admin/dashboard') }}" variant="ghost" size="sm" title="Back to dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            </x-button>
            <div>
                <h1 class="ds-page-head-title">School setup</h1>
                <p class="ds-page-head-sub">Step-by-step setup for {{ $school->name }}</p>
            </div>
        </div>
    </div>

    @if($finished)
        <x-card padding="lg" shadow="md" class="max-w-3xl mx-auto manual-wizard-card">
            <div class="text-center mb-6">
                <div class="manual-wizard-done-icon" aria-hidden="true">✓</div>
                <h2 class="text-2xl font-semibold text-gray-900" style="font-family:Sora,sans-serif;color:#0F172A;">
                    {{ $school->fresh()->name }} is ready
                </h2>
                <p class="mt-2 text-sm text-gray-600" style="color:#64748B;">
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
        @php $step = $this->currentStep; @endphp
        <x-card padding="lg" shadow="md" class="max-w-3xl mx-auto manual-wizard-card">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500" style="color:#64748B;">
                    Step {{ $stepIndex + 1 }} of {{ $this->stepCount }}
                </p>
                <h2 class="text-xl font-semibold mt-1" style="font-family:Sora,sans-serif;color:#0F172A;">
                    <span class="mr-2" aria-hidden="true">{{ $step['icon'] }}</span>{{ $step['label'] }}
                </h2>
            </div>

            @if($errorMessage)
                <div class="mb-4 text-sm text-red-600 font-medium" role="alert" data-testid="wizard-error">{{ $errorMessage }}</div>
            @endif

            <div class="manual-wizard-fields space-y-4" wire:key="step-{{ $step['key'] }}">
                @include('livewire.partials.manual-wizard-step-fields', ['stepKey' => $step['key'], 'countries' => $countries])
            </div>

            @if(!empty($step['route']) && !in_array($step['key'], ['school_name', 'curriculum', 'country', 'emis', 'uneb_center', 'academic_year', 'plan_selection'], true))
                <p class="mt-4 text-xs text-gray-500" style="color:#64748B;">
                    Prefer the full admin form?
                    <a href="{{ url($step['route']) }}" class="text-blue-600 underline" style="color:#1E6FD9;">Open {{ $step['label'] }}</a>
                </p>
            @endif
        </x-card>
    @endif

    {{-- Footer chrome: Previous | progress dots | Next --}}
    @if(! $finished && $this->stepCount > 0)
        <nav class="manual-wizard-nav" aria-label="Wizard navigation" data-testid="wizard-nav">
            <button type="button"
                    class="ds-btn ds-btn-outline ds-btn-md"
                    wire:click="previous"
                    @disabled($stepIndex === 0)
                    data-testid="wizard-prev">
                Previous
            </button>

            <div class="manual-wizard-progress" role="tablist" aria-label="Setup progress" data-testid="wizard-progress">
                @foreach($steps as $i => $s)
                    <button type="button"
                            class="manual-wizard-dot {{ $i === $stepIndex ? 'is-current' : '' }} {{ $s['is_complete'] ? 'is-complete' : '' }}"
                            wire:click="goToStep({{ $i }})"
                            title="{{ $s['label'] }}"
                            aria-label="{{ $s['label'] }}"
                            aria-current="{{ $i === $stepIndex ? 'step' : 'false' }}"
                            data-step-key="{{ $s['key'] }}"></button>
                @endforeach
            </div>

            <button type="button"
                    class="ds-btn ds-btn-primary ds-btn-md"
                    wire:click="next"
                    data-testid="wizard-next">
                {{ $stepIndex >= $this->stepCount - 1 ? 'Finish' : 'Next' }}
            </button>
        </nav>
    @elseif($finished)
        <nav class="manual-wizard-nav" aria-label="Wizard navigation">
            <button type="button" class="ds-btn ds-btn-outline ds-btn-md" wire:click="previous" data-testid="wizard-prev">
                Previous
            </button>
            <div class="manual-wizard-progress" data-testid="wizard-progress">
                @foreach($steps as $i => $s)
                    <span class="manual-wizard-dot is-complete" title="{{ $s['label'] }}"></span>
                @endforeach
            </div>
            <span class="ds-btn ds-btn-primary ds-btn-md" style="opacity:0.5;pointer-events:none;">Done</span>
        </nav>
    @endif
</div>
