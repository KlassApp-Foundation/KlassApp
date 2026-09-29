<?php

namespace Tests\Feature\Queue;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Production provisions only the default managed queue. Anything pinned to a
 * named queue other than "default" would silently never be processed once
 * deployed - the password reset and Nova message senders used to dispatch to
 * an "email" queue that production does not have. This audit fails on any
 * such pin in first-party PHP code so the mistake cannot come back.
 */
class QueueDispatchAuditTest extends TestCase
{
    /** Queue names that are always safe to dispatch to. */
    private const ALLOWED = ['default'];

    public function test_first_party_code_only_dispatches_to_safe_queues(): void
    {
        $roots = array_values(array_filter(
            [app_path(), base_path('packages')],
            fn ($path) => is_dir($path)
        ));

        $violations = [];

        foreach ($roots as $root) {
            foreach (File::allFiles($root) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = File::get($file->getPathname());

                // ->onQueue('name') / ->onQueue("name")
                if (preg_match_all('/onQueue\(\s*[\'"]([^\'"]+)[\'"]/', $contents, $m, PREG_OFFSET_CAPTURE)) {
                    foreach ($m[1] as $match) {
                        if (! in_array($match[0], self::ALLOWED, true)) {
                            $violations[] = $this->describe($file->getPathname(), $contents, $match[0], $match[1]);
                        }
                    }
                }

                // protected/public $queue = 'name';
                if (preg_match_all('/\$queue\s*=\s*[\'"]([^\'"]+)[\'"]/', $contents, $m, PREG_OFFSET_CAPTURE)) {
                    foreach ($m[1] as $match) {
                        if (! in_array($match[0], self::ALLOWED, true)) {
                            $violations[] = $this->describe($file->getPathname(), $contents, $match[0], $match[1]);
                        }
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($violations)), implode(PHP_EOL, [
            'First-party code pins work to a named queue that production does not provision:',
            implode(PHP_EOL, array_unique($violations)),
            'Move it to the default queue (drop the onQueue call / the $queue property).',
        ]));
    }

    private function describe(string $path, string $contents, string $queue, int $offset): string
    {
        $line = substr_count(substr($contents, 0, $offset), "\n") + 1;

        return str_replace(base_path() . '/', '', $path) . ':' . $line . ' -> queue "' . $queue . '"';
    }
}
