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

    <!-- Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-4 text-left">Student Name</th>
                    {{-- <th class="px-6 py-4 text-center">Class</th> --}}
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-left">Reason</th>
                    <th class="px-6 py-4 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium flex items-center gap-2">
                            <span>{{ $att->user->userprofile->firstname ?? 'Unknown' }}</span>
                            <span>{{ $att->user->userprofile->lastname ?? 'Unknown' }}</span>
                        </td>
                        {{-- {{ dd($att->standardLink->section->name) }} --}}
                        {{-- <td class="px-6 py-4 text-center text-sm">
                            {{$att->standardLink->section->name}}
                        </td> --}}
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

</div>
@endsection