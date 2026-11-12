{{-- SPDX-License-Identifier: MIT --}}
{{-- Marks releases: the list, newest first. --}}
@extends($layout)

@section('outside-app')
    @include('marks-releases._styles')
@endsection

@section('content')
@include('partials.ka-screen')
<div class="mr" data-testid="marks-releases">
    <h1>Marks Release</h1>
    <p class="mr-sub">Send an exam's marks to parents on WhatsApp.</p>
    <div class="mr-actions" style="margin:0 0 16px">
        <a class="mr-btn" href="{{ route($area.'.marks.releases.create') }}" data-testid="release-marks-new">Marks Release</a>
    </div>
    <div class="mr-card">
        @if($list->isEmpty())
            <div class="ka-empty" data-testid="releases-empty"><h2 class="ka-h2">No releases yet</h2><p class="ka-sub" style="margin:0">When you release a sitting, it is listed here.</p></div>
        @else
            <div class="ka-hide-sm mr-scroll">
            <table class="mr-table">
                <thead><tr><th>Exam</th><th>Class</th><th>Status</th><th>Sent</th><th>Skipped</th><th>Reason</th><th></th></tr></thead>
                <tbody>
                @foreach($list as $r)
                    <tr>
                        <td>{{ $r->exam?->examType?->name ?? 'Exam' }}{{ $r->exam?->academicTerm?->name ? ' · '.$r->exam->academicTerm->name : '' }}</td>
                        <td>{{ $r->scope === 'school' ? 'All classes' : trim(($r->standardLink?->section?->name ?? '').' '.($r->standardLink?->stream ?? '')) }}</td>
                        <td>{{ ucfirst($r->status) }}</td>
                        <td>{{ $r->result['sent'] ?? '–' }}</td>
                        <td>{{ $r->result['skipped'] ?? '–' }}</td>
                        <td>@if(filled($r->release_reason))<span data-testid="release-history-reason">{{ $r->release_reason }}</span>@else – @endif</td>
                        <td><a href="{{ route($area.'.marks.releases.show', $r->id) }}" style="min-height:44px;display:inline-flex;align-items:center">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div class="ka-phone-cards" data-testid="release-history-cards">
                @foreach($list as $r)
                    <article class="ka-queue-card">
                        <div style="font-weight:700">{{ $r->exam?->examType?->name ?? 'Exam' }}{{ $r->exam?->academicTerm?->name ? ' · '.$r->exam->academicTerm->name : '' }}</div>
                        <div style="font-size:.82rem;color:#475569">{{ $r->scope === 'school' ? 'All classes' : trim(($r->standardLink?->section?->name ?? '').' '.($r->standardLink?->stream ?? '')) }} · {{ ucfirst($r->status) }}</div>
                        @if($r->status === \App\Models\MarksRelease::STATUS_RELEASED && $r->released_at)
                            @include('partials.ka-released-lock', ['lock' => [
                                'at' => $r->released_at,
                                'timezone' => $r->timezone ?: 'Africa/Kampala',
                                'name' => $releaserNames[(int) $r->created_by] ?? 'Unknown',
                            ]])
                        @endif
                        @if(filled($r->release_reason))
                            <div data-testid="release-history-reason">{{ $r->release_reason }}</div>
                        @endif
                        <a class="ds-btn ds-btn-outline" style="min-height:44px" href="{{ route($area.'.marks.releases.show', $r->id) }}">View</a>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
