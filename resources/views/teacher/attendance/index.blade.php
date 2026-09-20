{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Attendance',
        'subtitle' => 'Review attendance recorded by you for a class, stream, and date.',
    ])

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm" style="color: var(--d-muted);">{{ $records->count() }} record(s) on {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</p>
        <a href="{{ url('/teacher/attendance/add') }}" class="ds-btn ds-btn-primary ds-btn-md inline-flex items-center gap-2">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Record attendance
        </a>
    </div>

    <form method="GET" action="{{ route('teacher.attendance.index') }}" class="ds-card ds-card-padding-default mt-6 grid gap-4 md:grid-cols-4">
        <label class="block">
            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Class</span>
            <select name="section_id" class="ds-form-input ds-form-select w-full">
                <option value="">All classes</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" @selected($selectedSection === $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Stream</span>
            <select name="stream" class="ds-form-input ds-form-select w-full">
                <option value="">All streams</option>
                @foreach($streams as $stream)
                    <option value="{{ $stream }}" @selected($selectedStream === $stream)>{{ $stream }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Date</span>
            <input type="date" name="date" value="{{ $selectedDate }}" class="ds-form-input w-full">
        </label>
        <div class="flex items-end gap-2">
            <button type="submit" class="ds-btn ds-btn-primary ds-btn-md">Apply</button>
            <a href="{{ route('teacher.attendance.index') }}" class="ds-btn ds-btn-ghost ds-btn-md">Reset</a>
        </div>
    </form>

    <div class="ds-card ds-card-padding-default mt-6 overflow-hidden">
        @if($records->isEmpty())
            <div class="py-14 text-center">
                <p class="text-lg font-semibold" style="color: var(--d-text);">No attendance recorded</p>
                <p class="mt-2 text-sm" style="color: var(--d-muted);">Try another date or record attendance for a class.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left">
                    <thead class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr><th class="px-3 py-3">Student ID</th><th class="px-3 py-3">Name</th><th class="px-3 py-3">Class</th><th class="px-3 py-3">Stream</th><th class="px-3 py-3">Session</th><th class="px-3 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($records as $record)
                            <tr class="hover:bg-emerald-50/40">
                                <td class="px-3 py-4 text-sm font-semibold text-slate-700">{{ $record->user?->studentAcademicLatest?->klassapp_student_id ?? '—' }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-slate-900">{{ $record->user?->name ?? 'Unknown student' }}</td>
                                <td class="px-3 py-4 text-sm text-slate-600">{{ $record->standardLink?->section?->name ?? '—' }}</td>
                                <td class="px-3 py-4 text-sm text-slate-600">{{ $record->standardLink?->section?->stream ?: '—' }}</td>
                                <td class="px-3 py-4 text-sm capitalize text-slate-600">{{ $record->session }}</td>
                                <td class="px-3 py-4"><span class="ds-badge ds-badge-sm {{ (int) $record->status === 1 ? 'ds-badge-success' : 'ds-badge-danger' }}">{{ (int) $record->status === 1 ? 'Present' : 'Absent' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection