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

use App\Models\StudentAcademic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\CoAdminInviteMail;
use App\Models\OnboardingSession;
use App\Services\OnboardingNameListExtractor;
use App\Services\OnboardingEngine;
use App\Services\ToshiActionService;
use App\Support\UserProvisioning;

class AgentToshi extends Component
{
    use WithFileUploads;

    /** @var array<string, string> */
    protected $listeners = [
        'manual-onboarding-finished' => 'syncOnboardingAfterManualWizard',
    ];

    /**
     * Map of tool name (as stored in pendingToolConfirm) to Tool class FQCN.
     * The only source of truth for what happens when a user confirms a tool.
     */
    private const TOOL_CLASS_MAP = [
        'toolCreateExam'          => \App\AiAgents\Tools\CreateExamTool::class,
        'toolAddParent'           => \App\AiAgents\Tools\AddParentTool::class,
        'toolEnterMark'           => \App\AiAgents\Tools\EnterMarkTool::class,
        'toolAddStudent'          => \App\AiAgents\Tools\AddStudentTool::class,
        'toolAddTeacher'          => \App\AiAgents\Tools\AddTeacherTool::class,
        'toolAddCoAdmin'          => \App\AiAgents\Tools\AddCoAdminTool::class,
        'toolCreateFee'           => \App\AiAgents\Tools\CreateFeeTool::class,
        'toolCreateTerm'          => \App\AiAgents\Tools\CreateTermTool::class,
        'toolRecordPayment'       => \App\AiAgents\Tools\RecordPaymentTool::class,
        'toolRecordAttendance'    => \App\AiAgents\Tools\RecordAttendanceTool::class,
        'toolRecordBulkAttendance' => \App\AiAgents\Tools\RecordBulkAttendanceTool::class,
        'toolAssignTeacher'       => \App\AiAgents\Tools\AssignTeacherTool::class,
        'toolCreateSubject'       => \App\AiAgents\Tools\CreateSubjectTool::class,
        'toolSeedDefaultGrading'  => \App\AiAgents\Tools\SeedDefaultGradingTool::class,
        'toolSetGradingScale'     => \App\AiAgents\Tools\SetGradingScaleTool::class,
        'toolSetCurriculum'       => \App\AiAgents\Tools\SetCurriculumTool::class,
        'toolCreateStream'        => \App\AiAgents\Tools\CreateStreamTool::class,
        'toolAssignStudentsToStream' => \App\AiAgents\Tools\AssignStudentsToStreamTool::class,
        // School Admin Batch 1 — school_comms (create/update only; lists need no confirm)
        'toolCreateNotice'        => \App\AiAgents\Tools\CreateNoticeTool::class,
        'toolUpdateNotice'        => \App\AiAgents\Tools\UpdateNoticeTool::class,
        'toolCreateEvent'         => \App\AiAgents\Tools\CreateEventTool::class,
        'toolUpdateEvent'         => \App\AiAgents\Tools\UpdateEventTool::class,
        'toolCreateHoliday'       => \App\AiAgents\Tools\CreateHolidayTool::class,
        'toolUpdateHoliday'       => \App\AiAgents\Tools\UpdateHolidayTool::class,
        // School Admin Batch 2 — academics ops (writes only; lists need no confirm)
        'toolCreateTimetableSlot' => \App\AiAgents\Tools\CreateTimetableSlotTool::class,
        'toolUpdateTimetableSlot' => \App\AiAgents\Tools\UpdateTimetableSlotTool::class,
        'toolCreateHomework'      => \App\AiAgents\Tools\CreateHomeworkTool::class,
        'toolUpdateHomework'      => \App\AiAgents\Tools\UpdateHomeworkTool::class,
        'toolApproveHomework'     => \App\AiAgents\Tools\ApproveHomeworkTool::class,
        'toolRejectHomework'      => \App\AiAgents\Tools\RejectHomeworkTool::class,
        'toolUpdateStudentHomework' => \App\AiAgents\Tools\UpdateStudentHomeworkTool::class,
        // TeacherOperationsAgent confirm keys (ug5)
        'toolTeacherMarkAttendance' => \App\AiAgents\Tools\Teacher\MarkAttendanceTool::class,
        'toolTeacherEnterMarks' => \App\AiAgents\Tools\Teacher\EnterMarksTool::class,
        'toolTeacherCreateLessonPlan' => \App\AiAgents\Tools\Teacher\CreateLessonPlanTool::class,
        'toolTeacherCreateAssignment' => \App\AiAgents\Tools\Teacher\CreateAssignmentTool::class,
        'toolTeacherCreateHomework' => \App\AiAgents\Tools\Teacher\CreateHomeworkTool::class,
        'toolTeacherApplyLeave' => \App\AiAgents\Tools\Teacher\ApplyLeaveTool::class,
        'toolTeacherCreateClassWallPost' => \App\AiAgents\Tools\Teacher\CreateClassWallPostTool::class,
        'toolTeacherCreateTask' => \App\AiAgents\Tools\Teacher\CreateTaskTool::class,
        // AccountantOperationsAgent confirm keys (ug11)
        'toolAccountantRecordPayment' => \App\AiAgents\Tools\Accountant\RecordPaymentTool::class,
        'toolAccountantManagePayroll' => \App\AiAgents\Tools\Accountant\ManagePayrollTool::class,
        'toolAccountantCreateTask' => \App\AiAgents\Tools\Accountant\CreateTaskTool::class,
        // LibrarianOperationsAgent confirm keys (ug8) — writes only
        'toolLibrarianManageBooks' => \App\AiAgents\Tools\Librarian\ManageBooksTool::class,
        'toolLibrarianManageBookCategories' => \App\AiAgents\Tools\Librarian\ManageBookCategoriesTool::class,
        'toolLibrarianManageLending' => \App\AiAgents\Tools\Librarian\ManageLendingTool::class,
        'toolLibrarianCreateTask' => \App\AiAgents\Tools\Librarian\CreateTaskTool::class,
        // ReceptionistOperationsAgent confirm keys (ug10) — writes only
        'toolReceptionistManageVisitorLog' => \App\AiAgents\Tools\Receptionist\ManageVisitorLogTool::class,
        'toolReceptionistManageCallLog' => \App\AiAgents\Tools\Receptionist\ManageCallLogTool::class,
        'toolReceptionistManagePostalRecord' => \App\AiAgents\Tools\Receptionist\ManagePostalRecordTool::class,
        'toolReceptionistCreateTask' => \App\AiAgents\Tools\Receptionist\CreateTaskTool::class,
        // StudentOperationsAgent confirm keys (ug6) — writes only
        'toolStudentSubmitAssignment' => \App\AiAgents\Tools\Student\SubmitAssignmentTool::class,
        'toolStudentSubmitHomework' => \App\AiAgents\Tools\Student\SubmitHomeworkTool::class,
        'toolStudentManageTasks' => \App\AiAgents\Tools\Student\ManageTasksTool::class,
        'toolStudentManageConversations' => \App\AiAgents\Tools\Student\ManageConversationsTool::class,
    ];

    public $step = 0;
    public $visible = false;
    public $maximized = false;
    public $attachment = null;
    public $messages = [];
    public $input = '';
    public $schoolId = null;
    public $preview = [];
    public $substep = 0;
    public $desktopMode = false; // internal sub-step within a step

    // Collected data across steps
    public $schoolName = '';
    public $schoolType = 'primary';
    public $schoolLevel = '';   // o-level, a-level, both
    public $schoolGender = '';  // boys, girls, mixed
    public $schoolCountry = '';
    public $schoolEmail = '';
    public $schoolPhone = '';
    public $ministryCode = '';
    /** @var string|null null = not asked; '' = skipped; value = UNEB centre number */
    public $unebCenterNumber = null;
    public $curriculum = 'uneb';
    public $suggestedPlanId = null;
    public $schoolPayPassword = '';
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
    public $showStudentForm = false;
    public $studentFormName = '';
    public $studentFormClass = '';
    public $studentFormStream = '';
    public $studentFormType = '';
    public $studentFormParent = '';
    public $studentFormParentPhone = '';
    public $showFeeForm = false;
    public $feeFormName = '';
    public $feeFormAmount = '';
    public $feeFormLevel = '';
    public $feeFormClass = '';
    public $feeFormTerm = '';
    public $showExamForm = false;
    public $examFormTerm = '';
    public $examFormType = '';
    public $examFormStatus = '';
    public $examFormLevel = '';
    public $examFormClass = '';
    public $examFormSubject = '';
    public $examFormTeacher = '';
    public $terms = [];
    public $fees = [];
    public $exams = [];
    public $selectedPlanId = null;
    public $reviewData = []; // populated when entering review step

    public $steps = [
        'school_info',
        'country',
        'emis',
        'uneb_center',
        'admin_account',
        'co_admin_invite',
        'academic_year',
        'standards',
        'subjects',
        'teachers',
        'students',
        'terms',
        'fees',
        'exams',
        'whatsapp_verify',
        'school_pay',
        'plan_selection',
        'review',
    ];

    /**
     * Steps that must be completed before a school can function.
     * Optional steps can be skipped and completed later via the admin panel.
     */
    public $mandatorySteps = [
        'school_info',
        'country',
        'admin_account',
        'academic_year',
        'standards',
        'subjects',
        'terms',
        'plan_selection',
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

    /** Tier 2 tool confirmation: pending tool name + args before execution */
    public $pendingToolConfirm = null;   // ['tool' => 'toolCreateExam', 'args' => [...]]

    /** Recently cancelled tool confirmation — shown as cancelled state briefly */
    public $cancelledToolConfirm = null; // same format as pendingToolConfirm

    /** Capabilities for the current user, loaded from ToshiActionService. */
    public array $capabilities = [];

    /** When true, the UI shows Yes/No buttons instead of requiring text input. */
    public bool $awaitingConfirm = false;

    /** Multi-step plan state (Phase 3). Non-null when a plan is displayed. */
    public ?array $planSteps = null;

    /** Index of the step currently being executed (-1 = idle). */
    public int $planCurrentStep = -1;

    /**
     * When a plan step triggers a tool confirmation, we store the step index
     * here so confirmYes() can resume the plan after the user confirms.
     * -1 means no plan step is awaiting confirmation.
     */
    public int $planPendingConfirmStep = -1;

    /** Whether a streaming placeholder message has been placed. */
    public bool $streamingMessagePlaced = false;

    /** Unique ID for the current streaming session (used as x-stream key). */
    public string $streamingMessageId = '';

    /**
     * Map of tool names to present-tense action verbs for the plan card.
     */
    private const PLAN_ACTION_VERBS = [
        'toolAddStudent'          => 'Adding student...',
        'toolRecordAttendance'    => 'Recording attendance...',
        'toolRecordBulkAttendance' => 'Recording attendance...',
        'toolCreateExam'          => 'Creating exam...',
        'toolCreateSubject'       => 'Creating subject...',
        'toolAddTeacher'          => 'Adding teacher...',
        'toolAddCoAdmin'          => 'Adding admin...',
        'toolAddParent'           => 'Adding parent...',
        'toolCreateFee'           => 'Creating fee...',
        'toolCreateTerm'          => 'Creating term...',
        'toolRecordPayment'       => 'Recording payment...',
        'toolSetCurriculum'       => 'Setting curriculum...',
        'toolEnterMark'           => 'Entering marks...',
        'toolAssignTeacher'       => 'Assigning teacher...',
        'toolSeedDefaultGrading'  => 'Configuring grading...',
        'toolSetGradingScale'     => 'Configuring grading...',
    ];

    /**
     * Evocative "thinking" verbs that cycle during idle processing.
     */
    private const THINKING_VERBS = [
        'Exploring...',
        'Navigating...',
        'Pondering...',
        'Preparing...',
        'Consulting records...',
        'Looking up data...',
    ];

    /**
     * Derive the action verb to show in the plan card footer,
     * based on whichever step is currently in_progress.
     */
    public function getPlanExecutingVerb(): string
    {
        if ($this->planSteps === null || $this->planCurrentStep < 0) {
            // No active step — pick a thinking verb based on the clock
            $idx = now()->second % count(self::THINKING_VERBS);
            return self::THINKING_VERBS[$idx];
        }

        $step = $this->planSteps[$this->planCurrentStep] ?? [];
        $tool = $step['tool'] ?? '';

        return self::PLAN_ACTION_VERBS[$tool] ?? 'Processing...';
    }

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
            // Always reconcile "Completing Setup" against OnboardingStepsService so a
            // manual-wizard finish (or any path that completed steps outside Toshi)
            // exits stale complete-mode instead of showing 1/18 + red ❌ forever.
            if ($this->scope === 'school' && $user->school_id && $user->usergroup_id === 3) {
                $this->reconcileSchoolOnboardingMode($user);
                if ($this->mode === 'assistant' && empty($this->messages)) {
                    $this->botSay($this->getAssistantGreeting());
                }
                return;
            }

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
            if (request()->boolean('toshi_onboarding') || session()->pull('open_toshi_onboarding')) {
                $this->visible = true;
                $this->maximized = true;
            }
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
        $this->schoolCountry = $data['schoolCountry'] ?? '';
        $this->schoolEmail = $data['schoolEmail'] ?? '';
        $this->schoolPhone = $data['schoolPhone'] ?? '';
        $this->ministryCode = $data['ministryCode'] ?? '';
        $this->unebCenterNumber = array_key_exists('unebCenterNumber', $data) ? $data['unebCenterNumber'] : null;
        $this->curriculum = $data['curriculum'] ?? 'uneb';
        $this->schoolPayPassword = $data['schoolPayPassword'] ?? '';
        $this->adminName = $data['adminName'] ?? '';
        $this->adminEmail = $data['adminEmail'] ?? '';
        $this->adminPassword = $data['adminPassword'] ?? '';
        $this->coAdminName = $data['coAdminName'] ?? '';
        $this->coAdminEmail = $data['coAdminEmail'] ?? '';
        $this->coAdminUserId = $data['coAdminUserId'] ?? null;
        $this->academicYearLabel = $data['academicYearLabel'] ?? '';
        $this->selectedPlanId = $data['selectedPlanId'] ?? null;
        $this->suggestedPlanId = $data['suggestedPlanId'] ?? null;
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
            'schoolCountry'     => $this->schoolCountry,
            'schoolEmail'       => $this->schoolEmail,
            'schoolPhone'       => $this->schoolPhone,
            'ministryCode'      => $this->ministryCode,
            'unebCenterNumber'  => $this->unebCenterNumber,
            'curriculum'        => $this->curriculum,
            'schoolPayPassword' => $this->schoolPayPassword,
            'adminName'         => $this->adminName,
            'adminEmail'        => $this->adminEmail,
            'adminPassword'     => $this->adminPassword,
            'coAdminName'       => $this->coAdminName,
            'coAdminEmail'      => $this->coAdminEmail,
            'coAdminUserId'     => $this->coAdminUserId,
            'academicYearLabel' => $this->academicYearLabel,
            'selectedPlanId'    => $this->selectedPlanId,
            'suggestedPlanId'   => $this->suggestedPlanId,
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
        $school = \App\Models\School::find($sid);
        if (!$school) {
            $this->botSay("I can't detect your school setup right now.");
            return;
        }

        $incomplete = \App\Services\OnboardingStepsService::incompleteSteps($school, auth()->id());
        if (! \App\Helpers\OnboardingHelper::hasMissingSteps($school->id, auth()->id())) {
            $this->exitCompletingSetupMode('✅ Everything looks set up! Your school is ready to go.');
            return;
        }

        $this->botSay("I found **" . count($incomplete) . "** thing" . (count($incomplete) > 1 ? 's' : '') . " to set up:");
        foreach ($incomplete as $step) {
            $this->botSay("  ❌ " . ($step['icon'] ?? '') . ' ' . $step['label']);
        }

        $first = $incomplete[0];
        $this->jumpToIncompleteOnboardingStep($first['key']);
        if ($first['key'] === 'plan_selection') {
            $this->promptPlanSelection();
        } else {
            $this->botSay(self::onboardingPromptForStep($first['key']));
        }
    }

    /**
     * Livewire event from ManualOnboardingWizard — leave Completing Setup when manual path finishes.
     */
    public function syncOnboardingAfterManualWizard(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->school_id || (int) $user->usergroup_id !== 3) {
            return;
        }

        $this->schoolId = $user->school_id;
        $this->scope = 'school';
        $this->reconcileSchoolOnboardingMode($user);
    }

    /**
     * Align mode + progress UI with OnboardingStepsService (same source as the manual wizard).
     * The "1/18 Required" counter is $step+1 / count($steps) in create-flow indices — it only
     * makes sense while mode === complete. Once onboarding is done, switch to assistant.
     */
    private function reconcileSchoolOnboardingMode($user): void
    {
        $school = \App\Models\School::find($user->school_id);
        if (! $school) {
            return;
        }

        $this->schoolId = $user->school_id;
        $incomplete = \App\Services\OnboardingStepsService::incompleteSteps($school, $user->id);

        if (! \App\Helpers\OnboardingHelper::hasMissingSteps($school->id, $user->id)) {
            if ($this->mode !== 'assistant' || $this->step !== 99) {
                $this->exitCompletingSetupMode();
            }

            return;
        }

        // Still incomplete — enter/refresh complete mode from the shared step list.
        if ($this->mode !== 'complete') {
            $this->mode = 'complete';
            $this->messages = [];
            $name = $school->name ?: 'your school';
            $this->botSay("Hello! Let's finish setting up **{$name}** on KlassApp.");
            $this->detectMissingSteps();
            $this->persistState();

            return;
        }

        // Already in complete mode with stale step index — jump to first incomplete key.
        $first = $incomplete[0];
        $this->jumpToIncompleteOnboardingStep($first['key']);
        $this->persistState();
    }

    /**
     * Exit Completing Setup UI (mode badge, 1/18 bar, red ❌ checklist messages).
     */
    private function exitCompletingSetupMode(?string $message = null): void
    {
        $this->mode = 'assistant';
        $this->step = 99;
        $this->substep = 0;
        $this->actionStep = null;
        $this->actionSubstep = 0;
        $this->messages = [];
        if ($message) {
            $this->botSay($message);
            $this->botSay('If you need help with anything, just ask.');
        } else {
            $this->botSay($this->getAssistantGreeting());
        }
        $this->persistState();
    }

    /**
     * Map OnboardingStepsService keys onto create-flow step indices / action flows.
     */
    private function jumpToIncompleteOnboardingStep(string $key): void
    {
        $this->actionStep = null;
        $this->actionSubstep = 0;

        $actionMap = [
            'curriculum' => 'onboarding_curriculum',
            'country' => 'onboarding_country',
            'school_category' => 'onboarding_school_category',
            'emis' => 'onboarding_emis',
            'uneb_center' => 'onboarding_uneb_center',
            'plan_selection' => 'onboarding_plan_selection',
        ];

        if (isset($actionMap[$key])) {
            $this->actionStep = $actionMap[$key];
            $this->actionSubstep = 0;
            if ($key === 'curriculum') {
                $this->curriculum = '';
            }
            return;
        }

        $map = [
            'school_name' => 'school_info',
            'academic_year' => 'academic_year',
            'standards' => 'standards',
            'subjects' => 'subjects',
            'teachers' => 'teachers',
            'terms' => 'terms',
            'fees' => 'fees',
            'whatsapp_verify' => 'whatsapp_verify',
        ];

        $stepName = $map[$key] ?? null;
        if ($stepName === null) {
            return;
        }

        $idx = array_search($stepName, $this->steps, true);
        if ($idx !== false) {
            $this->step = $idx;
            $this->substep = 0;
        }
    }

    /**
     * Get the conversational prompt for a given onboarding step key.
     */
    private static function onboardingPromptForStep(string $key): string
    {
        return match ($key) {
            'school_name' => "What's the real name of your school? (You can keep refining it later.)",
            'curriculum' => "Which curriculum does your school follow? I recommend **UNEB** for most Ugandan schools. Reply with UNEB, Cambridge, Montessori, or Other.",
            'school_category' => "What type of school is this? Pick a category below — it sets default classes, subjects, and grading (all editable later). You can also reply with **Primary**, **Nursery only**, **Primary + Nursery**, **O-Level**, or **O-Level + A-Level**.",
            'country' => "Which country is your school in? (e.g. **Uganda**, Kenya, Tanzania)",
            'emis' => "What's your school's **EMIS / Ministry code**? This is required for Ugandan schools.",
            'uneb_center' => "If you have a **UNEB centre number**, share it now — or type **skip** (optional).",
            'academic_year' => "Let's set your academic year next — classes and terms depend on it. Is **" . date('Y') . "** correct? (yes / no)",
            'standards'  => "Let's set up classes. What classes does your school have?",
            'subjects'   => "Let's set up subjects per class.",
            'teachers'   => "Let's add teachers. Paste their names (one per line) or type 'skip'.",
            'terms'      => "Let's set up academic terms.",
            'fees'       => "Let's add fee categories. Type a fee name or 'skip'.",
            'whatsapp_verify' => "Let's verify your WhatsApp number for school notifications.",
            'plan_selection' => "Let's pick a KlassApp plan for your school.",
            default      => "Let's continue setting up.",
        };
    }

    public function show() { $this->visible = true; $this->maximized = false; }
    public function hide() { $this->visible = false; $this->maximized = false; }
    public function maximize() { $this->maximized = true; $this->visible = false; }
    public function restore() { $this->maximized = false; $this->visible = true; }

    public function resetSchoolOnboarding()
    {
        $this->substep = 0;
        $this->actionStep = null;
        $this->actionSubstep = 0;
        $this->actionData = [];
        $this->subjectInputs = [];
        $this->teacherList = [];
        $this->studentList = [];
        $this->fees = [];
        $this->terms = [];
        $this->exams = [];
        $this->selectedPlanId = null;
        $this->hasNursery = null;
        $this->schoolName = '';
        $this->schoolType = '';
        $this->schoolLevel = '';
        $this->standards = [];
        $this->subjects = [];
        $this->streamClassIndex = 0;
        $this->deleteDraft();

        $user = auth()->user();
        if ($user && $user->school_id) {
            $this->schoolId = $user->school_id;
            $schoolName = \App\Models\School::find($this->schoolId)->name ?? 'your school';

            // Reset to the first school-level step (standards/classes)
            $this->step = 5;
            $this->mode = 'complete';
            $this->messages = [['role' => 'bot', 'text' => "Let's start fresh setting up **{$schoolName}** from the beginning."]];
            $this->botSay("First, let's set up your classes. What classes does your school have? (e.g. Baby Class, Top Class, P1-P7, S1-S6)");
            return;
        }

        $this->mode = 'assistant';
        $this->step = 99;
        $this->messages = [['role' => 'bot', 'text' => 'Setup restarted. How can I help you?']];
    }

    // ── Tool display helpers (Phase B) ──
    /**
     * Map internal tool names to user-friendly labels + icons.
     */
    private function getToolDisplay(string $tool): array
    {
        $map = [
            'toolAddStudent'       => ['label' => 'Add Student',       'icon' => '👤'],
            'toolAddTeacher'       => ['label' => 'Add Teacher',       'icon' => '👨‍🏫'],
            'toolAddCoAdmin'       => ['label' => 'Add Co-Admin',      'icon' => '👤'],
            'toolCreateExam'       => ['label' => 'Create Exam',       'icon' => '📝'],
            'toolCreateFee'        => ['label' => 'Create Fee',        'icon' => '💵'],
            'toolCreateTerm'       => ['label' => 'Create Term',       'icon' => '📅'],
            'toolRecordPayment'    => ['label' => 'Record Payment',    'icon' => '💳'],
            'toolRecordAttendance' => ['label' => 'Record Attendance', 'icon' => '📋'],
            'toolRecordBulkAttendance' => ['label' => 'Bulk Attendance', 'icon' => '📋'],
            'toolAssignTeacher'    => ['label' => 'Assign Teacher',    'icon' => '👨‍🏫'],
            'toolCreateSubject'    => ['label' => 'Create Subject',    'icon' => '📖'],
            'toolAddParent'        => ['label' => 'Add Parent',        'icon' => '👪'],
            'toolEnterMark'        => ['label' => 'Enter Mark',        'icon' => '📊'],
            'toolSeedDefaultGrading' => ['label' => 'Setup Grading',   'icon' => '⚙️'],
            'toolSetGradingScale'  => ['label' => 'Set Grading Scale', 'icon' => '📏'],
            'toolSetCurriculum'    => ['label' => 'Set Curriculum',   'icon' => '📚'],
            'toolCreateNotice'     => ['label' => 'Create Notice',    'icon' => '📢'],
            'toolUpdateNotice'     => ['label' => 'Update Notice',    'icon' => '📢'],
            'toolCreateEvent'      => ['label' => 'Create Event',     'icon' => '📅'],
            'toolUpdateEvent'      => ['label' => 'Update Event',     'icon' => '📅'],
            'toolCreateHoliday'    => ['label' => 'Create Holiday',   'icon' => '🎄'],
            'toolUpdateHoliday'    => ['label' => 'Update Holiday',   'icon' => '🎄'],
            'toolCreateTimetableSlot' => ['label' => 'Create Timetable Slot', 'icon' => '🗓️'],
            'toolUpdateTimetableSlot' => ['label' => 'Update Timetable Slot', 'icon' => '🗓️'],
            'toolCreateHomework'   => ['label' => 'Create Homework',  'icon' => '📝'],
            'toolUpdateHomework'   => ['label' => 'Update Homework',  'icon' => '📝'],
            'toolApproveHomework'  => ['label' => 'Approve Homework', 'icon' => '✅'],
            'toolRejectHomework'   => ['label' => 'Reject Homework',  'icon' => '❌'],
            'toolUpdateStudentHomework' => ['label' => 'Check Student Homework', 'icon' => '✔️'],
        ];
        return $map[$tool] ?? ['label' => 'Execute Action', 'icon' => '⚡'];
    }

    /**
     * Extract readable parameters from tool args for display.
     * Filters out internal/technical keys, returns label-value pairs.
     */
    private function getToolArgsDisplay(array $args): array
    {
        $labelMap = [
            'name' => 'Name', 'class' => 'Class', 'stream' => 'Stream',
            'type' => 'Type', 'date' => 'Date', 'status' => 'Status',
            'amount' => 'Amount', 'fee' => 'Fee', 'term' => 'Term',
            'student' => 'Student', 'teacher' => 'Teacher', 'subject' => 'Subject',
            'parent' => 'Parent', 'parentPhone' => 'Parent Phone',
            'section' => 'Section', 'grade' => 'Grade', 'score' => 'Score',
            'student_id' => 'Student ID', 'teacher_id' => 'Teacher ID',
            'class_id' => 'Class ID', 'subject_id' => 'Subject ID',
            'start_date' => 'Start Date', 'end_date' => 'End Date',
            'firstname' => 'First Name', 'lastname' => 'Last Name',
            'email' => 'Email', 'phone' => 'Phone',
            'students' => 'Students', 'teachers' => 'Teachers',
            'gradeDefinitions' => 'Grade Levels',
            'grade_definition' => 'Grade Levels',
        ];
        $result = [];
        foreach ($args as $key => $value) {
            // Format complex values (arrays, JSON strings) as human-readable summaries
            $formatted = $this->formatArgValue($value);
            if ($formatted === null) continue; // skip empty/complex unrepresentable
            $label = $labelMap[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
            $result[] = ['label' => $label, 'value' => $formatted];
        }
        return $result;
    }

    /**
     * Format a tool argument value for human-readable display.
     * Arrays and JSON strings are summarised; primitives pass through.
     * Returns null if the value should be omitted (empty after formatting).
     */
    private function formatArgValue(mixed $value): ?string
    {
        // Already a simple scalar
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            $s = trim((string) $value);
            // Try decoding a JSON string — if it decodes to an array, format it
            if (str_starts_with($s, '[') || str_starts_with($s, '{')) {
                $decoded = json_decode($s, true);
                if (is_array($decoded)) {
                    return $this->formatStructuredValue($decoded);
                }
            }
            return $s ?: null;
        }

        if (is_array($value)) {
            return $this->formatStructuredValue($value);
        }

        return null;
    }

    /**
     * Format a structured value (array of grade definitions, etc.) as a
     * compact human-readable summary.
     */
    private function formatStructuredValue(array $data): string
    {
        // Grade definitions: array of {grade, min, max} objects
        if (!empty($data) && isset($data[0]) && is_array($data[0])) {
            $first = $data[0];
            if (isset($first['grade']) && (isset($first['min']) || isset($first['min_score']))) {
                $parts = [];
                foreach ($data as $g) {
                    $grade = $g['grade'] ?? '';
                    $lo = $g['min'] ?? $g['min_score'] ?? 0;
                    $hi = $g['max'] ?? $g['max_score'] ?? 100;
                    $parts[] = "{$grade}: {$lo}–{$hi}%";
                }
                return count($parts) . ' levels — ' . implode(', ', $parts);
            }
            // Generic array of objects: show count + first item as preview
            $preview = json_encode($data[0], JSON_UNESCAPED_UNICODE);
            return count($data) . ' item(s) — e.g. ' . (strlen($preview) > 60 ? substr($preview, 0, 60) . '…' : $preview);
        }

        // Flat key-value object: "key: value, key: value"
        if ($this->isAssociative($data)) {
            $parts = [];
            foreach ($data as $k => $v) {
                $parts[] = $k . ': ' . (is_array($v) ? json_encode($v) : $v);
            }
            return implode(', ', $parts);
        }

        // Flat list: "item1, item2, …"
        $filtered = array_filter($data, fn($v) => !is_array($v));
        if (!empty($filtered)) {
            return implode(', ', $filtered);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function isAssociative(array $array): bool
    {
        if (empty($array)) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }

    // ── Button-driven confirm/edit ──
    public function confirmYes()
    {
        $this->awaitingConfirm = false;

        // Check for pending tool confirmation first
        if ($this->pendingToolConfirm !== null) {
            $tool = $this->pendingToolConfirm['tool'];
            $args = $this->pendingToolConfirm['args'];
            $wasPlanStep = $this->planPendingConfirmStep >= 0;
            $planStepIdx = $this->planPendingConfirmStep;
            $this->pendingToolConfirm = null;
            $this->planPendingConfirmStep = -1;

            // Single path: re-invoke the same Tool class via bypassConfirm
            // so the exact handle() logic that built the preview also writes
            $result = $this->executeConfirmedTool($tool, $args);
            $this->botSay($result);

            // If this was a plan step awaiting confirmation, resume the plan
            if ($wasPlanStep && $this->planSteps !== null && $planStepIdx >= 0) {
                $success = str_starts_with($result, '✅') || str_starts_with($result, '⚠️');
                $this->planSteps[$planStepIdx]['status'] = $success ? 'completed' : 'failed';

                // A failed step stops the plan
                if (!$success) {
                    $succeeded = collect($this->planSteps)->where('status', 'completed')->count();
                    $failed = collect($this->planSteps)->where('status', 'failed')->count();
                    $this->botSay("⚠️ Plan stopped: step " . ($planStepIdx + 1) . "/" . count($this->planSteps) . " failed. **{$succeeded}** succeeded, **{$failed}** failed.");
                    $this->planSteps = null;
                    $this->planCurrentStep = -1;
                    return;
                }

                $next = $planStepIdx + 1;
                if ($next < count($this->planSteps)) {
                    $this->planCurrentStep = $next;
                    $this->planSteps[$next]['status'] = 'in_progress';
                    $this->dispatch('toshi-run-plan-step');
                } else {
                    // All done — show summary
                    $succeeded = collect($this->planSteps)->where('status', 'completed')->count();
                    $failed = collect($this->planSteps)->where('status', 'failed')->count();
                    if ($failed === 0) {
                        $this->botSay("✅ All **{$succeeded}** steps completed successfully.");
                    } else {
                        $this->botSay("⚠️ Plan complete: **{$succeeded}** succeeded, **{$failed}** failed.");
                    }
                    $this->planSteps = null;
                    $this->planCurrentStep = -1;
                }
            }
            return;
        }

        $this->callStepHandler('yes');
    }
    public function confirmNo()
    {
        $this->awaitingConfirm = false;

        // Check for pending tool confirmation
        if ($this->pendingToolConfirm !== null) {
            // Store cancelled state for UI (cleared on next send)
            $this->cancelledToolConfirm = $this->pendingToolConfirm;
            $this->pendingToolConfirm = null;

            // Audit trail — log every cancellation so the picture is complete
            // (not just what executed, but what was proposed and rejected).
            $school = $this->schoolId ? \App\Models\School::find($this->schoolId) : null;
            \App\Services\ToshiAuditService::logCancellation(
                user: auth()->user() ?? auth('web')->user(),
                school: $school,
                toolName: $this->cancelledToolConfirm['tool'],
                arguments: $this->cancelledToolConfirm['args'],
            );

            // If this cancellation was part of a multi-step plan, cancel the
            // entire plan — a rejected step breaks the batch contract.
            if ($this->planPendingConfirmStep >= 0 && $this->planSteps !== null) {
                $this->planSteps = null;
                $this->planCurrentStep = -1;
                $this->planPendingConfirmStep = -1;
                $this->botSay('Plan cancelled because a step was rejected.');
            } else {
                $this->botSay('Cancelled. No changes were made.');
            }
            return;
        }

        $this->callStepHandler('no');
    }

    // ── Multi-Step Plan Execution (Phase 3) ──

    /**
     * Start executing the plan. Runs one step and dispatches a browser event
     * so Alpine auto-triggers the next step — each step is its own Livewire
     * request, giving the user a real-time view of progress.
     */
    public function confirmPlan()
    {
        if (empty($this->planSteps)) {
            return;
        }

        $this->planCurrentStep = 0;
        $this->planSteps[0]['status'] = 'in_progress';

        // Dispatch a browser event — Alpine will wait briefly then call
        // executeNextPlanStep, making the actual tool call.
        $this->dispatch('toshi-run-plan-step');
    }

    /**
     * Execute the current plan step, respecting each tool's confirmation gate.
     *
     * If the tool returns a __tier2_confirm payload, the plan pauses and the
     * existing confirmation card flow takes over. After the user confirms,
     * confirmYes() resumes the plan.
     *
     * Called from Alpine after a short delay so the UI re-renders first.
     */
    public function executeNextPlanStep()
    {
        $steps = $this->planSteps;
        $i = $this->planCurrentStep;

        if ($steps === null || $i < 0 || $i >= count($steps)) {
            $this->planCurrentStep = -1;
            return;
        }

        $step = $steps[$i];

        // Call the tool WITHOUT bypassConfirm so write tools still show
        // their confirmation card. The plan card is a visual preview, not
        // a shortcut past safety checks.
        $class = self::TOOL_CLASS_MAP[$step['tool']] ?? null;
        $result = '❌ Unknown tool.';

        if ($class !== null) {
            $tool = app($class);
            $request = new \Laravel\Ai\Tools\Request($step['args']);
            $result = $tool->handle($request);

            // Check if the tool requested confirmation
            $decoded = json_decode($result, true);
            if (isset($decoded['__tier2_confirm']) && $decoded['__tier2_confirm']) {
                // Pause the plan — confirmYes() will resume it after the user acts
                $this->planPendingConfirmStep = $i;
                $this->pendingToolConfirm = [
                    'tool' => $decoded['tool'],
                    'args' => $decoded['args'],
                ];
                $this->awaitingConfirm = true;
                $messageId = md5($decoded['preview'] ?? '');
                session(['toshi_pending_confirm_' . $messageId => $this->pendingToolConfirm]);
                $this->botSay($decoded['preview'] . "\n\nUse the buttons below to confirm or cancel.");
                return;
            }
        }

        $success = str_starts_with($result, '✅') || str_starts_with($result, '⚠️');
        $this->planSteps[$i]['status'] = $success ? 'completed' : 'failed';

        // A failed step must stop the plan — don't advance to the next step
        // (the step's ❌ result message is already visible in the chat).
        if (!$success) {
            $succeeded = collect($this->planSteps)->where('status', 'completed')->count();
            $failed = collect($this->planSteps)->where('status', 'failed')->count();
            $this->botSay("⚠️ Plan stopped: step " . ($i + 1) . "/" . count($steps) . " failed. **{$succeeded}** succeeded, **{$failed}** failed.");
            $this->planSteps = null;
            $this->planCurrentStep = -1;
            return;
        }

        // If there are more steps, advance and dispatch
        $next = $i + 1;
        if ($next < count($steps)) {
            $this->planCurrentStep = $next;
            $this->planSteps[$next]['status'] = 'in_progress';
            $this->dispatch('toshi-run-plan-step');
        } else {
            // All done — show summary and clean up
            $label = $step['label'] ?? $step['tool'];
            $icon = $success ? '✅' : '❌';
            $this->botSay("{$icon} Step " . ($i + 1) . "/" . count($steps) . ": {$label}");

            $succeeded = collect($this->planSteps)->where('status', 'completed')->count();
            $failed = collect($this->planSteps)->where('status', 'failed')->count();

            if ($failed === 0) {
                $this->botSay("✅ All **{$succeeded}** steps completed successfully.");
            } else {
                $this->botSay("⚠️ Plan complete: **{$succeeded}** succeeded, **{$failed}** failed.");
            }

            $this->planSteps = null;
            $this->planCurrentStep = -1;
        }
    }

    /**
     * Cancel the currently displayed plan.
     */
    public function cancelPlan()
    {
        $this->planSteps = null;
        $this->planCurrentStep = -1;
        $this->planPendingConfirmStep = -1;
        $this->botSay('Plan cancelled. No changes were made.');
    }

    // ── Streaming ──

    /**
     * Handle a streaming query via the Laravel AI SDK's Agent::stream().
     *
     * Creates a placeholder message, pipes text deltas to the browser
     * in real-time via Livewire's stream(), and returns the full response
     * (or a tier 2 confirmation JSON) after the stream completes.
     */
    private function handleStreamedQuery(
        \App\AiAgents\ToshiSdkV2Service $service,
        \App\Models\User $user,
        string $text,
        array $history,
        \App\Enums\ToshiScope $scope = \App\Enums\ToshiScope::School,
    ): ?string {
        // Generate a stable stream ID for the x-stream key
        $this->streamingMessageId = 'ts-' . md5($text . microtime());
        $this->streamingMessagePlaced = true;

        // Place an empty message — Alpine will fill it via x-stream
        $messageIdx = count($this->messages);
        $this->messages[] = [
            'role'      => 'bot',
            'text'      => '',
            '_streamId' => $this->streamingMessageId,
        ];

        // Run the stream; each chunk is flushed to the browser immediately
        $response = $service->askStreamed(
            $user,
            $this->schoolId,
            $text,
            $history,
            fn (string $chunk) => $this->stream($this->streamingMessageId, $chunk, false),
            $scope,
        );

        if ($response !== null) {
            // Finalize the placeholder with the full text
            $this->messages[$messageIdx]['text'] = $response;
        }

        return $response;
    }

    /**
     * Re-invoke the same Tool class that generated the confirmation preview,
     * with the user-confirmed args, via bypassConfirm so it writes to the DB.
     *
     * Returns the tool's output string (same format the LLM would see).
     */
    private function executeConfirmedTool(string $toolName, array $args): string
    {
        $class = self::TOOL_CLASS_MAP[$toolName] ?? null;
        if ($class === null) {
            return "❌ Unknown tool: {$toolName}";
        }

        $tool = app($class);
        $request = new \Laravel\Ai\Tools\Request($args);

        // Bypass the confirmation gate so the tool executes instead of
        // returning a __tier2_confirm payload. Reset immediately after
        // so the flag can't leak into unrelated requests.
        \App\Services\ToshiActionService::$bypassConfirm = true;
        try {
            $result = $tool->handle($request);

            // Self-verification: tools that implement VerifiableTool re-read
            // their write from the DB immediately. If the record isn't found
            // (race condition, persistence gap, or silent failure), the user
            // sees a caution instead of a false success.
            if (str_starts_with($result, '✅') && $tool instanceof \App\AiAgents\Concerns\VerifiableTool) {
                $verification = $tool->verify($request);
                if (!$verification['verified']) {
                    $result = '⚠️ The action appeared to succeed, but verification could not confirm the data was saved. '
                        . ($verification['message'] ?? 'You may want to check manually.');
                }
            }

            // Audit trail — log every write action at the single execution point.
            // School may be null during initial create-onboarding before schoolId is set.
            // Tier-2 ConfirmsBeforeWrite: confirming user is the approver (self-approve OK —
            // same person may fill both fields, but both must be populated on confirm).
            $school = $this->schoolId ? \App\Models\School::find($this->schoolId) : null;
            $actor = auth()->user() ?? auth('web')->user();
            \App\Services\ToshiAuditService::logExecution(
                user: $actor,
                school: $school,
                toolName: $toolName,
                arguments: $args,
                result: $result,
                approver: $actor,
                actingUser: $actor,
            );

            return $result;
        } finally {
            \App\Services\ToshiActionService::$bypassConfirm = false;
        }
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
    public function skipStep()
    {
        $this->awaitingConfirm = false;
        $this->callStepHandler('skip');
    }

    /**
     * Switch between modes (called from the mode dropdown).
     */
    public function switchMode(string $targetMode): void
    {
        if ($targetMode === 'assistant') {
            $this->mode = 'assistant';
            $this->step = 99;
            $this->actionStep = null;
            $this->actionSubstep = 0;
            $this->actionData = [];
            $this->messages = [];
            $this->botSay($this->getAssistantGreeting());
            $this->deleteDraft();
        } elseif ($targetMode === 'create' && $this->scope === 'platform') {
            $this->resetOnboarding(true);
        }
    }

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

        $this->actionData['teachers'][] = $name;
        if ($email) {
            $this->actionData['teacherEmails'][$name] = $email;
        }
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
        if (empty($this->actionData['teachers']) && empty($this->teacherList)) {
            $this->showTeacherForm = false;
            $this->botSay("No teachers added. You can add them later from the admin panel.");
            $this->substep = 0;
            $this->advance();
            return;
        }
        $this->showTeacherForm = false;
        $this->teacherList = !empty($this->actionData['teachers']) ? $this->actionData['teachers'] : $this->teacherList;
        $this->teacherPhones = $this->actionData['teacherPhones'] ?? [];

        $preview = implode(', ', array_slice($this->teacherList, 0, 3));
        $this->botSay("**" . count($this->teacherList) . "** teacher(s) added: {$preview}" . (count($this->teacherList) > 3 ? '...' : '') . ".");
        $this->substep = 0;
        $this->advance();
    }

    // ── Student Form ──

    public function showStudentFormFn()
    {
        $this->awaitingConfirm = false;
        $this->actionData['students'] = $this->actionData['students'] ?? [];
        $this->showStudentForm = true;
        $this->studentFormName = '';
        $this->studentFormClass = '';
        $this->studentFormStream = '';
        $this->studentFormType = '';
        $this->studentFormParent = '';
        $this->studentFormParentPhone = '';
        $this->substep = 6;
        $this->botSay("Let's add students. Use the form below to add each student.");
    }

    public function saveStudent()
    {
        $raw = trim($this->studentFormName);
        $class = trim($this->studentFormClass);
        if ($raw === '') return;
        if ($class === '') {
            $this->botSay("Please select a class for **{$raw}**.");
            return;
        }

        // Parse optional gender suffix from name — same pattern as actionAddStudent()
        $gender = null;
        $name = $raw;
        if (preg_match('/^(.+?)\s*\((\s*male\s*|\s*female\s*)\)\s*$/i', $raw, $m)) {
            $name = trim($m[1]);
            $gender = strtolower(trim($m[2]));
        }

        $entry = ['name' => $name, 'gender' => $gender, 'class' => $class, 'stream' => $this->studentFormStream, 'type' => $this->studentFormType];
        if ($this->studentFormParent) $entry['parent'] = trim($this->studentFormParent);
        if ($this->studentFormParentPhone) $entry['parent_phone'] = trim($this->studentFormParentPhone);

        $this->actionData['students'][] = $entry;
        $this->studentFormName = '';
        $this->studentFormClass = '';
        $this->studentFormParent = '';
        $this->studentFormParentPhone = '';

        $count = count($this->actionData['students']);
        $this->botSay("Added **{$name}** ({$count} so far). Add another or click **Continue**.");
    }

    function doneStudents()
    {
        if (empty($this->actionData['students']) && empty($this->studentList)) {
            $this->showStudentForm = false;
            $this->botSay("No students added. You can add them later from the admin panel.");
            $this->substep = 0;
            $this->advance();
            return;
        }
        $this->showStudentForm = false;
        // Preserve full student records (name + class + stream) for commitAll()
        $this->studentList = !empty($this->actionData['students'])
            ? collect($this->actionData['students'])->pluck('name')->values()->toArray()
            : $this->studentList;
        // actionData['students'] is kept intact for commitAll() to read class assignments
        $count = count($this->studentList);
        $this->botSay("**{$count}** student(s) added.");
        $this->substep = 0;
        $this->advance();
    }

    // ── Fee Form ──

    public function showFeeFormFn()
    {
        $this->awaitingConfirm = false;
        $this->actionData['fees'] = $this->actionData['fees'] ?? [];
        $this->showFeeForm = true;
        $this->feeFormName = '';
        $this->feeFormAmount = '';
        $this->feeFormLevel = '';
        $this->feeFormClass = '';
        $this->feeFormTerm = '';
        $this->substep = 6;
        $this->botSay("Let's add fee categories. Use the form below to add each fee.");
    }

    public function saveFee()
    {
        $name = trim($this->feeFormName);
        $amount = trim($this->feeFormAmount);
        if ($name === '') return;
        if ($amount === '' || !is_numeric($amount) || (float)$amount <= 0) {
            $this->botSay("Please enter a valid amount for **{$name}**.");
            return;
        }

        $this->actionData['fees'][] = [
            'name' => $name,
            'amount' => $amount,
            'level' => $this->feeFormLevel,
            'class' => $this->feeFormClass,
            'term' => $this->feeFormTerm,
        ];
        $this->feeFormName = '';
        $this->feeFormAmount = '';
        $this->feeFormLevel = '';
        $this->feeFormClass = '';
        $this->feeFormTerm = '';

        $count = count($this->actionData['fees']);
        $this->botSay("Added **{$name}** at " . number_format((float)$amount, 0) . " UGX ({$count} so far). Add another or click **Continue**.");
    }

    public function removeFee(int $index): void
    {
        if (isset($this->actionData['fees'][$index])) {
            unset($this->actionData['fees'][$index]);
            $this->actionData['fees'] = array_values($this->actionData['fees']);
        }
    }

    public function doneFees()
    {
        if (empty($this->actionData['fees'])) {
            $this->showFeeForm = false;
            $this->botSay("No fees added. You can add them later from the admin panel.");
            $this->substep = 0;
            $this->advance();
            return;
        }
        $this->showFeeForm = false;
        $this->fees = collect($this->actionData['fees'])->pluck('name')->values()->toArray();
        $this->botSay("**" . count($this->fees) . "** fee categor" . (count($this->fees) === 1 ? 'y' : 'ies') . " saved.");
        $this->substep = 0;
        $this->advance();
    }

    // ── Exam Form ──

    public function showExamFormFn()
    {
        $this->awaitingConfirm = false;
        $this->actionData['exams'] = $this->actionData['exams'] ?? [];
        $this->showExamForm = true;
        $this->examFormTerm = '';
        $this->examFormType = '';
        $this->examFormStatus = '';
        $this->examFormLevel = '';
        $this->examFormClass = '';
        $this->examFormSubject = '';
        $this->examFormTeacher = '';
        $this->substep = 6;
        $this->botSay("Let's add exams. Use the form below to add each exam.");
    }

    public function saveExam()
    {
        $term = trim($this->examFormTerm);
        $type = trim($this->examFormType);
        if ($term === '' || $type === '') return;

        $this->actionData['exams'][] = [
            'term' => $term,
            'type' => $type,
            'status' => $this->examFormStatus,
            'level' => $this->examFormLevel,
            'class' => $this->examFormClass,
            'subject' => $this->examFormSubject,
            'teacher' => $this->examFormTeacher,
        ];
        $this->examFormTerm = '';
        $this->examFormType = '';
        $this->examFormStatus = '';
        $this->examFormLevel = '';
        $this->examFormClass = '';
        $this->examFormSubject = '';
        $this->examFormTeacher = '';

        $count = count($this->actionData['exams']);
        $this->botSay("Added **{$type}** for **{$term}** ({$count} so far). Add another or click **Continue**.");
    }

    public function removeExam(int $index): void
    {
        if (isset($this->actionData['exams'][$index])) {
            unset($this->actionData['exams'][$index]);
            $this->actionData['exams'] = array_values($this->actionData['exams']);
        }
    }

    public function doneExams()
    {
        if (empty($this->actionData['exams'])) {
            $this->showExamForm = false;
            $this->botSay("No exams added. You can add them later from the admin panel.");
            $this->substep = 0;
            $this->advance();
            return;
        }
        $this->showExamForm = false;
        $this->exams = collect($this->actionData['exams'])->pluck('type')->values()->toArray();
        $count = count($this->exams);
        $this->botSay("**{$count}** exam(s) saved.");
        $this->substep = 0;
        $this->advance();
    }

    public function editBeforeCommit()
    {
        $this->reviewData = [];
        $this->botSay("No problem! Tell me what needs to change and we'll go back to fix it. | For example: **students**, **teachers**, **classes**, **subjects**, **fees**, **exams**, **school info**, **admin**, **co-admin**, **terms**, **plan**, or **WhatsApp**");
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
            $this->botSay("First, what's the name of your school?");
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
            $nameRows = $this->extractNamesFromFile($this->attachment->getRealPath(), $ext);
            $plainNames = array_map(fn($r) => $r['name'], $nameRows);

            if (in_array($stepName, ['teachers', 'students']) && count($nameRows) > 0) {
                if ($stepName === 'teachers') {
                    $this->teacherList = array_values(array_unique($plainNames));
                    $this->actionData['teachers'] = $this->teacherList;
                    $this->showTeacherForm = false;
                    // Also try extracting teacher-subject-class links from the same file
                    $linksResult = $this->extractTeacherLinksFromFile($this->attachment->getRealPath(), $ext);
                    $links = $linksResult['links'] ?? $linksResult;
                    if (count($links) > 0) {
                        $this->teacherLinks = $links;
                        foreach (($linksResult['phones'] ?? []) as $teacherName => $phone) {
                            $this->teacherPhones[$teacherName] = $phone;
                        }
                    }
                } else {
                    $this->studentList = array_values(array_unique($plainNames));
                    // Try to extract optional LIN (Learner Identification Number) from the file
                    $linValues = $this->extractColumnFromFile($this->attachment->getRealPath(), $ext, ['lin', 'learner_id', 'learner_identification_number', 'emis_lin']);
                    $this->actionData['students'] = array_map(fn($i, $r) => [
                        'name' => $r['name'],
                        'gender' => null,
                        'class' => $r['class'] ?? '',
                        'stream' => $r['stream'] ?? '',
                        'parent' => $r['parent'] ?? '',
                        'parent_phone' => $r['parent_phone'] ?? '',
                        'lin' => $linValues[$i] ?? '',
                    ], array_keys($nameRows), $nameRows);
                    $this->showStudentForm = false;
                }
                $label = $stepName === 'teachers' ? 'teachers' : 'students';
                $this->userSay("📎 Uploaded " . count($nameRows) . " {$label} from file");
                $linked = !empty($this->teacherLinks) ? " with **" . count($this->teacherLinks) . "** subject/class assignments" : "";
                $preview = implode(', ', array_slice($plainNames, 0, 5));
                $this->botSay("Parsed **" . count($nameRows) . "** {$label}{$linked}: {$preview}" . (count($nameRows) > 5 ? '...' : '') . " | Is this correct? (yes / no)");
                $this->awaitingConfirm = true;
                $this->substep = 1;
            } elseif ($stepName === 'standards' && count($nameRows) > 0) {
                $this->standards = array_map(fn($r) => ['name' => $r['name']], $nameRows);
                $this->userSay("📎 Uploaded " . count($nameRows) . " class(es) from file");
                $this->botSay("Parsed **" . count($nameRows) . "** class(es) from your file. Continue?");
            } elseif ($stepName === 'subjects' && count($nameRows) > 0) {
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
                        $this->subjects = [$firstClass => $plainNames];
                        $this->botSay("Parsed **" . count($plainNames) . "** subject(s). Continue?");
                    } else {
                        $this->botSay("Please set up classes first, then upload subjects.");
                    }
                }
            } elseif ($stepName === 'fees' && count($plainNames) > 0) {
                $this->actionData['fees'] = array_map(fn($n) => ['name' => $n, 'amount' => '', 'level' => '', 'class' => '', 'term' => ''], $plainNames);
                $this->showFeeForm = false;
                $this->userSay("📎 Uploaded " . count($plainNames) . " fees from file");
                $preview = implode(', ', array_slice($plainNames, 0, 5));
                $this->botSay("Parsed **" . count($plainNames) . "** fees: {$preview}" . (count($plainNames) > 5 ? '...' : '') . " | Is this correct? (yes / no)");
                $this->awaitingConfirm = true;
                $this->substep = 1;
            } elseif ($stepName === 'exams' && count($plainNames) > 0) {
                $this->actionData['exams'] = array_map(fn($n) => ['term' => '', 'type' => $n, 'status' => '', 'level' => '', 'class' => '', 'subject' => '', 'teacher' => ''], $plainNames);
                $this->showExamForm = false;
                $this->userSay("📎 Uploaded " . count($plainNames) . " exams from file");
                $preview = implode(', ', array_slice($plainNames, 0, 5));
                $this->botSay("Parsed **" . count($plainNames) . "** exams: {$preview}" . (count($plainNames) > 5 ? '...' : '') . " | Is this correct? (yes / no)");
                $this->awaitingConfirm = true;
                $this->substep = 1;
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

    private function nameListExtractor(): OnboardingNameListExtractor
    {
        return app(OnboardingNameListExtractor::class);
    }

    /**
     * @deprecated Prefer OnboardingNameListExtractor; kept as a thin Toshi wrapper.
     */
    private function extractNamesFromFile(string $path, string $ext): array
    {
        return $this->nameListExtractor()->extractNamesFromFile($path, $ext);
    }

    /**
     * @deprecated Prefer OnboardingNameListExtractor; kept as a thin Toshi wrapper.
     */
    private function extractColumnFromFile(string $path, string $ext, array $aliases): array
    {
        return $this->nameListExtractor()->extractColumnFromFile($path, $ext, $aliases);
    }

    /**
     * @return list<array{teacher: string, subject: string, class: string, phone: string}>|array{links: list, phones: array}
     * @deprecated Prefer OnboardingNameListExtractor; kept as a thin Toshi wrapper.
     */
    private function extractTeacherLinksFromFile(string $path, string $ext): array
    {
        $classNames = collect($this->standards ?? [])->pluck('name')->filter()->values()->all();
        $result = $this->nameListExtractor()->extractTeacherLinksFromFile(
            $path,
            $ext,
            $this->teacherList ?? [],
            $classNames
        );

        // Back-compat: historical callers expected a flat list of links.
        return $result;
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
            return "  • **".($u->displayName ?: $u->name)."** ({$class}) — {$kid}";
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

        $this->botSay("👤 **".($student->displayName ?: $student->name)."**\n"
            . "🆔 KlassApp ID: {$klassappId}\n"
            . "📚 Class: {$className}\n"
            . "💰 Fees: {$feeStatus}\n"
            . "📅 Attendance records: {$attendanceCount}"
            . $marksLines
            . "\n\nGo to *Students* in the sidebar for full management.");
    }

    private function botSay(string $message)
    {
        // Safety filter: never display raw __tier2_confirm JSON payloads as bot messages.
        // These are internal protocol payloads that should only reach the confirmation
        // card handler. If one leaks here (from the LLM echoing tool output, the agent
        // framework returning it as final text, or any other path), discard silently.
        $trimmed = ltrim($message);
        if (str_starts_with($trimmed, '{"__tier2_confirm')) {
            \Illuminate\Support\Facades\Log::warning('botSay suppressed raw __tier2_confirm JSON', [
                'preview' => json_decode($trimmed, true)['preview'] ?? '(unknown)',
            ]);
            return;
        }

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
        // Trigger the step handler to auto-populate defaults (e.g. terms, subjects).
        // Handlers must check for empty text and show a prompt instead of validating.
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
            'country'         => 'handleCountry',
            'emis'            => 'handleEmis',
            'uneb_center'     => 'handleUnebCenter',
            'admin_account'   => 'handleAdminAccount',
            'co_admin_invite' => null, // buttons only, no text handler on entry
            'academic_year'   => 'handleAcademicYear',
            'standards'       => 'handleStandards',
            'subjects'        => 'handleSubjects',
            'teachers'        => 'handleTeachers',
            'students'        => 'handleStudents',
            'terms'           => 'handleTerms',
            'fees'            => 'handleFees',
            'exams'           => 'handleExams',
            'whatsapp_verify' => 'handleWhatsAppVerify',
            'school_pay'      => 'handleSchoolPay',
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
        $query = \App\Models\School::where('name', trim($name));
        if ($this->mode === 'complete' && $this->schoolId) {
            $query->where('id', '!=', $this->schoolId);
        }

        return $query->exists();
    }

    // ── Assistant mode — keyword router first (zero cost), then LLM, then fallback ──
    private function handleAssistantQuery(string $text): void
    {
        // Reset cross-request flags — Livewire properties persist between queries
        $this->streamingMessagePlaced = false;
        $this->streamingMessageId = '';

        $lower = strtolower($text);

        // Check for pending Tier 2 tool confirmation — handled by buttons now
        if ($this->pendingToolConfirm !== null) {
            // Text-based fallback still works, but buttons are the primary path
            $yes = in_array($lower, ['yes', 'y', 'yeah', 'confirm', 'proceed', 'do it']);
            if ($yes) {
                $tool = $this->pendingToolConfirm['tool'];
                $args = $this->pendingToolConfirm['args'];
                $this->pendingToolConfirm = null;
                $this->awaitingConfirm = false;

                // Single path: re-invoke the same Tool class via bypassConfirm
                $result = $this->executeConfirmedTool($tool, $args);
                $this->botSay($result);
            } elseif (in_array($lower, ['no', 'n', 'cancel', 'stop'])) {
                // Audit trail — log text-based cancellation too
                $cancelledTool = $this->pendingToolConfirm['tool'] ?? null;
                $cancelledArgs = $this->pendingToolConfirm['args'] ?? [];
                $school = $this->schoolId ? \App\Models\School::find($this->schoolId) : null;
                if ($cancelledTool) {
                    \App\Services\ToshiAuditService::logCancellation(
                        user: auth()->user() ?? auth('web')->user(),
                        school: $school,
                        toolName: $cancelledTool,
                        arguments: $cancelledArgs,
                    );
                }
                $this->pendingToolConfirm = null;
                $this->awaitingConfirm = false;
                $this->botSay('Cancelled. No changes were made.');
            }
            // If neither yes nor no, do nothing — buttons handle it
            return;
        }

        // Fast path: keyword router runs first regardless (zero API cost)
        if ($this->tryKeywordRoute($lower, $text)) {
            return;
        }

        $user = auth()->user();
        $history = array_slice($this->messages, -20);
        $handled = false;

        // SDK v2 path: Laravel AI SDK agent stack (gated by feature flag)
        if (config('toshi.sdk_v2_enabled', false)) {
            try {
                $service = app(\App\AiAgents\ToshiSdkV2Service::class);
                // Platform scope for siteadmin; school scope otherwise (default).
                // getRoleCapabilities() scope is advisory UI context — the real
                // auth boundary is ToshiAvailabilityGate via isAvailable($scope).
                $sdkScope = $this->scope === 'platform'
                    ? \App\Enums\ToshiScope::Platform
                    : \App\Enums\ToshiScope::School;
                if ($service->isAvailable($user, $this->schoolId, $sdkScope) && $service->consumeBudget($user, $this->schoolId)) {

                    // Streaming path: pushes tokens to the browser in real-time
                    // via Livewire's stream() + Alpine x-stream.
                    if (config('toshi.streaming_enabled', false)) {
                        $response = $this->handleStreamedQuery($service, $user, $text, $history, $sdkScope);
                    } else {
                        $response = $service->ask($user, $this->schoolId, $text, $history, $sdkScope);
                    }

                    if ($response !== null) {
                        // Check if response is a Tier 2 confirmation prompt
                        // (write tools on the SDK v2 path return this via the
                        // side-channel in ToshiSdkV2Service::ask())
                        $decoded = json_decode($response, true);
                        if (isset($decoded['__tier2_confirm']) && $decoded['__tier2_confirm']) {
                            $this->pendingToolConfirm = [
                                'tool' => $decoded['tool'],
                                'args' => $decoded['args'],
                            ];
                            $this->awaitingConfirm = true;
                            $messageId = md5($decoded['preview'] ?? '');
                            session(['toshi_pending_confirm_' . $messageId => $this->pendingToolConfirm]);
                            $this->botSay($decoded['preview'] . "\n\nUse the buttons below to confirm or cancel.");
                        } elseif (!$this->streamingMessagePlaced) {
                            // Safety guard: never display raw JSON payloads as bot messages.
                            // The __tier2_confirm JSON should only reach the confirmation
                            // card handler above. If it reaches here, something went wrong
                            // with the side-channel — discard silently.
                            if (str_starts_with(ltrim($response), '{"__tier2_confirm')) {
                                $this->botSay('I need to confirm with you first. Please check the confirmation card.');
                                return;
                            }
                            // Only botSay if streaming didn't already place the response
                            $this->botSay($response);
                        }
                        \Log::info('Assistant path: SDK v2', ['user_id' => $user->id]);
                        return;
                    }
                } else {
                    $remaining = $service->getRemainingBudget($user, $this->schoolId);
                    if ($remaining <= 0) {
                        $this->botSay("You've reached your query limit for this period. I can still answer common questions using the keyword router.");
                        \Log::info('Assistant path: SDK v2 budget exhausted', ['user_id' => $user->id]);
                        return;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('SDK v2 failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
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
            if (auth()->user()?->usergroup_id === 1) {
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

        // Skip student lookup if the text looks like an action request (let LarAgent handle it)
        $actionVerbs = ['create ', 'add ', 'make ', 'record ', 'enter ', 'assign ', 'register ', 'new '];
        $startsWithAction = false;
        foreach ($actionVerbs as $verb) {
            if (str_starts_with(strtolower($original), $verb)) {
                $startsWithAction = true;
                break;
            }
        }

        if ($this->schoolId && !$startsWithAction && $this->tryStudentLookup($original)) {
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
        if ($this->actionStep === 'onboarding_curriculum') {
            $this->actionOnboardingCurriculum($text);
            return;
        }
        if ($this->actionStep === 'onboarding_school_category') {
            $this->actionOnboardingSchoolCategory($text);
            return;
        }
        if ($this->actionStep === 'onboarding_country') {
            $this->actionOnboardingCountry($text);
            return;
        }
        if ($this->actionStep === 'onboarding_emis') {
            $this->actionOnboardingEmis($text);
            return;
        }
        if ($this->actionStep === 'onboarding_uneb_center') {
            $this->actionOnboardingUnebCenter($text);
            return;
        }
        if ($this->actionStep === 'onboarding_plan_selection') {
            $this->actionOnboardingPlanSelection($text);
            return;
        }

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
     * Cancel the current action flow, or a pending tool confirmation.
     * Called internally (no args) to reset the action flow, or from the
     * confirmation UI (with messageId) to discard a pending confirmation.
     */
    public function cancelAction(string $messageId = ''): void
    {
        if ($messageId) {
            session()->forget('toshi_pending_confirm_' . $messageId);
            $this->cancelledToolConfirm = $this->pendingToolConfirm;
            $this->pendingToolConfirm = null;
            $this->awaitingConfirm = false;
            $this->botSay('Cancelled. No changes were made.');
            return;
        }
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
            // Collect name — optional gender can be appended as "(male)" or "(female)"
            $raw = trim($text);
            if (strlen($raw) < 3) {
                $this->botSay('Please enter a valid name (at least 3 characters).');
                return;
            }
            $gender = null;
            $name = $raw;
            if (preg_match('/^(.+?)\s*\((\s*male\s*|\s*female\s*)\)\s*$/i', $raw, $m)) {
                $name = trim($m[1]);
                $gender = strtolower(trim($m[2]));
            }
            $this->actionData['name'] = $name;
            $this->actionData['gender'] = $gender;
            $displayLine = "Name: **{$name}**" . ($gender ? " ({$gender})" : '');
            $this->actionSubstep = 1;
            $this->botSay("{$displayLine}. | What class should they join? (type the class name, or **skip** to assign later)");
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
                $this->botSay("Let's start over. What is the student's full name? (you can add gender in parentheses, e.g. \"John (male)\")");
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
        if (!empty($this->actionData['gender'])) $parts[] = "Gender: **{$this->actionData['gender']}**";
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
            $this->actionData['student_name'] = $student->displayName ?: $student->name;
            $this->actionSubstep = 1;
            $this->botSay("Student: **".($student->displayName ?: $student->name)."**. | What is the payment amount in UGX?");
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

        // Clear cancelled state on new message
        $this->cancelledToolConfirm = null;

        $this->userSay($text);
        $this->input = '';

        // Global commands — work regardless of mode
        $lower = strtolower($text);
        if (in_array($lower, ['reset', 'restart', 'start over'])) {
            $this->resetSchoolOnboarding();
            return;
        }

        // Slash commands
        if (str_starts_with($text, '/')) {
            $cmd = strtolower(trim(explode(' ', $text)[0]));
            match ($cmd) {
                '/agent', '/ask' => $this->switchMode('assistant'),
                '/create', '/school' => $this->switchMode('create'),
                '/help' => $this->botSay(
                    "**Available commands:**\n" .
                    ($this->scope === 'platform' ? "• `/create` or `/school` — Start creating a new school\n" : "") .
                    "• `/agent` or `/ask` — Switch to Q&A mode\n" .
                    "• `/status` — Show current mode and school info\n" .
                    "• `/help` — Show this message\n" .
                    "• `reset` or `restart` — Restart current flow"
                ),
                '/status' => $this->botSay(
                    "**Status:**\n" .
                    "• Mode: **{$this->mode}**\n" .
                    "• Scope: **{$this->scope}**\n" .
                    "• School: " . (\App\Models\School::find($this->schoolId)->name ?? '—') . "\n" .
                    "• Step: " . ($this->steps[$this->step] ?? '—')
                ),
                default => $this->botSay("Unknown command `{$cmd}`. Try `/help` for available commands."),
            };
            return;
        }

        // Active action flow (multi-step, e.g. add student, enter marks)
        if ($this->actionStep) {
            $this->handleActionFlow($text);
            return;
        }

        // Assistant mode — school is set up, Toshi can answer questions
        if ($this->mode === 'assistant') {
            // Phase 3: Check if the query is a multi-step batch.
            // If so, show a plan card instead of going straight to the orchestrator.
            $plan = app(\App\Services\ToshiPlanService::class)->generatePlan($text);
            if ($plan !== null && count($plan['steps']) >= 2) {
                $this->planSteps = $plan['steps'];
                $this->botSay("I'll perform these **" . count($plan['steps']) . "** steps. Please review and confirm:");
                return;
            }

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
            // Check if user wants to resume onboarding (setup mode)
            $isSetupIntent = preg_match('/\b(setup|set.?up|finish|onboard|continue setting|resume|what.?next|next step)\b/i', $text)
                && !preg_match('/\b(add|create|record|mark|enter)\b.*\b(student|exam|attendance|mark|fee|parent)\b/i', $text);
            if ($isSetupIntent) {
                $school = \App\Models\School::find($this->schoolId);
                if ($school) {
                    $next = \App\Services\OnboardingStepsService::nextIncompleteStep($school, auth()->id());
                    if ($next) {
                        $this->botSay("Let's continue setting up **{$school->name}**. " . self::onboardingPromptForStep($next['key']));
                        return true;
                    }
                }
            }

            // Collecting a school name (complete or create): never treat the answer as
            // student lookup / assistant keyword routing — multi-word names like
            // "Sunrise Primary School" would otherwise hit tryStudentLookup.
            $collectingSchoolName = ($this->steps[$this->step] ?? null) === 'school_info'
                && (int) $this->substep === 0;

            if (! $collectingSchoolName) {
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
        }

        // Handle draft resume commands — full reset to clear ALL stale data
        if ($this->draftSessionId && in_array(strtolower($text), ['reset', 'restart', 'start over'])) {
            $this->resetOnboarding();
            // Override the greeting so it's contextual for a draft restart
            $this->messages = [];
            $this->botSay("Restarting from scratch. First, what's the name of your school?");
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
            'country'         => $this->handleCountry($text),
            'emis'            => $this->handleEmis($text),
            'uneb_center'     => $this->handleUnebCenter($text),
            'admin_account'   => $this->handleAdminAccount($text),
            'co_admin_invite' => $this->handleCoAdminInvite($text),
            'academic_year'   => $this->handleAcademicYear($text),
            'standards'       => $this->handleStandards($text),
            'subjects'        => $this->handleSubjects($text),
            'teachers'        => $this->handleTeachers($text),
            'students'        => $this->handleStudents($text),
            'terms'           => $this->handleTerms($text),
            'fees'            => $this->handleFees($text),
            'exams'           => $this->handleExams($text),
            'whatsapp_verify' => $this->handleWhatsAppVerify($text),
            'school_pay'      => $this->handleSchoolPay($text),
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

        // Complete-mode plan step: persist CurrentPlan + Subscription immediately
        if ($this->mode === 'complete' && $this->schoolId) {
            $this->persistSelectedPlan($this->schoolId, $this->selectedPlanId);
            $this->botSay("**{$plan->name}** plan selected and saved.");
            $this->actionStep = null;
            $this->actionSubstep = 0;
            $this->detectMissingSteps();
            return;
        }

        $this->botSay("**{$plan->name}** plan selected. | Review your setup next.");
        $this->advance();
    }

    private function handlePlanSelection(string $text)
    {
        if ($text === '' && $this->substep === 0) {
            $this->promptPlanSelection();
            return;
        }

        $plan = \App\Models\Plan::whereRaw('LOWER(name) = ?', [strtolower(trim($text))])->first()
            ?? \App\Models\Plan::whereRaw('LOWER(display_name) = ?', [strtolower(trim($text))])->first();
        if ($plan) {
            $this->selectPlan($plan->id);
            return;
        }
        $this->botSay("Please select a plan using the buttons above (you can pick any tier).");
    }

    private function promptPlanSelection(): void
    {
        $count = 0;
        if ($this->schoolId) {
            $count = \App\Services\OnboardingStepsService::countActiveStudents((int) $this->schoolId);
        }
        if ($count === 0 && (! empty($this->actionData['students']) || ! empty($this->studentList))) {
            $count = count($this->actionData['students'] ?? $this->studentList);
        }

        $suggested = \App\Services\OnboardingStepsService::suggestPlanForStudentCount($count);
        $this->suggestedPlanId = $suggested?->id;

        $suggestLabel = $suggested
            ? "**{$suggested->display_name}**"
            : 'a plan that fits';

        $this->botSay(
            "You currently have **{$count}** active student" . ($count === 1 ? '' : 's') . ". "
            . "Based on that, I suggest {$suggestLabel}. "
            . "You can pick **any** tier below — even one below your student count."
        );
    }

    // ════════════════════════════════════════════════
    //  Step 1: School Info
    //  Uses substep for confirm/edit pattern
    // ════════════════════════════════════════════════
    private function handleSchoolInfo(string $text)
    {
        // Called on step entry via advance() — show prompt, don't validate
        if ($text === '' && $this->substep === 0) {
            return;
        }

        if ($this->substep === 0) {
            // Collecting school name
            $name = $this->validateRequired($text, 'School name', 3);
            if ($name === null) return;

            if ($this->mode === 'complete' && $this->schoolId) {
                // Align with signup: suffix -2/-3 on collision instead of rejecting.
                $unique = app(\App\Services\SchoolSignupBootstrapService::class)->uniqueSchoolName($name);
                if ($unique !== $name
                    && \App\Models\School::where('name', $name)->where('id', '!=', $this->schoolId)->exists()) {
                    $this->schoolName = $unique;
                } else {
                    $this->schoolName = $name;
                }
            } else {
                if ($this->isDuplicateSchool($name)) {
                    $this->botSay("A school named **{$name}** already exists. Please use a different name.");
                    return;
                }
                $this->schoolName = $name;
            }

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

            // Complete mode: persist rename immediately when it differs from placeholder
            if ($this->mode === 'complete' && $this->schoolId && $this->schoolName !== '') {
                if (! $this->persistSchoolNameIfChanged($this->schoolName)) {
                    return;
                }
                $this->botSay("School name updated to **{$this->schoolName}**.");
                $this->actionStep = null;
                $this->substep = 0;
                $this->detectMissingSteps();
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

    // ── Button-driven school category selection (canonical onboarding path) ──
    public function selectSchoolCategory(string $category): void
    {
        $this->persistSchoolCategory($category);
    }

    /**
     * Legacy create-flow alias — maps old nursery/primary/secondary/mixed buttons to category keys.
     *
     * @deprecated Prefer selectSchoolCategory() with SchoolCategorySeeder::CATEGORIES keys.
     */
    public function setSchoolType(string $type, string $level = '', string $gender = '')
    {
        $category = match ($type) {
            'nursery' => 'nursery',
            'primary' => 'primary',
            'secondary' => 'o_level',
            'mixed' => 'o_a_level',
            default => null,
        };

        if ($category === null) {
            $this->botSay('Please choose a school category from the buttons below.');

            return;
        }

        $this->selectSchoolCategory($category);
    }

    // ════════════════════════════════════════════════
    //  Step 2: Admin Account (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleAdminAccount(string $text)
    {
        // Called on step entry via advance() — show prompt, don't validate
        if ($text === '' && $this->substep === 0) {
            return;
        }

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
                if ($this->mode === 'complete' && $this->schoolId) {
                    $this->persistAcademicYearIfMissing($this->academicYearLabel ?: (string) date('Y'));
                    $this->substep = 0;
                    $this->detectMissingSteps();
                    return;
                }
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
            if ($this->mode === 'complete' && $this->schoolId) {
                $this->persistAcademicYearIfMissing($this->academicYearLabel);
                $this->substep = 0;
                $this->detectMissingSteps();
                return;
            }
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    /**
     * Complete-mode curriculum picker (UNEB suggested first, never silently pre-filled).
     */
    private function actionOnboardingCurriculum(string $text): void
    {
        $normalized = strtolower(trim($text));
        $map = [
            'uneb' => 'uneb',
            'uganda' => 'uneb',
            'ncdc' => 'uneb',
            'cambridge' => 'cambridge',
            'montessori' => 'montessori',
            'other' => 'other',
            'custom' => 'other',
        ];

        $choice = $map[$normalized] ?? null;
        if ($choice === null && preg_match('/\b(uneb|cambridge|montessori|other)\b/i', $normalized, $m)) {
            $choice = strtolower($m[1]);
        }

        if ($choice === null) {
            $this->botSay("Please choose **UNEB** (recommended), **Cambridge**, **Montessori**, or **Other**.");
            return;
        }

        $this->curriculum = $choice;
        $school = \App\Models\School::find($this->schoolId);
        if ($school) {
            try {
                app(\App\Services\OnboardingEngine::class)->saveCurriculum($school, $choice);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->botSay($e->getMessage());

                return;
            }
            $school->toshi_enabled = 1;
            $school->save();
        }

        $this->botSay("✅ Curriculum set to **" . strtoupper($choice) . "**.");
        $this->actionStep = null;
        $this->actionSubstep = 0;
        $this->detectMissingSteps();
    }

    private function actionOnboardingCountry(string $text): void
    {
        $this->persistCountryFromInput($text, completeMode: true);
    }

    private function actionOnboardingEmis(string $text): void
    {
        $this->persistEmisFromInput($text, completeMode: true);
    }

    private function actionOnboardingUnebCenter(string $text): void
    {
        $this->persistUnebCenterFromInput($text, completeMode: true);
    }

    private function actionOnboardingPlanSelection(string $text): void
    {
        if ($text === '') {
            $this->promptPlanSelection();
            return;
        }
        $this->handlePlanSelection($text);
    }

    private function handleCountry(string $text): void
    {
        if ($text === '' && $this->substep === 0) {
            $this->botSay(self::onboardingPromptForStep('country'));
            return;
        }
        $this->persistCountryFromInput($text, completeMode: false);
    }

    private function handleEmis(string $text): void
    {
        if ($text === '' && $this->substep === 0) {
            if (! \App\Services\OnboardingStepsService::isUganda($this->schoolCountry)) {
                $this->advance();
                return;
            }
            $this->botSay(self::onboardingPromptForStep('emis'));
            return;
        }
        $this->persistEmisFromInput($text, completeMode: false);
    }

    private function handleUnebCenter(string $text): void
    {
        if ($text === '' && $this->substep === 0) {
            if (! \App\Services\OnboardingStepsService::isUnebCurriculum($this->curriculum)) {
                $this->unebCenterNumber = '';
                $this->advance();
                return;
            }
            $this->botSay(self::onboardingPromptForStep('uneb_center'));
            return;
        }
        $this->persistUnebCenterFromInput($text, completeMode: false);
    }

    private function persistCountryFromInput(string $text, bool $completeMode): void
    {
        $country = $this->validateRequired($text, 'Country', 2);
        if ($country === null) {
            return;
        }

        $this->schoolCountry = $country;

        if ($completeMode && $this->schoolId) {
            $school = \App\Models\School::find($this->schoolId);
            if ($school) {
                try {
                    app(\App\Services\OnboardingEngine::class)->saveCountry($school, $country);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $this->botSay($e->getMessage());

                    return;
                }
            }
            $this->botSay("✅ Country set to **{$country}**.");
            $this->actionStep = null;
            $this->actionSubstep = 0;
            $this->detectMissingSteps();
            return;
        }

        $this->botSay("✅ Country: **{$country}**");
        $this->advance();
    }

    private function persistEmisFromInput(string $text, bool $completeMode): void
    {
        $country = $this->schoolCountry;
        if ($completeMode && $this->schoolId) {
            $country = optional(\App\Models\School::find($this->schoolId))->registration_country ?: $country;
        }

        if (! \App\Services\OnboardingStepsService::isUganda($country)) {
            if ($completeMode) {
                $this->actionStep = null;
                $this->detectMissingSteps();
            } else {
                $this->advance();
            }
            return;
        }

        $code = $this->validateRequired($text, 'EMIS / Ministry code', 2);
        if ($code === null) {
            return;
        }

        $this->ministryCode = $code;

        if ($completeMode && $this->schoolId) {
            $school = \App\Models\School::find($this->schoolId);
            if ($school) {
                try {
                    app(\App\Services\OnboardingEngine::class)->saveEmis($school, $code);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $this->botSay($e->getMessage());

                    return;
                }
            }
            $this->botSay("✅ EMIS code saved: **{$code}**.");
            $this->actionStep = null;
            $this->actionSubstep = 0;
            $this->detectMissingSteps();
            return;
        }

        $this->botSay("✅ EMIS code: **{$code}**");
        $this->advance();
    }

    private function persistUnebCenterFromInput(string $text, bool $completeMode): void
    {
        $curriculum = $this->curriculum;
        if ($completeMode && $this->schoolId) {
            $curriculum = optional(\App\Models\School::find($this->schoolId))->curriculum ?: $curriculum;
        }

        if (! \App\Services\OnboardingStepsService::isUnebCurriculum($curriculum)) {
            $this->unebCenterNumber = '';
            if ($completeMode) {
                $this->actionStep = null;
                $this->detectMissingSteps();
            } else {
                $this->advance();
            }
            return;
        }

        $lower = strtolower(trim($text));
        if (in_array($lower, ['skip', 'later', 'none', 'n/a', 'na', '-'], true)) {
            $this->unebCenterNumber = '';
            $msg = "No problem — UNEB centre number skipped (optional).";
        } else {
            $value = trim($text);
            if ($value === '') {
                $this->botSay("Enter a UNEB centre number, or type **skip**.");
                return;
            }
            $this->unebCenterNumber = $value;
            $msg = "✅ UNEB centre number saved: **{$value}**.";
        }

        if ($completeMode && $this->schoolId) {
            $school = \App\Models\School::find($this->schoolId);
            if ($school) {
                app(\App\Services\OnboardingEngine::class)->saveUnebCenter($school, $this->unebCenterNumber);
            }
            $this->botSay($msg);
            $this->actionStep = null;
            $this->actionSubstep = 0;
            $this->detectMissingSteps();
            return;
        }

        $this->botSay($msg);
        $this->advance();
    }

    private function persistSelectedPlan(int $schoolId, int $planId): void
    {
        $plan = \App\Models\Plan::find($planId);
        if (! $plan) {
            return;
        }

        if ($plan->amount > 0) {
            \App\Services\TrialService::startTrial($schoolId, $planId);
        } else {
            CurrentPlan::updateOrCreate(
                ['school_id' => $schoolId],
                ['plan_id' => $planId]
            );
        }

        $adminUser = auth()->user()
            ?? User::where('school_id', $schoolId)->where('usergroup_id', 3)->first();

        if ($adminUser) {
            Subscription::updateOrCreate(
                ['school_id' => $schoolId, 'user_id' => $adminUser->id],
                [
                    'plan_id' => $planId,
                    'status' => 'pending',
                    'start_date' => now(),
                    'end_date' => now()->addYear(),
                ]
            );
        }
    }

    private function actionOnboardingSchoolCategory(string $text): void
    {
        $this->persistSchoolCategoryFromInput($text);
    }

    private function persistSchoolCategoryFromInput(string $text): void
    {
        $category = strtolower(trim($text));

        // Friendly synonyms → canonical keys
        $synonyms = [
            'nursery only' => 'nursery',
            'nursery' => 'nursery',
            'primary' => 'primary',
            'primary + nursery' => 'primary_nursery',
            'primary_nursery' => 'primary_nursery',
            'primary and nursery' => 'primary_nursery',
            'o level' => 'o_level',
            'o-level' => 'o_level',
            'o_level' => 'o_level',
            'secondary' => 'o_level',
            'o a level' => 'o_a_level',
            'o-level + a-level' => 'o_a_level',
            'o_a_level' => 'o_a_level',
            'mixed' => 'o_a_level',
            'all levels' => 'o_a_level',
        ];

        $category = $synonyms[$category] ?? $category;

        if (! array_key_exists($category, \App\Services\SchoolCategorySeeder::CATEGORIES)) {
            $options = implode(', ', array_values(\App\Services\SchoolCategorySeeder::CATEGORIES));
            $this->botSay("Please choose a school category: **{$options}** — or use the buttons below.");

            return;
        }

        $this->persistSchoolCategory($category);
    }

    private function persistSchoolCategory(string $category): void
    {
        $category = trim($category);
        if (! array_key_exists($category, \App\Services\SchoolCategorySeeder::CATEGORIES)) {
            $this->botSay('Choose a school category from the options below.');

            return;
        }

        $school = \App\Models\School::find($this->schoolId);
        if ($school) {
            try {
                app(\App\Services\OnboardingEngine::class)->saveSchoolCategory($school, $category);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->botSay($e->getMessage());

                return;
            }
        }

        $this->schoolCategory = $category;

        // Sync legacy Toshi state used by curriculumDefaults()
        match ($category) {
            'nursery' => [$this->schoolType = 'nursery', $this->hasNursery = null],
            'primary' => [$this->schoolType = 'primary', $this->hasNursery = false],
            'primary_nursery' => [$this->schoolType = 'primary', $this->hasNursery = true],
            'o_level' => [$this->schoolType = 'secondary', $this->schoolLevel = 'o-level'],
            'o_a_level' => [$this->schoolType = 'mixed', $this->schoolLevel = 'both'],
            default => null,
        };

        // Pre-populate curriculum defaults so tests can assert early
        $defaults = $this->curriculumDefaults();
        $this->standards = $defaults['classes'] ?? [];
        $this->subjects = $defaults['subjects'] ?? [];

        $label = \App\Services\SchoolCategorySeeder::CATEGORIES[$category] ?? ucfirst($category);

        $this->userSay("School category: {$label}");
        $this->botSay("**{$label}** — got it!");

        if ($this->mode === 'complete' && $this->schoolId) {
            $this->botSay('✅ School category saved.');
            $this->actionStep = null;
            $this->actionSubstep = 0;
            $this->substep = 0;
            $this->detectMissingSteps();

            return;
        }

        $this->botSay("Now let's set up the admin account. | What is the admin's email address?");
        $this->substep = 0;
        $this->advance();
    }

    private function persistSchoolNameIfChanged(string $desiredName): bool
    {
        $school = \App\Models\School::find($this->schoolId);
        if (! $school || $desiredName === $school->name) {
            return true;
        }

        try {
            $result = app(\App\Services\OnboardingEngine::class)->saveSchoolName($school, $desiredName);
            $this->schoolName = $result->name;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->botSay($e->getMessage());

            return false;
        }

        return true;
    }

    private function persistAcademicYearIfMissing(string $label): void
    {
        if (! $this->schoolId) {
            return;
        }

        $existing = \App\Models\AcademicYear::where('school_id', $this->schoolId)->first();
        if ($existing) {
            return;
        }

        $school = \App\Models\School::find($this->schoolId);
        if (! $school) {
            return;
        }

        app(\App\Services\OnboardingEngine::class)->saveAcademicYear($school, $label, null, null);
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
                $this->teacherList = $names; // parseNameList already deduplicates
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
        return $this->nameListExtractor()->parseNameList($text);
    }


    // ════════════════════════════════════════════════
    //  Step 9: Teacher-Class-Subject Linking
    // ════════════════════════════════════════════════
    private function handleStudents(string $text)
    {
        // substep 0: collect names or skip
        if ($this->substep === 0) {
            if (trim($text) === '') {
                $this->showStudentFormFn();
                return;
            }
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
        // substep 0: auto-show form
        if ($this->substep === 0) {
            if (trim($text) === '') {
                $this->showFeeFormFn();
                return;
            }
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

        // substep 1: confirm uploaded fees or add more
        if ($this->substep === 1) {
            // If fees were uploaded via file, confirm or reject
            if (!empty($this->actionData['fees'])) {
                $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
                if ($yes) {
                    $this->fees = collect($this->actionData['fees'])->pluck('name')->values()->toArray();
                    $this->botSay("**" . count($this->fees) . "** fee categor" . (count($this->fees) === 1 ? 'y' : 'ies') . " saved.");
                    $this->substep = 0;
                    $this->advance();
                    return;
                }
                $this->showFeeFormFn();
                return;
            }
            // Manual entry flow
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
        // substep 0: auto-show form
        if ($this->substep === 0) {
            if (trim($text) === '') {
                $this->showExamFormFn();
                return;
            }
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

        // substep 1: confirm uploaded exams or add more
        if ($this->substep === 1) {
            if (!empty($this->actionData['exams'])) {
                $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
                if ($yes) {
                    $this->exams = collect($this->actionData['exams'])->pluck('type')->values()->toArray();
                    $this->botSay("**" . count($this->exams) . "** exam(s) saved.");
                    $this->substep = 0;
                    $this->advance();
                    return;
                }
                $this->showExamFormFn();
                return;
            }
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
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipping WhatsApp verification. You can verify later from the admin settings.");
                $this->advance();
                return;
            }
            $phone = $this->schoolPhone ?: '';
            if ($phone) {
                $this->botSay("📱 We'll verify the admin's WhatsApp number: **{$phone}**");
                $this->botSay("Is this the right number? (yes / no)");
                $this->awaitingConfirm = true;
                $this->substep = 1;
                return;
            }
            $this->botSay("What's the admin's WhatsApp number? (e.g., +256701234567) or type 'skip' to skip this step.");
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
            $skip = in_array(strtolower(trim($text)), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipping WhatsApp verification. You can verify later from the admin settings.");
                $this->advance();
                return;
            }
            $phone = $this->normalizeUgandaPhone($text);
            if ($phone === null) return;
            $this->whatsappPhone = $phone;
            $this->botSay("Sending verification code to **{$this->whatsappPhone}**...");
            $this->sendWhatsAppOtp();
            return;
        }

        $otpService = app(\App\Services\WhatsApp\WhatsAppOnboardingOtpService::class);

        // substep 3: verify OTP
        if ($this->substep === 3) {
            $code = trim($text);
            if ($otpService->matches($this->whatsappSentOtp, $code)) {
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
            if ($otpService->matches($this->whatsappSentOtp, $code)) {
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
        $otpService = app(\App\Services\WhatsApp\WhatsAppOnboardingOtpService::class);
        $otp = $otpService->generateCode();
        $this->whatsappSentOtp = $otp;

        // Always show the code in the chat so onboarding is never blocked.
        // The admin is already authenticated — this step collects their WhatsApp
        // for notifications, not identity proof. Code is also sent via API if configured.
        $this->botSay("📱 Your verification code: **{$otp}**");
        $this->botSay("(A message was also sent to your phone if WhatsApp API is configured.)");

        $delivery = $otpService->deliver((string) $this->whatsappPhone, $otp);
        if ($delivery['sent']) {
            $this->botSay("✅ WhatsApp message sent to **{$this->whatsappPhone}**.");
        }

        $this->botSay("Enter the 6-digit code you received:");
        $this->substep = 3;
    }

    // ════════════════════════════════════════════════
    //  Step 14: School Pay (optional)
    // ════════════════════════════════════════════════
    private function handleSchoolPay(string $text)
    {
        // substep 0: ask if they want to configure School Pay now
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none', '']);
            if ($skip || $text === '') {
                $this->botSay("School Pay integration skipped. You can configure it later from the school settings.\n\n> **What is School Pay?** It automatically links parent fee payments to student accounts and sends WhatsApp receipts when a payment is made.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            // They said yes — ask for the API password
            $this->botSay("Great! Enter the **School Pay API password** for this school.\n\nYou can find this in your School Pay school portal at schoolpay.co.ug.");
            $this->substep = 1;
            return;
        }

        if ($this->substep === 1) {
            // Collecting API password
            $password = trim($text);
            if (strlen($password) < 4) {
                $this->botSay("Please enter a valid API password (at least 4 characters).");
                return;
            }
            $this->schoolPayPassword = $password;
            $this->botSay("✅ School Pay API password saved.");
            $this->botSay("Make sure your webhook URL is set in the School Pay portal to:\n`https://klassapp.xyz/api/schoolpay/webhook`\n\nYou can also do this later. Moving to review.");
            $this->substep = 0;
            $this->advance();
            return;
        }

        $this->botSay("Type 'skip' to skip, or enter the API password to configure School Pay.");
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
                'school' => 0, 'school_info' => 0,
                'country' => 1,
                'emis' => 2, 'ministry' => 2,
                'uneb' => 3, 'uneb_center' => 3,
                'admin' => 4, 'admin_account' => 4,
                'co-admin' => 5, 'coadmin' => 5, 'co_admin' => 5, 'co_admin_invite' => 5,
                'academic' => 6, 'academic_year' => 6, 'year' => 6,
                'classes' => 7, 'class' => 7, 'standards' => 7, 'standard' => 7,
                'subjects' => 8, 'subject' => 8,
                'teachers' => 9, 'teacher' => 9,
                'students' => 10, 'student' => 10,
                'terms' => 11, 'term' => 11,
                'fees' => 12, 'fee' => 12,
                'exams' => 13, 'exam' => 13,
                'whatsapp' => 14, 'whatsapp_verify' => 14,
                'school_pay' => 15, 'payment' => 15,
                'plan' => 16, 'plans' => 16, 'plan_selection' => 16,
            ];
            $textLower = strtolower(trim($text));
            // Exact match first
            if (isset($stepMap[$textLower])) {
                $target = $stepMap[$textLower];
                $this->step = $target;
                $this->substep = 0;
                $this->botSay("Going back to **" . str_replace('_', ' ', $this->steps[$target]) . "**. Let's review that section.");
                return;
            }
            // Fuzzy match — look for keywords in natural language ("I want to add students" → students)
            foreach ($stepMap as $keyword => $stepIdx) {
                if (str_contains($textLower, $keyword)) {
                    $this->step = $stepIdx;
                    $this->substep = 0;
                    $this->botSay("Going back to **" . str_replace('_', ' ', $this->steps[$stepIdx]) . "**. Let's review that section.");
                    return;
                }
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
                'plan'         => ucfirst($planName ?: '—'),
                'schoolName'   => $schoolDisplay,
                'schoolType'   => $this->schoolType,
                'country'      => $this->schoolCountry ?: '—',
                'ministryCode' => $this->ministryCode ?: '—',
                'unebCenter'   => $this->unebCenterNumber ?: '—',
                'curriculum'   => strtoupper($this->curriculum ?: 'UNEB'),
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
                'schoolPay'    => '⏳ Optional — configure after onboarding',
                'mode'         => $this->mode,
            ];
            // Build a smart summary
            $parts = [];
            $parts[] = "{$schoolDisplay}";
            if ($planName && $planName !== '—') {
                $parts[] = "Plan: **{$planName}**";
            }
            $counts = [];
            $c = count($this->standards); if ($c) $counts[] = "{$c} class" . ($c !== 1 ? 'es' : '');
            $c = count($this->teacherList); if ($c) $counts[] = "{$c} teacher" . ($c !== 1 ? 's' : '');
            $c = count($this->studentList); if ($c) $counts[] = "{$c} student" . ($c !== 1 ? 's' : '');
            $c = count($this->terms); if ($c) $counts[] = "{$c} term" . ($c !== 1 ? 's' : '');
            $c = count($this->fees); if ($c) $counts[] = "{$c} fee" . ($c !== 1 ? 's' : '');
            $c = count($this->exams); if ($c) $counts[] = "{$c} exam" . ($c !== 1 ? 's' : '');
            if (!empty($counts)) $parts[] = implode(' · ', $counts);

            $whatsappStatus = $this->whatsappVerified ? '✅ WhatsApp verified' : '⏳ WhatsApp not verified';

            $this->botSay("📋 **" . implode(' | ', $parts) . "** | {$whatsappStatus}");
            $action = $this->mode === 'complete' ? 'save these changes' : 'create this school';
            $this->botSay("Review the summary below, then click **Confirm** to {$action}.");
        }

        // Chat fallback: typing "commit" works too
        if (strtolower($text) === 'commit') {
            $this->commit();
        }
    }

    /**
     * Confirm button handler — aliased from "commit" to avoid Alpine.js
     * which intercepts "commit" as a store mutation keyword and prevents
     * Livewire from dispatching it from the frontend.
     */
    public function confirmOnboarding(): void
    {
        $this->commit();
    }

    /**
     * Confirm button handler — commits the school to the database.
     * Also callable by typing "commit" in the chat.
     */
    public function commit()
    {
        // Pre-flight: check if a school with this name already exists
        if (empty($this->schoolId) && \App\Models\School::where('name', $this->schoolName)->exists()) {
            $this->botSay("⚠️ **{$this->schoolName}** already exists in the system. If you want to modify it, use the **Edit** button to change the school name, or start a fresh onboarding.");
            return;
        }

        // Pre-flight: check if admin email is already taken
        if ($this->adminEmail && \App\Models\User::where('email', $this->adminEmail)->exists()) {
            $this->botSay("⚠️ The email **{$this->adminEmail}** is already in use. Use the **Edit** button to choose a different admin email.");
            return;
        }

        try {
            $this->resolveCollectedDataForCommit();
            $this->commitAll();
            $this->deleteDraft();
            $this->reviewData['committed'] = true;
            $this->reviewData['adminEmail'] = $this->adminEmail ?: (auth()->user()->email ?? '');
            $this->reviewData['adminPhone'] = $this->schoolPhone;
            // Store whether a custom password was set (not the actual value)
            $this->reviewData['adminHasPassword'] = !empty($this->adminPassword);
            $this->reviewData['coAdminEmail'] = $this->coAdminEmail;
            $this->reviewData['coAdminPromoted'] = (bool) $this->coAdminUserId;
            $this->reviewData['mode'] = $this->mode;
            $this->step = 99;
            // Welcome message for school admin completing setup
            if ($this->mode === 'complete') {
                $schoolName = optional(\App\Models\School::find($this->schoolId))->name ?? 'your school';
                $this->botSay("✅ All done! Your school is set up. Ask me about **{$schoolName}**.");
            }
            // After completing onboarding: school admin goes to assistant mode for Q&A,
            // super admin stays in create mode to onboard another school.
            $this->mode = $this->mode === 'complete' ? 'assistant' : 'create';
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
        } catch (\Throwable $e) {
            \Log::error('Onboarding commit failed: ' . $e->getMessage());
            $this->botSay("Something unexpected happened: **" . class_basename($e) . "**. Please try again or contact support.");
        }
    }

    // ════════════════════════════════════════════════
    //  Transform Toshi's string[] fees into engine format
    // ════════════════════════════════════════════════

    /**
     * Merge session backup and actionData into canonical commit properties so
     * nested Livewire state collected during the chat survives confirmOnboarding.
     */
    private function resolveCollectedDataForCommit(): void
    {
        $backup = session('toshi_state');
        if (is_array($backup) && $backup !== []) {
            foreach (['actionData', 'terms', 'fees', 'studentList', 'standards', 'subjects'] as $key) {
                if (! array_key_exists($key, $backup)) {
                    continue;
                }

                $backupVal = $backup[$key];
                $currentVal = $this->{$key} ?? null;

                if ($key === 'actionData' && is_array($backupVal)) {
                    $currentActionData = is_array($currentVal) ? $currentVal : [];
                    foreach (['students', 'fees', 'exams', 'subjects', 'teachers'] as $nested) {
                        $backupNested = $backupVal[$nested] ?? [];
                        $currentNested = $currentActionData[$nested] ?? [];
                        if (is_array($backupNested) && count($backupNested) > count($currentNested)) {
                            $this->actionData[$nested] = $backupNested;
                        }
                    }

                    continue;
                }

                if ($this->isCollectedDataEmpty($currentVal) && ! $this->isCollectedDataEmpty($backupVal)) {
                    $this->{$key} = $backupVal;
                }
            }
        }

        $this->syncActionDataToCanonicalProperties();
    }

    private function isCollectedDataEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return $value === '' || $value === false;
    }

    /**
     * Copy nested actionData into top-level properties commitAll() reads.
     */
    private function syncActionDataToCanonicalProperties(): void
    {
        if (! empty($this->actionData['students']) && empty($this->studentList)) {
            $this->studentList = collect($this->actionData['students'])
                ->map(fn ($record) => is_array($record) ? trim((string) ($record['name'] ?? '')) : trim((string) $record))
                ->filter()
                ->values()
                ->toArray();
        }

        if (empty($this->fees) && ! empty($this->actionData['fees'])) {
            $this->fees = collect($this->actionData['fees'])
                ->map(fn ($fee) => is_array($fee) ? trim((string) ($fee['name'] ?? '')) : trim((string) $fee))
                ->filter()
                ->values()
                ->toArray();
        }
    }

    private function hasFeesToCommit(): bool
    {
        if (! empty($this->fees)) {
            return true;
        }

        return ! empty($this->actionData['fees']);
    }

    /**
     * Transform collected fees into the structured array format expected by
     * OnboardingEngine::saveFees(). Prefers actionData rows (amount/class/term)
     * when the form collected them; falls back to string[] names on $this->fees.
     */
    private function feesForEngine(): array
    {
        if (! empty($this->actionData['fees']) && is_array($this->actionData['fees'])) {
            $structured = [];
            foreach ($this->actionData['fees'] as $fee) {
                if (! is_array($fee)) {
                    continue;
                }

                $name = trim((string) ($fee['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $amount = $fee['amount'] ?? 0;
                $entry = [
                    'name' => $name,
                    'amount' => is_numeric($amount) ? (float) $amount : 0,
                ];

                $class = trim((string) ($fee['class'] ?? ''));
                if ($class !== '') {
                    $entry['class'] = $class;
                }

                $term = trim((string) ($fee['term'] ?? ''));
                if ($term !== '') {
                    $entry['term'] = $term;
                }

                $structured[] = $entry;
            }

            if ($structured !== []) {
                return $structured;
            }
        }

        return array_map(fn (string $name) => [
            'name' => trim($name),
            'amount' => 0,
        ], array_filter($this->fees, fn ($f) => is_string($f) && trim($f) !== ''));
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
                    'ministry_code' => $this->ministryCode ?: null,
                    'uneb_center_number' => $this->unebCenterNumber,
                    'curriculum'    => $this->curriculum ?: 'uneb',
                    'school_pay_api_password' => $this->schoolPayPassword ?: null,
                    'school_pay_webhook_enabled' => $this->schoolPayPassword ? true : false,
                    'toshi_enabled' => 1,
                    'status'  => 1,
                    'slug'    => Str::slug($this->schoolName),
                    'registration_country' => $this->schoolCountry ?: 'Uganda',
                ]);
                if ($this->schoolCountry) {
                    \App\Services\OnboardingStepsService::persistCountry($school->fresh(), $this->schoolCountry);
                    $school->refresh();
                }
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

                // ── Plan: delegate to OnboardingEngine for CurrentPlan/TrialService ──
                // Subscription record is Toshi-specific billing, kept inline.
                if ($this->selectedPlanId) {
                    app(OnboardingEngine::class)->savePlan($school, (int) $this->selectedPlanId, skipCompletionCheck: true);
                    Subscription::create([
                        'school_id'  => $school->id, 'plan_id' => $this->selectedPlanId,
                        'user_id'    => $adminUser->id,
                        'status' => 'pending', 'start_date' => now(), 'end_date' => now()->addYear(),
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
                    $coAdminCredentials = UserProvisioning::randomPasswordCredentials();
                    $coAdminUser = User::create([
                        'school_id' => $school->id, 'usergroup_id' => 3,
                        'name' => $this->coAdminName,
                        'email' => $this->coAdminEmail,
                        'password' => $coAdminCredentials['password'],
                        'is_reset' => $coAdminCredentials['is_reset'],
                        'status' => 'active', 'email_verified' => 1,
                    ]);
                    Userprofile::create([
                        'school_id' => $school->id, 'user_id' => $coAdminUser->id,
                        'usergroup_id' => 3, 'firstname' => $this->coAdminName,
                        'lastname' => 'Co-Admin', 'status' => 'active',
                    ]);

                    try {
                        Mail::to($this->coAdminEmail)->queue(new CoAdminInviteMail(
                            $this->coAdminName, $this->coAdminEmail,
                            $coAdminCredentials['plain'],
                            $school->name, false
                        ));
                    } catch (\Exception $e) {
                        \Log::warning('Co-admin invite email failed: ' . $e->getMessage());
                    }
                }

                // ── Content steps: delegate to OnboardingEngine ──
                // Fixes: (1) per-class tier mapping — nursery/primary/o-level/a-level
                // instead of a single $phase Standard for all classes; (2) SchoolCategorySeeder
                // now runs when school_category is set; (3) idempotent via firstOrCreate.
                app(OnboardingEngine::class)->saveStandards($school, $academicYear, $this->standards);
                if (!empty($this->subjects)) {
                    app(OnboardingEngine::class)->saveSubjects($school, $academicYear, $this->subjects);
                }

                // ── Rebuild classLinkMap from DB for student assignment below ──
                $firstStandardLink = null;
                $classLinkMap = []; // class name → StandardLink

                foreach ($this->standards as $class) {
                    $className = $class['name'];
                    $sections = Section::where('school_id', $school->id)
                        ->where(function ($q) use ($className) {
                            $q->where('name', $className)
                              ->orWhere('name', 'like', $className . ' %');
                        })->get();
                    foreach ($sections as $section) {
                        $link = StandardLink::where('school_id', $school->id)
                            ->where('section_id', $section->id)
                            ->where('academic_year_id', $academicYear->id)
                            ->first();
                        if ($link) {
                            $classLinkMap[$className] = $link;
                            if (!$firstStandardLink) {
                                $firstStandardLink = $link;
                            }
                        }
                    }
                }

                // ── Seed MoE default grading scales ──
                try {
                    \App\Helpers\GradingHelper::seedAllGradingForSchool($school->id);
                } catch (\Exception $e) {
                    \Log::warning('Failed to seed grading scales: ' . $e->getMessage());
                }

                // ── Teachers: delegate to OnboardingEngine ──
                // Fixes: random password per teacher (not shared admin password),
                // is_reset=1 on every account, Userprofile.alternate_no for phone,
                // email dedup with fallback.
                $teacherDrafts = array_map(function ($name) {
                    return [
                        'name'  => trim((string) $name),
                        'email' => Str::slug($name) . '@' . Str::slug($this->schoolName) . '.edu',
                        'phone' => $this->teacherPhones[$name] ?? '',
                    ];
                }, $this->teacherList);
                app(OnboardingEngine::class)->saveTeachers($school, $academicYear, $teacherDrafts);

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

                // ── Students: delegate to OnboardingEngine ──
                // Fixes: random password per student (not shared admin password),
                // is_reset=1 on every account, Userprofile with alternate_no for phone,
                // email dedup with fallback, proper StandardLink resolution.
                // Note: gender/LIN fields are passed through in the draft but
                // OnboardingEngine::saveStudents doesn't use them — they'll be
                // added when gender/LIN support is added to the engine. The engine
                // creates StudentAcademic with klassapp_student_id via nextForStudent.
                $studentRecords = !empty($this->actionData['students'])
                    ? $this->actionData['students']
                    : array_map(fn($n) => ['name' => $n, 'class' => ''], $this->studentList);
                $studentDrafts = array_map(function ($record) {
                    $name = is_string($record) ? $record : ($record['name'] ?? '');
                    return [
                        'name'  => trim((string) $name),
                        'class' => trim((string) (is_array($record) ? ($record['class'] ?? '') : '')),
                        'phone' => trim((string) (is_array($record) ? ($record['phone'] ?? '') : '')),
                        'school_student_id' => trim((string) (is_array($record) ? ($record['school_student_id'] ?? '') : '')),
                        'board_registration_number' => trim((string) (is_array($record) ? ($record['board_registration_number'] ?? '') : '')),
                    ];
                }, $studentRecords);
                app(OnboardingEngine::class)->saveStudents($school, $academicYear, $studentDrafts);

                // ── Terms: delegate to OnboardingEngine (idempotent via firstOrCreate) ──
                if (!empty($this->terms)) {
                    app(OnboardingEngine::class)->saveTerms($school, $academicYear, $this->terms);
                }

                // ── Fees: delegate to OnboardingEngine (whole-school spread, idempotent) ──
                if ($this->hasFeesToCommit()) {
                    app(OnboardingEngine::class)->saveFees($school, $this->feesForEngine());
                }

                // ── WhatsApp: delegate to OnboardingEngine ──
                // Fixes: updateOrCreate by user_id for idempotency, UniqueConstraintViolationException catch for phone dedup.
                if ($this->whatsappVerified && $this->whatsappPhone) {
                    app(OnboardingEngine::class)->saveWhatsApp($school, $adminUser->id, $this->whatsappPhone);
                }
            }

            // ════════════════════════════════════════════
            //  COMPLETE MODE: Only add missing items
            // ════════════════════════════════════════════
            if ($this->mode === 'complete') {
                $schoolId = $this->schoolId;
                $user = auth()->user();

                $school = School::find($schoolId);
                if ($school) {
                    if ($this->schoolName !== '' && $this->schoolName !== $school->name) {
                        $unique = app(\App\Services\SchoolSignupBootstrapService::class)->uniqueSchoolName($this->schoolName);
                        if ($unique !== $school->name) {
                            // Prefer exact desired name when only this school holds it
                            if (! School::where('name', $this->schoolName)->where('id', '!=', $school->id)->exists()) {
                                $unique = $this->schoolName;
                            }
                            $school->name = $unique;
                            $school->slug = Str::slug($unique);
                        }
                    }
                    if ($this->curriculum !== '' && $this->curriculum !== null && $school->curriculum !== $this->curriculum) {
                        $school->curriculum = $this->curriculum;
                    }
                    // Do not overwrite already-set values with empty/null when
                    // complete-mode is reopened without re-collecting fields.
                    if (filled($this->schoolCountry)) {
                        \App\Services\OnboardingStepsService::persistCountry($school, $this->schoolCountry);
                        $school->refresh();
                    }
                    if (filled($this->ministryCode)) {
                        $school->ministry_code = $this->ministryCode;
                    }
                    if ($this->unebCenterNumber !== null
                        && \Illuminate\Support\Facades\Schema::hasColumn('schools', 'uneb_center_number')) {
                        $school->uneb_center_number = $this->unebCenterNumber;
                    }
                    $school->toshi_enabled = 1;
                    $school->save();

                    if ($this->selectedPlanId && ! CurrentPlan::where('school_id', $schoolId)->exists()) {
                        $this->persistSelectedPlan((int) $schoolId, (int) $this->selectedPlanId);
                    }
                }

                $academicYear = \App\Models\AcademicYear::where('school_id', $schoolId)->first();
                if (!$academicYear) {
                    $academicYear = AcademicYear::create([
                        'school_id' => $schoolId, 'name' => $this->academicYearLabel ?: date('Y'),
                        'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
                        'type' => 'Current Academic Year',
                        'description' => 'Current Academic Year',
                    ]);
                } elseif ($this->academicYearLabel && (string) $academicYear->name !== (string) $this->academicYearLabel) {
                    $academicYear->name = $this->academicYearLabel;
                    $academicYear->save();
                }

                // ── Content steps: delegate to OnboardingEngine ──
                // Fixes: (1) per-class tier mapping instead of single $phase Standard;
                // (2) SchoolCategorySeeder runs when school_category is set;
                // (3) idempotent via firstOrCreate throughout.
                if (!empty($this->standards)) {
                    app(OnboardingEngine::class)->saveStandards($school, $academicYear, $this->standards);
                }
                if (!empty($this->subjects)) {
                    app(OnboardingEngine::class)->saveSubjects($school, $academicYear, $this->subjects);
                }

                // ── Teachers: delegate to OnboardingEngine ──
                // Fixes: random password per teacher, is_reset=1, Userprofile.alternate_no for phone.
                if (!empty($this->teacherList)) {
                    $teacherDrafts = array_map(function ($name) {
                        return [
                            'name'  => trim((string) $name),
                            'email' => Str::slug($name) . '@school.edu',
                            'phone' => $this->teacherPhones[$name] ?? '',
                        ];
                    }, $this->teacherList);
                    app(OnboardingEngine::class)->saveTeachers($school, $academicYear, $teacherDrafts);
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

                // ── Terms: delegate to OnboardingEngine (idempotent) ──
                if (!empty($this->terms)) {
                    app(OnboardingEngine::class)->saveTerms($school, $academicYear, $this->terms);
                }

                // ── Students: delegate to OnboardingEngine ──
                // Fixes: random password per student, is_reset=1, Userprofile.alternate_no for phone,
                // proper StandardLink resolution, klassapp_student_id via nextForStudent.
                if (!empty($this->studentList) || !empty($this->actionData['students'])) {
                    $studentRecords = !empty($this->actionData['students'])
                        ? $this->actionData['students']
                        : array_map(fn($n) => ['name' => $n, 'class' => ''], $this->studentList);
                    $studentDrafts = array_map(function ($record) {
                        $name = is_string($record) ? $record : ($record['name'] ?? '');
                        return [
                            'name'  => trim((string) $name),
                            'class' => trim((string) (is_array($record) ? ($record['class'] ?? '') : '')),
                            'phone' => trim((string) (is_array($record) ? ($record['phone'] ?? '') : '')),
                            'school_student_id' => trim((string) (is_array($record) ? ($record['school_student_id'] ?? '') : '')),
                            'board_registration_number' => trim((string) (is_array($record) ? ($record['board_registration_number'] ?? '') : '')),
                        ];
                    }, $studentRecords);
                    app(OnboardingEngine::class)->saveStudents($school, $academicYear, $studentDrafts);
                }

                // ── Fees: delegate to OnboardingEngine (whole-school spread, idempotent) ──
                if ($this->hasFeesToCommit()) {
                    app(OnboardingEngine::class)->saveFees($school, $this->feesForEngine());
                }

                // ── WhatsApp: delegate to OnboardingEngine ──
                // Fixes: updateOrCreate by user_id for idempotency, UniqueConstraintViolationException catch.
                if ($this->whatsappVerified && $this->whatsappPhone) {
                    app(OnboardingEngine::class)->saveWhatsApp($school, $user->id, $this->whatsappPhone);
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
    //  Only UNEB is shipped. When adding Cambridge, Edexcel, IB, KCPE/KCSE,
    //  Rwanda, Tanzania, or South Sudan curricula:
    //    1. Add the value to schools.curriculum enum (migration)
    //    2. Branch this method by $this->curriculum
    //    3. Define class names + subject lists per curriculum
    //    4. Update the setCurriculum() message in handleSchoolInfo
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
