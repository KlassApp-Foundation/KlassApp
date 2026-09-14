<?php

namespace Tests\Feature\Admission;

use Tests\TestCase;

class UgandaAdmissionCopyLabelsTest extends TestCase
{
    public function test_admission_academic_detail_vue_uses_uganda_facing_labels(): void
    {
        $path = resource_path('assets/js/components/admission/AcademicDetail.vue');
        $source = file_get_contents($path);

        $this->assertStringContainsString('Previous School Marks', $source);
        $this->assertStringContainsString('Local Language', $source);
        $this->assertStringContainsString('Examination Board', $source);
        $this->assertStringContainsString('UNEB candidate classes', $source);

        $this->assertStringNotContainsString('Half Yearly Mark Details', $source);
        $this->assertStringNotContainsString('>Tamil</label', $source);
        $this->assertStringNotContainsString('Board of Study', $source);
        $this->assertStringNotContainsString('Class X , XI , XII', $source);
    }

    public function test_student_edit_vue_drops_indian_class_hint(): void
    {
        $path = resource_path('assets/js/components/student/Edit.vue');
        $source = file_get_contents($path);

        $this->assertStringContainsString('UNEB candidate classes', $source);
        $this->assertStringNotContainsString('Class X , XI , XII', $source);
    }
}
