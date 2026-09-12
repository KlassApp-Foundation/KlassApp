{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview-only Pass-2 419. Live errors/419.blade.php untouched (illustrated-layout).
     Verbatim CSRF/session copy; amber refresh primary. --}}
@extends('errors-preview.layout')

@section('code', '419')
@section('title', __('Page Expired'))
@section('message', __('Your session has expired. Please refresh the page and try again. Your data is safe: no changes have been lost.'))

@section('icon')
<div class="err-icon err-icon-amber" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
    </svg>
</div>
@endsection

@section('actions')
<button type="button" onclick="window.location.reload()" class="err-btn err-btn-amber" data-testid="err-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="23 4 23 10 17 10"></polyline>
        <polyline points="1 20 1 14 7 14"></polyline>
        <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"></path>
    </svg>
    {{ __('Refresh and try again') }}
</button>
<a href="{{ url('/') }}" class="err-btn err-btn-secondary" data-testid="err-secondary">{{ __('Return to home') }}</a>
@endsection
