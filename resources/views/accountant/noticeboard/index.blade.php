{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
    <portal-target name="add_notice"></portal-target>
    <notice-board-list url="{{ url('/') }}" scope="" hidecolumns="true" searchquery="{{ $query }}" mode="accountant"></notice-board-list>
@endsection