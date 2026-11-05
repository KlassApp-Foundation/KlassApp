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
        if ($key === 'photo') { $openDialog = 'photo'; break; }
    }
    $oldSitting = old('sitting', $sittings[0]['key'] ?? '');
    $defaultAmount = $fees['owed'] > 0 ? number_format($fees['owed']) : '';
    $genericError = $errors->first();
@endphp
<link rel="stylesheet" href="{{ asset('css/student-profile.css') }}?v={{ filemtime(public_path('css/student-profile.css')) }}">

<div class="sp" data-testid="student-profile" aria-busy="false" id="student-profile-root">
    <nav class="sp-crumb" aria-label="Breadcrumb">
        <a href="{{ url('/admin/students') }}">Students</a><span aria-hidden="true">/</span>
        @if($page['class_name'])<span>{{ $page['class_name'] }}</span><span aria-hidden="true">/</span>@endif
        <span aria-current="page">{{ $page['name'] }}</span>
    </nav>

    @include('partials.message')

    @if($failed && $openDialog === null)
        <div class="sp-alert" role="alert">{{ $genericError }}</div>
    @endif

    <div class="sp-top" data-person-profile data-testid="person-profile-header">
        <div class="sp-id">
            <div class="sp-photo-wrap">
                <button type="button" class="sp-photo" data-open-dialog="photo" data-testid="student-photo" aria-label="{{ $page['photo_url'] ? 'Change photo' : 'Add photo' }}">
                    @if($page['photo_url'])<img src="{{ $page['photo_url'] }}" alt="{{ $page['name'] }}" width="132" height="132">@else<span data-testid="person-initials" aria-hidden="true">{{ $page['initials'] }}</span>@endif
                </button>
                <button type="button" class="sp-photo-pill" data-open-dialog="photo" data-testid="photo-action">{{ $page['photo_url'] ? 'Change photo' : 'Add photo' }}</button>
            </div>
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
            @if(!empty($page['can_resend']))
                <form method="POST" action="{{ url('/admin/students/'.$student->id.'/resend-setup') }}" style="margin:0;" class="sp-edit">
                    @csrf
                    <button type="submit" class="ds-btn ds-btn-ghost" style="min-height:44px;width:100%;justify-content:flex-start;" data-testid="resend-setup-link">Resend setup link</button>
                </form>
            @endif
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
            <div class="sp-row sp-row--fill"><button type="button" class="ds-btn ds-btn-primary" style="min-height:44px;" data-open-dialog="pay">Record a payment</button>@if(! $fees['error'] && ! $fees['empty'] && $fees['owed'] > 0)<button type="button" class="ds-btn ds-btn-outline" style="min-height:44px;" data-open-dialog="paid" data-testid="mark-paid">Mark as paid</button>@endif</div>
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

    {{-- Student photo --}}
    <dialog id="dlg-photo" class="sp-dlg" aria-labelledby="ph-t">
        <span class="sp-sheet-grip" aria-hidden="true"></span>
        <h2 id="ph-t">{{ $page['photo_url'] ? 'Change photo' : 'Add photo' }}</h2>
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/photo') }}" enctype="multipart/form-data" data-busy-form>
            @csrf
            <label>Photo of {{ $page['name'] }}
                <input class="ds-input" type="file" name="photo" accept="image/jpeg,image/png" required data-photo-input style="padding:8px;">
                <span class="sp-muted" style="font-weight:400;">JPG or PNG, up to 2 MB. It is cropped square.</span>
                <span class="sp-err" role="alert" data-photo-error>@error('photo'){{ $message }}@enderror</span>
            </label>
            <div class="sp-actions-row">
                <button type="button" class="ds-btn ds-btn-outline" data-close-dialog>Cancel</button>
                <button type="submit" class="ds-btn ds-btn-primary">{{ $page['photo_url'] ? 'Replace photo' : 'Save photo' }}</button>
            </div>
        </form>
        @if($page['photo_url'])
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/photo') }}" style="margin-top:12px;">
            @csrf @method('DELETE')
            <button type="submit" class="ds-btn ds-btn-ghost" style="min-height:44px;color:#B91C1C;" data-testid="photo-remove">Remove photo</button>
        </form>
        @endif
    </dialog>

    {{-- Mark as paid: the whole outstanding term balance --}}
    @if(! $fees['error'] && ! $fees['empty'] && $fees['owed'] > 0)
    <dialog id="dlg-paid" class="sp-dlg" aria-labelledby="mp-t">
        <span class="sp-sheet-grip" aria-hidden="true"></span>
        <h2 id="mp-t" data-testid="mark-paid-title">Record {{ $fees['owed_label'] }} as paid for {{ $page['term_name'] ?: 'this term' }}?</h2>
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/payments/full') }}" data-busy-form>
            @csrf
            <input type="hidden" name="request_id" value="{{ $requestId }}-full">
            <p class="sp-muted" style="margin:0;">This records one payment for everything {{ $page['name'] }} still owes this term.</p>
            <label>Paid by
                <select class="ds-input" name="payment_method" required>
                    @foreach($paymentMethods as $value => $label)<option value="{{ $value }}" @selected($value === 'cash')>{{ $label }}</option>@endforeach
                </select>
            </label>
            <div class="sp-actions-row">
                <button type="button" class="ds-btn ds-btn-outline" data-close-dialog>Cancel</button>
                <button type="submit" class="ds-btn ds-btn-primary">Record {{ $fees['owed_label'] }}</button>
            </div>
        </form>
    </dialog>
    @endif

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

@endsection

@push('scripts')
<script>
// The admin layout is a Vue root that re-renders its DOM after load, so every handler here is
// delegated from the document and the initial state is applied on window load.
(function () {
    var ids = { pay: 'dlg-pay', paid: 'dlg-paid', photo: 'dlg-photo', marks: 'dlg-marks', attendance: 'dlg-attendance' };
    function open(name) {
        var d = document.getElementById(ids[name]);
        if (d && typeof d.showModal === 'function' && !d.open) { d.showModal(); }
    }
    function payLabel() {
        var amount = document.querySelector('[data-pay-amount]');
        var pay = document.querySelector('[data-pay-submit]');
        if (!amount || !pay) { return; }
        var n = parseFloat((amount.value || '').replace(/[,\s]/g, ''));
        var cur = pay.getAttribute('data-currency');
        pay.textContent = n > 0 ? 'Record ' + (cur ? cur + ' ' : '') + n.toLocaleString('en-US') + ' payment' : 'Record payment';
    }
    function syncSitting() {
        var sitting = document.querySelector('[data-sitting]');
        if (!sitting) { return; }
        document.querySelectorAll('[data-sitting-group]').forEach(function (g) {
            var on = g.getAttribute('data-sitting-group') === sitting.value;
            g.hidden = !on;
            g.querySelectorAll('[data-mark]').forEach(function (i) {
                i.disabled = !on || i.getAttribute('data-locked') === '1';
            });
        });
    }
    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-open-dialog]');
        if (opener) { payLabel(); open(opener.getAttribute('data-open-dialog')); return; }
        if (e.target.closest('#open-deactivate')) { var d = document.getElementById('deactivate-student'); if (d) { d.showModal(); } return; }
        var closer = e.target.closest('[data-close-dialog]');
        if (closer) { closer.closest('dialog').close(); return; }
        if (e.target.matches && e.target.matches('dialog.sp-dlg')) { e.target.close(); }
    });
    document.addEventListener('input', function (e) { if (e.target.matches('[data-pay-amount]')) { payLabel(); } });
    document.addEventListener('change', function (e) { if (e.target.matches('[data-sitting]')) { syncSitting(); } });
    document.addEventListener('change', function (e) {
        if (!e.target.matches('[data-photo-input]')) { return; }
        var f = e.target.files && e.target.files[0];
        var msg = document.querySelector('[data-photo-error]');
        var bad = '';
        if (f && f.size > 2 * 1024 * 1024) { bad = 'That photo is larger than 2 MB. Choose a smaller one.'; }
        else if (f && ['image/jpeg', 'image/png'].indexOf(f.type) < 0) { bad = 'Photos must be a JPG or PNG file.'; }
        if (msg) { msg.textContent = bad; }
        if (bad) { e.target.value = ''; }
    });
    document.addEventListener('submit', function (e) {
        if (!e.target.matches('[data-busy-form]')) { return; }
        var b = e.target.querySelector('button[type=submit]');
        if (b) { b.disabled = true; b.textContent = 'Saving\u2026'; }
    });
    window.addEventListener('load', function () {
        document.querySelectorAll('[data-mark]').forEach(function (i) {
            if (i.disabled && !i.closest('[hidden]')) { i.setAttribute('data-locked', '1'); }
        });
        payLabel();
        @if($openDialog)
        open(@json($openDialog));
        @endif
    });
})();
</script>
@endpush
