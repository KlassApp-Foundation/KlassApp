{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <portal-target name="add_todolist"></portal-target>
    <list-todo url="{{ url('/') }}" mode="admin" hidecolumns="false"></list-todo>
    <!-- <portal-target name="list_todolist"></portal-target> -->
@endsection