{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" data-testid="students-roster">
    @include('layouts.partials.page-header', [
        'title' => 'Students',
        'subtitle' => $list['subtitle'],
        'actions' => '<a href="' . url('/admin/import') . '" class="ds-btn ds-btn-ghost text-sm">Import list</a>'
            . '<a href="' . url('/admin/student/add/') . '" class="ds-btn ds-btn-primary text-sm">Add student</a>',
    ])

    @include('partials.message')

    <x-people-list :list="$list" />
</div>
@endsection
