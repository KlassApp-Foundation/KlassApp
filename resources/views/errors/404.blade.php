{{-- SPDX-License-Identifier: MIT --}}
@extends('errors.illustrated-layout')

@section('code', '404')
@section('title', __('Page Not Found'))
@section('message', __('The page you are looking for could not be found. It may have been moved, deleted, or the link you followed might be incorrect.'))

@section('icon')
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="8"></circle>
    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    <line x1="11" y1="8" x2="11" y2="14"></line>
    <line x1="8" y1="11" x2="14" y2="11"></line>
</svg>
@endsection
