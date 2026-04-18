<div class=" mx-auto mt-12">
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
       @php
           $subject = $subject ?? null
       @endphp

        <div class="px-6 py-6">
            <form action="
            {{ $subject ?  route('admin.subject.update', $subject->id) : route('admin.subject.store') }}
             " method="POST" class="space-y-5">

             @if ($subject !== null)
                 @method("PATCH")
             @endif
                @csrf

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
                <!-- Class -->
                {{-- standard and section --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                    <label for="standard_id" class="block text-gray-700 font-medium mb-1">Level</label>
                    <select name="standard_id" id="standard_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 "
                        required>
                        <option value="">Select Level</option>
                        @foreach($standards as $std)
                            <option value="{{ $std->id }}" 
                                {{ old("standard_id", $subject?->standard_id) == $std->id ? "selected" : "" }}
                                 >
                                {{ $std->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
{{-- section --}}
                <div>
                    <label for="section_id" class="block text-gray-700 font-medium mb-1">Select Class</label>
                    <select name="section_id" id="section_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 "
                        required>
                        <option value="_______" disabled>Select Class</option>
                         @foreach($sections as $section)
                            <option value="{{ $section->id }}"
                                {{ old("section_id", $subject?->section_id) == $section->id ? "selected" : "" }}
                                 >
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                 <!-- Subject Name -->
                <div>
                    <label for="name" class="block text-gray-700 font-medium mb-1">Subject Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $subject?->name) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                        placeholder="Mathematics"
                        >
                   
                </div>

                   <!-- Subject Code -->
                <div>
                    <label for="code" class="block text-gray-700 font-medium mb-1">Subject Code</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $subject?->code) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Type -->
                
                    <div>
                    <label for="type" class="block text-gray-700 font-medium mb-1">Type</label>
                    <select name="type" id="type"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Select Type</option>
                        @foreach ($types as $type)
                            <option value="{{strtolower($type)}}" {{ old('type', $subject?->type) == strtolower($type) ? 'selected' : '' }}>
                                {{ $type }} 
                            </option>
                        @endforeach
                    </select>
                   
                </div>
               
                </div>
               
                <!-- Buttons -->
                <div class="flex items-center justify-end">
                    <div class="flex gap-4 mt-4  px-8">
                    <button type="submit"
                        class=" bg-green-500 text-white font-medium p-2 rounded hover:bg-green-600 transition-colors">Save Subject</button>
                    <a href="{{ route('admin.subjects') }}"
                        class=" bg-gray-200 text-gray-700 font-medium p-2 rounded hover:bg-gray-300 text-center transition-colors">Cancel</a>
                </div>
                </div>
            </form>
        </div>
    </div>
</div>