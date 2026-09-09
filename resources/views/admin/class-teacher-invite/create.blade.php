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
   </div>
@endsection

@push('scripts')
<script>
(function () {
    const select = document.querySelector('select[name="existing_teacher_id"]');
    const newFields = document.querySelectorAll('.js-new-teacher-fields');

    function toggle() {
        const isExisting = select.value !== '';
        newFields.forEach(el => {
            el.style.display = isExisting ? 'none' : 'block';
            el.querySelectorAll('input').forEach(input => {
                input.required = !isExisting;
            });
        });
    }

    select.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
