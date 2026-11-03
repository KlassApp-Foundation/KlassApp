<?php

namespace App\Console\Commands;

use App\Reference\ReferenceSnapshot;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Check the reference seed: expected counts, Uganda's code,
 * no orphan states or cities, and a checksum that a second
 * seed must leave unchanged.
 */
class ReferenceAuditCommand extends Command
{
    protected $signature = 'db:reference-audit
        {--fail : Exit with status 1 when the reference data does not match}
        {--snapshot= : Write counts and checksum to this JSON file}
        {--expect= : Compare this run with a snapshot written earlier}';

    protected $description = 'Assert reference seed counts, country codes, and a stable checksum';

    public function handle(): int
    {
        $snapshot = ReferenceSnapshot::capture();

        $this->line('Reference audit');
        foreach ($snapshot['counts'] as $table => $count) {
            $this->line("  {$table}: {$count}");
        }
        $this->line('  checksum: '.$snapshot['checksum']);

        $problems = $snapshot['problems'];

        $expect = $this->option('expect');
        if (is_string($expect) && $expect !== '') {
            $previous = json_decode((string) file_get_contents($expect), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($previous)) {
                throw new RuntimeException('The expected snapshot is not a JSON object.');
            }
            if (($previous['checksum'] ?? null) !== $snapshot['checksum']) {
                $problems[] = 'checksum changed after the seed was run again';
            }
            if (($previous['counts'] ?? null) !== $snapshot['counts']) {
                $problems[] = 'row counts changed after the seed was run again';
            }
        }

        $path = $this->option('snapshot');
        if (is_string($path) && $path !== '') {
            file_put_contents($path, json_encode([
                'counts' => $snapshot['counts'],
                'checksum' => $snapshot['checksum'],
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->line('  snapshot: '.$path);
        }

        $this->newLine();
        if ($problems === []) {
            $this->info('Result: PASS');

            return 0;
        }

        foreach ($problems as $problem) {
            $this->line('- '.$problem);
        }
        $this->error('Result: PROBLEMS '.count($problems));

        return $this->option('fail') ? 1 : 0;
    }
}
