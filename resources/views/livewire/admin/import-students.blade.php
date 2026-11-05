{{-- SPDX-License-Identifier: MIT --}}
<div class="manual-wizard-bulk" data-testid="import-students">
    @include('livewire.partials.manual-wizard-upload-progress')

    @if($errorMessage !== '')
        <p class="manual-wizard-bulk-help" data-testid="import-students-error">{{ $errorMessage }}</p>
    @endif
    @if($overLimitNotice)
        <p class="manual-wizard-bulk-help" data-testid="import-students-limit">{{ $overLimitNotice }}</p>
    @endif

    <div class="manual-wizard-bulk-toolbar">
        <a href="{{ route('admin.students.upload-template') }}" class="ds-btn ds-btn-outline ds-btn-md" data-testid="import-students-template">
            Download template
        </a>
        <p class="manual-wizard-bulk-help w-full" data-testid="import-students-stream-help">
            Leave Stream blank if your school doesn't use streams.
        </p>
        <label class="ds-btn ds-btn-primary ds-btn-md manual-wizard-bulk-upload">
            <span>Upload file</span>
            <input type="file" class="manual-wizard-bulk-file" wire:model="studentUpload" accept=".csv,.xlsx,.xls,.txt,.docx,.pdf" data-testid="import-students-upload" />
        </label>
    </div>
    <div wire:loading wire:target="studentUpload" class="manual-wizard-bulk-loading">Parsing file…</div>

    @if($studentUploadReport !== '')
        <p class="manual-wizard-bulk-help" data-testid="import-students-report">{{ $studentUploadReport }}</p>
        @if(count($studentUploadNotes) > 0)
            <ul class="manual-wizard-bulk-list" data-testid="import-students-notes">
                @foreach($studentUploadNotes as $note)
                    <li class="manual-wizard-bulk-item">Row {{ $note['row'] }}: {{ $note['reason'] }}</li>
                @endforeach
            </ul>
        @endif
        @if(count($studentUploadSkips) > 0)
            <ul class="manual-wizard-bulk-list" data-testid="import-students-skips">
                @foreach($studentUploadSkips as $skip)
                    <li class="manual-wizard-bulk-item" data-testid="import-students-skip">Row {{ $skip['row'] ?? '' }}: {{ $skip['reason'] ?? '' }}</li>
                @endforeach
            </ul>
        @endif
        @include('livewire.partials.manual-wizard-missing-streams', [
            'pairs' => $studentMissingStreams,
            'upload' => 'students',
            'testId' => 'import-students-missing-streams',
        ])
    @endif

    @if(count($studentUploadRows) > 0)
        <ul class="manual-wizard-bulk-list" data-testid="import-students-preview">
            @foreach($studentUploadRows as $row)
                <li class="manual-wizard-bulk-item">
                    <strong>{{ $row['name'] ?? '' }}</strong>
                    <span class="manual-wizard-bulk-meta">
                        {{ ($row['class'] ?? '') !== '' ? $row['class'] : 'No class' }}@if(!empty($row['stream'])) · {{ $row['stream'] }}@endif
                        @if(!empty($row['parent'])) · {{ $row['parent'] }}@endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="manual-wizard-bulk-divider"><span>or add one at a time</span></div>

    <div class="ds-form-group">
        <label class="ds-form-label" for="import-student-name">Student name</label>
        <input id="import-student-name" type="text" class="ds-form-input w-full" wire:model="studentName" data-testid="import-student-name" />
    </div>
    <div class="manual-wizard-bulk-pair">
        <div class="ds-form-group">
            <label class="ds-form-label" for="import-student-class">Class</label>
            @if(count($structureClasses) > 0)
                <select id="import-student-class" class="ds-form-input ds-form-select w-full" wire:model.live="studentClass" data-testid="import-student-class">
                    <option value="">Select class…</option>
                    @foreach($structureClasses as $class)
                        <option value="{{ $class['name'] }}">{{ $class['name'] }}</option>
                    @endforeach
                </select>
            @else
                <input id="import-student-class" type="text" class="ds-form-input w-full" wire:model.live="studentClass" data-testid="import-student-class" />
            @endif
        </div>
        <div class="ds-form-group">
            <label class="ds-form-label" for="import-student-stream">Stream</label>
            @php
                $streamOptions = [];
                foreach ($structureClasses as $structureRow) {
                    if (strcasecmp((string) ($structureRow['name'] ?? ''), trim($studentClass)) === 0) {
                        $streamOptions = array_values(array_map(
                            fn ($stream) => (string) ($stream['label'] ?? ''),
                            $structureRow['streams'] ?? []
                        ));
                        break;
                    }
                }
            @endphp
            @if(count($streamOptions) > 0)
                <select id="import-student-stream" class="ds-form-input ds-form-select w-full" wire:model="studentStream" data-testid="import-student-stream">
                    <option value="">Base class (no stream)</option>
                    @foreach($streamOptions as $streamLabel)
                        <option value="{{ $streamLabel }}">{{ $streamLabel }}</option>
                    @endforeach
                </select>
            @else
                <input id="import-student-stream" type="text" class="ds-form-input w-full" wire:model="studentStream" placeholder="Optional" data-testid="import-student-stream" />
            @endif
        </div>
    </div>
    <div class="manual-wizard-bulk-pair">
        <div class="ds-form-group">
            <label class="ds-form-label" for="import-student-parent">Parent name</label>
            <input id="import-student-parent" type="text" class="ds-form-input w-full" wire:model="studentParent" data-testid="import-student-parent" />
        </div>
        <div class="ds-form-group">
            <label class="ds-form-label" for="import-student-parent-phone">Parent phone</label>
            <input id="import-student-parent-phone" type="tel" class="ds-form-input w-full" wire:model="studentParentPhone" data-testid="import-student-parent-phone" />
        </div>
    </div>
    <button type="button" class="ds-btn ds-btn-outline ds-btn-sm" wire:click="addStudent" data-testid="import-student-add">Add student</button>
</div>
