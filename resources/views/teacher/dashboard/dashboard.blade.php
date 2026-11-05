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
                    <a class="ds-btn ds-btn-outline" href="{{ $board['marks_url'] }}">Enter marks</a>
                </div>
            </div>
            <div class="family-cards teacher-kpis">
                <div class="family-card"><strong>{{ $board['class_count'] }}</strong><span>my classes · {{ implode(', ', $board['class_names']) }}</span></div>
                <div class="family-card"><strong data-testid="teacher-students">{{ $board['student_count'] }}</strong><span>my students</span></div>
                <a class="family-card" href="{{ $board['marks_url'] }}"><strong>{{ $board['to_mark'] }}</strong><span>exams to mark</span></a>
                <div class="family-card"><strong>{{ $board['returned'] }}</strong><span>returned for fixing</span></div>
            </div>
            <section class="family-table-card" data-testid="teacher-attention">
                <h2>Marks needing attention</h2>
                @forelse($board['rows'] as $row)
                    <article class="teacher-row">
                        <p class="family-kicker">{{ $row['state'] }}</p>
                        <h3>{{ $row['title'] }}</h3>
                        <p>{{ $row['detail'] }}</p>
                        <a href="{{ $row['url'] }}">{{ $row['action'] }}</a>
                    </article>
                @empty
                    <p>Nothing is waiting.</p>
                @endforelse
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
                            <span class="family-bar__value">{{ (int) $day['present'] }}</span>
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
