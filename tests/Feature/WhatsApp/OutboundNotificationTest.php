<?php

namespace Tests\Feature\WhatsApp;

use App\Helpers\WhatsAppPhoneHelper;
use Tests\TestCase;

class OutboundNotificationTest extends TestCase
{
    public function test_phone_normalisation_uganda(): void
    {
        $this->assertSame('+256701234567', WhatsAppPhoneHelper::normalise('0701234567'));
        $this->assertSame('+256701234567', WhatsAppPhoneHelper::normalise('256701234567'));
        $this->assertSame('+256701234567', WhatsAppPhoneHelper::normalise('+256701234567'));
        $this->assertSame('+256701234567', WhatsAppPhoneHelper::normalise('0701 234 567'));
    }

    public function test_phone_validation_valid(): void
    {
        $this->assertTrue(WhatsAppPhoneHelper::validate('+256701234567'));
        $this->assertTrue(WhatsAppPhoneHelper::validate('+256781234567'));
        $this->assertTrue(WhatsAppPhoneHelper::validate('+256751234567'));
    }

    public function test_phone_validation_invalid(): void
    {
        $this->assertFalse(WhatsAppPhoneHelper::validate('+256601234567'));
        $this->assertFalse(WhatsAppPhoneHelper::validate('+254701234567'));
        $this->assertFalse(WhatsAppPhoneHelper::validate('123'));
    }

    public function test_format_message(): void
    {
        $message = WhatsAppPhoneHelper::formatMessage(
            'John Doe',
            'Test Results — P.5',
            ['• Math: 85/100 (A)', '• English: 72/100 (B)'],
            'Footer note',
        );

        $this->assertStringContainsString('John Doe', $message);
        $this->assertStringContainsString('Test Results', $message);
        $this->assertStringContainsString('Math', $message);
        $this->assertStringContainsString('Footer note', $message);
    }
}
