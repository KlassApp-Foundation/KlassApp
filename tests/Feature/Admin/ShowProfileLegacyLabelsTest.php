<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Follow-up to #465: show/profile display views must not render legacy
 * demographics labels (even as empty / "--").
 */
class ShowProfileLegacyLabelsTest extends TestCase
{
    #[Test]
    #[DataProvider('showSurfacesWithoutLegacyLabels')]
    public function show_surface_does_not_expose_legacy_labels(string $relativePath, array $forbidden): void
    {
        $path = resource_path($relativePath);
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $source,
                "{$relativePath} must not contain legacy label '{$needle}'"
            );
        }
    }

    public static function showSurfacesWithoutLegacyLabels(): array
    {
        $teacherStaff = [
            'Blood Group',
            'Aadhaar Number',
            'Aadhar Number',
            'Marital Status',
        ];

        $student = [
            'Blood Group',
            'Aadhaar Number',
            'Aadhar Number',
            'Birth Place',
            'Native Place',
            'Mother Tongue',
            'Caste :',
            'sub_caste',
            'Sub Caste',
        ];

        return [
            'admin teacher show' => ['views/admin/teacher/show.blade.php', $teacherStaff],
            'admin staff show' => ['views/admin/staff/show.blade.php', $teacherStaff],
            'teacher profile myprofile' => ['assets/js/components/teacher/profile/myprofile.vue', $teacherStaff],
            'admin student (member) show' => ['views/admin/member/show.blade.php', $student],
            'teacher student show' => ['views/teacher/student/show.blade.php', $student],
        ];
    }
}
