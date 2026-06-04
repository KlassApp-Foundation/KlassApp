{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <h1 class="admin-h1 my-3 flex items-center">
           
            <span class="mx-3">Staff Attendance List</span>
        </h1>

        @include('partials.message')

        @include('admin.staff_attendance.filter')

        <div class="bg-white shadow rounded-lg overflow-hidden mt-6">
            <table class="w-full text-left">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-4">Staff</th>
                        <th class="px-6 py-4">Session</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Reason</th>
                        <th class="px-6 py-4">Remarks</th>
                        <th class="px-6 py-4">Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                {{ $attendance->user->userprofile->firstname ?? 'Unknown' }} {{ $attendance->user->userprofile->lastname ?? '' }}
                            </td>
                            <td class="px-6 py-4">{{ ucfirst($attendance->session) }}</td>
                            <td class="px-6 py-4">
                                @if($attendance->status == 1)
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Present</span>
                                @else
                                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Absent</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">{{ $attendance->absentReason->title ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $attendance->remarks ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $attendance->admin->userprofile->firstname ?? '' }} {{ $attendance->admin->userprofile->lastname ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No attendance records found for {{ $date }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
