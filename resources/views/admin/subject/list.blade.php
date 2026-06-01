{{-- SPDX-License-Identifier: MIT --}}
<div class="relative">
   <div class="flex flex-row justify-between px-8">
      <table class="w-full">
         <caption><h1 class="admin-h1 mb-6">Subject Details</h1></caption>
         <thead class="bg-grey-light">
            <tr class="border-t-2 border-b-2">
               <th class="text-left text-sm px-2 py-2 text-grey-darker border border-gray-400">Subject Name</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker border border-gray-400">Class</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker border border-gray-400">Subject Code</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker border border-gray-400">Type</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker border border-gray-400">Actions</th>
            </tr>
         </thead>
         @if(count($subject) != 0)
            <tbody class="bg-grey-light">
               
               @foreach($subject as $subjects)
                  <tr class="border-t-2 border-b-2">    
                     <td class="py-3 px-2 border border-gray-400">{{ $subjects->name }}</td>
                     <td class="py-3 px-2 border border-gray-400">{{$subjects->section->name}}</td>
                     <td class="py-3 px-2 border border-gray-400">{{ $subjects->code }}</td>
                     <td class="py-3 px-2 border border-gray-400">
                        {{ $subjects->type ? $subjects->type : "-" }}
                     </td>

                      <td class="py-3 px-2 flex items-center justify-center gap-4 border border-gray-400">
                        <a href="{{route('admin.subjects.edit', $subjects)}}" class="capitalize text-white blue-bg rounded px-2 py-1 ml-2 font-medium">Edit</a>
                        <form action="{{route("admin.subject.destroy", $subjects->id)}}" method='POST' class='inline'>
                           @csrf
                           @method("DELETE")
                           <button type="submit"
                             class="capitalize text-white bg-red-600 rounded px-2 py-1 font-medium">
                                 Delete
                             </button>
                        </form>
                     </td>
                  </tr>
               @endforeach
            </tbody>

            {{-- test deleted subjects --}}
            <thead>
               <tr>
                  <th class='py-4 text-lg text-gray-700 font-semibold text-center'>Archieved subjects</th>
               </tr>
            </thead>
             <tbody class="bg-grey-light text-gray-500">
               @foreach($archievedSubjects as $subjects)
                  <tr class="border-t-2 border-b-2">    
                     {{-- {{ dd($subjects->standardlink) }} --}}
                     <td class="py-3 px-2 bg-gray-300 border border-gray-400">{{ $subjects->name }}</td>
                     <td class="py-3 px-2 bg-gray-300 border border-gray-400 ">{{ $subjects->standardlink->standard->name}} - {{$subjects->standardlink->section->name  }}</td>
                     <td class="py-3 px-2 bg-gray-300 border border-gray-400">{{ $subjects->code }}</td>
                     <td class="py-3 px-2 bg-gray-300 border border-gray-400">
                        {{ $subjects->type ? $subjects->type : "-" }}
                     </td>

                      <td class="py-3 px-2 flex items-center justify-center gap-4 bg-gray-300 border border-gray-400">
                        <form action="{{route("admin.subject.restore", $subjects->id)}}" method='POST'>
                           @csrf
                           <button class="capitalize text-white blue-bg rounded px-2 py-1 font-medium">
                           Restore
                        </button>
                        </form>
                        <form action="{{route("admin.subject.force-delete", $subjects->id)}}" method='POST' class='inline'>
                           @csrf
                           @method("DELETE")
                           <button type="submit"
                             class="capitalize text-white bg-red-600 rounded px-2 py-1 font-medium">
                                 Delete Completely
                             </button>
                        </form>
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