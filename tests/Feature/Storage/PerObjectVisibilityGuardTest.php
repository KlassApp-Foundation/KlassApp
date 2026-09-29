<?php

namespace Tests\Feature\Storage;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Laravel Cloud object storage (Cloudflare R2) rejects per-object ACL /
 * visibility headers. First-party uploads must not pass a 'public' visibility
 * option to put()/putFile(); bucket level visibility governs access instead.
 */
class PerObjectVisibilityGuardTest extends TestCase
{
    public function test_first_party_code_never_passes_per_object_public_visibility(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = File::get($file->getPathname());

            if (preg_match_all('/[\'"]public[\'"]\s*\)/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $before = substr($contents, max(0, $match[1] - 400), 400);

                    if (preg_match('/(?:putFile|Storage::put|->put|::put)\($/', $before)
                        || preg_match('/(?:putFile|Storage::put|->put|::put)\([^;{]*$/', $before)) {
                        $line = substr_count(substr($contents, 0, $match[1]), "\n") + 1;
                        $violations[] = str_replace(base_path() . '/', '', $file->getPathname()) . ':' . $line;
                    }
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, array_merge(
            ['Per-object public visibility passed to a storage put call (R2 rejects ACL headers):'],
            $violations
        )));
    }
}
