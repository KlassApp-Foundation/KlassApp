{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
@include('layouts.partials.page-header', ['title' => 'Timetable', 'subtitle' => 'Manage weekly lesson schedules. Conflicts are detected automatically on save.'])
@include('partials.message')

<div class="flex flex-wrap gap-4 items-center mb-6">
    <form method="GET" action="{{ route('admin.timetable.index') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="ds-label">Class</label>
            <select name="section_id" class="ds-input" onchange="this.form.submit()">
                <option value="">Select class...</option>
                @foreach(($sections ?? collect()) as $s)
                    <option value="{{ $s->id }}" {{ $s->id == ($sectionId ?? null) ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('admin.timetable.create', ['section_id' => $sectionId ?? null]) }}" class="ds-btn ds-btn-primary">+ Add Slot</a>
    </form>
</div>

@if(($slots ?? collect())->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="ds-table w-full">
            <thead>
                <tr>
                    <th class="ds-th">Day</th>
                    <th class="ds-th">Time</th>
                    <th class="ds-th">Subject</th>
                    <th class="ds-th">Teacher</th>
                    <th class="ds-th">Room</th>
                    <th class="ds-th">Term</th>
                    <th class="ds-th"></th>
                </tr>
            </thead>
            <tbody>
                @foreach(($slots ?? collect()) as $day => $daySlots)
                    @foreach($daySlots as $slot)
                        <tr class="ds-tr">
                            <td class="ds-td font-medium">{{ ($dayNames ?? [])[$day] ?? '?' }}</td>
                            <td class="ds-td">{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</td>
                            <td class="ds-td">{{ $slot->subject?->name ?? '—' }}</td>
                            <td class="ds-td">{{ $slot->teacher?->name ?? '—' }}</td>
                            <td class="ds-td">{{ $slot->room ?? '—' }}</td>
                            <td class="ds-td">{{ $slot->term?->name ?? '—' }}</td>
                            <td class="ds-td">
                                <a href="{{ route('admin.timetable.edit', $slot) }}" class="ds-btn ds-btn-sm">Edit</a>
                                <form method="POST" action="{{ route('admin.timetable.destroy', $slot) }}" class="inline" onsubmit="return confirm('Remove this slot?')">
                                    @csrf @method('DELETE')
                                    <button class="ds-btn ds-btn-sm ds-btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($sectionId ?? null)
    <div class="ds-empty-state"><p>No timetable slots for this class yet.</p>
        <a href="{{ route('admin.timetable.create', ['section_id' => $sectionId]) }}" class="ds-btn ds-btn-primary mt-3">Add the first slot</a></div>
@else
    <div class="ds-empty-state"><p>Select a class above to view its timetable.</p></div>
@endif
</div>
@endsection
