{{-- SPDX-License-Identifier: MIT --}}
{{-- Release marks, step 1 of 2: exam, class, now or later. Step 2 is the preview and confirmation. --}}
@extends($layout)

@section('outside-app')
    @include('marks-releases._styles')
@endsection

@section('content')
<div class="mr" data-testid="marks-release-create">
    <h1>Release marks</h1>
    <p class="mr-sub">Step 1 of 2. You see the message before anything is sent.</p>

    @if($exams->isEmpty())
        <div class="mr-card"><p style="margin:0">No exam has marks in your classes yet.</p></div>
    @else
    <form class="mr-card" method="POST" action="{{ route($area.'.marks.releases.preview') }}" role="dialog" aria-labelledby="mr-dialog-title">
        @csrf
        <h2 id="mr-dialog-title" style="font-size:17px;margin:0 0 14px">Which marks, and when?</h2>
        <input type="hidden" name="timezone" id="mr-timezone" value="{{ $old['timezone'] ?? 'Africa/Kampala' }}">

        <div class="mr-field">
            <label for="mr-exam">Exam</label>
            <select id="mr-exam" name="exam_id" required>
                @foreach($exams as $e)
                    <option value="{{ $e['id'] }}" @selected((int) ($old['exam_id'] ?? 0) === $e['id'])>{{ $e['label'] }}</option>
                @endforeach
            </select>
            @error('exam_id')<p class="mr-error">{{ $message }}</p>@enderror
        </div>

        <div class="mr-field">
            <label for="mr-class">Class</label>
            <select id="mr-class" name="standard_link_id" @unless($isAdmin) required @endunless>
                @if($isAdmin)<option value="">All classes</option>@endif
                @foreach($links as $l)
                    <option value="{{ $l->id }}" @selected((int) ($old['standard_link_id'] ?? 0) === (int) $l->id)>{{ trim(($l->section?->name ?? '').' '.($l->stream ?? '')) }}</option>
                @endforeach
            </select>
            @unless($isAdmin)<p class="mr-help">As class teacher you can release marks for your own class.</p>@endunless
            @error('standard_link_id')<p class="mr-error">{{ $message }}</p>@enderror
        </div>

        <div class="mr-field">
            <fieldset>
                <legend>When</legend>
                <label class="mr-radio"><input type="radio" name="when" value="now" @checked(($old['when'] ?? 'now') === 'now')> Release now</label>
                <label class="mr-radio"><input type="radio" name="when" value="schedule" @checked(($old['when'] ?? '') === 'schedule')> Schedule for</label>
            </fieldset>
            <label for="mr-when" class="sr-only" style="position:absolute;left:-9999px">Date and time</label>
            <input type="datetime-local" id="mr-when" name="scheduled_for" value="{{ $old['scheduled_for'] ?? '' }}">
            <p class="mr-help">In your time zone: <span id="mr-tz-label">{{ $old['timezone'] ?? 'Africa/Kampala' }}</span>.</p>
            @error('scheduled_for')<p class="mr-error">{{ $message }}</p>@enderror
        </div>

        <div class="mr-actions">
            <button type="submit" class="mr-btn" data-testid="release-marks-preview">Preview the message</button>
            <a class="mr-btn mr-btn--soft" href="{{ route($area.'.marks.releases.index') }}">Cancel</a>
        </div>
    </form>
    @endif
</div>
@endsection

{{-- Outside #app: the Vue root drops <script> tags inside its template. The
     browser time zone is read at submit time, so a re-rendered form still gets it. --}}
@push('scripts')
<script>
    (function () {
        var tz = '';
        try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}
        if (!tz) { return; }
        document.addEventListener('submit', function (e) {
            var input = e.target && e.target.querySelector ? e.target.querySelector('input[name="timezone"]') : null;
            if (input) { input.value = tz; }
        }, true);
        window.addEventListener('load', function () {
            var label = document.getElementById('mr-tz-label');
            if (label) { label.textContent = tz; }
        });
    })();
</script>
@endpush
