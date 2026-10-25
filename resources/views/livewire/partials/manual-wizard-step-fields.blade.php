@include('livewire.partials.manual-wizard-upload-progress')
{{-- SPDX-License-Identifier: MIT --}}
@php
    $stepKey = $stepKey ?? '';
    $phoneExample = $phoneExample ?? '';
    $whatsappHint = $whatsappHint ?? 'Your WhatsApp number, including the country code.';
    $teacherPhoneHint = $teacherPhoneHint ?? "Teacher's phone number, including the country code.";
    $parentPhoneHint = $parentPhoneHint ?? "Parent's phone number, including the country code.";
    $classTeacherPhoneHint = $classTeacherPhoneHint ?? "Class teacher's phone number, including the country code.";
@endphp

@if($stepKey === 'school_name')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-school-name">School name<span class="text-red-500">*</span></label>
        <input id="wizard-school-name" type="text" class="ds-form-input w-full" wire:model="schoolName" placeholder="e.g. Sunrise Academy" />
    </div>

@elseif($stepKey === 'student_size')
    <div class="ds-form-group">
        <label class="ds-form-label" id="wizard-student-size-label">Approximate number of students<span class="text-red-500">*</span></label>
        <p class="text-xs text-gray-500 mt-1 mb-2">Helps tailor setup defaults for your school. You can change this later.</p>
        <div class="manual-wizard-plan-grid manual-wizard-plan-grid--sizes"
             data-testid="wizard-student-size"
             role="radiogroup"
             aria-labelledby="wizard-student-size-label">
            @foreach(\App\Services\OnboardingStepsService::STUDENT_SIZE_OPTIONS as $option)
                @php
                    $isSelected = $studentSize === $option;
                    $sizeSlug = \Illuminate\Support\Str::slug($option);
                @endphp
                <button type="button"
                        class="manual-wizard-plan-card {{ $isSelected ? 'is-selected' : '' }}"
                        wire:click="selectStudentSize({{ \Illuminate\Support\Js::from($option) }})"
                        role="radio"
                        aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                        data-testid="wizard-student-size-{{ $sizeSlug }}">
                    <span class="manual-wizard-plan-name">{{ $option }}</span>
                </button>
            @endforeach
        </div>
        @error('studentSize') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>

@elseif($stepKey === 'country')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-country">Country<span class="text-red-500">*</span></label>
        <select id="wizard-country" class="ds-form-input ds-form-select w-full" wire:model="countryName">
            @foreach($countries as $country)
                <option value="{{ $country->name }}">{{ $country->name }}</option>
            @endforeach
            @if($countries->isEmpty())
                {{-- FLAGGED(product-decision): an empty country list falls back to Uganda. see docs/onboarding-engine-plan.md --}}
                <option value="Uganda">Uganda</option>
                <option value="Kenya">Kenya</option>
                <option value="Tanzania">Tanzania</option>
            @endif
        </select>
        <p class="text-xs text-gray-500 mt-1">Saves both country and registration country.</p>
    </div>

@elseif($stepKey === 'curriculum')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-curriculum">Board / Curriculum<span class="text-red-500">*</span></label>
        <select id="wizard-curriculum" class="ds-form-input ds-form-select w-full" wire:model="curriculum">
            {{-- FLAGGED(product-decision): the curriculum select is UNEB, Cambridge, Montessori, and Other. see docs/onboarding-engine-plan.md --}}
            <option value="uneb">UNEB (Uganda National Examinations Board)</option>
            <option value="cambridge">Cambridge</option>
            <option value="montessori">Montessori</option>
            <option value="other">Other</option>
        </select>
    </div>

@elseif($stepKey === 'school_category')
    <div class="ds-form-group">
        <label class="ds-form-label">School category<span class="text-red-500">*</span></label>
        <p class="text-xs text-gray-500 mt-1 mb-2">
            Sets the default classes, subjects, and grading system. Everything stays editable later.
        </p>
        <div class="manual-wizard-plan-grid" data-testid="wizard-category-options" role="radiogroup" aria-label="School category">
            @foreach(\App\Services\SchoolCategorySeeder::CATEGORIES as $value => $label)
                @php $isSelected = $schoolCategory === $value; @endphp
                <button type="button"
                        class="manual-wizard-plan-card {{ $isSelected ? 'is-selected' : '' }}"
                        wire:click="selectSchoolCategory('{{ $value }}')"
                        role="radio"
                        aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                        data-testid="wizard-category-{{ $value }}">
                    <span class="manual-wizard-plan-name">{{ $label }}</span>
                </button>
            @endforeach
        </div>
        @error('schoolCategory') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>

@elseif($stepKey === 'emis')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-emis">EMIS / Ministry code<span class="text-red-500">*</span></label>
        <input id="wizard-emis" type="text" class="ds-form-input w-full" wire:model="ministryCode" placeholder="e.g. EMIS-1001" />
    </div>

@elseif($stepKey === 'uneb_center')
    <div class="ds-form-group">
        {{-- FLAGGED(product-decision): the wizard asks for a UNEB centre number. see docs/onboarding-engine-plan.md --}}
        <label class="ds-form-label" for="wizard-uneb">UNEB centre number</label>
        <input id="wizard-uneb" type="text" class="ds-form-input w-full" wire:model="unebCenterNumber" placeholder="Optional — leave blank to skip" />
        <p class="text-xs text-gray-500 mt-1">Optional for UNEB schools. Leave blank if you do not have one yet.</p>
    </div>

@elseif($stepKey === 'academic_year')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-ay-desc">Description</label>
        <input id="wizard-ay-desc" type="text" class="ds-form-input w-full" wire:model="academicYearDescription" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-ay-start">Starts on<span class="text-red-500">*</span></label>
            <input id="wizard-ay-start" type="date" class="ds-form-input w-full" wire:model="academicYearStart" />
        </div>
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-ay-end">Ends on<span class="text-red-500">*</span></label>
            <input id="wizard-ay-end" type="date" class="ds-form-input w-full" wire:model="academicYearEnd" />
        </div>
    </div>

@elseif($stepKey === 'standards')
    <div class="manual-wizard-structure" data-testid="wizard-structure-step">
        <p class="manual-wizard-structure-intro" @if($structurePresetNote !== '') data-testid="wizard-structure-preset" @endif>
            @if($structurePresetNote !== '')
                {{ $structurePresetNote }}
            @else
                Your classes are ready from school category. Optionally add streams or invite a class teacher —
                both are optional. Click Continue anytime to skip.
            @endif
        </p>

        @if($structurePresetPreview !== [])
            <div class="manual-wizard-structure-preview" data-testid="wizard-structure-preset-preview">
                <p class="manual-wizard-structure-intro">These will be added. Nothing has been added yet.</p>
                <ul>
                    @foreach($structurePresetPreview as $item)
                        <li data-testid="wizard-structure-preset-item">{{ $item }}</li>
                    @endforeach
                </ul>
                <x-button type="button" variant="outline" size="sm"
                          wire:click="confirmSuggestedStructure"
                          data-testid="wizard-confirm-suggested-structure">Add these</x-button>
            </div>
        @elseif($structurePresetOffer)
            <x-button type="button" variant="outline" size="sm"
                      wire:click="previewSuggestedStructure"
                      data-testid="wizard-apply-suggested-structure">Apply suggested structure</x-button>
        @endif

        @if($structureFlash ?? false)
            <div class="manual-wizard-structure-flash" data-testid="wizard-structure-flash">{{ $structureFlash }}</div>
        @endif

        @if(count($structureClasses ?? []) === 0)
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-class">First class / stream<span class="text-red-500">*</span></label>
                <input id="wizard-class" type="text" class="ds-form-input w-full" wire:model="className" placeholder="e.g. P1" />
                <p class="text-xs text-gray-500 mt-1">No classes yet — add one to continue, or finish Academic Year with a UNEB category so classes auto-seed.</p>
            </div>
        @else
            <div class="manual-wizard-structure-list">
                @foreach($structureClasses as $class)
                    @php $sid = (int) $class['section_id']; @endphp
                    <div class="manual-wizard-structure-card" wire:key="structure-class-{{ $sid }}" data-testid="wizard-structure-class-{{ $sid }}">
                        <div class="manual-wizard-structure-card-head">
                            <div>
                                <h3 class="manual-wizard-structure-class-name">{{ $class['name'] }}</h3>
                                @if(!empty($class['streams']))
                                    <p class="manual-wizard-structure-streams" data-testid="wizard-structure-streams-{{ $sid }}">
                                        Streams:
                                        @foreach($class['streams'] as $stream)
                                            <span class="manual-wizard-stream-chip">{{ $stream['label'] }}</span>
                                        @endforeach
                                    </p>
                                @else
                                    <p class="manual-wizard-structure-streams is-empty" data-testid="wizard-structure-streams-empty-{{ $sid }}">No streams yet — undivided base class.</p>
                                @endif
                            </div>
                            <div class="manual-wizard-structure-ct-status" data-testid="wizard-structure-ct-status-{{ $sid }}">
                                @if(!empty($class['class_teacher_name']))
                                    CT: <strong>{{ $class['class_teacher_name'] }}</strong>
                                    @if(!empty($class['class_teacher_email']))
                                        <span class="manual-wizard-structure-ct-email">({{ $class['class_teacher_email'] }})</span>
                                    @endif
                                @else
                                    <span class="manual-wizard-structure-ct-empty">No class teacher yet</span>
                                @endif
                            </div>
                        </div>

                        <div class="manual-wizard-structure-card-body">
                            <div class="ds-form-group mb-0">
                                <label class="ds-form-label" for="wizard-stream-{{ $sid }}">Add stream</label>
                                <div class="manual-wizard-structure-stream-row">
                                    <input id="wizard-stream-{{ $sid }}" type="text" class="ds-form-input w-full"
                                           wire:model="structureStreamDrafts.{{ $sid }}"
                                           placeholder="e.g. A, East, Science"
                                           data-testid="wizard-structure-stream-input-{{ $sid }}" />
                                    <x-button type="button" variant="outline" size="sm" class="whitespace-nowrap"
                                              wire:click="addStructureStream({{ $sid }})"
                                              data-testid="wizard-structure-add-stream-{{ $sid }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add</x-button>
                                </div>
                            </div>

                            @if(empty($class['class_teacher_id']))
                                <div class="manual-wizard-structure-ct-form" data-testid="wizard-structure-ct-{{ $sid }}">
                                    <label class="ds-form-label">Invite Class Teacher</label>
                                    <input type="text" inputmode="email" autocomplete="email" class="ds-form-input w-full"
                                           wire:model="structureCtDrafts.{{ $sid }}.email"
                                           placeholder="teacher@school.ug"
                                           data-testid="wizard-structure-ct-email-{{ $sid }}" />
                                    <select class="ds-form-input ds-form-select w-full"
                                            wire:model.live="structureCtDrafts.{{ $sid }}.existing_teacher_id"
                                            data-testid="wizard-structure-ct-existing-{{ $sid }}">
                                        <option value="">— Create a new teacher —</option>
                                        @foreach($structureTeachers ?? [] as $teacher)
                                            <option value="{{ $teacher['id'] }}">{{ $teacher['name'] }} ({{ $teacher['email'] }})</option>
                                        @endforeach
                                    </select>
                                    @if(empty($structureCtDrafts[$sid]['existing_teacher_id'] ?? ''))
                                        <input type="text" class="ds-form-input w-full"
                                               wire:model="structureCtDrafts.{{ $sid }}.name"
                                               placeholder="Teacher name"
                                               data-testid="wizard-structure-ct-name-{{ $sid }}" />
                                        <input type="text" class="ds-form-input w-full"
                                               wire:model="structureCtDrafts.{{ $sid }}.phone"
                                               placeholder="{{ $phoneExample !== '' ? $phoneExample : 'Phone (optional)' }}"
                                               data-testid="wizard-structure-ct-phone-{{ $sid }}" />
                                        <p class="text-xs text-gray-500 mt-1" data-testid="wizard-structure-ct-phone-hint">{{ $classTeacherPhoneHint }}</p>
                                    @endif
                                    <x-button type="button" variant="outline" size="sm"
                                              wire:click="inviteStructureClassTeacher({{ $sid }})"
                                              data-testid="wizard-structure-invite-ct-{{ $sid }}">Send invite</x-button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

@elseif($stepKey === 'subjects')
    <div class="manual-wizard-subjects" data-testid="wizard-subjects">
        @if(count($structureClasses ?? []) > 0)
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-subject-class">Class</label>
                <select id="wizard-subject-class" class="ds-form-input ds-form-select w-full" wire:model.live="subjectSectionId" data-testid="wizard-subject-class">
                    @foreach($structureClasses as $class)
                        <option value="{{ $class['section_id'] }}" data-testid="wizard-subject-class-{{ $class['section_id'] }}">{{ $class['name'] }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Same classes as the classes step. Subjects are saved on the class you pick.</p>
            </div>
        @endif

        @if($subjectFlash ?? false)
            <div class="mb-4 rounded-lg border px-4 py-3 text-sm" data-testid="wizard-subject-flash" style="color:#0F172A;background:#F8FAFC;border-color:#E2E8F0;">{{ $subjectFlash }}</div>
        @endif

        @if(count($existingSubjectNames ?? []) > 0)
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3" data-testid="wizard-subjects-seeded">
                <p class="text-sm font-medium text-green-900" style="color:#14532D;">
                    Subjects already set up
                </p>
                <p class="text-xs text-green-800 mt-1" style="color:#166534;">
                    These came from your school category (or a previous save). Review them below — click Next to continue, or add another subject.
                </p>
                <ul class="mt-2 flex flex-wrap gap-2" data-testid="wizard-subjects-seeded-list">
                    @foreach($existingSubjectNames as $subjectIndex => $existingSubject)
                        <li class="text-xs font-medium px-2 py-1 rounded bg-white border border-green-200 text-green-900 inline-flex items-center gap-2" data-testid="wizard-subject-row" style="color:#14532D;background:#FFFFFF;border-color:#BBF7D0;">
                            <span>{{ $existingSubject }}</span>
                            <button type="button" class="manual-wizard-subject-remove" wire:click="removeSubject({{ $subjectIndex }})" aria-label="Remove {{ $existingSubject }}" data-testid="wizard-subject-remove">✕</button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-subject">
                @if(count($existingSubjectNames ?? []) > 0)
                    Add subjects (optional)
                @else
                    Subjects<span class="text-red-500">*</span>
                @endif
            </label>
            <textarea id="wizard-subject" class="ds-form-input ds-form-textarea w-full" rows="3" wire:model="subjectName" placeholder="Mathematics, English&#10;Science" autocomplete="off" data-testid="wizard-subject-input"></textarea>
            <p class="text-xs text-gray-500 mt-1">Separate subjects with commas, or put one on each line.</p>
            <x-button type="button" variant="outline" size="sm" class="mt-2" wire:click="addSubjects" data-testid="wizard-subject-add"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add</x-button>
        </div>
    </div>

@elseif($stepKey === 'teachers')
    <div class="manual-wizard-bulk" data-testid="wizard-teachers-bulk">
        <div class="manual-wizard-bulk-toolbar">
            <a href="{{ asset('templates/teacher-upload-template.xlsx') }}" download class="ds-btn ds-btn-outline ds-btn-md" data-testid="wizard-teacher-template">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download template
            </a>
            <label class="ds-btn ds-btn-primary ds-btn-md manual-wizard-bulk-upload">
                <svg class="manual-wizard-bulk-upload-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload file</span>
                <input type="file" class="manual-wizard-bulk-file" wire:model="teacherUpload" accept=".csv,.xlsx,.xls,.txt,.docx,.pdf" data-testid="wizard-teacher-upload" />
            </label>
            <a href="{{ route('admin.class-teachers.upload-template') }}" class="ds-btn ds-btn-outline ds-btn-md" data-testid="wizard-class-teacher-template">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download class teacher template
            </a>
            <label class="ds-btn ds-btn-primary ds-btn-md manual-wizard-bulk-upload">
                <svg class="manual-wizard-bulk-upload-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload class teachers</span>
                <input type="file" class="manual-wizard-bulk-file" wire:model="classTeacherUpload" accept=".csv,.xlsx,.xls,.txt,.docx,.pdf" data-testid="wizard-class-teacher-upload" />
            </label>
        </div>
        <div wire:loading wire:target="teacherUpload" class="manual-wizard-bulk-loading">Parsing file…</div>
        <div wire:loading wire:target="classTeacherUpload" class="manual-wizard-bulk-loading">Parsing file…</div>
        <p class="manual-wizard-bulk-help">Class teacher columns: class, stream, name, email, phone. Leave stream blank to assign the whole class.</p>

        @if($teacherUploadReport !== '')
            <p class="manual-wizard-bulk-help" data-testid="wizard-teacher-upload-report">{{ $teacherUploadReport }}</p>
            @if(count($teacherUploadSkips ?? []) > 0)
                <x-fold-list tag="ul" class="manual-wizard-bulk-list" data-testid="wizard-teacher-upload-skips">
                    @foreach($teacherUploadSkips as $skip)
                        <li class="manual-wizard-bulk-item" data-testid="wizard-teacher-upload-skip">Row {{ $skip['row'] }}: {{ $skip['reason'] }}</li>
                    @endforeach
                </x-fold-list>
            @endif
            @if(count($teacherUploadNotes ?? []) > 0)
                <x-fold-list tag="ul" class="manual-wizard-bulk-list" data-testid="wizard-teacher-upload-notes">
                    @foreach($teacherUploadNotes as $note)
                        <li class="manual-wizard-bulk-item">Row {{ $note['row'] }}: {{ $note['reason'] }}</li>
                    @endforeach
                </x-fold-list>
            @endif
            @include('livewire.partials.manual-wizard-missing-streams', [
                'pairs' => $teacherMissingStreams ?? [],
                'upload' => 'teachers',
                'testId' => 'wizard-teacher-missing-streams',
            ])
        @endif

        @if(($classTeacherUploadReport ?? '') !== '')
            <p class="manual-wizard-bulk-help" data-testid="wizard-class-teacher-upload-report">{{ $classTeacherUploadReport }}</p>
            @if(count($classTeacherUploadSkips ?? []) > 0)
                <x-fold-list tag="ul" class="manual-wizard-bulk-list" data-testid="wizard-class-teacher-upload-skips">
                    @foreach($classTeacherUploadSkips as $skip)
                        <li class="manual-wizard-bulk-item" data-testid="wizard-class-teacher-upload-skip">Row {{ $skip['row'] }}: {{ $skip['reason'] }}</li>
                    @endforeach
                </x-fold-list>
            @endif
            @include('livewire.partials.manual-wizard-missing-streams', [
                'pairs' => $classTeacherMissingStreams ?? [],
                'upload' => 'class_teachers',
                'testId' => 'wizard-class-teacher-missing-streams',
            ])
        @endif

        @if(count($this->savedClassTeacherRows()) > 0)
            <x-fold-list tag="ul" class="manual-wizard-bulk-list" data-testid="wizard-class-teacher-list">
                @foreach($this->savedClassTeacherRows() as $row)
                    <li class="manual-wizard-bulk-item" wire:key="class-teacher-saved-{{ $row['standard_link_id'] }}" data-testid="wizard-class-teacher-saved">
                        <span class="manual-wizard-bulk-item-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span class="manual-wizard-bulk-meta">{{ $row['class'] }}@if($row['stream'] !== '') · {{ $row['stream'] }}@endif · {{ $row['email'] }}@if($row['phone'] !== '') · {{ $row['phone'] }}@endif</span>
                        </span>
                    </li>
                @endforeach
            </x-fold-list>
        @endif

        @if(count($this->savedTeacherRows()) > 0 || count($teacherDrafts ?? []) > 0)
            <x-fold-list tag="ul" class="manual-wizard-bulk-list" data-testid="wizard-teacher-list">
                @foreach($this->savedTeacherRows() as $row)
                    <li class="manual-wizard-bulk-item" wire:key="teacher-saved-{{ $row['email'] }}" data-testid="wizard-teacher-saved">
                        <span class="manual-wizard-bulk-item-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span class="manual-wizard-bulk-meta">{{ $row['email'] }}@if($row['phone'] !== '') · {{ $row['phone'] }}@endif</span>
                        </span>
                    </li>
                @endforeach
                @foreach($teacherDrafts as $i => $row)
                    <li class="manual-wizard-bulk-item" wire:key="teacher-draft-{{ $i }}">
                        <span class="manual-wizard-bulk-item-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span class="manual-wizard-bulk-meta">
                                {{ $row['email'] }}@if(!empty($row['phone'])) · {{ $row['phone'] }}@endif
                                @if(!empty($row['classes'])) · {{ is_array($row['classes']) ? implode(', ', $row['classes']) : $row['classes'] }}@endif
                                @if(!empty($row['subjects'])) · {{ is_array($row['subjects']) ? implode(', ', $row['subjects']) : $row['subjects'] }}@endif
                            </span>
                        </span>
                        <button type="button" class="manual-wizard-bulk-remove" wire:click="removeTeacherDraft({{ $i }})" aria-label="Remove">✕</button>
                    </li>
                @endforeach
            </x-fold-list>
        @endif

        <div class="ds-form-group" data-testid="wizard-teacher-paste-block">
            <label class="ds-form-label" for="wizard-teacher-paste">Paste names (one per line)</label>
            <textarea id="wizard-teacher-paste" class="ds-form-input ds-form-textarea w-full" rows="3" wire:model="teacherPaste" placeholder="John Ssali&#10;Grace Nakamya" data-testid="wizard-teacher-paste"></textarea>
            <x-button type="button" variant="outline" size="sm" class="mt-2" wire:click="applyTeacherPaste" data-testid="wizard-teacher-paste-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>Add from paste</x-button>
        </div>

        <div class="manual-wizard-bulk-divider"><span>or add one at a time</span></div>

        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-teacher-name">Teacher name</label>
            <input id="wizard-teacher-name" type="text" class="ds-form-input w-full" wire:model="teacherName" placeholder="e.g. Jane Nabirye" autocomplete="name" data-testid="wizard-teacher-name" />
            <p class="manual-wizard-bulk-help">Full name only — put the phone number in the Phone field below.</p>
        </div>
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-teacher-email">Email</label>
            {{-- type=text: native type=email + deferred wire:model blocked Next after Add (empty/invalid sync). --}}
            <input id="wizard-teacher-email" type="text" inputmode="email" autocomplete="email" class="ds-form-input w-full" wire:model="teacherEmail" placeholder="teacher@school.ug" data-testid="wizard-teacher-email" />
        </div>
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-teacher-phone">Phone (optional)</label>
            <input id="wizard-teacher-phone" type="tel" inputmode="tel" autocomplete="tel" class="ds-form-input w-full" wire:model="teacherPhone" placeholder="{{ $phoneExample !== '' ? $phoneExample : 'Include the country code' }}" data-testid="wizard-teacher-phone" aria-describedby="wizard-teacher-phone-hint" />
            <p class="text-xs text-gray-500 mt-1" id="wizard-teacher-phone-hint" data-testid="wizard-teacher-phone-hint">{{ $teacherPhoneHint }}</p>
        </div>
        <div class="ds-form-group" data-testid="wizard-teacher-classes">
            <label class="ds-form-label">Classes taught <span class="text-xs text-gray-400">(optional)</span></label>
            <div class="manual-wizard-check-grid">
                @forelse(($structureClasses ?? []) as $class)
                    <label class="manual-wizard-check">
                        <input type="checkbox" value="{{ $class['name'] }}" wire:model="teacherSelectedClasses" />
                        <span>{{ $class['name'] }}</span>
                    </label>
                @empty
                    <p class="manual-wizard-bulk-help">Add classes first to assign teachers to them.</p>
                @endforelse
            </div>
        </div>
        <div class="ds-form-group" data-testid="wizard-teacher-subjects">
            <label class="ds-form-label">Subjects taught <span class="text-xs text-gray-400">(optional)</span></label>
            <div class="manual-wizard-check-grid">
                @forelse(($existingSubjectNames ?? []) as $subjectName)
                    <label class="manual-wizard-check">
                        <input type="checkbox" value="{{ $subjectName }}" wire:model="teacherSelectedSubjects" />
                        <span>{{ $subjectName }}</span>
                    </label>
                @empty
                    <p class="manual-wizard-bulk-help">Add subjects first to assign them to teachers.</p>
                @endforelse
            </div>
        </div>
        <x-button type="button" variant="outline" size="sm" wire:click="addTeacherDraft" data-testid="wizard-teacher-add"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add teacher</x-button>
        <p class="manual-wizard-bulk-footnote">Optional — skip if you’ll add teachers later. Continue saves everyone in the list.</p>
        {{-- Do not put @if inside <x-button> attrs — it breaks the outer @elseif chain when Blade compiles components. --}}
        @if(count($teacherDrafts ?? []) > 0)
            <x-button type="button"
                      variant="ghost"
                      size="sm"
                      class="mt-2"
                      wire:click="skipOptionalStep"
                      wire:confirm="You have teachers in the list that will not be saved. Skip anyway?"
                      data-testid="wizard-teachers-skip">Skip for now</x-button>
        @else
            <x-button type="button"
                      variant="ghost"
                      size="sm"
                      class="mt-2"
                      wire:click="skipOptionalStep"
                      data-testid="wizard-teachers-skip">Skip for now</x-button>
        @endif
    </div>

@elseif($stepKey === 'students')
    <div class="manual-wizard-bulk" data-testid="wizard-students-bulk">
        <div class="manual-wizard-bulk-toolbar">
            <a href="{{ route('admin.students.upload-template') }}" class="ds-btn ds-btn-outline ds-btn-md" data-testid="wizard-student-template">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download template
            </a>
            <p class="manual-wizard-bulk-help w-full" data-testid="wizard-student-stream-help">
                @if(!empty($schoolHasStreams))
                    When a class has streams, pick a stream by default. You can still choose “Base class (no stream)” to enrol on the undivided class.
                @else
                    Leave Stream blank if your school doesn't use streams.
                @endif
            </p>
            <label class="ds-btn ds-btn-primary ds-btn-md manual-wizard-bulk-upload">
                <svg class="manual-wizard-bulk-upload-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload file</span>
                <input type="file" class="manual-wizard-bulk-file" wire:model="studentUpload" accept=".csv,.xlsx,.xls,.txt,.docx,.pdf" data-testid="wizard-student-upload" />
            </label>
        </div>
        <div wire:loading wire:target="studentUpload" class="manual-wizard-bulk-loading">Parsing file…</div>

        @if(($studentUploadReport ?? '') !== '')
            <p class="manual-wizard-bulk-help" data-testid="wizard-student-upload-report">{{ $studentUploadReport }}</p>
            @if(count($studentUploadNotes ?? []) > 0)
                <ul class="manual-wizard-bulk-list" data-testid="wizard-student-upload-notes">
                    @foreach($studentUploadNotes as $note)
                        <li class="manual-wizard-bulk-item">Row {{ $note['row'] }}: {{ $note['reason'] }}</li>
                    @endforeach
                </ul>
            @endif
            @if(count($studentUploadSkips ?? []) > 0)
                <ul class="manual-wizard-bulk-list" data-testid="wizard-student-upload-skips">
                    @foreach($studentUploadSkips as $skip)
                        <li class="manual-wizard-bulk-item" data-testid="wizard-student-upload-skip">Row {{ $skip['row'] }}: {{ $skip['reason'] }}</li>
                    @endforeach
                </ul>
            @endif
            @include('livewire.partials.manual-wizard-missing-streams', [
                'pairs' => $studentMissingStreams ?? [],
                'upload' => 'students',
                'testId' => 'wizard-student-missing-streams',
            ])
        @endif

        @if(count($studentDrafts ?? []) > 0)
            <x-fold-list tag="ul" class="manual-wizard-bulk-list" data-testid="wizard-student-list">
                @foreach($studentDrafts as $i => $row)
                    <li class="manual-wizard-bulk-item" wire:key="student-draft-{{ $i }}">
                        <span class="manual-wizard-bulk-item-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span class="manual-wizard-bulk-meta">
                                {{ $row['class'] ?: 'No class' }}@if(!empty($row['stream'])) · {{ $row['stream'] }}@endif
                                @if(!empty($row['gender'])) · {{ ucfirst($row['gender']) }}@endif
                                @if(!empty($row['parent'])) · {{ $row['parent'] }}@endif
                            </span>
                        </span>
                        <button type="button" class="manual-wizard-bulk-remove" wire:click="removeStudentDraft({{ $i }})" aria-label="Remove">✕</button>
                    </li>
                @endforeach
            </x-fold-list>
        @endif

        <div class="ds-form-group" data-testid="wizard-student-paste-block">
            <label class="ds-form-label" for="wizard-student-paste">Paste names (one per line)</label>
            <textarea id="wizard-student-paste" class="ds-form-input ds-form-textarea w-full" rows="3" wire:model="studentPaste" placeholder="Amina Nakato&#10;Brian Okello" data-testid="wizard-student-paste"></textarea>
            <x-button type="button" variant="outline" size="sm" class="mt-2" wire:click="applyStudentPaste" data-testid="wizard-student-paste-btn">Add from paste</x-button>
        </div>

        <div class="manual-wizard-bulk-divider"><span>or add one at a time</span></div>

        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-student-name">Student name</label>
            <input id="wizard-student-name" type="text" class="ds-form-input w-full" wire:model="studentName" data-testid="wizard-student-name" />
        </div>
        <div class="manual-wizard-bulk-pair">
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-class">Class</label>
                @if(count($structureClasses ?? []) > 0)
                    <select id="wizard-student-class" class="ds-form-input ds-form-select w-full" wire:model.live="studentClass" data-testid="wizard-student-class">
                        <option value="">Select class…</option>
                        @foreach($structureClasses as $class)
                            <option value="{{ $class['name'] }}">{{ $class['name'] }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="wizard-student-class" type="text" class="ds-form-input w-full" wire:model.live="studentClass" placeholder="e.g. P1" data-testid="wizard-student-class" />
                @endif
            </div>
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-stream">Stream</label>
                @php
                    $studentStreamOptions = [];
                    $selectedStudentClass = trim($studentClass ?? '');
                    if ($selectedStudentClass !== '') {
                        foreach ($structureClasses ?? [] as $structureRow) {
                            if (strcasecmp((string) ($structureRow['name'] ?? ''), $selectedStudentClass) === 0) {
                                $studentStreamOptions = array_values(array_map(
                                    fn ($stream) => (string) ($stream['label'] ?? ''),
                                    $structureRow['streams'] ?? []
                                ));
                                break;
                            }
                        }
                    }
                @endphp
                @if(count($studentStreamOptions) > 0)
                    <select id="wizard-student-stream" class="ds-form-input ds-form-select w-full" wire:model="studentStream" data-testid="wizard-student-stream">
                        <option value="">Base class (no stream)</option>
                        @foreach($studentStreamOptions as $streamLabel)
                            <option value="{{ $streamLabel }}">{{ $streamLabel }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="wizard-student-stream" type="text" class="ds-form-input w-full" wire:model="studentStream" placeholder="Optional" data-testid="wizard-student-stream" />
                @endif
            </div>
        </div>
        <div class="manual-wizard-bulk-pair">
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-gender">Gender</label>
                <select id="wizard-student-gender" class="ds-form-input ds-form-select w-full" wire:model="studentGender" data-testid="wizard-student-gender">
                    <option value="">Select…</option>
                    <option value="female">Female</option>
                    <option value="male">Male</option>
                </select>
            </div>
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-dob">Date of birth <span class="text-xs text-gray-400">(optional)</span></label>
                <input id="wizard-student-dob" type="date" class="ds-form-input w-full" wire:model="studentDateOfBirth" data-testid="wizard-student-dob" />
            </div>
        </div>
        <div class="manual-wizard-bulk-pair">
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-parent">Parent name</label>
                <input id="wizard-student-parent" type="text" class="ds-form-input w-full" wire:model="studentParent" />
            </div>
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-parent-phone">Parent phone</label>
                <input id="wizard-student-parent-phone" type="tel" inputmode="tel" autocomplete="tel" class="ds-form-input w-full" wire:model="studentParentPhone" placeholder="{{ $phoneExample !== '' ? $phoneExample : 'Include the country code' }}" aria-describedby="wizard-student-parent-phone-hint" data-testid="wizard-student-parent-phone" />
                <p class="text-xs text-gray-500 mt-1" id="wizard-student-parent-phone-hint" data-testid="wizard-student-parent-phone-hint">{{ $parentPhoneHint }}</p>
            </div>
        </div>
        <div class="manual-wizard-bulk-pair">
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-school-id">School Student ID <span class="text-xs text-gray-400">(optional, school-internal)</span></label>
                <input id="wizard-student-school-id" type="text" class="ds-form-input w-full" wire:model="studentSchoolStudentId" placeholder="e.g. ADM-2025-001" data-testid="wizard-student-school-id" />
            </div>
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-lin">LIN <span class="text-xs text-gray-400">(optional, national learner ID)</span></label>
                <input id="wizard-student-lin" type="text" class="ds-form-input w-full" wire:model="studentLin" placeholder="e.g. 14-digit LIN" data-testid="wizard-student-lin" />
            </div>
        </div>
        <div class="manual-wizard-bulk-pair">
            @php
                $showStudentUneb = \App\Services\OnboardingEngine::isCandidateClass(trim((string) ($studentClass ?? '')));
            @endphp
            @if($showStudentUneb)
                <div class="ds-form-group" data-testid="wizard-student-uneb-wrap">
                    <label class="ds-form-label" for="wizard-student-board-reg">UNEB Reg No. <span class="text-xs text-gray-400">(optional)</span></label>
                    <input id="wizard-student-board-reg" type="text" class="ds-form-input w-full" wire:model="studentBoardRegNumber" placeholder="e.g. U1234/567" data-testid="wizard-student-board-reg" />
                </div>
            @endif
        </div>
        <x-button type="button" variant="outline" size="sm" wire:click="addStudentDraft" data-testid="wizard-student-add">+ Add student</x-button>
        <p class="manual-wizard-bulk-footnote">Optional — skip if you’ll enrol students later. KlassApp IDs are generated automatically.</p>
        @if(count($studentDrafts ?? []) > 0)
            <x-button type="button"
                      variant="ghost"
                      size="sm"
                      class="mt-2"
                      wire:click="skipOptionalStep"
                      wire:confirm="You have students in the list that will not be saved. Skip anyway?"
                      data-testid="wizard-students-skip">Skip for now</x-button>
        @else
            <x-button type="button"
                      variant="ghost"
                      size="sm"
                      class="mt-2"
                      wire:click="skipOptionalStep"
                      data-testid="wizard-students-skip">Skip for now</x-button>
        @endif
    </div>

@elseif($stepKey === 'terms')
    <div class="manual-wizard-bulk" data-testid="wizard-terms-bulk">
        <p class="manual-wizard-bulk-help mb-3" data-testid="wizard-terms-intro">Review the three UNEB terms (or add more), mark which one is current, then Continue. Dates can be adjusted later in admin settings.</p>

        @if(count($termDrafts ?? []) > 0)
            <ul class="manual-wizard-bulk-list" data-testid="wizard-term-list">
                @foreach($termDrafts as $i => $row)
                    <li class="manual-wizard-bulk-item" wire:key="term-draft-{{ $i }}">
                        <span class="manual-wizard-bulk-item-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span class="manual-wizard-bulk-meta">
                                {{ $row['start'] ?? '' }} → {{ $row['end'] ?? '' }}
                                @if(strcasecmp((string) ($currentTermName ?? ''), (string) ($row['name'] ?? '')) === 0)
                                    · <span data-testid="wizard-term-current-badge">Current</span>
                                @endif
                            </span>
                        </span>
                        <span class="manual-wizard-bulk-item-actions">
                            @if(strcasecmp((string) ($currentTermName ?? ''), (string) ($row['name'] ?? '')) !== 0)
                                <button type="button" class="ds-btn ds-btn-outline ds-btn-sm" wire:click="markTermCurrentByIndex({{ $i }})" data-testid="wizard-term-mark-current-{{ $i }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>Mark current</button>
                            @endif
                            <button type="button" class="manual-wizard-bulk-remove" wire:click="removeTermDraft({{ $i }})" aria-label="Remove">✕</button>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="manual-wizard-bulk-divider"><span>add another term</span></div>

        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-term-name">Term name</label>
            <input id="wizard-term-name" type="text" class="ds-form-input w-full" wire:model="termName" data-testid="wizard-term-name" placeholder="e.g. Term 4" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-term-start">Starts on</label>
                <input id="wizard-term-start" type="date" class="ds-form-input w-full" wire:model="termStartsOn" data-testid="wizard-term-start" />
            </div>
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-term-end">Ends on</label>
                <input id="wizard-term-end" type="date" class="ds-form-input w-full" wire:model="termEndsOn" data-testid="wizard-term-end" />
            </div>
        </div>
        <x-button type="button" variant="outline" size="sm" wire:click="addTermDraft" data-testid="wizard-term-add">+ Add term</x-button>
        <p class="manual-wizard-bulk-footnote" data-testid="wizard-terms-footnote">Prefills Term 1–3 for UNEB schools — edit, add, or remove before Continue. Start/end dates stay editable in admin later (not a blocking step).</p>
    </div>

@elseif($stepKey === 'fees')
    <div class="manual-wizard-bulk" data-testid="wizard-fees-bulk">
        @if(count($feeDrafts ?? []) > 0)
            <ul class="manual-wizard-bulk-list" data-testid="wizard-fee-list">
                @foreach($feeDrafts as $i => $row)
                    <li class="manual-wizard-bulk-item" wire:key="fee-draft-{{ $i }}">
                        <span class="manual-wizard-bulk-item-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span class="manual-wizard-bulk-meta">
                                {{-- FLAGGED(product-decision): the fee list prints UGX. see docs/onboarding-engine-plan.md --}}
                                UGX {{ number_format((float) ($row['amount'] ?? 0)) }}
                                · {{ ($row['scope'] ?? '') === 'class' ? ($row['class'] ?: 'Class') : 'Whole school' }}
                                · {{ !empty($row['is_yearly']) ? 'Yearly' : ($row['term'] ?: 'No term') }}
                            </span>
                        </span>
                        <button type="button" class="manual-wizard-bulk-remove" wire:click="removeFeeDraft({{ $i }})" aria-label="Remove">✕</button>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-fee-name">Fee name<span class="text-red-500">*</span></label>
            <input id="wizard-fee-name" type="text" class="ds-form-input w-full" wire:model="feeName" data-testid="wizard-fee-name" placeholder="e.g. Tuition" />
        </div>
        <div class="ds-form-group">
            {{-- FLAGGED(product-decision): the fee amount label says UGX. see docs/onboarding-engine-plan.md --}}
            <label class="ds-form-label" for="wizard-fee-amount">Amount (UGX)<span class="text-red-500">*</span></label>
            <input id="wizard-fee-amount" type="number" min="1" class="ds-form-input w-full" wire:model="feeAmount" data-testid="wizard-fee-amount" />
        </div>
        <div class="ds-form-group" data-testid="wizard-fee-scope">
            <label class="ds-form-label">Applies to<span class="text-red-500">*</span></label>
            <div class="manual-wizard-check-grid">
                <label class="manual-wizard-check">
                    <input type="radio" value="whole_school" wire:model.live="feeScope" />
                    <span>Whole school</span>
                </label>
                <label class="manual-wizard-check">
                    <input type="radio" value="class" wire:model.live="feeScope" />
                    <span>One class</span>
                </label>
            </div>
        </div>
        @if(($feeScope ?? 'whole_school') === 'class')
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-fee-class">Class<span class="text-red-500">*</span></label>
                <select id="wizard-fee-class" class="ds-form-input ds-form-select w-full" wire:model="feeClass" data-testid="wizard-fee-class">
                    <option value="">Select class…</option>
                    @foreach(($structureClasses ?? []) as $class)
                        <option value="{{ $class['name'] }}">{{ $class['name'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="ds-form-group">
            <label class="manual-wizard-check" data-testid="wizard-fee-yearly">
                <input type="checkbox" wire:model.live="feeIsYearly" />
                <span>Yearly fee (not tied to a term)</span>
            </label>
        </div>
        @if(! ($feeIsYearly ?? false))
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-fee-term">Academic term</label>
                <select id="wizard-fee-term" class="ds-form-input ds-form-select w-full" wire:model="feeTerm" data-testid="wizard-fee-term">
                    <option value="">Select term…</option>
                    @foreach(($availableTermNames ?? []) as $termNameOption)
                        <option value="{{ $termNameOption }}">{{ $termNameOption }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <x-button type="button" variant="outline" size="sm" wire:click="addFeeDraft" data-testid="wizard-fee-add">+ Add fee</x-button>
        <p class="manual-wizard-bulk-footnote">Add one or more fees, then Continue to save the list.</p>
    </div>

@elseif($stepKey === 'whatsapp_verify')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-wa">Your WhatsApp number<span class="text-red-500">*</span></label>
        <input id="wizard-wa" type="tel" inputmode="tel" autocomplete="tel" class="ds-form-input w-full" wire:model="whatsappPhone" placeholder="{{ $phoneExample !== '' ? $phoneExample : 'Include the country code' }}" data-testid="wizard-wa-phone" aria-describedby="wizard-wa-phone-hint" />
        <p class="text-xs text-gray-500 mt-1" id="wizard-wa-phone-hint" data-testid="wizard-wa-phone-hint">{{ $whatsappHint }}</p>
        <p class="text-xs mt-1" style="color:#64748B;">Send the code below from this number. You can finish setup without it. Messages to parents stay off until this number is verified.</p>
    </div>
    <div class="flex flex-wrap gap-2 mt-2 mb-3">
        <button type="button" class="ds-btn ds-btn-sm ds-btn-outline" wire:click="issueWhatsAppVerificationCode" data-testid="wizard-wa-issue">
            Get a code
        </button>
    </div>
    @if($whatsappOtpStatus !== '')
        <p class="text-sm mb-3" style="color: {{ $whatsappSendFailed ? '#B45309' : '#0F766E' }};" @if($whatsappSendFailed) role="alert" @endif data-testid="wizard-wa-otp-status">{{ $whatsappOtpStatus }}</p>
    @endif
    @if($whatsappOtpDisplay !== '' && ! $whatsappVerified)
        <div wire:poll.5s="pollWhatsAppVerification" data-testid="wizard-wa-poll" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
            <p class="text-xs" style="color:#64748B;">Your code</p>
            <p class="font-bold" style="color:#0F172A;font-size:1.5rem;letter-spacing:0.2em;margin:4px 0 8px;" data-testid="wizard-wa-code">{{ $whatsappOtpDisplay }}</p>
            <p class="text-sm mb-3" style="color:#0F172A;">Or send this message yourself: Verify KlassApp: {{ $whatsappOtpDisplay }}</p>
            @if($whatsappVerifyUrl !== '')
                <a href="{{ $whatsappVerifyUrl }}" target="_blank" rel="noopener noreferrer" class="ds-btn ds-btn-sm ds-btn-primary" data-testid="wizard-wa-open" style="background:#15803D;color:#FFFFFF;">Open WhatsApp</a>
            @endif
        </div>
    @endif
    @if($whatsappVerified)
        <p class="text-sm font-medium" style="color:#15803D;" data-testid="wizard-wa-verified">Verified — you can continue.</p>
    @endif

@elseif($stepKey === 'plan_selection')
    <p class="text-sm text-gray-600 mb-3" style="color:#64748B;">
        Schools are free to start — pick a plan so capacity limits are clear. No payment is required now.
    </p>
    <div class="manual-wizard-plan-grid" data-testid="wizard-plan-cards" role="radiogroup" aria-label="Plan selection">
        @forelse(($plans ?? []) as $plan)
            @php
                $isSelected = (int) ($selectedPlanId ?? 0) === (int) $plan->id;
                $isFreemium = strcasecmp((string) $plan->name, 'Freemium') === 0
                    || strcasecmp((string) ($plan->display_name ?? ''), 'Freemium') === 0
                    || (int) $plan->amount === 0;
            @endphp
            <button type="button"
                    class="manual-wizard-plan-card {{ $isSelected ? 'is-selected' : '' }}"
                    wire:click="selectPlan({{ $plan->id }})"
                    role="radio"
                    aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                    data-testid="wizard-plan-{{ $plan->id }}"
                    data-plan-name="{{ $plan->name }}">
                <span class="manual-wizard-plan-name">{{ $plan->display_name ?: ucfirst($plan->name) }}</span>
                <span class="manual-wizard-plan-price">
                    @if(!empty($plan->is_custom_pricing))
                        Contact us
                    @elseif((int) $plan->amount > 0)
                        ${{ number_format((float) $plan->amount) }} / {{ $plan->cycle }} days
                    @else
                        Free
                    @endif
                </span>
                @if($isFreemium)
                    <span class="manual-wizard-plan-hint">Recommended to start</span>
                @endif
            </button>
        @empty
            <p class="text-sm text-red-600" role="alert" data-testid="wizard-plan-empty">No plans are available yet. Contact support.</p>
        @endforelse
    </div>

@elseif($stepKey === 'review')
    <div class="manual-wizard-review" data-testid="wizard-review">
        <p class="manual-wizard-review-intro">
            Confirm everything looks right. Use Edit to jump back to a step — your later answers stay saved.
        </p>
        <div class="manual-wizard-review-panels" data-testid="wizard-review-card">
            @forelse(($reviewSummary ?? []) as $row)
                <section class="manual-wizard-review-panel" data-testid="wizard-review-{{ $row['key'] }}">
                    <header class="manual-wizard-review-panel-head">
                        <div class="manual-wizard-review-panel-title">
                            <span class="manual-wizard-review-icon" aria-hidden="true">{{ $row['icon'] }}</span>
                            <h3 class="manual-wizard-review-label">{{ $row['label'] }}</h3>
                        </div>
                        <button type="button"
                                class="manual-wizard-review-edit"
                                wire:click="editSection('{{ $row['key'] }}')"
                                data-testid="wizard-edit-{{ $row['key'] }}">
                            Edit
                        </button>
                    </header>
                    <div class="manual-wizard-review-value">{{ $row['value'] }}</div>
                </section>
            @empty
                <p class="text-sm text-gray-600">Nothing to review yet.</p>
            @endforelse
        </div>
    </div>
@endif
