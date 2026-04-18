
@section('content')
<div class="min-h-screen bg-gray-100 py-10">
    <div class="max-w-2xl mx-auto bg-white shadow-xl rounded-2xl p-8 border border-gray-100">

        <!-- Title -->
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            Add School Board & Level
        </h2>


        <!-- Error Messages -->
        @if($errors->any())
            <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700 border border-red-200">
                <ul class="list-disc pl-5 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="/admin/standard/create" class="space-y-6">
            @csrf

            <!-- Board -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Board / Curriculum <span class="text-red-500">*</span>
                </label>

                <select name="board"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Board</option>
                    <option value="uneb" {{ old('board') == 'uneb' ? 'selected' : '' }}>
                        UNEB (Uganda National Examinations Board)
                    </option>
                    <option value="cambridge" {{ old('board') == 'cambridge' ? 'selected' : '' }}>
                        Cambridge International (CIE)
                    </option>
                    <option value="ib" {{ old('board') == 'ib' ? 'selected' : '' }}>
                        International Baccalaureate (IB)
                    </option>
                    <option value="montessori" {{ old('board') == 'montessori' ? 'selected' : '' }}>
                        Montessori
                    </option>
                    <option value="other" {{ old('board') == 'other' ? 'selected' : '' }}>
                        Other / Custom Curriculum
                    </option>
                </select>
            </div>

            <!-- Standards -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    Highest Level Offered <span class="text-red-500">*</span>
                </label>

                <div class="space-y-3">
                    @php $selected = old('standards'); @endphp

                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="standards" value="nursery"
                               {{ $selected == 'nursery' ? 'checked' : '' }}>
                        <span>Nursery / Kindergarten</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="standards" value="primary"
                               {{ $selected == 'primary' ? 'checked' : '' }}>
                        <span>Primary</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="standards" value="O-Level"
                               {{ $selected == 'O-Level' ? 'checked' : '' }}>
                        <span>Secondary (O-Level)</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="standards" value="A-Level"
                               {{ $selected == 'A-Level' ? 'checked' : '' }}>
                        <span>Secondary (A-Level)</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="standards" value="international"
                               {{ $selected == 'international' ? 'checked' : '' }}>
                        <span>International</span>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div>
                <button type="submit"
                        class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                    Save School Board & Level
                </button>
            </div>

        </form>
    </div>
</div>
@endsection