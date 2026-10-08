{{-- SPDX-License-Identifier: MIT --}}
{{--
    Dashboard v2 setup chip — handoff-2026-09-30 profiles, Part B.
    After the setup banner is dismissed, progress moves to this chip at the
    bottom of the sidebar, directly above the account trigger. The dismissal
    is stored per user and school (user_preferences), so it holds across
    devices. Both the banner and this chip read OnboardingStepsService::progress()
    — one count, never two.
--}}
@php
    $dv2ChipUser = auth()->user();
    $dv2ChipActive = ((bool) config('dashboard.v2_enabled') || request()->boolean('v2'))
        && $dv2ChipUser
        && (int) $dv2ChipUser->usergroup_id === 3
        && $dv2ChipUser->school;
@endphp
@if($dv2ChipActive)
    @php
        $dv2ChipSchool = $dv2ChipUser->school;
        $dv2ChipDismissed = \App\Models\UserPreference::get(
            $dv2ChipUser,
            \App\Models\UserPreference::setupBannerDismissedKey((int) $dv2ChipSchool->id),
        ) !== null;
        $dv2ChipProgress = \App\Services\OnboardingStepsService::progress($dv2ChipSchool, $dv2ChipUser->id);
        // K25: neutral step names on the chip, from the one service.
        $dv2ChipNextLabel = $dv2ChipProgress['next']
            ? \App\Services\OnboardingStepsService::displayLabel(
                $dv2ChipProgress['next']['key'],
                ['label' => $dv2ChipProgress['next']['label']],
                $dv2ChipSchool,
            )
            : null;
    @endphp
    @if($dv2ChipDismissed && $dv2ChipProgress['done'] < $dv2ChipProgress['total'])
        <style>
        .dv2-chip{display:flex;flex-direction:column;gap:6px;padding:10px 12px;min-height:44px;border:1px solid var(--d-border,#E2E8F0);border-radius:12px;background:#fff;text-decoration:none;color:#0F172A;margin:0 0 10px}
        .dv2-chip:hover{border-color:var(--d-border-strong,#CBD5E1)}
        .dv2-chip-row{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .dv2-chip-row b{font-size:14px}
        .dv2-chip-pill{background:#DCFCE7;color:#14532D;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:700}
        .dv2-chip-next{color:#64748B;font-size:12.5px}
        .dv2-chip-bar{position:relative;height:4px;background:#DCFCE7;border-radius:999px;overflow:hidden;display:block}
        .dv2-chip-bar i{position:absolute;inset:0 auto 0 0;background:#14532D;border-radius:999px}
        body.sidebar-collapsed .dv2-chip{display:none}
        </style>
        <a class="dv2-chip" data-testid="dashboard-v2-chip"
           href="{{ url('/admin/onboarding/wizard') }}{{ $dv2ChipProgress['next'] ? '?step='.$dv2ChipProgress['next']['key'] : '' }}"
           aria-label="Finish setup, {{ $dv2ChipProgress['done'] }} of {{ $dv2ChipProgress['total'] }} steps done">
            <span class="dv2-chip-row">
                <b>Finish setup</b>
                <span class="dv2-chip-pill">{{ $dv2ChipProgress['done'] }}/{{ $dv2ChipProgress['total'] }}</span>
            </span>
            @if($dv2ChipNextLabel !== null)
                <small class="dv2-chip-next">Next: {{ $dv2ChipNextLabel }}</small>
            @endif
            <span class="dv2-chip-bar" aria-hidden="true"><i style="width:{{ $dv2ChipProgress['percent'] }}%"></i></span>
        </a>
    @endif
@endif
