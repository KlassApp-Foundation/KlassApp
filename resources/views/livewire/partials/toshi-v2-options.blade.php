{{-- Choice buttons for the v2 registry. The method already existed; the view never called it. --}}
@if(($actionStep ?? null) === 'onboarding_v2' && !empty($onboardingV2Options))
<div style="display: flex; gap: 6px; flex-wrap: wrap; padding: 8px 0;" data-testid="toshi-v2-options">
    @foreach($onboardingV2Options as $option)
        <button type="button"
                class="toshi-btn"
                wire:click="selectOnboardingV2OptionByIndex({{ $loop->index }})"
                data-testid="toshi-v2-option">{{ $option['label'] ?? '' }}</button>
    @endforeach
</div>
@endif
