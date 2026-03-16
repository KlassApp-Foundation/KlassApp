{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
    	<portal-target name="parent_index"></portal-target>
        @include('partials.message')
        <parent-list url="{{ url('/') }}" searchquery="{{ $query }}"></parent-list>
    </div>
@endsection