@extends('layouts.admin.layout')

@section('content')
<div class="py-6">
    <div class="container mx-auto p-6 bg-white shadow rounded ">
    <h2 class="text-xl font-semibold mb-4">Add Academic Term</h2>

    {{-- Display validation errors --}}
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fees-categories.store') }}" method="POST">
        @csrf

        {{-- Fee name --}}
        <div class="mb-4">
            <label for="name" class="block font-medium mb-1">
                <span>Fee Name</span>
                <span class='text-red-400'>*</span>
            </label>
            <input
                type="text"
                name="name"
                id="name"
                value="{{ old('name') }}"
                class="w-full border rounded p-2"
                placeholder="Academics"
                required
            >
        </div>

         {{-- amount --}}
        <div class="mb-4">
            <label for="amount" class="block font-medium mb-1">
                <span>Amount</span>
                <span class='text-red-400'>*</span>
            </label>
            <input
                type="number"
                name="amount"
                min="0"
                step="1000"
                id="amount"
                value="{{ old('amount') }}"
                class="w-full border rounded p-2"
                required
            >
        </div>

        {{-- Name --}}
        <div class="mb-4">
            <label for="standard_id" class="block font-medium mb-1">
                <span>Level</span>
                <span class='text-red-400'>*</span>
            </label>
                <select name="standard_id" id="standard_id"  class="border rounded p-2 w-full" required>
                    <option value="______">Select Standard</option>
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}" 
                        {{ old("standard_id") == $standard->id ? "selected" : ""}} 
                        > {{ $standard->name }}</option>
                    @endforeach
                </select>
        </div>

        {{-- classes --}}
       <div class="mb-4">
            <label for="section_id" class="block font-medium mb-1">Class (Optional)</label>
                <select name="section_id" id="section_id"  class="border rounded p-2 w-full">
                    <option value="">Select Class</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" 
                        {{ old("section_id") == $section->id ? "selected" : ""}} 
                        > {{ $section->name }}</option>
                    @endforeach
                </select>
        </div>

        {{-- term --}}
       <div class="mb-4">
            <label for="academic_term_id" class="block font-medium mb-1">Academic Term (Optional)</label>
                <select name="academic_term_id" id="academic_term_id"  class="border rounded p-2 w-full">
                    <option value="">Select Term</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}" 
                        {{ old("academic_term_id") == $term->id ? "selected" : ""}} 
                        > {{ $term->name }}</option>
                    @endforeach
                </select>
        </div>

        
        {{-- Submit --}}
        <div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Add Fee
            </button>
        </div>
    </form>
</div>
</div>
@endsection