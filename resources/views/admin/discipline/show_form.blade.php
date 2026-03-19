{{-- SPDX-License-Identifier: MIT --}}
<table class="w-full">
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2">Student Name   : </td>
      <td class="py-3 px-2"><a href="{{ url('/admin/student/show/'.$discipline->user->name) }}"> {{ $discipline->user->userprofile->firstname }} {{ $discipline->user->userprofile->lastname }}</a></td>
   </tr>
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2">Teacher Name  :  </td>
      <td class="py-3 px-2"><a href="{{ url('/admin/teacher/show/'.$discipline->teacher->name) }}">{{ $discipline->teacher->userprofile->firstname }} {{ $discipline->teacher->userprofile->lastname }}</a></td>
   </tr>
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2"> Type  :  </td>
      <td class="py-3 px-2">{{ ucfirst($discipline->type) }} </td>
   </tr> 
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2"> Detail  :  </td>
      <td class="py-3 px-2">{{ $discipline->incident_detail }} </td>
   </tr> 
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2"> Date : </td>
      <td class="py-3 px-2">{{ $discipline->incident_date }}</td>
   </tr>
   @if($discipline->type=='discipline')
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2">Action taken : </td>
      <td class="py-3 px-2">
         @if($discipline->action_taken == 1)
            Yes
         @else
            No
         @endif
      </td>
   </tr>
   @endif
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2">Notify Parents  :  </td>
      <td class="py-3 px-2">
         @if($discipline->notify_parents == 1)
            Yes
         @else
            No
         @endif
      </td>
   </tr>
  <!--  <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2">Seen By Parents : </td>
      <td class="py-3 px-2">
         @if($discipline->is_seen == 1)
            Yes
         @else
            No
         @endif
      </td>   
   </tr> -->
   <tr class="border-t-2 border-b-2">
      <td class="py-3 px-2">Attachments : </td>
      <td class="py-3 px-2">
         @if($discipline->attachments == null)
            --
         @else
            <a href="{{ $discipline->AttachmentPath }}" target="_blank">View</a>
         @endif
      </td>   
   </tr>
</table>