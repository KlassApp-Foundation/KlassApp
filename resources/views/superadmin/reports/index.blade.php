{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--superadmin">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Reports</h1>
            <p class="dashboard-subtitle" style="margin-top: 4px;">
                Platform reports and subscription management
            </p>
        </div>
    </div>

    @include('partials.message')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        {{-- Contact Inquiries --}}
        <a href="{{ route('superadmin.reports.contactlist') }}"
           class="block bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-[#1E6FD9]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Contact Inquiries</h2>
                    <p class="text-sm text-gray-500 mt-1">Form submissions from the public landing page</p>
                </div>
            </div>
        </a>

        {{-- Subscriptions --}}
        <a href="{{ route('superadmin.reports.subscriptionlist') }}"
           class="block bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-[#22C55E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Subscriptions</h2>
                    <p class="text-sm text-gray-500 mt-1">View and manage school plan subscriptions</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-8">
        <h2 class="text-base font-semibold text-gray-700 mb-3">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('superadmin.reports.subscription.create') }}"
               class="ds-btn ds-btn-primary ds-btn-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Create Subscription
            </a>
        </div>
    </div>
</div>
@endsection
