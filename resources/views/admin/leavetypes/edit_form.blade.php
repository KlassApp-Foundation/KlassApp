{{-- SPDX-License-Identifier: MIT --}}
<form method="POST" action="{{url('/admin/leavetype/edit/'.$leavetype->id)}}" enctype="multipart/form-data">
  @csrf
  <div class="flex"> 
    <div class="tw-form-group w-1/2">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="name" class="tw-form-label">Title<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <input type="text" name="name" id="name" class="tw-form-control w-3/4" placeholder="Title" value="{{ $leavetype->name }}">
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('name')}}</span>
      </div>
    </div>
  </div>

  <div class="flex"> 
    <div class="tw-form-group w-1/2">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="max_no_of_days" class="tw-form-label">Max. No. Of Days<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <input type="text" name="max_no_of_days" id="max_no_of_days" class="tw-form-control w-3/4" placeholder="Max. No. Of Days" value="{{ $leavetype->max_no_of_days }}">
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('max_no_of_days')}}</span>
      </div>
    </div>
  </div>
 
  <div class="pt-3 pb-6">
    <input type="submit" value="Submit" name="submit" class="btn btn-primary submit-btn cursor-pointer">
  </div>
</form>
