{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Media Files',
    'subtitle' => 'Upload and manage videos, documents, and learning resources.',
    'actions' => '<a href="' . url('/admin/mediafiles/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Upload File</a>'
])

<div class="relative mt-4"> 
        <portal-target name="add_media_file"></portal-target>
        <div class="bg-white rounded-lg shadow p-4">
            @include('partials.message')
            <file-list-tab url="{{ url('/') }}"></file-list-tab>
            <portal-target name="media_file_list"></portal-target>
        </div>
    </div>
@endsection