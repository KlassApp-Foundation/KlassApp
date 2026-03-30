@extends('layouts.admin.layout')

@section('content')
<div class="py-6">
    @php
        $new_term = $new_term ?? null
    @endphp
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
    <form action="{{
    $new_term ?  route('admin.academic-term.update', $new_term->id) : route('admin.academic-term.store') }}" method="POST">
        @csrf
        @if ($new_term !== null)
            @method("PATCH")
        @endif
        {{-- Name --}}
        <div class="mb-4">
            <label for="name" class="block font-medium mb-1">Name</label>
                <select name="name" id="name"  class="border rounded p-2 w-full">
                    <option value="______">Select Term</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term }}" 
                        {{ old("name", $new_term?->name) == $term ? "selected" : ""}} 
                        > {{ $term }}</option>
                    @endforeach
                </select>
        </div>

        {{-- Starts On --}}
        <div class="mb-4">
            <label for="starts_on" class="block font-medium mb-1">Starts On</label>
            <input
                type="date"
                name="starts_on"
                id="starts_on"
                value="{{ old('starts_on', optional($new_term)->starts_on?->format('Y-m-d')) }}"
                class="w-full border rounded p-2"
            >
        </div>

        {{-- Ends On --}}
        <div class="mb-4">
            <label for="ends_on" class="block font-medium mb-1">Ends On</label>
            <input
                type="date"
                name="ends_on"
                id="ends_on"
                value="{{ old('ends_on', optional($new_term)->ends_on?->format("Y-m-d")) }}"
                class="w-full border rounded p-2"
            >
        </div>

        {{-- Submit --}}
        <div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                {{$new_term ? "Update Term" : "Add Academic Term"}}
            </button>
        </div>
    </form>
</div>
</div>
@endsection