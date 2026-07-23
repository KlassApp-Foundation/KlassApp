{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Home Works',
    'subtitle' => 'Assign and manage homework tasks for all classes.',
    'actions' => '<a href="' . url('/admin/homework/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Homework</a>'
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