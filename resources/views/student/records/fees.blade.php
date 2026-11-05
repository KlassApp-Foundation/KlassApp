{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="family-page" data-testid="student-fees-page">
    <main class="family-main" style="width:100% !important;margin-left:0 !important;margin-right:0 !important;">
        <a class="family-back" href="{{ $links['dashboard'] }}">← {{ $childName }}</a>
        @include('partials.message')
        @include('portal.partials.family-states', ['loading' => $loading, 'panel' => 'fees', 'childName' => $childName, 'bundle' => $bundle])
        @if(!$loading && empty($bundle['errors']['fees']))
            @include('portal.partials.family-fees', ['bundle' => $bundle])
        @endif
    </main>
</div>
@endsection