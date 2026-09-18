{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Teachers',
        'subtitle' => 'A clear view of your teaching team and staff accounts.',
        'actions' => '<span class="ds-kpi-card !px-3 !py-2"><span class="ds-kpi-value !text-lg">' . $count . '</span><span class="ds-kpi-label">Total staff</span></span>'
    ])

    @include('partials.message')

    <x-card padding="sm" class="mt-4 mb-4">
        <form action="{{ url('/admin/teachers') }}" method="GET">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[220px] flex-1">
                    <label class="ds-label" for="teachers-search">Search staff</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                        <input id="teachers-search" type="search" name="search" value="{{ $search ?? '' }}" placeholder="Name, email or phone" class="ds-form-input pl-10">
                    </div>
                </div>
                <div class="w-[112px]">
                    <label class="ds-label" for="teachers-alphabet">Starts with</label>
                    <select id="teachers-alphabet" name="alphabet" class="ds-form-select">
                        <option value="">Any letter</option>
                        @foreach (range('A', 'Z') as $letter)
                            <option value="{{ $letter }}" {{ ($alphabet ?? '') === $letter ? 'selected' : '' }}>{{ $letter }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-[128px]">
                    <label class="ds-label" for="teachers-status">Status</label>
                    <select id="teachers-status" name="status" class="ds-form-select">
                        <option value="">All status</option>
                        <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 pb-px">
                    <button type="submit" class="ds-btn ds-btn-primary text-sm">Filter</button>
                    @if (($search ?? '') || ($alphabet ?? '') || ($statusFilter ?? ''))
                        <a href="{{ url('/admin/teachers') }}" class="ds-btn ds-btn-ghost text-sm">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </x-card>

    <x-card padding="none">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Staff directory</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ number_format($count) }} staff member{{ $count === 1 ? '' : 's' }} shown</p>
            </div>
            <a href="{{ url('/admin/import') }}" class="ds-btn ds-btn-ghost text-sm">Import list</a>
        </div>
        @if ($teachers->isEmpty())
            <div class="ds-table-empty ds-empty-state">
                <p class="ds-empty-state-title">No staff found</p>
                <p class="ds-empty-state-desc">Try a different search or clear the current filters.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Staff member</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($teachers as $teacher)
                            @php
                                $profile = $teacher->userprofile;
                                $teacherName = $teacher->name ?: trim((optional($profile)->firstname ?: '') . ' ' . (optional($profile)->lastname ?: ''));
                                $initial = strtoupper(substr((string) $teacherName, 0, 1));
                            @endphp
                            <tr class="group hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">
                                            @if (!empty($teacher->avatar))
                                                <img src="{{ asset('storage/avatars/' . $teacher->avatar) }}" alt="{{ $teacherName }}" class="h-9 w-9 rounded-full object-cover">
                                            @else
                                                {{ $initial }}
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ url('/admin/teacher/edit/' . $teacher->id) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $teacherName }}</a>
                                            <p class="truncate text-xs text-slate-500">{{ optional($profile)->designation ?: 'Teaching staff' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div>{{ $teacher->mobile_no ?: 'No phone number' }}</div>
                                    @if ($teacher->email || optional($profile)->email)
                                        <div class="text-xs text-slate-400">{{ $teacher->email ?: optional($profile)->email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($teacher->status === 'active')
                                        <span class="dt-badge dt-badge-active">Active</span>
                                    @else
                                        <span class="dt-badge dt-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ url('/admin/teacher/edit/' . $teacher->id) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <div class="mt-3 text-xs text-slate-500">Showing {{ $count }} of {{ $totalTeachers }} staff members</div>
</div>
@endsection