{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--parent px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Children',
        'subtitle' => 'Linked student accounts across your schools.',
    ])

    <div class="ds-card ds-card-padding">
        <p class="text-sm" style="color: var(--d-muted);">
            Child list and per-school detail views will be added in Phase 5.
        </p>
    </div>
</div>
@endsection
