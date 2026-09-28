{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', ['title' => 'My Timetable', 'subtitle' => 'Create, update, or remove the lessons assigned to you.'])
    @include('partials.message')

    <div class="mt-6 flex justify-end">
        <a href="{{ route('teacher.timetable.create') }}" class="ds-btn ds-btn-primary">+ Add Slot</a>
    </div>

    <div class="ds-card ds-card-padding-none mt-4 overflow-x-auto">
        <table class="ds-table w-full">
            <thead>
                <tr>
                    <th class="ds-th">Day</th>
                    <th class="ds-th">Time</th>
                    <th class="ds-th">Class</th>
                    <th class="ds-th">Subject</th>
                    <th class="ds-th">Room</th>
                    <th class="ds-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $day => $daySlots)
                    @foreach($daySlots as $slot)
                        <tr class="ds-tr">
                            <td class="ds-td font-medium">{{ ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day] ?? '—' }}</td>
                            <td class="ds-td">{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</td>
                            <td class="ds-td">{{ $slot->section?->name ?? '—' }}</td>
                            <td class="ds-td">{{ $slot->subject?->name ?? '—' }}</td>
                            <td class="ds-td">{{ $slot->room ?: '—' }}</td>
                            <td class="ds-td whitespace-nowrap">
                                <a href="{{ route('teacher.timetable.edit', $slot) }}" class="ds-btn ds-btn-sm">Edit</a>
                                <form method="POST" action="{{ route('teacher.timetable.destroy', $slot) }}" class="inline" onsubmit="return confirm('Remove this slot?')">
                                    @csrf @method('DELETE')
                                    <button class="ds-btn ds-btn-sm ds-btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="ds-td py-12 text-center">No timetable slots have been assigned yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection