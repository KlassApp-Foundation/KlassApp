@php $subject = $subject ?? null; @endphp

<div class="mx-auto mt-8 px-4 md:px-6">
    <x-card shadow="md">
        <form action="{{ $subject ? route('admin.subject.update', $subject->id) : route('admin.subject.store') }}"
              method="POST">
            @if ($subject)
                @method("PATCH")
            @endif
            @csrf

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <input type="hidden" name="standard_id" id="standard_id"
                   value="{{ old('standard_id', $subject?->standard_id) }}">

            <div class="grid grid-cols-2 gap-4">
                <x-form-group label="Class" name="section_id" type="select" required
                    :options="$sections->pluck('name', 'id')->prepend('Select Class', '')->toArray()"
                    :value="$subject?->section_id"
                    :error="$errors->first('section_id') ?? null" />

                <x-form-group label="Subject Name" name="name" type="text" required
                    placeholder="Mathematics"
                    :value="$subject?->name"
                    :error="$errors->first('name') ?? null" />

                <x-form-group label="Subject Code" name="code" type="text" required
                    placeholder="MATH"
                    :value="$subject?->code"
                    :error="$errors->first('code') ?? null" />

                <x-form-group label="Type" name="type" type="select" required
                    :options="(['' => 'Select Type'] + array_combine(array_map('strtolower', $types ?? []), $types ?? []))"
                    :value="$subject?->type"
                    :error="$errors->first('type') ?? null" />
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <x-button href="{{ route('admin.subjects') }}" variant="ghost">Cancel</x-button>
                <x-button type="submit" variant="primary">Save Subject</x-button>
            </div>
        </form>
    </x-card>
</div>

@push('scripts')
<script>
    var sectionStandardMap = @json($sectionStandardMap ?? []);
    document.getElementById('section_id')?.addEventListener('change', function() {
        var stdId = sectionStandardMap[this.value] || '';
        document.getElementById('standard_id').value = stdId;
    });
    (function() {
        var sel = document.getElementById('section_id');
        if (sel) {
            var stdId = sectionStandardMap[sel.value] || '';
            document.getElementById('standard_id').value = stdId;
        }
    })();
</script>
@endpush
