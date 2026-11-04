{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4" data-testid="student-fees-page">
    @include('layouts.partials.page-header', [
        'title' => 'My fees',
        'subtitle' => 'Your fee structure, what you have paid, and the balance.',
    ])

    @include('portal.partials.fees-chart', ['fees' => $fees])

    <div class="ds-card ds-card-padding-none mt-4">
        @include('parent.partials.fees-panel', ['fees' => $fees])
    </div>
</div>
@endsection
