<div class="min-h-screen flex items-center justify-center ">
 
  <form method="POST" action="{{route('admin.class.add')}}"  
        class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        {{-- enctype="multipart/form-data" --}}
    @csrf

    <div class="mb-4">
      <label for="name" class="font-semibold text-gray-700">
        Class Name <span class="text-red-500">*</span>
      </label>
    </div>

    <div class="mb-4">
      <select 
        name="name" 
        id="name" 
        class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-white shadow-sm 
               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">-- Select Class --</option>
{{-- {{ dd("Hello") }} --}}
        @foreach($classes as $class)
        {{-- {{ dd($class) }} --}}
          <option value="{{ $class }}" {{ old('name') == $class ? 'selected' : '' }}>
            {{ $class }}
          </option>
        @endforeach
      </select>
    </div>

    <span class="text-red-500 text-xs font-semibold">
      {{ $errors->first('name') }}
    </span>

    <div class="mt-6 text-center">
      <input 
        type="submit" 
        value="Submit" 
        name="submit" 
        class="btn btn-primary cursor-pointer px-6 py-2 rounded-lg shadow hover:shadow-md">
    </div>

  </form>
</div>