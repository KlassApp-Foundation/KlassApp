{{-- SPDX-License-Identifier: MIT --}}
{{-- Shared "Set up by chatting with Toshi" choice. Real schools see it disabled. --}}
@php
    $toshiPathAvailable = (bool) ($toshiPathAvailable ?? false);
@endphp
@if ($toshiPathAvailable)
    <button type="submit" name="path" value="toshi" class="ap-secondary" data-testid="onboarding-choice-toshi" style="text-align:left;padding:18px 20px;">
        <strong style="display:block;font-size:1rem;">Set up by chatting with Toshi</strong>
        <span style="display:block;font-weight:400;opacity:.9;margin-top:4px;">Toshi asks one question at a time and saves each answer. No AI key needed for guided setup.</span>
    </button>
@else
    <button type="button" disabled class="ap-secondary" data-testid="onboarding-choice-toshi" aria-disabled="true" style="text-align:left;padding:18px 20px;opacity:.55;cursor:not-allowed;">
        <strong style="display:block;font-size:1rem;">Set up by chatting with Toshi</strong>
        <span data-testid="onboarding-choice-toshi-soon" style="display:inline-block;margin-top:6px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">Coming soon</span>
        <span style="display:block;font-weight:400;opacity:.9;margin-top:4px;">Toshi asks one question at a time and saves each answer. No AI key needed for guided setup.</span>
    </button>
@endif
