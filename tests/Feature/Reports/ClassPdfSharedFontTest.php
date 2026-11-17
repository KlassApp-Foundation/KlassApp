<?php

namespace Tests\Feature\Reports;

use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Smalot\PdfParser\Parser;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * A class PDF is one document. The fonts go in once, and every learner still
 * has a page. Seventy-six pages of any template stay under 10 MB; four pages
 * here prove the font objects are not repeated per learner.
 */
class ClassPdfSharedFontTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_class_pdf_embeds_each_font_once_in_every_template(): void
    {
        foreach (['formal', 'warm', 'progressive'] as $template) {
            $class = SyntheticClass::build([
                'learners' => 4,
                'counting_sets' => [1],
                'template' => $template,
                'suffix' => $template,
            ]);
            $service = app(StudentReportCardService::class);
            $learners = collect(array_values($class->learners));
            $one = $service->pdfForStudent((int) $class->school->id, $class->link, $learners->first());
            $classPdf = $service->mergedPdfForLearners((int) $class->school->id, $class->link, $learners);

            $parser = new Parser;
            $pages = count($parser->parseContent($classPdf)->getPages());
            $oneText = trim($parser->parseContent($one)->getText());
            $classText = $parser->parseContent($classPdf)->getText();
            $oneFonts = $this->embeddedFonts($one);
            $classFonts = $this->embeddedFonts($classPdf);

            $this->assertNotSame('', $oneText, $template);
            $this->assertGreaterThan(strlen($oneText) * 3, strlen($classText), $template.' prints every card');
            $this->assertSame(4, $pages, $template.' has one page per learner');
            $this->assertGreaterThan(0, $oneFonts, $template.' embeds a font on a single card');
            $this->assertSame($oneFonts, $classFonts, $template.' embeds those fonts once for the class');
            $this->assertLessThan(strlen($one) * $learners->count(), strlen($classPdf), $template);
            $this->assertLessThan(10 * 1024 * 1024, strlen($classPdf), $template);
        }
    }

    private function embeddedFonts(string $pdf): int
    {
        return (int) preg_match_all('/\/FontFile[23]\b/', $pdf);
    }
}
