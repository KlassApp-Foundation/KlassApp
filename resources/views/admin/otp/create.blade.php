{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')
  <div class="w-full flex flex-col mx-2 lg:mx-0 md:mx-0">
    @include('layouts.partials.logo')
    <div class="w-full lg:w-1/3 p-8 mx-auto bg-white relative">
      @include('partials.message')
      @include('admin.otp.create_form')
    </div>
  </div>
@endsection

