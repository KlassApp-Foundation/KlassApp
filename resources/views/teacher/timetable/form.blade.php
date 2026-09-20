{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', ['title' => isset($slot) ? 'Edit Timetable Slot' : 'Add Timetable Slot', 'subtitle' => 'Keep your weekly teaching schedule accurate.'])
    @if($errors->any())
        <div class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ isset($slot) ? route('teacher.timetable.update', $slot) : route('teacher.timetable.store') }}" class="mt-6 max-w-xl">
        @csrf
        @if(isset($slot)) @method('PUT') @endif
        <div class="ds-field">
            <label class="ds-label">Class *</label>
            <select name="section_id" class="ds-input" required>
                <option value="">Select class...</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ old('section_id', $slot->section_id ?? '') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ds-field">
            <label class="ds-label">Subject *</label>
            <select name="subject_id" class="ds-input" required>
                <option value="">Select subject...</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ old('subject_id', $slot->subject_id ?? '') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ds-field">
            <label class="ds-label">Day *</label>
            <select name="day_of_week" class="ds-input" required>
                @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day => $name)
                    <option value="{{ $day }}" {{ old('day_of_week', $slot->day_of_week ?? '') == $day ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="ds-field"><label class="ds-label">Start Time *</label><input type="time" name="start_time" class="ds-input" value="{{ old('start_time', $slot->start_time ?? '') }}" required></div>
            <div class="ds-field"><label class="ds-label">End Time *</label><input type="time" name="end_time" class="ds-input" value="{{ old('end_time', $slot->end_time ?? '') }}" required></div>
        </div>
        <div class="ds-field"><label class="ds-label">Room</label><input type="text" name="room" class="ds-input" value="{{ old('room', $slot->room ?? '') }}" maxlength="100"></div>
        <div class="ds-field">
            <label class="ds-label">Term</label>
            <select name="academic_term_id" class="ds-input">
                <option value="">None</option>
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ old('academic_term_id', $slot->academic_term_id ?? '') == $term->id ? 'selected' : '' }}>{{ $term->displayName ?: $term->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-6 flex gap-3"><button type="submit" class="ds-btn ds-btn-primary">{{ isset($slot) ? 'Update' : 'Create' }} Slot</button><a href="{{ route('teacher.timetable.index') }}" class="ds-btn">Cancel</a></div>
    </form>
</div>
@endsection