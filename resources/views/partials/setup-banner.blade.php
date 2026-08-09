{{-- SPDX-License-Identifier: MIT --}}
{{--
  Consolidated setup banner for incomplete onboarding only.
  Plan / pricing is not surfaced on the dashboard — see Edit Profile.
--}}
@php
    $showSetup = !empty($setupIncomplete) || (!empty($onboardingMissing) && !session('onboarding_reminder_dismissed'));
    $showBanner = $showSetup && (auth()->user()->usergroup_id ?? null) == 3;
    $labels = !empty($onboardingMissing)
        ? \App\Helpers\OnboardingHelper::getMissingLabels($onboardingMissing)
        : [];
    $stepCount = count($labels);
@endphp

@if($showBanner)
    <div id="setup-banner" class="setup-banner" data-testid="setup-banner">
        <div class="setup-banner-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        </div>
        <div class="setup-banner-body">
            <h2 class="setup-banner-title">Finish school setup</h2>
            <p class="setup-banner-text">
                @if($stepCount > 0)
                    {{ $stepCount }} {{ $stepCount === 1 ? 'step' : 'steps' }} remaining
                    @if($stepCount <= 6)
                        ({{ implode(', ', $labels) }})
                    @endif.
                @else
                    A few setup items are still open.
                @endif
                Work through them step by step, or let Toshi guide you in a focused panel.
            </p>
            <div class="setup-banner-actions">
                <a href="{{ url('/admin/onboarding/wizard') }}" class="ds-btn ds-btn-primary ds-btn-md" data-testid="setup-banner-manual" style="background:var(--d-blue);">
                    Set up manually
                </a>
                <button type="button"
                        class="ds-btn ds-btn-success ds-btn-md"
                        data-testid="setup-banner-toshi"
                        onclick="document.body.classList.remove('toshi-collapsed'); window.dispatchEvent(new CustomEvent('toshi-maximize'));">
                    Set up with Toshi
                </button>
                @if(!empty($onboardingMissing))
                    <a href="{{ route('dismiss.onboarding.reminder') }}" class="ds-btn ds-btn-ghost ds-btn-md" data-testid="setup-banner-dismiss">
                        Dismiss
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
