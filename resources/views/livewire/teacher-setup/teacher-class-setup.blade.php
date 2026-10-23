{{-- SPDX-License-Identifier: MIT --}}
<div class="ds-page" data-testid="teacher-class-setup">
    <div class="ds-page-head">
        <div>
            <h1 class="admin-h1">Set up my class</h1>
            <p class="ds-page-sub">Confirm your class, add your learners, and say who teaches each subject.</p>
        </div>
        <a href="{{ route('whatsapp.phone') }}" class="ds-btn ds-btn-outline ds-btn-md">Link WhatsApp</a>
    </div>

    @if($errorMessage !== '')
        <div class="ds-alert ds-alert-error" role="alert" data-testid="setup-error">{{ $errorMessage }}</div>
    @endif

    @if($report !== '')
        <div class="ds-alert ds-alert-success" data-testid="setup-report">{{ $report }}</div>
    @endif

    <section class="ds-card ds-card-pad">
        <h2 class="ds-section-title">1. Your class</h2>
        @if($links->isEmpty())
            <p class="ds-muted">You are not a class teacher yet. Ask your school admin to assign you a class.</p>
        @else
            <div class="ds-choice-row">
                @foreach($links as $option)
                    <button type="button"
                            class="ds-choice {{ (int) $link?->id === (int) $option->id ? 'is-selected' : '' }}"
                            wire:click="selectClass({{ $option->id }})"
                            data-testid="choose-class-{{ $option->id }}">
                        {{ $option->section?->name ?? 'Class '.$option->id }}
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    @if($link)
        <section class="ds-card ds-card-pad">
            <h2 class="ds-section-title">2. Learners in {{ $link->section?->name }}</h2>
            <p class="ds-muted">{{ $students }} learners on this class list.</p>
            <label class="ds-btn ds-btn-primary ds-btn-md">
                <input type="file" class="sr-only" wire:model="studentUpload" accept=".csv,.xlsx,.xls,.txt,.docx,.pdf" data-testid="setup-student-upload">
                Upload file
            </label>
            <div wire:loading wire:target="studentUpload" class="ds-muted">Reading the file…</div>
            <x-button type="button" variant="outline" size="md" wire:click="uploadStudents" data-testid="setup-student-save">Add from file</x-button>
        </section>

        <section class="ds-card ds-card-pad">
            <h2 class="ds-section-title">3. Subjects you teach</h2>
            @if($subjects->isEmpty())
                <p class="ds-muted">No subjects on this class yet. Ask your admin to add them.</p>
            @else
                <div class="grid grid-cols-2 gap-2">
                    @foreach($subjects as $subject)
                        <label class="ds-check">
                            <input type="checkbox" wire:model="subjectIds" value="{{ $subject->id }}" data-testid="setup-subject-{{ $subject->id }}">
                            <span>{{ $subject->name }}</span>
                        </label>
                    @endforeach
                </div>
                <x-button type="button" variant="primary" size="md" wire:click="saveSubjects" data-testid="setup-subjects-save">Save my subjects</x-button>
            @endif
        </section>

        <section class="ds-card ds-card-pad">
            <h2 class="ds-section-title">4. Who teaches each subject</h2>
            @if($subjects->isEmpty())
                <p class="ds-muted">Add subjects first.</p>
            @else
                <div class="space-y-2">
                    @foreach($subjects as $subject)
                        <div class="flex items-center gap-3">
                            <span class="w-40 text-sm">{{ $subject->name }}</span>
                            <select class="ds-select" wire:model="subjectTeacher.{{ $subject->id }}" data-testid="setup-subject-teacher-{{ $subject->id }}">
                                <option value="">Not set</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <p class="ds-hint">Pick a teacher, or type their email to invite someone who is not on the list yet.</p>
                <x-button type="button" variant="primary" size="md" wire:click="saveSubjectTeachers" data-testid="setup-subject-teachers-save">Save subject teachers</x-button>
            @endif
        </section>
    @endif
</div>
