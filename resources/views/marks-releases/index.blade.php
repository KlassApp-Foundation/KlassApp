{{-- SPDX-License-Identifier: MIT --}}
{{-- Marks releases: the list, newest first. --}}
@extends($layout)

@section('outside-app')
    @include('marks-releases._styles')
@endsection

@section('content')
<div class="mr" data-testid="marks-releases">
    <h1>Marks releases</h1>
    <p class="mr-sub">Send an exam's marks to parents on WhatsApp.</p>
    <div class="mr-actions" style="margin:0 0 16px">
        <a class="mr-btn" href="{{ route($area.'.marks.releases.create') }}" data-testid="release-marks-new">Release marks</a>
    </div>
    <div class="mr-card mr-scroll">
        @if($list->isEmpty())
            <p style="margin:0">No releases yet.</p>
        @else
            <table class="mr-table">
                <thead><tr><th>Exam</th><th>Class</th><th>Status</th><th>Sent</th><th>Skipped</th><th></th></tr></thead>
                <tbody>
                @foreach($list as $r)
                    <tr>
                        <td>{{ $r->exam?->examType?->name ?? 'Exam' }}{{ $r->exam?->academicTerm?->name ? ' · '.$r->exam->academicTerm->name : '' }}</td>
                        <td>{{ $r->scope === 'school' ? 'All classes' : trim(($r->standardLink?->section?->name ?? '').' '.($r->standardLink?->stream ?? '')) }}</td>
                        <td>{{ ucfirst($r->status) }}</td>
                        <td>{{ $r->result['sent'] ?? '–' }}</td>
                        <td>{{ $r->result['skipped'] ?? '–' }}</td>
                        <td><a href="{{ route($area.'.marks.releases.show', $r->id) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
