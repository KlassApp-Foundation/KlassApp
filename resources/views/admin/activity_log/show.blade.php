{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Activity Log',
    'subtitle' => 'Track all system activity and user actions across the school.',
])

<div class="relative mt-4">
@include('admin.activity_log.show_list')
</div>
</div>
@endsection