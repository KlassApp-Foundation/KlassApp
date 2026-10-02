<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Toshi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Soft-launch 2: existing schools must land on safe Toshi defaults.
 *
 * The user asked for an explicit backfill: every pre-existing school row
 * flips to toshi_enabled = 0 and toshi_mode = 'preview', with the count
 * of changed rows logged. Raw query-builder rows (model events bypassed)
 * simulate pre-existing production rows written before the cast/boots
 * logic shipped.
 */
class ExistingSchoolsToshiSafeDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_02_183000_backfill_existing_schools_toshi_safe_defaults.php';

    private function insertLegacySchool(string $name, int $toshiEnabled, string $toshiMode): int
    {
        return (int) DB::table('schools')->insertGetId([
            'name' => 'Legacy '.$name,
            'email' => strtolower($name).'@legacy.test.ug',
            'phone' => '0700'.random_int(100000, 999999),
            'slug' => 'legacy-'.mb_strtolower($name),
            'status' => 1,
            'toshi_enabled' => $toshiEnabled,
            'toshi_mode' => $toshiMode,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);
    }

    private function row(int $id): object
    {
        return DB::table('schools')->where('id', $id)->first();
    }

    public function test_backflips_assistant_schools_to_preview_and_disabled(): void
    {
        $assistant = $this->insertLegacySchool('assistant-school', 1, 'assistant');
        $enabledUnknown = $this->insertLegacySchool('flag-only-school', 1, 'onboarding');
        $onboarding = $this->insertLegacySchool('onboarding-school', 0, 'onboarding');
        $preview = $this->insertLegacySchool('preview-school', 0, 'preview');

        $migration = require self::MIGRATION;
        $migration->up();

        $flipped = $this->row($assistant);
        $this->assertSame(0, (int) $flipped->toshi_enabled);
        $this->assertSame('preview', (string) $flipped->toshi_mode);

        // enabled=1 wins over stored mode.
        $flagOnly = $this->row($enabledUnknown);
        $this->assertSame(0, (int) $flagOnly->toshi_enabled);
        $this->assertSame('preview', (string) $flagOnly->toshi_mode);

        $this->assertSame('onboarding', (string) $this->row($onboarding)->toshi_mode);
        $this->assertSame('preview', (string) $this->row($preview)->toshi_mode);
        $this->assertSame(0, (int) $this->row($onboarding)->toshi_enabled);
    }

    public function test_backfill_is_idempotent(): void
    {
        $a = $this->insertLegacySchool('assistant-school', 1, 'assistant');
        $b = $this->insertLegacySchool('onboarding-school', 0, 'onboarding');

        $migration = require self::MIGRATION;
        $migration->up();
        $migration->up();

        $this->assertSame('preview', (string) $this->row($a)->toshi_mode);
        $this->assertSame(0, (int) $this->row($a)->toshi_enabled);
        $this->assertSame('onboarding', (string) $this->row($b)->toshi_mode);
        $this->assertSame(0, (int) $this->row($b)->toshi_enabled);
    }
}
