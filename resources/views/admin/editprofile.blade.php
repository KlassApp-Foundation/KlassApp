{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="py-6 px-4">
    <p class="text-sm text-gray-500 mb-4" style="color:#64748B;font-family:'DM Sans',sans-serif;" data-testid="profile-plan-info">
        @if(!empty($planSummary))
            Current plan: <span style="color:#334155;font-weight:500;">{{ $planSummary }}</span>
            <a href="{{ url('/admin/subscriptions') }}" class="ml-2 underline" style="color:#1E6FD9;">Manage</a>
        @else
            No active plan.
            <a href="{{ url('/admin/subscriptions') }}" class="ml-1 underline" style="color:#1E6FD9;">View plans</a>
        @endif
    </p>
    <edit-profile url="{{url('/')}}"></edit-profile>
</div>
@endsection
