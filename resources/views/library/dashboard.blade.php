{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.library.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--library">
        <div class="dashboard-heading">
            <div>
                <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Library</h1>
                <p class="dashboard-subtitle" style="margin-top: 4px;">Monitor books, lending activity, categories, and updates from one organized library console.</p>
            </div>
        </div>
        @include('partials.message')

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                    <i class="fa-solid fa-book"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['bookCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Total Books</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                    <i class="fa-solid fa-hand-holding-hand"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['booklendingCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Active Lendings</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(217,119,6,0.10); color: #D97706;">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['cardHolderCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Library Cards</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(15,23,42,0.08); color: #0F172A;">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['categoryCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Categories</div>
            </div>
        </div>

        {{-- Lower panels --}}
        <div class="flex flex-wrap my-2">
            {{-- Overdue Books --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Overdue Books</h1>
                    <div class="notice-box">
                        @if(isset($dashboard['booklendings']) && count($dashboard['booklendings']) > 0)
                            @foreach($dashboard['booklendings'] as $lending)
                                <div class="notice-box-list py-3 border-b last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $lending->book->name ?? 'Unknown' }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Borrowed: {{ \Carbon\Carbon::parse($lending->created_at)->format('d M Y') }}
                                                @if($lending->member)
                                                    · {{ $lending->member->name ?? '' }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="text-xs whitespace-nowrap ml-2 px-2 py-1 rounded-full" style="background: #FEF2F2; color: #EF4444;">
                                            Overdue
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="py-6 text-center text-gray-400 text-sm">No overdue books. All caught up!</div>
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
        </div>
    </div>
@endsection
