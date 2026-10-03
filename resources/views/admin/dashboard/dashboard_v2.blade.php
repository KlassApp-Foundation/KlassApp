{{-- SPDX-License-Identifier: MIT --}}
{{--
    Admin dashboard v2 — design handoff-2026-09-30 profiles, Part B.
    Selectable via config('dashboard.v2_enabled') or ?v2=1 (default off).
    Content-section swap only; sidebar/navigation remain the shared app shell.
--}}
@extends('layouts.admin.layout')

@section('content')
@php
    $school = auth()->user()?->school;
    $schoolLabel = $school?->name ?? ($greeting['name'] ?? 'KlassApp');
    $incompleteSteps = $onboardingSteps ?? [];
    if ($incompleteSteps === [] && !empty($onboardingMissing) && $school) {
        $incompleteSteps = \App\Services\OnboardingStepsService::incompleteSteps($school, auth()->id());
    }
    $totalSteps = 7;
    $doneSteps = max(0, $totalSteps - count($incompleteSteps));
    $nextStep = $incompleteSteps[0]['label'] ?? $incompleteSteps[0]['key'] ?? null;
    $bannerShown = !empty($setupIncomplete) && (auth()->user()->usergroup_id ?? null) == 3 && !session('onboarding_reminder_dismissed');
    $currentYear = \App\Helpers\SiteHelper::getAcademicYear($school?->id);
    $quickActions = [
        ['icon' => 'users',   'title' => 'Add students',  'helper' => 'Enroll a learner into their class',      'href' => url('/admin/student/add'), 'missing' => $doneSteps < $totalSteps && $nextStep !== null],
        ['icon' => 'classes', 'title' => 'Add teachers',  'helper' => 'Create staff accounts and assign classes', 'href' => url('/admin/teacher/add'), 'missing' => $doneSteps < $totalSteps && $nextStep !== null],
        ['icon' => 'message', 'title' => 'Send WhatsApp', 'helper' => 'Message the opted-in parent group',        'href' => url('/admin/whatsapp/dashboard'), 'missing' => false],
        ['icon' => 'calendar','title' => 'Record attendance','helper' => 'Mark today\'s register',               'href' => url('/admin/attendance/add'),  'missing' => false],
        ['icon' => 'reports', 'title' => 'Report cards',  'helper' => 'Generate end-of-term cards',             'href' => url('/admin/reports/cards'), 'missing' => false],
    ];
@endphp
<style>
.dv2-shell{background:var(--d-canvas,#FAFAF5);min-height:60vh;padding:12px}
.dv2-panel{background:#fff;border:1px solid var(--d-border,#E2E8F0);border-radius:16px;padding:24px;max-width:1180px;margin:0 auto}
.dv2-hello{font-size:15px;color:#64748B;margin:0}
.dv2-h1{font-family:Sora,var(--d-font-display,sans-serif);font-weight:600;font-size:32px;letter-spacing:-0.02em;color:#0F172A;margin:2px 0 0;line-height:1.25}
.dv2-hread{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;justify-content:space-between}
.dv2-year{min-width:200px}
.dv2-year-label{font-size:13px;font-weight:600;color:#334155;margin-bottom:4px}
.dv2-year-chip{min-height:44px;display:inline-flex;align-items:center;padding:0 12px;border:1px solid var(--d-border,#E2E8F0);border-radius:8px;font-size:14px;color:#0F172A;background:#fff}
.dv2-banner{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:14px;padding:14px 16px;margin:18px 0 4px;color:#0F172A}
.dv2-banner-title{font-weight:700;margin:0 0 2px}
.dv2-banner-meta{font-size:13.5px;color:#334155;margin:0 0 8px}
.dv2-banner-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.dv2-banner-bar{position:relative;height:6px;background:#DCFCE7;border-radius:999px;flex:1 1 140px;overflow:hidden}
.dv2-banner-fill{position:absolute;inset:0 auto 0 0;background:#14532D;border-radius:999px}
.dv2-btn{min-height:44px;display:inline-flex;align-items:center;padding:0 14px;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;background:#0F766E;color:#fff}
.dv2-x{min-height:44px;min-width:44px;border:0;background:transparent;border-radius:10px;font-size:16px;color:#334155;cursor:pointer}
.dv2-grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:28px;margin-top:22px;align-items:start}
.dv2-tiles{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.dv2-tile{display:flex;gap:12px;align-items:flex-start;padding:16px;border:1px solid var(--d-border,#E2E8F0);border-radius:14px;background-image:radial-gradient(#E2E8F0 1px,transparent 1.4px);background-size:14px 14px;background-color:#fff;text-decoration:none;color:inherit;min-height:72px}
.dv2-ico{width:40px;height:40px;flex:none;border-radius:12px;background:#F1F5F9;display:flex;align-items:center;justify-content:center}
.dv2-tile-title{font-size:16px;font-weight:700;margin:0 0 2px}
.dv2-tile-help{font-size:13.5px;color:#64748B;margin:0}
.dv2-tile-help--missing{color:#78350F}
.dv2-tile--span{grid-column:1 / -1}
.dv2-snapshot{border:1px solid var(--d-border,#E2E8F0);border-radius:14px;background:#fff;padding:6px 16px}
.dv2-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 0;border-bottom:1px solid #F1F5F9}
.dv2-row:last-child{border-bottom:0}
.dv2-row-label{font-size:14.5px;font-weight:700;color:#0F172A}
.dv2-row-sub{font-size:12.5px;color:#64748B;margin:0}
.dv2-row-value{font-family:Sora,sans-serif;font-weight:600;font-size:22px;color:#0F172A}
.dv2-early-badge{background:#FEF3C7;color:#78350F;font-size:11.5px;font-weight:700;border-radius:999px;padding:2px 8px;display:inline-block}
@media (max-width:767px){
    .dv2-shell{padding:0}
    .dv2-panel{border-radius:0;border-left:0;border-right:0;padding:16px}
    .dv2-h1{font-size:24px}
    .dv2-grid{grid-template-columns:1fr;gap:18px}
    .dv2-tiles{grid-template-columns:1fr}
    .dv2-tile--span{grid-column:auto}
    .dv2-year{width:100%}
    .dv2-year-chip{width:100%;justify-content:space-between}
}
@media (max-width:767px) and (min-width:361px){
    .dv2-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}
}
</style>
<div class="dv2-shell" data-testid="dashboard-v2-shell">
    <div class="dv2-panel" data-testid="dashboard-v2-panel">
        @include('partials.message')
        <div class="dv2-hread" data-testid="dashboard-v2-head">
            <div>
                <p class="dv2-hello" data-testid="dashboard-v2-greeting">
                    Welcome back{{ isset($greeting['name']) && $greeting['name'] !== '' ? ', '.($greeting['name']) : '' }}
                </p>
                <h1 class="dv2-h1" data-testid="dashboard-v2-title">{{ $schoolLabel }}</h1>
            </div>
            <div class="dv2-year" data-testid="dashboard-v2-year">
                <div class="dv2-year-label">Academic year</div>
                <span class="dv2-year-chip" role="status" aria-label="Academic year" data-testid="dashboard-v2-year-chip">{{ $currentYear?->name ?? 'No academic year yet' }}{{ $currentYear?->status === 1 ? ' (current)' : '' }}</span>
            </div>
        </div>

        @if($bannerShown)
            <div class="dv2-banner" data-testid="dashboard-v2-banner" role="status">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                    <div>
                        <h2 class="dv2-banner-title" data-testid="dashboard-v2-banner-title">Finish setting up {{ $schoolLabel }}</h2>
                        <p class="dv2-banner-meta" data-testid="dashboard-v2-banner-progress">
                            {{ $doneSteps }} of {{ $totalSteps }} steps done.{{ $nextStep !== null ? ' Next: '.$nextStep.'.' : '' }}
                        </p>
                    </div>
                    <a href="{{ url('/dismiss-onboarding') }}" class="dv2-x" data-testid="dashboard-v2-banner-dismiss" aria-label="Hide setup banner. Progress stays in the sidebar.">✕</a>
                </div>
                <div class="dv2-banner-row">
                    <div class="dv2-banner-bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $totalSteps }}" aria-valuenow="{{ $doneSteps }}" data-testid="dashboard-v2-banner-bar">
                        <span class="dv2-banner-fill" style="width:{{ $totalSteps > 0 ? (int) round($doneSteps / $totalSteps * 100) : 0 }}%"></span>
                    </div>
                    <a href="{{ url('/admin/onboarding/wizard') }}" class="dv2-btn" data-testid="dashboard-v2-banner-continue">Continue setup →</a>
                </div>
            </div>
        @endif

        <div class="dv2-grid" data-testid="dashboard-v2-grid">
            <div>
                <div class="dv2-tiles" data-testid="dashboard-v2-actions">
                    @foreach($quickActions as $i => $action)
                        <a class="dv2-tile {{ count($quickActions) % 2 === 1 && $i === count($quickActions) - 1 ? 'dv2-tile--span' : '' }}"
                           href="{{ $action['href'] }}" data-testid="dashboard-v2-tile-{{ $i + 1 }}">
                            <span class="dv2-ico" aria-hidden="true">
                                @if($action['icon'] === 'users')
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                @elseif($action['icon'] === 'classes')
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                                @elseif($action['icon'] === 'message')
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5c-1.2 0-2.4-.25-3.4-.7L3 21l1.7-6.1A8.5 8.5 0 1 1 21 11.5z"/></svg>
                                @elseif($action['icon'] === 'calendar')
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                @else
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg>
                                @endif
                            </span>
                            <span>
                                <p class="dv2-tile-title">{{ $action['title'] }}</p>
                                <p class="dv2-tile-help {{ $action['missing'] ? 'dv2-tile-help--missing' : '' }}" data-testid="dashboard-v2-tile-{{ $i + 1 }}-help">
                                    @if($action['missing'])
                                        Finish "{{ $nextStep }}" in setup first.
                                    @else
                                        {{ $action['helper'] }}
                                    @endif
                                </p>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
            <aside class="dv2-snapshot" data-testid="dashboard-v2-snapshot" aria-label="School snapshot">
                <div class="dv2-row">
                    <div><div class="dv2-row-label">Students</div><p class="dv2-row-sub">Enrolled this year</p></div>
                    <div class="dv2-row-value" data-testid="dashboard-v2-students">{{ $dashboard['studentCount'] ?? 0 }}</div>
                </div>
                <div class="dv2-row">
                    <div><div class="dv2-row-label">Teachers</div><p class="dv2-row-sub">Active staff</p></div>
                    <div class="dv2-row-value">{{ $dashboard['teacherCount'] ?? 0 }}</div>
                </div>
                <div class="dv2-row">
                    <div><div class="dv2-row-label">Parents</div><p class="dv2-row-sub">Linked contacts</p></div>
                    <div class="dv2-row-value">{{ $dashboard['parentCount'] ?? 0 }}</div>
                </div>
                <div class="dv2-row">
                    <div><div class="dv2-row-label">Non-teaching staff</div><p class="dv2-row-sub">Support roles</p></div>
                    <div class="dv2-row-value">{{ $dashboard['nonteachingCount'] ?? 0 }}</div>
                </div>
                <div class="dv2-row">
                    <div><div class="dv2-row-label">WhatsApp parents</div><p class="dv2-row-sub">Opted-in to updates</p></div>
                    <div class="dv2-row-value">{{ $dashboard['whatsapp']['parentsOptedIn'] ?? 0 }}</div>
                </div>
                <div class="dv2-row">
                    <div><div class="dv2-row-label">Messages this month</div><p class="dv2-row-sub">Sent from the school</p></div>
                    <div class="dv2-row-value">{{ $dashboard['whatsapp']['messagesThisMonth'] ?? 0 }}</div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
