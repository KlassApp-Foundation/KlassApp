<?php

namespace App\Console\Commands;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\OnboardingEngine;
use App\Services\ParentLinkService;
use App\Services\ParentMagicLoginService;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Persistent UI-review demo school for manual migration walkthroughs.
 * Uses SchoolSignupBootstrapService + OnboardingEngine (same paths as
 * /register and the wizard/Toshi), then layers exams/marks/parent link.
 */
class SetupUiReviewDemoSchool extends Command
{
    protected $signature = 'schools:setup-ui-review-demo
                            {--password=UiReview2026! : Shared password for all four role logins}
                            {--force : Recreate exam/marks fixtures if school already exists}';

    protected $description = 'Create or refresh the persistent UI Review Demo School (4 role logins)';

    private const SCHOOL_NAME = 'UI Review Demo School';

    private const ADMIN_EMAIL = 'admin@uireview.klassapp.demo';

    private const CT_EMAIL = 'classteacher@uireview.klassapp.demo';

    private const SUBJECT_EMAIL = 'subjectteacher@uireview.klassapp.demo';

    private const PARENT_EMAIL = 'parent@uireview.klassapp.demo';

    /** E.164-style phone used for WhatsApp magic-link path */
    private const PARENT_PHONE = '256700119922';

    public function handle(
        SchoolSignupBootstrapService $bootstrap,
        OnboardingEngine $engine,
        ParentLinkService $parentLinks,
        ParentMagicLoginService $magicLogin,
    ): int {
        $password = (string) $this->option('password');
        $force = (bool) $this->option('force');

        $school = School::where('slug', 'ui-review-demo-school')
            ->orWhere('name', self::SCHOOL_NAME)
            ->first();

        if ($school) {
            $this->warn('School already exists (id='.$school->id.') — refreshing logins and fixtures.');
            $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->where('email', self::ADMIN_EMAIL)->first()
                ?? User::where('school_id', $school->id)->where('usergroup_id', 3)->orderBy('id')->first();
        } else {
            $admin = $bootstrap->bootstrap([
                'name' => 'UI Review Admin',
                'email' => self::ADMIN_EMAIL,
                'phone' => '0700119900',
                'password' => $password,
                'email_verified' => true,
            ]);
            $school = $admin->school;
            $this->info('Bootstrapped signup school id='.$school->id);
        }

        // OnboardingEngine path (wizard / Toshi shared writes)
        $engine->saveSchoolName($school, self::SCHOOL_NAME);
        $school->refresh();
        $engine->saveCountry($school, 'Uganda');
        $engine->saveCurriculum($school, 'uneb');
        $year = $engine->saveAcademicYear($school, (string) now()->year, now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString(), 'Current Academic Year');
        $engine->saveSchoolCategory($school, 'primary_nursery');
        $school->refresh();
        // Category seed needs the year; re-save year to ensure seeder ran after category set
        $year = $engine->saveAcademicYear($school, (string) now()->year, now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString(), 'Current Academic Year');
        $engine->saveEmis($school, 'UIREV-001');
        $engine->saveUnebCenter($school, 'U0001');

        $engine->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => now()->year.'-02-03', 'end' => now()->year.'-05-02'],
            ['name' => 'Term 2', 'start' => now()->year.'-05-26', 'end' => now()->year.'-08-22'],
            ['name' => 'Term 3', 'start' => now()->year.'-09-08', 'end' => now()->year.'-12-05'],
        ]);

        // Mark Term 1 current; others upcoming (engine defaults all to current)
        AcademicTerm::where('school_id', $school->id)->where('name', 'Term 1')->update(['status' => 'current']);
        AcademicTerm::where('school_id', $school->id)->whereIn('name', ['Term 2', 'Term 3'])->update(['status' => 'upcoming']);

        $engine->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 450000],
            ['name' => 'Lunch', 'amount' => 80000, 'class' => 'Primary Seven', 'term' => 'Term 1'],
            ['name' => 'Development', 'amount' => 50000, 'class' => 'Primary One', 'term' => 'Term 1'],
        ]);

        $p7 = $this->linkForSection($school, $year, 'Primary Seven');
        $p1 = $this->linkForSection($school, $year, 'Primary One');
        if (! $p7 || ! $p1) {
            $this->error('Expected Primary Seven and Primary One StandardLinks after category seed.');

            return self::FAILURE;
        }

        // Seeder attaches subjects only to the first section of each tier —
        // ensure P.7 and P.1 each have ≥2 subjects for exams/report cards.
        $p7Subjects = $this->ensureSubjectsForSection($school, $year, $p7, ['English Language', 'Mathematics']);
        $p1Subjects = $this->ensureSubjectsForSection($school, $year, $p1, ['English Language', 'Mathematics']);

        $ct = $this->upsertTeacher($school, self::CT_EMAIL, 'UI Review Class Teacher', '0700119901', $password);
        $subj = $this->upsertTeacher($school, self::SUBJECT_EMAIL, 'UI Review Subject Teacher', '0700119902', $password);

        $p7->class_teacher_id = $ct->id;
        $p7->save();

        foreach ($p7Subjects as $subject) {
            Teacherlink::firstOrCreate([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standardLink_id' => $p7->id,
                'subject_id' => $subject->id,
                'teacher_id' => $subject->name === 'Mathematics' ? $subj->id : $ct->id,
            ]);
        }

        $students = $engine->saveStudents($school, $year, [
            [
                'name' => 'Amina Nabukeera',
                'class' => 'Primary Seven',
                'email' => 'amina.nabukeera@uireview.klassapp.demo',
                'school_student_id' => 'UIREV-P7-001',
                'board_registration_number' => 'U1234/567',
            ],
            [
                'name' => 'Brian Okello',
                'class' => 'Primary One',
                'email' => 'brian.okello@uireview.klassapp.demo',
                'school_student_id' => 'UIREV-P1-001',
            ],
        ]);

        $p7StudentId = collect($students['created'])->firstWhere('name', 'Amina Nabukeera')['user_id']
            ?? User::where('school_id', $school->id)->where('name', 'Amina Nabukeera')->value('id');
        $p1StudentId = collect($students['created'])->firstWhere('name', 'Brian Okello')['user_id']
            ?? User::where('school_id', $school->id)->where('name', 'Brian Okello')->value('id');

        if (! $p7StudentId || ! $p1StudentId) {
            $this->error('Students were not created.');

            return self::FAILURE;
        }

        $term1 = AcademicTerm::where('school_id', $school->id)->where('name', 'Term 1')->first();
        $eotType = ExamType::where('code', 'EOT')->first()
            ?? ExamType::create(['name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1]);

        if ($force || ! Exam::where('school_id', $school->id)->exists()) {
            Exam::where('school_id', $school->id)->delete();
            Marks::where('school_id', $school->id)->delete();

            foreach ($p7Subjects as $i => $subject) {
                $teacherId = $subject->name === 'Mathematics' ? $subj->id : $ct->id;
                $exam = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $school->id,
                    'standard_id' => $p7->standard_id,
                    'section_id' => $p7->section_id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacherId,
                    'exam_type_id' => $eotType->id,
                    'academic_term_id' => $term1->id,
                    'academic_year_id' => $year->id,
                    'scheduled_at' => now()->subDays(10 - $i),
                    'status' => 'submitted',
                ]));
                Marks::create([
                    'student_id' => $p7StudentId,
                    'exam_id' => $exam->id,
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacherId,
                    'section_id' => $p7->section_id,
                    'marks' => 70 + ($i * 5),
                    'grade' => 'D1',
                ]);
            }

            foreach ($p1Subjects as $i => $subject) {
                $exam = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $school->id,
                    'standard_id' => $p1->standard_id,
                    'section_id' => $p1->section_id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $subj->id,
                    'exam_type_id' => $eotType->id,
                    'academic_term_id' => $term1->id,
                    'academic_year_id' => $year->id,
                    'scheduled_at' => now()->subDays(5 - $i),
                    'status' => 'submitted',
                ]));
                Marks::create([
                    'student_id' => $p1StudentId,
                    'exam_id' => $exam->id,
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $subj->id,
                    'section_id' => $p1->section_id,
                    'marks' => 60 + ($i * 4),
                    'grade' => 'D2',
                ]);
            }
        }

        // Parent: link via WhatsApp path, then set durable email/password for /login
        $linkResult = $parentLinks->linkByStudentId(self::PARENT_PHONE, (int) $p7StudentId, 'UI Review Parent');
        if (! $linkResult->linked || ! $linkResult->parent) {
            $this->error('Parent link failed: '.$linkResult->outcome);

            return self::FAILURE;
        }

        $parent = $linkResult->parent;
        $parent->email = self::PARENT_EMAIL;
        $parent->password = Hash::make($password);
        $parent->is_reset = 0;
        $parent->status = 'active';
        $parent->email_verified = 1;
        $parent->mobile_no = self::PARENT_PHONE;
        $parent->save();

        Userprofile::updateOrCreate(
            ['user_id' => $parent->id],
            [
                'school_id' => null,
                'usergroup_id' => 7,
                'firstname' => 'UI Review',
                'lastname' => 'Parent',
                'alternate_no' => self::PARENT_PHONE,
                'status' => 'active',
            ]
        );

        StudentParentLink::where('parent_id', $parent->id)
            ->where('student_id', $p7StudentId)
            ->update(['status' => 1]);

        // Durable passwords for admin + teachers (no forced reset for review)
        foreach ([$admin, $ct, $subj] as $user) {
            $user->password = Hash::make($password);
            $user->is_reset = 0;
            $user->status = 'active';
            $user->email_verified = 1;
            $user->save();
        }
        $admin->email = self::ADMIN_EMAIL;
        $admin->save();

        $wa = WhatsAppUser::where('phone', self::PARENT_PHONE)->first();
        $magicReady = $magicLogin->canIssueLink($parent->fresh());
        $issued = $magicReady ? $magicLogin->issueLinkForPhone(self::PARENT_PHONE, $parent->fresh()) : null;

        $payload = [
            'school_id' => $school->id,
            'school_name' => $school->fresh()->name,
            'slug' => $school->fresh()->slug,
            'category' => $school->fresh()->school_category,
            'terms' => AcademicTerm::where('school_id', $school->id)->pluck('name')->all(),
            'p7_section' => Section::find($p7->section_id)?->name,
            'p1_section' => Section::find($p1->section_id)?->name,
            'exam_count' => Exam::where('school_id', $school->id)->count(),
            'parent_whatsapp_user_id' => $wa?->id,
            'parent_magic_link_ready' => $magicReady,
            'parent_magic_link_issued' => $issued !== null,
            'logins' => [
                ['role' => 'School admin', 'email' => self::ADMIN_EMAIL, 'password' => $password, 'url' => '/admin/dashboard'],
                ['role' => 'Class teacher', 'email' => self::CT_EMAIL, 'password' => $password, 'url' => '/teacher/dashboard'],
                ['role' => 'Subject teacher', 'email' => self::SUBJECT_EMAIL, 'password' => $password, 'url' => '/teacher/dashboard'],
                ['role' => 'Parent', 'email' => self::PARENT_EMAIL, 'password' => $password, 'url' => '/parent/dashboard', 'whatsapp_phone' => self::PARENT_PHONE],
            ],
        ];

        $this->line(json_encode($payload, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function linkForSection(School $school, AcademicYear $year, string $sectionName): ?StandardLink
    {
        return StandardLink::with(['section', 'standard'])
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->whereHas('section', fn ($q) => $q->where('name', $sectionName))
            ->first();
    }

    /**
     * @param  list<string>  $names
     * @return list<Subject>
     */
    private function ensureSubjectsForSection(School $school, AcademicYear $year, StandardLink $link, array $names): array
    {
        $out = [];
        foreach ($names as $name) {
            $out[] = Subject::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $link->standard_id,
                    'section_id' => $link->section_id,
                    'name' => $name,
                ],
                [
                    'code' => strtoupper(substr(preg_replace('/\s+/', '', $name), 0, 3)),
                    'type' => 'core',
                    'status' => 1,
                ]
            );
        }

        return $out;
    }

    private function upsertTeacher(School $school, string $email, string $name, string $phone, string $password): User
    {
        $teacher = User::where('email', $email)->first();
        if (! $teacher) {
            $teacher = User::create([
                'school_id' => $school->id,
                'usergroup_id' => 5,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'status' => 'active',
                'email_verified' => 1,
                'is_reset' => 0,
                'mobile_no' => $phone,
            ]);
        } else {
            $teacher->school_id = $school->id;
            $teacher->usergroup_id = 5;
            $teacher->name = $name;
            $teacher->password = Hash::make($password);
            $teacher->status = 'active';
            $teacher->email_verified = 1;
            $teacher->is_reset = 0;
            $teacher->mobile_no = $phone;
            $teacher->save();
        }

        Userprofile::updateOrCreate(
            ['user_id' => $teacher->id],
            [
                'school_id' => $school->id,
                'usergroup_id' => 5,
                'firstname' => $name,
                'lastname' => '',
                'profession' => 'teacher',
                'status' => 'active',
                'alternate_no' => $phone,
            ]
        );

        return $teacher;
    }
}
