<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\NurseryAssessment;
use App\Models\Attendance;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Helpers\SiteHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ToshiActionService
{
    /**
     * When false, write tools return a confirmation payload instead of executing.
     * Set to true by confirmYes() for the second pass that actually writes.
     */
    public static bool $bypassConfirm = false;

    /**
     * Side-channel for the SDK v2 path: when a write tool returns __tier2_confirm,
     * it stores the payload here so ToshiSdkV2Service can retrieve it after the
     * orchestrator returns, bypassing the LLM loop that might reformat the JSON.
     */
    public static ?array $pendingConfirmPayload = null;

    /**
     * If bypassConfirm is false, returns a __tier2_confirm JSON payload
     * that the Livewire component converts to pendingToolConfirm.
     * Returns null when bypassConfirm is true (tool should execute).
     */
    public static function confirmationPayload(string $toolName, array $args, string $preview): ?string
    {
        if (self::$bypassConfirm) {
            return null;
        }
        // Store in the side-channel so ToshiSdkV2Service can retrieve it
        // without relying on LLM-preserved JSON formatting
        self::$pendingConfirmPayload = [
            'tool' => $toolName,
            'args' => $args,
            'preview' => $preview,
        ];
        return json_encode([
            '__tier2_confirm' => true,
            'tool' => $toolName,
            'args' => $args,
            'preview' => $preview,
        ]);
    }

    /**
     * Result returned by every action method.
     */
    public static function result(bool $success, string $message, array $data = []): array
    {
        return array_merge(['success' => $success, 'message' => $message], $data);
    }

    // ── Shared daily budget tracking (single source of truth) ──

    /**
     * Get the remaining daily LLM query budget for a user.
     */
    public static function getRemainingBudget(int $userId, ?int $schoolId): int
    {
        $dailyLimit = (int) config('toshi.daily_llm_limit', 100);
        $key = 'toshi_daily_llm_' . $userId . '_' . ($schoolId ?? 0);
        $used = (int) cache()->get($key, 0);
        return max(0, $dailyLimit - $used);
    }

    /**
     * Consume one unit from the daily LLM query budget.
     * Returns true if the budget was available and consumed.
     */
    public static function consumeBudget(int $userId, ?int $schoolId): bool
    {
        if (self::getRemainingBudget($userId, $schoolId) <= 0) {
            return false;
        }

        $key = 'toshi_daily_llm_' . $userId . '_' . ($schoolId ?? 0);
        $ttl = now()->endOfDay()->diffInSeconds(now());

        cache()->add($key, 0, $ttl);
        cache()->increment($key);

        return true;
    }

    /**
     * Type map for plan-limit enforcement.
     *
     * Maps a human-readable type to the usergroup_id to count and the
     * plan column that stores the limit for that type.
     */
    private const PLAN_TYPES = [
        'students'  => ['usergroup_id' => 6, 'plan_column' => 'no_of_students', 'label' => 'students'],
        'teachers'  => ['usergroup_id' => 5, 'plan_column' => 'no_of_users',    'label' => 'teachers'],
        'admins'    => ['usergroup_id' => 3, 'plan_column' => 'no_of_users',    'label' => 'administrators'],
    ];

    /**
     * Enforce school plan limits for a given record type.
     *
     * Reads from CurrentPlan (the canonical runtime source — survives admin plan
     * changes and is already used by DashboardController and ToshiAssistantService).
     *
     * Returns a standard result array:
     *   ['success' => true,  'message' => '']            → under limit, proceed
     *   ['success' => false, 'message' => '...upgrade...'] → over limit, blocked
     *
     * The message is plain-text safe for both HTTP flash contexts and Toshi/WhatsApp
     * conversational use (no HTML, no route-dependent phrasing).
     */
    public static function enforcePlanLimit(int $schoolId, string $type): array
    {
        if (!isset(self::PLAN_TYPES[$type])) {
            return self::result(true, '');
        }

        $cfg = self::PLAN_TYPES[$type];

        $currentPlan = CurrentPlan::with('plan')->where('school_id', $schoolId)->first();
        if (!$currentPlan || !$currentPlan->plan) {
            return self::result(true, ''); // No plan configured → no limit
        }

        $plan   = $currentPlan->plan;
        $limit  = $plan->{$cfg['plan_column']} ?? 0;
        if ($limit <= 0) {
            return self::result(true, ''); // Unlimited
        }

        $count = User::where('school_id', $schoolId)
            ->where('usergroup_id', $cfg['usergroup_id'])
            ->count();

        if ($count >= $limit) {
            return self::result(
                false,
                "Your {$plan->name} plan allows a maximum of {$limit} {$cfg['label']}. Please upgrade to add more."
            );
        }

        return self::result(true, '');
    }

    // ── Role Capabilities ──

    /**
     * ⚠️  ADVISORY-ONLY — NOT for authorization enforcement.
     *
     * This method provides an LLM-readable hint layer about what actions each
     * usergroup can perform. It is used by the AI agent to understand context,
     * NOT to gate access.
     *
     * Real authorization enforcement lives in the `toshi-school-action` Gate
     * defined in `AuthServiceProvider`, which checks the same MustBeSchoolAdmin
     * rule: ug3 (SchoolAdmin) → always authorized; ug1 (Superadmin) → authorized
     * ONLY when impersonating a SchoolAdmin; all others → denied.
     *
     * Map of usergroup_id → allowed capabilities.
     *
     * Keys are action identifiers used in keyword routes and actionXxx handlers.
     * 'scope'  → 'platform' (no school_id), 'school' (has school_id), or 'none'
     * 'label'  → Human-readable role name
     * 'actions' → list of allowed action identifiers
     */
    public static function getRoleCapabilities(int $usergroupId): array
    {
        $map = [
            1 => [ // SiteAdmin
                'scope'   => 'platform',
                'label'   => 'platform administrator',
                'actions' => ['create_school', 'platform_reports', 'list_schools'],
            ],
            2 => [ // SiteSubadmin — dead role, pending removal. No routes or middleware.
                'scope'   => 'none',
                'label'   => 'inactive role',
                'actions' => [],
            ],
            3 => [ // SchoolAdmin
                'scope'   => 'school',
                'label'   => 'school administrator',
                'actions' => [
                    'add_student', 'add_teacher', 'add_coadmin',
                    'create_fee', 'create_term', 'record_attendance',
                    'record_payment', 'create_exam', 'add_parent', 'enter_mark',
                    'assign_teacher', 'create_subject',
                    'list_classes', 'list_teachers', 'list_sections', 'generate_report',
                ],
            ],
             4 => [ // SchoolSubadmin
                'scope'   => 'school',
                'label'   => 'school administrator',
                'actions' => [],
            ],
            5 => [ // Teacher
                'scope'   => 'school',
                'label'   => 'teacher',
                'actions' => [
                    'mark_attendance', 'enter_marks', 'manage_lesson_plans',
                    'manage_assignments', 'manage_homework', 'apply_leave',
                    'manage_class_wall', 'view_students', 'view_timetable',
                    'view_events', 'manage_tasks', 'manage_noticeboard',
                ],
            ],
            6 => [ // Student
                'scope'   => 'self',
                'label'   => 'student',
                'actions' => [
                    'view_dashboard', 'view_assignments', 'view_homework',
                    'manage_tasks', 'view_events', 'view_notices',
                    'view_marks', 'view_attendance', 'view_library_activity',
                    'view_class_wall', 'manage_conversations',
                ],
            ],
            7 => [ // Parent — API-only (no web dashboard). No route group registered.
                'scope'   => 'none',
                'label'   => 'parent',
                'actions' => [],
            ],
            8 => [ // Librarian
                'scope'   => 'school',
                'label'   => 'librarian',
                'actions' => [
                    'manage_books', 'manage_book_categories', 'manage_lending',
                    'manage_library_cards', 'view_dashboard', 'manage_tasks',
                ],
            ],
            9 => [ // OldStudent
                'scope'   => 'none',
                'label'   => 'former student',
                'actions' => [],
            ],
            10 => [ // Receptionist
                'scope'   => 'school',
                'label'   => 'receptionist',
                'actions' => [
                    'manage_visitor_log', 'manage_call_log', 'manage_postal_record',
                    'manage_email_record', 'view_dashboard', 'view_events',
                    'manage_noticeboard', 'manage_tasks',
                ],
            ],
            11 => [ // Accountant
                'scope'   => 'school',
                'label'   => 'accountant',
                'actions' => [
                    'record_payment', 'manage_payroll', 'view_unpaid_reports',
                    'view_fee_structure', 'view_dashboard', 'manage_tasks',
                ],
            ],
            12 => [ // Stock Keeper — routes/stock.php is empty, no modules implemented yet
                'scope'   => 'school',
                'label'   => 'stock keeper',
                'actions' => [],
            ],
            13 => [ // Non Teaching
                'scope'   => 'school',
                'label'   => 'staff',
                'actions' => [],
            ],
        ];

        return $map[$usergroupId] ?? ['scope' => 'none', 'label' => 'user', 'actions' => []];
    }

    /**
     * Check whether a user is allowed to perform a given action.
     */
    public static function can(User $user, string $action): bool
    {
        $caps = self::getRoleCapabilities($user->usergroup_id);
        return in_array($action, $caps['actions'], true);
    }

    /**
     * Get the display label for a user's role.
     */
    public static function getRoleLabel(User $user): string
    {
        return self::getRoleCapabilities($user->usergroup_id)['label'];
    }

    // ── Impersonation-aware identity helpers ──

    /**
     * Resolve the effective user for school-scoped authorization.
     *
     * When a Superadmin (ug1) impersonates a SchoolAdmin (ug3), Auth::user()
     * still returns the Superadmin. This method returns the impersonated
     * SchoolAdmin instead, so the tool's school-scoping logic works correctly.
     *
     * Returns the same $user for all non-impersonation contexts.
     */
    public static function getEffectiveUser(User $user): User
    {
        if ($user->usergroup_id === 1 && $user->isImpersonating()) {
            $impersonatedId = \Session::get('impersonate');
            $impersonated = User::find($impersonatedId);
            if ($impersonated && $impersonated->usergroup_id === 3) {
                return $impersonated;
            }
        }
        return $user;
    }

    /**
     * Resolve the effective school_id for the acting user.
     *
     * In impersonation context, returns the impersonated SchoolAdmin's school_id.
     * Otherwise returns the authenticated user's school_id.
     */
    public static function getEffectiveSchoolId(User $user): ?int
    {
        return self::getEffectiveUser($user)->school_id;
    }

    // ── Student ──

    /**
     * Add a student: creates User + StudentAcademic + Userprofile.
     *
     * @param User  $admin   The admin performing the action
     * @param array $data    Keys: name, email (optional), class_name (optional), section_name (optional)
     */
    public static function addStudent(User $admin, array $data): array
    {
        if (!self::can($admin, 'add_student')) {
            return self::result(false, 'You do not have permission to add students.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $limit = self::enforcePlanLimit($schoolId, 'students');
        if (!$limit['success']) {
            return $limit;
        }

        $name = trim($data['name'] ?? '');
        if ($name === '' || strlen($name) < 3) {
            return self::result(false, 'Student name must be at least 3 characters.');
        }

        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $email = 'student.' . Str::random(6) . '@' . Str::slug(School::find($schoolId)?->name ?? 'school') . '.sch.ug';
        }

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (!$academicYear) {
            return self::result(false, 'No academic year configured for this school.');
        }

        $standardLink = null;
        $className = trim($data['class_name'] ?? $data['class'] ?? '');
        if ($className !== '') {
            $sectionName = trim($data['section_name'] ?? '');
            $standardLink = self::resolveStandardLink($schoolId, $academicYear->id, $className, $sectionName);
        }

        try {
            DB::beginTransaction();

            $password = Hash::make('password');
            $student = User::create([
                'school_id'    => $schoolId,
                'usergroup_id' => 6,
                'name'         => $name,
                'email'        => $email,
                'password'     => $password,
                'status'       => 'active',
                'email_verified' => 1,
            ]);

            Userprofile::create([
                'school_id'    => $schoolId,
                'user_id'      => $student->id,
                'usergroup_id' => 6,
                'firstname'    => $name,
                'lastname'     => '',
                'status'       => 'active',
            ]);

            $academicData = [
                'school_id'        => $schoolId,
                'academic_year_id' => $academicYear->id,
                'user_id'          => $student->id,
                'academic_status'  => 'active',
            ];
            if ($standardLink) {
                $academicData['standardLink_id'] = $standardLink->id;
            }
            StudentAcademic::create($academicData);

            DB::commit();

            $msg = "Student **{$name}** added successfully";
            if ($standardLink) {
                $section = $standardLink->section;
                $std = $standardLink->standard;
                $label = ($std?->name ?? '') . ' ' . ($section?->name ?? '');
                $msg .= " to **{$label}**";
            }
            $msg .= ". Login: `{$email}` / password: `password`";

            return self::result(true, $msg, ['user_id' => $student->id, 'email' => $email]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ToshiAction: addStudent failed', ['error' => $e->getMessage()]);
            return self::result(false, 'Failed to add student: ' . $e->getMessage());
        }
    }

    // ── Teacher ──

    /**
     * Add a teacher: creates User + Userprofile.
     */
    public static function addTeacher(User $admin, array $data): array
    {
        if (!self::can($admin, 'add_teacher')) {
            return self::result(false, 'You do not have permission to add teachers.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $limit = self::enforcePlanLimit($schoolId, 'teachers');
        if (!$limit['success']) {
            return $limit;
        }

        $name = trim($data['name'] ?? '');
        if ($name === '' || strlen($name) < 3) {
            return self::result(false, 'Teacher name must be at least 3 characters.');
        }

        $email = trim($data['email'] ?? '');
        if ($email === '') {
            return self::result(false, 'Teacher email is required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::result(false, 'Invalid email format.');
        }
        if (User::where('email', $email)->exists()) {
            return self::result(false, "A user with email **{$email}** already exists.");
        }

        $phone = trim($data['phone'] ?? '');

        try {
            DB::beginTransaction();

            $password = Hash::make('password');
            $teacher = User::create([
                'school_id'    => $schoolId,
                'usergroup_id' => 5,
                'name'         => $name,
                'email'        => $email,
                'password'     => $password,
                'status'       => 'active',
                'email_verified' => 1,
            ]);

            Userprofile::create([
                'school_id'    => $schoolId,
                'user_id'      => $teacher->id,
                'usergroup_id' => 5,
                'firstname'    => $name,
                'lastname'     => '',
                'status'       => 'active',
                'phone_no'     => $phone,
            ]);

            DB::commit();

            return self::result(true, "Teacher **{$name}** added successfully. Login: `{$email}` / password: `password`", [
                'user_id' => $teacher->id, 'email' => $email,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ToshiAction: addTeacher failed', ['error' => $e->getMessage()]);
            return self::result(false, 'Failed to add teacher: ' . $e->getMessage());
        }
    }

    // ── Co-Admin ──

    /**
     * Add a co-admin (another school admin).
     */
    public static function addCoAdmin(User $admin, array $data): array
    {
        if (!self::can($admin, 'add_coadmin')) {
            return self::result(false, 'You do not have permission to add co-admins.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $limit = self::enforcePlanLimit($schoolId, 'admins');
        if (!$limit['success']) {
            return $limit;
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        if ($name === '' || $email === '') {
            return self::result(false, 'Both name and email are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::result(false, 'Invalid email format.');
        }
        if (User::where('email', $email)->exists()) {
            return self::result(false, "A user with email **{$email}** already exists.");
        }

        try {
            DB::beginTransaction();

            $password = Hash::make('password');
            $coAdmin = User::create([
                'school_id'    => $schoolId,
                'usergroup_id' => 3,
                'name'         => $name,
                'email'        => $email,
                'password'     => $password,
                'status'       => 'active',
                'email_verified' => 1,
            ]);

            Userprofile::create([
                'school_id'    => $schoolId,
                'user_id'      => $coAdmin->id,
                'usergroup_id' => 3,
                'firstname'    => $name,
                'lastname'     => '',
                'status'       => 'active',
            ]);

            DB::commit();

            return self::result(true, "Co-admin **{$name}** added. Login: `{$email}` / password: `password`", [
                'user_id' => $coAdmin->id, 'email' => $email,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ToshiAction: addCoAdmin failed', ['error' => $e->getMessage()]);
            return self::result(false, 'Failed to add co-admin: ' . $e->getMessage());
        }
    }

    // ── Attendance ──

    /**
     * Record attendance for a student on a given date.
     */
    public static function recordAttendance(User $admin, array $data): array
    {
        if (!self::can($admin, 'record_attendance')) {
            return self::result(false, 'You do not have permission to record attendance.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $studentIdentifier = trim($data['student'] ?? '');
        if ($studentIdentifier === '') {
            return self::result(false, 'Student identifier (name, email, or ID) is required.');
        }

        $date = trim($data['date'] ?? now()->toDateString());
        // Parse flexible date formats
        try {
            $date = \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return self::result(false, "Could not understand the date: {$date}. Use YYYY-MM-DD.");
        }

        $status = strtolower(trim($data['status'] ?? 'present'));
        if (!in_array($status, ['present', 'absent', 'late', 'half-day', 'holiday'])) {
            return self::result(false, "Status must be one of: present, absent, late, half-day.");
        }

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (!$academicYear) {
            return self::result(false, 'No academic year configured.');
        }

        // Find student
        $studentUser = self::findStudent($schoolId, $studentIdentifier);
        if (!$studentUser) {
            return self::result(false, "Student not found: {$studentIdentifier}");
        }

        $studentAcademic = StudentAcademic::where('school_id', $schoolId)
            ->where('user_id', $studentUser->id)
            ->where('academic_year_id', $academicYear->id)
            ->first();
        if (!$studentAcademic) {
            return self::result(false, 'Student academic record not found.');
        }

        // Upsert attendance
        try {
            Attendance::updateOrCreate(
                [
                    'school_id'  => $schoolId,
                    'user_id'    => $studentUser->id,
                    'date'       => $date,
                    'session'    => $data['session'] ?? 'morning',
                ],
                [
                    'academic_year_id'  => $academicYear->id,
                    'standardLink_id'   => $studentAcademic->standardLink_id,
                    'status'            => $status,
                    'recorded_by'       => $admin->id,
                    'remarks'           => $data['remarks'] ?? null,
                ]
            );

            return self::result(true, "Attendance marked as **{$status}** for **{$studentUser->name}** on {$date}.");
        } catch (\Exception $e) {
            Log::error('ToshiAction: recordAttendance failed', ['error' => $e->getMessage()]);
            return self::result(false, 'Failed to record attendance: ' . $e->getMessage());
        }
    }

    /**
     * Record attendance for an entire class.
     * $data['students'] = array of ['student' => identifier, 'status' => 'present'|'absent'|'late']
     */
    public static function recordBulkAttendance(User $admin, array $data): array
    {
        if (!self::can($admin, 'record_attendance')) {
            return self::result(false, 'You do not have permission to record attendance.');
        }
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        $students = $data['students'] ?? [];

        if (empty($students)) {
            return self::result(false, 'No student records provided.');
        }

        foreach ($students as $item) {
            $item['date'] = $data['date'] ?? now()->toDateString();
            $item['session'] = $data['session'] ?? 'morning';
            $result = self::recordAttendance($admin, $item);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $result['message'];
            }
        }

        $msg = "Attendance: {$results['success']} marked, {$results['failed']} failed.";
        return self::result($results['failed'] === 0, $msg, $results);
    }

    // ── Term ──

    /**
     * Create an academic term.
     */
    public static function createTerm(User $admin, array $data): array
    {
        if (!self::can($admin, 'create_term')) {
            return self::result(false, 'You do not have permission to create terms.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return self::result(false, 'Term name is required (e.g. "Term 1").');
        }

        $startDate = trim($data['start_date'] ?? '');
        $endDate = trim($data['end_date'] ?? '');
        if ($startDate === '' || $endDate === '') {
            return self::result(false, 'Both start date and end date are required.');
        }

        try {
            $startDate = \Carbon\Carbon::parse($startDate)->toDateString();
            $endDate = \Carbon\Carbon::parse($endDate)->toDateString();
        } catch (\Exception $e) {
            return self::result(false, 'Could not parse dates. Use YYYY-MM-DD format.');
        }

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (!$academicYear) {
            return self::result(false, 'No academic year configured.');
        }

        try {
            $term = AcademicTerm::create([
                'school_id'        => $schoolId,
                'academic_year_id' => $academicYear->id,
                'name'             => $name,
                'starts_on'        => $startDate,
                'ends_on'          => $endDate,
            ]);

            return self::result(true, "Term **{$name}** created ({$startDate} to {$endDate}).", ['term_id' => $term->id]);
        } catch (\Exception $e) {
            return self::result(false, 'Failed to create term: ' . $e->getMessage());
        }
    }

    // ── Fee ──

    /**
     * Create a fee category.
     */
    public static function createFee(User $admin, array $data): array
    {
        if (!self::can($admin, 'create_fee')) {
            return self::result(false, 'You do not have permission to manage fees.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $name = trim($data['name'] ?? '');
        $amount = $data['amount'] ?? null;
        if ($name === '' || $amount === null || !is_numeric($amount) || $amount <= 0) {
            return self::result(false, 'Fee name and a positive amount are required.');
        }

        $termId = null;
        if (!empty($data['term_name'])) {
            $term = AcademicTerm::where('school_id', $schoolId)
                ->where('name', $data['term_name'])
                ->first();
            if (!$term) {
                return self::result(false, "Term not found: {$data['term_name']}");
            }
            $termId = $term->id;
        } else {
            $term = AcademicTerm::where('school_id', $schoolId)->first();
            if ($term) $termId = $term->id;
        }

        // Resolve standard (class) — required by FK constraint
        $standardId = null;
        if (!empty($data['class_name'])) {
            $std = Standard::where('school_id', $schoolId)->where('name', $data['class_name'])->first();
            if ($std) $standardId = $std->id;
        }
        if (!$standardId) {
            $std = Standard::where('school_id', $schoolId)->first();
            if ($std) $standardId = $std->id;
        }

        try {
            $fee = FeesCategories::create([
                'school_id'        => $schoolId,
                'name'             => $name,
                'amount'           => $amount,
                'standard_id'      => $standardId,
                'academic_term_id' => $termId,
            ]);

            return self::result(true, "Fee **{$name}** created at " . number_format((float)$amount, 0) . " UGX.", ['fee_id' => $fee->id]);
        } catch (\Exception $e) {
            return self::result(false, 'Failed to create fee: ' . $e->getMessage());
        }
    }

    /**
     * Record a manual fee payment against a student.
     */
    public static function recordPayment(User $admin, array $data): array
    {
        if (!self::can($admin, 'record_payment')) {
            return self::result(false, 'You do not have permission to record payments.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $studentId = $data['student_id'] ?? null;
        $amount = $data['amount'] ?? null;
        if (!$studentId || !$amount || !is_numeric($amount) || (float)$amount <= 0) {
            return self::result(false, 'Student and a positive amount are required.');
        }

        $student = User::where('school_id', $schoolId)->where('id', $studentId)->where('usergroup_id', 6)->first();
        if (!$student) {
            return self::result(false, 'Student not found in your school.');
        }

        $feeCategoryId = !empty($data['fee_category_id']) ? (int)$data['fee_category_id'] : null;
        if ($feeCategoryId) {
            $feeCat = FeesCategories::where('school_id', $schoolId)->where('id', $feeCategoryId)->first();
            if (!$feeCat) {
                return self::result(false, 'Fee category not found.');
            }
        }

        try {
            $payment = FeePayment::create([
                'school_id'       => $schoolId,
                'fee_category_id' => $feeCategoryId,
                'user_id'         => $student->id,
                'amount'          => (float)$amount,
                'paid_on'         => $data['paid_on'] ?? now()->toDateString(),
                'payment_method'  => $data['payment_method'] ?? null,
                'reference'       => $data['reference'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'recorded_by'     => $admin->id,
                'status'          => 'paid',
            ]);

            $msg = "Payment of **" . number_format((float)$amount, 0) . " UGX** recorded for **{$student->name}**.";
            if (!empty($data['payment_method'])) {
                $msg .= " Method: **{$data['payment_method']}**.";
            }
            if (!empty($data['reference'])) {
                $msg .= " Reference: **{$data['reference']}**.";
            }

            return self::result(true, $msg, ['payment_id' => $payment->id]);
        } catch (\Exception $e) {
            return self::result(false, 'Failed to record payment: ' . $e->getMessage());
        }
    }

    // ── Teacher Assignment ──

    /**
     * Assign a teacher to a class/subject.
     */
    public static function assignTeacher(User $admin, array $data): array
    {
        if (!self::can($admin, 'assign_teacher')) {
            return self::result(false, 'You do not have permission to assign teachers.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $teacherEmail = trim($data['teacher_email'] ?? '');
        $className = trim($data['class_name'] ?? '');
        $subjectName = trim($data['subject_name'] ?? '');

        if ($teacherEmail === '' || $className === '' || $subjectName === '') {
            return self::result(false, 'Teacher email, class name, and subject name are all required.');
        }

        $teacher = User::where('school_id', $schoolId)->where('email', $teacherEmail)->where('usergroup_id', 5)->first();
        if (!$teacher) {
            return self::result(false, "Teacher not found: {$teacherEmail}");
        }

        $subject = Subject::where('school_id', $schoolId)->where('name', $subjectName)->first();
        if (!$subject) {
            return self::result(false, "Subject not found: {$subjectName}");
        }

        $standardLink = self::resolveStandardLink($schoolId, null, $className);
        if (!$standardLink) {
            return self::result(false, "Class not found: {$className}");
        }

        $year = AcademicYear::where('school_id', $schoolId)->where('status', 1)->first();
        if (!$year) $year = AcademicYear::where('school_id', $schoolId)->first();

        try {
            Teacherlink::updateOrCreate(
                [
                    'school_id'        => $schoolId,
                    'teacher_id'       => $teacher->id,
                    'standardLink_id'  => $standardLink->id,
                    'subject_id'       => $subject->id,
                ],
                [
                    'academic_year_id' => $year ? $year->id : null,
                    'status'           => 1,
                ]
            );

            return self::result(true, "Teacher **{$teacher->name}** assigned to **{$className}** for **{$subjectName}**.");
        } catch (\Exception $e) {
            return self::result(false, 'Failed to assign teacher: ' . $e->getMessage());
        }
    }

    /**
     * Create a subject. If class_name is provided, the subject is scoped to that class.
     * Otherwise it's scoped to the school's first standard/section.
     */
    public static function createSubject(User $admin, array $data): array
    {
        if (!self::can($admin, 'create_subject')) {
            return self::result(false, 'You do not have permission to create subjects.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'You are not assigned to a school.');
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return self::result(false, 'Subject name is required.');
        }

        // Resolve standard + section from class_name or use first available StandardLink
        $link = null;
        if (!empty($data['class_name'])) {
            $link = self::resolveStandardLink($schoolId, null, $data['class_name']);
        }
        if (!$link) {
            $link = StandardLink::where('school_id', $schoolId)->first();
        }
        if (!$link) {
            return self::result(false, 'No classes found for this school. Create a class first.');
        }

        // Get current academic year
        $year = AcademicYear::where('school_id', $schoolId)->where('status', 1)->first();
        if (!$year) {
            $year = AcademicYear::where('school_id', $schoolId)->first();
        }

        $exists = Subject::where('school_id', $schoolId)
            ->where('name', $name)
            ->where('standard_id', $link->standard_id)
            ->first();
        if ($exists) {
            return self::result(false, "Subject **{$name}** already exists in this class.");
        }

        try {
            Subject::create([
                'school_id'        => $schoolId,
                'academic_year_id' => $year ? $year->id : null,
                'standard_id'      => $link->standard_id,
                'section_id'       => $link->section_id,
                'name'             => $name,
                'type'             => $data['type'] ?? 'core',
            ]);

            return self::result(true, "Subject **{$name}** created.");
        } catch (\Exception $e) {
            return self::result(false, 'Failed to create subject: ' . $e->getMessage());
        }
    }

    // ── Class ──

    /**
     * List available classes for the school.
     */
    public static function listClasses(User $admin): array
    {
        if (!self::can($admin, 'list_classes')) {
            return self::result(false, 'You do not have permission to view classes.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'No school assigned.');
        }

        $links = StandardLink::with(['standard', 'section'])
            ->where('school_id', $schoolId)
            ->get();

        if ($links->isEmpty()) {
            return self::result(false, 'No classes configured yet.');
        }

        $lines = $links->map(function ($l) {
            $s = $l->standard;
            $sec = $l->section;
            $label = ($s?->name ?? '') . ($sec ? ' ' . $sec->name : '');
            $count = StudentAcademic::where('standardLink_id', $l->id)->count();
            return "  • {$label} ({$count} students)";
        })->implode("\n");

        return self::result(true, "**Classes:**\n{$lines}", ['classes' => $links]);
    }

    /**
     * List teachers available for assignment.
     */
    public static function listTeachers(User $admin): array
    {
        if (!self::can($admin, 'list_teachers')) {
            return self::result(false, 'You do not have permission to view teachers.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'No school assigned.');
        }

        $teachers = User::where('school_id', $schoolId)->where('usergroup_id', 5)->get();
        if ($teachers->isEmpty()) {
            return self::result(false, 'No teachers found.');
        }

        $lines = $teachers->map(fn($t) => "  • {$t->name} ({$t->email})")->implode("\n");
        return self::result(true, "**Teachers:**\n{$lines}", ['teachers' => $teachers]);
    }

    // ── School Summary Report ──

    /**
     * Generate a comprehensive school summary.
     */
    public static function generateReport(User $admin): array
    {
        if (!self::can($admin, 'generate_report')) {
            return self::result(false, 'You do not have permission to view reports.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'No school assigned.');
        }

        $school = School::find($schoolId);
        $academicYear = SiteHelper::getAcademicYear($schoolId);

        try {
            $studentCount = StudentAcademic::where('school_id', $schoolId)->count();
            $teacherCount = User::where('school_id', $schoolId)->where('usergroup_id', 5)->count();
            $classCount = StandardLink::where('school_id', $schoolId)->count();
            $subjectCount = Subject::where('school_id', $schoolId)->count();
            $termCount = AcademicTerm::where('school_id', $schoolId)->count();
            $feeCount = FeesCategories::where('school_id', $schoolId)->count();
            $linkedParents = \App\Models\WhatsAppUser::where('school_id', $schoolId)->count();

            $today = now()->toDateString();
            $presentToday = Attendance::where('school_id', $schoolId)
                ->whereDate('date', $today)->count();

            // Today's attendance as percentage
            $attendedToday = Attendance::where('school_id', $schoolId)
                ->whereDate('date', $today)
                ->where('status', 'present')
                ->count();

            $report = "📊 **{$school->name} — Summary Report**\n\n"
                . "👥 **Students:** {$studentCount} across {$classCount} classes\n"
                . "👨‍🏫 **Teachers:** {$teacherCount}\n"
                . "📚 **Subjects:** {$subjectCount}\n"
                . "📅 **Terms:** {$termCount}\n"
                . "💰 **Fee categories:** {$feeCount}\n"
                . "📱 **WhatsApp parents:** {$linkedParents}\n\n"
                . "**Today ({$today}):**\n"
                . "  • Attendance records: {$presentToday} entries\n";

            if ($presentToday > 0) {
                $pct = round(($attendedToday / $presentToday) * 100);
                $report .= "  • Present: {$attendedToday}/{$presentToday} ({$pct}%)\n";
            }

            return self::result(true, $report);
        } catch (\Exception $e) {
            return self::result(false, 'Failed to generate report: ' . $e->getMessage());
        }
    }

    // ── Helpers ──

    /**
     * Find a student user by name, email, or KlassApp ID.
     */
    private static function findStudent(int $schoolId, string $identifier): ?User
    {
        // Try by email first
        $user = User::where('school_id', $schoolId)
            ->where('email', $identifier)
            ->where('usergroup_id', 6)
            ->first();
        if ($user) return $user;

        // Try KlassApp ID
        $acad = StudentAcademic::where('school_id', $schoolId)
            ->where('klassapp_student_id', $identifier)
            ->first();
        if ($acad) return User::where('id', $acad->user_id)->where('school_id', $schoolId)->first();

        // Try by name (partial match)
        $user = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('name', 'LIKE', '%' . $identifier . '%')
            ->first();
        if ($user) return $user;

        return null;
    }

    /**
     * List all sections (streams) for the school.
     */
    public static function listSections(User $admin): array
    {
        if (!self::can($admin, 'list_sections')) {
            return self::result(false, 'You do not have permission to view sections.');
        }
        $schoolId = $admin->school_id;
        if (!$schoolId) {
            return self::result(false, 'No school assigned.');
        }

        $sections = Section::where('school_id', $schoolId)->where('status', 1)->get();
        if ($sections->isEmpty()) {
            return self::result(false, 'No sections found. Add streams (A, B, C...) from the Classes section in the admin panel.');
        }

        $lines = $sections->pluck('name')->map(fn($n) => "• {$n}")->implode("\n");
        return self::result(true, "**Sections/Streams:**\n{$lines}");
    }

    /**
     * Simple student lookup by name for payment recording flows.
     * Returns User model or null.
     */
    public static function findStudentSimple(User $admin, string $identifier): ?User
    {
        return self::findStudent($admin->school_id, $identifier);
    }

    /**
     * Resolve StandardLink from class name and optional section name.
     */
    private static function resolveStandardLink(int $schoolId, ?int $academicYearId, string $className, string $sectionName = ''): ?StandardLink
    {
        // First try matching the class name against sections directly (e.g. "Primary Three", "Senior One")
        $sectionNameToUse = $sectionName ?: $className;
        $section = null;
        if ($sectionNameToUse !== '') {
            $section = Section::where('school_id', $schoolId)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($sectionNameToUse) . '%'])
                ->first();
        }
        if ($section) {
            $query = StandardLink::where('school_id', $schoolId)
                ->where('section_id', $section->id);
            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            $link = $query->first();
            if ($link) return $link;
        }

        // Fallback: match class name against standards (legacy)
        $standard = Standard::where('school_id', $schoolId)->where('name', $className)->first();
        if (!$standard) {
            $standard = Standard::where('school_id', $schoolId)
                ->where('name', 'LIKE', '%' . $className . '%')
                ->first();
        }
        if (!$standard) return null;

        $query = StandardLink::where('school_id', $schoolId)
            ->where('standard_id', $standard->id);
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->first();
    }

    // ── Exam ──

    /**
     * Preview what createExam will do without actually creating.
     * Returns a human-readable description string for the confirmation prompt.
     */
    public static function previewExam(User $admin, array $data): string
    {
        $schoolId = $admin->school_id;
        if (!$schoolId) return 'No school assigned.';

        $label = trim($data['name'] ?? '');
        if ($label === '') return 'Unnamed exam';

        // Resolve the same way createExam would
        $examTypeName = $data['type'] ?? $data['exam_type'] ?? '';
        $examType = $examTypeName
            ? ExamType::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($examTypeName) . '%'])->first()
            : null;
        $typeName = $examType?->name ?? ExamType::first()?->name ?? 'Exam';

        $className = $data['class'] ?? $data['section'] ?? '';
        $section = $className
            ? Section::where('school_id', $schoolId)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($className) . '%'])->first()
            : null;
        $sectionName = $section?->name ?? Section::where('school_id', $schoolId)->first()?->name ?? '?';

        $subjectName = $data['subject'] ?? '';
        $subject = ($subjectName && $section)
            ? Subject::where('school_id', $schoolId)->where('section_id', $section->id)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($subjectName) . '%'])->first()
            : null;
        $subjName = $subject?->name ?? Subject::where('school_id', $schoolId)->first()?->name ?? '?';

        $inferred = [];
        if (!$examTypeName) $inferred[] = 'type: ' . $typeName;
        if (!$className) $inferred[] = 'class: ' . $sectionName;
        if (!$subjectName) $inferred[] = 'subject: ' . $subjName;
        $suffix = $inferred ? ' (inferred: ' . implode(', ', $inferred) . ')' : '';

        return "Create {$typeName} exam \"{$label}\" for {$sectionName}, {$subjName}{$suffix}";
    }

    public static function createExam(User $admin, array $data): array
    {
        $schoolId = $admin->school_id;
        if (!$schoolId) return self::result(false, 'No school assigned.');
        $label = trim($data['name'] ?? '');
        if ($label === '') return self::result(false, 'Exam name is required.');

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (!$academicYear) return self::result(false, 'No academic year set.');

        // Resolve exam type from label or parameter
        $examTypeName = $data['type'] ?? $data['exam_type'] ?? '';
        $examType = null;
        if ($examTypeName) {
            $examType = ExamType::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($examTypeName) . '%'])
                ->orWhereRaw('LOWER(code) = ?', [strtolower($examTypeName)])
                ->first();
        }
        if (!$examType) {
            $examType = ExamType::first();
        }

        // Resolve term from parameter
        $termName = $data['term'] ?? '';
        $term = null;
        if ($termName) {
            $term = AcademicTerm::where('school_id', $schoolId)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($termName) . '%'])
                ->first();
        }
        if (!$term) {
            $term = AcademicTerm::where('school_id', $schoolId)->first();
        }

        // Resolve class/section from parameter
        $className = $data['class'] ?? $data['section'] ?? '';
        $section = null;
        if ($className) {
            $section = Section::where('school_id', $schoolId)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($className) . '%'])
                ->first();
        }
        if (!$section) {
            $section = Section::where('school_id', $schoolId)->first();
        }

        // Resolve subject from parameter
        $subjectName = $data['subject'] ?? '';
        $subject = null;
        if ($subjectName && $section) {
            $subject = Subject::where('school_id', $schoolId)
                ->where('section_id', $section->id)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($subjectName) . '%'])
                ->first();
        }
        if (!$subject) {
            $subject = Subject::where('school_id', $schoolId)->first();
        }

        // Resolve standard from the section (which was resolved from the class parameter)
        $standard = null;
        if ($section) {
            $stdLink = StandardLink::where('school_id', $schoolId)
                ->where('section_id', $section->id)->first();
            $standard = $stdLink ? Standard::find($stdLink->standard_id) : null;
        }
        if (!$standard) {
            $standard = Standard::where('school_id', $schoolId)->first();
        }

        // If essential params are missing, say what was inferred
        $notes = [];
        if (!($data['type'] ?? $data['exam_type'] ?? '')) $notes[] = 'exam type: ' . ($examType->name ?? 'default');
        if (!($data['class'] ?? $data['section'] ?? '')) $notes[] = 'class: ' . ($section->name ?? 'default');
        if (!($data['subject'] ?? '')) $notes[] = 'subject: ' . ($subject->name ?? 'default');
        if (!($data['term'] ?? '')) $notes[] = 'term: ' . ($term->name ?? 'default');

        try {
            $exam = Exam::create([
                'school_id' => $schoolId,
                'standard_id' => $standard->id ?? 1,
                'academic_year_id' => $academicYear->id,
                'academic_term_id' => $term?->id,
                'exam_type_id' => $examType?->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id ?? 1,
                'teacher_id' => $admin->id,
                'scheduled_at' => $data['scheduled_at'] ?? now(),
                'status' => 'undone',
            ]);

            $msg = "Exam **{$label}** created.";
            if (!empty($notes)) {
                $msg .= ' (Inferred: ' . implode(', ', $notes) . '. Be more specific next time — e.g. "create a Mid-Term exam for P4 Mathematics".)';
            }
            return self::result(true, $msg, ['exam_id' => $exam->id]);
        } catch (\Exception $e) {
            Log::error('ToshiAction: createExam failed', ['error' => $e->getMessage()]);
            return self::result(false, 'Failed to create exam: ' . $e->getMessage());
        }
    }

    // ── Parent ──

    public static function addParent(User $admin, array $data): array
    {
        $schoolId = $admin->school_id;
        if (!$schoolId) return self::result(false, 'No school assigned.');

        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $studentId = (int) ($data['student_id'] ?? 0);
        if ($name === '' || $phone === '') return self::result(false, 'Parent name and phone are required.');

        try {
            DB::beginTransaction();
            $email = 'parent.' . Str::random(6) . '@school.ug';
            $parent = User::create([
                'school_id' => $schoolId, 'usergroup_id' => 7,
                'name' => $name, 'email' => $email,
                'password' => Hash::make('password'), 'status' => 'active',
                'mobile_no' => $phone,
            ]);
            Userprofile::create([
                'school_id' => $schoolId, 'user_id' => $parent->id, 'usergroup_id' => 7,
                'firstname' => $name, 'lastname' => '', 'status' => 'active',
            ]);
            if ($studentId) {
                StudentParentLink::create([
                    'school_id' => $schoolId, 'student_id' => $studentId,
                    'parent_id' => $parent->id, 'status' => 1,
                ]);
            }
            DB::commit();
            return self::result(true, "Parent **{$name}** added.", ['parent_id' => $parent->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ToshiAction: addParent failed', ['error' => $e->getMessage()]);
            return self::result(false, 'Failed to add parent: ' . $e->getMessage());
        }
    }

    // ── Marks (single entry) ──

    public static function enterMark(User $admin, array $data): array
    {
        $schoolId = $admin->school_id;
        if (!$schoolId) return self::result(false, 'No school assigned.');

        $studentId = (int) ($data['student_id'] ?? 0);
        $examId = (int) ($data['exam_id'] ?? 0);
        $score = (float) ($data['marks'] ?? 0);
        if (!$studentId || !$examId) return self::result(false, 'Student ID and Exam ID are required.');

        $exam = Exam::find($examId);
        if (!$exam) return self::result(false, 'Exam not found.');

        try {
            // Look up grade from the actual grading system table
            $grade = null;
            $isNursery = \App\Helpers\GradingHelper::levelTypeForStandard(
                \App\Models\Standard::find($exam->standard_id)
            ) === 'nursery';
            if (!$isNursery) {
                $gradingService = app(\App\Services\GradingSystemService::class);
                $grade = $gradingService->grade((int)$score, $schoolId, $exam);
            }

            $mark = Marks::create([
                'student_id' => $studentId, 'exam_id' => $examId,
                'school_id' => $schoolId, 'subject_id' => $exam->subject_id,
                'section_id' => $exam->section_id,
                'teacher_id' => $admin->id,
                'marks' => $score,
                'grade' => $grade,
            ]);
            return self::result(true, "Mark entered: {$score}.", ['mark_id' => $mark->id]);
        } catch (\Exception $e) {
            Log::error('ToshiAction: enterMark failed', ['error' => $e->getMessage()]);
            return self::result(false, 'Failed to enter mark: ' . $e->getMessage());
        }
    }

    public static function seedDefaultGrading(User $admin): array
    {
        $schoolId = self::getEffectiveSchoolId($admin);
        if (!$schoolId) return self::result(false, 'No school ID.');

        $defaultScales = [
            ['grade' => 'A', 'min' => 80, 'max' => 100],
            ['grade' => 'B', 'min' => 70, 'max' => 79],
            ['grade' => 'C', 'min' => 55, 'max' => 69],
            ['grade' => 'D', 'min' => 40, 'max' => 54],
            ['grade' => 'E', 'min' => 0, 'max' => 39],
        ];

        $standards = \App\Models\Standard::whereHas('standardLinks', fn($q) => $q->where('school_id', $schoolId))->get();
        $count = 0;
        foreach ($standards as $standard) {
            if (!$standard->grade_scale) {
                $standard->grade_scale = json_encode($defaultScales);
                $standard->save();
                $count++;
            }
        }
        return self::result(true, "Default grading scale seeded for {$count} standards.");
    }

    public static function setGradingScale(User $admin, array $data): array
    {
        $schoolId = self::getEffectiveSchoolId($admin);
        if (!$schoolId) return self::result(false, 'No school ID.');

        $standardName = $data['standardName'] ?? '';
        $grades = $data['gradeDefinitions'] ?? [];

        $standard = \App\Models\Standard::where('school_id', $schoolId)->where('name', $standardName)->first();
        if (!$standard) return self::result(false, "Standard '{$standardName}' not found.");
        if (empty($grades)) return self::result(false, 'No grade definitions provided.');

        $validGrades = [];
        foreach ($grades as $g) {
            $validGrades[] = ['grade' => $g['grade'], 'min' => (int)$g['min'], 'max' => (int)$g['max']];
        }
        $standard->grade_scale = json_encode($validGrades);
        $standard->save();
        return self::result(true, "Grading scale updated for {$standardName}.");
    }
}
