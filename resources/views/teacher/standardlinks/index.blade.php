{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
  <div class="relative">
    <div class="flex flex-col lg:flex-row justify-between">
      <div class="">
        <h1 class="admin-h1 my-3">Class Details</h1>
      </div>
    </div>
    <standardfilter url="{{ url('/') }}" type="teacher"></standardfilter>
    @include('partials.message')
    @include('teacher.standardlinks.list')
   </div>
@endsection