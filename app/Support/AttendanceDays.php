<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Attendance is one row per session (morning and afternoon).
 * A date with both sessions present is one day. One present session
 * out of the two is a half day.
 */
final class AttendanceDays
{
    /**
     * @param  iterable<int, object|array<string, mixed>>  $rows
     */
    public static function present(iterable $rows): float
    {
        $byDate = [];
        foreach ($rows as $row) {
            $date = $row instanceof \Illuminate\Database\Eloquent\Model || is_object($row)
                ? ($row->date ?? null)
                : ($row['date'] ?? null);
            $status = $row instanceof \Illuminate\Database\Eloquent\Model || is_object($row)
                ? ($row->status ?? null)
                : ($row['status'] ?? null);
            if ($date === null) {
                continue;
            }
            $key = Carbon::parse($date)->toDateString();
            $byDate[$key] ??= 0;
            if (in_array((int) $status, [1, 2], true)) {
                $byDate[$key]++;
            }
        }

        $total = 0.0;
        foreach ($byDate as $presentSessions) {
            if ($presentSessions >= 2) {
                $total += 1.0;
            } elseif ($presentSessions === 1) {
                $total += 0.5;
            }
        }

        return $total;
    }

    public static function phrase(float $days): string
    {
        $whole = (int) floor($days + 0.001);
        $half = abs($days - $whole - 0.5) < 0.01;

        if ($half && $whole === 0) {
            return '½ day present';
        }
        if ($half) {
            return $whole.'½ days present';
        }
        if ((int) round($days) === 1) {
            return '1 day present';
        }

        return ((int) round($days)).' days present';
    }

    public static function mark(float $days): string
    {
        $phrase = self::phrase($days);

        return trim(str_replace([' days present', ' day present'], '', $phrase));
    }

    /**
     * @param  Collection<int, mixed>  $rows
     */
    public static function fromCollection(Collection $rows): float
    {
        return self::present($rows);
    }
}
