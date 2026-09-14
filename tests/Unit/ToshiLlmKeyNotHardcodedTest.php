<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ToshiLlmKeyNotHardcodedTest extends TestCase
{
    #[Test]
    public function ai_and_toshi_config_do_not_ship_hardcoded_api_keys(): void
    {
        $ai = file_get_contents(base_path('config/ai.php'));
        $toshi = file_get_contents(base_path('config/toshi.php'));

        $this->assertDoesNotMatchRegularExpression(
            "/['\"]sk-[A-Za-z0-9]{10,}['\"]/",
            $ai,
            'config/ai.php must not embed a literal LLM API key'
        );
        $this->assertDoesNotMatchRegularExpression(
            "/['\"]sk-[A-Za-z0-9]{10,}['\"]/",
            $toshi,
            'config/toshi.php must not embed a literal LLM API key'
        );
        $this->assertStringContainsString("env('OPENAI_COMPATIBLE_API_KEY'", $ai);
        $this->assertStringContainsString("env('OPENAI_COMPATIBLE_API_KEY'", $toshi);
    }
}
