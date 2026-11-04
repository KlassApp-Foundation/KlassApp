{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
@php
    $student = $page['student'];
    $editUrl = url('/admin/student/edit/'.$student->name);
    $base = url('/admin/students/'.$student->id);
    $fees = $page['fees'];
    $perf = $page['performance'];
    $att = $page['attendance'];
    $currency = $page['currency'];
    $failed = $errors->any();
    $openDialog = null;
    foreach ($errors->keys() as $key) {
        if (in_array($key, ['amount', 'payment_method', 'paid_on', 'reference'], true)) { $openDialog = 'pay'; break; }
        if ($key === 'marks' || str_starts_with($key, 'marks.') || $key === 'sitting') { $openDialog = 'marks'; break; }
        if (in_array($key, ['date', 'status', 'comment'], true)) { $openDialog = 'attendance'; break; }
    }
    $oldSitting = old('sitting', $sittings[0]['key'] ?? '');
    $defaultAmount = $fees['owed'] > 0 ? number_format($fees['owed']) : '';
    $genericError = $errors->first();
@endphp
<style>
    .sp { display:flex; flex-direction:column; gap:20px; font-family:'DM Sans',system-ui,sans-serif; color:#1E293B; }
    .sp a { color:#1D4ED8; } .sp a:hover { color:#1E3A8A; }
    .sp-crumb { font-size:.8rem; color:#475569; display:flex; gap:6px; flex-wrap:wrap; }
    .sp-crumb a { text-decoration:none; }
    .sp-top { display:flex; flex-wrap:wrap; gap:20px; align-items:stretch; }
    .sp-id { display:flex; flex-wrap:wrap; gap:20px; flex:999 1 520px; border:1px solid #D6E6FA; border-radius:16px; padding:20px; background:#F0F6FE; min-width:0; }
    .sp-photo { width:132px; height:160px; border-radius:12px; background:#1E6FD9; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font:700 2.4rem 'Sora',sans-serif; }
    .sp-body { flex:1 1 280px; display:flex; flex-direction:column; gap:12px; min-width:0; }
    .sp h1 { margin:0; font:700 1.5rem 'Sora',system-ui,sans-serif; letter-spacing:-.02em; overflow-wrap:anywhere; }
    .sp-sub { font-size:.85rem; color:#475569; margin-top:2px; }
    .sp dl { margin:0; display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px 18px; font-size:.85rem; }
    .sp dt { font-size:.72rem; color:#475569; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
    .sp dd { margin:2px 0 0; font-weight:700; overflow-wrap:anywhere; font-variant-numeric:tabular-nums; }
    .sp-actions { flex:1 1 240px; display:flex; flex-direction:column; gap:8px; }
    .sp-actions .ds-btn { min-height:48px; justify-content:flex-start; }
    .sp-actions .sp-short { display:none; }
    .sp-tabs { display:flex; gap:4px; border-bottom:1px solid #E2E8F0; overflow-x:auto; }
    .sp-tabs a { min-height:44px; padding:0 16px; display:inline-flex; align-items:center; text-decoration:none; font-weight:600; color:#475569; white-space:nowrap; }
    .sp-tabs a[aria-current="page"] { font-weight:700; color:#14532D; border-bottom:3px solid #15803D; margin-bottom:-1px; }
    .sp-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr)); gap:16px; }
    .sp-card { border-radius:14px; padding:18px; display:flex; flex-direction:column; gap:12px; border:1px solid #CDEBD7; background:#F0FAF3; min-width:0; }
    .sp-card.blue { border-color:#D6E6FA; background:#F0F6FE; }
    .sp-card h2 { margin:0; font:600 1rem 'Sora',sans-serif; }
    .sp-head { display:flex; align-items:center; gap:10px; }
    .sp-ico { width:36px; height:36px; border-radius:10px; background:#fff; color:#15803D; display:inline-flex; align-items:center; justify-content:center; }
    .blue .sp-ico { color:#1E6FD9; }
    .sp-big { display:flex; align-items:baseline; gap:8px; flex-wrap:wrap; }
    .sp-big b { font:700 1.6rem 'Sora',sans-serif; }
    .sp-muted { font-size:.82rem; color:#475569; }
    .sp-note { border:1px solid #E2E8F0; border-radius:10px; padding:10px; background:#fff; font-size:.82rem; color:#334155; }
    .sp-bar { height:8px; border-radius:999px; background:#E2E8F0; overflow:hidden; }
    .sp-bar i { display:block; height:100%; background:#15803D; }
    .sp-row { display:flex; gap:8px; flex-wrap:wrap; }
    .sp-alert { border:1px solid #FCA5A5; background:#FEF2F2; color:#7F1D1D; border-radius:12px; padding:12px 14px; font-size:.88rem; }
    .sp-list { margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:6px; font-size:.85rem; }
    .sp-dlg { border:1px solid #E2E8F0; border-radius:16px; padding:22px; width:min(520px,calc(100vw - 32px)); color:#1E293B; font-family:'DM Sans',system-ui,sans-serif; }
    .sp-dlg::backdrop { background:rgba(15,23,42,.45); }
    .sp-dlg h2 { margin:0 0 10px; font:600 1.15rem 'Sora',sans-serif; }
    .sp-dlg form { display:flex; flex-direction:column; gap:12px; }
    .sp-dlg label { display:flex; flex-direction:column; gap:4px; font-size:.85rem; font-weight:600; }
    .sp-dlg .ds-input { min-height:44px; width:100%; }
    .sp-dlg .sp-two { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .sp-dlg .sp-who { font-size:.85rem; color:#334155; }
    .sp-dlg .sp-radio { flex-direction:row; align-items:center; gap:8px; min-height:44px; font-weight:500; }
    .sp-dlg .sp-actions-row { display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; }
    .sp-dlg .sp-mark { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .sp-dlg .sp-mark input { width:96px; flex-shrink:0; }
    .sp-dlg .sp-mark small { display:block; font-weight:400; color:#64748B; }
    .sp-err { color:#B91C1C; font-size:.8rem; font-weight:500; }
    .sp-sheet-grip { display:none; }
    @keyframes sp-shimmer { 0%{opacity:.55} 50%{opacity:1} 100%{opacity:.55} }
    .sp-sk { background:#E2E8F0; border-radius:8px; animation:sp-shimmer 1.4s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) { .sp-sk { animation:none; } }
    .sp[aria-busy="false"] .sp-skeleton { display:none; }
    .sp[aria-busy="true"] .sp-real { display:none; }
    @media (max-width: 767px) {
        .sp { gap:14px; }
        .sp-id { padding:14px; gap:12px; flex-basis:100%; }
        .sp-photo { width:56px; height:56px; font-size:1.2rem; border-radius:14px; }
        .sp h1 { font-size:1.25rem; }
        .sp dl { grid-template-columns:1fr 1fr; }
        .sp-actions { flex-direction:row; flex-basis:100%; }
        .sp-actions .ds-btn { flex:1 1 0; justify-content:center; min-height:48px; padding:0 8px; }
        .sp-actions .sp-long { display:none; } .sp-actions .sp-short { display:inline; }
        .sp-actions .sp-edit { display:none; }
        .sp-card { padding:14px; }
        .sp-dlg { width:100vw; max-width:100vw; margin:auto 0 0; border-radius:20px 20px 0 0; border-bottom:0; max-height:92vh; padding:12px 16px 20px; }
        .sp-sheet-grip { display:block; width:40px; height:4px; border-radius:999px; background:#CBD5E1; margin:0 auto 10px; }
        .sp-dlg .sp-two { grid-template-columns:1fr; }
        .sp-dlg .sp-actions-row { flex-direction:column-reverse; }
        .sp-dlg .sp-actions-row .ds-btn { width:100%; min-height:48px; }
    }
</style>

<div class="sp" data-testid="student-profile" aria-busy="false" id="student-profile-root">
    <nav class="sp-crumb" aria-label="Breadcrumb">
        <a href="{{ url('/admin/students') }}">Students</a><span aria-hidden="true">/</span>
        @if($page['class_name'])<span>{{ $page['class_name'] }}</span><span aria-hidden="true">/</span>@endif
        <span aria-current="page">{{ $page['name'] }}</span>
    </nav>

    @if($failed && $openDialog === null)
        <div class="sp-alert" role="alert">{{ $genericError }}</div>
    @endif

    <div class="sp-top" data-person-profile data-testid="person-profile-header">
        <div class="sp-id">
            <div class="sp-photo" data-testid="person-initials" aria-hidden="true">{{ $page['initials'] }}</div>
            <div class="sp-body">
                <div>
                    <h1>{{ $page['name'] }}</h1>
                    <div class="sp-sub" data-testid="person-status">{{ $page['status_label'] }} student · {{ $page['whatsapp_line'] }}</div>
                </div>
                <dl data-testid="person-facts">
                    <div><dt>Student number</dt><dd data-testid="student-kls">{{ $page['kls'] ?: 'No KLS number' }}</dd></div>
                    <div><dt>Class and stream</dt><dd>{{ $page['class_name'] ?: 'No class' }}</dd></div>
                    <div><dt>Gender</dt><dd>{{ $page['gender'] }}</dd></div>
                    <div><dt>Date of birth</dt><dd>{{ $page['date_of_birth'] }}@if($page['age_label']) · {{ $page['age_label'] }}@endif</dd></div>
                    <div><dt>Joined</dt><dd>{{ $page['joined'] }}</dd></div>
                    <div><dt>Class teacher</dt><dd>{{ $page['class_teacher'] ?: 'No class teacher' }}</dd></div>
                </dl>
            </div>
        </div>
        <div class="sp-actions">
            <button type="button" class="ds-btn ds-btn-primary" data-open-dialog="pay" data-testid="action-payment"><span class="sp-long">Record a payment</span><span class="sp-short">Payment</span></button>
            <button type="button" class="ds-btn ds-btn-outline" data-open-dialog="marks" data-testid="action-marks"><span class="sp-long">Add marks</span><span class="sp-short">Marks</span></button>
            <button type="button" class="ds-btn ds-btn-outline" data-open-dialog="attendance" data-testid="action-attendance"><span class="sp-long">Record attendance</span><span class="sp-short">Attendance</span></button>
            <a href="{{ $editUrl }}" class="ds-btn ds-btn-ghost sp-edit" style="min-height:44px;text-decoration:none;">Edit details</a>
            <a data-testid="profile-message" href="{{ $page['parents'][0]['profile_url'] ?? url('/admin/parents') }}" class="ds-btn ds-btn-ghost sp-edit" style="min-height:44px;text-decoration:none;">Message parent</a>
            <button type="button" class="ds-btn ds-btn-ghost sp-edit" id="open-deactivate" style="min-height:44px;justify-content:flex-start;color:#B91C1C;">Deactivate</button>
        </div>
    </div>

    <nav class="sp-tabs" aria-label="Profile sections" data-testid="person-profile-tabs">
        @foreach($tabs as $slug => $label)
            <a href="{{ $base }}?tab={{ $slug }}" @if($tab === $slug) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="sp-grid" data-testid="student-{{ $tab }}">
        {{-- Performance --}}
        @if(in_array($tab, ['overview', 'performance'], true))
        <section class="sp-card blue" data-testid="card-performance" aria-labelledby="sp-perf">
            <div class="sp-head"><span class="sp-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span><h2 id="sp-perf">Performance</h2></div>
            @if($perf['progress'])
                <div class="sp-big" data-testid="progress-average"><b>{{ $perf['progress']['average'] }}</b><span class="sp-muted">average · {{ $perf['progress']['label'] }}, approved, not released yet</span></div>
            @elseif($perf['latest'])
                <div class="sp-big" data-testid="latest-average"><b>{{ $perf['latest']['average'] }}</b><span class="sp-muted">average · {{ $perf['latest']['label'] }}</span></div>
            @endif
            @if(count($perf['chart']) >= 2)
                @php
                    $pts = []; $n = count($perf['chart']);
                    foreach ($perf['chart'] as $i => $p) { $pts[] = [10 + ($n > 1 ? $i * 220 / ($n - 1) : 0), 54 - max(0, min(100, $p['average'])) * 0.44]; }
                    $desc = collect($perf['chart'])->map(fn ($p) => 'Average '.rtrim(rtrim(number_format($p['average'], 1), '0'), '.').' at '.$p['label'])->implode(', ');
                @endphp
                <svg viewBox="0 0 240 60" role="img" aria-label="{{ $desc }}" style="width:100%;height:60px;"><polyline points="{{ collect($pts)->map(fn ($p) => round($p[0], 1).','.round($p[1], 1))->implode(' ') }}" fill="none" stroke="#1E6FD9" stroke-width="2.5"/>@foreach($pts as $p)<circle cx="{{ round($p[0], 1) }}" cy="{{ round($p[1], 1) }}" r="4" fill="#1E6FD9"/>@endforeach</svg>
            @endif
            @if($perf['progress'])
                <a class="ds-btn ds-btn-outline" style="min-height:44px;text-decoration:none;" href="{{ $perf['progress']['download_url'] }}" data-testid="download-progress">Download current progress report</a>
            @endif
            @if($perf['released'] === [])
                <p class="sp-muted" style="margin:0;" data-testid="performance-empty">No released results yet. They appear here after the school's next Marks Release.</p>
            @else
                <div class="sp-muted" style="display:flex;flex-direction:column;gap:6px;">
                    <span style="font-weight:700;color:#1E293B;">Released exams</span>
                    @foreach($perf['released'] as $row)
                        <span data-testid="released-row">{{ $row['label'] }} · average {{ $row['average'] }}@if($row['download_url']) · <a href="{{ $row['download_url'] }}">PDF</a>@endif</span>
                    @endforeach
                </div>
            @endif
        </section>
        @endif

        {{-- Attendance --}}
        @if(in_array($tab, ['overview', 'attendance'], true))
        <section class="sp-card" data-testid="card-attendance" aria-labelledby="sp-att">
            <div class="sp-head"><span class="sp-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span><h2 id="sp-att">Attendance</h2></div>
            @if($att['empty'])
                <p class="sp-muted" style="margin:0;" data-testid="attendance-empty">No days recorded yet.</p>
            @else
                <div class="sp-big"><b>{{ $att['percent'] }}%</b><span class="sp-muted">{{ $att['present'] }} of {{ $att['total'] }} days this term</span></div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @forelse($att['recent'] as $row)
                        <div class="sp-note" data-testid="recent-absence"><strong>Absent {{ $row['date'] }}</strong> · {{ $row['comment'] ? '“'.$row['comment'].'”' : 'no comment' }}@if($row['by']) · {{ $row['by'] }}@endif</div>
                    @empty
                        <div class="sp-note">No absences this term.</div>
                    @endforelse
                </div>
            @endif
            <div class="sp-row"><button type="button" class="ds-btn ds-btn-outline" style="min-height:44px;" data-open-dialog="attendance">Record attendance</button></div>
        </section>
        @endif

        {{-- Fees --}}
        @if(in_array($tab, ['overview', 'fees'], true))
        <section class="sp-card" data-testid="card-fees" aria-labelledby="sp-fees">
            <div class="sp-head"><span class="sp-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span><h2 id="sp-fees">Fees{{ $page['term_name'] ? ' · '.$page['term_name'] : '' }}</h2></div>
            @if($fees['error'])
                <div class="sp-alert" role="alert" data-testid="fees-error"><strong>The fees card couldn't load.</strong> The rest of the profile is up to date. <a href="{{ $base }}?tab={{ $tab }}">Reload fees</a></div>
            @elseif($fees['empty'])
                <p class="sp-muted" style="margin:0;" data-testid="fees-empty">No fees on record for this term.</p>
            @else
                <div class="sp-big" data-testid="kpi-fees"><b data-testid="fee-balance">{{ $fees['owed_label'] }}</b><span class="sp-muted">{{ $fees['owed'] > 0 ? 'still owed' : 'nothing owed' }}</span></div>
                <div class="sp-bar" aria-hidden="true"><i style="width:{{ $fees['percent'] }}%"></i></div>
                <div class="sp-muted"><span data-testid="fee-paid-of">{{ $fees['paid_label'] }} paid{{ $fees['expected_label'] ? ' of '.$fees['expected_label'] : '' }}</span>@if($fees['last_payment']) · last payment {{ $fees['last_payment'] }}@endif</div>
                @if($currency === null)
                    <a data-testid="fee-currency-hint" class="sp-muted" href="{{ url('/admin/settings') }}">Set your currency</a>
                @endif
            @endif
            @if($tab === 'fees' && $page['payments'] !== [])
                <ul class="sp-list" data-testid="payment-list">
                    @foreach($page['payments'] as $payment)
                        <li class="sp-note">{{ $payment['paid_on'] }} · <strong>{{ $payment['amount'] }}</strong>@if($payment['method']) · {{ $payment['method'] }}@endif @if($payment['reference']) · {{ $payment['reference'] }}@endif</li>
                    @endforeach
                </ul>
            @endif
            <div class="sp-row"><button type="button" class="ds-btn ds-btn-primary" style="min-height:44px;" data-open-dialog="pay">Record a payment</button></div>
        </section>
        @endif

        {{-- Parents --}}
        @if(in_array($tab, ['parents'], true) || ($tab === 'overview' && $page['parents'] === []))
        <section class="sp-card blue" data-testid="card-parents" style="grid-column:1/-1;" aria-labelledby="sp-par">
            <div class="sp-head"><h2 id="sp-par">Parents and guardians</h2></div>
            @if($page['parents'] === [])
                <p class="sp-muted" style="margin:0;">No parent linked, so nobody gets WhatsApp updates.</p>
                <div class="sp-row"><a class="ds-btn ds-btn-outline ds-btn-sm" style="min-height:44px;text-decoration:none;" href="{{ url('/admin/parents') }}">Add a parent</a></div>
            @else
                @include('admin.students._guardians', ['parents' => $page['parents']])
            @endif
        </section>
        @endif
    </div>

    {{-- Record a payment --}}
    <dialog id="dlg-pay" class="sp-dlg" aria-labelledby="pay-t">
        <span class="sp-sheet-grip" aria-hidden="true"></span>
        <h2 id="pay-t">Record a payment</h2>
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/payments') }}" data-busy-form>
            @csrf
            <input type="hidden" name="request_id" value="{{ $requestId }}">
            <div class="sp-who"><strong>{{ $page['name'] }}</strong>@if($page['class_name']) · {{ $page['class_name'] }}@endif<br>
                @if($fees['empty'] || $fees['error'])<span>No term total on record. The payment is saved against the student.</span>
                @else<span>{{ $page['term_name'] ?: 'Term' }} total {{ $fees['expected_label'] ?? $fees['paid_label'] }} · paid {{ $fees['paid_label'] }} · <strong>owes {{ $fees['owed_label'] }}</strong></span>@endif</div>
            <label>Amount{{ $currency ? ' ('.$currency.')' : '' }}
                <input class="ds-input" name="amount" value="{{ old('amount', $defaultAmount) }}" inputmode="decimal" required data-pay-amount autocomplete="off">
                @error('amount')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
            </label>
            <div class="sp-two">
                <label>Paid by
                    <select class="ds-input" name="payment_method" required>
                        @foreach($paymentMethods as $value => $label)<option value="{{ $value }}" @selected(old('payment_method', 'mobile_money') === $value)>{{ $label }}</option>@endforeach
                    </select>
                    @error('payment_method')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
                </label>
                <label>Date
                    <input class="ds-input" type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @error('paid_on')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
                </label>
            </div>
            <label>Reference (optional)
                <input class="ds-input" name="reference" value="{{ old('reference') }}" maxlength="255" placeholder="Receipt or transaction number">
                @error('reference')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
            </label>
            <p class="sp-muted" style="margin:0;">Goes against the term total. It is not tied to a fee line.</p>
            <div class="sp-actions-row">
                <button type="button" class="ds-btn ds-btn-outline" data-close-dialog>Cancel</button>
                <button type="submit" class="ds-btn ds-btn-primary" data-pay-submit data-currency="{{ $currency }}">Record payment</button>
            </div>
        </form>
    </dialog>

    {{-- Add marks (saved as drafts) --}}
    <dialog id="dlg-marks" class="sp-dlg" aria-labelledby="mk-t">
        <span class="sp-sheet-grip" aria-hidden="true"></span>
        <h2 id="mk-t">Add marks for {{ $page['name'] }}</h2>
        @if($sittings === [])
            <p class="sp-muted">There are no exams for this class this year yet, so there is nothing to add marks to.</p>
            <div class="sp-actions-row"><button type="button" class="ds-btn ds-btn-outline" data-close-dialog>Close</button></div>
        @else
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/marks') }}" data-busy-form>
            @csrf
            <label>Exam
                <select class="ds-input" name="sitting" data-sitting>
                    @foreach($sittings as $s)<option value="{{ $s['key'] }}" @selected($oldSitting === $s['key'])>{{ $s['label'] }}</option>@endforeach
                </select>
            </label>
            @error('marks')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
            @foreach($sittings as $s)
                <div data-sitting-group="{{ $s['key'] }}" style="display:flex;flex-direction:column;gap:10px;" @if($oldSitting !== $s['key']) hidden @endif>
                    @foreach($sittingSubjects[$s['key']] ?? [] as $sub)
                        <label class="sp-mark" for="mark-{{ $sub['exam_id'] }}">
                            <span>{{ $sub['subject'] }}<small>{{ $sub['status'] }}</small>@error('marks.'.$sub['exam_id'])<span class="sp-err" role="alert">{{ $message }}</span>@enderror</span>
                            <input class="ds-input" id="mark-{{ $sub['exam_id'] }}" name="marks[{{ $sub['exam_id'] }}]" value="{{ old('marks.'.$sub['exam_id'], $sub['mark']) }}" inputmode="numeric" aria-label="{{ $sub['subject'] }} mark out of 100" @disabled(! $sub['editable'] || $oldSitting !== $s['key']) data-mark>
                        </label>
                    @endforeach
                </div>
            @endforeach
            <p class="sp-muted" style="margin:0;">Saved into each subject's draft. They still go through Submit, Approve and Release.</p>
            <div class="sp-actions-row">
                <button type="button" class="ds-btn ds-btn-outline" data-close-dialog>Cancel</button>
                <button type="submit" class="ds-btn ds-btn-primary" data-marks-submit>Save marks as draft</button>
            </div>
        </form>
        @endif
    </dialog>

    {{-- Record attendance --}}
    <dialog id="dlg-attendance" class="sp-dlg" aria-labelledby="at-t">
        <span class="sp-sheet-grip" aria-hidden="true"></span>
        <h2 id="at-t">Record attendance</h2>
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/attendance') }}" data-busy-form>
            @csrf
            <label>Day
                <input class="ds-input" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                @error('date')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
            </label>
            <div role="radiogroup" aria-label="{{ $page['name'] }} was">
                <label class="sp-radio"><input type="radio" name="status" value="present" @checked(old('status') === 'present')> Present</label>
                <label class="sp-radio"><input type="radio" name="status" value="absent" @checked(old('status', 'absent') === 'absent')> Absent</label>
                @error('status')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
            </div>
            <label>Comment (optional)
                <textarea class="ds-input" name="comment" rows="3" maxlength="500">{{ old('comment') }}</textarea>
                @error('comment')<span class="sp-err" role="alert">{{ $message }}</span>@enderror
            </label>
            <div class="sp-actions-row">
                <button type="button" class="ds-btn ds-btn-outline" data-close-dialog>Cancel</button>
                <button type="submit" class="ds-btn ds-btn-primary">Save attendance</button>
            </div>
        </form>
    </dialog>

    <dialog id="deactivate-student" class="sp-dlg" style="width:min(420px,calc(100vw - 32px));">
        <span class="sp-sheet-grip" aria-hidden="true"></span>
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/deactivate') }}">
            @csrf
            <h2>Deactivate this student?</h2>
            <p class="sp-muted" style="margin:0;">They stay on record. Their status becomes inactive.</p>
            <div class="sp-actions-row">
                <button type="button" id="deactivate-cancel" class="ds-btn ds-btn-outline" data-close-dialog autofocus>Cancel</button>
                <button type="submit" class="ds-btn" style="background:#B91C1C;color:#fff;">Deactivate</button>
            </div>
        </form>
    </dialog>
</div>

<script>
(function () {
    var ids = { pay: 'dlg-pay', marks: 'dlg-marks', attendance: 'dlg-attendance' };
    function open(name) {
        var d = document.getElementById(ids[name]);
        if (d && typeof d.showModal === 'function' && !d.open) { d.showModal(); }
    }
    document.querySelectorAll('[data-open-dialog]').forEach(function (b) {
        b.addEventListener('click', function () { open(b.getAttribute('data-open-dialog')); });
    });
    document.querySelectorAll('[data-close-dialog]').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('dialog').close(); });
    });
    document.querySelectorAll('dialog.sp-dlg').forEach(function (d) {
        d.addEventListener('click', function (e) { if (e.target === d) { d.close(); } });
    });
    var deact = document.getElementById('open-deactivate');
    if (deact) { deact.addEventListener('click', function () { document.getElementById('deactivate-student').showModal(); }); }

    // Payment button names the amount, like the design.
    var amount = document.querySelector('[data-pay-amount]');
    var pay = document.querySelector('[data-pay-submit]');
    function label() {
        var n = parseFloat((amount.value || '').replace(/[,\s]/g, ''));
        var cur = pay.getAttribute('data-currency');
        pay.textContent = n > 0 ? 'Record ' + (cur ? cur + ' ' : '') + n.toLocaleString('en-US') + ' payment' : 'Record payment';
    }
    if (amount && pay) { amount.addEventListener('input', label); label(); }

    // Pick a sitting: show its subjects, and only submit those.
    var sitting = document.querySelector('[data-sitting]');
    function sync() {
        document.querySelectorAll('[data-sitting-group]').forEach(function (g) {
            var on = g.getAttribute('data-sitting-group') === sitting.value;
            g.hidden = !on;
            g.querySelectorAll('[data-mark]').forEach(function (i) {
                var locked = i.getAttribute('data-locked') === '1';
                if (!on) { i.disabled = true; } else if (!locked) { i.disabled = false; }
            });
        });
    }
    if (sitting) {
        document.querySelectorAll('[data-mark]').forEach(function (i) { if (i.disabled && !i.closest('[hidden]')) { i.setAttribute('data-locked', '1'); } });
        sitting.addEventListener('change', sync);
    }

    // Saving: stop double submits and say so.
    document.querySelectorAll('[data-busy-form]').forEach(function (f) {
        f.addEventListener('submit', function () {
            var b = f.querySelector('button[type=submit]');
            if (b) { b.disabled = true; b.textContent = 'Saving…'; }
        });
    });

    @if($openDialog)
    open(@json($openDialog));
    @endif
})();
</script>
@endsection
