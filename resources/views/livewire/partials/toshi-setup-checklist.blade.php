{{-- SPDX-License-Identifier: MIT --}}
{{--
  Toshi setup checklist — ONE source: OnboardingStepsService (the same list the
  dashboard setup bar and the sidebar chip read). Neutral step names (K25), the
  same count everywhere, and every row opens the same step via
  jumpToChecklistStep(). `variant`: 'dock' (dots + list in the panel) or
  'modal' (Progress — x/y + list in the maximized sidebar).
--}}
@php
    $sharedSteps = $this->onboardingChecklist;
    $sharedSchool = $this->schoolId ? \App\Models\School::find($this->schoolId) : null;
    $sharedSteps = array_map(function (array $s) use ($sharedSchool): array {
        $s['label'] = \App\Services\OnboardingStepsService::displayLabel($s['key'], $s, $sharedSchool);
        return $s;
    }, $sharedSteps);
    $sharedTotal = count($sharedSteps);
    $sharedDone = 0;
    $sharedCurrent = null;
    foreach ($sharedSteps as $si => $s) {
        if (! empty($s['is_complete'])) {
            $sharedDone++;
        } elseif ($sharedCurrent === null) {
            $sharedCurrent = $si;
        }
    }
    $currentIsOptional = $sharedCurrent !== null
        && in_array($sharedSteps[$sharedCurrent]['key'], \App\Services\OnboardingStepsService::OPTIONAL_STEPS, true);
@endphp
@if($sharedTotal > 0 && in_array($this->mode, ['complete', 'create'], true))
    @if(($variant ?? 'dock') === 'modal')
        <div style="margin-top: 12px;" data-testid="toshi-modal-checklist">
            <div class="toshi-setup-progress-label">Progress — {{ $sharedDone }}/{{ $sharedTotal }}</div>
            <div class="toshi-setup-list toshi-setup-list--modal" data-testid="toshi-setup-list-modal" role="list">
                @foreach($sharedSteps as $si => $s)
                    @php
                        $isDone = !empty($s['is_complete']);
                        $isOptional = in_array($s['key'], \App\Services\OnboardingStepsService::OPTIONAL_STEPS, true);
                        $tone = $isDone ? 'positive' : ($isOptional ? 'info' : 'warning');
                    @endphp
                    <button type="button"
                            role="listitem"
                            class="toshi-setup-row"
                            data-tone="{{ $tone }}"
                            data-testid="toshi-setup-row-modal-{{ $s['key'] }}"
                            wire:click="jumpToChecklistStep('{{ $s['key'] }}')"
                            aria-label="{{ $s['label'] }}, {{ $isDone ? 'set up already, review it' : 'not set up yet, start it' }}">
                        <span class="toshi-setup-row-icon" aria-hidden="true">
                            @if($isDone)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @else
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            @endif
                        </span>
                        <span class="toshi-setup-row-label">{{ $s['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @else
        <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; margin: 0 10px; background: #f5f4ed; border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 3px; flex: 1;">
                @foreach($sharedSteps as $si => $s)
                    <div class="toshi-progress-dot {{ !empty($s['is_complete']) ? 'toshi-progress-dot-done' : ($si === $sharedCurrent ? 'toshi-progress-dot-current' : 'toshi-progress-dot-pending') }}"></div>
                @endforeach
            </div>
            <span class="toshi-progress-step">{{ $sharedDone }}/{{ $sharedTotal }}</span>
            @if($currentIsOptional)
            <span class="toshi-badge-optional">Optional</span>
            @else
            <span class="toshi-badge-required">Required</span>
            @endif
        </div>
        {{-- Setup items, actionable. One row per shared step, tone-coded so an
             incomplete step reads as something to do rather than something wrong.
             Red is reserved for destructive actions and is never used here. --}}
        <div class="toshi-setup-list" data-testid="toshi-setup-list" role="list">
            @foreach($sharedSteps as $si => $s)
                @php
                    $isDone = !empty($s['is_complete']);
                    $isOptional = in_array($s['key'], \App\Services\OnboardingStepsService::OPTIONAL_STEPS, true);
                    $tone = $isDone ? 'positive' : ($isOptional ? 'info' : 'warning');
                    $action = $isDone ? 'Review' : ($isOptional ? 'Add later' : 'Set up');
                @endphp
                <button type="button"
                        role="listitem"
                        class="toshi-setup-row"
                        data-tone="{{ $tone }}"
                        data-testid="toshi-setup-row-{{ $s['key'] }}"
                        wire:click="jumpToChecklistStep('{{ $s['key'] }}')"
                        aria-label="{{ $s['label'] }}, {{ $isDone ? 'set up already, review it' : 'not set up yet, start it' }}">
                    <span class="toshi-setup-row-icon" aria-hidden="true">
                        @if($isDone)
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        @else
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        @endif
                    </span>
                    <span class="toshi-setup-row-label">{{ $s['label'] }}</span>
                    @if($isOptional && ! $isDone)
                        <span class="toshi-setup-row-flag">Optional</span>
                    @endif
                    <span class="toshi-setup-row-action">{{ $action }} →</span>
                </button>
            @endforeach
        </div>
        {{-- The step list is dynamic by design: answering country or curriculum
             unlocks more of the shared steps. Say so, so the count growing reads
             as intentional rather than as a jump. --}}
        <p style="margin: 0 10px; padding: 0 14px 10px; font-size: 10px; color: #94A3B8;">
            Steps appear as your answers unlock them.
        </p>
    @endif
@endif
