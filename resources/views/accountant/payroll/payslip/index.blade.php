{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
    <payroll-list url="{{ url('/') }}" ></payroll-list>
 
 @endsection