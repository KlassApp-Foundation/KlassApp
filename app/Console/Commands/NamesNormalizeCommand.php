<?php

namespace App\Console\Commands;

use App\Services\People\PersonNameNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * T-105. Fixes imported staff and parent names where the whole name sits in First Name with Last
 * Name empty, or a name is all capitals: "AGNES KALIISA" becomes Agnes / Kaliisa.
 *
 * Dry run is the default. Anything ambiguous (one word, three or more words, no last name) is
 * reported and left alone. Students are not touched. Only userprofiles.firstname and lastname are
 * written; users.name (the login handle) is never changed.
 */
class NamesNormalizeCommand extends Command
{
    protected $signature = 'names:normalize
        {--apply : Write the corrected names (default is a dry run that writes nothing)}
        {--dry-run : Report only. This is the default; it cannot be combined with --apply}
        {--school= : Only this school id}
        {--chunk=500 : Rows per write batch with --apply}';

    protected $description = 'Split and proper-case imported staff and parent names (dry run by default)';

    /** Staff and parents. Students (6), alumni and the platform admin are left alone. */
    private const USERGROUPS = [3, 4, 5, 7, 8, 10, 11, 12];

    private const SAMPLE_LIMIT = 20;

    /** @var list<array{id: int, before: string, after: string}> */
    private array $samples = [];

    /** @var list<array{id: int, reason: string}> */
    private array $skipped = [];

    public function handle(PersonNameNormalizer $normalizer): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply && $this->option('dry-run')) {
            $this->error('Use either --apply or --dry-run, not both. Dry run is the default.');

            return self::INVALID;
        }

        $schoolId = $this->option('school') !== null && $this->option('school') !== '' ? (int) $this->option('school') : null;
        $chunk = max(1, (int) $this->option('chunk'));

        $this->line($apply ? 'APPLY: writing corrected names.' : 'DRY RUN: nothing is written. Use --apply to write.');

        $counts = ['scanned' => 0, 'ok' => 0, 'split' => 0, 'cased' => 0, 'ambiguous' => 0];
        $query = DB::table('userprofiles')
            ->select('id', 'school_id', 'firstname', 'lastname')
            ->whereIn('usergroup_id', self::USERGROUPS);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        $query->orderBy('id')->chunkById($chunk, function ($batch) use ($normalizer, $apply, &$counts): void {
            $updates = [];
            foreach ($batch as $row) {
                $counts['scanned']++;
                $result = $normalizer->normalize($row->firstname, $row->lastname);
                $counts[$result['status']]++;

                if ($result['status'] === PersonNameNormalizer::AMBIGUOUS) {
                    $this->skipped[] = ['id' => (int) $row->id, 'reason' => (string) $result['reason']];
                    $this->sample((int) $row->id, trim($row->firstname.' | '.$row->lastname), '(not changed: '.$result['reason'].')', true);
                    if ($apply) {
                        Log::warning('names:normalize skipped an ambiguous name', ['id' => (int) $row->id, 'school_id' => (int) $row->school_id, 'reason' => $result['reason']]);
                    }
                } elseif (in_array($result['status'], [PersonNameNormalizer::SPLIT, PersonNameNormalizer::CASED], true)) {
                    $updates[(int) $row->id] = ['firstname' => $result['first'], 'lastname' => $result['last']];
                    $this->sample((int) $row->id, trim($row->firstname.' | '.$row->lastname), $result['first'].' | '.$result['last']);
                }
            }

            if ($apply && $updates !== []) {
                DB::transaction(function () use ($updates): void {
                    foreach ($updates as $id => $values) {
                        DB::table('userprofiles')->where('id', $id)->update($values);
                    }
                });
            }
        });

        $this->table(['Staff and parent profiles scanned', 'Already fine', $apply ? 'Split' : 'Would split', $apply ? 'Proper-cased' : 'Would proper-case', 'Ambiguous (left as they are)'], [[
            $counts['scanned'], $counts['ok'], $counts['split'], $counts['cased'], $counts['ambiguous'],
        ]]);

        if ($this->samples !== []) {
            $this->newLine();
            $this->line('Samples (first | last, before => after), up to '.self::SAMPLE_LIMIT.':');
            foreach ($this->samples as $sample) {
                $this->line(sprintf('  profile #%d: %s => %s', $sample['id'], $sample['before'], $sample['after']));
            }
        }

        if ($this->skipped !== []) {
            $this->warn(count($this->skipped).' name(s) were ambiguous and not changed. Fix them by hand'.($apply ? ' (listed in the log by profile id).' : '.'));
        }

        return self::SUCCESS;
    }

    private function sample(int $id, string $before, string $after, bool $skipped = false): void
    {
        $limit = self::SAMPLE_LIMIT;
        if (count($this->samples) >= $limit || ($skipped && count($this->samples) >= $limit - 5)) {
            return;
        }
        $this->samples[] = ['id' => $id, 'before' => $before, 'after' => $after];
    }
}
