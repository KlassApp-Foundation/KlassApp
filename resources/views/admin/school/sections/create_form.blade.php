{{-- SPDX-License-Identifier: MIT --}}
<form method="POST" action="{{url('/admin/section/add')}}" enctype="multipart/form-data">
  @csrf
  <div class="flex"> 
    <div class="tw-form-group w-1/2">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="name" class="tw-form-label">Section Name<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <input type="text" name="name" id="name" class="tw-form-control w-3/4" placeholder="Section Name">
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('name')}}</span>
      </div>
    </div>
  </div>
 
  <div class="my-6">
    <input type="submit" value="Submit" name="submit" class="btn btn-primary submit-btn cursor-pointer">
  </div>
</form>
