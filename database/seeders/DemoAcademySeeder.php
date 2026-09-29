<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Support\DemoSeedPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo Academy Uganda — the single canonical demo school.
 *
 * Run on purpose only:
 *     php artisan db:seed --class=DemoAcademySeeder
 *
 * - This is a SEEDER, not a migration, and it is referenced by no other
 *   seeder: it never runs automatically on any environment, and it must
 *   never be wired into deploy commands.
 * - The school is marked is_demo, so DemoSchoolCommsGuard blocks every
 *   outbound WhatsApp, SMS, email and push for it and for its users.
 * - Screenshot capture scripts target this school (slug: demo-academy-uganda).
 *
 * Data: nursery + primary classes with streams on the upper primary, learners,
 * staff (head teacher, teachers, bursar, librarian, receptionist), fee
 * categories and payments (paid, instalment and owing mixes), ten school days
 * of attendance, and end-of-term exams with marks for every primary class.
 */
class DemoAcademySeeder extends Seeder
{
    private const DOMAIN = 'demo.klassapp.test';

    private School $school;

    private AcademicYear $year;

    private string $password;

    private ?User $bursar = null;

    /** @var array<string, Section> */
    private array $sections = [];

    /** @var array<string, StandardLink> */
    private array $links = [];

    /** @var array<string, User> */
    private array $staff = [];

    /** @var array<int, array{grade:string,points:int,min_score:int,max_score:int,remark:string}> */
    private array $primaryGrades = [
        ['grade' => 'D1', 'points' => 1, 'min_score' => 95, 'max_score' => 100, 'remark' => 'Excellent'],
        ['grade' => 'D2', 'points' => 2, 'min_score' => 90, 'max_score' => 94, 'remark' => 'Very Good'],
        ['grade' => 'C3', 'points' => 3, 'min_score' => 75, 'max_score' => 89, 'remark' => 'Good'],
        ['grade' => 'C4', 'points' => 4, 'min_score' => 65, 'max_score' => 74, 'remark' => 'Fair'],
        ['grade' => 'C5', 'points' => 5, 'min_score' => 60, 'max_score' => 64, 'remark' => 'Needs Effort'],
        ['grade' => 'C6', 'points' => 6, 'min_score' => 50, 'max_score' => 59, 'remark' => 'Weak'],
        ['grade' => 'P7', 'points' => 7, 'min_score' => 45, 'max_score' => 49, 'remark' => 'Poor'],
        ['grade' => 'P8', 'points' => 8, 'min_score' => 40, 'max_score' => 44, 'remark' => 'Very Poor'],
        ['grade' => 'F9', 'points' => 9, 'min_score' => 0, 'max_score' => 39, 'remark' => 'Fail'],
    ];

    public function run(): void
    {
        $this->password = DemoSeedPassword::resolve();

        $this->command?->info('Demo Academy Uganda — manual seed (never automatic; is_demo comms guard applies).');

        $this->seedSchool();
        $this->seedStaff();
        $this->seedClassesAndSubjects();
        $this->seedStudents();
        $this->seedSecondaryPle();
        $this->seedFees();
        $this->seedAttendance();
        $this->seedExamsAndMarks();

        $this->command?->info('Demo Academy Uganda seeded.');
        $this->command?->line('School: ' . $this->school->name . ' (slug ' . $this->school->slug . ', is_demo=1)');
        $this->command?->line('Admin: admin@' . self::DOMAIN . ' | Head teacher: headteacher@' . self::DOMAIN);
        $this->command?->line('Teacher: teacher1@' . self::DOMAIN . ' | Bursar: bursar@' . self::DOMAIN);
        $this->command?->line('Password: not echoed — pin via STAGING_DEMO_PASSWORD / DEMO_SEED_PASSWORD, or a random value was generated for this run.');
    }

    // ──────────────────────────────────────────────────────────────── school

    private function seedSchool(): void
    {
        // Key on email: SchoolObserver::created() overwrites slug with Str::slug(name).
        $school = School::firstOrCreate(
            ['email' => 'demo-academy@klassapp.xyz'],
            [
                'name' => 'Demo Academy Uganda',
                'slug' => 'demo-academy-uganda',
                'phone' => '077' . random_int(1000000, 9999999),
                'registration_country' => 'Uganda',
                'curriculum' => 'UNEB',
                'status' => 1,
            ]
        );

        $school->forceFill([
            'name' => 'Demo Academy Uganda',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
            'is_demo' => 1,
            'is_test' => 1,
            'toshi_enabled' => 1,
        ])->save();

        $this->school = $school->refresh();

        $this->year = AcademicYear::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => '2026'],
            [
                'description' => 'Demo Academy Uganda academic year',
                'start_date' => '2026-02-02',
                'end_date' => '2026-12-04',
                'status' => 1,
            ]
        );

        foreach ([
            ['name' => 'Term I', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30', 'status' => 'last'],
            ['name' => 'Term II', 'starts_on' => '2026-05-18', 'ends_on' => '2026-08-21', 'status' => 'last'],
            ['name' => 'Term III', 'starts_on' => '2026-09-14', 'ends_on' => '2026-12-04', 'status' => 'current'],
        ] as $term) {
            AcademicTerm::firstOrCreate(
                ['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'name' => $term['name']],
                ['starts_on' => $term['starts_on'], 'ends_on' => $term['ends_on'], 'status' => $term['status']]
            );
        }
    }

    // ──────────────────────────────────────────────────────────────── staff

    private function seedStaff(): void
    {
        $this->staff['admin'] = $this->user('admin', 'Daniel Mukasa', 3);
        $this->staff['head'] = $this->user('headteacher', 'Sarah Nabukenya', 4);
        $this->bursar = $this->user('bursar', 'Robert Ssekandi', 11);
        $this->staff['librarian'] = $this->user('library', 'Alice Kirabo', 8);
        $this->staff['reception'] = $this->user('reception', 'Brian Tumusiime', 10);

        $teachers = [
            'Grace Nabirye', 'Peter Okello', 'Rita Auma', 'Samuel Kigozi',
            'Joan Achen', 'Moses Ssentamu', 'Esther Nansubuga', 'Isaac Tumwine',
            'Diana Kembabazi', 'Paul Musoke',
        ];
        foreach ($teachers as $i => $name) {
            $this->staff['teacher' . ($i + 1)] = $this->user('teacher' . ($i + 1), $name, 5);
        }

        // Best effort: the head teacher also carries the principal designation.
        try {
            $profile = new \App\Models\TeacherProfile;
            $table = $profile->getTable();
            if (! \DB::table($table)->where('user_id', $this->staff['head']->id)->exists()) {
                \DB::table($table)->insert([
                    'user_id' => $this->staff['head']->id,
                    'school_id' => $this->school->id,
                    'academic_year_id' => $this->year->id,
                    'designation' => 'principal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::info('demo-academy seed: principal profile skipped: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────── classes and subjects

    private function seedClassesAndSubjects(): void
    {
        $nursery = Standard::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'nursery'],
            ['order' => 1, 'status' => 1]
        );
        $primary = Standard::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'primary'],
            ['order' => 2, 'status' => 1]
        );
        $secondary = Standard::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'o-level'],
            ['order' => 3, 'status' => 1]
        );

        $teacherIds = [];
        foreach (array_keys($this->staff) as $key) {
            if (str_starts_with($key, 'teacher')) {
                $teacherIds[] = $this->staff[$key]->id;
            }
        }

        $classes = [
            ['name' => 'Baby Class', 'level' => 'nursery', 'streams' => [null]],
            ['name' => 'Middle Class', 'level' => 'nursery', 'streams' => [null]],
            ['name' => 'Top Class', 'level' => 'nursery', 'streams' => [null]],
            ['name' => 'Primary One', 'level' => 'primary', 'streams' => [null]],
            ['name' => 'Primary Two', 'level' => 'primary', 'streams' => [null]],
            ['name' => 'Primary Three', 'level' => 'primary', 'streams' => [null]],
            ['name' => 'Primary Four', 'level' => 'primary', 'streams' => [null]],
            ['name' => 'Primary Five', 'level' => 'primary', 'streams' => ['A', 'B']],
            ['name' => 'Primary Six', 'level' => 'primary', 'streams' => ['A', 'B']],
            ['name' => 'Primary Seven', 'level' => 'primary', 'streams' => ['A', 'B']],
            ['name' => 'Senior One', 'level' => 'secondary', 'streams' => [null]],
            ['name' => 'Senior Two', 'level' => 'secondary', 'streams' => [null]],
            ['name' => 'Senior Three', 'level' => 'secondary', 'streams' => [null]],
            ['name' => 'Senior Four', 'level' => 'secondary', 'streams' => [null]],
        ];

        $teacherIndex = 0;

        foreach ($classes as $class) {
            $standard = match ($class['level']) {
                'nursery' => $nursery,
                'secondary' => $secondary,
                default => $primary,
            };

            $section = Section::firstOrCreate(
                ['school_id' => $this->school->id, 'name' => $class['name']],
                ['status' => 1]
            );

            $classTeacher = $teacherIds[$teacherIndex % count($teacherIds)];
            $teacherIndex++;
            $section->forceFill(['class_teacher_id' => $classTeacher, 'status' => 1])->save();
            $this->sections[$class['name']] = $section;

            foreach ($class['streams'] as $stream) {
                $key = $class['name'] . ($stream ? ' ' . $stream : '');
                $link = StandardLink::firstOrCreate(
                    [
                        'school_id' => $this->school->id,
                        'academic_year_id' => $this->year->id,
                        'section_id' => $section->id,
                        'stream' => $stream,
                    ],
                    [
                        'standard_id' => $standard->id,
                        'class_teacher_id' => $classTeacher,
                        'status' => 1,
                    ]
                );
                $link->forceFill([
                    'standard_id' => $standard->id,
                    'class_teacher_id' => $classTeacher,
                    'status' => 1,
                ])->save();
                $this->links[$key] = $link;
            }

            $subjectNames = match ($class['level']) {
                'nursery' => ['Language' => 'LANG', 'Numbers' => 'NUM', 'Reading' => 'READ'],
                'secondary' => [
                    'Mathematics' => 'MTC', 'English' => 'ENG', 'Physics' => 'PHY',
                    'Chemistry' => 'CHE', 'Biology' => 'BIO', 'Geography' => 'GEO', 'History' => 'HIS',
                ],
                default => ['Mathematics' => 'MTC', 'English' => 'ENG', 'Science' => 'SCI', 'Social Studies' => 'SST'],
            };

            foreach ($subjectNames as $name => $code) {
                $subject = Subject::firstOrCreate(
                    [
                        'school_id' => $this->school->id,
                        'academic_year_id' => $this->year->id,
                        'section_id' => $section->id,
                        'name' => $name,
                    ],
                    [
                        'standard_id' => $standard->id,
                        'code' => $code,
                        'type' => 'core',
                        'status' => 1,
                    ]
                );

                $subjectTeacher = $teacherIds[($teacherIndex + array_search($code, array_values($subjectNames), true)) % count($teacherIds)];

                foreach ($class['streams'] as $stream) {
                    $link = $this->links[$class['name'] . ($stream ? ' ' . $stream : '')];
                    Teacherlink::firstOrCreate(
                        [
                            'school_id' => $this->school->id,
                            'academic_year_id' => $this->year->id,
                            'standardLink_id' => $link->id,
                            'subject_id' => $subject->id,
                        ],
                        ['teacher_id' => $subjectTeacher]
                    );
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────── students

    private function seedStudents(): void
    {
        $firsts = ['Aisha', 'Brian', 'Carol', 'Daphine', 'Eric', 'Faith', 'Godfrey', 'Hawa', 'Ivan', 'Joan', 'Kevin', 'Lillian', 'Moses', 'Naomi', 'Oscar', 'Patience', 'Ronald', 'Sylvia', 'Timothy', 'Winnie', 'Aaron', 'Betty', 'Chris', 'Doreen', 'Elias', 'Fiona', 'Gerald', 'Harriet', 'Isaac', 'Jesca', 'Kato', 'Lydia', 'Martin', 'Norah', 'Patrick', 'Queen', 'Reagan', 'Stella', 'Tonny', 'Ursula', 'Vincent', 'Winnie', 'Yasin', 'Zainah', 'Arnold', 'Brenda', 'Cyrus', 'Denis', 'Esther', 'Fred', 'Grace', 'Henry', 'Irene', 'Joel', 'Kevin', 'Lorna', 'Michael', 'Nancy', 'Owen', 'Proscovia', 'Rashid', 'Sonia', 'Trevor', 'Vivian', 'Wilson', 'Yvonne'];
        $lasts = ['Nabukenya', 'Okello', 'Auma', 'Kigozi', 'Achen', 'Ssentamu', 'Nansubuga', 'Tumwine', 'Wandera', 'Achieng', 'Kirabo', 'Tumusiime', 'Namuli', 'Ssekandi', 'Mukasa', 'Nakato', 'Otieno', 'Amooti', 'Byaruhanga', 'Kyeyune', 'Nsubuga', 'Babirye', 'Kizza', 'Ndagire', 'Wasswa', 'Nalubega', 'Kato', 'Nabirye', 'Odongo', 'Amongin', 'Mbabazi', 'Lubega', 'Namugga', 'Ochieng', 'Turyasingha', 'Kembabazi', 'Mugisha', 'Nassiwa', 'Balewa', 'Kawooya'];

        $plan = [
            'Baby Class' => 4, 'Middle Class' => 4, 'Top Class' => 4,
            'Primary One' => 6, 'Primary Two' => 6, 'Primary Three' => 6, 'Primary Four' => 6,
            'Primary Five' => 10, 'Primary Six' => 10, 'Primary Seven' => 10,
            'Senior One' => 5, 'Senior Two' => 5, 'Senior Three' => 4, 'Senior Four' => 4,
        ];

        $n = 0;
        foreach ($plan as $className => $count) {
            $linkKeys = array_values(array_filter(array_keys($this->links), fn ($k) => $k === $className || str_starts_with($k, $className . ' ')));

            for ($i = 0; $i < $count; $i++) {
                $n++;
                $first = $firsts[($n * 7) % count($firsts)];
                $last = $lasts[($n * 11) % count($lasts)];
                $student = $this->user('student' . $n, $first . ' ' . $last, 6);

                $link = $this->links[$linkKeys[$i % count($linkKeys)]];

                $academic = StudentAcademic::updateOrCreate(
                    [
                        'school_id' => $this->school->id,
                        'academic_year_id' => $this->year->id,
                        'user_id' => $student->id,
                    ],
                    [
                        'standardLink_id' => $link->id,
                        'academic_status' => 'pass',
                        'lin' => $n % 3 === 0 ? 'UG-DEMO-LIN-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT) : null,
                    ]
                );
                unset($academic);
            }
        }

        // Keep no_of_students in step for any link a capture script may show.
        foreach ($this->links as $link) {
            $link->forceFill([
                'no_of_students' => StudentAcademic::where('school_id', $this->school->id)
                    ->where('standardLink_id', $link->id)
                    ->count(),
            ])->save();
        }
    }

    // ─────────────────────────────────────── secondary PLE entry records

    /**
     * A few Senior One learners with full PLE results, so docs and landing
     * shots can show the secondary side: PLE index/aggregate land where the
     * app actually keeps them (approved S.1 admission records).
     */
    private function seedSecondaryPle(): void
    {
        if (\DB::table('admissions')->where('school_id', $this->school->id)->exists()) {
            $this->command?->info('Secondary PLE records already seeded — skipping.');

            return;
        }

        $seniorOne = $this->sections['Senior One'] ?? null;
        $secondary = Standard::where('school_id', $this->school->id)->where('name', 'o-level')->first();
        if (! $seniorOne || ! $secondary) {
            return;
        }

        $linkIds = [];
        foreach ($this->links as $link) {
            if ($link->section_id === $seniorOne->id) {
                $linkIds[] = $link->id;
            }
        }

        $learners = StudentAcademic::where('school_id', $this->school->id)
            ->whereIn('standardLink_id', $linkIds)
            ->with('user')
            ->orderBy('id')
            ->take(3)
            ->get();

        $aggregates = ['9', '12', '16'];
        foreach ($learners as $i => $academic) {
            $user = $academic->user;
            \DB::table('admissions')->insert([
                'school_id' => $this->school->id,
                'standard_id' => $secondary->id,
                'entry_term' => '1',
                'entry_year' => '2026',
                'boarding_type' => 'boarding',
                'name' => explode(' ', $user->name)[0],
                'lastname' => explode(' ', $user->name, 2)[1] ?? 'Demo',
                'date_of_birth' => '2013-0' . (3 + $i) . '-12',
                'gender' => 'female',
                'nationality' => 'Ugandan',
                'home_district' => 'Kampala',
                'village_town' => 'Ntinda',
                'permanent_address' => '12 Kampala Road',
                'address_for_communication' => '12 Kampala Road',
                'school_last_studied' => 'Demo Academy Primary',
                'last_class_completed' => 'P.7',
                'ple_index_number' => 'DEMO/2025/' . str_pad((string) (101 + $i), 4, '0', STR_PAD_LEFT),
                'ple_aggregate' => $aggregates[$i] ?? '12',
                'father_name' => 'Demo Parent',
                'father_relationship' => 'Father',
                'father_mobile_no' => '+2567700001' . (10 + $i),
                'father_district' => 'Kampala',
                'application_status' => 'Approved',
                'application_no' => 'APP-FORM-DEMO-' . now()->format('Y') . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'payment_status' => 'paid',
                'half_yearly_mark_details' => '{}',
                'remarks' => 'Seeded PLE entry record for the secondary demo.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Secondary PLE records seeded: ' . $learners->count() . ' approved S.1 entries with PLE results.');
    }

    // ───────────────────────────────────────────────────────────────── fees

    private function seedFees(): void
    {
        $termOne = AcademicTerm::where('school_id', $this->school->id)
            ->where('name', 'Term I')
            ->first();

        $nursery = Standard::where('school_id', $this->school->id)->where('name', 'nursery')->first();
        $primary = Standard::where('school_id', $this->school->id)->where('name', 'primary')->first();
        $oLevel = Standard::where('school_id', $this->school->id)->where('name', 'o-level')->first();

        $definitions = [
            ['standard' => $nursery, 'name' => 'Tuition', 'amount' => 450000, 'type' => 'tuition'],
            ['standard' => $nursery, 'name' => 'Lunch', 'amount' => 90000, 'type' => 'lunch'],
            ['standard' => $primary, 'name' => 'Tuition', 'amount' => 620000, 'type' => 'tuition'],
            ['standard' => $primary, 'name' => 'Lunch', 'amount' => 110000, 'type' => 'lunch'],
            ['standard' => $primary, 'name' => 'Transport', 'amount' => 150000, 'type' => 'transport'],
            ['standard' => $oLevel, 'name' => 'Tuition', 'amount' => 780000, 'type' => 'tuition'],
            ['standard' => $oLevel, 'name' => 'Lunch', 'amount' => 120000, 'type' => 'lunch'],
        ];

        $categories = [];
        foreach ($definitions as $def) {
            $categories[$def['name'] . ':' . $def['standard']->id] = FeesCategories::firstOrCreate(
                [
                    'school_id' => $this->school->id,
                    'standard_id' => $def['standard']->id,
                    'name' => $def['name'],
                ],
                [
                    'academic_term_id' => $termOne?->id,
                    'amount' => $def['amount'],
                    'due_date' => '2026-04-30',
                    'fee_type' => $def['type'],
                ]
            )->refresh();
        }

        if (FeePayment::where('school_id', $this->school->id)->exists()) {
            $this->command?->info('Fees already seeded for Demo Academy — skipping payments.');

            return;
        }

        $students = StudentAcademic::where('school_id', $this->school->id)
            ->with('user')
            ->get();

        $index = 0;
        foreach ($students as $academic) {
            if (! $academic->user) {
                continue;
            }
            $index++;

            $link = $this->links[array_search($academic->standardLink_id, array_map(fn ($l) => $l->id, $this->links), true)] ?? null;
            $standardName = null;
            if ($link) {
                $standardName = Standard::where('id', $link->standard_id)->value('name');
            }
            if (! $standardName) {
                continue;
            }
            $standardId = $link->standard_id;
            $tuition = $categories['Tuition:' . $standardId] ?? null;
            $lunch = $categories['Lunch:' . $standardId] ?? null;
            if (! $tuition) {
                continue;
            }

            $roll = $index % 12;
            $paidOn = fn (int $dayOffset) => now()->subDays($dayOffset)->toDateString();

            if ($roll <= 5) {
                // Paid in full, some recent.
                $this->payment($academic->user_id, $tuition, $tuition->amount, $paidOn(30 + $roll), 'mobile_money');
                if ($lunch && $roll % 2 === 0) {
                    $this->payment($academic->user_id, $lunch, $lunch->amount, $paidOn(20 + $roll), 'cash');
                }
            } elseif ($roll <= 7) {
                // Two instalments.
                $half = (int) round($tuition->amount / 2);
                $this->payment($academic->user_id, $tuition, $half, $paidOn(45), 'bank_transfer', 'INST-' . $index . '-1');
                $this->payment($academic->user_id, $tuition, $half, $paidOn(9), 'mobile_money', 'INST-' . $index . '-2');
            } elseif ($roll === 8) {
                // Partial — shows a balance.
                $this->payment($academic->user_id, $tuition, 200000, $paidOn(14), 'cash');
            } elseif ($roll === 9 && $lunch) {
                // Lunch only so far.
                $this->payment($academic->user_id, $lunch, $lunch->amount, $paidOn(7), 'cash');
            }
            // rolls 10-11: nothing paid yet (owing).
        }

        $this->command?->info('Fees seeded: ' . FeePayment::where('school_id', $this->school->id)->count() . ' payments.');
    }

    private function payment(int $studentId, FeesCategories $category, float|int $amount, string $paidOn, string $method, ?string $reference = null): void
    {
        FeePayment::create([
            'school_id' => $this->school->id,
            'fee_category_id' => $category->id,
            'user_id' => $studentId,
            'amount' => $amount,
            'paid_on' => $paidOn,
            'payment_method' => $method,
            'reference' => $reference,
            'recorded_by' => $this->bursar->id,
            'status' => 'paid',
        ]);
    }

    // ───────────────────────────────────────────────────────────── attendance

    private function seedAttendance(): void
    {
        if (Attendance::where('school_id', $this->school->id)->exists()) {
            $this->command?->info('Attendance already seeded for Demo Academy — skipping.');

            return;
        }

        $students = StudentAcademic::where('school_id', $this->school->id)->get(['user_id', 'standardLink_id']);
        $studentsByLink = [];
        foreach ($students as $s) {
            $studentsByLink[$s->standardLink_id][] = $s->user_id;
        }

        $classTeachers = [];
        foreach ($this->links as $link) {
            $classTeachers[$link->id] = $link->class_teacher_id;
        }

        $days = [];
        $cursor = now()->copy();
        while (count($days) < 10) {
            if (! $cursor->isWeekend()) {
                $days[] = $cursor->toDateString();
            }
            $cursor->subDay();
        }

        $count = 0;
        foreach ($studentsByLink as $linkId => $userIds) {
            foreach ($days as $day) {
                foreach (['forenoon', 'afternoon'] as $session) {
                    foreach ($userIds as $userId) {
                        $absent = (crc32($userId . '|' . $day . '|' . $session) % 100) < 4;
                        Attendance::create([
                            'school_id' => $this->school->id,
                            'academic_year_id' => $this->year->id,
                            'standardLink_id' => $linkId,
                            'user_id' => $userId,
                            'date' => $day,
                            'session' => $session,
                            'status' => ! $absent,
                            'reason_id' => null,
                            'remarks' => '',
                            'recorded_by' => $classTeachers[$linkId] ?? $this->staff['teacher1']->id,
                        ]);
                        $count++;
                    }
                }
            }
        }

        $this->command?->info('Attendance seeded: ' . $count . ' rows over ' . count($days) . ' school days.');
    }

    // ────────────────────────────────────────────────────────── exams & marks

    private function seedExamsAndMarks(): void
    {
        $eot = ExamType::firstOrCreate(['code' => 'EOT'], [
            'name' => 'End of Term Examination',
            'contributes_to_report_total' => true,
        ]);
        $bot = ExamType::firstOrCreate(['code' => 'BOT'], [
            'name' => 'Beginning of Term Examination',
            'contributes_to_report_total' => false,
        ]);

        $termTwo = AcademicTerm::where('school_id', $this->school->id)->where('name', 'Term II')->first();

        // Grading scale for the primary and o-level standards (same shape
        // AcademicSetupService writes).
        foreach (['primary', 'o-level'] as $standardName) {
            $standard = Standard::where('school_id', $this->school->id)->where('name', $standardName)->first();
            if (! $standard) {
                continue;
            }
            foreach ($this->primaryGrades as $grade) {
                SchoolGradingSystem::updateOrCreate(
                    ['school_id' => $this->school->id, 'standard_id' => $standard->id, 'grade' => $grade['grade']],
                    [
                        'points' => $grade['points'],
                        'min_score' => $grade['min_score'],
                        'max_score' => $grade['max_score'],
                        'remark' => $grade['remark'],
                    ]
                );
            }
        }

        if (Exam::where('school_id', $this->school->id)->exists()) {
            $this->command?->info('Exams already seeded for Demo Academy — skipping.');

            return;
        }

        $subjects = Subject::where('school_id', $this->school->id)->get();
        $teacherBySubject = Teacherlink::where('school_id', $this->school->id)
            ->pluck('teacher_id', 'subject_id');

        $marksCount = 0;

        $sectionNameById = [];
        foreach ($this->sections as $name => $section) {
            $sectionNameById[$section->id] = $name;
        }

        foreach ($subjects as $subject) {
            $sectionName = $sectionNameById[$subject->section_id] ?? null;

            // Only primary classes carry marks (nursery reporting is narrative).
            if (! $sectionName || str_contains($sectionName, 'Class')) {
                continue;
            }

            $exam = Exam::create([
                'standard_id' => $subject->standard_id,
                'school_id' => $this->school->id,
                'section_id' => $subject->section_id,
                'academic_year_id' => $this->year->id,
                'academic_term_id' => $termTwo?->id ?? AcademicTerm::where('school_id', $this->school->id)->value('id'),
                'subject_id' => $subject->id,
                'teacher_id' => $teacherBySubject[$subject->id] ?? $this->staff['teacher1']->id,
                'exam_type_id' => $eot->id,
                'status' => 'done',
                'scheduled_at' => '2026-08-17 09:00:00',
                'default_deadline' => '2026-08-24 17:00:00',
            ]);

            $linkIds = [];
            foreach ($this->links as $link) {
                if ($link->section_id === $subject->section_id) {
                    $linkIds[] = $link->id;
                }
            }

            $studentIds = StudentAcademic::where('school_id', $this->school->id)
                ->whereIn('standardLink_id', $linkIds)
                ->pluck('user_id');

            foreach ($studentIds as $studentId) {
                $score = 45 + (crc32($studentId . '|' . $subject->id . '|demo') % 54); // 45..98
                $grade = $this->gradeFor((int) $score);

                \DB::table('marks')->insert([
                    'student_id' => $studentId,
                    'teacher_id' => $exam->teacher_id,
                    'school_id' => $this->school->id,
                    'subject_id' => $subject->id,
                    'exam_id' => $exam->id,
                    'section_id' => $subject->section_id,
                    'remark_id' => null,
                    'marks' => $score,
                    'grade' => $grade,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $marksCount++;
            }
        }

        $this->command?->info('Exams + marks seeded: ' . $marksCount . ' mark rows across classes.');
    }

    private function gradeFor(int $score): string
    {
        foreach ($this->primaryGrades as $grade) {
            if ($score >= $grade['min_score'] && $score <= $grade['max_score']) {
                return $grade['grade'];
            }
        }

        return 'F9';
    }

    // ───────────────────────────────────────────────────────────────── users

    private function user(string $localPart, string $name, int $usergroupId): User
    {
        $email = $localPart . '@' . self::DOMAIN;

        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user = new User;
        }

        $user->forceFill([
            'email' => $email,
            'school_id' => $this->school->id,
            'usergroup_id' => $usergroupId,
            'name' => $name,
            'password' => Hash::make($this->password),
            'status' => 'active',
            'email_verified' => 1,
        ])->save();

        $profile = \App\Models\Userprofile::firstOrNew(['user_id' => $user->id]);
        $parts = explode(' ', $name, 2);
        $profile->forceFill([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'usergroup_id' => $usergroupId,
            'firstname' => $parts[0],
            'lastname' => $parts[1] ?? '',
            'status' => 'active',
        ])->save();

        return $user;
    }
}
