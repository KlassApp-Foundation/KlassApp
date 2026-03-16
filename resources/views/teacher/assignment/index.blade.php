{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <portal-target name="approve_assignment"></portal-target>
    @include('partials.message')
    <list-tab-assignment url="{{ url('/') }}" role="{{ $role }}" type="teacher" scope="" hidecolumns="false" searchquery="{{ $query }}"></list-tab-assignment>
    <portal-target name="list_assignment"></portal-target>
@endsection