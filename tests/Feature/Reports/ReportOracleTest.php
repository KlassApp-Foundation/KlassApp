<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * T-157: reference/calc_reference.py is the oracle. It is run here, on the fixture's marks, and its
 * answers are compared with the fixture's stored expectations and with the card, including a class
 * that ranks by total instead of aggregate. Skipped when python3 is not installed.
 */
class ReportOracleTest extends TestCase
{
    use RefreshDatabase;

    private const REFERENCE = __DIR__.'/../../fixtures/reports/reference';

    /**
     * @return list<list<array<string, mixed>>> per exam set, one row per learner, in fixture order
     */
    private function oracle(string $rankBy): array
    {
        if (trim((string) shell_exec('command -v python3')) === '') {
            $this->markTestSkipped('python3 is not installed here.');
        }

        $code = 'import sys, json; sys.path.insert(0, '.var_export(realpath(self::REFERENCE), true).'); import calc_reference as r; '
            .'d = json.load(sys.stdin); '
            .'sets = len(d["sets"]); '
            .'print(json.dumps([r.compute_set([l["marks"][i] for l in d["learners"]], '.var_export($rankBy, true).') for i in range(sets)]))';
        $process = proc_open(['python3', '-I', '-c', $code], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['PATH' => (string) getenv('PATH'), 'PYTHONDONTWRITEBYTECODE' => '1']);
        fwrite($pipes[0], (string) file_get_contents(__DIR__.'/../../fixtures/reports/p5-synthetic-class.json'));
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        proc_close($process);
        $decoded = json_decode((string) $out, true);
        $this->assertIsArray($decoded, 'the oracle ran: '.$err);

        return $decoded;
    }

    public function test_the_oracle_reproduces_the_fixtures_stored_expectations(): void
    {
        $fixture = SyntheticClass::fixture();
        $result = $this->oracle('aggregate');

        foreach ($fixture['expected'] as $index => $want) {
            foreach ($want['sets'] as $setIndex => $expected) {
                $got = $result[$setIndex][$index];
                foreach (['grades', 'points', 'total', 'aggregate', 'division', 'position'] as $key) {
                    $this->assertSame($expected[$key], $got[$key], "{$want['kls']} set ".($setIndex + 1)." {$key}");
                }
            }
        }
    }

    public function test_a_class_ranked_by_total_matches_the_oracle_ranked_by_total(): void
    {
        $oracle = $this->oracle('total');
        $class = SyntheticClass::build(['counting_sets' => [1], 'ranking_basis' => 'total']);
        $fixture = $class->fixture;

        // Learners that include the missing, the ABS and the tied ones, and an even spread.
        $pick = array_unique(array_merge([75, 9, 32, 40, 41, 0], range(2, 70, 8)));
        foreach ($pick as $index) {
            $kls = $fixture['learners'][$index]['kls'];
            $columns = $class->cardData($kls)['setColumns'];
            foreach ($columns as $setIndex => $column) {
                $this->assertSame($oracle[$setIndex][$index]['position'], $column['pos'], "{$kls} set ".($setIndex + 1).' position by total');
                $this->assertEqualsWithDelta($oracle[$setIndex][$index]['total'], (float) $column['total'], 0.001);
            }
        }
    }
}
