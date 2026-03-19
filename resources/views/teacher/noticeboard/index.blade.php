{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
   	<portal-target name="add_notice"></portal-target>
   	<notice-board-list url="{{ url('/') }}" scope="" hidecolumns="true" searchquery="{{ $query }}" mode="teacher"></notice-board-list>
@endsection