<?php

namespace Tests\Unit\Services;

use App\Services\OnboardingNameListExtractor;
use Tests\TestCase;

class OnboardingNameListExtractorTest extends TestCase
{
    private OnboardingNameListExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new OnboardingNameListExtractor;
    }

    public function test_csv_class_column_is_extracted(): void
    {
        $csv = "firstname,class\nAlice,Baby Class\nBob,Primary One\nCarol,P1\n";
        $path = tempnam(sys_get_temp_dir(), 'bulk_test').'.csv';
        file_put_contents($path, $csv);

        $result = $this->extractor->extractNamesFromFile($path, 'csv');
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
        $path = tempnam(sys_get_temp_dir(), 'bulk_test').'.csv';
        file_put_contents($path, $csv);

        $result = $this->extractor->extractNamesFromFile($path, 'csv');
        unlink($path);

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('', $result[0]['class']);
    }

    public function test_student_template_headers_extract_parent_fields(): void
    {
        $csv = "Name,Class,Stream,Parent Name,Parent Phone\nAmina Nakato,Baby Class,A,Sarah Nakato,+256771234567\n";
        $path = tempnam(sys_get_temp_dir(), 'bulk_test').'.csv';
        file_put_contents($path, $csv);

        $result = $this->extractor->extractNamesFromFile($path, 'csv');
        unlink($path);

        $this->assertCount(1, $result);
        $this->assertSame('Amina Nakato', $result[0]['name']);
        $this->assertSame('Baby Class', $result[0]['class']);
        $this->assertSame('A', $result[0]['stream']);
        $this->assertSame('Sarah Nakato', $result[0]['parent']);
        $this->assertSame('+256771234567', $result[0]['parent_phone']);
    }

    public function test_teacher_template_headers_extract_email_phone(): void
    {
        $csv = "Name,Email,Subjects,Classes,Phone\nJohn Ssali,jssali@school.ug,Mathematics,Primary Five,+256701234567\n";
        $path = tempnam(sys_get_temp_dir(), 'bulk_test').'.csv';
        file_put_contents($path, $csv);

        $result = $this->extractor->extractNamesFromFile($path, 'csv');
        unlink($path);

        $this->assertCount(1, $result);
        $this->assertSame('John Ssali', $result[0]['name']);
        $this->assertSame('jssali@school.ug', $result[0]['email']);
        $this->assertSame('+256701234567', $result[0]['phone']);
        $this->assertSame('Mathematics', $result[0]['subjects']);
        $this->assertSame('Primary Five', $result[0]['classes']);
    }

    public function test_parse_name_list_dedupes_and_caps(): void
    {
        $names = $this->extractor->parseNameList("Alice Wonder\nBob Builder\nAlice Wonder\n12\nx");
        $this->assertSame(['Alice Wonder', 'Bob Builder'], $names);
    }

    public function test_txt_mime_parses_like_csv(): void
    {
        $csv = "name\nGrace\nHelen\n";
        $path = tempnam(sys_get_temp_dir(), 'bulk_test').'.txt';
        file_put_contents($path, $csv);

        $result = $this->extractor->extractNamesFromFile($path, 'txt');
        unlink($path);

        $this->assertCount(2, $result);
        $this->assertSame('Grace', $result[0]['name']);
    }
}
