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
use Database\Seeders\Concerns\SeedsRichDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo Senior School — O + A level soft-launch demo.
 *
 * Run on purpose only:
 *     php artisan db:seed --class=DemoSeniorSchoolSeeder
 *
 * - This is a SEEDER, not a migration, and it is referenced by no other
 *   seeder: it never runs automatically on any environment, and it must
 *   never be wired into deploy commands.
 * - The school is marked is_demo, so DemoSchoolCommsGuard blocks every
 *   outbound WhatsApp, SMS, email and push for it and for its users.
 * - Screenshot capture scripts target this school (slug: demo-senior-school).
 *
 * Data: S.1–S.6 with streams on lower O-level, staff, fees, attendance, and
 * end-of-term exams/marks. A-level subject combinations are a small follow-up.
 */
class DemoSeniorSchoolSeeder extends Seeder
{
    use SeedsRichDemoData;

    public const DOMAIN = 'senior.demo.klassapp.test';

    /** A1: ages by class for seeded dates of birth. */
    public const AGE_BY_CLASS = [
        'S.1' => 13, 'S.2' => 14, 'S.3' => 15, 'S.4' => 16, 'S.5' => 17, 'S.6' => 18,
    ];

    private School $school;

    private AcademicYear $year;

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

    /**
     * Passwords are per-account: a NEW account gets its own unique random
     * password (never echoed, never read from the environment); an EXISTING
     * account keeps its password (reruns must never overwrite it).
     */
    private function freshPassword(): string
    {
        return Str::random(32);
    }

    public function run(): void
    {
        $this->command?->info('Demo Senior School — manual seed (never automatic; is_demo comms guard applies).');

        $this->seedSchool();
        $this->seedStaff();
        $this->seedClassesAndSubjects();
        $this->seedStudents();
        $this->seedFees();
        $this->seedAttendance();
        $this->seedExamsAndMarks();
        $this->seedRichDemoData();

        $this->command?->info('Demo Senior School seeded.');
        $this->command?->line('School: ' . $this->school->name . ' (slug ' . $this->school->slug . ', is_demo=1)');
        $this->command?->line('Admin: admin@' . self::DOMAIN . ' | Head teacher: headteacher@' . self::DOMAIN);
        $this->command?->line('Teacher: teacher1@' . self::DOMAIN . ' | Bursar: bursar@' . self::DOMAIN);
        $this->command?->line('Passwords: unique random per account (set at account creation only, never echoed).');
    }

    // ──────────────────────────────────────────────────────────────── school

    private function seedSchool(): void
    {
        // Key on email: SchoolObserver::created() overwrites slug with Str::slug(name).
        $school = School::firstOrCreate(
            ['email' => 'demo-senior@klassapp.xyz'],
            [
                'name' => 'Demo Senior School',
                'slug' => 'demo-senior-school',
                'phone' => '077' . random_int(1000000, 9999999),
                'registration_country' => 'Uganda',
                'curriculum' => 'UNEB',
                'status' => 1,
            ]
        );

        $school->forceFill([
            'name' => 'Demo Senior School',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'school_category' => 'o_a_level',
            'status' => 1,
            'is_demo' => 1,
            'is_test' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => \App\Enums\ToshiMode::configuredDefault(),
        ])->save();

        $this->school = $school->refresh();

        $this->year = AcademicYear::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => '2026'],
            [
                'description' => 'Demo Senior School academic year',
                'start_date' => '2026-02-02',
                'end_date' => '2026-12-04',
                'status' => 1,
            ]
        );

        foreach ([
            ['name' => 'Term I', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30', 'status' => 'last'],
            ['name' => 'Term II', 'starts_on' => '2026-05-18', 'ends_on' => '2026-08-21', 'status' => 'last'],
            // Same window as the junior demo: late August to late November,
            // so October can show a 60–75% collection on the fee tile.
            ['name' => 'Term III', 'starts_on' => '2026-08-24', 'ends_on' => '2026-11-28', 'status' => 'current'],
        ] as $term) {
            AcademicTerm::updateOrCreate(
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
            $teacher = $this->user('teacher' . ($i + 1), $name, 5);
            $teacher->forceFill([
                'mobile_no' => $this->richFictionalPhone('teacher' . ($i + 1) . '@' . self::DOMAIN),
            ])->save();
            $this->staff['teacher' . ($i + 1)] = $teacher;
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
            \Log::info('demo-senior seed: principal profile skipped: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────── classes and subjects

    private function seedClassesAndSubjects(): void
    {
        $secondary = Standard::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'o-level'],
            ['order' => 1, 'status' => 1]
        );
        $aLevel = Standard::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'a-level'],
            ['order' => 2, 'status' => 1]
        );

        $teacherIds = [];
        foreach (array_keys($this->staff) as $key) {
            if (str_starts_with($key, 'teacher')) {
                $teacherIds[] = $this->staff[$key]->id;
            }
        }

        $classes = [
            ['name' => 'S.1', 'level' => 'secondary', 'streams' => ['East', 'West']],
            ['name' => 'S.2', 'level' => 'secondary', 'streams' => ['East', 'West']],
            ['name' => 'S.3', 'level' => 'secondary', 'streams' => [null]],
            ['name' => 'S.4', 'level' => 'secondary', 'streams' => [null]],
            // A-level forms — subject combinations added tomorrow; core subjects seeded now.
            ['name' => 'S.5', 'level' => 'a-level', 'streams' => [null]],
            ['name' => 'S.6', 'level' => 'a-level', 'streams' => [null]],
        ];

        $teacherIndex = 0;

        foreach ($classes as $class) {
            $standard = match ($class['level']) {
                'a-level' => $aLevel,
                default => $secondary,
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
                'a-level' => [
                    // Combinations deferred — principal/subsidiary packs land tomorrow.
                    'Mathematics' => 'MTC', 'English' => 'ENG', 'Physics' => 'PHY',
                    'Chemistry' => 'CHE', 'Biology' => 'BIO', 'Economics' => 'ECO',
                    'History' => 'HIS', 'Geography' => 'GEO',
                ],
                default => [
                    'Mathematics' => 'MTC', 'English' => 'ENG', 'Physics' => 'PHY',
                    'Chemistry' => 'CHE', 'Biology' => 'BIO', 'Geography' => 'GEO', 'History' => 'HIS',
                ],
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
        $plan = [
            'S.1' => 12, 'S.2' => 12, 'S.3' => 10, 'S.4' => 10,
            'S.5' => 8, 'S.6' => 8,
        ];
        $names = \App\Support\DemoRosterNames::names(array_sum($plan));

        $n = 0;
        $classIndex = 0;
        foreach ($plan as $className => $count) {
            $linkKeys = array_values(array_filter(array_keys($this->links), fn ($k) => $k === $className || str_starts_with($k, $className . ' ')));

            for ($i = 0; $i < $count; $i++) {
                $n++;
                $student = $this->user('student' . $n, $names[$n - 1], 6);

                // A1: every student carries a date of birth and a gender.
                // The mix changes by class; paired fractions keep the school near 50/50.
                $age = self::AGE_BY_CLASS[$className] ?? 15;
                \App\Models\Userprofile::where('user_id', $student->id)->update([
                    'date_of_birth' => \Illuminate\Support\Carbon::create(2026 - $age, (($n * 5) % 12) + 1, (($n * 7) % 27) + 1)->toDateString(),
                    'gender' => $this->demoStudentGender($classIndex, $i, $count),
                ]);

                $klsNumber = \App\Services\StudentIdGeneratorService::ensureForStudent($student);

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
                        'lin' => $n % 3 === 0 ? 'UG-SEN-LIN-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT) : null,
                    ]
                );
                unset($academic);
            }
            $classIndex++;
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

    // ─ fees

    private function seedFees(): void
    {
        $termOne = AcademicTerm::where('school_id', $this->school->id)
            ->where('name', 'Term I')
            ->first();

        $oLevel = Standard::where('school_id', $this->school->id)->where('name', 'o-level')->first();
        $aLevel = Standard::where('school_id', $this->school->id)->where('name', 'a-level')->first();

        $definitions = array_values(array_filter([
            $oLevel ? ['standard' => $oLevel, 'name' => 'Tuition', 'amount' => 780000, 'type' => 'tuition'] : null,
            $oLevel ? ['standard' => $oLevel, 'name' => 'Lunch', 'amount' => 120000, 'type' => 'lunch'] : null,
            $aLevel ? ['standard' => $aLevel, 'name' => 'Tuition', 'amount' => 920000, 'type' => 'tuition'] : null,
            $aLevel ? ['standard' => $aLevel, 'name' => 'Lunch', 'amount' => 120000, 'type' => 'lunch'] : null,
        ]));

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

        FeePayment::where('school_id', $this->school->id)->delete();

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
            $billed = array_values(array_filter(
                $categories,
                fn (FeesCategories $category) => (int) $category->standard_id === (int) $standardId
            ));
            if ($billed === []) {
                continue;
            }

            // 6 of 10 pay every fee in full, 2 of 10 pay half (a balance remains),
            // 2 of 10 have paid nothing. About 70% of the term bill, inside the
            // current term window so the dashboard tile and the fee chart agree.
            $roll = $index % 10;
            $paidOn = $this->paidOnInsideCurrentTerm();

            if ($roll <= 5) {
                foreach ($billed as $offset => $category) {
                    $this->payment($academic->user_id, $category, $category->amount, $paidOn($offset), 'mobile_money');
                }
            } elseif ($roll <= 7) {
                foreach ($billed as $offset => $category) {
                    $half = (int) round(((float) $category->amount) / 2);
                    $this->payment($academic->user_id, $category, $half, $paidOn($offset + 2), 'cash', 'PART-'.$index.'-'.$offset);
                }
            }
        }

        $this->command?->info('Fees seeded: ' . FeePayment::where('school_id', $this->school->id)->count() . ' payments total.');
    }

    /**
     * @return callable(int): string
     */
    private function paidOnInsideCurrentTerm(): callable
    {
        $term = AcademicTerm::where('school_id', $this->school->id)->where('status', 'current')->first();
        $start = $term ? \Carbon\Carbon::parse($term->starts_on)->startOfDay() : now()->subDays(20)->startOfDay();
        $latest = now()->startOfDay();
        if ($term && $term->ends_on) {
            $end = \Carbon\Carbon::parse($term->ends_on)->startOfDay();
            if ($end->lt($latest)) {
                $latest = $end;
            }
        }
        if ($latest->lt($start)) {
            $latest = $start->copy();
        }
        $span = max(1, $start->diffInDays($latest));

        return function (int $slot) use ($start, $span): string {
            $day = (int) round((($slot % 5) / 4) * $span);

            return $start->copy()->addDays(min($day, $span))->toDateString();
        };
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
        $alreadyRecorded = Attendance::where('school_id', $this->school->id)->distinct()->pluck('user_id')->all();

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
        while (count($days) < 40) { // 8 school weeks — the dashboard attendance trend charts the last 8 weeks
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
                        if (in_array($userId, $alreadyRecorded, true)) {
                            continue;
                        }
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

        $this->command?->info('Attendance seeded: ' . $count . ' new rows over ' . count($days) . ' school days.');
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
        foreach (['o-level', 'a-level'] as $standardName) {
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

            if (! $sectionName || ! str_starts_with($sectionName, 'S.')) {
                continue;
            }

            // Per class+subject idempotency: a newly added class seeds its own
            // exams and marks without touching existing ones.
            if (Exam::where('school_id', $this->school->id)
                ->where('section_id', $subject->section_id)
                ->where('subject_id', $subject->id)
                ->exists()) {
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

    /**
     * Rich-data shape for this school (see SeedsRichDemoData::richConfig).
     */
    protected function richConfig(): array
    {
        return [
            'parents' => [
                ['name' => 'Harriet Nakanwagi'],
                ['name' => 'Silver Ojok'],
                ['name' => 'Immaculate Driciru'],
                ['name' => 'Godfrey Bwambale'],
                ['name' => 'Robinah Nakirya'],
                ['name' => 'Moses Odongo'],
                ['name' => 'Stella Ampaire'],
                ['name' => 'Denis Byaruhanga'],
                ['name' => 'Christine Amongin'],
                ['name' => 'Patrick Omara'],
                ['name' => 'Florence Nabwire'],
                ['name' => 'Ambrose Kigongo'],
                ['name' => 'Janet Akumu'],
                ['name' => 'Paul Wafula'],
                ['name' => 'Rebecca Nabbosa'],
                ['name' => 'Stephen Ochieng'],
                ['name' => 'Justine Nabatanzi'],
                ['name' => 'Anthony Ekwaro'],
                ['name' => 'Monica Aciro'],
                ['name' => 'Benard Ssekitoleko'],
                ['name' => 'Carol Namusoke'],
                ['name' => 'Wilberforce Ogwang'],
                ['name' => 'Lydia Nakanwagi'],
                ['name' => 'Geoffrey Tumwine'],
                ['name' => 'Sylvia Ijang'],
                ['name' => 'Alfred Kaggwa'],
                ['name' => 'Harriet Auma'],
                ['name' => 'Boniface Woniala'],
                ['name' => 'Faith Nabirye'],
                ['name' => 'Simon Oyat'],
                ['name' => 'Justine Amono'],
                ['name' => 'Gerald Ssenyonga'],
                ['name' => 'Beatrice Adong'],
                ['name' => 'Milton Obbo'],
                ['name' => 'Sarah Atuhaire'],
                ['name' => 'Edward Kamya'],
                ['name' => 'Peace Adero'],
                ['name' => 'Noah Bisaso'],
                ['name' => 'Diana Aketo'],
                ['name' => 'Ismail Waiswa'],
            ],
            'admissions' => [
                ['standard' => 'o-level', 'section' => 'S.1', 'name' => 'Brenda Akello', 'age' => 13, 'gender' => 'female', 'district' => 'Gulu', 'village' => 'Pece', 'last_school' => 'Pece Hill Primary', 'last_class' => 'P.7', 'father' => 'Okello Akello', 'father_job' => 'Teacher', 'mother' => 'Akello Grace', 'mother_job' => 'Trader'],
                ['standard' => 'a-level', 'section' => 'S.5', 'name' => 'Timothy Wanyama', 'age' => 16, 'gender' => 'male', 'district' => 'Mbale', 'village' => 'Namakwekwe', 'last_school' => 'St. Lawrence SS', 'last_class' => 'S.4', 'father' => 'James Wanyama', 'father_job' => 'Engineer', 'mother' => 'Rose Wanyama', 'mother_job' => 'Nurse'],
            ],
            'exam_section_names' => ['S.1', 'S.2', 'S.3', 'S.4', 'S.5', 'S.6'],
            'skip_second' => ['section' => 'S.3', 'stream' => null],
            'fallback_open' => ['section' => 'S.4', 'subject' => 'Mathematics'],
        ];
    }

    // ───────────────────────────────────────────────────────────────── users

    private function user(string $localPart, string $name, int $usergroupId): User
    {
        $email = $localPart . '@' . self::DOMAIN;

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'email' => $email,
                'school_id' => $this->school->id,
                'usergroup_id' => $usergroupId,
                'name' => $name,
                // Password on CREATE only: a rerun never overwrites an existing
                // account's password (walkthrough logins keep working).
                'password' => Hash::make($this->freshPassword()),
                'status' => 'active',
                'email_verified' => 1,
            ]);
        }

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
