{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
   <div class="relative">
      <div class="flex flex-wrap lg:flex-row justify-between">
         <div class="">
            <h1 class="admin-h1 my-3">Subject Details</h1>
         </div>
         <div class="relative flex items-center w-1/4 lg:justify-end">
            <div class="flex items-center">
               <a href="{{url('/admin/subjects/add')}}" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">
                  <span class="mx-1 text-sm font-semibold">Add</span>
                  <img src="{{asset('uploads/icons/plus.svg')}}" class="w-3 h-3">
               </a> 
            </div>
         </div>
      </div>
      @include('partials.message')
      @include('admin.subject.list')
   </div>
@endsection