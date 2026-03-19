{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
   	<portal-target name="add_homework"></portal-target>
   	<homework-list url="{{ url('/') }}" scope="" hidecolumns="true" searchquery="{{ $query }}" mode="student"></homework-list>
@endsection