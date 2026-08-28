{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--parent px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Parent Portal',
        'subtitle' => 'Signed-in parent web shell — linked children and school views ship in Phase 5.',
    ])

    @include('partials.message')

    <div class="dashboard-kpi-grid mt-4">
        <x-ds-kpi-card icon="students" value="—" label="Linked Children" color="blue" />
        <x-ds-kpi-card icon="messages" value="—" label="Messages" color="green" />
    </div>

    <div class="ds-card ds-card-padding mt-6">
        <p class="text-sm" style="color: var(--d-muted);">
            Welcome, {{ auth()->user()->name }}. Use WhatsApp for fees and grades today; the web dashboard fills in during Phase 5.
        </p>
    </div>
</div>
@endsection
