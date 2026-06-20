{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--student">
        <div class="dashboard-heading">
            <div>
                <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Student</h1>
                <p class="dashboard-subtitle" style="margin-top: 4px;">Track attendance, upcoming events, notices, and class activity in one view.</p>
            </div>
        </div>
        @include('partials.message')

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['presentPercentage'] ?? 0 }}%</div>
                <div class="dashboard-kpi-label">Attendance Rate</div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $dashboard['presentDay'] ?? 0 }} present · {{ $dashboard['absentDay'] ?? 0 }} absent
                </div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['presentDay'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Days Present</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(217,119,6,0.10); color: #D97706;">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="dashboard-kpi-value">{{ $dashboard['upcomingeventCount'] ?? 0 }}</div>
                <div class="dashboard-kpi-label">Upcoming Events</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(15,23,42,0.08); color: #0F172A;">
                    <i class="fa-solid fa-marks"></i>
                </div>
                <div class="dashboard-kpi-value">{{ isset($dashboard['marks']) ? count($dashboard['marks']) : 0 }}</div>
                <div class="dashboard-kpi-label">Recent Marks</div>
            </div>
        </div>

        {{-- Lower panels --}}
        <div class="flex flex-wrap my-2">
            {{-- Recent Marks --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Recent Marks</h1>
                    <div class="notice-box">
                        @if(isset($dashboard['marks']) && count($dashboard['marks']) > 0)
                            @foreach($dashboard['marks'] as $mark)
                                <div class="notice-box-list py-3 border-b last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $mark->subject->name ?? 'Subject' }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ $mark->exam->name ?? 'Exam' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold {{ $mark->obtained_marks >= 50 ? 'text-green-600' : 'text-red-500' }}">
                                                {{ $mark->obtained_marks }}/{{ $mark->total_marks ?? 100 }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="py-6 text-center text-gray-400 text-sm">No marks recorded yet.</div>
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
                            <div class="py-6 text-center text-gray-400 text-sm">No notices for you yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Attendance Summary --}}
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-panel-card">
                    <h1 class="dashboard-panel-title">Attendance Summary</h1>
                    <div class="py-4 px-2">
                        @php
                            $present = $dashboard['presentDay'] ?? 0;
                            $absent = $dashboard['absentDay'] ?? 0;
                            $total = $present + $absent;
                            $pct = $total > 0 ? round(($present / $total) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center text-2xl font-bold" style="font-family: 'Sora', sans-serif; background: rgba(34,197,94,0.10); color: #22C55E;">
                                {{ $pct }}%
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Overall Attendance</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $present }} days present · {{ $absent }} days absent</div>
                            </div>
                        </div>
                        {{-- Attendance bar --}}
                        <div style="height: 8px; background: #E2E8F0; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $pct }}%; background: #22C55E; border-radius: 4px; transition: width 0.5s;"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>0%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
