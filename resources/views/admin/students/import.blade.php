{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" data-testid="import-students-page">
        @include('layouts.partials.page-header', [
            'title' => 'Import students',
            'subtitle' => 'Same template, preview and errors as setup.',
            'actions' => '<a href="' . url('/admin/students') . '" class="ds-btn ds-btn-ghost text-sm">Back to students</a>',
        ])

        <livewire:admin.import-students />
    </div>
@endsection
