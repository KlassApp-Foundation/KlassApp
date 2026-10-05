@extends($layout)

@section('content')
<div class="container-fluid w-full py-3 lg:mx-2" data-testid="marks-import-upload">
    @include('partials.message')
    @include('marks-import._header', ['title' => 'Import marks from a spreadsheet'])

    @if($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-red-900">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $message) <li>{{ $message }}</li> @endforeach
            </ul>
        </div>
    @endif

    <x-card title="1. Download the template">
        <p class="text-gray-800 mb-3">The template lists the students in this class with their admission numbers. Fill in the <strong>Marks</strong> column (0 to 100) and leave a mark blank for a student who did not sit the exam.</p>
        <div class="flex flex-wrap gap-3">
            <x-button variant="outline" :href="route($routePrefix.'.template', $exam)">Download template (.xlsx)</x-button>
            <x-button variant="ghost" :href="route($routePrefix.'.template', [$exam, 'format' => 'csv'])">Download as .csv</x-button>
        </div>
    </x-card>

    <div class="mt-6">
        <x-card title="2. Upload your file">
            <form method="POST" action="{{ route($routePrefix.'.preview', $exam) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="ds-form-group">
                    <label for="marks-file" class="ds-form-label">Spreadsheet file (.xlsx, .xls or .csv, up to 2 MB)</label>
                    <input id="marks-file" type="file" name="file" required accept=".xlsx,.xls,.csv,.txt"
                           class="ds-form-input @error('file') ds-form-input-error @enderror" aria-describedby="marks-file-help">
                    <p id="marks-file-help" class="ds-form-help">Nothing is saved yet. You will see exactly what would be saved and confirm it on the next screen.</p>
                </div>
                <x-button type="submit" variant="primary">Check file</x-button>
            </form>
        </x-card>
    </div>

    <div class="mt-6 text-gray-800">
        <h2 class="text-lg font-semibold text-gray-900">How rows are matched</h2>
        <ul class="list-disc pl-5 mt-2 space-y-1">
            <li>Students are matched by <strong>admission number</strong>. A name is used only when it matches exactly one student in the class.</li>
            <li>Rows that cannot be saved (unknown student, not in this class, mark out of range, blank, repeated) are listed with the reason. None are dropped silently.</li>
            <li>A mark that is already saved with a different value is only changed if you tick the overwrite box on the next screen.</li>
            <li>Uploading the same file again changes nothing.</li>
        </ul>
    </div>
</div>
@endsection
