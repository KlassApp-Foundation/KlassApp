<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Contract for <x-ka-icon> (design/system handoff-2026-10-01-icons, PR I1).
 */
namespace Tests\Feature\DesignSystem;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class KaIconComponentTest extends TestCase
{
    public function test_default_icon_is_a_20px_stroke_2_decorative_current_color_svg(): void
    {
        $html = Blade::render('<x-ka-icon name="house" />');

        $this->assertStringStartsWith('<svg', trim($html));
        $this->assertStringContainsString('width="20"', $html);
        $this->assertStringContainsString('height="20"', $html);
        $this->assertStringContainsString('stroke-width="2"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('focusable="false"', $html);
        $this->assertStringContainsString('stroke="currentColor"', $html);
        $this->assertStringContainsString('ka-icon--default', $html);
    }

    public function test_size_24_uses_stroke_1_75_and_size_16_keeps_stroke_2(): void
    {
        $big = Blade::render('<x-ka-icon name="house" size="24" />');
        $this->assertStringContainsString('width="24"', $big);
        $this->assertStringContainsString('stroke-width="1.75"', $big);

        $small = Blade::render('<x-ka-icon name="house" size="16" />');
        $this->assertStringContainsString('width="16"', $small);
        $this->assertStringContainsString('stroke-width="2"', $small);
    }

    public function test_unknown_name_renders_info_and_warns_outside_production(): void
    {
        Log::spy();

        $unknown = Blade::render('<x-ka-icon name="definitely-not-a-lucide-icon" />');
        $info = Blade::render('<x-ka-icon name="info" />');

        $strip = fn (string $svg) => preg_replace('/\s+/', '', $svg);
        $this->assertSame($strip($info), $strip($unknown));
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_tone_picks_a_token_class_and_caller_attributes_pass_through(): void
    {
        $html = Blade::render('<x-ka-icon name="triangle-alert" tone="warning" class="extra" />');

        $this->assertStringContainsString('ka-icon--warning', $html);
        $this->assertStringContainsString('extra', $html);
    }
}
