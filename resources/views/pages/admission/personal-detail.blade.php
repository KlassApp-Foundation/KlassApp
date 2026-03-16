{{-- SPDX-License-Identifier: MIT --}}
<fieldset class="shadow">
    <!-- <h2 class="fs-title">Social Profiles</h2> -->
    <!-- ***medical*** -->
    <div class="my-1">
    <h2 class="text-lg">Medical History</h2>
    <div class="flex flex-col lg:flex-row lg:items-center">
    <label for="name" class="tw-form-label">Is the Student having any Health issues:</label>
   <div class="flex py-2 lg:mx-5">
   <div class="w-full lg:w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
   <input type="radio" name="m1" value="h1" onclick="show4();"> 
   <span class="text-sm mx-2">Yes</span></div>
   <div class="w-1/4 flex items-center ">
   <input type="radio" name="m1" value="h2" onclick="show3();">
   <span class="text-sm mx-2">No</span>
   </div>
   </div>
   </div>
     <!-- *** -->
     <div id="health-issue" class="hidden">
        <ul class="list-reset leading-loose">
          <li class="flex items-center my-1">
            <input type="checkbox" id="Asthama" name="Asthama" value="Asthama">
            <label for="Asthama" class="tw-form-label mx-2">Asthama</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Dizziness" name="Dizziness" value="Dizziness">
            <label for="Dizziness" class="tw-form-label mx-2">Fainting / Dizziness</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Migraines" name="Migraines" value="Migraines">
            <label for="Migraines" class="tw-form-label mx-2">Migraines</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Intellectual Disability" name="Intellectual Disability" value="Intellectual Disability">
            <label for="Intellectual Disability" class="tw-form-label mx-2">Intellectual Disability</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Fits" name="Fits" value="Fits">
            <label for="Fits" class="tw-form-label mx-2">Fits</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Hear Problem " name="Hear Problem " value="Hear Problem ">
            <label for="Hear Problem " class="tw-form-label mx-2">Hear Problem </label>
          </li>
           <li class="flex items-center my-1">
            <input type="checkbox" id="Diabetics" name="Diabetics" value="Diabetics">
            <label for="Diabetics" class="tw-form-label mx-2">Diabetics</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Recent Injuries" name="Recent Injuries" value="Recent Injuries">
            <label for="Recent Injuries" class="tw-form-label mx-2">Recent Injuries</label>
          </li>
           <li class="flex items-center my-1">
            <input type="checkbox" id="Others" name="Others" value="Others">
            <label for="Others" class="tw-form-label mx-2">Others</label>
          </li>
        </ul>
     </div>
     <!-- *** -->
    </div>
    <!-- ***medical*** -->
     <!-- ***Extra Curricular*** -->
    <div class="my-1">
    <h2 class="text-lg mt-4">Extra Curricular</h2>
    <div class="flex flex-col lg:flex-row lg:items-center">
    <label for="name" class="tw-form-label">Is the student is in any extra co – curricular activities :</label>
   <div class="flex py-2 lg:mx-5">
   <div class="w-full lg:w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
   <input type="radio" name="e1" value="h1" onclick="show6();"> 
   <span class="text-sm mx-2">Yes</span></div>
   <div class="w-1/4 flex items-center ">
   <input type="radio" name="e1" value="h2" onclick="show5();">
   <span class="text-sm mx-2">No</span>
   </div>
   </div>
   </div>
     <!-- *** -->
     <div id="extra-curricular" class="hidden">
        <ul class="list-reset leading-loose">
          <li class="flex items-center my-1">
            <input type="checkbox" id="Vocal" name="Vocal" value="Vocal">
            <label for="Vocal" class="tw-form-label mx-2">Vocal</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Guitar" name="Guitar" value="Guitar">
            <label for="Guitar" class="tw-form-label mx-2">Guitar</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Mridangam" name="Mridangam" value="Mridangam">
            <label for="Mridangam" class="tw-form-label mx-2">Mridangam</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Violin" name="Violin" value="Violin">
            <label for="Violin" class="tw-form-label mx-2">Violin</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Karate" name="Karate" value="Karate">
            <label for="Karate" class="tw-form-label mx-2">Karate</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Chess" name="Chess" value="Chess">
            <label for="Chess" class="tw-form-label mx-2">Chess</label>
          </li>
           <li class="flex items-center my-1">
            <input type="checkbox" id="Bharatham" name="Bharatham" value="Bharatham">
            <label for="Bharatham" class="tw-form-label mx-2">Bharatham</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Art & Craft" name="Art & Craft" value="Art & Craft">
            <label for="Art & Craft" class="tw-form-label mx-2">Art & Craft</label>
          </li>
           <li class="flex items-center my-1">
            <input type="checkbox" id="Embroidery" name="Embroidery" value="Embroidery">
            <label for="Embroidery" class="tw-form-label mx-2">Embroidery</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Skating" name="Skating" value="Skating">
            <label for="Skating" class="tw-form-label mx-2">Skating</label>
          </li>
           <li class="flex items-center my-1">
            <input type="checkbox" id="Keyboard" name="Keyboard" value="Keyboard">
            <label for="Keyboard" class="tw-form-label mx-2">Keyboard</label>
          </li>
          <li class="flex items-center my-1">
            <input type="checkbox" id="Drums" name="Drums" value="Drums">
            <label for="Drums" class="tw-form-label mx-2">Drums</label>
          </li>
        </ul>
     </div>
     <!-- *** -->
    </div>
    <!-- ***Extra Curricular*** -->

      <!-- ***transport*** -->
    <div class="my-1">
    <h2 class="text-lg mt-4">Mode of Transport</h2>
    <div class="">
   <div class="flex flex-col py-2">
   <div class="flex items-center my-1">
   <input type="radio" name="e1" value="t1"> 
   <span class="text-sm mx-2">School Van</span>
   </div>
   <div class="flex items-center my-1">
   <input type="radio" name="e1" value="t2" onclick="show7();">
   <span class="text-sm mx-2">Private Vehicle </span>
   </div>
   <div class="flex items-center my-1">
   <input type="radio" name="e1" value="t3">
   <span class="text-sm mx-2">Cycle</span>
   </div>
   <div class="flex items-center my-1">
   <input type="radio" name="e1" value="t4">
   <span class="text-sm mx-2">By Walk</span>
   </div>
   <div class="flex items-center my-1">
   <input type="radio" name="e1" value="t5">
   <span class="text-sm mx-2">Drop by Parents</span>
   </div>
   </div>
   </div>
     <!-- *** -->
     <div id="transport" class="hidden">
     <div class="flex flex-col lg:flex-row">
          <div class="w-full lg:w-1/2 my-1 mr-2">
           <label for="name" class="tw-form-label">Driver's Name</label>
            <input type="text"  name="aadhar" placeholder="" class="tw-form-control w-full my-1 py-2">
          </div>
           <div class="w-full lg:w-1/2 my-1 mr-2">
           <label for="name" class="tw-form-label">Phone Number</label>
            <input type="text"  name="aadhar" placeholder="" class="tw-form-control w-full my-1 py-2">
          </div>
      </div>  
     </div>
     <!-- *** -->
    </div>
    <!-- ***transport*** -->
    <input type="button" name="previous" class="previous action-button my-2" value="Previous" />
    <input type="submit" name="submit" class="submit action-button my-2 mx-2" value="Submit" />
  </fieldset>
@push(scripts)
<script>
  function show3(){
  document.getElementById('health-issue').style.display ='none';
}
function show4(){
  document.getElementById('health-issue').style.display = 'block';
}
</script>
<script>
  function show5(){
  document.getElementById('extra-curricular').style.display ='none';
}
function show6(){
  document.getElementById('extra-curricular').style.display = 'block';
}
</script>
<script>
function show7(){
  document.getElementById('transport').style.display = 'block';
}
</script>
@endpush