{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="family-page" data-testid="teacher-dashboard">
    <main class="family-main" style="width:100% !important;margin-left:0 !important;margin-right:0 !important;">
        @if(($board['state'] ?? '') === 'loading')
            <section data-testid="teacher-loading" aria-busy="true">
                <div class="family-sk" style="height:28px;width:50%;"></div>
                <div class="family-sk" style="height:72px;"></div>
                <div class="family-sk" style="height:72px;"></div>
                <p>Loading your classes…</p>
            </section>
        @elseif(($board['state'] ?? '') === 'error')
            <section data-testid="teacher-error" role="alert" class="family-alert">
                <strong>Attendance wasn't saved</strong>
                <p>Today's register for {{ $board['head_class'] ?: 'your class' }} is kept on this phone and saves when you're back online.</p>
                <a class="ds-btn ds-btn-primary" href="{{ url()->current() }}">Try again now</a>
            </section>
        @elseif(!empty($board['empty']))
            <h1 data-testid="dashboard-greeting">{{ $board['greeting'] }}</h1>
            <section class="family-empty" data-testid="teacher-empty">
                <h2>No classes yet</h2>
                <p>When the school assigns you a class or a subject, your students, attendance and exams appear here.</p>
            </section>
        @else
            <div class="family-page-head">
                <div>
                    <h1 data-testid="dashboard-greeting">{{ $board['greeting'] }}</h1>
                    <p>{{ $board['dateline'] }} · {{ $board['role_line'] }}</p>
                </div>
                <div class="teacher-actions" data-testid="teacher-quick-actions">
                    <a class="ds-btn ds-btn-primary" href="{{ $board['attendance_url'] }}">Take attendance{{ $board['head_class'] ? ' · '.$board['head_class'] : '' }}</a>
                    <a class="ds-btn ds-btn-outline" href="{{ route('teacher.exam.marks') }}">Enter marks</a>
                    @include('marks-releases._button', ['area' => 'teacher', 'class' => 'ds-btn ds-btn-outline'])
                </div>
            </div>
            <div class="family-cards teacher-kpis">
                <div class="family-card"><strong>{{ $board['class_count'] }}</strong><span>my classes · {{ implode(', ', $board['class_names']) }}</span></div>
                <div class="family-card"><strong data-testid="teacher-students">{{ $board['student_count'] }}</strong><span>my students</span></div>
                <a class="family-card" href="{{ route('teacher.exam.marks') }}" data-testid="teacher-marks-needing-you" aria-label="Marks needing you, {{ $board['to_mark'] }} exams to mark">
                    <strong data-testid="marks-needing-you-count">{{ $board['to_mark'] }}</strong>
                    <span>Marks needing you</span>
                </a>
                <a class="family-card" href="{{ route('teacher.exam.marks') }}" data-testid="teacher-upcoming-exams">
                    <strong data-testid="upcoming-exams-count">{{ $board['upcoming_exams'] }}</strong>
                    <span>Upcoming exams</span>
                </a>
                <div class="family-card"><strong data-testid="teacher-returned-count">{{ $board['returned'] }}</strong><span>returned for fixing</span></div>
            </div>
            <section class="family-table-card" data-testid="teacher-marks-entry">
                <h2>Marks &amp; corrections</h2>
                <p>Returned work that needs fixing.</p>
                @forelse($board['corrections'] as $row)
                    <article class="teacher-row" data-testid="teacher-correction">
                        <h3>{{ $row['title'] }}</h3>
                        <p>{{ $row['detail'] }}</p>
                        <a class="ds-btn ds-btn-primary ds-btn-sm" href="{{ $row['url'] }}" data-testid="teacher-marks-entry-link">{{ $row['action'] }}</a>
                    </article>
                @empty
                    <p data-testid="teacher-corrections-empty">No returned work needs fixing.</p>
                @endforelse
            </section>
            <section class="family-table-card ka-marks-groups" data-testid="teacher-attention">
                <h2>Marks needing attention</h2>
                @php
                    $markGroups = ['Returned' => [], 'In progress' => [], 'Not started' => []];
                    foreach ($board['rows'] as $markRow) {
                        $markState = (string) ($markRow['state'] ?? '');
                        if (array_key_exists($markState, $markGroups)) {
                            $markGroups[$markState][] = $markRow;
                        }
                    }
                @endphp
                @if($board['rows'] === [])
                    <p>Nothing is waiting.</p>
                @else
                    @foreach($markGroups as $markState => $markRows)
                        @foreach($markRows as $row)
                            <article class="ka-marks-row teacher-row" data-testid="teacher-marks-row" data-state="{{ $markState }}">
                                <span class="ka-pill {{ $markState === 'Returned' ? 'ka-pill-red' : ($markState === 'In progress' ? 'ka-pill-blue' : 'ka-pill-muted') }}">{{ $markState }}</span>
                                <div class="ka-marks-copy">
                                    <h3>{{ $row['title'] }}</h3>
                                    <p class="{{ $markState === 'Returned' ? 'ka-marks-note' : 'ka-marks-detail' }}" @if($markState === 'Returned') data-testid="teacher-return-note" @endif>{{ $row['detail'] }}</p>
                                </div>
                                <a href="{{ $row['url'] }}" @if($markState === 'In progress') data-testid="teacher-marks-continue" @endif @if($markState === 'Not started') data-testid="teacher-marks-enter" @endif>{{ $row['action'] }}</a>
                            </article>
                        @endforeach
                    @endforeach
                @endif
            </section>
            @if($board['subjects'] !== [])
                <section class="family-table-card" data-testid="teacher-performance">
                    <h2>{{ $board['head_class'] }} performance</h2>
                    @foreach($board['subjects'] as $subject)
                        <p class="family-fee-line"><span>{{ $subject['name'] }}</span><strong>{{ $subject['average'] === null ? '—' : $subject['average'] }}</strong></p>
                    @endforeach
                </section>
            @endif
            <section class="family-table-card" data-testid="teacher-week">
                <h2>Attendance this week</h2>
                <p>present of {{ $board['week_total'] }}</p>
                <div class="family-bars">
                    @foreach($board['week'] as $day)
                        <div class="family-bar">
                            <span class="family-bar__value">{{ $day['present_label'] ?? \App\Support\AttendanceDays::mark((float) $day['present']) }}</span>
                            <span class="family-bar__fill is-full" style="height: {{ max(8, (int) $day['present']) }}px;"></span>
                            <span class="family-bar__label">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
            @if($board['streams'] !== [])
                <section class="family-table-card">
                    <h2>Girls and boys</h2>
                    @foreach($board['streams'] as $stream)
                        <p class="family-fee-line"><span>{{ $stream['name'] }}</span><strong>{{ $stream['total'] }}</strong></p>
                        <p>Girls {{ $stream['girls'] }} · Boys {{ $stream['boys'] }}</p>
                    @endforeach
                </section>
            @endif
        @endif
    </main>
</div>
@endsection
