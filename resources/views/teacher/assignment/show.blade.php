{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
   <portal-target name="assignment_list"></portal-target>
   <student-assignment-list url="{{ url('/') }}" id="{{ $assignment->id }}" searchquery="{{ $query }}" viewers="{{count($assignment->viewers)}}"></student-assignment-list>
@endsection