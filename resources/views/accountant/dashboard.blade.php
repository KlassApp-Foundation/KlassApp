{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--accountant">
        <div class="dashboard-heading">
            <div>
                <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Accountant</h1>
                <p class="dashboard-subtitle" style="margin-top: 4px;">Review fee structures, pending tasks, and school finance at a glance.</p>
            </div>
        </div>
        @include('partials.message')

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['feeCategoryCount'] }}</div>
                <div class="dashboard-kpi-label">Fee Categories</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="dashboard-kpi-value">${{ number_format($dashboard['totalFeesAmount'], 0) }}</div>
                <div class="dashboard-kpi-label">Total Fees ($)</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(15,23,42,0.08); color: #0F172A;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['totalStudents'] }}</div>
                <div class="dashboard-kpi-label">Total Students</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(217,119,6,0.10); color: #D97706;">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['pendingTasks'] }}</div>
                <div class="dashboard-kpi-label">Pending Tasks</div>
            </div>
        </div>

        {{-- Lower panels --}}
        <div class="flex flex-wrap my-2">
            {{-- Fee Categories --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Fee Categories</h1>
                    <div class="notice-box">
                        @if(count($dashboard['feeCategories']) > 0)
                            @foreach($dashboard['feeCategories'] as $fee)
                                <div class="notice-box-list py-3 border-b last:border-b-0">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $fee->name }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                @if($fee->standard)<span class="text-brand-blue">{{ $fee->standard->name }}</span>@endif
                                                @if($fee->section) · <span>{{ $fee->section->name }}</span>@endif
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-900">${{ number_format($fee->amount, 2) }}</p>
                                            <p class="text-xs text-gray-500">{{ $fee->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="py-6 text-center text-gray-400 text-sm">No fee categories created yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upcoming Events --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Upcoming Events</h1>
                    <div class="notice-box">
                        @if(count($dashboard['events']) > 0)
                            @foreach($dashboard['events'] as $events)
                                <div class="notice-box-list py-3 border-b last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $events->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($events->description, 60) }}</p>
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
                        @if(count($dashboard['noticeboard']) > 0)
                            @foreach($dashboard['noticeboard'] as $noticeboard)
                                <div class="notice-box-list py-3 border-b last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $noticeboard->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit(strip_tags($noticeboard->description), 60) }}</p>
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
        </div>
    </div>
@endsection
