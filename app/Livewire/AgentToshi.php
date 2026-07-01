<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\School;
use App\Models\User;
use App\Models\Standard;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use App\Models\Teacherlink;
use App\Models\Userprofile;
use App\Models\Subscription;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\AcademicTerm;
use App\Models\StudentAcademic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\CoAdminInviteMail;
use App\Models\OnboardingSession;
use App\Services\ToshiActionService;
use App\Services\ToshiAssistantService;

class AgentToshi extends Component
{
    use WithFileUploads;

    public $step = 0;
    public $visible = false;
    public $maximized = false;
    public $attachment = null;
    public $messages = [];
    public $input = '';
    public $schoolId = null;
    public $preview = [];
    public $substep = 0; // internal sub-step within a step

    // Collected data across steps
    public $schoolName = '';
    public $schoolType = 'primary';
    public $schoolLevel = '';   // o-level, a-level, both
    public $schoolGender = '';  // boys, girls, mixed
    public $schoolEmail = '';
    public $schoolPhone = '';
    public $adminName = '';
    public $adminEmail = '';
    public $adminPassword = '';
    public $coAdminName = '';
    public $coAdminEmail = '';
    public $coAdminUserId = null;
    public $academicYearLabel = '';
    public $standards = [];
    public $subjects = [];
    public $teacherList = [];
    public $teacherLinks = [];
    public $teacherPhones = [];
    public $studentList = [];
    public $hasNursery = null; // null = not asked, true/false for primary schools
    public $streamClassIndex = 0; // tracks which class we're adding streams for
    public $showSubjectForm = false;
    public $subjectFormName = '';
    public $subjectFormCode = '';
    public $subjectFormType = 'core';
    public $subjectFormClass = '';
    public $showTeacherForm = false;
    public $teacherFormName = '';
    public $teacherFormEmail = '';
    public $teacherFormSubjects = '';
    public $teacherFormClasses = '';
    public $teacherFormPhone = '';
    public $terms = [];
    public $fees = [];
    public $exams = [];
    public $selectedPlanId = null;
    public $reviewData = []; // populated when entering review step

    public $steps = [
        'plan_selection',
        'school_info',
        'admin_account',
        'co_admin_invite',
        'academic_year',
        'standards',
        'subjects',
        'teachers',
        'teacher_links',
        'students',
        'terms',
        'fees',
        'exams',
        'whatsapp_verify',
        'review',
    ];

    /**
     * Steps that must be completed before a school can function.
     * Optional steps can be skipped and completed later via the admin panel.
     */
    public $mandatorySteps = [
        'plan_selection',
        'school_info',
        'admin_account',
        'academic_year',
        'standards',
        'subjects',
        'terms',
    ];

    public $whatsappPhone = '';
    public $whatsappSentOtp = '';
    public $whatsappVerified = false;

    public $mode = 'assistant';
    public $scope = 'school'; // 'platform' (super admin) or 'school' (school admin)
    public $draftSessionId = null;

    // Assistant-mode action flows (multi-step operations)
    public $actionStep = null;    // 'add_student', 'add_teacher', 'enter_marks', etc.
    public $actionSubstep = 0;
    public $actionData = [];

    /** Capabilities for the current user, loaded from ToshiActionService. */
    public array $capabilities = [];

    /** When true, the UI shows Yes/No buttons instead of requiring text input. */
    public bool $awaitingConfirm = false;

    public function mount()
    {
        $user = auth()->user();
        if (!$user) return;

        $this->capabilities = ToshiActionService::getRoleCapabilities($user->usergroup_id);

        // Gate: block roles with no allowed actions or no scope
        if (empty($this->capabilities['actions']) || $this->capabilities['scope'] === 'none') return;

        $this->scope = $this->capabilities['scope'];

        // ── Restore state from session (survives page refresh) ──
        if ($this->restoreState()) {
            if ($this->mode === 'assistant' && empty($this->messages)) {
                $this->botSay($this->getAssistantGreeting());
            }
            return;
        }

        // ── Platform scope (super admin) ──
        if ($this->scope === 'platform') {
            $this->mode = 'assistant';
            $this->step = 99;

            $greeting = $this->getAssistantGreeting();

            $draft = OnboardingSession::where('user_id', $user->id)
                ->where('status', 'draft')
                ->latest()
                ->first();
            if ($draft) {
                $stepName = $this->steps[$draft->step] ?? 'setup';
                $greeting .= " By the way, you have an unfinished school setup on the **" . ucfirst(str_replace('_', ' ', $stepName)) . "** step. Say **'create school'** to start fresh or continue where you left off.";
                $this->draftSessionId = $draft->id;
            }

            $this->botSay($greeting);
            return;
        }

        // ── School scope (school roles with school_id) ──
        if ($this->scope === 'school' && $user->school_id) {
            $this->schoolId = $user->school_id;
            $school = \App\Models\School::find($this->schoolId);

            // Only school admins (usergroup_id 3) get the setup completion check
            $missing = $user->usergroup_id === 3
                ? \App\Helpers\OnboardingHelper::getMissingSteps($user->school_id, $user->id)
                : [];

            if (empty($missing)) {
                $this->mode = 'assistant';
                $this->step = 99;
                $this->botSay($this->getAssistantGreeting());
                return;
            }

            $this->mode = 'complete';
            $this->botSay("Hello! Let's finish setting up **{$school->name}** on KlassApp.");
            $this->detectMissingSteps();
            return;
        }

        // ── Fallback: assistant mode ──
        $this->mode = 'assistant';
        $this->scope = 'school';
        $this->step = 99;
        $this->botSay($this->getAssistantGreeting());
    }

    /**
     * Returns the appropriate assistant greeting based on the user's capabilities.
     */
    private function getAssistantGreeting(): string
    {
        $label = $this->capabilities['label'] ?? 'user';

        if ($this->scope === 'platform') {
            return "Hi! I'm Toshi, your KlassApp {$label} assistant. I can help with platform stats, schools, users, and system management. What would you like to know?";
        }

        $schoolName = '';
        if ($this->schoolId) {
            $school = \App\Models\School::find($this->schoolId);
            $schoolName = $school ? " **{$school->name}**" : '';
        }

        $actionHints = $this->getActionHints();

        return "Hi! I'm Toshi. Ask me about{$schoolName} — {$actionHints}";
    }

    /**
     * Get a human-readable list of action hints from the user's capabilities.
     */
    private function getActionHints(): string
    {
        $actions = $this->capabilities['actions'] ?? [];
        if (empty($actions)) {
            return 'reports, stats, and school information';
        }

        $hints = [
            'add_student'        => 'add students',
            'add_teacher'        => 'add teachers',
            'add_coadmin'        => 'add co-admins',
            'create_fee'         => 'manage fees',
            'create_term'        => 'create terms',
            'record_attendance'  => 'record attendance',
            'record_payment'     => 'record fee payments',
            'assign_teacher'     => 'assign teachers to classes',
            'create_subject'     => 'create subjects',
            'list_classes'       => 'view classes',
            'list_teachers'      => 'view teachers',
            'list_sections'      => 'view class streams',
            'generate_report'    => 'reports and stats',
        ];

        $matched = [];
        foreach ($actions as $action) {
            if (isset($hints[$action])) {
                $matched[] = $hints[$action];
            }
        }

        if (empty($matched)) {
            return 'reports, stats, and school information';
        }

        // Combine with unique, natural phrasing
        $matched = array_unique($matched);
        if (count($matched) === 1) {
            return $matched[0];
        }

        $last = array_pop($matched);
        return implode(', ', $matched) . ', and ' . $last;
    }

    /**
     * Check if the current user can perform a given action.
     */
    private function can(string $action): bool
    {
        return in_array($action, $this->capabilities['actions'] ?? [], true);
    }

    private function restoreDraft(OnboardingSession $draft)
    {
        $data = $draft->data;
        $this->step = $draft->step;
        $this->substep = $draft->substep;
        $this->mode = $data['mode'] ?? 'create';
        $this->schoolId = $draft->school_id;
        $this->schoolName = $data['schoolName'] ?? '';
        $this->schoolType = $data['schoolType'] ?? 'primary';
        $this->schoolLevel = $data['schoolLevel'] ?? '';
        $this->schoolGender = $data['schoolGender'] ?? '';
        $this->schoolEmail = $data['schoolEmail'] ?? '';
        $this->schoolPhone = $data['schoolPhone'] ?? '';
        $this->adminName = $data['adminName'] ?? '';
        $this->adminEmail = $data['adminEmail'] ?? '';
        $this->adminPassword = $data['adminPassword'] ?? '';
        $this->coAdminName = $data['coAdminName'] ?? '';
        $this->coAdminEmail = $data['coAdminEmail'] ?? '';
        $this->coAdminUserId = $data['coAdminUserId'] ?? null;
        $this->academicYearLabel = $data['academicYearLabel'] ?? '';
        $this->selectedPlanId = $data['selectedPlanId'] ?? null;
        $this->standards = $data['standards'] ?? [];
        $this->subjects = $data['subjects'] ?? [];
        $this->teacherList = $data['teacherList'] ?? [];
        $this->teacherLinks = $data['teacherLinks'] ?? [];
        $this->teacherPhones = $data['teacherPhones'] ?? [];
        $this->studentList = $data['studentList'] ?? [];
        $this->terms = $data['terms'] ?? [];
        $this->fees = $data['fees'] ?? [];
        $this->exams = $data['exams'] ?? [];
        $this->whatsappPhone = $data['whatsappPhone'] ?? '';
        $this->whatsappVerified = $data['whatsappVerified'] ?? false;
        $this->messages = $data['messages'] ?? [];
    }

    private function saveDraft()
    {
        if ($this->mode !== 'create') return; // only persist creation mode
        $user = auth()->user();
        if (!$user) return;

        $data = [
            'mode'              => $this->mode,
            'schoolName'        => $this->schoolName,
            'schoolType'        => $this->schoolType,
            'schoolLevel'       => $this->schoolLevel,
            'schoolGender'      => $this->schoolGender,
            'schoolEmail'       => $this->schoolEmail,
            'schoolPhone'       => $this->schoolPhone,
            'adminName'         => $this->adminName,
            'adminEmail'        => $this->adminEmail,
            'adminPassword'     => $this->adminPassword,
            'coAdminName'       => $this->coAdminName,
            'coAdminEmail'      => $this->coAdminEmail,
            'coAdminUserId'     => $this->coAdminUserId,
            'academicYearLabel' => $this->academicYearLabel,
            'selectedPlanId'    => $this->selectedPlanId,
            'standards'         => $this->standards,
            'subjects'          => $this->subjects,
            'teacherList'       => $this->teacherList,
            'teacherLinks'      => $this->teacherLinks,
            'teacherPhones'     => $this->teacherPhones,
            'studentList'       => $this->studentList,
            'terms'             => $this->terms,
            'fees'              => $this->fees,
            'hasNursery'        => $this->hasNursery,
            'exams'             => $this->exams,
            'whatsappPhone'     => $this->whatsappPhone,
            'whatsappVerified'  => $this->whatsappVerified,
            'messages'          => $this->messages,
        ];

        $draft = OnboardingSession::updateOrCreate(
            ['id' => $this->draftSessionId ?? 0],
            [
                'user_id'   => $user->id,
                'school_id' => $this->schoolId,
                'step'      => $this->step,
                'substep'   => $this->substep,
                'data'      => $data,
                'status'    => 'draft',
            ]
        );
        $this->draftSessionId = $draft->id;
    }

    private function deleteDraft()
    {
        if ($this->draftSessionId) {
            OnboardingSession::where('id', $this->draftSessionId)->delete();
            $this->draftSessionId = null;
        }
    }

    /**
     * School Admin mode: detect what's missing and jump to first incomplete step.
     */
    private function detectMissingSteps()
    {
        $sid = $this->schoolId;
        $missing = [];

        if (!\App\Models\Standard::where('school_id', $sid)->exists()) $missing[] = 'standards';
        if (!\App\Models\Subject::where('school_id', $sid)->exists()) $missing[] = 'subjects';
        if (!\App\Models\Teacherlink::where('school_id', $sid)->exists()) $missing[] = 'teachers';
        if (!\App\Models\AcademicTerm::where('school_id', $sid)->exists()) $missing[] = 'terms';
        if (!\App\Models\FeesCategories::where('school_id', $sid)->exists()) $missing[] = 'fees';
        if (!\App\Models\WhatsAppUser::where('user_id', auth()->id())->exists()) $missing[] = 'whatsapp_verify';

        if (empty($missing)) {
            $this->botSay("✅ Everything looks set up! Your school is ready to go.");
            $this->botSay("If you need help with anything, just ask.");
            $this->step = 99;
            return;
        }

        $this->botSay("I found **" . count($missing) . "** thing" . (count($missing) > 1 ? 's' : '') . " to set up:");
        foreach ($missing as $item) {
            $labels = [
                'standards' => '📚 Classes',
                'subjects'  => '📖 Subjects',
                'teachers'  => '👨‍🏫 Teachers',
                'terms'     => '📅 Academic terms',
                'fees'      => '💵 Fee structures',
                'whatsapp_verify' => '📱 WhatsApp verification',
            ];
            $this->botSay("  ❌ " . ($labels[$item] ?? $item));
        }

        // Jump to first missing step
        $stepNames = array_values($this->steps);
        foreach ($stepNames as $i => $name) {
            if (in_array($name, $missing)) {
                $this->step = $i;
                break;
            }
        }

        $current = $this->steps[$this->step] ?? '';
        $labels = [
            'standards' => "Let's start with classes. What classes does your school have?",
            'subjects'  => "Let's set up subjects per class.",
            'teachers'  => "Let's add teachers. Paste their names (one per line) or type 'skip'.",
            'terms'     => "Let's set up academic terms.",
            'fees'      => "Let's add fee categories. Type a fee name or 'skip'.",
            'whatsapp_verify' => "Let's verify your WhatsApp number for school notifications.",
        ];
        $this->botSay($labels[$current] ?? "Let's continue setting up.");
    }

    public function show() { $this->visible = true; $this->maximized = false; }
    public function hide() { $this->visible = false; $this->maximized = false; }
    public function maximize() { $this->maximized = true; $this->visible = false; }
    public function restore() { $this->maximized = false; $this->visible = true; }

    // ── Button-driven confirm/edit ──
    public function confirmYes()
    {
        $this->awaitingConfirm = false;
        $this->input = 'yes';
        $this->send();
    }
    public function confirmNo()
    {
        $this->awaitingConfirm = false;
        $this->input = 'no';
        $this->send();
    }

    /**
     * Skip adding streams for all remaining classes and advance.
     */
    public function confirmSkipAll()
    {
        $stepName = $this->steps[$this->step] ?? '';
        if ($stepName !== 'standards' || empty($this->standards)) {
            $this->confirmNo();
            return;
        }
        $this->awaitingConfirm = false;
        $this->streamClassIndex = count($this->standards);
        $this->substep = 0;
        $this->advance();
    }

    /**
     * Custom action button — routes to the "no" path of the current step.
     */
    public function confirmCustom()
    {
        $this->awaitingConfirm = false;
        $this->actionData['subjects'] = $this->actionData['subjects'] ?? [];
        $this->showSubjectForm = true;
        $this->substep = 6;
    }

    /**
     * Save a subject from the inline form and add it to the list.
     */
    public function saveSubject()
    {
        $name = trim($this->subjectFormName);
        if ($name === '') return;

        $this->actionData['subjects'][] = $name;
        $this->subjectFormName = '';
        $this->subjectFormCode = '';
        $this->subjectFormType = 'core';

        $count = count($this->actionData['subjects']);
        $this->botSay("Added **{$name}** ({$count} so far). Add another or click **Done**.");
    }

    /**
     * Finish subject entry.
     */
    public function doneSubjects()
    {
        if (empty($this->actionData['subjects'])) {
            $this->botSay("Add at least one subject first.");
            return;
        }
        $this->showSubjectForm = false;
        $this->subjects = [
            ($this->standards[0]['name'] ?? 'default') => $this->actionData['subjects'],
        ];
        $this->botSay("Subjects saved: **" . implode(', ', $this->actionData['subjects']) . "**.");
        $this->substep = 0;
        $this->advance();
    }

    /**
     * Remove a subject from the list by index.
     */
    public function removeSubject(int $index): void
    {
        if (isset($this->actionData['subjects'][$index])) {
            unset($this->actionData['subjects'][$index]);
            $this->actionData['subjects'] = array_values($this->actionData['subjects']);
        }
    }

    // ── Teacher Form ──

    /**
     * Show the inline teacher form.
     */
    public function showTeacherFormFn()
    {
        $this->awaitingConfirm = false;
        $this->actionData['teachers'] = $this->actionData['teachers'] ?? [];
        $this->actionData['teacherLinks'] = $this->actionData['teacherLinks'] ?? [];
        $this->showTeacherForm = true;
        $this->teacherFormName = '';
        $this->teacherFormEmail = '';
        $this->teacherFormSubjects = '';
        $this->teacherFormClasses = '';
        $this->teacherFormPhone = '';
        $this->substep = 6;
        $this->botSay("Let's add teachers. Use the form below to add each teacher.");
    }

    /**
     * Save a teacher from the inline form.
     */
    public function saveTeacher()
    {
        $name = trim($this->teacherFormName);
        $email = trim($this->teacherFormEmail);
        if ($name === '') return;
        if ($email === '') {
            $this->botSay("Email is required for **{$name}**. Enter the teacher's email address.");
            return;
        }

        $this->actionData['teachers'][] = $name;
        $this->actionData['teacherEmails'][$name] = $email;
        if ($this->teacherFormPhone) {
            $this->actionData['teacherPhones'][$name] = trim($this->teacherFormPhone);
        }
        if ($this->teacherFormSubjects) {
            $this->actionData['teacherSubjects'][$name] = array_map('trim', explode(',', $this->teacherFormSubjects));
        }
        if ($this->teacherFormClasses) {
            $this->actionData['teacherClasses'][$name] = array_map('trim', explode(',', $this->teacherFormClasses));
        }

        $this->teacherFormName = '';
        $this->teacherFormEmail = '';
        $this->teacherFormSubjects = '';
        $this->teacherFormClasses = '';
        $this->teacherFormPhone = '';

        $count = count($this->actionData['teachers']);
        $this->botSay("Added **{$name}** ({$count} so far). Add another or click **Done**.");
    }

    /**
     * Remove a teacher from the list.
     */
    public function removeTeacher(int $index): void
    {
        if (isset($this->actionData['teachers'][$index])) {
            $name = $this->actionData['teachers'][$index];
            unset($this->actionData['teachers'][$index]);
            unset($this->actionData['teacherEmails'][$name]);
            unset($this->actionData['teacherPhones'][$name]);
            unset($this->actionData['teacherSubjects'][$name]);
            unset($this->actionData['teacherClasses'][$name]);
            $this->actionData['teachers'] = array_values($this->actionData['teachers']);
        }
    }

    /**
     * Finish teacher entry.
     */
    public function doneTeachers()
    {
        if (empty($this->actionData['teachers'])) {
            $this->botSay("Add at least one teacher first.");
            return;
        }
        $this->showTeacherForm = false;
        $this->teacherList = $this->actionData['teachers'];
        $this->teacherPhones = $this->actionData['teacherPhones'] ?? [];

        $preview = implode(', ', array_slice($this->teacherList, 0, 3));
        $this->botSay("**" . count($this->teacherList) . "** teacher(s) added: {$preview}" . (count($this->teacherList) > 3 ? '...' : '') . ". They'll get auto-generated emails and default passwords.");
        $this->substep = 0;
        $this->advance();
    }

    public function commit()
    {
        $this->input = 'commit';
        $this->send();
    }
    public function editBeforeCommit()
    {
        $this->reviewData = [];
        $this->botSay("No problem! Tell me what needs to change and we'll go back to fix it. | Type the step name: plan, school, admin, co-admin, classes, subjects, teachers, students, terms, fees, exams");
        $this->substep = 0;
    }
    public function resumeDraft()
    {
        $user = auth()->user();
        if (!$user) return;
        $draft = \App\Models\OnboardingSession::where('user_id', $user->id)
            ->where('status', 'draft')
            ->latest()
            ->first();
        if ($draft) {
            $this->restoreDraft($draft);
            $this->substep = 0;
            $this->callStepHandler('');
        }
    }

    /**
     * Get the user's draft onboarding sessions for display.
     */
    public function getDrafts(): array
    {
        $user = auth()->user();
        if (!$user) return [];
        return \App\Models\OnboardingSession::where('user_id', $user->id)
            ->where('status', 'draft')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($draft) {
                $data = is_string($draft->data) ? json_decode($draft->data, true) : ($draft->data ?? []);
                return [
                    'id'          => $draft->id,
                    'school_name' => $data['schoolName'] ?? 'Unnamed School',
                    'step'        => $draft->step,
                    'updated_at'  => $draft->updated_at,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Jump to a specific step in the onboarding flow (clickable progress bar).
     */
    public function jumpToStep(int $step): void
    {
        if ($step < 0 || $step >= count($this->steps) || $step > $this->step) {
            return; // can only go back, not skip forward
        }
        $this->step = $step;
        $this->substep = 0;
        $this->saveDraft();
        $this->callStepHandler('');
    }

    public function resetOnboarding(bool $startNew = false)
    {
        // Start a new school creation from any mode
        if ($startNew) {
            $this->mode = 'create';
            $this->deleteDraft();
            $this->step = 0;
            $this->substep = 0;
            $this->messages = [];
            $this->reviewData = [];
            $this->schoolName = '';
            $this->schoolType = 'primary';
            $this->schoolLevel = '';
            $this->schoolGender = '';
            $this->adminName = '';
            $this->adminEmail = '';
            $this->schoolPhone = '';
            $this->adminPassword = '';
            $this->coAdminName = '';
            $this->coAdminEmail = '';
            $this->coAdminUserId = null;
            $this->academicYearLabel = '';
            $this->selectedPlanId = null;
            $this->standards = [];
            $this->subjects = [];
            $this->teacherList = [];
            $this->teacherLinks = [];
            $this->teacherPhones = [];
            $this->schoolEmail = '';
            $this->studentList = [];
            $this->hasNursery = null;
            $this->terms = [];
            $this->fees = [];
            $this->exams = [];
            $this->whatsappPhone = '';
            $this->whatsappSentOtp = '';
            $this->whatsappVerified = false;
            $this->schoolId = null;
            $this->scope = 'platform';
            $this->mode = 'create';
            $this->botSay("Hello! I'll help you set up a new school on KlassApp.");
            $this->botSay("First, let's choose a plan. | Select one of the plans below to get started.");
            return;
        }

        // In complete mode, transition to assistant mode (admin is done)
        $this->mode = 'assistant';
        $this->step = 99;
        $this->substep = 0;
        $this->messages = [];
        $this->reviewData = [];
        $this->botSay($this->getAssistantGreeting());
    }

    public function updatedAttachment()
    {
        $this->validate(['attachment' => 'file|mimes:csv,txt,xlsx,xls,docx|max:5120']);

        $ext = strtolower($this->attachment->getClientOriginalExtension());
        $parsable = in_array($ext, ['csv', 'txt', 'xlsx', 'xls', 'pdf', 'docx']);

        if ($parsable) {
            $stepName = $this->steps[$this->step] ?? '';
            $names = $this->extractNamesFromFile($this->attachment->getRealPath(), $ext);

            if ($stepName === 'teacher_links') {
                $links = $this->extractTeacherLinksFromFile($this->attachment->getRealPath(), $ext);
                if (count($links) > 0) {
                    $this->teacherLinks = $links;
                    $this->userSay("📎 Uploaded " . count($links) . " teacher link(s) from file");
                    $this->botSay("Parsed **" . count($links) . "** teacher link(s) from your file. Continuing...");
                    // Auto-advance — file upload is explicit confirmation
                    $this->substep = 2;
                    $this->input = 'yes';
                    $this->send();
                    return;
                } else {
                    $this->botSay("I couldn't find any valid teacher links. Make sure your file has columns: Teacher, Subject, Class.");
                }
            } elseif (in_array($stepName, ['teachers', 'students']) && count($names) > 0) {
                if ($stepName === 'teachers') $this->teacherList = $names;
                else $this->studentList = $names;
                $this->userSay("📎 Uploaded " . count($names) . " names from file");
                $this->botSay("Parsed **" . count($names) . "** names from your file. Continue?");
            } elseif ($stepName === 'standards' && count($names) > 0) {
                $this->standards = array_map(fn($n) => ['name' => $n], $names);
                $this->userSay("📎 Uploaded " . count($names) . " class(es) from file");
                $this->botSay("Parsed **" . count($names) . "** class(es) from your file. Continue?");
            } elseif ($stepName === 'subjects' && count($names) > 0) {
                // For subjects, if file has 2+ columns, assume [class, subject] format
                $headers = [];
                $dataRows = [];
                try {
                    if (in_array($ext, ['xlsx', 'xls'])) {
                        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($this->attachment->getRealPath());
                        $reader->setReadDataOnly(true);
                        $spreadsheet = $reader->load($this->attachment->getRealPath());
                        $allRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                        $headers = array_map('trim', $allRows[0] ?? []);
                        $dataRows = array_slice($allRows, 1);
                    } elseif ($ext === 'csv' || $ext === 'txt') {
                        $handle = fopen($this->attachment->getRealPath(), 'r');
                        $first = true;
                        while (($line = fgetcsv($handle)) !== false) {
                            if ($first) { $headers = array_map('trim', $line); $first = false; continue; }
                            $dataRows[] = $line;
                        }
                        fclose($handle);
                    }
                } catch (\Exception $e) {}

                if (!empty($headers) && count($headers) >= 2) {
                    $subjectsByClass = [];
                    foreach ($dataRows as $row) {
                        $className = trim($row[0] ?? '');
                        $subjectName = trim($row[1] ?? '');
                        if ($className && $subjectName) {
                            $subjectsByClass[$className][] = $subjectName;
                        }
                    }
                    if (!empty($subjectsByClass)) {
                        $this->subjects = $subjectsByClass;
                        $total = collect($subjectsByClass)->flatten()->count();
                        $this->userSay("📎 Uploaded subjects from file");
                        $this->botSay("Parsed **{$total}** subject(s) across **" . count($subjectsByClass) . "** class(es). Continue?");
                    } else {
                        $this->botSay("Couldn't parse subjects. Make sure your file has columns: Class, Subject.");
                    }
                } else {
                    // Single column — apply to all classes
                    if (!empty($this->standards)) {
                        $firstClass = $this->standards[0]['name'] ?? 'default';
                        $this->subjects = [$firstClass => $names];
                        $this->botSay("Parsed **" . count($names) . "** subject(s). Continue?");
                    } else {
                        $this->botSay("Please set up classes first, then upload subjects.");
                    }
                }
            } elseif (count($names) > 0) {
                $this->botSay("File received with " . count($names) . " names. We'll use this when we get to the teachers/students step.");
            } else {
                $this->botSay("I couldn't find any names in that file. Make sure it contains a list of names (one per line).");
            }
        } else {
            $fileName = $this->attachment->getClientOriginalName();
            $size = round($this->attachment->getSize() / 1024, 1);
            $this->userSay("📎 Uploaded: {$fileName} ({$size} KB)");
            $this->botSay("Received **{$fileName}**. I can extract names from spreadsheets (CSV, XLSX, XLS), documents (PDF, DOCX), and text files.");
        }

        $this->attachment = null;
    }

    /**
     * Extract names from a tabular file (CSV/TXT/XLSX/XLS).
     * Returns an array of full names (first + last where available).
     */
    private function extractNamesFromFile(string $path, string $ext): array
    {
        $headers = [];
        $dataRows = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $allRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                if (empty($allRows)) return [];
                $headers = array_map('trim', $allRows[0]);
                $dataRows = array_slice($allRows, 1);
            } catch (\Exception $e) {
                \Log::warning('XLSX parse failed: ' . $e->getMessage());
                return [];
            }
        } elseif ($ext === 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                $text = $pdf->getText();
                return $this->parseNameList($text);
            } catch (\Exception $e) {
                \Log::warning('PDF parse failed: ' . $e->getMessage());
                return [];
            }
        } elseif ($ext === 'docx') {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        } elseif (method_exists($element, 'getElements')) {
                            foreach ($element->getElements() as $child) {
                                if (method_exists($child, 'getText')) {
                                    $text .= $child->getText() . "\n";
                                }
                            }
                        }
                    }
                }
                return $this->parseNameList($text);
            } catch (\Exception $e) {
                \Log::warning('DOCX parse failed: ' . $e->getMessage());
                return [];
            }
        } else {
            $handle = fopen($path, 'r');
            if (!$handle) return [];
            $headerLine = fgetcsv($handle);
            if ($headerLine === false) { fclose($handle); return []; }
            $headers = array_map('trim', $headerLine);
            while (($row = fgetcsv($handle)) !== false) {
                $dataRows[] = $row;
            }
            fclose($handle);
        }

        // Spreadsheet/CSV path — try column-based extraction
        if (empty($dataRows)) return [];

        // Find the name column index
        $nameIdx = null;
        foreach (['firstname', 'name', 'First Name', 'Name', 'student_name', 'teacher_name', 'full_name'] as $col) {
            $idx = array_search($col, $headers);
            if ($idx !== false) { $nameIdx = $idx; break; }
        }

        // Find lastname column index
        $lastIdx = array_search('lastname', array_map('strtolower', $headers));
        if ($lastIdx === false) {
            $lastIdx = array_search('last_name', array_map('strtolower', $headers));
        }

        $names = [];
        foreach ($dataRows as $row) {
            if (!is_array($row) || count($row) === 0) continue;
            $row = array_map('trim', $row);

            // Determine name value
            if ($nameIdx !== null && !empty($row[$nameIdx] ?? '')) {
                $name = $row[$nameIdx];
            } elseif (count($row) > 0 && !empty($row[0])) {
                $name = $row[0]; // fallback to first column
            } else {
                continue;
            }

            // Append lastname if available
            if ($lastIdx !== false && !empty($row[$lastIdx] ?? '')) {
                $name .= ' ' . $row[$lastIdx];
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * Extract teacher-subject-class links from a spreadsheet (CSV/XLSX).
     * Expects columns: Teacher, Subject, Class, Phone (optional).
     */
    private function extractTeacherLinksFromFile(string $path, string $ext): array
    {
        $rows = [];

        try {
            if (in_array($ext, ['xlsx', 'xls'])) {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } elseif ($ext === 'csv' || $ext === 'txt') {
                $handle = fopen($path, 'r');
                while (($line = fgetcsv($handle)) !== false) {
                    $rows[] = $line;
                }
                fclose($handle);
            } else {
                return [];
            }
        } catch (\Exception $e) {
            \Log::warning('Teacher links file parse error: ' . $e->getMessage());
            return [];
        }

        if (empty($rows)) return [];

        // Find column indices from header row
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $colMap = ['teacher' => null, 'subject' => null, 'class' => null, 'phone' => null];
        foreach ($header as $i => $h) {
            if (str_contains($h, 'teacher') || str_contains($h, 'name')) $colMap['teacher'] = $i;
            if (str_contains($h, 'subject')) $colMap['subject'] = $i;
            if (str_contains($h, 'class') || str_contains($h, 'grade')) $colMap['class'] = $i;
            if (str_contains($h, 'phone') || str_contains($h, 'mobile') || str_contains($h, 'tel')) $colMap['phone'] = $i;
        }

        // If no header match, assume column order: Teacher, Subject, Class, Phone
        if ($colMap['teacher'] === null && count($rows[0]) >= 3) {
            $colMap = ['teacher' => 0, 'subject' => 1, 'class' => 2, 'phone' => 3];
        }

        if ($colMap['teacher'] === null || $colMap['subject'] === null || $colMap['class'] === null) {
            return [];
        }

        $parsed = [];
        $dataRows = array_slice($rows, 1); // skip header

        foreach ($dataRows as $row) {
            $teacher = trim($row[$colMap['teacher']] ?? '');
            $subject = trim($row[$colMap['subject']] ?? '');
            $class = trim($row[$colMap['class']] ?? '');
            $phone = $colMap['phone'] !== null ? trim($row[$colMap['phone']] ?? '') : '';

            if ($teacher && $subject && $class) {
                // Validate against existing teacher list and standards
                $teacherExists = in_array($teacher, $this->teacherList);
                $classExists = collect($this->standards)->pluck('name')->contains($class);

                if ($teacherExists && $classExists) {
                    $parsed[] = [
                        'teacher' => $teacher,
                        'subject' => $subject,
                        'class'   => $class,
                        'phone'   => $phone,
                    ];
                    if ($phone) {
                        $this->teacherPhones[$teacher] = $phone;
                    }
                }
            }
        }

        return $parsed;
    }

    public function render()
    {
        return view('livewire.agent-toshi');
    }

    // ── Agent says something ──
    /**
     * Try to interpret free-form text as a per-student lookup (name search).
     * Returns true if handled, false to let the fallback run.
     */
    private function tryStudentLookup(string $text): bool
    {
        $lower = strtolower(trim($text));
        if (strlen($lower) < 2) return false;

        // Keywords that suggest a student lookup
        $lookupTriggers = ['find', 'search', 'show', 'where is', 'locate', 'lookup', 'tell me about',
            'marks for', 'marks of', 'grades for', 'grades of',
            'fees for', 'fees of', 'balance for', 'balance of',
            'attendance for', 'attendance of',
            'class for', 'class of', 'what class',
            'details for', 'details of', 'info for', 'info on',
            'student', 'learner', 'pupil',
        ];

        $isLookup = false;
        $nameHint = '';

        foreach ($lookupTriggers as $trigger) {
            if (str_starts_with($lower, $trigger)) {
                $isLookup = true;
                $nameHint = trim(substr($text, strlen($trigger)));
                break;
            }
            // Also try "name + keyword" pattern: "John Doe marks"
            if (str_ends_with($lower, $trigger) && $trigger !== $lower) {
                $isLookup = true;
                $nameHint = trim(substr($text, 0, -strlen($trigger)));
                break;
            }
        }

        // If no trigger found but text looks like a short name (2-4 words, no URL), treat as lookup
        if (!$isLookup && !str_contains($lower, 'http') && !str_contains($lower, 'sidebar')
            && preg_match('/^[a-zA-ZÀ-ÿ\'\-\s\.]{2,60}$/', $text)) {
            $isLookup = true;
            $nameHint = $text;
        }

        if (!$isLookup || trim($nameHint) === '') return false;

        $nameHint = trim($nameHint);
        // Strip trailing punctuation that might be leftovers from keyword matching
        $nameHint = rtrim($nameHint, ' ,.!?:;');

        // Search for students matching this name in the school
        $students = \App\Models\User::where('school_id', $this->schoolId)
            ->where('usergroup_id', 6)
            ->where(function ($q) use ($nameHint) {
                $q->where('name', 'LIKE', "%{$nameHint}%")
                  ->orWhere('name', 'LIKE', "%{$nameHint}%")
                  ->orWhere('email', 'LIKE', "%{$nameHint}%");
                // Also try matching first/last name parts
                $parts = preg_split('/[\s]+/', $nameHint);
                foreach ($parts as $part) {
                    if (strlen($part) >= 2) {
                        $q->orWhere('name', 'LIKE', "%{$part}%");
                    }
                }
            })
            ->take(10)
            ->get();

        if ($students->isEmpty()) {
            $this->botSay("I couldn't find a student matching \"**{$nameHint}**\" in this school. Try using their full name or KlassApp ID.");
            return true;
        }

        if ($students->count() === 1) {
            $this->showStudentDetail($students->first());
            return true;
        }

        // Multiple matches — show a list
        $lines = $students->take(8)->map(function ($u) {
            $sa = $u->studentAcademicLatest;
            $class = $sa?->standardLink?->section?->name ?? '—';
            $kid = $sa?->klassapp_student_id ?? '';
            return "  • **{$u->name}** ({$class}) — {$kid}";
        })->implode("\n");
        $more = $students->count() > 8 ? "\n  … and " . ($students->count() - 8) . " more" : '';
        $this->botSay("Found **{$students->count()}** students matching \"**{$nameHint}**\":\n{$lines}{$more}\n\nType a more specific name.");
        return true;
    }

    /**
     * Show a detailed summary card for a single student.
     */
    private function showStudentDetail(\App\Models\User $student): void
    {
        $sa = $student->studentAcademicLatest;
        $section = $sa?->standardLink?->section;
        $className = $section?->name ?? '—';
        $klassappId = $sa?->klassapp_student_id ?? '—';
        $studentId = $student->id;

        // Fee summary
        $feeTotal = \App\Models\FeesCategories::where('school_id', $this->schoolId)->sum('amount');
        $feePaid = \Illuminate\Support\Facades\DB::table('schoolpay_transactions')
            ->where('school_id', $this->schoolId)
            ->where('student_id', $studentId)
            ->sum('amount');
        $feeStatus = $feePaid > 0 ? 'Paid ' . number_format($feePaid, 0) . ' UGX' : 'No payments recorded';

        // Recent marks (last 5)
        $recentMarks = \App\Models\Academics\Marks::where('school_id', $this->schoolId)
            ->where('student_id', $studentId)
            ->with(['subject', 'exam'])
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $marksLines = '';
        if ($recentMarks->isNotEmpty()) {
            $marksLines = "\n📝 **Recent Marks**\n";
            foreach ($recentMarks as $m) {
                $subj = $m->subject?->name ?? 'Subject';
                $mark = $m->marks ?? '—';
                $grade = $m->grade ?? '';
                $marksLines .= "  • {$subj}: {$mark}" . ($grade ? " ({$grade})" : '') . "\n";
            }
        }

        // Attendance count this term
        $attendanceCount = \App\Models\Attendance::where('school_id', $this->schoolId)
            ->where('user_id', $studentId)
            ->count();

        $this->botSay("👤 **{$student->name}**\n"
            . "🆔 KlassApp ID: {$klassappId}\n"
            . "📚 Class: {$className}\n"
            . "💰 Fees: {$feeStatus}\n"
            . "📅 Attendance records: {$attendanceCount}"
            . $marksLines
            . "\n\nGo to *Students* in the sidebar for full management.");
    }

    private function botSay(string $message)
    {
        $this->messages[] = ['role' => 'bot', 'text' => $message];
        $this->persistState();
    }

    // ── User says something ──
    private function userSay(string $message)
    {
        $this->messages[] = ['role' => 'user', 'text' => $message];
        $this->persistState();
    }

    /**
     * Persist Toshi state to session so it survives page refreshes.
     */
    private function persistState(): void
    {
        session(['toshi_state' => [
            'messages'          => $this->messages,
            'step'              => $this->step,
            'substep'           => $this->substep,
            'mode'              => $this->mode,
            'scope'             => $this->scope,
            'schoolId'          => $this->schoolId,
            'schoolName'        => $this->schoolName,
            'schoolType'        => $this->schoolType,
            'schoolLevel'       => $this->schoolLevel,
            'schoolGender'      => $this->schoolGender,
            'schoolEmail'       => $this->schoolEmail,
            'schoolPhone'       => $this->schoolPhone,
            'adminName'         => $this->adminName,
            'adminEmail'        => $this->adminEmail,
            'adminPassword'     => $this->adminPassword,
            'coAdminName'       => $this->coAdminName,
            'coAdminEmail'      => $this->coAdminEmail,
            'coAdminUserId'     => $this->coAdminUserId,
            'academicYearLabel' => $this->academicYearLabel,
            'selectedPlanId'    => $this->selectedPlanId,
            'standards'         => $this->standards,
            'subjects'          => $this->subjects,
            'teacherList'       => $this->teacherList,
            'teacherLinks'      => $this->teacherLinks,
            'teacherPhones'     => $this->teacherPhones,
            'studentList'       => $this->studentList,
            'terms'             => $this->terms,
            'fees'              => $this->fees,
            'exams'             => $this->exams,
            'whatsappPhone'     => $this->whatsappPhone,
            'whatsappVerified'  => $this->whatsappVerified,
            'reviewData'        => $this->reviewData,
            'draftSessionId'    => $this->draftSessionId,
            'actionStep'        => $this->actionStep,
            'actionSubstep'     => $this->actionSubstep,
            'actionData'        => $this->actionData,
        ]]);
    }

    /**
     * Restore Toshi state from session after a page refresh.
     * Returns true if state was restored, false if no state found.
     */
    private function restoreState(): bool
    {
        $state = session('toshi_state');
        if (empty($state)) return false;

        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        // Always start closed on refresh — user clicks pill to open
        $this->visible = false;
        $this->maximized = false;
        return true;
    }

    // ── Mark step as done ──
    private function advance(?int $to = null)
    {
        $this->step = $to ?? $this->step + 1;
        $this->substep = 0;
        $this->saveDraft();
        // Immediately trigger the step handler to show its prompt
        $this->callStepHandler('');
    }

    /**
     * Call the current step's handler to initialize its prompt.
     * Passes an empty string so substep=0 fires the introductory message.
     */
    private function callStepHandler(string $text): void
    {
        $stepName = $this->steps[$this->step] ?? null;
        if (!$stepName) return;

        $handler = match ($stepName) {
            'plan_selection'  => 'handlePlanSelection',
            'school_info'     => 'handleSchoolInfo',
            'admin_account'   => 'handleAdminAccount',
            'co_admin_invite' => null, // buttons only, no text handler on entry
            'academic_year'   => 'handleAcademicYear',
            'standards'       => 'handleStandards',
            'subjects'        => 'handleSubjects',
            'teachers'        => 'handleTeachers',
            'teacher_links'   => 'handleTeacherLinks',
            'students'        => 'handleStudents',
            'terms'           => 'handleTerms',
            'fees'            => 'handleFees',
            'exams'           => 'handleExams',
            'whatsapp_verify' => 'handleWhatsAppVerify',
            'review'          => 'handleReview',
            default           => null,
        };

        if ($handler && method_exists($this, $handler)) {
            $this->$handler($text);
        }
    }

    /**
     * Returns true if the current (or given) step is mandatory and cannot be skipped.
     */
    public function isStepMandatory(?string $stepName = null): bool
    {
        return in_array($stepName ?? $this->steps[$this->step] ?? '', $this->mandatorySteps);
    }

    // ════════════════════════════════════════════════
    //  Validation Helpers
    // ════════════════════════════════════════════════

    /**
     * Validate a required text field is non-empty with optional min length.
     * Returns the trimmed string on success, null on failure (botSay already called).
     */
    private function validateRequired(string $text, string $label, int $minLen = 1): ?string
    {
        $text = trim($text);
        if ($text === '') {
            $this->botSay("**{$label}** cannot be empty. Please enter a value.");
            return null;
        }
        if (mb_strlen($text) < $minLen) {
            $this->botSay("**{$label}** must be at least {$minLen} characters. You entered " . mb_strlen($text) . ".");
            return null;
        }
        return $text;
    }

    /**
     * Normalize a Ugandan phone number to +256XXXXXXXXX format.
     * Accepts: +256701234567, 256701234567, 0701234567, +256 701 234 567
     * Returns normalized number or null on failure.
     */
    private function normalizeUgandaPhone(string $phone): ?string
    {
        $phone = trim(preg_replace('/[\s\-\(\)]/', '', $phone));

        // Already in +256 format
        if (preg_match('/^\+256[0-9]{9}$/', $phone)) {
            return $phone;
        }

        // 256XXXXXXXXX (no +)
        if (preg_match('/^256[0-9]{9}$/', $phone)) {
            return '+' . $phone;
        }

        // 07XXXXXXXX or 07XXX XXX XXX
        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return '+256' . substr($phone, 1);
        }

        // 7XXXXXXXX (no prefix)
        if (preg_match('/^7[0-9]{8}$/', $phone)) {
            return '+256' . $phone;
        }

        $this->botSay("That doesn't look like a valid Ugandan phone number. Please use format +256701234567.");
        return null;
    }

    /**
     * Validate an email address.
     * Returns the trimmed email on success, null on failure.
     */
    private function validateEmail(string $email): ?string
    {
        $email = trim($email);
        if ($email === '') {
            $this->botSay("Email address cannot be empty.");
            return null;
        }
        // Remove common typos
        $email = str_replace([' ', '，'], ['', ','], $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->botSay("**{$email}** doesn't look like a valid email. Please try again (e.g., admin@school.ug).");
            return null;
        }
        return $email;
    }

    /**
     * Check if a school name is already taken.
     */
    private function isDuplicateSchool(string $name): bool
    {
        return \App\Models\School::where('name', trim($name))->exists();
    }

    // ── Assistant mode — keyword router first (zero cost), then LLM, then fallback ──
    private function handleAssistantQuery(string $text): void
    {
        $lower = strtolower($text);

        if ($this->tryKeywordRoute($lower, $text)) {
            return;
        }

        $user = auth()->user();
        $service = app(ToshiAssistantService::class);

        if ($service->isAvailable($user, $this->schoolId)) {
            $history = array_slice($this->messages, -20);
            $response = $service->ask($user, $this->schoolId, $text, $history);
            if ($response !== null) {
                $this->botSay($response);
                return;
            }
            // LLM call failed (API error) — fall through to keyword fallback
        } else {
            $remaining = $service->getRemainingBudget($user, $this->schoolId);
            if ($remaining <= 0) {
                $resetTime = $service->getWindowResetTime($user, $this->schoolId)->format('g:i A');
                $upgrade = $service->getUpgradeSuggestion($user, $this->schoolId);
                $msg = "You've reached your query limit for this period. I can still answer common questions about reports, students, attendance, fees, marks, and WhatsApp. More queries available at {$resetTime}.";
                if ($upgrade) {
                    $msg .= " " . $upgrade;
                }
                $this->botSay($msg);
                return;
            }
        }

        $this->fallbackMessage();
    }

    /**
     * Route known queries via keyword matching (zero API cost).
     * Returns true if the query was handled.
     */
    private function tryKeywordRoute(string $lower, string $original): bool
    {
        if (in_array($lower, ['hi', 'hello', 'hey', 'help', 'what can you do'])) {
            $this->botSay("Hi! I'm Toshi. I can help with reports, stats, students, fees, and attendance. "
                . "Try asking about your school or use the admin sidebar for full management tools.");
            return true;
        }

        if (in_array($lower, ['reports', 'dashboard', 'stats', 'summary'])) {
            if (!$this->schoolId) {
                $this->botSay("📊 Platform overview is available on the main dashboard.");
                return true;
            }
            $studentCount = \App\Models\StudentAcademic::where('school_id', $this->schoolId)->count();
            $teacherCount = \App\Models\User::where('school_id', $this->schoolId)->where('usergroup_id', 5)->count();
            $classCount = \App\Models\StandardLink::where('school_id', $this->schoolId)->count();
            $feeTotal = \App\Models\FeesCategories::where('school_id', $this->schoolId)->sum('amount');
            $this->botSay("📊 **School Summary**\n"
                . "• 👥 {$studentCount} students across {$classCount} classes\n"
                . "• 👨‍🏫 {$teacherCount} teachers\n"
                . "• 💰 Total fees: " . number_format($feeTotal, 0) . " UGX\n\n"
                . "See the admin dashboard for detailed charts.");
            return true;
        }

        if (in_array($lower, ['students', 'student list', 'learners'])) {
            if (!$this->schoolId) {
                $this->botSay("👥 Go to *Students* in the sidebar to view, add, or manage students.");
                return true;
            }
            $total = \App\Models\StudentAcademic::where('school_id', $this->schoolId)->count();
            $classCounts = \App\Models\StudentAcademic::where('school_id', $this->schoolId)
                ->selectRaw('standardLink_id, COUNT(*) as count')
                ->groupBy('standardLink_id')
                ->orderByDesc('count')
                ->take(5)
                ->get();
            $lines = '';
            foreach ($classCounts as $sa) {
                $sl = \App\Models\StandardLink::with('section')->find($sa->standardLink_id);
                $name = $sl?->section?->name ?? 'Class #' . $sa->standardLink_id;
                $lines .= "  • {$name}: {$sa->count} students\n";
            }
            $this->botSay("👥 **{$total} total students**\n{$lines}Go to *Students* in the sidebar for details.");
            return true;
        }

        if (in_array($lower, ['attendance'])) {
            if (!$this->schoolId) {
                $this->botSay("📅 Attendance tracking is under *Attendance* in the sidebar.");
                return true;
            }
            $today = now()->toDateString();
            $presentToday = \App\Models\Attendance::where('school_id', $this->schoolId)
                ->whereDate('created_at', $today)->count();
            $this->botSay("📅 **Attendance**\n"
                . "• Today's records: {$presentToday} entries\n"
                . "• Full reports are under *Attendance* in the sidebar.");
            return true;
        }

        if (in_array($lower, ['fees', 'fee balance', 'payments', 'money'])) {
            if (!$this->schoolId) {
                $this->botSay("💰 Fee management is under *Fees* in the sidebar.");
                return true;
            }
            $totalFees = \App\Models\FeesCategories::where('school_id', $this->schoolId)->sum('amount');
            $totalPaid = \Illuminate\Support\Facades\DB::table('schoolpay_transactions')
                ->where('school_id', $this->schoolId)->sum('amount');
            $this->botSay("💰 **Fee Summary**\n"
                . "• Total fee categories: " . number_format($totalFees, 0) . " UGX\n"
                . "• Total collected: " . number_format($totalPaid, 0) . " UGX\n\n"
                . "Parents can check their balance via WhatsApp.");
            return true;
        }

        if (in_array($lower, ['marks', 'grades', 'exams', 'results'])) {
            if (!$this->schoolId) {
                $this->botSay("📝 Exam management is under *Exams* in the sidebar.");
                return true;
            }
            $examCount = \App\Models\Exam::where('school_id', $this->schoolId)->count();
            $this->botSay("📝 **Exams & Grades**\n"
                . "• {$examCount} exams configured\n"
                . "• Teachers enter marks under *Exams* in the sidebar\n"
                . "• Parents receive results via WhatsApp.");
            return true;
        }

        if (in_array($lower, ['whatsapp', 'wa', 'parent', 'parents'])) {
            if (!$this->schoolId) {
                $this->botSay("📱 WhatsApp is live! See *WhatsApp Dashboard* in the sidebar.");
                return true;
            }
            $linked = \App\Models\WhatsAppUser::where('school_id', $this->schoolId)->count();
            $this->botSay("📱 **WhatsApp**\n"
                . "• {$linked} parents linked via WhatsApp\n"
                . "• Parents can text: fees, grades, attendance\n"
                . "• See *WhatsApp Dashboard* in the sidebar for full management.");
            return true;
        }

        // ── School Admin Actions ──

        // One-shot: generate school report
        if (in_array($lower, ['school report', 'report', 'summary report', 'school summary'])) {
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard to get a report.");
                return true;
            }
            $result = ToshiActionService::generateReport(auth()->user());
            $this->botSay($result['message']);
            return true;
        }

        // One-shot: list classes
        if (in_array($lower, ['list classes', 'classes', 'my classes', 'class list'])) {
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $result = ToshiActionService::listClasses(auth()->user());
            $this->botSay($result['message']);
            return true;
        }

        // One-shot: list teachers
        if (in_array($lower, ['list teachers', 'teachers', 'teacher list', 'staff'])) {
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $result = ToshiActionService::listTeachers(auth()->user());
            $this->botSay($result['message']);
            return true;
        }

        // One-shot: list sections / streams
        if (in_array($lower, ['list sections', 'sections', 'streams', 'list streams', 'class streams'])) {
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $result = ToshiActionService::listSections(auth()->user());
            $this->botSay($result['message']);
            return true;
        }

        // One-shot: mark attendance (text: "mark John present" or "mark 123 absent 2024-01-15")
        if (preg_match('/^(mark|record)\s+(.+?)\s+(present|absent|late|half-day)(?:\s+(.+))?$/i', $text, $m)) {
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $result = ToshiActionService::recordAttendance(auth()->user(), [
                'student' => $m[2], 'status' => $m[3], 'date' => trim($m[4] ?? now()->toDateString()),
            ]);
            $this->botSay($result['message']);
            return true;
        }

        // One-shot: create term ("create term Term 1 2024-01-15 2024-04-15")
        if (preg_match('/^(?:create|add)\s+term\s+(.+?)\s+(\d{4}[\-\/]\d{1,2}[\-\/]\d{1,2})\s+(\d{4}[\-\/]\d{1,2}[\-\/]\d{1,2})$/i', $text, $m)) {
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $result = ToshiActionService::createTerm(auth()->user(), [
                'name' => $m[1], 'start_date' => $m[2], 'end_date' => $m[3],
            ]);
            $this->botSay($result['message']);
            return true;
        }

        // One-shot: add co-admin ("add co-admin Name email@example.com")
        if (preg_match('/^(?:add|create)\s+co-admin\s+(.+?)\s+([\w\.\-]+@[\w\.\-]+\.\w+)$/i', $text, $m)) {
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $result = ToshiActionService::addCoAdmin(auth()->user(), [
                'name' => $m[1], 'email' => $m[2],
            ]);
            $this->botSay($result['message']);
            return true;
        }

        // Multi-step: add student
        if (preg_match('/^(?:add|create|register)\s+(?:a\s+)?student(?:\s+(.+))?$/i', $text, $m)) {
            if (!$this->can('add_student')) { $this->botSay('You do not have permission to add students.'); return true; }
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $this->actionStep = 'add_student';
            $this->actionSubstep = 0;
            $this->actionData = [];
            $this->botSay("Let's add a student. What is the student's full name?");
            return true;
        }

        // Multi-step: add teacher
        if (preg_match('/^(?:add|create)\s+(?:a\s+)?teacher(?:\s+(.+))?$/i', $text, $m)) {
            if (!$this->can('add_teacher')) { $this->botSay('You do not have permission to add teachers.'); return true; }
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $this->actionStep = 'add_teacher';
            $this->actionSubstep = 0;
            $this->actionData = [];
            $this->botSay("Let's add a teacher. What is the teacher's full name?");
            return true;
        }

        // Multi-step: record fee payment
        if (preg_match('/^(?:record|add|create)\s+(?:a\s+)?(?:fee\s+)?payment/i', $text)) {
            if (!$this->can('record_payment')) { $this->botSay('You do not have permission to record payments.'); return true; }
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $this->actionStep = 'record_payment';
            $this->actionSubstep = 0;
            $this->actionData = [];
            $this->botSay("Let's record a fee payment. What is the student's name or ID?");
            return true;
        }

        // Multi-step: assign teacher to class
        if (preg_match('/^(?:assign|link)\s+teacher/i', $text)) {
            if (!$this->can('assign_teacher')) { $this->botSay('You do not have permission to assign teachers.'); return true; }
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $this->actionStep = 'assign_teacher';
            $this->actionSubstep = 0;
            $this->actionData = [];
            $this->botSay("Let's assign a teacher. What is the teacher's email address?");
            return true;
        }

        // Multi-step: create fee category
        if (preg_match('/^(?:create|add)\s+(?:a\s+)?fee\s+(?:category\s+)?(?:\s+(.+))?$/i', $text, $m)) {
            if (!$this->can('create_fee')) { $this->botSay('You do not have permission to manage fees.'); return true; }
            if (!$this->schoolId) {
                $this->botSay("Open Toshi from your school dashboard.");
                return true;
            }
            $this->actionStep = 'create_fee';
            $this->actionSubstep = 0;
            $this->actionData = [];
            $this->botSay("Let's create a fee. What is the fee name (e.g. 'Tuition')?");
            return true;
        }

        // Re-launch onboarding (super admin only)
        if (preg_match('/\b(?:create|add|start|new|setup|onboard)\b.*\b(?:school|onboarding)\b/i', $text)
            || in_array($lower, ['setup', 'onboard', 'add school', 'new school', 'create school'])) {
            if (auth()->user()?->usergroup_id === 1 || auth()->user()?->usergroup_id === 2) {
                $this->resetOnboarding(startNew: true);
                return true;
            }
            $this->botSay("Only admins can onboard new schools. Ask your system administrator.");
            return true;
        }

        // Resume draft onboarding
        if (in_array($lower, ['resume', 'continue', 'continue setup', 'go back', 'resume school'])) {
            $user = auth()->user();
            $draft = \App\Models\OnboardingSession::where('user_id', $user->id)
                ->where('status', 'draft')
                ->latest()
                ->first();
            if ($draft) {
                $this->resumeDraft();
                return true;
            }
            $this->botSay("I couldn't find an unfinished school setup. Say **'create school'** to start a new one.");
            return true;
        }

        if ($this->schoolId && $this->tryStudentLookup($original)) {
            return true;
        }

        return false;
    }

    // ── Action Flow Handler (multi-step assistant actions) ──

    /**
     * Route input to the active multi-step action handler.
     */
    private function handleActionFlow(string $text): void
    {
        if (!$this->can($this->actionStep)) {
            $this->actionStep = null;
            $this->botSay("You don't have permission to do that. Let me know if you need something else.");
            return;
        }

        $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('_', ' ', $this->actionStep)));
        if (method_exists($this, $methodName)) {
            $this->$methodName($text);
        } else {
            $this->actionStep = null;
            $this->botSay("Action cancelled. How else can I help you?");
        }
    }

    /**
     * Cancel the current action flow.
     */
    private function cancelAction(): void
    {
        $this->actionStep = null;
        $this->actionSubstep = 0;
        $this->actionData = [];
        $this->botSay('Cancelled. What would you like to do?');
    }

    // ── Action: Add Student ──

    private function actionAddStudent(string $text): void
    {
        $lower = strtolower(trim($text));
        if (in_array($lower, ['cancel', 'stop', 'never mind', 'forget it'])) {
            $this->cancelAction(); return;
        }

        if ($this->actionSubstep === 0) {
            // Collect name
            if (strlen(trim($text)) < 3) {
                $this->botSay('Please enter a valid name (at least 3 characters).');
                return;
            }
            $this->actionData['name'] = trim($text);
            $this->actionSubstep = 1;
            $this->botSay("Name: **{$this->actionData['name']}**. | What class should they join? (type the class name, or **skip** to assign later)");
            return;
        }

        if ($this->actionSubstep === 1) {
            if (!in_array($lower, ['skip', 'none', '', 'later'])) {
                $this->actionData['class_name'] = trim($text);
                $this->botSay("Class: **{$this->actionData['class_name']}**. | Any section? (type section name, or **skip**)");
                $this->actionSubstep = 2;
                return;
            }
            // Skip class → go straight to confirm
            $this->actionSubstep = 3;
            $this->confirmAddStudent();
            return;
        }

        if ($this->actionSubstep === 2) {
            if (!in_array($lower, ['skip', 'none', '', 'later'])) {
                $this->actionData['section_name'] = trim($text);
            }
            $this->actionSubstep = 3;
            $this->confirmAddStudent();
            return;
        }

        if ($this->actionSubstep === 3) {
            if (in_array($lower, ['yes', 'y', 'ok', 'confirm', 'done'])) {
                $result = ToshiActionService::addStudent(auth()->user(), $this->actionData);
                $this->botSay($result['message']);
                if ($result['success']) {
                    $this->actionData = [];
                    $this->actionData['last_created_email'] = $result['email'] ?? '';
                }
                $this->botSay("Add another student? Say **add student** or ask me something else.");
                $this->actionStep = null;
                $this->actionSubstep = 0;
            } elseif (in_array($lower, ['no', 'n', 'edit', 'change'])) {
                $this->actionSubstep = 0;
                $this->actionData = [];
                $this->botSay("Let's start over. What is the student's full name?");
            } else {
                $this->botSay("Type **yes** to confirm or **no** to restart.");
            }
            return;
        }
    }

    private function confirmAddStudent(): void
    {
        $parts = [];
        if (!empty($this->actionData['name'])) $parts[] = "Name: **{$this->actionData['name']}**";
        if (!empty($this->actionData['class_name'])) $parts[] = "Class: **{$this->actionData['class_name']}**";
        if (!empty($this->actionData['section_name'])) $parts[] = "Section: **{$this->actionData['section_name']}**";
        $this->botSay(implode(' | ', $parts) . "\n\nType **yes** to confirm, **no** to restart, or **cancel** to stop.");
    }

    // ── Action: Add Teacher ──

    private function actionAddTeacher(string $text): void
    {
        $lower = strtolower(trim($text));
        if (in_array($lower, ['cancel', 'stop'])) { $this->cancelAction(); return; }

        if ($this->actionSubstep === 0) {
            if (strlen(trim($text)) < 3) {
                $this->botSay('Please enter a valid name (at least 3 characters).'); return;
            }
            $this->actionData['name'] = trim($text);
            $this->actionSubstep = 1;
            $this->botSay("Name: **{$this->actionData['name']}**. | What is the teacher's email address?");
            return;
        }

        if ($this->actionSubstep === 1) {
            $email = trim($text);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->botSay('Please enter a valid email address.'); return;
            }
            $this->actionData['email'] = $email;
            $this->actionSubstep = 2;
            $this->botSay("Email: **{$email}**. | What is their phone number? (type **skip** to omit)");
            return;
        }

        if ($this->actionSubstep === 2) {
            if (!in_array($lower, ['skip', 'none', '', 'later'])) {
                $this->actionData['phone'] = trim($text);
            }
            $parts = [];
            $parts[] = "Name: **{$this->actionData['name']}**";
            $parts[] = "Email: **{$this->actionData['email']}**";
            if (!empty($this->actionData['phone'])) $parts[] = "Phone: **{$this->actionData['phone']}**";
            $this->botSay(implode(' | ', $parts) . "\n\nType **yes** to confirm, **no** to restart.");
            $this->actionSubstep = 3;
            return;
        }

        if ($this->actionSubstep === 3) {
            if (in_array($lower, ['yes', 'y', 'ok', 'confirm'])) {
                $result = ToshiActionService::addTeacher(auth()->user(), $this->actionData);
                $this->botSay($result['message']);
                $this->actionStep = null; $this->actionSubstep = 0;
            } elseif (in_array($lower, ['no', 'n', 'edit', 'change'])) {
                $this->actionSubstep = 0; $this->actionData = [];
                $this->botSay("Let's start over. What is the teacher's full name?");
            } else {
                $this->botSay("Type **yes** to confirm or **no** to restart.");
            }
            return;
        }
    }

    // ── Action: Record Payment ──

    private function actionRecordPayment(string $text): void
    {
        $lower = strtolower(trim($text));
        if (in_array($lower, ['cancel', 'stop'])) { $this->cancelAction(); return; }

        if ($this->actionSubstep === 0) {
            // Find student by name
            $student = ToshiActionService::findStudentSimple(auth()->user(), trim($text));
            if (!$student) {
                $this->botSay("Student not found. Try the full name, email, or KlassApp ID.");
                return;
            }
            $this->actionData['student_id'] = $student->id;
            $this->actionData['student_name'] = $student->name;
            $this->actionSubstep = 1;
            $this->botSay("Student: **{$student->name}**. | What is the payment amount in UGX?");
            return;
        }

        if ($this->actionSubstep === 1) {
            $amount = str_replace([',', ' ', 'UGX', 'ugx', '='], '', trim($text));
            if (!is_numeric($amount) || (float)$amount <= 0) {
                $this->botSay('Please enter a valid positive amount (e.g. 150000).'); return;
            }
            $this->actionData['amount'] = (float)$amount;
            $this->actionSubstep = 2;
            $this->botSay("Amount: " . number_format($this->actionData['amount'], 0) . " UGX. | Payment method? (e.g. **cash**, **cheque**, **mobile money**, **bank transfer**, or **skip**)");
            return;
        }

        if ($this->actionSubstep === 2) {
            if (!in_array($lower, ['skip', 'none', '', 'later'])) {
                $this->actionData['payment_method'] = trim($text);
            }
            $this->actionSubstep = 3;
            $this->botSay("Payment method: **" . ($this->actionData['payment_method'] ?? 'not specified') . "**. | Any reference number? (e.g. cheque number, transaction ID, or **skip**)");
            return;
        }

        if ($this->actionSubstep === 3) {
            if (!in_array($lower, ['skip', 'none', '', 'later', 'no'])) {
                $this->actionData['reference'] = trim($text);
            }
            // Confirm and record
            $parts = [
                "Student: **{$this->actionData['student_name']}**",
                "Amount: " . number_format($this->actionData['amount'], 0) . " UGX",
            ];
            if (!empty($this->actionData['payment_method'])) $parts[] = "Method: **{$this->actionData['payment_method']}**";
            if (!empty($this->actionData['reference'])) $parts[] = "Ref: **{$this->actionData['reference']}**";

            $result = ToshiActionService::recordPayment(auth()->user(), [
                'student_id'      => $this->actionData['student_id'],
                'amount'          => $this->actionData['amount'],
                'payment_method'  => $this->actionData['payment_method'] ?? null,
                'reference'       => $this->actionData['reference'] ?? null,
            ]);

            if ($result['success']) {
                $this->botSay("✅ {$result['message']}");
            } else {
                $this->botSay("❌ {$result['message']}");
            }
            $this->actionStep = null; $this->actionSubstep = 0;
            return;
        }
    }

    // ── Action: Assign Teacher ──

    private function actionAssignTeacher(string $text): void
    {
        $lower = strtolower(trim($text));
        if (in_array($lower, ['cancel', 'stop'])) { $this->cancelAction(); return; }

        if ($this->actionSubstep === 0) {
            $this->actionData['teacher_email'] = trim($text);
            $this->actionSubstep = 1;
            $this->botSay("Teacher email: **{$this->actionData['teacher_email']}**. | What class? (e.g. **Primary 5**)");
            return;
        }

        if ($this->actionSubstep === 1) {
            $this->actionData['class_name'] = trim($text);
            $this->actionSubstep = 2;
            $this->botSay("Class: **{$this->actionData['class_name']}**. | What subject? (e.g. **Mathematics**)");
            return;
        }

        if ($this->actionSubstep === 2) {
            $this->actionData['subject_name'] = trim($text);
            $this->actionData['_create_subject'] = false;
            $result = ToshiActionService::assignTeacher(auth()->user(), $this->actionData);
            if ($result['success']) {
                $this->botSay($result['message']);
                $this->actionStep = null; $this->actionSubstep = 0;
                return;
            }
            // Subject not found — offer to create it
            if (str_starts_with($result['message'], 'Subject not found:')) {
                $this->actionSubstep = 3;
                $this->botSay("**{$this->actionData['subject_name']}** doesn't exist yet. Shall I create it now? (yes/no)");
                return;
            }
            // Other failure
            $this->botSay($result['message']);
            $this->actionStep = null; $this->actionSubstep = 0;
            return;
        }

        // substep 3 — confirm subject creation
        if ($this->actionSubstep === 3) {
            $lower = strtolower(trim($text));
            if (in_array($lower, ['yes', 'y', 'ok', 'sure', 'create', 'yeah'])) {
                $createResult = ToshiActionService::createSubject(auth()->user(), [
                    'name'       => $this->actionData['subject_name'],
                    'class_name' => $this->actionData['class_name'],
                ]);
                if ($createResult['success']) {
                    $retry = ToshiActionService::assignTeacher(auth()->user(), $this->actionData);
                    $this->botSay($retry['message']);
                } else {
                    $this->botSay($createResult['message'] . ' Try a different subject name.');
                }
            } else {
                $this->botSay("OK, cancelled. Try a subject that already exists.");
            }
            $this->actionStep = null; $this->actionSubstep = 0;
        }
    }

    // ── Action: Create Fee ──

    private function actionCreateFee(string $text): void
    {
        $lower = strtolower(trim($text));
        if (in_array($lower, ['cancel', 'stop'])) { $this->cancelAction(); return; }

        if ($this->actionSubstep === 0) {
            if (strlen(trim($text)) < 2) {
                $this->botSay('Please enter a fee name (e.g. "Tuition" or "Sports Fee").'); return;
            }
            $this->actionData['name'] = trim($text);
            $this->actionSubstep = 1;
            $this->botSay("Fee: **{$this->actionData['name']}**. | What is the amount in UGX?");
            return;
        }

        if ($this->actionSubstep === 1) {
            $amount = str_replace([',', ' ', 'UGX', 'ugx'], '', trim($text));
            if (!is_numeric($amount) || (float)$amount <= 0) {
                $this->botSay('Please enter a valid amount (e.g. 500000).'); return;
            }
            $this->actionData['amount'] = (float)$amount;
            $this->actionSubstep = 2;
            $this->botSay("Amount: " . number_format($this->actionData['amount'], 0) . " UGX. | Which term? (e.g. **Term 1** or **skip**)");
            return;
        }

        if ($this->actionSubstep === 2) {
            if (!in_array($lower, ['skip', 'none', '', 'later'])) {
                $this->actionData['term_name'] = trim($text);
            }
            $parts = ["Fee: **{$this->actionData['name']}**", "Amount: " . number_format($this->actionData['amount'], 0) . " UGX"];
            if (!empty($this->actionData['term_name'])) $parts[] = "Term: **{$this->actionData['term_name']}**";
            $this->botSay(implode(' | ', $parts) . "\n\nType **yes** to confirm or **no** to restart.");
            $this->actionSubstep = 3;
            return;
        }

        if ($this->actionSubstep === 3) {
            if (in_array($lower, ['yes', 'y', 'ok', 'confirm'])) {
                $result = ToshiActionService::createFee(auth()->user(), $this->actionData);
                $this->botSay($result['message']);
                $this->actionStep = null; $this->actionSubstep = 0;
            } elseif (in_array($lower, ['no', 'n', 'edit', 'change'])) {
                $this->actionSubstep = 0; $this->actionData = [];
                $this->botSay("Let's start over. What is the fee name?");
            } else {
                $this->botSay("Type **yes** to confirm or **no** to restart.");
            }
            return;
        }
    }

    /**
     * Final fallback when both keyword router and LLM fail or are unavailable.
     */
    private function fallbackMessage(): void
    {
        $actions = $this->capabilities['actions'] ?? [];

        // Build dynamic capability list
        $groups = [];

        $infoActions = array_intersect($actions, ['list_classes', 'list_teachers', 'generate_report', 'list_schools', 'platform_reports']);
        if ($infoActions) {
            $items = [];
            if (in_array('generate_report', $infoActions) || in_array('platform_reports', $infoActions)) $items[] = '"show report"';
            if (in_array('list_classes', $infoActions)) $items[] = '"list classes"';
            if (in_array('list_teachers', $infoActions)) $items[] = '"list teachers"';
            if (in_array('list_sections', $infoActions)) $items[] = '"list sections"';
            $groups[] = '**📊 Info** — ' . implode(', ', $items);
        }

        $addActions = array_intersect($actions, ['add_student', 'add_teacher', 'add_coadmin']);
        if ($addActions) {
            $items = [];
            if (in_array('add_student', $addActions)) $items[] = '"add student [name]"';
            if (in_array('add_teacher', $addActions)) $items[] = '"add teacher [name]"';
            if (in_array('add_coadmin', $addActions)) $items[] = '"add co-admin"';
            $groups[] = '**👥 Add** — ' . implode(', ', $items);
        }

        $recordActions = array_intersect($actions, ['record_attendance', 'create_term', 'create_fee', 'record_payment']);
        if ($recordActions) {
            $items = [];
            if (in_array('record_attendance', $recordActions)) $items[] = '"mark [student] present/absent"';
            if (in_array('create_term', $recordActions)) $items[] = '"create term"';
            if (in_array('create_fee', $recordActions)) $items[] = '"create fee"';
            if (in_array('record_payment', $recordActions)) $items[] = '"record payment"';
            $groups[] = '**📝 Record** — ' . implode(', ', $items);
        }

        $actionItems = array_intersect($actions, ['assign_teacher', 'create_subject']);
        if ($actionItems) {
            $items = [];
            if (in_array('assign_teacher', $actionItems)) $items[] = '"assign teacher"';
            if (in_array('create_subject', $actionItems)) $items[] = '"create subject"';
            $groups[] = '**⚙️ Actions** — ' . implode(', ', $items);
        }

        if (empty($groups)) {
            $this->botSay("I'm not sure about that yet. Try asking about your school, or use the sidebar for full management tools.");
            return;
        }

        $msg = "I'm not sure about that yet. Here's what I can do:\n\n" . implode("\n", $groups);
        $msg .= "\n\nTip: For multi-step actions like adding a student, just say the action name and I'll guide you through it!";

        $this->botSay($msg);
    }

    // ── Handle user input ──
    public function send()
    {
        $text = trim($this->input);
        if ($text === '') return;

        $this->userSay($text);
        $this->input = '';

        // Active action flow (multi-step, e.g. add student, enter marks)
        if ($this->actionStep) {
            $this->handleActionFlow($text);
            return;
        }

        // Assistant mode — school is set up, Toshi can answer questions
        if ($this->mode === 'assistant') {
            $this->handleAssistantQuery($text);
            return;
        }

        // ── Setup mode: auto-detect if user wants assistant instead ──
        // Skip heuristic if awaiting a yes/no confirmation
        if ($this->awaitingConfirm) {
            // Treat affirmative continuations as "yes"
            if (in_array($lower, ['can we go on', 'go on', 'continue', 'proceed', 'next', 'lets go', 'move on', 'yes continue', 'yeah continue'])) {
                $this->confirmYes();
                return;
            }
            // Fall through to step handler normally
        } else {
            // Try the keyword router first (zero-cost). If it matches, switch to assistant.
            if ($this->tryKeywordRoute(strtolower($text), $text)) {
                $this->mode = 'assistant';
                $this->step = 99;
                $this->saveDraft();
                return;
            }

            // Heuristic: detect natural language queries vs. setup answers.
            $isQuestion = (bool) preg_match('/^(what|how|why|when|where|who|which|can|could|would|will|do|does|did|is|are|has|have|show|tell|list|find|give)\b/i', $text);
            $hasQueryVerb = (bool) preg_match('/\b(show|list|tell|find|give|add|create|record|mark|assign|report|how many|what is|who is|i want|i need|can you)\b/i', $lower);
            $isMultiWord = str_word_count($text) >= 3;
            $isSetupAnswer = in_array($lower, ['yes', 'y', 'no', 'n', 'correct', 'right', 'ok', 'default', 'skip', 'later', 'cash', 'cheque', 'mobile_money', 'bank_transfer', 'can we go on', 'go on', 'continue', 'proceed', 'next', 'lets go', 'move on'])
                || preg_match('/^\+?256\d{9,12}$/', $text)
                || preg_match('/^[\w\.\-]+@[\w\.\-]+\.\w+$/', $text);

            if ($this->mode !== 'create' && ($isQuestion || ($hasQueryVerb && $isMultiWord)) && !$isSetupAnswer) {
                $this->mode = 'assistant';
                $this->step = 99;
                $this->saveDraft();
                $this->handleAssistantQuery($text);
                return;
            }
        }

        // Handle draft resume commands — full reset to clear ALL stale data
        if ($this->draftSessionId && in_array(strtolower($text), ['reset', 'restart', 'start over'])) {
            $this->resetOnboarding();
            // Override the greeting so it's contextual for a draft restart
            $this->messages = [];
            $this->botSay("Restarting from scratch. First, let's choose a plan.");
            return;
        }

        $stepName = $this->steps[$this->step];

        // Block explicit skip on mandatory steps ('no' is still allowed — it routes to custom input)
        if ($this->isStepMandatory($stepName) && in_array(strtolower($text), ['skip', 'later'])) {
            $label = str_replace('_', ' ', $stepName);
            $this->botSay("**" . ucwords($label) . "** is required to set up the school. Please complete this step.");
            return;
        }

        match ($stepName) {
            'plan_selection'  => $this->handlePlanSelection($text),
            'school_info'     => $this->handleSchoolInfo($text),
            'admin_account'   => $this->handleAdminAccount($text),
            'co_admin_invite' => $this->handleCoAdminInvite($text),
            'academic_year'   => $this->handleAcademicYear($text),
            'standards'       => $this->handleStandards($text),
            'subjects'        => $this->handleSubjects($text),
            'teachers'        => $this->handleTeachers($text),
            'teacher_links'   => $this->handleTeacherLinks($text),
            'students'        => $this->handleStudents($text),
            'terms'           => $this->handleTerms($text),
            'fees'            => $this->handleFees($text),
            'exams'           => $this->handleExams($text),
            'whatsapp_verify' => $this->handleWhatsAppVerify($text),
            'review'          => $this->handleReview($text),
            default           => $this->callStepHandler($text),
        };
    }

    // ════════════════════════════════════════════════
    //  Step 0: Plan Selection
    //  (Buttons rendered in blade — this handles text fallback)
    // ════════════════════════════════════════════════
    public function selectPlan(int $planId)
    {
        $plan = \App\Models\Plan::find($planId);
        if (!$plan) {
            $this->botSay("I didn't recognize that plan. Please select from the buttons above.");
            return;
        }
        $this->selectedPlanId = $plan->id;
        $this->userSay("Selected plan: **{$plan->name}**");
        $this->botSay("**{$plan->name}** plan selected. | Now, what's the name of your school?");
        $this->advance();
    }

    private function handlePlanSelection(string $text)
    {
        $plan = \App\Models\Plan::where('name', strtolower($text))->first();
        if ($plan) {
            $this->selectPlan($plan->id);
            return;
        }
        $this->botSay("Please select a plan using the buttons above.");
    }

    // ════════════════════════════════════════════════
    //  Step 1: School Info
    //  Uses substep for confirm/edit pattern
    // ════════════════════════════════════════════════
    private function handleSchoolInfo(string $text)
    {
        if ($this->substep === 0) {
            // Collecting school name
            $name = $this->validateRequired($text, 'School name', 3);
            if ($name === null) return;
            if ($this->isDuplicateSchool($name)) {
                $this->botSay("A school named **{$name}** already exists. Please use a different name.");
                return;
            }
            $this->schoolName = $name;
            $this->botSay("🏫 **{$this->schoolName}**");
            $this->botSay("Is the name correct? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 1; // awaiting name confirmation
            return;
        }

        if ($this->substep === 1) {
            // Confirming school name
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if (!$yes) {
                $this->botSay("No problem — type the correct school name:");
                $this->substep = 0; // go back to name entry
                return;
            }
            // Name confirmed — school type is now button-driven in blade
            $this->botSay("Great! Now select the school type from the options below.");
            $this->substep = 2; // awaiting type selection (via button)
            return;
        }

        // substep === 2 — type already selected via button, shouldn't reach here via text
        $this->botSay("Please use the buttons above to select the school type.");
    }

    // ── Button-driven school type selection ──
    public function setSchoolType(string $type, string $level = '', string $gender = '')
    {
        $this->schoolType = $type;
        $this->schoolLevel = $level;
        $this->schoolGender = $gender;

        // Pre-populate curriculum defaults so tests can assert early
        $defaults = $this->curriculumDefaults();
        $this->standards = $defaults['classes'] ?? [];
        $this->subjects = $defaults['subjects'] ?? [];

        $label = ucfirst($type);
        if ($level) $label .= ' — ' . strtoupper($level);
        if ($gender) $label .= ' — ' . ucfirst($gender);

        $this->userSay("School type: {$label}");
        $this->botSay("**{$label}** — got it!");
        $this->botSay("Now let's set up the admin account. | What is the admin's email address?");
        $this->substep = 0;
        $this->advance();
    }

    // ════════════════════════════════════════════════
    //  Step 2: Admin Account (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleAdminAccount(string $text)
    {
        // substep 0: collect admin email
        if ($this->substep === 0) {
            $email = $this->validateEmail($text);
            if ($email === null) return;
            $this->adminEmail = $email;
            $this->botSay("You entered: **{$this->adminEmail}** | Is this correct? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 1;
            return;
        }

        // substep 1: confirm email
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->botSay("Email confirmed. | What is the admin's full name?");
                $this->substep = 2;
                return;
            }
            $this->botSay("No problem. Please enter the correct admin email:");
            $this->substep = 0;
            return;
        }

        // substep 2: collect admin name
        if ($this->substep === 2) {
            $name = $this->validateRequired($text, 'Admin name', 3);
            if ($name === null) return;
            $this->adminName = $name;
            $this->botSay("You entered: **{$this->adminName}** | Is this correct? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 3;
            return;
        }

        // substep 3: confirm name
        if ($this->substep === 3) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->botSay("Name confirmed. | What is the admin's WhatsApp phone number? This will be used for school notifications. (format: +256XXXXXXXXX)");
                $this->substep = 4;
                return;
            }
            $this->botSay("No problem. Please enter the correct admin name:");
            $this->substep = 2;
            return;
        }

        // substep 4: collect admin phone
        if ($this->substep === 4) {
            $phone = $this->normalizeUgandaPhone($text);
            if ($phone === null) return;
            $this->schoolPhone = $phone;
            $this->botSay("You entered: **{$phone}** | Is this correct? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 5;
            return;
        }

        // substep 5: confirm phone
        if ($this->substep === 5) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->botSay("Phone confirmed. | Set a password for the admin account (min 6 characters), or type 'default' to use the default password.");
                $this->substep = 6;
                return;
            }
            $this->botSay("No problem. Please enter the correct phone number:");
            $this->substep = 4;
            return;
        }

        // substep 6: collect password
        if ($this->substep === 6) {
            if (strtolower(trim($text)) === 'default') {
                $this->adminPassword = 'password';
                $this->botSay("Using default password. The admin can change this later.");
            } else {
                $pw = trim($text);
                if (strlen($pw) < 6) {
                    $this->botSay("Password must be at least 6 characters. Try again, or type 'default'.");
                    return;
                }
                $this->adminPassword = $pw;
                $this->botSay("✅ Password set.");
            }
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 3: Co-admin Invite (optional — dual-mode)
    //  Create mode: enter email + name → new admin user
    //  Complete mode: pick an existing teacher to promote → usergroup_id 5→3
    // ════════════════════════════════════════════════
    public function getAvailableStaff()
    {
        if ($this->mode !== 'complete' || !$this->schoolId) return collect();
        return \App\Models\User::where('school_id', $this->schoolId)
            ->where('usergroup_id', 5)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function coAdminInviteYes()
    {
        $staff = $this->getAvailableStaff();
        if ($this->mode === 'complete' && $staff->isNotEmpty()) {
            $this->substep = 10;
            $count = $staff->count();
            $this->botSay("There **{$count}** teacher" . ($count > 1 ? 's' : '') . " on staff. Select one to promote to co-admin:");
            return;
        }
        $this->substep = 1;
        $this->botSay("Great! Enter the co-admin's email address:");
    }

    public function coAdminInviteSkip()
    {
        $this->botSay("Skipped. You can add more admins later from the admin panel.");
        $this->substep = 0;
        $this->advance();
    }

    /**
     * Promote an existing teacher (usergroup_id: 5) to co-admin (usergroup_id: 3).
     */
    public function promoteCoAdmin(int $userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user || $user->school_id !== $this->schoolId) {
            $this->botSay("Invalid selection. Please try again.");
            return;
        }

        $this->coAdminUserId = $user->id;
        $this->coAdminName = $user->name;
        $this->coAdminEmail = $user->email;
        $this->botSay("**{$user->name}** ({$user->email}) will be promoted to co-admin on commit.");
        $this->substep = 0;
        $this->advance();
    }

    public function promoteCoAdminAddNew()
    {
        $this->substep = 1;
        $this->botSay("Enter the new co-admin's email address:");
    }

    private function handleCoAdminInvite(string $text)
    {
        // substep 0: buttons only — text fallback (skip or email)
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->coAdminInviteSkip();
                return;
            }
            $email = $this->validateEmail($text);
            if ($email === null) return;
            $this->coAdminEmail = $email;
            $this->botSay("Co-admin email: **{$email}**. | What is their full name?");
            $this->substep = 2;
            return;
        }

        // substep 1: collect email (from "Yes" or "Add someone else")
        if ($this->substep === 1) {
            $email = $this->validateEmail($text);
            if ($email === null) return;
            $this->coAdminEmail = $email;
            $this->botSay("Co-admin email: **{$email}**. | What is their full name?");
            $this->substep = 2;
            return;
        }

        // substep 2: collect co-admin name
        if ($this->substep === 2) {
            $name = $this->validateRequired($text, 'Co-admin name', 3);
            if ($name === null) return;
            $this->coAdminName = $name;
            $this->botSay("Co-admin **{$name}** added. They'll receive login credentials when the school is created.");
            $this->substep = 0;
            $this->advance();
            return;
        }

        // substep 10: text fallback for teacher selection (email or name search)
        if ($this->substep === 10) {
            $this->botSay("Please use the buttons above to select a teacher, or type **skip**.");
        }
    }

    // ════════════════════════════════════════════════
    //  Step 5: Academic Year (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleAcademicYear(string $text)
    {
        // substep 0: show current year, ask if correct
        if ($this->substep === 0) {
            $year = date('Y');
            $this->academicYearLabel = (string) $year;
            $this->botSay("Academic year: **{$year}** | Is that correct? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 1;
            return;
        }

        // substep 1: confirm year or ask for custom
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Please enter the academic year (e.g. 2025 or 2025/2026):");
            $this->substep = 2;
            return;
        }

        // substep 2: accept custom year
        if ($this->substep === 2) {
            $this->academicYearLabel = trim($text);
            $this->botSay("Academic year set to **{$this->academicYearLabel}**.");
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 6: Standards / Classes (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleStandards(string $text)
    {
        // substep -1: ask about nursery for primary schools
        if ($this->substep === -1) {
            $yes = in_array(strtolower(trim($text)), ['yes', 'y', 'correct', 'right', 'ok', 'true', 'yeah']);
            $this->hasNursery = $yes;
            $this->substep = 0;
            $this->handleStandards('');
            return;
        }

        // substep 0: load defaults, show list, ask if correct
        if ($this->substep === 0) {
            // Ask about nursery first if primary and hasn't been asked yet
            if ($this->schoolType === 'primary' && $this->hasNursery === null) {
                $this->botSay("Does your school have a nursery section? (Baby Class, Middle Class, Top Class)");
                $this->awaitingConfirm = true;
                $this->substep = -1;
                return;
            }
            $defaults = $this->curriculumDefaults();
            $this->standards = $defaults['classes'] ?? [];
            $classList = implode(', ', array_column($this->standards, 'name'));
            $this->botSay("I'll create these classes: **{$classList}**. You can add more classes later from the admin panel. | Is this correct? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 1;
            return;
        }

        // substep 1: confirm or ask for custom list
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->streamClassIndex = 0;
                $this->substep = 3;
                $this->handleStandards('');
                return;
            }
            // Detect nursery request in natural language
            $lower = strtolower($text);
            if (($this->schoolType === 'primary' && !$this->hasNursery) && preg_match('/nursery|baby class|pre.?primary|kindergarten/', $lower)) {
                $this->hasNursery = true;
                $this->substep = 0;
                $this->handleStandards('');
                return;
            }
            $this->botSay("Please enter the class names separated by commas (e.g. Primary 1, Primary 2, Primary 3):");
            $this->substep = 2;
            return;
        }

        // substep 2: parse custom list and advance
        if ($this->substep === 2) {
            $names = array_map('trim', explode(',', $text));
            $this->standards = [];
            foreach ($names as $name) {
                if (strlen($name) > 0) {
                    $this->standards[] = ['name' => $name];
                }
            }
            $classList = implode(', ', array_column($this->standards, 'name'));
            $this->botSay("Classes set to: **{$classList}**.");
            $this->streamClassIndex = 0;
            $this->substep = 3;
            $this->handleStandards('');
            return;
        }

        // substep 3: ask about streams class by class
        if ($this->substep === 3) {
            if ($this->streamClassIndex >= count($this->standards)) {
                $this->streamClassIndex = 0;
                $this->substep = 0;
                $this->advance();
                return;
            }
            $className = $this->standards[$this->streamClassIndex]['name'];
            $this->botSay("Does **{$className}** have class streams? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 5;
            return;
        }

        // substep 5: yes/no for current class streams
        if ($this->substep === 5) {
            $yes = in_array(strtolower(trim($text)), ['yes', 'y', 'correct', 'right', 'ok', 'yeah']);
            if ($yes) {
                $className = $this->standards[$this->streamClassIndex]['name'];
                $this->botSay("Type stream names for **{$className}** separated by commas (e.g. A, B, C):");
                $this->substep = 4;
                return;
            }
            $this->standards[$this->streamClassIndex]['streams'] = [];
            $this->streamClassIndex++;
            $this->substep = 3;
            $this->handleStandards('');
            return;
        }

        // substep 4: collect stream names for current class, move to next
        if ($this->substep === 4) {
            $className = $this->standards[$this->streamClassIndex]['name'];
            $lower = strtolower(trim($text));
            if (in_array($lower, ['skip', 'none', 'no', 'n', '', 'later'])) {
                $this->standards[$this->streamClassIndex]['streams'] = [];
            } else {
                $names = array_map('trim', explode(',', $text));
                $names = array_filter($names, fn($n) => strlen($n) > 0);
                $this->standards[$this->streamClassIndex]['streams'] = array_values($names);
            }
            $this->streamClassIndex++;
            $this->substep = 3;
            $this->handleStandards('');
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 7: Subjects (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleSubjects(string $text)
    {
        // substep 0: load defaults, show sample, ask if fine
        if ($this->substep === 0) {
            $defaults = $this->curriculumDefaults();
            $this->subjects = $defaults['subjects'] ?? [];
            $subjectList = collect($this->subjects)->first() ?? [];
            if (is_array($subjectList)) {
                $subjectList = implode(', ', array_slice($subjectList, 0, 5)) . '...';
            }
            $this->botSay("Default subjects assigned per class (NCDC curriculum), e.g. {$subjectList} | Accept or customize? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 1;
            return;
        }

        // substep 1: confirm or enter custom subjects
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Enter a subject name (e.g. Mathematics, English, or say 'done' when finished):");
            $this->actionData = ['subjects' => [], 'current_class' => 0];
            $this->substep = 6;
            return;
        }

        // substep 6: collect custom subjects one at a time
        if ($this->substep === 6) {
            $lower = strtolower(trim($text));
            if (in_array($lower, ['done', 'finish', 'stop', 'no', 'none', ''])) {
                if (empty($this->actionData['subjects'])) {
                    $this->botSay("I need at least one subject. Type a subject name:");
                    return;
                }
                $this->subjects = [
                    ($this->standards[0]['name'] ?? 'default') => $this->actionData['subjects'],
                ];
                $this->botSay("Subjects saved: **" . implode(', ', $this->actionData['subjects']) . "**. You can customize per-class from the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            // Add subject name
            $this->actionData['subjects'][] = trim($text);
            $count = count($this->actionData['subjects']);
            $this->botSay("Added **" . trim($text) . "** ({$count} so far). Type another or say **done**.");
            return;
        }

        // substep 2: (legacy - kept for backward compatibility)
        if ($this->substep === 2) {
            $names = array_map('trim', explode(',', $text));
            $names = array_filter($names, fn($n) => strlen($n) > 1);
            if (empty($names)) {
                $this->botSay("I need at least one subject. Please enter subjects separated by commas:");
                return;
            }
            $this->subjects = [
                ($this->standards[0]['name'] ?? 'default') => array_values($names),
            ];
            $this->botSay("Subjects saved: **" . implode(', ', $names) . "**.");
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 8: Teachers (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleTeachers(string $text)
    {
        // substep 0: collect names or skip
        if ($this->substep === 0) {
            if (trim($text) === '') {
                $this->showTeacherFormFn();
                return;
            }
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can add teachers later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $names = $this->parseNameList($text);
            if (count($names) > 0) {
                $this->teacherList = $names;
                $preview = implode(', ', array_slice($this->teacherList, 0, 3));
                $this->botSay("Parsed **" . count($this->teacherList) . "** teachers: {$preview}" . (count($this->teacherList) > 3 ? '...' : '') . " | Is this correct? (yes / no)");
                $this->awaitingConfirm = true;
                $this->substep = 1;
                return;
            }
            $this->botSay("I couldn't find any names. Please paste a list (one per line) or type 'skip' to add later.");
            return;
        }

        // substep 1: confirm teacher list
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("No problem. Please paste the correct teacher names (one per line) or type 'skip':");
            $this->substep = 0;
            return;
        }
    }

    private function parseNameList(string $text): array
    {
        $lines = preg_split('/[\n,]+/', $text);
        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 3 && !is_numeric($line)) {
                $names[] = $line;
            }
        }
        return array_slice(array_unique($names), 0, 50);
    }

    // ════════════════════════════════════════════════
    //  Step 9: Teacher-Class-Subject Linking
    // ════════════════════════════════════════════════
    private function handleTeacherLinks(string $text)
    {
        // substep 0: explain format, collect input
        if ($this->substep === 0) {
            if (trim($text) === '') {
                // show prompt below
            }
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can assign teachers to subjects later in the admin panel (Classes → select class → assign teachers).");
                $this->substep = 0;
                $this->advance();
                return;
            }

            // If teacher list is empty, skip automatically
            if (empty($this->teacherList)) {
                $this->botSay("No teachers to link. Moving on.");
                $this->substep = 0;
                $this->advance();
                return;
            }

            $classNames = collect($this->standards)->pluck('name')->implode(', ');
            $this->botSay(
                "Now let's link each teacher to their subjects and classes.\n\n"
                . "Your classes: *{$classNames}*\n"
                . "Your teachers: *" . implode(', ', $this->teacherList) . "*\n\n"
                . "Enter one line per teacher:\n"
                . "`Teacher Name | Subject | Class | Phone`\n\n"
                . "Phone is optional — use the teacher's WhatsApp number if known.\n\n"
                . "Example:\n"
                . "John Ssali | Mathematics | P.5 | +256701234567\n"
                . "Jane Okello | English | P.5\n"
                . "John Ssali | Science | P.6 | +256701234567\n\n"
                . "Type the full list, or type 'skip' to do this later."
            );
            $this->substep = 1;
            return;
        }

        // substep 1: parse the list
        if ($this->substep === 1) {
            // Handle skip at substep 1 too — the bot says "or type 'skip'" but setting substep=0
            // in the explanation block means user input lands here.
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can assign teachers to subjects later in the admin panel (Classes → select class → assign teachers).");
                $this->substep = 0;
                $this->advance();
                return;
            }

            $lines = array_filter(explode("\n", $text), fn($l) => trim($l) !== '');
            $parsed = [];
            $phones = [];

            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) < 3) continue;

                $teacherName = $parts[0];
                $subjectName = $parts[1];
                $className = $parts[2];
                $phone = $parts[3] ?? '';

                // Validate teacher exists in teacherList
                $teacherExists = in_array($teacherName, $this->teacherList);
                // Validate class exists in standards
                $classExists = collect($this->standards)->pluck('name')->contains($className);

                if ($teacherExists && $classExists) {
                    $parsed[] = [
                        'teacher' => $teacherName,
                        'subject' => $subjectName,
                        'class'   => $className,
                        'phone'   => $phone,
                    ];
                    // Store phone for teacher if provided
                    if ($phone) {
                        $phones[$teacherName] = $phone;
                    }
                }
            }

            if (empty($parsed)) {
                $this->botSay(
                    "I couldn't parse any valid entries. Make sure each line uses:\n"
                    . "`Teacher Name | Subject | Class | Phone`\n\n"
                    . "Example: John Ssali | Mathematics | P.5 | +256701234567\n\n"
                    . "Type your list again, or type 'skip'."
                );
                return;
            }

            $this->teacherLinks = $parsed;
            $this->teacherPhones = $phones;
            $preview = collect($parsed)->take(3)->map(
                fn($l) => "• {$l['teacher']} → {$l['subject']} ({$l['class']})" . ($l['phone'] ? " 📱{$l['phone']}" : '')
            )->implode("\n");

            $phoneCount = count($phones);
            $phoneNote = $phoneCount > 0 ? "\n📱 Phone numbers saved for {$phoneCount} teacher(s)." : '';

            $this->botSay(
                "Parsed **" . count($parsed) . "** teacher link(s):\n{$preview}{$phoneNote}"
                . (count($parsed) > 3 ? "\n...and " . (count($parsed) - 3) . " more" : '')
                . "\n\nIs this correct? (yes / no)"
            );
            $this->awaitingConfirm = true;
            $this->substep = 2;
            return;
        }

        // substep 2: confirmation
        if ($this->substep === 2) {
            if (in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok'])) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Let's try again. Enter the teacher links one per line:\n`Teacher Name | Subject | Class | Phone`");
            $this->substep = 0;
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 10: Students (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleStudents(string $text)
    {
        // substep 0: collect names or skip
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can add students later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $names = $this->parseNameList($text);
            if (count($names) > 0) {
                $this->studentList = $names;
                $preview = implode(', ', array_slice($this->studentList, 0, 3));
                $this->botSay("Parsed **" . count($this->studentList) . "** students: {$preview}" . (count($this->studentList) > 3 ? '...' : '') . " | Is this correct? (yes / no)");
                $this->awaitingConfirm = true;
                $this->substep = 1;
                return;
            }
            $this->botSay("I couldn't find any names. Please paste a list (one per line) or type 'skip' to add later.");
            return;
        }

        // substep 1: confirm student list
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("No problem. Please paste the correct student names (one per line) or type 'skip':");
            $this->substep = 0;
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 11: Academic Terms (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleTerms(string $text)
    {
        // substep 0: set default 3 terms, show them, ask if correct
        if ($this->substep === 0) {
            $this->terms = [
                ['name' => 'Term I',   'start' => date('Y') . '-02-01', 'end' => date('Y') . '-04-30'],
                ['name' => 'Term II',  'start' => date('Y') . '-05-01', 'end' => date('Y') . '-08-31'],
                ['name' => 'Term III', 'start' => date('Y') . '-09-01', 'end' => date('Y') . '-12-31'],
            ];
            $this->botSay("Default Ugandan terms set: Term I (Feb-Apr), Term II (May-Aug), Term III (Sep-Dec). | Is this correct? (yes / no)");
            $this->awaitingConfirm = true;
            $this->substep = 1;
            return;
        }

        // substep 1: confirm or enter custom terms (mandatory step)
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Please enter your custom terms (e.g. Term I, Term II, Term III):");
            $this->substep = 2;
            return;
        }

        // substep 2: parse custom terms (mandatory — cannot skip)
        if ($this->substep === 2) {
            $names = array_map('trim', explode(',', $text));
            $names = array_filter($names, fn($n) => strlen($n) > 1);
            if (empty($names)) {
                $this->botSay("I need at least one term name. Please enter your terms (e.g. Term I, Term II, Term III):");
                return;
            }
            $this->terms = [];
            foreach ($names as $name) {
                $this->terms[] = ['name' => $name, 'start' => now()->startOfYear(), 'end' => now()->endOfYear()];
            }
            $this->botSay("**" . count($this->terms) . "** terms saved: " . implode(', ', $names));
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 12: Fees (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleFees(string $text)
    {
        // substep 0: collect first fee or skip
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can set up fees later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $fee = $this->validateRequired($text, 'Fee name');
            if ($fee === null) return;
            $this->fees[] = $fee;
            $this->botSay("Added **{$fee}**. | Add another fee category? (type name or 'done')");
            $this->substep = 1;
            return;
        }

        // substep 1: add more fees or finish
        if ($this->substep === 1) {
            $done = in_array(strtolower($text), ['done', 'no', 'skip', 'later', 'none']);
            if ($done) {
                $count = count($this->fees);
                $this->botSay("**{$count}** fee categor" . ($count === 1 ? 'y' : 'ies') . " saved.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $fee = $this->validateRequired($text, 'Fee name');
            if ($fee === null) return;
            $this->fees[] = $fee;
            $this->botSay("Added **{$fee}**. | Add another fee category? (type name or 'done')");
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 13: Exams (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleExams(string $text)
    {
        // substep 0: collect first exam or skip
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can create exams later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $exam = $this->validateRequired($text, 'Exam name');
            if ($exam === null) return;
            $this->exams[] = $exam;
            $this->botSay("Added **{$exam}**. | Add another exam? (type name or 'done')");
            $this->substep = 1;
            return;
        }

        // substep 1: add more exams or finish
        if ($this->substep === 1) {
            $done = in_array(strtolower($text), ['done', 'no', 'skip', 'later', 'none']);
            if ($done) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->exams[] = trim($text);
            $this->botSay("Added **" . trim($text) . "**. | Add another exam? (type name or 'done')");
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 14: WhatsApp Verification
    // ════════════════════════════════════════════════
    private function handleWhatsAppVerify(string $text)
    {
        // substep 0: confirm phone number or enter different one
        if ($this->substep === 0) {
            $phone = $this->schoolPhone ?: '';
            if ($phone) {
                $this->botSay("📱 We'll verify the admin's WhatsApp number: **{$phone}**");
                $this->botSay("Is this the right number? (yes / no)");
                $this->awaitingConfirm = true;
                $this->substep = 1;
                return;
            }
            $this->botSay("What's the admin's WhatsApp number? (e.g., +256701234567)");
            $this->substep = 2;
            return;
        }

        // substep 1: confirm phone
        if ($this->substep === 1) {
            if (in_array(strtolower($text), ['yes', 'y', 'correct', 'ok'])) {
                $this->whatsappPhone = $this->schoolPhone;
                $this->botSay("Sending verification code to **{$this->whatsappPhone}**...");
                $this->sendWhatsAppOtp();
                return;
            }
            // Allow skip at substep 1 too (for E2E testing or users without WhatsApp)
            if (in_array(strtolower($text), ['skip', 'later', 'no'])) {
                $this->botSay("Skipping WhatsApp verification. You can verify later from the admin settings.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Enter the correct WhatsApp number:");
            $this->substep = 2;
            return;
        }

        // substep 2: enter different phone
        if ($this->substep === 2) {
            $phone = $this->normalizeUgandaPhone($text);
            if ($phone === null) return;
            $this->whatsappPhone = $phone;
            $this->botSay("Sending verification code to **{$this->whatsappPhone}**...");
            $this->sendWhatsAppOtp();
            return;
        }

        // substep 3: verify OTP
        if ($this->substep === 3) {
            $code = trim($text);
            if ($code === $this->whatsappSentOtp) {
                $this->whatsappVerified = true;
                $this->userSay("✅ Code verified!");
                $this->botSay("✅ WhatsApp verified for **{$this->whatsappPhone}**! The admin will receive school notifications here.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("That code doesn't match. Please try again, or type 'resend' for a new code.");
            $this->substep = 4;
            return;
        }

        // substep 4: wrong code — retry, resend, or skip
        if ($this->substep === 4) {
            if (strtolower($text) === 'resend') {
                $this->sendWhatsAppOtp();
                return;
            }
            if (in_array(strtolower($text), ['skip', 'later'])) {
                $this->botSay("You can verify WhatsApp later from the admin settings.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $code = trim($text);
            if ($code === $this->whatsappSentOtp) {
                $this->whatsappVerified = true;
                $this->userSay("✅ Code verified!");
                $this->botSay("✅ WhatsApp verified for **{$this->whatsappPhone}**!");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Still doesn't match. Type **resend** for a new code or **skip** to do this later.");
        }
    }

    private function sendWhatsAppOtp()
    {
        $otp = (string) rand(100000, 999999);
        $this->whatsappSentOtp = $otp;

        try {
            if ($this->whatsappPhone) {
                $sent = app(\App\Services\WhatsAppBusinessService::class)->sendTextSafe(
                    $this->whatsappPhone,
                    "Your KlassApp verification code is: {$otp}. It expires in 5 minutes.",
                );
                if (($sent['status'] ?? '') === 'success' || ($sent['success'] ?? false)) {
                    $this->botSay("📱 Verification code sent! Check WhatsApp on **{$this->whatsappPhone}**.");
                } else {
                    \Log::info('WhatsApp OTP send result', $sent);
                    if (!app()->environment('production')) {
                        $this->botSay("📱 In production, a code would be sent via WhatsApp. For testing, use code: **{$otp}**");
                    } else {
                        $this->botSay("📱 Could not send the verification code via WhatsApp right now. Type **resend** to try again or **skip** to do this later.");
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('WhatsApp OTP send failed: ' . $e->getMessage());
            if (!app()->environment('production')) {
                $this->botSay("📱 Could not send via WhatsApp right now. For testing, use code: **{$otp}**");
            } else {
                $this->botSay("📱 Could not send the verification code via WhatsApp right now. Type **resend** to try again or **skip** to do this later.");
            }
        }

        $this->botSay("Enter the 6-digit code you received:");
        $this->substep = 3;
    }

    // ════════════════════════════════════════════════
    //  Step 15: Review & Commit
    // ════════════════════════════════════════════════
    private function handleReview(string $text)
    {
        // —— Edit-navigation mode: user clicked "← Edit" and reviewData was cleared ——
        // Parse the step name they typed and jump to that step
        if (empty($this->reviewData)) {
            $stepMap = [
                'plan' => 0, 'plans' => 0, 'plan_selection' => 0,
                'school' => 1, 'school_info' => 1,
                'admin' => 2, 'admin_account' => 2,
                'co-admin' => 3, 'coadmin' => 3, 'co_admin' => 3, 'co_admin_invite' => 3,
                'academic' => 4, 'academic_year' => 4, 'year' => 4,
                'classes' => 5, 'class' => 5, 'standards' => 5, 'standard' => 5,
                'subjects' => 6, 'subject' => 6,
                'teachers' => 7, 'teacher' => 7,
                'teacher_links' => 8, 'teacher_link' => 8, 'links' => 8,
                'students' => 9, 'student' => 9,
                'terms' => 10, 'term' => 10,
                'fees' => 11, 'fee' => 11,
                'exams' => 12, 'exam' => 12,
                'whatsapp' => 13, 'whatsapp_verify' => 13,
            ];
            $textLower = strtolower(trim($text));
            // Only match known step names — 'commit' falls through to build reviewData
            if (isset($stepMap[$textLower])) {
                $target = $stepMap[$textLower];
                $this->step = $target;
                $this->substep = 0;
                $this->botSay("Going back to **" . str_replace('_', ' ', $this->steps[$target]) . "**. Let's review that section.");
                return;
            }
            // Unknown text — rebuild reviewData so buttons reappear
        }

        // Build review data once (on first entry, or after failed edit navigation)
        if (empty($this->reviewData)) {
            $planName = $this->selectedPlanId ? \App\Models\Plan::find($this->selectedPlanId)?->name : '—';
            $schoolDisplay = $this->mode === 'complete'
                ? optional(\App\Models\School::find($this->schoolId))->name ?? 'Your school'
                : $this->schoolName;
            $this->reviewData = [
                'plan'         => $this->mode === 'create' ? ucfirst($planName ?: '—') : 'Already assigned',
                'schoolName'   => $schoolDisplay,
                'schoolType'   => $this->schoolType,
                'adminName'    => $this->adminName ?: (auth()->user()->name ?? '—'),
                'adminEmail'   => $this->adminEmail ?: (auth()->user()->email ?? '—'),
                'adminPhone'   => $this->schoolPhone,
                'coAdminName'  => $this->coAdminName ?: '',
                'coAdminEmail' => $this->coAdminEmail ?: '',
                'classCount'   => count($this->standards),
                'classList'    => implode(', ', array_column($this->standards, 'name')),
                'teacherCount' => count($this->teacherList),
                'teacherLinkCount' => count($this->teacherLinks),
                'studentCount' => count($this->studentList),
                'studentIds'   => count($this->studentList) > 0
                    ? count($this->studentList) . " students — each gets a unique KlassApp ID"
                    : '—',
                'termCount'    => count($this->terms),
                'feeCount'     => count($this->fees),
                'examCount'    => count($this->exams),
                'whatsapp'     => $this->whatsappVerified ? "✅ {$this->whatsappPhone}" : '⏳ Not verified',
                'mode'         => $this->mode,
            ];
            $action = $this->mode === 'complete' ? 'save these changes' : 'create this school';
            $this->botSay("📋 Review the changes below, then click **Confirm** to {$action}.");
        }

        if (strtolower($text) === 'commit') {
            try {
                $this->commitAll();
                $this->deleteDraft();
                $this->reviewData['committed'] = true;
                $this->reviewData['adminEmail'] = $this->adminEmail ?: (auth()->user()->email ?? '');
                $this->reviewData['adminPhone'] = $this->schoolPhone;
                $this->reviewData['adminPassword'] = $this->adminPassword ?: 'password';
                $this->reviewData['coAdminEmail'] = $this->coAdminEmail;
                $this->reviewData['coAdminPassword'] = $this->coAdminUserId ? null : ($this->adminPassword ?: 'password');
                $this->reviewData['coAdminPromoted'] = (bool) $this->coAdminUserId;
                $this->reviewData['mode'] = $this->mode;
                $this->step = 99;
                // Transition to assistant mode after successful commit
                $this->mode = 'assistant';
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Onboarding DB error: ' . $e->getMessage());
                $code = $e->getCode();
                if ($code == 23000) {
                    $this->botSay("This school or email already exists in the system. Please check for duplicates and try again.");
                } elseif ($code == 2002 || $code == 1045) {
                    $this->botSay("Unable to connect to the database. Please try again in a moment.");
                } else {
                    $this->botSay("A database error occurred. Please try again or contact support.");
                }
            } catch (\Exception $e) {
                \Log::error('Onboarding commit failed: ' . $e->getMessage());
                $this->botSay("Something unexpected happened. Please try again or contact support.");
            }
        }
    }

    // ════════════════════════════════════════════════
    //  Commit everything to the database
    // ════════════════════════════════════════════════
    private function commitAll()
    {
        DB::transaction(function () {
            // ════════════════════════════════════════════
            //  CREATE MODE: Full school + admin creation
            // ════════════════════════════════════════════
            if ($this->mode === 'create') {
                $school = School::create([
                    'name'    => $this->schoolName,
                    'email'   => $this->schoolEmail ?: Str::slug($this->schoolName) . '@klassapp.sch.ug',
                    'phone'   => $this->schoolPhone ?: '0700000000',
                    'status'  => 1,
                    'slug'    => Str::slug($this->schoolName),
                    'registration_country' => 'Uganda',
                ]);
                $this->schoolId = $school->id;
                $schoolId = $school->id;

                // Create admin user FIRST so we can reference its ID in subscriptions
                $password = bcrypt($this->adminPassword ?: 'password');
                $adminUser = User::create([
                    'school_id' => $school->id, 'usergroup_id' => 3,
                    'name' => $this->adminName ?: 'School Admin',
                    'email' => $this->adminEmail ?: 'admin@' . Str::slug($this->schoolName) . '.sch.ug',
                    'password' => $password, 'status' => 'active', 'email_verified' => 1,
                ]);
                Userprofile::create([
                    'school_id' => $school->id, 'user_id' => $adminUser->id,
                    'usergroup_id' => 3, 'firstname' => $this->adminName ?: 'School',
                    'lastname' => 'Admin', 'status' => 'active',
                ]);

                if ($this->selectedPlanId) {
                    CurrentPlan::create(['school_id' => $school->id, 'plan_id' => $this->selectedPlanId]);
                    Subscription::create([
                        'school_id'  => $school->id, 'plan_id' => $this->selectedPlanId,
                        'user_id'    => $adminUser->id,
                        'status' => 'active', 'start_date' => now(), 'end_date' => now()->addYear(),
                    ]);
                }

                $academicYear = AcademicYear::create([
                    'school_id' => $school->id, 'name' => $this->academicYearLabel ?: date('Y'),
                    'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
                    'type' => 'Current Academic Year',
                    'description' => 'Current Academic Year',
                ]);

                // Co-admin
                $coAdminUser = null;
                if ($this->coAdminName && $this->coAdminEmail) {
                    $coAdminUser = User::create([
                        'school_id' => $school->id, 'usergroup_id' => 3,
                        'name' => $this->coAdminName,
                        'email' => $this->coAdminEmail,
                        'password' => $password, 'status' => 'active', 'email_verified' => 1,
                    ]);
                    Userprofile::create([
                        'school_id' => $school->id, 'user_id' => $coAdminUser->id,
                        'usergroup_id' => 3, 'firstname' => $this->coAdminName,
                        'lastname' => 'Co-Admin', 'status' => 'active',
                    ]);

                    try {
                        Mail::to($this->coAdminEmail)->queue(new CoAdminInviteMail(
                            $this->coAdminName, $this->coAdminEmail,
                            $this->adminPassword ?: 'password',
                            $school->name, false
                        ));
                    } catch (\Exception $e) {
                        \Log::warning('Co-admin invite email failed: ' . $e->getMessage());
                    }
                }

                $phase = Standard::create(['school_id' => $school->id, 'name' => $this->schoolType, 'order' => 1, 'status' => '1']);

                $firstStandardLink = null;

                foreach ($this->standards as $class) {
                    $section = Section::firstOrCreate(
                        ['school_id' => $school->id, 'name' => $class['name']],
                        ['status' => '1']
                    );
                    $standardLink = StandardLink::create([
                        'school_id' => $school->id, 'academic_year_id' => $academicYear->id,
                        'standard_id' => $phase->id, 'section_id' => $section->id, 'status' => '1',
                    ]);
                    if (!$firstStandardLink) {
                        $firstStandardLink = $standardLink;
                    }
                    $classSubjects = $this->subjects[$class['name']] ?? [];
                    foreach ($classSubjects as $subjectName) {
                        Subject::firstOrCreate(
                            ['school_id' => $school->id, 'standard_id' => $phase->id, 'section_id' => $section->id, 'name' => $subjectName],
                            ['academic_year_id' => $academicYear->id, 'type' => 'core']
                        );
                    }
                }

                foreach ($this->teacherList as $name) {
                    $tEmail = Str::slug($name) . '@' . Str::slug($this->schoolName) . '.edu';
                    $phone = $this->teacherPhones[$name] ?? null;
                    $teacher = User::create([
                        'school_id' => $school->id, 'usergroup_id' => 5, 'name' => $name,
                        'email' => $tEmail, 'password' => $password, 'status' => 'active', 'email_verified' => 1,
                        'mobile_no' => $phone,
                    ]);
                    Userprofile::create([
                        'school_id' => $school->id, 'user_id' => $teacher->id, 'usergroup_id' => 5,
                        'firstname' => $name, 'lastname' => '', 'profession' => 'teacher', 'status' => 'active',
                    ]);
                }

                // Create teacher-class-subject links from parsed data
                foreach ($this->teacherLinks as $link) {
                    // Look up teacher by name — case-insensitive LIKE match
                    $teacherUser = User::where('school_id', $school->id)
                        ->where('usergroup_id', 5)
                        ->where(function ($q) use ($link) {
                            $q->where('name', $link['teacher'])
                              ->orWhere('name', 'like', strtolower($link['teacher']) . '%');
                        })
                        ->first();
                    $linkSection = Section::where('school_id', $school->id)->where('name', $link['class'])->first();
                    $linkStandardLink = $linkSection
                        ? StandardLink::where('school_id', $school->id)->where('section_id', $linkSection->id)->where('academic_year_id', $academicYear->id)->first()
                        : null;
                    $linkSubject = $linkSection
                        ? Subject::where('school_id', $school->id)->where('section_id', $linkSection->id)->where('name', $link['subject'])->first()
                        : null;

                    if ($teacherUser && $linkStandardLink && $linkSubject) {
                        Teacherlink::firstOrCreate([
                            'school_id' => $school->id,
                            'academic_year_id' => $academicYear->id,
                            'standardLink_id' => $linkStandardLink->id,
                            'subject_id' => $linkSubject->id,
                            'teacher_id' => $teacherUser->id,
                        ]);
                    }
                }

                // Create students from onboarding list
                foreach ($this->studentList as $index => $studentName) {
                    if (!trim($studentName)) continue;

                    $sEmail = 'student.' . ($index + 1) . '@' . Str::slug($this->schoolName) . '.sch.ug';
                    $studentUser = User::create([
                        'school_id' => $school->id, 'usergroup_id' => 6,
                        'name' => trim($studentName),
                        'email' => $sEmail, 'password' => $password,
                        'status' => 'active', 'email_verified' => 1,
                    ]);
                    Userprofile::create([
                        'school_id' => $school->id, 'user_id' => $studentUser->id, 'usergroup_id' => 6,
                        'firstname' => trim($studentName), 'lastname' => '', 'status' => 'active',
                    ]);
                    if ($firstStandardLink) {
                        // Generate KlassApp Student ID: KLS{school_code_3}{sequential_4}
                        // Uses ministry_code if available, falls back to school DB id
                        $codeRaw = $school->ministry_code ?? $school->id;
                        $schoolCode = str_pad(substr((string) $codeRaw, 0, 3), 3, '0', STR_PAD_LEFT);
                        $seq = str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                        $klassappId = "KLS{$schoolCode}{$seq}";

                        StudentAcademic::create([
                            'school_id' => $school->id,
                            'academic_year_id' => $academicYear->id,
                            'user_id' => $studentUser->id,
                            'standardLink_id' => $firstStandardLink->id,
                            'klassapp_student_id' => $klassappId,
                        ]);
                    }
                }

                foreach ($this->terms as $term) {
                    AcademicTerm::create([
                        'school_id' => $school->id, 'academic_year_id' => $academicYear->id,
                        'name' => $term['name'], 'start_date' => $term['start'], 'end_date' => $term['end'],
                    ]);
                }

                foreach ($this->fees as $feeName) {
                    if (is_string($feeName) && trim($feeName)) {
                        FeesCategories::create([
                            'school_id' => $school->id, 'standard_id' => $phase->id,
                            'name' => trim($feeName), 'amount' => 0,
                        ]);
                    }
                }

                if ($this->whatsappVerified && $this->whatsappPhone) {
                    \App\Models\WhatsAppUser::create([
                        'phone' => $this->whatsappPhone, 'user_id' => $adminUser->id,
                        'school_id' => $this->schoolId,
                        'verified_at' => now(), 'opted_in' => true,
                    ]);
                }
            }

            // ════════════════════════════════════════════
            //  COMPLETE MODE: Only add missing items
            // ════════════════════════════════════════════
            if ($this->mode === 'complete') {
                $schoolId = $this->schoolId;
                $user = auth()->user();

                $academicYear = \App\Models\AcademicYear::where('school_id', $schoolId)->first();
                if (!$academicYear) {
                    $academicYear = AcademicYear::create([
                        'school_id' => $schoolId, 'name' => date('Y'),
                        'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
                        'type' => 'Current Academic Year',
                        'description' => 'Current Academic Year',
                    ]);
                }

                $phase = \App\Models\Standard::where('school_id', $schoolId)->first();
                if (!$phase) {
                    $phase = Standard::create(['school_id' => $schoolId, 'name' => 'default', 'order' => 1, 'status' => '1']);
                }

                // Create standards if missing
                foreach ($this->standards as $class) {
                    Section::firstOrCreate(
                        ['school_id' => $schoolId, 'name' => $class['name']],
                        ['value' => $class['name'], 'status' => '1']
                    );
                }

                // Create teachers if any
                foreach ($this->teacherList as $name) {
                    $tEmail = Str::slug($name) . '@school.edu';
                    $exists = User::where('school_id', $schoolId)->where('name', $name)->exists();
                    if (!$exists) {
                        $phone = $this->teacherPhones[$name] ?? null;
                        $teacher = User::create([
                            'school_id' => $schoolId, 'usergroup_id' => 5, 'name' => $name,
                            'email' => $tEmail, 'password' => bcrypt('password'), 'status' => 'active', 'email_verified' => 1,
                            'mobile_no' => $phone,
                        ]);
                        Userprofile::create([
                            'school_id' => $schoolId, 'user_id' => $teacher->id, 'usergroup_id' => 5,
                            'firstname' => $name, 'lastname' => '', 'profession' => 'teacher', 'status' => 'active',
                        ]);
                    }
                }

                // Create teacher-class-subject links from parsed data
                foreach ($this->teacherLinks as $link) {
                    $teacherUser = User::where('school_id', $schoolId)
                        ->where('usergroup_id', 5)
                        ->where(function ($q) use ($link) {
                            $q->where('name', $link['teacher'])
                              ->orWhere('name', 'like', strtolower($link['teacher']) . '%');
                        })
                        ->first();
                    $linkSection = Section::where('school_id', $schoolId)->where('name', $link['class'])->first();
                    $linkStandardLink = $linkSection
                        ? StandardLink::where('school_id', $schoolId)->where('section_id', $linkSection->id)
                            ->where('academic_year_id', $academicYear->id)->first()
                        : null;
                    $linkSubject = $linkSection
                        ? Subject::where('school_id', $schoolId)->where('section_id', $linkSection->id)
                            ->where('name', $link['subject'])->first()
                        : null;

                    if ($teacherUser && $linkStandardLink && $linkSubject) {
                        Teacherlink::firstOrCreate([
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYear->id,
                            'standardLink_id' => $linkStandardLink->id,
                            'subject_id' => $linkSubject->id,
                            'teacher_id' => $teacherUser->id,
                        ]);
                    }
                }

                // Create terms
                foreach ($this->terms as $term) {
                    AcademicTerm::firstOrCreate(
                        ['school_id' => $schoolId, 'name' => $term['name']],
                        ['academic_year_id' => $academicYear->id, 'start_date' => $term['start'], 'end_date' => $term['end']]
                    );
                }

                // Create fees
                foreach ($this->fees as $feeName) {
                    if (is_string($feeName) && trim($feeName)) {
                        FeesCategories::create([
                            'school_id' => $schoolId, 'standard_id' => $phase->id, 'name' => trim($feeName), 'amount' => 0,
                        ]);
                    }
                }

                // WhatsApp verification
                if ($this->whatsappVerified && $this->whatsappPhone) {
                    \App\Models\WhatsAppUser::firstOrCreate(
                        ['phone' => $this->whatsappPhone],
                        ['user_id' => $user->id, 'school_id' => $this->schoolId, 'verified_at' => now(), 'opted_in' => true]
                    );
                }

                // Promote selected teacher to co-admin (usergroup_id: 5 → 3)
                if ($this->coAdminUserId) {
                    $coAdminUser = \App\Models\User::find($this->coAdminUserId);
                    if ($coAdminUser && $coAdminUser->school_id === $schoolId) {
                        $coAdminUser->update(['usergroup_id' => 3]);
                        \App\Models\Userprofile::updateOrCreate(
                            ['user_id' => $coAdminUser->id],
                            ['usergroup_id' => 3, 'school_id' => $schoolId, 'status' => 'active']
                        );

                        try {
                            $schoolName = optional(\App\Models\School::find($schoolId))->name ?? 'your school';
                            Mail::to($coAdminUser->email)->queue(new CoAdminInviteMail(
                                $coAdminUser->name, $coAdminUser->email,
                                null, $schoolName, true
                            ));
                        } catch (\Exception $e) {
                            \Log::warning('Co-admin promotion email failed: ' . $e->getMessage());
                        }
                    }
                }
            }
        });
    }

    // ════════════════════════════════════════════════
    //  Curriculum Defaults (NCDC Uganda)
    // ════════════════════════════════════════════════
    private function curriculumDefaults(): array
    {
        return match ($this->schoolType) {
            'nursery' => [
                'classes' => [
                    ['name' => 'Baby Class'], ['name' => 'Middle Class'], ['name' => 'Top Class'],
                ],
                'subjects' => [
                    'Baby Class'   => ['English Rhymes & Stories', 'Numbers & Counting', 'Colour & Shapes', 'Creative Play'],
                    'Middle Class' => ['English Language Basics', 'Numeracy', 'Environmental Awareness', 'Art & Craft'],
                    'Top Class'    => ['Pre-Literacy (English)', 'Pre-Numeracy', 'Social Habits', 'Creative Arts', 'Religious Education'],
                ],
            ],
            'primary' => [
                'classes' => $this->hasNursery
                    ? [['name' => 'Baby Class'], ['name' => 'Middle Class'], ['name' => 'Top Class'],
                       ['name' => 'Primary 1'], ['name' => 'Primary 2'], ['name' => 'Primary 3'],
                       ['name' => 'Primary 4'], ['name' => 'Primary 5'], ['name' => 'Primary 6'], ['name' => 'Primary 7']]
                    : [['name' => 'Primary 1'], ['name' => 'Primary 2'], ['name' => 'Primary 3'],
                       ['name' => 'Primary 4'], ['name' => 'Primary 5'], ['name' => 'Primary 6'], ['name' => 'Primary 7']],
                'subjects' => $this->hasNursery
                    ? [
                        'Baby Class'   => ['English Rhymes & Stories', 'Numbers & Counting', 'Colour & Shapes', 'Creative Play'],
                        'Middle Class' => ['English Language Basics', 'Numeracy', 'Environmental Awareness', 'Art & Craft'],
                        'Top Class'    => ['Pre-Literacy (English)', 'Pre-Numeracy', 'Social Habits', 'Creative Arts', 'Religious Education'],
                        'Primary 1' => ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education'],
                    ]
                    : [
                        'Primary 1' => ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education'],
                        'Primary 2' => ['English Language', 'Mathematics', 'Literacy II', 'Numeracy II', 'Religious Education'],
                    'Primary 3' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education'],
                    'Primary 4' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 5' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 6' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 7' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                ],
            ],
            'secondary', 'o-level' => [
                'classes' => [
                    ['name' => 'Senior 1'], ['name' => 'Senior 2'], ['name' => 'Senior 3'], ['name' => 'Senior 4'],
                ],
                'subjects' => [
                    'Senior 1' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education', 'Physical Education', 'ICT', 'Entrepreneurship'],
                    'Senior 2' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education', 'Physical Education', 'ICT', 'Entrepreneurship'],
                    'Senior 3' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education'],
                    'Senior 4' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education'],
                ],
            ],
            'a-level' => [
                'classes' => [
                    ['name' => 'Senior 5'], ['name' => 'Senior 6'],
                ],
                'subjects' => [
                    'Senior 5' => ['General Paper', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Geography', 'History', 'Economics', 'Divinity', 'Literature in English', 'Computer Science', 'Entrepreneurship', 'Fine Art', 'Music', 'Physical Education', 'French', 'Kiswahili', 'Luganda'],
                    'Senior 6' => ['General Paper', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Geography', 'History', 'Economics', 'Divinity', 'Literature in English', 'Computer Science', 'Entrepreneurship', 'Fine Art', 'Music', 'Physical Education', 'French', 'Kiswahili', 'Luganda'],
                ],
            ],
            'mixed' => [
                'classes' => [
                    ['name' => 'Baby Class'], ['name' => 'Middle Class'], ['name' => 'Top Class'],
                    ['name' => 'Primary 1'], ['name' => 'Primary 2'], ['name' => 'Primary 3'],
                    ['name' => 'Primary 4'], ['name' => 'Primary 5'], ['name' => 'Primary 6'], ['name' => 'Primary 7'],
                    ['name' => 'Senior 1'], ['name' => 'Senior 2'], ['name' => 'Senior 3'], ['name' => 'Senior 4'],
                ],
                'subjects' => [
                    'Baby Class'   => ['English Rhymes & Stories', 'Numbers & Counting', 'Creative Play'],
                    'Middle Class' => ['English Language Basics', 'Numeracy', 'Art & Craft'],
                    'Top Class'    => ['Pre-Literacy', 'Pre-Numeracy', 'Social Habits', 'Creative Arts'],
                    'Primary 1' => ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education'],
                    'Primary 2' => ['English Language', 'Mathematics', 'Literacy II', 'Numeracy II', 'Religious Education'],
                    'Primary 3' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education'],
                    'Primary 4' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 5' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 6' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 7' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Senior 1' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education', 'Physical Education', 'ICT', 'Entrepreneurship'],
                    'Senior 2' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education', 'Physical Education', 'ICT', 'Entrepreneurship'],
                    'Senior 3' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education'],
                    'Senior 4' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Religious Education'],
                ],
            ],
            default => [
                'classes' => [['name' => 'Primary 1']],
                'subjects' => ['Primary 1' => ['English Language', 'Mathematics']],
            ],
        };
    }
}
