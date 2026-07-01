{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Home Works',
    'subtitle' => 'Assign and manage homework tasks for all classes.',
    'actions' => '<a href="' . url('/admin/homework/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><i class="fa-solid fa-plus text-xs"></i> Add Homework</a>'
])

<div class="relative mt-4">
    @if(Auth::user()->school->settings->approval == 'true')
        <portal-target name="add_homework"></portal-target>
        <home-work-list url="{{ url('/') }}" scope="" hidecolumns="false" searchquery="{{ $query }}" mode="admin"></home-work-list>
    @else
        <portal-target name="add_homework"></portal-target>
        <list-tab-homework url="{{ url('/') }}" role="{{ $role }}" scope="" hidecolumns="false" searchquery="{{ $query }}" mode="admin"></list-tab-homework>
        <portal-target name="list_homework"></portal-target>
    @endif
@endsection