{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')

<div class="w-full  ">
    <h1 class="admin-h1 mb-5 flex items-center">
      <a href="{{ url('/admin/subjects') }}" title="Back" class="rounded-full bg-gray-300 p-2">
          <img src="{{asset('uploads/icons/back.svg')}}" class="w-3 h-3">
      </a>
      <span class="mx-3">Add Subjects</span>
    </h1>
    <div class="flex items-center justify-center shadow-lg px-6">
        <div class="w-full">
            <add-subjects></add-subjects> 
        </div>
    </div> 
</div>

@endsection