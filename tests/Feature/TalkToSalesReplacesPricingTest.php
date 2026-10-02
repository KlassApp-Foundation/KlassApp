<?php

namespace Tests\Feature;

use App\Helpers\SiteHelper;
use App\Mail\SubscriptionExpiredMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalkToSalesReplacesPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_redirects_to_sales_lead_form(): void
    {
        $response = $this->get('/pricing');

        $this->assertSame(301, $response->getStatusCode());
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('source=sales', $location);
        $this->assertStringContainsString('#demo', $location);
        $this->assertStringNotContainsString('%23', $location);
    }

    public function test_talk_to_sales_url_marks_sales_source(): void
    {
        $url = SiteHelper::talkToSalesUrl();
        $this->assertStringContainsString('source=sales', $url);
        $this->assertStringContainsString('#demo', $url);
    }

    public function test_subscription_expired_mail_wires_sales_url(): void
    {
        $source = file_get_contents(base_path('app/Mail/SubscriptionExpiredMail.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('SiteHelper::talkToSalesUrl()', $source);
        $this->assertStringNotContainsString("'/pricing'", $source);
        $this->assertTrue(class_exists(SubscriptionExpiredMail::class));
    }

    public function test_overlimit_flash_cta_is_talk_to_sales(): void
    {
        $source = file_get_contents(resource_path('views/partials/message.blade.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('Talk to sales', $source);
        $this->assertStringContainsString('talkToSalesUrl()', $source);
        $this->assertStringNotContainsString('href="/pricing"', $source);
    }
}
