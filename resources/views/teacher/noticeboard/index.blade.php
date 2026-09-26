{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Notices',
        'subtitle' => 'Keep up with class updates, announcements, and school-wide messages.',
    ])

    <div class="ds-card ds-card-padding-default mt-6">
        <portal-target name="add_notice"></portal-target>
        <notice-board-list url="{{ url('/') }}" scope="" hidecolumns="true" searchquery="{{ $query }}" mode="teacher"></notice-board-list>
    </div>
</div>
@endsection