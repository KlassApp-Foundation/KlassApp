{{-- SPDX-License-Identifier: MIT --}}
{{-- Search — inline, server-side GET, debounced via form submit --}}
<form method="GET" action="{{ route('admin.subjects') }}" class="mb-4">
    <div class="ds-card">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="ds-label">Search name or code</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subjects..." class="ds-form-input" autocomplete="off">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="ds-btn ds-btn-primary ds-btn-sm">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.subjects') }}" class="ds-btn ds-btn-ghost ds-btn-sm">Clear</a>
                @endif
            </div>
        </div>
    </div>
</form>

<div class="relative">
   @if(count($subject) != 0)
   @php $headers = ['Subject Name', 'Class', 'Subject Code', 'Type', 'Actions']; @endphp
   <x-table :headers="$headers" hover>
      @foreach($subject as $subjects)
         <tr>
            <td data-label="Subject Name" class="font-medium">{{ $subjects->name }}</td>
            <td data-label="Class">{{ $subjects->section->name ?? '-' }}</td>
            <td data-label="Subject Code"><code>{{ $subjects->code }}</code></td>
            <td data-label="Type">{{ $subjects->type ? $subjects->type : "-" }}</td>
            <td data-label="Actions" class="flex items-center gap-2">
               <x-button href="{{ route('admin.subjects.edit', $subjects) }}" variant="ghost" size="sm">Edit</x-button>
               <form action="{{ route('admin.subject.destroy', $subjects->id) }}" method='POST' class='inline'>
                  @csrf
                  @method("DELETE")
                  <x-button type="submit" variant="danger" size="sm">Delete</x-button>
               </form>
            </td>
         </tr>
      @endforeach
   </x-table>
   @else
      <x-card padding="lg" class="text-center">
         <p class="text-gray-400 text-sm">{{ request('search') ? 'No subjects match your search.' : 'No subjects created yet.' }}</p>
      </x-card>
   @endif

   {{-- Archived subjects --}}
   @if(isset($archievedSubjects) && count($archievedSubjects) > 0)
   <div class="mt-6">
      <h3 class="ds-card-title">Archived Subjects</h3>
      @php $archivedHeaders = ['Subject Name', 'Class', 'Subject Code', 'Type', 'Actions']; @endphp
      <x-table :headers="$archivedHeaders">
         @foreach($archievedSubjects as $subjects)
            <tr>
               <td data-label="Subject Name">{{ $subjects->name }}</td>
               <td data-label="Class">{{ $subjects->standardlink->standard->name ?? '-' }} - {{ $subjects->standardlink->section->name ?? '' }}</td>
               <td data-label="Subject Code"><code>{{ $subjects->code }}</code></td>
               <td data-label="Type">{{ $subjects->type ? $subjects->type : "-" }}</td>
               <td data-label="Actions" class="flex items-center gap-2">
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
      </x-table>
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
