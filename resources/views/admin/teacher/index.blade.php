{{-- SPDX-License-Identifier: MIT --}}
 @extends('layouts.admin.layout')

 @section('content')
 <div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

 @include('layouts.partials.page-header', [
     'title' => 'Teachers',
     'subtitle' => 'Manage all school staff, teachers, and support personnel.',
     'actions' => '<span class="text-sm text-gray-500 font-medium">Total: ' . $count . '</span>'
 ]]

 <div class="relative mt-4">

     <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
         <div class="lg:col-span-2">
             <div class="rounded-lg border border-slate-200 p-4 shadow-sm">
                 <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-2">Teachers List</h2>
                 <div class="overflow-x-auto">
                     <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                         <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50">
                             <tr>
                                 <th class="px-3 py-3">Photo</th>
                                 <th class="px-3 py-3">Name</th>
                                 <th class="px-3 py-3">Contact</th>
                                 <th class="px-3 py-3">Actions</th>
                             </tr>
                         </thead>
                         <tbody class="divide-y divide-slate-50">
                             @foreach($teachers as $teacher)
                             <tr class="hover:bg-slate-50 transition">
                                 <td class="px-3 py-3">
                                     <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center">
                                         @if($teacher->avatar)
                                             <img src="{{ asset('storage/avatars/' . $teacher->avatar) }}" alt="{{ $teacher->name }}" class="w-4 h-4 rounded-full">
                                         @else
                                             <span class="text-slate-400 text-xs">{{ strtoupper(substr($teacher->name, 0, 1)) }}</span>
                                         @endif
                                     </div>
                                 </td>
                                 <td class="px-3 py-3 font-semibold text-slate-900">
                                     {{ $teacher->name }}
                                     @if($teacher->userprofile)
                                         <div class="text-xs text-slate-500">{{ $teacher->userprofile->firstname }}</div>
                                     @endif
                                 </td>
                                 <td class="px-3 py-3 text-slate-500">
                                     {{ $teacher->mobile_no ?: '—' }}
                                     @if($teacher->userprofile && $teacher->userprofile->email)
                                         <br><span class="text-[10px] text-slate-400">{{ $teacher->userprofile->email }}</span>
                                     @endif
                                 </td>
                                 <td class="px-3 py-3">
                                     <a href="{{ url('/admin/teacher/edit/' . $teacher->id) }}" class="text-blue-600 hover:text-blue-800 text-xs">Edit</a>
                                     <form method="POST" action="{{ url('/teacher/delete/' . $teacher->name) }}" style="display:inline">
                                         @csrf @method('DELETE')
                                         <button class="text-red-500 hover:text-red-400 text-xs">Delete</button>
                                     </form>
                                 </td>
                             </tr>
                             @endforeach
                         </tbody>
                     </table>
                 </div>
             </div>
         </div>

         <div class="col-span-1 lg:col-span-1">
             <div class="bg-white rounded-lg shadow p-4">
                 <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3">Filters</h3>
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-medium text-slate-700 mb-1">Alphabet</label>
                         <select class="mt-1 block w-full rounded border-slate-200 py-2 px-3 text-slate-700 focus:outline-none focus:ring-slate-300">
                             <option value="">All</option>
                             @foreach(range('A', 'Z') as $letter)
                                 <option value="{{ $letter }}">{{ $letter }}</option>
                             @endforeach
                         </select>
                     </div>
                     <div>
                         <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                         <select class="mt-1 block w-full rounded border-slate-200 py-2 px-3 text-slate-700 focus:outline-none focus:ring-slate-300">
                             <option value="">All</option>
                             <option value="active">Active</option>
                             <option value="inactive">Inactive</option>
                         </select>
                     </div>
                 </div>
             </div>
         </div>
     </div>

     <div class="mt-6">
         <nav class="mt-4">
             <div class="flex justify-between items-center">
                 <p class="text-sm text-slate-500">Showing 1 to {{ $teachers->count() }} of {{ $totalTeachers }} teachers</p>
                 <div class="flex items-center gap-2">
                     <a href="#" class="text-blue-600 hover:text-blue-800 text-xs">← Prev</a>
                     <a href="#" class="text-blue-600 hover:text-blue-800 text-xs">Next →</a>
                 </div>
             </div>
         </nav>
     </div>
 </div>

 @include('partials.message')
 </div>
 @endsection