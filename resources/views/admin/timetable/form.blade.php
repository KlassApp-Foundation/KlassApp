{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
@include('layouts.partials.page-header', [
    'title' => isset($slot) ? 'Edit Timetable Slot' : 'Add Timetable Slot',
    'subtitle' => isset($slot) ? 'Update slot details. Conflicts are rechecked on save.' : 'Create a new weekly lesson slot. Conflicts are detected automatically.',
])
@include('partials.message')
@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ isset($slot) ? route('admin.timetable.update', $slot) : route('admin.timetable.store') }}" class="max-w-xl">
    @csrf
    @if(isset($slot)) @method('PUT') @endif

    <div class="ds-field">
        <label class="ds-label">Class *</label>
        <select name="section_id" class="ds-input" required>
            <option value="">Select...</option>
            @foreach($sections as $s)
                <option value="{{ $s->id }}" {{ (old('section_id', $slot->section_id ?? $sectionId ?? '') == $s->id) ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="ds-field">
        <label class="ds-label">Subject *</label>
        <select name="subject_id" class="ds-input" required>
            <option value="">Select...</option>
            @foreach($subjects as $subj)
                <option value="{{ $subj->id }}" {{ old('subject_id', $slot->subject_id ?? '') == $subj->id ? 'selected' : '' }}>{{ $subj->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="ds-field">
        <label class="ds-label">Teacher *</label>
        <select name="teacher_id" class="ds-input" required>
            <option value="">Select...</option>
            @foreach($teachers as $t)
                <option value="{{ $t->id }}" {{ old('teacher_id', $slot->teacher_id ?? '') == $t->id ? 'selected' : '' }}>{{ $t->displayName ?: $t->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="ds-field">
        <label class="ds-label">Day *</label>
        <select name="day_of_week" class="ds-input" required>
            @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $i => $d)
                <option value="{{ $i }}" {{ old('day_of_week', $slot->day_of_week ?? '') == $i ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="ds-field">
            <label class="ds-label">Start Time *</label>
            <input type="time" name="start_time" class="ds-input" value="{{ old('start_time', $slot->start_time ?? '') }}" required>
        </div>
        <div class="ds-field">
            <label class="ds-label">End Time *</label>
            <input type="time" name="end_time" class="ds-input" value="{{ old('end_time', $slot->end_time ?? '') }}" required>
        </div>
    </div>

    <div class="ds-field">
        <label class="ds-label">Room</label>
        <input type="text" name="room" class="ds-input" value="{{ old('room', $slot->room ?? '') }}" placeholder="e.g. Lab 3" maxlength="100">
    </div>

    <div class="ds-field">
        <label class="ds-label">Academic Year</label>
        <select name="academic_year_id" class="ds-input">
            @foreach($years as $y)
                <option value="{{ $y->id }}" {{ old('academic_year_id', $slot->academic_year_id ?? '') == $y->id ? 'selected' : '' }}>{{ $y->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="ds-field">
        <label class="ds-label">Term (for calendar sync)</label>
        <select name="academic_term_id" class="ds-input">
            <option value="">—</option>
            @foreach($terms as $t)
                <option value="{{ $t->id }}" {{ old('academic_term_id', $slot->academic_term_id ?? '') == $t->id ? 'selected' : '' }}>{{ $t->displayName ?: $t->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-6 flex gap-3">
        <button type="submit" class="ds-btn ds-btn-primary">{{ isset($slot) ? 'Update' : 'Create' }} Slot</button>
        <a href="{{ route('admin.timetable.index', ['section_id' => $slot->section_id ?? $sectionId]) }}" class="ds-btn">Cancel</a>
    </div>
</form>
</div>
@endsection
