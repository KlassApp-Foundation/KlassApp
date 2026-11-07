<?php

namespace Tests\Feature\Teacher;

use Tests\TestCase;

/**
 * Chrome check that Mark all present flips the visible radios and does not
 * save until Save attendance. Skips where Chrome is not installed. CI without
 * Chrome still runs the HTTP register tests.
 */
class MarkAllPresentBrowserTest extends TestCase
{
    public function test_mark_all_present_flips_visible_radios_without_saving(): void
    {
        $chrome = getenv('CHROME_PATH') ?: '/opt/google/chrome/chrome';
        // The full-suite job allows 45 minutes. This check boots a server and
        // Chrome, so it stays out of that job. Run it directly when Chrome is installed.
        if (getenv('GITHUB_ACTIONS') === 'true' || ! is_executable($chrome)) {
            $this->markTestSkipped('Chrome register check runs outside the full-suite job.');
        }

        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('tests/Browser/mark-all-present.php'));
        $output = [];
        $exit = 0;
        exec($command.' 2>&1', $output, $exit);
        $text = implode("\n", $output);
        $this->assertSame(0, $exit, $text);
        $this->assertStringContainsString('mark-all-present browser check passed', $text);
    }
}
