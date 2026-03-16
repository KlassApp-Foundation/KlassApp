{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <teacher-payroll-list url="{{ url('/') }}" ></teacher-payroll-list>
 
 @endsection