{{-- SPDX-License-Identifier: MIT --}}
<div class="relative">
   <div class="flex flex-row justify-between">
      <table class="w-full">
         <caption><h1 class="admin-h1 mb-6">Subject Details</h1></caption>
         <thead class="bg-grey-light">
            <tr class="border-t-2 border-b-2">
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Subject Name</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Class</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Subject Code</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Type</th>
               <th colspan="3">Actions</th>
            </tr>
         </thead>
         @if(count($subject) != 0)
            <tbody class="bg-grey-light">
               @foreach($subject as $subjects)
                  <tr class="border-t-2 border-b-2">    
                     <td class="py-3 px-2">{{ $subjects->name }}</td>
                     <td class="py-3 px-2">{{ $subjects->standardlink->standard->name}} - {{$subjects->standardlink->section->name}}</td>
                     <td class="py-3 px-2">{{ $subjects->code }}</td>
                     <td class="py-3 px-2">{{ $subjects->type }}</td>

                      <td class="py-3 px-2">
                        <a href="{{url('/admin/subjects/edit/'.$subjects->id)}}" class="capitalize text-white blue-bg rounded px-2 py-1 ml-2 font-medium">Edit</a>
                     </td>
                     <td class="py-3 px-2">
                        <a href="#" rel="{{url('/admin/subjects/delete/'.$subjects->id)}}" class="capitalize text-white bg-red-600 rounded px-2 py-1 font-medium delete">Delete</a>
                     </td> 
                    
                
                  </tr>

                 
               @endforeach
            </tbody>
         @else
            <tbody class="bg-grey-light">
               <tr class="border-t-2 border-b-2">    
                  <td colspan="5" class="py-3 px-2"><p class="font-semibold text-s" style="text-align: center">No Records Found</p></td>
               </tr>
            </tbody>
         @endif
      </table>      
   </div>
</div>



@push('scripts')

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script type="text/javascript">


   $(document).ready(function(){
      $('.delete').on('click', function(){
         var link = $(this).attr('rel');
         swal({
            icon: "info",
            text: "Do you want to delete this Exam ?",
            buttons: {
               cancel: true,
               confirm: true,
            },
            allowOutsideClick: false,
         }).then((willChange) => {
            if (willChange) 
            {
               $.ajax({
                  url: link,
                  type: "GET",
                  headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                  success:function(data)
                  {
                     swal({
                        icon: "success",
                        text: "Exam Deleted Successfully",
                     }).then(function(){
                        window.location.reload();
                     });
                  }
               })
            }
            else 
            {
               swal("Cancelled");
            } 
         });
      });
   });
</script>

@endpush 