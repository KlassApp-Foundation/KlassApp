{{-- Shared page header for the marks import screens. Needs $exam, $title, $backRoute/$backLabel. --}}
<div class="mb-6">
    <a href="{{ route($backRoute) }}" class="inline-flex items-center text-sm font-semibold text-blue-800 underline" style="min-height:44px;">{{ $backLabel }}</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $title }}</h1>
    <p class="mt-1 text-gray-700">
        {{ $exam->subject?->name ?? 'Subject' }}
        &bull; {{ $exam->section?->name ?? $exam->standard?->name ?? 'Class' }}
        @if($exam->examType) &bull; {{ $exam->examType->name }} @endif
        @if($exam->academicTerm) &bull; {{ $exam->academicTerm->name }} @endif
    </p>
</div>
