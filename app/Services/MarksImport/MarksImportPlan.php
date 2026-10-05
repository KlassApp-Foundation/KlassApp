<?php

namespace App\Services\MarksImport;

/**
 * What an import WOULD do. Built by MarksImportService::preview(); nothing is
 * saved until commit(). Plain data so it can be cached between the preview and
 * the confirm step, or handed to another caller (Toshi, a file feed).
 */
final class MarksImportPlan
{
    /**
     * @param  list<array{row:int,identifier:?string,name:?string,mark:?string}>  $input  Normalised input rows (what the user uploaded).
     * @param  list<array<string,mixed>>  $rows  One outcome per input row.
     * @param  array<string,string>  $blockers  code => message; non-empty means commit() refuses.
     */
    public function __construct(
        public readonly int $examId,
        public readonly int $schoolId,
        public readonly string $fileName,
        public readonly string $fileSha256,
        public readonly array $input,
        public readonly array $rows,
        public readonly array $blockers,
        public readonly bool $requiresReason,
    ) {
    }

    public function isBlocked(): bool
    {
        return $this->blockers !== [];
    }

    /** @return array{new:int,update:int,unchanged:int,skipped:int,total:int} */
    public function counts(): array
    {
        $c = ['new' => 0, 'update' => 0, 'unchanged' => 0, 'skipped' => 0, 'total' => count($this->rows)];
        foreach ($this->rows as $row) {
            $c[$row['outcome']]++;
        }

        return $c;
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

    /** @return list<array<string,mixed>> */
    public function rowsWithOutcome(string $outcome): array
    {
        return array_values(array_filter($this->rows, fn ($r) => $r['outcome'] === $outcome));
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'exam_id' => $this->examId,
            'school_id' => $this->schoolId,
            'file_name' => $this->fileName,
            'file_sha256' => $this->fileSha256,
            'input' => $this->input,
            'rows' => $this->rows,
            'blockers' => $this->blockers,
            'requires_reason' => $this->requiresReason,
        ];
    }

    /** @param  array<string,mixed>  $d */
    public static function fromArray(array $d): self
    {
        return new self(
            (int) $d['exam_id'], (int) $d['school_id'], (string) $d['file_name'], (string) $d['file_sha256'],
            $d['input'], $d['rows'], $d['blockers'], (bool) $d['requires_reason'],
        );
    }
}
