{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
   	<assignment-list-student url="{{ url('/') }}" type="student" scope="{{ $standardLink_id }}" hidecolumns="true" searchquery="{{$query}}"></assignment-list-student>
@endsection