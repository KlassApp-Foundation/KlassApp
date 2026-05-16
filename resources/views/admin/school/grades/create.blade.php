@extends('layouts.admin.layout')

@section('content')
<div class="py-6">
    
    <div class="container mx-auto p-6 bg-white shadow rounded">
        <h2 class="text-xl font-semibold mb-6">
            {{ isset($grade) ? 'Edit Grading System' : 'Create a School Grading System' }}
        </h2>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form 
            action="{{ isset($grade) ? route('admin.grades.update', $grade->id) : route('admin.grades.store') }}" 
            method="POST" 
            class="space-y-6"
        >
            @csrf
            @if(isset($grade))
                @method('PUT')
            @endif

            {{-- Standard --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Class / Standard
                </label>
                <select name="standard_id" class="w-full border border-gray-400 rounded p-2">
                    <option value="">-- Select Standard --</option>
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}"
                            {{ old('standard_id', $grade->standard_id ?? '') == $standard->id ? 'selected' : '' }}>
                            {{ $standard->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Grade --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Grade
                </label>
                <input 
                    type="text" 
                    name="grade" 
                    value="{{ old('grade', $grade->grade ?? '') }}"
                    placeholder="e.g. D1"
                    class="w-full border border-gray-400 rounded p-2"
                >
            </div>

             {{-- Rank --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Rank
                </label>
                <input 
                    type="number" 
                    name="rank" 
                    value="{{ old('rank', $grade->rank ?? '') }}"
                    placeholder="e.g. 1"
                    class="w-full border border-gray-400 rounded p-2"
                >
            </div>

            {{-- Min Score --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Minimum Score
                </label>
                <input 
                    type="number" 
                    name="min_score" 
                    value="{{ old('min_score', $grade->min_score ?? '') }}"
                    min="0" 
                    max="100"
                    class="w-full border border-gray-400 rounded p-2"
                >
            </div>

            {{-- Max Score --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Maximum Score
                </label>
                <input 
                    type="number" 
                    name="max_score" 
                    value="{{ old('max_score', $grade->max_score ?? '') }}"
                    min="0" 
                    max="100"
                    class="w-full border border-gray-400 rounded p-2"
                >
            </div>

            {{-- Remark --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Remark (optional)
                </label>
                <input 
                    type="text" 
                    name="remark" 
                    value="{{ old('remark', $grade->remark ?? '') }}"
                    placeholder="e.g. Excellent"
                    class="w-full border border-gray-400 rounded p-2"
                >
            </div>
{{-- {{ dd($grade) }} --}}
            {{-- Submit --}}
            <div class="pt-4">
                <button 
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded shadow"
                >
                    {{ isset($grade) ? 'Update Grading Rule' : 'Save Grading Rule' }}
                </button>
            </div>

        </form>
    </div>

</div>
@endsection