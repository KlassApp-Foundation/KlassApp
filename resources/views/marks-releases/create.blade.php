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
@include('partials.ka-screen')
<div class="mr" data-testid="marks-release-create">
    @if(($area ?? '') === 'admin')
        <h1 class="ka-h1">Marks</h1>
        @include('partials.ka-marks-nav', ['marksNav' => 'release'])
        @include('partials.ka-lifecycle', ['current' => 'approved'])
    @endif
    <h1>Marks Release</h1>
    <p class="mr-sub">Choose the exam and who receives it. The sheet shows the marks that will be sent.</p>
    @if(!empty($exams) && $exams->isNotEmpty())
        <ol class="ka-life" data-testid="release-steps" aria-label="Release steps">
            <li class="{{ ($subjectsApproved ?? false) ? 'is-done' : 'is-now' }}"><span>1</span><span><strong>All subjects approved</strong><br><span style="font-size:.75rem;color:#475569">{{ collect($approvals ?? [])->where('state', 'approved')->count() }} of {{ count($approvals ?? []) }} approved</span></span></li>
            <li class="{{ ($confirming ?? false) ? 'is-now' : '' }}"><span>2</span><span><strong>Preview</strong><br><span style="font-size:.75rem;color:#475569">Check the sheet and the message</span></span></li>
            <li><span>3</span><span><strong>Release</strong><br><span style="font-size:.75rem;color:#475569">Families are told only from this step</span></span></li>
        </ol>
    @endif

    @if($exams->isEmpty())
        <div class="mr-card"><p style="margin:0">No exam has marks in your classes yet.</p></div>
    @else
    <div class="mr-page">
        <div class="mr-filters">
            <div class="mr-bar" data-testid="marks-release-controls" id="release-filters" data-ka-filters>
                <button type="button" class="ka-filter-launch ds-btn ds-btn-outline" data-ka-open-filters="release-filters" data-testid="marks-filters-open">Filters</button>
                <div class="ka-filter-backdrop" data-ka-close-filters></div>
                <form class="mr-scope ka-filter-panel" method="GET" action="{{ route($area.'.marks.releases.create') }}">
                    <h2 class="ka-h2 ka-filter-launch">Filters</h2>
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
            </div>
        </div>

        <form class="mr-go" method="POST" action="{{ ($confirming ?? false) || (($old['pilot'] ?? 'class') !== 'chosen') ? route($area.'.marks.releases.store') : route($area.'.marks.releases.preview') }}" data-store-action="{{ route($area.'.marks.releases.store') }}" data-preview-action="{{ route($area.'.marks.releases.preview') }}" data-confirming="{{ ($confirming ?? false) ? '1' : '0' }}" style="display:contents">
            @csrf
            <div class="mr-reason">
                <input type="hidden" name="exam_id" value="{{ $examId }}">
                <input type="hidden" name="class" value="{{ $selectedClass }}">
                <input type="hidden" name="stream" value="{{ $selectedStream }}">
                @if(!empty($old['standard_link_id']))
                    <input type="hidden" name="standard_link_id" value="{{ (int) $old['standard_link_id'] }}">
                @endif
                <input type="hidden" name="timezone" class="mr-timezone" value="{{ $old['timezone'] ?? 'Africa/Kampala' }}">
                @if(($needsReason ?? false) || $errors->has('release_reason'))
                    <label class="mr-reason-box" data-testid="release-reason">
                        <span>{{ $releaseReasonPrompt ?? 'Why release this sitting again?' }}</span>
                        <textarea name="release_reason" maxlength="500" required>{{ old('release_reason') }}</textarea>
                    </label>
                    <p class="mr-sub" style="margin:8px 0 0">Required. Families get a short note that results were updated. Your reason is kept in the release history, not sent to families.</p>
                @endif
                @error('release_reason')<p class="mr-error" data-testid="release-reason-error">{{ $message }}</p>@enderror
            </div>

            <aside class="mr-aside" data-testid="release-aside">
                <h2 class="ka-h2">Who will be told</h2>
                <fieldset class="mr-pilot" data-testid="release-pilot">
                    <legend>Who receives this release</legend>
                    <label><input type="radio" name="pilot" value="class" @checked(($old['pilot'] ?? 'class') !== 'chosen')> Everyone in this class</label>
                    <label><input type="radio" name="pilot" value="chosen" @checked(($old['pilot'] ?? 'class') === 'chosen')> Only the people I choose</label>
                </fieldset>
                <div data-testid="release-pilot-list" @if(($old['pilot'] ?? 'class') !== 'chosen') hidden @endif @if($confirming ?? false) hidden @endif>
                @if(!($confirming ?? false))
                    <p class="mr-sub" style="margin:0" data-testid="release-pilot-scope">Only the people you choose are told. The rest of the class is not.</p>
                    @forelse(($pilotChoices ?? []) as $choice)
                        <div class="mr-pilot-learner">
                            <strong>{{ $choice['name'] }}</strong>
                            @if($choice['parents'] === [])
                                <label>
                                    <input type="checkbox" name="pilot_parents[]" value="{{ $choice['student_id'] }}:0" @checked(in_array($choice['student_id'].':0', $old['pilot_parents'] ?? [], true) || in_array($choice['student_id'], $old['pilot_learners'] ?? [], true))>
                                    This learner has no linked parent
                                </label>
                            @else
                                <label>
                                    <input type="checkbox" name="pilot_learners[]" value="{{ $choice['student_id'] }}" @checked(in_array($choice['student_id'], array_map('intval', $old['pilot_learners'] ?? []), true))>
                                    Every linked parent of this learner
                                </label>
                                @foreach($choice['parents'] as $parent)
                                    <label>
                                        <input type="checkbox" name="pilot_parents[]" value="{{ $choice['student_id'] }}:{{ $parent['id'] }}" @checked(in_array($choice['student_id'].':'.$parent['id'], $old['pilot_parents'] ?? [], true))>
                                        {{ $parent['name'] }} · {{ $parent['number'] }}
                                    </label>
                                @endforeach
                            @endif
                        </div>
                    @empty
                        <p class="mr-sub" style="margin:0">No learners in this class yet.</p>
                    @endforelse
                @endif
                </div>
                @error('pilot')<p class="mr-error" data-testid="release-pilot-error">{{ $message }}</p>@enderror
                @if($confirming && ($old['pilot'] ?? 'class') === 'chosen')
                    <input type="hidden" name="pilot_confirmed" value="1">
                    @foreach(($old['pilot_learners'] ?? []) as $learnerId)
                        <input type="hidden" name="pilot_learners[]" value="{{ (int) $learnerId }}">
                    @endforeach
                    @foreach(($old['pilot_parents'] ?? []) as $token)
                        <input type="hidden" name="pilot_parents[]" value="{{ $token }}">
                    @endforeach
                    <div data-testid="release-pilot-preview">
                        <p class="mr-sub" style="margin:0">The rest of the class is not told.</p>
                        @foreach(($pilotLines ?? []) as $line)
                            <article data-testid="release-pilot-person">
                                <p><strong>{{ $line['learner'] }}</strong> · {{ $line['parent'] }} · {{ $line['number'] }}</p>
                                <p>{{ $line['told'] ? 'Will be told' : 'Will not be told' }}@if($line['reason']) — {{ $line['reason'] }}@endif</p>
                                @if($line['message'] !== '')
                                    <div data-testid="release-pilot-message">{!! \App\Support\WhatsAppMessagePreview::html($line['message']) !!}</div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
                <p class="mr-sub" style="margin:0" data-testid="release-audience">Nothing is sent until you release, and KlassApp does not message a parent first.</p>
                @if(($old['pilot'] ?? 'class') !== 'chosen')
                @forelse(($audience['classes'] ?? []) as $classRow)
                    <div data-testid="release-audience-class">{{ $classRow['name'] }} · {{ $classRow['parents'] }} {{ \Illuminate\Support\Str::plural('parent', $classRow['parents']) }}</div>
                @empty
                    <div>No students in this selection yet.</div>
                @endforelse
                <div><strong>{{ $audience['parents'] ?? 0 }}</strong> parents · <strong>{{ $audience['whatsapp'] ?? 0 }}</strong> on WhatsApp</div>
                @endif
                <details class="mr-wa" data-testid="what-parents-receive">
                    <summary>What parents receive</summary>
                    @if($confirming)
                        <p class="mr-when-label">Send these marks?</p>
                    @endif
                    <div class="mr-wa-body" data-testid="release-marks-message">{!! \App\Support\WhatsAppMessagePreview::html($previewMessage) !!}</div>
                </details>
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
                @if($pastSitting ?? false)
                    <label class="mr-confirm-past" data-testid="release-confirm-past">
                        <span><input type="checkbox" name="confirm_past" value="1" @checked(old('confirm_past'))> This sitting is from an earlier term or year. Release it anyway.</span>
                    </label>
                @endif
                <label class="mr-wa-send" data-testid="release-whatsapp-send">
                    <span><input type="checkbox" name="whatsapp_send" value="1" @checked(old('whatsapp_send', $old['whatsapp_send'] ?? false))> Send these results on WhatsApp</span>
                </label>
                <p class="mr-sub" style="margin:0">Off by default. With it off, the release still happens and opens the portal, Toshi and the report cards, and nothing is sent. A send also waits until <code>results_released</code> is approved by Meta.</p>
                <button type="submit" class="mr-btn" data-testid="release-marks-confirm" data-release-to="{{ $releaseTo }}">{{ $confirming ? ($when === 'schedule' ? 'Confirm schedule' : 'Confirm and release') : ((($old['pilot'] ?? 'class') === 'chosen') ? 'Preview who will be told' : $buttonLabel) }}</button>
                @error('scheduled_for')<p class="mr-error">{{ $message }}</p>@enderror
                @error('exam_id')<p class="mr-error">{{ $message }}</p>@enderror
                @error('standard_link_id')<p class="mr-error">{{ $message }}</p>@enderror
                @error('approval')<p class="mr-error" data-testid="release-approval-error">{{ $message }}</p>@enderror
                @error('confirm_past')<p class="mr-error" data-testid="release-confirm-past-error">{{ $message }}</p>@enderror
            </aside>
        </form>

    @if(!empty($approvals))
        <x-fold-list tag="ul" class="mr-card mr-approvals" data-testid="release-subject-approvals">
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
            var pilotChosen = document.querySelector('input[name="pilot"][value="chosen"]');
            if (button && button.getAttribute('data-release-to') && !button.textContent.startsWith('Confirm') && !(pilotChosen && pilotChosen.checked)) {
                button.textContent = (schedule ? 'Schedule for ' : 'Release to ') + button.getAttribute('data-release-to');
            }
        }
        if (input.matches('label.mr-when-at input')) {
            var keepAt = document.getElementById('mr-when-at');
            if (keepAt) { keepAt.value = input.value; }
        }
        if (input.matches('input[name="pilot"]')) {
            var chosen = input.value === 'chosen' && input.checked;
            var list = document.querySelector('[data-testid="release-pilot-list"]');
            if (list) { list.hidden = !chosen; }
            var form = input.closest('form');
            if (form && form.getAttribute('data-confirming') !== '1') {
                form.action = chosen ? form.getAttribute('data-preview-action') : form.getAttribute('data-store-action');
            }
            var pilotButton = document.querySelector('[data-testid="release-marks-confirm"]');
            if (pilotButton && !pilotButton.textContent.startsWith('Confirm')) {
                pilotButton.textContent = chosen ? 'Preview who will be told' : ('Release to ' + (pilotButton.getAttribute('data-release-to') || ''));
            }
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
