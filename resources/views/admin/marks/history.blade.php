{{-- SPDX-License-Identifier: MIT --}}
{{-- One class sitting: the released lock, the saved-copy check, and what happened. --}}
@extends('layouts.admin.layout')

@section('content')
@php
    $params = ['section' => $section->id, 'term' => $term->id, 'examType' => $examType->id];
    if ($subjectFilter) {
        $params['subject'] = $subjectFilter;
    }
    if ($learner) {
        $params['learner'] = $learner->id;
    }
    $sheetParams = ['class' => $section->id, 'term' => $term->id, 'examType' => $examType->id];
@endphp
<div class="ka-main ka-history" data-testid="marks-history">
    @if($canReopen)
        <input type="checkbox" id="ka-history-reopen" class="ka-sr ka-history-open" @checked($reopenOpen)>
    @endif

    <nav class="ka-crumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.marks.filter', $sheetParams) }}">Mark sheets</a>
        <span>/</span>
        <span>{{ $title }}</span>
        <span>/</span>
        <span>History</span>
    </nav>

    <div class="ka-history-head">
        <div class="ka-history-titles">
            <div class="ka-history-title-row">
                <h1 class="ka-h1">{{ $title }}</h1>
                @if($locked)
                    <span class="ka-pill ka-pill-green ka-history-lock" data-testid="released-pill">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 018 0v4"></path></svg>
                        Released and locked
                    </span>
                @endif
            </div>
            @if($meta !== '')
                <p class="ka-history-meta" data-testid="history-meta">{{ $meta }}</p>
            @endif
            @if($check['show'])
                <div class="ka-history-check {{ $check['passed'] ? 'is-pass' : 'is-fail' }}" data-testid="record-check">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v6c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6l7-3z"></path><path d="M9 12l2 2 4-4"></path></svg>
                    <div class="ka-history-check-copy">
                        @if($check['passed'])
                            <strong>Record check passed.</strong> These marks match the copy saved at release.
                        @else
                            <strong>Record check failed.</strong> These marks do not match the copy saved at release.
                        @endif
                        @foreach($check['hashes'] as $hash)
                            <div class="ka-history-hash" data-testid="record-hash">{{ $hash }}</div>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('admin.marks.history.check', $params) }}">
                        @csrf
                        <button type="submit" class="ds-btn ds-btn-outline">Check again</button>
                    </form>
                </div>
            @endif
        </div>
        @if($canReopen)
            <div class="ka-history-reopen-desktop">
                <label for="ka-history-reopen" class="ds-btn ds-btn-outline" aria-haspopup="dialog">Reopen marks</label>
                <span>Admins and head teachers only</span>
            </div>
        @endif
    </div>

    @if(session('status'))
        <p class="ka-history-status" role="status">{{ session('status') }}</p>
    @endif

    <div class="ka-history-panels">
        <input type="radio" id="ka-history-class" name="ka-history-show" class="ka-sr" checked>
        <input type="radio" id="ka-history-learner" name="ka-history-show" class="ka-sr">
        <div class="ka-history-switch" role="radiogroup" aria-label="Show">
            <label for="ka-history-class">The class</label>
            <label for="ka-history-learner">One learner</label>
        </div>

        <section class="ka-card ka-card-pad ka-history-class" aria-labelledby="h-class" data-testid="history-timeline">
            <div class="ka-history-class-head">
                <h2 id="h-class" class="ka-h2">What happened to this class's marks</h2>
                @if($subjects->isNotEmpty())
                    <form method="GET" action="{{ route('admin.marks.history', $params) }}" class="ka-history-subject">
                        @if($learner)
                            <input type="hidden" name="learner" value="{{ $learner->id }}">
                        @endif
                        <label for="fs" class="ka-lbl">Subject</label>
                        <select id="fs" name="subject" class="ds-input">
                            <option value="">All subjects</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected($subjectFilter === (int) $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ds-btn ds-btn-outline">Show</button>
                    </form>
                @endif
            </div>
            @if($visibleEvents === [])
                <p class="ka-history-empty">Nothing has been recorded for this class yet.</p>
            @else
                <ol class="ka-history-line">
                    @foreach($visibleEvents as $event)
                        @include('admin.marks.history-event', ['event' => $event])
                    @endforeach
                </ol>
            @endif
            @if($earlierEvents !== [])
                <details class="ka-history-earlier">
                    <summary>Show earlier entries</summary>
                    <ol class="ka-history-line">
                        @foreach($earlierEvents as $event)
                            @include('admin.marks.history-event', ['event' => $event])
                        @endforeach
                    </ol>
                </details>
            @endif
        </section>

        <section class="ka-card ka-card-pad ka-history-learner" aria-labelledby="h-one" data-testid="history-learner">
            <span class="ka-lbl">One mark</span>
            @if($learner)
                <div class="ka-history-learner-head">
                    <div>
                        <h2 id="h-one" class="ka-h2">{{ $learner->name }}@if($learnerSubject) · {{ $learnerSubject->name }}@endif</h2>
                        <p>
                            @if(filled($learner->registration_number)){{ $learner->registration_number }} · @endif{{ $section->name }}@if($learnerSubject) · {{ $learnerSubject->name }}@endif · {{ $examType->name }}
                        </p>
                    </div>
                    @if($learnerMark !== null)
                        <div class="ka-history-score">
                            <b>{{ $learnerMark }}</b>
                            <span>out of 100</span>
                        </div>
                    @endif
                </div>
                @if($students->count() > 1)
                    <form method="GET" action="{{ route('admin.marks.history', ['section' => $section->id, 'term' => $term->id, 'examType' => $examType->id]) }}" class="ka-history-subject">
                        @if($subjectFilter)
                            <input type="hidden" name="subject" value="{{ $subjectFilter }}">
                        @endif
                        <label for="learner" class="ka-lbl">Learner</label>
                        <select id="learner" name="learner" class="ds-input">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected((int) $learner->id === (int) $student->id)>{{ $student->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ds-btn ds-btn-outline">Show</button>
                    </form>
                @endif
                @if($trail !== [])
                    <ol class="ka-history-trail">
                        @foreach($trail as $event)
                            <li>
                                <span class="ka-dot ka-dot-{{ $event['kind'] }}"></span>
                                <div>
                                    <strong>{{ $event['title'] }}</strong>
                                    @if($event['change'])
                                        <span class="ka-history-change">{{ $event['change'] }}</span>
                                    @endif
                                    @if($event['by'] !== '' || $event['when'] !== '')
                                        <div class="ka-history-by">{{ $event['by'] }}@if($event['by'] !== '' && $event['when'] !== '') · @endif{{ $event['when'] }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            @else
                <p class="ka-history-empty">No marks for this class yet.</p>
            @endif
        </section>
    </div>

    @if($canReopen)
        <section class="ka-history-reopen" role="dialog" aria-labelledby="h-re" data-testid="history-reopen">
            <h2 id="h-re" class="ka-h2">Reopen {{ $section->name }} · {{ $examType->name }}?</h2>
            <ul>
                <li>The marks unlock so the class teacher can correct them.</li>
                <li>Families keep seeing the released results until you release again.</li>
                <li>Your reason is kept in this history. It isn't sent to families.</li>
            </ul>
            <form method="POST" action="{{ route('admin.marks.history.reopen', $params) }}">
                @csrf
                <label for="why">Why are you reopening it? <span class="ka-history-required">Required</span></label>
                <textarea id="why" name="reason" rows="3" required minlength="10" maxlength="500">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="ka-alert ka-alert-error" role="alert">{{ $message }}</p>
                @enderror
                <div class="ka-history-reopen-actions">
                    <label for="ka-history-reopen" class="ds-btn ds-btn-outline">Cancel</label>
                    <button type="submit" class="ds-btn ds-btn-primary">Reopen {{ $section->name }} marks</button>
                </div>
            </form>
        </section>
        <div class="ka-history-reopen-bar">
            <label for="ka-history-reopen" class="ds-btn ds-btn-outline" aria-haspopup="dialog">Reopen marks</label>
            <span>Admins and head teachers only</span>
        </div>
    @endif
</div>
@endsection
