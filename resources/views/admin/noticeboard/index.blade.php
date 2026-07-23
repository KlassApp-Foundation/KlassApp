{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Notice Board',
    'subtitle' => 'Post and manage school announcements, circulars, and notices.',
    'actions' => '<a href="' . url('/admin/noticeboard/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New Notice</a>'
])

<div class="relative mt-4">
   	<portal-target name="add_notice"></portal-target>
   	<notice-board-list url="{{ url('/') }}" scope="" hidecolumns="false" searchquery="{{ $query }}" mode="admin"></notice-board-list>
</div>
</div>
@endsection