{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Media Files',
    'subtitle' => 'Upload and manage videos, documents, and learning resources.',
    'actions' => '<a href="' . url('/admin/mediafiles/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><i class="fa-solid fa-plus text-xs"></i> Upload File</a>'
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