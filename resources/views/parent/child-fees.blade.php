{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--parent px-4 md:px-6 py-4" data-testid="parent-child-fees-page">
    @include('layouts.partials.page-header', [
        'title' => 'Fees',
        'subtitle' => $childName.($studentId ? ' · Student #'.$studentId : ''),
    ])

    <p class="mt-2 mb-4">
        <a href="{{ route('parent.children') }}" class="ds-btn ds-btn-sm ds-btn-outline">← Children</a>
        @if ($studentId)
            <a href="{{ route('parent.dashboard', ['child' => $studentId]) }}" class="ds-btn ds-btn-sm ds-btn-outline">Dashboard</a>
        @endif
    </p>

    <x-card title="Fee Balance" padding="none" class="parent-panel" data-testid="parent-fees-panel">
        @include('parent.partials.fees-panel', ['fees' => $fees])
    </x-card>
</div>
@endsection
