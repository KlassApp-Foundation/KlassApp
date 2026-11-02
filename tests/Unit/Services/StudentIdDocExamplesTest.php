<?php

namespace Tests\Unit\Services;

use App\Services\StudentIdGeneratorService;
use PHPUnit\Framework\TestCase;

class StudentIdDocExamplesTest extends TestCase
{
    public function test_pattern_is_unchanged_and_doc_samples_match_it(): void
    {
        $this->assertSame('/^KLS(?:\d{7}|\d{11})$/i', StudentIdGeneratorService::PATTERN);

        $files = [
            'docs/product/student-import.md',
            'docs/proposals/2026-10-05-marks-import-ui.md',
            'docs-site/help/add-students.md',
            'design/system/readme.md',
            'design/system/klassapp-handoff-2026-10-08-admin-mvp/HANDOFF.md',
            'design/system/guidelines/handoff-2026-10-08-admin-mvp-combined.md',
            'resources/views/livewire/superadmin/academics/userprofile-form.blade.php',
        ];

        $sawSeven = false;
        $sawEleven = false;

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3).'/'.$file;
            $this->assertFileExists($path, $file);
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            $this->assertIsArray($lines);

            foreach ($lines as $number => $line) {
                if (! preg_match_all('/KLS\d+/i', $line, $matches)) {
                    continue;
                }
                foreach ($matches[0] as $token) {
                    if (StudentIdGeneratorService::isValid($token)) {
                        $sawSeven = $sawSeven || (bool) preg_match('/^KLS\d{7}$/i', $token);
                        $sawEleven = $sawEleven || (bool) preg_match('/^KLS\d{11}$/i', $token);
                        continue;
                    }
                    $this->assertMatchesRegularExpression(
                        '/rejected|not an ID/i',
                        $line,
                        $token.' on '.$file.':'.($number + 1).' is not a valid sample'
                    );
                }
            }
        }

        $this->assertTrue($sawSeven, 'docs should include a 7-digit sample such as KLS0010005');
        $this->assertTrue($sawEleven, 'docs should include an 11-digit sample such as KLS00100010001');
    }
}
