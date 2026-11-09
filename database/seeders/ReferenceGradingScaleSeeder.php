<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use Illuminate\Database\Seeder;

/**
 * Copies config/grading_uganda.php into reference_grading_bands.
 * The config file stays the source. These rows are not tied to a school.
 */
class ReferenceGradingScaleSeeder extends Seeder
{
    public function run(): void
    {
        $scales = config('grading_uganda');
        if (! is_array($scales) || $scales === []) {
            throw new \RuntimeException('config/grading_uganda.php did not return any bands.');
        }

        foreach ($scales as $level => $bands) {
            if (! is_array($bands)) {
                continue;
            }
            $position = 0;
            foreach ($bands as $band) {
                $position++;
                ReferenceUpsert::sync('reference_grading_bands', [
                    'level' => (string) $level,
                    'position' => $position,
                ], [
                    'grade' => (string) $band['grade'],
                    'points' => $band['points'],
                    'min_score' => $band['min_score'],
                    'max_score' => $band['max_score'],
                    'remark' => $band['remark'],
                ]);
            }
        }
    }
}
