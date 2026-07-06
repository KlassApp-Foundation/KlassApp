{{-- SPDX-License-Identifier: MIT --}}
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

<div class="ds-page-head">
    <div>
        <h1 class="ds-page-head-title">Subjects</h1>
        <p class="ds-page-head-sub">Browse and manage all subjects across classes.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-button href="{{ url('/admin/subjects/create') }}" variant="success" size="sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Add Subject
        </x-button>
    </div>
</div>

<div class="relative mt-4">
   @if(count($subject) != 0)
   <div class="ds-table-wrap">
      <table class="ds-table w-full">
         <thead>
            <tr>
               <th>Subject Name</th>
               <th>Class</th>
               <th>Subject Code</th>
               <th>Type</th>
               <th>Actions</th>
            </tr>
         </thead>
         <tbody>
            @foreach($subject as $subjects)
               <tr>
                  <td class="font-medium">{{ $subjects->name }}</td>
                  <td>{{ $subjects->section->name ?? '-' }}</td>
                  <td><code>{{ $subjects->code }}</code></td>
                  <td>{{ $subjects->type ? $subjects->type : "-" }}</td>
                  <td class="flex items-center gap-2">
                     <x-button href="{{ route('admin.subjects.edit', $subjects) }}" variant="ghost" size="sm">Edit</x-button>
                     <form action="{{ route('admin.subject.destroy', $subjects->id) }}" method='POST' class='inline'>
                        @csrf
                        @method("DELETE")
                        <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                     </form>
                  </td>
               </tr>
            @endforeach
         </tbody>
      </table>
   </div>
   @else
      <x-card padding="lg" class="text-center">
         <p class="text-gray-400 text-sm">No subjects created yet.</p>
      </x-card>
   @endif

   {{-- Archived subjects --}}
   @if(isset($archievedSubjects) && count($archievedSubjects) > 0)
   <div class="mt-6">
      <h3 class="ds-card-title">Archived Subjects</h3>
      <div class="ds-table-wrap">
         <table class="ds-table w-full opacity-75">
            <thead>
               <tr>
                  <th>Subject Name</th>
                  <th>Class</th>
                  <th>Subject Code</th>
                  <th>Type</th>
                  <th>Actions</th>
               </tr>
            </thead>
            <tbody>
               @foreach($archievedSubjects as $subjects)
                  <tr class="bg-gray-100">
                     <td>{{ $subjects->name }}</td>
                     <td>{{ $subjects->standardlink->standard->name ?? '-' }} - {{ $subjects->standardlink->section->name ?? '' }}</td>
                     <td><code>{{ $subjects->code }}</code></td>
                     <td>{{ $subjects->type ? $subjects->type : "-" }}</td>
                     <td class="flex items-center gap-2">
                        <form action="{{ route('admin.subject.restore', $subjects->id) }}" method='POST' class='inline'>
                           @csrf
                           <x-button type="submit" variant="outline" size="sm">Restore</x-button>
                        </form>
                        <form action="{{ route('admin.subject.force-delete', $subjects->id) }}" method='POST' class='inline'>
                           @csrf
                           @method("DELETE")
                           <x-button type="submit" variant="danger" size="sm">Delete Completely</x-button>
                        </form>
                     </td>
                  </tr>
               @endforeach
            </tbody>
         </table>
      </div>
   </div>
   @endif
</div>

@push('scripts')
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script type="text/javascript">
   $(document).ready(function(){
      $('.delete').on('click', function(){
         var link = $(this).attr('rel');
         swal({
            icon: "info",
            title: "Are you sure you want to delete subject?",
            buttons: ["Cancel", "Yes"],
            dangerMode: true,
         }).then((willDelete) => {
            if(willDelete){ window.location = link; }
         });
      });
   });
</script>
@endpush
</div>
