{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Admin Dashboard',
        'subtitle' => 'Manage students, classes, fees, and day-to-day school operations.',
    ])

    @include('partials.message')

    {{-- KPI Cards --}}
    <div class="dashboard-kpi-grid">
        <a href="{{ url('/admin/students') }}" class="ds-kpi-card">
            <div class="ds-kpi-icon-wrap" style="background: rgba(30,111,217,0.10);">
                <svg class="w-6 h-6" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $studentCount }}</p>
            <p class="ds-kpi-label">Students</p>
        </a>
        <a href="{{ url('/admin/teachers') }}" class="ds-kpi-card">
            <div class="ds-kpi-icon-wrap" style="background: rgba(34,197,94,0.10);">
                <svg class="w-6 h-6" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $teacherCount }}</p>
            <p class="ds-kpi-label">Teachers</p>
        </a>
        <a href="{{ url('/admin/classes') }}" class="ds-kpi-card">
            <div class="ds-kpi-icon-wrap" style="background: rgba(217,119,6,0.10);">
                <svg class="w-6 h-6" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $classCount }}</p>
            <p class="ds-kpi-label">Classes</p>
        </a>
        <a href="{{ url('/admin/fees/payments') }}" class="ds-kpi-card">
            <div class="ds-kpi-icon-wrap" style="background: rgba(139,92,246,0.10);">
                <svg class="w-6 h-6" style="color: #8B5CF6;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $feeCount }}</p>
            <p class="ds-kpi-label">Fee Categories</p>
        </a>
    </div>

    {{-- Quick Module Grid --}}
    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Quick Access
            </h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <a href="{{ url('/admin/students') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center transition-all" style="border: 1px solid var(--d-border); background: var(--d-surface);" hover="border-color: var(--d-blue);">
                    <svg class="w-6 h-6" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Students</span>
                </a>
                <a href="{{ url('/admin/parents') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center transition-all" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Parents</span>
                </a>
                <a href="{{ url('/admin/classes') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Classes</span>
                </a>
                <a href="{{ url('/admin/subjects') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-purple);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Subjects</span>
                </a>
                <a href="{{ url('/admin/timetable') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Timetable</span>
                </a>
                <a href="{{ url('/admin/attendance') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Attendance</span>
                </a>
                <a href="{{ url('/admin/exams') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Exams</span>
                </a>
                <a href="{{ url('/admin/fees/payments') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: #8B5CF6;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Fees</span>
                </a>
                <a href="{{ url('/admin/approvals') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Approvals</span>
                </a>
                <a href="{{ url('/admin/reports') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Reports</span>
                </a>
                <a href="{{ url('/admin/sentmessages') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Messaging</span>
                </a>
                <a href="{{ url('/library/books/index') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Library</span>
                </a>
                <a href="{{ url('/admin/calendar') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Calendar</span>
                </a>
                <a href="{{ url('/admin/grades') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl text-center" style="border: 1px solid var(--d-border); background: var(--d-surface);">
                    <svg class="w-6 h-6" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    <span class="text-xs font-medium" style="color: var(--d-text);">Grading</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
