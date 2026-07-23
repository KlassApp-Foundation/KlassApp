{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--superadmin">
    <div class="ds-page-head">
        <h1 class="ds-page-head-title">Locations</h1>
        <p class="ds-page-head-sub">Manage cities, districts, and countries</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
        <a href="{{ url('/superadmin/setting/cities') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="10" width="16" height="11" rx="2" ry="2"/>
                    <path d="M9 21V14h6v7"/>
                    <path d="M12 3L3 10h18L12 3z"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">Cities / Districts</p><p class="text-xs text-gray-500">Manage cities and districts</p></div>
        </a>
        <a href="{{ url('/superadmin/setting/countries') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">Countries</p><p class="text-xs text-gray-500">Manage countries</p></div>
        </a>
    </div>
</div>
@endsection
