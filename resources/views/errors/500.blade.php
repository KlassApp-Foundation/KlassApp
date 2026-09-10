{{-- SPDX-License-Identifier: MIT --}}
@extends('errors.illustrated-layout')

@section('code', '500')
@section('title', __('Server Error'))
@section('message', __('Something went wrong on our end. Our team has been notified and is working on a fix. Please try again in a few minutes.'))

@section('icon')
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
    <line x1="12" y1="9" x2="12" y2="13"></line>
    <line x1="12" y1="17" x2="12.01" y2="17"></line>
</svg>
@endsection
