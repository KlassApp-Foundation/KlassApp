{{-- SPDX-License-Identifier: MIT --}}
<fieldset class="shadow">
    <!-- <h2 class="fs-title">Social Profiles</h2> -->
    <!-- *** -->

    <div class="my-1">
     <!--   <h2 class="text-lg py-2 border-b">Academic Detail</h2> -->
       <h6 class="text-sm font-bold mb-3">Half Yearly Mark Details</h6>
     <div class="my-1 w-full lg:w-1/2 lg:mr-2 flex items-center">
    <label for="name" class="tw-form-label w-24">Engligh</label>
    <input type="text"  name="address" placeholder="" class="tw-form-control my-1 py-2">
    </div>
    <div class="my-1 w-full lg:w-1/2 lg:mr-2 flex items-center">
    <label for="name" class="tw-form-label w-24">Tamil</label>
    <input type="text"  name="address" placeholder="" class="tw-form-control my-1 py-2">
    </div>
    <div class="my-1 w-full lg:w-1/2 lg:mr-2 flex items-center">
    <label for="name" class="tw-form-label w-24">Maths</label>
    <input type="text"  name="address" placeholder="" class="tw-form-control my-1 py-2">
    </div>
    <div class="my-1 w-full lg:w-1/2 lg:mr-2 flex items-center">
    <label for="name" class="tw-form-label w-24">Science</label>
    <input type="text"  name="address" placeholder="" class="tw-form-control my-1 py-2">
    </div>
    <div class="my-1 w-full lg:w-1/2 lg:mr-2 flex items-center">
    <label for="name" class="tw-form-label w-24">Social</label>
    <input type="text"  name="address" placeholder="" class="tw-form-control my-1 py-2">
    </div>
    </div>
    <div class="flex flex-col lg:flex-row">
       <div class="w-full lg:w-1/2 my-1">
       <h6 class="text-sm font-bold mb-3">Board of Study</h6>
        <ul class="list-reset leading-loose">
          <li>
            <input type="radio" name="s1" value="s1"> 
            <span class="text-sm mx-2">CBSE</span>
          </li>
            <li>
            <input type="radio" name="s2" value="s2"> 
            <span class="text-sm mx-2">Matric</span>
          </li>
           <li>
            <input type="radio" name="s3" value="s3"> 
            <span class="text-sm mx-2">ICSE</span>
          </li>
           <li>
            <input type="radio" name="s4" value="s4"> 
            <span class="text-sm mx-2">State Board </span>
          </li>
           <li>
            <input type="radio" name="s5" value="s5"> 
            <span class="text-sm mx-2">Anglo Indian</span>
          </li>
            <li>
            <input type="radio" name="s6" value="s6"> 
            <span class="text-sm mx-2">Others</span>
          </li>
        </ul>
    </div>
     <div class="w-full lg:w-1/2 my-1">
       <h6 class="text-sm font-bold mb-3">Choice of Language</h6>
        <ul class="list-reset leading-loose">
          <li>
            <input type="radio" name="l1" value="l1"> 
            <span class="text-sm mx-2">Tamil </span>
          </li>
            <li>
            <input type="radio" name="l2" value="l2"> 
            <span class="text-sm mx-2">Hindi</span>
          </li>
           <li>
            <input type="radio" name="l3" value="l3"> 
            <span class="text-sm mx-2">Sanskri</span>
          </li>
           <li>
            <input type="radio" name="l4" value="l4"> 
            <span class="text-sm mx-2">French</span>
          </li>
        </ul>
    </div>
    </div>
        <div class="my-1">
       <h6 class="text-sm font-bold mb-3">Group selection</h6>
        <ul class="list-reset leading-loose">
          <li>
            <input type="radio" name="g1" value="g1"> 
            <span class="text-sm mx-2">Group 1     :  Maths, Physics, Chemistry &Computer Science </span>
          </li>
            <li>
            <input type="radio" name="g2" value="g2"> 
            <span class="text-sm mx-2">Group 2     :    Maths, Physics, Chemistry & Biology</span>
          </li>
           <li>
            <input type="radio" name="g3" value="g3"> 
            <span class="text-sm mx-2">Group 3   :    Physics, Chemistry, Biology & Computer Science</span>
          </li>
           <li>
            <input type="radio" name="g4" value="g4"> 
            <span class="text-sm mx-2">Group 4     :    Commerce, Accountancy, Economics & Business Maths</span>
          </li>
           <li>
            <input type="radio" name="g4" value="g4"> 
            <span class="text-sm mx-2">Group 5     :    Commerce, Accountancy, Economics & Computer Science</span>
          </li>
        </ul>
    </div>
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