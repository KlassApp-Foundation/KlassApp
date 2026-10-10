{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Parents',
        'subtitle' => $list['subtitle'],
        'actions' => '<a href="' . url('/admin/import') . '" class="ds-btn ds-btn-ghost text-sm">Import list</a>'
            . '<a href="' . url('/admin/parent/add') . '" class="ds-btn ds-btn-primary text-sm">Add parent</a>',
    ])

    @include('partials.message')

    <x-people-list :list="$list" />
</div>
@endsection
