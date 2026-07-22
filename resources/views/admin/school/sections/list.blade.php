{{-- SPDX-License-Identifier: MIT --}}
@php $headers = ['Section Name', 'Status', 'Actions']; @endphp
<div class="">
   <x-table :headers="$headers" hover>
      @forelse($sections as $section)
         <tr>
            <td data-label="Section Name" class="font-medium">{{ $section->name }}</td>
            <td data-label="Status">
               @if($section->status == 1)
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
               <a href="#" class="ds-btn ds-btn-ghost ds-btn-sm">Edit</a>
               <form action="{{ route('admin.classes.delete', $section) }}" method='POST' class='inline'>
                  @csrf
                  @method("DELETE")
                  <button type="submit" class="ds-btn ds-btn-danger ds-btn-sm">Delete</button>
               </form>
            </td>
         </tr>
      @empty
         <tr>
            <td colspan="3" class="text-center text-gray-400 py-8">No sections found.</td>
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
                        text: "Section Status Updated Successfully",
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
            text: "Do you want to delete this section ?",
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
                        text: "Section Deleted Successfully",
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