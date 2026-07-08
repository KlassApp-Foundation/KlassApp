<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestSchoolOneDataSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1;
        $academicYearId = 1;
        $now = Carbon::now();

        // ── Academic Terms (Term 1, 2, 3) ──
        $terms = [
            ['school_id' => $schoolId, 'academic_year_id' => $academicYearId, 'name' => 'Term 1', 'starts_on' => '2026-02-01', 'ends_on' => '2026-04-30', 'status' => 'current'],
            ['school_id' => $schoolId, 'academic_year_id' => $academicYearId, 'name' => 'Term 2', 'starts_on' => '2026-05-15', 'ends_on' => '2026-08-15', 'status' => 'next'],
            ['school_id' => $schoolId, 'academic_year_id' => $academicYearId, 'name' => 'Term 3', 'starts_on' => '2026-09-15', 'ends_on' => '2026-12-10', 'status' => 'last'],
        ];

        // Only insert if not exist
        $existingTerm = DB::table('academic_terms')->where('school_id', $schoolId)->first();
        if (!$existingTerm) {
            foreach ($terms as $t) {
                DB::table('academic_terms')->insert($t + ['created_at' => $now, 'updated_at' => $now]);
            }
            echo "✓ Academic terms created\n";
        } else {
            echo "→ Academic terms already exist\n";
        }

        $currentTermId = DB::table('academic_terms')->where('school_id', $schoolId)->where('status', 'current')->value('id');

        // ── Subjects ──
        $subjects = [
            ['name' => 'Literacy', 'standard_id' => 1],   // nursery
            ['name' => 'Numeracy', 'standard_id' => 1],
            ['name' => 'Motor Skills', 'standard_id' => 1],
            ['name' => 'Social/Emotional', 'standard_id' => 1],
            ['name' => 'English', 'standard_id' => 2],     // primary
            ['name' => 'Mathematics', 'standard_id' => 2],
            ['name' => 'Science', 'standard_id' => 2],
            ['name' => 'Social Studies', 'standard_id' => 2],
            ['name' => 'English', 'standard_id' => 3],     // o-level
            ['name' => 'Mathematics', 'standard_id' => 3],
            ['name' => 'Biology', 'standard_id' => 3],
            ['name' => 'Chemistry', 'standard_id' => 3],
            ['name' => 'Physics', 'standard_id' => 3],
            ['name' => 'Geography', 'standard_id' => 3],
            ['name' => 'History', 'standard_id' => 3],
            ['name' => 'General Paper', 'standard_id' => 4],  // a-level
            ['name' => 'Subsidiary Math', 'standard_id' => 4],
            ['name' => 'Physics', 'standard_id' => 4],
            ['name' => 'Economics', 'standard_id' => 4],
        ];

        $existingSubjectCount = DB::table('subjects')->where('school_id', $schoolId)->where('academic_year_id', $academicYearId)->count();
        if ($existingSubjectCount === 0) {
            // Get first section per standard to use as default
            $defaultSectionIds = [];
            foreach ([1, 2, 3, 4] as $stdId) {
                $defaultSectionIds[$stdId] = DB::table('standards_link')
                    ->where('school_id', $schoolId)
                    ->where('standard_id', $stdId)
                    ->value('section_id') ?? 1;
            }

            foreach ($subjects as $s) {
                DB::table('subjects')->insert([
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'standard_id' => $s['standard_id'],
                    'section_id' => $defaultSectionIds[$s['standard_id']] ?? 1,
                    'name' => $s['name'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            echo "✓ Subjects created\n";
        } else {
            echo "→ Subjects already exist\n";
        }

        // ── Exam Types for this school ──
        $existingET = DB::table('exam_types')->where('school_id', $schoolId)->count();
        if ($existingET === 0) {
            $globalTypes = DB::table('exam_types')->whereNull('school_id')->orWhere('school_id', 0)->get();
            if ($globalTypes->count() > 0) {
                foreach ($globalTypes as $gt) {
                    DB::table('exam_types')->insert([
                        'school_id' => $schoolId,
                        'name' => $gt->name,
                        'code' => $gt->code,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                echo "✓ Exam types copied for school\n";
            }
        } else {
            echo "→ Exam types already exist\n";
        }

        // ── Students ──
        $students = [
            // Nursery
            ['firstname' => 'Amina', 'lastname' => 'Nakato', 'gender' => 'female', 'standard_link_id' => 11, 'section_id' => 1, 'standard_id' => 1, 'dob' => '2020-03-15'],
            ['firstname' => 'Brian', 'lastname' => 'Okello', 'gender' => 'male', 'standard_link_id' => 12, 'section_id' => 2, 'standard_id' => 1, 'dob' => '2019-07-22'],
            // Primary
            ['firstname' => 'Grace', 'lastname' => 'Mbabazi', 'gender' => 'female', 'standard_link_id' => 37, 'section_id' => 4, 'standard_id' => 2, 'dob' => '2015-11-08'],
            ['firstname' => 'Daniel', 'lastname' => 'Kato', 'gender' => 'male', 'standard_link_id' => 41, 'section_id' => 8, 'standard_id' => 2, 'dob' => '2013-05-30'],
            // O-Level
            ['firstname' => 'Esther', 'lastname' => 'Nampiima', 'gender' => 'female', 'standard_link_id' => 23, 'section_id' => 8, 'standard_id' => 3, 'dob' => '2010-09-14'],
            // A-Level
            ['firstname' => 'Samuel', 'lastname' => 'Wasswa', 'gender' => 'male', 'standard_link_id' => 1, 'section_id' => 1, 'standard_id' => 4, 'dob' => '2007-01-20'],
        ];

        $existingStudents = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 6)->count();
        if ($existingStudents === 0) {
            $teacherId = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 5)->value('id') ?? 30;
            $cityId = DB::table('cities')->where('country_id', 1)->value('id') ?? 1;
            $countryId = 1;

            foreach ($students as $i => $s) {
                $email = 'student' . ($i + 1) . '@testschoolone.sch.ug';
                $userId = DB::table('users')->insertGetId([
                    'school_id' => $schoolId,
                    'usergroup_id' => 6,
                    'name' => $s['firstname'] . ' ' . $s['lastname'],
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'mobile_no' => '+2567' . rand(55, 78) . rand(100000, 999999),
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Profile
                DB::table('userprofiles')->insert([
                    'school_id' => $schoolId,
                    'user_id' => $userId,
                    'usergroup_id' => 6,
                    'firstname' => $s['firstname'],
                    'lastname' => $s['lastname'],
                    'gender' => $s['gender'],
                    'date_of_birth' => $s['dob'],
                    'address' => 'Kampala, Uganda',
                    'city_id' => $cityId,
                    'country_id' => $countryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Student academic
                $klassappId = 'KLS001' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT);
                DB::table('student_academics')->insert([
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'user_id' => $userId,
                    'standardLink_id' => $s['standard_link_id'],
                    'klassapp_student_id' => $klassappId,
                    'std_school_pay_number' => 'SPN00' . ($i + 1) . '0' . $schoolId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            echo "✓ " . count($students) . " students created\n";
        } else {
            echo "→ Students already exist ($existingStudents)\n";
        }

        // ── Parents ──
        $existingParents = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 7)->count();
        if ($existingParents === 0) {
            $parents = [
                ['firstname' => 'Sarah', 'lastname' => 'Nakato', 'email' => 'parent1@testschoolone.sch.ug', 'mobile' => '+256771234567'],
                ['firstname' => 'Peter', 'lastname' => 'Okello', 'email' => 'parent2@testschoolone.sch.ug', 'mobile' => '+256772345678'],
            ];

            $cityId = DB::table('cities')->where('country_id', 1)->value('id') ?? 1;
            $studentIds = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 6)->pluck('id')->toArray();

            foreach ($parents as $i => $p) {
                $parentUserId = DB::table('users')->insertGetId([
                    'school_id' => $schoolId,
                    'usergroup_id' => 7,
                    'name' => $p['firstname'] . ' ' . $p['lastname'],
                    'email' => $p['email'],
                    'password' => Hash::make('password123'),
                    'mobile_no' => $p['mobile'],
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('userprofiles')->insert([
                    'school_id' => $schoolId,
                    'user_id' => $parentUserId,
                    'usergroup_id' => 7,
                    'firstname' => $p['firstname'],
                    'lastname' => $p['lastname'],
                    'gender' => $i === 0 ? 'female' : 'male',
                    'date_of_birth' => $i === 0 ? '1985-06-12' : '1983-09-25',
                    'address' => 'Kampala, Uganda',
                    'city_id' => $cityId,
                    'country_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Link parent to first student
                if (isset($studentIds[$i])) {
                    DB::table('student_parent_links')->insert([
                        'school_id' => $schoolId,
                        'student_id' => $studentIds[$i],
                        'parent_id' => $parentUserId,
                        'relationship' => $i === 0 ? 'mother' : 'father',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
            echo "✓ Parents created and linked\n";
        } else {
            echo "→ Parents already exist\n";
        }

        // ── Fees Categories ──
        $existingFees = DB::table('fees_categories')->where('school_id', $schoolId)->count();
        if ($existingFees === 0) {
            $fees = [
                ['name' => 'Tuition (Nursery)', 'amount' => 350000, 'standard_id' => 1],
                ['name' => 'Tuition (Primary)', 'amount' => 450000, 'standard_id' => 2],
                ['name' => 'Tuition (O-Level)', 'amount' => 550000, 'standard_id' => 3],
                ['name' => 'Tuition (A-Level)', 'amount' => 650000, 'standard_id' => 4],
                ['name' => 'Lunch Fee', 'amount' => 120000, 'standard_id' => null],
                ['name' => 'Transport', 'amount' => 80000, 'standard_id' => null],
            ];
            foreach ($fees as $f) {
                DB::table('fees_categories')->insert([
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'name' => $f['name'],
                    'amount' => $f['amount'],
                    'standard_id' => $f['standard_id'],
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            echo "✓ Fee categories created\n";
        } else {
            echo "→ Fee categories already exist\n";
        }

        // ── Nursery Assessments ──
        $existingAssessments = DB::table('nursery_assessments')->count();
        if ($existingAssessments === 0) {
            $nurseryStudents = DB::table('users')
                ->where('school_id', $schoolId)
                ->where('usergroup_id', 6)
                ->whereIn('id', function ($q) use ($schoolId) {
                    $q->select('user_id')->from('student_academics')
                        ->where('school_id', $schoolId)
                        ->whereIn('standardLink_id', function ($qq) {
                            $qq->select('id')->from('standards_link')->where('standard_id', 1);
                        });
                })->get();

            $ratings = ['Excellent', 'Good', 'Satisfactory', 'Needs Improvement'];
            $domains = ['Literacy', 'Numeracy', 'Motor Skills', 'Social/Emotional'];

            foreach ($nurseryStudents as $ns) {
                foreach ($domains as $d) {
                    DB::table('nursery_assessments')->insert([
                        'student_id' => $ns->id,
                        'academic_term_id' => $currentTermId,
                        'domain' => $d,
                        'rating' => $ratings[array_rand($ratings)],
                        'remarks' => 'Good progress shown.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
            echo "✓ Nursery assessments created\n";
        } else {
            echo "→ Nursery assessments already exist\n";
        }

        // ── Exams ──
        $existingExams = DB::table('exams')->where('school_id', $schoolId)->count();
        if ($existingExams === 0) {
            $examTypeId = DB::table('exam_types')->where('school_id', $schoolId)->where('code', 'EOT')->value('id')
                ?? DB::table('exam_types')->whereNull('school_id')->where('code', 'EOT')->value('id')
                ?? 6;

            // Get subject IDs for each standard
            $stdSubjects = [];
            foreach ([1, 2, 3, 4] as $stdId) {
                $stdSubjects[$stdId] = DB::table('subjects')
                    ->where('school_id', $schoolId)
                    ->where('standard_id', $stdId)
                    ->pluck('id')
                    ->toArray();
            }

            $examId = null;
            foreach ($stdSubjects as $stdId => $subjectIds) {
                foreach ($subjectIds as $subjId) {
                    // Find a standards_link for this standard
                    $slId = DB::table('standards_link')
                        ->where('school_id', $schoolId)
                        ->where('standard_id', $stdId)
                        ->value('id');

                    if (!$slId) continue;

                    $sectionId = DB::table('standards_link')
                        ->where('id', $slId)
                        ->value('section_id');

                    $examId = DB::table('exams')->insertGetId([
                        'school_id' => $schoolId,
                        'standard_id' => $stdId,
                        'academic_year_id' => $academicYearId,
                        'subject_id' => $subjId,
                        'section_id' => $sectionId,
                        'exam_type_id' => $examTypeId,
                        'academic_term_id' => $currentTermId,
                        'scheduled_at' => $now->copy()->subDays(rand(1, 30)),
                        'status' => 'done',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // ── Marks ──
            if ($examId) {
                $markExams = DB::table('exams')->where('school_id', $schoolId)->get();
                $allStudents = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 6)->pluck('id')->toArray();
                $teacherId = DB::table('users')->where('school_id', $schoolId)->where('usergroup_id', 5)->value('id') ?? 30;

                foreach ($markExams as $me) {
                    foreach ($allStudents as $sid) {
                        DB::table('marks')->insert([
                            'student_id' => $sid,
                            'teacher_id' => $teacherId,
                            'school_id' => $schoolId,
                            'subject_id' => $me->subject_id,
                            'exam_id' => $me->id,
                            'section_id' => $me->section_id,
                            'marks' => rand(40, 100),
                            'grade' => rand(70, 100) > 80 ? 'A' : (rand(50, 70) > 50 ? 'B' : 'C'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
                echo "✓ Exams and marks created\n";
            }
        } else {
            echo "→ Exams already exist ($existingExams)\n";
        }

        echo "\n✅ Test School One seeding complete!\n";
        echo "   Login: admin@testschoolone.sch.ug / password123\n";
        echo "   Dashboard: http://localhost:8000/admin/dashboard\n";
    }
}
