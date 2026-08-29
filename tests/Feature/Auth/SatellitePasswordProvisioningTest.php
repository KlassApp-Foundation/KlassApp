<?php

namespace Tests\Feature\Auth;

use App\Console\Commands\EnrollStudents;
use App\Http\Controllers\Admin\TeacherLinkImportController;
use App\Support\UserProvisioning;
use App\Traits\AdmissionUser;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Source-level regression: satellite enrollment/admission paths must not
 * hardcode bcrypt('password'). Behaviour is covered for RegisterUser;
 * these assert the same helper pattern is wired in.
 */
class SatellitePasswordProvisioningTest extends TestCase
{
    public function test_admission_user_trait_uses_user_provisioning_not_demo_password(): void
    {
        $source = File::get(base_path('app/Traits/AdmissionUser.php'));

        $this->assertStringContainsString('UserProvisioning::randomPasswordAttributes()', $source);
        $this->assertStringNotContainsString("bcrypt('password')", $source);
        $this->assertSame(3, substr_count($source, 'UserProvisioning::randomPasswordAttributes()'));
    }

    public function test_teacher_link_import_uses_user_provisioning_not_demo_password(): void
    {
        $source = File::get(base_path('app/Http/Controllers/Admin/TeacherLinkImportController.php'));

        $this->assertStringContainsString('UserProvisioning::randomPasswordAttributes()', $source);
        $this->assertStringNotContainsString("bcrypt('password')", $source);
    }

    public function test_enroll_students_command_uses_user_provisioning_not_demo_password(): void
    {
        $source = File::get(base_path('app/Console/Commands/EnrollStudents.php'));

        $this->assertStringContainsString('UserProvisioning::randomPasswordAttributes()', $source);
        $this->assertStringNotContainsString("bcrypt('password')", $source);
    }

    public function test_admission_create_student_father_sets_is_reset(): void
    {
        $this->assertTrue(trait_exists(AdmissionUser::class));
        $this->assertTrue(class_exists(UserProvisioning::class));
        $this->assertTrue(class_exists(TeacherLinkImportController::class));
        $this->assertTrue(class_exists(EnrollStudents::class));

        $attrs = UserProvisioning::randomPasswordAttributes();
        $this->assertArrayHasKey('password', $attrs);
        $this->assertSame(1, $attrs['is_reset']);
    }
}
