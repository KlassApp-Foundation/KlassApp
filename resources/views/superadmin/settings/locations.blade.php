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
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600"><i class="fa-solid fa-city"></i></div>
            <div><p class="font-semibold text-sm">Cities / Districts</p><p class="text-xs text-gray-500">Manage cities and districts</p></div>
        </a>
        <a href="{{ url('/superadmin/setting/countries') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600"><i class="fa-solid fa-globe"></i></div>
            <div><p class="font-semibold text-sm">Countries</p><p class="text-xs text-gray-500">Manage countries</p></div>
        </a>
    </div>
</div>
@endsection
