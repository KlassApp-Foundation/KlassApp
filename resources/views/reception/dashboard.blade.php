{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--reception">
        <div class="dashboard-heading">
            <div>
                <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Reception</h1>
                <p class="dashboard-subtitle" style="margin-top: 4px;">Manage front-desk operations, events, notices, and day-to-day tasks with clarity.</p>
            </div>
        </div>
        @include('partials.message')

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['studentCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Total Students</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['teacherCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Teachers</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(217,119,6,0.10); color: #D97706;">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['eventCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Upcoming Events</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(15,23,42,0.08); color: #0F172A;">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div class="dashboard-kpi-value">{{ count($dashboard['noticeboard'] ?? []) }}</div>
                <div class="dashboard-kpi-label">Active Notices</div>
            </div>
        </div>

        {{-- Lower panels --}}
        <div class="flex flex-wrap my-2">
            {{-- Upcoming Events --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Upcoming Events</h1>
                    <div class="notice-box">
                        @if(isset($dashboard['events']) && count($dashboard['events']) > 0)
                            @foreach($dashboard['events'] as $events)
                                <div class="notice-box-list py-3 border-b last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $events->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($events->description ?? '', 60) }}</p>
                                        </div>
                                        <span class="text-xs whitespace-nowrap ml-2 px-2 py-1 rounded-full" style="background: #1E6FD9; color: white;">
                                            {{ date('d M', strtotime($events->start_date)) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="py-6 text-center text-gray-400 text-sm">No upcoming events.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Notice Board --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Notice Board</h1>
                    <div class="notice-box">
                        @if(isset($dashboard['noticeboard']) && count($dashboard['noticeboard']) > 0)
                            @foreach($dashboard['noticeboard'] as $noticeboard)
                                <div class="notice-box-list py-3 border-b last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $noticeboard->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit(strip_tags($noticeboard->description ?? ''), 60) }}</p>
                                        </div>
                                        <span class="text-xs text-gray-400 whitespace-nowrap ml-2">{{ $noticeboard->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="py-6 text-center text-gray-400 text-sm">No notices posted yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Quick Actions</h1>
                    <div class="flex flex-col gap-3 py-3">
                        <a href="{{ url('/reception/visitorlog') }}" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-brand-blue hover:shadow-sm transition" style="text-decoration: none; color: inherit;">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                                <i class="fa-solid fa-book"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Visitor Log</div>
                                <div class="text-xs text-gray-500">Record visitor entries</div>
                            </div>
                        </a>
                        <a href="{{ url('/reception/calllog') }}" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-brand-green hover:shadow-sm transition" style="text-decoration: none; color: inherit;">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Call Log</div>
                                <div class="text-xs text-gray-500">Log incoming calls</div>
                            </div>
                        </a>
                        <a href="{{ url('/reception/postalrecord') }}" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-brand-amber hover:shadow-sm transition" style="text-decoration: none; color: inherit;">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10); color: #D97706;">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Postal Records</div>
                                <div class="text-xs text-gray-500">Track incoming/outgoing mail</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
