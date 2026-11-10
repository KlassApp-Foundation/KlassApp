{{-- SPDX-License-Identifier: MIT --}}
{{--
    Admin dashboard. Built from concepts/admin-mvp. This is the only
    school-admin dashboard.

    One count: the setup bar and the sidebar chip read OnboardingStepsService
    (neutral step names via displayLabel — K25). The setup bar's "What's set up"
    list is OnboardingProgress, one line per finished step. No promo carousel,
    no Toshi slide, no Connected tools card.
--}}
@extends('layouts.admin.layout')

@section('content')
@php
    $v2 = $v2Data ?? [];
    $v2User = auth()->user();
    $school = $v2User?->school;
    $schoolLabel = $v2['schoolName'] ?? ($school?->name ?? ($greeting['name'] ?? 'KlassApp'));
    $setup = $v2['setup'] ?? ['total' => 0, 'done' => 0, 'percent' => 100, 'next' => null, 'labels' => [], 'incomplete' => [], 'dismissed' => false, 'routes' => [], 'summary' => [], 'destination' => null];
    $kpis = $v2['kpis'] ?? [];
    $charts = $v2['charts'] ?? ['per_class' => [], 'attendance_weeks' => [], 'gender' => ['girls' => 0, 'boys' => 0, 'not_specified' => 0, 'total' => 0], 'fees_months' => [], 'exam' => null];
    $activity = $v2['activity'] ?? [];
    $currency = $v2['currency'] ?? null;
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
    $nextHref = $setup['destination'] ?? $wizardUrl;

    $toshiSwitch = app(\App\Services\Toshi\ToshiUiSwitch::class);
    $toshiMode = $school ? $toshiSwitch->mode($v2User) : \App\Enums\ToshiMode::Preview;
    $toshiPathOpen = $school && app(\App\Services\Toshi\ToshiOnboardingPath::class)->openFor($school);
    $checklistSetup = $school && app(\App\Services\Onboarding\ChecklistOnboardingGate::class)->enabledFor($school);
    $ledger = ($checklistSetup && $school)
        ? \App\Onboarding\OnboardingProgress::checklistRows($school, (int) $v2User->id)
        : [];
    $profile = ($checklistSetup && $school)
        ? \App\Onboarding\OnboardingProgress::profile($school)
        : null;
    $setupFinished = $school && $school->onboarding_finished_at !== null;

    // Quick actions (kept from v2) — row in the data state, tiles otherwise.
    $quickActions = [
        ['icon' => 'user-plus', 'title' => 'Add students', 'helper' => 'One by one or from a spreadsheet', 'href' => url('/admin/student/add'), 'prereqs' => ['standards']],
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
<link rel="stylesheet" href="{{ asset('css/dashboard-v2.css') }}?v={{ filemtime(public_path('css/dashboard-v2.css')) }}">
@if($checklistSetup)
    <link rel="stylesheet" href="{{ asset('css/toshi-checklist-panel.css') }}?v={{ @filemtime(public_path('css/toshi-checklist-panel.css')) }}">
@endif
@if($checklistSetup && $setupFinished)
<script>document.documentElement.classList.add('checklist-setup-done');</script>
@endif
<div class="dv2-shell" data-testid="dashboard-v2-shell">
    <div class="dv2-panel" data-testid="dashboard-v2-panel">
        @include('partials.message')

        {{-- 1 · Header --}}
        <div class="dv2-hread" data-testid="dashboard-v2-head">
            <div>
                <h1 class="dv2-h1" data-testid="dashboard-v2-greeting">{{ $greeting['phrase'] ?? 'Welcome' }}{{ !empty($greeting['name']) ? ', '.$greeting['name'] : '' }}</h1>
                <p class="dv2-hsub" data-testid="dashboard-v2-title">{{ $v2['termLabel'] ?? '' }}</p>
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

        @if($checklistSetup && $profile && ! $setupFinished)
            <section class="dv2-profile" data-testid="dashboard-v2-profile">
                <h2>{{ $profile['name'] }}</h2>
                <p>{{ collect([$profile['country'], $profile['curriculum'], $profile['year'], $profile['term'] !== '' ? $profile['term'] : null])->filter()->implode(' · ') }}</p>
            </section>
            <details class="dv2-setup-summary" data-testid="dashboard-v2-setup-summary" open>
                <summary>What's set up</summary>
                <div class="dv2-ledger" data-testid="dashboard-v2-setup-ledger">
                    @php $ledgerGroup = null; @endphp
                    @forelse($ledger as $row)
                        @if(($row['group'] ?? '') !== $ledgerGroup)
                            @php $ledgerGroup = $row['group'] ?? ''; @endphp
                            <h3 class="dv2-ledger-group">{{ $ledgerGroup }}</h3>
                        @endif
                        <div class="dv2-ledger-row" data-testid="dashboard-v2-ledger-row">
                            <span>{{ $row['label'] }}</span>
                            <b>{{ $row['value'] }}</b>
                        </div>
                    @empty
                        <p class="dv2-setup-empty" data-testid="dashboard-v2-setup-empty">Nothing is set up yet.</p>
                    @endforelse
                </div>
            </details>
        @endif

        @if($setupShown && ! $checklistSetup)
            <div class="dv2-setup" data-testid="dashboard-v2-setup-bar" role="region" aria-label="School setup">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#14532D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8M13 12h8M13 18h8"/></svg>
                <b data-testid="dashboard-v2-setup-count">Setup {{ $setup['done'] }} of {{ $setup['total'] }} done</b>
                <span class="dv2-setup-bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $setup['total'] }}" aria-valuenow="{{ $setup['done'] }}" aria-label="Setup progress" data-testid="dashboard-v2-setup-progress" data-setup-done="{{ $setup['done'] }}" data-setup-total="{{ $setup['total'] }}">
                    <i style="width:{{ $setup['percent'] }}%"></i>
                </span>
                @if($setup['next'])
                    <span class="dv2-setup-next" data-testid="dashboard-v2-setup-next">Next: {{ $setup['next']['label'] }}</span>
                @endif
                <a href="{{ $nextHref }}" class="dv2-btn" data-testid="dashboard-v2-setup-continue">Continue setup</a>
                    @if($toshiMode === \App\Enums\ToshiMode::Onboarding)
                        @if($toshiPathOpen)
                        <button type="button" class="dv2-btn dv2-btn--ghost" data-testid="dashboard-v2-toshi-setup"
                                onclick="window.toshiSetCollapsed && window.toshiSetCollapsed(false); window.dispatchEvent(new CustomEvent('toshi-maximize'));">
                            Set up with Toshi
                        </button>
                        @else
                        <button type="button" disabled class="dv2-btn dv2-btn--ghost" data-testid="dashboard-v2-toshi-setup" aria-disabled="true">
                            Set up with Toshi
                            <span data-testid="dashboard-v2-toshi-soon">Coming soon</span>
                        </button>
                        @endif
                    @endif
                <form method="POST" action="{{ $dismissUrl }}" class="dv2-setup-dismiss" onsubmit="return confirm('Hide the setup bar? Your progress stays in the sidebar.');">
                    @csrf
                    <button type="submit" class="dv2-x" data-testid="dashboard-v2-setup-dismiss" aria-label="Hide setup bar. Progress stays in the sidebar.">Hide</button>
                </form>
                @php
                    $setupLines = array_values(array_filter(
                        is_array($setup['summary'] ?? null) ? $setup['summary'] : [],
                        fn ($line) => is_string($line) && trim($line) !== ''
                    ));
                @endphp
                <details class="dv2-setup-summary" data-testid="dashboard-v2-setup-summary" open>
                    <summary>What's set up</summary>
                    @if($setupLines !== [])
                        <ul>
                            @foreach($setupLines as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="dv2-setup-empty" data-testid="dashboard-v2-setup-empty">Nothing is set up yet.</p>
                    @endif
                </details>
            </div>
        @endif

        {{-- Hidden poller: a new link request still toasts, and the sidebar badge still updates. --}}
        @if((int) ($v2User->usergroup_id ?? 0) === 3)
            <livewire:admin.whatsapp-dashboard-card />
        @endif

        @if($state === 'new' && ! $checklistSetup)
            {{-- Brand new: quick-action tiles + zeroed tiles + one empty state --}}
            <div class="dv2-qat" data-testid="dashboard-v2-actions">
                @foreach($quickActions as $i => $action)
                    @php $missing = $missingFor($action); @endphp
                    @if($missing && $checklistSetup)
                        <div class="dv2-qt" data-testid="dashboard-v2-tile-{{ $i + 1 }}" data-icon="{{ $action['icon'] }}">
                            <span class="dv2-ico" aria-hidden="true"><x-ka-icon :name="$action['icon']" :size="18" tone="default" /></span>
                            <span>
                                <b>{{ $action['title'] }}</b>
                                <span class="help missing">Finish “{{ $missing }}” in setup first.</span>
                                <span class="dv2-qt-actions">
                                    <button type="button" class="dv2-btn" data-testid="dashboard-v2-tile-toshi-{{ $i + 1 }}"
                                            onclick="window.toshiSetCollapsed && window.toshiSetCollapsed(false);">Set up with Toshi</button>
                                    <a class="dv2-btn" href="{{ $nextHref }}" data-testid="dashboard-v2-tile-manual-{{ $i + 1 }}">Set up manually</a>
                                </span>
                            </span>
                        </div>
                    @else
                        <a class="dv2-qt" href="{{ $missing ? $nextHref : $action['href'] }}" data-testid="dashboard-v2-tile-{{ $i + 1 }}" data-icon="{{ $action['icon'] }}">
                            <span class="dv2-ico" aria-hidden="true"><x-ka-icon :name="$action['icon']" :size="18" tone="default" /></span>
                            <span>
                                <b>{{ $action['title'] }}</b>
                                <span class="help {{ $missing ? 'missing' : '' }}">{{ $missing ? 'Finish “'.$missing.'” in setup first.' : $action['helper'] }}</span>
                            </span>
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="dv2-kpis" data-testid="dashboard-v2-kpis">
                @include('admin.dashboard._whatsapp_stat')
                <div class="dv2-kpi"><a class="dv2-kpi-link" href="{{ url('/admin/students') }}" aria-label="Students, open the students list"><span class="dv2-kpi-label" data-icon="graduation-cap"><x-ka-icon name="graduation-cap" :size="16" tone="muted" />Students</span><span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-students">0</span><p class="dv2-kpi-sub">None added yet</p></a></div>
                <div class="dv2-kpi"><a class="dv2-kpi-link" href="{{ url('/admin/teachers') }}" aria-label="Staff, open teachers and staff"><span class="dv2-kpi-label"><x-ka-icon name="presentation" :size="16" tone="muted" />Staff</span><span class="dv2-kpi-value">{{ ($kpis['staff']['value'] ?? 1) }}</span><p class="dv2-kpi-sub">{{ $kpis['staff']['detail'] ?? 'Just you' }}</p></a></div>
                <div class="dv2-kpi"><a class="dv2-kpi-link" href="{{ route('admin.attendance.index') }}" aria-label="Attendance, open the registers"><span class="dv2-kpi-label"><x-ka-icon name="calendar-check" :size="16" tone="muted" />Attendance this week</span><span class="dv2-kpi-value">–</span><p class="dv2-kpi-sub">Starts after students are added</p></a></div>
                <div class="dv2-kpi"><a class="dv2-kpi-link" href="{{ url('/admin/fees/payments') }}" aria-label="Fees collected, open fees"><span class="dv2-kpi-label"><x-ka-icon name="wallet" :size="16" tone="muted" />Fees collected</span><span class="dv2-kpi-value">–</span><p class="dv2-kpi-sub warn">No fee structure yet</p></a></div>
                <div class="dv2-kpi"><a class="dv2-kpi-link" href="{{ url('/admin/reports/cards') }}" aria-label="Report cards ready, open report cards"><span class="dv2-kpi-label"><x-ka-icon name="file-text" :size="16" tone="muted" />Report cards ready</span><span class="dv2-kpi-value">–</span><p class="dv2-kpi-sub">After the first exam</p></a></div>
            </div>

            <div class="dv2-new-empty" data-testid="dashboard-v2-empty-students">
                <b>Add your students to get started</b>
                <p>Your dashboard fills in as you add students, take attendance and enter marks. You can add them one by one or import a spreadsheet.</p>
                <span style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center">
                    <a class="dv2-btn" href="{{ url('/admin/student/add') }}">Add student</a>
                    <a class="dv2-btn dv2-btn--soft" href="{{ url('/admin/students/import') }}" data-testid="dashboard-v2-import-students">Import a list</a>
                </span>
            </div>
        @else
            {{-- 4 · KPI tiles. The old quick-action row is gone; each tile links to its list. --}}
            <div class="dv2-kpis" data-testid="dashboard-v2-kpis" @if($checklistSetup) data-checklist-live data-figures-url="{{ url('/admin/dashboard/checklist-figures') }}" @endif>
                @include('admin.dashboard._whatsapp_stat')
                <div class="dv2-kpi">
                    <a class="dv2-kpi-link" href="{{ url('/admin/students') }}" aria-label="Students, open the students list">
                        <span class="dv2-kpi-label"><x-ka-icon name="graduation-cap" :size="16" tone="muted" />Students</span>
                        <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-students">{{ $kpis['students']['value'] ?? 0 }}</span>
                        <p class="dv2-kpi-sub {{ ($kpis['students']['direction'] ?? null) === 'up' ? 'up' : '' }}">{{ $kpis['students']['detail'] ?? '—' }}</p>
                    </a>
                </div>
                <div class="dv2-kpi">
                    <a class="dv2-kpi-link" href="{{ url('/admin/teachers') }}" aria-label="Staff, open teachers and staff">
                        <span class="dv2-kpi-label"><x-ka-icon name="presentation" :size="16" tone="muted" />Staff</span>
                        <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-staff">{{ $kpis['staff']['value'] ?? 0 }}</span>
                        <p class="dv2-kpi-sub">{{ $kpis['staff']['detail'] ?? '—' }}</p>
                    </a>
                </div>
                <div class="dv2-kpi">
                    <a class="dv2-kpi-link" href="{{ route('admin.attendance.index') }}" aria-label="Attendance, open the registers">
                        <span class="dv2-kpi-label"><x-ka-icon name="calendar-check" :size="16" tone="muted" />{{ ($kpis['attendance']['detail'] ?? '') === 'Last week' ? 'Last week' : 'Attendance this week' }}</span>
                        <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-attendance">{{ $kpis['attendance']['value'] ?? '–' }}</span>
                        <p class="dv2-kpi-sub {{ ($kpis['attendance']['direction'] ?? null) === 'up' ? 'up' : (($kpis['attendance']['direction'] ?? null) === 'down' ? 'down' : '') }}">{{ $kpis['attendance']['detail'] ?? '—' }}</p>
                    </a>
                </div>
                <div class="dv2-kpi">
                    <a class="dv2-kpi-link" href="{{ url('/admin/fees/payments') }}" aria-label="Fees collected, open fees">
                    <span class="dv2-kpi-label"><x-ka-icon name="wallet" :size="16" tone="muted" />Fees collected</span>
                    @if(($kpis['fees']['state'] ?? 'ok') === 'ok')
                        <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-fees">{{ $kpis['fees']['percent'] }}%</span>
                        <span class="dv2-meter" role="img" aria-label="{{ $kpis['fees']['percent'] }}% collected"><i style="width:{{ $kpis['fees']['percent'] }}%"></i></span>
                        <p class="dv2-kpi-sub">{{ $kpis['fees']['collected_label'] }} of {{ $kpis['fees']['expected_label'] }}</p>
                    @else
                        <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-fees">–</span>
                        <p class="dv2-kpi-sub warn">Set up fees first</p>
                    @endif
                    @if($currency === null && ($kpis['fees']['state'] ?? 'ok') === 'ok')
                        <p class="dv2-kpi-sub dv2-currency-hint" data-testid="dashboard-v2-currency-hint">Set your currency</p>
                    @endif
                    </a>
                </div>
                <div class="dv2-kpi">
                    <a class="dv2-kpi-link" href="{{ url('/admin/reports/cards') }}" aria-label="Report cards ready, open report cards" data-testid="dashboard-v2-report-action">
                        <span class="dv2-kpi-label"><x-ka-icon name="file-text" :size="16" tone="muted" />Report cards ready</span>
                        <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-reports">{{ $kpis['report_cards']['ready'] ?? 0 }}</span>
                        @if(($kpis['report_cards']['percent'] ?? null) !== null)
                            <span class="dv2-meter" role="img" aria-label="{{ $kpis['report_cards']['percent'] }}% of students"><i style="width:{{ $kpis['report_cards']['percent'] }}%"></i></span>
                        @endif
                        <p class="dv2-kpi-sub">of {{ $kpis['report_cards']['students'] ?? 0 }}@if(!empty($kpis['report_cards']['exam'])) · {{ $kpis['report_cards']['exam'] }}@elseif(($kpis['report_cards']['state'] ?? '') === 'no_exam') · No exam closed yet @endif</p>
                    </a>
                </div>
            </div>

            @if($checklistSetup && $setupShown && ! $setupFinished)
                <section class="dv2-setup-card" data-testid="dashboard-v2-setup-bar" role="region" aria-label="School setup">
                    <div class="dv2-setup-top">
                        <div class="dv2-setup-copy">
                            <h2>Set up {{ $schoolLabel }}</h2>
                            <p class="dv2-setup-lead">Pick one. You can switch at any time, and nothing is saved until you approve it.</p>
                        </div>
                        <form method="POST" action="{{ $dismissUrl }}" class="dv2-setup-dismiss" onsubmit="return confirm('Hide the setup bar? Your progress stays in the sidebar.');">
                            @csrf
                            <button type="submit" class="dv2-x dv2-setup-hide" data-testid="dashboard-v2-setup-dismiss" aria-label="Hide setup bar. Progress stays in the sidebar.">Hide</button>
                        </form>
                    </div>
                    <div class="dv2-setup-options">
                        <article class="dv2-setup-option">
                            <div class="dv2-setup-option-head">
                                <span class="dv2-setup-ico" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M8 13h8M8 17h5"/></svg></span>
                                <h3>Set up with Toshi</h3>
                            </div>
                            <p>Add your checklist workbook. Toshi reads it, shows what it found, and creates each part after you approve it. About 10 minutes.</p>
                            <button type="button" class="dv2-btn" data-testid="dashboard-v2-toshi-setup" onclick="window.klassDashboardToshiSetup && window.klassDashboardToshiSetup()">Set up with Toshi</button>
                        </article>
                        <article class="dv2-setup-option">
                            <div class="dv2-setup-option-head">
                                <span class="dv2-setup-ico" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h6"/></svg></span>
                                <h3>Set up manually</h3>
                            </div>
                            <p>Fill in a short form for each part: terms, classes, subjects, teachers, learners. Best if you have no checklist yet.</p>
                            <a class="dv2-btn secondary" href="{{ $nextHref }}" data-testid="dashboard-v2-setup-manual">Set up manually</a>
                        </article>
                    </div>
                </section>
            @endif

            {{-- 5 · Charts --}}
            <div class="dv2-grid2">
                <div class="dv2-card" data-testid="dashboard-v2-chart-performance">
                    <div class="act-head">
                        <h2>Performance by class</h2>
                        <a href="{{ url('/admin/exams') }}">Enter marks</a>
                    </div>
                    <small>{{ $charts['exam'] ? $charts['exam'].' · average mark' : 'Latest exam · average mark' }}</small>
                    @if(count($charts['per_class'] ?? []) > 0)
                        @php
                            $pc = $charts['per_class'];
                            $pcAria = implode(', ', array_map(fn ($r) => $r['label'].' '.($r['value'] === null ? 'no marks yet' : $r['value'].'%'), $pc));
                            $pcMissing = collect($pc)->contains(fn ($r) => $r['value'] === null);
                        @endphp
                        <div class="hbars" role="img" aria-label="Performance by class: {{ $pcAria }}">
                            @foreach($pc as $row)
                                <div class="hb" data-testid="dashboard-performance-class">
                                    <span>{{ $row['label'] }}</span>
                                    @if($row['value'] === null)
                                        <span class="t"></span><b data-testid="dashboard-performance-empty">–</b>
                                    @else
                                        <span class="t"><i style="width:{{ min(100, (float) $row['value']) }}%"></i></span><b>{{ $row['value'] }}%</b>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if($pcMissing)
                            <p class="dv2-kpi-sub" data-testid="dashboard-performance-caption">– no marks yet</p>
                        @endif
                        <a class="dv2-btn dv2-btn--soft" style="margin-top:12px" href="{{ route('admin.marks.classes') }}" data-testid="dashboard-v2-view-class-marks">View marks by class</a>
                        @include('marks-releases._button', ['area' => 'admin', 'class' => 'dv2-btn dv2-btn--soft', 'style' => 'margin-top:12px'])
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
                    <small>This term, {{ max(1, count($charts['attendance_weeks'] ?? [])) }} {{ \Illuminate\Support\Str::plural('week', max(1, count($charts['attendance_weeks'] ?? []))) }} · whole school</small>
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
                        @php
                            $genderCount = function (array $row): string {
                                $parts = [
                                    (int) $row['girls'].' '.((int) $row['girls'] === 1 ? 'girl' : 'girls'),
                                    (int) $row['boys'].' '.((int) $row['boys'] === 1 ? 'boy' : 'boys'),
                                ];
                                if ((int) ($row['not_specified'] ?? 0) > 0) {
                                    $parts[] = (int) $row['not_specified'].' not specified';
                                }

                                return implode(', ', $parts);
                            };
                        @endphp
                        <div class="hb" data-testid="dashboard-v2-gender-total">
                            <span>School</span>
                            <span class="stack" role="img" aria-label="School total: girls {{ $g['girls'] }}, boys {{ $g['boys'] }}, not specified {{ $g['not_specified'] }}">
                                @if((int) $g['girls'] > 0)<i style="flex:{{ (int) $g['girls'] }} 1 0;background:#B45309"></i>@endif
                                @if((int) $g['boys'] > 0)<i style="flex:{{ (int) $g['boys'] }} 1 0;background:#1E6FD9"></i>@endif
                                @if((int) $g['not_specified'] > 0)<i style="flex:{{ (int) $g['not_specified'] }} 1 0;background:#64748B"></i>@endif
                            </span>
                            <span class="n">{{ $genderCount($g) }}</span>
                        </div>
                        @foreach($charts['gender_classes'] ?? [] as $row)
                            @php
                                $rt = max(1, (int) $row['girls'] + (int) $row['boys'] + (int) $row['not_specified']);
                                $rw = fn ($n) => (int) round($n / $rt * 100);
                            @endphp
                            <div class="hb" data-testid="dashboard-v2-gender-class">
                                <span>{{ $row['label'] }}</span>
                                <span class="stack" role="img" aria-label="{{ $row['label'] }}: girls {{ $row['girls'] }}, boys {{ $row['boys'] }}, not specified {{ $row['not_specified'] }}">
                                    @if((int) $row['girls'] > 0)<i style="flex:{{ (int) $row['girls'] }} 1 0;background:#B45309"></i>@endif
                                    @if((int) $row['boys'] > 0)<i style="flex:{{ (int) $row['boys'] }} 1 0;background:#1E6FD9"></i>@endif
                                    @if((int) $row['not_specified'] > 0)<i style="flex:{{ (int) $row['not_specified'] }} 1 0;background:#64748B"></i>@endif
                                </span>
                                <span class="n">{{ $genderCount($row) }}</span>
                            </div>
                        @endforeach
                        <div class="legend" data-testid="dashboard-v2-gender-legend">
                            <span><i style="background:#B45309"></i>{{ (int) $g['girls'] }} {{ (int) $g['girls'] === 1 ? 'girl' : 'girls' }}</span>
                            <span><i style="background:#1E6FD9"></i>{{ (int) $g['boys'] }} {{ (int) $g['boys'] === 1 ? 'boy' : 'boys' }}</span>
                            <span><i style="background:#64748B"></i>{{ (int) $g['not_specified'] }} not specified</span>
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
                            $fmAria = implode(', ', array_map(fn ($m) => $m['label'].' collected '.($currency ? $currency.' ' : '').number_format($m['collected']).', expected '.($currency ? $currency.' ' : '').number_format($m['expected']), $fm));
                            $fmCollectedBg = array_map(fn ($m) => ! empty($m['future']) ? 'rgba(21,128,61,0.25)' : '#15803D', $fm);
                            $fmExpectedBg = array_map(fn ($m) => ! empty($m['future']) ? 'rgba(148,163,184,0.06)' : 'rgba(148,163,184,0.10)', $fm);
                        @endphp
                        <x-chart type="bar" :height="190"
                                 :labels="array_map(fn ($m) => $m['label'], $fm)"
                                 :datasets="[
                                     ['label' => 'Collected', 'data' => array_map(fn ($m) => $m['collected'], $fm), 'kaFuture' => array_map(fn ($m) => ! empty($m['future']), $fm), 'backgroundColor' => $fmCollectedBg, 'borderRadius' => 4],
                                     ['label' => 'Expected', 'data' => array_map(fn ($m) => $m['expected'], $fm), 'kaHideValueLabel' => true, 'backgroundColor' => $fmExpectedBg, 'borderColor' => '#94A3B8', 'borderDash' => [4, 3], 'borderWidth' => 1.5, 'borderRadius' => 4],
                                 ]"
                                 :options="['plugins' => ['dsValueLabels' => ['display' => true]], 'layout' => ['padding' => ['top' => 14, 'left' => 4, 'right' => 8]]]"
                                 options-js='{ scales: { y: { ticks: { maxTicksLimit: 6, callback: function (v) { var n = Number(v); if (!isFinite(n)) return ""; if (Math.abs(n) >= 1000000) return Math.round(n / 1000000) + "M"; if (Math.abs(n) >= 1000) return Math.round(n / 1000) + "K"; return String(n); } }, afterFit: function (scale) { scale.width = Math.max(scale.width, 44); } } } }'
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
                    @php
                        // Per-event icons keyed off DashboardV2DataService::activity() 'icon'.
                        $actIcons = [
                            'wallet' => ['stroke' => '#B45309', 'path' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 14h2"/>'],
                            'calendar-check' => ['stroke' => '#15803D', 'path' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18m-9 4 2 2 4-4"/>'],
                            'clipboard-list' => ['stroke' => '#1E6FD9', 'path' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h4"/>'],
                            'user-plus' => ['stroke' => '#64748B', 'path' => '<circle cx="10" cy="8" r="4"/><path d="M3 21v-1a7 7 0 0 1 14 0v1M19 8v6M16 11h6"/>'],
                        ];
                    @endphp
                    <ul class="act">
                        @foreach($activity as $row)
                            @php $ico = $actIcons[$row['icon'] ?? ''] ?? ['stroke' => '#475569', 'path' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>']; @endphp
                            <li>
                                <span class="ib" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $ico['stroke'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ico['path'] !!}</svg></span>
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

        @if(!empty($connectedTools['driveConnected']))
            <p class="dv2-drive" data-testid="dashboard-connected-drive">Reports filed to Drive</p>
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
@if($checklistSetup)
<script>
window.klassDashboardToshiSetup = function () {
    var collapsed = document.body.classList.contains('toshi-collapsed')
        || document.documentElement.classList.contains('toshi-collapsed');
    var welcome = document.querySelector('[data-testid="checklist-card-welcome"]');
    var start = document.querySelector('[data-testid="onboard-from-checklist"]');
    if (!collapsed && welcome && start) {
        start.focus();
        return;
    }
    if (window.toshiSetCollapsed) {
        window.toshiSetCollapsed(false);
    }
};
(function () {
    if (window.klassChecklistFigures) {
        return;
    }
    window.klassChecklistFigures = true;
    var root = document.querySelector('[data-checklist-live]');
    if (!root) {
        return;
    }
    var url = root.getAttribute('data-figures-url');
    var busy = false;
    function setText(id, value) {
        var node = document.querySelector('[data-testid="' + id + '"]');
        if (node && node.textContent !== value) {
            node.textContent = value;
        }
    }
    function setDetail(id, value) {
        var node = document.querySelector('[data-testid="' + id + '"]');
        if (!node) {
            return;
        }
        var card = node.closest('.dv2-kpi');
        var detail = card ? card.querySelector('.dv2-kpi-sub') : null;
        if (detail && value !== '' && detail.textContent !== value) {
            detail.textContent = value;
        }
    }
    function paint(payload) {
        setText('dashboard-v2-kpi-students', String(payload.students.value));
        setDetail('dashboard-v2-kpi-students', payload.students.detail || '');
        setText('dashboard-v2-kpi-staff', String(payload.staff.value));
        setDetail('dashboard-v2-kpi-staff', payload.staff.detail || '');
        setText('dashboard-v2-kpi-attendance', String(payload.attendance.value));
        setDetail('dashboard-v2-kpi-attendance', payload.attendance.detail || '');
        setText('dashboard-v2-kpi-fees', String(payload.fees.value));
        setDetail('dashboard-v2-kpi-fees', payload.fees.detail || '');
        setText('dashboard-v2-kpi-reports', String(payload.reports.value));
        setDetail('dashboard-v2-kpi-reports', payload.reports.detail || '');
        var whatsapp = document.querySelector('[data-testid="dashboard-whatsapp-waiting-count"], [data-testid="dashboard-connected-whatsapp-count"]');
        if (whatsapp) {
            whatsapp.textContent = String(payload.whatsapp.value);
        }
        function retireSetupChrome() {
            document.documentElement.classList.add('checklist-setup-done');
            ['dashboard-v2-setup-bar', 'dashboard-v2-profile', 'dashboard-v2-setup-summary'].forEach(function (id) {
                var node = document.querySelector('[data-testid="' + id + '"]');
                if (node && node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            });
        }
        if (payload.hide_options) {
            retireSetupChrome();
        }
        var ledger = document.querySelector('[data-testid="dashboard-v2-setup-ledger"]');
        if (ledger && !payload.hide_options) {
            ledger.replaceChildren();
            var rows = Array.isArray(payload.ledger) ? payload.ledger : [];
            if (rows.length === 0) {
                var empty = document.createElement('p');
                empty.className = 'dv2-setup-empty';
                empty.setAttribute('data-testid', 'dashboard-v2-setup-empty');
                empty.textContent = 'Nothing is set up yet.';
                ledger.appendChild(empty);
            } else {
                var group = '';
                rows.forEach(function (row) {
                    if ((row.group || '') !== group) {
                        group = row.group || '';
                        var heading = document.createElement('h3');
                        heading.className = 'dv2-ledger-group';
                        heading.textContent = group;
                        ledger.appendChild(heading);
                    }
                    var line = document.createElement('div');
                    line.className = 'dv2-ledger-row';
                    line.setAttribute('data-testid', 'dashboard-v2-ledger-row');
                    var label = document.createElement('span');
                    label.textContent = row.label || '';
                    var value = document.createElement('b');
                    value.textContent = row.value || '';
                    line.appendChild(label);
                    line.appendChild(value);
                    ledger.appendChild(line);
                });
            }
        }
        var yearBox = document.querySelector('[data-testid="dashboard-v2-year"]');
        var year = payload.academic_year || {};
        var yearLabel = year.label || '';
        if (yearBox && yearLabel !== '') {
            var select = yearBox.querySelector('[data-testid="dashboard-v2-year-select"]');
            if (!select) {
                yearBox.replaceChildren();
                var caption = document.createElement('label');
                caption.className = 'dv2-year-label';
                caption.setAttribute('for', 'dv2-year-select');
                caption.textContent = 'Academic year';
                select = document.createElement('select');
                select.id = 'dv2-year-select';
                select.setAttribute('aria-label', 'Academic year');
                select.setAttribute('data-testid', 'dashboard-v2-year-select');
                var option = document.createElement('option');
                option.value = year.id ? String(year.id) : '';
                option.selected = true;
                option.textContent = yearLabel;
                select.appendChild(option);
                yearBox.appendChild(caption);
                yearBox.appendChild(select);
            } else {
                var chosen = select.options[select.selectedIndex] || select.options[0];
                if (chosen && chosen.textContent !== yearLabel) {
                    chosen.textContent = yearLabel;
                }
            }
        }
        if (payload.term_label) {
            setText('dashboard-v2-title', payload.term_label);
            var termNode = document.querySelector('.nav-school-term');
            if (!termNode) {
                var schoolLine = document.querySelector('.nav-school-line');
                if (schoolLine) {
                    termNode = document.createElement('span');
                    termNode.className = 'nav-school-term';
                    schoolLine.appendChild(termNode);
                }
            }
            if (termNode && termNode.textContent !== payload.term_label) {
                termNode.textContent = payload.term_label;
            }
        }
        if (payload.profile_line) {
            var profileLine = document.querySelector('[data-testid="dashboard-v2-profile"] p');
            if (profileLine && profileLine.textContent !== payload.profile_line) {
                profileLine.textContent = payload.profile_line;
            }
        }
        var sidebar = payload.sidebar || {};
        Object.keys(sidebar).forEach(function (key) {
            var count = parseInt(sidebar[key], 10) || 0;
            document.querySelectorAll('[data-sidebar-count="' + key + '"]').forEach(function (chip) {
                chip.textContent = count > 0 ? String(count) : '';
                chip.hidden = count < 1;
            });
        });
    }
    function tick() {
        if (busy || !url) {
            return;
        }
        busy = true;
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (payload) { if (payload) { paint(payload); } })
            .catch(function () {})
            .finally(function () { busy = false; });
    }
    document.addEventListener('livewire:init', function () {
        if (!window.Livewire || !window.Livewire.hook) {
            return;
        }
        window.Livewire.hook('commit', function (payload) {
            payload.succeed(function () { tick(); });
        });
        if (window.Livewire.on) {
            window.Livewire.on('checklist-setup-finished', function () {
                document.documentElement.classList.add('checklist-setup-done');
                ['dashboard-v2-setup-bar', 'dashboard-v2-profile', 'dashboard-v2-setup-summary'].forEach(function (id) {
                    var node = document.querySelector('[data-testid="' + id + '"]');
                    if (node && node.parentNode) {
                        node.parentNode.removeChild(node);
                    }
                });
            });
        }
    });
    setInterval(tick, 3000);
})();
</script>
@endif
@endsection
