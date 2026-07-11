{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--student px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Student',
        'subtitle' => 'Track attendance, upcoming events, notices, and class activity in one view.',
    ])

    @include('partials.message')

    {{-- KPI Cards --}}
    <div class="dashboard-kpi-grid">
        <x-ds-kpi-card icon="calendar" value="{{ $dashboard['presentPercentage'] ?? 0 }}%" label="Attendance Rate" color="green" />
        <x-ds-kpi-card icon="check" value="{{ $dashboard['presentDay'] ?? 0 }}" label="Days Present" color="blue" />
        <x-ds-kpi-card icon="calendar" value="{{ $dashboard['upcomingeventCount'] ?? 0 }}" label="Upcoming Events" color="amber" />
        <x-ds-kpi-card icon="book" value="{{ isset($dashboard['marks']) ? count($dashboard['marks']) : 0 }}" label="Recent Marks" color="purple" />
    </div>

    {{-- Main grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Recent Marks --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    Recent Marks
                </h2>
            </div>
            <div class="p-5">
                @if(isset($dashboard['marks']) && count($dashboard['marks']) > 0)
                    <div class="space-y-3">
                        @foreach($dashboard['marks'] as $mark)
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--d-text);">{{ $mark->subject->name ?? '—' }}</p>
                                    <p class="text-xs" style="color: var(--d-muted);">{{ $mark->exam->name ?? '' }}</p>
                                </div>
                                <span class="ds-badge ds-badge-sm {{ ($mark->marks ?? 0) >= 50 ? 'ds-badge-active' : 'ds-badge-warning' }}">
                                    {{ $mark->marks ?? 0 }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No marks recorded yet.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Upcoming Events --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
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
                                    <p class="text-xs mt-0.5" style="color: var(--d-muted);">{{ date('d M Y', strtotime($event->start_date)) }}</p>
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
    </div>

    {{-- Attendance Summary --}}
    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Attendance Summary
            </h2>
        </div>
        <div class="p-5">
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-2xl font-bold" style="font-family: Sora, sans-serif; color: var(--d-green);">{{ $dashboard['presentDay'] ?? 0 }}</p>
                    <p class="text-xs" style="color: var(--d-muted);">Present</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold" style="font-family: Sora, sans-serif; color: var(--d-amber);">{{ $dashboard['absentDay'] ?? 0 }}</p>
                    <p class="text-xs" style="color: var(--d-muted);">Absent</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold" style="font-family: Sora, sans-serif; color: var(--d-blue);">{{ $dashboard['presentPercentage'] ?? 0 }}%</p>
                    <p class="text-xs" style="color: var(--d-muted);">Attendance Rate</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
