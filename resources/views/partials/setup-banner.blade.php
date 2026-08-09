{{-- SPDX-License-Identifier: MIT --}}
{{--
  Wave 3 consolidated setup banner — one calm entry point for incomplete
  onboarding and/or missing plan (replaces separate reminder + no-plan boxes
  + continue-setup card).
--}}
@php
    $showSetup = !empty($setupIncomplete) || (!empty($onboardingMissing) && !session('onboarding_reminder_dismissed'));
    $showNoPlan = empty($plan);
    $showBanner = ($showSetup || $showNoPlan) && (auth()->user()->usergroup_id ?? null) == 3;
    $labels = !empty($onboardingMissing)
        ? \App\Helpers\OnboardingHelper::getMissingLabels($onboardingMissing)
        : [];
    $stepCount = count($labels);
@endphp

@if($showBanner)
    <div id="setup-banner" class="setup-banner" data-testid="setup-banner">
        <div class="setup-banner-icon" aria-hidden="true">
            @if($showSetup)
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            @else
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>
            @endif
        </div>
        <div class="setup-banner-body">
            <h2 class="setup-banner-title">
                @if($showSetup && $showNoPlan)
                    Finish school setup
                @elseif($showSetup)
                    Your school setup is incomplete
                @else
                    No active plan
                @endif
            </h2>
            <p class="setup-banner-text">
                @if($showSetup)
                    @if($stepCount > 0)
                        {{ $stepCount }} {{ $stepCount === 1 ? 'step' : 'steps' }} remaining
                        @if($stepCount <= 6)
                            ({{ implode(', ', $labels) }})
                        @endif.
                    @else
                        A few setup items are still open.
                    @endif
                    Work through them step by step, or let Toshi guide you in a focused panel.
                    @if($showNoPlan)
                        You can pick a plan at the end — it is not a blocker.
                    @endif
                @else
                    Your school is running without a subscription limit. Choose a plan when you are ready to track capacity.
                @endif
            </p>
            <div class="setup-banner-actions">
                @if($showSetup)
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
                @else
                    <a href="{{ url('/admin/subscriptions') }}" class="ds-btn ds-btn-primary ds-btn-md" data-testid="setup-banner-plans" style="background:var(--d-blue);">
                        View plans
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- Keep usage meters when a plan exists (not an alert — capacity tracking). --}}
@if($plan && !empty($planUsage))
    @include('partials.plan-banner-usage')
@endif
