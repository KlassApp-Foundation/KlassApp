@extends($layout)

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4" data-testid="exam-set-page">
    @include('partials.message')

    <div class="ds-page-head">
        <div>
            <h1 class="ds-page-head-title">
                @if($set)
                    {{ $set->term->name ?? 'Term' }} exam set
                @else
                    Exam sets
                @endif
            </h1>
            <p class="ds-page-head-sub">
                @if($set)
                    {{ $examCount }} {{ \Illuminate\Support\Str::plural('exam', $examCount) }}, grouped by class and exam type.
                @else
                    Each term's exam sets. Open one to see its classes, exam types, and teachers.
                @endif
            </p>
        </div>
        <a href="{{ route($createRoute) }}" class="ds-btn ds-btn-primary ds-btn-sm" data-testid="exam-set-create-link">Create exam set</a>
    </div>

    @if($set)
        <div class="mt-6" data-testid="exam-set-exams">
            @forelse($groups as $classId => $class)
                <section class="ds-card ds-card-padding-default mb-4" data-testid="exam-set-class-{{ $classId }}">
                    <h2 class="text-base font-semibold">{{ $class['name'] }}</h2>
                    <p class="text-xs mb-3" style="color: var(--d-muted);" data-testid="exam-set-class-count-{{ $classId }}">{{ $class['count'] }} {{ \Illuminate\Support\Str::plural('exam', $class['count']) }}</p>
                    @foreach($class['types'] as $typeId => $type)
                        <div class="mb-4" data-testid="exam-set-type-{{ $classId }}-{{ $typeId }}">
                            <h3 class="text-sm font-semibold">{{ $type['name'] }}</h3>
                            <p class="text-xs mb-2" style="color: var(--d-muted);">{{ $type['count'] }} {{ \Illuminate\Support\Str::plural('exam', $type['count']) }}</p>
                            <ul class="divide-y" style="border-color: var(--d-border);">
                                @foreach($type['exams'] as $exam)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2" data-testid="exam-set-exam-{{ $exam['id'] }}">
                                        <span>{{ $exam['subject'] }}</span>
                                        <span data-testid="exam-set-teacher-{{ $exam['id'] }}">{{ $exam['teacher'] }}</span>
                                        @if($canRemove && ! $exam['has_marks'])
                                            <form method="POST" action="{{ route('admin.exam-sets.exams.destroy', ['batch' => $set->id, 'exam' => $exam['id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ds-btn ds-btn-ghost text-sm" data-testid="exam-set-remove-{{ $exam['id'] }}">Remove</button>
                                            </form>
                                        @elseif($exam['has_marks'])
                                            <span class="text-xs" style="color: var(--d-muted);" data-testid="exam-set-locked-{{ $exam['id'] }}">Has marks</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </section>
            @empty
                <div class="ds-card ds-card-padding-default">
                    <p class="text-sm" style="color: var(--d-muted);">This set has no exams. They may already have existed for the term.</p>
                </div>
            @endforelse
        </div>
    @endif

    <div class="mt-6" data-testid="exam-set-term-list">
        <h2 class="text-base font-semibold mb-3">Sets by term</h2>
        @forelse($byTerm as $termName => $termSets)
            <section class="mb-4" data-testid="exam-set-term-{{ \Illuminate\Support\Str::slug($termName) }}">
                <h3 class="text-sm font-semibold mb-2">{{ $termName }}</h3>
                <ul class="ds-card ds-card-padding-default divide-y" style="border-color: var(--d-border);">
                    @foreach($termSets as $row)
                        <li class="py-2">
                            <a href="{{ route($showRoute, ['batch' => $row->id]) }}" data-testid="exam-set-link-{{ $row->id }}">
                                {{ $termName }} · {{ $row->exams_count }} {{ \Illuminate\Support\Str::plural('exam', $row->exams_count) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="text-sm" style="color: var(--d-muted);">No exam sets yet.</p>
        @endforelse
    </div>
</div>
@endsection
