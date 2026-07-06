{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
   <div class="relative py-4 px-4 md:px-6">
      <div class="ds-page-head">
         <div>
            <h1 class="ds-page-head-title">Subject Details</h1>
         </div>
         <div class="flex items-center gap-2">
            <x-button href="{{ url('/admin/subjects/add-new') }}" variant="success" size="sm">
               <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
               Add Subject
            </x-button>
         </div>
      </div>
      @include('partials.message')
      @include('admin.subject.list')
   </div>
@endsection