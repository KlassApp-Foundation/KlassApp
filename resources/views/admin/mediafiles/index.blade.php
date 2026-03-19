{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
    <div class="w-full relative"> 
        <portal-target name="add_media_file"></portal-target>
        <div class="bg-white my-3">
            @include('partials.message')
            <file-list-tab url="{{ url('/') }}"></file-list-tab>
            <portal-target name="media_file_list"></portal-target>
        </div>
    </div>
@endsection