{{-- SPDX-License-Identifier: MIT --}}
{{--
    Admin dashboard v2 — PR1 "school-with-data first screen" (combined
    2026-10-08 handoff). Built from concepts/admin-mvp. Selectable via
    config('dashboard.v2_enabled') or ?v2=1 (default off).

    One count: the setup bar and the sidebar chip read OnboardingStepsService
    (neutral step names via displayLabel — K25). No promo carousel, no Toshi
    slide, no Connected tools card.
--}}
@extends('layouts.admin.layout')

@section('content')
@php
    $v2 = $v2Data ?? [];
    $v2User = auth()->user();
    $school = $v2User?->school;
    $schoolLabel = $v2['schoolName'] ?? ($school?->name ?? ($greeting['name'] ?? 'KlassApp'));
    $setup = $v2['setup'] ?? ['total' => 0, 'done' => 0, 'percent' => 100, 'next' => null, 'labels' => [], 'incomplete' => [], 'dismissed' => false, 'routes' => []];
    $kpis = $v2['kpis'] ?? [];
    $charts = $v2['charts'] ?? ['per_class' => [], 'attendance_weeks' => [], 'gender' => ['girls' => 0, 'boys' => 0, 'not_specified' => 0, 'total' => 0], 'fees_months' => [], 'exam' => null];
    $activity = $v2['activity'] ?? [];
    $currency = $v2['currency'] ?? 'UGX';
    $state = $v2['state'] ?? 'data';

    $years = $school
        ? \App\Models\AcademicYear::where('school_id', $school->id)->orderByDesc('start_date')->get()
        : collect();
    $currentYear = $years->firstWhere('status', 1) ?? $years->first();

    $wizardUrl = url('/admin/onboarding/wizard');
    $dismissUrl = url('/dismiss-onboarding');
    $setupShown = ! $setup['dismissed'] && $setup['total'] > 0 && $setup['done'] < $setup['total'];
    // Mid-setup presentation (concept): any chart without data = quick-action tiles.
    $midSetup = count($charts['per_class'] ?? []) === 0 || count($charts['fees_months'] ?? []) === 0;
    $nextHref = $setup['next']
        ? (($setup['routes'][$setup['next']['key']] ?? null) ?: $wizardUrl.'?step='.$setup['next']['key'])
        : $wizardUrl;

    $toshiSwitch = app(\App\Services\Toshi\ToshiUiSwitch::class);
    $toshiMode = $school ? $toshiSwitch->mode($v2User) : \App\Enums\ToshiMode::Preview;

    // Quick actions (kept from v2) — row in the data state, tiles otherwise.
    $quickActions = [
        ['icon' => 'users', 'title' => 'Add students', 'helper' => 'One by one or from a spreadsheet', 'href' => url('/admin/student/add'), 'prereqs' => ['standards']],
        ['icon' => 'clipboard-list', 'title' => 'Enter marks', 'helper' => 'Open exams are ready for marks', 'href' => url('/admin/exams'), 'prereqs' => ['standards']],
        ['icon' => 'file-text', 'title' => 'Generate report cards', 'helper' => 'Ready once marks exist for an exam', 'href' => url('/admin/reports/cards'), 'prereqs' => ['students']],
        ['icon' => 'message-circle', 'title' => 'Send report cards on WhatsApp', 'helper' => 'Needs report cards first', 'href' => url('/admin/whatsapp/dashboard'), 'prereqs' => ['whatsapp_verify']],
        ['icon' => 'wallet', 'title' => 'Fees', 'helper' => 'Record payments and send reminders', 'href' => url('/admin/fees/payments'), 'prereqs' => ['fees']],
    ];
    $missingFor = function (array $action) use ($setup) {
        foreach ($action['prereqs'] as $prereq) {
            if (in_array($prereq, $setup['incomplete'], true)) {
                return $setup['labels'][$prereq] ?? $prereq;
            }
        }

        return null;
    };
@endphp
<style>
.dv2-shell{background:var(--d-canvas,#FAFAF5);min-height:60vh;padding:12px}
.dv2-panel{background:#fff;border:1px solid var(--d-border,#E2E8F0);border-radius:16px;padding:24px;max-width:1180px;margin:0 auto}
.dv2-hread{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;justify-content:space-between}
.dv2-hello{font-size:15px;color:#64748B;margin:0}
.dv2-h1{font-family:Sora,var(--d-font-display,sans-serif);font-weight:600;font-size:30px;letter-spacing:-0.02em;color:#0F172A;margin:2px 0 0;line-height:1.25}
.dv2-hsub{color:#64748B;font-size:14px;margin:4px 0 0}
.dv2-year{min-width:200px}
.dv2-year-label{font-size:13px;font-weight:600;color:#334155;margin-bottom:4px;display:block}
.dv2-year select{min-height:44px;width:100%;min-width:200px;padding:0 12px;border:1px solid var(--d-border,#E2E8F0);border-radius:8px;font-size:14px;color:#0F172A;background:#fff}
.dv2-year-chip{min-height:44px;display:inline-flex;align-items:center;padding:0 12px;border:1px solid var(--d-border,#E2E8F0);border-radius:8px;font-size:14px;color:#0F172A;background:#fff}
.dv2-setup{display:flex;flex-wrap:wrap;align-items:center;gap:12px;min-height:56px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:14px;padding:10px 14px;margin:18px 0 4px}
.dv2-setup b{font-size:14.5px;color:#0F172A;white-space:nowrap}
.dv2-setup-bar{position:relative;height:6px;background:#DCFCE7;border-radius:999px;flex:1 1 140px;overflow:hidden;min-width:120px}
.dv2-setup-bar i{position:absolute;inset:0 auto 0 0;background:#14532D;border-radius:999px}
.dv2-setup-next{font-size:13.5px;color:#334155;white-space:nowrap}
.dv2-btn{min-height:44px;display:inline-flex;align-items:center;padding:0 14px;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;background:#0F766E;color:#fff;border:0;cursor:pointer}
.dv2-btn--ghost{background:#fff;color:#14532D;border:1px solid #BBF7D0}
.dv2-btn--soft{background:#fff;color:#0F172A;border:1px solid var(--d-border,#E2E8F0);font-weight:600}
.dv2-x{min-height:44px;min-width:44px;display:inline-flex;align-items:center;justify-content:center;border:0;background:transparent;border-radius:10px;font-size:16px;color:#334155;cursor:pointer;text-decoration:none}
.dv2-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-top:18px}
.dv2-kpi{border:1px solid var(--d-border,#E2E8F0);border-radius:14px;background:#fff;padding:14px;display:flex;flex-direction:column;gap:6px;min-height:96px}
.dv2-kpi-label{font-size:12.5px;font-weight:600;color:#475569;display:flex;align-items:center;gap:6px}
.dv2-kpi-value{font-family:Sora,sans-serif;font-weight:600;font-size:26px;color:#0F172A;line-height:1.1}
.dv2-kpi-sub{font-size:12.5px;color:#64748B;margin:0;margin-top:auto}
.dv2-kpi-sub.up{color:#15803D}
.dv2-kpi-sub.down{color:#B91C1C}
.dv2-kpi-sub.warn{color:#78350F}
.dv2-meter{position:relative;height:6px;background:#F1F5F9;border-radius:999px;overflow:hidden;display:block}
.dv2-meter i{position:absolute;inset:0 auto 0 0;background:#15803D;border-radius:999px}
.dv2-qa{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
.dv2-qa .dv2-btn{gap:8px}
.dv2-qat{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:16px}
.dv2-qt{display:flex;gap:12px;align-items:flex-start;padding:14px;border:1px solid var(--d-border,#E2E8F0);border-radius:14px;background:#fff;text-decoration:none;color:inherit;min-height:72px}
.dv2-qt b{font-size:15px;color:#0F172A}
.dv2-qt span.help{display:block;font-size:13px;color:#64748B;margin-top:2px}
.dv2-qt span.help.missing{color:#78350F}
.dv2-ico{width:38px;height:38px;flex:none;border-radius:11px;background:#F1F5F9;display:flex;align-items:center;justify-content:center}
.dv2-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:18px}
.dv2-card{border:1px solid var(--d-border,#E2E8F0);border-radius:14px;background:#fff;padding:16px}
.dv2-card h2{font-size:15.5px;font-weight:700;color:#0F172A;margin:0}
.dv2-card small{display:block;font-size:12.5px;color:#64748B;margin:2px 0 10px}
.dv2-empty-in{border:1px dashed var(--d-border,#E2E8F0);border-radius:12px;padding:20px 14px;text-align:center;color:#334155}
.dv2-empty-in b{display:block;font-size:14.5px;color:#0F172A;margin-bottom:4px}
.dv2-empty-in p{margin:0 0 10px;font-size:13.5px;color:#475569}
.hbars{display:flex;flex-direction:column;gap:8px}
.hb{display:grid;grid-template-columns:110px 1fr 52px;align-items:center;gap:10px;font-size:13px;color:#334155}
.hb .t{background:#F1F5F9;border-radius:999px;height:10px;position:relative;overflow:hidden}
.hb .t i{position:absolute;inset:0 auto 0 0;background:#1E6FD9;border-radius:999px}
.hb b{text-align:right;color:#0F172A}
.stack{display:flex;height:16px;border-radius:999px;overflow:hidden;background:#F1F5F9}
.stack i{height:100%}
.legend{display:flex;flex-wrap:wrap;gap:12px;margin-top:10px;font-size:12.5px;color:#475569}
.legend span{display:inline-flex;align-items:center;gap:6px}
.legend i{width:10px;height:10px;border-radius:3px;display:inline-block}
.act{list-style:none;margin:0;padding:0}
.act li{display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #F1F5F9;font-size:13.5px;color:#334155}
.act li:last-child{border-bottom:0}
.act .ib{width:30px;height:30px;border-radius:9px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;flex:none}
.act time{margin-left:auto;color:#94A3B8;font-size:12px;white-space:nowrap}
.act-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:2px}
.act-head a{font-size:13px;color:#0F766E;font-weight:600;text-decoration:none}
.dv2-soon{display:flex;align-items:center;gap:8px;margin:18px 0 2px;font-size:13px;color:#64748B}
.dv2-early{background:#FEF3C7;color:#78350F;font-size:11.5px;font-weight:700;border-radius:999px;padding:2px 8px;display:inline-block}
.dv2-new-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px;border:1px dashed var(--d-border,#E2E8F0);border-radius:14px;padding:32px 16px;margin-top:18px}
.dv2-new-empty b{font-size:16px;color:#0F172A}
.dv2-new-empty p{margin:0;color:#475569;font-size:13.5px;max-width:520px}
@media (max-width:1023px){.dv2-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:767px){
    .dv2-shell{padding:0}
    .dv2-panel{border-radius:0;border-left:0;border-right:0;padding:16px}
    .dv2-h1{font-size:24px}
    .dv2-grid2{grid-template-columns:1fr}
    .dv2-year{width:100%}
    .hb{grid-template-columns:84px 1fr 44px}
}
</style>
<div class="dv2-shell" data-testid="dashboard-v2-shell">
    <div class="dv2-panel" data-testid="dashboard-v2-panel">
        @include('partials.message')

        {{-- 1 · Header --}}
        <div class="dv2-hread" data-testid="dashboard-v2-head">
            <div>
                <p class="dv2-hello" data-testid="dashboard-v2-greeting">{{ $greeting['phrase'] ?? 'Welcome' }}{{ !empty($greeting['name']) ? ', '.$greeting['name'] : '' }}</p>
                <h1 class="dv2-h1" data-testid="dashboard-v2-title">
                    {{ $schoolLabel }}@if(!empty($v2['termLabel']))<span class="dv2-hsub"> · {{ $v2['termLabel'] }}</span>@endif
                </h1>
            </div>
            <div class="dv2-year" data-testid="dashboard-v2-year">
                @if($years->isNotEmpty())
                    <label class="dv2-year-label" for="dv2-year-select">Academic year</label>
                    <select id="dv2-year-select" aria-label="Academic year" data-testid="dashboard-v2-year-select">
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected($currentYear && $year->id === $currentYear->id)>
                                {{ $year->name }}{{ (int) $year->status === 1 ? ' (current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <div class="dv2-year-label">Academic year</div>
                    <span class="dv2-year-chip" role="status" aria-label="Academic year" data-testid="dashboard-v2-year-chip">No academic year yet</span>
                @endif
            </div>
        </div>

        {{-- 2 · Setup bar (only while incomplete) --}}
        @if($setupShown)
            <div class="dv2-setup" data-testid="dashboard-v2-setup-bar" role="region" aria-label="School setup">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#14532D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8M13 12h8M13 18h8"/></svg>
                <b data-testid="dashboard-v2-setup-count">Setup {{ $setup['done'] }} of {{ $setup['total'] }} done</b>
                <span class="dv2-setup-bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $setup['total'] }}" aria-valuenow="{{ $setup['done'] }}" aria-label="Setup progress" data-testid="dashboard-v2-setup-progress">
                    <i style="width:{{ $setup['percent'] }}%"></i>
                </span>
                @if($setup['next'])
                    <span class="dv2-setup-next" data-testid="dashboard-v2-setup-next">Next: {{ $setup['next']['label'] }}</span>
                @endif
                <a href="{{ $nextHref }}" class="dv2-btn" data-testid="dashboard-v2-setup-continue">Continue setup</a>
                @if($toshiMode === \App\Enums\ToshiMode::Onboarding)
                    <button type="button" class="dv2-btn dv2-btn--ghost" data-testid="dashboard-v2-toshi-setup"
                            onclick="window.toshiSetCollapsed && window.toshiSetCollapsed(false); window.dispatchEvent(new CustomEvent('toshi-maximize'));">
                        Set up with Toshi
                    </button>
                @endif
                <a href="{{ $dismissUrl }}" class="dv2-x" data-testid="dashboard-v2-setup-dismiss" aria-label="Hide setup bar. Progress stays in the sidebar.">✕</a>
            </div>
        @endif

        @if($state === 'new')
            {{-- Brand new: quick-action tiles + zeroed tiles + one empty state --}}
            <div class="dv2-qat" data-testid="dashboard-v2-actions">
                @foreach($quickActions as $i => $action)
                    @php $missing = $missingFor($action); @endphp
                    <a class="dv2-qt" href="{{ $missing ? ($setup['routes'][$action['prereqs'][0]] ?? $wizardUrl) : $action['href'] }}" data-testid="dashboard-v2-tile-{{ $i + 1 }}">
                        <span class="dv2-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg></span>
                        <span>
                            <b>{{ $action['title'] }}</b>
                            <span class="help {{ $missing ? 'missing' : '' }}">{{ $missing ? 'Finish “'.$missing.'” in setup first.' : $action['helper'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="dv2-kpis" data-testid="dashboard-v2-kpis">
                <div class="dv2-kpi"><span class="dv2-kpi-label">Students</span><span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-students">0</span><p class="dv2-kpi-sub">None added yet</p></div>
                <div class="dv2-kpi"><span class="dv2-kpi-label">Staff</span><span class="dv2-kpi-value">{{ ($kpis['staff']['value'] ?? 1) }}</span><p class="dv2-kpi-sub">{{ $kpis['staff']['detail'] ?? 'Just you' }}</p></div>
                <div class="dv2-kpi"><span class="dv2-kpi-label">Attendance this week</span><span class="dv2-kpi-value">–</span><p class="dv2-kpi-sub">Starts after students are added</p></div>
                <div class="dv2-kpi"><span class="dv2-kpi-label">Fees collected</span><span class="dv2-kpi-value">–</span><p class="dv2-kpi-sub warn">No fee structure yet</p></div>
                <div class="dv2-kpi"><span class="dv2-kpi-label">Report cards ready</span><span class="dv2-kpi-value">–</span><p class="dv2-kpi-sub">After the first exam</p></div>
            </div>

            <div class="dv2-new-empty" data-testid="dashboard-v2-empty-students">
                <b>Add your students to get started</b>
                <p>Your dashboard fills in as you add students, take attendance and enter marks. You can add them one by one or import a spreadsheet.</p>
                <span style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center">
                    <a class="dv2-btn" href="{{ url('/admin/student/add') }}">Add student</a>
                    <a class="dv2-btn dv2-btn--soft" href="{{ url('/admin/student/import') }}">Import a list</a>
                </span>
            </div>
        @else
            {{-- 3 · Quick actions: compact row only when every chart has data --}}
            @if($state === 'data' && ! $midSetup)
                <nav class="dv2-qa" aria-label="Quick actions" data-testid="dashboard-v2-actions">
                    @foreach($quickActions as $action)
                        <a class="dv2-btn dv2-btn--soft" href="{{ $action['href'] }}">{{ $action['title'] }}</a>
                    @endforeach
                </nav>
            @else
                <div class="dv2-qat" data-testid="dashboard-v2-actions">
                    @foreach($quickActions as $i => $action)
                        @php $missing = $missingFor($action); @endphp
                        <a class="dv2-qt" href="{{ $missing ? ($setup['routes'][$action['prereqs'][0]] ?? $wizardUrl) : $action['href'] }}" data-testid="dashboard-v2-tile-{{ $i + 1 }}">
                            <span class="dv2-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg></span>
                            <span>
                                <b>{{ $action['title'] }}</b>
                                <span class="help {{ $missing ? 'missing' : '' }}">{{ $missing ? 'Finish “'.$missing.'” in setup first.' : $action['helper'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- 4 · KPI tiles --}}
            <div class="dv2-kpis" data-testid="dashboard-v2-kpis">
                <div class="dv2-kpi">
                    <span class="dv2-kpi-label">Students</span>
                    <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-students">{{ $kpis['students']['value'] ?? 0 }}</span>
                    <p class="dv2-kpi-sub {{ ($kpis['students']['direction'] ?? null) === 'up' ? 'up' : '' }}">{{ $kpis['students']['detail'] ?? '—' }}</p>
                </div>
                <div class="dv2-kpi">
                    <span class="dv2-kpi-label">Staff</span>
                    <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-staff">{{ $kpis['staff']['value'] ?? 0 }}</span>
                    <p class="dv2-kpi-sub">{{ $kpis['staff']['detail'] ?? '—' }}</p>
                </div>
                <div class="dv2-kpi">
                    <span class="dv2-kpi-label">Attendance this week</span>
                    <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-attendance">{{ $kpis['attendance']['value'] ?? '–' }}</span>
                    <p class="dv2-kpi-sub {{ ($kpis['attendance']['direction'] ?? null) === 'up' ? 'up' : (($kpis['attendance']['direction'] ?? null) === 'down' ? 'down' : '') }}">{{ $kpis['attendance']['detail'] ?? '—' }}</p>
                </div>
                <div class="dv2-kpi">
                    <span class="dv2-kpi-label">Fees collected</span>
                    @if(($kpis['fees']['state'] ?? 'ok') === 'ok')
                        <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-fees">{{ $kpis['fees']['percent'] }}%</span>
                        <span class="dv2-meter" role="img" aria-label="{{ $kpis['fees']['percent'] }}% collected"><i style="width:{{ $kpis['fees']['percent'] }}%"></i></span>
                        <p class="dv2-kpi-sub">{{ $kpis['fees']['collected_label'] }} of {{ $kpis['fees']['expected_label'] }}</p>
                    @else
                        <span class="dv2-kpi-value">–</span>
                        <p class="dv2-kpi-sub warn">Set up {{ $setup['labels']['fees'] ?? 'fees' }} first · <a href="{{ $setup['routes']['fees'] ?? url('/admin/fees-categories') }}">Set up fees</a></p>
                    @endif
                </div>
                <div class="dv2-kpi">
                    <span class="dv2-kpi-label">Report cards ready</span>
                    <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-reports">{{ $kpis['report_cards']['ready'] ?? 0 }}</span>
                    @if(($kpis['report_cards']['percent'] ?? null) !== null)
                        <span class="dv2-meter" role="img" aria-label="{{ $kpis['report_cards']['percent'] }}% of students"><i style="width:{{ $kpis['report_cards']['percent'] }}%"></i></span>
                    @endif
                    <p class="dv2-kpi-sub">of {{ $kpis['report_cards']['students'] ?? 0 }}@if(!empty($kpis['report_cards']['exam'])) · {{ $kpis['report_cards']['exam'] }}@elseif(($kpis['report_cards']['state'] ?? '') === 'no_exam') · No exam closed yet @endif</p>
                </div>
            </div>

            {{-- 5 · Charts --}}
            <div class="dv2-grid2">
                <div class="dv2-card" data-testid="dashboard-v2-chart-performance">
                    <h2>Performance by class</h2>
                    <small>{{ $charts['exam'] ? $charts['exam'].' · average mark' : 'Latest exam · average mark' }}</small>
                    @if(count($charts['per_class'] ?? []) > 0)
                        @php
                            $pc = $charts['per_class'];
                            $pcAria = implode(', ', array_map(fn ($r) => $r['label'].' '.$r['value'].'%', $pc));
                        @endphp
                        <div class="hbars" role="img" aria-label="Performance by class: {{ $pcAria }}">
                            @foreach($pc as $row)
                                <div class="hb"><span>{{ $row['label'] }}</span><span class="t"><i style="width:{{ min(100, (float) $row['value']) }}%"></i></span><b>{{ $row['value'] }}%</b></div>
                            @endforeach
                        </div>
                    @else
                        <div class="dv2-empty-in">
                            <b>No marks entered yet</b>
                            <p>Averages appear when teachers enter marks for an exam.</p>
                            <a class="dv2-btn dv2-btn--soft" href="{{ $setup['routes']['exams'] ?? url('/admin/exams') }}" data-testid="dashboard-v2-go-exams">Go to exams</a>
                        </div>
                    @endif
                </div>

                <div class="dv2-card" data-testid="dashboard-v2-chart-attendance">
                    <h2>Attendance trend</h2>
                    <small>Last 8 weeks · whole school</small>
                    @php
                        $aw = $charts['attendance_weeks'] ?? [];
                        $awHas = count(array_filter($aw, fn ($w) => $w['value'] !== null)) > 0;
                        $awAria = implode(', ', array_map(fn ($w) => $w['label'].' '.($w['value'] ?? 'no data').'%', $aw));
                    @endphp
                    @if($awHas)
                        <x-chart type="line" :height="180"
                                 :labels="array_map(fn ($w) => $w['label'], $aw)"
                                 :datasets="[['data' => array_map(fn ($w) => $w['value'], $aw), 'borderColor' => '#15803D', 'backgroundColor' => 'rgba(21,128,61,0.08)', 'fill' => true, 'tension' => 0.3]]"
                                 :options="['scales' => ['y' => ['min' => 80, 'max' => 100]]]"
                                 aria-label="Attendance trend, last 8 weeks: {{ $awAria }}"
                                 empty-message="No attendance recorded yet — it appears after the first register." />
                    @else
                        <div class="dv2-empty-in"><b>No register taken yet</b><p>Attendance appears after the class teacher takes the first register.</p></div>
                    @endif
                </div>

                <div class="dv2-card" data-testid="dashboard-v2-chart-gender">
                    <h2>Students by gender</h2>
                    @php
                        $g = $charts['gender'] ?? ['girls' => 0, 'boys' => 0, 'not_specified' => 0, 'total' => 0];
                        $gt = max(1, (int) $g['total']);
                        $gp = fn ($n) => (int) round($n / $gt * 100);
                    @endphp
                    <small>{{ $g['total'] }} students</small>
                    @if($g['total'] > 0)
                        <div class="stack" role="img" aria-label="Girls {{ $gp($g['girls']) }}%, boys {{ $gp($g['boys']) }}%, not specified {{ $gp($g['not_specified']) }}%">
                            <i style="width:{{ $gp($g['girls']) }}%;background:#B45309"></i><i style="width:{{ $gp($g['boys']) }}%;background:#1E6FD9"></i><i style="width:{{ $gp($g['not_specified']) }}%;background:#64748B"></i>
                        </div>
                        <div class="legend">
                            <span><i style="background:#B45309"></i>Girls {{ $gp($g['girls']) }}%</span>
                            <span><i style="background:#1E6FD9"></i>Boys {{ $gp($g['boys']) }}%</span>
                            <span><i style="background:#64748B"></i>Not specified {{ $gp($g['not_specified']) }}%</span>
                        </div>
                    @else
                        <div class="dv2-empty-in"><b>No students yet</b><p>The split appears once students are added.</p></div>
                    @endif
                </div>

                <div class="dv2-card" data-testid="dashboard-v2-chart-fees">
                    <h2>Fees collection</h2>
                    <small>Collected against expected, by month</small>
                    @if(count($charts['fees_months'] ?? []) > 0)
                        @php
                            $fm = $charts['fees_months'];
                            $fmAria = implode(', ', array_map(fn ($m) => $m['label'].' collected '.$currency.' '.number_format($m['collected']), $fm));
                        @endphp
                        <x-chart type="bar" :height="190"
                                 :labels="array_map(fn ($m) => $m['label'], $fm)"
                                 :datasets="[
                                     ['label' => 'Collected', 'data' => array_map(fn ($m) => $m['collected'], $fm), 'backgroundColor' => '#15803D', 'borderRadius' => 4],
                                     ['label' => 'Expected', 'data' => array_map(fn ($m) => $m['expected'], $fm), 'backgroundColor' => 'rgba(148,163,184,0.10)', 'borderColor' => '#94A3B8', 'borderDash' => [4, 3], 'borderWidth' => 1.5, 'borderRadius' => 4],
                                 ]"
                                 :options="['plugins' => ['dsValueLabels' => ['display' => true]], 'layout' => ['padding' => ['top' => 14]]]"
                                 aria-label="Fees collected by month: {{ $fmAria }}"
                                 empty-message="No fee collections recorded yet." />
                        <div class="legend">
                            <span><i style="background:#15803D"></i>Collected</span>
                            <span><i style="border:1px dashed #94A3B8;background:transparent"></i>Expected</span>
                        </div>
                    @else
                        <div class="dv2-empty-in">
                            <b>No fee structure{{ !empty($v2['termLabel']) ? ' for '.$v2['termLabel'] : '' }}</b>
                            <p>Set the term's fees to start recording payments.</p>
                            <a class="dv2-btn" href="{{ $setup['routes']['fees'] ?? url('/admin/fees-categories') }}" data-testid="dashboard-v2-set-up-fees">Set up fees</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 6 · Recent activity --}}
            <div class="dv2-card" style="margin-top:16px" data-testid="dashboard-v2-activity">
                <div class="act-head">
                    <h2>Recent activity</h2>
                    <a href="{{ url('/admin/activity') }}">See all</a>
                </div>
                @if(count($activity) > 0)
                    <ul class="act">
                        @foreach($activity as $row)
                            <li>
                                <span class="ib" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                                <span>
                                    @foreach($row['segments'] as $segment)
                                        @if(is_array($segment))<b>{{ $segment['bold'] }}</b>@else{{ $segment }}@endif
                                    @endforeach
                                </span>
                                <time datetime="{{ $row['time'] }}">{{ $row['time'] }}</time>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p style="margin:6px 0 0;color:#64748B;font-size:13.5px" data-testid="dashboard-v2-activity-empty">No recent activity yet.</p>
                @endif
            </div>
        @endif

        {{-- 7 · Toshi line --}}
        @if($toshiMode === \App\Enums\ToshiMode::Assistant)
            <p class="dv2-soon" data-testid="dashboard-v2-toshi-line">
                <span class="dv2-early" data-testid="dashboard-v2-early-pill">Early access</span>
                Toshi is on for this school.
            </p>
        @else
            <p class="dv2-soon" data-testid="dashboard-v2-toshi-line">
                <img src="{{ asset('images/klassapp-icon.svg') }}" alt="" width="16" height="16">
                <span data-testid="dashboard-v2-toshi-coming">Toshi, your school&#8217;s AI assistant, is coming soon.</span>
            </p>
        @endif
    </div>
</div>
@endsection
