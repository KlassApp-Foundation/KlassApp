{{-- SPDX-License-Identifier: MIT --}}
{{-- Release marks, step 2 of 2: the message and the confirmation. --}}
@extends($layout)

@section('outside-app')
    @include('marks-releases._styles')
@endsection

@section('content')
<div class="mr" data-testid="marks-release-preview">
    <h1>Release marks</h1>
    <p class="mr-sub">Step 2 of 2. Check the message, then confirm.</p>

    <div class="mr-card" role="dialog" aria-labelledby="mr-confirm-title">
        <h2 id="mr-confirm-title" style="font-size:17px;margin:0 0 12px">Send these marks?</h2>
        <dl class="mr-dl">
            <dt>Exam</dt><dd>{{ $examLabel }}</dd>
            <dt>Class</dt><dd>{{ $classLabel }}</dd>
            <dt>When</dt><dd>{{ $whenLabel }}</dd>
        </dl>
        <p class="mr-note">Only parents following a student in the demo get a message. Everyone else is skipped, and the result page says why.</p>
        <p style="font-weight:600;margin:0 0 6px">Message preview</p>
        <pre class="mr-msg" data-testid="release-marks-message">{{ $message }}</pre>

        <form method="POST" action="{{ route($area.'.marks.releases.store') }}" class="mr-actions" style="margin-top:16px">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $data['exam_id'] }}">
            <input type="hidden" name="standard_link_id" value="{{ $data['standard_link_id'] }}">
            <input type="hidden" name="when" value="{{ $data['when'] }}">
            <input type="hidden" name="scheduled_for" value="{{ $data['scheduled_for'] }}">
            <input type="hidden" name="timezone" value="{{ $timezone }}">
            <button type="submit" class="mr-btn" data-testid="release-marks-confirm">{{ $data['when'] === 'schedule' ? 'Confirm schedule' : 'Confirm and release' }}</button>
            <a class="mr-btn mr-btn--soft" href="{{ route($area.'.marks.releases.create', ['exam_id' => $data['exam_id'], 'standard_link_id' => $data['standard_link_id'], 'when' => $data['when'], 'scheduled_for' => $data['scheduled_for']]) }}">Back</a>
        </form>
    </div>
</div>
@endsection
