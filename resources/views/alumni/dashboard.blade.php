{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.alumni.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--alumni px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Alumni Dashboard',
        'subtitle' => 'View your academic records, past marks, and connect with fellow alumni.',
    ])

    @include('partials.message')

    {{-- Stats KPI Cards --}}
    <div class="dashboard-kpi-grid">
        <div class="ds-kpi-card">
            <div class="ds-kpi-icon-wrap" style="background: rgba(30,111,217,0.10);">
                <svg class="w-6 h-6" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $stats['total_marks'] }}</p>
            <p class="ds-kpi-label">Exam Records</p>
        </div>
        <div class="ds-kpi-card">
            <div class="ds-kpi-icon-wrap" style="background: rgba(34,197,94,0.10);">
                <svg class="w-6 h-6" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $stats['subjects'] }}</p>
            <p class="ds-kpi-label">Subjects Taken</p>
        </div>
        <div class="ds-kpi-card">
            <div class="ds-kpi-icon-wrap" style="background: rgba(217,119,6,0.10);">
                <svg class="w-6 h-6" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $stats['grad_year'] }}</p>
            <p class="ds-kpi-label">Graduation Year</p>
        </div>
        <a href="{{ url('/alumni/directory') }}" class="ds-kpi-card" style="cursor: pointer;">
            <div class="ds-kpi-icon-wrap" style="background: rgba(139,92,246,0.10);">
                <svg class="w-6 h-6" style="color: #8B5CF6;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="ds-kpi-value">{{ $stats['alumni_count'] }}</p>
            <p class="ds-kpi-label">Alumni Network</p>
        </a>
    </div>

    {{-- Main grid: Recent Marks + Directory Preview --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Recent Exam Marks --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    Recent Exam Records
                </h2>
                @if($marks->count() > 0)
                    <a href="{{ url('/alumni/marks') }}" class="text-sm" style="color: var(--d-blue);">View all →</a>
                @endif
            </div>
            <div class="p-5">
                @if($marks->count() > 0)
                    <div class="space-y-3">
                        @foreach($marks->take(10) as $mark)
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium" style="color: var(--d-text);">
                                        {{ $mark->subject->name ?? '—' }}
                                        @if($mark->exam)
                                            <span class="text-xs ml-1" style="color: var(--d-muted);">({{ $mark->exam->name ?? '' }})</span>
                                        @endif
                                    </p>
                                </div>
                                <span class="ds-badge ds-badge-sm {{ ($mark->marks ?? 0) >= 50 ? 'ds-badge-active' : 'ds-badge-warning' }}">
                                    {{ $mark->marks ?? 0 }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 text-center">
                        <a href="{{ url('/alumni/report-card/download') }}" class="ds-btn ds-btn-sm ds-btn-primary">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Download Report Card (PDF)
                        </a>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No exam records found.</p>
                        <p class="text-xs mt-1" style="color: var(--d-muted);">Your academic records will appear here once available.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Alumni Network Preview --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Alumni Network
                </h2>
                @if($alumni->count() > 0)
                    <a href="{{ url('/alumni/directory') }}" class="text-sm" style="color: var(--d-blue);">View all →</a>
                @endif
            </div>
            <div class="p-5">
                @if($alumni->count() > 0)
                    <div class="space-y-3">
                        @foreach($alumni->take(8) as $fellow)
                            <div class="flex items-center gap-3 pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background: rgba(30,111,217,0.10);">
                                    <span class="text-sm font-semibold" style="color: var(--d-blue);">
                                        {{ $fellow->userprofile ? strtoupper(substr($fellow->userprofile->firstname, 0, 1) . substr($fellow->userprofile->lastname ?? '', 0, 1)) : '?' }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--d-text);">{{ $fellow->name }}</p>
                                    @if($fellow->mobile_no)
                                        <p class="text-xs" style="color: var(--d-muted);">{{ $fellow->mobile_no }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No other alumni registered yet.</p>
                        <p class="text-xs mt-1" style="color: var(--d-muted);">The alumni directory will populate as more graduates join.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Actions
            </h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ url('/alumni/marks') }}" class="ds-btn ds-btn-outline text-sm justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    View Full Marks History
                </a>
                <a href="{{ url('/alumni/report-card/download') }}" class="ds-btn ds-btn-primary text-sm justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Report Card PDF
                </a>
                <a href="{{ url('/alumni/directory') }}" class="ds-btn ds-btn-outline text-sm justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Browse Alumni Directory
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
