{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="family-page" data-testid="student-simple-edit">
    <main class="family-main">
        <h1>Edit student</h1>
        <p>Name, class, stream, gender, date of birth, and the parent's name and phone. The phone is saved on the parent.</p>
        @include('partials.message')
        <form method="POST" action="{{ url('/admin/student/edit/'.$user->name) }}" class="roster-simple-form">
            @csrf
            <label>Name
                <input class="ds-input" name="name" value="{{ old('name', $fields['name']) }}" required maxlength="120" autocomplete="name">
            </label>
            @error('name')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <label>Class
                <input class="ds-input" name="class" value="{{ old('class', $fields['class']) }}" required maxlength="80">
            </label>
            @error('class')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <label>Stream
                <input class="ds-input" name="stream" value="{{ old('stream', $fields['stream']) }}" maxlength="40">
            </label>
            @error('stream')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <label>Gender
                <select class="ds-input" name="gender">
                    <option value="">Not set</option>
                    <option value="female" @selected(old('gender', $fields['gender']) === 'female')>Female</option>
                    <option value="male" @selected(old('gender', $fields['gender']) === 'male')>Male</option>
                </select>
            </label>
            @error('gender')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <label>Date of birth
                <input class="ds-input" type="date" name="date_of_birth" value="{{ old('date_of_birth', $fields['date_of_birth']) }}">
            </label>
            @error('date_of_birth')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <label>Parent name
                <input class="ds-input" name="parent_name" value="{{ old('parent_name', $fields['parent_name']) }}" maxlength="120">
            </label>
            <label>Parent phone
                <input class="ds-input" name="parent_phone" value="{{ old('parent_phone', $fields['parent_phone']) }}" maxlength="20" inputmode="tel" autocomplete="tel">
            </label>
            @error('parent_phone')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="ds-btn ds-btn-primary">Save</button>
        </form>
    </main>
</div>
@endsection
