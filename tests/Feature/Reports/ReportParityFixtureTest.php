<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * T-157: the pack's invented class of 76 (4 subjects, 2 exam sets, a missing mark, ABS marks and a tie)
 * is built in the database and run through the real report code. Every learner's grades, points, total,
 * aggregate, division and position must equal the values the independent reference calculated
 * (reference/calc_reference.py, stored in the fixture), for both sets, in the card's view data and in
 * the text of the real PDF.
 *
 * Rules under test: missing and ABS count as 0 (F9, 9 points); aggregate ranks lowest first; equal
 * values share a place and the next place is skipped.
 */
class ReportParityFixtureTest extends TestCase
{
    use RefreshDatabase;

    private SyntheticClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        // One set must count toward the term total or the card refuses ("No EOT exam found"): known gap.
        $this->class = SyntheticClass::build(['counting_sets' => [1]]);
    }

    public function test_the_class_is_built_as_the_fixture_describes_it(): void
    {
        $this->assertCount(76, $this->class->learners);
        $this->assertSame(['ENGLISH', 'MATHEMATICS', 'SCIENCE', 'SOCIAL STUDIES'], array_map(fn ($s) => strtoupper((string) $s->name), $this->class->subjects));
        // Pupil 076 has two missing marks in the first set, two learners have ABS, 041 and 042 tie.
        $this->assertSame([null, null], [$this->class->fixture['learners'][75]['marks'][0][1], $this->class->fixture['learners'][75]['marks'][0][2]]);
        $this->assertSame('ABS', $this->class->fixture['learners'][9]['marks'][1][2]);
        $this->assertSame('ABS', $this->class->fixture['learners'][32]['marks'][0][0]);
    }

    /**
     * One pass over all 76 learners (each card builds the class sheet, so this is the slow test): grades,
     * points, total, aggregate, division and position for both sets, then the tie and the division counts
     * from the same data.
     */
    public function test_every_learner_in_both_sets_matches_the_reference_in_the_card_view_data(): void
    {
        $subjectIds = array_map(fn ($s) => (int) $s->id, $this->class->subjects);
        $cards = [];

        foreach (array_keys($this->class->learners) as $kls) {
            $want = $this->class->expected($kls);
            $data = $this->class->cardData($kls);
            $cards[$kls] = $data['setColumns'];

            $this->assertCount(2, $data['setColumns'], "{$kls}: one column group per exam set");
            foreach ($want['sets'] as $setIndex => $expected) {
                $column = $data['setColumns'][$setIndex];
                $grades = $points = [];
                foreach ($subjectIds as $subjectId) {
                    $grades[] = $column['cells'][$subjectId]->grade;
                    $points[] = $column['cells'][$subjectId]->points;
                }
                $where = "{$kls} set ".($setIndex + 1);
                $this->assertSame($expected['grades'], $grades, "{$where} grades");
                $this->assertSame($expected['points'], $points, "{$where} points");
                $this->assertEqualsWithDelta($expected['total'], (float) $column['total'], 0.001, "{$where} total");
                $this->assertSame($expected['aggregate'], $column['aggregate'], "{$where} aggregate");
                $this->assertSame($expected['division'], (string) $column['division'], "{$where} division");
                $this->assertSame($expected['position'], $column['pos'], "{$where} position");
                $this->assertSame(76, $column['of']);
            }
        }

        // The tie: 041 and 042 have the same marks in the second set, so they share a place, and the
        // places that tied learners would have taken are skipped.
        $this->assertSame($cards['KLS9000041'][1]['pos'], $cards['KLS9000042'][1]['pos']);
        foreach ([0, 1] as $setIndex) {
            $positions = array_map(fn ($kls) => $cards[$kls][$setIndex]['pos'], array_keys($cards));
            foreach (array_count_values($positions) as $place => $sharing) {
                for ($skipped = 1; $skipped < $sharing; $skipped++) {
                    $this->assertNotContains($place + $skipped, $positions, "set ".($setIndex + 1).": place {$place} shared by {$sharing}, so ".($place + $skipped).' is skipped');
                }
            }
        }

        // Division counts per set.
        foreach ($this->class->fixture['sets'] as $setIndex => $setName) {
            $got = array_fill_keys(['1', '2', '3', '4', 'U'], 0);
            foreach ($cards as $columns) {
                $got[(string) $columns[$setIndex]['division']]++;
            }
            $this->assertSame(array_map('intval', array_values($this->class->fixture['division_counts'][$setName])), array_values($got), $setName.' division counts');
        }
    }

    public function test_the_card_marks_a_missing_mark_missing_and_an_absence_absent_and_counts_both_as_zero(): void
    {
        $subjectIds = array_map(fn ($s) => (int) $s->id, $this->class->subjects);

        $missing = $this->class->cardData('KLS9000076')['setColumns'][0]['cells'];
        $this->assertSame('missing', $missing[$subjectIds[1]]->status);
        $this->assertSame('missing', $missing[$subjectIds[2]]->status);
        $this->assertNull($missing[$subjectIds[1]]->mark);
        $this->assertSame(['F9', 9], [$missing[$subjectIds[1]]->grade, $missing[$subjectIds[1]]->points]);

        $absent = $this->class->cardData('KLS9000010')['setColumns'][1]['cells'];
        $this->assertSame('absent', $absent[$subjectIds[2]]->status);
        $this->assertSame(['F9', 9], [$absent[$subjectIds[2]]->grade, $absent[$subjectIds[2]]->points]);

        $absentFirst = $this->class->cardData('KLS9000033')['setColumns'][0]['cells'];
        $this->assertSame('absent', $absentFirst[$subjectIds[0]]->status);
    }

    public function test_the_pdf_text_matches_the_reference_for_the_learners_that_test_the_rules(): void
    {
        // 076 missing, 010 and 033 absent, 041 and 042 tie, 001 first, 060 an ordinary middle learner.
        foreach (['KLS9000076', 'KLS9000010', 'KLS9000033', 'KLS9000041', 'KLS9000042', 'KLS9000001', 'KLS9000060'] as $kls) {
            $card = $this->parsePdf($this->class->pdfText($kls));
            $want = $this->class->expected($kls);
            $row = $this->class->fixture['learners'][(int) substr($kls, -3) - 1]['marks'];

            $this->assertSame($kls, $card['kls']);
            $this->assertSame('76', $card['pupils']);
            foreach ($want['sets'] as $setIndex => $expected) {
                $where = "{$kls} set ".($setIndex + 1);
                foreach ($this->class->fixture['subjects'] as $subjectIndex => $subjectName) {
                    $raw = $row[$setIndex][$subjectIndex];
                    $shown = $raw === null ? 'Missing' : (string) $raw;
                    $this->assertSame(
                        [$shown, $expected['grades'][$subjectIndex]],
                        $card['subjects'][strtoupper($subjectName)][$setIndex],
                        "{$where} {$subjectName}"
                    );
                }
                $this->assertSame((string) $expected['total'], $card['total'][$setIndex], "{$where} total");
                $this->assertSame((string) $expected['aggregate'], $card['aggregate'][$setIndex], "{$where} aggregate");
                $this->assertSame($expected['division'], $card['division'][$setIndex], "{$where} division");
                $this->assertSame($this->ordinal($expected['position']).' of 76', $card['position'][$setIndex], "{$where} position");
            }
        }
    }

    public function test_the_footnote_about_missing_marks_prints_only_when_a_card_needs_it(): void
    {
        $needs = $this->class->pdfText('KLS9000076');
        $clean = $this->class->pdfText('KLS9000001');

        $this->assertStringContainsString('Missing marks and ABS count as 0', preg_replace('/\s+/', ' ', $needs));
        $this->assertStringNotContainsString('Missing marks and ABS count as 0', preg_replace('/\s+/', ' ', $clean));
        $this->assertStringNotContainsString('Missing', $clean);
        $this->assertStringNotContainsString('ABS', $clean);
    }

    public function test_the_card_never_depends_on_the_names_of_the_sets(): void
    {
        $text = $this->class->pdfText('KLS9000001');

        foreach ($this->class->fixture['sets'] as $name) {
            $this->assertStringContainsString($name, $text, 'the school\'s own set name is the column heading');
        }
        $this->assertStringNotContainsString('END OF TERM', strtoupper(preg_replace('/\s+/', ' ', $text)).'', 'no fixed end-of-term wording');
    }

    /**
     * KNOWN GAP (handoff finding 1): when no exam set counts toward the term total, the card refuses
     * ("No EOT exam found for this class") instead of printing the sets that were taken. The report
     * builder is the marks agent's work; remove the incomplete marker when a card prints with no
     * counting set.
     */
    public function test_a_card_prints_when_no_set_counts_toward_the_term_total(): void
    {
        $none = SyntheticClass::build(['learners' => 3, 'counting_sets' => [], 'suffix' => 'none']);

        $data = $none->cardData('KLS9000001');

        $this->assertCount(2, $data['setColumns']);
        $this->assertTrue($data['noCombinedMark']);
        $this->assertSame([], $data['termSets']);
    }

    private function ordinal(int $n): string
    {
        $suffix = ($n % 100 >= 11 && $n % 100 <= 13) ? 'th' : [1 => 'st', 2 => 'nd', 3 => 'rd'][$n % 10] ?? 'th';

        return $n.$suffix;
    }

    /**
     * Reads the lines of a Progressive card's PDF text.
     *
     * @return array{kls: string, pupils: string, subjects: array<string, list<array{0: string, 1: string}>>, total: list<string>, aggregate: list<string>, division: list<string>, position: list<string>}
     */
    private function parsePdf(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), fn ($l) => $l !== ''));
        $card = ['kls' => '', 'pupils' => '', 'subjects' => [], 'total' => [], 'aggregate' => [], 'division' => [], 'position' => []];

        foreach ($lines as $i => $line) {
            if ($line === 'KLS NUMBER') {
                $card['kls'] = $lines[$i + 1];
            }
            if ($line === 'PUPILS IN CLASS') {
                $card['pupils'] = $lines[$i + 1];
            }
            $tokens = preg_split('/\s+/', $line);
            foreach ($this->class->fixture['subjects'] as $subjectName) {
                $upper = strtoupper($subjectName);
                if (str_starts_with($line, $upper."\t") || str_starts_with($line, $upper.' ')) {
                    $rest = preg_split('/\s+/', trim(substr($line, strlen($upper))));
                    // marks, grade, marks, grade
                    $card['subjects'][$upper] = [[$rest[0], $rest[1]], [$rest[2], $rest[3]]];
                }
            }
            foreach (['TOTAL MARKS' => 'total', 'AGGREGATE' => 'aggregate', 'DIVISION' => 'division'] as $label => $key) {
                if (str_starts_with($line, $label."\t") || str_starts_with($line, $label.' ')) {
                    $card[$key] = array_slice(preg_split('/\s+/', trim(substr($line, strlen($label)))), 0, 2);
                }
            }
            if (str_starts_with($line, 'POSITION')) {
                preg_match_all('/(\d+(?:st|nd|rd|th) of \d+)/', $line, $m);
                $card['position'] = $m[1];
            }
        }

        return $card;
    }
}
