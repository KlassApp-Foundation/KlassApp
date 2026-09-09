<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the medical-history height/weight UI strip (UI-only; DB columns retained).
 */
class MedicalHistoryHeightWeightStripTest extends TestCase
{
    #[Test]
    #[DataProvider('surfacesWithoutHeightWeight')]
    public function medical_history_surface_does_not_expose_height_or_weight(string $relativePath, array $forbidden): void
    {
        $path = base_path($relativePath);
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $source,
                "{$relativePath} must not contain '{$needle}'"
            );
        }
    }

    public static function surfacesWithoutHeightWeight(): array
    {
        $vueForbidden = [
            'v-model="height"',
            'v-model="weight"',
            'name="height"',
            'name="weight"',
            'Height ( in cm',
            'Weight ( in kg',
            'Height :',
            'Weight :',
            'medical.height',
            'medical.weight',
            "formData.append('height'",
            "formData.append('weight'",
            'this.height',
            'this.weight',
            'this.user.height',
            'this.user.weight',
        ];

        $requestForbidden = [
            "'height'",
            "'weight'",
            'Height is required',
            'Weight is required',
        ];

        $controllerForbidden = [
            "medicals['height']",
            "medicals['weight']",
            "number_format(\$request->height",
            "number_format(\$request->weight",
            'studentAcademicLatest->height',
            'studentAcademicLatest->weight',
            '\$studentacademic->height',
            '\$studentacademic->weight',
        ];

        return [
            'CreateMedicalHistory.vue' => ['resources/assets/js/components/student/CreateMedicalHistory.vue', $vueForbidden],
            'medicalHistory.vue' => ['resources/assets/js/components/student/profile/medicalHistory.vue', [
                'Height :',
                'Weight :',
                'medical.height',
                'medical.weight',
                'v-model="height"',
                'v-model="weight"',
            ]],
            'MedicalHistoryRequest' => ['app/Http/Requests/MedicalHistoryRequest.php', $requestForbidden],
            'Admin StudentDetailsController medical history' => [
                'app/Http/Controllers/Admin/StudentDetailsController.php',
                $controllerForbidden,
            ],
            'Teacher StudentDetailsController medical history' => [
                'app/Http/Controllers/Teacher/StudentDetailsController.php',
                $controllerForbidden,
            ],
        ];
    }

    #[Test]
    public function medical_history_vue_uses_content_gate_not_height_weight(): void
    {
        $path = resource_path('assets/js/components/student/profile/medicalHistory.vue');
        $source = file_get_contents($path);

        $this->assertStringContainsString('hasMedicalContent', $source);
        $this->assertStringNotContainsString(
            'medical.height != null && medical.weight != null',
            $source
        );
    }
}
