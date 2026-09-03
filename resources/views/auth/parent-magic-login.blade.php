{{-- SPDX-License-Identifier: MIT --}}
@extends('errors.illustrated-layout')

@section('title', 'Open parent dashboard')
@section('message', 'Tap continue to sign in as '.($parent->displayName ?: 'parent').'. This link works once.')

@section('icon')
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
    <path d="M7 11V7a5 5 0 0110 0v4"></path>
</svg>
@endsection

@section('actions')
<form method="POST" action="{{ route('parent.magic-login.confirm') }}" style="display:inline;">
    @csrf
    <button type="submit" class="klass-error-btn klass-error-btn-primary">
        Continue to dashboard
    </button>
</form>
@endsection
