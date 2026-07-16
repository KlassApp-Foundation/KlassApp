@extends('layouts.admin.layout')

@section('content')
<div class="px-4 md:px-6 py-4">

    <div class="ds-page-head">
        <div>
            <h1 class="ds-page-head-title">Student Attendance</h1>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="ds-card ds-card-padding-default ds-card-shadow-sm mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm mb-1">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="tw-form-control w-full">
        </div>

        <div>
            <label class="block text-sm mb-1">Class</label>
            <select name="section" class="tw-form-control w-full">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ $section == $class->id ? 'selected' : '' }}>
                        {{ $class->name ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Session</label>
            <select name="session" class="tw-form-control w-full">
                <option value="">All</option>
                <option value="forenoon" {{ $session == 'forenoon' ? 'selected' : '' }}>Forenoon</option>
                <option value="afternoon" {{ $session == 'afternoon' ? 'selected' : '' }}>Afternoon</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="btn btn-submit blue-bg text-white px-6 py-2">
                Filter
            </button>
        </div>
    </form>

    <!-- Table (desktop) -->
    <div class="ds-table-wrap hidden md:block">
        <table class="ds-table-ledger">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium flex items-center gap-2">
                            <span>{{ $att->user->userprofile->firstname ?? 'Unknown' }}</span>
                            <span>{{ $att->user->userprofile->lastname ?? 'Unknown' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($att->status == 1)
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Present</span>
                            @else
                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Absent</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $att->absentReason->title ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $att->remarks ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            No attendance records found for this filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Cards (mobile) -->
    <div class="block md:hidden space-y-3">
        @forelse($attendances as $att)
            <div class="ds-card ds-card-padding-default ds-card-shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-semibold text-gray-900">{{ $att->user->userprofile->firstname ?? 'Unknown' }} {{ $att->user->userprofile->lastname ?? 'Unknown' }}</span>
                    @if($att->status == 1)
                        <span class="dt-badge dt-badge-active text-xs">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Present
                        </span>
                    @else
                        <span class="dt-badge dt-badge-inactive text-xs">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Absent
                        </span>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">Reason:</span>
                        <span class="text-gray-800 ml-1">{{ $att->absentReason->title ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Remarks:</span>
                        <span class="text-gray-800 ml-1">{{ $att->remarks ?? '-' }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="ds-card ds-card-padding-default ds-card-shadow-sm text-center text-gray-500 py-8">
                No attendance records found for this filter.
            </div>
        @endforelse
    </div>

</div>
@endsection