@if(!empty($onboardingMissing) && !session('onboarding_reminder_dismissed'))
    @php
        $labels = \App\Helpers\OnboardingHelper::getMissingLabels($onboardingMissing);
        $count = count($labels);
    @endphp
    <div id="onboarding-reminder" style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; font-family: 'DM Sans', sans-serif;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: #FEF3C7; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                ⚡
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 14px; font-weight: 600; color: #92400E;">
                    Your school setup is incomplete
                </div>
                <div style="font-size: 13px; color: #A16207; margin-top: 4px; line-height: 1.5;">
                    <span>{{ $count }} {{ $count === 1 ? 'step' : 'steps' }} remaining:</span>
                    <span style="font-weight: 500;">{{ implode(', ', $labels) }}.</span>
                    <span>Open the <strong>Toshi</strong> chat bubble at the bottom-right to continue setup.</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-shrink: 0; align-items: center;">
                <button onclick="fetch('{{ route('dismiss.onboarding.reminder') }}').then(function(){ document.getElementById('toshi-pill').click(); document.getElementById('onboarding-reminder').style.display='none'; })" style="padding: 12px 20px; min-height: 44px; background: #0F172A; color: #FFFFFF; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif;">
                    Open Toshi
                </button>
                <a href="{{ route('dismiss.onboarding.reminder') }}" style="color: #A16207; font-size: 14px; text-decoration: none; min-height: 44px; display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 6px; transition: background 0.15s;" onmouseover="this.style.background='#FEF3C7'" onmouseout="this.style.background='transparent'">
                    Dismiss
                </a>
            </div>
        </div>
    </div>
@endif
