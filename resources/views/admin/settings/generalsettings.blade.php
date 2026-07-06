{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Settings',
    'subtitle' => 'Configure school preferences, academic settings, and system options.',
])

<div class="relative mt-4">
<div class="w-full main-content flex h-auto">
<div class="flex lg:flex-row w-full">
@include('admin.settings.general_settings')
</div>
</div>
@endsection