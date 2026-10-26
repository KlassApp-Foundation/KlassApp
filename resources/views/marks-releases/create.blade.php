{{-- SPDX-License-Identifier: MIT --}}
{{-- Release marks: controls, the combined mark sheet, and one example of the WhatsApp message. --}}
@extends($layout)

@section('outside-app')
    @include('marks-releases._styles')
@endsection

@section('content')
@php
    $examId = (int) ($old['exam_id'] ?? 0);
    $when = $old['when'] ?? 'now';
    $selectedClass = (string) ($old['class'] ?? '');
    $selectedStream = (string) ($old['stream'] ?? '');
@endphp
<div class="mr" data-testid="marks-release-create">
    <h1>Release marks</h1>
    <p class="mr-sub">Choose the exam and who receives it. The sheet shows the marks that will be sent.</p>

    @if($exams->isEmpty())
        <div class="mr-card"><p style="margin:0">No exam has marks in your classes yet.</p></div>
    @else
    <div class="mr-head">
        <div class="mr-bar" data-testid="marks-release-controls">
            <form class="mr-scope" method="GET" action="{{ route($area.'.marks.releases.create') }}">
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
                    <select name="class" data-testid="release-class" onchange="this.form.submit()">
                        @if($isAdmin)<option value="">All classes</option>@endif
                        @foreach($classOptions as $className)
                            <option value="{{ $className }}" @selected($selectedClass === $className)>{{ $className }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="mr-control">
                    <span>Stream</span>
                    <select name="stream" data-testid="release-stream" onchange="this.form.submit()">
                        <option value="">All streams</option>
                        @foreach($streamOptions as $streamName)
                            <option value="{{ $streamName }}" @selected($selectedStream === $streamName)>{{ $streamName }}</option>
                        @endforeach
                    </select>
                </label>
                <input type="hidden" name="when" id="mr-when-keep" value="{{ $when }}">
                <input type="hidden" name="scheduled_for" id="mr-when-at" value="{{ $old['scheduled_for'] ?? '' }}">
            </form>

            <form class="mr-go" method="POST" action="{{ route($area.'.marks.releases.store') }}">
                @csrf
                <input type="hidden" name="exam_id" value="{{ $examId }}">
                <input type="hidden" name="class" value="{{ $selectedClass }}">
                <input type="hidden" name="stream" value="{{ $selectedStream }}">
                <input type="hidden" name="timezone" class="mr-timezone" value="{{ $old['timezone'] ?? 'Africa/Kampala' }}">
                <div class="mr-seg" role="radiogroup" aria-label="When">
                    <label><input type="radio" name="when" value="now" @checked($when !== 'schedule')> Release now</label>
                    <label><input type="radio" name="when" value="schedule" @checked($when === 'schedule')> Schedule</label>
                </div>
                <label class="mr-control mr-when-at" @if($when !== 'schedule') hidden @endif>
                    <span class="sr-only">Date and time</span>
                    <input type="datetime-local" name="scheduled_for" value="{{ $old['scheduled_for'] ?? '' }}" aria-label="Date and time">
                </label>
                @if($confirming)
                    <p class="mr-when-label" data-testid="release-when">{{ $whenLabel }}</p>
                @endif
                <button type="submit" class="mr-btn" data-testid="release-marks-confirm" data-release-to="{{ $releaseTo }}">{{ $confirming ? ($when === 'schedule' ? 'Confirm schedule' : 'Confirm and release') : $buttonLabel }}</button>
                @error('scheduled_for')<p class="mr-error">{{ $message }}</p>@enderror
                @error('exam_id')<p class="mr-error">{{ $message }}</p>@enderror
                @error('standard_link_id')<p class="mr-error">{{ $message }}</p>@enderror
                @error('approval')<p class="mr-error" data-testid="release-approval-error">{{ $message }}</p>@enderror
            </form>
        </div>

        <details class="mr-wa" data-testid="what-parents-receive">
            <summary>What parents receive</summary>
            @if($confirming)
                <p class="mr-when-label">Send these marks?</p>
            @endif
            <div class="mr-wa-body" data-testid="release-marks-message">{!! \App\Support\WhatsAppMessagePreview::html($previewMessage) !!}</div>
        </details>
    </div>

    @if(!empty($approvals))
        <x-fold-list tag="ul" class="mr-card" data-testid="release-subject-approvals">
            @foreach($approvals as $row)
                <li data-testid="release-subject-approval" data-state="{{ $row['state'] }}">{{ $row['name'] }}@if($row['class_name'] !== '') · {{ $row['class_name'] }}@endif — {{ $row['label'] }}</li>
            @endforeach
        </x-fold-list>
    @endif

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

    // Delegate. Vue remounts #app after this script runs, so listeners on the
    // original nodes would never see a click.
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || !input.matches) { return; }
        if (input.matches('.mr-seg input[name="when"]')) {
            var schedule = input.value === 'schedule' && input.checked;
            var field = document.querySelector('label.mr-when-at');
            if (field) { field.hidden = !schedule; }
            var keep = document.getElementById('mr-when-keep');
            if (keep) { keep.value = input.value; }
            var button = document.querySelector('[data-testid="release-marks-confirm"]');
            if (button && button.getAttribute('data-release-to') && !button.textContent.startsWith('Confirm')) {
                button.textContent = (schedule ? 'Schedule for ' : 'Release to ') + button.getAttribute('data-release-to');
            }
        }
        if (input.matches('label.mr-when-at input')) {
            var keepAt = document.getElementById('mr-when-at');
            if (keepAt) { keepAt.value = input.value; }
        }
    });

    var dir = {};
    document.addEventListener('click', function (e) {
        var button = e.target && e.target.closest ? e.target.closest('[data-testid="marks-release-sheet"] button[data-sort]') : null;
        if (!button) { return; }
        var sheet = button.closest('[data-testid="marks-release-sheet"]');
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
})();
</script>
@endpush
