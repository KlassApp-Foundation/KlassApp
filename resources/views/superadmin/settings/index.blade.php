{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--superadmin">
    <div class="ds-page-head">
        <h1 class="ds-page-head-title">Settings</h1>
        <p class="ds-page-head-sub">Platform configuration and management</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
        <a href="{{ url('/superadmin/setting/plans') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600"><i class="fa-solid fa-credit-card"></i></div>
            <div><p class="font-semibold text-sm">Plans</p><p class="text-xs text-gray-500">Manage subscription plans</p></div>
        </a>
        <a href="{{ url('/superadmin/settings/co-admins') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center text-green-600"><i class="fa-solid fa-user-shield"></i></div>
            <div><p class="font-semibold text-sm">Co-Admins</p><p class="text-xs text-gray-500">Manage site sub-administrators</p></div>
        </a>
        <a href="{{ url('/superadmin/settings/system') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600"><i class="fa-solid fa-sliders"></i></div>
            <div><p class="font-semibold text-sm">System Settings</p><p class="text-xs text-gray-500">Maintenance, login, registration</p></div>
        </a>
        <a href="{{ url('/superadmin/setting/cities') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600"><i class="fa-solid fa-location-dot"></i></div>
            <div><p class="font-semibold text-sm">Locations</p><p class="text-xs text-gray-500">Cities, states, countries</p></div>
        </a>
    </div>
</div>
@endsection
