<div x-data="{ hasText: false }"
     x-on:toshi-run-plan-step.window="setTimeout(() => $wire.executeNextPlanStep(), 200)"
     x-on:toshi-maximize.window="$wire.maximize()"
     data-toshi-root
     class="toshi-root">
    <div id="toshi-pill"
         wire:click="show"
         onclick="document.body.classList.remove('toshi-collapsed');"
         class="toshi-pill"
         style="{{ $visible || $maximized ? 'display: none;' : '' }}">
        <div class="toshi-pill-avatar">
            <img src="{{ asset('images/klassapp-logo.svg') }}" class="toshi-pill-logo" alt="KlassApp">
        </div>
        <span class="toshi-pill-text">Toshi Agent</span>
        <div class="toshi-pill-badge">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor"><rect x="1" y="4" width="3" height="6" rx="1"/><rect x="5.5" y="1" width="3" height="12" rx="1"/><rect x="10" y="3" width="3" height="8" rx="1"/></svg> Talk
        </div>
    </div>

    <div id="toshi-panel"
         class="toshi-panel"
         style="{{ $visible ? 'display: flex;' : 'display: none;' }}">
        <div class="toshi-header">
            <div class="toshi-header-logo">
                <img src="{{ asset('images/klassapp-logo.svg') }}" alt="KlassApp">
                <span>Toshi</span>
            </div>
            {{-- Mode dropdown — shared partial --}}
            @include('livewire.partials.toshi-mode-dropdown')
            <div class="toshi-header-actions">
                <button wire:click="resetSchoolOnboarding" class="toshi-header-btn" title="Restart onboarding" style="font-size:11px;font-weight:600;color:#D97706;">
                    ↻ Restart
                </button>
                <button wire:click="maximize" class="toshi-header-btn" title="Expand" data-testid="toshi-expand">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6V2h4M10 2h4v4M14 10v4h-4M6 14H2v-4"/></svg>
                </button>
                <button onclick="document.body.classList.add('toshi-collapsed');document.getElementById('toshi-toggle').textContent='◀';" class="toshi-header-btn" title="Close">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 4l8 8M12 4l-8 8"/></svg>
                </button>
            </div>
        </div>
        <div class="toshi-messages-area"
             x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight); $wire.$watch('messages', () => $nextTick(() => $el.scrollTop = $el.scrollHeight))">
            @foreach($messages as $msg)
                @php $isUser = $msg['role'] === 'user'; @endphp
                <div class="{{ $isUser ? 'toshi-msg-row-end' : 'toshi-msg-row' }}">
                    <div>
                        <div class="toshi-msg-label">{{ $isUser ? 'You' : 'Toshi' }}</div>
                        <div class="{{ $isUser ? 'toshi-msg-user' : 'toshi-msg-bot' }}">
                            @if(!$isUser)
                                @if(!empty($msg['_streamId']))
                                    <span x-stream="{{ $msg['_streamId'] }}"></span>
                                @endif
                                {!! preg_replace('/\*\*(.+?)\*\*/','<strong>$1</strong>',nl2br(e($msg['text']))) !!}
                            @else
                                {!! nl2br(e($msg['text'])) !!}
                            @endif
                            @if(!$isUser && $loop->last && $pendingToolConfirm)
                                @php
                                    $toolInfo = $this->getToolDisplay($pendingToolConfirm['tool']);
                                    $toolArgs = $this->getToolArgsDisplay($pendingToolConfirm['args'] ?? []);
                                @endphp
                                @include('toshi-ui::components.tool-confirm-card', [
                                    'toolName' => $toolInfo['label'],
                                    'toolIcon' => $toolInfo['icon'],
                                    'params' => $toolArgs,
                                    'state' => 'pending',
                                    'wireKey' => 'tpc-' . md5($msg['text'] ?? ''),
                                    'confirmMethod' => 'confirmYes',
                                    'cancelMethod' => 'cancelAction',
                                    'cancelParam' => md5($msg['text'] ?? ''),
                                ])
                            @elseif(!$isUser && $loop->last && $cancelledToolConfirm)
                                @php
                                    $toolInfo = $this->getToolDisplay($cancelledToolConfirm['tool']);
                                    $toolArgs = $this->getToolArgsDisplay($cancelledToolConfirm['args'] ?? []);
                                @endphp
                                @include('toshi-ui::components.tool-confirm-card', [
                                    'toolName' => $toolInfo['label'],
                                    'toolIcon' => $toolInfo['icon'],
                                    'params' => $toolArgs,
                                    'state' => 'cancelled',
                                    'wireKey' => 'tcc-' . md5(json_encode($cancelledToolConfirm)),
                                ])
                            @elseif(!$isUser && $loop->last && $planSteps)
                                @include('toshi-ui::components.plan-card', [
                                    'steps' => $planSteps,
                                    'wireKey' => 'plan-' . md5(json_encode($planSteps)),
                                    'confirmMethod' => 'confirmPlan',
                                    'cancelMethod' => 'cancelPlan',
                                    'executingVerb' => $this->getPlanExecutingVerb(),
                                ])
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            {{-- Subject inline form --}}
            @if($showSubjectForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects')
            <div class="toshi-form-card">
                <div class="toshi-section-title" style="margin-bottom: 10px;">Add Subject</div>
                @php $subjects = $this->actionData['subjects'] ?? []; @endphp
                @if(count($subjects) > 0)
                <div class="toshi-spacer-8">
                    @foreach($subjects as $si => $s)
                    <div class="toshi-row" style="padding: 4px 0; font-size: 12px; color: #4d4c48; border-bottom: 1px solid #f5f4ed;">
                        <span class="flex-1">{{ $s }}</span>
                        <button wire:click="removeSubject({{ $si }})" type="button"
                                class="toshi-remove-btn">✕</button>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="toshi-flex-col">
                    <input type="text" wire:model="subjectFormName" placeholder="Subject name *"
                           class="toshi-input">
                    <input type="text" wire:model="subjectFormCode" placeholder="Subject code (optional)"
                           class="toshi-input">
                    <select wire:model="subjectFormType"
                            class="toshi-input">
                        <option value="core">Core</option>
                        <option value="elective">Elective</option>
                    </select>
                    <div class="flex gap-2">
                        <button wire:click="saveSubject" type="button"
                                class="toshi-btn-primary-sm">
                            + Add Subject
                        </button>
                        <button wire:click="doneSubjects" type="button"
                                class="toshi-btn-done">
                            Continue ({{ count($this->actionData['subjects'] ?? []) }})
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Teacher inline form --}}
            @if($showTeacherForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers')
            <div class="toshi-form-card">
                <div class="toshi-flex-row">
                    <span class="toshi-section-title">Add Teacher</span>
                    <div class="flex items-center gap-2 toshi-ml-auto">
                        <a href="{{ asset('templates/teacher-upload-template.xlsx') }}" download
                           class="toshi-tag-link">Download Template</a>
                        <span style="font-size: 10px; color: #87867f; cursor: help;" title="CSV/XLSX: columns Name, Email, Subjects, Classes. TXT: one name per line.">ⓘ</span>
                        <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                            Upload File
                            <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                        </label>
                    </div>
                </div>
                @php $teachers = $this->actionData['teachers'] ?? []; @endphp
                @if(count($teachers) > 0)
                <div class="toshi-spacer-8">
                    @foreach($teachers as $ti => $t)
                    <div class="toshi-list-item">
                        <span class="flex-1">{{ $t }}</span>
                        <button wire:click="removeTeacher({{ $ti }})" type="button"
                                class="toshi-remove-btn">✕</button>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="toshi-flex-col">
                    <input type="text" wire:model="teacherFormName" placeholder="Teacher name *"
                           class="toshi-input">
                    <input type="email" wire:model="teacherFormEmail" placeholder="Email (optional)"
                           class="toshi-input">
                    <input type="text" wire:model="teacherFormPhone" placeholder="WhatsApp number (optional)"
                           class="toshi-input">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <div class="toshi-sub-label">Subject(s)</div>
                            <input type="text" wire:model="teacherFormSubjects" placeholder="e.g. Math, English" list="subject-list"
                                   class="toshi-input">
                            <datalist id="subject-list">
                                @php
                                    $allSubjects = [];
                                    foreach (($this->subjects ?? []) as $class => $subs) {
                                        $allSubjects = array_merge($allSubjects, $subs);
                                    }
                                    $allSubjects = array_unique($allSubjects ?? []);
                                @endphp
                                @foreach($allSubjects as $s)
                                <option value="{{ $s }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="flex-1">
                            <div class="toshi-sub-label">Class(es)</div>
                            <input type="text" wire:model="teacherFormClasses" placeholder="e.g. P1, P2" list="class-list"
                                   class="toshi-input">
                            <datalist id="class-list">
                                @foreach($this->standards ?? [] as $std)
                                <option value="{{ $std['name'] }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="saveTeacher" type="button"
                                class="toshi-btn-primary-sm">
                            + Add Teacher
                        </button>
                        <button wire:click="doneTeachers" type="button"
                                class="toshi-btn-done">
                            Continue ({{ count($this->actionData['teachers'] ?? []) }})
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Student inline form --}}
            @if($showStudentForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'students')
            <div class="toshi-form-card">
                <div class="toshi-flex-row">
                    <span class="toshi-section-title">Add Student</span>
                    <div class="flex items-center gap-2 toshi-ml-auto">
                        <a href="{{ asset('templates/student-upload-template.xlsx') }}" download
                           class="toshi-tag-link">Download Template</a>
                        <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                            Upload File
                            <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                        </label>
                    </div>
                </div>
                @php $students = $this->actionData['students'] ?? []; @endphp
                @if(count($students) > 0)
                <div class="toshi-spacer-8">
                    @foreach($students as $si => $s)
                    <div class="toshi-list-item">
                        <span class="flex-1">{{ $s['name'] }}@if(!empty($s['class'])) <span style="color: #87867f;">({{ $s['class'] }})</span>@endif</span>
                        <button wire:click="removeStudent({{ $si }})" type="button"
                                class="toshi-remove-btn">✕</button>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="toshi-flex-col">
                    <input type="text" wire:model="studentFormName" placeholder="Student name *"
                           class="toshi-input">
                    <input type="text" wire:model="studentFormStream" placeholder="Stream (optional)"
                           class="toshi-input">
<select wire:model="studentFormType"
        class="toshi-input">
    <option value="">Type (optional)</option>
    <option value="boarding">Boarding</option>
    <option value="day">Day Scholar</option>
</select>
<select wire:model="studentFormType"
                                                    class="toshi-input">
                                                <option value="">Type (optional)</option>
                                                <option value="boarding">Boarding</option>
                                                <option value="day">Day Scholar</option>
                                            </select>
                    <input type="text" wire:model="studentFormClass" placeholder="Class *" list="student-class-list"
                           class="toshi-input">
                    <datalist id="student-class-list">
                        @foreach($this->standards ?? [] as $std)
                        <option value="{{ $std['name'] }}">
                        @endforeach
                    </datalist>
                    <input type="text" wire:model="studentFormParent" placeholder="Parent name (optional)"
                           class="toshi-input">
                    <input type="text" wire:model="studentFormParentPhone" placeholder="Parent phone (optional)"
                           class="toshi-input">
                    <div class="flex gap-2">
                        <button wire:click="saveStudent" type="button"
                                class="toshi-btn-primary-sm">
                            + Add Student
                        </button>
                        <button wire:click="doneStudents" type="button"
                                class="toshi-btn-done">
                            Continue ({{ count($this->actionData['students'] ?? []) }})
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Fee inline form --}}
            @if($showFeeForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'fees')
            <div class="toshi-form-card">
                <div class="toshi-flex-row">
                    <span class="toshi-section-title">Add Fee</span>
                    <div class="flex items-center gap-2 toshi-ml-auto">
                        <a href="{{ asset('templates/fee-upload-template.xlsx') }}" download
                           class="toshi-tag-link">Download Template</a>
                        <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                            Upload File
                            <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                        </label>
                    </div>
                </div>
                @php $fees = $this->actionData['fees'] ?? []; @endphp
                @if(count($fees) > 0)
                <div class="toshi-spacer-8">
                    @foreach($fees as $fi => $f)
                    <div class="toshi-list-item">
                        <span class="flex-1">{{ $f['name'] }}@if(!empty($f['amount'])) — {{ number_format((float)$f['amount'], 0) }} UGX @endif</span>
                        <button wire:click="removeFee({{ $fi }})" type="button"
                                class="toshi-remove-btn">✕</button>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="toshi-flex-col">
                    <input type="text" wire:model="feeFormName" placeholder="Fee name *"
                           class="toshi-input">
                    <input type="number" wire:model="feeFormAmount" placeholder="Amount (UGX) *"
                           class="toshi-input">
                    <select wire:model="feeFormLevel"
                            class="toshi-input">
                        <option value="">Level (optional)</option>
                        <option value="nursery">Nursery</option>
                        <option value="primary">Primary</option>
                        <option value="secondary">Secondary</option>
                        <option value="all">All Levels</option>
                    </select>
                    <input type="text" wire:model="feeFormClass" placeholder="Class (optional)" list="fee-class-list"
                           class="toshi-input">
                    <datalist id="fee-class-list">
                        @foreach($this->standards ?? [] as $std)
                        <option value="{{ $std['name'] }}">
                        @endforeach
                    </datalist>
                    <select wire:model="feeFormTerm"
                            class="toshi-input">
                        <option value="">Term (optional)</option>
                        <option value="Term I">Term I</option>
                        <option value="Term II">Term II</option>
                        <option value="Term III">Term III</option>
                    </select>
                    <div class="flex gap-2">
                        <button wire:click="saveFee" type="button"
                                class="toshi-btn-primary-sm">
                            + Add Fee
                        </button>
                        <button wire:click="doneFees" type="button"
                                class="toshi-btn-done">
                            Continue ({{ count($this->actionData['fees'] ?? []) }})
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Exam inline form --}}
            @if($showExamForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'exams')
            <div class="toshi-form-card">
                <div class="toshi-flex-row">
                    <span class="toshi-section-title">Add Exam</span>
                    <div class="flex items-center gap-2 toshi-ml-auto">
                        <a href="{{ asset('templates/exam-upload-template.xlsx') }}" download
                           class="toshi-tag-link">Download Template</a>
                        <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                            Upload File
                            <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                        </label>
                    </div>
                </div>
                @php $exams = $this->actionData['exams'] ?? []; @endphp
                @if(count($exams) > 0)
                <div class="toshi-spacer-8">
                    @foreach($exams as $ei => $e)
                    <div class="toshi-list-item">
                        <span class="flex-1">{{ $e['type'] }} — {{ $e['term'] }}</span>
                        <button wire:click="removeExam({{ $ei }})" type="button"
                                class="toshi-remove-btn">✕</button>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="toshi-flex-col">
                    <select wire:model="examFormTerm"
                            class="toshi-input">
                        <option value="">Term *</option>
                        <option value="Term I">Term I</option>
                        <option value="Term II">Term II</option>
                        <option value="Term III">Term III</option>
                    </select>
                    <input type="text" wire:model="examFormType" placeholder="Exam type * (e.g. Midterm, Final)"
                           class="toshi-input">
                    <select wire:model="examFormStatus"
                            class="toshi-input">
                        <option value="">Status</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select wire:model="examFormLevel"
                            class="toshi-input">
                        <option value="">Level</option>
                        <option value="nursery">Nursery</option>
                        <option value="primary">Primary</option>
                        <option value="secondary">Secondary</option>
                        <option value="all">All Levels</option>
                    </select>
                    <input type="text" wire:model="examFormClass" placeholder="Class" list="exam-class-list"
                           class="toshi-input">
                    <datalist id="exam-class-list">
                        @foreach($this->standards ?? [] as $std)
                        <option value="{{ $std['name'] }}">
                        @endforeach
                    </datalist>
                    <input type="text" wire:model="examFormSubject" placeholder="Subject" list="exam-subject-list"
                           class="toshi-input">
                    <datalist id="exam-subject-list">
                        @php
                            $allSubs = [];
                            foreach (($this->subjects ?? []) as $cls => $subs) {
                                $allSubs = array_merge($allSubs, $subs);
                            }
                            $allSubs = array_unique($allSubs ?? []);
                        @endphp
                        @foreach($allSubs as $s)
                        <option value="{{ $s }}">
                        @endforeach
                    </datalist>
                    <input type="text" wire:model="examFormTeacher" placeholder="Teacher" list="exam-teacher-list"
                           class="toshi-input">
                    <datalist id="exam-teacher-list">
                        @foreach($this->teacherList ?? [] as $t)
                        <option value="{{ $t }}">
                        @endforeach
                    </datalist>
                    <div class="flex gap-2">
                        <button wire:click="saveExam" type="button"
                                class="toshi-btn-primary-sm">
                            + Add Exam
                        </button>
                        <button wire:click="doneExams" type="button"
                                class="toshi-btn-outline-sm">
                            Skip
                        </button>
                        <button wire:click="doneExams" type="button"
                                class="toshi-btn-done-sm">
                            Continue ({{ count($this->actionData['exams'] ?? []) }})
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Plan Selection Buttons --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'plan_selection' && !$selectedPlanId)
            <div style="display: flex; flex-direction: column; gap: 8px; padding: 8px 0;">
                @php $plans = \App\Models\Plan::where('is_active', 1)->orderBy('order')->get(); @endphp
                @foreach($plans as $plan)
                <button wire:click="selectPlan({{ $plan->id }})"
                        class="toshi-option-card"
                        onmouseover="this.style.borderColor='#22C55E';this.style.boxShadow='0 2px 8px rgba(34,197,94,0.15)'"
                        onmouseout="this.style.borderColor='#e8e6dc';this.style.boxShadow='none'">
                    <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; background: #22C55E; color: white;">{{ $loop->first ? '🆓' : ($loop->iteration === 2 ? '⭐' : '👑') }}</div>
                    <div class="flex-1">
                        <div style="font-weight: 600;">{{ ucfirst($plan->name) }}</div>
                        <div style="color: #5e5d59; font-size: 12px; margin-top: 2px;">
                            @if($plan->is_custom_pricing)
                                Contact Us
                            @elseif($plan->amount > 0)
                                ${{ number_format($plan->amount) }} / {{ $plan->cycle }} days
                            @else
                                Free
                            @endif
                        </div>
                    </div>
                </button>
                @endforeach
            </div>
            @endif

            {{-- School Category Buttons (complete-mode action step) --}}
            @if($actionStep === 'onboarding_school_category')
            <div style="display: flex; flex-direction: column; gap: 8px; padding: 8px 0;" data-testid="toshi-category-options">
                <div style="font-size: 12px; font-weight: 600; color: #5e5d59; margin-bottom: 4px;">→ School category</div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    @foreach(\App\Services\SchoolCategorySeeder::CATEGORIES as $value => $label)
                    <button wire:click="selectSchoolCategory('{{ $value }}')" class="toshi-btn" data-testid="toshi-category-{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- School Category Buttons (create-flow school_info substep) --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'school_info' && $substep === 2)
            <div style="display: flex; flex-direction: column; gap: 8px; padding: 8px 0;" data-testid="toshi-category-options">
                <div style="font-size: 12px; font-weight: 600; color: #5e5d59; margin-bottom: 4px;">→ School category</div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    @foreach(\App\Services\SchoolCategorySeeder::CATEGORIES as $value => $label)
                    <button wire:click="selectSchoolCategory('{{ $value }}')" class="toshi-btn" data-testid="toshi-category-{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Co-admin Invite Buttons --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'co_admin_invite' && $substep === 0)
            <div style="font-size: 12px; font-weight: 600; color: #5e5d59; margin-bottom: 2px; padding: 8px 0 0;">→ Would you like to add a co-admin for this school?</div>
            <div style="display: flex; gap: 8px; padding: 4px 0 8px;">
                <button wire:click="coAdminInviteYes" class="toshi-btn" style="background: #F0FDF4; border-color: #22C55E; font-weight: 600;">Yes, add a co-admin</button>
                <button wire:click="coAdminInviteSkip" class="toshi-btn">Skip</button>
            </div>
            @endif

            {{-- Teacher Selection (complete mode — promote existing staff) --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'co_admin_invite' && $substep === 10)
            <div style="display: flex; flex-direction: column; gap: 6px; padding: 8px 0;">
                @php $staff = $this->getAvailableStaff(); @endphp
                @foreach($staff as $teacher)
                <button wire:click="promoteCoAdmin({{ $teacher->id }})"
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #FFFFFF; border: 1px solid #e8e6dc; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #4d4c48;"
                        onmouseover="this.style.borderColor='#c96442';this.style.boxShadow='0 2px 8px rgba(30,111,217,0.12)'"
                        onmouseout="this.style.borderColor='#e8e6dc';this.style.boxShadow='none'">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #faf9f5; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #c96442; font-size: 12px;">{{ strtoupper(substr($teacher->name, 0, 1)) }}</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600;">{{ $teacher->name }}</div>
                        <div class="toshi-sm-text">{{ $teacher->email }}</div>
                    </div>
                </button>
                @endforeach
                <button wire:click="promoteCoAdminAddNew"
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #f5f4ed; border: 1px dashed #CBD5E1; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #5e5d59; margin-top: 4px;"
                        onmouseover="this.style.borderColor='#22C55E';this.style.color='#166534'"
                        onmouseout="this.style.borderColor='#CBD5E1';this.style.color='#5e5d59'">
                    <span style="font-size: 16px;">＋</span>
                    <span>Add someone else (not on this list)</span>
                </button>
            </div>
            @endif

            {{-- Confirmation Buttons (shown when substep indicates awaiting yes/no) --}}
            {{-- Success Card (shown after commit) --}}
            @if($step === 99 && !empty($reviewData['committed']))
            @php $isComplete = ($reviewData['mode'] ?? 'create') === 'complete'; @endphp
            <div style="background: #FFFFFF; border: 1px solid #e8e6dc; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 4px 0; text-align: center;">
                <div style="background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%); padding: 24px 16px;">
                    <div style="font-size: 40px; margin-bottom: 8px;">🎉</div>
                    <div style="font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.01em;">
                        {{ $isComplete ? 'School Updated!' : 'School Created!' }}
                    </div>
                    <div style="font-size: 13px; color: rgba(255,255,255,0.85); margin-top: 4px;">
                        {{ $reviewData['schoolName'] ?? '' }} {{ $isComplete ? 'has been updated' : 'is now live on KlassApp' }}
                    </div>
                </div>
                <div style="padding: 16px; display: flex; flex-direction: column; gap: 10px;">
                    @if(!$isComplete)
                    <div class="toshi-banner-success">
                        <div class="toshi-green-label">Admin Login Credentials</div>
                        <div class="toshi-info-value">{{ $reviewData['adminEmail'] ?? '—' }}</div>
                        <div class="toshi-info-sub">Password: <strong>{{ $reviewData['adminPassword'] ?? 'password' }}</strong></div>
                        <div style="font-size: 12px; color: #5e5d59; margin-top: 2px;">📱 {{ $reviewData['adminPhone'] ?? '—' }}</div>
                    </div>
                    @if(!empty($reviewData['coAdminEmail']))
                    <div class="toshi-banner-success">
                        <div class="toshi-green-label">Co-Admin</div>
                        <div class="toshi-info-value">{{ $reviewData['coAdminEmail'] ?? '—' }}</div>
                        @if(!empty($reviewData['coAdminPromoted']))
                        <div style="font-size: 12px; color: #166534; margin-top: 2px;">✅ Promoted from teacher — they keep their existing password</div>
                        @else
                        <div class="toshi-info-sub">Password: <strong>{{ $reviewData['coAdminPassword'] ?? 'password' }}</strong></div>
                        @endif
                    </div>
                    @endif
                    <div style="font-size: 12px; color: #5e5d59; line-height: 1.5;">
                        Share these credentials with the school admin. They can log in at the <strong>KlassApp login page</strong>.
                    </div>
                    @else
                    <div style="font-size: 13px; color: #5e5d59; line-height: 1.5;">
                        ✅ Your school setup is complete. Toshi will remind you if anything needs attention later.
                    </div>
                    @endif
                </div>
                <div style="padding: 0 16px 16px;">
                    <button wire:click="resetOnboarding"
                            style="width: 100%; padding: 11px; background: #141413; color: #FFFFFF; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.background='#4d4c48'" onmouseout="this.style.background='#141413'">
                        @if($isComplete) ✅ Done — Ask Toshi @else + Onboard Another School @endif
                    </button>
                </div>
            </div>
            @endif

            {{-- Step progress bar — dots for all 15 steps --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] !== 'review')
            <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; margin: 0 10px; background: #f5f4ed; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 3px; flex: 1;">
                    @foreach($steps as $i => $name)
                        @php
                            $isDone = $i < $step;
                            $isCurrent = $i === $step;
                        @endphp
                        <div class="toshi-progress-dot {{ $isDone ? 'toshi-progress-dot-done' : ($isCurrent ? 'toshi-progress-dot-current' : 'toshi-progress-dot-pending') }}"></div>
                    @endforeach
                </div>
                <span class="toshi-progress-step">{{ $step + 1 }}/{{ count($steps) }}</span>
                @if(in_array($steps[$step] ?? '', $mandatorySteps ?? []))
                <span class="toshi-badge-required">Required</span>
                @else
                <span class="toshi-badge-optional">Optional</span>
                @endif
            </div>
            {{-- Step progress list — always visible, not just in maximized modal --}}
            <div style="display: flex; flex-wrap: wrap; gap: 4px; padding: 6px 14px; margin: 0 10px;">
                @foreach($steps as $i => $name)
                    @php
                        $isDone = $i < $step;
                        $isCurrent = $i === $step;
                        $label = ucfirst(str_replace('_', ' ', $name));
                    @endphp
                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; padding: 3px 8px; border-radius: 4px; background: {{ $isDone ? '#F0FDF4' : ($isCurrent ? '#FFFFFF' : '#F1F5F9') }}; color: {{ $isDone ? '#15803D' : ($isCurrent ? '#141413' : '#94A3B8') }}; font-weight: {{ $isCurrent ? '600' : '400' }}; border: 1px solid {{ $isCurrent ? '#22C55E' : 'transparent' }}; white-space: nowrap;">
                        {{ $isDone ? '✓' : ($isCurrent ? '→' : '') }} {{ $label }}
                    </span>
                @endforeach
            </div>
            @endif

            {{-- Review Card (shown on review step) --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'review' && !empty($reviewData) && empty($reviewData['committed']))
            <div class="toshi-review-card">
                {{-- Header --}}
                <div class="toshi-review-header">
                    <span>📋 Review &amp; Confirm</span>
                </div>

                {{-- Body --}}
                <div class="toshi-review-body">
                    {{-- Plan + School --}}
                    <div class="flex" style="gap: 10px;">
                        <div class="toshi-info-card">
                            <div class="toshi-uppercase-label">Plan</div>
                            <div class="toshi-section-title toshi-gap-3">{{ $reviewData['plan'] }}</div>
                        </div>
                        <div class="toshi-info-card">
                            <div class="toshi-uppercase-label">School</div>
                            <div class="toshi-section-title toshi-gap-3">{{ $reviewData['schoolName'] }}</div>
                            <div class="toshi-sm-text">{{ ucfirst($reviewData['schoolType']) }} · {{ $reviewData['curriculum'] ?? 'UNEB' }}</div>
                            <div class="toshi-tiny-note">EMIS: {{ $reviewData['ministryCode'] ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- Admin --}}
                    <div class="toshi-info-card" style="flex: none;">
                        <div class="toshi-uppercase-label">Admin Account</div>
                        <div class="toshi-section-title toshi-gap-3">{{ $reviewData['adminName'] }}</div>
                        <div class="toshi-sm-text">{{ $reviewData['adminEmail'] }} · {{ $reviewData['adminPhone'] }}</div>
                    </div>

                    @if(!empty($reviewData['coAdminName']))
                    {{-- Co-Admin --}}
                    <div class="toshi-info-card" style="flex: none;">
                        <div class="toshi-uppercase-label">Co-Admin</div>
                        <div class="toshi-section-title toshi-gap-3">{{ $reviewData['coAdminName'] }}</div>
                        <div class="toshi-sm-text">{{ $reviewData['coAdminEmail'] }}</div>
                    </div>
                    @endif

                    {{-- WhatsApp Status --}}
                    <div style="background: {{ !empty($reviewData['whatsapp']) && str_contains($reviewData['whatsapp'], '✅') ? '#F0FDF4' : '#FEF2F2' }}; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">📱</span>
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: #141413;">WhatsApp</div>
                            <div class="toshi-sm-text">{{ $reviewData['whatsapp'] ?? 'Not set up' }}</div>
                        </div>
                    </div>

                    {{-- School Pay (optional) --}}
                    <div style="background: #FFFBEB; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; gap: 8px; border: 1px solid #FDE68A;">
                        <span style="font-size: 16px;">💳</span>
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: #92400E;">School Pay Integration</div>
                            <div style="font-size: 11px; color: #B45309;">{{ $reviewData['schoolPay'] ?? '⏳ Optional — configure after onboarding' }}</div>
                            <div style="font-size: 10px; color: #A16207; margin-top: 2px;">Set up in School Pay portal → get API password → enable webhooks</div>
                        </div>
                    </div>

                    {{-- Counts grid --}}
                    <div class="toshi-counts-grid">
                        <div class="toshi-stat-card">
                            <div class="toshi-stat-value" style="color: #c96442;">{{ $reviewData['classCount'] }}</div>
                            <div class="toshi-stat-digit">Classes</div>
                        </div>
                        <div class="toshi-stat-card">
                            <div class="toshi-stat-value" style="color: #22C55E;">{{ $reviewData['teacherCount'] }}</div>
                            <div class="toshi-stat-digit">Teachers</div>
                            @if(!empty($reviewData['teacherLinkCount']) && $reviewData['teacherLinkCount'] > 0)
                            <div class="toshi-tiny-note">{{ $reviewData['teacherLinkCount'] }} subject links</div>
                            @endif
                        </div>
                        <div class="toshi-stat-card">
                            <div class="toshi-stat-value" style="color: #D97706;">{{ $reviewData['studentCount'] }}</div>
                            <div class="toshi-stat-digit">Students</div>
                            @if(!empty($reviewData['studentIds']) && $reviewData['studentIds'] !== '—')
                            <div class="toshi-tiny-note">{{ $reviewData['studentIds'] }}</div>
                            @endif
                        </div>
                        <div class="toshi-stat-card">
                            <div class="toshi-stat-value" style="color: #8B5CF6;">{{ $reviewData['termCount'] }}</div>
                            <div class="toshi-stat-digit">Terms</div>
                        </div>
                        <div class="toshi-stat-card">
                            <div class="toshi-stat-value" style="color: #EC4899;">{{ $reviewData['feeCount'] }}</div>
                            <div class="toshi-stat-digit">Fees</div>
                        </div>
                        <div class="toshi-stat-card">
                            <div class="toshi-stat-value" style="color: #14B8A6;">{{ $reviewData['examCount'] }}</div>
                            <div class="toshi-stat-digit">Exams</div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="toshi-review-actions">
                </div>
            </div>
            @endif
            {{-- Review actions outside the card to avoid CSS interference --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'review' && !empty($reviewData) && empty($reviewData['committed']))
            <div class="toshi-review-actions-outside">
                <button type="button" wire:click="confirmOnboarding"
                        class="toshi-btn-confirm toshi-confirm-full"
                        onmouseover="this.style.background='#16A34A'" onmouseout="this.style.background='#22C55E'"
                        id="confirm-btn">
                    🎉 Confirm &amp; Create School
                </button>
                <button type="button" wire:click="editBeforeCommit"
                        class="toshi-btn-secondary toshi-btn-edit"
                        onmouseover="this.style.borderColor='#c96442';this.style.color='#c96442'" onmouseout="this.style.borderColor='#e8e6dc';this.style.color='#5e5d59'">
                    ← Edit
                </button>
            </div>
            @endif
        </div>
        {{-- Quick action chips --}}
        @if($scope === 'platform' && $mode === 'assistant' && !$actionStep && !$awaitingConfirm)
        <div class="shrink-0" style="display: flex; flex-direction: column; gap: 6px; padding: 4px 16px 8px; background: #FFFFFF;">
            @php $drafts = $this->getDrafts(); @endphp
            @if(count($drafts) > 0)
            <div class="toshi-label">Continue Setup</div>
            @foreach($drafts as $draft)
            <button wire:click="resumeDraft" type="button"
                    style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f5f4ed; border: 1px solid #e8e6dc; border-radius: 10px; cursor: pointer; text-align: left; font-size: 12px; color: #4d4c48; font-weight: 500; transition: all 0.15s; width: 100%;"
                    onmouseover="this.style.borderColor='#22C55E'" onmouseout="this.style.borderColor='#e8e6dc'">
                <span style="width: 24px; height: 24px; border-radius: 6px; background: #22C55E; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px;">↻</span>
                <span class="flex-1">{{ $draft['school_name'] }}</span>
                <span style="font-size: 10px; color: #87867f;">Step {{ $draft['step'] + 1 }}</span>
            </button>
            @endforeach
            @endif
            <button wire:click="resetOnboarding(true)" type="button"
                    style="display: flex; align-items: center; gap: 6px; padding: 6px 14px; background: #141413; color: white; border: none; border-radius: 20px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.15s; align-self: flex-start;"
                    onmouseover="this.style.background='#22C55E'"
                    onmouseout="this.style.background='#141413'">
                <span style="font-size: 16px; line-height: 1;">+</span> New School
            </button>
        </div>
        @endif

        {{-- School admin quick actions — suggestion chips row --}}
        @if($scope === 'school' && $mode === 'assistant' && !$actionStep && !$awaitingConfirm && $schoolId)
        @php
            $availableActions = $this->capabilities['actions'] ?? [];
            $suggestions = array_values(array_filter([
                'record_attendance' => ['icon' => '📋', 'label' => 'Record attendance'],
                'record_payment'    => ['icon' => '💳', 'label' => 'Check fee balance'],
                'add_student'       => ['icon' => '👤', 'label' => 'Add a student'],
                'list_classes'      => ['icon' => '📚', 'label' => 'List my classes'],
                'list_teachers'     => ['icon' => '🔍', 'label' => 'Find a student'],
            ], fn($key) => in_array($key, $availableActions), ARRAY_FILTER_USE_KEY));
        @endphp
        @if(count($messages) < 3)
        @include('toshi-ui::components.suggestion-chips', [
            'suggestions' => $suggestions,
            'inputId' => 'toshi-input-panel',
            'wireKey' => 'sc-panel',
        ])
        @endif
        @endif

        {{-- Confirmation buttons --}}
        @if($awaitingConfirm)
        <div class="shrink-0" style="display: flex; gap: 10px; padding: 8px 16px 8px; background: #FFFFFF; border-top: 1px solid #f5f4ed;">
            <button wire:click="confirmYes" type="button"
                    style="flex: 1; padding: 10px; background: #22C55E; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                    onmouseover="this.style.background='#16A34A'"
                    onmouseout="this.style.background='#22C55E'">
                Yes ✓
            </button>
            <button wire:click="confirmNo" type="button"
                    class="toshi-btn-outline"
                    onmouseover="this.style.background='#e8e6dc'"
                    onmouseout="this.style.background='#f5f4ed'">
                No
            </button>
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects' && $substep === 1)
            <button wire:click="confirmCustom" type="button"
                    class="toshi-btn-primary"
                   
                    onmouseout="this.style.background='#c96442'">
                + Add Subject
            </button>
            @endif
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers' && $substep === 0)
            <button wire:click="showTeacherFormFn" type="button"
                    class="toshi-btn-primary"
                   
                    onmouseout="this.style.background='#c96442'">
                + Add Teacher
            </button>
            @endif
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'students' && $substep === 0)
            <button wire:click="showStudentFormFn" type="button"
                    class="toshi-btn-primary"
                   
                    onmouseout="this.style.background='#c96442'">
                + Add Student
            </button>
            @endif
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'fees' && $substep === 0)
            <button wire:click="showFeeFormFn" type="button"
                    class="toshi-btn-primary"
                   
                    onmouseout="this.style.background='#c96442'">
                + Add Fee
            </button>
            @endif
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'exams' && $substep === 0)
            <button wire:click="showExamFormFn" type="button"
                    class="toshi-btn-primary"
                   
                    onmouseout="this.style.background='#c96442'">
                + Add Exam
            </button>
            @endif
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'standards' && $substep === 5)
            <button wire:click="confirmSkipAll" type="button"
                    class="toshi-btn-outline"
                    onmouseover="this.style.background='#e8e6dc'"
                    onmouseout="this.style.background='#f5f4ed'">
                Skip All
            </button>
            @endif
        </div>
        @endif
        {{-- Skip step button — shared partial --}}
        @include('livewire.partials.toshi-skip-button', ['modal' => false])
        {{-- Composer — Claude-style unified bar --}}
        <form wire:submit.prevent="send" class="toshi-composer">
            <div class="toshi-composer-inner">
                <label class="toshi-attach-btn" title="Upload file">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 4v8M4 8h8"/></svg>
                    <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.xls,.pdf,.png,.jpg,.jpeg,.docx,.txt">
                </label>
                <textarea rows="1" wire:model.defer="input"
                          placeholder="Message Toshi…"
                          id="toshi-input-panel"
                          x-init="$el.removeAttribute('readonly')"
                          @input="
                              hasText = $el.value.trim().length > 0;
                              $el.style.height = 'auto';
                              $el.style.height = Math.min($el.scrollHeight, 320) + 'px';
                          "
                           @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $el.closest('form').dispatchEvent(new Event('submit', {cancelable:true, bubbles:true})) }"
                           class="toshi-composer-input"></textarea>
                <button type="submit"
                        :disabled="!hasText"
                        :style="hasText ? 'color: #141413; cursor: pointer;' : 'color: #CBD5E1; cursor: default;'"
                        style="width: 32px; height: 32px; background: none; border: none; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: color 0.15s; margin-right: 2px; border-radius: 8px;"
                        class="active:scale-95"
                        title="Send">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                </button>
            </div>
        </form>
    </div>

    {{-- ===== MAXIMIZED MODAL — Claude-inspired two-column layout ===== --}}
    <div id="toshi-modal"
         class="toshi-modal-overlay"
         style="{{ $maximized ? 'display: flex;' : 'display: none;' }} align-items: center; justify-content: center;"
         @click.self="$wire.call('hide')">
        <div class="toshi-modal-box"
             style="background: #FFFFFF; display: flex; overflow: hidden;"
             onclick="event.stopPropagation()">

            {{-- Left Sidebar --}}
            <div style="width: 260px; background: #f5f4ed; border-right: 1px solid #e8e6dc; display: flex; flex-direction: column; padding: 16px; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px; padding-bottom: 4px;">
                    <img src="{{ asset('images/klassapp-logo.svg') }}" style="width: 22px; height: 22px;" alt="KlassApp">
                    <span style="font-size: 16px; font-weight: 700; color: #141413; font-family: 'Sora', sans-serif;">Toshi</span>
                    <button wire:click="resetSchoolOnboarding" style="margin-left:auto;font-size:11px;font-weight:600;color:#D97706;background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:4px;transition:background 0.15s;" onmouseover="this.style.background='rgba(217,119,6,0.1)'" onmouseout="this.style.background='transparent'" title="Restart onboarding">
                        ↻ Restart
                    </button>
                </div>
                <div style="font-size: 11px; text-transform: uppercase; color: #87867f; letter-spacing: 0.04em; font-weight: 600; margin: 16px 0 8px;">Onboarding Session</div>
                <button wire:click="resumeDraft" style="width: 100%; background: #FFFFFF; border: 1px solid #e8e6dc; border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #4d4c48; font-weight: 500; cursor: pointer; text-align: left; transition: all 0.15s;" onmouseover="this.style.borderColor='#22C55E'" onmouseout="this.style.borderColor='#e8e6dc'">
                    {{ $schoolName ?: ($reviewData['schoolName'] ?? 'New School') }}
                </button>
                @if($scope === 'platform' && $mode === 'assistant' && !$actionStep && !$awaitingConfirm)
                @php $drafts = $this->getDrafts(); @endphp
                @if(count($drafts) > 0)
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 4px;">
                    <div class="toshi-label">Continue Setup</div>
                    @foreach($drafts as $draft)
                    <button wire:click="resumeDraft" type="button"
                            style="display: flex; align-items: center; gap: 8px; padding: 7px 10px; background: #FFFFFF; border: 1px solid #e8e6dc; border-radius: 8px; cursor: pointer; text-align: left; font-size: 12px; color: #4d4c48; font-weight: 500; transition: all 0.15s; width: 100%; font-family: 'DM Sans', sans-serif;"
                            onmouseover="this.style.borderColor='#22C55E'" onmouseout="this.style.borderColor='#e8e6dc'">
                        <span style="width: 20px; height: 20px; border-radius: 5px; background: #22C55E; color: white; display: flex; align-items: center; justify-content: center; font-size: 10px;">↻</span>
                        <span class="flex-1">{{ $draft['school_name'] }}</span>
                        <span style="font-size: 9px; color: #87867f;">Step {{ $draft['step'] + 1 }}</span>
                    </button>
                    @endforeach
                </div>
                @endif
                <div style="margin-top: 8px;">
                    <button wire:click="resetOnboarding(true)" type="button"
                            style="width: 100%; padding: 8px 12px; background: #141413; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; text-align: center; transition: all 0.15s; font-family: 'DM Sans', sans-serif;"
                            onmouseover="this.style.background='#22C55E'"
                            onmouseout="this.style.background='#141413'">
                        + New School
                    </button>
                </div>
                @endif
                {{-- Step progress in sidebar --}}
                @if(!empty($steps) && isset($steps[$step]) && $mode !== 'assistant')
                <div style="margin-top: 12px;">
                    <div style="font-size: 10px; text-transform: uppercase; color: #87867f; letter-spacing: 0.04em; font-weight: 600; margin-bottom: 6px;">Progress — {{ $step + 1 }}/{{ count($steps) }}</div>
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        @foreach($steps as $i => $name)
                            @php
                                $isDone = $i < $step;
                                $isCurrent = $i === $step;
                                $label = ucfirst(str_replace('_', ' ', $name));
                            @endphp
                            <button wire:click="jumpToStep({{ $i }})" type="button"
                                    style="display: flex; align-items: center; gap: 6px; padding: 4px 6px; border: none; border-radius: 4px; background: {{ $isCurrent ? '#FFFFFF' : 'transparent' }}; cursor: {{ $isDone || $isCurrent ? 'pointer' : 'default' }}; text-align: left; font-size: 10px; color: {{ $isDone ? '#22C55E' : ($isCurrent ? '#141413' : '#CBD5E1') }}; font-weight: {{ $isCurrent ? '600' : '400' }}; transition: all 0.15s; font-family: 'DM Sans', sans-serif;"
                                    onmouseover="this.style.background='#FFFFFF'" onmouseout="this.style.background='{{ $isCurrent ? '#FFFFFF' : 'transparent' }}'">
                                <span style="width: 14px; height: 14px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 7px; font-weight: 700; flex-shrink: 0; background: {{ $isDone ? '#22C55E' : ($isCurrent ? '#141413' : '#e8e6dc') }}; color: white;">{{ $isDone ? '✓' : $i + 1 }}</span>
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                @endif
                <div style="margin-top: auto; padding-top: 16px;">
                    <button wire:click="hide" style="font-size: 12px; color: #87867f; background: none; border: none; cursor: pointer; padding: 4px 0; font-family: 'DM Sans', sans-serif;" onmouseover="this.style.color='#5e5d59'" onmouseout="this.style.color='#87867f'">Close</button>
                </div>
            </div>

            {{-- Right Main Area --}}
            <div style="flex: 1; display: flex; flex-direction: column; background: #FFFFFF; min-width: 0;">

                {{-- Header --}}
                <div class="toshi-header">
                    <div class="toshi-header-logo">
                        <img src="{{ asset('images/klassapp-logo.svg') }}" alt="KlassApp">
                        <span>Toshi</span>
                    </div>
                    {{-- Mode dropdown — shared partial --}}
                    @include('livewire.partials.toshi-mode-dropdown')
                    <div class="toshi-header-actions">
                        <button wire:click="restore" class="toshi-header-btn" title="Restore">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2H2v4M12 2h2v4M12 14h2v-4M4 14H2v-4"/></svg>
                        </button>
                        <button wire:click="hide" class="toshi-header-btn" title="Close">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 4l8 8M12 4l-8 8"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Chat Area --}}
                <div class="flex-1 overflow-y-auto"
                     x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight); $wire.$watch('messages', () => $nextTick(() => $el.scrollTop = $el.scrollHeight))"
                     style="background: #FFFFFF; padding: 32px 24px; min-height: 0;">
                    <div style="max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">
                        @foreach($messages as $msg)
                            @php $isUser = $msg['role'] === 'user'; @endphp
                            @if($isUser)
                                <div class="toshi-msg-row-end">
                                    <div class="toshi-msg-user" style="padding: 12px 16px; font-size: 14px; line-height: 1.6; max-width: 75%;">
                                        {!! nl2br(e($msg['text'])) !!}
                                    </div>
                                </div>
                            @else
                                <div class="toshi-msg-row">
                                    <div class="toshi-avatar">
                                        <img src="{{ asset('images/klassapp-logo.svg') }}" style="width: 18px; height: 18px;" alt="Toshi">
                                    </div>
                                    <div class="toshi-msg-bot" style="padding: 12px 16px; font-size: 14px; line-height: 1.6; max-width: 85%;">
                                        {!! preg_replace('/\*\*(.+?)\*\*/','<strong>$1</strong>',nl2br(e($msg['text']))) !!}
                                        @if($loop->last && $pendingToolConfirm)
                                            @php
                                                $toolInfo = $this->getToolDisplay($pendingToolConfirm['tool']);
                                                $toolArgs = $this->getToolArgsDisplay($pendingToolConfirm['args'] ?? []);
                                            @endphp
                                            @include('toshi-ui::components.tool-confirm-card', [
                                                'toolName' => $toolInfo['label'],
                                                'toolIcon' => $toolInfo['icon'],
                                                'params' => $toolArgs,
                                                'state' => 'pending',
                                                'wireKey' => 'tpc-m-' . md5($msg['text'] ?? ''),
                                                'confirmMethod' => 'confirmYes',
                                                'cancelMethod' => 'cancelAction',
                                                'cancelParam' => md5($msg['text'] ?? ''),
                                            ])
                                        @elseif($loop->last && $cancelledToolConfirm)
                                            @php
                                                $toolInfo = $this->getToolDisplay($cancelledToolConfirm['tool']);
                                                $toolArgs = $this->getToolArgsDisplay($cancelledToolConfirm['args'] ?? []);
                                            @endphp
                                            @include('toshi-ui::components.tool-confirm-card', [
                                                'toolName' => $toolInfo['label'],
                                                'toolIcon' => $toolInfo['icon'],
                                                'params' => $toolArgs,
                                                'state' => 'cancelled',
                                                'wireKey' => 'tcc-m-' . md5(json_encode($cancelledToolConfirm)),
                                            ])
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                                    {{-- Subject inline form --}}
                                    @if($showSubjectForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects')
                                    <div class="toshi-form-card">
                                        <div class="toshi-section-title" style="margin-bottom: 10px;">Add Subject</div>
                                        @php $subjects = $this->actionData['subjects'] ?? []; @endphp
                                        @if(count($subjects) > 0)
                                        <div class="toshi-spacer-8">
                                            @foreach($subjects as $si => $s)
                                            <div class="toshi-list-item">
                                                <span class="flex-1">{{ $s }}</span>
                                                <button wire:click="removeSubject({{ $si }})" type="button"
                                                        class="toshi-remove-btn">✕</button>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        <div class="toshi-flex-col">
                                            <input type="text" wire:model="subjectFormName" placeholder="Subject name *"
                                                   class="toshi-input">
                                            <input type="text" wire:model="subjectFormCode" placeholder="Subject code (optional)"
                                                   class="toshi-input">
                                            <select wire:model="subjectFormType"
                                                    class="toshi-input">
                                                <option value="core">Core</option>
                                                <option value="elective">Elective</option>
                                            </select>
                                            <div class="flex gap-2">
                                                <button wire:click="saveSubject" type="button"
                                                        class="toshi-btn-primary-sm">
                                                    + Add Subject
                                                </button>
                                                <button wire:click="doneSubjects" type="button"
                                                        class="toshi-btn-done">
                                                    Continue ({{ count($this->actionData['subjects'] ?? []) }})
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Teacher inline form (maximized) --}}
                                    @if($showTeacherForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers')
                                    <div class="toshi-form-card">
                                        <div class="toshi-flex-row">
                                            <span class="toshi-section-title">Add Teacher</span>
                                            <div class="flex items-center gap-2 toshi-ml-auto">
                                                <a href="{{ asset('templates/teacher-upload-template.xlsx') }}" download
                                                   class="toshi-tag-link">Download Template</a>
                                                <span style="font-size: 10px; color: #87867f; cursor: help;" title="CSV/XLSX: columns Name, Email, Subjects, Classes. TXT: one name per line.">ⓘ</span>
                                                <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                                                    Upload File
                                                    <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                                                </label>
                                            </div>
                                        </div>
                                        @php $teachers = $this->actionData['teachers'] ?? []; @endphp
                                        @if(count($teachers) > 0)
                                        <div class="toshi-spacer-8">
                                            @foreach($teachers as $ti => $t)
                                            <div class="toshi-list-item">
                                                <span class="flex-1">{{ $t }}</span>
                                                <button wire:click="removeTeacher({{ $ti }})" type="button"
                                                        class="toshi-remove-btn">✕</button>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        <div class="toshi-flex-col">
                                            <input type="text" wire:model="teacherFormName" placeholder="Teacher name *"
                                                   class="toshi-input">
                                            <input type="email" wire:model="teacherFormEmail" placeholder="Email (optional)"
                                                   class="toshi-input">
                                            <input type="text" wire:model="teacherFormPhone" placeholder="WhatsApp number (optional)"
                                                   class="toshi-input">
                                            <div class="flex gap-2">
                                                <div class="flex-1">
                                                    <div class="toshi-sub-label">Subject(s)</div>
                                                    <input type="text" wire:model="teacherFormSubjects" placeholder="e.g. Math, English" list="subject-list-modal"
                                                           class="toshi-input">
                                                    <datalist id="subject-list-modal">
                                                        @php
                                                            $allSubjects = [];
                                                            foreach (($this->subjects ?? []) as $class => $subs) {
                                                                $allSubjects = array_merge($allSubjects, $subs);
                                                            }
                                                            $allSubjects = array_unique($allSubjects ?? []);
                                                        @endphp
                                                        @foreach($allSubjects as $s)
                                                        <option value="{{ $s }}">
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="toshi-sub-label">Class(es)</div>
                                                    <input type="text" wire:model="teacherFormClasses" placeholder="e.g. P1, P2" list="class-list-modal"
                                                           class="toshi-input">
                                                    <datalist id="class-list-modal">
                                                        @foreach($this->standards ?? [] as $std)
                                                        <option value="{{ $std['name'] }}">
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <button wire:click="saveTeacher" type="button"
                                                        class="toshi-btn-primary-sm">
                                                    + Add Teacher
                                                </button>
                                                <button wire:click="doneTeachers" type="button"
                                                        class="toshi-btn-done">
                                                    Continue ({{ count($this->actionData['teachers'] ?? []) }})
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                                {{-- Student inline form --}}
                                                @if($showStudentForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'students')
                                                <div class="toshi-form-card">
                                                    <div class="toshi-flex-row">
                                                        <span class="toshi-section-title">Add Student</span>
                    <div class="flex items-center gap-2 toshi-ml-auto">
                        <a href="{{ asset('templates/student-upload-template.xlsx') }}" download
                           class="toshi-tag-link">Download Template</a>
                        <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                            Upload File
                            <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                        </label>
                    </div>
                                                    </div>
                                                    @php $students = $this->actionData['students'] ?? []; @endphp
                                                    @if(count($students) > 0)
                                                    <div class="toshi-spacer-8">
                                                        @foreach($students as $si => $s)
                                                        <div class="toshi-list-item">
                                                            <span class="flex-1">{{ $s['name'] }}@if(!empty($s['class'])) <span style="color: #87867f;">({{ $s['class'] }})</span>@endif</span>
                                                            <button wire:click="removeStudent({{ $si }})" type="button"
                                                                    class="toshi-remove-btn">✕</button>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                    <div class="toshi-flex-col">
                                                        <input type="text" wire:model="studentFormName" placeholder="Student name *"
                                                               class="toshi-input">
                                                        <input type="text" wire:model="studentFormStream" placeholder="Stream (optional)"
                           class="toshi-input">

                    <input type="text" wire:model="studentFormStream" placeholder="Stream (optional)"
                           class="toshi-input">
                    <input type="text" wire:model="studentFormClass" placeholder="Class *" list="student-class-list"
                                                               class="toshi-input">
                                                        <datalist id="student-class-list">
                                                            @foreach($this->standards ?? [] as $std)
                                                            <option value="{{ $std['name'] }}">
                                                            @endforeach
                                                        </datalist>
                                                        <input type="text" wire:model="studentFormParent" placeholder="Parent name (optional)"
                                                               class="toshi-input">
                                                        <input type="text" wire:model="studentFormParentPhone" placeholder="Parent phone (optional)"
                                                               class="toshi-input">
                                                        <div class="flex gap-2">
                                                            <button wire:click="saveStudent" type="button"
                                                                    class="toshi-btn-primary-sm">
                                                                + Add Student
                                                            </button>
                                                            <button wire:click="doneStudents" type="button"
                                                                    class="toshi-btn-done">
                                                                Continue ({{ count($this->actionData['students'] ?? []) }})
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif

                                    {{-- Fee inline form (maximized) --}}
                                    @if($showFeeForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'fees')
                                    <div class="toshi-form-card">
                                        <div class="toshi-flex-row">
                                            <span class="toshi-section-title">Add Fee</span>
                                            <div class="flex items-center gap-2 toshi-ml-auto">
                                                <a href="{{ asset('templates/fee-upload-template.xlsx') }}" download
                                                   class="toshi-tag-link">Download Template</a>
                                                <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                                                    Upload File
                                                    <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                                                </label>
                                            </div>
                                        </div>
                                        @php $fees = $this->actionData['fees'] ?? []; @endphp
                                        @if(count($fees) > 0)
                                        <div class="toshi-spacer-8">
                                            @foreach($fees as $fi => $f)
                                            <div class="toshi-list-item">
                        <span class="flex-1">{{ $f['name'] }}@if(!empty($f['amount'])) — {{ number_format((float)$f['amount'], 0) }} UGX @endif</span>
                                                <button wire:click="removeFee({{ $fi }})" type="button"
                                                        class="toshi-remove-btn">✕</button>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        <div class="toshi-flex-col">
                                            <input type="text" wire:model="feeFormName" placeholder="Fee name *"
                                                   class="toshi-input">
                                            <input type="number" wire:model="feeFormAmount" placeholder="Amount (UGX) *"
                                                   class="toshi-input">
                                            <select wire:model="feeFormLevel"
                                                    class="toshi-input">
                                                <option value="">Level (optional)</option>
                                                <option value="nursery">Nursery</option>
                                                <option value="primary">Primary</option>
                                                <option value="secondary">Secondary</option>
                                                <option value="all">All Levels</option>
                                            </select>
                                            <input type="text" wire:model="feeFormClass" placeholder="Class (optional)" list="fee-class-list-modal"
                                                   class="toshi-input">
                                            <datalist id="fee-class-list-modal">
                                                @foreach($this->standards ?? [] as $std)
                                                <option value="{{ $std['name'] }}">
                                                @endforeach
                                            </datalist>
                                            <select wire:model="feeFormTerm"
                                                    class="toshi-input">
                                                <option value="">Term (optional)</option>
                                                <option value="Term I">Term I</option>
                                                <option value="Term II">Term II</option>
                                                <option value="Term III">Term III</option>
                                            </select>
                                            <div class="flex gap-2">
                                                <button wire:click="saveFee" type="button"
                                                        class="toshi-btn-primary-sm">
                                                    + Add Fee
                                                </button>
                                                <button wire:click="doneFees" type="button"
                                                        class="toshi-btn-done">
                                                    Continue ({{ count($this->actionData['fees'] ?? []) }})
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                                {{-- Exam inline form --}}
                                                @if($showExamForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'exams')
                                                <div class="toshi-form-card">
                                                    <div class="toshi-flex-row">
                                                        <span class="toshi-section-title">Add Exam</span>
                                                        <div class="flex items-center gap-2 toshi-ml-auto">
                                                            <a href="{{ asset('templates/exam-upload-template.xlsx') }}" download
                                                               class="toshi-tag-link">Download Template</a>
                                                            <label class="flex items-center gap-1 cursor-pointer toshi-chip-compact">
                                                                Upload File
                                                                <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @php $exams = $this->actionData['exams'] ?? []; @endphp
                                                    @if(count($exams) > 0)
                                                    <div class="toshi-spacer-8">
                                                        @foreach($exams as $ei => $e)
                                                        <div class="toshi-list-item">
                                                            <span class="flex-1">{{ $e['type'] }} — {{ $e['term'] }}</span>
                                                            <button wire:click="removeExam({{ $ei }})" type="button"
                                                                    class="toshi-remove-btn">✕</button>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                    <div class="toshi-flex-col">
                                                        <select wire:model="examFormTerm"
                                                                class="toshi-input">
                                                            <option value="">Term *</option>
                                                            <option value="Term I">Term I</option>
                                                            <option value="Term II">Term II</option>
                                                            <option value="Term III">Term III</option>
                                                        </select>
                                                        <input type="text" wire:model="examFormType" placeholder="Exam type * (e.g. Midterm, Final)"
                                                               class="toshi-input">
                                                        <select wire:model="examFormStatus"
                                                                class="toshi-input">
                                                            <option value="">Status</option>
                                                            <option value="active">Active</option>
                                                            <option value="completed">Completed</option>
                                                            <option value="cancelled">Cancelled</option>
                                                        </select>
                                                        <select wire:model="examFormLevel"
                                                                class="toshi-input">
                                                            <option value="">Level</option>
                                                            <option value="nursery">Nursery</option>
                                                            <option value="primary">Primary</option>
                                                            <option value="secondary">Secondary</option>
                                                            <option value="all">All Levels</option>
                                                        </select>
                                                        <input type="text" wire:model="examFormClass" placeholder="Class" list="exam-class-list"
                                                               class="toshi-input">
                                                        <datalist id="exam-class-list">
                                                            @foreach($this->standards ?? [] as $std)
                                                            <option value="{{ $std['name'] }}">
                                                            @endforeach
                                                        </datalist>
                                                        <input type="text" wire:model="examFormSubject" placeholder="Subject" list="exam-subject-list"
                                                               class="toshi-input">
                                                        <datalist id="exam-subject-list">
                                                            @php
                                                                $allSubs = [];
                                                                foreach (($this->subjects ?? []) as $cls => $subs) {
                                                                    $allSubs = array_merge($allSubs, $subs);
                                                                }
                                                                $allSubs = array_unique($allSubs ?? []);
                                                            @endphp
                                                            @foreach($allSubs as $s)
                                                            <option value="{{ $s }}">
                                                            @endforeach
                                                        </datalist>
                                                        <input type="text" wire:model="examFormTeacher" placeholder="Teacher" list="exam-teacher-list"
                                                               class="toshi-input">
                                                        <datalist id="exam-teacher-list">
                                                            @foreach($this->teacherList ?? [] as $t)
                                                            <option value="{{ $t }}">
                                                            @endforeach
                                                        </datalist>
                                                        <div class="flex gap-2">
                                                            <button wire:click="saveExam" type="button"
                                                                    class="toshi-btn-primary-sm">
                                                                + Add Exam
                                                            </button>
                                                            <button wire:click="doneExams" type="button"
                                                                    class="toshi-btn-outline-sm">
                                                                Skip
                                                            </button>
                                                            <button wire:click="doneExams" type="button"
                                                                    class="toshi-btn-done-sm">
                                                                Continue ({{ count($this->actionData['exams'] ?? []) }})
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                    
                        {{-- Buttons inside maximize modal --}}
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'plan_selection' && !$selectedPlanId)
                        <div style="display: flex; flex-direction: column; gap: 8px; padding: 4px 0;">
                            @php $plans = \App\Models\Plan::where('is_active', 1)->orderBy('order')->get(); @endphp
                            @foreach($plans as $plan)
                            <button wire:click="selectPlan({{ $plan->id }})"
                                    class="toshi-option-card"
                                    onmouseover="this.style.borderColor='#22C55E';this.style.boxShadow='0 2px 8px rgba(34,197,94,0.15)'"
                                    onmouseout="this.style.borderColor='#e8e6dc';this.style.boxShadow='none'">
                                <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; background: #22C55E; color: white;">{{ $loop->first ? '🆓' : ($loop->iteration === 2 ? '⭐' : '👑') }}</div>
                                <div class="flex-1">
                                    <div style="font-weight: 600;">{{ ucfirst($plan->name) }}</div>
                                    <div style="color: #5e5d59; font-size: 12px; margin-top: 2px;">
                                        @if(strtolower($plan->name) === 'premium')
                                            Contact sales
                                        @elseif($plan->amount > 0)
                                            ${{ number_format($plan->amount) }} / {{ $plan->cycle }} days
                                        @else
                                            Free
                                        @endif
                                    </div>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @endif

                        @if($actionStep === 'onboarding_school_category')
                        <div style="display: flex; flex-direction: column; gap: 8px; padding: 4px 0;" data-testid="toshi-category-options">
                            <div style="font-size: 12px; font-weight: 600; color: #5e5d59; margin-bottom: 4px;">→ School category</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach(\App\Services\SchoolCategorySeeder::CATEGORIES as $value => $label)
                                <button wire:click="selectSchoolCategory('{{ $value }}')" class="toshi-btn" data-testid="toshi-category-{{ $value }}">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'school_info' && $substep === 2)
                        <div style="display: flex; flex-direction: column; gap: 8px; padding: 4px 0;" data-testid="toshi-category-options">
                            <div style="font-size: 12px; font-weight: 600; color: #5e5d59; margin-bottom: 4px;">→ School category</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach(\App\Services\SchoolCategorySeeder::CATEGORIES as $value => $label)
                                <button wire:click="selectSchoolCategory('{{ $value }}')" class="toshi-btn" data-testid="toshi-category-{{ $value }}">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'co_admin_invite' && $substep === 0)
                        <div style="font-size: 12px; font-weight: 600; color: #5e5d59; margin-bottom: 2px; padding: 4px 0 0;">→ Would you like to add a co-admin for this school?</div>
                        <div style="display: flex; gap: 8px; padding: 4px 0 8px;">
                            <button wire:click="coAdminInviteYes" class="toshi-btn" style="background: #F0FDF4; border-color: #22C55E; font-weight: 600;">Yes, add a co-admin</button>
                            <button wire:click="coAdminInviteSkip" class="toshi-btn">Skip</button>
                        </div>
                        @endif

                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'co_admin_invite' && $substep === 10)
                        <div style="display: flex; flex-direction: column; gap: 6px; padding: 4px 0;">
                            @php $staff = $this->getAvailableStaff(); @endphp
                            @foreach($staff as $teacher)
                            <button wire:click="promoteCoAdmin({{ $teacher->id }})"
                                    style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #FFFFFF; border: 1px solid #e8e6dc; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #4d4c48;"
                                    onmouseover="this.style.borderColor='#c96442';this.style.boxShadow='0 2px 8px rgba(30,111,217,0.12)'"
                                    onmouseout="this.style.borderColor='#e8e6dc';this.style.boxShadow='none'">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #faf9f5; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #c96442; font-size: 12px;">{{ strtoupper(substr($teacher->name, 0, 1)) }}</div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600;">{{ $teacher->name }}</div>
                                    <div class="toshi-sm-text">{{ $teacher->email }}</div>
                                </div>
                            </button>
                            @endforeach
                            <button wire:click="promoteCoAdminAddNew"
                                    style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #f5f4ed; border: 1px dashed #CBD5E1; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #5e5d59; margin-top: 4px;"
                                    onmouseover="this.style.borderColor='#22C55E';this.style.color='#166534'"
                                    onmouseout="this.style.borderColor='#CBD5E1';this.style.color='#5e5d59'">
                                <span style="font-size: 16px;">＋</span>
                                <span>Add someone else (not on this list)</span>
                            </button>
                        </div>
                        @endif

                        {{-- Review Card (maximized modal) --}}
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'review' && !empty($reviewData) && empty($reviewData['committed']))
                        <div class="toshi-review-card">
                            {{-- Header --}}
                            <div class="toshi-review-header">
                                <span>📋 Review &amp; Confirm</span>
                            </div>
                            {{-- Body --}}
                            <div class="toshi-review-body">
                                <div class="flex" style="gap: 10px;">
                                    <div class="toshi-info-card">
                                        <div class="toshi-uppercase-label">Plan</div>
                                        <div class="toshi-section-title toshi-gap-3">{{ $reviewData['plan'] }}</div>
                                    </div>
                                    <div class="toshi-info-card">
                                        <div class="toshi-uppercase-label">School</div>
                                        <div class="toshi-section-title toshi-gap-3">{{ $reviewData['schoolName'] }}</div>
                                        <div class="toshi-sm-text">{{ ucfirst($reviewData['schoolType']) }}</div>
                                    </div>
                                </div>
                                <div class="toshi-info-card" style="flex: none;">
                                    <div class="toshi-uppercase-label">Admin Account</div>
                                    <div class="toshi-section-title toshi-gap-3">{{ $reviewData['adminName'] }}</div>
                                    <div class="toshi-sm-text">{{ $reviewData['adminEmail'] }} · {{ $reviewData['adminPhone'] }}</div>
                                </div>
                                @if(!empty($reviewData['coAdminName']))
                                <div class="toshi-info-card" style="flex: none;">
                                    <div class="toshi-uppercase-label">Co-Admin</div>
                                    <div class="toshi-section-title toshi-gap-3">{{ $reviewData['coAdminName'] }}</div>
                                    <div class="toshi-sm-text">{{ $reviewData['coAdminEmail'] }}</div>
                                </div>
                                @endif
                                <div style="background: {{ !empty($reviewData['whatsapp']) && str_contains($reviewData['whatsapp'], '✅') ? '#F0FDF4' : '#FEF2F2' }}; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 16px;">📱</span>
                                    <div>
                                        <div style="font-size: 12px; font-weight: 600; color: #141413;">WhatsApp</div>
                                        <div class="toshi-sm-text">{{ $reviewData['whatsapp'] ?? 'Not set up' }}</div>
                                    </div>
                                </div>
                                <div class="toshi-counts-grid">
                                    <div class="toshi-stat-card">
                                        <div class="toshi-stat-value" style="color: #c96442;">{{ $reviewData['classCount'] }}</div>
                                        <div class="toshi-stat-digit">Classes</div>
                                    </div>
                                    <div class="toshi-stat-card">
                                        <div class="toshi-stat-value" style="color: #22C55E;">{{ $reviewData['teacherCount'] }}</div>
                                        <div class="toshi-stat-digit">Teachers</div>
                                        @if(!empty($reviewData['teacherLinkCount']) && $reviewData['teacherLinkCount'] > 0)
                                        <div class="toshi-tiny-note">{{ $reviewData['teacherLinkCount'] }} subject links</div>
                                        @endif
                                    </div>
                                    <div class="toshi-stat-card">
                                        <div class="toshi-stat-value" style="color: #D97706;">{{ $reviewData['studentCount'] }}</div>
                                        <div class="toshi-stat-digit">Students</div>
                                        @if(!empty($reviewData['studentIds']) && $reviewData['studentIds'] !== '—')
                                        <div class="toshi-tiny-note">{{ $reviewData['studentIds'] }}</div>
                                        @endif
                                    </div>
                                    <div class="toshi-stat-card">
                                        <div class="toshi-stat-value" style="color: #8B5CF6;">{{ $reviewData['termCount'] }}</div>
                                        <div class="toshi-stat-digit">Terms</div>
                                    </div>
                                    <div class="toshi-stat-card">
                                        <div class="toshi-stat-value" style="color: #EC4899;">{{ $reviewData['feeCount'] }}</div>
                                        <div class="toshi-stat-digit">Fees</div>
                                    </div>
                                    <div class="toshi-stat-card">
                                        <div class="toshi-stat-value" style="color: #14B8A6;">{{ $reviewData['examCount'] }}</div>
                                        <div class="toshi-stat-digit">Exams</div>
                                    </div>
                                </div>
                            </div>
                            {{-- Actions --}}
                            <div class="toshi-review-actions">
                                <button type="button" wire:click="confirmOnboarding"
                                        class="toshi-btn-confirm toshi-confirm-full"
                                        onmouseover="this.style.background='#16A34A'" onmouseout="this.style.background='#22C55E'">
                                    🎉 Confirm &amp; Create School
                                </button>
                                <button type="button" wire:click="editBeforeCommit"
                                        style="padding: 11px 14px;"
                                        class="toshi-btn-secondary"
                                        onmouseover="this.style.borderColor='#c96442';this.style.color='#c96442'" onmouseout="this.style.borderColor='#e8e6dc';this.style.color='#5e5d59'">
                                    ← Edit
                                </button>
                            </div>
                        </div>
                        @endif

                        @if($step === 99 && !empty($reviewData['committed']))
                        @php $isComplete = ($reviewData['mode'] ?? 'create') === 'complete'; @endphp
                        <div style="background: #FFFFFF; border: 1px solid #e8e6dc; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 4px 0; text-align: center;">
                            <div style="background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%); padding: 24px 16px;">
                                <div style="font-size: 40px; margin-bottom: 8px;">🎉</div>
                                <div style="font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.01em;">
                                    {{ $isComplete ? 'School Updated!' : 'School Created!' }}
                                </div>
                                <div style="font-size: 13px; color: rgba(255,255,255,0.85); margin-top: 4px;">
                                    {{ $reviewData['schoolName'] ?? '' }} {{ $isComplete ? 'has been updated' : 'is now live on KlassApp' }}
                                </div>
                            </div>
                            <div style="padding: 16px; display: flex; flex-direction: column; gap: 10px;">
                                @if(!$isComplete)
                                <div class="toshi-banner-success">
                                    <div class="toshi-green-label">Admin Login Credentials</div>
                                    <div class="toshi-info-value">{{ $reviewData['adminEmail'] ?? '—' }}</div>
                                    <div class="toshi-info-sub">Password: <strong>{{ $reviewData['adminPassword'] ?? 'password' }}</strong></div>
                                    <div style="font-size: 12px; color: #5e5d59; margin-top: 2px;">📱 {{ $reviewData['adminPhone'] ?? '—' }}</div>
                                </div>
                                @if(!empty($reviewData['coAdminEmail']))
                                <div class="toshi-banner-success">
                                    <div class="toshi-green-label">Co-Admin</div>
                                    <div class="toshi-info-value">{{ $reviewData['coAdminEmail'] ?? '—' }}</div>
                                    @if(!empty($reviewData['coAdminPromoted']))
                                    <div style="font-size: 12px; color: #166534; margin-top: 2px;">✅ Promoted from teacher — they keep their existing password</div>
                                    @else
                                    <div class="toshi-info-sub">Password: <strong>{{ $reviewData['coAdminPassword'] ?? 'password' }}</strong></div>
                                    @endif
                                </div>
                                @endif
                                <div style="font-size: 12px; color: #5e5d59; line-height: 1.5;">
                                    Share these credentials with the school admin. They can log in at the <strong>KlassApp login page</strong>.
                                </div>
                                @else
                                <div style="font-size: 13px; color: #5e5d59; line-height: 1.5;">
                                    ✅ Your school setup is complete. Toshi will remind you if anything needs attention later.
                                </div>
                                @endif
                            </div>
                            <div style="padding: 0 16px 16px;">
                                <button wire:click="resetOnboarding"
                                        style="width: 100%; padding: 11px; background: #141413; color: #FFFFFF; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif;"
                                        onmouseover="this.style.background='#4d4c48'" onmouseout="this.style.background='#141413'">
                                    @if($isComplete) ✅ Done — Ask Toshi @else + Onboard Another School @endif
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                    {{-- Confirmation buttons (inside right area) --}}
                    @if($awaitingConfirm)
                    <div class="shrink-0" style="display: flex; gap: 10px; padding: 8px 24px 12px; background: #FFFFFF;">
                        <button wire:click="confirmYes" type="button"
                                style="flex: 1; padding: 10px; background: #22C55E; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.background='#16A34A'"
                                onmouseout="this.style.background='#22C55E'">
                            Yes ✓
                        </button>
                        <button wire:click="confirmNo" type="button"
                                class="toshi-btn-outline"
                                onmouseover="this.style.background='#e8e6dc'"
                                onmouseout="this.style.background='#f5f4ed'">
                        No
                    </button>
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects' && $substep === 1)
                        <button wire:click="confirmCustom" type="button"
                                class="toshi-btn-primary"
                               
                                onmouseout="this.style.background='#c96442'">
                            + Add Subject
                        </button>
                        @endif
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers' && $substep === 0)
                        <button wire:click="showTeacherFormFn" type="button"
                                class="toshi-btn-primary"
                               
                                onmouseout="this.style.background='#c96442'">
                            + Add Teacher
                        </button>
                        @endif
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'students' && $substep === 0)
                        <button wire:click="showStudentFormFn" type="button"
                                class="toshi-btn-primary"
                               
                                onmouseout="this.style.background='#c96442'">
                            + Add Student
                        </button>
                        @endif
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'fees' && $substep === 0)
                        <button wire:click="showFeeFormFn" type="button"
                                class="toshi-btn-primary"
                               
                                onmouseout="this.style.background='#c96442'">
                            + Add Fee
                        </button>
                        @endif
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'exams' && $substep === 0)
                        <button wire:click="showExamFormFn" type="button"
                                class="toshi-btn-primary"
                               
                                onmouseout="this.style.background='#c96442'">
                            + Add Exam
                        </button>
                        @endif
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'standards' && $substep === 5)
                        <button wire:click="confirmSkipAll" type="button"
                                class="toshi-btn-outline"
                                onmouseover="this.style.background='#e8e6dc'"
                                onmouseout="this.style.background='#f5f4ed'">
                            Skip All
                        </button>
                        @endif
                </div>
                @endif
            {{-- Skip step button — shared partial (modal) --}}
            @include('livewire.partials.toshi-skip-button', ['modal' => true])
            {{-- Suggestion chips (modal) --}}
            @if($scope === 'school' && $mode === 'assistant' && !$actionStep && !$awaitingConfirm && $schoolId)
            @php
                $availableActions = $this->capabilities['actions'] ?? [];
                $suggestions = array_values(array_filter([
                    'record_attendance' => ['icon' => '📋', 'label' => 'Record attendance'],
                    'record_payment'    => ['icon' => '💳', 'label' => 'Check fee balance'],
                    'add_student'       => ['icon' => '👤', 'label' => 'Add a student'],
                    'list_classes'      => ['icon' => '📚', 'label' => 'List my classes'],
                    'list_teachers'     => ['icon' => '🔍', 'label' => 'Find a student'],
                ], fn($key) => in_array($key, $availableActions), ARRAY_FILTER_USE_KEY));
            @endphp
            @if(count($messages) < 3)
            @include('toshi-ui::components.suggestion-chips', [
                'suggestions' => $suggestions,
                'inputId' => 'toshi-input-modal',
                'wireKey' => 'sc-modal',
            ])
            @endif
            @endif
            {{-- Composer: maximized modal — unified bar matching panel --}}
                <form wire:submit.prevent="send" style="padding: 0 24px 16px; background: #FFFFFF;">
                    <div class="toshi-composer-box">
                        <div class="toshi-composer-inner" style="padding: 6px 4px 6px 6px;">
                            <label class="toshi-attach-btn" title="Upload file">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 4v8M4 8h8"/></svg>
                                <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.xls,.pdf,.png,.jpg,.jpeg,.docx,.txt">
                            </label>
                            <textarea rows="1" wire:model.defer="input"
                                      placeholder="Message Toshi..."
                                      id="toshi-input-modal"
                                      x-init="$el.removeAttribute('readonly')"
                                      @input="
                                          hasText = $el.value.trim().length > 0;
                                          $el.style.height = 'auto';
                                          $el.style.height = Math.min($el.scrollHeight, 320) + 'px';
                                      "
                                      @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $el.closest('form').dispatchEvent(new Event('submit', {cancelable:true, bubbles:true})) }"
                                      class="toshi-composer-textarea"></textarea>
                            <button type="submit"
                                    :disabled="!hasText"
                                    :style="hasText ? 'color: #141413; cursor: pointer;' : 'color: #CBD5E1; cursor: default;'"
                                    style="width: 32px; height: 32px; background: none; border: none; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: color 0.15s; margin-right: 2px; border-radius: 8px;"
                                    class="active:scale-95"
                                    title="Send">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
