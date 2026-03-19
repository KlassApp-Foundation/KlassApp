{{-- SPDX-License-Identifier: MIT --}}
<form method="POST" action="{{url('/admin/standardLink/update/'.$standardLink->id)}}" enctype="multipart/form-data">
  @csrf
  <div class="flex"> 
    <div class="tw-form-group w-1/2">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="standard_id" class="tw-form-label">Standard<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <select name="standard_id" id="standard_id" class="tw-form-control w-full">
            <option value="{{ $standardLink->standard_id }}">{{ $standardLink->standard->name }}</option>
            @foreach($standards as $standard)
              <option value="{{ $standard->id }}">{{ $standard->name }}</option>
            @endforeach 
          </select>
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('standard_id')}}</span>
      </div>
    </div>

    <div class="tw-form-group w-1/2">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="section_id" class="tw-form-label">Section<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <select name="section_id" id="section_id" class="tw-form-control w-full">
            <option value="{{ $standardLink->section_id }}">{{ $standardLink->section->name }}</option>
            @foreach($sections as $section)
              <option value="{{ $section->id }}">{{ $section->name }}</option>
            @endforeach 
          </select>
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('section_id')}}</span>
      </div>
    </div>
  </div>

  <div class="flex"> 
    <div class="tw-form-group w-1/4">
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="no_of_students" class="tw-form-label">No Of Students<span class="text-red-500">*</span></label>
        </div>
        <div class="mb-2">
          <input type="text" name="no_of_students" id="no_of_students" class="tw-form-control w-full" value="{{ $standardLink->no_of_students }}">
        </div>
        <span class="text-red-500 text-xs font-semibold">{{$errors->first('no_of_students')}}</span>
      </div>
    </div>
  </div>
 
  <div class="my-6">
    <input type="submit" value="Submit" name="submit" class="btn btn-primary submit-btn cursor-pointer">
  </div>
</form>