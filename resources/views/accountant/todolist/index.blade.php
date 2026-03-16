{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
    <portal-target name="add_todolist"></portal-target>
    <list-todo url="{{ url('/') }}" mode="accountant" hidecolumns="true"></list-todo>
    <!-- <portal-target name="list_todolist"></portal-target> -->
@endsection