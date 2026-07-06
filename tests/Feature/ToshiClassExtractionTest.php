<?php

namespace Tests\Feature;

use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiClassExtractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('schools')->insert(['id' => 1, 'name' => 'Test School', 'slug' => 'test-school', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('academic_years')->insert(['id' => 1, 'school_id' => 1, 'name' => '2026', 'description' => 'Current Academic Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'created_at' => now(), 'updated_at' => now()]);

        $nursery = Standard::create(['school_id' => 1, 'name' => 'nursery', 'order' => 1]);
        $primary = Standard::create(['school_id' => 1, 'name' => 'primary', 'order' => 2]);

        $baby = Section::create(['school_id' => 1, 'name' => 'Baby Class']);
        $primaryOne = Section::create(['school_id' => 1, 'name' => 'Primary One']);

        StandardLink::create(['school_id' => 1, 'standard_id' => $nursery->id, 'section_id' => $baby->id, 'academic_year_id' => 1]);
        StandardLink::create(['school_id' => 1, 'standard_id' => $primary->id, 'section_id' => $primaryOne->id, 'academic_year_id' => 1]);
    }

    public function test_csv_class_column_is_extracted(): void
    {
        $csv = "firstname,class\nAlice,Baby Class\nBob,Primary One\nCarol,P1\n";
        $path = tempnam(sys_get_temp_dir(), 'toshi_test') . '.csv';
        file_put_contents($path, $csv);

        $ref = new \ReflectionMethod(\App\Livewire\AgentToshi::class, 'extractNamesFromFile');
        $ref->setAccessible(true);

        $component = $this->getMockBuilder(\App\Livewire\AgentToshi::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $ref->invoke($component, $path, 'csv');
        unlink($path);

        $this->assertCount(3, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('Baby Class', $result[0]['class']);
        $this->assertSame('Bob', $result[1]['name']);
        $this->assertSame('Primary One', $result[1]['class']);
        $this->assertSame('Carol', $result[2]['name']);
        $this->assertSame('P1', $result[2]['class']);
    }

    public function test_csv_without_class_column_returns_empty_class(): void
    {
        $csv = "firstname\nAlice\nBob\n";
        $path = tempnam(sys_get_temp_dir(), 'toshi_test') . '.csv';
        file_put_contents($path, $csv);

        $ref = new \ReflectionMethod(\App\Livewire\AgentToshi::class, 'extractNamesFromFile');
        $ref->setAccessible(true);

        $component = $this->getMockBuilder(\App\Livewire\AgentToshi::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $ref->invoke($component, $path, 'csv');
        unlink($path);

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('', $result[0]['class']);
        $this->assertSame('Bob', $result[1]['name']);
        $this->assertSame('', $result[1]['class']);
    }

    public function test_class_fuzzy_match_resolves_standard_link(): void
    {
        $nurseryStd = Standard::where(['school_id' => 1, 'name' => 'nursery'])->first();
        $primaryStd = Standard::where(['school_id' => 1, 'name' => 'primary'])->first();

        // Fuzzy match "Primary One" → primary standard
        $sl = StandardLink::where('school_id', 1)
            ->whereHas('section', fn($q) => $q->where('name', 'LIKE', '%Primary%'))
            ->whereHas('standard', fn($q) => $q->where('name', 'primary'))
            ->first();
        $this->assertNotNull($sl);
        $this->assertEquals($primaryStd->id, $sl->standard_id);

        // Exact match "Baby Class" → nursery standard
        $sl2 = StandardLink::where('school_id', 1)
            ->whereHas('section', fn($q) => $q->where('name', 'Baby Class'))
            ->whereHas('standard', fn($q) => $q->where('name', 'nursery'))
            ->first();
        $this->assertNotNull($sl2);
        $this->assertEquals($nurseryStd->id, $sl2->standard_id);
    }
}
