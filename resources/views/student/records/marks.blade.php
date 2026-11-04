{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4" data-testid="student-marks-page">
    @include('layouts.partials.page-header', [
        'title' => 'My marks',
        'subtitle' => 'Released results for every year. Only your own marks are shown here.',
    ])

    @include('portal.partials.results-year-filter', ['grades' => $grades])
    @include('portal.partials.performance-chart', ['grades' => $grades])

    <div class="mt-4">
        @include('parent.partials.grades-panel', ['grades' => $grades, 'emptyMessage' => $message ?? null])
    </div>
</div>
@endsection
