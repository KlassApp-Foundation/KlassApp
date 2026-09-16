{{-- Primary confirm UX: buttons/chips, not free-text. Panel + modal share this. --}}
@php
    $chipVariant = $variant ?? 'panel';
@endphp
<div class="toshi-confirm-chips"
     data-testid="toshi-confirm-chips"
     data-toshi-confirm-variant="{{ $chipVariant }}"
     role="group"
     aria-label="Confirm or decline">
    <p class="toshi-confirm-chips-hint" data-testid="toshi-confirm-hint">
        Tap a button below — typing Yes/No is optional.
    </p>
    <div class="toshi-confirm-chips-row">
        <button wire:click="confirmYes"
                type="button"
                class="toshi-chip-action toshi-chip-action--yes"
                data-testid="toshi-confirm-yes">
            Yes ✓
        </button>
        <button wire:click="confirmNo"
                type="button"
                class="toshi-chip-action toshi-chip-action--no"
                data-testid="toshi-confirm-no">
            No
        </button>
        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects' && $substep === 1)
        <button wire:click="confirmCustom"
                type="button"
                class="toshi-chip-action toshi-chip-action--secondary"
                data-testid="toshi-confirm-add-subject">
            + Add Subject
        </button>
        @endif
        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers' && $substep === 0)
        <button wire:click="showTeacherFormFn"
                type="button"
                class="toshi-chip-action toshi-chip-action--secondary"
                data-testid="toshi-confirm-add-teacher">
            + Add Teacher
        </button>
        @endif
        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'students' && $substep === 0)
        <button wire:click="showStudentFormFn"
                type="button"
                class="toshi-chip-action toshi-chip-action--secondary"
                data-testid="toshi-confirm-add-student">
            + Add Student
        </button>
        @endif
        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'fees' && $substep === 0)
        <button wire:click="showFeeFormFn"
                type="button"
                class="toshi-chip-action toshi-chip-action--secondary"
                data-testid="toshi-confirm-add-fee">
            + Add Fee
        </button>
        @endif
        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'exams' && $substep === 0)
        <button wire:click="showExamFormFn"
                type="button"
                class="toshi-chip-action toshi-chip-action--secondary"
                data-testid="toshi-confirm-add-exam">
            + Add Exam
        </button>
        @endif
        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'standards' && ($substep === 5 || ($substep === 0 && $this->shouldUseStructureCheckpoint())))
        <button wire:click="confirmSkipAll"
                type="button"
                class="toshi-chip-action toshi-chip-action--no"
                data-testid="toshi-structure-done">
            {{ $substep === 5 ? 'Skip All' : 'Done with structure' }}
        </button>
        @endif
    </div>
</div>
