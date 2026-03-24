{{-- TO DICTH later on --}}
@extends('layouts.admin.layout')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-10">
 @include('partials.message')
    <div class="w-full max-w-xl bg-white border border-gray-100 shadow-lg rounded-2xl p-8">

        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Create Class</h2>
            <p class="text-sm text-gray-500 mt-1">
                Fill in the details below and save.
            </p>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route("admin.classes.store") }}">
            @csrf

            {{-- Standard --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Standard
                </label>

                <select name="standard_id"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white
                    focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                    transition duration-200">
                    
                    <option value="">Select standard</option>

                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}">
                            {{ $standard->name }}
                        </option>
                    @endforeach

                </select>

                @error('standard_id')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Class --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Class
                </label>

                <select name="name"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white
                    focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                    transition duration-200">

                    <option value="">Select class</option>

                    {{-- Nursery --}}
                    @if(isset($nursery_classes))
                        @foreach ($nursery_classes as $class)
                            <option value="{{ $class }}">
                                {{ $class }}
                            </option>
                        @endforeach
                    @endif

                    {{-- Primary --}}
                    @if(isset($primary_classes))
                        @foreach ($primary_classes as $class)
                            <option value="{{ $class }}">
                                {{ $class }}
                            </option>
                        @endforeach
                    @endif

                    {{-- Secondary --}}
                    @if(isset($secondary_classes))
                        @foreach ($secondary_classes as $class)
                            <option value="{{ $class }}">
                                {{ $class }}
                            </option>
                        @endforeach
                    @endif

                </select>

                @error('name')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                class="w-full bg-green-600 text-white font-semibold py-3 rounded-lg
                hover:bg-green-700 transition duration-200 shadow-sm">
                Save Class
            </button>

        </form>

    </div>

</div>
@endsection