<?php

namespace Database\Seeders;

use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The 1,000-student school the dashboard query audit was measured on:
 * 30 classes, 12 subjects, 24,000 marks over two exam sets and 800 payments.
 * Test/demo only — never seeded outside tests.
 */
class PerfDashboardFixtureSeeder extends Seeder
{
    public const STUDENTS = 1000;

    public const CLASSES = 30;

    public const SUBJECTS = 12;

    public const PAYMENTS = 800;

    public const ADMIN_EMAIL = 'perf.fixture.admin@demo.local';

    private const SUBJECT_NAMES = [
        'English', 'Mathematics', 'Science', 'Social Studies', 'Religious Education',
        'Literacy I', 'Literacy II', 'Local Language', 'Music, Dance and Drama',
        'Physical Education', 'Arts and Technology', 'Computer Studies',
    ];

    public function run(): School
    {
        $now = now();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'Student', 'created_at' => $now, 'updated_at' => $now],
        ], ['id']);

        $school = School::create([
            'name' => 'Perf Fixture School', 'slug' => 'perf-fixture', 'email' => 'perf.fixture@demo.local',
            'phone' => '+256700001111', 'status' => 1, 'registration_country' => 'Uganda', 'is_demo' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Third Term',
            'starts_on' => '2026-09-01', 'ends_on' => '2026-11-28', 'status' => 'current',
        ]);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 5, 'status' => 1]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');

        $admin = User::create([
            'school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Perf Fixture Admin',
            'email' => self::ADMIN_EMAIL, 'password' => Hash::make('fixture-unused'),
            'status' => 'active', 'email_verified' => 1,
        ]);
        DB::table('userprofiles')->insert([
            'user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3,
            'firstname' => 'Perf', 'lastname' => 'Admin', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $bot = ExamType::firstOrCreate(['code' => 'BOT'], ['name' => 'Beginning of Term Examination', 'contributes_to_report_total' => false]);
        $mot = ExamType::firstOrCreate(['code' => 'MOT'], ['name' => 'Mid Term Examination', 'contributes_to_report_total' => true]);

        $sections = [];
        $links = [];
        for ($i = 1; $i <= self::CLASSES; $i++) {
            $sectionId = DB::table('sections')->insertGetId([
                'school_id' => $school->id, 'name' => 'P.'.(($i % 7) + 1).'-'.$i,
                'status' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $sections[$i] = $sectionId;
            $links[$i] = DB::table('standards_link')->insertGetId([
                'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
                'section_id' => $sectionId, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $exams = [];
        foreach ($sections as $i => $sectionId) {
            foreach (self::SUBJECT_NAMES as $subjectName) {
                $subjectId = DB::table('subjects')->insertGetId([
                    'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
                    'section_id' => $sectionId, 'name' => $subjectName, 'type' => 'core',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                foreach (['bot' => $bot, 'mot' => $mot] as $key => $type) {
                    $exams[$i][$key][$subjectName] = [
                        'exam_id' => DB::table('exams')->insertGetId([
                            'school_id' => $school->id, 'section_id' => $sectionId, 'standard_id' => $standard->id,
                            'academic_year_id' => $year->id, 'academic_term_id' => $term->id,
                            'subject_id' => $subjectId, 'teacher_id' => $admin->id, 'exam_type_id' => $type->id,
                            'status' => 'done', 'scheduled_at' => '2026-10-01 09:00:00',
                            'created_at' => $now, 'updated_at' => $now,
                        ]),
                        'subject_id' => $subjectId,
                    ];
                }
            }
        }

        $grades = function (int $mark): string {
            foreach ([[95, 100, 'D1'], [90, 94, 'D2'], [75, 89, 'C3'], [65, 74, 'C4'], [60, 64, 'C5'], [50, 59, 'C6'], [45, 49, 'P7'], [40, 44, 'P8'], [0, 39, 'F9']] as [$min, $max, $grade]) {
                if ($mark >= $min && $mark <= $max) {
                    return $grade;
                }
            }

            return 'F9';
        };

        $perClass = intdiv(self::STUDENTS, self::CLASSES);
        $users = $profiles = $academics = $marks = [];
        for ($n = 1; $n <= self::STUDENTS; $n++) {
            $class = min(self::CLASSES, intdiv($n - 1, $perClass) + 1);
            $users[] = ['school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Learner '.$n,
                'email' => 'perf.student.'.$n.'@demo.local', 'password' => '$2y$10$fixture', 'status' => 'active',
                'email_verified' => 1, 'created_at' => $now, 'updated_at' => $now];
            $profiles[] = ['user_id' => null, 'school_id' => $school->id, 'usergroup_id' => 6,
                'firstname' => 'Learner', 'lastname' => (string) $n, 'created_at' => $now, 'updated_at' => $now];
            $academics[] = ['school_id' => $school->id, 'academic_year_id' => $year->id, 'user_id' => null,
                'standardLink_id' => $links[$class], 'klassapp_student_id' => 'KLS'.str_pad((string) (300000 + $n), 7, '0', STR_PAD_LEFT),
                'created_at' => $now, 'updated_at' => $now];

            if (count($users) >= 200) {
                $this->insertStudents($users, $profiles, $academics, $exams, $grades, $sections, $school->id, $admin->id, $now, $marks);
                $users = $profiles = $academics = [];
            }
        }
        if ($users !== []) {
            $this->insertStudents($users, $profiles, $academics, $exams, $grades, $sections, $school->id, $admin->id, $now, $marks);
        }
        if ($marks !== []) {
            DB::table('marks')->insert($marks);
        }

        $payments = [];
        $categoryIds = [];
        foreach ($sections as $i => $sectionId) {
            $categoryIds[$i] = DB::table('fees_categories')->insertGetId([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $sectionId,
                'academic_term_id' => $term->id, 'name' => 'Term III fees', 'amount' => 1200000,
                'due_date' => '2026-10-15', 'enable_installments' => 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $studentIds = DB::table('student_academics')->where('school_id', $school->id)->orderBy('user_id')->pluck('user_id')->all();
        foreach ($studentIds as $index => $studentId) {
            if ($index % 5 === 0 || count($payments) >= self::PAYMENTS) {
                continue;
            }
            $payments[] = ['school_id' => $school->id, 'fee_category_id' => $categoryIds[($index % self::CLASSES) + 1],
                'user_id' => $studentId, 'amount' => 400000, 'paid_on' => '2026-10-05', 'payment_method' => 'cash',
                'reference' => 'R'.$index, 'recorded_by' => $admin->id, 'status' => 'paid',
                'created_at' => $now, 'updated_at' => $now];
            if (count($payments) >= 400) {
                DB::table('fee_payments')->insert($payments);
                $payments = [];
            }
        }
        if ($payments !== []) {
            DB::table('fee_payments')->insert($payments);
        }

        return $school;
    }

    /**
     * @param  array<int, array<string, mixed>>  $users
     * @param  array<int, array<string, mixed>>  $profiles
     * @param  array<int, array<string, mixed>>  $academics
     */
    private function insertStudents(array &$users, array &$profiles, array &$academics, array $exams, callable $grades, array $sections, int $schoolId, int $adminId, $now, array &$marks): void
    {
        $before = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 6)->max('id') ?? 0;
        DB::table('users')->insert($users);
        $ids = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 6)->where('id', '>', $before)
            ->orderBy('id')->pluck('id')->all();

        $perClass = intdiv(self::STUDENTS, self::CLASSES);

        foreach ($users as $index => $row) {
            $id = $ids[$index];
            $studentNumber = (int) substr((string) $row['name'], strlen('Learner '));
            $class = min(self::CLASSES, intdiv($studentNumber - 1, $perClass) + 1);

            $profiles[$index]['user_id'] = $id;
            $academics[$index]['user_id'] = $id;

            foreach (self::SUBJECT_NAMES as $subjectName) {
                foreach (['bot', 'mot'] as $set) {
                    $mark = 50 + (crc32($studentNumber.'|'.$subjectName.'|'.$set) % 50);
                    $marks[] = [
                        'student_id' => $id, 'teacher_id' => $adminId, 'school_id' => $schoolId,
                        'subject_id' => $exams[$class][$set][$subjectName]['subject_id'],
                        'exam_id' => $exams[$class][$set][$subjectName]['exam_id'],
                        'section_id' => $sections[$class], 'remark_id' => null,
                        'marks' => $mark, 'grade' => $grades($mark),
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
        }

        DB::table('userprofiles')->insert($profiles);
        DB::table('student_academics')->insert($academics);
        if ($marks !== []) {
            DB::table('marks')->insert($marks);
            $marks = [];
        }
    }
}
