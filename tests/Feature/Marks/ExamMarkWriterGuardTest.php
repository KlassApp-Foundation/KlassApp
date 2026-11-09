<?php

namespace Tests\Feature\Marks;

use Tests\TestCase;

/**
 * Product entry code writes a numeric mark only inside ExamMarkWriter.
 * A new Marks::create, updateOrCreate, or marks-table insert/update fails this test.
 */
class ExamMarkWriterGuardTest extends TestCase
{
    private const WRITER = 'app/Services/Marks/ExamMarkWriter.php';

    /**
     * These files still touch the marks table and are not a person or an import entering a mark.
     * Adding a path here is a decision, not a way to silence the test.
     *
     * @var array<string, string>
     */
    private const NOT_AN_ENTRY_PATH = [
        'app/Console/Commands/BackfillMarksGrades.php' => 'rewrites the stored letter on rows that already exist',
        'app/Console/Commands/GenerateReportTemplateThumbnails.php' => 'thumbnail fixture, not a person entering marks',
        'app/Console/Commands/ImportKabaleMarks.php' => 'one-off historical load that stores many subjects on one exam row',
        'app/Console/Commands/ImportKabelMarks.php' => 'one-off historical load that deletes a class and bulk-inserts',
        'app/Console/Commands/SetupUiReviewDemoSchool.php' => 'demo fixture on exams already marked submitted',
        'app/Console/Commands/SetupUiReviewSecondaryDemoSchool.php' => 'demo fixture on exams already marked submitted',
        'app/Services/Toshi/LiveAdversarialRunner.php' => 'isolation fixture aimed at exam id 1, which is not an exam row',
    ];

    public function test_product_code_does_not_write_marks_outside_exam_mark_writer(): void
    {
        $found = $this->filesThatWriteMarks();
        $allowed = array_merge([self::WRITER], array_keys(self::NOT_AN_ENTRY_PATH));

        $unexpected = array_values(array_diff($found, $allowed));
        $this->assertSame(
            [],
            $unexpected,
            'These files write exam marks outside ExamMarkWriter: '.implode(', ', $unexpected)
        );

        $stale = array_values(array_diff(array_keys(self::NOT_AN_ENTRY_PATH), $found));
        $this->assertSame(
            [],
            $stale,
            'These exclusions no longer write marks and should leave the list: '.implode(', ', $stale)
        );

        $this->assertContains(self::WRITER, $found);
        $this->assertNotContains('app/Services/MarksImport/MarksImportService.php', $found);
        $this->assertNotContains('app/Http/Controllers/Teacher/MarksController.php', $found);
    }

    /**
     * @return list<string>
     */
    private function filesThatWriteMarks(): array
    {
        $hits = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('app'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (! is_string($source) || ! $this->sourceWritesMarks($source)) {
                continue;
            }

            $relative = ltrim(str_replace(base_path(), '', $file->getPathname()), '/');
            $hits[] = $relative;
        }

        sort($hits);

        return $hits;
    }

    private function sourceWritesMarks(string $source): bool
    {
        if (preg_match('/Marks::\s*(create|insert|updateOrCreate|upsert)\s*\(/', $source) === 1) {
            return true;
        }

        if (preg_match('/new\s+Marks\s*\(/', $source) === 1) {
            return true;
        }

        if (preg_match(
            '/DB::table\(\s*[\'"]marks[\'"]\s*\)(?:[^;]{0,1500})->\s*(insert|update|upsert|updateOrInsert)\s*\(/s',
            $source
        ) === 1) {
            return true;
        }

        return preg_match('/->update\(\s*\[\s*[\'"]marks\.grade[\'"]/', $source) === 1;
    }
}
