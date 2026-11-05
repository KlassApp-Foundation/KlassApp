@extends('layouts.teacher.layout')

@section('content')
@include('partials.ka-screen')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
<main class="ka-main">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:16px;align-items:flex-start">
        <div>
            <h1 class="ka-h1">Your exams to mark</h1>
            @php
                $firstTerm = $exams->first()?->academicTerm;
            @endphp
            <p class="ka-sub">@if($firstTerm){{ $firstTerm->name }} · @endif The exams assigned to you, by class and subject.</p>
        </div>
        @include('marks-releases._button', ['area' => 'teacher', 'class' => 'ds-btn ds-btn-outline', 'style' => 'white-space:nowrap;min-height:44px'])
    </div>

    @include('partials.message')

    @if($exams->isEmpty())
        <div class="ka-empty" data-testid="teacher-marks-empty">
            <h2 class="ka-h2">No exams waiting for your marks</h2>
            <p class="ka-sub">No exams yet: create this term's exam set. When the school sets this term's exams, yours appear here by class and subject.</p>
            @if($canCreateExams)
                <a href="{{ route('teacher.exam-sets.create') }}" class="ds-btn ds-btn-primary" style="min-height:44px" data-testid="teacher-exam-set-link">Create this term's exam set</a>
            @endif
        </div>
    @else
        <div id="to-mark-filters" data-ka-filters>
            <button type="button" class="ka-filter-launch ds-btn ds-btn-outline" data-ka-open-filters="to-mark-filters">Filters</button>
            <div class="ka-filter-backdrop" data-ka-close-filters></div>
            <div class="ka-filter-panel" role="group" aria-label="Status">
                <h2 class="ka-h2 ka-filter-launch">Filters</h2>
                @foreach(['All', 'Not started', 'In progress', 'Submitted', 'Returned', 'Approved'] as $status)
                    <a class="ka-chip {{ request('status', 'All') === $status ? 'is-on' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $status === 'All' ? null : $status]) }}">{{ $status }}</a>
                @endforeach
            </div>
        </div>
        @foreach($examsByClass as $sectionId => $classExams)
            @php
                $stdLink = $assignedStdLinks->get($sectionId);
                $className = $classExams->first()->section->name ?? ('Class #' . $sectionId);
                $visible = $classExams->filter(function ($exam) {
                    $status = request('status');
                    if (! $status || $status === 'All') return true;
                    $label = (string) ($exam->submission_label ?? 'Not started');
                    $shown = $label === 'Not started' && (int) ($exam->marks_entered ?? 0) > 0 ? 'In progress' : $label;
                    return $shown === $status;
                });
            @endphp
            @if($visible->isNotEmpty())
            <section style="display:flex;flex-direction:column;gap:8px">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
                    <h2 class="ka-h2">{{ $className }}</h2>
                    @if ($stdLink)
                        <a href="{{ route('teacher.marks.sheet', ['class' => $stdLink->section_id, 'stream' => $stdLink->id, 'term' => $classExams->first()->academic_term_id]) }}" class="ds-btn ds-btn-outline" style="min-height:44px" data-testid="combined-sheet-link">My class</a>
                    @endif
                </div>
                <div class="ka-card">
                    @foreach($visible as $exam)
                        @php
                            $label = (string) ($exam->submission_label ?? 'Not started');
                            $action = match ($label) {
                                'Returned' => 'Fix and resubmit',
                                'Submitted', 'Approved', 'Locked' => 'View marks',
                                default => ((int) ($exam->marks_entered ?? 0) > 0 ? 'Continue' : 'Enter marks'),
                            };
                            $pill = match ($label) {
                                'Approved' => 'ka-pill ka-pill-green',
                                'Submitted', 'Locked' => 'ka-pill ka-pill-amber',
                                'Returned' => 'ka-pill ka-pill-red',
                                default => ((int) ($exam->marks_entered ?? 0) > 0 ? 'ka-pill ka-pill-blue' : 'ka-pill ka-pill-muted'),
                            };
                        @endphp
                        <div class="ka-row">
                            <div style="flex:2 1 220px">
                                <div style="font-weight:700">{{ $exam->subject->name ?? 'Exam' }} · {{ $exam->examType->name ?? 'Exam' }}</div>
                                <div style="font-size:.8rem;color:#64748B">{{ $exam->section->name ?? 'Class' }} · {{ (int) ($exam->marks_entered ?? 0) }} marks entered</div>
                            </div>
                            <span class="{{ $pill }}" data-testid="exam-submission-state">{{ $label === 'Not started' && (int) ($exam->marks_entered ?? 0) > 0 ? 'In progress' : $label }}</span>
                            <a href="{{ route('teacher.exam.marks.enter', $exam) }}" class="ds-btn ds-btn-primary" style="min-height:44px;min-width:150px">{{ $action }}</a>
                            <a href="{{ route('teacher.exam.marks.import.page', $exam) }}" class="ds-btn ds-btn-outline" style="min-height:44px" data-testid="marks-import-button">Paste or upload marks</a>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif
        @endforeach
    @endif
</main>
</div>
@endsection