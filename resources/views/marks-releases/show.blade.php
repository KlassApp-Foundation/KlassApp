{{-- SPDX-License-Identifier: MIT --}}
{{-- One marks release: when, who, and per student sent or skipped with the reason. --}}
@extends($layout)

@section('outside-app')
    @include('marks-releases._styles')
@endsection

@section('content')
@php
    $tz = $release->timezone ?: 'Africa/Kampala';
    $fmt = fn ($t) => $t ? $t->setTimezone($tz)->format('D j M Y, H:i') : '–';
@endphp
<div class="mr" data-testid="marks-release-show">
    <h1>Marks release</h1>
    <p class="mr-sub">Times in {{ $tz }}.</p>
    @if(session('status'))
        <div class="mr-card" role="status" data-testid="release-flash">{{ session('status') }}</div>
    @endif
    @if(($release->result['queued'] ?? 0) > 0)
        <p class="mr-sub" data-testid="release-in-progress">{{ $release->result['queued'] }} still sending. Refresh this page to update.</p>
    @endif
    <div class="mr-card">
        <dl class="mr-dl">
            <dt>Exam</dt><dd>{{ $release->exam?->examType?->name ?? 'Exam' }}{{ $release->exam?->academicTerm?->name ? ' · '.$release->exam->academicTerm->name : '' }}</dd>
            <dt>Class</dt><dd>{{ $release->scope === 'school' ? 'All classes' : trim(($release->standardLink?->section?->name ?? '').' '.($release->standardLink?->stream ?? '')) }}</dd>
            <dt>Status</dt><dd data-testid="release-status">{{ ucfirst($release->status) }}</dd>
            <dt>Scheduled</dt><dd>{{ $fmt($release->scheduled_at) }}</dd>
            <dt>Released</dt><dd>{{ $fmt($release->released_at) }}</dd>
            <dt>By</dt><dd>{{ $release->creator?->name ?? '–' }}</dd>
            <dt>Sent</dt><dd data-testid="release-sent">{{ $release->result['sent'] ?? '–' }}</dd>
            <dt>Skipped</dt><dd data-testid="release-skipped">{{ $release->result['skipped'] ?? '–' }}</dd>
            @if(isset($release->result['queued']))
            <dt>Sending</dt><dd data-testid="release-queued">{{ $release->result['queued'] }}</dd>
            <dt>Failed</dt><dd data-testid="release-failed">{{ $release->result['failed'] ?? 0 }}</dd>
            @endif
        </dl>
        @if(! empty($release->result['reasons']))
            <p style="font-weight:600;margin:0 0 6px">Why some were skipped or failed</p>
            <x-fold-list tag="ul" data-testid="release-reasons" style="margin:0 0 12px;padding-left:18px">
                @foreach($release->result['reasons'] as $reason => $count)
                    <li>{{ $reason }}: {{ $count }}</li>
                @endforeach
            </x-fold-list>
        @endif
        <a class="mr-btn mr-btn--soft" href="{{ route($area.'.marks.releases.index') }}">All releases</a>
    </div>

    @if(! empty($release->result['messages']))
    <div class="mr-card mr-scroll">
        <x-fold-list table>
        <table class="mr-table">
            <thead><tr><th>Student</th><th>Class</th><th>Number</th><th>Result</th></tr></thead>
            <tbody>
            @foreach($release->result['messages'] as $m)
                <tr>
                    <td data-testid="release-student-name">{{ $m['name'] ?? $m['student'] }}</td>
                    <td data-testid="release-student-class">{{ ($m['class'] ?? '') !== '' ? $m['class'] : '–' }}</td>
                    <td>{{ $m['to'] ?? '–' }}</td>
                    <td>{{ match ($m['status']) { 'sent' => 'Sent', 'queued' => 'Sending…', 'retrying' => 'Retrying: '.$m['reason'], 'failed' => 'Failed: '.$m['reason'], default => 'Skipped: '.$m['reason'] } }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </x-fold-list>
        @if(($release->result['failed'] ?? 0) > 0)
            <p class="mr-sub" data-testid="release-resend-hint">To try the failed numbers again, release this exam again: numbers already sent are skipped.</p>
        @endif
    </div>
    @endif
</div>
@endsection
