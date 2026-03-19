{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.library.layout')

@section('content')
    <portal-target name="add_todolist"></portal-target>
    <list-todo url="{{ url('/') }}" mode="library" hidecolumns="true"></list-todo>
    <!-- <portal-target name="list_todolist"></portal-target> -->
@endsection