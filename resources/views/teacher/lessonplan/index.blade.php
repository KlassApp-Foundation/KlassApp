{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
  <portal-target name="approve_lesson_plan"></portal-target>
  @include('partials.message')
  <list-tab-lesson url="{{ url('/') }}" role="{{ $role }}"></list-tab-lesson>
  <portal-target name="list_lessonplan"></portal-target>
@endsection