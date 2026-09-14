{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview-only Pass-2 404. Live errors/404.blade.php untouched (illustrated-layout). --}}
@extends('errors-preview.layout')

@section('code', '404')
@section('title', __('Page Not Found'))
@section('message', __('The page you are looking for could not be found. It may have been moved, deleted, or the link you followed might be incorrect.'))

@section('icon')
<div class="err-icon err-icon-blue" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        <line x1="11" y1="8" x2="11" y2="14"></line>
        <line x1="8" y1="11" x2="14" y2="11"></line>
    </svg>
</div>
@endsection

@section('actions')
<a href="{{ url('/') }}" class="err-btn err-btn-primary" data-testid="err-primary">{{ __('Go to home') }}</a>
<button type="button" onclick="window.history.back()" class="err-btn err-btn-secondary" data-testid="err-secondary">{{ __('Go back') }}</button>
@endsection
