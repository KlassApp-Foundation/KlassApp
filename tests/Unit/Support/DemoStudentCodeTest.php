<?php

namespace Tests\Unit\Support;

use App\Support\DemoStudentCode;
use Tests\TestCase;

class DemoStudentCodeTest extends TestCase
{
    public function test_legacy_kls_keeps_its_base36_code_and_new_ids_round_trip(): void
    {
        $this->assertSame('9035', DemoStudentCode::fromKls('KLS0420017'));
        $this->assertSame('KLS0420017', DemoStudentCode::toKls('9035'));

        $wide = DemoStudentCode::fromKls('KLS-100-10001');
        $other = DemoStudentCode::fromKls('KLS-1001-1');

        $this->assertSame('100-10001', $wide);
        $this->assertSame('1001-1', $other);
        $this->assertNotSame($wide, $other);
        $this->assertNotSame('9035', $wide);
        $this->assertSame('KLS-100-10001', DemoStudentCode::toKls($wide));
        $this->assertSame('KLS-1001-1', DemoStudentCode::toKls($other));
        $this->assertNull(DemoStudentCode::toKls('01-1'));
    }
}
