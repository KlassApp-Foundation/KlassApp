{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'My Timetable',
        'subtitle' => 'Today’s teaching schedule organised by class and period.',
    ])

    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Today’s Schedule
            </h2>
        </div>

        <div class="p-5">
            @if(isset($dashboard['timetable']) && count($dashboard['timetable']) > 0)
                @foreach($dashboard['timetable'] as $class => $periods)
                    <div class="mb-5 last:mb-0">
                        <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--d-muted);">{{ $class }}</h3>
                        <div class="space-y-2">
                            @foreach($periods as $period)
                                <div class="flex items-center justify-between p-3 rounded-lg" style="background: var(--d-surface); border: 1px solid var(--d-border);">
                                    <div class="flex items-center gap-3">
                                        <span class="ds-badge ds-badge-sm ds-badge-info">{{ $period['period'] ?? '—' }}</span>
                                        <span class="text-sm font-medium" style="color: var(--d-text);">{{ $period['subject'] ?? '' }}</span>
                                    </div>
                                    <span class="text-xs" style="color: var(--d-muted);">
                                        {{ isset($period['start_time']) ? date('h:i A', strtotime($period['start_time'])) : '—' }}
                                        –
                                        {{ isset($period['end_time']) ? date('h:i A', strtotime($period['end_time'])) : '—' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-sm" style="color: var(--d-muted);">No classes are scheduled for today yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
