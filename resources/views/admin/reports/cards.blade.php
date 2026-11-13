{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
@php
    $state = $data['state'] ?? null;
    $kpis = $data['kpis'] ?? null;
    $rows = $data['rows'] ?? [];
    $subjects = $data['subjects'] ?? [];
    $generations = $data['generations'] ?? [];
    $releasedLinks = $data['released_links'] ?? [];
    $linkCounts = $data['link_counts'] ?? [];
    $streamsByLink = collect($links)->pluck('stream', 'id')->all();
    $query = fn (array $extra = []) => array_filter(array_merge(['class' => $class['id'] ?? null, 'exam' => $sitting['key'] ?? null, 'stream' => $streamId, 'sort' => $sort === 'name' ? 'name' : null], $extra), fn ($v) => $v !== null && $v !== '');
    $pending = collect($generations)->contains(fn ($g) => $g['status'] === 'pending');
    $failedLink = collect($generations)->filter(fn ($g) => $g['status'] === 'failed')->keys()->first();
    $total = array_sum($linkCounts);
    $maxDivision = max(1, (int) collect($data['divisions'] ?? [])->max());
    $maxSubject = 100;
@endphp
<link rel="stylesheet" href="{{ asset('css/report-cards-screen.css') }}?v={{ filemtime(public_path('css/report-cards-screen.css')) }}">

<div class="rc" data-testid="report-cards-screen" @if($pending) data-pending="1" @endif>
    @include('partials.message')

    <div class="rc-head">
        <div>
            <h1>Report cards</h1>
            <p style="margin:6px 0 0;font-size:.9rem;color:#475569;">Built from released marks only</p>
            @if(!empty($data['composition_line'] ?? null))
                <p class="ka-compose" data-testid="eot-composition">{{ $data['composition_line'] }}</p>
            @endif
        </div>
        @if($class && $sitting)
        <form method="GET" action="{{ url('/admin/reports/cards') }}" class="rc-filters" data-testid="desktop-filters">
            <label for="rc-class">Class</label>
            <select id="rc-class" name="class" class="ds-input" style="width:160px;" onchange="this.form.exam.value=''; this.form.stream.value=''; this.form.submit()">
                @foreach($classes as $c)<option value="{{ $c['id'] }}" @selected($c['id'] === $class['id'])>{{ $c['label'] }}</option>@endforeach
            </select>
            <label for="rc-exam">Exam</label>
            <select id="rc-exam" name="exam" class="ds-input" style="width:220px;" onchange="this.form.submit()">
                @foreach($sittings as $s)<option value="{{ $s['key'] }}" @selected($s['key'] === $sitting['key'])>{{ $s['label'] }}</option>@endforeach
            </select>
            @if($streamId)<input type="hidden" name="stream" value="{{ $streamId }}">@else<input type="hidden" name="stream" value="">@endif
            <noscript><button class="ds-btn ds-btn-outline" type="submit">Show</button></noscript>
        </form>
        <button type="button" class="ds-btn ds-btn-outline rc-sheet-btn" style="min-height:44px;" data-open-filters>Filters</button>
        @endif
    </div>

    @if(!empty($data['own_reports'] ?? []))
        <section class="ka-own" aria-labelledby="own-reports" data-testid="own-reports">
            <h2 id="own-reports">Each exam set has its own report</h2>
            @foreach($data['own_reports'] as $own)
                <div class="ka-own-row" data-testid="own-report-{{ $own['id'] }}">
                    <strong>{{ $own['name'] }}</strong>
                    <span class="ka-pill {{ !empty($own['released']) ? 'ka-pill-green' : 'ka-pill-muted' }}">{{ $own['status'] }}</span>
                </div>
            @endforeach
        </section>
    @endif

    @if(! $class)
        <div class="rc-empty" data-testid="rc-no-classes">
            <h2>No classes yet</h2>
            <p>Report cards need a class with an exam. Set up classes first, then come back.</p>
            <a href="{{ url('/admin/classes') }}" class="ds-btn ds-btn-outline" style="min-height:44px;text-decoration:none;">Go to Classes</a>
        </div>
    @elseif(! $sitting)
        <div class="rc-empty" data-testid="rc-no-exams">
            <h2>{{ $class['label'] }} has no exams yet</h2>
            <p>Report cards use released marks only. Once an exam is marked and released, it shows here.</p>
            <a href="{{ route('admin.marks.releases.index') }}" class="ds-btn ds-btn-outline" style="min-height:44px;text-decoration:none;">Go to Marks Release</a>
        </div>
    @elseif($state === 'blocked')
        <div class="rc-empty" data-testid="report-blocked">
            <h2>Combined report</h2>
            <p data-testid="report-blocked-sets">{{ collect($data['blocked_sets'] ?? [])->pluck('name')->filter()->implode(', ') }} has marks that are not released yet.</p>
            <a href="{{ route('admin.marks.releases.index') }}" class="ds-btn ds-btn-outline" style="min-height:44px;text-decoration:none;">Marks Release</a>
        </div>
    @elseif($state === 'unreleased' || $state === 'empty')
        <div class="rc-empty" data-testid="rc-unreleased">
            <h2>{{ $class['label'] }} {{ $sitting['label'] }} {{ $state === 'empty' ? 'has no marks to show yet' : "isn't released yet" }}</h2>
            <p>Report cards use released marks only. Nothing from this exam is shown until it is released.</p>
            <a href="{{ route('admin.marks.releases.index') }}" class="ds-btn ds-btn-outline" style="min-height:44px;text-decoration:none;">Go to Marks Release</a>
        </div>
    @else
        {{-- Class PDF: one control per released stream --}}
        @foreach($releasedLinks as $linkId)
            @php
                $g = $generations[$linkId] ?? null;
                $count = $linkCounts[$linkId] ?? 0;
                $stream = $streamsByLink[$linkId] ?? '';
                $suffix = count($releasedLinks) > 1 && $stream !== '' ? ' · '.$stream : '';
                $mergedUrl = route('admin.reports.cards.merged', ['stdLink' => $linkId, 'term' => $sitting['term_id']]);
            @endphp
            <div data-testid="class-pdf" data-state="{{ $g['status'] ?? 'none' }}">
                @if($g && $g['status'] === 'pending')
                    <div class="rc-progress" role="status" aria-busy="true">
                        <strong>Preparing {{ $count }} report cards{{ $suffix }}…</strong>
                        <i aria-hidden="true"><b></b></i>
                        <span style="font-size:.82rem;color:#475569;">The download appears here when it is ready. You can keep working.</span>
                    </div>
                @elseif($g && $g['status'] === 'failed')
                    <div class="rc-alert" role="alert">
                        <strong>The class PDF didn't finish{{ $suffix }}</strong>
                        <span style="font-size:.86rem;">Nothing was lost. You can still download each card from the list below, or try the class PDF again.</span>
                        <div><a href="{{ $mergedUrl }}" class="ds-btn ds-btn-primary" style="min-height:44px;text-decoration:none;">Try all {{ $count }} again</a></div>
                    </div>
                @elseif($g && $g['status'] === 'completed')
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="{{ route('admin.reports.cards.generation.download', $g['id']) }}" class="ds-btn ds-btn-primary rc-all" style="min-height:44px;text-decoration:none;" data-testid="download-all">Download all {{ $count }} cards (one PDF){{ $suffix }}</a>
                        <a href="{{ $mergedUrl }}" class="ds-btn ds-btn-outline" style="min-height:44px;text-decoration:none;">Build again</a>
                    </div>
                @else
                    <a href="{{ $mergedUrl }}" class="ds-btn ds-btn-primary rc-all" style="min-height:44px;text-decoration:none;" data-testid="build-all">Build all {{ $count }} cards (one PDF){{ $suffix }}</a>
                @endif
            </div>
        @endforeach

        <div class="rc-kpis" data-testid="rc-kpis">
            <div class="rc-kpi"><b>{{ $kpis['ready'] }}</b><span>report cards ready</span></div>
            <div class="rc-kpi"><b>{{ $kpis['average'] ?? '—' }}</b><span>class average</span></div>
            <div class="rc-kpi hide-phone"><b>{{ $kpis['division_one'] }}</b><span>in Division I</span></div>
            <div class="rc-kpi hide-phone"><b>{{ $kpis['ungraded'] }}</b><span>ungraded (U)</span></div>
        </div>

        <div class="rc-charts">
            <section class="rc-card" aria-labelledby="rc-subj">
                <h2 id="rc-subj">Average by subject</h2>
                @foreach($data['subject_averages'] as $s)
                    <div class="rc-bar"><span>{{ $s['name'] }}</span><i aria-hidden="true"><b style="width:{{ min(100, max(0, $s['value'] ?? 0)) }}%"></b></i><strong class="rc-r">{{ $s['value'] !== null ? rtrim(rtrim(number_format($s['value'], 1), '0'), '.') : '—' }}</strong></div>
                @endforeach
            </section>
            <section class="rc-card" aria-labelledby="rc-div">
                <h2 id="rc-div">Students by division</h2>
                @php $colors = ['1' => '#15803D', '2' => '#1E6FD9', '3' => '#64748B', '4' => '#B45309', 'U' => '#B91C1C']; @endphp
                <div class="rc-div" role="img" aria-label="{{ collect($data['divisions'])->map(fn ($n, $d) => 'Division '.$data['division_labels'][$d].' '.$n)->implode(', ') }}">
                    @foreach($data['divisions'] as $d => $n)
                        <div><span style="font-weight:700;">{{ $n }}</span><i style="height:{{ (int) round($n / $maxDivision * 90) }}px;background:{{ $colors[$d] }};"></i><span style="color:#475569;">Div {{ $data['division_labels'][$d] }}</span></div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="rc-table">
            <table data-testid="rc-table">
                <thead><tr>
                    <th scope="col">Position</th><th scope="col">Student</th><th scope="col">Stream</th>
                    @foreach($subjects as $sub)<th scope="col" class="rc-r" title="{{ $sub['name'] }}">{{ $sub['short'] }}</th>@endforeach
                    <th scope="col" class="rc-r">Total</th><th scope="col" class="rc-r">Aggregate</th><th scope="col">Division</th>
                    <th scope="col"><span style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);">Report card</span></th>
                </tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr data-testid="rc-row">
                        <td><strong>{{ $r['position'] ?? '—' }}</strong> <span style="color:#475569;">of {{ $r['out_of'] }}</span></td>
                        <th scope="row">{{ $r['name'] }}@if(($r['kls'] ?? '') !== '')<div data-testid="rc-kls">{{ $r['kls'] }}</div>@endif</th>
                        <td>{{ $r['stream'] !== '' ? $r['stream'] : '—' }}</td>
                        @foreach($r['marks'] as $m)<td class="rc-r">{{ $m }}</td>@endforeach
                        <td class="rc-r" style="font-weight:700;">{{ $r['total'] }}</td>
                        <td class="rc-r">{{ $r['aggregate'] }}</td>
                        <td><span class="rc-pill">{{ $r['division'] }}</span></td>
                        <td><a href="{{ $r['download_url'] }}" class="ds-btn ds-btn-ghost ds-btn-sm ka-hit" style="min-height:44px;text-decoration:none;" aria-label="Download report card for {{ $r['name'] }}">PDF</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="rc-phone" data-testid="rc-phone-list">
            @foreach($rows as $r)
                <div data-testid="rc-phone-card">
                    <span class="rc-pos">{{ $r['position'] ?? '—' }}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;">{{ $r['name'] }}</div>
                        <div style="font-size:.75rem;color:#475569;">@if(($r['kls'] ?? '') !== '')<span data-testid="rc-phone-kls">{{ $r['kls'] }}</span> · @endif{{ $class['label'] }}{{ $r['stream'] !== '' ? ' '.$r['stream'] : '' }} · total {{ $r['total'] }} · agg {{ $r['aggregate'] }} · Div {{ $r['division'] }}</div>
                        {{-- design review: ReportPhone.dc.html shows only the summary line. Subject rows are listed here so a mark or ABS stays on the card and the phone does not scroll sideways. --}}
                        @foreach($subjects as $i => $sub)
                            <div class="ka-mark-row"><span>{{ $sub['name'] }}</span><strong>{{ $r['marks'][$i] ?? '—' }}</strong></div>
                        @endforeach
                    </div>
                    <a href="{{ $r['download_url'] }}" class="rc-dl ka-hit" aria-label="Download report card for {{ $r['name'] }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></a>
                </div>
            @endforeach
        </div>
        @if($rows === [])
            <div class="rc-empty"><h2>No students in this stream</h2><p>Choose another stream, or all streams.</p><a class="ds-btn ds-btn-outline" style="min-height:44px;text-decoration:none;" href="{{ url('/admin/reports/cards').'?'.http_build_query($query(['stream' => null])) }}">Show all streams</a></div>
        @endif
    @endif

    <p style="margin:0;font-size:.82rem;color:#475569;"><a href="{{ route('admin.reports.cards.history') }}">Report card template and earlier PDFs</a> · <a href="{{ route('admin.reports.cards.missing') }}">Missing marks report</a></p>

    @if($class && $sitting)
    <dialog id="rc-filter-sheet" class="rc-dlg" aria-labelledby="fs-t">
        <span class="rc-grip" aria-hidden="true"></span>
        <form method="GET" action="{{ url('/admin/reports/cards') }}">
            <div style="display:flex;justify-content:space-between;align-items:center;"><h2 id="fs-t" style="margin:0;font:600 1.1rem 'Sora',sans-serif;">Filters</h2><a class="ds-btn ds-btn-ghost ds-btn-sm" style="min-height:44px;text-decoration:none;" href="{{ url('/admin/reports/cards') }}">Reset</a></div>
            <label class="f">Class<select name="class" class="ds-input">@foreach($classes as $c)<option value="{{ $c['id'] }}" @selected($c['id'] === $class['id'])>{{ $c['label'] }}</option>@endforeach</select></label>
            <label class="f">Exam<select name="exam" class="ds-input">@foreach($sittings as $s)<option value="{{ $s['key'] }}" @selected($s['key'] === $sitting['key'])>{{ $s['label'] }}</option>@endforeach</select></label>
            @if(count(array_filter(array_column($links, 'stream'))) > 0)
            <div class="f" style="display:flex;flex-direction:column;gap:6px;"><span style="font-size:.85rem;font-weight:600;">Stream</span>
                <div class="rc-seg" role="radiogroup" aria-label="Stream" style="grid-template-columns:repeat(auto-fit,minmax(90px,1fr));">
                    <label><input type="radio" name="stream" value="" @checked(! $streamId)> All</label>
                    @foreach($links as $l)@if($l['stream'] !== '')<label><input type="radio" name="stream" value="{{ $l['id'] }}" @checked($streamId === $l['id'])> {{ $l['stream'] }}</label>@endif @endforeach
                </div></div>
            @endif
            <div class="f" style="display:flex;flex-direction:column;gap:6px;"><span style="font-size:.85rem;font-weight:600;">Sort by</span>
                <div class="rc-seg" role="radiogroup" aria-label="Sort by" style="grid-template-columns:1fr 1fr;">
                    <label><input type="radio" name="sort" value="position" @checked($sort !== 'name')> Position</label>
                    <label><input type="radio" name="sort" value="name" @checked($sort === 'name')> Name</label>
                </div></div>
            <button type="submit" class="ds-btn ds-btn-primary" style="min-height:52px;">Show {{ $state === 'ready' ? ($streamId ? count($rows) : $total) : '' }} students</button>
        </form>
    </dialog>
    @endif
</div>

@endsection

@push('scripts')
<script>
// The admin layout re-renders its DOM after load, so handlers are delegated from the document.
(function () {
    document.addEventListener('click', function (e) {
        var open = e.target.closest('[data-open-filters]');
        var dlg = document.getElementById('rc-filter-sheet');
        if (open && dlg && typeof dlg.showModal === 'function') { dlg.showModal(); return; }
        if (dlg && e.target === dlg) { dlg.close(); }
    });
    window.addEventListener('load', function () {
        var root = document.querySelector('[data-testid="report-cards-screen"]');
        if (root && root.hasAttribute('data-pending')) { setTimeout(function () { window.location.reload(); }, 5000); }
    });
})();
</script>
@endpush
