{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Telephone Directory',
    'subtitle' => 'Manage phone contacts for staff, parents, and emergency services.',
    'actions' => '<a href="' . url('/admin/telephonedirectory/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><i class="fa-solid fa-plus text-xs"></i> Add Contact</a>'
])

<div class="relative mt-4">
        @include('partials.message')
        <list-phone-number url="{{ url('/') }}"></list-phone-number>
   </div>
</div>
@endsection