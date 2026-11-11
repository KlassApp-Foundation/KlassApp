<?php

namespace App\Services\Academics;

use App\Database\ReferenceUpsert;
use App\Reference\ExamTypeCatalog;

/**
 * Puts the shared exam-type catalog in place the first time it is needed.
 *
 * The catalog rows are global (exam_types.school_id is NULL); a school sees
 * those plus its own. The reference seeder writes them, but a database that
 * never ran it — a fresh signup, a restored dump — left a school with an empty
 * list and exam-set creation with nothing to choose. This sync is idempotent:
 * a second run changes nothing, and it never touches a school's own rows.
 */
class ExamTypeCatalogSync
{
    /** @return int rows inserted or updated */
    public function syncShared(): int
    {
        $touched = 0;

        foreach (ExamTypeCatalog::all() as $type) {
            $before = $this->snapshot($type['code']);

            ReferenceUpsert::sync('exam_types', ['code' => $type['code'], 'school_id' => null], [
                'name' => $type['name'],
                'contributes_to_report_total' => $type['contributes_to_report_total'],
            ]);

            if ($before !== $this->snapshot($type['code'])) {
                $touched++;
            }
        }

        return $touched;
    }

    private function snapshot(string $code): ?string
    {
        $row = \App\Models\Academics\ExamType::query()
            ->whereNull('school_id')
            ->where('code', $code)
            ->first();

        return $row === null ? null : $row->name.'|'.(int) $row->contributes_to_report_total;
    }
}
