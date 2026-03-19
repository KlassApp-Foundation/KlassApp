@extends('layouts.admin.layout')
@section('content')

<div class="w-full lg:mx-2">
    <div class="py-3 flex items-center justify-between">
        <h3>Exams Schedule</h3>
        {{-- add btn --}}
         <div class="flex items-center" dusk="add-button">
                    <a href="{{url('/admin/exams/add-new/')}}" class="no-underline text-white  px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">
                        <span class="mx-1 text-sm font-semibold">Add</span>
                        <svg class="w-3 h-3 fill-current text-white" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 409.6 409.6" style="enable-background:new 0 0 409.6 409.6;" xml:space="preserve"><g><g><path d="M392.533,187.733H221.867V17.067C221.867,7.641,214.226,0,204.8,0s-17.067,7.641-17.067,17.067v170.667H17.067 C7.641,187.733,0,195.374,0,204.8s7.641,17.067,17.067,17.067h170.667v170.667c0,9.426,7.641,17.067,17.067,17.067 s17.067-7.641,17.067-17.067V221.867h170.667c9.426,0,17.067-7.641,17.067-17.067S401.959,187.733,392.533,187.733z"/></g></g></svg>
                    </a> 
                </div>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class / Standard</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse ($exams as $exam)
            <tr>
                <td class="p-2">{{ $loop->iteration }}</td>
                <td class="p-2">{{ $exam->name }}</td>
                <td class="p-2">{{ $exam->term }}</td>
                <td class="p-2">{{ $exam->standard->name ?? '-' }}</td>
                <td class="p-2 ">{{ $exam->subject->name ?? '-' }}</td>
                <td class="p-2">{{ $exam->teacher->name ?? '-' }}</td>
                <td class="p-2">{{ $exam->academicYear->name ?? '-' }}</td>
                <td class="p-2 space-x-2">
                    <a href="#" class="text-blue-500 hover:text-blue-700">Edit</a>
                    <form action="#" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-2 text-center text-gray-500">No exams found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection