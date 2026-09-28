{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Attendance',
        'subtitle' => 'Record daily attendance for your assigned classes and keep the mark-up clear for each session.',
    ])

    <div class="mt-6">
        @include('partials.message')
    </div>

    <div class="ds-card ds-card-padding-default mt-6">
        @if($standard != null)
            <div class="mb-4 flex items-center justify-between gap-3">
                <a href="{{ url('/teacher/standardLink/show/'.$standard) }}" class="ds-btn ds-btn-ghost ds-btn-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                    Back to class
                </a>
            </div>
        @endif

        <create-attendance url="{{ url('/') }}" standard="{{ $standard }}" mode="teacher" date="{{ date('Y-m-d') }}"></create-attendance>
    </div>
</div>
@endsection