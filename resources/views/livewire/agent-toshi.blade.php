<div x-data="{ hasText: false }">
    <style>
        @keyframes toshi-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }
        .toshi-btn {
            padding: 10px 18px; background: #FFFFFF; border: 1px solid #E2E8F0;
            border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 500;
            color: #1E293B; transition: all 0.2s; text-align: center; flex: 1;
            min-width: 80px;
        }
        .toshi-btn:hover {
            border-color: #22C55E; background: #F0FDF4;
            box-shadow: 0 2px 8px rgba(34,197,94,0.12);
        }
        /* Kill all browser/Tailwind focus rings inside Toshi panels */
        #toshi-panel *:focus, #toshi-modal *:focus,
        #toshi-panel *:focus-visible, #toshi-modal *:focus-visible {
            outline: none !important;
            outline-offset: 0 !important;
            box-shadow: none !important;
        }
    </style>
    <div id="toshi-pill"
         wire:click="show" onclick="this.style.display='none'; document.getElementById('toshi-panel').style.display='flex';"
         class="fixed flex items-center cursor-pointer"
         style="{{ $visible ? 'display: none;' : '' }} bottom: 24px; right: 24px; height: 56px; width: 280px; background: #FFFFFF; border-radius: 28px; box-shadow: 0 4px 24px rgba(0,0,0,0.12); padding: 0 8px 0 8px; gap: 10px; z-index: 9999;">
        <div class="flex items-center justify-center shrink-0" style="width: 38px; height: 38px; border-radius: 50%; background: #0F172A; overflow: hidden;">
            <img src="{{ asset('images/klassapp-logo.svg') }}" style="width: 24px; height: 24px;" alt="KlassApp">
        </div>
        <span style="flex: 1; font-size: 13px; color: #64748B; font-weight: 400; white-space: nowrap;">Ask Toshi anything</span>
        <div class="flex items-center shrink-0" style="height: 38px; padding: 0 14px; background: #0075e3; border-radius: 20px; gap: 6px; color: #FFFFFF; font-size: 13px; font-weight: 600;">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="white"><rect x="1" y="4" width="3" height="6" rx="1"/><rect x="5.5" y="1" width="3" height="12" rx="1"/><rect x="10" y="3" width="3" height="8" rx="1"/></svg> Talk
        </div>
    </div>

    <div id="toshi-panel"
         class="flex flex-col overflow-hidden"
         style="{{ $visible ? 'display: flex;' : 'display: none;' }} position: fixed; z-index: 9999; bottom: 0; right: 24px; width: 373px; height: 457px; border-radius: 16px 16px 0 0; box-shadow: 0 -8px 40px rgba(0,0,0,0.18); background: #FFFFFF; border: 1px solid #E2E8F0; border-bottom: none;">
        <div class="flex items-center shrink-0" style="height: 48px; padding: 0 12px; background: #22C55E; gap: 10px;">
            <div class="flex items-center gap-2.5">
                <div class="flex items-center justify-center shrink-0" style="width: 32px; height: 32px; border-radius: 50%; background: #FFFFFF; overflow: hidden;">
                    <img src="{{ asset('images/klassapp-logo.svg') }}" style="width: 22px; height: 22px;" alt="KlassApp">
                </div>
                <div style="color: #FFFFFF; font-size: 14px; font-weight: 800; line-height: 1.2; font-family: 'Sora', sans-serif; letter-spacing: -0.01em;">Toshi</div>
            </div>
            <div class="flex items-center gap-2" style="margin-left: auto;">
                <button wire:click="maximize"
                        class="flex items-center justify-center"
                        style="width: 28px; height: 28px; border-radius: 6px; background: rgba(255,255,255,0.15); border: none; cursor: pointer;" title="Expand">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 5V1h4M9 1h4v4M13 9v4H9M5 13H1V9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button wire:click="hide"
                        class="flex items-center justify-center"
                        style="width: 28px; height: 28px; border-radius: 6px; background: rgba(255,255,255,0.15); border: none; cursor: pointer;" title="Close">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 1l10 10M11 1L1 11" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto"
             x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight); $wire.$watch('messages', () => $nextTick(() => $el.scrollTop = $el.scrollHeight))"
             style="background: #F8FAFC; padding: 16px; display: flex; flex-direction: column; gap: 12px; min-height: 0;">
            @foreach($messages as $msg)
                @php $isUser = $msg['role'] === 'user'; @endphp
                <div style="display: flex; justify-content: {{ $isUser ? 'flex-end' : 'flex-start' }};">
                    <div style="max-width: 80%;">
                        <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94A3B8; margin-bottom: 4px; padding: 0 4px;">{{ $isUser ? 'You' : 'Toshi' }}</div>
                        <div style="border-radius: 12px; padding: 10px 14px; font-size: 13px; line-height: 1.5; {{ $isUser ? 'background: #F8FAFC; color: #1E293B; border: 1px solid #E2E8F0;' : 'background: #FFFFFF; color: #1E293B; border: 1px solid #E2E8F0;' }}">
                            @if(!$isUser){!! preg_replace('/\*\*(.+?)\*\*/','<strong>$1</strong>',nl2br(e($msg['text']))) !!}@else{!! nl2br(e($msg['text'])) !!}@endif
                        </div>
                    </div>
                </div>
            @endforeach
            {{-- Subject inline form --}}
            @if($showSubjectForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects')
            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px; margin: 4px 0;">
                <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-bottom: 10px;">Add Subject</div>
                @php $subjects = $this->actionData['subjects'] ?? []; @endphp
                @if(count($subjects) > 0)
                <div style="margin-bottom: 8px;">
                    @foreach($subjects as $si => $s)
                    <div style="display: flex; align-items: center; gap: 6px; padding: 4px 0; font-size: 12px; color: #1E293B; border-bottom: 1px solid #F1F5F9;">
                        <span style="flex: 1;">{{ $s }}</span>
                        <button wire:click="removeSubject({{ $si }})" type="button"
                                style="background: none; border: none; color: #EF4444; cursor: pointer; font-size: 14px; padding: 2px 4px;">✕</button>
                    </div>
                    @endforeach
                </div>
                @endif
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <input type="text" wire:model="subjectFormName" placeholder="Subject name *"
                           style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                    <input type="text" wire:model="subjectFormCode" placeholder="Subject code (optional)"
                           style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                    <select wire:model="subjectFormType"
                            style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; background: white; font-family: 'DM Sans', sans-serif;">
                        <option value="core">Core</option>
                        <option value="elective">Elective</option>
                    </select>
                    <div style="display: flex; gap: 8px;">
                        <button wire:click="saveSubject" type="button"
                                style="flex: 1; padding: 8px; background: #1E6FD9; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            + Add Subject
                        </button>
                        <button wire:click="doneSubjects" type="button"
                                style="padding: 8px 12px; background: #22C55E; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                            Done ({{ count($this->actionData['subjects'] ?? []) }})
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Teacher inline form --}}
            @if($showTeacherForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers')
            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px; margin: 4px 0;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <span style="font-size: 13px; font-weight: 600; color: #0F172A;">Add Teacher</span>
                    <div style="display: flex; align-items: center; gap: 6px; margin-left: auto;">
                        <span style="font-size: 10px; color: #94A3B8; cursor: help;" title="CSV/XLSX: columns Name, Email, Subjects, Classes. TXT: one name per line.">ⓘ</span>
                        <label class="flex items-center gap-1 cursor-pointer"
                               style="padding: 4px 8px; background: #F1F5F9; border-radius: 6px; font-size: 11px; color: #64748B; cursor: pointer;">
                            Upload File
                            <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                        </label>
                    </div>
                </div>
                @php $teachers = $this->actionData['teachers'] ?? []; @endphp
                @if(count($teachers) > 0)
                <div style="margin-bottom: 8px;">
                    @foreach($teachers as $ti => $t)
                    <div style="display: flex; align-items: center; gap: 6px; padding: 4px 0; font-size: 12px; color: #1E293B; border-bottom: 1px solid #F1F5F9;">
                        <span style="flex: 1;">{{ $t }}</span>
                        <button wire:click="removeTeacher({{ $ti }})" type="button"
                                style="background: none; border: none; color: #EF4444; cursor: pointer; font-size: 14px; padding: 2px 4px;">✕</button>
                    </div>
                    @endforeach
                </div>
                @endif
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <input type="text" wire:model="teacherFormName" placeholder="Teacher name *"
                           style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                    <input type="email" wire:model="teacherFormEmail" placeholder="Email *"
                           style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                    <input type="text" wire:model="teacherFormPhone" placeholder="WhatsApp number (optional)"
                           style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                    <div style="display: flex; gap: 8px;">
                        <div style="flex: 1;">
                            <div style="font-size: 10px; color: #94A3B8; margin-bottom: 2px;">Subject(s)</div>
                            <input type="text" wire:model="teacherFormSubjects" placeholder="e.g. Math, English" list="subject-list"
                                   style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
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
                        <div style="flex: 1;">
                            <div style="font-size: 10px; color: #94A3B8; margin-bottom: 2px;">Class(es)</div>
                            <input type="text" wire:model="teacherFormClasses" placeholder="e.g. P1, P2" list="class-list"
                                   style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                            <datalist id="class-list">
                                @foreach($this->standards ?? [] as $std)
                                <option value="{{ $std['name'] }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button wire:click="saveTeacher" type="button"
                                style="flex: 1; padding: 8px; background: #1E6FD9; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            + Add Teacher
                        </button>
                        <button wire:click="doneTeachers" type="button"
                                style="padding: 8px 12px; background: #22C55E; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                            Done ({{ count($this->actionData['teachers'] ?? []) }})
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
                        style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #1E293B;"
                        onmouseover="this.style.borderColor='#22C55E';this.style.boxShadow='0 2px 8px rgba(34,197,94,0.15)'"
                        onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='none'">
                    <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; background: #22C55E; color: white;">{{ $loop->first ? '🆓' : ($loop->iteration === 2 ? '⭐' : '👑') }}</div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">{{ ucfirst($plan->name) }}</div>
                        <div style="color: #64748B; font-size: 12px; margin-top: 2px;">
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

            {{-- School Type Buttons --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'school_info' && $substep === 2)
            <div style="display: flex; flex-direction: column; gap: 8px; padding: 8px 0;">
                <div style="font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 4px;">→ Category</div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <button wire:click="setSchoolType('nursery')" class="toshi-btn">Nursery</button>
                    <button wire:click="setSchoolType('primary','','mixed')" class="toshi-btn">Primary</button>
                    <button wire:click="setSchoolType('secondary','o-level','mixed')" class="toshi-btn">Secondary</button>
                    <button wire:click="setSchoolType('mixed','both','mixed')" class="toshi-btn">All Levels</button>
                </div>
            </div>
            @endif

            {{-- Co-admin Invite Buttons --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'co_admin_invite' && $substep === 0)
            <div style="font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 2px; padding: 8px 0 0;">→ Would you like to add a co-admin for this school?</div>
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
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #1E293B;"
                        onmouseover="this.style.borderColor='#1E6FD9';this.style.boxShadow='0 2px 8px rgba(30,111,217,0.12)'"
                        onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='none'">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #EFF6FF; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1E6FD9; font-size: 12px;">{{ strtoupper(substr($teacher->name, 0, 1)) }}</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600;">{{ $teacher->name }}</div>
                        <div style="font-size: 11px; color: #64748B;">{{ $teacher->email }}</div>
                    </div>
                </button>
                @endforeach
                <button wire:click="promoteCoAdminAddNew"
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #64748B; margin-top: 4px;"
                        onmouseover="this.style.borderColor='#22C55E';this.style.color='#166534'"
                        onmouseout="this.style.borderColor='#CBD5E1';this.style.color='#64748B'">
                    <span style="font-size: 16px;">＋</span>
                    <span>Add someone else (not on this list)</span>
                </button>
            </div>
            @endif

            {{-- Confirmation Buttons (shown when substep indicates awaiting yes/no) --}}
            {{-- Success Card (shown after commit) --}}
            @if($step === 99 && !empty($reviewData['committed']))
            @php $isComplete = ($reviewData['mode'] ?? 'create') === 'complete'; @endphp
            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 4px 0; text-align: center;">
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
                    <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px;">
                        <div style="font-size: 11px; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.04em;">Admin Login Credentials</div>
                        <div style="font-size: 14px; font-weight: 600; color: #0F172A; margin-top: 6px;">{{ $reviewData['adminEmail'] ?? '—' }}</div>
                        <div style="font-size: 13px; color: #64748B; margin-top: 2px;">Password: <strong>{{ $reviewData['adminPassword'] ?? 'password' }}</strong></div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 2px;">📱 {{ $reviewData['adminPhone'] ?? '—' }}</div>
                    </div>
                    @if(!empty($reviewData['coAdminEmail']))
                    <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px;">
                        <div style="font-size: 11px; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.04em;">Co-Admin</div>
                        <div style="font-size: 14px; font-weight: 600; color: #0F172A; margin-top: 6px;">{{ $reviewData['coAdminEmail'] ?? '—' }}</div>
                        @if(!empty($reviewData['coAdminPromoted']))
                        <div style="font-size: 12px; color: #166534; margin-top: 2px;">✅ Promoted from teacher — they keep their existing password</div>
                        @else
                        <div style="font-size: 13px; color: #64748B; margin-top: 2px;">Password: <strong>{{ $reviewData['coAdminPassword'] ?? 'password' }}</strong></div>
                        @endif
                    </div>
                    @endif
                    <div style="font-size: 12px; color: #64748B; line-height: 1.5;">
                        Share these credentials with the school admin. They can log in at the <strong>KlassApp login page</strong>.
                    </div>
                    @else
                    <div style="font-size: 13px; color: #64748B; line-height: 1.5;">
                        ✅ Your school setup is complete. Toshi will remind you if anything needs attention later.
                    </div>
                    @endif
                </div>
                <div style="padding: 0 16px 16px;">
                    <button wire:click="resetOnboarding"
                            style="width: 100%; padding: 11px; background: #0F172A; color: #FFFFFF; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.background='#1E293B'" onmouseout="this.style.background='#0F172A'">
                        @if($isComplete) ✅ Done — Ask Toshi @else + Onboard Another School @endif
                    </button>
                </div>
            </div>
            @endif

            {{-- Step progress bar — dots for all 15 steps --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] !== 'review')
            <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; margin: 0 10px; background: #F8FAFC; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 3px; flex: 1;">
                    @foreach($steps as $i => $name)
                        @php
                            $isDone = $i < $step;
                            $isCurrent = $i === $step;
                        @endphp
                        <div style="flex: 1; height: 4px; border-radius: 2px; background: {{ $isDone ? '#22C55E' : ($isCurrent ? '#0F172A' : '#E2E8F0') }};{{ $isCurrent ? ' box-shadow: 0 0 0 2px rgba(15,23,42,0.15);' : '' }}"></div>
                    @endforeach
                </div>
                <span style="font-size: 10px; font-weight: 600; color: #64748B; white-space: nowrap;">{{ $step + 1 }}/{{ count($steps) }}</span>
                @if(in_array($steps[$step] ?? '', $mandatorySteps ?? []))
                <span style="background: #FEF2F2; color: #DC2626; padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; white-space: nowrap;">Required</span>
                @else
                <span style="background: #F0FDF4; color: #16A34A; padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; white-space: nowrap;">Optional</span>
                @endif
            </div>
            @endif

            {{-- Review Card (shown on review step) --}}
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'review' && !empty($reviewData) && empty($reviewData['committed']))
            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 4px 0;">
                {{-- Header --}}
                <div style="background: #0F172A; color: #FFFFFF; padding: 14px 16px; font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; letter-spacing: -0.01em; display: flex; align-items: center; gap: 8px;">
                    <span>📋 Review &amp; Confirm</span>
                </div>

                {{-- Body --}}
                <div style="padding: 12px 16px; display: flex; flex-direction: column; gap: 10px;">
                    {{-- Plan + School --}}
                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1; background: #F8FAFC; border-radius: 10px; padding: 10px 12px;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.04em;">Plan</div>
                            <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-top: 3px;">{{ $reviewData['plan'] }}</div>
                        </div>
                        <div style="flex: 1; background: #F8FAFC; border-radius: 10px; padding: 10px 12px;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.04em;">School</div>
                            <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-top: 3px;">{{ $reviewData['schoolName'] }}</div>
                            <div style="font-size: 11px; color: #64748B;">{{ ucfirst($reviewData['schoolType']) }}</div>
                        </div>
                    </div>

                    {{-- Admin --}}
                    <div style="background: #F8FAFC; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.04em;">Admin Account</div>
                        <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-top: 3px;">{{ $reviewData['adminName'] }}</div>
                        <div style="font-size: 11px; color: #64748B;">{{ $reviewData['adminEmail'] }} · {{ $reviewData['adminPhone'] }}</div>
                    </div>

                    @if(!empty($reviewData['coAdminName']))
                    {{-- Co-Admin --}}
                    <div style="background: #F8FAFC; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.04em;">Co-Admin</div>
                        <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-top: 3px;">{{ $reviewData['coAdminName'] }}</div>
                        <div style="font-size: 11px; color: #64748B;">{{ $reviewData['coAdminEmail'] }}</div>
                    </div>
                    @endif

                    {{-- WhatsApp Status --}}
                    <div style="background: {{ !empty($reviewData['whatsapp']) && str_contains($reviewData['whatsapp'], '✅') ? '#F0FDF4' : '#FEF2F2' }}; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">📱</span>
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: #0F172A;">WhatsApp</div>
                            <div style="font-size: 11px; color: #64748B;">{{ $reviewData['whatsapp'] ?? 'Not set up' }}</div>
                        </div>
                    </div>

                    {{-- Counts grid --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
                        <div style="background: #F8FAFC; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 700; color: #1E6FD9;">{{ $reviewData['classCount'] }}</div>
                            <div style="font-size: 10px; color: #64748B; font-weight: 500;">Classes</div>
                        </div>
                        <div style="background: #F8FAFC; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 700; color: #22C55E;">{{ $reviewData['teacherCount'] }}</div>
                            <div style="font-size: 10px; color: #64748B; font-weight: 500;">Teachers</div>
                            @if(!empty($reviewData['teacherLinkCount']) && $reviewData['teacherLinkCount'] > 0)
                            <div style="font-size: 8px; color: #94A3B8; margin-top: 2px;">{{ $reviewData['teacherLinkCount'] }} subject links</div>
                            @endif
                        </div>
                        <div style="background: #F8FAFC; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 700; color: #D97706;">{{ $reviewData['studentCount'] }}</div>
                            <div style="font-size: 10px; color: #64748B; font-weight: 500;">Students</div>
                            @if(!empty($reviewData['studentIds']) && $reviewData['studentIds'] !== '—')
                            <div style="font-size: 8px; color: #94A3B8; margin-top: 2px;">{{ $reviewData['studentIds'] }}</div>
                            @endif
                        </div>
                        <div style="background: #F8FAFC; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 700; color: #8B5CF6;">{{ $reviewData['termCount'] }}</div>
                            <div style="font-size: 10px; color: #64748B; font-weight: 500;">Terms</div>
                        </div>
                        <div style="background: #F8FAFC; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 700; color: #EC4899;">{{ $reviewData['feeCount'] }}</div>
                            <div style="font-size: 10px; color: #64748B; font-weight: 500;">Fees</div>
                        </div>
                        <div style="background: #F8FAFC; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 700; color: #14B8A6;">{{ $reviewData['examCount'] }}</div>
                            <div style="font-size: 10px; color: #64748B; font-weight: 500;">Exams</div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div style="display: flex; gap: 8px; padding: 12px 16px; border-top: 1px solid #E2E8F0; background: #FAFAFA;">
                    <button wire:click="commit"
                            style="flex: 1; padding: 11px; background: #22C55E; color: #FFFFFF; border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: 'Sora', sans-serif;"
                            onmouseover="this.style.background='#16A34A'" onmouseout="this.style.background='#22C55E'">
                        🎉 Confirm &amp; Create School
                    </button>
                    <button wire:click="editBeforeCommit"
                            style="padding: 11px 14px; background: #FFFFFF; color: #64748B; border: 1px solid #E2E8F0; border-radius: 10px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.borderColor='#1E6FD9';this.style.color='#1E6FD9'" onmouseout="this.style.borderColor='#E2E8F0';this.style.color='#64748B'">
                        ← Edit
                    </button>
                </div>
            </div>
            @endif
        </div>
        {{-- Quick action chips --}}
        @if($scope === 'platform' && $mode === 'assistant' && !$actionStep && !$awaitingConfirm)
        <div class="shrink-0" style="display: flex; flex-direction: column; gap: 6px; padding: 4px 16px 8px; background: #FFFFFF;">
            @php $drafts = $this->getDrafts(); @endphp
            @if(count($drafts) > 0)
            <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.04em;">Continue Setup</div>
            @foreach($drafts as $draft)
            <button wire:click="resumeDraft" type="button"
                    style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; cursor: pointer; text-align: left; font-size: 12px; color: #1E293B; font-weight: 500; transition: all 0.15s; width: 100%;"
                    onmouseover="this.style.borderColor='#22C55E'" onmouseout="this.style.borderColor='#E2E8F0'">
                <span style="width: 24px; height: 24px; border-radius: 6px; background: #22C55E; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px;">↻</span>
                <span style="flex: 1;">{{ $draft['school_name'] }}</span>
                <span style="font-size: 10px; color: #94A3B8;">Step {{ $draft['step'] + 1 }}</span>
            </button>
            @endforeach
            @endif
            <button wire:click="resetOnboarding(true)" type="button"
                    style="display: flex; align-items: center; gap: 6px; padding: 6px 14px; background: #0F172A; color: white; border: none; border-radius: 20px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.15s; align-self: flex-start;"
                    onmouseover="this.style.background='#1E6FD9'"
                    onmouseout="this.style.background='#0F172A'">
                <span style="font-size: 16px; line-height: 1;">+</span> New School
            </button>
        </div>
        @endif

        {{-- School admin quick actions --}}
        @if($scope === 'school' && $mode === 'assistant' && !$actionStep && !$awaitingConfirm && $schoolId)
        <div class="shrink-0" style="display: flex; flex-direction: column; gap: 6px; padding: 4px 16px 8px; background: #FFFFFF;">
            @php
                $school = \App\Models\School::find($schoolId);
                $schoolName = $school ? $school->name : 'School';
                $actions = $this->capabilities['actions'] ?? [];
                $quickActions = [
                    'add_student'  => ['label' => '+ Student', 'hint' => '"add student"'],
                    'record_payment' => ['label' => '💳 Payment', 'hint' => '"record payment"'],
                    'record_attendance' => ['label' => '📋 Attendance', 'hint' => '"mark present"'],
                    'generate_report' => ['label' => '📊 Report', 'hint' => '"show report"'],
                ];
            @endphp
            <div style="font-size: 12px; font-weight: 600; color: #0F172A;">{{ $schoolName }}</div>
            <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.04em;">Quick Actions</div>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                @foreach($quickActions as $action => $def)
                    @if(in_array($action, $actions))
                    <span style="padding: 4px 10px; background: #F1F5F9; border-radius: 6px; font-size: 11px; color: #64748B; cursor: default;" title="{{ $def['hint'] }}">{{ $def['label'] }}</span>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Confirmation buttons --}}
        @if($awaitingConfirm)
        <div class="shrink-0" style="display: flex; gap: 10px; padding: 8px 16px 8px; background: #FFFFFF; border-top: 1px solid #F1F5F9;">
            <button wire:click="confirmYes" type="button"
                    style="flex: 1; padding: 10px; background: #22C55E; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                    onmouseover="this.style.background='#16A34A'"
                    onmouseout="this.style.background='#22C55E'">
                Yes ✓
            </button>
            <button wire:click="confirmNo" type="button"
                    style="flex: 1; padding: 10px; background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                    onmouseover="this.style.background='#E2E8F0'"
                    onmouseout="this.style.background='#F1F5F9'">
                No
            </button>
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects' && $substep === 1)
            <button wire:click="confirmCustom" type="button"
                    style="flex: 1; padding: 10px; background: #1E6FD9; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                    onmouseover="this.style.background='#1557B3'"
                    onmouseout="this.style.background='#1E6FD9'">
                + Customize
            </button>
            @endif
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers' && $substep === 0)
            <button wire:click="showTeacherFormFn" type="button"
                    style="flex: 1; padding: 10px; background: #1E6FD9; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                    onmouseover="this.style.background='#1557B3'"
                    onmouseout="this.style.background='#1E6FD9'">
                + Add Teacher
            </button>
            @endif
            @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'standards' && $substep === 5)
            <button wire:click="confirmSkipAll" type="button"
                    style="flex: 1; padding: 10px; background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                    onmouseover="this.style.background='#E2E8F0'"
                    onmouseout="this.style.background='#F1F5F9'">
                Skip All
            </button>
            @endif
        </div>
        @endif
        {{-- Composer: compact panel — single rounded shell with bottom action row --}}
        <form wire:submit.prevent="send" class="shrink-0" style="background: #FFFFFF;">
            <div style="margin: 0 10px 12px; border: 1px solid rgba(0,0,0,0.08); border-radius: 18px; background: #FFFFFF;">
                {{-- Textarea --}}
                <div style="padding: 12px 14px 0;">
                    <textarea rows="1" wire:model.defer="input"
                              placeholder="Message Toshi…"
                              id="toshi-input-panel"
                              @input="
                                  hasText = $el.value.trim().length > 0;
                                  $el.style.height = 'auto';
                                  $el.style.height = Math.min($el.scrollHeight, 320) + 'px';
                              "
                               @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $el.closest('form').dispatchEvent(new Event('submit', {cancelable:true, bubbles:true})) }"
                               class="resize-none w-full"
                               style="border: none; outline: none; background: transparent; font-size: 15px; color: #0F172A; font-family: 'DM Sans', sans-serif; width: 100%; min-height: 24px; max-height: 320px; line-height: 1.5; padding: 0; box-sizing: border-box; transition: height 0.15s ease;"></textarea>
                </div>
                {{-- Bottom action row --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 8px 8px 10px;">
                    {{-- Voice (left) --}}
                    <button type="button" x-data="{ listening: false }"
                            @click="
                                if (!listening) {
                                    listening = true; hasText = true;
                                    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                                    if (!SpeechRecognition) { listening = false; alert('Voice input not supported in this browser'); return; }
                                    var recognition = new SpeechRecognition();
                                    recognition.lang = 'en-US';
                                    recognition.interimResults = false;
                                    recognition.onresult = function(event) {
                                        var text = event.results[0][0].transcript;
                                        var input = document.getElementById('toshi-input-panel');
                                        input.value = text; input.dispatchEvent(new Event('input', {bubbles: true}));
                                        input.closest('form').querySelector('button[type=submit]').click();
                                        listening = false;
                                    };
                                    recognition.onerror = function() { listening = false; };
                                    recognition.onend = function() { listening = false; };
                                    recognition.start();
                                }
                            "
                            class="relative"
                            style="width: 30px; height: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #94A3B8; transition: color 0.15s;"
                            onmouseover="this.style.color='#1E6FD9'"
                            onmouseout="this.style.color='#94A3B8'">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                        <span x-show="listening" class="absolute" style="width: 8px; height: 8px; border-radius: 50%; background: #22C55E; animation: toshi-pulse 1.4s ease-in-out infinite; top: 2px; right: 2px;"></span>
                    </button>
                    {{-- Right cluster: attach + send --}}
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <label class="flex items-center justify-center cursor-pointer"
                               style="width: 30px; height: 30px; color: #94A3B8; transition: color 0.15s; font-size: 20px; font-weight: 300;"
                               onmouseover="this.style.color='#1E6FD9'"
                               onmouseout="this.style.color='#94A3B8'"
                               title="Upload file">
                            +
                            <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.xls,.pdf,.png,.jpg,.jpeg,.docx,.txt">
                        </label>
                        {{-- Send --}}
                        <button type="submit"
                                :disabled="!hasText"
                                :style="hasText ? 'color: #0F172A; cursor: pointer;' : 'color: #CBD5E1; cursor: default;'"
                                style="width: 30px; height: 30px; background: none; border: none; display: flex; align-items: center; justify-content: center; transition: color 0.15s;"
                                class="active:scale-95">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ===== MAXIMIZED MODAL — Claude-inspired two-column layout ===== --}}
    <div id="toshi-modal" onclick="if(event.target===this){this.style.display='none'}"
         style="{{ $maximized ? 'display: flex;' : 'display: none;' }} position: fixed; inset: 0; align-items: center; justify-content: center; background: rgba(0,0,0,0.4); z-index: 99999;">
        <div style="width: 900px; max-width: 92vw; height: 85vh; background: #FFFFFF; border-radius: 16px; display: flex; overflow: hidden; box-shadow: 0 24px 80px rgba(0,0,0,0.25);"
             onclick="event.stopPropagation()">

            {{-- Left Sidebar --}}
            <div style="width: 260px; background: #F8FAFC; border-right: 1px solid #E2E8F0; display: flex; flex-direction: column; padding: 16px; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px; padding-bottom: 4px;">
                    <img src="{{ asset('images/klassapp-logo.svg') }}" style="width: 22px; height: 22px;" alt="KlassApp">
                    <span style="font-size: 16px; font-weight: 700; color: #0F172A; font-family: 'Sora', sans-serif;">Toshi</span>
                </div>
                <div style="font-size: 11px; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.04em; font-weight: 600; margin: 16px 0 8px;">Onboarding Session</div>
                <button wire:click="resumeDraft" style="width: 100%; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #1E293B; font-weight: 500; cursor: pointer; text-align: left; transition: all 0.15s;" onmouseover="this.style.borderColor='#22C55E'" onmouseout="this.style.borderColor='#E2E8F0'">
                    {{ $schoolName ?: ($reviewData['schoolName'] ?? 'New School') }}
                </button>
                @if($scope === 'platform' && $mode === 'assistant' && !$actionStep && !$awaitingConfirm)
                @php $drafts = $this->getDrafts(); @endphp
                @if(count($drafts) > 0)
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 4px;">
                    <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.04em;">Continue Setup</div>
                    @foreach($drafts as $draft)
                    <button wire:click="resumeDraft" type="button"
                            style="display: flex; align-items: center; gap: 8px; padding: 7px 10px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer; text-align: left; font-size: 12px; color: #1E293B; font-weight: 500; transition: all 0.15s; width: 100%; font-family: 'DM Sans', sans-serif;"
                            onmouseover="this.style.borderColor='#22C55E'" onmouseout="this.style.borderColor='#E2E8F0'">
                        <span style="width: 20px; height: 20px; border-radius: 5px; background: #22C55E; color: white; display: flex; align-items: center; justify-content: center; font-size: 10px;">↻</span>
                        <span style="flex: 1;">{{ $draft['school_name'] }}</span>
                        <span style="font-size: 9px; color: #94A3B8;">Step {{ $draft['step'] + 1 }}</span>
                    </button>
                    @endforeach
                </div>
                @endif
                <div style="margin-top: 8px;">
                    <button wire:click="resetOnboarding(true)" type="button"
                            style="width: 100%; padding: 8px 12px; background: #0F172A; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; text-align: center; transition: all 0.15s; font-family: 'DM Sans', sans-serif;"
                            onmouseover="this.style.background='#1E6FD9'"
                            onmouseout="this.style.background='#0F172A'">
                        + New School
                    </button>
                </div>
                @endif
                {{-- Step progress in sidebar --}}
                @if(!empty($steps) && isset($steps[$step]) && $mode !== 'assistant')
                <div style="margin-top: 12px;">
                    <div style="font-size: 10px; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.04em; font-weight: 600; margin-bottom: 6px;">Progress — {{ $step + 1 }}/{{ count($steps) }}</div>
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        @foreach($steps as $i => $name)
                            @php
                                $isDone = $i < $step;
                                $isCurrent = $i === $step;
                                $label = ucfirst(str_replace('_', ' ', $name));
                            @endphp
                            <button wire:click="jumpToStep({{ $i }})" type="button"
                                    style="display: flex; align-items: center; gap: 6px; padding: 4px 6px; border: none; border-radius: 4px; background: {{ $isCurrent ? '#FFFFFF' : 'transparent' }}; cursor: {{ $isDone || $isCurrent ? 'pointer' : 'default' }}; text-align: left; font-size: 10px; color: {{ $isDone ? '#22C55E' : ($isCurrent ? '#0F172A' : '#CBD5E1') }}; font-weight: {{ $isCurrent ? '600' : '400' }}; transition: all 0.15s; font-family: 'DM Sans', sans-serif;"
                                    onmouseover="this.style.background='#FFFFFF'" onmouseout="this.style.background='{{ $isCurrent ? '#FFFFFF' : 'transparent' }}'">
                                <span style="width: 14px; height: 14px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 7px; font-weight: 700; flex-shrink: 0; background: {{ $isDone ? '#22C55E' : ($isCurrent ? '#0F172A' : '#E2E8F0') }}; color: white;">{{ $isDone ? '✓' : $i + 1 }}</span>
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                @endif
                <div style="margin-top: auto; padding-top: 16px;">
                    <button wire:click="hide" style="font-size: 12px; color: #94A3B8; background: none; border: none; cursor: pointer; padding: 4px 0; font-family: 'DM Sans', sans-serif;" onmouseover="this.style.color='#64748B'" onmouseout="this.style.color='#94A3B8'">Close</button>
                </div>
            </div>

            {{-- Right Main Area --}}
            <div style="flex: 1; display: flex; flex-direction: column; background: #FFFFFF; min-width: 0;">

                {{-- Header --}}
                <div style="height: 48px; border-bottom: 1px solid #E2E8F0; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; background: #FFFFFF; flex-shrink: 0;">
                    <span style="font-size: 14px; font-weight: 600; color: #0F172A; font-family: 'DM Sans', sans-serif;">Toshi</span>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button wire:click="restore" style="width: 28px; height: 28px; border-radius: 6px; background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #94A3B8;" onmouseover="this.style.color='#64748B'" onmouseout="this.style.color='#94A3B8'" title="Restore">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 1H1v3M10 1h3v3M10 13h3v-3M4 13H1v-3"/></svg>
                        </button>
                        <button wire:click="hide" style="width: 28px; height: 28px; border-radius: 6px; background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #94A3B8;" onmouseover="this.style.color='#64748B'" onmouseout="this.style.color='#94A3B8'" title="Close">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 2l10 10M12 2L2 12"/></svg>
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
                                <div style="display: flex; justify-content: flex-end;">
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; color: #1E293B; border-radius: 12px 0 12px 12px; padding: 12px 16px; font-size: 14px; line-height: 1.6; max-width: 75%;">
                                        {!! nl2br(e($msg['text'])) !!}
                                    </div>
                                </div>
                            @else
                                <div style="display: flex; gap: 12px; align-items: flex-start;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #22C55E; flex-shrink: 0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        <img src="{{ asset('images/klassapp-logo.svg') }}" style="width: 18px; height: 18px;" alt="Toshi">
                                    </div>
                                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 0 12px 12px 12px; padding: 12px 16px; font-size: 14px; line-height: 1.6; color: #1E293B; max-width: 85%;">
                                        {!! preg_replace('/\*\*(.+?)\*\*/','<strong>$1</strong>',nl2br(e($msg['text']))) !!}
                                    </div>
                                </div>
                            @endif
                        @endforeach
                                    {{-- Subject inline form --}}
                                    @if($showSubjectForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects')
                                    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px; margin: 4px 0;">
                                        <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-bottom: 10px;">Add Subject</div>
                                        @php $subjects = $this->actionData['subjects'] ?? []; @endphp
                                        @if(count($subjects) > 0)
                                        <div style="margin-bottom: 8px;">
                                            @foreach($subjects as $si => $s)
                                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px 0; font-size: 12px; color: #1E293B; border-bottom: 1px solid #F1F5F9;">
                                                <span style="flex: 1;">{{ $s }}</span>
                                                <button wire:click="removeSubject({{ $si }})" type="button"
                                                        style="background: none; border: none; color: #EF4444; cursor: pointer; font-size: 14px; padding: 2px 4px;">✕</button>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <input type="text" wire:model="subjectFormName" placeholder="Subject name *"
                                                   style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                                            <input type="text" wire:model="subjectFormCode" placeholder="Subject code (optional)"
                                                   style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                                            <select wire:model="subjectFormType"
                                                    style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; background: white; font-family: 'DM Sans', sans-serif;">
                                                <option value="core">Core</option>
                                                <option value="elective">Elective</option>
                                            </select>
                                            <div style="display: flex; gap: 8px;">
                                                <button wire:click="saveSubject" type="button"
                                                        style="flex: 1; padding: 8px; background: #1E6FD9; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                                    + Add Subject
                                                </button>
                                                <button wire:click="doneSubjects" type="button"
                                                        style="padding: 8px 12px; background: #22C55E; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                                                    Done ({{ count($this->actionData['subjects'] ?? []) }})
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Teacher inline form (maximized) --}}
                                    @if($showTeacherForm && !empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers')
                                    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px; margin: 4px 0;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                            <span style="font-size: 13px; font-weight: 600; color: #0F172A;">Add Teacher</span>
                                            <div style="display: flex; align-items: center; gap: 6px; margin-left: auto;">
                                                <span style="font-size: 10px; color: #94A3B8; cursor: help;" title="CSV/XLSX: columns Name, Email, Subjects, Classes. TXT: one name per line.">ⓘ</span>
                                                <label class="flex items-center gap-1 cursor-pointer"
                                                       style="padding: 4px 8px; background: #F1F5F9; border-radius: 6px; font-size: 11px; color: #64748B; cursor: pointer;">
                                                    Upload File
                                                    <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.txt,.docx">
                                                </label>
                                            </div>
                                        </div>
                                        @php $teachers = $this->actionData['teachers'] ?? []; @endphp
                                        @if(count($teachers) > 0)
                                        <div style="margin-bottom: 8px;">
                                            @foreach($teachers as $ti => $t)
                                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px 0; font-size: 12px; color: #1E293B; border-bottom: 1px solid #F1F5F9;">
                                                <span style="flex: 1;">{{ $t }}</span>
                                                <button wire:click="removeTeacher({{ $ti }})" type="button"
                                                        style="background: none; border: none; color: #EF4444; cursor: pointer; font-size: 14px; padding: 2px 4px;">✕</button>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <input type="text" wire:model="teacherFormName" placeholder="Teacher name *"
                                                   style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                                            <input type="email" wire:model="teacherFormEmail" placeholder="Email *"
                                                   style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                                            <input type="text" wire:model="teacherFormPhone" placeholder="WhatsApp number (optional)"
                                                   style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                                            <div style="display: flex; gap: 8px;">
                                                <div style="flex: 1;">
                                                    <div style="font-size: 10px; color: #94A3B8; margin-bottom: 2px;">Subject(s)</div>
                                                    <input type="text" wire:model="teacherFormSubjects" placeholder="e.g. Math, English" list="subject-list-modal"
                                                           style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
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
                                                <div style="flex: 1;">
                                                    <div style="font-size: 10px; color: #94A3B8; margin-bottom: 2px;">Class(es)</div>
                                                    <input type="text" wire:model="teacherFormClasses" placeholder="e.g. P1, P2" list="class-list-modal"
                                                           style="padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; font-family: 'DM Sans', sans-serif;">
                                                    <datalist id="class-list-modal">
                                                        @foreach($this->standards ?? [] as $std)
                                                        <option value="{{ $std['name'] }}">
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 8px;">
                                                <button wire:click="saveTeacher" type="button"
                                                        style="flex: 1; padding: 8px; background: #1E6FD9; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                                    + Add Teacher
                                                </button>
                                                <button wire:click="doneTeachers" type="button"
                                                        style="padding: 8px 12px; background: #22C55E; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                                                    Done ({{ count($this->actionData['teachers'] ?? []) }})
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
                                    style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #1E293B; font-family: 'DM Sans', sans-serif;"
                                    onmouseover="this.style.borderColor='#22C55E';this.style.boxShadow='0 2px 8px rgba(34,197,94,0.15)'"
                                    onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='none'">
                                <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; background: #22C55E; color: white;">{{ $loop->first ? '🆓' : ($loop->iteration === 2 ? '⭐' : '👑') }}</div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;">{{ ucfirst($plan->name) }}</div>
                                    <div style="color: #64748B; font-size: 12px; margin-top: 2px;">
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

                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'school_info' && $substep === 2)
                        <div style="display: flex; flex-direction: column; gap: 8px; padding: 4px 0;">
                            <div style="font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 4px;">→ Category</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button wire:click="setSchoolType('nursery')" class="toshi-btn">Nursery</button>
                                <button wire:click="setSchoolType('primary','','mixed')" class="toshi-btn">Primary</button>
                                <button wire:click="setSchoolType('secondary','o-level','mixed')" class="toshi-btn">Secondary</button>
                                <button wire:click="setSchoolType('mixed','both','mixed')" class="toshi-btn">All Levels</button>
                            </div>
                        </div>
                        @endif

                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'co_admin_invite' && $substep === 0)
                        <div style="font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 2px; padding: 4px 0 0;">→ Would you like to add a co-admin for this school?</div>
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
                                    style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #1E293B;"
                                    onmouseover="this.style.borderColor='#1E6FD9';this.style.boxShadow='0 2px 8px rgba(30,111,217,0.12)'"
                                    onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='none'">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #EFF6FF; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1E6FD9; font-size: 12px;">{{ strtoupper(substr($teacher->name, 0, 1)) }}</div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600;">{{ $teacher->name }}</div>
                                    <div style="font-size: 11px; color: #64748B;">{{ $teacher->email }}</div>
                                </div>
                            </button>
                            @endforeach
                            <button wire:click="promoteCoAdminAddNew"
                                    style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: left; font-size: 13px; color: #64748B; margin-top: 4px;"
                                    onmouseover="this.style.borderColor='#22C55E';this.style.color='#166534'"
                                    onmouseout="this.style.borderColor='#CBD5E1';this.style.color='#64748B'">
                                <span style="font-size: 16px;">＋</span>
                                <span>Add someone else (not on this list)</span>
                            </button>
                        </div>
                        @endif

                        @if($step === 99 && !empty($reviewData['committed']))
                        @php $isComplete = ($reviewData['mode'] ?? 'create') === 'complete'; @endphp
                        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 4px 0; text-align: center;">
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
                                <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px;">
                                    <div style="font-size: 11px; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.04em;">Admin Login Credentials</div>
                                    <div style="font-size: 14px; font-weight: 600; color: #0F172A; margin-top: 6px;">{{ $reviewData['adminEmail'] ?? '—' }}</div>
                                    <div style="font-size: 13px; color: #64748B; margin-top: 2px;">Password: <strong>{{ $reviewData['adminPassword'] ?? 'password' }}</strong></div>
                                    <div style="font-size: 12px; color: #64748B; margin-top: 2px;">📱 {{ $reviewData['adminPhone'] ?? '—' }}</div>
                                </div>
                                @if(!empty($reviewData['coAdminEmail']))
                                <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px;">
                                    <div style="font-size: 11px; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.04em;">Co-Admin</div>
                                    <div style="font-size: 14px; font-weight: 600; color: #0F172A; margin-top: 6px;">{{ $reviewData['coAdminEmail'] ?? '—' }}</div>
                                    @if(!empty($reviewData['coAdminPromoted']))
                                    <div style="font-size: 12px; color: #166534; margin-top: 2px;">✅ Promoted from teacher — they keep their existing password</div>
                                    @else
                                    <div style="font-size: 13px; color: #64748B; margin-top: 2px;">Password: <strong>{{ $reviewData['coAdminPassword'] ?? 'password' }}</strong></div>
                                    @endif
                                </div>
                                @endif
                                <div style="font-size: 12px; color: #64748B; line-height: 1.5;">
                                    Share these credentials with the school admin. They can log in at the <strong>KlassApp login page</strong>.
                                </div>
                                @else
                                <div style="font-size: 13px; color: #64748B; line-height: 1.5;">
                                    ✅ Your school setup is complete. Toshi will remind you if anything needs attention later.
                                </div>
                                @endif
                            </div>
                            <div style="padding: 0 16px 16px;">
                                <button wire:click="resetOnboarding"
                                        style="width: 100%; padding: 11px; background: #0F172A; color: #FFFFFF; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif;"
                                        onmouseover="this.style.background='#1E293B'" onmouseout="this.style.background='#0F172A'">
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
                                style="flex: 1; padding: 10px; background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.background='#E2E8F0'"
                                onmouseout="this.style.background='#F1F5F9'">
                        No
                    </button>
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'subjects' && $substep === 1)
                        <button wire:click="confirmCustom" type="button"
                                style="flex: 1; padding: 10px; background: #1E6FD9; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.background='#1557B3'"
                                onmouseout="this.style.background='#1E6FD9'">
                            + Customize
                        </button>
                        @endif
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'teachers' && $substep === 0)
                        <button wire:click="showTeacherFormFn" type="button"
                                style="flex: 1; padding: 10px; background: #1E6FD9; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.background='#1557B3'"
                                onmouseout="this.style.background='#1E6FD9'">
                            + Add Teacher
                        </button>
                        @endif
                        @if(!empty($steps) && isset($steps[$step]) && $steps[$step] === 'standards' && $substep === 5)
                        <button wire:click="confirmSkipAll" type="button"
                                style="flex: 1; padding: 10px; background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.background='#E2E8F0'"
                                onmouseout="this.style.background='#F1F5F9'">
                            Skip All
                        </button>
                        @endif
                </div>
                @endif
            {{-- Composer: maximized modal — single rounded shell with bottom action row --}}
                <form wire:submit.prevent="send" class="shrink-0" style="padding: 0 24px 16px; background: #FFFFFF;">
                    <div style="border: 1px solid rgba(0,0,0,0.08); border-radius: 18px; background: #FFFFFF;">
                        {{-- Textarea --}}
                        <div style="padding: 14px 16px 0;">
                            <textarea rows="1" wire:model.defer="input"
                                      placeholder="Message Toshi..."
                                      id="toshi-input-modal"
                                      @input="
                                          hasText = $el.value.trim().length > 0;
                                          $el.style.height = 'auto';
                                          $el.style.height = Math.min($el.scrollHeight, 320) + 'px';
                                      "
                                      @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $el.closest('form').dispatchEvent(new Event('submit', {cancelable:true, bubbles:true})) }"
                                      class="resize-none w-full"
                                      style="border: none; outline: none; background: transparent; font-size: 15px; color: #0F172A; font-family: 'DM Sans', sans-serif; width: 100%; min-height: 24px; max-height: 320px; line-height: 1.5; padding: 0; box-sizing: border-box; transition: height 0.15s ease;"></textarea>
                        </div>
                        {{-- Bottom action row --}}
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 4px 8px 6px 12px;">
                            {{-- Voice (left) --}}
                            <button type="button" x-data="{ listening: false }"
                                    @click="
                                        if (!listening) {
                                            listening = true; hasText = true;
                                            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                                            if (!SpeechRecognition) { listening = false; alert('Voice input not supported in this browser'); return; }
                                            var recognition = new SpeechRecognition();
                                            recognition.lang = 'en-US';
                                            recognition.interimResults = false;
                                            recognition.onresult = function(event) {
                                                var text = event.results[0][0].transcript;
                                                var input = document.getElementById('toshi-input-modal');
                                                input.value = text; input.dispatchEvent(new Event('input', {bubbles: true}));
                                                input.closest('form').querySelector('button[type=submit]').click();
                                                listening = false;
                                            };
                                            recognition.onerror = function() { listening = false; };
                                            recognition.onend = function() { listening = false; };
                                            recognition.start();
                                        }
                                    "
                                    class="relative"
                                    style="width: 30px; height: 30px; background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #94A3B8; transition: color 0.15s;"
                                    onmouseover="this.style.color='#1E6FD9'"
                                    onmouseout="this.style.color='#94A3B8'">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                                <span x-show="listening" class="absolute" style="width: 8px; height: 8px; border-radius: 50%; background: #22C55E; animation: toshi-pulse 1.4s ease-in-out infinite; top: 2px; right: 2px;"></span>
                            </button>
                            {{-- Right cluster: attach + send --}}
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <label class="flex items-center justify-center cursor-pointer"
                                       style="width: 30px; height: 30px; color: #94A3B8; transition: color 0.15s; font-size: 20px; font-weight: 300;"
                                       onmouseover="this.style.color='#1E6FD9'"
                                       onmouseout="this.style.color='#94A3B8'"
                                       title="Upload file">
                                    +
                                    <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.xls,.pdf,.png,.jpg,.jpeg,.docx,.txt">
                                </label>
                                {{-- Send --}}
                                <button type="submit"
                                        :disabled="!hasText"
                                        :style="hasText ? 'color: #0F172A; cursor: pointer;' : 'color: #CBD5E1; cursor: default;'"
                                        style="width: 30px; height: 30px; background: none; border: none; display: flex; align-items: center; justify-content: center; transition: color 0.15s;"
                                        class="active:scale-95">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
