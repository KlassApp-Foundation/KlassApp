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
        $resolver = file_get_contents(base_path('app/AiAgents/ToshiLlm.php'));

        foreach (['config/ai.php' => $ai, 'config/toshi.php' => $toshi, 'ToshiLlm.php' => $resolver] as $label => $source) {
            $this->assertDoesNotMatchRegularExpression(
                "/['\"]sk-[A-Za-z0-9]{10,}['\"]/",
                $source,
                $label.' must not embed a literal LLM API key'
            );
            $this->assertDoesNotMatchRegularExpression(
                '/AKIA[A-Z0-9]{8,}/',
                $source,
                $label.' must not embed an AWS access key id'
            );
        }

        $this->assertStringContainsString("env('OPENAI_COMPATIBLE_API_KEY'", $ai);
        $this->assertStringContainsString("env('OPENAI_COMPATIBLE_API_KEY'", $toshi);
        $this->assertStringContainsString("env('AWS_BEARER_TOKEN_BEDROCK')", $ai);
        $this->assertStringContainsString("env('AWS_ACCESS_KEY_ID')", $ai);
        $this->assertStringContainsString("env('AWS_SECRET_ACCESS_KEY')", $ai);
        $this->assertStringNotContainsString("return 'openai-compatible';", $resolver);
    }
}
