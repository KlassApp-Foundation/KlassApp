{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.library.layout')

@section('content')
   <div class="relative">
      <div class="flex flex-wrap lg:flex-row justify-between">
         <div class="">
            <h1 class="admin-h1 my-3">Book Lending Details</h1>
         </div>
         <div class="relative flex items-center w-1/3 lg:w-1/4 justify-end">

            <div class="flex items-center">
               <a href="{{url('/library/booklending/add')}}" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center whitespace-no-wrap">
                  <span class="mx-1 text-sm font-semibold">Add</span>
               <svg data-v-2a22d6ae="" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 409.6 409.6" xml:space="preserve" class="w-3 h-3 fill-current text-white"><g data-v-2a22d6ae=""><g data-v-2a22d6ae=""><path data-v-2a22d6ae="" d="M392.533,187.733H221.867V17.067C221.867,7.641,214.226,0,204.8,0s-17.067,7.641-17.067,17.067v170.667H17.067
         C7.641,187.733,0,195.374,0,204.8s7.641,17.067,17.067,17.067h170.667v170.667c0,9.426,7.641,17.067,17.067,17.067
         s17.067-7.641,17.067-17.067V221.867h170.667c9.426,0,17.067-7.641,17.067-17.067S401.959,187.733,392.533,187.733z"></path></g></g></svg>
               </a> 
            </div>
         </div>
   </div>
          <form method="GET" action="{{ url('/library/bookscategory/index') }}" role="search" enctype="multipart/form-data">


          <div class="w-1/2 lg:w-1/6 ml-auto mb-2">
        
        <div class="relative">
          <input type="text" name="q" class="tw-form-control w-full relative" placeholder="Search" value="{{ old('q') }}">

          
          <button class="no-underline text-white  px-4  mx-1  py-1 absolute right-0 focus:outline-none">
          <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30.239px" height="30.239px" viewBox="0 0 30.239 30.239" xml:space="preserve" class="w-4 h-4 fill-current text-gray-600 absolute right-0 mt-2 mx-1 top-0"><g><path d="M20.194,3.46c-4.613-4.613-12.121-4.613-16.734,0c-4.612,4.614-4.612,12.121,0,16.735
    c4.108,4.107,10.506,4.547,15.116,1.34c0.097,0.459,0.319,0.897,0.676,1.254l6.718,6.718c0.979,0.977,2.561,0.977,3.535,0
    c0.978-0.978,0.978-2.56,0-3.535l-6.718-6.72c-0.355-0.354-0.794-0.577-1.253-0.674C24.743,13.967,24.303,7.57,20.194,3.46z
     M18.073,18.074c-3.444,3.444-9.049,3.444-12.492,0c-3.442-3.444-3.442-9.048,0-12.492c3.443-3.443,9.048-3.443,12.492,0
    C21.517,9.026,21.517,14.63,18.073,18.074z"></path></g></svg>
          </button>
        </div>
      </div>

         
         
         </form>
   
      @include('partials.message')
     @include('library.booklending.list')
     
   </div>
@endsection