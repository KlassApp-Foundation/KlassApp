{{-- SPDX-License-Identifier: MIT --}}
<form method="POST" action="{{url('/admin/leavetype/add')}}" enctype="multipart/form-data">
  @csrf
  <div class="bg-white shadow px-4 py-3">
  <div class="flex flex-col lg:flex-row md:flex-row"> 
    <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="name" class="tw-form-label">Title<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <input type="text" name="name" id="name" class="tw-form-control w-full lg:w-3/4 md:w-3/4" placeholder="Title">
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('name')}}</span>
      </div>
    </div>
  </div>

  <div class="flex flex-col lg:flex-row md:flex-row"> 
    <div class="tw-form-group w-full lg:w-1/2 md:w-1/2 ">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="max_no_of_days" class="tw-form-label">Max. No. Of Days<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <input type="text" name="max_no_of_days" id="max_no_of_days" class="tw-form-control w-full lg:w-3/4 md:w-3/4" placeholder="Max. No. Of Days">
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('max_no_of_days')}}</span>
      </div>
    </div>
  </div>
 
  <div class="my-6">
    <input type="submit" value="Submit" name="submit" class="btn btn-primary submit-btn cursor-pointer">
  </div>
  </div>
</form>
