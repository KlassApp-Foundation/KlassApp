<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Traits\RegisterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegisterUserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StandardLink $standardLink;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Register User Prov School',
            'email' => 'reg-prov@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        $this->standardLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
    }

    public function test_create_teacher_uses_random_password_and_is_reset(): void
    {
        $harness = new class
        {
            use RegisterUser;
        };

        $data = $this->teacherPayload('teacher-prov@test.sch.ug');

        $user = $harness->CreateTeacher($data, $this->school->id, $this->year, '', 5);

        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertSame(1, (int) $user->is_reset);
    }

    public function test_create_user_student_uses_random_password_and_is_reset(): void
    {
        $harness = new class
        {
            use RegisterUser;
        };

        $data = $this->studentPayload('student-prov@test.sch.ug');

        $user = $harness->CreateUser($data, $this->school->id, $this->year->id, '', 6);

        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertSame(1, (int) $user->is_reset);
    }

    private function teacherPayload(string $email): object
    {
        return (object) [
            'name' => 'Prov Teacher',
            'email' => $email,
            'mobile_no' => '+256700111222',
            'firstname' => 'Prov',
            'lastname' => 'Teacher',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'blood_group' => 'o+',
            'address' => 'Kampala',
            'city_id' => null,
            'country_id' => null,
            'pincode' => null,
            'aadhar_number' => null,
            'joining_date' => '2024-01-01',
            'notes' => null,
            'marital_status' => null,
            'qualification_id' => null,
            'ug_degree' => null,
            'pg_degree' => null,
            'specialization' => null,
            'designation' => 'teacher',
            'sub_designation' => null,
            'employee_id' => null,
            'job_type' => null,
            'interested_in' => null,
            'reporting_to' => null,
        ];
    }

    private function studentPayload(string $email): object
    {
        return (object) [
            'name' => 'Prov Student',
            'email' => $email,
            'mobile_no' => '+256700333444',
            'registration_number' => 'KLS0010427',
            'firstname' => 'Prov',
            'lastname' => 'Student',
            'gender' => 'male',
            'date_of_birth' => '2015-06-01',
            'blood_group' => 'o+',
            'address' => 'Kampala',
            'city_id' => null,
            'country_id' => null,
            'pincode' => null,
            'birth_place' => null,
            'native_place' => null,
            'caste' => null,
            'sub_caste' => null,
            'aadhar_number' => null,
            'joining_date' => '2024-01-01',
            'notes' => null,
            'standard' => $this->standardLink->id,
            'std_school_pay_number' => null,
            'lin' => null,
            'school_student_id' => null,
            'board_registration_number' => null,
            'mode_of_transport' => 'walking',
            'siblings' => 'no',
            'siblings_count' => 0,
        ];
    }
}
