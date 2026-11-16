@extends('layouts.admin.layout')
@section('content')

@include('partials.ka-screen')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
<main class="ka-main">
    <h1 class="ka-h1">Marks</h1>
    <p class="ka-sub">Check what teachers submit, then release it to families. Approving does not show anything to families.</p>
    @include('partials.ka-marks-nav', ['marksNav' => 'approvals'])
    @include('partials.ka-lifecycle', ['current' => 'submitted'])

    <p class="mb-4 text-sm text-gray-700" data-testid="marks-submissions-count">{{ $pendingCount }} awaiting review</p>

    @if($errors->any())
        <div class="ka-alert ka-alert-error" role="alert" data-testid="approval-error">{{ $errors->first() }}</div>
    @endif

    <!-- design review: empty state has no remind-teachers action; there is no remind route yet -->
    <div id="approval-filters" data-ka-filters>
        <button type="button" class="ka-filter-launch ds-btn ds-btn-outline" data-ka-open-filters="approval-filters" data-testid="approvals-filters-open">Filters</button>
        <div class="ka-filter-backdrop" data-ka-close-filters></div>
        <form class="ka-filter-panel" method="GET" action="{{ route('admin.marks.submissions') }}">
            <h2 class="ka-h2 ka-filter-launch">Filters</h2>
            <label class="ka-filter-field">Class
                <select name="class_id" class="ds-input" style="min-height:44px">
                    <option value="">All classes</option>
                    @foreach($classOptions as $section)
                        <option value="{{ $section->id }}" @selected((int) request('class_id') === (int) $section->id)>{{ $section->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="ka-filter-field">Exam
                <select name="exam_id" class="ds-input" style="min-height:44px">
                    <option value="">All exams</option>
                    @foreach($examOptions as $examOption)
                        <option value="{{ $examOption->id }}" @selected((int) request('exam_id') === (int) $examOption->id)>{{ $examOption->examType?->name ?? 'Exam' }} · {{ $examOption->section?->name ?? 'Class' }} · {{ $examOption->subject?->name ?? 'Subject' }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px">Apply</button>
        </form>
    </div>

    @if($submissions->isEmpty())
        <div class="ka-empty" data-testid="approvals-empty">
            <h2 class="ka-h2">Nothing waiting for approval</h2>
            <p class="ka-sub">No submitted marks found. When teachers submit, their subjects appear here.</p>
        </div>
    @else
        <form id="approve-selected-form" method="POST" action="{{ route('admin.marks.submissions.approve-selected') }}" data-testid="approve-selected-form">
            @csrf
        </form>
        <div class="ka-queue-tools">
            <span data-testid="approvals-selected"><span data-approve-n>0</span> selected</span>
            <button type="submit" form="approve-selected-form" class="ds-btn ds-btn-primary" style="min-height:44px" data-testid="approve-selected">Approve <span data-approve-n>0</span> <span data-approve-word>subjects</span></button>
        </div>
        <!-- design review: a below-another-class flag is not computed on this queue -->
        <div class="ka-queue" data-testid="approvals-queue">
            @foreach($submissions as $submission)
                @php
                    $stat = $markStats[$submission->id] ?? null;
                    $lock = $releaseLocks[$submission->id] ?? null;
                    $returnOpen = $errors->has('return_reason')
                        && (int) old('class_id') === (int) $submission->class_id
                        && (int) old('subject_id') === (int) $submission->subject_id
                        && (int) old('exam_id') === (int) $submission->exam_id;
                    $reopenOpen = $errors->has('reopen_reason')
                        && (int) old('class_id') === (int) $submission->class_id
                        && (int) old('subject_id') === (int) $submission->subject_id
                        && (int) old('exam_id') === (int) $submission->exam_id;
                @endphp
                <article class="ka-queue-card" data-testid="marks-submission-row">
                    <div class="ka-queue-main">
                        <input form="approve-selected-form" type="checkbox" name="submission_ids[]" value="{{ $submission->id }}" aria-label="Select {{ $submission->class->name ?? 'class' }} {{ $submission->subject->name ?? 'subject' }}" data-approve-box>
                        <div style="flex:1 1 180px;min-width:0">
                            <div style="font-weight:700">{{ $submission->class->name ?? 'Class' }} · {{ $submission->subject->name ?? 'Subject' }}</div>
                            <div style="font-size:.8rem;color:#64748B">{{ $submission->teacher->name ?? 'Teacher' }} · submitted {{ $submission->submitted_at?->format('j M Y H:i') ?? '—' }}</div>
                        </div>
                        <span class="ka-pill ka-pill-amber">{{ $submission->isLocked() ? 'Locked' : 'Submitted' }}</span>
                    </div>
                    @if($stat && $stat['count'] > 0)
                        <div style="font-size:.82rem;color:#334155">
                            {{ $stat['count'] }} {{ $stat['count'] === 1 ? 'mark' : 'marks' }}
                            @if($stat['average'] !== null)
                                · average <strong>{{ $stat['average'] }}</strong> · range {{ $stat['min'] }} to {{ $stat['max'] }}
                            @endif
                        </div>
                    @endif
                    <!-- design review: the roster total (marks of marks) is not stored on the submission, so this count is entered marks only -->
                    @if($lock)
                        @include('partials.ka-released-lock', ['lock' => $lock])
                    @endif
                    <div class="ka-action-row">
                        <a href="{{ route('admin.marks.submissions.show', $submission->exam_id) }}" class="ds-btn ds-btn-outline" style="min-height:44px">View marks</a>
                        @if($submission->isApprovable())
                            <button type="button" class="ds-btn ds-btn-outline" style="min-height:44px" data-ka-toggle="return-{{ $submission->id }}">Return</button>
                            <form action="{{ route('admin.marks.submissions.approve', $submission->exam_id) }}" method="POST" data-testid="marks-approve-form">
                                @csrf
                                <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                                <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                                <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px">Approve</button>
                            </form>
                        @endif
                        @if($canReopen && ($submission->isLocked() || $submission->isApproved()))
                            <button type="button" class="ds-btn ds-btn-outline" style="min-height:44px" data-ka-toggle="reopen-{{ $submission->id }}">Reopen</button>
                        @endif
                    </div>
                    @if($submission->isApprovable())
                        <form id="return-{{ $submission->id }}" action="{{ route('admin.marks.submissions.reject', $submission->exam_id) }}" method="POST" class="ka-return-box" @unless($returnOpen) hidden @endunless data-testid="marks-return-form">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                            <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                            <input type="hidden" name="exam_id" value="{{ $submission->exam_id }}">
                            <label for="return-why-{{ $submission->id }}">Why are you returning it? {{ $submission->teacher->name ?? 'The teacher' }} sees this note.</label>
                            <textarea id="return-why-{{ $submission->id }}" name="return_reason" rows="2" required minlength="10" maxlength="500">{{ $returnOpen ? old('return_reason') : '' }}</textarea>
                            @if($returnOpen)
                                @error('return_reason')<p class="ka-alert ka-alert-error" role="alert">{{ $message }}</p>@enderror
                            @endif
                            <div class="ka-action-row">
                                <button type="button" class="ds-btn ds-btn-outline" style="min-height:44px" data-ka-toggle="return-{{ $submission->id }}">Cancel</button>
                                <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px;background:#B91C1C;border-color:#B91C1C">Return to {{ $submission->teacher->name ?? 'the teacher' }}</button>
                            </div>
                        </form>
                    @endif
                    @if($canReopen && ($submission->isLocked() || $submission->isApproved()))
                        {{-- design review: head teachers (usergroup 4) see Reopen; MarksSubmissionService still allows only usergroups 1, 2 and 3 --}}
                        <form id="reopen-{{ $submission->id }}" action="{{ route('admin.marks.submissions.reopen', $submission->exam_id) }}" method="POST" class="ka-return-box" @unless($reopenOpen) hidden @endunless data-testid="marks-reopen-form">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                            <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                            <input type="hidden" name="exam_id" value="{{ $submission->exam_id }}">
                            <label for="reopen-why-{{ $submission->id }}">Why reopen it? A reason is required.</label>
                            <textarea id="reopen-why-{{ $submission->id }}" name="reopen_reason" rows="2" required minlength="10" maxlength="500">{{ $reopenOpen ? old('reopen_reason') : '' }}</textarea>
                            @if($reopenOpen)
                                @error('reopen_reason')<p class="ka-alert ka-alert-error" role="alert">{{ $message }}</p>@enderror
                            @endif
                            <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px">Reopen</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-4">
            {{ $submissions->links() }}
        </div>
    @endif
</main>
</div>
<script>
document.querySelectorAll('[data-approve-box]').forEach(function (box) {
    box.addEventListener('change', function () {
        var n = document.querySelectorAll('[data-approve-box]:checked').length;
        document.querySelectorAll('[data-approve-n]').forEach(function (label) {
            label.textContent = String(n);
        });
        var word = document.querySelector('[data-approve-word]');
        if (word) word.textContent = n === 1 ? 'subject' : 'subjects';
    });
});
document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-ka-toggle]');
    if (!toggle) return;
    var panel = document.getElementById(toggle.getAttribute('data-ka-toggle'));
    if (panel) panel.hidden = !panel.hidden;
});
</script>

@endsection
