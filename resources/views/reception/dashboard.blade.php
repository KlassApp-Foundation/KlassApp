{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--reception px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Reception',
        'subtitle' => 'Manage front-desk operations, events, notices, and day-to-day tasks with clarity.',
    ])

    @include('partials.message')

    {{-- KPI Cards --}}
    <div class="dashboard-kpi-grid">
        <x-ds-kpi-card icon="users" value="{{ $dashboard['studentCount'] ?? 0 }}" label="Total Students" color="blue" link="{{ url('/reception/students') }}" />
        <x-ds-kpi-card icon="users" value="{{ $dashboard['teacherCount'] ?? 0 }}" label="Teachers" color="green" />
        <x-ds-kpi-card icon="calendar" value="{{ $dashboard['eventCount'] ?? 0 }}" label="Upcoming Events" color="amber" link="{{ url('/reception/events') }}" />
        <x-ds-kpi-card icon="bell" value="{{ count($dashboard['noticeboard'] ?? []) }}" label="Active Notices" color="purple" />
    </div>

    {{-- Main grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Upcoming Events --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Upcoming Events
                </h2>
            </div>
            <div class="p-5">
                @if(isset($dashboard['events']) && count($dashboard['events']) > 0)
                    <div class="space-y-3">
                        @foreach($dashboard['events'] as $event)
                            <div class="flex items-start gap-3 pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background: rgba(30,111,217,0.10);">
                                    <svg class="w-5 h-5" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium" style="color: var(--d-text);">{{ $event->title }}</p>
                                    <p class="text-xs mt-0.5" style="color: var(--d-muted);">
                                        {{ date('d M Y', strtotime($event->start_date)) }}
                                        @if($event->start_date !== $event->end_date)
                                            — {{ date('d M Y', strtotime($event->end_date)) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No upcoming events.</p>
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
                @if(isset($dashboard['noticeboard']) && count($dashboard['noticeboard']) > 0)
                    <div class="space-y-4">
                        @foreach($dashboard['noticeboard'] as $notice)
                            <div class="pb-4 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="ds-badge ds-badge-sm ds-badge-active">{{ $notice->title }}</span>
                                    <span class="ds-badge ds-badge-sm ds-badge-info">{{ date('d M Y', strtotime($notice->publish_date)) }}</span>
                                    <span class="ds-badge ds-badge-sm ds-badge-warning">{{ ucwords($notice->type) }}</span>
                                </div>
                                <p class="text-sm" style="color: var(--d-text);">{!! $notice->description !!}</p>
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

    {{-- Recent Visitor Log --}}
    @if(isset($dashboard['visitorlog']) && count($dashboard['visitorlog']) > 0)
    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Recent Visitors
            </h2>
        </div>
        <div class="p-5">
            <div class="space-y-3">
                @foreach($dashboard['visitorlog'] as $visitor)
                    <div class="flex items-start gap-3 pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background: rgba(30,111,217,0.10);">
                            <svg class="w-4 h-4" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium" style="color: var(--d-text);">{{ $visitor->name ?? '—' }}</p>
                            <p class="text-xs" style="color: var(--d-muted);">
                                {{ $visitor->purpose ?? '' }} · {{ $visitor->created_at ? \Carbon\Carbon::parse($visitor->created_at)->diffForHumans() : '' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
