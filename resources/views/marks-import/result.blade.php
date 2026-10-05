@extends($layout)

@section('content')
<div class="container-fluid w-full py-3 lg:mx-2" data-testid="marks-import-result">
    @include('partials.message')
    @include('marks-import._header', ['title' => 'Import finished'])

    <div role="status" class="mb-4 rounded-lg border border-green-300 bg-green-50 p-4 text-green-900">
        Saved <strong data-testid="result-saved">{{ $result->saved }}</strong>,
        updated <strong data-testid="result-updated">{{ $result->updated }}</strong>,
        unchanged <strong data-testid="result-unchanged">{{ $result->unchanged }}</strong>,
        skipped <strong data-testid="result-skipped">{{ $result->skipped }}</strong>.
        This import was recorded in the activity log.
    </div>

    @if($result->skipped > 0)
        <section class="mb-6" aria-labelledby="skipped-heading">
            <h2 id="skipped-heading" class="text-lg font-semibold text-gray-900 mb-1">Skipped rows ({{ $result->skipped }})</h2>
            <ul class="mb-3 list-disc pl-5 text-gray-800">
                @foreach($result->skippedByReason() as $code => $n) <li>{{ $n }} &times; {{ $reasons[$code] ?? $code }}</li> @endforeach
            </ul>
            @include('marks-import._rows', ['rows' => array_values(array_filter($result->rows, fn ($r) => $r['outcome'] === 'skipped')), 'showStatus' => true])
        </section>
    @endif

    @php $changed = array_values(array_filter($result->rows, fn ($r) => in_array($r['outcome'], ['saved', 'updated'], true))); @endphp
    @if(count($changed) > 0)
        <section class="mb-6" aria-labelledby="changed-heading">
            <h2 id="changed-heading" class="text-lg font-semibold text-gray-900 mb-2">Saved and updated ({{ count($changed) }})</h2>
            @include('marks-import._rows', ['rows' => $changed, 'showStatus' => true])
        </section>
    @endif

    <div class="flex flex-wrap gap-3">
        <x-button variant="primary" :href="route($backRoute)">{{ $backLabel }}</x-button>
        <x-button variant="outline" :href="route($routePrefix.'.page', $exam)">Import another file</x-button>
    </div>
</div>
@endsection
