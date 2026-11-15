<?php

namespace App\Services\Results;

use App\Contracts\Results\GradingScale;

/**
 * Grade bands copied onto a release. A later edit to the school's bands
 * does not change a reprint that uses this copy.
 *
 * @phpstan-type Band array{grade: string, points: ?int, min_score: ?float, max_score: ?float, remark: ?string}
 */
final class StoredGradingScale implements GradingScale
{
    /**
     * @param  list<Band>  $bands
     */
    public function __construct(private readonly array $bands) {}

    public function pointsFor(int $schoolId, int $standardId, float $mark): ?int
    {
        $band = $this->bandFor($mark);
        if ($band === null || $band['points'] === null) {
            return null;
        }

        return (int) $band['points'];
    }

    public function labelFor(int $schoolId, int $standardId, float $mark): ?string
    {
        $band = $this->bandFor($mark);
        if ($band === null) {
            return null;
        }

        $grade = trim((string) $band['grade']);

        return $grade === '' ? null : $grade;
    }

    /**
     * @return Band|null
     */
    private function bandFor(float $mark): ?array
    {
        $exact = null;
        foreach ($this->bands as $band) {
            if ($band['min_score'] === null || $band['max_score'] === null) {
                continue;
            }
            if ((float) $band['min_score'] > $mark || (float) $band['max_score'] < $mark) {
                continue;
            }
            if ($exact === null || (float) $band['min_score'] > (float) $exact['min_score']) {
                $exact = $band;
            }
        }

        return $exact;
    }
}
