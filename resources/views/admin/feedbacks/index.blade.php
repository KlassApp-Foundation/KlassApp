{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout') 

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between">
            <div class="">
                <h1 class="admin-h1 my-3">Feedbacks</h1>
            </div>
            <form action="{{ url('/admin/feedbacks') }}" enctype="multipart form-data">
                <div class="py-4">
                    <div class="flex items-center mx-2">
                        <div class="search relative mx-2">
                            <input type="text" name="search" class="border px-10 py-1 text-sm border-gray-400 w-full rounded bg-white shadow" placeholder="Search" value="{{ old('search') }}">  
                            <span class="input-group-btn absolute left-0 px-3 py-2 top-0">                  
                                <button type="submit">
                                    <svg class="w-4 h-4 fill-current text-gray-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30.239px" height="30.239px" viewBox="0 0 30.239 30.239" style="enable-background:new 0 0 30.239 30.239;" xml:space="preserve"><g><path d="M20.194,3.46c-4.613-4.613-12.121-4.613-16.734,0c-4.612,4.614-4.612,12.121,0,16.735 c4.108,4.107,10.506,4.547,15.116,1.34c0.097,0.459,0.319,0.897,0.676,1.254l6.718,6.718c0.979,0.977,2.561,0.977,3.535,0 c0.978-0.978,0.978-2.56,0-3.535l-6.718-6.72c-0.355-0.354-0.794-0.577-1.253-0.674C24.743,13.967,24.303,7.57,20.194,3.46z M18.073,18.074c-3.444,3.444-9.049,3.444-12.492,0c-3.442-3.444-3.442-9.048,0-12.492c3.443-3.443,9.048-3.443,12.492,0 C21.517,9.026,21.517,14.63,18.073,18.074z"/></g></svg>
                                </button>
                            </span>
                        </div>
                        <div class="date-select date-select_none dashboard-reset mx-1 lg:mx-0 md:mx-0">
                            <a href="{{ url('/admin/feedbacks') }}" id="do-reset" class="text-sm border bg-gray-100 text-grey-darkest py-1 px-4">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @include('partials.message')
        <div class="p-4 bg-white shadow-lg">
            @include('admin.feedbacks.list')
        </div>
    </div>
@endsection