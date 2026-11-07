{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="family-page" data-testid="parent-simple-edit">
    <main class="family-main">
        <h1>Edit parent</h1>
        <p>Name, phone, and email.</p>
        @include('partials.message')
        <form method="POST" action="{{ url('/admin/parent/edit/'.$user->name) }}" class="roster-simple-form">
            @csrf
            <label>Name
                <input class="ds-input" name="name" value="{{ old('name', $fields['name']) }}" required maxlength="120" autocomplete="name">
            </label>
            @error('name')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <label>Phone
                <input class="ds-input" name="phone" value="{{ old('phone', $fields['phone']) }}" required maxlength="20" inputmode="tel" autocomplete="tel">
            </label>
            @error('phone')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <label>Email
                <input class="ds-input" type="email" name="email" value="{{ old('email', $fields['email']) }}" maxlength="190" autocomplete="email">
            </label>
            @error('email')<p class="roster-simple-error" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="ds-btn ds-btn-primary">Save</button>
        </form>
    </main>
</div>
@endsection
