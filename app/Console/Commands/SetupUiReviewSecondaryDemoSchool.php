<?php

namespace App\Console\Commands;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Persistent secondary (O-Level + A-Level) UI-review demo school.
 *
 * Kept separate from schools:setup-ui-review-demo because school_category is
 * mutually exclusive — primary_nursery and o_a_level cannot share one school
 * (SchoolCategorySeeder::CATEGORIES).
 */
class SetupUiReviewSecondaryDemoSchool extends Command
{
    protected $signature = 'schools:setup-ui-review-secondary-demo
                            {--password=UiReview2026! : Shared password for role logins}
                            {--force : Recreate exam/marks fixtures if school already exists}';

    protected $description = 'Create or refresh the persistent UI Review Secondary Demo School (O+A Level)';

    private const SCHOOL_NAME = 'UI Review Secondary Demo School';

    private const SLUG = 'ui-review-secondary-demo-school';

    private const ADMIN_EMAIL = 'admin@uireview-secondary.klassapp.demo';

    private const CT_EMAIL = 'classteacher@uireview-secondary.klassapp.demo';

    private const SUBJECT_EMAIL = 'subjectteacher@uireview-secondary.klassapp.demo';

    public function handle(SchoolSignupBootstrapService $bootstrap, OnboardingEngine $engine): int
    {
        $password = (string) $this->option('password');
        $force = (bool) $this->option('force');

        $school = School::where('slug', self::SLUG)
            ->orWhere('name', self::SCHOOL_NAME)
            ->first();

        if ($school) {
            $this->warn('Secondary demo school already exists (id='.$school->id.') — refreshing fixtures.');
            $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->where('email', self::ADMIN_EMAIL)->first()
                ?? User::where('school_id', $school->id)->where('usergroup_id', 3)->orderBy('id')->first();
        } else {
            $admin = $bootstrap->bootstrap([
                'name' => 'UI Review Secondary Admin',
                'email' => self::ADMIN_EMAIL,
                'phone' => '0700119910',
                'password' => $password,
                'email_verified' => true,
            ]);
            $school = $admin->school;
            $this->info('Bootstrapped secondary signup school id='.$school->id);
        }

        $engine->saveSchoolName($school, self::SCHOOL_NAME);
        $school->refresh();
        if ($school->slug !== self::SLUG) {
            $school->slug = self::SLUG;
            $school->save();
        }

        $engine->saveCountry($school, 'Uganda');
        $engine->saveCurriculum($school, 'uneb');
        $year = $engine->saveAcademicYear(
            $school,
            (string) now()->year,
            now()->startOfYear()->toDateString(),
            now()->endOfYear()->toDateString(),
            'Current Academic Year'
        );
        $engine->saveSchoolCategory($school, 'o_a_level');
        $school->refresh();
        // Category seed needs the year; re-save so SchoolCategorySeeder runs after category is set.
        $year = $engine->saveAcademicYear(
            $school,
            (string) now()->year,
            now()->startOfYear()->toDateString(),
            now()->endOfYear()->toDateString(),
            'Current Academic Year'
        );
        $engine->saveEmis($school, 'UIREV-SEC-001');
        $engine->saveUnebCenter($school, 'U0002');

        $engine->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => now()->year.'-02-03', 'end' => now()->year.'-05-02'],
            ['name' => 'Term 2', 'start' => now()->year.'-05-26', 'end' => now()->year.'-08-22'],
            ['name' => 'Term 3', 'start' => now()->year.'-09-08', 'end' => now()->year.'-12-05'],
        ]);
        AcademicTerm::where('school_id', $school->id)->where('name', 'Term 1')->update(['status' => 'current']);
        AcademicTerm::where('school_id', $school->id)->whereIn('name', ['Term 2', 'Term 3'])->update(['status' => 'next']);

        // School-wide Tuition → one FeesCategories row per Standard (o-level + a-level).
        $engine->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 650000],
            ['name' => 'Boarding', 'amount' => 400000, 'class' => 'Senior Four', 'term' => 'Term 1'],
            ['name' => 'Lab Fee', 'amount' => 75000, 'class' => 'Senior Six', 'term' => 'Term 1'],
        ]);

        $s4 = $this->linkForSection($school, $year, 'Senior Four');
        $s1 = $this->linkForSection($school, $year, 'Senior One');
        $s6 = $this->linkForSection($school, $year, 'Senior Six');
        if (! $s4 || ! $s1 || ! $s6) {
            $this->error('Expected Senior One / Four / Six StandardLinks after o_a_level category seed.');

            return self::FAILURE;
        }

        $s4Subjects = $this->ensureSubjectsForSection($school, $year, $s4, [
            'English Language',
            'General Mathematics',
            'Biology',
            'Chemistry',
            'Physics',
        ]);
        $s6Subjects = $this->ensureSubjectsForSection($school, $year, $s6, [
            'General Paper',
            'Biology',
            'Chemistry',
            'Physics',
        ]);
        $s1Subjects = $this->ensureSubjectsForSection($school, $year, $s1, [
            'English Language',
            'General Mathematics',
        ]);

        $ct = $this->upsertTeacher($school, self::CT_EMAIL, 'UI Review Secondary Class Teacher', '0700119911', $password);
        $subj = $this->upsertTeacher($school, self::SUBJECT_EMAIL, 'UI Review Secondary Subject Teacher', '0700119912', $password);

        $s4->class_teacher_id = $ct->id;
        $s4->save();

        foreach ($s4Subjects as $subject) {
            Teacherlink::firstOrCreate([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standardLink_id' => $s4->id,
                'subject_id' => $subject->id,
                'teacher_id' => in_array($subject->name, ['General Mathematics', 'Physics'], true) ? $subj->id : $ct->id,
            ]);
        }

        $studentSpecs = [
            [
                'name' => 'Diana Namukasa',
                'class' => 'Senior Four',
                'email' => 'diana.namukasa@uireview-secondary.klassapp.demo',
                'school_student_id' => 'UIREV-S4-001',
                'board_registration_number' => 'U5678/901',
                'gender' => 'female',
            ],
            [
                'name' => 'Eric Ssempala',
                'class' => 'Senior Four',
                'email' => 'eric.ssempala@uireview-secondary.klassapp.demo',
                'school_student_id' => 'UIREV-S4-002',
                'board_registration_number' => 'U5678/902',
                'gender' => 'male',
            ],
            [
                'name' => 'Faith Atwine',
                'class' => 'Senior Six',
                'email' => 'faith.atwine@uireview-secondary.klassapp.demo',
                'school_student_id' => 'UIREV-S6-001',
                'board_registration_number' => 'U9012/345',
                'gender' => 'female',
            ],
            [
                'name' => 'George Tumusiime',
                'class' => 'Senior One',
                'email' => 'george.tumusiime@uireview-secondary.klassapp.demo',
                'school_student_id' => 'UIREV-S1-001',
                'gender' => 'male',
            ],
            [
                'name' => 'Hannah Kyomuhendo',
                'class' => 'Senior One',
                'email' => 'hannah.kyomuhendo@uireview-secondary.klassapp.demo',
                'school_student_id' => 'UIREV-S1-002',
                'gender' => 'female',
            ],
        ];

        $studentIds = [];
        foreach ($studentSpecs as $spec) {
            $student = $this->upsertStudent($school, $year, $engine, $spec);
            $studentIds[$spec['name']] = $student->id;
        }

        $s4StudentId = $studentIds['Diana Namukasa'] ?? null;
        $s6StudentId = $studentIds['Faith Atwine'] ?? null;
        if (! $s4StudentId || ! $s6StudentId) {
            $this->error('Candidate-class students were not created.');

            return self::FAILURE;
        }

        $term1 = AcademicTerm::where('school_id', $school->id)->where('name', 'Term 1')->first();
        $eotType = ExamType::where('code', 'EOT')->first()
            ?? ExamType::create(['name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1]);

        if ($force || ! Exam::where('school_id', $school->id)->exists()) {
            Exam::where('school_id', $school->id)->delete();
            Marks::where('school_id', $school->id)->delete();

            foreach ($s4Subjects as $i => $subject) {
                $teacherId = in_array($subject->name, ['General Mathematics', 'Physics'], true) ? $subj->id : $ct->id;
                $exam = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $school->id,
                    'standard_id' => $s4->standard_id,
                    'section_id' => $s4->section_id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacherId,
                    'exam_type_id' => $eotType->id,
                    'academic_term_id' => $term1->id,
                    'academic_year_id' => $year->id,
                    'scheduled_at' => now()->subDays(12 - $i),
                    'status' => 'submitted',
                ]));
                foreach (['Diana Namukasa', 'Eric Ssempala'] as $j => $name) {
                    Marks::create([
                        'student_id' => $studentIds[$name],
                        'exam_id' => $exam->id,
                        'school_id' => $school->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacherId,
                        'section_id' => $s4->section_id,
                        'marks' => 72 + ($i * 3) - ($j * 4),
                        'grade' => 'B',
                    ]);
                }
            }

            foreach ($s6Subjects as $i => $subject) {
                $exam = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $school->id,
                    'standard_id' => $s6->standard_id,
                    'section_id' => $s6->section_id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $subj->id,
                    'exam_type_id' => $eotType->id,
                    'academic_term_id' => $term1->id,
                    'academic_year_id' => $year->id,
                    'scheduled_at' => now()->subDays(6 - $i),
                    'status' => 'submitted',
                ]));
                Marks::create([
                    'student_id' => $s6StudentId,
                    'exam_id' => $exam->id,
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $subj->id,
                    'section_id' => $s6->section_id,
                    'marks' => 78 + ($i * 2),
                    'grade' => 'A',
                ]);
            }

            foreach ($s1Subjects as $i => $subject) {
                $exam = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $school->id,
                    'standard_id' => $s1->standard_id,
                    'section_id' => $s1->section_id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $subj->id,
                    'exam_type_id' => $eotType->id,
                    'academic_term_id' => $term1->id,
                    'academic_year_id' => $year->id,
                    'scheduled_at' => now()->subDays(3 - $i),
                    'status' => 'submitted',
                ]));
                Marks::create([
                    'student_id' => $studentIds['George Tumusiime'],
                    'exam_id' => $exam->id,
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $subj->id,
                    'section_id' => $s1->section_id,
                    'marks' => 65 + ($i * 5),
                    'grade' => 'C',
                ]);
            }
        }

        foreach ([$admin, $ct, $subj] as $user) {
            $user->password = Hash::make($password);
            $user->is_reset = 0;
            $user->status = 'active';
            $user->email_verified = 1;
            $user->save();
        }
        $admin->email = self::ADMIN_EMAIL;
        $admin->save();

        $tuitionLabels = FeesCategories::with('standard')
            ->where('school_id', $school->id)
            ->where('name', 'Tuition')
            ->get()
            ->map(fn (FeesCategories $fee) => $fee->labeledName())
            ->values()
            ->all();

        $payload = [
            'school_id' => $school->id,
            'school_name' => $school->fresh()->name,
            'slug' => $school->fresh()->slug,
            'category' => $school->fresh()->school_category,
            'sections' => Section::where('school_id', $school->id)->orderBy('id')->pluck('name')->all(),
            'tuition_labels' => $tuitionLabels,
            'exam_count' => Exam::where('school_id', $school->id)->count(),
            's4_student_id' => $s4StudentId,
            's4_board_reg' => 'U5678/901',
            's6_student_id' => $s6StudentId,
            's6_board_reg' => 'U9012/345',
            'gender_counts' => [
                'female' => User::ByActive()->BySchool($school->id)->ByRole(6)->ByGender('female')->count(),
                'male' => User::ByActive()->BySchool($school->id)->ByRole(6)->ByGender('male')->count(),
            ],
            'logins' => [
                ['role' => 'School admin', 'email' => self::ADMIN_EMAIL, 'password' => $password, 'url' => '/admin/dashboard'],
                ['role' => 'Class teacher (S.4)', 'email' => self::CT_EMAIL, 'password' => $password, 'url' => '/teacher/dashboard'],
                ['role' => 'Subject teacher', 'email' => self::SUBJECT_EMAIL, 'password' => $password, 'url' => '/teacher/dashboard'],
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

    /**
     * @param  array{name: string, class: string, email: string, school_student_id?: string, board_registration_number?: string, gender?: string}  $spec
     */
    private function upsertStudent(School $school, AcademicYear $year, OnboardingEngine $engine, array $spec): User
    {
        $existing = User::where('school_id', $school->id)->where('email', $spec['email'])->first();
        if ($existing) {
            $this->applyGender($existing, $spec['gender'] ?? null);

            return $existing;
        }

        $result = $engine->saveStudents($school, $year, [[
            'name' => $spec['name'],
            'class' => $spec['class'],
            'email' => $spec['email'],
            'school_student_id' => $spec['school_student_id'] ?? null,
            'board_registration_number' => $spec['board_registration_number'] ?? null,
        ]]);

        $userId = collect($result['created'])->firstWhere('name', $spec['name'])['user_id']
            ?? User::where('school_id', $school->id)->where('email', $spec['email'])->value('id')
            ?? User::where('school_id', $school->id)->where('name', $spec['name'])->value('id');

        $student = User::findOrFail($userId);
        $this->applyGender($student, $spec['gender'] ?? null);

        return $student;
    }

    private function applyGender(User $student, ?string $gender): void
    {
        if (! in_array($gender, ['male', 'female'], true)) {
            return;
        }

        Userprofile::updateOrCreate(
            ['user_id' => $student->id],
            [
                'school_id' => $student->school_id,
                'usergroup_id' => 6,
                'firstname' => $student->name,
                'lastname' => '',
                'profession' => 'student',
                'status' => 'active',
                'gender' => $gender,
            ]
        );
    }
}
