{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between">
            <div class="">
                <h1 class="admin-h1 my-3">Posts</h1>
            </div>
        </div>
        @include('partials.message')
        <post-list url="{{ url('/') }}" entity_id="{{ $entity_id }}" entity_name="{{ $entity_name }}" mode="student" auth_id="{{ \Auth::id() }}" hidecolumns="false" id="null"></post-list>
   </div>

@endsection