{{-- SPDX-License-Identifier: MIT --}}
 <div class="flex flex-col lg:flex-row md:flex-row">
    <div class="w-full lg:w-1/3 my-3 lg:my-0 md:my-0 px-1 dashboard-task">
      <div class="bg-white px-3 py-2 my-3">
        <div class="border-b mx-2 py-1 pb-3 flex flex-col lg:flex-row md:flex-row  lg:items-center md:items-center justify-between">
          <div>
            <h1 class="text-gray-800 font-semibold text-lg ">Tasks</h1>
          </div>
         
            <div class="flex items-center">
            <div class="flex items-center">
             <form method="GET" action="{{ url('/receptionist/dashboard') }}" role="search" enctype="multipart/form-data">
               <div class="relative">
                <input type="text" name="q" class="px-2 py-1 border rounded  text-xs" placeholder="Search" value="{{ old('q') }}">
                  <button class="no-underline text-white  px-4  mx-1  py-1 absolute right-0 focus:outline-none">
          <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30.239px" height="30.239px" viewBox="0 0 30.239 30.239" xml:space="preserve" class="w-4 h-4 fill-current text-gray-600 absolute right-0 mt-2 mx-1 top-0"><g><path d="M20.194,3.46c-4.613-4.613-12.121-4.613-16.734,0c-4.612,4.614-4.612,12.121,0,16.735
    c4.108,4.107,10.506,4.547,15.116,1.34c0.097,0.459,0.319,0.897,0.676,1.254l6.718,6.718c0.979,0.977,2.561,0.977,3.535,0
    c0.978-0.978,0.978-2.56,0-3.535l-6.718-6.72c-0.355-0.354-0.794-0.577-1.253-0.674C24.743,13.967,24.303,7.57,20.194,3.46z
     M18.073,18.074c-3.444,3.444-9.049,3.444-12.492,0c-3.442-3.444-3.442-9.048,0-12.492c3.443-3.443,9.048-3.443,12.492,0
    C21.517,9.026,21.517,14.63,18.073,18.074z"></path></g></svg>
       
          </button>
              </div>
               </form>
              
              </div>


              <div>
              <add-task-popup mode="receptionist">
             
                </add-task-popup>
              </div>


            </div>
         
        </div>
        <!-- list tab -->
<list-task-tab url="{{ url('/') }}" mode="receptionist"></list-task-tab>
<portal-target name="list_task"></portal-target>
            <!-- ***** -->
            <!-- ***** -->

           
          </div>
          <!-- ***** -->
        </div>
        <!-- ***** -->
      
      </div>

@push('scripts')
<script> 
  // $(document).ready(function() {
   /*  $("#show_hide").click(function () {
      $( ".form-group mt-5 text-right" ).toggle({
        duration: 3000,
      });
  }); */ 

 // }); 
   function customtoggle(val)
  {

 //$('.task_date').removeClass('hidden').addClass('block');

 if( $('.'+val).hasClass('block'))
 {
  // $('.task_date').removeClass('hidden').addClass('block');
   $('.'+val).addClass('hidden').removeClass('block');

  
 }
 else
 {
   $('.'+val).removeClass('hidden').addClass('block');

  
 }



  }

  function show(val)
  {

   if(val)
   {
    
     $('#completed_button').removeClass('hidden').addClass('block');
    
   }
 }
</script>
@endpush