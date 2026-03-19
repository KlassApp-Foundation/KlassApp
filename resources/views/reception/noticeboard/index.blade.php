{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')
    <portal-target name="add_notice"></portal-target>
   	<notice-board-list url="{{ url('/') }}" scope="" hidecolumns="true" searchquery="{{ $query }}" mode="receptionist"></notice-board-list>
@endsection