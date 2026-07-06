{{-- SPDX-License-Identifier: MIT --}}
@extends('errors.illustrated-layout')

@section('code', '419')
@section('title', __('Page Expired'))
@section('message', __('Your session has expired. Please refresh the page and try again. Your data is safe — no changes have been lost.'))

@section('icon')
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#FBBF24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <polyline points="12 6 12 12 16 14"></polyline>
</svg>
@endsection
