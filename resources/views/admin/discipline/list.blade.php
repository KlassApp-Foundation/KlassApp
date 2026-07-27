{{-- SPDX-License-Identifier: MIT --}}
@php $headers = ['Student Name', 'Teacher Name', 'Type', 'Date', 'Detail', 'Notify Parents', 'Actions']; @endphp
<div class="custom-table overflow-x-auto">
   <x-table :headers="$headers" hover>
      @forelse($disciplines as $discipline)
         <tr>
            <td data-label="Student Name">
               <a href="{{ url('/admin/student/show/'.$discipline->user->name) }}" class="font-medium text-gray-900 hover:text-blue-600 transition-colors">{{ ucfirst($discipline->user->FullName) }}</a>
            </td>
            <td data-label="Teacher Name">
               <a href="{{ url('/admin/teacher/show/'.$discipline->teacher->name) }}" class="text-sm text-gray-700 hover:text-blue-600 transition-colors">{{ ucfirst($discipline->teacher->FullName) }}</a>
            </td>
            <td data-label="Type">{{ ucfirst($discipline->type) }}</td>
            <td data-label="Date" class="text-sm text-gray-600">{{ date('Y-m-d H:i:s', strtotime($discipline->incident_date)) }}</td>
            <td data-label="Detail" class="text-sm text-gray-600">{{ str_limit($discipline->incident_detail, 10, '...') }}</td>
            <td data-label="Notify">
               @if($discipline->notify_parents == 1)
                  <a href="#" rel="{{ url('/admin/discipline/updateStatus/'.$discipline->id) }}" class="status">
                     <span class="dt-badge dt-badge-active">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Notified
                     </span>
                  </a>
               @else
                  <a href="#" rel="{{ url('/admin/discipline/updateStatus/'.$discipline->id) }}" class="status">
                     <span class="dt-badge dt-badge-inactive">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Pending
                     </span>
                  </a>
               @endif
            </td>
            <td data-label="Actions">
               <div class="flex items-center gap-2">
                  @if($discipline->type == 'discipline')
                     <a href="{{ url('/admin/discipline/edit/'.$discipline->id) }}" class="ds-btn ds-btn-ghost ds-btn-sm" title="Edit">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                     </a>
                  @endif
                  <a href="{{ url('/admin/discipline/show/'.$discipline->id) }}" class="ds-btn ds-btn-ghost ds-btn-sm" title="View">
                     <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <a href="#" rel="{{ url('/admin/discipline/delete/'.$discipline->id) }}" class="ds-btn ds-btn-ghost ds-btn-sm delete" title="Delete">
                     <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </a>
               </div>
            </td>
         </tr>
      @empty
         <tr>
            <td colspan="7" class="text-center text-gray-400 py-8">No discipline records found.</td>
         </tr>
      @endforelse
   </x-table>
</div>