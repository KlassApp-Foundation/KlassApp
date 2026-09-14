{{-- SPDX-License-Identifier: MIT --}}
@php $stepKey = $stepKey ?? ''; @endphp

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
                <option value="Uganda">Uganda</option>
                <option value="Kenya">Kenya</option>
                <option value="Tanzania">Tanzania</option>
            @endif
        </select>
        <p class="text-xs text-gray-500 mt-1">Saves both country and Toshi registration country.</p>
    </div>

@elseif($stepKey === 'curriculum')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-curriculum">Board / Curriculum<span class="text-red-500">*</span></label>
        <select id="wizard-curriculum" class="ds-form-input ds-form-select w-full" wire:model="curriculum">
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
        <p class="manual-wizard-structure-intro">
            Your classes are ready from school category. Optionally add streams or invite a class teacher —
            both are optional. Click Continue anytime to skip.
        </p>

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
                                              data-testid="wizard-structure-add-stream-{{ $sid }}">Add</x-button>
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
                                               placeholder="Phone (optional)"
                                               data-testid="wizard-structure-ct-phone-{{ $sid }}" />
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
        @if(count($existingSubjectNames ?? []) > 0)
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3" data-testid="wizard-subjects-seeded">
                <p class="text-sm font-medium text-green-900" style="color:#14532D;">
                    Subjects already set up
                </p>
                <p class="text-xs text-green-800 mt-1" style="color:#166534;">
                    These came from your school category (or a previous save). Review them below — click Next to continue, or add another subject.
                </p>
                <ul class="mt-2 flex flex-wrap gap-2" data-testid="wizard-subjects-seeded-list">
                    @foreach($existingSubjectNames as $existingSubject)
                        <li class="text-xs font-medium px-2 py-1 rounded bg-white border border-green-200 text-green-900">{{ $existingSubject }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-subject">Add another subject (optional)</label>
                <input id="wizard-subject" type="text" class="ds-form-input w-full" wire:model="subjectName" placeholder="e.g. Music" autocomplete="off" />
            </div>
        @else
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-subject">First subject<span class="text-red-500">*</span></label>
                <input id="wizard-subject" type="text" class="ds-form-input w-full" wire:model="subjectName" placeholder="e.g. Mathematics" autocomplete="off" />
            </div>
        @endif
    </div>

@elseif($stepKey === 'teachers')
    <div class="manual-wizard-bulk" data-testid="wizard-teachers-bulk">
        <div class="manual-wizard-bulk-toolbar">
            <a href="{{ asset('templates/teacher-upload-template.xlsx') }}"
               download
               class="manual-wizard-bulk-link"
               data-testid="wizard-teacher-template">Download template</a>
            <label class="manual-wizard-bulk-upload">
                <svg class="manual-wizard-bulk-upload-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload file</span>
                <input type="file" class="manual-wizard-bulk-file" wire:model="teacherUpload" accept=".csv,.xlsx,.xls,.txt,.docx,.pdf" data-testid="wizard-teacher-upload" />
            </label>
        </div>
        <div wire:loading wire:target="teacherUpload" class="manual-wizard-bulk-loading">Parsing file…</div>

        @if(count($teacherDrafts ?? []) > 0)
            <ul class="manual-wizard-bulk-list" data-testid="wizard-teacher-list">
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
            </ul>
        @endif

        <div class="ds-form-group" data-testid="wizard-teacher-paste-block">
            <label class="ds-form-label" for="wizard-teacher-paste">Paste names (one per line)</label>
            <textarea id="wizard-teacher-paste" class="ds-form-input ds-form-textarea w-full" rows="3" wire:model="teacherPaste" placeholder="John Ssali&#10;Grace Nakamya" data-testid="wizard-teacher-paste"></textarea>
            <x-button type="button" variant="outline" size="sm" class="mt-2" wire:click="applyTeacherPaste" data-testid="wizard-teacher-paste-btn">Add from paste</x-button>
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
            <input id="wizard-teacher-phone" type="tel" inputmode="tel" autocomplete="tel" class="ds-form-input w-full" wire:model="teacherPhone" placeholder="+2567…" data-testid="wizard-teacher-phone" />
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
        <x-button type="button" variant="outline" size="sm" wire:click="addTeacherDraft" data-testid="wizard-teacher-add">+ Add teacher</x-button>
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
            <a href="{{ route('admin.students.upload-template') }}"
               class="manual-wizard-bulk-link"
               data-testid="wizard-student-template">Download template</a>
            <p class="manual-wizard-bulk-help w-full" data-testid="wizard-student-stream-help">
                @if(!empty($schoolHasStreams))
                    When a class has streams, pick a stream by default. You can still choose “Base class (no stream)” to enrol on the undivided class.
                @else
                    Leave Stream blank if your school doesn't use streams.
                @endif
            </p>
            <label class="manual-wizard-bulk-upload">
                <svg class="manual-wizard-bulk-upload-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload file</span>
                <input type="file" class="manual-wizard-bulk-file" wire:model="studentUpload" accept=".csv,.xlsx,.xls,.txt,.docx,.pdf" data-testid="wizard-student-upload" />
            </label>
        </div>
        <div wire:loading wire:target="studentUpload" class="manual-wizard-bulk-loading">Parsing file…</div>

        @if(count($studentDrafts ?? []) > 0)
            <ul class="manual-wizard-bulk-list" data-testid="wizard-student-list">
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
            </ul>
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
                <input id="wizard-student-parent-phone" type="text" class="ds-form-input w-full" wire:model="studentParentPhone" placeholder="+2567…" />
            </div>
        </div>
        <div class="manual-wizard-bulk-pair">
            <div class="ds-form-group">
                <label class="ds-form-label" for="wizard-student-school-id">School Student ID <span class="text-xs text-gray-400">(optional)</span></label>
                <input id="wizard-student-school-id" type="text" class="ds-form-input w-full" wire:model="studentSchoolStudentId" placeholder="e.g. ADM-2025-001" data-testid="wizard-student-school-id" />
            </div>
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
                                <button type="button" class="manual-wizard-bulk-link" wire:click="markTermCurrent('{{ str_replace("'", "\\'", $row['name']) }}')" data-testid="wizard-term-mark-current-{{ $i }}">Mark current</button>
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
        <input id="wizard-wa" type="text" class="ds-form-input w-full" wire:model="whatsappPhone" placeholder="+2567…" data-testid="wizard-wa-phone" />
        <p class="text-xs text-gray-500 mt-1">Same OTP flow as Toshi — we send a 6-digit code, then link the number only after you verify it.</p>
    </div>
    <div class="flex flex-wrap gap-2 mt-2 mb-3">
        <button type="button" class="ds-btn ds-btn-sm ds-btn-outline" wire:click="sendWhatsAppVerificationCode" data-testid="wizard-wa-send-otp">
            Send verification code
        </button>
    </div>
    @if($whatsappOtpStatus !== '')
        <p class="text-sm mb-3" style="color:#0F766E;" data-testid="wizard-wa-otp-status">{{ $whatsappOtpStatus }}</p>
    @endif
    @if(! $whatsappVerified)
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-wa-otp">6-digit code<span class="text-red-500">*</span></label>
            <input id="wizard-wa-otp" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                   class="ds-form-input w-full" wire:model="whatsappOtpInput" placeholder="123456"
                   data-testid="wizard-wa-otp-input" />
        </div>
        <button type="button" class="ds-btn ds-btn-sm ds-btn-primary mt-1" wire:click="verifyWhatsAppCode" data-testid="wizard-wa-verify-otp">
            Verify code
        </button>
    @else
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
            <p class="text-sm text-red-600" role="alert">No plans are available yet. Contact support.</p>
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
