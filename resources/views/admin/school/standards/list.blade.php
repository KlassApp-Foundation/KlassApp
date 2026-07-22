{{-- SPDX-License-Identifier: MIT --}}
@php $headers = ['Standard Name', 'Status', 'Actions']; @endphp
<div class="relative">
   <x-table :headers="$headers" hover>
      @forelse($standards as $standard)
         <tr>
            <td data-label="Standard Name" class="font-medium">{{ $standard->name }}</td>
            <td data-label="Status">
               @if($standard->status == 1)
                  <span class="dt-badge dt-badge-active">
                     <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                     Active
                  </span>
               @else
                  <span class="dt-badge dt-badge-inactive">
                     <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                     Inactive
                  </span>
               @endif
            </td>
            <td data-label="Actions" class="flex items-center gap-2">
               <a href="#" rel="{{ url('/admin/standard/updateStatus/'.$standard->id) }}" class="ds-btn ds-btn-ghost ds-btn-sm status">{{ $standard->status == 1 ? 'Deactivate' : 'Activate' }}</a>
               <a href="#" rel="{{ url('/admin/standard/delete/'.$standard->id) }}" class="ds-btn ds-btn-danger ds-btn-sm delete">Delete</a>
            </td>
         </tr>
      @empty
         <tr>
            <td colspan="3" class="text-center text-gray-400 py-8">No standards found.</td>
         </tr>
      @endforelse
   </x-table>
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