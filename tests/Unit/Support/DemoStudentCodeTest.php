<?php

namespace Tests\Unit\Support;

use App\Support\DemoStudentCode;
use Tests\TestCase;

class DemoStudentCodeTest extends TestCase
{
    public function test_legacy_seven_digit_kls_keeps_its_base36_code_and_hyphens_are_not_ids(): void
    {
        $this->assertSame('9035', DemoStudentCode::fromKls('KLS0420017'));
        $this->assertSame('KLS0420017', DemoStudentCode::toKls('9035'));

        $code = DemoStudentCode::fromKls('KLS0010005');
        $this->assertNotNull($code);
        $this->assertSame('KLS0010005', DemoStudentCode::toKls($code));
        $this->assertNull(DemoStudentCode::fromKls('KLS-100-10001'));
        $this->assertNull(DemoStudentCode::toKls('100-10001'));
    }
}
