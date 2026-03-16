{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <teacher-transaction-list url="{{ url('/') }}" ></teacher-transaction-list>
 
 @endsection