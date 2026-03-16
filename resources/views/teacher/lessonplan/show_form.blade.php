{{-- SPDX-License-Identifier: MIT --}}
   <div class="bg-white shadow overflow-x-auto">
   <table class="w-full">
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Class   : </td>
         <td class="py-3 px-2">{{ $lessonplan->teacherlink->standardLink->StandardSection }}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Subject  :  </td>
         <td class="py-3 px-2">{{ $lessonplan->teacherlink->subject->name }}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Unit No.  :  </td>
         <td class="py-3 px-2">{{ $lessonplan->unit_no }}</td>
      </tr> 
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Unit Name : </td>
         <td class="py-3 px-2">{{ $lessonplan->unit_name }}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Title   : </td>
         <td class="py-3 px-2">{{ $lessonplan->title }}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Brief Description  :  </td>
         <td class="py-3 px-2">{{ $lessonplan->description }}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Objective  :  </td>
         <td class="py-3 px-2">{!! $lessonplan->objective !!}</td>
      </tr> 
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Duration : </td>
         <td class="py-3 px-2">{{ date('H',strtotime($lessonplan->duration))== '00' ? date('i',strtotime($lessonplan->duration)).' minutes':date('H',strtotime($lessonplan->duration)).' hours '.date('i',strtotime($lessonplan->duration)).' minutes' }}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Materials Required   : </td>
         <td class="py-3 px-2">{!! $lessonplan->materials_required !!}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Introduction  :  </td>
         <td class="py-3 px-2">{!! $lessonplan->introduction !!}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Procedure  :  </td>
         <td class="py-3 px-2">{!! $lessonplan->procedure !!}</td>
      </tr> 
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Conclusion : </td>
         <td class="py-3 px-2">{!! $lessonplan->conclusion !!}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Assessment   : </td>
         <td class="py-3 px-2">{{ $lessonplan->assessment }}</td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Modification  :  </td>
         <td class="py-3 px-2">
            @if($lessonplan->modification != null)
               {{ $lessonplan->modification }}
            @else
               --
            @endif
         </td>
      </tr>
      <tr class="border-b-2">
         <td class="py-3 px-2 w-1/5">Notes  :  </td>
         <td class="py-3 px-2">
            @if($lessonplan->notes != null)
               {{ $lessonplan->notes }}
            @else
               --
            @endif
         </td>
      </tr> 
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Status  :  </td>
         <td class="py-3 px-2">{{ ucfirst($lessonplan->status) }}</td>
      </tr>  
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Comments  :  </td>
         <td class="py-3 px-2">
            @if($lessonplan->lessonplanapproval != null)
               {{ $lessonplan->lessonplanapproval->comments }}
            @else
               --
            @endif
         </td>
      </tr> 
   </table>
</div>

@if( (\request()->query('status') == 'approve') ||  (\request()->query('status') == 'reject') )
   <approve-lesson-plan url="{{ url('/') }}" id="{{ $lessonplan->id }}" status="{{ \request()->query('status') }}"></approve-lesson-plan>
@endif