{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" data-testid="students-roster">
    <div class="ds-page-head" data-testid="students-page-head">
        <div>
            <h1 class="ds-page-head-title">Students</h1>
            <p class="ds-page-head-sub" data-testid="students-page-sub">{{ $rosterSubtitle }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ url('/admin/import') }}" class="ds-btn ds-btn-ghost text-sm">Import list</a>
            <a href="{{ url('/admin/student/add/') }}" class="ds-btn ds-btn-primary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Add student
            </a>
        </div>
    </div>

    @include('partials.message')

    <div data-testid="students-filter-card">
    <x-card padding="sm" class="mb-4">
        <form action="{{ url('/admin/students') }}" method="GET">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[220px] flex-1">
                    <label class="ds-label" for="students-search">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input id="students-search" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by student name" class="ds-form-input pl-10">
                    </div>
                </div>

                <div class="w-[180px]">
                    <label class="ds-label" for="students-class">Class</label>
                    <select id="students-class" name="standard" class="ds-form-select">
                        <option value="">All classes</option>
                        @foreach($standardLinks as $link)
                            <option value="{{ $link->id }}" {{ (string) $link->id === (string) ($standardFilter ?? '') ? 'selected' : '' }}>
                                {{ $link->StandardSection }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-[116px]">
                    <label class="ds-label" for="students-stream">Stream</label>
                    <select id="students-stream" name="stream" class="ds-form-select">
                        <option value="">All Streams</option>
                        @foreach(['EAST', 'WEST', 'A', 'B'] as $stream)
                            <option value="{{ $stream }}" {{ ($streamFilter ?? '') === $stream ? 'selected' : '' }}>
                                {{ $stream }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-[128px]">
                    <label class="ds-label" for="students-status">Status</label>
                    <select id="students-status" name="status" class="ds-form-select">
                        <option value="">All</option>
                        <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pb-px">
                    <button type="submit" class="ds-btn ds-btn-primary text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                    @if($search || $standardFilter || $streamFilter || $statusFilter)
                        <a href="{{ url('/admin/students') }}" class="ds-btn ds-btn-ghost text-sm">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </x-card>
    </div>

    <div data-testid="students-ledger-card">
    <x-card padding="none">
        @if($students->isEmpty())
            <div class="ds-table-empty ds-empty-state" data-testid="students-empty">
                <p class="ds-empty-state-title">
                    @if($search)
                        No students match “{{ $search }}”
                    @else
                        No students found
                    @endif
                </p>
                <p class="ds-empty-state-desc">
                    @if($search || $standardFilter || $streamFilter || $statusFilter)
                        Check the spelling, or clear the class filter to search the whole school.
                    @else
                        Add a student or import a list to get started.
                    @endif
                </p>
                @if($search || $standardFilter || $streamFilter || $statusFilter)
                    <a href="{{ url('/admin/students') }}" class="text-blue-600 text-sm mt-3 inline-block hover:underline">Clear all filters</a>
                @endif
            </div>
        @else
            <div data-testid="students-ledger">
            <x-table
                :headers="['Student name', 'Class', 'Stream', 'Gender', 'Status']"
                sortable
            >
                @foreach($students as $student)
                    @php
                    $gender = $student->userprofile ? ($student->userprofile->gender ?? null) : null;
                    $genderDisplay = $gender ? ucfirst($gender) : 'N/A';
                    $stream = $student->standardLink?->stream ?? '—';
                @endphp
                    <tr>
<td data-label="Student name">
                            <a href="{{ url('/admin/student/show/' . $student->name) }}" class="dt-name-link">
                                {{ $student->displayName }}
                            </a>
                            @if(Gate::allows('member-edit', $student))
                                <a href="{{ url('/admin/student/edit/' . $student->name) }}" class="dt-edit-link text-xs text-blue-600 ml-2">Edit</a>
                            @endif
                        </td>
                        <td data-label="Class">
                            {{ $student->class_name ?? '—' }}
                        </td>
                        <td data-label="Stream">
                            {{ $stream }}
                        </td>
                        <td data-label="Gender">
                            <span class="text-sm font-medium">{{ $genderDisplay }}</span>
                        </td>
                        <td class="dt-cell-badge" data-label="Status">
                            @if($student->status === 'active')
                                <span class="dt-badge dt-badge-active">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                                    Active
                                </span>
                            @elseif($student->status === 'inactive')
                                <span class="dt-badge dt-badge-inactive">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/></svg>
                                    Left school
                                </span>
                            @else
                                <span class="dt-badge dt-badge-inactive">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/></svg>
                                    {{ ucfirst($student->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            </div>

            <div class="dt-pagination px-4" data-testid="students-pagination">
                <span class="dt-pagination-info">
                    Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ number_format($students->total()) }} students
                </span>
                <div class="dt-pagination-pages">
                    {{ $students->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </x-card>
    </div>
</div>
<script>
(function () {
    var selectAll = document.getElementById('select-all');
    if (!selectAll) return;
    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.students-row-check').forEach(function (box) {
            box.checked = selectAll.checked;
        });
    });
})();
</script>
@endsection
