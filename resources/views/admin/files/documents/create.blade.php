{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="w-full">
    <div>
      <h1 class="admin-h1 mb-5 flex items-center">
  <span class="">File Upload ({{ $count }})</span>
      </h1>
    </div>
      @include('partials.message')
      <div class="bg-white shadow my-5">
       @include('layouts.partials.customtab')

       @if($count < $subscription->plan->no_of_files)
      <div class="px-3 py-3 mx-2">
   <form method="post" action="{{url('/admin/files')}}" enctype="multipart/form-data">
       @csrf
   <div class="my-3">
         <div class="">
          <div class="w-full lg:w-1/4">
            <label for="name" class="tw-form-label">Name</label>
            </div>
            <div class="w-full lg:w-2/5 my-2">
               <input type="text" name="name" id="name" class="tw-form-control w-full">
               <span class="text-danger text-xs">{{$errors->first('name')}}</span>
             </div>
      </div>
  </div>

   <div class="my-3">
       <div class="">
           <div class="w-full lg:w-1/4">
           <label class="tw-form-label ">Description</label>
           </div>
           <div class="w-full lg:w-2/5 my-2">
               <textarea type="textarea" name="description" id="description" class="tw-form-control w-full" rows="3"> </textarea>
               <span class="text-danger text-xs">{{$errors->first('description')}}</span>
           </div>
       </div>
   </div>

   <div class="my-3">
       <div class="">
             <div class="w-full lg:w-1/4">
             <label class="tw-form-label ">Select File</label>
             </div>
             <div class="my-2 w-full lg:w-2/5 flex flex-col">
               <input type="file" name="file[]" id="file" multiple="multiple">
               <span class="text-danger text-xs">{{$errors->first('file')}}</span>
               @foreach($errors->all() as $error)
                 @if($error=="File extension error")
                   <span class="text-danger text-xs">{{$error}}</span>
                  @elseif($error == "Maximum file size to upload is 8MB")
                    <span class="text-danger text-xs">{{$error}}</span>
                 @endif
               @endforeach
            </div>
       </div>
   </div>
   <div class="mt-6 mb-4">
       <button class="btn btn-primary blue-bg text-white rounded px-3 py-1 text-sm font-medium" id="upload">Upload</button>
   </div>

</form>
</div>
@else
    <a href="{{ url('/pricing') }}"> 
        <button type="submit" class="no-underline text-white  px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">
            Upgrade Plan to Add More Files
        </button>
    </a>
@endif

</div>
<div class="custom-table py-3">
 <table class="w-full">
         <thead class="bg-grey-light">
            <tr class="border-t-2 border-b-2">
               <th class="text-left text-sm px-2 py-2 text-grey-darker">ID</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Church ID</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Name</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Description</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Path</th>
 
         </tr>
         </thead>
         <tbody>
         @foreach ($files as $file)
          <tr>
            <td>{{ $file->id }}</td>
            <td>{{ $file->church_id }}</td>
            <td>{{ $file->name }}</td>
            <td>{{ $file->description }}</td>
            <td><a href="{{ url('/admin/download/files/'.$file->id) }}" id="download">Download</a>
            </td> 
          </tr>
         @endforeach
          </tbody>

      </table>
</div>
 {{$files->links()}}

</div>
@endsection
 <style>
 .text-danger
 {
  color:red;
 }
 </style>