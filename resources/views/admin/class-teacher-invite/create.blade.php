{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
   <div class="relative">
      <div class="flex flex-wrap lg:flex-row justify-between">
         <div class="">
            <h1 class="admin-h1 my-3">Invite Class Teacher</h1>
         </div>
         <div class="relative flex items-center w-1/4 lg:justify-end">
            <div class="flex items-center">
               <a href="{{ route('admin.classes') }}" class="ds-btn ds-btn-ghost ds-btn-sm">
                  ← Back to Classes
               </a>
            </div>
         </div>
      </div>

      @include('partials.message')

      <div class="ds-card ds-card-body max-w-2xl">
         <p class="text-gray-600 mb-4">
            Assign a class teacher for <strong>{{ $section->name }}</strong>.
         </p>

         <form method="POST" action="{{ route('admin.class-teacher-invite.store', $standardLink) }}">
            @csrf

            <div class="mb-4">
               <label class="block text-sm font-semibold mb-1">Email</label>
               <input type="email" name="email" value="{{ old('email') }}"
                      class="form-control w-full @error('email') border-red-500 @enderror"
                      placeholder="teacher@school.ug" required>
               @error('email')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
               @enderror
            </div>

            <div class="mb-4">
               <label class="block text-sm font-semibold mb-1">Existing teacher?</label>
               <select name="existing_teacher_id" class="form-control w-full">
                  <option value="">— Create a new teacher —</option>
                  @foreach($existingTeachers as $teacher)
                     <option value="{{ $teacher->id }}"
                        {{ old('existing_teacher_id') == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }} ({{ $teacher->email }})
                     </option>
                  @endforeach
               </select>
               <p class="text-gray-500 text-xs mt-1">If you choose an existing teacher, only the email reassignment notice is sent.</p>
            </div>

            <div class="mb-4 js-new-teacher-fields">
               <label class="block text-sm font-semibold mb-1">Teacher Name</label>
               <input type="text" name="name" value="{{ old('name') }}"
                      class="form-control w-full @error('name') border-red-500 @enderror"
                      placeholder="John Kato">
               @error('name')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
               @enderror
            </div>

            <div class="mb-6 js-new-teacher-fields">
               <label class="block text-sm font-semibold mb-1">Phone (optional)</label>
               <input type="text" name="phone" value="{{ old('phone') }}"
                      class="form-control w-full"
                      placeholder="0777000000">
            </div>

            <div class="flex items-center gap-3">
               <button type="submit" class="ds-btn ds-btn-primary">
                  Send Invite
               </button>
               <a href="{{ route('admin.classes') }}" class="ds-btn ds-btn-ghost">
                  Cancel
               </a>
            </div>
         </form>
      </div>

      @if($pendingInvites->isNotEmpty())
      <div class="ds-card ds-card-body max-w-2xl mt-6" data-testid="pending-invites">
         <h2 class="text-sm font-semibold mb-3">Pending invites</h2>
         <table class="w-full text-sm">
            <thead>
               <tr class="text-left text-gray-500">
                  <th class="py-1">Email</th>
                  <th class="py-1">Name</th>
                  <th class="py-1">Status</th>
                  <th class="py-1 text-right">Link</th>
               </tr>
            </thead>
            <tbody>
               @foreach($pendingInvites as $invite)
               <tr class="border-t">
                  <td class="py-2">{{ $invite->email }}</td>
                  <td class="py-2">{{ $invite->name }}</td>
                  <td class="py-2">
                     @if($invite->isExpired())
                     <span class="text-amber-600">Expired</span>
                     @else
                     <span class="text-green-700">Pending</span>
                     @endif
                  </td>
                  <td class="py-2 text-right">
                     <form method="POST" action="{{ route('admin.class-teacher-invite.resend', $invite) }}">
                        @csrf
                        <button type="submit" class="ds-btn ds-btn-ghost ds-btn-sm">Resend</button>
                     </form>
                  </td>
               </tr>
               @endforeach
            </tbody>
         </table>
         <p class="text-gray-500 text-xs mt-2">Resending sends a fresh link and replaces the previous one.</p>
      </div>
      @endif

   </div>
@endsection

@push('scripts')
<script>
(function () {
    const select = document.querySelector('select[name="existing_teacher_id"]');
    const newFields = document.querySelectorAll('.js-new-teacher-fields');
    const nameInput = document.querySelector('input[name="name"]');

    function toggle() {
        const isExisting = select.value !== '';

        newFields.forEach(el => {
            el.style.display = isExisting ? 'none' : 'block';
        });

        // Only the teacher name is required for a new teacher.
        // Phone stays optional, matching its label and the server-side rules.
        if (nameInput) {
            nameInput.required = !isExisting;
        }
    }

    select.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
