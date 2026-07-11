{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Teacher',
        'subtitle' => 'Stay on top of notice updates, subjects, exams, and day-to-day class rhythm.',
    ])

    @include('partials.message')

    {{-- Teaching KPIs --}}
    <div class="dashboard-kpi-grid">
        <x-ds-kpi-card icon="users" value="{{ $dashboard['myStudents'] }}" label="My Students" color="green" link="{{ url('/teacher/students') }}" />
        <x-ds-kpi-card icon="classes" value="{{ $dashboard['myClasses'] }}" label="My Classes" color="blue" />
        <x-ds-kpi-card icon="exam" value="{{ count($dashboard['upcomingExam']) }}" label="Upcoming Exams" color="amber" link="{{ url('/teacher/exams') }}" />
        <x-ds-kpi-card icon="whatsapp" value="{{ $dashboard['whatsapp']['totalLinked'] }}" label="WhatsApp Linked" color="green" />
    </div>

    @if(($dashboard['myStudents'] ?? 0) === 0)
    <div class="ds-card ds-card-padding-default mt-4" style="background: #FFF7ED; border-color: #FED7AA;">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 shrink-0" style="color: #D97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.072 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <p class="text-sm" style="color: #9A3412;">You haven't been assigned any classes yet. Contact your school admin to set up your class assignments before students and schedules appear here.</p>
        </div>
    </div>
    @endif

    {{-- Main grid: Timetable + Noticeboard --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Today's Timetable --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Today's Schedule
                </h2>
            </div>
            <div class="p-5">
                @if(count($dashboard['timetable']) > 0)
                    @foreach($dashboard['timetable'] as $class => $periods)
                        <div class="mb-4 last:mb-0">
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
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No classes scheduled for today.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Notice Board --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Notice Board
                </h2>
            </div>
            <div class="p-5">
                @if(count($dashboard['noticeboard']) > 0)
                    <div class="space-y-4">
                        @foreach($dashboard['noticeboard'] as $notice)
                            <div class="pb-4 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="ds-badge ds-badge-sm ds-badge-active">{{ $notice->title }}</span>
                                    <span class="ds-badge ds-badge-sm ds-badge-info">{{ date('d M Y', strtotime($notice->publish_date)) }}</span>
                                    <span class="ds-badge ds-badge-sm ds-badge-warning">{{ ucwords($notice->type) }}</span>
                                </div>
                                <p class="text-sm" style="color: var(--d-text);">{!! $notice->description !!}</p>
                                <p class="text-xs mt-1" style="color: var(--d-muted);">
                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ $notice->createdBy->name ?? 'System' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No notices published yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Recent Activity
            </h2>
        </div>
        <div class="p-5">
            @if(count($dashboard['activitylog']) > 0)
                <div class="space-y-3">
                    @foreach($dashboard['activitylog'] as $log)
                        <div class="flex items-start gap-3 pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5" style="background: rgba(30,111,217,0.10);">
                                <svg class="w-4 h-4" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm" style="color: var(--d-text);">{{ $log->activity ?? $log->description ?? '—' }}</p>
                                <p class="text-xs mt-0.5" style="color: var(--d-muted);">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm" style="color: var(--d-muted);">No recent activity.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
