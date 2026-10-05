<?php

namespace App\Services\MarksImport;

/** What commit() actually did. Every input row is accounted for in $rows. */
final class MarksImportResult
{
    /**
     * @param  list<array<string,mixed>>  $rows  Final outcome per input row: saved|updated|unchanged|skipped.
     */
    public function __construct(
        public readonly int $examId,
        public readonly int $saved,
        public readonly int $updated,
        public readonly int $unchanged,
        public readonly int $skipped,
        public readonly array $rows,
    ) {
    }

    /** @return array<string,int> reason code => rows */
    public function skippedByReason(): array
    {
        $out = [];
        foreach ($this->rows as $row) {
            if ($row['outcome'] === 'skipped') {
                $out[$row['reason']] = ($out[$row['reason']] ?? 0) + 1;
            }
        }
        ksort($out);

        return $out;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'exam_id' => $this->examId, 'saved' => $this->saved, 'updated' => $this->updated,
            'unchanged' => $this->unchanged, 'skipped' => $this->skipped, 'rows' => $this->rows,
        ];
    }

    /** @param  array<string,mixed>  $d */
    public static function fromArray(array $d): self
    {
        return new self((int) $d['exam_id'], (int) $d['saved'], (int) $d['updated'], (int) $d['unchanged'], (int) $d['skipped'], $d['rows']);
    }
}
