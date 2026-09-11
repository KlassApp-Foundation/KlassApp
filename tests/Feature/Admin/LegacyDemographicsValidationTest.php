<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the admin-dashboard legacy-demographics strip (Track A + marital_status)
 * and DOB-optional validation (Track B).
 */
class LegacyDemographicsValidationTest extends TestCase
{
    #[Test]
    #[DataProvider('vueSurfacesWithoutLegacyFields')]
    public function vue_surface_does_not_expose_legacy_fields(string $relativePath, array $forbidden): void
    {
        $path = resource_path($relativePath);
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $source,
                "{$relativePath} must not contain legacy field '{$needle}'"
            );
        }
    }

    public static function vueSurfacesWithoutLegacyFields(): array
    {
        $trackA = [
            'blood_group',
            'aadhar_number',
            'adhaar',
            'caste',
            'sub_caste',
            'mother_tongue',
            'birth_place',
            'native_place',
        ];

        $teacherStaff = array_merge($trackA, ['marital_status']);

        $admission = [
            'blood_group',
            'aadhar_number',
            'mother_tongue',
            'birth_place',
            'community',
            'v-model="community"',
            'name="community"',
            'v-model="religion"',
            'v-model="nationality"',
            'v-model="height"',
            'v-model="weight"',
            'name="religion"',
            'name="nationality"',
            'name="height"',
            'name="weight"',
        ];

        return [
            'student Edit' => ['assets/js/components/student/Edit.vue', $trackA],
            'student Create' => ['assets/js/components/student/Create.vue', $trackA],
            'student Filter' => ['assets/js/components/student/Filter.vue', ['blood_group', 'caste']],
            'teacher Create' => ['assets/js/components/teacher/Create.vue', $teacherStaff],
            'teacher Edit' => ['assets/js/components/teacher/Edit.vue', $teacherStaff],
            'teacher Filter' => ['assets/js/components/teacher/Filter.vue', ['blood_group', 'marital_status']],
            'staff Filter' => ['assets/js/components/staff/Filter.vue', ['blood_group', 'marital_status']],
            'export Student' => ['assets/js/components/export/Student.vue', ['blood_group', 'caste', 'adhaar']],
            'export Teacher' => ['assets/js/components/export/Teacher.vue', ['blood_group', 'adhaar']],
            'export Staff' => ['assets/js/components/export/Staff.vue', ['blood_group', 'adhaar']],
            'admission StudentDetail' => ['assets/js/components/admission/StudentDetail.vue', $admission],
            'admission ParentDetail' => ['assets/js/components/admission/ParentDetail.vue', ['aadhar_number', 'father_aadhar', 'mother_aadhar']],
        ];
    }

    #[Test]
    #[DataProvider('dobOptionalRequestFiles')]
    public function date_of_birth_is_nullable_not_required(string $relativePath): void
    {
        $path = app_path($relativePath);
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        $this->assertMatchesRegularExpression(
            "/['\"]date_of_birth['\"]\s*=>\s*['\"]nullable\|/",
            $source,
            "{$relativePath} must mark date_of_birth as nullable"
        );

        $this->assertDoesNotMatchRegularExpression(
            "/['\"]date_of_birth['\"]\s*=>\s*['\"]required\|/",
            $source,
            "{$relativePath} must not require date_of_birth"
        );
    }

    public static function dobOptionalRequestFiles(): array
    {
        return [
            'UserProfileAddRequest' => ['Http/Requests/UserProfileAddRequest.php'],
            'UserProfileUpdateRequest' => ['Http/Requests/UserProfileUpdateRequest.php'],
            'TeacherProfileAddRequest' => ['Http/Requests/TeacherProfileAddRequest.php'],
            'TeacherUpdateRequest' => ['Http/Requests/TeacherUpdateRequest.php'],
            'AdmissionStudentRequest' => ['Http/Requests/Admission/AdmissionStudentRequest.php'],
        ];
    }

    #[Test]
    public function teacher_add_request_does_not_require_marital_status_or_legacy_fields(): void
    {
        $path = app_path('Http/Requests/TeacherProfileAddRequest.php');
        $source = file_get_contents($path);

        foreach (['marital_status', 'blood_group', 'aadhar_number'] as $field) {
            $this->assertDoesNotMatchRegularExpression(
                "/['\"]{$field}['\"]\s*=>/",
                $source,
                "TeacherProfileAddRequest must not validate {$field}"
            );
        }
    }

    #[Test]
    public function admission_student_request_does_not_require_community_or_other_track_a_fields(): void
    {
        $path = app_path('Http/Requests/Admission/AdmissionStudentRequest.php');
        $source = file_get_contents($path);

        foreach (['community', 'religion', 'nationality', 'height', 'weight', 'mother_tongue', 'aadhar_number', 'blood_group', 'birth_place'] as $field) {
            $this->assertDoesNotMatchRegularExpression(
                "/['\"]{$field}['\"]\s*=>/",
                $source,
                "AdmissionStudentRequest must not validate {$field}"
            );
        }
    }

    #[Test]
    public function student_create_and_edit_keep_date_of_birth_field(): void
    {
        foreach (['Create.vue', 'Edit.vue'] as $file) {
            $source = file_get_contents(resource_path("assets/js/components/student/{$file}"));
            $this->assertStringContainsString('date_of_birth', $source);
            $this->assertStringNotContainsString(
                'Blood Group',
                $source
            );
        }
    }
}
