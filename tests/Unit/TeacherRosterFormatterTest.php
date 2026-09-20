<?php

namespace Tests\Unit;

use App\Support\TeacherRosterFormatter;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TeacherRosterFormatterTest extends TestCase
{
    public function test_it_formats_multiple_streams_as_a_readable_list(): void
    {
        $streams = new Collection([
            (object) ['stream' => 'A'],
            (object) ['stream' => 'B'],
            (object) ['stream' => 'A'],
            (object) ['stream' => null],
        ]);

        $this->assertSame('A, B', TeacherRosterFormatter::formatStreams($streams));
    }
}
