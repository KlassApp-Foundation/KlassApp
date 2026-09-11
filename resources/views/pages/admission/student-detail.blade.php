{{-- SPDX-License-Identifier: MIT --}}
<fieldset class="shadow">
    <!-- <h2 class="fs-title">Social Profiles</h2> -->
    <!-- ** -->
    <div class="">
     <label for="name" class="tw-form-label">Name of the Student</label>
     <div class="flex flex-col lg:flex-row">
     <div class="my-1 w-full lg:w-1/3 lg:mr-2">
    <input type="text"  name="firstname" placeholder="" class="tw-form-control w-full my-1 py-2">
    </div>
    <div class="my-1 w-full lg:w-1/3 lg:mr-2">
    <input type="text"  name="middlename" placeholder="" class="tw-form-control w-full my-1 py-2">
    </div>
    <div class="my-1 w-full lg:w-1/3 lg:mr-2">
    <input type="text"  name="lastname" placeholder="" class="tw-form-control w-full my-1 py-2">
    </div>
    </div>
    </div>
    <!-- ** -->
    <!-- ** -->
    <div class="flex flex-col lg:flex-row">
    <div class="w-full lg:w-2/3 ">
    <div class="flex flex-col lg:flex-row">
    <div class="w-full lg:w-1/2 lg:mr-2">
    <div class="my-1">
    <label for="name" class="tw-form-label">Date of birth</label>
    <input type="date"  name="firstname" placeholder="Student Name" class="tw-form-control w-full my-1 py-2">
    </div>
    </div>
       <div class="w-full lg:w-1/2 lg:mr-2">
     <div class="my-1">
    <label for="name" class="tw-form-label">Gender</label>
   <div class="flex tw-form-control py-2 my-1">
   <div class="w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
   <input type="radio" name="gender" id="gender1" value="male"> 
   <span class="text-sm mx-2">Boy</span></div>
    <div class="w-1/4 flex items-center ">
    <input type="radio" name="gender" id="gender2" value="female">
     <span class="text-sm mx-2">Girl</span>
     </div>
     </div>
    </div>
    </div>
    </div>
    </div>
    <!-- *** -->
    </div>
    <div class="w-full lg:w-1/3">
      <div class="relative w-10/12 mx-auto my-2">
       <label for="name" class="tw-form-label">Attach Photo</label>
        <input type='file' onchange="readURL(this);" class="student-avatar cursor-pointer">
        <img id="blah" class="student-img text-sm border border-dashed border-gray-300 my-2" src="http://placehold.it/180">
      </div>
    </div>
    </div>
    <!-- ** -->

    <!-- ** -->
    <div class="">
     <label for="name" class="tw-form-label">Identification Marks</label>
     <div class="flex flex-col lg:flex-row">
     <div class="my-1 w-full lg:w-1/2 lg:mr-2">
    <input type="text"  name="m1" placeholder="" class="tw-form-control w-full my-1 py-2">
    </div>
    <div class="my-1 w-full lg:w-1/2 lg:mr-2">
    <input type="text"  name="m2" placeholder="" class="tw-form-control w-full my-1 py-2">
    </div>
    </div>
    </div>
    <!-- ** -->
       <!-- *** -->
    <div class="flex flex-col lg:flex-row">
    <div class="w-full lg:w-1/2 lg:mr-2">
    <div class="my-1">
    <label for="name" class="tw-form-label">School last studied</label>
    <input type="text"  name="birthplace" placeholder="" class="tw-form-control w-full my-1 py-2">
    </div>
    </div>
       <div class="w-full lg:w-1/2 lg:mr-2">
      <div class="my-1">
    <label for="name" class="tw-form-label">Reason for leaving</label>
    <input type="text"  name="leaving_reason" placeholder="" class="tw-form-control w-full my-1 py-2">
    </div>
    </div>
    </div>
    <!-- *** -->
    <!-- ** -->
    <div class="flex flex-col lg:flex-row ">
    <div class="my-1 w-full lg:w-1/2 lg:mr-2">
    <label for="name" class="tw-form-label">Address : Permanent</label>
    <textarea type="text"  name="address" placeholder="" class="tw-form-control w-full my-1 py-2"></textarea>
    </div>
    <div class="my-1 w-full lg:w-1/2 lg:mr-2">
    <label for="name" class="tw-form-label">Address : for communication</label>
    <textarea type="text"  name="address" placeholder="" class="tw-form-control w-full my-1 py-2"></textarea>
    </div>
    </div>
    <!-- ** -->
     <!-- *** -->
       <div class="my-1">
    <div class="flex flex-col lg:flex-row lg:items-center">
    <label for="name" class="tw-form-label">Is the sibling studying in the same school:</label>
   <div class="flex py-2 my-1 lg:mx-5">
   <div class="w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
   <input type="radio" name="tab" value="igotnone" onclick="show2();"> 
   <span class="text-sm mx-2">Yes</span></div>
   <div class="w-1/4 flex items-center ">
   <input type="radio" name="tab" value="igottwo" onclick="show1();">
   <span class="text-sm mx-2">No</span>
   </div>
   </div>
   </div>
     <!-- *** -->
     <div id="sibling" class="hidden">
       <input type="text" name="m1" placeholder="" class="tw-form-control w-full my-1 py-2">
     </div>
     <!-- *** -->
    </div>
    <!-- *** -->

    
    <input type="button" name="previous" class="previous action-button my-2" value="Previous" />
    <input type="button" name="next" class="next action-button my-2 mx-2" value="Next" />
  </fieldset>

@push(scripts)
<script>
  function show1(){
  document.getElementById('sibling').style.display ='none';
}
function show2(){
  document.getElementById('sibling').style.display = 'block';
}
</script>
@endpush