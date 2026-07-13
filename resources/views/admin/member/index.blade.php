{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Students (' . ($count ?? 0) . ')',
        'subtitle' => 'View, search and manage all students.',
        'actions' => '<a href="' . url('/admin/student/add/') . '" class="px-3 py-1.5 rounded text-xs text-white font-medium bg-green-600 hover:bg-green-700 flex items-center gap-1 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Add Student</a>'
    ])

    @include('partials.message')

    {{-- Search & Filter Bar --}}
    <div class="ds-card">
        <form action="{{ url('/admin/students') }}" method="GET">
            <div class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label class="ds-label">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name or ID..." class="ds-form-input pl-10">
                    </div>
                </div>

                {{-- Class Filter --}}
                <div class="w-full sm:w-48">
                    <label class="ds-label">Class</label>
                    <select name="standard" class="ds-form-select">
                        <option value="">All Classes</option>
                        @foreach($standardLinks as $link)
                            <option value="{{ $link->id }}" {{ (string)$link->id === (string)($standardFilter ?? '') ? 'selected' : '' }}>
                                {{ $link->StandardSection }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="w-full sm:w-40">
                    <label class="ds-label">Status</label>
                    <select name="status" class="ds-form-select">
                        <option value="">All</option>
                        <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-2 pb-px">
                    <button type="submit" class="ds-btn ds-btn-primary text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                    @if($search || $standardFilter || $statusFilter)
                        <a href="{{ url('/admin/students') }}" class="ds-btn ds-btn-ghost text-sm">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Students Table --}}
    @if($students->isEmpty())
        <div class="ds-table-empty">
            <div class="ds-empty-state-icon">👤</div>
            <p class="ds-empty-state-title">No students found</p>
            <p class="ds-empty-state-desc">Try adjusting your search or filter criteria.</p>
            @if($search || $standardFilter || $statusFilter)
                <a href="{{ url('/admin/students') }}" class="text-blue-600 text-sm mt-3 inline-block hover:underline">Clear all filters</a>
            @endif
        </div>
    @else
        <div class="mt-4">
            <x-table :headers="['#', 'Student Name', 'Class', 'Parent / Guardian', 'Status', 'Actions']" hover>
                @foreach($students as $student)
                    <tr>
                        <td class="text-gray-400 text-xs font-mono">{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                @php $profile = $student->userprofile; @endphp
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-xs shrink-0">
                                    {{ $profile ? strtoupper(substr($profile->firstname, 0, 1)) . strtoupper(substr($profile->lastname ?? '', 0, 1)) : '?' }}
                                </div>
                                <div>
                                    <a href="{{ url('/admin/student/edit/' . $student->name) }}" class="font-medium text-gray-800 hover:text-blue transition-colors">
                                        {{ $profile ? $profile->firstname . ' ' . $profile->lastname : $student->name }}
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($student->class_name)
                                <span class="dt-badge dt-badge-active" style="background:#EFF6FF;color:#1D4ED8;">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
                                    {{ $student->class_name }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $parentLink = $student->parents->first();
                                $parentUser = $parentLink && $parentLink->userParent ? $parentLink->userParent : null;
                                $parentProfile = $parentUser ? $parentUser->userprofile : null;
                                $parentName = $parentProfile ? $parentProfile->firstname . ' ' . $parentProfile->lastname : null;
                            @endphp
                            @if($parentName)
                                <span class="text-sm text-gray-700">{{ $parentName }}</span>
                            @else
                                <span class="text-gray-400 text-xs">No parent linked</span>
                            @endif
                        </td>
                        <td>
                            @if($student->status === 'active')
                                <span class="dt-badge dt-badge-active">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                    Active
                                </span>
                            @else
                                <span class="dt-badge dt-badge-inactive">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                    {{ ucfirst($student->status) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ url('/admin/student/edit/' . $student->name) }}"
                                   class="p-1.5 rounded text-gray-400 hover:text-blue hover:bg-blue-100 transition-colors"
                                   title="View Profile">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ url('/admin/student/edit/' . $student->name) }}"
                                   class="p-1.5 rounded text-gray-400 hover:text-orange-600 hover:bg-orange-100 transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ url('/admin/student/delete/' . $student->name) }}" method="POST" class="inline" onsubmit="return confirm('Delete this student?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded text-gray-400 hover:text-red hover:bg-red-100 transition-colors"
                                            title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
