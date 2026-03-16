{{-- SPDX-License-Identifier: MIT --}}
<div class="relative">
   <div class="flex flex-row justify-between custom-table">
      <table class="w-1/2">
         <caption><h1 class="admin-h1 mb-6">Standards History</h1></caption>
         <thead class="bg-grey-light">
            <tr class="border-t-2 border-b-2">
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Standard Name</th>

               <th class="text-left text-sm px-2 py-2 text-grey-darker">Status</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Actions</th>

            </tr>
         </thead>
         @if(count($standards) != 0)
            <tbody class="bg-grey-light">
               @foreach($standards as $standard)
                  <tr class="border-t-2 border-b-2">    
                     <td class="py-3 px-2">{{ $standard->name }}</td>
                     <td class="py-3 px-2">
                        @if( $standard->status == 1)
                           <a href="#" rel="{{ url('/admin/standard/updateStatus/'.$standard->id) }}" class=" status" value="0"><img src="{{asset('uploads/icons/actions/tick.svg')}}" class="w-5 h-5"></a>
                        @else
                           <a href="#" rel="{{ url('/admin/standard/updateStatus/'.$standard->id) }}" class="status" value="1"><img src="{{asset('uploads/icons/actions/close.svg')}}" class="w-5 h-5"></a>
                        @endif
                     </td>
                     <td class="py-3 px-2">
                        <a href="#" rel="{{url('/admin/standard/delete/'.$standard->id)}}" class="delete"><img src="{{asset('uploads/icons/actions/trash.svg')}}" class="w-5 h-5 ml-1"></a>
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
      $('.status').on('click', function(){
         var link = $(this).attr('rel');
         var status = $(this).attr('value');
         swal({
            icon: "info",
            text: "Do you want to change the status ?",
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
                  data: { status: status },
                  type: "POST",
                  headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                  success:function(data)
                  {
                     swal({
                        icon: "success",
                        text: "Standard Status Updated Successfully",
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

   $(document).ready(function(){
      $('.delete').on('click', function(){
         var link = $(this).attr('rel');
         swal({
            icon: "info",
            text: "Do you want to delete this Standard ?",
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
                        text: "Standard Deleted Successfully",
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