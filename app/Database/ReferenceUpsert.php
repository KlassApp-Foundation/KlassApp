<?php

namespace App\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Insert or update one reference row.
 *
 * When the natural key already matches and every value column
 * already matches, the row is left untouched, including its
 * timestamps. A second seed of the same reference data is a no-op.
 */
final class ReferenceUpsert
{
    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $values
     */
    public static function sync(string $table, array $key, array $values = []): void
    {
        $query = DB::table($table);
        foreach ($key as $column => $value) {
            $query->where($column, $value);
        }

        $matches = $query->get();
        if ($matches->count() > 1) {
            throw new RuntimeException(
                "{$table} has {$matches->count()} rows for key ".json_encode($key).'. Reference seeders refuse to guess.'
            );
        }

        $now = now();

        if ($matches->isEmpty()) {
            DB::table($table)->insert($key + $values + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $existing = $matches->first();
        $dirty = [];
        foreach ($values as $column => $value) {
            $current = $existing->{$column} ?? null;
            if (! self::same($current, $value)) {
                $dirty[$column] = $value;
            }
        }

        if ($dirty === []) {
            return;
        }

        $dirty['updated_at'] = $now;
        DB::table($table)->where('id', $existing->id)->update($dirty);
    }

    public static function same(mixed $current, mixed $value): bool
    {
        if ($current === null || $value === null) {
            return $current === null && $value === null;
        }

        if (is_bool($value)) {
            return (int) $current === (int) $value;
        }

        if (is_numeric($current) && is_numeric($value)) {
            return (float) $current === (float) $value;
        }

        return (string) $current === (string) $value;
    }
}
