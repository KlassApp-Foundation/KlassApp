{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="family-page" data-testid="parent-child-attendance-page">
    <main class="family-main" style="width:100% !important;margin-left:0 !important;margin-right:0 !important;">
        <a class="family-back" href="{{ $links['dashboard'] }}">← {{ $childName }}</a>
        @include('partials.message')
        @include('portal.partials.family-states', ['loading' => $loading, 'panel' => 'attendance', 'childName' => $childName, 'bundle' => $bundle])
        @if(!$loading && empty($bundle['errors']['attendance']))
            @include('portal.partials.family-attendance', ['bundle' => $bundle])
        @endif
    </main>
</div>
@endsection
