{{-- SPDX-License-Identifier: MIT --}}
@extends('errors.illustrated-layout')

@section('code', '410')
@section('title', __('Link Expired'))
@section('message', __('This link has expired. Please ask the school to resend it.'))

@section('icon')
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#FBBF24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <path d="M12 7v5l3 3"></path>
</svg>
@endsection
