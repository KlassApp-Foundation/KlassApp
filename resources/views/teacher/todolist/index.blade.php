{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <portal-target name="add_todolist"></portal-target>
    <list-todo url="{{ url('/') }}" mode="teacher" hidecolumns="false"></list-todo>
    <!-- <portal-target name="list_todolist"></portal-target> -->
@endsection