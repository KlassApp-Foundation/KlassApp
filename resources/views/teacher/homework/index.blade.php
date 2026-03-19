{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    @if(Auth::user()->school->settings->approval == 'true')
        <portal-target name="add_homework"></portal-target>
        <home-work-list url="{{ url('/') }}" scope="" hidecolumns="false" searchquery="{{ $query }}" mode="teacher"></home-work-list>
    @else
        <portal-target name="add_homework"></portal-target>
        <list-tab-homework url="{{ url('/') }}" scope="" role="{{ $role }}" hidecolumns="false" searchquery="{{ $query }}" mode="teacher"></list-tab-homework>
        <portal-target name="list_homework"></portal-target>
    @endif
@endsection