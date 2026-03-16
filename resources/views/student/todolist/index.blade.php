{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
    <portal-target name="add_todolist"></portal-target>
    <list-todo url="{{ url('/') }}" mode="student" hidecolumns="true"></list-todo>
    <!-- <portal-target name="list_todolist"></portal-target> -->
@endsection