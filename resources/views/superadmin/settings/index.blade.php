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
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">Plans</p><p class="text-xs text-gray-500">Manage subscription plans</p></div>
        </a>
        <a href="{{ url('/superadmin/settings/co-admins') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center text-green-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">Co-Admins</p><p class="text-xs text-gray-500">Manage site sub-administrators</p></div>
        </a>
        <a href="{{ url('/superadmin/settings/system') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="21" x2="4" y2="14"/>
                    <line x1="4" y1="10" x2="4" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12" y2="3"/>
                    <line x1="20" y1="21" x2="20" y2="16"/>
                    <line x1="20" y1="12" x2="20" y2="3"/>
                    <line x1="1" y1="14" x2="7" y2="14"/>
                    <line x1="9" y1="8" x2="15" y2="8"/>
                    <line x1="17" y1="16" x2="23" y2="16"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">System Settings</p><p class="text-xs text-gray-500">Maintenance, login, registration</p></div>
        </a>
        <a href="{{ url('/superadmin/settings/features') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="5" width="22" height="14" rx="7" ry="7"/>
                    <circle cx="8" cy="12" r="3.5"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">Feature Toggles</p><p class="text-xs text-gray-500">Per-school feature flags</p></div>
        </a>
        <a href="{{ url('/superadmin/settings/locations') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">Locations</p><p class="text-xs text-gray-500">Cities, districts, countries</p></div>
        </a>
        <a href="{{ url('/superadmin/settings/emis') }}" class="ds-card ds-card-hover p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c0 1.1 2.7 3 6 3s6-1.9 6-3v-5"/>
                </svg>
            </div>
            <div><p class="font-semibold text-sm">EMIS</p><p class="text-xs text-gray-500">Education data integration</p></div>
        </a>
    </div>
</div>
@endsection
