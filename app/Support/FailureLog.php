<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Log a caught failure with the school and, when known, the import row.
 * The SQL template and the stack are included. Bound values, emails, and
 * phone numbers are not.
 */
class FailureLog
{
    public static function write(string $message, Throwable $e, int $schoolId, ?int $row = null): void
    {
        $context = [
            'school_id' => $schoolId,
            'exception' => $e::class,
            'detail' => self::detail($e),
            'trace' => self::trace($e),
        ];
        if ($row !== null) {
            $context['row'] = $row;
        }

        Log::error($message, $context);
    }

    public static function detail(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            $state = $e->errorInfo[0] ?? 'unknown';

            return 'SQLSTATE['.$state.'] SQL: '.$e->getSql();
        }

        $message = $e->getMessage();
        $message = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email]', $message) ?? $message;
        $message = preg_replace('/\+?\d{10,15}/', '[number]', $message) ?? $message;

        return $e::class.': '.$message;
    }

    public static function trace(Throwable $e): string
    {
        $lines = [$e->getFile().':'.$e->getLine()];
        foreach (array_slice($e->getTrace(), 0, 30) as $frame) {
            $file = $frame['file'] ?? '[internal]';
            $line = $frame['line'] ?? 0;
            $call = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '');
            $lines[] = $file.':'.$line.' '.$call;
        }

        return implode("\n", $lines);
    }
}
