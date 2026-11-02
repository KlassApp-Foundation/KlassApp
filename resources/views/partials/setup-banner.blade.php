{{-- SPDX-License-Identifier: MIT --}}
{{--
  Consolidated setup banner for incomplete onboarding only.
  Plan / pricing is not surfaced on the dashboard — see Edit Profile.
--}}
@php
    $showSetup = !empty($setupIncomplete) || (!empty($onboardingMissing) && !session('onboarding_reminder_dismissed'));
    $showBanner = $showSetup && (auth()->user()->usergroup_id ?? null) == 3;
    // Single source: OnboardingStepsService::incompleteSteps (passed as $onboardingSteps).
    $incompleteSteps = $onboardingSteps ?? [];
    if ($incompleteSteps === [] && !empty($onboardingMissing) && auth()->user()?->school) {
        $incompleteSteps = \App\Services\OnboardingStepsService::incompleteSteps(
            auth()->user()->school,
            auth()->id()
        );
    }
    $stepCount = count($incompleteSteps);
    $labels = array_map(static fn (array $step): string => (string) ($step['label'] ?? $step['key'] ?? ''), $incompleteSteps);
@endphp

@if($showBanner)
    <div id="setup-banner" class="setup-banner" data-testid="setup-banner">
        <div class="setup-banner-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        </div>
        <div class="setup-banner-body">
            <h2 class="setup-banner-title">Finish school setup</h2>
            <p class="setup-banner-text" data-testid="setup-banner-steps">
                @if($stepCount > 0)
                    {{ $stepCount }} {{ $stepCount === 1 ? 'step' : 'steps' }} remaining{{ $stepCount <= 6 ? ' ('.implode(', ', $labels).')' : '' }}.
                @else
                    A few setup items are still open.
                @endif
                @toshiOnboarding
                    Work through them step by step, or let Toshi guide you in a focused panel.
                @else
                    Work through them step by step in the setup wizard.
                @endtoshiOnboarding
            </p>
            <div class="setup-banner-actions">
                {{-- Green acts, blue informs: this is an action, so it takes the primary
                     treatment. It used to carry an inline --d-blue override, which made the
                     primary CTA blue while every other primary action in the app is green. --}}
                @php
                    $setupSchool = auth()->user()?->school;
                    $setupHref = $setupSchool
                        ? (\App\Services\OnboardingEngine::setupDestination($setupSchool, auth()->id()) ?? url('/admin/onboarding/wizard'))
                        : url('/admin/onboarding/wizard');
                @endphp
                <a href="{{ $setupHref }}" class="ds-btn ds-btn-primary ds-btn-md" data-testid="setup-banner-manual">
                    Set up manually
                </a>
                @toshiOnboarding
                @if(auth()->user()?->school && app(\App\Services\Toshi\ToshiOnboardingPath::class)->openFor(auth()->user()->school))
                <button type="button"
                        class="ds-btn ds-btn-success ds-btn-md"
                        data-testid="setup-banner-toshi"
                        onclick="window.toshiSetCollapsed && window.toshiSetCollapsed(false); window.dispatchEvent(new CustomEvent('toshi-maximize'));">
                    Set up with Toshi
                </button>
                @else
                <button type="button"
                        disabled
                        class="ds-btn ds-btn-success ds-btn-md"
                        data-testid="setup-banner-toshi"
                        aria-disabled="true">
                    Set up with Toshi
                    <span data-testid="setup-banner-toshi-soon">Coming soon</span>
                </button>
                @endif
                @endtoshiOnboarding
                @if(!empty($onboardingMissing))
                    <form method="POST" action="{{ route('dismiss.onboarding.reminder') }}" class="inline" onsubmit="return confirm('Hide the setup banner? Your progress stays available.');">
                        @csrf
                        <button type="submit" class="ds-btn ds-btn-ghost ds-btn-md" data-testid="setup-banner-dismiss">
                            Dismiss
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endif
