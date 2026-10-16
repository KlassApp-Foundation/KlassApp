{{-- SPDX-License-Identifier: MIT --}}
{{-- Release marks: controls, the combined mark sheet, and one example of the WhatsApp message. --}}
@extends($layout)

@section('outside-app')
    @include('marks-releases._styles')
@endsection

@section('content')
@php
    $examId = (int) ($old['exam_id'] ?? 0);
    $linkId = $old['standard_link_id'] ?? null;
    $when = $old['when'] ?? 'now';
    $classes = collect($sheet['groups'] ?? [])->pluck('class_name')->filter()->unique()->values();
    $streams = collect($sheet['groups'] ?? [])->pluck('stream')->filter()->unique()->values();
@endphp
<div class="mr" data-testid="marks-release-create">
    <h1>Release marks</h1>
    <p class="mr-sub">{{ $confirming ? 'Check the sheet, then confirm. Nothing is sent until you do.' : 'Choose the exam and who receives it. The sheet is who those marks belong to.' }}</p>

    @if($exams->isEmpty())
        <div class="mr-card"><p style="margin:0">No exam has marks in your classes yet.</p></div>
    @else
    <div class="mr-top">
        <form class="mr-controls" method="GET" action="{{ route($area.'.marks.releases.create') }}" data-testid="marks-release-controls">
            <label class="mr-control">
                <span>Exam</span>
                <select name="exam_id" onchange="this.form.submit()">
                    @foreach($exams as $e)
                        <option value="{{ $e['id'] }}" @selected($examId === $e['id'])>{{ $e['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="mr-control">
                <span>Class</span>
                <select name="standard_link_id" onchange="this.form.submit()">
                    @if($isAdmin)<option value="">All classes</option>@endif
                    @foreach($links as $l)
                        <option value="{{ $l->id }}" @selected((int) $linkId === (int) $l->id)>{{ trim(($l->section?->name ?? '').' '.($l->stream ?? '')) }}</option>
                    @endforeach
                </select>
            </label>
            <input type="hidden" name="when" value="{{ $when }}">
            <input type="hidden" name="scheduled_for" value="{{ $old['scheduled_for'] ?? '' }}">
        </form>

        <form class="mr-release" method="POST" action="{{ route($area.'.marks.releases.store') }}">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $examId }}">
            <input type="hidden" name="standard_link_id" value="{{ $linkId }}">
            <input type="hidden" name="timezone" class="mr-timezone" value="{{ $old['timezone'] ?? 'Africa/Kampala' }}">
            <fieldset class="mr-when">
                <legend class="sr-only">When</legend>
                <label><input type="radio" name="when" value="now" @checked($when !== 'schedule')> Release now</label>
                <label><input type="radio" name="when" value="schedule" @checked($when === 'schedule')> Schedule</label>
                <input type="datetime-local" name="scheduled_for" value="{{ $old['scheduled_for'] ?? '' }}" aria-label="Date and time">
            </fieldset>
            @if($confirming)
                <p class="mr-when-label" data-testid="release-when">{{ $whenLabel }}</p>
                <button type="submit" class="mr-btn" data-testid="release-marks-confirm">{{ $when === 'schedule' ? 'Confirm schedule' : 'Confirm and release' }}</button>
            @else
                <button type="submit" class="mr-btn" data-testid="release-marks-confirm">{{ $when === 'schedule' ? 'Schedule release' : 'Release now' }}</button>
            @endif
            @error('scheduled_for')<p class="mr-error">{{ $message }}</p>@enderror
            @error('exam_id')<p class="mr-error">{{ $message }}</p>@enderror
            @error('standard_link_id')<p class="mr-error">{{ $message }}</p>@enderror
        </form>

        <aside class="mr-wa" data-testid="what-parents-receive">
            <h2>What parents receive</h2>
            @if($confirming)
                <p class="mr-when-label">Send these marks?</p>
            @endif
            <div class="mr-wa-body" data-testid="release-marks-message">{!! \App\Support\WhatsAppMessagePreview::html($previewMessage) !!}</div>
        </aside>
    </div>

    <div class="mr-sheet-tools">
        <label>Class
            <select id="mr-filter-class" data-testid="sheet-filter-class">
                <option value="">All classes</option>
                @foreach($classes as $className)
                    <option value="{{ $className }}">{{ $className }}</option>
                @endforeach
            </select>
        </label>
        <label>Stream
            <select id="mr-filter-stream" data-testid="sheet-filter-stream">
                <option value="">All streams</option>
                @foreach($streams as $streamName)
                    <option value="{{ $streamName }}">{{ $streamName }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="mr-sheet {{ $wide ? 'mr-sheet--wide' : '' }}" data-testid="marks-release-sheet">
        <table class="mr-grid">
            <thead>
                <tr>
                    <th><button type="button" data-sort="name">Student</button></th>
                    @foreach($sheet['subjects'] as $subject)
                        <th class="mr-num"><button type="button" data-sort="subject" data-subject="{{ $subject['key'] }}">{{ $subject['name'] }}</button></th>
                    @endforeach
                    <th class="mr-num"><button type="button" data-sort="total">Total</button></th>
                    <th class="mr-num"><button type="button" data-sort="average">Average</button></th>
                    <th class="mr-num">Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sheet['groups'] as $group)
                    <tr class="mr-group" data-class="{{ $group['class_name'] }}" data-stream="{{ $group['stream'] }}">
                        <th colspan="{{ 4 + count($sheet['subjects']) }}">
                            {{ $group['class_name'] !== '' ? $group['class_name'] : 'Class' }}
                            @if($group['stream'] !== '') · {{ $group['stream'] }} @endif
                            · {{ $group['count'] }} {{ $group['count'] === 1 ? 'student' : 'students' }}
                        </th>
                    </tr>
                    @foreach($group['students'] as $student)
                        <tr class="mr-student" data-testid="sheet-student" data-class="{{ $group['class_name'] }}" data-stream="{{ $group['stream'] }}" data-name="{{ $student['name'] }}" data-total="{{ $student['sort_total'] }}" data-average="{{ $student['sort_average'] }}">
                            <th scope="row">{{ $student['name'] }}</th>
                            @foreach($sheet['subjects'] as $subject)
                                @php $cell = $student['cells'][$subject['key']] ?? null; @endphp
                                <td class="mr-num" data-subject="{{ $subject['key'] }}" data-value="{{ $cell['mark'] ?? '' }}">
                                    @if($cell)
                                        <span class="mr-mark">{{ $cell['mark'] }}</span>
                                        <span class="mr-grade">{{ $cell['grade'] }}</span>
                                    @else
                                        <span class="mr-mark">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="mr-num">{{ $student['total'] }}</td>
                            <td class="mr-num">{{ $student['average'] }}</td>
                            <td class="mr-num"><strong>{{ $student['grade'] }}</strong></td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ 4 + count($sheet['subjects']) }}">No students in this exam yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    function timezone() {
        try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { return ''; }
    }
    document.addEventListener('submit', function (e) {
        var input = e.target && e.target.querySelector ? e.target.querySelector('input.mr-timezone') : null;
        var tz = timezone();
        if (input && tz) { input.value = tz; }
    }, true);

    var sheet = document.querySelector('[data-testid="marks-release-sheet"]');
    if (!sheet) { return; }
    var classFilter = document.getElementById('mr-filter-class');
    var streamFilter = document.getElementById('mr-filter-stream');
    function applyFilters() {
        var cls = classFilter ? classFilter.value : '';
        var stream = streamFilter ? streamFilter.value : '';
        sheet.querySelectorAll('tr[data-class]').forEach(function (row) {
            var show = (cls === '' || row.getAttribute('data-class') === cls) && (stream === '' || row.getAttribute('data-stream') === stream);
            row.hidden = !show;
        });
    }
    if (classFilter) { classFilter.addEventListener('change', applyFilters); }
    if (streamFilter) { streamFilter.addEventListener('change', applyFilters); }

    var dir = {};
    sheet.querySelectorAll('button[data-sort]').forEach(function (button) {
        button.addEventListener('click', function () {
            var key = button.getAttribute('data-sort') + ':' + (button.getAttribute('data-subject') || '');
            dir[key] = dir[key] === 'asc' ? 'desc' : 'asc';
            var subject = button.getAttribute('data-subject');
            sheet.querySelectorAll('tr.mr-group').forEach(function (header) {
                var rows = [];
                var next = header.nextElementSibling;
                while (next && next.classList.contains('mr-student')) {
                    rows.push(next);
                    next = next.nextElementSibling;
                }
                rows.sort(function (a, b) {
                    var av, bv;
                    if (button.getAttribute('data-sort') === 'name') {
                        av = a.getAttribute('data-name') || '';
                        bv = b.getAttribute('data-name') || '';
                        return dir[key] === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
                    }
                    if (subject) {
                        var ac = a.querySelector('[data-subject="' + subject + '"]');
                        var bc = b.querySelector('[data-subject="' + subject + '"]');
                        av = parseFloat(ac ? ac.getAttribute('data-value') : '');
                        bv = parseFloat(bc ? bc.getAttribute('data-value') : '');
                    } else {
                        av = parseFloat(a.getAttribute('data-' + button.getAttribute('data-sort')) || '');
                        bv = parseFloat(b.getAttribute('data-' + button.getAttribute('data-sort')) || '');
                    }
                    if (isNaN(av)) { av = -1; }
                    if (isNaN(bv)) { bv = -1; }
                    return dir[key] === 'asc' ? av - bv : bv - av;
                });
                rows.forEach(function (row) { header.parentNode.insertBefore(row, next); });
            });
        });
    });
})();
</script>
@endpush
