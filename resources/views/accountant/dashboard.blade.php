{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--accountant px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Accountant',
        'subtitle' => 'Review fee structures, pending tasks, and school finance at a glance.',
    ])

    @include('partials.message')

    {{-- KPI Cards --}}
    <div class="dashboard-kpi-grid">
        <x-ds-kpi-card icon="dollar" value="{{ $dashboard['feeCategoryCount'] }}" label="Fee Categories" color="blue" />
        <x-ds-kpi-card icon="dollar" value="{{ number_format($dashboard['totalFeesAmount'], 0) }}" label="Total Fees (UGX)" color="green" />
        <x-ds-kpi-card icon="users" value="{{ $dashboard['totalStudents'] }}" label="Total Students" color="purple" />
        <x-ds-kpi-card icon="check" value="{{ $dashboard['pendingTasks'] }}" label="Pending Tasks" color="amber" link="{{ url('/accountant/tasks') }}" />
    </div>

    {{-- Main grid: Fee Categories + Events --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Fee Categories --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 14l6-6m-5.5.5h.01M14.5 9.5h.01M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Fee Categories
                </h2>
            </div>
            <div class="p-5">
                @if(count($dashboard['feeCategories']) > 0)
                    <div class="space-y-3">
                        @foreach($dashboard['feeCategories'] as $fee)
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--d-text);">{{ $fee->name }}</p>
                                    <p class="text-xs" style="color: var(--d-muted);">
                                        @if($fee->standard)
                                            {{ $fee->standard->name }}
                                        @endif
                                        @if($fee->section)
                                            — {{ $fee->section->name }}
                                        @endif
                                    </p>
                                </div>
                                <span class="text-sm font-semibold" style="font-family: Sora, sans-serif; color: var(--d-green);">UGX {{ number_format($fee->amount) }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if(count($dashboard['feeCategories']) >= 5)
                        <div class="mt-4 text-center">
                            <a href="{{ url('/accountant/fees/payments') }}" class="text-sm" style="color: var(--d-blue);">View all fees →</a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M9 14l6-6m-5.5.5h.01M14.5 9.5h.01M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No fee categories set up yet.</p>
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
                @if(count($dashboard['events']) > 0)
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
    </div>

    {{-- Notice Board --}}
    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
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
@endsection
