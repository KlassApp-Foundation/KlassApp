{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
   	<portal-target name="add_notice"></portal-target>
   	<notice-board-list url="{{ url('/') }}" scope="" hidecolumns="true" searchquery="{{ $query }}" mode="student"></notice-board-list>
@endsection