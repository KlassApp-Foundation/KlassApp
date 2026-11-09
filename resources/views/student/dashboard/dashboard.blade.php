{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="family-page" data-testid="student-dashboard">
    <main class="family-main" style="width:100% !important;margin-left:0 !important;margin-right:0 !important;">
        @include('partials.message')
        <p class="family-student-hello">Hi, {{ $childName }}</p>
        @if($className !== '' || !empty($bundle['attendance']['term_label']))
            <p class="family-student-class">{{ trim($className.($className !== '' && !empty($bundle['attendance']['term_label']) ? ' · ' : '').($bundle['attendance']['term_label'] ?? '')) }}</p>
        @endif
        @include('portal.partials.family-states', ['loading' => $loading, 'panel' => 'home', 'childName' => $childName, 'bundle' => $bundle])
        @if(!$loading)
            @include('portal.partials.family-cards', ['bundle' => $bundle, 'links' => $links])
            @if(!empty($bundle['grades']['exam_groups']))
                <section class="family-table-card">
                    <h2>Your marks over time</h2>
                    @include('portal.partials.performance-chart', ['grades' => $bundle['grades']])
                </section>
            @endif
        @endif
    </main>
</div>
@endsection
