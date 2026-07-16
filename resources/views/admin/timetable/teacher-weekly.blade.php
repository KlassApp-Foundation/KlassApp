{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
@include('layouts.partials.page-header', ['title' => 'My Weekly Timetable', 'subtitle' => 'Your assigned lessons for this term.'])

@if($slots->isEmpty())
    <div class="ds-empty-state"><p>You have no timetable slots assigned.</p></div>
@else
    <div class="overflow-x-auto">
        <table class="ds-table w-full">
            <thead>
                <tr>
                    <th class="ds-th">Day</th>
                    <th class="ds-th">Time</th>
                    <th class="ds-th">Subject</th>
                    <th class="ds-th">Class</th>
                    <th class="ds-th">Room</th>
                </tr>
            </thead>
            <tbody>
                @foreach($slots as $day => $daySlots)
                    @foreach($daySlots as $slot)
                        <tr class="ds-tr">
                            <td class="ds-td font-medium">{{ $dayNames[$day] ?? '?' }}</td>
                            <td class="ds-td">{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</td>
                            <td class="ds-td">{{ $slot->subject?->name ?? '—' }}</td>
                            <td class="ds-td">{{ $slot->section?->name ?? '—' }}</td>
                            <td class="ds-td">{{ $slot->room ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endif
</div>
@endsection
