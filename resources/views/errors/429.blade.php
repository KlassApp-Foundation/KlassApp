{{-- SPDX-License-Identifier: MIT --}}
@extends('errors.illustrated-layout')

@section('code', '429')
@section('title', __('Too Many Requests'))
@section('message', __('You are making too many requests. Please wait a moment and try again. This limit helps us keep KlassApp fast and reliable for everyone.'))

@section('icon')
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#FBBF24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
</svg>
@endsection
