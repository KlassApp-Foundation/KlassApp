{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="family-page" data-testid="student-marks-page">
    <main class="family-main" style="width:100% !important;margin-left:0 !important;margin-right:0 !important;">
        <a class="family-back" href="{{ $links['dashboard'] }}">← {{ $childName }}</a>
        @include('partials.message')
        @include('portal.partials.family-states', ['loading' => $loading, 'panel' => 'marks', 'childName' => $childName, 'bundle' => $bundle])
        @if(!$loading)
            @include('portal.partials.family-marks', ['bundle' => $bundle, 'links' => $links])
        @endif
    </main>
</div>
@endsection
